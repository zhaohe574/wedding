<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单支付适配逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\logic;

use app\common\enum\PayEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\model\order\Order;
use app\common\model\order\Payment as OrderPayment;
use app\common\model\user\User;
use app\common\service\OrderNotificationService;
use app\common\service\pay\WeChatPayService;
use think\facade\Db;

/**
 * 订单支付适配逻辑
 * Class OrderPayLogic
 * @package app\common\logic
 */
class OrderPayLogic extends BaseLogic
{
    /**
     * 订单端仅开放微信、余额两种在线支付方式
     */
    private const SUPPORTED_PAY_WAYS = [
        PayEnum::WECHAT_PAY,
        PayEnum::BALANCE_PAY,
    ];

    /**
     * @notes 同步处理已超时的待支付订单
     * @param Order $order
     * @return bool
     */
    private static function syncExpiredOrderIfNeeded(Order $order): bool
    {
        if (Order::syncExpiredAutoCancel($order)) {
            self::setError(Order::AUTO_CANCEL_MESSAGE);
            return true;
        }

        if (
            (int)$order->order_status === Order::STATUS_CANCELLED
            && (string)($order->cancel_reason ?? '') === Order::AUTO_CANCEL_REASON
        ) {
            self::setError(Order::AUTO_CANCEL_MESSAGE);
            return true;
        }

        return false;
    }

    /**
     * @notes 构造订单支付状态响应
     * @param Order $order
     * @param float $orderAmount
     * @param string $payWay
     * @param string $payStatus
     * @param string $payTime
     * @return array
     */
    private static function buildOrderStatusPayload(
        Order $order,
        float $orderAmount,
        string $payWay,
        string $payStatus,
        string $payTime = ''
    ): array {
        $paymentSummary = Order::getPaymentSummary($order);

        return array_merge($paymentSummary, [
            'order_id' => (int)$order->id,
            'order_sn' => (string)$order->order_sn,
            'order_amount' => round($orderAmount, 2),
            'pay_way' => $payWay,
            'pay_status' => $payStatus,
            'pay_time' => $payTime,
            'order_status' => (int)$order->order_status,
            'order_status_desc' => $order->order_status_desc,
            'pay_deadline_time' => (int)($order->pay_deadline_time ?? 0),
            'pay_remain_seconds' => $order->getPayRemainSeconds(),
        ]);
    }

    /**
     * @notes 获取可支付订单
     * @param int $userId
     * @param int $orderId
     * @param bool $lock
     * @return Order|false
     */
    public static function getPayableOrder(int $userId, int $orderId, bool $lock = false)
    {
        $query = Order::where('user_id', $userId)->where('id', $orderId);
        if ($lock) {
            $query->lock(true);
        }

        $order = $query->find();
        if (!$order) {
            self::setError('订单不存在');
            return false;
        }

        if (self::syncExpiredOrderIfNeeded($order)) {
            return false;
        }

        if ((int)$order->order_status !== Order::STATUS_PENDING_PAY) {
            self::setError('订单状态不允许支付');
            return false;
        }

        if (
            (int)$order->pay_type === Order::PAY_WAY_OFFLINE
            && !empty($order->pay_voucher)
            && (int)($order->pay_voucher_status ?? -1) === Order::VOUCHER_STATUS_PENDING
        ) {
            self::setError('线下支付凭证审核中，请等待审核结果');
            return false;
        }

        $payContext = self::getCurrentPayContext($order);
        if ($payContext === false) {
            return false;
        }

        return $order;
    }

    /**
     * @notes 获取当前支付阶段
     * @param Order $order
     * @return array|false
     */
    public static function getCurrentPayContext(Order $order)
    {
        if ((float)$order->deposit_amount > 0) {
            if (!(int)$order->deposit_paid) {
                return [
                    'pay_type' => OrderPayment::TYPE_DEPOSIT,
                    'pay_amount' => round((float)$order->deposit_amount, 2),
                    'need_pay' => 'deposit',
                ];
            }

            if (!(int)$order->balance_paid) {
                return [
                    'pay_type' => OrderPayment::TYPE_BALANCE,
                    'pay_amount' => round((float)$order->balance_amount, 2),
                    'need_pay' => 'balance',
                ];
            }

            self::setError('订单已完成支付');
            return false;
        }

        if ((int)$order->pay_status === Order::PAY_STATUS_PAID) {
            self::setError('订单已支付');
            return false;
        }

        $payAmount = round(max((float)$order->pay_amount - (float)($order->paid_amount ?? 0), 0), 2);
        if ($payAmount <= 0) {
            self::setError('当前订单无需支付');
            return false;
        }

        return [
            'pay_type' => OrderPayment::TYPE_FULL,
            'pay_amount' => $payAmount,
            'need_pay' => 'full',
        ];
    }

