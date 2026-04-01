<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作台逻辑
// +----------------------------------------------------------------------

namespace app\adminapi\logic;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\Refund;
use app\common\model\order\Payment;
use app\common\model\user\User;
use app\common\model\staff\Staff;
use app\common\model\service\ServicePackage;
use app\common\model\order\OrderItem;
use app\common\service\ConfigService;
use think\facade\Db;

/**
 * 工作台
 * Class WorkbenchLogic
 * @package app\adminapi\logic
 */
class WorkbenchLogic extends BaseLogic
{
    /**
     * @notes 工作台首页数据
     * @return array
     */
    public static function index(): array
    {
        return [
            // 今日核心数据
            'today' => self::todayData(),
            // 待办事项
            'todo' => self::todoData(),
            // 营收趋势（近15天）
            'revenue_trend' => self::revenueTrend(),
            // 订单状态分布
            'order_status' => self::orderStatusDistribution(),
            // 热门服务TOP5
            'hot_services' => self::hotServices(),
            // 近期订单
            'recent_orders' => self::recentOrders(),
        ];
    }

    /**
     * @notes 今日核心数据（含环比）
     * @return array
     */
    private static function todayData(): array
    {
        $todayStart = strtotime('today');
        $todayEnd = time();
        $yesterdayStart = strtotime('yesterday');
        $yesterdayEnd = $todayStart - 1;

        // 今日营收
        $todayRevenue = (float)Payment::where('pay_status', Payment::STATUS_PAID)
            ->whereBetweenTime('pay_time', $todayStart, $todayEnd)
            ->sum('pay_amount');

        // 昨日营收
        $yesterdayRevenue = (float)Payment::where('pay_status', Payment::STATUS_PAID)
            ->whereBetweenTime('pay_time', $yesterdayStart, $yesterdayEnd)
            ->sum('pay_amount');

        // 今日订单数
        $todayOrders = (int)Order::whereBetweenTime('create_time', $todayStart, $todayEnd)
            ->count();

        // 昨日订单数
        $yesterdayOrders = (int)Order::whereBetweenTime('create_time', $yesterdayStart, $yesterdayEnd)
            ->count();

        // 今日新增用户
        $todayUsers = (int)User::whereBetweenTime('create_time', $todayStart, $todayEnd)
            ->count();

        // 昨日新增用户
        $yesterdayUsers = (int)User::whereBetweenTime('create_time', $yesterdayStart, $yesterdayEnd)
            ->count();

        // 总营收
        $totalRevenue = (float)Payment::where('pay_status', Payment::STATUS_PAID)
            ->sum('pay_amount');

        // 总订单数
        $totalOrders = (int)Order::count();

        // 总用户数
        $totalUsers = (int)User::count();

        return [
            'time' => date('Y-m-d H:i:s'),
            'revenue' => round($todayRevenue, 2),
            'revenue_yesterday' => round($yesterdayRevenue, 2),
            'revenue_compare' => self::calcCompare($todayRevenue, $yesterdayRevenue),
            'total_revenue' => round($totalRevenue, 2),
            'order_count' => $todayOrders,
            'order_yesterday' => $yesterdayOrders,
            'order_compare' => self::calcCompare($todayOrders, $yesterdayOrders),
            'total_orders' => $totalOrders,
            'new_user' => $todayUsers,
            'user_yesterday' => $yesterdayUsers,
            'user_compare' => self::calcCompare($todayUsers, $yesterdayUsers),
            'total_users' => $totalUsers,
        ];
    }

    /**
     * @notes 待办事项统计
     * @return array
     */
    private static function todoData(): array
    {
        return [
            // 待确认订单
            'pending_confirm' => (int)Order::where('order_status', Order::STATUS_PENDING_CONFIRM)->count(),
            // 待支付订单
            'pending_pay' => (int)Order::where('order_status', Order::STATUS_PENDING_PAY)->count(),
            // 服务中订单
            'in_service' => (int)Order::where('order_status', Order::STATUS_IN_SERVICE)->count(),
            // 待审核退款
            'pending_refund' => (int)Refund::where('refund_status', Refund::STATUS_PENDING)->count(),
            // 待审核员工
            'pending_staff' => (int)Staff::where('audit_status', Staff::AUDIT_PENDING)->count(),
        ];
    }

