<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端我的钱包（对账只读）逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\dynamic\ActivityPayment;
use app\common\model\dynamic\ActivityRefund;
use app\common\model\dynamic\ActivityRegistration;
use app\common\model\dynamic\Dynamic;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\Payment;
use app\common\model\order\Refund;
use app\common\model\order\RefundItem;
use app\common\service\FileService;
use app\common\service\MoneyService;

/**
 * 我的钱包 - 对账流水逻辑
 *
 * 只读聚合：订单支付/退款 + 活动报名支付/退款，全部按 user_id 过滤，
 * 不读取 la_financial_flow（该表只记录平台收款，服务人员代收会漏）。
 * 仅提供资金流水查询，不提供任何资金变动类写能力。
 *
 * Class WalletLogic
 * @package app\api\logic
 */
class WalletLogic extends BaseLogic
{
    /** 订单支付异常态（历史数据没有模型常量） */
    private const PAY_STATUS_EXCEPTION = 4;

    private const PAGE_SIZE_DEFAULT = 20;
    private const PAGE_SIZE_MAX = 50;

    /**
     * @notes 对账流水列表
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function flows(int $userId, array $params): array
    {
        $month = self::normalizeMonth($params['month'] ?? '');
        $direction = self::normalizeDirection($params['direction'] ?? 'all');
        $bizGroup = self::normalizeBizGroup($params['biz_group'] ?? 'all');
        $page = max(1, (int)($params['page'] ?? 1));
        $pageSize = (int)($params['page_size'] ?? 0);
        if ($pageSize < 1) {
            $pageSize = self::PAGE_SIZE_DEFAULT;
        }
        $pageSize = min($pageSize, self::PAGE_SIZE_MAX);

        $allFlows = self::collectFlows($userId);

        $months = [];
        foreach ($allFlows as $item) {
            $flowMonth = (string)($item['month'] ?? '');
            if ($flowMonth !== '') {
                $months[$flowMonth] = true;
            }
        }
        $months = array_keys($months);
        rsort($months, SORT_STRING);

        $summary = self::computeSummary($allFlows, $month);

        $filtered = [];
        foreach ($allFlows as $item) {
            if ($month !== '' && (string)($item['month'] ?? '') !== $month) {
                continue;
            }
            if ($direction !== 'all' && (string)($item['direction'] ?? '') !== $direction) {
                continue;
            }
            if ($bizGroup !== 'all' && (string)($item['biz_group'] ?? '') !== $bizGroup) {
                continue;
            }
            $filtered[] = $item;
        }

        usort($filtered, static function (array $a, array $b): int {
            if ((int)$a['event_time'] !== (int)$b['event_time']) {
                return (int)$b['event_time'] <=> (int)$a['event_time'];
            }
            if ((int)$a['id'] !== (int)$b['id']) {
                return (int)$b['id'] <=> (int)$a['id'];
            }
            return strcmp((string)$b['flow_key'], (string)$a['flow_key']);
        });

        $total = count($filtered);
        $offset = ($page - 1) * $pageSize;
        $slice = array_slice($filtered, $offset, $pageSize);

        return [
            'summary' => $summary,
            'months' => $months,
            'list' => $slice,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'has_more' => ($offset + count($slice)) < $total,
        ];
    }

    /**
     * @notes 对账流水详情
     * @param int $userId
     * @param string $flowKey
     * @return array|null
     */
    public static function detail(int $userId, string $flowKey): ?array
    {
        self::$error = '';

        if (!preg_match('/^(order_pay|order_refund|activity_pay|activity_refund):([1-9]\d*)$/', $flowKey, $matches)) {
            self::$error = '参数错误';
            return null;
        }

        $category = (string)$matches[1];
        $id = (int)$matches[2];

        switch ($category) {
            case 'order_pay':
                return self::orderPayDetail($userId, $id);
            case 'order_refund':
                return self::orderRefundDetail($userId, $id);
            case 'activity_pay':
                return self::activityPayDetail($userId, $id);
            default:
                return self::activityRefundDetail($userId, $id);
        }
    }

    /**
     * @notes 对账汇总（整数分累加，退款中不计入已退款）
     * @param array $flows
     * @param string $month
     * @return array
     */
    public static function computeSummary(array $flows, string $month = ''): array
    {
        $paidFen = 0;
        $refundedFen = 0;
        $refundingFen = 0;
        $paidCount = 0;
        $refundedCount = 0;
        $refundingCount = 0;
        $totalCount = 0;

        foreach ($flows as $item) {
            if (!is_array($item)) {
                continue;
            }
            if ($month !== '' && (string)($item['month'] ?? '') !== $month) {
                continue;
            }

            $totalCount++;
            $fen = MoneyService::yuanToFen($item['amount'] ?? 0);
            $direction = (string)($item['direction'] ?? '');
            $status = (string)($item['status'] ?? '');

            if ($direction === 'out') {
                $paidFen += $fen;
                $paidCount++;
            } elseif ($direction === 'in' && $status === 'completed') {
                $refundedFen += $fen;
                $refundedCount++;
            } elseif ($direction === 'in' && $status === 'processing') {
                $refundingFen += $fen;
                $refundingCount++;
            }
        }

        return [
            'month' => $month,
            'total_paid' => round($paidFen / 100, 2),
            'total_refunded' => round($refundedFen / 100, 2),
            'net_expense' => round(($paidFen - $refundedFen) / 100, 2),
            'refunding_amount' => round($refundingFen / 100, 2),
            'refunding_count' => (int)$refundingCount,
            'paid_count' => (int)$paidCount,
            'refunded_count' => (int)$refundedCount,
            'total_count' => (int)$totalCount,
        ];
    }

