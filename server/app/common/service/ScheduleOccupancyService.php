<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期占用统一口径
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\exception\ScheduleOccupancyException;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\staff\Staff;
use think\facade\Db;

/**
 * 服务人员单日档期占用的唯一判定入口。
 *
 * 业务口径（2026-11 起）：
 *   1. 只有「订单已确认」且「订单已付款」的订单项才占用服务人员单日档期；
 *   2. 未付款订单（待确认 / 待支付 / 线下凭证待审核）不占用档期，也不阻塞其他用户预约；
 *   3. 已付款订单在单日档期上互斥：同一服务人员同一天只允许一笔已付款订单
 *      （与 `la_schedule.uk_staff_date_slot` 的唯一约束一致）。
 *
 * 所有可用性查询、下单校验、服务人员确认、支付锁档、改期/恢复都必须走本服务，
 * 禁止在各业务里再写一份 status/paid_amount 判断。
 */
class ScheduleOccupancyService
{
    /**
     * 单日已付款订单容量。
     *
     * 档期表 `uk_staff_date_slot(staff_id, schedule_date, time_slot)` 本身只允许
     * 一条「已预约」记录，因此已付款订单的实际上限就是 1。
     * `schedule_rule.max_orders_per_day` 仅作为后台配置保留，不能放大已付款容量。
     */
    public const PAID_ORDER_CAPACITY_PER_DAY = 1;

    /**
     * 「已确认」的订单状态集合。
     *
     * 状态机保证：订单从 STATUS_PENDING_CONFIRM(0) 进入 STATUS_PENDING_PAY(1) 时，
     * 全部有效订单项的 confirm_status 均已为 1；因此这里用订单状态即可代表整单已确认。
     * 兼容历史数据不强制要求 `order_item.confirm_status = 1`，避免旧已付款订单漏占档期。
     */
    public const CONFIRMED_ORDER_STATUSES = [
        Order::STATUS_PENDING_PAY,      // 1  已确认，待支付（需配合已付款条件）
        Order::STATUS_PENDING_SERVICE,  // 2  已付款，待服务
        Order::STATUS_IN_SERVICE,       // 3  服务中
        Order::STATUS_COMPLETED,        // 4  已完成
        Order::STATUS_REVIEWED,         // 5  已评价
        Order::STATUS_PAUSED,           // 7  已暂停，原档期仍被占用（暂停不释放档期）
        Order::STATUS_REFUNDING,        // 10 退款中，退款完成前继续占用
    ];

    /**
     * @notes 占用判定基础查询（订单项 + 订单）
     *
     * 调用方只需追加 staff_id / service_date 条件，口径不会漂移。
     * @return \think\db\Query
     */
    public static function occupancyQuery()
    {
        $orderTable = (new Order())->getTable();

        return Db::name('order_item')->alias('oi')
            ->join($orderTable . ' o', 'o.id = oi.order_id')
            ->whereIn('oi.item_type', [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF])
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereIn('o.order_status', self::CONFIRMED_ORDER_STATUSES)
            ->whereNull('o.delete_time')
            ->where(function ($query) {
                // 已付款：定金、全款、线下收款任一已到账即视为已付款
                $query->where('o.paid_amount', '>', 0)
                    ->whereOr('o.deposit_paid', 1)
                    ->whereOr('o.pay_status', Order::PAY_STATUS_PAID);
            });
    }

    /**
     * @notes 统计某服务人员某日已被已付款订单占用的单数
     * @param int $staffId
     * @param string $date
     * @param int $excludeOrderId 排除的订单（校验自身时使用）
     * @return int
     */
    public static function countOccupied(int $staffId, string $date, int $excludeOrderId = 0): int
    {
        if ($staffId <= 0 || trim($date) === '') {
            return 0;
        }

        $query = self::occupancyQuery()
            ->where('oi.staff_id', $staffId)
            ->where('oi.service_date', $date);
        if ($excludeOrderId > 0) {
            $query->where('oi.order_id', '<>', $excludeOrderId);
        }

        $row = $query->field('COUNT(DISTINCT o.id) AS cnt')->find();

        return (int)($row['cnt'] ?? 0);
    }

    /**
     * @notes 批量统计「人员 × 日期」已付款占用数
     * @param array $staffIds
     * @param array $dates
     * @return array [staff_id => [date => count]]
     */
    public static function countOccupiedBatch(array $staffIds, array $dates): array
    {
        $staffIds = array_values(array_unique(array_filter(
            array_map('intval', $staffIds),
            static fn (int $id): bool => $id > 0
        )));
        $dates = array_values(array_unique(array_filter(
            array_map(static fn ($date): string => trim((string)$date), $dates),
            static fn (string $date): bool => $date !== ''
        )));

        if (empty($staffIds) || empty($dates)) {
            return [];
        }

        $rows = self::occupancyQuery()
            ->whereIn('oi.staff_id', $staffIds)
            ->whereIn('oi.service_date', $dates)
            ->field('oi.staff_id, oi.service_date, COUNT(DISTINCT o.id) AS cnt')
            ->group('oi.staff_id, oi.service_date')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $staffId = (int)($row['staff_id'] ?? 0);
            $date = (string)($row['service_date'] ?? '');
            if ($staffId <= 0 || $date === '') {
                continue;
            }
            $map[$staffId][$date] = (int)($row['cnt'] ?? 0);
        }

