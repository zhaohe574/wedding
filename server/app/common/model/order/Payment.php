<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 支付记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\enum\user\UserTerminalEnum;
use app\common\model\BaseModel;
use app\common\model\financial\FinancialFlow;
use app\common\model\order\Refund;
use app\common\model\staff\Staff;
use app\common\model\user\UserAuth;
use app\common\service\MoneyService;
use app\common\service\OrderConfirmLetterService;
use app\common\service\OrderRefundService;
use app\common\service\StaffSettlementService;
use think\facade\Log;

/**
 * 支付记录模型
 * Class Payment
 * @package app\common\model\order
 */
class Payment extends BaseModel
{
    protected $name = 'payment';

    public const EXCEPTION_TYPE_SCHEDULE_LOCK_FAILED_AFTER_PAYMENT = 'schedule_lock_failed_after_payment';

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

        if (!in_array((int)$payment->pay_status, [self::STATUS_PENDING, self::STATUS_PAID, self::STATUS_FAILED], true)) {
            return [false, '支付记录状态不允许处理回调', []];
        }

        if ((int)$payment->pay_status === self::STATUS_PAID) {
            $replayError = self::validatePaidCallback($payment, $callbackData, $transactionId, true);
            if ($replayError !== '') {
                self::logRejectedReplay($payment, $transactionId, $callbackData, $replayError);
                return [false, $replayError, []];
            }

            return [true, '已处理', [
                'order_id' => (int)$payment->order_id,
                'pay_type' => (int)$payment->pay_type,
                'should_notify' => false,
                'should_notify_completed' => false,
            ]];
        }

        $callbackError = self::validatePaidCallback($payment, $callbackData, $transactionId, false);
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

