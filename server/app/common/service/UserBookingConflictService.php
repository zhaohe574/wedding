<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 用户重复预约统一校验
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\exception\UserBookingConflictException;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\schedule\Schedule;
use think\facade\Db;

/**
 * 同一用户 + 同一服务人员 + 同一自然日 的重复/重叠预约校验。
 *
 * 业务口径：
 *   1. 「自然日」指订单项的服务日期 `order_item.service_date`，不是下单时间；
 *   2. 时间维度取 `order_item.time_slot`：0=全天，与任何时段重叠；1/2/3 为具体时段，
 *      仅相同值重叠。当前业务只开放全天，因此实际等价于「同一天即冲突」；
 *   3. 冲突状态包含待确认、待支付、待服务、服务中、已完成、已评价、已暂停、退款中；
 *      已取消、已退款、用户已删除不参与冲突判定。
 *
 * 并发：
 *   - 创建/修改事务内先对 `la_order.user_id` 做 `FOR UPDATE` 当前读，避免
 *     REPEATABLE READ 快照漏掉「等待锁期间」其他事务已提交的预约；
 *   - 创建/修改前按「用户+人员+日期」获取 MySQL 命名锁（GET_LOCK），把
 *     「查冲突 → 写订单」串行化；不同人员/日期互不阻塞；
 *   - 命名锁在事务提交后立即释放，异常/进程退出时由连接关闭自动释放。
 */
class UserBookingConflictService
{
    /** 参与重复预约判定的订单状态（已提交且未释放的预约） */
    public const CONFLICT_ORDER_STATUSES = [
        Order::STATUS_PENDING_CONFIRM,  // 0 待确认
        Order::STATUS_PENDING_PAY,      // 1 待支付
        Order::STATUS_PENDING_SERVICE,  // 2 待服务
        Order::STATUS_IN_SERVICE,       // 3 服务中
        Order::STATUS_COMPLETED,        // 4 已完成
        Order::STATUS_REVIEWED,         // 5 已评价
        Order::STATUS_PAUSED,           // 7 已暂停（原预约仍占用）
        Order::STATUS_REFUNDING,        // 10 退款中（退款完成前仍占用）
    ];

    /** 订单状态文案（仅用于冲突提示） */
    private const STATUS_LABELS = [
        Order::STATUS_PENDING_CONFIRM => '待确认',
        Order::STATUS_PENDING_PAY => '待支付',
        Order::STATUS_PENDING_SERVICE => '待服务',
        Order::STATUS_IN_SERVICE => '服务中',
        Order::STATUS_COMPLETED => '已完成',
        Order::STATUS_REVIEWED => '已评价',
        Order::STATUS_PAUSED => '已暂停',
        Order::STATUS_REFUNDING => '退款中',
    ];

    /** 命名锁等待秒数：并发重复提交时短暂等待，超时直接提示 */
    private const LOCK_WAIT_SECONDS = 3;

    /**
     * @notes 订单状态是否参与重复预约判定
     * @param int $status
     * @return bool
     */
    public static function isConflictStatus(int $status): bool
    {
        return in_array($status, self::CONFLICT_ORDER_STATUSES, true);
    }

    /**
     * @notes 两个时间段是否重叠
     *
     * 0=全天，与任何时段重叠；具体时段仅相同值重叠。
     * @param int $left
     * @param int $right
     * @return bool
     */
    public static function timeSlotOverlaps(int $left, int $right): bool
    {
        if ($left === Schedule::TIME_SLOT_ALL || $right === Schedule::TIME_SLOT_ALL) {
            return true;
        }

        return $left === $right;
    }

    /**
     * @notes 重复预约基础查询
     * @return \think\db\Query
     */
    public static function conflictQuery()
    {
        $orderTable = (new Order())->getTable();

        return Db::name('order_item')->alias('oi')
            ->join($orderTable . ' o', 'o.id = oi.order_id')
            ->whereIn('oi.item_type', [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF])
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereIn('o.order_status', self::CONFLICT_ORDER_STATUSES)
            ->whereNull('o.delete_time');
    }

    /**
     * @notes 查找冲突预约
     * @param int $userId
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $excludeOrderId 修改场景排除当前订单
     * @param int $excludeItemId 修改场景排除当前订单项
     * @return array|null
     */
    public static function findConflict(
        int $userId,
        int $staffId,
        string $date,
        int $timeSlot = Schedule::TIME_SLOT_ALL,
        int $excludeOrderId = 0,
        int $excludeItemId = 0,
        ?array $scopeOrderIds = null
    ): ?array {
        if ($userId <= 0 || $staffId <= 0 || trim($date) === '') {
            return null;
        }

        $query = self::conflictQuery()
            ->where('o.user_id', $userId)
            ->where('oi.staff_id', $staffId)
            ->where('oi.service_date', $date)
            ->field('oi.id AS item_id, oi.order_id, oi.staff_id, oi.service_date, oi.time_slot, o.order_sn, o.order_status');

        // 创建/修改的权威校验：只查当前读锁定的用户订单，并用 FOR UPDATE 绕过事务快照
        if ($scopeOrderIds !== null) {
            if (empty($scopeOrderIds)) {
                return null;
            }
            $query->whereIn('oi.order_id', $scopeOrderIds)->lock(true);
        }

        // 新预约是全天时，任何时段都冲突；是具体时段时，只与全天或同一时段冲突
        if ($timeSlot !== Schedule::TIME_SLOT_ALL) {
            $query->whereIn('oi.time_slot', [Schedule::TIME_SLOT_ALL, $timeSlot]);
        }
        if ($excludeOrderId > 0) {
            $query->where('oi.order_id', '<>', $excludeOrderId);
        }
        if ($excludeItemId > 0) {
            $query->where('oi.id', '<>', $excludeItemId);
        }

        $row = $query->find();

        return $row ?: null;
    }

