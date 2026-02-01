<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 支付记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\order;

use app\common\model\BaseModel;

/**
 * 支付记录模型
 * Class Payment
 * @package app\common\model\order
 */
class Payment extends BaseModel
{
    protected $name = 'payment';

    // 支付类型
    const TYPE_DEPOSIT = 1;     // 定金
    const TYPE_BALANCE = 2;     // 尾款
    const TYPE_FULL = 3;        // 全款

    // 支付方式
    const WAY_WECHAT = 1;   // 微信
    const WAY_ALIPAY = 2;   // 支付宝
    const WAY_BALANCE = 3;  // 余额
    const WAY_OFFLINE = 4;  // 线下

    // 支付状态
    const STATUS_PENDING = 0;   // 待支付
    const STATUS_PAID = 1;      // 已支付
    const STATUS_REFUNDED = 2;  // 已退款
    const STATUS_FAILED = 3;    // 支付失败

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @notes 支付类型描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayTypeDescAttr($value, $data): string
    {
        $map = [
            self::TYPE_DEPOSIT => '定金',
            self::TYPE_BALANCE => '尾款',
            self::TYPE_FULL => '全款',
        ];
        return $map[$data['pay_type']] ?? '未知';
    }

    /**
     * @notes 支付方式描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayWayDescAttr($value, $data): string
    {
        $map = [
            self::WAY_WECHAT => '微信支付',
            self::WAY_ALIPAY => '支付宝',
            self::WAY_BALANCE => '余额支付',
            self::WAY_OFFLINE => '线下支付',
        ];
        return $map[$data['pay_way']] ?? '未知';
    }

    /**
     * @notes 支付状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPayStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待支付',
            self::STATUS_PAID => '已支付',
            self::STATUS_REFUNDED => '已退款',
            self::STATUS_FAILED => '支付失败',
        ];
        return $map[$data['pay_status']] ?? '未知';
    }

    /**
     * @notes 生成支付流水号
     * @return string
     */
    public static function generatePaymentSn(): string
    {
        return 'PAY' . date('YmdHis') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @notes 创建支付记录
     * @param int $orderId
     * @param string $orderSn
     * @param int $userId
     * @param int $payType
     * @param int $payWay
     * @param float $payAmount
     * @param int $expireMinutes 过期时间(分钟)
     * @return Payment
     */
    public static function createPayment(int $orderId, string $orderSn, int $userId, int $payType, int $payWay, float $payAmount, int $expireMinutes = 30): Payment
    {
        return self::create([
            'payment_sn' => self::generatePaymentSn(),
            'order_id' => $orderId,
            'order_sn' => $orderSn,
            'user_id' => $userId,
            'pay_type' => $payType,
            'pay_way' => $payWay,
            'pay_amount' => $payAmount,
            'pay_status' => self::STATUS_PENDING,
            'expire_time' => time() + ($expireMinutes * 60),
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    /**
     * @notes 支付成功回调
     * @param string $paymentSn
     * @param string $transactionId
     * @param array $callbackData
     * @return array [bool $success, string $message]
     */
    public static function paySuccess(string $paymentSn, string $transactionId, array $callbackData = []): array
    {
        $payment = self::where('payment_sn', $paymentSn)->find();
        if (!$payment) {
            return [false, '支付记录不存在'];
        }

        if ($payment->pay_status == self::STATUS_PAID) {
            return [true, '已处理'];
        }

        $payment->pay_status = self::STATUS_PAID;
        $payment->transaction_id = $transactionId;
        $payment->pay_time = time();
        $payment->callback_time = time();
        $payment->callback_data = json_encode($callbackData, JSON_UNESCAPED_UNICODE);
        $payment->update_time = time();
        $payment->save();

        // 更新订单状态
        $order = Order::find($payment->order_id);
        if ($order) {
            if ($payment->pay_type == self::TYPE_DEPOSIT) {
                $order->deposit_paid = 1;
                $order->pay_time = time();
            } elseif ($payment->pay_type == self::TYPE_BALANCE) {
                $order->balance_paid = 1;
            }

            // 累计已支付金额
            $order->paid_amount = round((float)($order->paid_amount ?? 0) + (float)$payment->pay_amount, 2);

            // 检查是否全额支付
            if ($order->deposit_amount > 0) {
                if ($order->deposit_paid && $order->balance_paid) {
                    $order->order_status = Order::STATUS_PAID;
                    $order->pay_status = Order::PAY_STATUS_PAID;
                }
            } else {
                if ($order->paid_amount >= $order->pay_amount) {
                    $order->order_status = Order::STATUS_PAID;
                    $order->pay_status = Order::PAY_STATUS_PAID;
                }
            }

            if ($order->pay_type != Order::PAY_WAY_COMBINATION) {
                $order->pay_type = $payment->pay_way;
            }
            $order->update_time = time();
            $order->save();

            // 记录日志
            OrderLog::addLog(
                $order->id,
                OrderLog::OPERATOR_SYSTEM,
                0,
                $payment->pay_type == self::TYPE_DEPOSIT ? 'pay_deposit' : ($payment->pay_type == self::TYPE_BALANCE ? 'pay_balance' : 'pay'),
                Order::STATUS_PENDING_PAY,
                $order->order_status,
                '支付成功，金额：' . $payment->pay_amount
            );
        }

        return [true, '支付成功'];
    }
}
