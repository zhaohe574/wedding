<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\auth\AdminRole;
use app\common\model\auth\SystemRole;
use app\common\model\staff\Staff;
use app\common\model\user\User;

/**
 * 服务人员通用服务
 */
class StaffService
{
    protected static ?int $staffRoleId = null;
    protected static ?array $staffRoleIds = null;
    protected static array $staffScopeIdCache = [];

    /**
     * @notes 获取主服务人员角色ID（用于新建后台账号绑定）
     */
    public static function getStaffRoleId(): int
    {
        if (self::$staffRoleId !== null) {
            return self::$staffRoleId;
        }
        $roleId = SystemRole::where('name', '服务人员')
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->value('id');
        self::$staffRoleId = $roleId ? (int)$roleId : 0;
        return self::$staffRoleId;
    }

    /**
     * @notes 获取全部有效的服务人员角色ID
     */
    public static function getStaffRoleIds(): array
    {
        if (self::$staffRoleIds !== null) {
            return self::$staffRoleIds;
        }

        $roleIds = SystemRole::where('name', '服务人员')
            ->whereNull('delete_time')
            ->order('id', 'asc')
            ->column('id');

        self::$staffRoleIds = array_values(array_unique(array_map('intval', $roleIds)));
        return self::$staffRoleIds;
    }

    /**
     * @notes 判断管理员ID是否具备任一服务人员角色
     */
    public static function hasStaffRoleByAdminId(int $adminId): bool
    {
        if ($adminId <= 0) {
            return false;
        }

        $staffRoleIds = self::getStaffRoleIds();
        if (empty($staffRoleIds)) {
            return false;
        }

        return AdminRole::where('admin_id', $adminId)
            ->whereIn('role_id', $staffRoleIds)
            ->count() > 0;
    }

    /**
     * @notes 判断是否服务人员角色
     */
    public static function isStaffRole(array $adminInfo): bool
    {
        if (($adminInfo['root'] ?? 0) == 1) {
            return false;
        }

        $staffRoleIds = self::getStaffRoleIds();
        if (empty($staffRoleIds)) {
            return false;
        }

        $roleIds = array_map('intval', $adminInfo['role_id'] ?? []);
        return !empty(array_intersect($staffRoleIds, $roleIds));
    }

    /**
     * @notes 获取服务人员ID（按管理员ID）
     */
    public static function getStaffIdByAdminId(int $adminId): int
    {
        if ($adminId <= 0) {
            return 0;
        }

        return (int)Staff::where('admin_id', $adminId)
            ->whereNull('delete_time')
            ->value('id');
    }

    /**
     * @notes 获取服务人员ID（按用户ID）
     */
    public static function getStaffIdByUserId(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        return (int)Staff::where('user_id', $userId)
            ->whereNull('delete_time')
            ->value('id');
    }

    /**
     * @notes 获取服务人员范围缺失时的提示文案
     */
    public static function getStaffScopeAccessDeniedMessage(array $adminInfo): string
    {
        return self::isStaffRole($adminInfo)
            ? '当前后台账号未关联服务人员档案，请联系管理员处理'
            : '无权限操作';
    }

    /**
     * @notes 获取当前管理员可访问的服务人员ID（0=不限制）
     */
    public static function getStaffScopeId(int $adminId, array $adminInfo): int
    {
        if (!self::isStaffRole($adminInfo)) {
            return 0;
        }

        if (isset(self::$staffScopeIdCache[$adminId])) {
            return self::$staffScopeIdCache[$adminId];
        }

        $staffId = self::getStaffIdByAdminId($adminId);
        if ($staffId <= 0) {
            $staffId = self::getStaffIdByAdminAccount((string)($adminInfo['account'] ?? ''));
        }

        self::$staffScopeIdCache[$adminId] = $staffId > 0 ? $staffId : 0;
        return self::$staffScopeIdCache[$adminId];
    }

    /**
     * @notes 按后台账号兼容反查服务人员ID
     */
    protected static function getStaffIdByAdminAccount(string $account): int
    {
        $account = trim($account);
        if ($account === '') {
            return 0;
        }

        $staffId = self::matchUniqueStaffIdByUserField('mobile', $account);
        if ($staffId !== null) {
            return $staffId;
        }

        return self::matchUniqueStaffIdByUserField('account', $account) ?? 0;
    }

    /**
     * @notes 通过用户字段唯一匹配服务人员ID
     * @return int|null null=当前字段未命中，0=命中不唯一，正整数=唯一 staff_id
     */
    protected static function matchUniqueStaffIdByUserField(string $field, string $value): ?int
    {
        $userIds = User::where($field, $value)
            ->whereNull('delete_time')
            ->column('id');

        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return null;
        }

        $staffIds = Staff::whereIn('user_id', $userIds)
            ->whereNull('delete_time')
            ->column('id');

        $staffIds = array_values(array_unique(array_map('intval', $staffIds)));
        if (empty($staffIds)) {
            return null;
        }

        if (count($staffIds) !== 1) {
            return 0;
        }

        return $staffIds[0];
    }
}
