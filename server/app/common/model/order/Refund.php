<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 退款记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;
use app\common\service\OrderRefundService;
use think\facade\Db;

/**
 * 退款记录模型
 * Class Refund
 * @package app\common\model\order
 */
class Refund extends BaseModel
{
    protected $name = 'refund';

    // 退款类型
    const TYPE_USER = 1;    // 用户申请
    const TYPE_ADMIN = 2;   // 管理员操作
    const TYPE_SYSTEM = 3;  // 系统自动

    // 退款状态
    const STATUS_PENDING = 0;       // 待审核
    const STATUS_APPROVED = 1;      // 审核通过
    const STATUS_PROCESSING = 2;    // 退款中
    const STATUS_COMPLETED = 3;     // 已退款
    const STATUS_REJECTED = 4;      // 已拒绝
    const STATUS_FAILED = 5;        // 退款失败

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @notes 关联退款子项
     * @return \think\model\relation\HasMany
     */
    public function refundItems()
    {
        return $this->hasMany(RefundItem::class, 'refund_id', 'id');
    }

    /**
     * @notes 退款状态描述获取器
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getRefundStatusDescAttr($value, $data): string
    {
        return self::getStatusText((int)($data['refund_status'] ?? -1));
    }

    /**
     * @notes 退款类型描述获取器
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getRefundTypeDescAttr($value, $data): string
    {
        return self::getTypeText((int)($data['refund_type'] ?? -1));
    }

    /**
     * @notes 是否允许线下确认
     * @param mixed $value
     * @param array $data
     * @return int
     */
    public function getCanConfirmOfflineAttr($value, $data): int
    {
        $refundId = (int)($data['id'] ?? 0);
        if ($refundId <= 0) {
            return 0;
        }

        if (!RefundItem::isTableReady()) {
            return 0;
        }

        $items = RefundItem::where('refund_id', $refundId)->select();
        if ($items->isEmpty()) {
            return 0;
        }

        foreach ($items as $item) {
            if ((int)$item->pay_way !== Payment::WAY_OFFLINE) {
                return 0;
            }
        }

        return 1;
    }

    /**
     * @notes 获取退款状态文案
     * @param int $status
     * @return string
     */
    public static function getStatusText(int $status): string
    {
        $map = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '审核通过',
            self::STATUS_PROCESSING => '退款中',
            self::STATUS_COMPLETED => '已退款',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_FAILED => '退款失败',
        ];

