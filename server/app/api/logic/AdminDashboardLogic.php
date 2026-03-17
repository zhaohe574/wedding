<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 移动端管理员看板逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\adminapi\logic\financial\FinancialReportLogic;
use app\adminapi\logic\order\OrderLogic as AdminOrderLogic;
use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\schedule\Schedule;
use app\common\model\schedule\Waitlist;
use app\common\model\staff\Staff;
use app\common\service\ConfigService;
use think\facade\Db;

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
     * @notes 团队总览
     */
    public static function teamOverview(array $params = []): array
    {
        return [
            'team' => self::getTeamStats(),
            'capacity' => self::getCapacityStats($params),
            'todo' => self::getTodoStats(),
            'members' => self::getMemberLoadList(),
        ];
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

    /**
     * @notes 团队基础统计
     */
    private static function getTeamStats(): array
    {
        $baseQuery = Staff::whereNull('delete_time');
        $activeQuery = Staff::whereNull('delete_time')
            ->where('status', Staff::STATUS_ENABLE)
            ->where('audit_status', Staff::AUDIT_PASS);

        return [
            'total_staff' => (int) (clone $baseQuery)->count(),
            'active_staff' => (int) (clone $activeQuery)->count(),
            'recommended_staff' => (int) (clone $activeQuery)->where('is_recommend', 1)->count(),
        ];
    }

    /**
     * @notes 当前月档期占用统计
     */
    private static function getCapacityStats(array $params): array
    {
        $anchorDate = trim((string) ($params['end_date'] ?? date('Y-m-d')));
        $monthStart = date('Y-m-01', strtotime($anchorDate));
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $query = Schedule::whereBetween('schedule_date', [$monthStart, $monthEnd])
            ->where('time_slot', Schedule::TIME_SLOT_ALL);

        $totalSlots = (int) (clone $query)->count();
        $bookedSlots = (int) (clone $query)->where('status', Schedule::STATUS_BOOKED)->count();
        $occupiedSlots = (int) (clone $query)
            ->whereIn('status', [
                Schedule::STATUS_BOOKED,
                Schedule::STATUS_LOCKED,
                Schedule::STATUS_RESERVED,
            ])
            ->count();

        return [
            'month_label' => date('Y-m', strtotime($anchorDate)),
            'month_total_slots' => $totalSlots,
            'month_booked_slots' => $bookedSlots,
            'month_occupied_slots' => $occupiedSlots,
            'booking_rate' => $totalSlots > 0 ? round($occupiedSlots / $totalSlots * 100, 1) : 0,
        ];
    }

    /**
     * @notes 团队待办统计
     */
    private static function getTodoStats(): array
    {
        $pendingConfirm = (int) Order::whereNull('delete_time')
            ->where('order_status', Order::STATUS_PENDING_CONFIRM)
            ->count();
        $pendingPay = (int) Order::whereNull('delete_time')
            ->where('order_status', Order::STATUS_PENDING_PAY)
            ->count();
        $inService = (int) Order::whereNull('delete_time')
            ->where('order_status', Order::STATUS_IN_SERVICE)
            ->count();
        $waitlistTotal = (int) Waitlist::whereIn('notify_status', [
            Waitlist::NOTIFY_STATUS_PENDING,
            Waitlist::NOTIFY_STATUS_NOTIFIED,
        ])->count();

        return [
            'pending_confirm' => $pendingConfirm,
            'pending_pay' => $pendingPay,
            'in_service' => $inService,
            'waitlist_total' => $waitlistTotal,
            'total' => $pendingConfirm + $pendingPay + $waitlistTotal,
        ];
    }

    /**
     * @notes 成员负载列表
     */
    private static function getMemberLoadList(): array
    {
        $staffList = Staff::whereNull('delete_time')
            ->where('status', Staff::STATUS_ENABLE)
            ->where('audit_status', Staff::AUDIT_PASS)
            ->field('id,name,avatar,category_id,is_recommend')
            ->select();

        if ($staffList->isEmpty()) {
            return [];
        }

        $staffRows = $staffList->toArray();
        $staffIds = array_values(array_unique(array_map('intval', array_column($staffRows, 'id'))));

        $recentStartTime = strtotime(date('Y-m-d 00:00:00', strtotime('-29 days')));
        $recentEndTime = time();
        $upcomingStartDate = date('Y-m-d');
        $upcomingEndDate = date('Y-m-d', strtotime('+29 days'));

        $recentOrderMap = Db::name('order_item')
            ->alias('oi')
            ->join('order o', 'o.id = oi.order_id')
            ->whereIn('oi.staff_id', $staffIds)
            ->whereBetween('o.create_time', [$recentStartTime, $recentEndTime])
            ->whereNull('o.delete_time')
            ->group('oi.staff_id')
            ->column('COUNT(DISTINCT oi.order_id) as count', 'oi.staff_id');

        $pendingOrderMap = Db::name('order_item')
            ->alias('oi')
            ->join('order o', 'o.id = oi.order_id')
            ->whereIn('oi.staff_id', $staffIds)
            ->whereNull('o.delete_time')
            ->whereIn('o.order_status', [
                Order::STATUS_PENDING_CONFIRM,
                Order::STATUS_PENDING_PAY,
            ])
            ->group('oi.staff_id')
            ->column('COUNT(DISTINCT oi.order_id) as count', 'oi.staff_id');

        $upcomingBookedMap = Schedule::whereIn('staff_id', $staffIds)
            ->whereBetween('schedule_date', [$upcomingStartDate, $upcomingEndDate])
            ->where('time_slot', Schedule::TIME_SLOT_ALL)
            ->whereIn('status', [
                Schedule::STATUS_BOOKED,
                Schedule::STATUS_LOCKED,
                Schedule::STATUS_RESERVED,
            ])
            ->group('staff_id')
            ->column('COUNT(*) as count', 'staff_id');

        $waitlistMap = Waitlist::whereIn('staff_id', $staffIds)
            ->whereIn('notify_status', [
                Waitlist::NOTIFY_STATUS_PENDING,
                Waitlist::NOTIFY_STATUS_NOTIFIED,
            ])
            ->group('staff_id')
            ->column('COUNT(*) as count', 'staff_id');

        $members = [];
        foreach ($staffRows as $staff) {
            $staffId = (int) ($staff['id'] ?? 0);
            $recentOrderCount = (int) ($recentOrderMap[$staffId] ?? 0);
            $upcomingBookedSlots = (int) ($upcomingBookedMap[$staffId] ?? 0);
            $followUpCount = (int) ($pendingOrderMap[$staffId] ?? 0) + (int) ($waitlistMap[$staffId] ?? 0);

            $members[] = [
                'id' => $staffId,
                'name' => (string) ($staff['name'] ?? ''),
                'avatar' => (string) ($staff['avatar'] ?? ''),
                'category_name' => (string) ($staff['category_name'] ?? ''),
                'is_recommend' => (int) ($staff['is_recommend'] ?? 0),
                'recent_order_count' => $recentOrderCount,
                'upcoming_booked_slots' => $upcomingBookedSlots,
                'follow_up_count' => $followUpCount,
                'load_level' => self::resolveLoadLevel($recentOrderCount, $upcomingBookedSlots, $followUpCount),
            ];
        }

        usort($members, static function (array $a, array $b) {
            return [$b['upcoming_booked_slots'], $b['recent_order_count'], $b['follow_up_count']]
                <=> [$a['upcoming_booked_slots'], $a['recent_order_count'], $a['follow_up_count']];
        });

        return array_slice($members, 0, 5);
    }

    /**
     * @notes 负载等级
     */
    private static function resolveLoadLevel(int $recentOrderCount, int $upcomingBookedSlots, int $followUpCount): string
    {
        if ($upcomingBookedSlots >= 8 || $followUpCount >= 4) {
            return '高负载';
        }

        if ($upcomingBookedSlots >= 3 || $recentOrderCount >= 3) {
            return '平稳';
        }

        return '可分配';
    }
}
