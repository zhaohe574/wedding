<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 移动端管理员看板逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\adminapi\logic\financial\FinancialReportLogic;
use app\adminapi\logic\order\OrderLogic as AdminOrderLogic;
use app\common\logic\BaseLogic;
use app\common\service\ConfigService;

/**
 * 移动端管理员看板逻辑
 * Class AdminDashboardLogic
 * @package app\api\logic
 */
class AdminDashboardLogic extends BaseLogic
{
    /**
     * @notes 校验当前用户是否可访问管理员看板
     */
    public static function canAccess(int $userId): bool
    {
        if ($userId <= 0) {
            self::setError('请先登录');
            return false;
        }

        if ((int) ConfigService::get('feature_switch', 'admin_dashboard', 1) !== 1) {
            self::setError('管理员看板已关闭');
            return false;
        }

        $allowedUserIds = self::getAllowedUserIds();
        if (empty($allowedUserIds) || !in_array($userId, $allowedUserIds, true)) {
            self::setError('暂无权限访问管理员看板');
            return false;
        }

        return true;
    }

    /**
     * @notes 财务概览
     */
    public static function overview(array $params): array
    {
        return FinancialReportLogic::overview($params);
    }

    /**
     * @notes 收入趋势
     */
    public static function incomeTrend(array $params): array
    {
        return FinancialReportLogic::incomeTrend($params);
    }

    /**
     * @notes 订单统计
     */
    public static function orderStats(array $params): array
    {
        return AdminOrderLogic::statistics($params);
    }

    /**
     * @notes 获取管理员看板可访问用户ID列表
     */
    public static function getAllowedUserIds(): array
    {
        $rawUserIds = (string) ConfigService::get('feature_switch', 'admin_dashboard_user_ids', '');
        if (trim($rawUserIds) === '') {
            return [];
        }

        $items = preg_split('/[\s,，]+/u', trim($rawUserIds)) ?: [];
        $userIds = [];
        foreach ($items as $item) {
            $id = trim((string) $item);
            if ($id === '' || !preg_match('/^[1-9]\d*$/', $id)) {
                continue;
            }
            $userIds[] = (int) $id;
        }

        if (empty($userIds)) {
            return [];
        }

        return array_values(array_unique($userIds));
    }
}
