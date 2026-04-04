<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 支付记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;

/**
 * 支付记录模型
 * Class Payment
 * @package app\common\model\order
 */
class Payment extends BaseModel
{
    protected $name = 'payment';

    // 支付类型
    const TYPE_DEPOSIT = 1;     // 定金
    const TYPE_BALANCE = 2;     // 尾款
    const TYPE_FULL = 3;        // 全款

    // 支付方式
    const WAY_WECHAT = 1;   // 微信
    const WAY_ALIPAY = 2;   // 支付宝
    const WAY_BALANCE = 3;  // 余额
    const WAY_OFFLINE = 4;  // 线下

    // 支付状态
    const STATUS_PENDING = 0;   // 待支付
    const STATUS_PAID = 1;      // 已支付
    const STATUS_REFUNDED = 2;  // 已退款
    const STATUS_FAILED = 3;    // 支付失败

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @notes 关联退款子项
     * @return \think\model\relation\HasMany
     */
    public function refundItems()
    {
        return $this->hasMany(RefundItem::class, 'payment_id', 'id');
    }

    /**
     * @notes 支付类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayTypeDescAttr($value, $data): string
    {
        $map = [
            self::TYPE_DEPOSIT => '定金',
            self::TYPE_BALANCE => '尾款',
            self::TYPE_FULL => '全款',
        ];
        return $map[$data['pay_type']] ?? '未知';
    }

    /**
     * @notes 支付方式描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayWayDescAttr($value, $data): string
    {
        return self::getPayWayText((int)($data['pay_way'] ?? -1));
    }

    /**
     * @notes 支付状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayStatusDescAttr($value, $data): string
    {
        return self::getPayStatusText((int)($data['pay_status'] ?? -1));
    }

    /**
     * @notes 获取支付方式文案
     * @param int $payWay
     * @return string
     */
    public static function getPayWayText(int $payWay): string
    {
        $map = [
            self::WAY_WECHAT => '微信支付',
            self::WAY_ALIPAY => '支付宝',
            self::WAY_BALANCE => '余额支付',
            self::WAY_OFFLINE => '线下支付',
        ];

        return $map[$payWay] ?? '未知';
    }

    /**
     * @notes 获取支付状态文案
     * @param int $status
     * @return string
     */
    public static function getPayStatusText(int $status): string
    {
        $map = [
            self::STATUS_PENDING => '待支付',
            self::STATUS_PAID => '已支付',
            self::STATUS_REFUNDED => '已退款',
            self::STATUS_FAILED => '支付失败',
        ];

        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取支付流水剩余可退金额
     * @return float
     */
    public function getRefundableAmount(): float
    {
        return round(max((float)$this->pay_amount - (float)($this->refund_amount ?? 0), 0), 2);
    }

    /**
     * @notes 生成支付流水号
     * @return string
     */
    public static function generatePaymentSn(): string
    {
        return 'PAY' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 创建支付记录
     * @param int $orderId
     * @param string $orderSn
     * @param int $userId
     * @param int $payType
     * @param int $payWay
     * @param float $payAmount
     * @param int $expireMinutes 过期时间(分钟)
     * @param int|null $expireTime 指定过期时间戳
     * @return Payment
     */
    public static function createPayment(
        int $orderId,
        string $orderSn,
        int $userId,
        int $payType,
        int $payWay,
        float $payAmount,
        int $expireMinutes = 30,
        ?int $expireTime = null
    ): Payment
    {
        $resolvedExpireTime = $expireTime !== null
            ? max(0, $expireTime)
            : time() + ($expireMinutes * 60);

        return self::create([
            'payment_sn' => self::generatePaymentSn(),
            'order_id' => $orderId,
            'order_sn' => $orderSn,
            'user_id' => $userId,
            'pay_type' => $payType,
            'pay_way' => $payWay,
            'pay_amount' => $payAmount,
            'pay_status' => self::STATUS_PENDING,
            'expire_time' => $resolvedExpireTime,
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    /**
     * @notes 将订单下待支付流水标记为失败
     * @param int $orderId
     * @return void
     */
    public static function markOrderPendingAsFailed(int $orderId): void
    {
        self::where('order_id', $orderId)
            ->where('pay_status', self::STATUS_PENDING)
            ->update([
                'pay_status' => self::STATUS_FAILED,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 支付成功回调
     * @param string $paymentSn
     * @param string $transactionId
     * @param array $callbackData
     * @return array [bool $success, string $message, array $context]
     */
    public static function paySuccess(string $paymentSn, string $transactionId, array $callbackData = []): array
    {
        $payment = self::where('payment_sn', $paymentSn)->lock(true)->find();
        if (!$payment) {
            return [false, '支付记录不存在', []];
        }

        if ($payment->pay_status == self::STATUS_PAID) {
            return [true, '已处理', [
                'order_id' => (int)$payment->order_id,
                'pay_type' => (int)$payment->pay_type,
                'should_notify' => false,
                'should_notify_completed' => false,
            ]];
        }

        // 更新订单状态
        $order = Order::where('id', $payment->order_id)->lock(true)->find();
        if (!$order) {
            self::recordCallbackInfo($payment, $transactionId, $callbackData);
            return [false, '订单不存在', []];
        }

        if ((int)$order->order_status !== Order::STATUS_PENDING_PAY) {
            self::recordCallbackInfo($payment, $transactionId, $callbackData);
            return [false, '订单状态不允许确认支付', []];
        }

        if ($order->shouldAutoCancelExpiredUnpaid()) {
            self::recordCallbackInfo($payment, $transactionId, $callbackData);
            return [false, Order::AUTO_CANCEL_MESSAGE, []];
        }

        $payment->pay_status = self::STATUS_PAID;
        $payment->transaction_id = $transactionId;
        $payment->pay_time = time();
        $payment->callback_time = time();
        $payment->callback_data = json_encode($callbackData, JSON_UNESCAPED_UNICODE);
        $payment->update_time = time();
        $payment->save();

        // 累计已支付金额
        $order->paid_amount = round((float)($order->paid_amount ?? 0) + (float)$payment->pay_amount, 2);
        Order::applyPaidStateAfterPayment($order, (int)$payment->pay_type, (int)$payment->pay_time);

        if ($order->pay_type != Order::PAY_WAY_COMBINATION) {
            $order->pay_type = $payment->pay_way;
        }
        $order->update_time = time();
        $order->save();

        // 记录日志
        OrderLog::addLog(
            $order->id,
            OrderLog::OPERATOR_SYSTEM,
            0,
            $payment->pay_type == self::TYPE_DEPOSIT ? 'pay_deposit' : ($payment->pay_type == self::TYPE_BALANCE ? 'pay_balance' : 'pay'),
            Order::STATUS_PENDING_PAY,
            $order->order_status,
            '支付成功，金额：' . $payment->pay_amount
        );

        return [true, '支付成功', [
            'order_id' => (int)$order->id,
            'pay_type' => (int)$payment->pay_type,
            'should_notify' => true,
            'should_notify_completed' => (int)$order->order_status === Order::STATUS_COMPLETED,
        ]];
    }

    /**
     * @notes 记录回调信息
     * @param Payment $payment
     * @param string $transactionId
     * @param array $callbackData
     * @return void
     */
    protected static function recordCallbackInfo(self $payment, string $transactionId, array $callbackData = []): void
    {
        $payment->transaction_id = $transactionId;
        $payment->callback_time = time();
        $payment->callback_data = json_encode($callbackData, JSON_UNESCAPED_UNICODE);
        $payment->update_time = time();
        $payment->save();
    }
}
