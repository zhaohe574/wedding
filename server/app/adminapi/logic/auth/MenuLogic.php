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


use app\common\enum\YesNoEnum;
use app\common\logic\BaseLogic;
use app\common\model\auth\Admin;
use app\common\model\auth\SystemMenu;
use app\common\model\auth\SystemRoleMenu;
use app\common\service\StaffService;


/**
 * 系统菜单
 * Class MenuLogic
 * @package app\adminapi\logic\auth
 */
class MenuLogic extends BaseLogic
{


    /**
     * @notes 获取管理员对应的角色菜单
     * @param $adminId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/7/1 10:50
     */
    public static function getMenuByAdminId($adminId)
    {
        $admin = Admin::findOrEmpty($adminId);

        $where = [];
        $where[] = ['type', 'in', ['M', 'C']];
        $where[] = ['is_disable', '=', 0];

        if ($admin['root'] != 1) {
            $roleMenu = SystemRoleMenu::whereIn('role_id', $admin['role_id'])->column('menu_id');
            $where[] = ['id', 'in', $roleMenu];
        }

        $menu = SystemMenu::where($where)
            ->order(['sort' => 'desc', 'id' => 'asc'])
            ->select()
            ->toArray();

        $menu = self::filterUnavailableMenus($menu);
        $menu = self::normalizeStaffCenterProfileMenu($menu);

        // 服务人员中心仅对服务人员角色可见（管理员默认不展示该入口）
        if (!self::hasStaffRole((int)$adminId)) {
            $staffCenterIds = array_column(array_filter($menu, function ($item) {
                return $item['pid'] == 0
                    && $item['type'] == 'M'
                    && in_array($item['paths'], ['staff_center', 'staff-center']);
            }), 'id');

            if (!empty($staffCenterIds)) {
                $menu = array_values(array_filter($menu, function ($item) use ($staffCenterIds) {
                    return !in_array($item['id'], $staffCenterIds)
                        && !in_array($item['pid'], $staffCenterIds);
                }));
            }
        }

        return linear_to_tree($menu, 'children');
    }


    /**
     * @notes 修正服务人员中心“我的资料”菜单为详情页
     */
    private static function normalizeStaffCenterProfileMenu(array $menu): array
    {
        $staffCenterIds = array_column(array_filter($menu, function ($item) {
            return ($item['pid'] ?? 0) == 0
                && ($item['type'] ?? '') === 'M'
                && in_array(($item['paths'] ?? ''), ['staff_center', 'staff-center'], true);
        }), 'id');

        if (empty($staffCenterIds)) {
            return $menu;
        }

        foreach ($menu as &$item) {
            if (!in_array((int)($item['pid'] ?? 0), $staffCenterIds, true)) {
                continue;
            }

            $isProfileMenu = ($item['type'] ?? '') === 'C'
                && (
                    ($item['paths'] ?? '') === 'profile'
                    || ($item['perms'] ?? '') === 'staff.staff/lists'
                    || ($item['perms'] ?? '') === 'staff.staff/myProfile'
                );

            if (!$isProfileMenu) {
                continue;
            }

            $item['name'] = '我的资料';
            $item['paths'] = 'profile';
            $item['component'] = 'staff_center/profile/index';
            $item['perms'] = 'staff.staff/myProfile';
        }
        unset($item);

        return $menu;
    }


    /**
     * @notes 是否包含服务人员角色
     * @param int $adminId
     * @return bool
     */
    private static function hasStaffRole(int $adminId): bool
    {
        return StaffService::hasStaffRoleByAdminId($adminId);
    }


