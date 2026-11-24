<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员结算服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\user\UserTerminalEnum;
use app\common\model\financial\StaffCompanyFeeUsage;
use app\common\model\financial\StaffSettlement;
use app\common\model\financial\StaffSettlementConfig;
use app\common\model\financial\StaffSettlementRepay;
use app\common\model\financial\StaffSettlementTransfer;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderItemAddon;
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

    public const REFUND_PAYOUT_BLOCKED_MESSAGE = '关联订单存在待处理退款申请，暂不可发起结算打款';

    /**
     * @notes 订单是否处于退款中/已退款或存在待处理退款申请（阻断结算打款）
     */
    public static function isOrderRefundBlocked(int $orderId): bool
    {
        if ($orderId <= 0) {
            return false;
        }

        $orderStatus = (int)Order::where('id', $orderId)->value('order_status');
        if (in_array($orderStatus, [Order::STATUS_REFUNDING, Order::STATUS_REFUNDED], true)) {
            return true;
        }

        if (
            Refund::where('order_id', $orderId)
                ->whereIn('refund_status', Refund::getPendingStatuses())
                ->find()
        ) {
            return true;
        }

        return false;
    }

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
            ->whereIn('o.order_status', [Order::STATUS_COMPLETED, Order::STATUS_REVIEWED])
            ->whereIn('o.pay_status', [Order::PAY_STATUS_PAID, Order::PAY_STATUS_PARTIAL_REFUND])
            ->where(function ($query) {
                $query->where('o.balance_paid', 1)
                    ->whereOr('o.balance_amount', '<=', 0);
            })
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

            // 订单项附加费用按 order_item_id 汇总，计入结算基础金额
            $addonMap = [];
            $addons = OrderItemAddon::where('order_id', $orderId)
                ->where('status', OrderItemAddon::STATUS_ACTIVE)
                ->select();
            foreach ($addons as $addon) {
                $addonItemId = (int)$addon->order_item_id;
                $addonMap[$addonItemId] = round((float)($addonMap[$addonItemId] ?? 0) + max((float)$addon->subtotal, 0), 2);
            }

            $baseAmountMap = [];
            $totalStaffBase = 0.0;
            foreach ($items as $item) {
                $baseAmount = round(max((float)$item->subtotal, 0) + max((float)($addonMap[(int)$item->id] ?? 0), 0), 2);
                $baseAmountMap[(int)$item->id] = $baseAmount;
                $totalStaffBase = round($totalStaffBase + $baseAmount, 2);
            }

            if ($totalStaffBase <= 0) {
                return 0;
            }

            // 订单实收低于结算基础金额时（整单折让），按比例将实收分摊到各订单项
            $itemAmountMap = $baseAmountMap;
            $orderPayAmount = round((float)$order->pay_amount, 2);
            if ((float)$order->pay_amount > 0 && $orderPayAmount < round($totalStaffBase, 2)) {
                $itemAmountMap = self::allocatePlatformPaidShares((float)$order->pay_amount, $items, $totalStaffBase, $baseAmountMap);
            }

            $platformPaidShareMap = self::allocatePlatformPaidShares(
                self::getOrderPlatformPaidNetAmount($orderId),
                $items,
                $totalStaffBase,
                $baseAmountMap
            );
            $created = 0;

            foreach ($items as $item) {
                $exists = StaffSettlement::where('order_item_id', (int)$item->id)->lock(true)->find();
                if ($exists) {
                    continue;
                }

                $itemAmount = round(max((float)($itemAmountMap[(int)$item->id] ?? 0), 0), 2);
                if ($itemAmount <= 0) {
                    continue;
                }

                $serviceDate = $item->service_date ?: date('Y-m-d', (int)($order->complete_time ?: time()));
                $calcResult = StaffSettlementConfig::calculateSettlement($itemAmount, (int)$item->staff_id, 0, $serviceDate);
                $platformAmount = round((float)($calcResult['platform_amount'] ?? $calcResult['company_amount'] ?? 0), 2);
                $companyAmount = round((float)($calcResult['company_amount'] ?? $platformAmount), 2);
                $settlementAmount = round((float)$calcResult['settlement_amount'], 2);
                $leaderAmount = round((float)($calcResult['leader_amount'] ?? 0), 2);
                $platformPaidShareAmount = round((float)($platformPaidShareMap[(int)$item->id] ?? 0), 2);

                $actualAmount = 0.0;
                $staffDuePlatformAmount = 0.0;
                if ($platformPaidShareAmount > $platformAmount) {
                    $payablePool = round(max($platformPaidShareAmount - $platformAmount, 0), 2);
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
                        'cost_amount' => 0,
                        'actual_amount' => $actualAmount,
                        'settlement_type' => StaffSettlement::TYPE_AUTO,
                        'status' => $status,
                        'settle_way' => $settleWay,
                        'remark' => $remark,
                    ]);
                } catch (\Throwable $e) {
                    if (self::isDuplicateKeyException($e)) {
                        // 唯一键并发冲突时释放本条已扣减的包月费用，避免重复扣减
                        if ((float)($calcResult['monthly_fee_deduct_amount'] ?? 0) > 0) {
                            StaffCompanyFeeUsage::releaseMonthlyFee(
                                (int)$item->staff_id,
                                $serviceDate,
                                (float)$calcResult['monthly_fee_deduct_amount']
                            );
                        }
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

        if (self::isOrderRefundBlocked((int)$settlement->order_id)) {
            return ['success' => false, 'skipped' => true, 'message' => self::REFUND_PAYOUT_BLOCKED_MESSAGE];
        }

        try {
            // 行锁占位：先在事务内锁单并推进状态，微信 HTTP 请求严禁放进事务
            $prep = Db::transaction(function () use ($settlement, $forceRetry) {
                $locked = StaffSettlement::where('id', (int)$settlement->id)->lock(true)->find();
                if (!$locked || $locked->isNoPayout()) {
                    return ['done' => true, 'result' => ['success' => false, 'skipped' => true, 'message' => '平台实收不足或无可打款金额，无需向服务人员打款']];
                }
                if (self::isOrderRefundBlocked((int)$locked->order_id)) {
                    return ['done' => true, 'result' => ['success' => false, 'skipped' => true, 'message' => self::REFUND_PAYOUT_BLOCKED_MESSAGE]];
                }
                if (!in_array((int)$locked->status, [
                    StaffSettlement::STATUS_PENDING,
                    StaffSettlement::STATUS_FAILED,
                    StaffSettlement::STATUS_TRANSFER_PROCESSING,
                ], true)) {
                    return ['done' => true, 'result' => ['success' => false, 'skipped' => true, 'message' => '结算状态不可发起转账']];
                }
                $transfer = $this->ensureTransferRow($locked, $forceRetry);
                if (!$transfer) {
                    return ['done' => true, 'result' => ['success' => false, 'message' => '没有可发起的转账明细']];
                }
                if ((int)$transfer->status === StaffSettlementTransfer::STATUS_SUCCESS) {
                    $this->refreshSettlementFromTransfers($locked);
                    return ['done' => true, 'result' => ['success' => true, 'message' => '转账已到账']];
                }
                if (in_array((int)$transfer->status, [
                    StaffSettlementTransfer::STATUS_PROCESSING,
                    StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM,
                ], true)) {
                    $locked->markTransferProcessing();
                    if ($forceRetry) {
                        return ['done' => true, 'result' => ['success' => false, 'message' => '当前转账处理中或待确认，请先同步状态']];
                    }
                    return ['done' => true, 'result' => ['success' => true, 'message' => '转账已受理，沿用原商户单号']];
                }
                if (!$transfer->canRetry()) {
                    return ['done' => true, 'result' => ['success' => false, 'message' => '当前转账状态不可重试，请先同步状态']];
                }
                $transfer->markProcessing();
                $locked->markTransferProcessing();
                return ['done' => false, 'transfer_id' => (int)$transfer->id, 'settlement_id' => (int)$locked->id];
            });
            if (!empty($prep['done'])) {
                return $prep['result'];
            }

            // 微信 HTTP 请求在事务外执行
            $transfer = StaffSettlementTransfer::find((int)$prep['transfer_id']);
            if (!$transfer) {
                return ['success' => false, 'message' => '没有可发起的转账明细'];
            }
            $sendResult = (new WeChatMerchantTransferService())->createTransferBill($transfer);
            $message = (string)($sendResult['message'] ?? '微信商家转账失败');

            // 事务 + 行锁回写转账结果并刷新结算状态
            $finalStatus = Db::transaction(function () use ($transfer, $sendResult, $message) {
                $lockedTransfer = StaffSettlementTransfer::where('id', (int)$transfer->id)->lock(true)->find();
                if (!$lockedTransfer) {
                    return (int)$transfer->status;
                }
                if (!($sendResult['success'] ?? false)) {
                    $lockedTransfer->markFailed($message, $sendResult['response'] ?? []);
                } else {
                    $lockedTransfer->applyWechatResult($sendResult['response'] ?? []);
                }
                $lockedSettlement = StaffSettlement::where('id', (int)$lockedTransfer->settlement_id)->lock(true)->find();
                if ($lockedSettlement) {
                    $this->refreshSettlementFromTransfers($lockedSettlement);
                }
                return (int)$lockedTransfer->status;
            });

            if (!($sendResult['success'] ?? false)) {
                return ['success' => false, 'message' => $message];
            }

            return [
                'success' => true,
                'message' => '微信商家转账已受理',
                'status' => $finalStatus,
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
            if (round((float)$settlement->staff_due_collected_amount, 2) > 0) {
                return [false, '订单关联结算存在已补交或待确认平台抽成记录，请先核实处理后再退款'];
            }
            $activeRepay = StaffSettlementRepay::where('settlement_id', (int)$settlement->id)
                ->whereIn('pay_status', [
                    StaffSettlementRepay::PAY_STATUS_PENDING,
                    StaffSettlementRepay::PAY_STATUS_PAID,
                ])
                ->where(function ($q) {
                    $q->where('is_compensation', 0)
                        ->whereOr('refund_status', '<>', 3);
                })
                ->find();
            if ($activeRepay) {
                return [false, '订单关联结算存在已补交或待确认平台抽成记录，请先核实处理后再退款'];
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

                // 金额校验：与回调路径（handleTransferNotify）复用同一段校验逻辑。
                // 查询接口返回的金额同样不可信，金额不符时不得把本地状态推进为成功态。
                $amountError = $this->validateTransferNotifyAmount($transfer, $response);
                if ($amountError !== '') {
                    $transfer->saveQueryResult($response);
                    Log::error('服务人员转账状态同步金额校验失败：out_bill_no='
                        . (string)$transfer->out_bill_no . '，' . $amountError);
                    $result['fail_count']++;
                    continue;
                }

                // 事务 + 行锁：与回调路径对齐，避免并发（cron 与回调同时到达）重复刷新结算金额
                Db::startTrans();
                try {
                    $lockedTransfer = StaffSettlementTransfer::where('id', (int)$transfer->id)->lock(true)->find();
                    if (!$lockedTransfer) {
                        Db::rollback();
                        Log::error('服务人员转账状态同步找不到转账单：id=' . (int)$transfer->id);
                        $result['fail_count']++;
                        continue;
                    }

                    $lockedTransfer->applyWechatResult($response, true);
                    $status = (int)$lockedTransfer->status;
                    $settlementId = (int)$lockedTransfer->settlement_id;
                    Db::commit();
                } catch (\Throwable $e) {
                    Db::rollback();
                    throw $e;
                }

                if ($status === StaffSettlementTransfer::STATUS_SUCCESS) {
                    $result['success_count']++;
                } elseif ($status === StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM) {
                    $result['wait_confirm_count']++;
                } elseif ($status === StaffSettlementTransfer::STATUS_FAILED || $status === StaffSettlementTransfer::STATUS_CLOSED) {
                    $result['fail_count']++;
                } else {
                    $result['processing_count']++;
                }

                $touchedSettlementIds[$settlementId] = true;
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
     * 与支付回调（Payment::paySuccess）对齐四件套：事务 + 行锁 + 金额分比较 + 商户号校验 + 状态幂等。
     * @param array $payload 微信回调解密后的报文
     * @return bool true 表示处理成功（微信不再重试）；false 表示拒绝或异常（微信按策略重试）
     */
    public function handleTransferNotify(array $payload): bool
    {
        $outBillNo = trim((string)($payload['out_bill_no'] ?? ''));
        if ($outBillNo === '') {
            Log::error('商家转账回调缺少 out_bill_no：' . StaffSettlementTransfer::encodePayload($payload));
            return false;
        }

        Db::startTrans();
        try {
            // 1. 行锁：并发/重复回调串行处理，避免结算金额被重复刷新
            $transfer = StaffSettlementTransfer::where('out_bill_no', $outBillNo)->lock(true)->find();
            if (!$transfer) {
                Db::rollback();
                Log::error('商家转账回调找不到转账单：out_bill_no=' . $outBillNo);
                return false;
            }

            // 2. 状态幂等：已到账的转账单重复回调直接返回成功，不再刷新结算
            if ((int)$transfer->status === StaffSettlementTransfer::STATUS_SUCCESS) {
                Db::commit();
                return true;
            }

            // 3. 商户号校验：回调必须来自本商户，避免跨商户/伪造回调篡改结算状态
            if (!$this->isTransferNotifyMchIdMatched($transfer, $payload)) {
                Db::rollback();
                Log::error('商家转账回调商户号不匹配：out_bill_no=' . $outBillNo
                    . '，payload.mch_id=' . (string)($payload['mch_id'] ?? $payload['mchid'] ?? ''));
                return false;
            }

            // 4. 金额校验：统一按「分」比较，金额不一致直接拒绝并告警
            $amountError = $this->validateTransferNotifyAmount($transfer, $payload);
            if ($amountError !== '') {
                Db::rollback();
                Log::error('商家转账回调金额校验失败：out_bill_no=' . $outBillNo . '，' . $amountError);
                return false;
            }

            $transfer->notify_data = StaffSettlementTransfer::encodePayload($payload);
            $transfer->applyWechatResult($payload, true);

            $settlement = StaffSettlement::where('id', (int)$transfer->settlement_id)->lock(true)->find();
            if ($settlement) {
                $this->refreshSettlementFromTransfers($settlement);
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('商家转账回调处理异常：out_bill_no=' . $outBillNo . '，' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 校验转账回调的商户号是否为本商户
     * 优先比对转账单落库的 mch_id（发起时写入），其次比对当前支付配置；
     * 两者都取不到时不做拦截（回调签名已由微信支付 SDK 校验）。
     */
    protected function isTransferNotifyMchIdMatched(StaffSettlementTransfer $transfer, array $payload): bool
    {
        $payloadMchId = trim((string)($payload['mch_id'] ?? $payload['mchid'] ?? ''));
        if ($payloadMchId === '') {
            // 回调报文未携带商户号时无法比对，交由签名校验兜底
            return true;
        }

        $expectedList = [trim((string)$transfer->mch_id)];
        try {
            $payConfig = WeChatConfigService::getPayConfigByTerminal(UserTerminalEnum::WECHAT_MMP);
            $expectedList[] = trim((string)($payConfig['mch_id'] ?? ''));
        } catch (\Throwable $e) {
            Log::write('商家转账回调读取支付配置失败：' . $e->getMessage());
        }

        foreach ($expectedList as $expected) {
            if ($expected !== '' && hash_equals($expected, $payloadMchId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @notes 校验转账回调金额（分）
     * @return string 空字符串表示校验通过，否则返回失败原因
     */
    protected function validateTransferNotifyAmount(StaffSettlementTransfer $transfer, array $payload): string
    {
        $notifyAmountFen = $this->resolveTransferNotifyAmountFen($payload);
        if ($notifyAmountFen === null) {
            // 报文未携带金额字段，无法比对；记录告警但不阻断资金状态同步
            Log::write('商家转账回调缺少金额字段，跳过金额校验：out_bill_no=' . (string)$transfer->out_bill_no);
            return '';
        }

        $expectAmountFen = (int)$transfer->amount_fen;
        if ($expectAmountFen <= 0 && (float)$transfer->amount > 0) {
            $expectAmountFen = MoneyService::yuanToFen($transfer->amount);
        }

        if (abs($notifyAmountFen - $expectAmountFen) > 0) {
            return '回调金额（分）' . $notifyAmountFen . ' 与转账单金额（分）' . $expectAmountFen . ' 不一致';
        }

        return '';
    }

    /**
     * @notes 从回调报文解析转账金额（分），兼容 transfer_amount / amount / amount_fen 三种字段
     */
    protected function resolveTransferNotifyAmountFen(array $payload): ?int
    {
        foreach (['transfer_amount', 'amount_fen', 'amount'] as $field) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }
            $value = $payload[$field];
            if ($value === '' || $value === null) {
                continue;
            }
            return (int)$value;
        }
        return null;
    }

    /**
     * @notes 判断订单是否可生成结算
     */
    protected function canGenerateForOrder(Order $order): bool
    {
        if (!in_array((int)$order->order_status, [Order::STATUS_COMPLETED, Order::STATUS_REVIEWED], true)) {
            return false;
        }
        $balanceSettled = (float)($order->balance_amount ?? 0) <= 0 || (int)$order->balance_paid === 1;
        if (!in_array((int)$order->pay_status, [Order::PAY_STATUS_PAID, Order::PAY_STATUS_PARTIAL_REFUND], true) || !$balanceSettled) {
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
     * @notes 获取平台收款净额，包括平台线下到账，排除人员代收
     */
    public static function getOrderPlatformPaidNetAmount(int $orderId): float
    {
        if ($orderId <= 0) {
            return 0.0;
        }

        $compensationPaymentIds = Refund::where('order_id', $orderId)
            ->where('is_compensation', 1)
            ->where('payment_id', '>', 0)
            ->column('payment_id');
        $compensationPaymentIds = array_values(array_unique(array_filter(array_map('intval', $compensationPaymentIds ?: []))));

        $query = Payment::where('order_id', $orderId)
            ->where('collection_owner', Payment::COLLECTION_PLATFORM)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED]);
        if (!empty($compensationPaymentIds)) {
            $query->whereNotIn('id', $compensationPaymentIds);
        }
        $payments = $query->select();

        $amount = 0.0;
        foreach ($payments as $payment) {
            $amount += max(round((float)$payment->pay_amount - (float)($payment->refund_amount ?? 0), 2), 0);
        }

        return round($amount, 2);
    }

    /**
     * @notes 按订单项金额比例分摊平台实收净额
     * 传入 $baseAmountMap 时按附加项合计后的基础金额作权重，否则按 order_item.subtotal
     */
    protected static function allocatePlatformPaidShares(float $platformPaidNetAmount, iterable $items, float $totalStaffSubtotal, array $baseAmountMap = []): array
    {
        $shares = [];
        $validItems = [];
        foreach ($items as $item) {
            $itemId = (int)$item->id;
            $amount = array_key_exists($itemId, $baseAmountMap)
                ? round(max((float)$baseAmountMap[$itemId], 0), 2)
                : round(max((float)$item->subtotal, 0), 2);
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
    protected function ensureTransferRow(StaffSettlement $settlement, bool $forceRetry = false): ?StaffSettlementTransfer
    {
        $transfers = StaffSettlementTransfer::where('settlement_id', (int)$settlement->id)
            ->order('id', 'desc')
            ->select();

        foreach ($transfers as $transfer) {
            if (in_array((int)$transfer->status, [
                StaffSettlementTransfer::STATUS_SUCCESS,
                StaffSettlementTransfer::STATUS_PROCESSING,
                StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM,
            ], true)) {
                return $transfer;
            }
        }

        $latest = $transfers->isEmpty() ? null : $transfers[0];
        if ($latest && (int)$latest->status === StaffSettlementTransfer::STATUS_PENDING) {
            return $latest;
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

        // 终态失败且未生成微信侧单据、收款信息未变更的原单可直接沿用重试
        if (
            $latest
            && (int)$latest->status === StaffSettlementTransfer::STATUS_FAILED
            && trim((string)$latest->transfer_bill_no) === ''
            && trim((string)$latest->wx_state) === ''
            && (string)$latest->openid === $openid
            && (string)$latest->user_name === $userName
            && (string)$latest->transfer_scene_id === $sceneId
            && (int)$latest->amount_fen === $amountFen
        ) {
            return $latest;
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
        Db::transaction(function () use ($settlement) {
            $locked = StaffSettlement::where('id', (int)$settlement->id)->lock(true)->find();
            if (!$locked) {
                return;
            }

            $transfers = StaffSettlementTransfer::where('settlement_id', (int)$locked->id)
                ->order('id', 'asc')
                ->select();
            if ($transfers->isEmpty()) {
                return;
            }

            foreach ($transfers as $transfer) {
                if ((int)$transfer->status === StaffSettlementTransfer::STATUS_SUCCESS) {
                    $locked->settle(
                        (string)$transfer->transfer_bill_no ?: (string)$transfer->out_bill_no,
                        StaffSettlement::SETTLE_WAY_WECHAT
                    );
                    return;
                }
            }

            foreach ($transfers as $transfer) {
                if (in_array((int)$transfer->status, [
                    StaffSettlementTransfer::STATUS_PROCESSING,
                    StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM,
                ], true)) {
                    $locked->markTransferProcessing();
                    return;
                }
            }

            // 全部转账均已终止：仅当最新一条为失败/关闭时才把结算标记为失败
            $latest = $transfers[$transfers->count() - 1];
            if (in_array((int)$latest->status, [
                StaffSettlementTransfer::STATUS_FAILED,
                StaffSettlementTransfer::STATUS_CLOSED,
            ], true)) {
                $locked->markFailed((string)$latest->fail_reason ?: '微信商家转账失败');
            }
        });
    }

    /**
     * @notes 汇总结算转账明细信息
     */
    public static function buildTransferSummary(array $transfers): array
    {
        $summary = [
            'count' => count($transfers),
            'success_count' => 0,
            'wait_confirm_count' => 0,
            'status_text' => '',
            'out_bill_no' => '',
            'transfer_bill_no' => '',
            'package_info' => '',
            'fail_reason' => '',
        ];
        if ($summary['count'] === 0) {
            return $summary;
        }

        $successTransfer = null;
        $latestTransfer = null;
        $latestId = -1;
        foreach ($transfers as $transfer) {
            $status = (int)($transfer['status'] ?? 0);
            if ($status === StaffSettlementTransfer::STATUS_SUCCESS) {
                $summary['success_count']++;
                if ($successTransfer === null) {
                    $successTransfer = $transfer;
                }
            } elseif ($status === StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM) {
                $summary['wait_confirm_count']++;
            }

            // 默认按 id asc 排列取最后一条；若首尾 id 倒序则取 id 最大者
            $id = (int)($transfer['id'] ?? 0);
            if ($id >= $latestId) {
                $latestId = $id;
                $latestTransfer = $transfer;
            }
        }

        $primary = $successTransfer ?? $latestTransfer;
        if ($primary === null) {
            return $summary;
        }

        $summary['out_bill_no'] = (string)($primary['out_bill_no'] ?? '');
        $summary['transfer_bill_no'] = (string)($primary['transfer_bill_no'] ?? '');
        $summary['package_info'] = (string)($primary['package_info'] ?? '');

        $primaryStatus = (int)($primary['status'] ?? 0);
        if (in_array($primaryStatus, [
            StaffSettlementTransfer::STATUS_FAILED,
            StaffSettlementTransfer::STATUS_CLOSED,
        ], true)) {
            $summary['fail_reason'] = (string)($primary['fail_reason'] ?? '');
        }

        if ($summary['success_count'] === $summary['count'] && $summary['count'] > 0) {
            $summary['status_text'] = '全部已到账';
        } else {
            $summary['status_text'] = StaffSettlementTransfer::getStatusDesc($primaryStatus);
        }

        return $summary;
    }
}
