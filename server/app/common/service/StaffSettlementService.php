<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员结算服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\user\UserTerminalEnum;
use app\common\model\financial\CostRecord;
use app\common\model\financial\StaffSettlement;
use app\common\model\financial\StaffSettlementConfig;
use app\common\model\financial\StaffSettlementTransfer;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\Payment;
use app\common\model\order\Refund;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use app\common\model\user\UserAuth;
use app\common\service\wechat\WeChatConfigService;
use think\facade\Db;
use think\facade\Log;

/**
 * 服务人员结算服务
 * Class StaffSettlementService
 * @package app\common\service
 */
class StaffSettlementService
{
    public const REFUND_BLOCKED_MESSAGE = '订单存在转账中/待确认/已结算结算记录，请先处理服务人员结算';

    /**
     * @notes 是否启用微信商家转账结算
     */
    public static function isMerchantTransferModeEnabled(): bool
    {
        return WeChatMerchantTransferService::isEnabled();
    }

    /**
     * @notes 扫描已完成订单并生成结算记录
     */
    public function generateFromCompletedOrders(string $startDate = '', string $endDate = '', int $limit = 200): int
    {
        $startDate = $startDate ?: date('Y-m-d', strtotime('-90 days'));
        $endDate = $endDate ?: date('Y-m-d');
        $limit = max(1, min($limit, 500));

        $orderTable = (new Order())->getTable();
        $settlementTable = (new StaffSettlement())->getTable();

        $orderIds = OrderItem::alias('oi')
            ->leftJoin($orderTable . ' o', 'oi.order_id = o.id')
            ->leftJoin($settlementTable . ' ss', 'ss.order_item_id = oi.id')
            ->where('o.order_status', Order::STATUS_COMPLETED)
            ->where('o.pay_status', Order::PAY_STATUS_PAID)
            ->where('o.balance_paid', 1)
            ->whereNotIn('o.order_status', [Order::STATUS_REFUNDING, Order::STATUS_REFUNDED])
            ->where('oi.staff_id', '>', 0)
            ->whereIn('oi.item_type', [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF])
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereBetween('o.complete_time', [strtotime($startDate . ' 00:00:00'), strtotime($endDate . ' 23:59:59')])
            ->whereNull('ss.id')
            ->limit($limit)
            ->distinct(true)
            ->column('oi.order_id');

        $count = 0;
        foreach ($orderIds as $orderId) {
            $count += $this->generateFromCompletedOrder((int)$orderId);
        }

        return $count;
    }