    /**
     * @notes 聚合当前用户全部有效对账流水
     * @param int $userId
     * @return array
     */
    private static function collectFlows(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $orderPayments = Payment::where('user_id', $userId)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED, self::PAY_STATUS_EXCEPTION])
            ->where('pay_amount', '>', 0)
            ->order('id', 'asc')
            ->select();

        $orderRefunds = Refund::where('user_id', $userId)
            ->whereIn('refund_status', [
                Refund::STATUS_PENDING,
                Refund::STATUS_APPROVED,
                Refund::STATUS_PROCESSING,
                Refund::STATUS_COMPLETED,
            ])
            ->order('id', 'asc')
            ->select();

        $activityPayments = ActivityPayment::where('user_id', $userId)
            ->whereIn('pay_status', [
                ActivityPayment::STATUS_PAID,
                ActivityPayment::STATUS_REFUNDED,
                ActivityPayment::STATUS_EXCEPTION,
            ])
            ->where('pay_amount', '>', 0)
            ->order('id', 'asc')
            ->select();

        $activityRefunds = ActivityRefund::where('user_id', $userId)
            ->whereIn('refund_status', [
                ActivityRefund::STATUS_PENDING,
                ActivityRefund::STATUS_APPROVED,
                ActivityRefund::STATUS_PROCESSING,
                ActivityRefund::STATUS_COMPLETED,
            ])
            ->order('id', 'asc')
            ->select();

        $orderIds = [];
        $refundIds = [];
        $paymentIds = [];
        $registrationIds = [];
        $dynamicIds = [];
        $activityPaymentIds = [];

        foreach ($orderPayments as $payment) {
            $orderIds[(int)$payment->order_id] = true;
        }
        foreach ($orderRefunds as $refund) {
            $orderIds[(int)$refund->order_id] = true;
            $refundIds[(int)$refund->id] = true;
            if ((int)$refund->payment_id > 0) {
                $paymentIds[(int)$refund->payment_id] = true;
            }
        }
        foreach ($activityPayments as $payment) {
            $registrationIds[(int)$payment->registration_id] = true;
            $dynamicIds[(int)$payment->dynamic_id] = true;
        }
        foreach ($activityRefunds as $refund) {
            $registrationIds[(int)$refund->registration_id] = true;
            $dynamicIds[(int)$refund->dynamic_id] = true;
            if ((int)$refund->payment_id > 0) {
                $activityPaymentIds[(int)$refund->payment_id] = true;
            }
        }

        $orderMap = self::loadOrders(array_keys($orderIds));
        $orderItemMap = self::loadOrderItems(array_keys($orderIds));
        $refundItemMap = self::loadRefundItems(array_keys($refundIds));
        $paymentMap = self::loadPayments(array_keys($paymentIds));
        $registrationMap = self::loadRegistrations(array_keys($registrationIds));
        $dynamicMap = self::loadDynamics(array_keys($dynamicIds));
        $activityPaymentMap = self::loadActivityPayments(array_keys($activityPaymentIds));

        $flows = [];
        foreach ($orderPayments as $payment) {
            $flow = self::buildOrderPayFlow($payment, $orderMap, $orderItemMap);
            if ($flow !== null) {
                $flows[] = $flow;
            }
        }
        foreach ($orderRefunds as $refund) {
            $flow = self::buildOrderRefundFlow($refund, $orderMap, $orderItemMap, $refundItemMap, $paymentMap);
            if ($flow !== null) {
                $flows[] = $flow;
            }
        }
        foreach ($activityPayments as $payment) {
            $flow = self::buildActivityPayFlow($payment, $registrationMap, $dynamicMap);
            if ($flow !== null) {
                $flows[] = $flow;
            }
        }
        foreach ($activityRefunds as $refund) {
            $flow = self::buildActivityRefundFlow($refund, $registrationMap, $dynamicMap, $activityPaymentMap);
            if ($flow !== null) {
                $flows[] = $flow;
            }
        }

        return $flows;
    }

    /**
     * @notes 订单支付流水的对账字段
     * @param Payment $payment
     * @param array $orderMap
     * @param array $orderItemMap
     * @return array|null
     */
    private static function buildOrderPayFlow(Payment $payment, array $orderMap, array $orderItemMap): ?array
    {
        $payStatus = (int)$payment->pay_status;
        if (!in_array($payStatus, [Payment::STATUS_PAID, Payment::STATUS_REFUNDED, self::PAY_STATUS_EXCEPTION], true)) {
            return null;
        }

        $amount = round((float)$payment->pay_amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $order = $orderMap[(int)$payment->order_id] ?? null;
        $items = $orderItemMap[(int)$payment->order_id] ?? [];
        [$serviceSummary, $subtitle] = self::buildOrderContext($order, $items, (string)($payment->order_sn ?? ''));
        $orderSn = (string)($order->order_sn ?? $payment->order_sn ?? '');

        $bizTypeLabel = self::getOrderPayTypeLabel((int)$payment->pay_type);
        $isException = $payStatus === self::PAY_STATUS_EXCEPTION;
        $collectionOwner = (int)($payment->collection_owner ?? Payment::COLLECTION_PLATFORM);
        $payTime = self::getModelTimestamp($payment, 'pay_time');
        $eventTime = $payTime > 0 ? $payTime : self::getModelTimestamp($payment, 'create_time');

        $voucherUrl = (string)($payment->pay_voucher ?? '');
        if ($voucherUrl === '' && (int)$payment->pay_way === Payment::WAY_OFFLINE) {
            $voucherUrl = self::resolveOrderVoucher($order);
        }

        return [
            'id' => (int)$payment->id,
            'flow_key' => 'order_pay:' . (int)$payment->id,
            'biz_category' => 'order_pay',
            'biz_group' => 'order',
            'biz_sn' => (string)($payment->payment_sn ?? ''),
            'biz_type_label' => $bizTypeLabel,
            'order_id' => (int)$payment->order_id,
            'order_sn' => $orderSn,
            'registration_id' => 0,
            'dynamic_id' => 0,
            'remark' => (string)($payment->remark ?? ''),
            'direction' => 'out',
            'amount' => $amount,
            'status' => $isException ? 'exception' : 'completed',
            'status_label' => $isException ? '异常待退' : '已支付',
            'status_tone' => $isException ? 'danger' : 'success',
            'title' => $serviceSummary !== '' ? $bizTypeLabel . ' · ' . $serviceSummary : $bizTypeLabel,
            'subtitle' => $subtitle,
            'month' => self::resolveMonth($eventTime),
            'event_time' => $eventTime,
            'event_time_text' => self::formatMinuteTime($eventTime),
            'pay_way' => (int)$payment->pay_way,
            'pay_way_label' => Payment::getPayWayText((int)$payment->pay_way),
            'transaction_id' => (string)($payment->transaction_id ?? ''),
            'voucher_url' => $voucherUrl,
            'refunded_amount' => round((float)($payment->refund_amount ?? 0), 2),
            'collection_owner' => $collectionOwner,
            'collection_owner_label' => $collectionOwner === Payment::COLLECTION_STAFF ? '服务人员代收' : '平台收款',
        ];
    }

    /**
     * @notes 订单退款流水的对账字段
     * @param Refund $refund
     * @param array $orderMap
     * @param array $orderItemMap
     * @param array $refundItemMap
     * @param array $paymentMap
     * @return array|null
     */
    private static function buildOrderRefundFlow(
        Refund $refund,
        array $orderMap,
        array $orderItemMap,
        array $refundItemMap,
        array $paymentMap
    ): ?array {
        $refundStatus = (int)$refund->refund_status;
        if (!in_array($refundStatus, [
            Refund::STATUS_PENDING,
            Refund::STATUS_APPROVED,
            Refund::STATUS_PROCESSING,
            Refund::STATUS_COMPLETED,
        ], true)) {
            return null;
        }

        $isCompleted = $refundStatus === Refund::STATUS_COMPLETED;
        $amount = $isCompleted
            ? round((float)$refund->actual_refund_amount, 2)
            : round((float)$refund->refund_amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $order = $orderMap[(int)$refund->order_id] ?? null;
        $items = $orderItemMap[(int)$refund->order_id] ?? [];
        [$serviceSummary, $subtitle] = self::buildOrderContext($order, $items);
        $orderSn = (string)($order->order_sn ?? '');

        $refundItems = $refundItemMap[(int)$refund->id] ?? [];
        $payWay = 0;
        $payWayLabel = '原路退回';
        if ($refundItems !== []) {
            $ways = [];
            foreach ($refundItems as $refundItem) {
                $ways[(int)$refundItem->pay_way] = true;
            }
            if (count($ways) === 1) {
                $payWay = (int)array_key_first($ways);
                $payWayLabel = Payment::getPayWayText($payWay);
            } else {
                $payWayLabel = '多渠道退回';
            }
        } else {
            $originPayment = $paymentMap[(int)$refund->payment_id] ?? null;
            if ($originPayment !== null) {
                $payWay = (int)$originPayment->pay_way;
                $payWayLabel = Payment::getPayWayText($payWay);
            }
        }

        $transactionId = trim((string)($refund->refund_transaction_id ?? ''));
        if ($transactionId === '') {
            foreach ($refundItems as $refundItem) {
                $thirdRefundNo = trim((string)($refundItem->third_refund_no ?? ''));
                if ($thirdRefundNo !== '') {
                    $transactionId = $thirdRefundNo;
                    break;
                }
            }
        }

        $voucherUrl = '';
        foreach ($refundItems as $refundItem) {
            $voucher = (string)($refundItem->refund_voucher ?? '');
            if ($voucher !== '') {
                $voucherUrl = $voucher;
                break;
            }
        }

        if ($isCompleted) {
            $refundTime = self::getModelTimestamp($refund, 'refund_time');
            $eventTime = $refundTime > 0 ? $refundTime : self::getModelTimestamp($refund, 'update_time');
        } else {
            $eventTime = self::getModelTimestamp($refund, 'create_time');
        }

        return [
            'id' => (int)$refund->id,
            'flow_key' => 'order_refund:' . (int)$refund->id,
            'biz_category' => 'order_refund',
            'biz_group' => 'order',
            'biz_sn' => (string)($refund->refund_sn ?? ''),
            'biz_type_label' => '订单退款',
            'order_id' => (int)$refund->order_id,
            'order_sn' => $orderSn,
            'registration_id' => 0,
            'dynamic_id' => 0,
            'remark' => (string)($refund->refund_reason ?? ''),
            'direction' => 'in',
            'amount' => $amount,
            'status' => $isCompleted ? 'completed' : 'processing',
            'status_label' => self::getRefundStatusLabel($refundStatus),
            'status_tone' => $isCompleted ? 'success' : 'warning',
            'title' => $serviceSummary !== '' ? '订单退款 · ' . $serviceSummary : '订单退款',
            'subtitle' => $subtitle,
            'month' => self::resolveMonth($eventTime),
            'event_time' => $eventTime,
            'event_time_text' => self::formatMinuteTime($eventTime),
            'pay_way' => $payWay,
            'pay_way_label' => $payWayLabel,
            'transaction_id' => $transactionId,
            'voucher_url' => $voucherUrl,
            'refunded_amount' => 0.0,
            'collection_owner' => 1,
            'collection_owner_label' => '平台收款',
        ];
    }

    /**
     * @notes 活动报名支付流水的对账字段
     * @param ActivityPayment $payment
     * @param array $registrationMap
     * @param array $dynamicMap
     * @return array|null
     */
    private static function buildActivityPayFlow(ActivityPayment $payment, array $registrationMap, array $dynamicMap): ?array
    {
        $payStatus = (int)$payment->pay_status;
        if (!in_array($payStatus, [
            ActivityPayment::STATUS_PAID,
            ActivityPayment::STATUS_REFUNDED,
            ActivityPayment::STATUS_EXCEPTION,
        ], true)) {
            return null;
        }

        $amount = round((float)$payment->pay_amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $context = self::buildActivityContext(
            (int)$payment->registration_id,
            (int)$payment->dynamic_id,
            $registrationMap,
            $dynamicMap
        );
        $ticketName = $context['ticket_name'];
        $dynamicTitle = $context['dynamic_title'];

        $isException = $payStatus === ActivityPayment::STATUS_EXCEPTION;
        $payTime = self::getModelTimestamp($payment, 'pay_time');
        $eventTime = $payTime > 0 ? $payTime : self::getModelTimestamp($payment, 'create_time');

        return [
            'id' => (int)$payment->id,
            'flow_key' => 'activity_pay:' . (int)$payment->id,
            'biz_category' => 'activity_pay',
            'biz_group' => 'activity',
            'biz_sn' => (string)($payment->payment_sn ?? ''),
            'biz_type_label' => '活动报名',
            'order_id' => 0,
            'order_sn' => '',
            'registration_id' => (int)$payment->registration_id,
            'dynamic_id' => $context['dynamic_id'],
            'remark' => '',
            'direction' => 'out',
            'amount' => $amount,
            'status' => $isException ? 'exception' : 'completed',
            'status_label' => $isException ? '异常待退' : '已支付',
            'status_tone' => $isException ? 'danger' : 'success',
            'title' => $dynamicTitle !== '' ? '活动报名 · ' . $dynamicTitle : '活动报名',
            'subtitle' => $ticketName,
            'month' => self::resolveMonth($eventTime),
            'event_time' => $eventTime,
            'event_time_text' => self::formatMinuteTime($eventTime),
            'pay_way' => (int)$payment->pay_way,
            'pay_way_label' => ActivityPayment::getPayWayText((int)$payment->pay_way),
            'transaction_id' => (string)($payment->transaction_id ?? ''),
            'voucher_url' => '',
            'refunded_amount' => round((float)($payment->refund_amount ?? 0), 2),
            'collection_owner' => 1,
            'collection_owner_label' => '平台收款',
        ];
    }

    /**
     * @notes 活动报名退款流水的对账字段
     * @param ActivityRefund $refund
     * @param array $registrationMap
     * @param array $dynamicMap
     * @param array $activityPaymentMap
     * @return array|null
     */
    private static function buildActivityRefundFlow(
        ActivityRefund $refund,
        array $registrationMap,
        array $dynamicMap,
        array $activityPaymentMap
    ): ?array {
        $refundStatus = (int)$refund->refund_status;
        if (!in_array($refundStatus, [
            ActivityRefund::STATUS_PENDING,
            ActivityRefund::STATUS_APPROVED,
            ActivityRefund::STATUS_PROCESSING,
            ActivityRefund::STATUS_COMPLETED,
        ], true)) {
            return null;
        }

        $isCompleted = $refundStatus === ActivityRefund::STATUS_COMPLETED;
        $amount = $isCompleted
            ? round((float)$refund->actual_refund_amount, 2)
            : round((float)$refund->refund_amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $context = self::buildActivityContext(
            (int)$refund->registration_id,
            (int)$refund->dynamic_id,
            $registrationMap,
            $dynamicMap
        );
        $ticketName = $context['ticket_name'];
        $dynamicTitle = $context['dynamic_title'];

        $payWay = 0;
        $payWayLabel = '原路退回';
        $originPayment = $activityPaymentMap[(int)$refund->payment_id] ?? null;
        if ($originPayment !== null) {
            $payWay = (int)$originPayment->pay_way;
            $payWayLabel = ActivityPayment::getPayWayText($payWay);
        }

        if ($isCompleted) {
            $refundTime = self::getModelTimestamp($refund, 'refund_time');
            $eventTime = $refundTime > 0 ? $refundTime : self::getModelTimestamp($refund, 'update_time');
        } else {
            $eventTime = self::getModelTimestamp($refund, 'create_time');
        }

        return [
            'id' => (int)$refund->id,
            'flow_key' => 'activity_refund:' . (int)$refund->id,
            'biz_category' => 'activity_refund',
            'biz_group' => 'activity',
            'biz_sn' => (string)($refund->refund_sn ?? ''),
            'biz_type_label' => '活动退款',
            'order_id' => 0,
            'order_sn' => '',
            'registration_id' => (int)$refund->registration_id,
            'dynamic_id' => $context['dynamic_id'],
            'remark' => (string)($refund->refund_reason ?? ''),
            'direction' => 'in',
            'amount' => $amount,
            'status' => $isCompleted ? 'completed' : 'processing',
            'status_label' => self::getRefundStatusLabel($refundStatus),
            'status_tone' => $isCompleted ? 'success' : 'warning',
            'title' => $dynamicTitle !== '' ? '活动退款 · ' . $dynamicTitle : '活动退款',
            'subtitle' => $ticketName,
            'month' => self::resolveMonth($eventTime),
            'event_time' => $eventTime,
            'event_time_text' => self::formatMinuteTime($eventTime),
            'pay_way' => $payWay,
            'pay_way_label' => $payWayLabel,
            'transaction_id' => (string)($refund->third_refund_no ?? ''),
            'voucher_url' => '',
            'refunded_amount' => 0.0,
            'collection_owner' => 1,
            'collection_owner_label' => '平台收款',
        ];
    }

    /**
     * @notes 订单支付详情
     * @param int $userId
     * @param int $id
     * @return array|null
     */
    private static function orderPayDetail(int $userId, int $id): ?array
    {
        $payment = Payment::where('id', $id)->find();
        if (!$payment || (int)$payment->user_id !== $userId) {
            return self::notFound();
        }

        $orderId = (int)$payment->order_id;
        $orderMap = self::loadOrders([$orderId]);
        $orderItemMap = self::loadOrderItems([$orderId]);
        $flow = self::buildOrderPayFlow($payment, $orderMap, $orderItemMap);
        if ($flow === null) {
            return self::notFound();
        }

        $order = $orderMap[$orderId] ?? null;
        $items = $orderItemMap[$orderId] ?? [];
        $staffNames = [];
        foreach ($items as $item) {
            $name = trim((string)($item->staff_name ?? ''));
            if ($name !== '') {
                $staffNames[$name] = true;
            }
        }

        $flow['event_full_time_text'] = self::formatTime((int)$flow['event_time']);
        $flow['order_total_amount'] = round((float)($order->total_amount ?? 0), 2);
        $flow['order_paid_amount'] = round((float)($order->paid_amount ?? 0), 2);
        $flow['service_date'] = (string)($order->service_date ?? '');
        $flow['staff_names'] = implode('、', array_keys($staffNames));
        $flow['pay_type_label'] = match ((int)$payment->pay_type) {
            Payment::TYPE_DEPOSIT => '定金',
            Payment::TYPE_BALANCE => '尾款',
            default => '全款',
        };

        return $flow;
    }

    /**
     * @notes 订单退款详情
     * @param int $userId
     * @param int $id
     * @return array|null
     */
    private static function orderRefundDetail(int $userId, int $id): ?array
    {
        $refund = Refund::where('id', $id)->find();
        if (!$refund || (int)$refund->user_id !== $userId) {
            return self::notFound();
        }

        $orderId = (int)$refund->order_id;
        $orderMap = self::loadOrders([$orderId]);
        $orderItemMap = self::loadOrderItems([$orderId]);
        $refundItemMap = self::loadRefundItems([(int)$refund->id]);
        $paymentMap = self::loadPayments((int)$refund->payment_id > 0 ? [(int)$refund->payment_id] : []);
        $flow = self::buildOrderRefundFlow($refund, $orderMap, $orderItemMap, $refundItemMap, $paymentMap);
        if ($flow === null) {
            return self::notFound();
        }

        $refundItems = [];
        foreach ($refundItemMap[(int)$refund->id] ?? [] as $refundItem) {
            $refundTime = self::getModelTimestamp($refundItem, 'refund_time');
            $refundItems[] = [
                'id' => (int)$refundItem->id,
                'pay_way_label' => Payment::getPayWayText((int)$refundItem->pay_way),
                'refund_amount' => round((float)$refundItem->refund_amount, 2),
                'refund_status_label' => (string)$refundItem->refund_status_desc,
                'out_refund_no' => (string)($refundItem->out_refund_no ?? ''),
                'third_refund_no' => (string)($refundItem->third_refund_no ?? ''),
                'refund_voucher' => (string)($refundItem->refund_voucher ?? ''),
                'refund_time_text' => self::formatTime($refundTime),
            ];
        }

        $flow['event_full_time_text'] = self::formatTime((int)$flow['event_time']);
        $flow['apply_amount'] = round((float)$refund->refund_amount, 2);
        $flow['actual_refund_amount'] = round((float)$refund->actual_refund_amount, 2);
        $flow['refund_reason'] = (string)($refund->refund_reason ?? '');
        $flow['audit_remark'] = (string)($refund->audit_remark ?? '');
        $flow['refund_items'] = $refundItems;

        return $flow;
    }

    /**
     * @notes 活动报名支付详情
     * @param int $userId
     * @param int $id
     * @return array|null
     */
    private static function activityPayDetail(int $userId, int $id): ?array
    {
        $payment = ActivityPayment::where('id', $id)->find();
        if (!$payment || (int)$payment->user_id !== $userId) {
            return self::notFound();
        }

        $registrationId = (int)$payment->registration_id;
        $dynamicId = (int)$payment->dynamic_id;
        $registrationMap = self::loadRegistrations([$registrationId]);
        $dynamicMap = self::loadDynamics([$dynamicId]);
        $flow = self::buildActivityPayFlow($payment, $registrationMap, $dynamicMap);
        if ($flow === null) {
            return self::notFound();
        }

        $context = self::resolveActivityContext($registrationId, $dynamicId, $registrationMap, $dynamicMap);
        $flow['event_full_time_text'] = self::formatTime((int)$flow['event_time']);
        $flow['dynamic_title'] = $context['dynamic_title'];
        $flow['ticket_name'] = $context['ticket_name'];
        $flow['activity_start_time_text'] = $context['activity_start_time'] > 0
            ? self::formatTime($context['activity_start_time'])
            : '';

        return $flow;
    }

    /**
     * @notes 活动报名退款详情
     * @param int $userId
     * @param int $id
     * @return array|null
     */
    private static function activityRefundDetail(int $userId, int $id): ?array
    {
        $refund = ActivityRefund::where('id', $id)->find();
        if (!$refund || (int)$refund->user_id !== $userId) {
            return self::notFound();
        }

        $registrationId = (int)$refund->registration_id;
        $dynamicId = (int)$refund->dynamic_id;
        $paymentId = (int)$refund->payment_id;
        $registrationMap = self::loadRegistrations([$registrationId]);
        $dynamicMap = self::loadDynamics([$dynamicId]);
        $activityPaymentMap = self::loadActivityPayments($paymentId > 0 ? [$paymentId] : []);
        $flow = self::buildActivityRefundFlow($refund, $registrationMap, $dynamicMap, $activityPaymentMap);
        if ($flow === null) {
            return self::notFound();
        }

        $context = self::resolveActivityContext($registrationId, $dynamicId, $registrationMap, $dynamicMap);
        $flow['event_full_time_text'] = self::formatTime((int)$flow['event_time']);
        $flow['dynamic_title'] = $context['dynamic_title'];
        $flow['ticket_name'] = $context['ticket_name'];
        $flow['activity_start_time_text'] = $context['activity_start_time'] > 0
            ? self::formatTime($context['activity_start_time'])
            : '';
        $flow['apply_amount'] = round((float)$refund->refund_amount, 2);
        $flow['actual_refund_amount'] = round((float)$refund->actual_refund_amount, 2);
        $flow['refund_reason'] = (string)($refund->refund_reason ?? '');
        $flow['audit_remark'] = (string)($refund->audit_remark ?? '');
        $flow['third_refund_no'] = (string)($refund->third_refund_no ?? '');

        return $flow;
    }

    /**
     * @notes 批量加载订单
     * @param array $orderIds
     * @return array
     */
    private static function loadOrders(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        $rows = Order::withTrashed()
            ->whereIn('id', $orderIds)
            ->field('id,order_sn,order_status,service_date,total_amount,pay_amount,paid_amount,pay_voucher')
            ->select();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row->id] = $row;
        }

        return $map;
    }

    /**
     * @notes 批量加载订单项
     * @param array $orderIds
     * @return array
     */
    private static function loadOrderItems(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        $rows = OrderItem::whereIn('order_id', $orderIds)
            ->field('id,order_id,staff_name,package_name,item_type')
            ->order('item_type asc, id asc')
            ->select();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row->order_id][] = $row;
        }

        return $map;
    }

    /**
     * @notes 批量加载退款子项
     * @param array $refundIds
     * @return array
     */
    private static function loadRefundItems(array $refundIds): array
    {
        if ($refundIds === [] || !RefundItem::isTableReady()) {
            return [];
        }

        $rows = RefundItem::whereIn('refund_id', $refundIds)->select();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row->refund_id][] = $row;
        }

        return $map;
    }

    /**
     * @notes 批量加载订单支付（退款单补齐支付方式）
     * @param array $paymentIds
     * @return array
     */
    private static function loadPayments(array $paymentIds): array
    {
        if ($paymentIds === []) {
            return [];
        }

        $rows = Payment::whereIn('id', $paymentIds)->field('id,pay_way')->select();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row->id] = $row;
        }

        return $map;
    }

    /**
     * @notes 批量加载活动报名
     * @param array $registrationIds
     * @return array
     */
    private static function loadRegistrations(array $registrationIds): array
    {
        if ($registrationIds === []) {
            return [];
        }

        $rows = ActivityRegistration::whereIn('id', $registrationIds)
            ->field('id,dynamic_id,ticket_name')
            ->select();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row->id] = $row;
        }

        return $map;
    }

    /**
     * @notes 批量加载活动动态
     * @param array $dynamicIds
     * @return array
     */
    private static function loadDynamics(array $dynamicIds): array
    {
        if ($dynamicIds === []) {
            return [];
        }

        $rows = Dynamic::withTrashed()
            ->whereIn('id', $dynamicIds)
            ->field('id,title,activity_start_time')
            ->select();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row->id] = $row;
        }

        return $map;
    }

    /**
     * @notes 批量加载活动支付（退款单补齐支付方式）
     * @param array $paymentIds
     * @return array
     */
    private static function loadActivityPayments(array $paymentIds): array
    {
        if ($paymentIds === []) {
            return [];
        }

        $rows = ActivityPayment::whereIn('id', $paymentIds)->field('id,pay_way')->select();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row->id] = $row;
        }

        return $map;
    }

    /**
     * @notes 订单标题上下文：主服务摘要 + 副标题
     * @param Order|null $order
     * @param array $items
     * @param string $fallbackOrderSn
     * @return array [serviceSummary, subtitle]
     */
    private static function buildOrderContext(?Order $order, array $items, string $fallbackOrderSn = ''): array
    {
        $orderSn = (string)($order->order_sn ?? '');
        if ($orderSn === '') {
            $orderSn = $fallbackOrderSn;
        }
        $serviceSummary = '';
        foreach ($items as $item) {
            if ((int)$item->item_type !== OrderItem::TYPE_SERVICE) {
                continue;
            }
            $name = trim((string)($item->package_name ?? ''));
            if ($name === '') {
                $name = trim((string)($item->staff_name ?? ''));
            }
            if ($name !== '') {
                $serviceSummary = $name;
                break;
            }
        }
        if ($serviceSummary === '' && $orderSn !== '') {
            $serviceSummary = substr($orderSn, -6);
        }

        $serviceDate = (string)($order->service_date ?? '');
        $subtitle = $serviceDate !== '' ? '婚期 ' . $serviceDate : $orderSn;

        return [$serviceSummary, $subtitle];
    }

    /**
     * @notes 活动标题上下文：票种 + 活动标题
     * @param int $registrationId
     * @param int $fallbackDynamicId
     * @param array $registrationMap
     * @param array $dynamicMap
     * @return array
     */
    private static function buildActivityContext(
        int $registrationId,
        int $fallbackDynamicId,
        array $registrationMap,
        array $dynamicMap
    ): array {
        return self::resolveActivityContext($registrationId, $fallbackDynamicId, $registrationMap, $dynamicMap);
    }

    /**
     * @notes 解析活动票种与活动信息
     * @param int $registrationId
     * @param int $fallbackDynamicId
     * @param array $registrationMap
     * @param array $dynamicMap
     * @return array
     */
    private static function resolveActivityContext(
        int $registrationId,
        int $fallbackDynamicId,
        array $registrationMap,
        array $dynamicMap
    ): array {
        $registration = $registrationMap[$registrationId] ?? null;
        $dynamicId = (int)($registration->dynamic_id ?? 0);
        if ($dynamicId <= 0) {
            $dynamicId = $fallbackDynamicId;
        }
        $dynamic = $dynamicMap[$dynamicId] ?? null;

        return [
            'ticket_name' => (string)($registration->ticket_name ?? ''),
            'dynamic_title' => (string)($dynamic->title ?? ''),
            'dynamic_id' => $dynamicId,
            'activity_start_time' => $dynamic !== null
                ? self::getModelTimestamp($dynamic, 'activity_start_time')
                : 0,
        ];
    }

    /**
     * @notes 解析订单线下支付凭证地址
     * @param Order|null $order
     * @return string
     */
    private static function resolveOrderVoucher(?Order $order): string
    {
        if ($order === null) {
            return '';
        }

        $voucher = trim((string)($order->pay_voucher ?? ''));
        if ($voucher === '') {
            return '';
        }

        return str_starts_with($voucher, 'http') ? $voucher : FileService::getFileUrl($voucher);
    }

    /**
     * @notes 支付类型文案
     * @param int $payType
     * @return string
     */
    private static function getOrderPayTypeLabel(int $payType): string
    {
        return match ($payType) {
            Payment::TYPE_DEPOSIT => '订单定金',
            Payment::TYPE_BALANCE => '订单尾款',
            default => '订单全款',
        };
    }

    /**
     * @notes 退款状态文案（订单/活动共用口径）
     * @param int $status
     * @return string
     */
    private static function getRefundStatusLabel(int $status): string
    {
        return match ($status) {
            Refund::STATUS_COMPLETED => '已退款',
            Refund::STATUS_PENDING => '待审核',
            default => '退款中',
        };
    }

    /**
     * @notes 由事件时间戳解析月份
     * @param int $eventTime
     * @return string
     */
    private static function resolveMonth(int $eventTime): string
    {
        return $eventTime > 0 ? date('Y-m', $eventTime) : '';
    }

    /**
     * @notes 格式化时间
     * @param int $timestamp
     * @return string
     */
    private static function formatTime(int $timestamp): string
    {
        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    /**
     * @notes 格式化到分钟（对账列表展示用）
     * @param int $timestamp
     * @return string
     */
    private static function formatMinuteTime(int $timestamp): string
    {
        return $timestamp > 0 ? date('Y-m-d H:i', $timestamp) : '';
    }

    /**
     * @notes 统一解析模型时间字段（兼容 datetime 字符串与秒级时间戳）
     * @param mixed $value
     * @return int
     */
    public static function parseTimestamp($value): int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : 0;
        }
        if (is_float($value)) {
            $timestamp = (int)$value;
            return $timestamp > 0 ? $timestamp : 0;
        }
        if (!is_string($value)) {
            return 0;
        }

        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        if (preg_match('/^\d+$/', $value)) {
            $timestamp = (int)$value;
            return $timestamp > 0 ? $timestamp : 0;
        }

        $timestamp = strtotime($value);
        return $timestamp === false || $timestamp <= 0 ? 0 : $timestamp;
    }

    /**
     * @notes 读取模型原始时间字段，避免 auto_timestamp 的 datetime 字符串被 (int) 截断
     * @param object $model
     * @param string $field
     * @return int
     */
    public static function getModelTimestamp(object $model, string $field): int
    {
        $value = null;
        if (method_exists($model, 'getData')) {
            $value = $model->getData($field);
        }
        if ($value === null) {
            $value = $model->{$field} ?? null;
        }

        return self::parseTimestamp($value);
    }

    /**
     * @notes 规范化月份
     * @param mixed $month
     * @return string
     */
    private static function normalizeMonth($month): string
    {
        if (!is_scalar($month)) {
            return '';
        }
        $month = trim((string)$month);
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) ? $month : '';
    }

    /**
     * @notes 规范化流水方向
     * @param mixed $direction
     * @return string
     */
    private static function normalizeDirection($direction): string
    {
        if (!is_scalar($direction)) {
            return 'all';
        }
        $direction = trim((string)$direction);
        return in_array($direction, ['out', 'in'], true) ? $direction : 'all';
    }

    /**
     * @notes 规范化业务分组
     * @param mixed $bizGroup
     * @return string
     */
    private static function normalizeBizGroup($bizGroup): string
    {
        if (!is_scalar($bizGroup)) {
            return 'all';
        }
        $bizGroup = trim((string)$bizGroup);
        return in_array($bizGroup, ['order', 'activity'], true) ? $bizGroup : 'all';
    }

    /**
     * @notes 记录不存在的流水错误
     * @return null
     */
    private static function notFound(): ?array
    {
        self::$error = '流水记录不存在';
        return null;
    }
}
