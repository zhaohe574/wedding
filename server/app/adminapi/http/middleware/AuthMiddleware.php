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

        if ($request->adminInfo['login_ip'] != request()->ip()) {
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

        $adminAuthCache = new AdminAuthCache($request->adminInfo['admin_id']);

        if ($this->isStaffSelfServicePermission($accessUri, $request->adminInfo ?? [])) {
            return $next($request);
        }

        // 全部路由
        $allUri = $this->formatUrl($adminAuthCache->getAllUri());

        // 判断该当前访问的uri是否存在，不存在无需验证
        if (!in_array($accessUri, $allUri)) {
            if ($this->isSensitiveUnregisteredPermission($accessUri)) {
                return JsonService::fail('权限未登记，无法访问或操作');
            }
            return $next($request);
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
            'ops.staff/myProfileBannerList',
            'ops.staff/myProfileBannerAdd',
            'ops.staff/myProfileBannerEdit',
            'ops.staff/myProfileBannerDelete',
            'ops.staff/myProfileBannerSort',
            'ops.staff/myProfileBannerConfig',
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
            'ops.staffWork/changeStatus',
            'ops.staffWork/setCover',
            'ops.staffCertificate/lists',
            'ops.staffCertificate/detail',
            'ops.staffCertificate/add',
            'ops.staffCertificate/edit',
            'ops.staffCertificate/delete',
            'ops.staff/myScheduleConfirmLetterConfig',
            'ops.staff/myScheduleConfirmLetterSave',
            'ops.staff/myScheduleConfirmLetterPreview',
            'ops.staff/myScheduleConfirmLetterCopy',
            'ops.staff/myScheduleConfirmLetterSetDefault',
            'ops.staff/myScheduleConfirmLetterDisable',
            'ops.staff/myScheduleConfirmLetterGenerate',
            'ops.staff/myScheduleConfirmLetterHistory',
        ];

        $leaderServiceUris = [
            'ops.staff/myTeamSummary',
            'ops.staff/myTeamMembers',
            'ops.staff/myTeamMemberDetail',
            'ops.staff/myTeamMemberUpdate',
            'ops.staffWork/audit',
            'ops.staffCertificate/audit',
            'ops.staffTagReview/lists',
            'ops.staffTagReview/detail',
            'ops.staffTagReview/approve',
            'ops.staffTagReview/reject',
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

    /**
     * @notes 敏感后台命名空间必须显式登记权限，避免漏配接口被普通登录账号绕过。
     */
    protected function isSensitiveUnregisteredPermission(string $accessUri): bool
    {
        $sensitivePrefixes = [
            'order.',
            'finance.',
            'financial.',
            'recharge.',
            'dynamic.',
            'growth.dynamic',
            'pay.',
            'tools.generator',
            'ops.staff',
            'ops.staffwork',
            'ops.staffcertificate',
            'staff.',
            'schedule.',
            'setting.pay',
        ];

        foreach ($sensitivePrefixes as $prefix) {
            if (str_starts_with($accessUri, strtolower($prefix))) {
                return true;
            }
        }

        return false;
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