    /**
     * @notes 获取统一支付订单信息
     * @param array $params
     * @return array|false
     */
    public static function getPayOrderInfo(array $params)
    {
        $order = self::getPayableOrder((int)$params['user_id'], (int)$params['order_id']);
        if ($order === false) {
            return false;
        }

        $payContext = self::getCurrentPayContext($order);
        if ($payContext === false) {
            return false;
        }

        return [
            'id' => (int)$order->id,
            'user_id' => (int)$order->user_id,
            'sn' => (string)$order->order_sn,
            'order_sn' => (string)$order->order_sn,
            'order_amount' => (float)$payContext['pay_amount'],
            'pay_type' => (int)$payContext['pay_type'],
            'need_pay' => (string)$payContext['need_pay'],
            'need_pay_amount' => (float)$payContext['pay_amount'],
            'need_pay_label' => match ((string) $payContext['need_pay']) {
                'deposit' => '支付定金',
                'balance' => '支付尾款',
                default => '立即支付',
            },
            'payment_mode' => (float)($order->deposit_amount ?? 0) > 0 ? 'deposit' : 'full',
            'total_amount' => round((float)($order->total_amount ?? $order->pay_amount), 2),
            'pay_amount' => round((float)($order->pay_amount ?? 0), 2),
            'paid_amount' => round((float)($order->paid_amount ?? 0), 2),
            'unpaid_amount' => round(max((float)($order->pay_amount ?? 0) - (float)($order->paid_amount ?? 0), 0), 2),
            'deposit_amount' => round((float)($order->deposit_amount ?? 0), 2),
            'balance_amount' => round((float)($order->balance_amount ?? 0), 2),
            'deposit_paid' => (int)($order->deposit_paid ?? 0),
            'balance_paid' => (int)($order->balance_paid ?? 0),
            'deposit_remark' => (string) Order::getDepositConfig()['deposit_remark'],
            'pay_deadline_time' => (int)($order->pay_deadline_time ?? 0),
            'pay_remain_seconds' => $order->getPayRemainSeconds(),
        ];
    }

    /**
     * @notes 过滤订单支付方式
     * @param array $payWays
     * @return array
     */
    public static function filterPayWays(array $payWays): array
    {
        return array_values(array_filter($payWays, static function (array $item) {
            return in_array((int)($item['pay_way'] ?? 0), self::SUPPORTED_PAY_WAYS, true);
        }));
    }