    /**
     * @notes 营收趋势（近15天）
     * @return array
     */
    private static function revenueTrend(): array
    {
        $dates = [];
        $revenues = [];
        $orders = [];

        for ($i = 14; $i >= 0; $i--) {
            $dayStart = strtotime("-{$i} days", strtotime('today'));
            $dayEnd = $dayStart + 86399;
            $dates[] = date('m/d', $dayStart);

            $dayRevenue = (float)Payment::where('pay_status', Payment::STATUS_PAID)
                ->whereBetweenTime('pay_time', $dayStart, $dayEnd)
                ->sum('pay_amount');
            $revenues[] = round($dayRevenue, 2);

            $dayOrders = (int)Order::whereBetweenTime('create_time', $dayStart, $dayEnd)
                ->count();
            $orders[] = $dayOrders;
        }

        return [
            'date' => $dates,
            'revenue' => $revenues,
            'orders' => $orders,
        ];
    }

    /**
     * @notes 订单状态分布
     * @return array
     */
    private static function orderStatusDistribution(): array
    {
        $statusMap = [
            Order::STATUS_PENDING_CONFIRM => '待确认',
            Order::STATUS_PENDING_PAY => '待支付',
            Order::STATUS_PAID => '已支付',
            Order::STATUS_IN_SERVICE => '服务中',
            Order::STATUS_COMPLETED => '已完成',
            Order::STATUS_CANCELLED => '已取消',
            Order::STATUS_REFUNDED => '已退款',
            Order::STATUS_USER_DELETED => '用户已删除',
        ];

        $result = [];
        foreach ($statusMap as $status => $name) {
            $count = (int)Order::where('order_status', $status)->count();
            if ($count > 0) {
                $result[] = [
                    'name' => $name,
                    'value' => $count,
                    'status' => $status,
                ];
            }
        }

        return $result;
    }

    /**
     * @notes 热门服务TOP5（按订单项数量统计）
     * @return array
     */
    private static function hotServices(): array
    {
        $list = OrderItem::field('package_id, package_name, COUNT(*) as order_count, SUM(subtotal) as total_amount')
            ->where('package_id', '>', 0)
            ->group('package_id, package_name')
            ->order('order_count', 'desc')
            ->limit(5)
            ->select()
            ->toArray();

        return array_map(function ($item) {
            return [
                'package_id' => $item['package_id'],
                'name' => $item['package_name'] ?: '未知套餐',
                'order_count' => (int)$item['order_count'],
                'total_amount' => round((float)$item['total_amount'], 2),
            ];
        }, $list);
    }

    /**
     * @notes 近期订单（最新10条）
     * @return array
     */
    private static function recentOrders(): array
    {
        $orders = Order::field('id, order_sn, order_status, pay_amount, contact_name, contact_mobile, service_date, create_time')
            ->order('id', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        return array_map(function ($order) {
            return [
                'id' => $order['id'],
                'order_sn' => $order['order_sn'],
                'order_status' => $order['order_status'],
                'order_status_desc' => self::getStatusDesc($order['order_status']),
                'pay_amount' => round((float)$order['pay_amount'], 2),
                'contact_name' => $order['contact_name'] ?? '',
                'service_date' => $order['service_date'] ?? '',
                'create_time' => is_numeric($order['create_time'])
                    ? date('Y-m-d H:i', (int)$order['create_time'])
                    : ($order['create_time'] ?? ''),
            ];
        }, $orders);
    }

    /**
     * @notes 计算环比增长率
     * @param float|int $current
     * @param float|int $previous
     * @return float
     */
    private static function calcCompare($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round(($current - $previous) / $previous * 100, 1);
    }

    /**
     * @notes 获取订单状态描述
     * @param int $status
     * @return string
     */
    private static function getStatusDesc(int $status): string
    {
        $map = [
            Order::STATUS_PENDING_CONFIRM => '待确认',
            Order::STATUS_PENDING_PAY => '待支付',
            Order::STATUS_PAID => '已支付',
            Order::STATUS_IN_SERVICE => '服务中',
            Order::STATUS_COMPLETED => '已完成',
            Order::STATUS_REVIEWED => '已评价',
            Order::STATUS_CANCELLED => '已取消',
            Order::STATUS_PAUSED => '已暂停',
            Order::STATUS_REFUNDED => '已退款',
            Order::STATUS_USER_DELETED => '用户已删除',
        ];
        return $map[$status] ?? '未知';
    }
}
