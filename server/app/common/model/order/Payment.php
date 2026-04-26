<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 支付记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;
use app\common\model\financial\FinancialFlow;
use app\common\model\order\Refund;
use app\common\service\MoneyService;
use app\common\service\OrderConfirmLetterService;
use app\common\service\OrderRefundService;

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

        $callbackError = self::validatePaidCallback($payment, $callbackData);
        if ($callbackError !== '') {
            self::rejectPaidCallback($payment, $transactionId, $callbackData, $callbackError);
            return [false, $callbackError, []];
        }

        // 更新订单状态
        $order = Order::where('id', $payment->order_id)->lock(true)->find();
        if (!$order) {
            return self::handleExceptionalPaidCallback(
                $payment,
                null,
                $transactionId,
                $callbackData,
                '订单不存在，支付回调已登记待人工处理'
            );
        }

        if ((int)$order->order_status !== Order::STATUS_PENDING_PAY) {
            return self::handleExceptionalPaidCallback(
                $payment,
                $order,
                $transactionId,
                $callbackData,
                '订单已关闭或状态已变更，系统已登记异常支付并发起补偿处理'
            );
        }

        if ($order->shouldAutoCancelExpiredUnpaid() || $order->shouldAutoCloseExpiredBalancePayment()) {
            return self::handleExceptionalPaidCallback(
                $payment,
                $order,
                $transactionId,
                $callbackData,
                '支付已超时，系统已登记异常支付并发起补偿处理'
            );
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
        OrderConfirmLetterService::invalidateCurrentLetter($order, false);
        $order->update_time = time();
        $order->save();

        self::recordFinancialFlow($payment, $order, $transactionId);

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
     * @notes 处理订单关闭后的异常支付回调
     * @param Payment $payment
     * @param Order|null $order
     * @param string $transactionId
     * @param array $callbackData
     * @param string $reason
     * @return array
     */
    protected static function handleExceptionalPaidCallback(
        self $payment,
        ?Order $order,
        string $transactionId,
        array $callbackData,
        string $reason
    ): array {
        $paidAt = time();
        $payment->pay_status = self::STATUS_PAID;
        $payment->transaction_id = $transactionId;
        $payment->pay_time = (int)($payment->pay_time ?? 0) > 0 ? (int)$payment->pay_time : $paidAt;
        $payment->callback_time = $paidAt;
        $payment->callback_data = json_encode($callbackData, JSON_UNESCAPED_UNICODE);
        $payment->remark = self::appendRemark((string)($payment->remark ?? ''), $reason);
        $payment->update_time = $paidAt;
        $payment->save();

        $context = [
            'order_id' => (int)($order->id ?? 0),
            'pay_type' => (int)$payment->pay_type,
            'should_notify' => false,
            'should_notify_completed' => false,
            'refund_id' => 0,
            'late_callback_exception' => true,
        ];

        if (!$order) {
            return [true, $reason, $context];
        }

        self::recordFinancialFlow($payment, $order, $transactionId);

        OrderLog::addLog(
            (int)$order->id,
            OrderLog::OPERATOR_SYSTEM,
            0,
            'pay_exception',
            (int)$order->order_status,
            (int)$order->order_status,
            $reason
        );

        $shouldAutoRefund = OrderRefundService::isOrderFinishedStatus((int)$order->order_status)
            || $order->shouldAutoCancelExpiredUnpaid()
            || $order->shouldAutoCloseExpiredBalancePayment();

        if ($shouldAutoRefund && !OrderRefundService::hasPendingRefund((int)$order->id)) {
            $refundResult = Refund::createSystemRefund(
                (int)$order->id,
                0,
                round((float)$payment->pay_amount, 2),
                '订单关闭后收到支付回调，系统已自动创建退款申请',
                Refund::TYPE_SYSTEM
            );

            if ($refundResult[0] ?? false) {
                $context['refund_id'] = (int)($refundResult[2]->id ?? 0);
            } else {
                OrderLog::addLog(
                    (int)$order->id,
                    OrderLog::OPERATOR_SYSTEM,
                    0,
                    'refund_create_fail',
                    (int)$order->order_status,
                    (int)$order->order_status,
                    '异常支付自动退款创建失败：' . (string)($refundResult[1] ?? '未知错误')
                );
            }
        }

        return [true, $reason, $context];
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

    /**
     * @notes 追加回调备注
     * @param string $origin
     * @param string $append
     * @return string
     */
    protected static function appendRemark(string $origin, string $append): string
    {
        $parts = array_filter([
            trim($origin),
            trim($append),
        ]);

        return mb_substr(implode('；', array_unique($parts)), 0, 255);
    }

    /**
     * @notes 校验三方支付回调金额
     */
    protected static function validatePaidCallback(self $payment, array $callbackData): string
    {
        if ((int)$payment->pay_way !== self::WAY_WECHAT) {
            return '';
        }

        $amount = (array)($callbackData['amount'] ?? []);
        if (!array_key_exists('total', $amount)) {
            return '微信支付回调缺少金额信息';
        }

        $currency = strtoupper(trim((string)($amount['currency'] ?? 'CNY')));
        if ($currency !== '' && $currency !== 'CNY') {
            return '微信支付回调币种不支持：' . $currency;
        }

        try {
            $expectedFen = MoneyService::yuanToFen($payment->pay_amount);
        } catch (\Throwable $e) {
            return '本地支付金额格式错误';
        }

        $actualFen = (int)$amount['total'];
        if ($actualFen !== $expectedFen) {
            return '微信支付回调金额不一致，应付' . $expectedFen . '分，实付' . $actualFen . '分';
        }

        return '';
    }

    /**
     * @notes 登记被拒绝的支付回调
     */
    protected static function rejectPaidCallback(
        self $payment,
        string $transactionId,
        array $callbackData,
        string $reason
    ): void {
        $payment->transaction_id = $transactionId;
        $payment->callback_time = time();
        $payment->callback_data = json_encode($callbackData, JSON_UNESCAPED_UNICODE);
        $payment->remark = self::appendRemark((string)($payment->remark ?? ''), $reason);
        $payment->update_time = time();
        $payment->save();

        $order = Order::where('id', (int)$payment->order_id)->find();
        if ($order) {
            OrderLog::addLog(
                (int)$order->id,
                OrderLog::OPERATOR_SYSTEM,
                0,
                'pay_callback_reject',
                (int)$order->order_status,
                (int)$order->order_status,
                $reason
            );
        }
    }

    /**
     * @notes 记录支付资金流水
     */
    protected static function recordFinancialFlow(self $payment, Order $order, string $transactionId = ''): void
    {
        if ((int)$payment->pay_way === self::WAY_OFFLINE) {
            return;
        }

        FinancialFlow::safeCreateUniqueFlow([
            'flow_type' => FinancialFlow::FLOW_TYPE_INCOME,
            'biz_type' => FinancialFlow::BIZ_TYPE_ORDER_PAY,
            'biz_id' => (int)$payment->id,
            'biz_sn' => (string)$payment->payment_sn,
            'order_id' => (int)$order->id,
            'user_id' => (int)$order->user_id,
            'amount' => round((float)$payment->pay_amount, 2),
            'direction' => FinancialFlow::DIRECTION_IN,
            'pay_way' => (int)$payment->pay_way,
            'transaction_id' => trim($transactionId) !== '' ? $transactionId : (string)($payment->transaction_id ?? ''),
            'remark' => self::buildFinancialFlowRemark((int)$payment->pay_type),
            'operator_type' => 0,
            'operator_id' => 0,
            'create_time' => max((int)($payment->pay_time ?? 0), time()),
        ]);
    }

    /**
     * @notes 构建支付流水备注
     */
    protected static function buildFinancialFlowRemark(int $payType): string
    {
        return match ($payType) {
            self::TYPE_DEPOSIT => '订单定金支付入账',
            self::TYPE_BALANCE => '订单尾款支付入账',
            default => '订单支付入账',
        };
    }
}
