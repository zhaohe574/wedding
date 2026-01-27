<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订单逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderLog;
use app\common\model\order\Payment;
use app\common\model\order\Refund;
use app\common\model\cart\Cart;
use app\common\model\schedule\Schedule;
use think\facade\Db;

/**
 * 小程序端订单逻辑
 * Class OrderLogic
 * @package app\api\logic
 */
class OrderLogic extends BaseLogic
{
    /**
     * @notes 获取用户订单列表
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getUserOrders(int $userId, array $params): array
    {
        $query = Order::where('user_id', $userId);

        // 状态筛选
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('order_status', $params['status']);
        }

        // 搜索
        if (!empty($params['keyword'])) {
            $query->where('order_sn|contact_name|contact_mobile', 'like', '%' . $params['keyword'] . '%');
        }

        $list = $query->with(['items' => function ($q) {
                $q->field('id, order_id, staff_id, staff_name, package_name, service_date, time_slot, price, quantity, subtotal');
            }])
            ->order('id', 'desc')
            ->paginate((int)($params['page_size'] ?? 10))
            ->toArray();

        // 添加状态描述
        foreach ($list['data'] as &$item) {
            $item['order_status_desc'] = self::getStatusDesc($item['order_status']);
            $item['pay_status_desc'] = self::getPayStatusDesc($item['pay_status']);
        }

        return $list;
    }

    /**
     * @notes 获取订单详情
     * @param int $orderId
     * @param int $userId
     * @return array|null
     */
    public static function getOrderDetail(int $orderId, int $userId): ?array
    {
        $order = Order::with([
            'items' => function ($query) {
                $query->with(['staff' => function ($q) {
                    $q->field('id, name, avatar');
                }]);
            },
            'payments' => function ($query) {
                $query->where('pay_status', Payment::STATUS_PAID);
            },
            'logs' => function ($query) {
                $query->order('create_time', 'desc')->limit(10);
            }
        ])->where('user_id', $userId)->find($orderId);

        if (!$order) {
            return null;
        }

        $data = $order->toArray();
        $data['order_status_desc'] = self::getStatusDesc($order->order_status);
        $data['pay_status_desc'] = self::getPayStatusDesc($order->pay_status);
        $data['pay_type_desc'] = self::getPayTypeDesc($order->pay_type);

        // 获取退款信息
        $refund = Refund::where('order_id', $orderId)->order('id', 'desc')->find();
        $data['refund'] = $refund ? $refund->toArray() : null;

        return $data;
    }