        return $map[$status] ?? '未知';
    }

    /**
     * @notes 获取退款类型文案
     * @param int $type
     * @return string
     */
    public static function getTypeText(int $type): string
    {
        $map = [
            self::TYPE_USER => '用户申请',
            self::TYPE_ADMIN => '管理员操作',
            self::TYPE_SYSTEM => '系统自动',
        ];

        return $map[$type] ?? '未知';
    }

    /**
     * @notes 获取未完成退款状态
     * @return array
     */
    public static function getPendingStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_PROCESSING,
        ];
    }

    /**
     * @notes 生成退款编号
     * @return string
     */
    public static function generateRefundSn(): string
    {
        return 'REF' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 申请退款
     * @param int $orderId
     * @param int $userId
     * @param string $reason
     * @param int $paymentId
     * @return array [bool, string, Refund|null]
     */
    public static function applyRefund(int $orderId, int $userId, string $reason, int $paymentId = 0): array
    {
        Db::startTrans();
        try {
            $order = Order::where('id', $orderId)->lock(true)->find();
            if (!$order) {
                Db::rollback();
                return [false, '订单不存在', null];
            }

            if ((int)$order->user_id !== $userId) {
                Db::rollback();
                return [false, '无权操作此订单', null];
            }

            if (!in_array((int)$order->order_status, [Order::STATUS_PAID, Order::STATUS_IN_SERVICE], true)) {
                Db::rollback();
                return [false, '当前订单状态不可申请退款', null];
            }

            $refundAmount = OrderRefundService::getRefundableAmount((int)$order->id);
            $validateMessage = self::validateRefundRequest($order, $refundAmount);
            if ($validateMessage !== '') {
                Db::rollback();
                return [false, $validateMessage, null];
            }

            $beforeStatus = (int)$order->order_status;
            $refund = self::create([
                'refund_sn' => self::generateRefundSn(),
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'user_id' => $userId,
                'refund_type' => self::TYPE_USER,
                'refund_amount' => $refundAmount,
                'actual_refund_amount' => 0,
                'refund_reason' => $reason,
                'refund_status' => self::STATUS_PENDING,
                'source_order_status' => $beforeStatus,
                'source_pay_status' => (int)$order->pay_status,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            OrderRefundService::moveOrderToRefunding($order);
            OrderLog::addLog(
                $orderId,
                OrderLog::OPERATOR_USER,
                $userId,
                'refund_apply',
                $beforeStatus,
                Order::STATUS_REFUNDING,
                '申请退款：' . $reason
            );

            Db::commit();
            return [true, '退款申请已提交', $refund];
        } catch (\Throwable $e) {
            Db::rollback();
            return [false, '申请失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 系统自动创建退款（订单取消时使用）
     * @param int $orderId
     * @param int $operatorId
     * @param float $refundAmount
     * @param string $reason
     * @param int $refundType
     * @return array [bool, string, Refund|null]
     */
    public static function createSystemRefund(int $orderId, int $operatorId, float $refundAmount, string $reason, int $refundType = self::TYPE_SYSTEM): array
    {
        Db::startTrans();
        try {
            $order = Order::where('id', $orderId)->lock(true)->find();
            if (!$order) {
                Db::rollback();
                return [false, '订单不存在', null];
            }

            $refundAmount = round($refundAmount, 2);
            $refundableAmount = OrderRefundService::getRefundableAmount((int)$order->id);
            if ($refundAmount <= 0 || $refundAmount > $refundableAmount) {
                $refundAmount = $refundableAmount;
            }

            $validateMessage = self::validateRefundRequest($order, $refundAmount);
            if ($validateMessage !== '') {
                Db::rollback();
                return [false, $validateMessage, null];
            }

            $beforeStatus = (int)$order->order_status;
            $refund = self::create([
                'refund_sn' => self::generateRefundSn(),
                'order_id' => $orderId,
                'payment_id' => 0,
                'user_id' => (int)$order->user_id,
                'refund_type' => $refundType,
                'refund_amount' => $refundAmount,
                'actual_refund_amount' => 0,
                'refund_reason' => $reason,
                'refund_status' => self::STATUS_PENDING,
                'source_order_status' => $beforeStatus,
                'source_pay_status' => (int)$order->pay_status,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            OrderRefundService::moveOrderToRefunding($order);
            $operatorType = $refundType === self::TYPE_USER ? OrderLog::OPERATOR_USER : OrderLog::OPERATOR_SYSTEM;
            OrderLog::addLog(
                $orderId,
                $operatorType,
                $operatorId,
                'refund_create',
                $beforeStatus,
                Order::STATUS_REFUNDING,
                '创建退款申请：' . $reason
            );

            Db::commit();
            return [true, '退款申请已创建', $refund];
        } catch (\Throwable $e) {
            Db::rollback();
            return [false, '创建失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 审核退款
     * @param int $refundId
     * @param int $adminId
     * @param bool $approved
     * @param string $remark
     * @return array [bool, string]
     */
    public static function auditRefund(int $refundId, int $adminId, bool $approved, string $remark = ''): array
    {
        Db::startTrans();
        try {
            $refund = self::where('id', $refundId)->lock(true)->find();
            if (!$refund) {
                Db::rollback();
                return [false, '退款记录不存在'];
            }

            if ((int)$refund->refund_status !== self::STATUS_PENDING) {
                Db::rollback();
                return [false, '当前状态不可审核'];
            }

            $order = Order::where('id', (int)$refund->order_id)->lock(true)->find();
            if (!$order) {
                Db::rollback();
                return [false, '订单不存在'];
            }

            $beforeOrderStatus = (int)$order->order_status;
            $refund->audit_admin_id = $adminId;
            $refund->audit_time = time();
            $refund->audit_remark = $remark;
            $refund->refund_status = $approved ? self::STATUS_APPROVED : self::STATUS_REJECTED;
            $refund->update_time = time();
            $refund->save();

            if (!$approved) {
                $order->order_status = self::normalizeSourceOrderStatus((int)$refund->source_order_status);
                $order->pay_status = self::normalizeSourcePayStatus((int)$refund->source_pay_status);
                $order->update_time = time();
                $order->save();

                OrderLog::addLog(
                    (int)$refund->order_id,
                    OrderLog::OPERATOR_ADMIN,
                    $adminId,
                    'refund_audit',
                    $beforeOrderStatus,
                    (int)$order->order_status,
                    '审核拒绝' . ($remark !== '' ? '：' . $remark : '')
                );

                Db::commit();
                return [true, '已拒绝'];
            }

            [$success, $message] = OrderRefundService::executeApprovedRefund($refund);
            $refund = self::find($refundId);
            $order = Order::find((int)$refund->order_id);

            OrderLog::addLog(
                (int)$refund->order_id,
                OrderLog::OPERATOR_ADMIN,
                $adminId,
                'refund_audit',
                $beforeOrderStatus,
                (int)($order->order_status ?? $beforeOrderStatus),
                ($success ? '审核通过' : '审核通过后执行失败')
                . ($remark !== '' ? '：' . $remark : '')
                . (!$success && $message !== '' ? '，原因：' . $message : '')
            );

            Db::commit();
            return [$success, $success ? '审核通过' : $message];
        } catch (\Throwable $e) {
            Db::rollback();
            return [false, '审核失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 校验退款请求
     * @param Order $order
     * @param float $refundAmount
     * @return string
     */
    protected static function validateRefundRequest(Order $order, float $refundAmount): string
    {
        $existsRefund = self::where('order_id', (int)$order->id)
            ->whereIn('refund_status', self::getPendingStatuses())
            ->lock(true)
            ->find();
        if ($existsRefund) {
            return '存在未处理的退款申请';
        }

        if ($refundAmount <= 0) {
            return '退款金额必须大于0';
        }

        $refundableAmount = OrderRefundService::getRefundableAmount((int)$order->id);
        if ($refundableAmount <= 0) {
            return '当前订单暂无可退金额';
        }

        if ($refundAmount > $refundableAmount) {
            return '退款金额不能超过当前可退金额';
        }

        return '';
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

        if (in_array($status, $validStatus, true) && $status !== Order::STATUS_REFUNDING) {
            return $status;
        }

        return Order::STATUS_PAID;
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
}
