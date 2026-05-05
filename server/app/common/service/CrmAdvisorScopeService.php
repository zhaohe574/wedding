<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - CRM 顾问数据范围服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\auth\SystemRole;
use app\common\model\crm\SalesAdvisor;

/**
 * CRM 顾问角色数据范围。
 */
class CrmAdvisorScopeService
{
    public const ROLE_NAME = '顾问';

    protected static ?array $advisorRoleIds = null;

    /**
     * @notes 获取全部有效的顾问角色ID
     */
    public static function getAdvisorRoleIds(): array
    {
        if (self::$advisorRoleIds !== null) {
            return self::$advisorRoleIds;
        }

        $roleIds = SystemRole::where('name', self::ROLE_NAME)
            ->whereNull('delete_time')
            ->order('id', 'asc')
            ->column('id');

        self::$advisorRoleIds = array_values(array_unique(array_map('intval', $roleIds)));
        return self::$advisorRoleIds;
    }

    /**
     * @notes 当前后台账号是否为顾问角色
     */
    public static function isAdvisorRole(array $adminInfo): bool
    {
        if ((int)($adminInfo['root'] ?? 0) === 1) {
            return false;
        }

        $advisorRoleIds = self::getAdvisorRoleIds();
        $roleIds = array_map('intval', $adminInfo['role_id'] ?? []);
        if (!empty($advisorRoleIds) && !empty(array_intersect($advisorRoleIds, $roleIds))) {
            return true;
        }

        $roleName = (string)($adminInfo['role_name'] ?? '');
        $roleNames = array_filter(array_map('trim', explode('/', $roleName)));
        return in_array(self::ROLE_NAME, $roleNames, true);
    }

    /**
     * @notes 获取当前后台账号绑定的顾问ID，0表示不限制，-1表示顾问角色未绑定资料
     */
    public static function getAdvisorScopeId(int $adminId, array $adminInfo = []): int
    {
        if (!self::isAdvisorRole($adminInfo)) {
            return 0;
        }

        $advisorId = (int)SalesAdvisor::where('admin_id', $adminId)->value('id');
        return $advisorId > 0 ? $advisorId : -1;
    }

    /**
     * @notes 顾问角色缺少绑定资料时的提示
     */
    public static function getAdvisorScopeAccessDeniedMessage(array $adminInfo = []): string
    {
        if (self::isAdvisorRole($adminInfo)) {
            return '当前后台账号未绑定顾问资料，请联系管理员在顾问管理中绑定';
        }
        return '无权访问该CRM数据';
    }
}