    /**
     * @notes 订单支付状态
     * @param array $params
     * @return array|false
     */
    public static function getPayStatus(array $params)
    {
        try {
            $userId = (int)$params['user_id'];
            $orderId = (int)$params['order_id'];
            $paymentSn = trim((string)($params['payment_sn'] ?? ''));

            if ($paymentSn !== '') {
                $payment = OrderPayment::where('payment_sn', $paymentSn)
                    ->where('user_id', $userId)
                    ->find();
                if (!$payment) {
                    throw new \Exception('支付记录不存在');
                }

                $order = Order::where('user_id', $userId)
                    ->where('id', (int)$payment->order_id)
                    ->find();
                if (!$order) {
                    throw new \Exception('订单不存在');
                }

                Order::syncExpiredAutoCancel($order);
                $payment = OrderPayment::where('payment_sn', $paymentSn)
                    ->where('user_id', $userId)
                    ->find();
                if (!$payment) {
                    throw new \Exception('支付记录不存在');
                }
                $order = Order::where('user_id', $userId)
                    ->where('id', (int)$payment->order_id)
                    ->find();
                if (!$order) {
                    throw new \Exception('订单不存在');
                }

                return [
                    'pay_status' => (int)$payment->pay_status === OrderPayment::STATUS_PAID ? PayEnum::ISPAID : PayEnum::UNPAID,
                    'pay_way' => self::toCommonPayWay((int)$payment->pay_way),
                    'order' => self::buildOrderStatusPayload(
                        $order,
                        (float)$payment->pay_amount,
                        $payment->pay_way_desc,
                        $payment->pay_status_desc,
                        empty($payment->pay_time) ? '' : date('Y-m-d H:i:s', (int)$payment->pay_time)
                    ),
                ];
            }

            $order = Order::where('user_id', $userId)
                ->where('id', $orderId)
                ->find();
            if (!$order) {
                throw new \Exception('订单不存在');
            }

            Order::syncExpiredAutoCancel($order);
            $order = Order::where('user_id', $userId)
                ->where('id', $orderId)
                ->find();
            if (!$order) {
                throw new \Exception('订单不存在');
            }

            return [
                'pay_status' => (int)$order->pay_status === Order::PAY_STATUS_PAID ? PayEnum::ISPAID : PayEnum::UNPAID,
                'pay_way' => self::toCommonPayWay((int)$order->pay_type),
                'order' => self::buildOrderStatusPayload(
                    $order,
                    (float)$order->pay_amount,
                    $order->pay_type_desc,
                    $order->pay_status_desc,
                    empty($order->pay_time) ? '' : date('Y-m-d H:i:s', (int)$order->pay_time)
                ),
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 订单统一支付
     * @param int $payWay
     * @param array $order
     * @param int $terminal
     * @param string $redirectUrl
     * @return array|false
     */
    public static function pay(int $payWay, array $order, int $terminal, string $redirectUrl)
    {
        if (!in_array($payWay, self::SUPPORTED_PAY_WAYS, true)) {
            self::setError('当前订单暂不支持该支付方式');
            return false;
        }

        return $payWay === PayEnum::BALANCE_PAY
            ? self::balancePay($order)
            : self::wechatPay($order, $terminal, $redirectUrl);
    }

    /**
     * @notes 兼容旧订单支付接口
     * @param array $params
     * @return array
     */
    public static function legacyCreatePayment(array $params): array
    {
        try {
            $orderPayWay = (int)($params['pay_way'] ?? 0);
            if ($orderPayWay === Order::PAY_WAY_OFFLINE) {
                return ['success' => false, 'message' => '线下支付请上传支付凭证'];
            }

            if ($orderPayWay === Order::PAY_WAY_COMBINATION) {
                return ['success' => false, 'message' => '组合支付暂不支持，请使用微信支付或余额支付'];
            }

            $commonPayWay = self::toCommonPayWay($orderPayWay);
            if ($commonPayWay <= 0) {
                return ['success' => false, 'message' => '支付方式参数错误'];
            }

            $orderInfo = self::getPayOrderInfo([
                'user_id' => (int)$params['user_id'],
                'order_id' => (int)$params['id'],
            ]);
            if ($orderInfo === false) {
                return ['success' => false, 'message' => self::getError()];
            }

            $result = self::pay(
                $commonPayWay,
                $orderInfo,
                (int)($params['terminal'] ?? 0),
                (string)($params['redirect'] ?? '/pages/order_detail/order_detail')
            );
            if ($result === false) {
                return ['success' => false, 'message' => self::getError()];
            }

            $data = [
                'payment_sn' => $result['payment_sn'] ?? '',
                'pay_amount' => (float)$orderInfo['order_amount'],
            ];

            if ($commonPayWay === PayEnum::WECHAT_PAY) {
                $data['pay_params'] = $result['config'] ?? [];
            } else {
                $data['pay_status'] = OrderPayment::STATUS_PAID;
            }

            return ['success' => true, 'data' => $data];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @notes 微信支付
     * @param array $orderData
     * @param int $terminal
     * @param string $redirectUrl
     * @return array|false
     */
    private static function wechatPay(array $orderData, int $terminal, string $redirectUrl)
    {
        $payment = null;
        $payAmount = 0;

        Db::startTrans();
        try {
            $order = self::getPayableOrder((int)$orderData['user_id'], (int)$orderData['id'], true);
            if ($order === false) {
                throw new \Exception(self::getError());
            }

            $payContext = self::getCurrentPayContext($order);
            if ($payContext === false) {
                throw new \Exception(self::getError());
            }

            $payAmount = (float)$payContext['pay_amount'];
            $expireTime = (int)($order->pay_deadline_time ?? 0);
            $payment = OrderPayment::createPayment(
                (int)$order->id,
                (string)$order->order_sn,
                (int)$order->user_id,
                (int)$payContext['pay_type'],
                OrderPayment::WAY_WECHAT,
                $payAmount,
                30,
                $expireTime > 0 ? $expireTime : 0
            );

            $order->pay_type = Order::PAY_WAY_WECHAT;
            $order->update_time = time();
            $order->save();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }

        $payload = [
            'id' => (int)$orderData['id'],
            'user_id' => (int)$orderData['user_id'],
            'sn' => (string)$payment->payment_sn,
            'pay_sn' => (string)$payment->payment_sn,
            'payment_sn' => (string)$payment->payment_sn,
            'order_sn' => (string)$orderData['order_sn'],
            'order_amount' => $payAmount,
            'redirect_url' => $redirectUrl,
            'pay_deadline_time' => (int)($orderData['pay_deadline_time'] ?? 0),
        ];

        $payService = new WeChatPayService($terminal, (int)$orderData['user_id']);
        $result = $payService->pay('order', $payload);
        if ($result === false) {
            OrderPayment::update([
                'id' => $payment->id,
                'pay_status' => OrderPayment::STATUS_FAILED,
                'update_time' => time(),
            ]);
            self::setError($payService->getError());
            return false;
        }

        $result['payment_sn'] = $payment->payment_sn;
        return $result;
    }

    /**
     * @notes 余额支付
     * @param array $orderData
     * @return array|false
     */
    private static function balancePay(array $orderData)
    {
        $notifyContext = [];

        Db::startTrans();
        try {
            $order = self::getPayableOrder((int)$orderData['user_id'], (int)$orderData['id'], true);
            if ($order === false) {
                throw new \Exception(self::getError());
            }

            $payContext = self::getCurrentPayContext($order);
            if ($payContext === false) {
                throw new \Exception(self::getError());
            }

            $payAmount = (float)$payContext['pay_amount'];
            $user = User::lock(true)->find((int)$order->user_id);
            if (!$user) {
                throw new \Exception('用户不存在');
            }

            if ((float)$user->user_money < $payAmount) {
                throw new \Exception('余额不足');
            }

            $payment = OrderPayment::createPayment(
                (int)$order->id,
                (string)$order->order_sn,
                (int)$order->user_id,
                (int)$payContext['pay_type'],
                OrderPayment::WAY_BALANCE,
                $payAmount,
                30,
                (int)($order->pay_deadline_time ?? 0) > 0 ? (int)$order->pay_deadline_time : 0
            );

            $user->user_money = round((float)$user->user_money - $payAmount, 2);
            $user->save();

            AccountLogLogic::add(
                (int)$order->user_id,
                AccountLogEnum::UM_DEC_ADMIN,
                AccountLogEnum::DEC,
                $payAmount,
                (string)$order->order_sn,
                '订单余额支付'
            );

            [$paid, $message, $notifyContext] = OrderPayment::paySuccess(
                (string)$payment->payment_sn,
                'BALANCE',
                ['pay_way' => 'balance']
            );
            if (!$paid) {
                throw new \Exception($message ?: '余额支付失败');
            }

            Db::commit();

            if (!empty($notifyContext['should_notify'])) {
                OrderNotificationService::notifyUserAndStaffOnPaymentSuccess(
                    (int)$notifyContext['order_id'],
                    (int)$notifyContext['pay_type']
                );
            }

            return [
                'pay_way' => PayEnum::BALANCE_PAY,
                'config' => [],
                'payment_sn' => $payment->payment_sn,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 将订单支付方式转换为通用支付方式
     * @param int $payWay
     * @return int
     */
    public static function toCommonPayWay(int $payWay): int
    {
        return match ($payWay) {
            Order::PAY_WAY_WECHAT, OrderPayment::WAY_WECHAT => PayEnum::WECHAT_PAY,
            Order::PAY_WAY_BALANCE, OrderPayment::WAY_BALANCE => PayEnum::BALANCE_PAY,
            Order::PAY_WAY_ALIPAY, OrderPayment::WAY_ALIPAY => PayEnum::ALI_PAY,
            default => 0,
        };
    }
}