    /**
     * @notes 订单预览
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function previewOrder(int $userId, array $params): array
    {
        // 获取选中的购物车项
        $cartIds = $params['cart_ids'] ?? [];
        if (empty($cartIds)) {
            $cartItems = Cart::where('user_id', $userId)
                ->where('is_selected', 1)
                ->with(['staff', 'package'])
                ->select()
                ->toArray();
        } else {
            $cartItems = Cart::where('user_id', $userId)
                ->whereIn('id', $cartIds)
                ->with(['staff', 'package'])
                ->select()
                ->toArray();
        }

        if (empty($cartItems)) {
            return ['success' => false, 'message' => '请选择要结算的服务'];
        }

        // 计算金额
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            $totalAmount += $item['price'] * ($item['quantity'] ?? 1);
        }

        // 优惠券
        $couponAmount = 0;
        $couponId = $params['coupon_id'] ?? 0;
        if ($couponId > 0) {
            // TODO: 计算优惠券抵扣
        }

        $payAmount = $totalAmount - $couponAmount;

        // 定金计算
        $depositRatio = $params['deposit_ratio'] ?? 0;
        $depositAmount = 0;
        $balanceAmount = 0;
        if ($depositRatio > 0) {
            $depositAmount = round($payAmount * $depositRatio / 100, 2);
            $balanceAmount = $payAmount - $depositAmount;
        }

        return [
            'success' => true,
            'data' => [
                'items' => $cartItems,
                'total_amount' => round($totalAmount, 2),
                'coupon_amount' => round($couponAmount, 2),
                'pay_amount' => round($payAmount, 2),
                'deposit_amount' => round($depositAmount, 2),
                'balance_amount' => round($balanceAmount, 2),
            ]
        ];
    }

    /**
     * @notes 创建订单
     * @param array $params
     * @return array
     */
    public static function createOrder(array $params): array
    {
        Db::startTrans();
        try {
            $userId = $params['user_id'];

            // 获取购物车项
            $cartIds = $params['cart_ids'] ?? [];
            if (empty($cartIds)) {
                $cartItems = Cart::where('user_id', $userId)
                    ->where('is_selected', 1)
                    ->with(['staff', 'package'])
                    ->select()
                    ->toArray();
            } else {
                $cartItems = Cart::where('user_id', $userId)
                    ->whereIn('id', $cartIds)
                    ->with(['staff', 'package'])
                    ->select()
                    ->toArray();
            }

            if (empty($cartItems)) {
                return ['success' => false, 'message' => '请选择要结算的服务'];
            }

            // 检查档期是否仍然可用
            foreach ($cartItems as $item) {
                if (!empty($item['schedule_id'])) {
                    $schedule = Schedule::find($item['schedule_id']);
                    if (!$schedule || !in_array($schedule->status, [Schedule::STATUS_AVAILABLE, Schedule::STATUS_LOCKED])) {
                        return ['success' => false, 'message' => '部分档期已不可用，请刷新购物车'];
                    }
                }
            }

            // 创建订单
            [$success, $message, $order] = Order::createOrder($userId, $cartItems, $params);
            
            if (!$success) {
                Db::rollback();
                return ['success' => false, 'message' => $message];
            }

            // 清空已结算的购物车项
            Cart::where('user_id', $userId)->whereIn('id', array_column($cartItems, 'id'))->delete();

            Db::commit();
            return [
                'success' => true,
                'message' => '订单创建成功',
                'order_id' => $order->id,
                'order_sn' => $order->order_sn,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'message' => '创建失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 取消订单
     * @param int $orderId
     * @param int $userId
     * @param string $reason
     * @return array
     */
    public static function cancelOrder(int $orderId, int $userId, string $reason = ''): array
    {
        $order = Order::where('user_id', $userId)->find($orderId);
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在'];
        }

        [$success, $message] = Order::cancelOrder($orderId, $userId, OrderLog::OPERATOR_USER, $reason);
        return ['success' => $success, 'message' => $message];
    }

    /**
     * @notes 确认完成
     * @param int $orderId
     * @param int $userId
     * @return array
     */
    public static function confirmComplete(int $orderId, int $userId): array
    {
        $order = Order::where('user_id', $userId)->find($orderId);
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在'];
        }

        [$success, $message] = Order::completeOrder($orderId, $userId, OrderLog::OPERATOR_USER);
        return ['success' => $success, 'message' => $message];
    }

    /**
     * @notes 删除订单（软删除）
     * @param int $orderId
     * @param int $userId
     * @return array
     */
    public static function deleteOrder(int $orderId, int $userId): array
    {
        $order = Order::where('user_id', $userId)->find($orderId);
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在'];
        }

        if (!in_array($order->order_status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])) {
            return ['success' => false, 'message' => '只能删除已完成、已取消或已退款的订单'];
        }

