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
use app\common\model\order\RefundItem;
use app\common\service\OrderNotificationService;
use app\common\service\OrderRefundService;
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
        $refundQuery = Refund::with([
            'order' => function ($query) {
                $query->with(['user' => function ($q) {
                    $q->field('id, nickname, avatar, mobile');
                }]);
            }
        ]);

        if (RefundItem::isTableReady()) {
            $refundQuery = $refundQuery->with([
                'refundItems' => function ($query) {
                    $query->with(['payment']);
                }
            ]);
        }

        $refund = $refundQuery->find($id);

        if (!$refund) {
            return null;
        }

        $data = $refund->toArray();
        $data['refund_items'] = $data['refund_items'] ?? [];
        $data['refund_status_desc'] = $refund->refund_status_desc;
        $data['refund_type_desc'] = $refund->refund_type_desc;
        $data['can_confirm_offline'] = (int)$refund->can_confirm_offline;

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
        $refund = Refund::find($refundId);

        if ($refund) {
            if (!$approved) {
                OrderNotificationService::notifyUserOnRefundRejected($refundId);
            } else {
                switch ((int)$refund->refund_status) {
                    case Refund::STATUS_PROCESSING:
                        OrderNotificationService::notifyUserOnRefundApproved($refundId);
                        OrderNotificationService::notifyUserOnRefundProcessing($refundId);
                        break;
                    case Refund::STATUS_COMPLETED:
                        OrderNotificationService::notifyUserAndStaffOnRefundCompleted($refundId);
                        break;
                    case Refund::STATUS_FAILED:
                        OrderNotificationService::notifyUserOnRefundFailed($refundId);
                        break;
                    default:
                        OrderNotificationService::notifyUserOnRefundApproved($refundId);
                        break;
                }
            }
        }

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
            $refund = Refund::where('id', $refundId)->lock(true)->find();
            if (!$refund) {
                self::setError('退款记录不存在');
                Db::rollback();
                return false;
            }

            if (!in_array((int)$refund->refund_status, [Refund::STATUS_APPROVED, Refund::STATUS_PROCESSING], true)) {
                self::setError('当前状态不允许此操作');
                Db::rollback();
                return false;
            }

            if (!(int)$refund->can_confirm_offline) {
                self::setError('当前退款单不是线下人工退款单');
                Db::rollback();
                return false;
            }

            $beforeOrderStatus = (int)Order::where('id', (int)$refund->order_id)->value('order_status');
            [$success, $message] = OrderRefundService::confirmOfflineRefund($refund, $transactionId);
            if (!$success) {
                self::setError($message);
                Db::rollback();
                return false;
            }

            $refund = Refund::find($refundId);
            $order = Order::find((int)$refund->order_id);
            OrderLog::addLog(
                (int)$refund->order_id,
                OrderLog::OPERATOR_ADMIN,
                $adminId,
                'refund_success',
                $beforeOrderStatus,
                (int)($order->order_status ?? $beforeOrderStatus),
                '确认线下退款完成，金额：' . (float)($refund->actual_refund_amount ?: $refund->refund_amount)
            );

            Db::commit();

            OrderNotificationService::notifyUserAndStaffOnRefundCompleted($refundId);
            return true;
        } catch (\Throwable $e) {
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
        $notifyRefundId = 0;
        $notifyCompleted = false;
        $notifyFailed = false;

        Db::startTrans();
        try {
            $order = Order::where('id', (int)$params['order_id'])->lock(true)->find();
            if (!$order) {
                self::setError('订单不存在');
                Db::rollback();
                return false;
            }

            if (!in_array((int)$order->order_status, [Order::STATUS_PAID, Order::STATUS_IN_SERVICE], true)) {
                self::setError('当前订单状态不可退款');
                Db::rollback();
                return false;
            }

            $refundableAmount = OrderRefundService::getRefundableAmount((int)$order->id);
            if ($refundableAmount <= 0) {
                self::setError('当前订单暂无可退金额');
                Db::rollback();
                return false;
            }

            $existsRefund = Refund::where('order_id', (int)$params['order_id'])
                ->whereIn('refund_status', Refund::getPendingStatuses())
                ->lock(true)
                ->find();
            if ($existsRefund) {
                self::setError('存在未处理的退款申请');
                Db::rollback();
                return false;
            }

            $refundAmount = round((float)($params['refund_amount'] ?? $refundableAmount), 2);
            if ($refundAmount <= 0 || $refundAmount > $refundableAmount) {
                self::setError('退款金额不能超过当前可退金额');
                Db::rollback();
                return false;
            }

            $beforeStatus = (int)$order->order_status;
            $refund = Refund::create([
                'refund_sn' => Refund::generateRefundSn(),
                'order_id' => (int)$params['order_id'],
                'payment_id' => 0,
                'user_id' => (int)$order->user_id,
                'refund_type' => Refund::TYPE_ADMIN,
                'refund_amount' => $refundAmount,
                'actual_refund_amount' => 0,
                'refund_reason' => $params['reason'] ?? '管理员操作退款',
                'refund_status' => Refund::STATUS_APPROVED,
                'source_order_status' => $beforeStatus,
                'source_pay_status' => (int)$order->pay_status,
                'audit_admin_id' => (int)$params['admin_id'],
                'audit_time' => time(),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            OrderRefundService::moveOrderToRefunding($order);
            [$success, $message] = OrderRefundService::executeApprovedRefund($refund);
            $refund = Refund::find((int)$refund->id);
            $order = Order::find((int)$params['order_id']);

            OrderLog::addLog(
                (int)$params['order_id'],
                OrderLog::OPERATOR_ADMIN,
                (int)$params['admin_id'],
                'refund_apply',
                $beforeStatus,
                (int)($order->order_status ?? Order::STATUS_REFUNDING),
                '管理员发起退款：' . ($params['reason'] ?? '管理员操作')
                . (!$success && $message !== '' ? '，执行结果：' . $message : '')
            );

            $notifyRefundId = (int)$refund->id;
            $notifyCompleted = (int)$refund->refund_status === Refund::STATUS_COMPLETED;
            $notifyFailed = (int)$refund->refund_status === Refund::STATUS_FAILED;

            Db::commit();

            if ($notifyRefundId > 0) {
                OrderNotificationService::notifyUserAndStaffOnRefundApplied($notifyRefundId);
                if ($notifyCompleted) {
                    OrderNotificationService::notifyUserAndStaffOnRefundCompleted($notifyRefundId);
                } elseif ($notifyFailed) {
                    OrderNotificationService::notifyUserOnRefundFailed($notifyRefundId);
                } else {
                    OrderNotificationService::notifyUserOnRefundApproved($notifyRefundId);
                    OrderNotificationService::notifyUserOnRefundProcessing($notifyRefundId);
                }
            }

            if (!$success) {
                self::setError($message);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
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
        $totalRefunds = (clone $query)->count();

        $statusCounts = [];
        foreach ([
            Refund::STATUS_PENDING => Refund::getStatusText(Refund::STATUS_PENDING),
            Refund::STATUS_APPROVED => Refund::getStatusText(Refund::STATUS_APPROVED),
            Refund::STATUS_PROCESSING => Refund::getStatusText(Refund::STATUS_PROCESSING),
            Refund::STATUS_COMPLETED => Refund::getStatusText(Refund::STATUS_COMPLETED),
            Refund::STATUS_REJECTED => Refund::getStatusText(Refund::STATUS_REJECTED),
            Refund::STATUS_FAILED => Refund::getStatusText(Refund::STATUS_FAILED),
        ] as $status => $label) {
            $statusCounts[] = [
                'status' => $status,
                'label' => $label,
                'count' => (clone $query)->where('refund_status', $status)->count(),
            ];
        }

        $totalAmount = (clone $query)->sum('refund_amount');
        $completedAmount = (clone $query)->sum('actual_refund_amount');

        return [
            'total_refunds' => $totalRefunds,
            'status_counts' => $statusCounts,
            'total_amount' => round((float)$totalAmount, 2),
            'completed_amount' => round((float)$completedAmount, 2),
        ];
    }

    /**
     * @notes 获取退款状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => Refund::STATUS_PENDING, 'label' => Refund::getStatusText(Refund::STATUS_PENDING)],
            ['value' => Refund::STATUS_APPROVED, 'label' => Refund::getStatusText(Refund::STATUS_APPROVED)],
            ['value' => Refund::STATUS_PROCESSING, 'label' => Refund::getStatusText(Refund::STATUS_PROCESSING)],
            ['value' => Refund::STATUS_COMPLETED, 'label' => Refund::getStatusText(Refund::STATUS_COMPLETED)],
            ['value' => Refund::STATUS_REJECTED, 'label' => Refund::getStatusText(Refund::STATUS_REJECTED)],
            ['value' => Refund::STATUS_FAILED, 'label' => Refund::getStatusText(Refund::STATUS_FAILED)],
        ];
    }
}
