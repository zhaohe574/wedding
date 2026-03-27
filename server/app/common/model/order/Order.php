<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;
use app\common\model\user\User;
use app\common\model\schedule\Schedule;
use app\common\model\package\PackageBooking;
use app\common\service\ConfigService;
use app\common\service\OrderNotificationService;
use think\model\concern\SoftDelete;
use think\facade\Db;

/**
 * 订单模型
 * Class Order
 * @package app\common\model\order
 */
class Order extends BaseModel
{
    use SoftDelete;

    protected $name = 'order';
    protected $deleteTime = 'delete_time';

    // 订单类型
    const TYPE_NORMAL = 1;      // 普通订单
    const TYPE_PACKAGE = 2;     // 套餐订单
    const TYPE_COMBO = 3;       // 组合订单

    // 订单状态
    const STATUS_PENDING_CONFIRM = 0; // 待确认
    const STATUS_PENDING_PAY = 1;     // 待支付
    const STATUS_PAID = 2;            // 已支付
    const STATUS_IN_SERVICE = 3;      // 服务中
    const STATUS_COMPLETED = 4;       // 已完成
    const STATUS_REVIEWED = 5;        // 已评价
    const STATUS_CANCELLED = 6;       // 已取消
    const STATUS_PAUSED = 7;          // 已暂停
    const STATUS_REFUNDED = 8;        // 已退款

    // 支付状态
    const PAY_STATUS_UNPAID = 0;        // 未支付
    const PAY_STATUS_PAID = 1;          // 已支付
    const PAY_STATUS_PARTIAL_REFUND = 2; // 部分退款
    const PAY_STATUS_FULL_REFUND = 3;   // 全额退款

    // 支付方式
    const PAY_WAY_NONE = 0;        // 未支付
    const PAY_WAY_WECHAT = 1;      // 微信
    const PAY_WAY_ALIPAY = 2;      // 支付宝
    const PAY_WAY_BALANCE = 3;     // 余额
    const PAY_WAY_OFFLINE = 4;     // 线下
    const PAY_WAY_COMBINATION = 5; // 组合支付

    // 线下支付凭证状态
    const VOUCHER_STATUS_PENDING = 0;  // 待审核
    const VOUCHER_STATUS_APPROVED = 1; // 已通过
    const VOUCHER_STATUS_REJECTED = 2; // 已拒绝

    // 订单来源
    const SOURCE_MINIAPP = 1;   // 小程序
    const SOURCE_H5 = 2;        // H5
    const SOURCE_ADMIN = 3;     // 后台

    const AUTO_CANCEL_REASON = '支付超时自动取消';
    const AUTO_CANCEL_MESSAGE = '订单支付超时，已自动取消';

    /**
     * @notes 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @notes 关联订单项
     * @return \think\model\relation\HasMany
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    /**
     * @notes 关联支付记录
     * @return \think\model\relation\HasMany
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id', 'id');
    }

    /**
     * @notes 关联订单日志
     * @return \think\model\relation\HasMany
     */
    public function logs()
    {
        return $this->hasMany(OrderLog::class, 'order_id', 'id');
    }

