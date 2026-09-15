<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\adminapi\http\middleware;

use app\common\{
    cache\AdminAuthCache,
    service\JsonService,
    service\StaffService
};
use think\helper\Str;

/**
 * 权限验证中间件
 * Class AuthMiddleware
 * @package app\adminapi\http\middleware
 */
class AuthMiddleware
{
    /**
     * @notes 权限验证
     * @param $request
     * @param \Closure $next
     * @return mixed
     * @author 令狐冲
     * @date 2021/7/2 19:29
     */
    public function handle($request, \Closure $next)
    {
        //不登录访问，无需权限验证
        if ($request->controllerObject->isNotNeedLogin()) {
            return $next($request);
        }

        if ($this->isLoginIpCheckEnabled()
            && $request->adminInfo['login_ip'] != request()->ip()
        ) {
            return JsonService::fail('ip地址发生变化，请重新登录', [], -1);
        }

        // 当前访问路径
        $accessUri = strtolower($request->controller() . '/' . $request->action());

        if ((int)($request->adminInfo['force_password_reset'] ?? 0) === 1
            && !$this->isForcePasswordResetAllowed($accessUri)
        ) {
            return JsonService::fail('当前账号必须先重置密码', ['force_password_reset' => 1]);
        }

        //系统默认超级管理员，无需权限验证
        if (1 === $request->adminInfo['root']) {
            return $next($request);
        }

        if ($this->isPublicPermission($accessUri)) {
            return $next($request);
        }
        $adminAuthCache = new AdminAuthCache($request->adminInfo['admin_id']);

        if ($this->isStaffSelfServicePermission($accessUri, $request->adminInfo ?? [])) {
            return $next($request);
        }

        // 全部路由
        $allUri = $this->formatUrl($adminAuthCache->getAllUri());

        // 未登记的接口默认拒绝，不能将菜单漏配或停用解释为公开访问。
        if (!in_array($accessUri, $allUri)) {
            return JsonService::fail('权限未登记，无法访问或操作');
        }

        // 当前管理员拥有的路由权限
        $AdminUris = $adminAuthCache->getAdminUri() ?? [];
        $AdminUris = $this->formatUrl($AdminUris);

        if (in_array($accessUri, $AdminUris)) {
            return $next($request);
        }
        return JsonService::fail('权限不足，无法访问或操作');
    }

