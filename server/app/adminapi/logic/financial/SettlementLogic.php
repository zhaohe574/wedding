<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 结算管理逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\financial;

use app\common\logic\BaseLogic;
use app\common\model\financial\SettlementBatch;
use app\common\model\financial\StaffSettlement;
use app\common\model\financial\StaffSettlementConfig;
use app\common\model\financial\StaffSettlementTransfer;
use app\common\service\StaffSettlementRepayService;
use app\common\service\StaffSettlementService;
use app\common\service\WeChatMerchantTransferService;
use think\facade\Db;

/**
 * 结算管理逻辑层
 * Class SettlementLogic
 * @package app\adminapi\logic\financial
 */
class SettlementLogic extends BaseLogic
{
    /**
     * @notes 结算详情
     */
    public static function detail(int $id): array
    {
        $settlement = StaffSettlement::with(['staff', 'team', 'leader', 'order', 'orderItem', 'batch', 'transfers', 'repays'])
            ->find($id);

        if (!$settlement) {
            return [];
        }

        $data = $settlement->toArray();
        $data['status_text'] = StaffSettlement::getStatusDesc($settlement->status);
        $data['type_text'] = StaffSettlement::getTypeDesc($settlement->settlement_type);
        $data['settle_way_text'] = StaffSettlement::getSettleWayDesc($settlement->settle_way);
        $data['settlement_mode_text'] = StaffSettlementConfig::getModeDesc((int)($settlement->settlement_mode ?? StaffSettlementConfig::MODE_RATE));
        $data['scope_type_text'] = StaffSettlementConfig::getScopeDesc((int)($settlement->scope_type ?? StaffSettlementConfig::SCOPE_DEFAULT));
        $data['is_no_payout'] = $settlement->isNoPayout() ? 1 : 0;
        $data['is_offline_payment_order'] = self::isOfflinePaymentOrderData($data) ? 1 : 0;
        $data['platform_commission_amount'] = round((float)($data['platform_amount'] ?? $data['company_amount'] ?? 0), 2);
        $data['platform_paid_share_amount'] = round((float)($data['platform_paid_share_amount'] ?? 0), 2);
        $data['staff_due_platform_amount'] = round((float)($data['staff_due_platform_amount'] ?? 0), 2);
        $data['staff_due_collected_amount'] = round((float)($data['staff_due_collected_amount'] ?? 0), 2);
        $data['staff_due_left_amount'] = $settlement->getDuePlatformLeftAmount();
        $data['staff_due_collect_status_text'] = StaffSettlement::getDueCollectStatusDesc((int)($settlement->staff_due_collect_status ?? 0));
        $data['transfer_summary'] = self::buildTransferSummary($data['transfers'] ?? []);
        $repays = [];
        foreach ($settlement->repays as $repay) {
            $repays[] = StaffSettlementRepayService::formatRepay($repay);
        }
        $data['repays'] = $repays;

        return $data;
    }

