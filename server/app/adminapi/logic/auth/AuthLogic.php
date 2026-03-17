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

namespace app\adminapi\logic\auth;

use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;
use app\common\model\auth\SystemMenu;
use app\common\model\auth\SystemRoleMenu;


/**
 * 权限功能类
 * Class AuthLogic
 * @package app\adminapi\logic\auth
 */
class AuthLogic
{
    /**
     * @notes 服务人员中心“我的资料”权限兼容映射
     * @return array<string, string[]>
     */
    private static function exactPermissionAliasMap(): array
    {
        return [
            'staff.staff/lists' => [
                'staff.staff/myProfile',
                'staff.staff/myProfileUpdate',
                'staff.staff/myProfilePackageConfig',
                'staff.staff/myProfileUpdatePackageConfig',
                'staff.staff/myProfileCreatePackage',
                'staff.staff/myProfileUpdateStaffPackage',
                'staff.staff/myProfileDeletePackage',
                'staff.staff/myProfileBannerList',
                'staff.staff/myProfileBannerAdd',
                'staff.staff/myProfileBannerEdit',
                'staff.staff/myProfileBannerDelete',
                'staff.staff/myProfileBannerSort',
                'staff.staff/myProfileBannerConfig',
                'ops.staff/myProfile',
                'ops.staff/myProfileUpdate',
                'ops.staff/myProfilePackageConfig',
                'ops.staff/myProfileUpdatePackageConfig',
                'ops.staff/myProfileCreatePackage',
                'ops.staff/myProfileUpdateStaffPackage',
                'ops.staff/myProfileDeletePackage',
                'ops.staff/myProfileBannerList',
                'ops.staff/myProfileBannerAdd',
                'ops.staff/myProfileBannerEdit',
                'ops.staff/myProfileBannerDelete',
                'ops.staff/myProfileBannerSort',
                'ops.staff/myProfileBannerConfig',
            ],
            'ops.staff/lists' => [
                'staff.staff/myProfile',
                'staff.staff/myProfileUpdate',
                'staff.staff/myProfilePackageConfig',
                'staff.staff/myProfileUpdatePackageConfig',
                'staff.staff/myProfileCreatePackage',
                'staff.staff/myProfileUpdateStaffPackage',
                'staff.staff/myProfileDeletePackage',
                'staff.staff/myProfileBannerList',
                'staff.staff/myProfileBannerAdd',
                'staff.staff/myProfileBannerEdit',
                'staff.staff/myProfileBannerDelete',
                'staff.staff/myProfileBannerSort',
                'staff.staff/myProfileBannerConfig',
                'ops.staff/myProfile',
                'ops.staff/myProfileUpdate',
                'ops.staff/myProfilePackageConfig',
                'ops.staff/myProfileUpdatePackageConfig',
                'ops.staff/myProfileCreatePackage',
                'ops.staff/myProfileUpdateStaffPackage',
                'ops.staff/myProfileDeletePackage',
                'ops.staff/myProfileBannerList',
                'ops.staff/myProfileBannerAdd',
                'ops.staff/myProfileBannerEdit',
                'ops.staff/myProfileBannerDelete',
                'ops.staff/myProfileBannerSort',
                'ops.staff/myProfileBannerConfig',
            ],
        ];
    }