    /**
     * @notes 根据已完成订单生成结算记录
     */
    public function generateFromCompletedOrder(int $orderId): int
    {
        if ($orderId <= 0) {
            return 0;
        }

        return Db::transaction(function () use ($orderId) {
            $order = Order::where('id', $orderId)->lock(true)->find();
            if (!$order || !$this->canGenerateForOrder($order)) {
                return 0;
            }
            $items = OrderItem::where('order_id', $orderId)
                ->where('staff_id', '>', 0)
                ->whereIn('item_type', [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF])
                ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                ->order('id', 'asc')
                ->select();

            if ($items->isEmpty()) {
                return 0;
            }

            $totalStaffSubtotal = 0.0;
            foreach ($items as $item) {
                $totalStaffSubtotal += max(round((float)$item->subtotal, 2), 0);
            }

            if ($totalStaffSubtotal <= 0) {
                return 0;
            }

            $orderCost = round((float)CostRecord::getOrderTotalCost($orderId), 2);
            $platformPaidShareMap = self::allocatePlatformPaidShares(
                self::getOrderPlatformPaidNetAmount($orderId),
                $items,
                $totalStaffSubtotal
            );
            $created = 0;

            foreach ($items as $item) {
                $exists = StaffSettlement::where('order_item_id', (int)$item->id)->lock(true)->find();
                if ($exists) {
                    continue;
                }

                $itemAmount = round(max((float)$item->subtotal, 0), 2);
                if ($itemAmount <= 0) {
                    continue;
                }

                $serviceDate = $item->service_date ?: date('Y-m-d', (int)($order->complete_time ?: time()));
                $calcResult = StaffSettlementConfig::calculateSettlement($itemAmount, (int)$item->staff_id, 0, $serviceDate);
                $allocatedCost = $orderCost > 0
                    ? round($orderCost * $itemAmount / $totalStaffSubtotal, 2)
                    : 0.0;
                $platformAmount = round((float)($calcResult['platform_amount'] ?? $calcResult['company_amount'] ?? 0), 2);
                $companyAmount = round((float)($calcResult['company_amount'] ?? $platformAmount), 2);
                $settlementAmount = round((float)$calcResult['settlement_amount'], 2);
                $leaderAmount = round((float)($calcResult['leader_amount'] ?? 0), 2);
                $platformPaidShareAmount = round((float)($platformPaidShareMap[(int)$item->id] ?? 0), 2);

                $actualAmount = 0.0;
                $staffDuePlatformAmount = 0.0;
                if ($platformPaidShareAmount > $platformAmount) {
                    $payablePool = round(max($platformPaidShareAmount - $platformAmount - $allocatedCost, 0), 2);
                    $actualAmount = round(min($settlementAmount, $payablePool), 2);
                } else {
                    $staffDuePlatformAmount = round(max($platformAmount - $platformPaidShareAmount, 0), 2);
                }

                $status = $actualAmount > 0
                    ? StaffSettlement::STATUS_PENDING
                    : StaffSettlement::STATUS_NO_PAYOUT;
                $settleWay = $actualAmount > 0
                    ? StaffSettlement::SETTLE_WAY_WECHAT
                    : StaffSettlement::SETTLE_WAY_NO_PAYOUT;
                $staffDueCollectStatus = $staffDuePlatformAmount > 0
                    ? StaffSettlement::DUE_COLLECT_STATUS_PENDING
                    : StaffSettlement::DUE_COLLECT_STATUS_NONE;
                $ruleText = (string)($calcResult['settlement_mode_text'] ?? '比例抽成');
                if ($staffDuePlatformAmount > 0) {
                    $remark = '平台实收不足，需服务人员补交平台抽成，规则：' . $ruleText;
                } elseif ($actualAmount <= 0) {
                    $remark = '平台实收抵扣平台抽成后无可打款金额，规则：' . $ruleText;
                } else {
                    $remark = '订单完成自动生成微信商家转账结算，规则：' . $ruleText;
                }

                try {
                    $settlement = StaffSettlement::createSettlement([
                        'staff_id' => (int)$item->staff_id,
                        'team_id' => $calcResult['team_id'] ?? 0,
                        'leader_staff_id' => $calcResult['leader_staff_id'] ?? 0,
                        'config_id' => $calcResult['config_id'] ?? 0,
                        'scope_type' => $calcResult['scope_type'] ?? StaffSettlementConfig::SCOPE_DEFAULT,
                        'settlement_mode' => $calcResult['settlement_mode'] ?? StaffSettlementConfig::MODE_RATE,
                        'rule_source' => $calcResult['rule_source'] ?? '',
                        'order_id' => $orderId,
                        'order_item_id' => (int)$item->id,
                        'service_date' => $serviceDate,
                        'order_amount' => $itemAmount,
                        'settlement_rate' => $calcResult['settlement_rate'],
                        'company_rate' => $calcResult['company_rate'] ?? 0,
                        'company_amount' => $companyAmount,
                        'leader_rate' => $calcResult['leader_rate'] ?? 0,
                        'leader_amount' => $leaderAmount,
                        'monthly_fee_amount' => $calcResult['monthly_fee_amount'] ?? 0,
                        'monthly_fee_deduct_amount' => $calcResult['monthly_fee_deduct_amount'] ?? 0,
                        'settlement_amount' => $settlementAmount,
                        'platform_amount' => $platformAmount,
                        'platform_paid_share_amount' => $platformPaidShareAmount,
                        'staff_due_platform_amount' => $staffDuePlatformAmount,
                        'staff_due_collected_amount' => 0,
                        'staff_due_collect_status' => $staffDueCollectStatus,
                        'cost_amount' => $allocatedCost,
                        'actual_amount' => $actualAmount,
                        'settlement_type' => StaffSettlement::TYPE_AUTO,
                        'status' => $status,
                        'settle_way' => $settleWay,
                        'remark' => $remark,
                    ]);
                } catch (\Throwable $e) {
                    if (self::isDuplicateKeyException($e)) {
                        continue;
                    }
                    throw $e;
                }
                $created++;
            }

            return $created;
        });
    }

