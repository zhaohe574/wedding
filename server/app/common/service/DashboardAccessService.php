<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 经营看板访问鉴权
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\cache\AdminAuthCache;
use app\common\model\auth\Admin;
use think\facade\Log;
use think\helper\Str;

/**
 * 经营看板（财务看板）访问鉴权
 * 说明：看板数据只经后台 RBAC 暴露，不再使用 feature_switch.admin_dashboard_user_ids 白名单。
 * 移动端（uniapp）用户先解析其绑定的后台管理员账号（la_admin.user_id），
 * 再按该管理员在 la_system_menu 中的节点权限判定，未登记节点默认拒绝。
 * Class DashboardAccessService
 * @package app\common\service
 */
class DashboardAccessService
{
    /** 财务概览节点 */
    public const NODE_OVERVIEW = 'dashboard.dashboard/overview';
    /** 收入趋势节点 */
    public const NODE_INCOME_TREND = 'dashboard.dashboard/incomeTrend';
    /** 订单统计节点 */
    public const NODE_ORDER_STATS = 'dashboard.dashboard/orderStats';
    /** 团队总览节点 */
    public const NODE_TEAM_OVERVIEW = 'dashboard.dashboard/teamOverview';

    /**
     * @notes 看板总开关是否开启
     */
    public static function isFeatureEnabled(): bool
    {
        return (int)ConfigService::get('feature_switch', 'admin_dashboard', 1) === 1;
    }

    /**
     * @notes 判断小程序用户是否可访问经营看板
     * @param int $userId 小程序用户ID
     * @param string $node 需要的权限节点，默认财务概览
     */
    public static function canAccess(int $userId, string $node = self::NODE_OVERVIEW): bool
    {
        if ($userId <= 0 || !self::isFeatureEnabled()) {
            return false;
        }

        return self::hasNodePermission(self::getBoundAdminId($userId), $node);
    }

    /**
     * @notes 获取小程序用户绑定的后台管理员ID，未绑定返回 0
     */
    public static function getBoundAdminId(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $adminId = Admin::where('user_id', $userId)
            ->where('disable', 0)
            ->value('id');

        return (int)$adminId;
    }

    /**
     * @notes 判断管理员是否拥有指定节点权限
     * 超级管理员直接通过；普通管理员需节点已登记且已授权，否则默认拒绝。
     */
    public static function hasNodePermission(int $adminId, string $node = self::NODE_OVERVIEW): bool
    {
        if ($adminId <= 0 || !self::isFeatureEnabled()) {
            return false;
        }

        $admin = Admin::where('id', $adminId)->find();
        if (!$admin) {
            return false;
        }
        if ((int)$admin->root === 1) {
            return true;
        }

        $target = self::normalizeUri($node);
        try {
            $authCache = new AdminAuthCache($adminId);
            $allUri = self::normalizeUriList($authCache->getAllUri() ?: []);
            // 未登记节点不允许任何非超管访问，避免"漏配即公开"
            if (!in_array($target, $allUri, true)) {
                return false;
            }
            $adminUri = self::normalizeUriList($authCache->getAdminUri() ?: []);
            return in_array($target, $adminUri, true);
        } catch (\Throwable $e) {
            Log::write('经营看板权限校验失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 归一化权限标识，与 AuthMiddleware 的匹配规则保持一致
     */
    public static function normalizeUri(string $uri): string
    {
        return strtolower(Str::camel(trim($uri)));
    }

    /**
     * @notes 批量归一化权限标识
     * @param array $uris
     * @return array
     */
    public static function normalizeUriList(array $uris): array
    {
        $result = [];
        foreach ($uris as $uri) {
            $normalized = self::normalizeUri((string)$uri);
            if ($normalized !== '') {
                $result[] = $normalized;
            }
        }
        return $result;
    }
}