        return $map;
    }

    /**
     * @notes 查找冲突的已付款订单
     * @param int $staffId
     * @param string $date
     * @param int $excludeOrderId
     * @param bool $lockRows 是否加行锁（必须在事务内使用）
     * @return array|null
     */
    public static function findConflict(
        int $staffId,
        string $date,
        int $excludeOrderId = 0,
        bool $lockRows = false
    ): ?array {
        if ($staffId <= 0 || trim($date) === '') {
            return null;
        }

        $query = self::occupancyQuery()
            ->where('oi.staff_id', $staffId)
            ->where('oi.service_date', $date)
            ->field('o.id, o.order_sn, o.order_status, o.paid_amount, o.deposit_paid, o.pay_status');
        if ($excludeOrderId > 0) {
            $query->where('oi.order_id', '<>', $excludeOrderId);
        }
        if ($lockRows) {
            $query->lock(true);
        }

        $row = $query->find();

        return $row ?: null;
    }

    /**
     * @notes 校验「人员 + 日期」是否可被指定订单占用
     * @param int $staffId
     * @param string $date
     * @param int $excludeOrderId 当前订单 ID（必须排除自身）
     * @param string $action 用于提示的动作文案，如「确认订单」「支付」
     * @param bool $lockRows
     * @return void
     * @throws ScheduleOccupancyException
     */
    public static function assertDateFree(
        int $staffId,
        string $date,
        int $excludeOrderId,
        string $action,
        bool $lockRows = false
    ): void {
        $conflict = self::findConflict($staffId, $date, $excludeOrderId, $lockRows);
        if (!$conflict) {
            return;
        }

        $staffName = (string)(Staff::where('id', $staffId)->value('name') ?? '');

        throw ScheduleOccupancyException::conflict(
            $staffName,
            trim($date),
            (string)($conflict['order_sn'] ?? ''),
            $action
        );
    }

    /**
     * @notes 校验整单所有有效服务项是否可以占用档期
     * @param int $orderId
     * @param string $action
     * @param bool $lockRows
     * @param int $onlyStaffId 只校验指定服务人员（服务人员确认本人订单项时使用）
     * @return void
     * @throws ScheduleOccupancyException
     */
    public static function assertOrderItemsFree(
        int $orderId,
        string $action,
        bool $lockRows = false,
        int $onlyStaffId = 0
    ): void {
        if ($orderId <= 0) {
            return;
        }

        $query = Db::name('order_item')
            ->where('order_id', $orderId)
            ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereIn('item_type', [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF]);
        if ($onlyStaffId > 0) {
            $query->where('staff_id', $onlyStaffId);
        }

        $items = $query->field('staff_id, service_date')->select()->toArray();

        foreach ($items as $item) {
            $staffId = (int)($item['staff_id'] ?? 0);
            $date = trim((string)($item['service_date'] ?? ''));
            if ($staffId <= 0 || $date === '') {
                continue;
            }

            self::assertDateFree($staffId, $date, $orderId, $action, $lockRows);
        }
    }

    /**
     * @notes 内存判定：单条订单状态是否构成档期占用
     *
     * 供已加载订单对象的场景复用，避免再查一次库。
     * @param array $orderState
     * @return bool
     */
    public static function isOrderOccupying(array $orderState): bool
    {
        if ((int)($orderState['delete_time'] ?? 0) > 0) {
            return false;
        }

        if (!in_array((int)($orderState['order_status'] ?? -1), self::CONFIRMED_ORDER_STATUSES, true)) {
            return false;
        }

        return (float)($orderState['paid_amount'] ?? 0) > 0
            || (int)($orderState['deposit_paid'] ?? 0) === 1
            || (int)($orderState['pay_status'] ?? 0) === Order::PAY_STATUS_PAID;
    }

    /**
     * @notes 按订单 ID 判定是否仍在占用档期
     * @param int $orderId
     * @return bool
     */
    public static function isOrderOccupyingById(int $orderId): bool
    {
        if ($orderId <= 0) {
            return false;
        }

        $order = Order::field('id, order_status, pay_status, paid_amount, deposit_paid, delete_time')->find($orderId);

        return $order ? self::isOrderOccupying($order->toArray()) : false;
    }

    /**
     * @notes 释放用户下单时持有的临时档期锁
     *
     * 未付款订单不占用档期，因此订单创建成功后必须主动释放 Redis/记录锁，
     * 否则该档期会在锁 TTL 内继续阻塞其他用户。
     * @param int $staffId
     * @param string $date
     * @param int $userId
     * @return bool
     */
    public static function releaseUserHold(int $staffId, string $date, int $userId): bool
    {
        return \app\common\model\schedule\Schedule::releaseUserHold($staffId, $date, $userId);
    }
}
