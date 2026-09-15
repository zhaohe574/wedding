<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单退款服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\user\UserTerminalEnum;
use app\common\model\financial\FinancialFlow;
use app\common\model\financial\StaffSettlement;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\Payment;
use app\common\model\order\Refund;
use app\common\model\order\RefundItem;
use app\common\model\package\PackageBooking;
use app\common\model\schedule\Schedule;
use app\common\service\pay\WeChatPayService;
use think\Collection;
use think\facade\Db;

/**
 * 订单退款服务
 * Class OrderRefundService
 * @package app\common\service
 */
class OrderRefundService
{
    /**
     * @notes 获取订单剩余可退金额
     * @param int $orderId
     * @return float
     */
    public static function getRefundableAmount(int $orderId): float
    {
        return round(array_sum(array_column(self::getRefundablePayments($orderId), 'left_amount')), 2);
    }

    /**
     * @notes 是否存在进行中的退款
     * @param int $orderId
     * @return bool
     */
    public static function hasPendingRefund(int $orderId): bool
    {
        return Refund::where('order_id', $orderId)
            ->whereIn('refund_status', Refund::getPendingStatuses())
            ->find() !== null;
    }

    /**
     * @notes 订单状态是否已结束
     * @param int $status
     * @return bool
     */
    public static function isOrderFinishedStatus(int $status): bool
    {
        return in_array($status, [
            Order::STATUS_COMPLETED,
            Order::STATUS_REVIEWED,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
            Order::STATUS_USER_DELETED,
        ], true);
    }

    /**
     * @notes 用户端是否允许申请退款
     * @param Order $order
     * @return bool
     */
    public static function canUserApplyRefund(Order $order): bool
    {
        if (!in_array((int)$order->order_status, [Order::STATUS_PAID, Order::STATUS_IN_SERVICE], true)) {
            return false;
        }

        if (self::hasPendingRefund((int)$order->id)) {
            return false;
        }

        return self::getRefundableAmount((int)$order->id) > 0;
    }

    /**
     * @notes 管理员是否允许发起退款
     * @param Order $order
     * @return bool
     */
    public static function canAdminApplyRefund(Order $order): bool
    {
        if (self::hasPendingRefund((int)$order->id)) {
            return false;
        }

        if (
            StaffSettlement::where('order_id', (int)$order->id)
                ->whereIn('status', [
                    StaffSettlement::STATUS_SETTLED,
                    StaffSettlement::STATUS_TRANSFER_PROCESSING,
                ])
                ->find()
        ) {
            return false;
        }

        return self::getRefundableAmount((int)$order->id) > 0;
    }

    /**
     * @notes 是否允许后台人工确认线下退款完成
     * @param Refund $refund
     * @return int
     */
    public static function canConfirmOfflineRefund(Refund $refund): int
    {
        if ((int)($refund->id ?? 0) <= 0) {
            return 0;
        }

        return RefundItem::where('refund_id', (int)$refund->id)
            ->where('pay_way', Payment::WAY_OFFLINE)
            ->whereIn('refund_status', [RefundItem::STATUS_PENDING, RefundItem::STATUS_PROCESSING])
            ->count() > 0 ? 1 : 0;
    }

    /**
     * @notes 将订单切换到退款中
     * @param Order $order
     * @return void
     */
    public static function moveOrderToRefunding(Order $order): void
    {
        $order->order_status = Order::STATUS_REFUNDING;
        $order->update_time = time();
        $order->save();
    }

    /**
     * @notes 审核通过后执行退款
     * @param Refund $refund
     * @return array [bool, string]
     */
    public static function executeApprovedRefund(Refund $refund): array
    {
        Order::where('id', (int)$refund->order_id)->lock(true)->find();
        $refund = Refund::where('id', (int)$refund->id)->lock(true)->find();
        if (!$refund) {
            return [false, '退款记录不存在'];
        }

        if ((int)$refund->refund_status === Refund::STATUS_COMPLETED) {
            return [true, '退款已完成'];
        }
        if (!in_array((int)$refund->refund_status, [Refund::STATUS_APPROVED, Refund::STATUS_PROCESSING], true)) {
            return [false, '当前退款状态不允许执行'];
        }
        if ((int)$refund->is_compensation !== 1) {
            [$allowed, $message] = StaffSettlementService::guardRefundForOrder((int)$refund->order_id);
            if (!$allowed) {
                return [false, $message];
            }
        }
        $items = RefundItem::where('refund_id', (int)$refund->id)->select();
        if ($items->isEmpty()) {
            [$success, $message] = self::createRefundItems($refund);
            if (!$success) {
                self::failRefund($refund, 0.0, $message);
                return [false, $message];
            }
            $items = RefundItem::where('refund_id', (int)$refund->id)->select();
        }

        $errors = [];
        foreach ($items as $item) {
            if ((int)$item->refund_status !== RefundItem::STATUS_PENDING) {
                continue;
            }

            [$itemSuccess, $message] = self::executeRefundItem($refund, $item);
            if (!$itemSuccess && $message !== '') {
                $errors[] = $message;
            }
        }

        [$finalSuccess, $finalMessage] = self::refreshParentRefund($refund, implode('；', $errors));
        return [$finalSuccess, $finalMessage];
    }

