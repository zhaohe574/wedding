<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单转让业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\order;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderTransfer;
use app\common\model\order\OrderChangeLog;
use think\facade\Db;

/**
 * 订单转让业务逻辑
 * Class OrderTransferLogic
 * @package app\adminapi\logic\order
 */
class OrderTransferLogic extends BaseLogic
{
    /**
     * @notes 获取转让详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $transfer = OrderTransfer::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, order_status, pay_status, total_amount, pay_amount, service_date');
            },
            'fromUser' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            },
            'toUser' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            },
        ])->find($id);

        if (!$transfer) {
            return null;
        }

        $data = $transfer->toArray();
        $data['transfer_status_desc'] = $transfer->transfer_status_desc;
        
        // 隐藏验证码（安全考虑）
        unset($data['accept_code']);

        return $data;
    }

    /**
     * @notes 审核转让申请
     * @param int $transferId
     * @param int $adminId
     * @param bool $approved
     * @param string $remark
     * @param string $rejectReason
     * @return bool
     */
    public static function audit(
        int $transferId,
        int $adminId,
        bool $approved,
        string $remark = '',
        string $rejectReason = ''
    ): bool {
        [$success, $message] = OrderTransfer::auditTransfer($transferId, $adminId, $approved, $remark, $rejectReason);
        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 手动完成转让
     * @param int $transferId
     * @param int $adminId
     * @return bool
     */
    public static function complete(int $transferId, int $adminId): bool
    {
        $transfer = OrderTransfer::find($transferId);
        if (!$transfer) {
            self::setError('转让记录不存在');
            return false;
        }

        // 如果是待接收状态，先更新为已接收
        if ($transfer->transfer_status == OrderTransfer::STATUS_WAITING) {
            $transfer->transfer_status = OrderTransfer::STATUS_ACCEPTED;
            $transfer->to_user_verified = 1; // 管理员手动确认，视为已验证
            $transfer->accept_time = time();
            $transfer->update_time = time();
            $transfer->save();
        }

        [$success, $message] = OrderTransfer::completeTransfer($transferId, $adminId);
        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 取消转让
     * @param int $transferId
     * @param int $adminId
     * @param string $reason
     * @return bool
     */
    public static function cancel(int $transferId, int $adminId, string $reason = ''): bool
    {
        $transfer = OrderTransfer::find($transferId);
        if (!$transfer) {
            self::setError('转让记录不存在');
            return false;
        }

        if (!in_array($transfer->transfer_status, [
            OrderTransfer::STATUS_PENDING,
            OrderTransfer::STATUS_WAITING,
            OrderTransfer::STATUS_ACCEPTED
        ])) {
            self::setError('当前状态不可取消');
            return false;
        }

        Db::startTrans();
        try {
            $beforeStatus = $transfer->transfer_status;
            $transfer->transfer_status = OrderTransfer::STATUS_CANCELLED;
            $transfer->reject_reason = $reason ?: '管理员取消';
            $transfer->update_time = time();
            $transfer->save();

            // 记录日志
            OrderChangeLog::addLog(
                $transfer->order_id,
                OrderChangeLog::RELATED_TYPE_TRANSFER,
                $transferId,
                OrderChangeLog::OPERATOR_ADMIN,
                $adminId,
                'cancel',
                $beforeStatus,
                OrderTransfer::STATUS_CANCELLED,
                '管理员取消转让：' . ($reason ?: '无原因')
            );

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 重发验证码
     * @param int $transferId
     * @param int $adminId
     * @return bool
     */
    public static function resendCode(int $transferId, int $adminId): bool
    {
        [$success, $message] = OrderTransfer::sendAcceptCode($transferId);
        if (!$success) {
            self::setError($message);
            return false;
        }

        // 记录日志
        $transfer = OrderTransfer::find($transferId);
        OrderChangeLog::addLog(
            $transfer->order_id,
            OrderChangeLog::RELATED_TYPE_TRANSFER,
            $transferId,
            OrderChangeLog::OPERATOR_ADMIN,
            $adminId,
            'resend_code',
            $transfer->transfer_status,
            $transfer->transfer_status,
            '管理员重发接收验证码'
        );

        return true;
    }

    /**
     * @notes 获取转让操作日志
     * @param int $transferId
     * @return array
     */
    public static function logs(int $transferId): array
    {
        $transfer = OrderTransfer::find($transferId);
        if (!$transfer) {
            return [];
        }

        $logs = OrderChangeLog::where('order_id', $transfer->order_id)
            ->where('related_type', OrderChangeLog::RELATED_TYPE_TRANSFER)
            ->where('related_id', $transferId)
            ->order('create_time', 'desc')
            ->select()
            ->toArray();

        return $logs;
    }

    /**
     * @notes 转让统计
     * @param array $params
     * @return array
     */
    public static function statistics(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');

        $query = OrderTransfer::whereBetween('create_time', [$startTime, $endTime]);

        // 总申请数
        $totalTransfers = (clone $query)->count();

        // 各状态统计
        $statusCounts = [];
        foreach ([
            OrderTransfer::STATUS_PENDING => '待审核',
            OrderTransfer::STATUS_WAITING => '待接收',
            OrderTransfer::STATUS_ACCEPTED => '接收确认',
            OrderTransfer::STATUS_COMPLETED => '转让完成',
            OrderTransfer::STATUS_REJECTED => '已拒绝',
            OrderTransfer::STATUS_CANCELLED => '已取消',
        ] as $status => $label) {
            $statusCounts[] = [
                'status' => $status,
                'label' => $label,
                'count' => (clone $query)->where('transfer_status', $status)->count(),
            ];
        }

        // 待处理数量
        $pendingCount = OrderTransfer::whereIn('transfer_status', [
            OrderTransfer::STATUS_PENDING,
            OrderTransfer::STATUS_WAITING
        ])->count();

        // 完成数量（时间范围内）
        $completedCount = (clone $query)
            ->where('transfer_status', OrderTransfer::STATUS_COMPLETED)
            ->count();

        // 手续费统计
        $totalFee = (clone $query)
            ->where('transfer_status', OrderTransfer::STATUS_COMPLETED)
            ->sum('transfer_fee');

        // 今日数据
        $todayStart = strtotime(date('Y-m-d'));
        $todayTransfers = OrderTransfer::whereBetween('create_time', [$todayStart, time()])->count();
        $todayCompleted = OrderTransfer::where('transfer_status', OrderTransfer::STATUS_COMPLETED)
            ->whereBetween('complete_time', [$todayStart, time()])
            ->count();

        return [
            'total_transfers' => $totalTransfers,
            'status_counts' => $statusCounts,
            'pending_count' => $pendingCount,
            'completed_count' => $completedCount,
            'total_fee' => round($totalFee, 2),
            'today' => [
                'transfers' => $todayTransfers,
                'completed' => $todayCompleted,
            ],
        ];
    }

    /**
     * @notes 获取转让状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return OrderTransfer::getStatusOptions();
    }
}
