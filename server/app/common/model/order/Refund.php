<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 退款记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;
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

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @notes 退款状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getRefundStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '审核通过',
            self::STATUS_PROCESSING => '退款中',
            self::STATUS_COMPLETED => '已退款',
            self::STATUS_REJECTED => '已拒绝',
        ];
        return $map[$data['refund_status']] ?? '未知';
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
     * @param float $refundAmount
     * @param string $reason
     * @param int $paymentId
     * @return array [bool $success, string $message, Refund|null $refund]
     */
    public static function applyRefund(int $orderId, int $userId, float $refundAmount, string $reason, int $paymentId = 0): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return [false, '订单不存在', null];
        }

        if ($order->user_id != $userId) {
            return [false, '无权操作此订单', null];
        }

        if (!in_array($order->order_status, [Order::STATUS_PAID, Order::STATUS_IN_SERVICE])) {
            return [false, '当前订单状态不可申请退款', null];
        }

        // 检查是否有未处理的退款申请
        $existsRefund = self::where('order_id', $orderId)
            ->whereIn('refund_status', [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_PROCESSING])
            ->find();
        
        if ($existsRefund) {
            return [false, '存在未处理的退款申请', null];
        }

        // 验证退款金额
        if ($refundAmount <= 0 || $refundAmount > $order->pay_amount) {
            return [false, '退款金额不合法', null];
        }

        try {
            $refund = self::create([
                'refund_sn' => self::generateRefundSn(),
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'user_id' => $userId,
                'refund_type' => self::TYPE_USER,
                'refund_amount' => $refundAmount,
                'refund_reason' => $reason,
                'refund_status' => self::STATUS_PENDING,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 记录日志
            OrderLog::addLog($orderId, OrderLog::OPERATOR_USER, $userId, 'refund_apply', $order->order_status, $order->order_status, '申请退款：' . $reason);

            return [true, '退款申请已提交', $refund];
        } catch (\Exception $e) {
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
     * @return array [bool $success, string $message, Refund|null $refund]
     */
    public static function createSystemRefund(int $orderId, int $operatorId, float $refundAmount, string $reason, int $refundType = self::TYPE_SYSTEM): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return [false, '订单不存在', null];
        }

        // 检查是否有未处理的退款申请
        $existsRefund = self::where('order_id', $orderId)
            ->whereIn('refund_status', [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_PROCESSING])
            ->find();
        
        if ($existsRefund) {
            return [false, '存在未处理的退款申请', null];
        }

        // 验证退款金额
        if ($refundAmount <= 0 || $refundAmount > $order->pay_amount) {
            $refundAmount = $order->paid_amount > 0 ? $order->paid_amount : $order->pay_amount;
        }

        try {
            $refund = self::create([
                'refund_sn' => self::generateRefundSn(),
                'order_id' => $orderId,
                'payment_id' => 0,
                'user_id' => $order->user_id,
                'refund_type' => $refundType,
                'refund_amount' => $refundAmount,
                'refund_reason' => $reason,
                'refund_status' => self::STATUS_PENDING, // 待审核，管理员审核后执行退款
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 记录日志
            $operatorType = $refundType == self::TYPE_USER ? OrderLog::OPERATOR_USER : OrderLog::OPERATOR_SYSTEM;
            OrderLog::addLog($orderId, $operatorType, $operatorId, 'refund_create', $order->order_status, $order->order_status, '创建退款申请：' . $reason);

            return [true, '退款申请已创建', $refund];
        } catch (\Exception $e) {
            return [false, '创建失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 审核退款
     * @param int $refundId
     * @param int $adminId
     * @param bool $approved
     * @param string $remark
     * @return array [bool $success, string $message]
     */
    public static function auditRefund(int $refundId, int $adminId, bool $approved, string $remark = ''): array
    {
        Db::startTrans();
        try {
            $refund = self::find($refundId);
            if (!$refund) {
                return [false, '退款记录不存在'];
            }

            if ($refund->refund_status != self::STATUS_PENDING) {
                return [false, '当前状态不可审核'];
            }

            $refund->refund_status = $approved ? self::STATUS_APPROVED : self::STATUS_REJECTED;
            $refund->audit_admin_id = $adminId;
            $refund->audit_time = time();
            $refund->audit_remark = $remark;
            $refund->update_time = time();
            $refund->save();

            // 记录日志
            OrderLog::addLog(
                $refund->order_id,
                OrderLog::OPERATOR_ADMIN,
                $adminId,
                'refund_audit',
                0,
                0,
                ($approved ? '审核通过' : '审核拒绝') . ($remark ? '：' . $remark : '')
            );

            // 审核通过后执行退款
            if ($approved) {
                // TODO: 调用第三方支付退款接口
                $refund->refund_status = self::STATUS_PROCESSING;
                $refund->save();
            }

            Db::commit();
            return [true, $approved ? '审核通过' : '已拒绝'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '审核失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 退款完成
     * @param int $refundId
     * @param string $transactionId
     * @return array [bool $success, string $message]
     */
    public static function refundComplete(int $refundId, string $transactionId = ''): array
    {
        Db::startTrans();
        try {
            $refund = self::find($refundId);
            if (!$refund) {
                return [false, '退款记录不存在'];
            }

            $refund->refund_status = self::STATUS_COMPLETED;
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
                OrderLog::OPERATOR_SYSTEM,
                0,
                'refund_success',
                0,
                0,
                '退款成功，金额：' . $refund->refund_amount
            );

            Db::commit();
            return [true, '退款完成'];
        } catch (\Exception $e) {
            Db::rollback();
            return [false, '退款失败：' . $e->getMessage()];
        }
    }
}