        if (trim((string)$payment->order_sn) !== '' && trim((string)$payment->order_sn) !== trim((string)$order->order_sn)) {
            return self::handleExceptionalPaidCallback(
                $payment,
                $order,
                $transactionId,
                $callbackData,
                '支付流水订单号与订单不一致，系统已登记异常支付并发起补偿处理'
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
        $payment->transaction_id = trim($transactionId) !== '' ? $transactionId : null;
        $payment->pay_time = time();
        $payment->callback_time = time();
        $payment->callback_data = json_encode($callbackData, JSON_UNESCAPED_UNICODE);
        $payment->update_time = time();
        try {
            $payment->save();
        } catch (\Throwable $e) {
            if (self::isDuplicateKeyException($e)) {
                return [false, '第三方交易号已被其他支付记录处理', []];
            }
            throw $e;
        }

        // 累计已支付金额
        $order->paid_amount = round((float)($order->paid_amount ?? 0) + (float)$payment->pay_amount, 2);
        if ($order->pay_type != Order::PAY_WAY_COMBINATION) {
            $order->pay_type = $payment->pay_way;
        }

        if (Order::isFirstPaidStage((int)$payment->pay_type)) {
            try {
                Order::lockSchedulesAfterFirstPayment($order);
            } catch (\Throwable $e) {
                if (in_array((int)$payment->pay_way, [self::WAY_BALANCE, self::WAY_OFFLINE], true)) {
                    return [
                        false,
                        '档期已被占用，请重新选择服务',
                        []
                    ];
                }

                $reason = '首笔支付成功但档期锁定失败，系统已登记异常并创建退款申请：' . $e->getMessage();
                $payment->remark = self::appendRemark(
                    (string)($payment->remark ?? ''),
                    self::EXCEPTION_TYPE_SCHEDULE_LOCK_FAILED_AFTER_PAYMENT . '：' . $reason
                );
                $order->update_time = time();
                $order->save();
                OrderConfirmLetterService::invalidateCurrentLetter($order, false);
                return self::handleExceptionalPaidCallback(
                    $payment,
                    $order,
                    $transactionId,
                    $callbackData,
                    $reason,
                    true
                );
            }
        }

        Order::applyPaidStateAfterPayment($order, (int)$payment->pay_type, (int)$payment->pay_time);

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

        if ((int)$order->order_status === Order::STATUS_COMPLETED) {
            Staff::refreshServiceStatsByOrder((int)$order->id);
            self::tryGenerateStaffSettlement((int)$order->id);
        }

        return [true, '支付成功', [
            'order_id' => (int)$order->id,
            'pay_type' => (int)$payment->pay_type,
            'should_notify' => true,
            'should_notify_completed' => (int)$order->order_status === Order::STATUS_COMPLETED,
        ]];
    }

    /**
     * @notes 支付完成后安全生成服务人员结算
     */
    protected static function tryGenerateStaffSettlement(int $orderId): void
    {
        try {
            (new StaffSettlementService())->generateFromCompletedOrder($orderId);
        } catch (\Throwable $e) {
            Log::write('尾款支付完成生成服务人员结算失败：' . $e->getMessage());
        }
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
        string $reason,
        bool $forceAutoRefund = false
    ): array {
        $paidAt = time();
        $payment->pay_status = self::STATUS_PAID;
        $payment->transaction_id = trim($transactionId) !== '' ? $transactionId : null;
        $payment->pay_time = (int)($payment->pay_time ?? 0) > 0 ? (int)$payment->pay_time : $paidAt;
        $payment->callback_time = $paidAt;
        $payment->callback_data = json_encode($callbackData, JSON_UNESCAPED_UNICODE);
        $payment->remark = self::appendRemark((string)($payment->remark ?? ''), $reason);
        $payment->update_time = $paidAt;
        try {
            $payment->save();
        } catch (\Throwable $e) {
            if (self::isDuplicateKeyException($e)) {
                return [false, '第三方交易号已被其他支付记录处理', []];
            }
            throw $e;
        }

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

        $shouldAutoRefund = $forceAutoRefund
            || OrderRefundService::isOrderFinishedStatus((int)$order->order_status)
            || $order->shouldAutoCancelExpiredUnpaid()
            || $order->shouldAutoCloseExpiredBalancePayment();

        if ($shouldAutoRefund && !OrderRefundService::hasPendingRefund((int)$order->id)) {
            if ($forceAutoRefund) {
                $beforeStatus = (int)$order->order_status;
                $order->order_status = Order::STATUS_CANCELLED;
                $order->cancel_reason = $reason;
                $order->cancel_time = time();
                $order->confirm_deadline_time = 0;
                $order->pay_deadline_time = 0;
                $order->pay_status = Order::PAY_STATUS_PAID;
                $order->paid_amount = max(
                    round((float)($order->paid_amount ?? 0), 2),
                    round((float)$payment->pay_amount, 2)
                );
                $order->update_time = time();
                $order->save();
                OrderLog::addLog(
                    (int)$order->id,
                    OrderLog::OPERATOR_SYSTEM,
                    0,
                    'pay_schedule_lock_failed_cancel',
                    $beforeStatus,
                    Order::STATUS_CANCELLED,
                    $reason
                );
            }

            $refundResult = Refund::createSystemRefund(
                (int)$order->id,
                0,
                round((float)$payment->pay_amount, 2),
                $forceAutoRefund
                    ? $reason
                    : '订单关闭后收到支付回调，系统已自动创建退款申请',
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

    public static function buildPaymentExceptionPayload(?self $payment): array
    {
        $remark = trim((string)($payment->remark ?? ''));
        if (
            $remark === '' ||
            !str_contains($remark, self::EXCEPTION_TYPE_SCHEDULE_LOCK_FAILED_AFTER_PAYMENT)
        ) {
            return [
                'payment_exception' => 0,
                'payment_exception_type' => '',
                'payment_exception_desc' => '',
                'refund_id' => 0,
            ];
        }

        $refundId = 0;
        if ($payment && (int)($payment->order_id ?? 0) > 0) {
            $refundId = (int)Refund::where('order_id', (int)$payment->order_id)
                ->order('id', 'desc')
                ->value('id');
        }

        return [
            'payment_exception' => 1,
            'payment_exception_type' => self::EXCEPTION_TYPE_SCHEDULE_LOCK_FAILED_AFTER_PAYMENT,
            'payment_exception_desc' => '支付已收到，但档期锁定失败，退款处理中。',
            'refund_id' => $refundId,
        ];
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
        $payment->transaction_id = trim($transactionId) !== '' ? $transactionId : null;
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
     * @notes 校验三方支付回调上下文（来源、状态、金额、交易号、支付者身份）
     */
    protected static function validatePaidCallback(
        self $payment,
        array $callbackData,
        string $transactionId = '',
        bool $isReplay = false
    ): string {
        $payWay = (int)$payment->pay_way;
        $transactionId = trim($transactionId);

        if (in_array($payWay, [self::WAY_WECHAT, self::WAY_ALIPAY], true) && $transactionId === '') {
            return '支付回调缺少第三方交易号';
        }

        $storedTransactionId = trim((string)($payment->transaction_id ?? ''));
        if ($isReplay) {
            if ($storedTransactionId !== '' && $transactionId !== '' && $storedTransactionId !== $transactionId) {
                return '重复支付回调交易号不一致';
            }
        } else {
            $transactionError = self::validateUniqueTransactionId($payment, $transactionId);
            if ($transactionError !== '') {
                return $transactionError;
            }
        }

        return match ($payWay) {
            self::WAY_WECHAT => self::validateWechatPaidCallback($payment, $callbackData, $isReplay),
            self::WAY_ALIPAY => self::validateAliPaidCallback($payment, $callbackData, $isReplay),
            default => '',
        };
    }

    /**
     * @notes 校验微信支付通知业务字段
     */
    protected static function validateWechatPaidCallback(self $payment, array $callbackData, bool $isReplay = false): string
    {
        $source = trim((string)($callbackData['source'] ?? ''));
        if (empty($callbackData['source_verified']) || $source !== 'wechat_pay_v3') {
            return '微信支付回调来源未验证';
        }

        $tradeState = strtoupper(trim((string)($callbackData['trade_state'] ?? '')));
        if ($tradeState !== 'SUCCESS') {
            return '微信支付回调状态不是SUCCESS';
        }

        $attach = trim((string)($callbackData['attach'] ?? ''));
        if ($attach !== 'order') {
            return '微信支付回调来源不是订单支付';
        }

        $outTradeNo = trim((string)($callbackData['out_trade_no'] ?? ''));
        if ($outTradeNo === '') {
            return '微信支付回调缺少商户支付单号';
        }
        if ($outTradeNo !== (string)$payment->payment_sn) {
            return '微信支付回调商户支付单号不匹配';
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

        return self::validateWechatPayer($payment, $callbackData, $isReplay);
    }

    /**
     * @notes 校验支付宝通知业务字段（预留订单支付宝支付兼容）
     */
    protected static function validateAliPaidCallback(self $payment, array $callbackData, bool $isReplay = false): string
    {
        $tradeStatus = strtoupper(trim((string)($callbackData['trade_status'] ?? '')));
        if ($tradeStatus !== '' && !in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'], true)) {
            return '支付宝回调状态不允许处理';
        }

        $passback = trim((string)($callbackData['passback_params'] ?? $callbackData['attach'] ?? ''));
        if ($passback !== '' && $passback !== 'order') {
            return '支付宝回调来源不是订单支付';
        }

        $outTradeNo = trim((string)($callbackData['out_trade_no'] ?? ''));
        if ($outTradeNo !== '' && $outTradeNo !== (string)$payment->payment_sn) {
            return '支付宝回调商户支付单号不匹配';
        }

        if (isset($callbackData['total_amount'])) {
            $actualAmount = round((float)$callbackData['total_amount'], 2);
            $expectedAmount = round((float)$payment->pay_amount, 2);
            if (abs($actualAmount - $expectedAmount) >= 0.01) {
                return '支付宝回调金额不一致，应付' . $expectedAmount . '元，实付' . $actualAmount . '元';
            }
        }

        return '';
    }

    /**
     * @notes 校验第三方交易号唯一性
     */
    protected static function validateUniqueTransactionId(self $payment, string $transactionId): string
    {
        if ($transactionId === '') {
            return '';
        }

        $existing = self::where('transaction_id', $transactionId)
            ->where('id', '<>', (int)$payment->id)
            ->whereNotNull('transaction_id')
            ->lock(true)
            ->find();
        if (!$existing) {
            return '';
        }

        return '第三方交易号已被其他支付记录处理';
    }

    /**
     * @notes 判断是否数据库唯一键冲突
     */
    protected static function isDuplicateKeyException(\Throwable $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, '1062') || stripos($message, 'Duplicate') !== false;
    }

    /**
     * @notes 校验微信支付者身份
     */
    protected static function validateWechatPayer(self $payment, array $callbackData, bool $isReplay = false): string
    {
        $payer = (array)($callbackData['payer'] ?? []);
        $openid = trim((string)($payer['openid'] ?? ''));
        $terminal = (int)($callbackData['terminal'] ?? 0);
        $isJsapiTerminal = in_array($terminal, [UserTerminalEnum::WECHAT_MMP, UserTerminalEnum::WECHAT_OA], true);

        if ($openid === '') {
            return $isJsapiTerminal ? '微信支付回调缺少支付者openid' : '';
        }

        $allowedOpenids = self::getExpectedWechatOpenids((int)$payment->user_id, $terminal);
        if (empty($allowedOpenids)) {
            return $isJsapiTerminal ? '无法校验微信支付者身份' : '';
        }

        if (!in_array($openid, $allowedOpenids, true)) {
            return '微信支付者身份与订单用户不一致';
        }

        return '';
    }

    /**
     * @notes 获取订单用户允许的微信 openid 集合
     */
    protected static function getExpectedWechatOpenids(int $userId, int $terminal = 0): array
    {
        if ($userId <= 0) {
            return [];
        }

        $query = UserAuth::where('user_id', $userId);
        if (in_array($terminal, [UserTerminalEnum::WECHAT_MMP, UserTerminalEnum::WECHAT_OA], true)) {
            $query->where('terminal', $terminal);
        } else {
            $query->whereIn('terminal', [UserTerminalEnum::WECHAT_MMP, UserTerminalEnum::WECHAT_OA]);
        }

        $openids = $query->column('openid');
        $openids = array_map(static fn ($openid) => trim((string)$openid), $openids);
        $openids = array_filter($openids, static fn (string $openid) => $openid !== '');

        return array_values(array_unique($openids));
    }

    /**
     * @notes 记录被拒绝的重复通知，不覆盖已成功流水
     */
    protected static function logRejectedReplay(
        self $payment,
        string $transactionId,
        array $callbackData,
        string $reason
    ): void {
        $order = Order::where('id', (int)$payment->order_id)->find();
        if ($order) {
            OrderLog::addLog(
                (int)$order->id,
                OrderLog::OPERATOR_SYSTEM,
                0,
                'pay_callback_replay_reject',
                (int)$order->order_status,
                (int)$order->order_status,
                $reason
            );
        }

        Log::write('重复支付回调校验失败：' . json_encode([
            'payment_id' => (int)$payment->id,
            'payment_sn' => (string)$payment->payment_sn,
            'stored_transaction_id' => (string)($payment->transaction_id ?? ''),
            'callback_transaction_id' => $transactionId,
            'reason' => $reason,
            'callback_data' => $callbackData,
        ], JSON_UNESCAPED_UNICODE));
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
        // 被拒绝的伪造/异常回调不占用主交易号唯一索引，交易号保留在 callback_data 便于追溯。
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