    /**
     * @notes 校验单个「用户+人员+日期+时段」是否可预约
     * @param int $userId
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $excludeOrderId
     * @param string $action 仅用于后续扩展日志，不拼接进用户提示
     * @return void
     * @throws UserBookingConflictException
     */
    public static function assertBookable(
        int $userId,
        int $staffId,
        string $date,
        int $timeSlot = Schedule::TIME_SLOT_ALL,
        int $excludeOrderId = 0,
        string $action = '',
        ?array $scopeOrderIds = null
    ): void {
        $conflict = self::findConflict($userId, $staffId, $date, $timeSlot, $excludeOrderId, 0, $scopeOrderIds);
        if (!$conflict) {
            return;
        }

        $sameSlot = $timeSlot !== Schedule::TIME_SLOT_ALL
            && (int)($conflict['time_slot'] ?? Schedule::TIME_SLOT_ALL) !== Schedule::TIME_SLOT_ALL;

        throw UserBookingConflictException::duplicate(
            trim($date),
            (string)($conflict['order_sn'] ?? ''),
            self::statusLabel((int)($conflict['order_status'] ?? -1)),
            $sameSlot
        );
    }

    /**
     * @notes 校验一组待创建/待修改的预约项
     *
     * 同时拦截「同一请求内重复选择同一人员同一天」的情况。
     * @param int $userId
     * @param array $items 每项需含 staff_id + (schedule_date|service_date) + 可选 time_slot
     * @param int $excludeOrderId
     * @param string $action
     * @return void
     * @throws UserBookingConflictException
     */
    public static function assertItemsBookable(
        int $userId,
        array $items,
        int $excludeOrderId = 0,
        string $action = '',
        ?array $scopeOrderIds = null
    ): void {
        $seen = [];

        foreach ($items as $item) {
            $itemType = (int)($item['item_type'] ?? OrderItem::TYPE_SERVICE);
            if (!in_array($itemType, [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF], true)) {
                continue;
            }

            $staffId = (int)($item['staff_id'] ?? 0);
            $date = trim((string)($item['schedule_date'] ?? $item['service_date'] ?? ''));
            $timeSlot = (int)($item['time_slot'] ?? Schedule::TIME_SLOT_ALL);
            if ($staffId <= 0 || $date === '') {
                continue;
            }

            foreach ($seen as $previous) {
                if (
                    $previous['staff_id'] === $staffId
                    && $previous['date'] === $date
                    && self::timeSlotOverlaps($previous['time_slot'], $timeSlot)
                ) {
                    throw UserBookingConflictException::duplicatedInPayload(
                        (string)($item['staff_name'] ?? $previous['staff_name'] ?? ''),
                        $date
                    );
                }
            }
            $seen[] = [
                'staff_id' => $staffId,
                'date' => $date,
                'time_slot' => $timeSlot,
                'staff_name' => (string)($item['staff_name'] ?? ''),
            ];

            self::assertBookable($userId, $staffId, $date, $timeSlot, $excludeOrderId, $action, $scopeOrderIds);
        }
    }

    /**
     * @notes 校验同一批订单项内不存在同一人员重复（改期后全部落到同一天时使用）
     * @param array $items 每项需含 staff_id，可选 staff_name
     * @param string $date
     * @return void
     * @throws UserBookingConflictException
     */
    public static function assertNoDuplicateStaffInItems(array $items, string $date): void
    {
        $seen = [];
        foreach ($items as $item) {
            $staffId = (int)($item['staff_id'] ?? 0);
            if ($staffId <= 0) {
                continue;
            }
            if (isset($seen[$staffId])) {
                throw UserBookingConflictException::duplicatedInPayload(
                    (string)($item['staff_name'] ?? ''),
                    $date
                );
            }
            $seen[$staffId] = true;
        }
    }