    /**
     * @notes 处理待发起和待同步转账
     */
    public function processPendingTransfers(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        $result = [
            'sent_count' => 0,
            'fail_count' => 0,
            'skip_count' => 0,
            'success_count' => 0,
            'wait_confirm_count' => 0,
        ];

        $syncResult = $this->syncTransferStatus(0, $limit);
        $result['success_count'] = (int)($syncResult['success_count'] ?? 0);
        $result['wait_confirm_count'] = (int)($syncResult['wait_confirm_count'] ?? 0);

        $config = WeChatMerchantTransferService::getConfig();
        if (!self::isMerchantTransferModeEnabled() || (int)$config['auto_send'] !== 1) {
            return $result;
        }

        $settlements = StaffSettlement::where('settle_way', StaffSettlement::SETTLE_WAY_WECHAT)
            ->where('status', StaffSettlement::STATUS_PENDING)
            ->order('id', 'asc')
            ->limit($limit)
            ->select();

        foreach ($settlements as $settlement) {
            $sendResult = $this->sendSettlementTransfer($settlement, false);
            if ($sendResult['success'] ?? false) {
                $result['sent_count']++;
            } elseif (($sendResult['skipped'] ?? false) === true) {
                $result['skip_count']++;
            } else {
                $result['fail_count']++;
            }
        }

        return $result;
    }

