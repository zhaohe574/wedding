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
use app\common\model\financial\StaffSettlementRedPacket;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\Refund;
use app\common\model\staff\Staff;
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
    /**
     * @notes 是否启用微信红包结算模式
     */
    public static function isRedPacketModeEnabled(): bool
    {
        return WeChatRedPacketService::isEnabled();
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

                $calcResult = StaffSettlementConfig::calculateSettlement($itemAmount, (int)$item->staff_id, 0);
                $allocatedCost = $orderCost > 0
                    ? round($orderCost * $itemAmount / $totalStaffSubtotal, 2)
                    : 0.0;
                $actualAmount = round(max((float)$calcResult['settlement_amount'] - $allocatedCost, 0), 2);
                if ($actualAmount <= 0) {
                    continue;
                }

                StaffSettlement::createSettlement([
                    'staff_id' => (int)$item->staff_id,
                    'order_id' => $orderId,
                    'order_item_id' => (int)$item->id,
                    'service_date' => $item->service_date ?: date('Y-m-d', (int)($order->complete_time ?: time())),
                    'order_amount' => $itemAmount,
                    'settlement_rate' => $calcResult['settlement_rate'],
                    'settlement_amount' => $calcResult['settlement_amount'],
                    'platform_amount' => $calcResult['platform_amount'],
                    'cost_amount' => $allocatedCost,
                    'actual_amount' => $actualAmount,
                    'settlement_type' => StaffSettlement::TYPE_AUTO,
                    'settle_way' => StaffSettlement::SETTLE_WAY_WECHAT,
                    'remark' => '订单完成自动生成微信红包结算',
                ]);
                $created++;
            }

            return $created;
        });
    }

    /**
     * @notes 发放待处理红包
     */
    public function processPendingRedPackets(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        $result = [
            'sent_count' => 0,
            'fail_count' => 0,
            'skip_count' => 0,
            'received_count' => 0,
        ];

        $syncResult = $this->syncRedPacketStatus(0, $limit);
        $result['received_count'] = (int)($syncResult['received_count'] ?? 0);

        if (!self::isRedPacketModeEnabled() || (int)WeChatRedPacketService::getConfig()['auto_send'] !== 1) {
            return $result;
        }

        $settlements = StaffSettlement::where('settle_way', StaffSettlement::SETTLE_WAY_WECHAT)
            ->where('status', StaffSettlement::STATUS_PENDING)
            ->order('id', 'asc')
            ->limit($limit)
            ->select();

        foreach ($settlements as $settlement) {
            $sendResult = $this->sendSettlementRedPacket($settlement, false);
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
     * @notes 发放结算红包
     */
    public function sendSettlementRedPacket(StaffSettlement $settlement, bool $forceRetry = false): array
    {
        if (!self::isRedPacketModeEnabled()) {
            return ['success' => false, 'skipped' => true, 'message' => '微信红包结算未启用'];
        }

        if (!in_array((int)$settlement->status, [StaffSettlement::STATUS_PENDING, StaffSettlement::STATUS_FAILED], true)) {
            return ['success' => false, 'skipped' => true, 'message' => '结算状态不可发放红包'];
        }

        $activePackets = StaffSettlementRedPacket::where('settlement_id', (int)$settlement->id)
            ->whereIn('status', [
                StaffSettlementRedPacket::STATUS_SENDING,
                StaffSettlementRedPacket::STATUS_SENT,
                StaffSettlementRedPacket::STATUS_RECEIVED,
            ])
            ->select();
        if (!$activePackets->isEmpty()) {
            $settlement->markRedPacketProcessing();
            return ['success' => true, 'message' => '红包已发放或处理中，沿用原单号'];
        }

        try {
            $packets = $this->ensureRedPacketRows($settlement);
            if (!$packets) {
                return ['success' => false, 'message' => '没有可发放的红包明细'];
            }

            $client = new WeChatRedPacketService();
            $successCount = 0;
            $failReason = '';
            foreach ($packets as $packet) {
                if (!$forceRetry && (int)$packet->status === StaffSettlementRedPacket::STATUS_FAILED) {
                    continue;
                }
                if (!$packet->canRetry()) {
                    continue;
                }

                $sendResult = $client->sendMiniProgramRedPacket($packet);
                if ($sendResult['success'] ?? false) {
                    $packet->markSent($sendResult['response'] ?? []);
                    $successCount++;
                    continue;
                }

                $failReason = (string)($sendResult['message'] ?? '红包发放失败');
                $packet->markFailed($failReason, $sendResult['response'] ?? []);
                break;
            }

            $this->refreshSettlementFromPackets($settlement);
            if ($failReason !== '') {
                return ['success' => false, 'message' => $failReason, 'success_count' => $successCount];
            }

            return ['success' => true, 'message' => '红包已发放，待服务人员领取', 'success_count' => $successCount];
        } catch (\Throwable $e) {
            $sendingPacket = StaffSettlementRedPacket::where('settlement_id', (int)$settlement->id)
                ->where('status', StaffSettlementRedPacket::STATUS_SENDING)
                ->find();
            if ($sendingPacket) {
                $settlement->markRedPacketProcessing();
            } else {
                $settlement->markFailed($e->getMessage());
            }
            Log::write('服务人员结算红包发放失败：' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @notes 同步红包状态
     */
    public function syncRedPacketStatus(int $settlementId = 0, int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $result = [
            'query_count' => 0,
            'received_count' => 0,
            'refunded_count' => 0,
            'fail_count' => 0,
        ];

        $query = StaffSettlementRedPacket::whereIn('status', [
            StaffSettlementRedPacket::STATUS_SENDING,
            StaffSettlementRedPacket::STATUS_SENT,
        ]);
        if ($settlementId > 0) {
            $query->where('settlement_id', $settlementId);
        }

        $packets = $query->order('last_query_time', 'asc')
            ->order('id', 'asc')
            ->limit($limit)
            ->select();

        if ($packets->isEmpty()) {
            return $result;
        }

        $client = new WeChatRedPacketService();
        $touchedSettlementIds = [];

        foreach ($packets as $packet) {
            try {
                $queryResult = $client->queryRedPacket($packet);
                $result['query_count']++;

                if (!($queryResult['success'] ?? false)) {
                    $packet->saveQueryResult($queryResult['response'] ?? []);
                    continue;
                }

                $response = $queryResult['response'] ?? [];
                $status = strtoupper((string)($response['status'] ?? ''));
                if ($status === 'RECEIVED') {
                    $packet->markReceived($response);
                    $result['received_count']++;
                } elseif (in_array($status, ['REFUND', 'RFUND_ING', 'REFUND_ING'], true)) {
                    $packet->markRefunded($response);
                    $result['refunded_count']++;
                } elseif ($status === 'FAILED') {
                    $packet->markFailed((string)($response['reason'] ?? '微信红包失败'), $response);
                    $result['fail_count']++;
                } else {
                    $packet->saveQueryResult($response);
                }

                $touchedSettlementIds[(int)$packet->settlement_id] = true;
            } catch (\Throwable $e) {
                Log::write('服务人员红包状态同步失败：' . $e->getMessage());
                $result['fail_count']++;
            }
        }

        foreach (array_keys($touchedSettlementIds) as $id) {
            $settlement = StaffSettlement::find((int)$id);
            if ($settlement) {
                $this->refreshSettlementFromPackets($settlement);
            }
        }

        return $result;
    }

    /**
     * @notes 重试红包发放
     */
    public function retryRedPacket(int $settlementId): array
    {
        $settlement = StaffSettlement::find($settlementId);
        if (!$settlement) {
            return ['success' => false, 'message' => '结算记录不存在'];
        }
        return $this->sendSettlementRedPacket($settlement, true);
    }

    /**
     * @notes 获取红包领取参数
     */
    public function getReceivePayload(int $settlementId, int $staffId): array|bool
    {
        $settlement = StaffSettlement::where('id', $settlementId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$settlement) {
            return false;
        }

        $packets = StaffSettlementRedPacket::where('settlement_id', $settlementId)
            ->whereIn('status', [StaffSettlementRedPacket::STATUS_SENT, StaffSettlementRedPacket::STATUS_SENDING])
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $packages = [];
        foreach ($packets as $packet) {
            $receivePackage = (string)($packet['receive_package'] ?? '');
            if ($receivePackage === '') {
                continue;
            }
            $packages[] = [
                'id' => (int)$packet['id'],
                'mch_billno' => (string)$packet['mch_billno'],
                'amount' => round((float)$packet['amount'], 2),
                'package' => $receivePackage,
            ];
        }

        return [
            'settlement_id' => (int)$settlement->id,
            'settlement_sn' => (string)$settlement->settlement_sn,
            'amount' => round((float)$settlement->actual_amount, 2),
            'packages' => $packages,
            'package' => $packages[0]['package'] ?? '',
            'status' => (int)$settlement->status,
            'status_text' => StaffSettlement::getStatusDesc((int)$settlement->status),
        ];
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
                ->whereIn('refund_status', array_merge(Refund::getPendingStatuses(), [Refund::STATUS_COMPLETED]))
                ->find()
        ) {
            return false;
        }
        return true;
    }

    /**
     * @notes 确保红包明细存在
     */
    protected function ensureRedPacketRows(StaffSettlement $settlement): array
    {
        $existing = StaffSettlementRedPacket::where('settlement_id', (int)$settlement->id)
            ->order('id', 'asc')
            ->select();
        if (!$existing->isEmpty()) {
            return $existing->all();
        }

        $staff = Staff::find((int)$settlement->staff_id);
        if (!$staff || (int)$staff->user_id <= 0) {
            $settlement->markFailed('服务人员未关联用户，无法获取小程序openid');
            return [];
        }

        $openid = UserAuth::where([
            'user_id' => (int)$staff->user_id,
            'terminal' => UserTerminalEnum::WECHAT_MMP,
        ])->value('openid');
        $openid = trim((string)$openid);
        if ($openid === '') {
            $settlement->markFailed('服务人员未绑定微信小程序openid，无法发放红包');
            return [];
        }

        $redPacketConfig = WeChatRedPacketService::getConfig();
        $wechatConfig = WeChatConfigService::getPayConfigByTerminal(UserTerminalEnum::WECHAT_MMP);
        $mnpConfig = WeChatConfigService::getMnpConfig();
        $amountFen = MoneyService::yuanToFen($settlement->actual_amount);
        $minFen = MoneyService::yuanToFen($redPacketConfig['min_amount']);
        $maxFen = MoneyService::yuanToFen($redPacketConfig['max_amount']);

        if ($amountFen < $minFen) {
            $settlement->markFailed('结算金额低于微信红包最小金额');
            return [];
        }

        $splits = $this->splitAmountFen($amountFen, $minFen, $maxFen);
        $packets = [];
        foreach ($splits as $index => $splitFen) {
            $packet = StaffSettlementRedPacket::createPacket($settlement, [
                'mch_billno' => $this->buildMchBillNo((string)($wechatConfig['mch_id'] ?? ''), (int)$settlement->id, $index + 1),
                'mch_id' => (string)($wechatConfig['mch_id'] ?? ''),
                'wxappid' => (string)($mnpConfig['app_id'] ?? ''),
                'openid' => $openid,
                'amount' => round($splitFen / 100, 2),
                'amount_fen' => $splitFen,
                'total_num' => 1,
                'send_name' => $redPacketConfig['send_name'],
                'wishing' => $redPacketConfig['wishing'],
                'act_name' => $redPacketConfig['act_name'],
                'remark' => $redPacketConfig['remark'],
                'scene_id' => $redPacketConfig['scene_id'],
            ]);
            $packets[] = $packet;
        }

        return $packets;
    }

    /**
     * @notes 拆分红包金额
     */
    protected function splitAmountFen(int $amountFen, int $minFen, int $maxFen): array
    {
        $splits = [];
        $remaining = $amountFen;
        while ($remaining > $maxFen) {
            $current = ($remaining - $maxFen < $minFen) ? $remaining - $minFen : $maxFen;
            $splits[] = $current;
            $remaining -= $current;
        }
        if ($remaining > 0) {
            $splits[] = $remaining;
        }
        return $splits;
    }

    /**
     * @notes 构造微信红包商户单号
     */
    protected function buildMchBillNo(string $mchId, int $settlementId, int $index): string
    {
        $mchId = preg_replace('/\D/', '', $mchId) ?: '0';
        $mchPart = str_pad(substr($mchId, 0, 10), 10, '0');
        $settlementPart = str_pad((string)($settlementId % 100000000), 8, '0', STR_PAD_LEFT);
        $indexPart = str_pad((string)($index % 100), 2, '0', STR_PAD_LEFT);
        return $mchPart . date('Ymd') . $settlementPart . $indexPart;
    }

    /**
     * @notes 根据红包状态刷新结算状态
     */
    protected function refreshSettlementFromPackets(StaffSettlement $settlement): void
    {
        $packets = StaffSettlementRedPacket::where('settlement_id', (int)$settlement->id)
            ->order('id', 'asc')
            ->select();
        if ($packets->isEmpty()) {
            return;
        }

        $allReceived = true;
        $hasActive = false;
        $hasFailed = false;
        $hasRefunded = false;
        $transactions = [];
        $failReason = '';

        foreach ($packets as $packet) {
            $status = (int)$packet->status;
            if ($status !== StaffSettlementRedPacket::STATUS_RECEIVED) {
                $allReceived = false;
            }
            if (in_array($status, [StaffSettlementRedPacket::STATUS_SENDING, StaffSettlementRedPacket::STATUS_SENT], true)) {
                $hasActive = true;
            }
            if ($status === StaffSettlementRedPacket::STATUS_FAILED) {
                $hasFailed = true;
                $failReason = (string)$packet->fail_reason;
            }
            if ($status === StaffSettlementRedPacket::STATUS_REFUNDED) {
                $hasRefunded = true;
                $failReason = '红包未领取已退款';
            }
            if ((string)$packet->wx_hb_id !== '') {
                $transactions[] = (string)$packet->wx_hb_id;
            } else {
                $transactions[] = (string)$packet->mch_billno;
            }
        }

        if ($allReceived) {
            $settlement->settle(implode(',', array_filter($transactions)), StaffSettlement::SETTLE_WAY_WECHAT);
            return;
        }
        if ($hasFailed || $hasRefunded) {
            $settlement->markFailed($failReason ?: '红包发放或领取失败');
            return;
        }
        if ($hasActive) {
            $settlement->markRedPacketProcessing();
        }
    }
}