        $order->delete();
        return ['success' => true, 'message' => '删除成功'];
    }

    /**
     * @notes 获取支付信息
     * @param int $orderId
     * @param int $userId
     * @return array|null
     */
    public static function getPayInfo(int $orderId, int $userId): ?array
    {
        $order = Order::where('user_id', $userId)->find($orderId);
        if (!$order) {
            return null;
        }

        $info = [
            'order_id' => $order->id,
            'order_sn' => $order->order_sn,
            'pay_amount' => $order->pay_amount,
            'deposit_amount' => $order->deposit_amount,
            'balance_amount' => $order->balance_amount,
            'deposit_paid' => $order->deposit_paid,
            'balance_paid' => $order->balance_paid,
            'pay_status' => $order->pay_status,
            'order_status' => $order->order_status,
        ];

        // 需要支付的金额
        if ($order->deposit_amount > 0) {
            if (!$order->deposit_paid) {
                $info['need_pay'] = 'deposit';
                $info['need_pay_amount'] = $order->deposit_amount;
            } elseif (!$order->balance_paid) {
                $info['need_pay'] = 'balance';
                $info['need_pay_amount'] = $order->balance_amount;
            } else {
                $info['need_pay'] = 'none';
                $info['need_pay_amount'] = 0;
            }
        } else {
            $info['need_pay'] = $order->pay_status == Order::PAY_STATUS_UNPAID ? 'full' : 'none';
            $info['need_pay_amount'] = $order->pay_status == Order::PAY_STATUS_UNPAID ? $order->pay_amount : 0;
        }

        return $info;
    }

    /**
     * @notes 创建支付
     * @param array $params
     * @return array
     */
    public static function createPayment(array $params): array
    {
        $order = Order::where('user_id', $params['user_id'])->find($params['id']);
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在'];
        }

        if ($order->order_status != Order::STATUS_PENDING) {
            return ['success' => false, 'message' => '订单状态不允许支付'];
        }

        $payType = $params['pay_type'] ?? Payment::TYPE_FULL;
        $payWay = $params['pay_way'] ?? Payment::WAY_WECHAT;

        // 计算支付金额
        if ($payType == Payment::TYPE_DEPOSIT) {
            if ($order->deposit_paid) {
                return ['success' => false, 'message' => '定金已支付'];
            }
            $payAmount = $order->deposit_amount;
        } elseif ($payType == Payment::TYPE_BALANCE) {
            if (!$order->deposit_paid) {
                return ['success' => false, 'message' => '请先支付定金'];
            }
            if ($order->balance_paid) {
                return ['success' => false, 'message' => '尾款已支付'];
            }
            $payAmount = $order->balance_amount;
        } else {
            $payAmount = $order->pay_amount;
        }

        // 创建支付记录
        $payment = Payment::createPayment(
            $order->id,
            $order->order_sn,
            $order->user_id,
            $payType,
            $payWay,
            $payAmount
        );

        // TODO: 根据支付方式调用第三方支付接口
        // 这里返回模拟的支付参数
        return [
            'success' => true,
            'data' => [
                'payment_sn' => $payment->payment_sn,
                'pay_amount' => $payAmount,
                'expire_time' => $payment->expire_time,
                // 微信支付参数（模拟）
                'pay_params' => [
                    'appId' => '',
                    'timeStamp' => (string)time(),
                    'nonceStr' => md5(uniqid()),
                    'package' => '',
                    'signType' => 'MD5',
                    'paySign' => '',
                ],
            ],
        ];
    }

    /**
     * @notes 申请退款
     * @param int $orderId
     * @param int $userId
     * @param float $amount
     * @param string $reason
     * @return array
     */
    public static function applyRefund(int $orderId, int $userId, float $amount, string $reason): array
    {
        [$success, $message, $refund] = Refund::applyRefund($orderId, $userId, $amount, $reason);
        return ['success' => $success, 'message' => $message];
    }

    /**
     * @notes 获取退款详情
     * @param int $orderId
     * @param int $userId
     * @return array|null
     */
    public static function getRefundDetail(int $orderId, int $userId): ?array
    {
        $refund = Refund::where('order_id', $orderId)
            ->where('user_id', $userId)
            ->order('id', 'desc')
            ->find();

        if (!$refund) {
            return null;
        }

        $data = $refund->toArray();
        $data['refund_status_desc'] = $refund->refund_status_desc;
        return $data;
    }

    /**
     * @notes 用户订单统计
     * @param int $userId
     * @return array
     */
    public static function getUserOrderStatistics(int $userId): array
    {
        $counts = [];
        foreach ([
            'pending' => Order::STATUS_PENDING,
            'paid' => Order::STATUS_PAID,
            'in_service' => Order::STATUS_IN_SERVICE,
            'completed' => Order::STATUS_COMPLETED,
            'refund' => Order::STATUS_REFUNDED,
        ] as $key => $status) {
            $counts[$key] = Order::where('user_id', $userId)->where('order_status', $status)->count();
        }
        $counts['all'] = Order::where('user_id', $userId)->count();

        return $counts;
    }

    /**
     * @notes 获取可用优惠券
     * @param int $userId
     * @param float $amount
     * @return array
     */
    public static function getAvailableCoupons(int $userId, float $amount = 0): array
    {
        // TODO: 实现优惠券查询逻辑
        return [];
    }

    /**
     * @notes 获取状态描述
     */
    protected static function getStatusDesc(int $status): string
    {
        $map = [
            Order::STATUS_PENDING => '待支付',
            Order::STATUS_PAID => '已支付',
            Order::STATUS_IN_SERVICE => '服务中',
            Order::STATUS_COMPLETED => '已完成',
            Order::STATUS_CANCELLED => '已取消',
            Order::STATUS_REFUNDED => '已退款',
        ];
        return $map[$status] ?? '未知';
    }

    protected static function getPayStatusDesc(int $status): string
    {
        $map = [
            Order::PAY_STATUS_UNPAID => '未支付',
            Order::PAY_STATUS_PAID => '已支付',
            Order::PAY_STATUS_PARTIAL_REFUND => '部分退款',
            Order::PAY_STATUS_FULL_REFUND => '全额退款',
        ];
        return $map[$status] ?? '未知';
    }

    protected static function getPayTypeDesc(int $type): string
    {
        $map = [
            Order::PAY_WAY_NONE => '未支付',
            Order::PAY_WAY_WECHAT => '微信支付',
            Order::PAY_WAY_ALIPAY => '支付宝',
            Order::PAY_WAY_BALANCE => '余额支付',
            Order::PAY_WAY_OFFLINE => '线下支付',
        ];
        return $map[$type] ?? '未知';
    }
}
