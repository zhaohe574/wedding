<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 退款业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\order;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderLog;
use app\common\model\order\Refund;
use think\facade\Db;

/**
 * 退款业务逻辑
 * Class RefundLogic
 * @package app\adminapi\logic\order
 */
class RefundLogic extends BaseLogic
{
    /**
     * @notes 获取退款详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $refund = Refund::with([
            'order' => function ($query) {
                $query->with(['user' => function ($q) {
                    $q->field('id, nickname, avatar, mobile');
                }]);
            }
        ])->find($id);

        if (!$refund) {
            return null;
        }

        $data = $refund->toArray();
        $data['refund_status_desc'] = $refund->refund_status_desc;

        return $data;
    }

    /**
     * @notes 审核退款
     * @param int $refundId
     * @param int $adminId
     * @param bool $approved
     * @param string $remark
     * @return bool
     */
    public static function audit(int $refundId, int $adminId, bool $approved, string $remark = ''): bool
    {
        [$success, $message] = Refund::auditRefund($refundId, $adminId, $approved, $remark);
        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 确认退款完成（线下退款）
     * @param int $refundId
     * @param int $adminId
     * @param string $transactionId
     * @return bool
     */
    public static function confirmRefund(int $refundId, int $adminId, string $transactionId = ''): bool
    {
        Db::startTrans();
        try {
            $refund = Refund::find($refundId);
            if (!$refund) {
                self::setError('退款记录不存在');
                return false;
            }

            if (!in_array($refund->refund_status, [Refund::STATUS_APPROVED, Refund::STATUS_PROCESSING])) {
                self::setError('当前状态不允许此操作');
                return false;
            }

            // 更新退款记录
            $refund->refund_status = Refund::STATUS_COMPLETED;
            $refund->refund_transaction_id = $transactionId;
            $refund->refund_time = time();
            $refund->update_time = time();
            $refund->save();

            // 更新订单状态
            $order = Order::find($refund->order_id);
            if ($order) {
                if ($refund->refund_amount >= $order->pay_amount) {
                    $order->order_status = Order::STATUS_REFUNDED;
                    $order->pay_status = Order::PAY_STATUS_FULL_REFUND;
                } else {
                    $order->pay_status = Order::PAY_STATUS_PARTIAL_REFUND;
                }
                $order->update_time = time();
                $order->save();
            }

            // 记录日志
            OrderLog::addLog(
                $refund->order_id,
                OrderLog::OPERATOR_ADMIN,
                $adminId,
                'refund_success',
                0,
                0,
                '确认退款完成，金额：' . $refund->refund_amount
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
     * @notes 管理员发起退款
     * @param array $params
     * @return bool
     */
    public static function adminApply(array $params): bool
    {
        Db::startTrans();
        try {
            $order = Order::find($params['order_id']);
            if (!$order) {
                self::setError('订单不存在');
                return false;
            }

            if (!in_array($order->order_status, [Order::STATUS_PAID, Order::STATUS_IN_SERVICE])) {
                self::setError('当前订单状态不可退款');
                return false;
            }

            // 检查是否有未处理的退款申请
            $existsRefund = Refund::where('order_id', $params['order_id'])
                ->whereIn('refund_status', [Refund::STATUS_PENDING, Refund::STATUS_APPROVED, Refund::STATUS_PROCESSING])
                ->find();
            
            if ($existsRefund) {
                self::setError('存在未处理的退款申请');
                return false;
            }

            $refundAmount = $params['refund_amount'] ?? $order->pay_amount;
            if ($refundAmount <= 0 || $refundAmount > $order->pay_amount) {
                self::setError('退款金额不合法');
                return false;
            }

            // 创建退款记录（管理员发起的退款直接审核通过）
            $refund = Refund::create([
                'refund_sn' => Refund::generateRefundSn(),
                'order_id' => $params['order_id'],
                'user_id' => $order->user_id,
                'refund_type' => Refund::TYPE_ADMIN,
                'refund_amount' => $refundAmount,
                'refund_reason' => $params['reason'] ?? '管理员操作退款',
                'refund_status' => Refund::STATUS_APPROVED,
                'audit_admin_id' => $params['admin_id'],
                'audit_time' => time(),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 记录日志
            OrderLog::addLog(
                $params['order_id'],
                OrderLog::OPERATOR_ADMIN,
                $params['admin_id'],
                'refund_apply',
                $order->order_status,
                $order->order_status,
                '管理员发起退款：' . ($params['reason'] ?? '管理员操作')
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
     * @notes 退款统计
     * @param array $params
     * @return array
     */
    public static function statistics(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');

        $query = Refund::whereBetween('create_time', [$startTime, $endTime]);

        // 总申请数
        $totalRefunds = (clone $query)->count();

        // 各状态统计
        $statusCounts = [];
        foreach ([
            Refund::STATUS_PENDING => '待审核',
            Refund::STATUS_APPROVED => '审核通过',
            Refund::STATUS_PROCESSING => '退款中',
            Refund::STATUS_COMPLETED => '已退款',
            Refund::STATUS_REJECTED => '已拒绝',
        ] as $status => $label) {
            $statusCounts[] = [
                'status' => $status,
                'label' => $label,
                'count' => (clone $query)->where('refund_status', $status)->count(),
            ];
        }

        // 退款金额
        $totalAmount = (clone $query)->sum('refund_amount');
        $completedAmount = (clone $query)->where('refund_status', Refund::STATUS_COMPLETED)->sum('refund_amount');

        return [
            'total_refunds' => $totalRefunds,
            'status_counts' => $statusCounts,
            'total_amount' => round($totalAmount, 2),
            'completed_amount' => round($completedAmount, 2),
        ];
    }

    /**
     * @notes 获取退款状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => Refund::STATUS_PENDING, 'label' => '待审核'],
            ['value' => Refund::STATUS_APPROVED, 'label' => '审核通过'],
            ['value' => Refund::STATUS_PROCESSING, 'label' => '退款中'],
            ['value' => Refund::STATUS_COMPLETED, 'label' => '已退款'],
            ['value' => Refund::STATUS_REJECTED, 'label' => '已拒绝'],
        ];
    }
}
