<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\auth\SystemRole;
use app\common\model\staff\Staff;

/**
 * 服务人员通用服务
 */
class StaffService
{
    protected static ?int $staffRoleId = null;

    /**
     * @notes 获取服务人员角色ID
     */
    public static function getStaffRoleId(): int
    {
        if (self::$staffRoleId !== null) {
            return self::$staffRoleId;
        }
        $roleId = SystemRole::where('name', '服务人员')
            ->whereNull('delete_time')
            ->value('id');
        self::$staffRoleId = $roleId ? (int)$roleId : 0;
        return self::$staffRoleId;
    }

    /**
     * @notes 判断是否服务人员角色
     */
    public static function isStaffRole(array $adminInfo): bool
    {
        if (($adminInfo['root'] ?? 0) == 1) {
            return false;
        }
        $roleId = self::getStaffRoleId();
        if ($roleId <= 0) {
            return false;
        }
        $roleIds = $adminInfo['role_id'] ?? [];
        return in_array($roleId, $roleIds, true);
    }

    /**
     * @notes 获取服务人员ID（按管理员ID）
     */
    public static function getStaffIdByAdminId(int $adminId): int
    {
        if ($adminId <= 0) {
            return 0;
        }
        return (int)Staff::where('admin_id', $adminId)->value('id');
    }

    /**
     * @notes 获取服务人员ID（按用户ID）
     */
    public static function getStaffIdByUserId(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        return (int)Staff::where('user_id', $userId)->value('id');
    }

    /**
     * @notes 获取当前管理员可访问的服务人员ID（0=不限制）
     */
    public static function getStaffScopeId(int $adminId, array $adminInfo): int
    {
        if (!self::isStaffRole($adminInfo)) {
            return 0;
        }
        return self::getStaffIdByAdminId($adminId);
    }
}