    /**
     * @notes 订单状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getOrderStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING_CONFIRM => '待确认',
            self::STATUS_PENDING_PAY => '待支付',
            self::STATUS_PAID => '已支付',
            self::STATUS_IN_SERVICE => '服务中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_REVIEWED => '已评价',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_PAUSED => '已暂停',
            self::STATUS_REFUNDED => '已退款',
        ];
        return $map[$data['order_status']] ?? '未知';
    }

    /**
     * @notes 支付状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayStatusDescAttr($value, $data): string
    {
        $map = [
            self::PAY_STATUS_UNPAID => '未支付',
            self::PAY_STATUS_PAID => '已支付',
            self::PAY_STATUS_PARTIAL_REFUND => '部分退款',
            self::PAY_STATUS_FULL_REFUND => '全额退款',
        ];
        return $map[$data['pay_status']] ?? '未知';
    }

    /**
     * @notes 支付方式描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayTypeDescAttr($value, $data): string
    {
        $map = [
            self::PAY_WAY_NONE => '未支付',
            self::PAY_WAY_WECHAT => '微信支付',
            self::PAY_WAY_ALIPAY => '支付宝',
            self::PAY_WAY_BALANCE => '余额支付',
            self::PAY_WAY_OFFLINE => '线下支付',
            self::PAY_WAY_COMBINATION => '组合支付',
        ];
        return $map[$data['pay_type']] ?? '未知';
    }

    /**
     * @notes 线下凭证状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayVoucherStatusDescAttr($value, $data): string
    {
        if (empty($data['pay_voucher'] ?? '')) {
            return '未上传';
        }
        $map = [
            self::VOUCHER_STATUS_PENDING => '待审核',
            self::VOUCHER_STATUS_APPROVED => '已通过',
            self::VOUCHER_STATUS_REJECTED => '已拒绝',
        ];
        return $map[$data['pay_voucher_status']] ?? '未知';
    }

    /**
     * @notes 服务地区文本获取器
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getServiceRegionTextAttr($value, $data): string
    {
        $names = array_filter([
            trim((string)($data['service_province'] ?? '')),
            trim((string)($data['service_city'] ?? '')),
            trim((string)($data['service_district'] ?? '')),
        ]);

        return implode(' ', $names);
    }

    /**
     * @notes 生成订单号
     * @return string
     */
    public static function generateOrderSn(): string
    {
        return date('YmdHis') . str_pad((string)mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 是否启用未支付自动取消
     * @return bool
     */
    public static function isUnpaidAutoCancelEnabled(): bool
    {
        return (int)ConfigService::get('transaction', 'cancel_unpaid_orders', 1) === 1;
    }

    /**
     * @notes 获取未支付自动取消分钟数
     * @return int
     */
    public static function getUnpaidAutoCancelMinutes(): int
    {
        return max((int)ConfigService::get('transaction', 'cancel_unpaid_orders_times', 30), 1);
    }

    /**
     * @notes 是否为线下凭证待审核
     * @return bool
     */
    public function isOfflineVoucherPending(): bool
    {
        return (int)$this->pay_type === self::PAY_WAY_OFFLINE
            && !empty($this->pay_voucher)
            && (int)($this->pay_voucher_status ?? -1) === self::VOUCHER_STATUS_PENDING;
    }

    /**
     * @notes 是否处于首笔待支付阶段
     * @return bool
     */
    public function isInFirstPendingPaymentStage(): bool
    {
        if ((int)$this->order_status !== self::STATUS_PENDING_PAY) {
            return false;
        }

        if ((float)$this->deposit_amount > 0) {
            return !(int)$this->deposit_paid;
        }

        if ((int)$this->pay_status === self::PAY_STATUS_PAID) {
            return false;
        }

        return round(max((float)$this->pay_amount - (float)($this->paid_amount ?? 0), 0), 2) > 0;
    }

    /**
     * @notes 是否展示支付倒计时
     * @return bool
     */
    public function shouldDisplayPayCountdown(): bool
    {
        return self::isUnpaidAutoCancelEnabled()
            && $this->isInFirstPendingPaymentStage()
            && !$this->isOfflineVoucherPending()
            && (int)($this->pay_deadline_time ?? 0) > 0;
    }

    /**
     * @notes 获取支付剩余秒数
     * @return int
     */
    public function getPayRemainSeconds(): int
    {
        if (!$this->shouldDisplayPayCountdown()) {
            return 0;
        }

        return max((int)($this->pay_deadline_time ?? 0) - time(), 0);
    }

    /**
     * @notes 是否符合自动取消未支付条件
     * @return bool
     */
    public function shouldAutoCancelExpiredUnpaid(): bool
    {
        return $this->shouldDisplayPayCountdown()
            && (int)($this->pay_deadline_time ?? 0) > 0
            && (int)$this->pay_deadline_time <= time();
    }

    /**
     * @notes 构建支付截止时间
     * @param int $startTime
     * @return int
     */
    public static function buildPayDeadlineTime(int $startTime): int
    {
        if (!self::isUnpaidAutoCancelEnabled()) {
            return 0;
        }

        return $startTime + self::getUnpaidAutoCancelMinutes() * 60;
    }

    /**
     * @notes 同步待支付截止时间
     * @param Order $order
     * @param int|null $startTime
     * @param bool $persist
     * @return int
     */
    public static function syncPendingPayDeadline(self $order, ?int $startTime = null, bool $persist = true): int
    {
        $deadlineTime = 0;
        if (self::isUnpaidAutoCancelEnabled() && $order->isInFirstPendingPaymentStage()) {
            $deadlineTime = self::buildPayDeadlineTime($startTime ?? time());
        }

        $order->pay_deadline_time = $deadlineTime;

        if ($persist) {
            $order->update_time = time();
            $order->save();
        }

        return $deadlineTime;
    }

    /**
     * @notes 清空支付截止时间
     * @param Order $order
     * @param bool $persist
     * @return void
     */
    public static function clearPayDeadline(self $order, bool $persist = true): void
    {
        $order->pay_deadline_time = 0;

        if ($persist) {
            $order->update_time = time();
            $order->save();
        }
    }

    /**
     * @notes 用数据库最新状态刷新运行时订单对象，避免并发下继续使用旧状态
     * @param Order $target
     * @param Order $source
     * @return void
     */
    protected static function refreshRuntimeState(self $target, self $source): void
    {
        foreach ([
            'order_status',
            'pay_status',
            'pay_type',
            'pay_time',
            'pay_deadline_time',
            'cancel_reason',
            'cancel_time',
            'deposit_paid',
            'balance_paid',
            'paid_amount',
            'update_time',
        ] as $field) {
            $target->{$field} = $source->{$field};
        }
    }

    /**
     * @notes 同步触发超时取消
     * @param Order $order
     * @return bool
     */
    public static function syncExpiredAutoCancel(self $order): bool
    {
        if (!$order->shouldAutoCancelExpiredUnpaid()) {
            return false;
        }

        [$success, ] = self::autoCancelExpiredOrder((int)$order->id);
        if ($success) {
            $order->order_status = self::STATUS_CANCELLED;
            $order->cancel_reason = self::AUTO_CANCEL_REASON;
            $order->cancel_time = time();
            $order->pay_deadline_time = 0;
            $order->pay_status = self::PAY_STATUS_UNPAID;
            return true;
        }

        $latestOrder = self::find((int)$order->id);
        if ($latestOrder) {
            self::refreshRuntimeState($order, $latestOrder);
        }

        return false;
    }

    /**
     * @notes 自动取消超时未支付订单
     * @param int $orderId
     * @param string $reason
     * @return array
     */
    public static function autoCancelExpiredOrder(int $orderId, string $reason = self::AUTO_CANCEL_REASON): array
    {
        $order = self::find($orderId);
        if (!$order) {
            return [false, '订单不存在'];
        }

        if (!$order->shouldAutoCancelExpiredUnpaid()) {
            return [false, '订单未达到自动取消条件'];
        }

        [$success, $message] = self::cancelOrder($orderId, 0, OrderLog::OPERATOR_SYSTEM, $reason);
        if (!$success) {
            return [$success, $message];
        }

        Payment::markOrderPendingAsFailed($orderId);
        return [true, $message];
    }

    /**
     * @notes 创建订单
     * @param int $userId
     * @param array $selectedItems 已选服务项
     * @param array $orderInfo 订单信息
     * @return array [bool $success, string $message, Order|null $order]
     */
    public static function createOrder(int $userId, array $selectedItems, array $orderInfo): array
    {
        Db::startTrans();
        try {
            // 计算订单金额
            $serviceAmount = 0;
            $addonAmount = 0;
            foreach ($selectedItems as $item) {
                $itemAmount = round((float)$item['price'] * (int)($item['quantity'] ?? 1), 2);
                if ((int)($item['item_type'] ?? OrderItem::TYPE_SERVICE) === OrderItem::TYPE_SERVICE) {
                    $serviceAmount += $itemAmount;
                } else {
                    $addonAmount += $itemAmount;
                }

                foreach (($item['addons'] ?? []) as $addon) {
                    $addonAmount += (float)($addon['price'] ?? 0) * (int)($addon['quantity'] ?? 1);
                }
            }
            $serviceAmount = round($serviceAmount, 2);
            $addonAmount = round($addonAmount, 2);
            $totalAmount = round($serviceAmount + $addonAmount, 2);

            // 计算优惠
            $discountAmount = $orderInfo['discount_amount'] ?? 0;
            $payAmount = $totalAmount - $discountAmount;
            
            // 定金/尾款模式
            $depositAmount = 0;
            $balanceAmount = 0;
            if (!empty($orderInfo['deposit_ratio'])) {
                $depositAmount = round($payAmount * $orderInfo['deposit_ratio'] / 100, 2);
                $balanceAmount = $payAmount - $depositAmount;
            }

            $confirmLockDuration = 3600;
            $createdLegacyAddonSnapshots = false;

            // 创建订单
            $order = self::create([
                'order_sn' => self::generateOrderSn(),
                'user_id' => $userId,
                'order_type' => $orderInfo['order_type'] ?? self::TYPE_NORMAL,
                'order_status' => self::STATUS_PENDING_CONFIRM,
                'pay_status' => self::PAY_STATUS_UNPAID,
                'paid_amount' => 0,
                'total_amount' => $totalAmount,
                'addon_amount' => $addonAmount,
                'discount_amount' => $discountAmount,
                'pay_amount' => $payAmount,
                'deposit_amount' => $depositAmount,
                'balance_amount' => $balanceAmount,
                'service_date' => $orderInfo['date'] ?? $orderInfo['service_date'] ?? ($selectedItems[0]['schedule_date'] ?? null),
                'service_time_slot' => 0,
                'service_address' => $orderInfo['service_address'] ?? '',
                'service_province_code' => $orderInfo['province_code'] ?? '',
                'service_province' => $orderInfo['province_name'] ?? '',
                'service_city_code' => $orderInfo['city_code'] ?? '',
                'service_city' => $orderInfo['city_name'] ?? '',
                'service_district_code' => $orderInfo['district_code'] ?? '',
                'service_district' => $orderInfo['district_name'] ?? '',
                'contact_name' => $orderInfo['contact_name'] ?? '',
                'contact_mobile' => $orderInfo['contact_mobile'] ?? '',
                'wedding_date' => $orderInfo['wedding_date'] ?? null,
                'wedding_venue' => $orderInfo['wedding_venue'] ?? '',
                'user_remark' => $orderInfo['remark'] ?? '',
                'source' => $orderInfo['source'] ?? self::SOURCE_MINIAPP,
                'pay_type' => self::PAY_WAY_NONE,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 创建订单项
            foreach ($selectedItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'staff_id' => $item['staff_id'],
                    'package_id' => $item['package_id'] ?? 0,
                    'schedule_id' => $item['schedule_id'] ?? 0,
                    'service_date' => $item['schedule_date'],
                    'time_slot' => 0,
                    'staff_name' => $item['staff']['name'] ?? ($item['staff_name'] ?? ''),
                    'package_name' => $item['package']['name'] ?? ($item['package_name'] ?? ''),
                    'package_description' => OrderItem::resolvePackageDescription(
                        (int)($item['package_id'] ?? 0),
                        (string)($item['package']['description'] ?? ($item['package_description'] ?? ''))
                    ),
                    'price' => $item['price'],
                    'quantity' => (int)($item['quantity'] ?? 1),
                    'subtotal' => round((float)$item['price'] * (int)($item['quantity'] ?? 1), 2),
                    'item_type' => (int)($item['item_type'] ?? OrderItem::TYPE_SERVICE),
                    'item_meta' => $item['item_meta'] ?? [],
                    'confirm_status' => 0,
                    'remark' => $item['remark'] ?? '',
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

                if (
                    (int)($item['staff_id'] ?? 0) > 0 &&
                    !empty($item['schedule_date']) &&
                    in_array(
                        (int)($item['item_type'] ?? OrderItem::TYPE_SERVICE),
                        [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF],
                        true
                    )
                ) {
                    $scheduleResult = Schedule::confirmBooking(
                        (int)$item['staff_id'],
                        (string)$item['schedule_date'],
                        0,
                        (int)$order->id,
                        $userId
                    );
                    if (!($scheduleResult[0] ?? false)) {
                        throw new \Exception((string)($scheduleResult[1] ?? '档期锁定失败'));
                    }

                    $scheduleId = (int)($scheduleResult['schedule_id'] ?? 0);
                    if ($scheduleId > 0) {
                        $orderItem->schedule_id = $scheduleId;
                        $orderItem->time_slot = 0;
                        $orderItem->save();
                    }
                }

                if (
                    !empty($item['package_id']) &&
                    in_array(
                        (int)($item['item_type'] ?? OrderItem::TYPE_SERVICE),
                        [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF],
                        true
                    )
                ) {
                    $confirmed = PackageBooking::confirmSelection(
                        $userId,
                        (int)$item['package_id'],
                        (int)$item['staff_id'],
                        (string)$item['schedule_date'],
                        0,
                        (int)$order->id,
                        (int)$orderItem->id
                    );
                    if (!$confirmed) {
                        $availability = PackageBooking::checkAvailability(
                            (int)$item['package_id'],
                            (string)$item['schedule_date'],
                            (int)$item['staff_id'],
                            0
                        );
                        $message = $availability['available'] ?? false
                            ? '套餐预订锁定失败，请刷新后重试'
                            : ($availability['message'] ?? '套餐预订锁定失败');
                        throw new \Exception($message);
                    }
                }

                if (!empty($item['addons']) && is_array($item['addons'])) {
                    OrderItemAddon::createSnapshots(
                        (int)$order->id,
                        (int)$orderItem->id,
                        $item['addons'],
                        OrderItemAddon::SOURCE_ORDER
                    );
                    $createdLegacyAddonSnapshots = true;
                }
            }

            if ($createdLegacyAddonSnapshots) {
                OrderItemAddon::refreshOrderAmounts((int)$order->id);
            }

            // 记录订单日志
            OrderLog::addLog($order->id, 1, $userId, 'create', 0, self::STATUS_PENDING_CONFIRM, '创建订单');

            Db::commit();
            return [true, '订单创建成功', $order];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '订单创建失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 取消订单
     * @param int $orderId
     * @param int $operatorId
     * @param int $operatorType
     * @param string $reason
     * @return array [bool $success, string $message]
     */
    public static function cancelOrder(int $orderId, int $operatorId, int $operatorType = 1, string $reason = ''): array
    {
        $createdRefundId = 0;
        Db::startTrans();
        try {
            $order = self::where('id', $orderId)->lock(true)->find();
            if (!$order) {
                throw new \RuntimeException('订单不存在');
            }

            if (!in_array($order->order_status, [self::STATUS_PENDING_CONFIRM, self::STATUS_PENDING_PAY, self::STATUS_PAID])) {
                throw new \RuntimeException('当前订单状态不可取消');
            }

            $beforeStatus = $order->order_status;

            // 更新订单状态
            $order->order_status = self::STATUS_CANCELLED;
            $order->cancel_reason = $reason;
            $order->cancel_time = time();
            $order->pay_deadline_time = 0;
            $order->update_time = time();
            $order->save();

            // 释放档期
            $items = OrderItem::where('order_id', $orderId)->select();
            foreach ($items as $item) {
                if ($item->schedule_id > 0) {
                    Schedule::releaseLock($item->schedule_id);
                }
            }

            // 释放套餐预订锁
            PackageBooking::releaseByOrderId($orderId);

            // 记录日志
            OrderLog::addLog($orderId, $operatorType, $operatorId, 'cancel', $beforeStatus, self::STATUS_CANCELLED, '取消订单：' . $reason);

            // 已支付订单自动创建退款申请
            if ($beforeStatus == self::STATUS_PAID && $order->pay_status == self::PAY_STATUS_PAID) {
                $refundResult = Refund::createSystemRefund(
                    $orderId,
                    $operatorId,
                    $order->paid_amount > 0 ? $order->paid_amount : $order->pay_amount,
                    '订单取消自动退款：' . $reason,
                    $operatorType == OrderLog::OPERATOR_USER ? Refund::TYPE_USER : Refund::TYPE_ADMIN
                );
                
                if (!$refundResult[0]) {
                    // 退款创建失败，记录日志但不影响取消操作
                    OrderLog::addLog($orderId, OrderLog::OPERATOR_SYSTEM, 0, 'refund_create_fail', 0, 0, '自动退款创建失败：' . $refundResult[1]);
                } elseif (!empty($refundResult[2])) {
                    $createdRefundId = (int)$refundResult[2]->id;
                }
            }

            Db::commit();

            OrderNotificationService::notifyUserAndStaffOnOrderCancelled($orderId, $operatorType, $reason);
            if ($createdRefundId > 0) {
                OrderNotificationService::notifyUserAndStaffOnRefundApplied($createdRefundId);
            }
            return [true, '订单已取消'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, $e->getMessage()];
        }
    }

    /**
     * @notes 完成订单
     * @param int $orderId
     * @param int $operatorId
     * @param int $operatorType
     * @return array [bool $success, string $message]
     */
    public static function completeOrder(int $orderId, int $operatorId, int $operatorType = 1): array
    {
        $order = self::find($orderId);
        if (!$order) {
            return [false, '订单不存在'];
        }

        if ($order->order_status != self::STATUS_IN_SERVICE) {
            return [false, '当前订单状态不可完成'];
        }

        $beforeStatus = $order->order_status;
        $order->order_status = self::STATUS_COMPLETED;
        $order->complete_time = time();
        $order->update_time = time();
        $order->save();

        // 更新订单项状态
        OrderItem::where('order_id', $orderId)->update([
            'item_status' => 2,
            'update_time' => time(),
        ]);

        // 记录日志
        OrderLog::addLog($orderId, $operatorType, $operatorId, 'complete', $beforeStatus, self::STATUS_COMPLETED, '订单完成');

        OrderNotificationService::notifyOnOrderCompleted($orderId);
        return [true, '订单已完成'];
    }

    /**
     * @notes 获取订单状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_PENDING_CONFIRM, 'label' => '待确认'],
            ['value' => self::STATUS_PENDING_PAY, 'label' => '待支付'],
            ['value' => self::STATUS_PAID, 'label' => '已支付'],
            ['value' => self::STATUS_IN_SERVICE, 'label' => '服务中'],
            ['value' => self::STATUS_COMPLETED, 'label' => '已完成'],
            ['value' => self::STATUS_REVIEWED, 'label' => '已评价'],
            ['value' => self::STATUS_CANCELLED, 'label' => '已取消'],
            ['value' => self::STATUS_PAUSED, 'label' => '已暂停'],
            ['value' => self::STATUS_REFUNDED, 'label' => '已退款'],
        ];
    }

    /**
     * @notes 获取支付方式选项
     * @return array
     */
    public static function getPayWayOptions(): array
    {
        return [
            ['value' => self::PAY_WAY_WECHAT, 'label' => '微信支付'],
            ['value' => self::PAY_WAY_ALIPAY, 'label' => '支付宝'],
            ['value' => self::PAY_WAY_BALANCE, 'label' => '余额支付'],
            ['value' => self::PAY_WAY_OFFLINE, 'label' => '线下支付'],
            ['value' => self::PAY_WAY_COMBINATION, 'label' => '组合支付'],
        ];
    }

    /**
     * @notes 关联变更记录
     * @return \think\model\relation\HasMany
     */
    public function orderChanges()
    {
        return $this->hasMany(OrderChange::class, 'order_id', 'id');
    }

    /**
     * @notes 关联暂停记录
     * @return \think\model\relation\HasOne
     */
    public function orderPause()
    {
        return $this->hasOne(OrderPause::class, 'order_id', 'id')
            ->where('pause_status', OrderPause::STATUS_PAUSED);
    }

    /**
     * @notes 关联所有暂停记录
     * @return \think\model\relation\HasMany
     */
    public function orderPauses()
    {
        return $this->hasMany(OrderPause::class, 'order_id', 'id');
    }

    /**
     * @notes 关联变更日志
     * @return \think\model\relation\HasMany
     */
    public function changeLogs()
    {
        return $this->hasMany(OrderChangeLog::class, 'order_id', 'id');
    }

    /**
     * @notes 是否暂停描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsPausedDescAttr($value, $data): string
    {
        return ($data['is_paused'] ?? 0) ? '暂停中' : '正常';
    }

    /**
     * @notes 检查订单是否可变更
     * @return array [bool $canChange, string $message]
     */
    public function canChange(): array
    {
        return OrderChange::checkCanChange($this->id);
    }

    /**
     * @notes 检查订单是否可暂停
     * @return array [bool $canPause, string $message]
     */
    public function canPause(): array
    {
        return OrderPause::checkCanPause($this->id, $this->user_id);
    }

    /**
     * @notes 获取订单的变更统计
     * @return array
     */
    public function getChangeStatistics(): array
    {
        $changes = OrderChange::where('order_id', $this->id)->select();
        
        $stats = [
            'total' => count($changes),
            'date_change' => 0,
            'staff_change' => 0,
            'add_item' => 0,
            'addon_change' => 0,
            'pending' => 0,
            'approved' => 0,
            'executed' => 0,
        ];

        foreach ($changes as $change) {
            switch ($change->change_type) {
                case OrderChange::TYPE_DATE:
                    $stats['date_change']++;
                    break;
                case OrderChange::TYPE_STAFF:
                    $stats['staff_change']++;
                    break;
                case OrderChange::TYPE_ADD_ITEM:
                    $stats['add_item']++;
                    break;
                case OrderChange::TYPE_ADDON:
                    $stats['addon_change']++;
                    break;
            }

            switch ($change->change_status) {
                case OrderChange::STATUS_PENDING:
                    $stats['pending']++;
                    break;
                case OrderChange::STATUS_APPROVED:
                    $stats['approved']++;
                    break;
                case OrderChange::STATUS_EXECUTED:
                    $stats['executed']++;
                    break;
            }
        }

        return $stats;
    }
}
