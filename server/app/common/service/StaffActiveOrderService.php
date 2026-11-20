<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员当前日期有效订单
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\schedule\Schedule;
use think\facade\Db;

/**
 * 服务人员详情页「当前日期有效订单」查询。
 *
 * 有效订单：
 *   1. 当前登录用户本人下单（order.user_id = 当前用户）；
 *   2. 订单项指向当前服务人员，服务日期等于详情页当前日期；
 *   3. 订单状态为待确认 / 待支付 / 待服务 / 服务中 / 已暂停 / 退款中；
 *      已完成、已评价、已取消、已退款、用户已删除都不算有效订单；
 *   4. 订单项未取消、订单未软删除。
 *
 * 日期口径：详情页传入的 `date`（页面当前选中的服务日期，默认明天）；
 * 未传时按 Asia/Shanghai 的今天计算。`order_item.service_date` 是 DATE，
 * 所以自然日范围就是 [00:00:00, 23:59:59] 的日期值本身。
 */
class StaffActiveOrderService
{
    /** 详情页「查看订单」认可的有效订单状态 */
    public const EFFECTIVE_ORDER_STATUSES = [
        Order::STATUS_PENDING_CONFIRM,  // 0 待确认
        Order::STATUS_PENDING_PAY,      // 1 待支付
        Order::STATUS_PENDING_SERVICE,  // 2 待服务
        Order::STATUS_IN_SERVICE,       // 3 服务中
        Order::STATUS_PAUSED,           // 7 已暂停
        Order::STATUS_REFUNDING,        // 10 退款中
    ];

    /** 状态文案（详情页按钮旁提示用） */
    private const STATUS_LABELS = [
        Order::STATUS_PENDING_CONFIRM => '待确认',
        Order::STATUS_PENDING_PAY => '待支付',
        Order::STATUS_PENDING_SERVICE => '待服务',
        Order::STATUS_IN_SERVICE => '服务中',
        Order::STATUS_PAUSED => '已暂停',
        Order::STATUS_REFUNDING => '退款中',
    ];

    /**
     * @notes 查询当前用户在某服务人员某日的有效订单
     * @param int $userId 当前登录用户，0 表示游客
     * @param int $staffId
     * @param string $date 服务日期；空串按 Asia/Shanghai 今天
     * @return array|null
     */
    public static function findForUser(int $userId, int $staffId, string $date = ''): ?array
    {
        if ($userId <= 0 || $staffId <= 0) {
            return null;
        }

        $date = self::normalizeDate($date);
        $orderTable = (new Order())->getTable();

        $row = Db::name('order_item')->alias('oi')
            ->join($orderTable . ' o', 'o.id = oi.order_id')
            ->where('o.user_id', $userId)
            ->where('oi.staff_id', $staffId)
            ->where('oi.service_date', $date)
            ->whereIn('oi.item_type', [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF])
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereIn('o.order_status', self::EFFECTIVE_ORDER_STATUSES)
            ->whereNull('o.delete_time')
            ->field([
                'o.id' => 'order_id',
                'o.order_sn',
                'o.order_status',
                'o.pay_status',
                'oi.service_date',
                'oi.time_slot',
                'oi.package_name',
                'oi.staff_name',
            ])
            ->order('o.id', 'desc')
            ->find();

        return $row ? self::format($row) : null;
    }

    /**
     * @notes 订单状态是否算详情页的有效订单
     * @param int $status
     * @return bool
     */
    public static function isEffectiveStatus(int $status): bool
    {
        return in_array($status, self::EFFECTIVE_ORDER_STATUSES, true);
    }

    /**
     * @notes 规范化日期；空串回落为 Asia/Shanghai 今天
     * @param string $date
     * @return string
     */
    public static function normalizeDate(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return date('Y-m-d');
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return date('Y-m-d');
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * @notes 统一输出结构，前端只认 order_id
     * @param array $row
     * @return array
     */
    private static function format(array $row): array
    {
        $status = (int)($row['order_status'] ?? -1);
        $timeSlot = (int)($row['time_slot'] ?? Schedule::TIME_SLOT_ALL);

        return [
            'order_id' => (int)($row['order_id'] ?? 0),
            'order_sn' => (string)($row['order_sn'] ?? ''),
            'order_status' => $status,
            'order_status_text' => self::STATUS_LABELS[$status] ?? '处理中',
            'service_date' => (string)($row['service_date'] ?? ''),
            'time_slot' => $timeSlot,
            'time_slot_text' => $timeSlot === Schedule::TIME_SLOT_ALL ? '全天' : '指定时段',
            'package_name' => (string)($row['package_name'] ?? ''),
            'staff_name' => (string)($row['staff_name'] ?? ''),
        ];
    }
}