    /**
     * @notes 线下退款确认完成
     * @param Refund $refund
     * @param string $transactionId
     * @return array [bool, string]
     */
    public static function retryRefund(int $refundId): array
    {
        return Db::transaction(static function () use ($refundId): array {
            $record = Refund::find($refundId);
            if (!$record) {
                return [false, '退款记录不存在'];
            }
            Order::where('id', (int)$record->order_id)->lock(true)->find();
            $refund = Refund::where('id', $refundId)->lock(true)->find();
            if (!in_array((int)$refund->refund_status, [Refund::STATUS_FAILED, Refund::STATUS_PROCESSING], true)) {
                return [false, '当前状态无需重试'];
            }
            $items = RefundItem::where('refund_id', $refundId)->lock(true)->select();
            foreach ($items as $item) {
                if ((int)$item->refund_status === RefundItem::STATUS_COMPLETED) {
                    continue;
                }
                if ((int)$item->pay_way === Payment::WAY_WECHAT) {
                    try {
                        $result = (new WeChatPayService(UserTerminalEnum::WECHAT_MMP))->queryRefund((string)$item->out_refund_no);
                        $payment = Payment::find((int)$item->payment_id);
                        if (!$payment || WeChatPayService::validateRefundResult($result, (string)$payment->payment_sn,
                            (string)$payment->transaction_id, $payment->pay_amount, $item->refund_amount) !== '') {
                            return [false, '退款查询校验失败'];
                        }
                        $status = strtoupper((string)($result['status'] ?? ''));
                        if ($status === 'SUCCESS') {
                            self::syncRefundItemCompleted($item, (string)$result['refund_id'], '主动查询退款成功',
                                (string)$refund->refund_reason, $refund);
                            continue;
                        }
                        if ($status === 'ABNORMAL') {
                            return [false, '退款异常，请先在微信商户平台处理'];
                        }
                        if ($status === 'CLOSED') {
                            // 微信明确关闭后才允许新单号，网络未知结果始终复用原单号。
                            $item->out_refund_no = RefundItem::buildOutRefundNo((string)$refund->refund_sn, (int)$item->id)
                                . '-' . bin2hex(random_bytes(3));
                        } else {
                            $item->refund_status = RefundItem::STATUS_PROCESSING;
                            $item->save();
                            continue;
                        }
                    } catch (\Throwable $e) {
                        $item->refund_msg = '重试查询未确认：' . mb_substr($e->getMessage(), 0, 300);
                    }
                }
                $item->refund_status = RefundItem::STATUS_PENDING;
                $item->save();
            }
            $refund->refund_status = Refund::STATUS_APPROVED;
            $refund->save();
            return self::executeApprovedRefund($refund);
        });
    }

    public static function confirmOfflineRefund(Refund $refund, string $transactionId = '', string $voucher = ''): array
    {
        $refund = Refund::where('id', (int)$refund->id)->lock(true)->find();
        if (!$refund) {
            return [false, '退款记录不存在'];
        }

        $items = RefundItem::where('refund_id', (int)$refund->id)->select();
        if ($items->isEmpty()) {
            [$success, $message] = self::createRefundItems($refund);
            if (!$success) {
                self::failRefund($refund, 0.0, $message);
                return [false, $message];
            }
            $items = RefundItem::where('refund_id', (int)$refund->id)->select();
        }

        if (trim($transactionId) === '' || trim($voucher) === '') {
            return [false, '请填写实际退款流水号并上传付款凭证'];
        }
        foreach ($items as $item) {
            if ((int)$item->pay_way !== Payment::WAY_OFFLINE) {
                continue;
            }
            if ((int)$item->refund_status === RefundItem::STATUS_COMPLETED) {
                continue;
            }

            if ((int)$item->refund_status === RefundItem::STATUS_FAILED) {
                return [false, '退款子项已失败，请重新发起退款'];
            }

            $item->refund_voucher = FileService::setFileUrl($voucher);
            $item->save();
            self::syncRefundItemCompleted(
                $item,
                $transactionId !== '' ? $transactionId : (string)$refund->refund_sn,
                '线下退款已确认',
                (string)$refund->refund_reason,
                $refund
            );
        }

        return self::refreshParentRefund($refund);
    }

