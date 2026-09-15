<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\auth\Admin;
use app\common\model\auth\AdminSession;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use think\facade\Db;

/** 平台工作身份只能通过此入口变更，手机号不是身份凭据。 */
class AccountBindingService
{
    public static function assertManage(int $operatorId): void
    {
        $operator = Admin::where('id', $operatorId)->where('disable', 0)->find();
        if (!$operator) throw new \RuntimeException('操作管理员不可用');
        if ((int)$operator->root === 1) return;
        $permissions = array_map('strtolower', (new \app\common\cache\AdminAuthCache($operatorId))->getAdminUri() ?: []);
        if (!in_array('auth.admin/bindingsave', $permissions, true)) throw new \RuntimeException('缺少账号绑定管理权限');
    }

    public static function bind(int $adminId, int $userId, int $operatorId, string $reason, int $staffId = 0): array
    {
        if ($adminId <= 0 || $userId < 0 || $staffId < 0) throw new \InvalidArgumentException('账号参数不合法');
        if (trim($reason) === '') throw new \RuntimeException('请填写绑定操作原因');
        return Db::transaction(function () use ($adminId, $userId, $operatorId, $reason, $staffId) {
            $admin = Admin::where('id', $adminId)->lock(true)->find();
            if (!$admin) throw new \RuntimeException('后台账号不存在');
            if ((int)$admin->root === 1 && !(int)Admin::where('id', $operatorId)->value('root')) {
                throw new \RuntimeException('只有超级管理员可以管理超级管理员的账号关联');
            }
            $staff = $staffId > 0 ? Staff::where('id', $staffId)->lock(true)->find()
                : Staff::where('admin_id', $adminId)->lock(true)->find();
            if ($staffId > 0 && !$staff) throw new \RuntimeException('服务人员档案不存在');
            if ($staff && (int)$staff->admin_id > 0 && (int)$staff->admin_id !== $adminId) {
                throw new \RuntimeException('档案已关联其他后台账号，请先核实');
            }
            if (Staff::where('admin_id', $adminId)->where('id', '<>', (int)($staff->id ?? 0))->find()) {
                throw new \RuntimeException('后台账号已关联其他服务人员档案');
            }
            if ($userId > 0) {
                if (!User::where('id', $userId)->where('is_disable', 0)->lock(true)->find()) {
                    throw new \RuntimeException('平台用户不存在或已停用');
                }
                if (Admin::where('user_id', $userId)->where('id', '<>', $adminId)->find()
                    || Staff::where('user_id', $userId)->where('id', '<>', (int)($staff->id ?? 0))->find()) {
                    throw new \RuntimeException('平台用户已关联其他工作账号或档案');
                }
            }
            $oldUserId = (int)$admin->user_id;
            $oldStaffUserId = (int)($staff->user_id ?? 0);
            $admin->user_id = $userId ?: null;
            if (!$userId && $staff) $admin->disable = 1;
            $admin->save();
            if ($staff) {
                $staff->user_id = $userId ?: null;
                $staff->admin_id = $adminId;
                if (!$userId) $staff->status = 0;
                $staff->save();
            }
            Db::name('wechat_oa_bind_session')->where('admin_id', $adminId)->where('status', 0)
                ->update(['status' => 2, 'expires_time' => time(), 'update_time' => time()]);
            foreach (array_unique([$oldUserId, $oldStaffUserId]) as $previousId) {
                if ($previousId <= 0 || $previousId === $userId) continue;
                Db::name('notification')->where('user_id', $previousId)->where('audience', '<>', 'user')
                    ->update(['identity_revoked' => 1]);
                Db::name('notification_event')->where('user_id', $previousId)->where('audience', '<>', 'user')->whereIn('status', [0, 3])
                    ->update(['status' => 2, 'error' => '工作账号关联已撤销', 'update_time' => time()]);
                Db::name('wechat_oa_notification_log')->where('user_id', $previousId)->where('audience', 'staff')
                    ->whereIn('send_status', [0, 3])->update(['send_status' => 4, 'error_msg' => '工作账号关联已撤销', 'lock_token' => '', 'lock_until' => 0]);
            }
            Db::name('account_binding_audit')->insert([
                'admin_id' => $adminId, 'staff_id' => (int)($staff->id ?? 0), 'old_user_id' => $oldUserId,
                'new_user_id' => $userId, 'operator_id' => $operatorId, 'reason' => mb_substr(trim($reason), 0, 500),
                'create_time' => time(),
            ]);
            self::expireSessions($adminId);
            return self::detail($adminId);
        });
    }

    public static function expireSessions(int $adminId): void
    {
        foreach (AdminSession::where('admin_id', $adminId)->column('token') as $token) {
            \app\adminapi\logic\auth\AdminLogic::expireToken($token);
        }
        (new \app\common\cache\AdminAuthCache($adminId))->clearAuthCache();
    }

    public static function stopPendingWork(int $adminId): void
    {
        $userId = (int)Admin::where('id', $adminId)->value('user_id');
        if ($userId > 0) {
            Db::name('notification_event')->where('user_id', $userId)->where('audience', '<>', 'user')->whereIn('status', [0, 3])
                ->update(['status' => 2, 'error' => '后台账号已停用', 'update_time' => time()]);
            Db::name('wechat_oa_notification_log')->where('user_id', $userId)->where('audience', 'staff')->whereIn('send_status', [0, 3])
                ->update(['send_status' => 4, 'error_msg' => '后台账号已停用', 'lock_token' => '', 'lock_until' => 0]);
        }
        self::expireSessions($adminId);
    }

    public static function detail(int $adminId): array
    {
        $admin = Admin::find($adminId);
        if (!$admin) throw new \RuntimeException('后台账号不存在');
        $userId = (int)$admin->user_id;
        $user = $userId ? User::find($userId) : null;
        $mobile = $user ? (string)$user->getData('mobile') : '';
        return ['admin_id' => $adminId, 'account' => $admin->account, 'disable' => (int)$admin->disable,
            'user_id' => $userId, 'nickname' => (string)($user->nickname ?? ''),
            'mobile' => preg_replace('/^(\d{3})\d{4}(\d+)$/', '$1****$2', $mobile),
            'audit' => Db::name('account_binding_audit')->where('admin_id', $adminId)->order('id desc')->limit(10)
                ->field('old_user_id,new_user_id,operator_id,reason,create_time')->select()->toArray(),
            'oa_status' => $userId ? \app\common\service\wechat\WechatOaBindingService::getStatus($userId) : null];
    }
}
