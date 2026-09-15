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
    protected static array $staffManageScopeIdsCache = [];

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

        $admin = \app\common\model\auth\Admin::where('id', $adminId)->where('disable', 0)->find();
        if (!$admin || !(int)$admin->user_id) return 0;
        return (int)Staff::where('admin_id', $adminId)
            ->where('user_id', (int)$admin->user_id)->where('status', 1)
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
            ->where('status', 1)
            ->whereIn('admin_id', \app\common\model\auth\Admin::where('user_id', $userId)->where('disable', 0)->column('id'))
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

        self::$staffScopeIdCache[$adminId] = $staffId > 0 ? $staffId : 0;
        return self::$staffScopeIdCache[$adminId];
    }

    /**
     * @notes 获取当前管理员可管理的服务人员ID集合（空数组=不限制）
     */
    public static function getStaffManageScopeIds(int $adminId, array $adminInfo, bool $includeSelf = true): array
    {
        if (!self::isStaffRole($adminInfo)) {
            return [];
        }

        $cacheKey = $adminId . ':' . ($includeSelf ? '1' : '0');
        if (isset(self::$staffManageScopeIdsCache[$cacheKey])) {
            return self::$staffManageScopeIdsCache[$cacheKey];
        }

        $staffId = self::getStaffScopeId($adminId, $adminInfo);
        if ($staffId <= 0) {
            return self::$staffManageScopeIdsCache[$cacheKey] = [];
        }

        $ids = StaffTeamService::getManageStaffIdsByStaffId($staffId, $includeSelf);
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        return self::$staffManageScopeIdsCache[$cacheKey] = $ids;
    }

    /**
     * @notes 判断当前服务人员账号是否有效队长
     */
    public static function isStaffTeamLeaderByAdminId(int $adminId, array $adminInfo): bool
    {
        $staffId = self::getStaffScopeId($adminId, $adminInfo);
        return $staffId > 0 && StaffTeamService::isLeader($staffId);
    }

    /**
     * @notes 判断当前服务人员账号是否可访问目标服务人员
     */
    public static function canAccessStaff(int $adminId, array $adminInfo, int $targetStaffId, bool $includeSelf = true): bool
    {
        if (!self::isStaffRole($adminInfo)) {
            return true;
        }
        if ($targetStaffId <= 0) {
            return false;
        }

        $ids = self::getStaffManageScopeIds($adminId, $adminInfo, $includeSelf);
        return in_array($targetStaffId, $ids, true);
    }

}