    /**
     * @notes 微信退款回调处理
     * @param array $message
     * @return bool
     */
    public static function handleWechatRefundCallback(array $message): bool
    {
        $refundStatus = strtoupper((string)($message['refund_status'] ?? ''));
        $outRefundNo = trim((string)($message['out_refund_no'] ?? ''));
        if ($outRefundNo === '') {
            return false;
        }

        $item = RefundItem::where('out_refund_no', $outRefundNo)->find();
        $payment = $item ? Payment::find((int)$item->payment_id) : null;
        if (!$item || !$payment || (int)$item->pay_way !== Payment::WAY_WECHAT
            || WeChatPayService::validateRefundResult($message, (string)$payment->payment_sn,
                (string)$payment->transaction_id, $payment->pay_amount, $item->refund_amount) !== '') {
            return false;
        }
        $messageJson = json_encode($message, JSON_UNESCAPED_UNICODE) ?: '';
        if ($refundStatus === 'SUCCESS') {
            $result = self::completeRefundItemByOutRefundNo(
                $outRefundNo,
                (string)($message['refund_id'] ?? $outRefundNo),
                $messageJson
            );

            return (bool)($result['success'] ?? false);
        }

        if (in_array($refundStatus, ['ABNORMAL', 'CLOSED'], true)) {
            $refundItem = RefundItem::where('out_refund_no', $outRefundNo)->find();
            if (!$refundItem) {
                return false;
            }

            $result = self::failRefundItem(
                (int)$refundItem->id,
                '微信退款失败：' . $refundStatus . ($messageJson ? '；' . $messageJson : '')
            );

            return (bool)($result['success'] ?? false);
        }

        return false;
    }

