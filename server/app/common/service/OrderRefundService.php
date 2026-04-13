<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单退款服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\user\AccountLogEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\Payment;
use app\common\model\order\Refund;
use app\common\model\order\RefundItem;
use app\common\model\package\PackageBooking;
use app\common\model\schedule\Schedule;
use app\common\model\user\User;
use app\common\service\pay\AliPayService;
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

        return self::getRefundableAmount((int)$order->id) > 0;
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
        if (!RefundItem::isTableReady()) {
            return [false, '退款子项表不存在，请执行退款流程升级脚本'];
        }

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
    public static function confirmOfflineRefund(Refund $refund, string $transactionId = ''): array
    {
        if (!RefundItem::isTableReady()) {
            return [false, '退款子项表不存在，请执行退款流程升级脚本'];
        }

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

        foreach ($items as $item) {
            if ((int)$item->pay_way !== Payment::WAY_OFFLINE) {
                return [false, '当前退款单包含自动退款项，不能手工确认'];
            }
        }

        foreach ($items as $item) {
            if ((int)$item->refund_status === RefundItem::STATUS_COMPLETED) {
                continue;
            }

            if ((int)$item->refund_status === RefundItem::STATUS_FAILED) {
                return [false, '退款子项已失败，请重新发起退款'];
            }

            self::syncRefundItemCompleted(
                $item,
                $transactionId !== '' ? $transactionId : (string)$refund->refund_sn,
                '线下退款已确认',
                (string)$refund->refund_reason
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
        if ($refundStatus !== 'SUCCESS' || $outRefundNo === '') {
            return false;
        }

        $result = self::completeRefundItemByOutRefundNo(
            $outRefundNo,
            (string)($message['refund_id'] ?? $outRefundNo),
            json_encode($message, JSON_UNESCAPED_UNICODE)
        );

        return (bool)($result['success'] ?? false);
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

            self::syncRefundItemCompleted($refundItem, $thirdRefundNo, $message, (string)$refund->refund_reason);
            [$success, $resultMessage, $notifyCompleted, $notifyFailed] = self::refreshParentRefund($refund);
            $refundId = (int)$refund->id;

            Db::commit();

            self::dispatchRefundNotifications($refundId, $notifyCompleted, $notifyFailed);

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

            Db::commit();

            self::dispatchRefundNotifications($refundId, $notifyCompleted, $notifyFailed);

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
    protected static function createRefundItems(Refund $refund): array
    {
        $plans = self::getRefundablePayments((int)$refund->order_id);
        if (empty($plans)) {
            return [false, '订单缺少可退支付流水'];
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
                'pay_terminal' => 0,
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
            case Payment::WAY_BALANCE:
                return self::refundBalanceItem($refund, $refundItem);
            case Payment::WAY_WECHAT:
                return self::refundWechatItem($refund, $refundItem);
            case Payment::WAY_ALIPAY:
                return self::refundAliPayItem($refund, $refundItem);
            case Payment::WAY_OFFLINE:
                return self::markOfflineRefundProcessing($refundItem);
            default:
                self::syncRefundItemFailed($refundItem, '暂不支持的退款方式');
                return [false, '暂不支持的退款方式'];
        }
    }

    /**
     * @notes 余额退款
     * @param Refund $refund
     * @param RefundItem $refundItem
     * @return array
     */
    protected static function refundBalanceItem(Refund $refund, RefundItem $refundItem): array
    {
        $user = User::lock(true)->find((int)$refund->user_id);
        if (!$user) {
            self::syncRefundItemFailed($refundItem, '退款用户不存在');
            return [false, '退款用户不存在'];
        }

        $refundAmount = round((float)$refundItem->refund_amount, 2);
        $user->user_money = round((float)$user->user_money + $refundAmount, 2);
        $user->save();

        AccountLogLogic::add(
            (int)$refund->user_id,
            AccountLogEnum::UM_INC_ORDER_REFUND,
            AccountLogEnum::INC,
            $refundAmount,
            (string)$refund->refund_sn,
            '订单退款退回余额',
            [
                'order_id' => (int)$refund->order_id,
                'refund_id' => (int)$refund->id,
                'refund_item_id' => (int)$refundItem->id,
                'payment_id' => (int)$refundItem->payment_id,
            ]
        );

        self::syncRefundItemCompleted($refundItem, 'BALANCE', '余额退款完成', (string)$refund->refund_reason);
        return [true, '退款完成'];
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
                (new WeChatPayService($terminal))->refund([
                    'transaction_id' => (string)$payment->transaction_id,
                    'refund_sn' => (string)$refundItem->out_refund_no,
                    'refund_amount' => (float)$refundItem->refund_amount,
                    'total_amount' => (float)$payment->pay_amount,
                ]);

                $refundItem->pay_terminal = $terminal;
                $refundItem->refund_status = RefundItem::STATUS_PROCESSING;
                $refundItem->refund_msg = '';
                $refundItem->update_time = time();
                $refundItem->save();

                return [true, '退款处理中'];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        self::syncRefundItemFailed($refundItem, $lastError);
        return [false, $lastError];
    }

    /**
     * @notes 支付宝退款
     * @param Refund $refund
     * @param RefundItem $refundItem
     * @return array
     */
    protected static function refundAliPayItem(Refund $refund, RefundItem $refundItem): array
    {
        $payment = Payment::where('id', (int)$refundItem->payment_id)->find();
        if (!$payment) {
            self::syncRefundItemFailed($refundItem, '支付流水不存在');
            return [false, '支付流水不存在'];
        }

        $orderSn = trim((string)($payment->payment_sn ?: $payment->order_sn));
        if ($orderSn === '') {
            self::syncRefundItemFailed($refundItem, '缺少支付宝商户单号，无法自动退款');
            return [false, '缺少支付宝商户单号，无法自动退款'];
        }

        try {
            $result = (array)(new AliPayService())->refund($orderSn, (float)$refundItem->refund_amount, (string)$refundItem->out_refund_no);
            if (($result['code'] ?? '') === '10000' && ($result['msg'] ?? '') === 'Success' && ($result['fundChange'] ?? '') === 'Y') {
                self::syncRefundItemCompleted(
                    $refundItem,
                    (string)($result['tradeNo'] ?? ''),
                    json_encode($result, JSON_UNESCAPED_UNICODE),
                    (string)$refund->refund_reason
                );
                return [true, '退款完成'];
            }

            $message = (string)($result['subMsg'] ?? $result['msg'] ?? '支付宝退款失败');
            self::syncRefundItemFailed($refundItem, $message);
            return [false, $message];
        } catch (\Throwable $e) {
            self::syncRefundItemFailed($refundItem, $e->getMessage());
            return [false, $e->getMessage()];
        }
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
     * @return void
     */
    protected static function syncRefundItemCompleted(RefundItem $refundItem, string $thirdRefundNo = '', string $message = '', string $refundReason = ''): void
    {
        $refundItem->refund_status = RefundItem::STATUS_COMPLETED;
        $refundItem->third_refund_no = $thirdRefundNo;
        $refundItem->refund_msg = $message;
        $refundItem->refund_time = time();
        $refundItem->update_time = time();
        $refundItem->save();

        $payment = Payment::where('id', (int)$refundItem->payment_id)->lock(true)->find();
        if ($payment) {
            self::applyPaymentRefund($payment, (float)$refundItem->refund_amount, $refundReason);
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
        if (!$order) {
            $refund->refund_status = Refund::STATUS_COMPLETED;
            $refund->actual_refund_amount = $actualRefundAmount;
            $refund->refund_time = time();
            $refund->update_time = time();
            $refund->save();
            return;
        }

        $paymentQuery = Payment::where('order_id', (int)$order->id);
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

        OrderConfirmLetterService::markOutdatedByOrderId((int) $order->id);
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
        if (!$order) {
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
        $payment->refund_amount = round(min((float)$payment->pay_amount, (float)$payment->refund_amount + $refundAmount), 2);
        $payment->refund_time = time();
        $payment->refund_reason = $refundReason;
        $payment->pay_status = $payment->refund_amount >= round((float)$payment->pay_amount, 2)
            ? Payment::STATUS_REFUNDED
            : Payment::STATUS_PAID;
        $payment->update_time = time();
        $payment->save();
    }

    /**
     * @notes 获取订单可退支付流水
     * @param int $orderId
     * @return array<int, array{payment: Payment, left_amount: float}>
     */
    protected static function getRefundablePayments(int $orderId): array
    {
        $payments = Payment::where('order_id', $orderId)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->order(['pay_time' => 'desc', 'id' => 'desc'])
            ->select();

        $lists = [];
        foreach ($payments as $payment) {
            $leftAmount = round((float)$payment->pay_amount - (float)($payment->refund_amount ?? 0), 2);
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
        $terminals = [];
        if ($savedTerminal > 0) {
            $terminals[] = $savedTerminal;
        }

        $sourceTerminal = match ((int)($order->source ?? Order::SOURCE_MINIAPP)) {
            Order::SOURCE_H5 => UserTerminalEnum::H5,
            Order::SOURCE_ADMIN => UserTerminalEnum::PC,
            default => UserTerminalEnum::WECHAT_MMP,
        };
        $terminals[] = $sourceTerminal;

        foreach ([
            UserTerminalEnum::WECHAT_MMP,
            UserTerminalEnum::WECHAT_OA,
            UserTerminalEnum::H5,
            UserTerminalEnum::PC,
            UserTerminalEnum::IOS,
            UserTerminalEnum::ANDROID,
        ] as $terminal) {
            $terminals[] = $terminal;
        }

        return array_values(array_unique(array_filter($terminals)));
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