    /**
     * @notes 发起结算转账
     */
    public function sendSettlementTransfer(StaffSettlement $settlement, bool $forceRetry = false): array
    {
        if ($settlement->isNoPayout()) {
            return ['success' => false, 'skipped' => true, 'message' => '平台实收不足或无可打款金额，无需向服务人员打款'];
        }

        if (!self::isMerchantTransferModeEnabled()) {
            return ['success' => false, 'skipped' => true, 'message' => '微信商家转账未启用，请走人工处理'];
        }

        if (!in_array((int)$settlement->status, [
            StaffSettlement::STATUS_PENDING,
            StaffSettlement::STATUS_FAILED,
            StaffSettlement::STATUS_TRANSFER_PROCESSING,
        ], true)) {
            return ['success' => false, 'skipped' => true, 'message' => '结算状态不可发起转账'];
        }

        try {
            $transfer = $this->ensureTransferRow($settlement);
            if (!$transfer) {
                return ['success' => false, 'message' => '没有可发起的转账明细'];
            }

            if ((int)$transfer->status === StaffSettlementTransfer::STATUS_SUCCESS) {
                $this->refreshSettlementFromTransfers($settlement);
                return ['success' => true, 'message' => '转账已到账'];
            }

            if (!$transfer->canRetry() && !$forceRetry) {
                $settlement->markTransferProcessing();
                return ['success' => true, 'message' => '转账已受理，沿用原商户单号'];
            }

            if (!$transfer->canRetry()) {
                return ['success' => false, 'message' => '当前转账状态不可重试，请先同步状态'];
            }

            $client = new WeChatMerchantTransferService();
            $sendResult = $client->createTransferBill($transfer);
            if (!($sendResult['success'] ?? false)) {
                $message = (string)($sendResult['message'] ?? '微信商家转账失败');
                $transfer->markFailed($message, $sendResult['response'] ?? []);
                $settlement->markFailed($message);
                return ['success' => false, 'message' => $message];
            }

            $transfer->applyWechatResult($sendResult['response'] ?? []);
            $this->refreshSettlementFromTransfers($settlement);

            return [
                'success' => true,
                'message' => '微信商家转账已受理',
                'status' => (int)$transfer->status,
            ];
        } catch (\Throwable $e) {
            $activeTransfer = StaffSettlementTransfer::where('settlement_id', (int)$settlement->id)
                ->whereIn('status', [
                    StaffSettlementTransfer::STATUS_PROCESSING,
                    StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM,
                ])
                ->find();
            if ($activeTransfer) {
                $settlement->markTransferProcessing();
            } else {
                $settlement->markFailed($e->getMessage());
            }
            Log::write('服务人员结算转账发起失败：' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @notes 退款前处理订单关联结算。待结算自动取消，已进入资金链路则阻断退款。
     */
    public static function guardRefundForOrder(int $orderId): array
    {
        if ($orderId <= 0) {
            return [true, ''];
        }

        $settlements = StaffSettlement::where('order_id', $orderId)
            ->whereIn('status', [
                StaffSettlement::STATUS_PENDING,
                StaffSettlement::STATUS_SETTLED,
                StaffSettlement::STATUS_FAILED,
                StaffSettlement::STATUS_TRANSFER_PROCESSING,
                StaffSettlement::STATUS_NO_PAYOUT,
            ])
            ->lock(true)
            ->select();
        if ($settlements->isEmpty()) {
            return [true, ''];
        }

        foreach ($settlements as $settlement) {
            if (in_array((int)$settlement->status, [
                StaffSettlement::STATUS_SETTLED,
                StaffSettlement::STATUS_TRANSFER_PROCESSING,
            ], true)) {
                return [false, self::REFUND_BLOCKED_MESSAGE];
            }
        }

        foreach ($settlements as $settlement) {
            $settlement->cancelForRefund();
        }

        return [true, '已取消待结算记录'];
    }

    /**
     * @notes 同步转账状态
     */
    public function syncTransferStatus(int $settlementId = 0, int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $result = [
            'query_count' => 0,
            'success_count' => 0,
            'processing_count' => 0,
            'wait_confirm_count' => 0,
            'fail_count' => 0,
        ];

        $query = StaffSettlementTransfer::whereIn('status', [
            StaffSettlementTransfer::STATUS_PROCESSING,
            StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM,
        ]);
        if ($settlementId > 0) {
            $query->where('settlement_id', $settlementId);
        }

        $transfers = $query->order('last_query_time', 'asc')
            ->order('id', 'asc')
            ->limit($limit)
            ->select();

        if ($transfers->isEmpty()) {
            return $result;
        }

        $client = new WeChatMerchantTransferService();
        $touchedSettlementIds = [];

        foreach ($transfers as $transfer) {
            try {
                $queryResult = $client->queryTransferBill($transfer);
                $result['query_count']++;

                if (!($queryResult['success'] ?? false)) {
                    $transfer->saveQueryResult($queryResult['response'] ?? []);
                    continue;
                }

                $response = $queryResult['response'] ?? [];
                $transfer->applyWechatResult($response, true);
                $status = (int)$transfer->status;
                if ($status === StaffSettlementTransfer::STATUS_SUCCESS) {
                    $result['success_count']++;
                } elseif ($status === StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM) {
                    $result['wait_confirm_count']++;
                } elseif ($status === StaffSettlementTransfer::STATUS_FAILED || $status === StaffSettlementTransfer::STATUS_CLOSED) {
                    $result['fail_count']++;
                } else {
                    $result['processing_count']++;
                }

                $touchedSettlementIds[(int)$transfer->settlement_id] = true;
            } catch (\Throwable $e) {
                Log::write('服务人员转账状态同步失败：' . $e->getMessage());
                $result['fail_count']++;
            }
        }

        foreach (array_keys($touchedSettlementIds) as $id) {
            $settlement = StaffSettlement::find((int)$id);
            if ($settlement) {
                $this->refreshSettlementFromTransfers($settlement);
            }
        }

        return $result;
    }

    public static function getTransferStatusCounts(int $settlementId): array
    {
        $result = [
            'success_count' => 0,
            'processing_count' => 0,
            'wait_confirm_count' => 0,
            'fail_count' => 0,
        ];
        if ($settlementId <= 0) {
            return $result;
        }

        $transfers = StaffSettlementTransfer::where('settlement_id', $settlementId)->select();
        foreach ($transfers as $transfer) {
            $status = (int)$transfer->status;
            if ($status === StaffSettlementTransfer::STATUS_SUCCESS) {
                $result['success_count']++;
            } elseif ($status === StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM) {
                $result['wait_confirm_count']++;
            } elseif (in_array($status, [
                StaffSettlementTransfer::STATUS_FAILED,
                StaffSettlementTransfer::STATUS_CLOSED,
            ], true)) {
                $result['fail_count']++;
            } elseif (in_array($status, [
                StaffSettlementTransfer::STATUS_PENDING,
                StaffSettlementTransfer::STATUS_PROCESSING,
            ], true)) {
                $result['processing_count']++;
            }
        }

        return $result;
    }

    /**
     * @notes 重试转账
     */
    public function retryTransfer(int $settlementId): array
    {
        $settlement = StaffSettlement::find($settlementId);
        if (!$settlement) {
            return ['success' => false, 'message' => '结算记录不存在'];
        }
        return $this->sendSettlementTransfer($settlement, true);
    }

    /**
     * @notes 获取用户确认收款参数
     */
    public function getConfirmPayload(int $settlementId, int $staffId): array|bool
    {
        $settlement = StaffSettlement::where('id', $settlementId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$settlement) {
            return false;
        }

        $transfer = StaffSettlementTransfer::where('settlement_id', $settlementId)
            ->whereIn('status', [
                StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM,
                StaffSettlementTransfer::STATUS_PROCESSING,
            ])
            ->order('id', 'desc')
            ->find();
        if (!$transfer || trim((string)$transfer->package_info) === '') {
            return [
                'settlement_id' => (int)$settlement->id,
                'settlement_sn' => (string)$settlement->settlement_sn,
                'amount' => round((float)$settlement->actual_amount, 2),
                'package' => '',
                'package_info' => '',
                'status' => (int)$settlement->status,
                'status_text' => StaffSettlement::getStatusDesc((int)$settlement->status),
            ];
        }

        return array_merge([
            'settlement_id' => (int)$settlement->id,
            'settlement_sn' => (string)$settlement->settlement_sn,
            'amount' => round((float)$settlement->actual_amount, 2),
            'status' => (int)$settlement->status,
            'status_text' => StaffSettlement::getStatusDesc((int)$settlement->status),
        ], (new WeChatMerchantTransferService())->buildConfirmPayload($transfer));
    }

    /**
     * @notes 处理微信商家转账回调
     */
    public function handleTransferNotify(array $payload): bool
    {
        $outBillNo = (string)($payload['out_bill_no'] ?? '');
        if ($outBillNo === '') {
            return false;
        }

        $transfer = StaffSettlementTransfer::where('out_bill_no', $outBillNo)->find();
        if (!$transfer) {
            return false;
        }

        $transfer->notify_data = StaffSettlementTransfer::encodePayload($payload);
        $transfer->applyWechatResult($payload, true);

        $settlement = StaffSettlement::find((int)$transfer->settlement_id);
        if ($settlement) {
            $this->refreshSettlementFromTransfers($settlement);
        }

        return true;
    }

    /**
     * @notes 判断订单是否可生成结算
     */
    protected function canGenerateForOrder(Order $order): bool
    {
        if ((int)$order->order_status !== Order::STATUS_COMPLETED) {
            return false;
        }
        if ((int)$order->pay_status !== Order::PAY_STATUS_PAID || (int)$order->balance_paid !== 1) {
            return false;
        }
        if (in_array((int)$order->order_status, [Order::STATUS_REFUNDING, Order::STATUS_REFUNDED], true)) {
            return false;
        }
        if (
            Refund::where('order_id', (int)$order->id)
                ->whereIn('refund_status', Refund::getPendingStatuses())
                ->find()
        ) {
            return false;
        }
        return true;
    }

    /**
     * @notes 判断订单是否包含线下成功付款
     */
    public static function isOfflinePaymentOrder(Order $order): bool
    {
        $paidPayments = Payment::where('order_id', (int)$order->id)
            ->where('pay_status', Payment::STATUS_PAID)
            ->select();
        if (!$paidPayments->isEmpty()) {
            foreach ($paidPayments as $payment) {
                if ((int)$payment->pay_way === Payment::WAY_OFFLINE) {
                    return true;
                }
            }
            return false;
        }

        return (int)($order->payment_channel ?? 0) === Order::PAYMENT_CHANNEL_OFFLINE
            || (int)($order->pay_type ?? 0) === Order::PAY_WAY_OFFLINE
            || trim((string)($order->pay_voucher ?? '')) !== '';
    }

    /**
     * @notes 获取订单平台实收净额：已支付非线下流水减已退款金额
     */
    public static function getOrderPlatformPaidNetAmount(int $orderId): float
    {
        if ($orderId <= 0) {
            return 0.0;
        }

        $payments = Payment::where('order_id', $orderId)
            ->where('pay_way', '<>', Payment::WAY_OFFLINE)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->select();

        $amount = 0.0;
        foreach ($payments as $payment) {
            $amount += max(round((float)$payment->pay_amount - (float)($payment->refund_amount ?? 0), 2), 0);
        }

        return round($amount, 2);
    }

    /**
     * @notes 按订单项金额比例分摊平台实收净额
     */
    protected static function allocatePlatformPaidShares(float $platformPaidNetAmount, iterable $items, float $totalStaffSubtotal): array
    {
        $shares = [];
        $validItems = [];
        foreach ($items as $item) {
            $itemId = (int)$item->id;
            $amount = round(max((float)$item->subtotal, 0), 2);
            $shares[$itemId] = 0.0;
            if ($amount > 0) {
                $validItems[] = ['id' => $itemId, 'amount' => $amount];
            }
        }

        $platformPaidNetAmount = round(max($platformPaidNetAmount, 0), 2);
        if ($platformPaidNetAmount <= 0 || $totalStaffSubtotal <= 0 || empty($validItems)) {
            return $shares;
        }

        $allocated = 0.0;
        $lastIndex = count($validItems) - 1;
        foreach ($validItems as $index => $item) {
            if ($index === $lastIndex) {
                $share = round(max($platformPaidNetAmount - $allocated, 0), 2);
            } else {
                $share = round($platformPaidNetAmount * $item['amount'] / $totalStaffSubtotal, 2);
                $allocated = round($allocated + $share, 2);
            }
            $shares[(int)$item['id']] = $share;
        }

        return $shares;
    }

    protected static function isDuplicateKeyException(\Throwable $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, '1062') || stripos($message, 'Duplicate') !== false;
    }

    /**
     * @notes 确保转账明细存在
     */
    protected function ensureTransferRow(StaffSettlement $settlement): ?StaffSettlementTransfer
    {
        $existing = StaffSettlementTransfer::where('settlement_id', (int)$settlement->id)
            ->order('id', 'asc')
            ->find();
        if ($existing) {
            return $existing;
        }

        $staff = Staff::find((int)$settlement->staff_id);
        if (!$staff || (int)$staff->user_id <= 0) {
            $settlement->markFailed('服务人员未关联用户，无法获取小程序openid');
            return null;
        }

        $openid = UserAuth::where([
            'user_id' => (int)$staff->user_id,
            'terminal' => UserTerminalEnum::WECHAT_MMP,
        ])->value('openid');
        $openid = trim((string)$openid);
        if ($openid === '') {
            $settlement->markFailed('服务人员未绑定微信小程序openid，无法发起微信商家转账');
            return null;
        }

        $transferConfig = WeChatMerchantTransferService::getConfig();
        $wechatConfig = WeChatConfigService::getPayConfigByTerminal(UserTerminalEnum::WECHAT_MMP);
        $mnpConfig = WeChatConfigService::getMnpConfig();
        $amountFen = MoneyService::yuanToFen($settlement->actual_amount);
        if ($amountFen <= 0) {
            $settlement->markFailed('结算金额必须大于0');
            return null;
        }

        $sceneId = trim((string)$transferConfig['transfer_scene_id']);
        if ($sceneId === '') {
            $settlement->markFailed('微信商家转账场景ID未配置');
            return null;
        }

        $userName = $this->resolveReceiverName($staff);
        $thresholdFen = MoneyService::yuanToFen((float)$transferConfig['amount_name_threshold']);
        if ($amountFen >= $thresholdFen && $userName === '') {
            $settlement->markFailed('转账金额达到实名校验阈值，必须维护服务人员实名姓名');
            return null;
        }

        return StaffSettlementTransfer::createTransfer($settlement, [
            'out_bill_no' => $this->buildOutBillNo((int)$settlement->id),
            'mch_id' => (string)($wechatConfig['mch_id'] ?? ''),
            'appid' => (string)($mnpConfig['app_id'] ?? ''),
            'openid' => $openid,
            'user_name' => $userName,
            'amount' => round($amountFen / 100, 2),
            'amount_fen' => $amountFen,
            'transfer_scene_id' => $sceneId,
            'transfer_remark' => $transferConfig['transfer_remark'],
            'user_recv_perception' => $transferConfig['user_recv_perception'],
        ]);
    }

    /**
     * @notes 获取收款实名姓名
     */
    protected function resolveReceiverName(Staff $staff): string
    {
        if ((int)$staff->user_id > 0) {
            $realName = trim((string)User::where('id', (int)$staff->user_id)->value('real_name'));
            if ($realName !== '') {
                return mb_substr($realName, 0, 64);
            }
        }

        $staffName = trim((string)$staff->name);
        if ($staffName !== '') {
            return mb_substr($staffName, 0, 64);
        }

        return '';
    }

    /**
     * @notes 构造微信商家转账商户单号
     */
    protected function buildOutBillNo(int $settlementId): string
    {
        $settlementPart = str_pad((string)($settlementId % 10000000000), 10, '0', STR_PAD_LEFT);
        return 'ST' . date('YmdHis') . $settlementPart . mt_rand(10, 99);
    }

    /**
     * @notes 根据转账状态刷新结算状态
     */
    protected function refreshSettlementFromTransfers(StaffSettlement $settlement): void
    {
        $transfers = StaffSettlementTransfer::where('settlement_id', (int)$settlement->id)
            ->order('id', 'asc')
            ->select();
        if ($transfers->isEmpty()) {
            return;
        }

        $hasSuccess = false;
        $hasActive = false;
        $hasFailed = false;
        $transactionId = '';
        $failReason = '';

        foreach ($transfers as $transfer) {
            $status = (int)$transfer->status;
            if ($status === StaffSettlementTransfer::STATUS_SUCCESS) {
                $hasSuccess = true;
                $transactionId = (string)$transfer->transfer_bill_no ?: (string)$transfer->out_bill_no;
            }
            if (in_array($status, [
                StaffSettlementTransfer::STATUS_PROCESSING,
                StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM,
            ], true)) {
                $hasActive = true;
            }
            if (in_array($status, [
                StaffSettlementTransfer::STATUS_FAILED,
                StaffSettlementTransfer::STATUS_CLOSED,
            ], true)) {
                $hasFailed = true;
                $failReason = (string)$transfer->fail_reason;
            }
        }

        if ($hasSuccess) {
            $settlement->settle($transactionId, StaffSettlement::SETTLE_WAY_WECHAT);
            return;
        }
        if ($hasActive) {
            $settlement->markTransferProcessing();
            return;
        }
        if ($hasFailed) {
            $settlement->markFailed($failReason ?: '微信商家转账失败');
        }
    }
}
