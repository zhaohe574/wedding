<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 预约业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\schedule;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderLog;
use app\common\model\schedule\Schedule;
use think\facade\Db;

/**
 * 预约业务逻辑（基于订单项）
 * Class BookingLogic
 * @package app\adminapi\logic\schedule
 */
class BookingLogic extends BaseLogic
{
    /**
     * @notes 预约详情
     * @param int $staffId
     * @param int $itemId
     * @return array|null
     */
    public static function detail(int $staffId, int $itemId): ?array
    {
        $item = OrderItem::alias('oi')
            ->leftJoin('la_order o', 'o.id = oi.order_id')
            ->leftJoin('la_user u', 'u.id = o.user_id')
            ->field([
                'oi.id',
                'oi.order_id',
                'oi.staff_id',
                'oi.staff_name',
                'oi.package_name',
                'oi.service_date',
                'oi.item_status',
                'oi.confirm_status',
                'oi.schedule_id',
                'oi.price',
                'oi.quantity',
                'oi.subtotal',
                'oi.remark',
                'o.order_sn',
                'o.order_status',
                'o.pay_status',
                'o.pay_type',
                'o.total_amount',
                'o.discount_amount',
                'o.coupon_amount',
                'o.pay_amount',
                'o.contact_name',
                'o.contact_mobile',
                'o.create_time',
                'u.nickname as user_nickname',
                'u.mobile as user_mobile',
            ])
            ->where('oi.id', $itemId)
            ->where('oi.staff_id', $staffId)
            ->find();

        if (!$item) {
            self::setError('预约项不存在');
            return null;
        }

        $data = $item->toArray();
        $data['customer_name'] = $data['contact_name'] ?: ($data['user_nickname'] ?? '');
        $data['customer_phone'] = $data['contact_mobile'] ?: ($data['user_mobile'] ?? '');
        $data['item_status_desc'] = self::getItemStatusDesc((int)$data['item_status']);
        $data['confirm_status_desc'] = (int)$data['confirm_status'] === 1 ? '已确认' : '待确认';

        return $data;
    }