    /**
     * @notes 校验当前订单内是否已有同一人员同一天的预约项（加项场景）
     * @param int $orderId
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $excludeItemId
     * @return void
     * @throws UserBookingConflictException
     */
    public static function assertNoIntraOrderDuplicate(
        int $orderId,
        int $staffId,
        string $date,
        int $timeSlot = Schedule::TIME_SLOT_ALL,
        int $excludeItemId = 0
    ): void {
        if ($orderId <= 0 || $staffId <= 0 || trim($date) === '') {
            return;
        }

        $query = Db::name('order_item')
            ->where('order_id', $orderId)
            ->where('staff_id', $staffId)
            ->where('service_date', $date)
            ->where('item_status', '<>', OrderItem::STATUS_CANCELLED);
        if ($excludeItemId > 0) {
            $query->where('id', '<>', $excludeItemId);
        }

        foreach ($query->field('id, time_slot')->select()->toArray() as $existing) {
            if (self::timeSlotOverlaps((int)($existing['time_slot'] ?? Schedule::TIME_SLOT_ALL), $timeSlot)) {
                throw UserBookingConflictException::duplicatedInPayload('', trim($date));
            }
        }
    }

    /**
     * @notes 锁定用户维度的订单范围（当前读）
     *
     * REPEATABLE READ 下普通 SELECT 使用事务快照，可能看不到「等待命名锁期间」其他事务
     * 已提交的预约。这里用 FOR UPDATE 做当前读，并对 user_id 索引加范围锁，
     * 保证「查冲突 → 写订单」在同一用户维度串行。必须在事务内调用。
     *
     * 返回当前读到的订单 ID，供后续冲突查询使用（避免再用快照读漏单）。
     *
     * @param int $userId
     * @return array
     */
    public static function lockUserScope(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        return array_values(array_map(
            'intval',
            Db::name('order')->where('user_id', $userId)->lock(true)->column('id')
        ));
    }

    /**
     * @notes 为待创建/待修改的预约项获取并发互斥锁
     * @param int $userId
     * @param array $items
     * @param int $timeout
     * @return array 已获取的锁名，用于 releaseLocks()
     * @throws UserBookingConflictException
     */
    public static function acquireForItems(int $userId, array $items, int $timeout = self::LOCK_WAIT_SECONDS): array
    {
        if ($userId <= 0) {
            return [];
        }

        $names = [];
        foreach ($items as $item) {
            $itemType = (int)($item['item_type'] ?? OrderItem::TYPE_SERVICE);
            if (!in_array($itemType, [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF], true)) {
                continue;
            }

            $staffId = (int)($item['staff_id'] ?? 0);
            $date = trim((string)($item['schedule_date'] ?? $item['service_date'] ?? ''));
            if ($staffId <= 0 || $date === '') {
                continue;
            }
            $timeSlot = (int)($item['time_slot'] ?? Schedule::TIME_SLOT_ALL);
            $names[] = self::dateLockName($userId, $staffId, $date, $timeSlot);
        }

        return self::acquireLocks($names, $timeout);
    }

    /**
     * @notes 获取一组命名锁（按名称排序，避免多人员订单互相死锁）
     * @param array $names
     * @param int $timeout
     * @return array
     * @throws UserBookingConflictException
     */
    public static function acquireLocks(array $names, int $timeout = self::LOCK_WAIT_SECONDS): array
    {
        $names = array_values(array_unique(array_filter($names)));
        sort($names);
        if (empty($names)) {
            return [];
        }

        $acquired = [];
        try {
            foreach ($names as $name) {
                if (!self::acquireNamedLock($name, $timeout)) {
                    throw UserBookingConflictException::submitting();
                }
                $acquired[] = $name;
            }
        } catch (\Throwable $e) {
            self::releaseLocks($acquired);
            throw $e;
        }

        return $acquired;
    }

    /**
     * @notes 释放命名锁（幂等）
     * @param array $names
     * @return void
     */
    public static function releaseLocks(array $names): void
    {
        foreach (array_reverse($names) as $name) {
            try {
                Db::query('SELECT RELEASE_LOCK(?) AS released', [$name]);
            } catch (\Throwable $e) {
                // 连接已断开时锁会随连接关闭自动释放
            }
        }
    }

    /**
     * @notes 订单级互斥锁：同一订单的改期/恢复串行执行
     * @param int $orderId
     * @return string
     */
    public static function orderLockName(int $orderId): string
    {
        return 'ub:o:' . $orderId;
    }

    /**
     * @notes 日期级互斥锁名（MySQL 命名锁最长 64 字符）
     * @param int $userId
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @return string
     */
    public static function dateLockName(int $userId, int $staffId, string $date, int $timeSlot = 0): string
    {
        return sprintf(
            'ub:d:%d:%d:%s:%d',
            $userId,
            $staffId,
            str_replace('-', '', $date),
            $timeSlot
        );
    }

    /**
     * @notes 订单状态文案
     * @param int $status
     * @return string
     */
    public static function statusLabel(int $status): string
    {
        return self::STATUS_LABELS[$status] ?? '未知状态';
    }

    /**
     * @notes 获取 MySQL 命名锁
     * @param string $name
     * @param int $timeout
     * @return bool
     */
    private static function acquireNamedLock(string $name, int $timeout): bool
    {
        $row = Db::query('SELECT GET_LOCK(?, ?) AS locked', [$name, max(0, $timeout)]);
        $locked = $row[0]['locked'] ?? 0;

        return (int)$locked === 1;
    }
}