    /**
     * @notes 是否开启后台登录 IP 变化检测
     */
    protected function isLoginIpCheckEnabled(): bool
    {
        $value = env('admin.check_login_ip', null);

        if (is_null($value)) {
            $value = env('admin_check_login_ip', null);
        }

        if (is_null($value)) {
            $value = env('admin.admin_check_login_ip', null);
        }

        if (is_null($value)) {
            $value = env('app.admin_check_login_ip', 1);
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string)$value, " \t\n\r\0\x0B\"'"));

        return !in_array($normalized, ['0', 'false', 'off', 'no'], true);
    }

    /**
     * @notes staff 自助资料接口放行（仍要求后台账号已绑定 staff 档案）
     */
    protected function isStaffSelfServicePermission(string $accessUri, array $adminInfo): bool
    {
        $selfServiceUris = [
            'ops.staff/myProfile',
            'ops.staff/myProfileUpdate',
            'ops.staff/myProfilePackageConfig',
            'ops.staff/myProfileConfigurePackages',
            'ops.staff/myProfileUpdatePackageConfig',
            'ops.staff/myProfileCreatePackage',
            'ops.staff/myProfileUpdateStaffPackage',
            'ops.staff/myProfileDeletePackage',
            'ops.staff/myProfileAddonList',
            'ops.staff/myProfileAddonAdd',
            'ops.staff/myProfileAddonUpdate',
            'ops.staff/myProfileAddonDelete',
            'ops.staff/myProfileRegionEnabledCityOptions',
            'ops.staff/myProfileRegionDistrictOptions',
            'ops.region/enabledCityOptions',
            'ops.region/districtOptions',
            'ops.staff/myProfileBannerList',
            'ops.staff/myProfileBannerAdd',
            'ops.staff/myProfileBannerEdit',
            'ops.staff/myProfileBannerDelete',
            'ops.staff/myProfileBannerSort',
            'ops.staff/myProfileBannerConfig',
            'ops.staff/getAddonConfig',
            'ops.staff/myCoupleQuestionnaireConfig',
            'ops.staff/myCoupleQuestionnaireSave',
            'ops.staff/myCoupleQuestionnairePublish',
            'ops.staff/myCoupleQuestionnaireTasks',
            'ops.staff/myCoupleQuestionnaireTaskDetail',
            'ops.staff/myCoupleQuestionnaireSend',
            'ops.staffWork/lists',
            'ops.staffWork/detail',
            'ops.staffWork/add',
            'ops.staffWork/edit',
            'ops.staffWork/delete',
            'ops.staffWork/batchDelete',
            'ops.staffWork/changeStatus',
            'ops.staffWork/setCover',
            'ops.staffCertificate/lists',
            'ops.staffCertificate/detail',
            'ops.staffCertificate/add',
            'ops.staffCertificate/edit',
            'ops.staffCertificate/delete',
            'ops.staffCertificate/batchDelete',
            'ops.staff/myScheduleConfirmLetterConfig',
            'ops.staff/myScheduleConfirmLetterSave',
            'ops.staff/myScheduleConfirmLetterPreview',
            'ops.staff/myScheduleConfirmLetterCopy',
            'ops.staff/myScheduleConfirmLetterSetDefault',
            'ops.staff/myScheduleConfirmLetterDisable',
            'ops.staff/myScheduleConfirmLetterGenerate',
            'ops.staff/myScheduleConfirmLetterHistory',
            'ops.order/offlineMainPackages',
            'ops.order/offlineRoleCandidates',
            'ops.order/estimateOffline',
            'ops.order/addOffline',
            'ops.order/customerOptions',
            'ops.order/myOrderConfirm',
            'ops.order/myOrderStartService',
            'ops.order/myOrderComplete',
            'ops.order/myOrderDirectReschedule',
            'ops.schedule/myCalendarBatchSet',
            'ops.schedule/myCalendarSetStatus',
            'ops.schedule/myCalendarUnlock',
            'ops.scheduleRule/myRuleSave',
            'ops.scheduleRule/myRuleDelete',
            'ops.scheduleRule/myRuleChangeStatus',
            'ops.booking/myBookingCancel',
            'ops.booking/myBookingConfirm',
            'ops.waitlist/myWaitlistBatchNotify',
            'ops.waitlist/myWaitlistConvert',
            'ops.waitlist/myWaitlistInvalidate',
            'ops.waitlist/myWaitlistNotify',
            'growth.dynamic/myDynamicAdd',
            'growth.dynamic/myDynamicEdit',
            'growth.dynamic/myDynamicDelete',
        ];

        $leaderServiceUris = [
            'ops.staff/myTeamSummary',
            'ops.staff/myTeamMembers',
            'ops.staff/myTeamMemberDetail',
            'ops.staff/myTeamMemberUpdate',
            'ops.staffWork/audit',
            'ops.staffWork/batchAudit',
            'ops.staffCertificate/audit',
            'ops.staffCertificate/batchAudit',
            'ops.staffTagReview/lists',
            'ops.staffTagReview/detail',
            'ops.staffTagReview/approve',
            'ops.staffTagReview/reject',
            'ops.staffTagReview/batchApprove',
            'ops.staffTagReview/batchReject',
            'growth.dynamic/lists',
            'growth.dynamic/detail',
            'growth.dynamic/audit',
            'growth.dynamic/typeOptions',
            'growth.dynamic/statusOptions',
        ];

        if (!in_array($accessUri, array_map(fn ($item) => strtolower(Str::camel($item)), $selfServiceUris), true)) {
            if (!in_array($accessUri, array_map(fn ($item) => strtolower(Str::camel($item)), $leaderServiceUris), true)) {
                return false;
            }
            return StaffService::isStaffTeamLeaderByAdminId((int)($adminInfo['admin_id'] ?? 0), $adminInfo);
        }

        return StaffService::getStaffScopeId((int)($adminInfo['admin_id'] ?? 0), $adminInfo) > 0;
    }

    protected function isForcePasswordResetAllowed(string $accessUri): bool
    {
        $allowedUris = [
            'auth.admin/mySelf',
            'auth.admin/editSelf',
            'login/logout',
        ];

        return in_array($accessUri, array_map(fn ($item) => strtolower(Str::camel($item)), $allowedUris), true);
    }

    /** 仅放行操作当前账号自身或按当前账号过滤的基础入口。 */
    protected function isPublicPermission(string $accessUri): bool
    {
        return $this->isForcePasswordResetAllowed($accessUri) || in_array($accessUri, [
            'auth.admin/bindingentry', 'auth.admin/bindingconfirm', 'auth.admin/bindingrevoke',
            'auth.menu/route',
            'file/lists', 'file/listcate', 'file/addcate', 'file/editcate', 'file/delcate',
            'file/delete', 'file/move', 'file/rename', 'content.material/listcate',
            'upload/image', 'upload/video', 'upload/file',
        ], true);
    }



    /**
     * @notes 格式化URL
     * @param array $data
     * @return array|string[]
     * @author 段誉
     * @date 2022/7/7 15:39
     */
    public function formatUrl(array $data)
    {
        return array_map(function ($item) {
            return strtolower(Str::camel($item));
        }, $data);
    }

}