    /**
     * @notes 权限前缀迁移映射（旧 => 新）
     * @return array<string, string>
     */
    private static function permissionAliasMap(): array
    {
        return [
            'staff.staff/' => 'ops.staff/',
            'staff.staffWork/' => 'ops.staffWork/',
            'staff.staffCertificate/' => 'ops.staffCertificate/',
            'staff.work/' => 'ops.work/',
            'service.category/' => 'ops.category/',
            'service.package/' => 'ops.package/',
            'service.service_category/' => 'ops.category/',
            'service.service_package/' => 'ops.package/',
            'service.styleTag/' => 'ops.styleTag/',
            'service.style_tag/' => 'ops.styleTag/',
            'schedule.schedule/' => 'ops.schedule/',
            'schedule.scheduleRule/' => 'ops.scheduleRule/',
            'schedule.booking/' => 'ops.booking/',
            'schedule.waitlist/' => 'ops.waitlist/',
            'schedule.calendarEvent/' => 'ops.calendarEvent/',
            'order.order/' => 'ops.order/',
            'order.orderChange/' => 'ops.orderChange/',
            'order.order_change/' => 'ops.orderChange/',
            'order.orderTransfer/' => 'ops.orderTransfer/',
            'order.order_transfer/' => 'ops.orderTransfer/',
            'order.orderPause/' => 'ops.orderPause/',
            'order.order_pause/' => 'ops.orderPause/',
            'order.refund/' => 'ops.refund/',
            'order.payment/' => 'ops.payment/',
            'aftersale.aftersale/' => 'ops.aftersaleTicket/',
            'aftersale.complaint/' => 'ops.complaint/',
            'aftersale.reshoot/' => 'ops.reshoot/',
            'aftersale.callback/' => 'ops.callback/',
            'crm.customer/' => 'growth.customer/',
            'crm.sales_advisor/' => 'growth.advisor/',
            'crm.salesAdvisor/' => 'growth.advisor/',
            'crm.customer_loss_warning/' => 'growth.lossWarning/',
            'crm.customerLossWarning/' => 'growth.lossWarning/',
            'crm.followRecord/' => 'growth.followRecord/',
            'dynamic.dynamic/' => 'growth.dynamic/',
            'dynamic.dynamicComment/' => 'growth.dynamicComment/',
            'review.review/' => 'growth.review/',
            'review.reviewAppeal/' => 'growth.reviewAppeal/',
            'review.review_appeal/' => 'growth.reviewAppeal/',
            'review.reviewTag/' => 'growth.reviewTag/',
            'review.review_tag/' => 'growth.reviewTag/',
            'review.sensitiveWord/' => 'growth.sensitiveWord/',
            'review.sensitive_word/' => 'growth.sensitiveWord/',
            'notification.notification/' => 'growth.notification/',
            'subscribe.subscribe/' => 'growth.subscribe/',
            'timeline.timeline/' => 'growth.timeline/',
            'financial.' => 'finance.',
            'finance.account_log/' => 'finance.accountLog/',
            'recharge.recharge/' => 'finance.recharge/',
            'user.user/' => 'content.user/',
            'article.article/' => 'content.article/',
            'article.article_cate/' => 'content.articleCategory/',
            'article.articleCate/' => 'content.articleCategory/',
            'file/listCate' => 'content.material/listCate',
            'channel.' => 'experience.channel.',
            'decorate.' => 'experience.decorate.',
        ];
    }

    /**
     * @notes 兼容新旧权限前缀，返回双轨权限集合
     * @param array $permissions
     * @return array
     */
    private static function expandPermissionAliases(array $permissions): array
    {
        $results = [];
        $map = self::permissionAliasMap();
        $exactMap = self::exactPermissionAliasMap();

        foreach ($permissions as $permission) {
            if (empty($permission) || !is_string($permission)) {
                continue;
            }
            $results[$permission] = true;

            foreach (($exactMap[$permission] ?? []) as $aliasPermission) {
                $results[$aliasPermission] = true;
            }

            foreach ($map as $oldPrefix => $newPrefix) {
                if (str_starts_with($permission, $oldPrefix)) {
                    $alias = $newPrefix . substr($permission, strlen($oldPrefix));
                    $results[$alias] = true;
                }
                if (str_starts_with($permission, $newPrefix)) {
                    $alias = $oldPrefix . substr($permission, strlen($newPrefix));
                    $results[$alias] = true;
                }
            }
        }

        return array_values(array_keys($results));
    }


    /**
     * @notes 获取全部权限
     * @return mixed
     * @author 段誉
     * @date 2022/7/1 11:55
     */
    public static function getAllAuth()
    {
        $permissions = SystemMenu::distinct(true)
            ->where([
                ['is_disable', '=', 0],
                ['perms', '<>', '']
            ])
            ->column('perms');

        return self::expandPermissionAliases($permissions);
    }


    /**
     * @notes 获取当前管理员角色按钮权限
     * @param $roleId
     * @return mixed
     * @author 段誉
     * @date 2022/7/1 16:10
     */
    public static function getBtnAuthByRoleId($admin)
    {
        if ($admin['root']) {
            return ['*'];
        }

        $menuId = SystemRoleMenu::whereIn('role_id', $admin['role_id'])
            ->column('menu_id');

        $where[] = ['is_disable', '=', 0];
        $where[] = ['perms', '<>', ''];

        $roleAuth = SystemMenu::distinct(true)
            ->where('id', 'in', $menuId)
            ->where($where)
            ->column('perms');

        $allAuth = SystemMenu::distinct(true)
            ->where($where)
            ->column('perms');

        $roleAuth = self::expandPermissionAliases($roleAuth);
        $allAuth = self::expandPermissionAliases($allAuth);

        $hasAllAuth = array_diff($allAuth, $roleAuth);
        if (empty($hasAllAuth)) {
            return ['*'];
        }

        return $roleAuth;
    }


    /**
     * @notes 获取管理员角色关联的菜单id(菜单，权限)
     * @param int $adminId
     * @return array
     * @author 段誉
     * @date 2022/7/1 15:56
     */
    public static function getAuthByAdminId(int $adminId): array
    {
        $roleIds = AdminRole::where('admin_id', $adminId)->column('role_id');
        $menuId = SystemRoleMenu::whereIn('role_id', $roleIds)->column('menu_id');

        $permissions = SystemMenu::distinct(true)
            ->where([
                ['is_disable', '=', 0],
                ['perms', '<>', ''],
                ['id', 'in', array_unique($menuId)],
            ])
            ->column('perms');

        return self::expandPermissionAliases($permissions);
    }
}
