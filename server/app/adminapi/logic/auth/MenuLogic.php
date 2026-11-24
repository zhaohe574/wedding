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
     * 始终对所有后台账号开放的公共菜单。
     *
     * 原先是写死的主键 [497, 501, 502, 503]，环境一换 id 就变（本库 440 段整体偏移过）。
     * 现在由 database/menu/menu_manifest.php 的 policy.public 按 menu_key 解析，
     * 见 app\common\service\AdminMenuPolicyService。
     */
    private static function publicMenuIds(): array
    {
        return \app\common\service\AdminMenuPolicyService::publicIds();
    }


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
            $roleMenu = array_values(array_unique(array_merge(array_map('intval', $roleMenu), self::publicMenuIds())));
            $where[] = ['id', 'in', $roleMenu];
        }

        $menu = SystemMenu::where($where)
            ->order(['sort' => 'desc', 'id' => 'asc'])
            ->select()
            ->toArray();

        $menu = self::filterUnavailableMenus($menu);
        $menu = self::filterStaffLeaderMenus($menu, (int)$adminId);
        $menu = self::normalizeStaffCenterProfileMenu($menu);
        // 服务人员中心仅对服务人员角色可见（管理员默认不展示该入口）
        $menu = self::filterNonStaffMenus($menu, (int)$adminId);
        // 剪除过滤后已无可见子节点的空壳目录，防止侧边栏出现空文件夹
        $menu = self::pruneEmptyStaffCenterDirs($menu);

        return linear_to_tree($menu, 'children');
    }


    /**
     * @notes 修正服务人员中心“我的资料”菜单为详情页
     * 不再依赖「父节点 pid == 0」：按组件 / menu_key / staff-center 子树内的旧 paths 识别
     */
    private static function normalizeStaffCenterProfileMenu(array $menu): array
    {
        // staff-center 子树节点集合（任意层级），用于识别挂在服务人员中心下的旧版 profile 菜单
        $staffCenterSubtreeIds = self::collectIdsWithDescendants($menu, self::staffCenterDirIds($menu));

        foreach ($menu as &$item) {
            if (($item['type'] ?? '') !== 'C') {
                continue;
            }

            $isProfileMenu = (($item['component'] ?? '') === 'staff_center/profile/index')
                || (($item['menu_key'] ?? '') === 'page.staff_center.profile')
                || (
                    ($item['paths'] ?? '') === 'profile'
                    && isset($staffCenterSubtreeIds[(int)($item['id'] ?? 0)])
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
     * @notes 是否为 staff-center 目录节点（兼容 staff_center / staff-center / nav.staff-center 命名）
     */
    private static function matchStaffCenterDir(array $item): bool
    {
        return ($item['type'] ?? '') === 'M'
            && (
                in_array((string)($item['paths'] ?? ''), ['staff_center', 'staff-center'], true)
                || (($item['menu_key'] ?? '') === 'nav.staff-center')
            );
    }


    /**
     * @notes 收集菜单中所有 staff-center 目录节点 id（不再限制 pid == 0）
     * @return int[]
     */
    private static function staffCenterDirIds(array $menu): array
    {
        $ids = [];
        foreach ($menu as $item) {
            if (self::matchStaffCenterDir($item)) {
                $ids[] = (int)($item['id'] ?? 0);
            }
        }

        return $ids;
    }


    /**
     * @notes 收集指定节点及其全部后代节点 id（返回以 id 为键的集合）
     * @param int[] $rootIds
     * @return array<int, bool>
     */
    private static function collectIdsWithDescendants(array $menu, array $rootIds): array
    {
        if (empty($rootIds)) {
            return [];
        }

        $childrenMap = [];
        foreach ($menu as $item) {
            $childrenMap[(int)($item['pid'] ?? 0)][] = (int)($item['id'] ?? 0);
        }

        $ids = [];
        $stack = array_map('intval', $rootIds);
        while ($stack !== []) {
            $id = (int)array_pop($stack);
            if (isset($ids[$id])) {
                continue;
            }
            $ids[$id] = true;
            foreach ($childrenMap[$id] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        return $ids;
    }


    /**
     * @notes 按父子关系递归剔除指定节点及其全部后代
     * @param int[] $rootIds
     */
    private static function removeMenuWithDescendants(array $menu, array $rootIds): array
    {
        if (empty($rootIds)) {
            return $menu;
        }

        $removeIds = self::collectIdsWithDescendants($menu, $rootIds);

        return array_values(array_filter($menu, static function ($item) use ($removeIds) {
            return !isset($removeIds[(int)($item['id'] ?? 0)]);
        }));
    }


    /**
     * @notes 服务人员中心仅对服务人员角色可见（管理员默认不展示该入口）
     * 隐藏根节点 = 策略 staff_only 全量 + 所有 staff-center 目录节点（不再限制 pid == 0），
     * 并按父子关系递归剔除其后代，避免深层子菜单残留
     */
    private static function filterNonStaffMenus(array $menu, int $adminId): array
    {
        if (self::hasStaffRole($adminId)) {
            return $menu;
        }

        $rootIds = array_merge(\app\common\service\AdminMenuPolicyService::staffOnlyIds(), self::staffCenterDirIds($menu));

        return self::removeMenuWithDescendants($menu, $rootIds);
    }


    /**
     * @notes 剪除过滤/鉴权后已无任何子节点的空壳目录（M 型空目录会在侧边栏变成指向空白 ROUTE_VIEW 的菜单项）
     */
    private static function pruneEmptyStaffCenterDirs(array $menu): array
    {
        // 由深到浅反复剪枝：目录无子节点则移除，父级目录因此变空也继续剪
        do {
            $childIdMap = [];
            foreach ($menu as $item) {
                $childIdMap[(int)($item['pid'] ?? 0)][] = (int)($item['id'] ?? 0);
            }

            $pruned = false;
            $kept = [];
            foreach ($menu as $item) {
                $id = (int)($item['id'] ?? 0);
                if (($item['type'] ?? '') === 'M' && empty($childIdMap[$id])) {
                    $pruned = true;
                    continue;
                }
                $kept[] = $item;
            }
            $menu = $kept;
        } while ($pruned);

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
     * @notes 队长菜单仅对有效队长可见
     */
    private static function filterStaffLeaderMenus(array $menu, int $adminId): array
    {
        $leaderComponents = [
            'staff_center/team/index',
            'staff_center/team_review/index',
        ];

        // 队长专属节点：策略 leader_only + 团队分组目录（兼容 nav.staff-center.team-group / team-group 命名）
        $leaderRootIds = \app\common\service\AdminMenuPolicyService::leaderOnlyIds();
        foreach ($menu as $item) {
            if ((($item['menu_key'] ?? '') === 'nav.staff-center.team-group')
                || (($item['paths'] ?? '') === 'team-group')
            ) {
                $leaderRootIds[] = (int)($item['id'] ?? 0);
            }
        }

        $hasLeaderMenu = !empty($leaderRootIds);
        if (!$hasLeaderMenu) {
            foreach ($menu as $item) {
                if (in_array((string)($item['component'] ?? ''), $leaderComponents, true)) {
                    $hasLeaderMenu = true;
                    break;
                }
            }
        }
        if (!$hasLeaderMenu) {
            return $menu;
        }

        $admin = Admin::findOrEmpty($adminId);
        $adminInfo = $admin->isEmpty() ? [] : $admin->toArray();
        $staffId = StaffService::getStaffScopeId($adminId, $adminInfo);
        if ($staffId > 0 && \app\common\service\StaffTeamService::isLeader($staffId)) {
            return $menu;
        }

        // 非有效队长：队长专属节点（含组件命中的页面）及其后代一并剔除
        foreach ($menu as $item) {
            if (in_array((string)($item['component'] ?? ''), $leaderComponents, true)) {
                $leaderRootIds[] = (int)($item['id'] ?? 0);
            }
        }

        return self::removeMenuWithDescendants($menu, $leaderRootIds);
    }


    /**
     * @notes 过滤已下线和空壳菜单
     */
    private static function filterUnavailableMenus(array $menu): array
    {
        $blockedPaths = ['timeline', 'marketing'];
        $blockedComponents = [
            'coupon/lists/index',
            'order/transfer/index',
            'timeline/lists/index',
            'schedule/booking/index',
            'schedule/event/index',
            'decoration/style/style',
            'channel/h5',
            'channel/open_setting',
            'setting/wecom/index',
        ];
        $blockedPermPrefixes = [
            'coupon.coupon/',
            'growth.campaign/',
            'timeline.timeline/',
            'growth.timeline/',
            'financial.cost/',
            'schedule.booking/',
            'ops.booking/',
            'growth.customer/',
            'crm.sales_advisor/',
            'growth.advisor/',
            'crm.customer_loss_warning/',
            'crm.customerLossWarning/',
            'growth.lossWarning/',
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