    /**
     * @notes 执行单笔结算
     */
    public static function settle(int $id): bool
    {
        try {
            $settlement = StaffSettlement::find($id);
            if (!$settlement) {
                self::setError('结算记录不存在');
                return false;
            }

            if ($settlement->isNoPayout()) {
                self::setError('平台实收不足或无可打款金额，无需向服务人员打款');
                return false;
            }

            if (!in_array((int)$settlement->status, [
                StaffSettlement::STATUS_PENDING,
                StaffSettlement::STATUS_FAILED,
            ], true)) {
                self::setError('结算状态不正确');
                return false;
            }

            if ((int)$settlement->settle_way === StaffSettlement::SETTLE_WAY_WECHAT) {
                $result = (new StaffSettlementService())->sendSettlementTransfer($settlement, true);
                if (!($result['success'] ?? false)) {
                    self::setError((string)($result['message'] ?? '微信商家转账失败'));
                    return false;
                }
                return true;
            }

            return $settlement->settle();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量结算
     */
    public static function batchSettle(array $ids): array|bool
    {
        try {
            $successCount = 0;
            $failCount = 0;

            foreach ($ids as $id) {
                $settlement = StaffSettlement::find($id);
                if ($settlement && in_array((int)$settlement->status, [
                    StaffSettlement::STATUS_PENDING,
                    StaffSettlement::STATUS_FAILED,
                ], true) && !$settlement->isNoPayout()) {
                    if ((int)$settlement->settle_way === StaffSettlement::SETTLE_WAY_WECHAT) {
                        $result = (new StaffSettlementService())->sendSettlementTransfer($settlement, true);
                        $success = (bool)($result['success'] ?? false);
                    } else {
                        $success = $settlement->settle();
                    }

                    if ($success) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                } else {
                    $failCount++;
                }
            }

            return [
                'success_count' => $successCount,
                'fail_count' => $failCount,
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 取消结算
     */
    public static function cancel(int $id): bool
    {
        try {
            $settlement = StaffSettlement::find($id);
            if (!$settlement) {
                self::setError('结算记录不存在');
                return false;
            }

            return $settlement->cancel();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 结算统计
     */
    public static function statistics(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');

        $query = StaffSettlement::whereBetween('service_date', [$startDate, $endDate]);

        $totalPending = (clone $query)->where('status', StaffSettlement::STATUS_PENDING)->sum('actual_amount');
        $totalSettled = (clone $query)->where('status', StaffSettlement::STATUS_SETTLED)->sum('actual_amount');
        $pendingCount = (clone $query)->where('status', StaffSettlement::STATUS_PENDING)->count();
        $settledCount = (clone $query)->where('status', StaffSettlement::STATUS_SETTLED)->count();
        $transferProcessingCount = (clone $query)->where('status', StaffSettlement::STATUS_TRANSFER_PROCESSING)->count();
        $transferProcessingAmount = (clone $query)->where('status', StaffSettlement::STATUS_TRANSFER_PROCESSING)->sum('actual_amount');
        $noPayoutCount = (clone $query)->where('status', StaffSettlement::STATUS_NO_PAYOUT)->count();
        $platformCommissionAmount = (clone $query)->sum('platform_amount');
        $platformPaidShareAmount = (clone $query)->sum('platform_paid_share_amount');
        $staffDuePlatformAmount = (clone $query)->sum('staff_due_platform_amount');
        $staffDueCollectedAmount = (clone $query)->sum('staff_due_collected_amount');

        return [
            'pending_amount' => round($totalPending, 2),
            'settled_amount' => round($totalSettled, 2),
            'pending_count' => $pendingCount,
            'settled_count' => $settledCount,
            'transfer_processing_count' => $transferProcessingCount,
            'transfer_processing_amount' => round($transferProcessingAmount, 2),
            'no_payout_count' => $noPayoutCount,
            'platform_commission_amount' => round($platformCommissionAmount, 2),
            'platform_paid_share_amount' => round($platformPaidShareAmount, 2),
            'staff_due_platform_amount' => round($staffDuePlatformAmount, 2),
            'staff_due_collected_amount' => round($staffDueCollectedAmount, 2),
            'staff_due_left_amount' => round(max($staffDuePlatformAmount - $staffDueCollectedAmount, 0), 2),
            'total_amount' => round($totalPending + $totalSettled + $transferProcessingAmount, 2),
            'total_count' => $pendingCount + $settledCount + $transferProcessingCount + $noPayoutCount,
        ];
    }

    /**
     * @notes 人员结算汇总
     */
    public static function staffSummary(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');

        $list = StaffSettlement::alias('s')
            ->leftJoin('la_staff st', 's.staff_id = st.id')
            ->whereBetween('s.service_date', [$startDate, $endDate])
            ->group('s.staff_id')
            ->field([
                's.staff_id',
                'st.name as staff_name',
                'st.avatar as staff_avatar',
                'COUNT(*) as total_count',
                'SUM(s.order_amount) as total_order_amount',
                'SUM(s.actual_amount) as total_settlement_amount',
                'SUM(s.platform_amount) as platform_commission_amount',
                'SUM(s.platform_paid_share_amount) as platform_paid_share_amount',
                'SUM(s.staff_due_platform_amount) as staff_due_platform_amount',
                'SUM(s.staff_due_collected_amount) as staff_due_collected_amount',
                'SUM(CASE WHEN s.status = ' . StaffSettlement::STATUS_PENDING . ' THEN s.actual_amount ELSE 0 END) as pending_amount',
                'SUM(CASE WHEN s.status = ' . StaffSettlement::STATUS_SETTLED . ' THEN s.actual_amount ELSE 0 END) as settled_amount',
                'SUM(CASE WHEN s.status = ' . StaffSettlement::STATUS_TRANSFER_PROCESSING . ' THEN s.actual_amount ELSE 0 END) as transfer_processing_amount',
                'SUM(CASE WHEN s.status = ' . StaffSettlement::STATUS_NO_PAYOUT . ' THEN 1 ELSE 0 END) as no_payout_count',
            ])
            ->order('total_settlement_amount', 'desc')
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['staff_due_left_amount'] = round(max(
                (float)($item['staff_due_platform_amount'] ?? 0) - (float)($item['staff_due_collected_amount'] ?? 0),
                0
            ), 2);
        }
        unset($item);

        return $list;
    }

    /**
     * @notes 创建结算批次
     */
    public static function createBatch(array $params): array|bool
    {
        Db::startTrans();
        try {
            $startDate = $params['settle_start_date'];
            $endDate = $params['settle_end_date'];

            $pendingSettlements = StaffSettlement::where('status', StaffSettlement::STATUS_PENDING)
                ->where('settle_way', '<>', StaffSettlement::SETTLE_WAY_NO_PAYOUT)
                ->whereBetween('service_date', [$startDate, $endDate])
                ->select();

            if ($pendingSettlements->isEmpty()) {
                self::setError('没有找到待结算的记录');
                return false;
            }

            $totalAmount = 0.0;
            foreach ($pendingSettlements as $settlement) {
                $totalAmount += (float)($settlement->actual_amount ?? 0);
            }
            $totalAmount = round($totalAmount, 2);

            $batch = SettlementBatch::createBatch([
                'batch_name' => $params['batch_name'] ?? '结算批次-' . date('Y-m-d'),
                'settle_start_date' => $startDate,
                'settle_end_date' => $endDate,
                'total_count' => count($pendingSettlements),
                'total_amount' => $totalAmount,
                'remark' => $params['remark'] ?? '',
            ]);

            StaffSettlement::where('status', StaffSettlement::STATUS_PENDING)
                ->where('settle_way', '<>', StaffSettlement::SETTLE_WAY_NO_PAYOUT)
                ->whereBetween('service_date', [$startDate, $endDate])
                ->update(['batch_id' => $batch->id]);

            Db::commit();
            return [
                'batch_id' => $batch->id,
                'batch_sn' => $batch->batch_sn,
                'total_count' => count($pendingSettlements),
                'total_amount' => round($totalAmount, 2),
            ];
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 审核批次
     */
    public static function auditBatch(array $params, int $adminId): bool
    {
        try {
            $batch = SettlementBatch::find($params['batch_id']);
            if (!$batch) {
                self::setError('批次不存在');
                return false;
            }

            if ($params['status'] == 1) {
                return $batch->approve($adminId, $params['remark'] ?? '');
            } else {
                return $batch->cancel();
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 执行批次结算
     */
    public static function executeBatch(array $params, int $adminId): array|bool
    {
        try {
            $batch = SettlementBatch::find($params['batch_id']);
            if (!$batch) {
                self::setError('批次不存在');
                return false;
            }

            if (!$batch->startExecute($adminId)) {
                self::setError('批次状态不正确');
                return false;
            }

            return $batch->execute();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 取消批次
     */
    public static function cancelBatch(array $params): bool
    {
        try {
            $batch = SettlementBatch::find($params['batch_id']);
            if (!$batch) {
                self::setError('批次不存在');
                return false;
            }

            return $batch->cancel();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 结算配置列表
     */
    public static function configLists(): array
    {
        $list = StaffSettlementConfig::with(['staff', 'category', 'team'])
            ->order('is_default', 'desc')
            ->order('scope_type', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['cycle_text'] = StaffSettlementConfig::getCycleDesc($item['settle_cycle']);
            $item['status_text'] = StaffSettlementConfig::getStatusDesc($item['status']);
            $item['scope_type_text'] = StaffSettlementConfig::getScopeDesc((int)($item['scope_type'] ?? StaffSettlementConfig::SCOPE_DEFAULT));
            $item['settlement_mode_text'] = StaffSettlementConfig::getModeDesc((int)($item['settlement_mode'] ?? StaffSettlementConfig::MODE_RATE));
        }

        return $list;
    }

    /**
     * @notes 添加结算配置
     */
    public static function addConfig(array $params): bool
    {
        try {
            self::validateConfigPayload($params);
            StaffSettlementConfig::createConfig($params);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑结算配置
     */
    public static function editConfig(array $params): bool
    {
        try {
            $config = StaffSettlementConfig::find($params['id']);
            if (!$config) {
                self::setError('配置不存在');
                return false;
            }

            self::validateConfigPayload($params);
            return $config->saveConfig($params);
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除结算配置
     */
    public static function deleteConfig(int $id): bool
    {
        try {
            $config = StaffSettlementConfig::find($id);
            if (!$config) {
                self::setError('配置不存在');
                return false;
            }

            if ($config->is_default) {
                self::setError('默认配置不能删除');
                return false;
            }

            return $config->delete();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 后台手动生成结算记录
     */
    public static function generate(array $params): array|bool
    {
        try {
            $startDate = (string)($params['start_date'] ?? date('Y-m-d', strtotime('-90 days')));
            $endDate = (string)($params['end_date'] ?? date('Y-m-d'));
            $count = (new StaffSettlementService())->generateFromCompletedOrders($startDate, $endDate);
            return ['count' => $count];
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 重试转账
     */
    public static function retryTransfer(int $id): bool
    {
        $settlement = StaffSettlement::find($id);
        if ($settlement && $settlement->isNoPayout()) {
            self::setError('平台实收不足或无可打款金额，无需向服务人员打款');
            return false;
        }

        $result = (new StaffSettlementService())->retryTransfer($id);
        if (!($result['success'] ?? false)) {
            self::setError((string)($result['message'] ?? '转账重试失败'));
            return false;
        }
        return true;
    }

    /**
     * @notes 后台补入线下收款
     */
    public static function collectDue(array $params, int $adminId): bool
    {
        $result = StaffSettlementRepayService::manualCollect(
            (int)$params['id'],
            (float)$params['amount'],
            $adminId,
            (string)($params['remark'] ?? '')
        );
        if ($result === false) {
            self::setError(StaffSettlementRepayService::getError());
            return false;
        }
        return true;
    }

    /**
     * @notes 同步转账状态
     */
    public static function syncTransfer(int $id = 0): array|bool
    {
        try {
            return (new StaffSettlementService())->syncTransferStatus($id);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 转账明细
     */
    public static function transferDetail(int $id): array
    {
        $settlement = StaffSettlement::with(['transfers'])
            ->find($id);
        if (!$settlement) {
            return [];
        }
        $transfers = $settlement->transfers ? $settlement->transfers->toArray() : [];
        foreach ($transfers as &$transfer) {
            $transfer['status_text'] = StaffSettlementTransfer::getStatusDesc((int)$transfer['status']);
        }
        return [
            'settlement_id' => (int)$settlement->id,
            'settlement_sn' => (string)$settlement->settlement_sn,
            'status_text' => StaffSettlement::getStatusDesc((int)$settlement->status),
            'transfer_summary' => self::buildTransferSummary($transfers),
            'transfers' => $transfers,
        ];
    }

    /**
     * @notes 转账配置
     */
    public static function transferConfig(): array
    {
        return WeChatMerchantTransferService::getConfig();
    }

    /**
     * @notes 校验结算配置业务字段
     */
    protected static function validateConfigPayload(array $params): void
    {
        $scopeType = (int)($params['scope_type'] ?? StaffSettlementConfig::SCOPE_DEFAULT);
        if ($scopeType === StaffSettlementConfig::SCOPE_STAFF && (int)($params['staff_id'] ?? 0) <= 0) {
            throw new \RuntimeException('人员配置必须选择服务人员');
        }
        if ($scopeType === StaffSettlementConfig::SCOPE_TEAM && (int)($params['team_id'] ?? 0) <= 0) {
            throw new \RuntimeException('队伍配置必须选择服务队伍');
        }

        $settlementMode = (int)($params['settlement_mode'] ?? StaffSettlementConfig::MODE_RATE);
        if ($settlementMode === StaffSettlementConfig::MODE_MONTHLY && (float)($params['monthly_fee'] ?? 0) <= 0) {
            throw new \RuntimeException('包月模式必须填写包月金额');
        }
        if ($settlementMode === StaffSettlementConfig::MODE_RATE) {
            $companyRate = (float)($params['company_rate'] ?? 0);
            $leaderRate = (float)($params['leader_rate'] ?? 0);
            if ($companyRate + $leaderRate > 100) {
                throw new \RuntimeException('公司抽成比例和队长抽成比例合计不能超过100%');
            }
        }
    }

    /**
     * @notes 保存转账配置
     */
    public static function saveTransferConfig(array $params): array|bool
    {
        try {
            return WeChatMerchantTransferService::saveConfig($params);
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 构造转账摘要
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

        foreach ($transfers as $transfer) {
            if ($summary['out_bill_no'] === '' && !empty($transfer['out_bill_no'])) {
                $summary['out_bill_no'] = (string)$transfer['out_bill_no'];
            }
            if ($summary['transfer_bill_no'] === '' && !empty($transfer['transfer_bill_no'])) {
                $summary['transfer_bill_no'] = (string)$transfer['transfer_bill_no'];
            }
            if ($summary['package_info'] === '' && !empty($transfer['package_info'])) {
                $summary['package_info'] = (string)$transfer['package_info'];
            }
            if ($summary['fail_reason'] === '' && !empty($transfer['fail_reason'])) {
                $summary['fail_reason'] = (string)$transfer['fail_reason'];
            }
            if ((int)($transfer['status'] ?? -1) === StaffSettlementTransfer::STATUS_SUCCESS) {
                $summary['success_count']++;
            }
            if ((int)($transfer['status'] ?? -1) === StaffSettlementTransfer::STATUS_WAIT_USER_CONFIRM) {
                $summary['wait_confirm_count']++;
            }
        }

        if ($summary['count'] > 0) {
            $firstStatus = (int)($transfers[0]['status'] ?? StaffSettlementTransfer::STATUS_PENDING);
            $summary['status_text'] = StaffSettlementTransfer::getStatusDesc($firstStatus);
            if ($summary['success_count'] === $summary['count']) {
                $summary['status_text'] = '全部已到账';
            }
        }

        return $summary;
    }

    protected static function isOfflinePaymentOrderData(array $data): bool
    {
        $order = $data['order'] ?? [];
        return (int)($order['payment_channel'] ?? 0) === \app\common\model\order\Order::PAYMENT_CHANNEL_OFFLINE
            || (int)($order['pay_type'] ?? 0) === \app\common\model\order\Order::PAY_WAY_OFFLINE
            || trim((string)($order['pay_voucher'] ?? '')) !== '';
    }
}