    /**
     * @notes 通过子项退款单号完成退款
     * @param string $outRefundNo
     * @param string $thirdRefundNo
     * @param string $message
     * @return array
     */
    public static function completeRefundItemByOutRefundNo(string $outRefundNo, string $thirdRefundNo = '', string $message = ''): array
    {
        $notifyCompleted = false;
        $notifyFailed = false;
        $refundId = 0;

        Db::startTrans();
        try {
            $refundItem = RefundItem::where('out_refund_no', $outRefundNo)->lock(true)->find();
            if (!$refundItem) {
                Db::rollback();
                return ['success' => false, 'message' => '退款子项不存在'];
            }

            if ((int)$refundItem->refund_status === RefundItem::STATUS_COMPLETED) {
                Db::commit();
                return ['success' => true, 'message' => '已处理'];
            }

            $refund = Refund::where('id', (int)$refundItem->refund_id)->lock(true)->find();
            if (!$refund) {
                Db::rollback();
                return ['success' => false, 'message' => '退款单不存在'];
            }

            self::syncRefundItemCompleted($refundItem, $thirdRefundNo, $message, (string)$refund->refund_reason, $refund);
            [$success, $resultMessage, $notifyCompleted, $notifyFailed] = self::refreshParentRefund($refund);
            $refundId = (int)$refund->id;

            self::dispatchRefundNotifications($refundId, $notifyCompleted, $notifyFailed);
            Db::commit();

            return ['success' => $success, 'message' => $resultMessage];
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @notes 将退款子项标记为失败
     * @param int $refundItemId
     * @param string $message
     * @return array
     */
    public static function failRefundItem(int $refundItemId, string $message): array
    {
        $notifyCompleted = false;
        $notifyFailed = false;
        $refundId = 0;

        Db::startTrans();
        try {
            $refundItem = RefundItem::where('id', $refundItemId)->lock(true)->find();
            if (!$refundItem) {
                Db::rollback();
                return ['success' => false, 'message' => '退款子项不存在'];
            }

            if ((int)$refundItem->refund_status === RefundItem::STATUS_COMPLETED) {
                Db::commit();
                return ['success' => true, 'message' => '已处理'];
            }

            $refund = Refund::where('id', (int)$refundItem->refund_id)->lock(true)->find();
            if (!$refund) {
                Db::rollback();
                return ['success' => false, 'message' => '退款单不存在'];
            }

            self::syncRefundItemFailed($refundItem, $message);
            [$success, $resultMessage, $notifyCompleted, $notifyFailed] = self::refreshParentRefund($refund, $message);
            $refundId = (int)$refund->id;

            self::dispatchRefundNotifications($refundId, $notifyCompleted, $notifyFailed);
            Db::commit();

            return ['success' => $success, 'message' => $resultMessage];
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @notes 创建退款子项
     * @param Refund $refund
     * @return array [bool, string]
     */
    protected static function createRefundItems(Refund $refund, ?int $payWay = null): array
    {
        $plans = self::getRefundablePayments((int)$refund->order_id,
            (int)$refund->is_compensation === 1 ? (int)$refund->payment_id : 0);
        if ($payWay !== null) {
            $plans = array_values(array_filter($plans, static function (array $plan) use ($payWay): bool {
                /** @var Payment $payment */
                $payment = $plan['payment'];
                return (int)$payment->pay_way === $payWay;
            }));
        }

        if (empty($plans)) {
            return [false, $payWay === Payment::WAY_OFFLINE ? '订单缺少可退线下支付流水' : '订单缺少可退支付流水'];
        }

        $totalRefundable = round(array_sum(array_column($plans, 'left_amount')), 2);
        $needRefundAmount = round((float)$refund->refund_amount, 2);
        if ($needRefundAmount <= 0) {
            return [false, '退款金额必须大于0'];
        }

        if ($needRefundAmount > $totalRefundable) {
            return [false, '退款金额超过当前可退金额'];
        }

        $remaining = $needRefundAmount;
        $index = 1;
        $firstPaymentId = 0;

        foreach ($plans as $plan) {
            if ($remaining <= 0) {
                break;
            }

            /** @var Payment $payment */
            $payment = $plan['payment'];
            $refundAmount = round(min($remaining, (float)$plan['left_amount']), 2);
            if ($refundAmount <= 0) {
                continue;
            }

            $refundItem = RefundItem::create([
                'refund_id' => (int)$refund->id,
                'order_id' => (int)$refund->order_id,
                'payment_id' => (int)$payment->id,
                'pay_way' => (int)$payment->pay_way,
                'pay_terminal' => UserTerminalEnum::WECHAT_MMP,
                'refund_amount' => $refundAmount,
                'refund_status' => RefundItem::STATUS_PENDING,
                'out_refund_no' => RefundItem::buildOutRefundNo((string)$refund->refund_sn, $index),
                'third_refund_no' => '',
                'refund_msg' => '',
                'create_time' => time(),
                'update_time' => time(),
            ]);

            if ($firstPaymentId <= 0) {
                $firstPaymentId = (int)$refundItem->payment_id;
            }

            $remaining = round($remaining - $refundAmount, 2);
            $index++;
        }

        if ($remaining > 0) {
            return [false, '退款金额拆分失败'];
        }

        if ($firstPaymentId > 0 && (int)($refund->payment_id ?? 0) <= 0) {
            $refund->payment_id = $firstPaymentId;
            $refund->update_time = time();
            $refund->save();
        }

        return [true, 'ok'];
    }


    /**
     * @notes 执行单个退款子项
     * @param Refund $refund
     * @param RefundItem $refundItem
     * @return array [bool, string]
     */
    protected static function executeRefundItem(Refund $refund, RefundItem $refundItem): array
    {
        switch ((int)$refundItem->pay_way) {
            case Payment::WAY_WECHAT:
                return self::refundWechatItem($refund, $refundItem);
            case Payment::WAY_OFFLINE:
                return self::markOfflineRefundProcessing($refundItem);
            default:
                self::syncRefundItemFailed($refundItem, '暂不支持的退款方式');
                return [false, '暂不支持的退款方式'];
        }
    }


    /**
     * @notes 微信退款
     * @param Refund $refund
     * @param RefundItem $refundItem
     * @return array
     */
    protected static function refundWechatItem(Refund $refund, RefundItem $refundItem): array
    {
        $payment = Payment::where('id', (int)$refundItem->payment_id)->find();
        $order = Order::where('id', (int)$refund->order_id)->find();
        if (!$payment || !$order) {
            self::syncRefundItemFailed($refundItem, '支付流水不存在');
            return [false, '支付流水不存在'];
        }

        if (trim((string)$payment->transaction_id) === '') {
            self::syncRefundItemFailed($refundItem, '缺少微信交易号，无法自动退款');
            return [false, '缺少微信交易号，无法自动退款'];
        }

        $lastError = '微信退款配置不可用';
        foreach (self::resolveWechatTerminals($order, (int)($refundItem->pay_terminal ?? 0)) as $terminal) {
            try {
                $result = (array)(new WeChatPayService($terminal))->refund([
                    'transaction_id' => (string)$payment->transaction_id,
                    'refund_sn' => (string)$refundItem->out_refund_no,
                    'refund_amount' => (float)$refundItem->refund_amount,
                    'total_amount' => (float)$payment->pay_amount,
                ]);

                $refundItem->pay_terminal = $terminal;
                $refundItem->update_time = time();
                $refundItem->save();

                $status = strtoupper((string)($result['status'] ?? ''));
                $validation = WeChatPayService::validateRefundResult($result, (string)$payment->payment_sn,
                    (string)$payment->transaction_id, $payment->pay_amount, $refundItem->refund_amount);
                if ($validation !== '') {
                    throw new \RuntimeException($validation);
                }
                if (in_array($status, ['ABNORMAL', 'CLOSED'], true)) {
                    self::syncRefundItemFailed($refundItem, '微信退款失败：' . $status);
                    return [false, '微信退款失败：' . $status];
                }
                if ($status === 'SUCCESS') {
                    self::syncRefundItemCompleted(
                        $refundItem,
                        (string)($result['refund_id'] ?? $refundItem->out_refund_no),
                        json_encode($result, JSON_UNESCAPED_UNICODE) ?: '微信退款完成',
                        (string)$refund->refund_reason,
                        $refund
                    );

                    return [true, '退款完成'];
                }

                $refundItem->refund_status = RefundItem::STATUS_PROCESSING;
                $refundItem->refund_msg = $status !== '' ? '微信退款处理中：' . $status : '微信退款处理中';
                $refundItem->update_time = time();
                $refundItem->save();

                return [true, '退款处理中'];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        // 网络异常不能证明退款未发出，保留额度并使用同一退款单号查单或重试。
        $refundItem->refund_status = RefundItem::STATUS_PROCESSING;
        $refundItem->refund_msg = '退款结果待确认：' . $lastError;
        $refundItem->save();
        return [true, '退款结果待确认'];
    }


    /**
     * @notes 线下退款进入处理中
     * @param RefundItem $refundItem
     * @return array
     */
    protected static function markOfflineRefundProcessing(RefundItem $refundItem): array
    {
        $refundItem->refund_status = RefundItem::STATUS_PROCESSING;
        $refundItem->refund_msg = '待线下确认退款';
        $refundItem->update_time = time();
        $refundItem->save();

        return [true, '待线下确认退款'];
    }

    /**
     * @notes 将子项标记为退款成功
     * @param RefundItem $refundItem
     * @param string $thirdRefundNo
     * @param string $message
     * @param string $refundReason
     * @param Refund|null $refund
     * @return void
     */
    protected static function syncRefundItemCompleted(
        RefundItem $refundItem,
        string $thirdRefundNo = '',
        string $message = '',
        string $refundReason = '',
        ?Refund $refund = null
    ): void
    {
        if ((int)$refundItem->refund_status === RefundItem::STATUS_COMPLETED) {
            return;
        }
        $refundItem->refund_status = RefundItem::STATUS_COMPLETED;
        $refundItem->third_refund_no = $thirdRefundNo;
        $refundItem->refund_msg = $message;
        $refundItem->refund_time = time();
        $refundItem->update_time = time();
        $refundItem->save();

        $payment = Payment::where('id', (int)$refundItem->payment_id)->lock(true)->find();
        if ($payment) {
            self::applyPaymentRefund($payment, (float)$refundItem->refund_amount, $refundReason);
            self::recordRefundFinancialFlow($refundItem, $payment, $refund, $thirdRefundNo, $refundReason);
        }
    }

    /**
     * @notes 将子项标记为退款失败
     * @param RefundItem $refundItem
     * @param string $message
     * @return void
     */
    protected static function syncRefundItemFailed(RefundItem $refundItem, string $message): void
    {
        $refundItem->refund_status = RefundItem::STATUS_FAILED;
        $refundItem->refund_msg = $message;
        $refundItem->update_time = time();
        $refundItem->save();
    }

    /**
     * @notes 刷新父退款单状态
     * @param Refund $refund
     * @param string $errorMessage
     * @return array [bool, string, bool, bool]
     */
    protected static function refreshParentRefund(Refund $refund, string $errorMessage = ''): array
    {
        /** @var Collection<int, RefundItem> $items */
        $items = RefundItem::where('refund_id', (int)$refund->id)->select();
        if ($items->isEmpty()) {
            self::failRefund($refund, 0.0, $errorMessage !== '' ? $errorMessage : '退款子项不存在');
            return [false, $errorMessage !== '' ? $errorMessage : '退款子项不存在', false, true];
        }

        if ((int)$refund->refund_status === Refund::STATUS_COMPLETED) {
            return [true, '退款已完成', false, false];
        }
        $actualRefundAmount = 0.0;
        $hasProcessing = false;
        $hasPending = false;
        $hasFailed = false;
        foreach ($items as $item) {
            $itemStatus = (int)$item->refund_status;
            if ($itemStatus === RefundItem::STATUS_COMPLETED) {
                $actualRefundAmount += (float)$item->refund_amount;
                continue;
            }

            if ($itemStatus === RefundItem::STATUS_PROCESSING) {
                $hasProcessing = true;
                continue;
            }

            if ($itemStatus === RefundItem::STATUS_PENDING) {
                $hasPending = true;
                continue;
            }

            if ($itemStatus === RefundItem::STATUS_FAILED) {
                $hasFailed = true;
            }
        }
        $actualRefundAmount = round($actualRefundAmount, 2);

        $refund->actual_refund_amount = $actualRefundAmount;
        $refund->update_time = time();

        if (!$hasProcessing && !$hasPending && !$hasFailed) {
            self::completeRefund($refund, $actualRefundAmount);
            return [true, '退款完成', true, false];
        }

        if ($hasProcessing || $hasPending) {
            $refund->refund_status = Refund::STATUS_PROCESSING;
            $refund->save();
            return [true, '退款处理中', false, false];
        }

        $message = $errorMessage !== '' ? $errorMessage : '退款执行失败';
        self::failRefund($refund, $actualRefundAmount, $message);
        return [false, $message, false, true];
    }

    /**
     * @notes 退款成功后的订单与退款单同步
     * @param Refund $refund
     * @param float $actualRefundAmount
     * @return void
     */
    protected static function completeRefund(Refund $refund, float $actualRefundAmount): void
    {
        $order = Order::where('id', (int)$refund->order_id)->lock(true)->find();
        if (!$order || (int)$refund->is_compensation === 1) {
            $refund->refund_status = Refund::STATUS_COMPLETED;
            $refund->actual_refund_amount = $actualRefundAmount;
            $refund->refund_time = time();
            $refund->update_time = time();
            $refund->save();
            return;
        }

        $paymentQuery = Payment::where('order_id', (int)$order->id)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED]);
        $totalRefunded = round((float)(clone $paymentQuery)->sum('refund_amount'), 2);
        $totalPaidAmount = round((float)(clone $paymentQuery)->sum('pay_amount'), 2);
        $isFullyRefunded = $totalPaidAmount > 0 && $totalRefunded >= $totalPaidAmount;
        $sourceStatus = self::normalizeSourceOrderStatus((int)($refund->source_order_status ?? Order::STATUS_PAID));
        $shouldReleaseResources = $isFullyRefunded && !self::isOrderFinishedStatus($sourceStatus);

        $refund->refund_status = Refund::STATUS_COMPLETED;
        $refund->actual_refund_amount = $actualRefundAmount;
        $refund->refund_time = time();
        if (trim((string)$refund->refund_transaction_id) === '') {
            $thirdRefundNo = (string)RefundItem::where('refund_id', (int)$refund->id)
                ->where('third_refund_no', '<>', '')
                ->value('third_refund_no');
            if ($thirdRefundNo !== '') {
                $refund->refund_transaction_id = $thirdRefundNo;
            }
        }
        $refund->update_time = time();
        $refund->save();

        if ($shouldReleaseResources) {
            self::releaseOrderResources($order);
        }

        $order->order_status = self::resolveCompletedOrderStatus($refund, $isFullyRefunded);
        $order->pay_status = $isFullyRefunded ? Order::PAY_STATUS_FULL_REFUND : Order::PAY_STATUS_PARTIAL_REFUND;
        $order->update_time = time();
        $order->save();

        StaffScheduleConfirmLetterService::markOutdatedByOrderId((int) $order->id);
    }

    /**
     * @notes 退款失败后的订单与退款单同步
     * @param Refund $refund
     * @param float $actualRefundAmount
     * @param string $message
     * @return void
     */
    protected static function failRefund(Refund $refund, float $actualRefundAmount, string $message = ''): void
    {
        $refund->refund_status = Refund::STATUS_FAILED;
        $refund->actual_refund_amount = $actualRefundAmount;
        if ($actualRefundAmount > 0) {
            $refund->refund_time = max((int)($refund->refund_time ?? 0), time());
        }
        $refund->update_time = time();
        $refund->save();

        $order = Order::where('id', (int)$refund->order_id)->lock(true)->find();
        if (!$order || (int)$refund->is_compensation === 1) {
            return;
        }

        $order->order_status = self::normalizeSourceOrderStatus((int)($refund->source_order_status ?? Order::STATUS_PAID));
        $order->pay_status = $actualRefundAmount > 0
            ? Order::PAY_STATUS_PARTIAL_REFUND
            : self::normalizeSourcePayStatus((int)($refund->source_pay_status ?? Order::PAY_STATUS_PAID));
        $order->update_time = time();
        $order->save();
    }

    /**
     * @notes 更新支付流水退款信息
     * @param Payment $payment
     * @param float $refundAmount
     * @param string $refundReason
     * @return void
     */
    protected static function applyPaymentRefund(Payment $payment, float $refundAmount, string $refundReason = ''): void
    {
        $total = MoneyService::yuanToFen($payment->refund_amount) + MoneyService::yuanToFen($refundAmount);
        if ($total > MoneyService::yuanToFen($payment->pay_amount)) {
            throw new \RuntimeException('累计退款超过原支付流水金额');
        }
        $payment->refund_amount = $total / 100;
        $payment->refund_time = time();
        $payment->refund_reason = $refundReason;
        $payment->pay_status = $payment->refund_amount >= round((float)$payment->pay_amount, 2)
            ? Payment::STATUS_REFUNDED
            : Payment::STATUS_PAID;
        $payment->update_time = time();
        $payment->save();
    }

    /**
     * @notes 记录退款资金流水
     * @param RefundItem $refundItem
     * @param Payment $payment
     * @param Refund|null $refund
     * @param string $thirdRefundNo
     * @param string $refundReason
     * @return void
     */
    protected static function recordRefundFinancialFlow(
        RefundItem $refundItem,
        Payment $payment,
        ?Refund $refund = null,
        string $thirdRefundNo = '',
        string $refundReason = ''
    ): void {
        if (!$payment->isPlatformCollection()) {
            return;
        }
        $refund = $refund ?: Refund::find((int)$refundItem->refund_id);

        FinancialFlow::createUniqueFlow([
            'flow_type' => FinancialFlow::FLOW_TYPE_REFUND,
            'biz_type' => FinancialFlow::BIZ_TYPE_ORDER_REFUND,
            'biz_id' => (int)$refundItem->id,
            'biz_sn' => (string)($refund->refund_sn ?? $refundItem->out_refund_no),
            'order_id' => (int)$refundItem->order_id,
            'user_id' => (int)($refund->user_id ?? $payment->user_id ?? 0),
            'amount' => round((float)$refundItem->refund_amount, 2),
            'direction' => FinancialFlow::DIRECTION_OUT,
            'pay_way' => (int)$refundItem->pay_way,
            'transaction_id' => trim($thirdRefundNo) !== '' ? $thirdRefundNo : (string)($refundItem->third_refund_no ?? ''),
            'remark' => self::buildRefundFlowRemark($refundReason),
            'operator_type' => 0,
            'operator_id' => 0,
            'create_time' => max((int)($refundItem->refund_time ?? 0), time()),
        ]);
    }

    /**
     * @notes 构建退款流水备注
     * @param string $refundReason
     * @return string
     */
    protected static function buildRefundFlowRemark(string $refundReason = ''): string
    {
        $remark = trim($refundReason);
        if ($remark === '') {
            return '订单退款出账';
        }

        $maxLength = 240;
        $reason = mb_strlen($remark, 'UTF-8') > $maxLength
            ? mb_substr($remark, 0, $maxLength, 'UTF-8')
            : $remark;

        return '订单退款出账：' . $reason;
    }

    /**
     * @notes 获取订单可退支付流水
     * @param int $orderId
     * @return array<int, array{payment: Payment, left_amount: float}>
     */
    protected static function getRefundablePayments(int $orderId, int $compensationPaymentId = 0): array
    {
        $query = Payment::where('order_id', $orderId);
        if ($compensationPaymentId > 0) {
            $query->where('id', $compensationPaymentId);
        } else {
            $compensationIds = Refund::where('order_id', $orderId)->where('is_compensation', 1)->column('payment_id');
            if ($compensationIds) {
                $query->whereNotIn('id', $compensationIds);
            }
        }
        $payments = $query->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->order(['pay_time' => 'desc', 'id' => 'desc'])
            ->select();

        $lists = [];
        foreach ($payments as $payment) {
            $reserved = RefundItem::where('payment_id', (int)$payment->id)
                ->where('refund_status', '<>', RefundItem::STATUS_COMPLETED)->sum('refund_amount');
            $leftAmount = (MoneyService::yuanToFen($payment->pay_amount)
                - MoneyService::yuanToFen($payment->refund_amount) - MoneyService::yuanToFen($reserved)) / 100;
            if ($leftAmount <= 0) {
                continue;
            }

            $lists[] = [
                'payment' => $payment,
                'left_amount' => $leftAmount,
            ];
        }

        return $lists;
    }

    /**
     * @notes 获取微信退款优先终端
     * @param Order $order
     * @param int $savedTerminal
     * @return array<int>
     */
    protected static function resolveWechatTerminals(Order $order, int $savedTerminal = 0): array
    {
        return [UserTerminalEnum::WECHAT_MMP];
    }

    /**
     * @notes 规范化来源订单状态
     * @param int $status
     * @return int
     */
    protected static function normalizeSourceOrderStatus(int $status): int
    {
        $validStatus = [
            Order::STATUS_PENDING_CONFIRM,
            Order::STATUS_PENDING_PAY,
            Order::STATUS_PAID,
            Order::STATUS_IN_SERVICE,
            Order::STATUS_COMPLETED,
            Order::STATUS_REVIEWED,
            Order::STATUS_CANCELLED,
            Order::STATUS_PAUSED,
            Order::STATUS_REFUNDED,
            Order::STATUS_USER_DELETED,
        ];

        if (in_array($status, $validStatus, true) && $status !== Order::STATUS_REFUNDED) {
            return $status;
        }

        return Order::STATUS_PAID;
    }

    /**
     * @notes 解析退款完成后的订单状态
     * @param Refund $refund
     * @param bool $isFullyRefunded
     * @return int
     */
    protected static function resolveCompletedOrderStatus(Refund $refund, bool $isFullyRefunded): int
    {
        if ($isFullyRefunded) {
            return Order::STATUS_REFUNDED;
        }

        $sourceStatus = self::normalizeSourceOrderStatus(
            (int)($refund->source_order_status ?? Order::STATUS_PAID)
        );

        return $sourceStatus;
    }

    /**
     * @notes 全额退款成功后释放订单占用资源
     * @param Order $order
     * @return void
     */
    protected static function releaseOrderResources(Order $order): void
    {
        $items = OrderItem::where('order_id', (int)$order->id)
            ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->select();

        foreach ($items as $item) {
            if ((int)$item->schedule_id > 0) {
                Schedule::releaseLock((int)$item->schedule_id);
            }
        }

        PackageBooking::releaseByOrderId((int)$order->id);
    }

    /**
     * @notes 规范化来源支付状态
     * @param int $status
     * @return int
     */
    protected static function normalizeSourcePayStatus(int $status): int
    {
        return in_array($status, [
            Order::PAY_STATUS_UNPAID,
            Order::PAY_STATUS_PAID,
            Order::PAY_STATUS_PARTIAL_REFUND,
            Order::PAY_STATUS_FULL_REFUND,
        ], true) ? $status : Order::PAY_STATUS_PAID;
    }

    /**
     * @notes 分发退款通知
     * @param int $refundId
     * @param bool $notifyCompleted
     * @param bool $notifyFailed
     * @return void
     */
    protected static function dispatchRefundNotifications(int $refundId, bool $notifyCompleted, bool $notifyFailed): void
    {
        if ($refundId <= 0) {
            return;
        }

        if ($notifyCompleted) {
            OrderNotificationService::notifyUserAndStaffOnRefundCompleted($refundId);
        }

        if ($notifyFailed) {
            OrderNotificationService::notifyUserOnRefundFailed($refundId);
        }
    }
}