    /**
     * @notes 预约确认（仅确认本人订单项）
     * @param int $staffId
     * @param int $adminId
     * @param int $itemId
     * @return bool
     */
    public static function confirm(int $staffId, int $adminId, int $itemId): bool
    {
        try {
            return Db::transaction(function () use ($staffId, $adminId, $itemId) {
                /** @var OrderItem|null $item */
                $item = OrderItem::where('id', $itemId)
                    ->where('staff_id', $staffId)
                    ->lock(true)
                    ->find();

                if (!$item) {
                    self::setError('预约项不存在');
                    return false;
                }

                /** @var Order|null $order */
                $order = Order::where('id', (int)$item->order_id)->lock(true)->find();
                if (!$order) {
                    self::setError('订单不存在');
                    return false;
                }

                if ((int)$order->order_status !== Order::STATUS_PENDING_CONFIRM) {
                    self::setError('当前订单状态不可确认');
                    return false;
                }

                if ((int)$item->item_status === OrderItem::STATUS_CANCELLED) {
                    self::setError('该预约项已取消');
                    return false;
                }

                if ((int)$item->confirm_status === 1) {
                    self::setError('该预约项已确认');
                    return false;
                }

                if ((int)$item->schedule_id > 0) {
                    [$ok, $msg] = Schedule::confirmBooking(
                        (int)$item->staff_id,
                        (string)$item->service_date,
                        0,
                        (int)$order->id,
                        (int)$order->user_id
                    );
                    if (!$ok) {
                        throw new \RuntimeException($msg);
                    }
                }

                $item->confirm_status = 1;
                $item->update_time = time();
                $item->save();

                // 全部有效项均确认后，推进整单到待支付
                $remain = OrderItem::where('order_id', (int)$order->id)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                    ->where('confirm_status', 0)
                    ->count();

                if ($remain === 0 && (int)$order->order_status === Order::STATUS_PENDING_CONFIRM) {
                    $beforeStatus = (int)$order->order_status;
                    $order->order_status = Order::STATUS_PENDING_PAY;
                    $order->update_time = time();
                    $order->save();

                    OrderLog::addLog(
                        (int)$order->id,
                        OrderLog::OPERATOR_ADMIN,
                        $adminId,
                        'confirm',
                        $beforeStatus,
                        Order::STATUS_PENDING_PAY,
                        '服务人员确认完成，订单进入待支付'
                    );
                } else {
                    OrderLog::addLog(
                        (int)$order->id,
                        OrderLog::OPERATOR_ADMIN,
                        $adminId,
                        'confirm_item',
                        (int)$order->order_status,
                        (int)$order->order_status,
                        '服务人员确认预约项：' . (string)$item->id
                    );
                }

                return true;
            });
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 取消预约项（仅取消本人项）
     * @param int $staffId
     * @param int $adminId
     * @param int $itemId
     * @param string $reason
     * @return bool
     */
    public static function cancel(int $staffId, int $adminId, int $itemId, string $reason = ''): bool
    {
        try {
            return Db::transaction(function () use ($staffId, $adminId, $itemId, $reason) {
                /** @var OrderItem|null $item */
                $item = OrderItem::where('id', $itemId)
                    ->where('staff_id', $staffId)
                    ->lock(true)
                    ->find();

                if (!$item) {
                    self::setError('预约项不存在');
                    return false;
                }

                /** @var Order|null $order */
                $order = Order::where('id', (int)$item->order_id)->lock(true)->find();
                if (!$order) {
                    self::setError('订单不存在');
                    return false;
                }

                if ((int)$order->order_status !== Order::STATUS_PENDING_CONFIRM) {
                    self::setError('仅待确认订单允许取消预约项');
                    return false;
                }

                if ((int)$order->pay_status !== Order::PAY_STATUS_UNPAID) {
                    self::setError('已支付链路不允许取消预约项');
                    return false;
                }

                if ((int)$item->item_status === OrderItem::STATUS_CANCELLED) {
                    self::setError('该预约项已取消');
                    return false;
                }

                $item->item_status = OrderItem::STATUS_CANCELLED;
                // 标记为已处理，避免继续出现在“待确认订单项”
                $item->confirm_status = 1;
                $item->update_time = time();
                $item->save();

                if ((int)$item->schedule_id > 0) {
                    Schedule::releaseLock((int)$item->schedule_id);
                }

                // 自动重算继续：优惠金额保持不变
                $activeItemsQuery = OrderItem::where('order_id', (int)$order->id)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED);

                $activeCount = (clone $activeItemsQuery)->count();
                $activeSubtotal = (float)(clone $activeItemsQuery)->sum('subtotal');

                $discount = (float)$order->discount_amount;
                $coupon = (float)$order->coupon_amount;
                $payAmount = round(max($activeSubtotal - $discount - $coupon, 0), 2);

                $beforeStatus = (int)$order->order_status;
                $order->total_amount = round($activeSubtotal, 2);
                $order->pay_amount = $payAmount;
                $order->update_time = time();

                if ($activeCount === 0) {
                    $order->order_status = Order::STATUS_CANCELLED;
                    $order->cancel_reason = $reason ?: '服务人员取消全部预约项';
                    $order->cancel_time = time();
                } else {
                    $remainUnConfirm = OrderItem::where('order_id', (int)$order->id)
                        ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                        ->where('confirm_status', 0)
                        ->count();
                    $order->order_status = $remainUnConfirm > 0 ? Order::STATUS_PENDING_CONFIRM : Order::STATUS_PENDING_PAY;
                }
                $order->save();

                OrderLog::addLog(
                    (int)$order->id,
                    OrderLog::OPERATOR_ADMIN,
                    $adminId,
                    'cancel_item',
                    $beforeStatus,
                    (int)$order->order_status,
                    '服务人员取消预约项：' . (string)$item->id . ($reason ? ('，原因：' . $reason) : '')
                );

                return true;
            });
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 预约统计
     * @param int $staffId
     * @return array
     */
    public static function statistics(int $staffId): array
    {
        $baseQuery = OrderItem::alias('oi')
            ->leftJoin('la_order o', 'o.id = oi.order_id')
            ->where('oi.staff_id', $staffId)
            ->where('o.delete_time', null);

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)
                ->where('oi.confirm_status', 0)
                ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
                ->count(),
            'confirmed' => (clone $baseQuery)
                ->where('oi.confirm_status', 1)
                ->where('oi.item_status', 'in', [OrderItem::STATUS_PENDING, OrderItem::STATUS_IN_SERVICE])
                ->count(),
            'completed' => (clone $baseQuery)->where('oi.item_status', OrderItem::STATUS_COMPLETED)->count(),
            'cancelled' => (clone $baseQuery)->where('oi.item_status', OrderItem::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * @notes 订单项状态文案
     * @param int $status
     * @return string
     */
    private static function getItemStatusDesc(int $status): string
    {
        $map = [
            OrderItem::STATUS_PENDING => '待服务',
            OrderItem::STATUS_IN_SERVICE => '服务中',
            OrderItem::STATUS_COMPLETED => '已完成',
            OrderItem::STATUS_CANCELLED => '已取消',
        ];
        return $map[$status] ?? '未知';
    }
}