    /**
     * @notes 过滤已下线和空壳菜单
     */
    private static function filterUnavailableMenus(array $menu): array
    {
        $blockedPaths = ['timeline', 'aftersale', 'marketing'];
        $blockedComponents = [
            'coupon/lists/index',
            'aftersale/ticket/index',
            'order/transfer/index',
            'timeline/lists/index',
            'financial/cost/index',
            'schedule/booking/index',
            'schedule/event/index',
            'decoration/style/style',
            'crm/customer/index',
            'crm/advisor/index',
            'crm/warning/index',
        ];
        $blockedPermPrefixes = [
            'coupon.coupon/',
            'growth.campaign/',
            'timeline.timeline/',
            'growth.timeline/',
            'aftersale.aftersale/',
            'ops.aftersaleTicket/',
            'financial.cost/',
            'finance.cost/',
            'schedule.calendarEvent/',
            'ops.calendarEvent/',
            'schedule.booking/',
            'ops.booking/',
            'crm.customer/',
            'growth.customer/',
            'crm.sales_advisor/',
            'crm.salesAdvisor/',
            'growth.advisor/',
            'crm.customer_loss_warning/',
            'crm.customerLossWarning/',
            'growth.lossWarning/',
            'crm.followRecord/',
            'growth.followRecord/',
            'order.order_transfer/',
            'ops.orderTransfer/',
        ];

        return array_values(array_filter($menu, function ($item) use (
            $blockedPaths,
            $blockedComponents,
            $blockedPermPrefixes
        ) {
            $path = (string)($item['paths'] ?? '');
            $component = (string)($item['component'] ?? '');
            $perms = (string)($item['perms'] ?? '');

            if (in_array($path, $blockedPaths, true)) {
                return false;
            }

            if (in_array($component, $blockedComponents, true)) {
                return false;
            }

            foreach ($blockedPermPrefixes as $prefix) {
                if (self::isBlockedPermission($perms, $prefix)) {
                    return false;
                }
            }

            return true;
        }));
    }


    /**
     * @notes 判断权限是否属于已下线模块
     */
    private static function isBlockedPermission(string $permission, string $prefix): bool
    {
        if ($permission === '' || !str_starts_with($permission, $prefix)) {
            return false;
        }

        // 服务人员中心的预约能力仍在线，不能被后台预约壳模块误伤
        if (in_array($prefix, ['schedule.booking/', 'ops.booking/'], true)) {
            return !str_starts_with($permission, 'schedule.booking/my')
                && !str_starts_with($permission, 'ops.booking/my');
        }

        return true;
    }


    /**
     * @notes 添加菜单
     * @param array $params
     * @return SystemMenu|\think\Model
     * @author 段誉
     * @date 2022/6/30 10:06
     */
    public static function add(array $params)
    {
        return SystemMenu::create([
            'pid' => $params['pid'],
            'type' => $params['type'],
            'name' => $params['name'],
            'icon' => $params['icon'] ?? '',
            'sort' => $params['sort'],
            'perms' => $params['perms'] ?? '',
            'paths' => $params['paths'] ?? '',
            'component' => $params['component'] ?? '',
            'selected' => $params['selected'] ?? '',
            'params' => $params['params'] ?? '',
            'is_cache' => $params['is_cache'],
            'is_show' => $params['is_show'],
            'is_disable' => $params['is_disable'],
        ]);
    }


    /**
     * @notes 编辑菜单
     * @param array $params
     * @return SystemMenu
     * @author 段誉
     * @date 2022/6/30 10:07
     */
    public static function edit(array $params)
    {
        return SystemMenu::update([
            'id' => $params['id'],
            'pid' => $params['pid'],
            'type' => $params['type'],
            'name' => $params['name'],
            'icon' => $params['icon'] ?? '',
            'sort' => $params['sort'],
            'perms' => $params['perms'] ?? '',
            'paths' => $params['paths'] ?? '',
            'component' => $params['component'] ?? '',
            'selected' => $params['selected'] ?? '',
            'params' => $params['params'] ?? '',
            'is_cache' => $params['is_cache'],
            'is_show' => $params['is_show'],
            'is_disable' => $params['is_disable'],
        ]);
    }


    /**
     * @notes 详情
     * @param $params
     * @return array
     * @author 段誉
     * @date 2022/6/30 9:54
     */
    public static function detail($params)
    {
        return SystemMenu::findOrEmpty($params['id'])->toArray();
    }


    /**
     * @notes 删除菜单
     * @param $params
     * @author 段誉
     * @date 2022/6/30 9:47
     */
    public static function delete($params)
    {
        // 删除菜单
        SystemMenu::destroy($params['id']);
        // 删除角色-菜单表中 与该菜单关联的记录
        SystemRoleMenu::where(['menu_id' => $params['id']])->delete();
    }


    /**
     * @notes 更新状态
     * @param array $params
     * @return SystemMenu
     * @author 段誉
     * @date 2022/7/6 17:02
     */
    public static function updateStatus(array $params)
    {
        return SystemMenu::update([
            'id' => $params['id'],
            'is_disable' => $params['is_disable']
        ]);
    }


    /**
     * @notes 全部数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/10/13 11:03
     */
    public static function getAllData()
    {
        $data = SystemMenu::where(['is_disable' => YesNoEnum::NO])
            ->field('id,pid,name')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();

        return linear_to_tree($data, 'children');
    }

}
