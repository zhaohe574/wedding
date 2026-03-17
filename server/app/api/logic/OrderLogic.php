<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订单逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\coupon\UserCoupon;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderLog;
use app\common\model\order\Payment;
use app\common\model\package\PackageBooking;
use app\common\model\order\Refund;
use app\common\model\schedule\Schedule;
use app\common\model\service\ServicePackage;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use app\common\logic\AccountLogLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\service\OrderNotificationService;
use think\facade\Db;

/**
 * 小程序端订单逻辑
 * Class OrderLogic
 * @package app\api\logic
 */
class OrderLogic extends BaseLogic
{
    /**
     * @notes 构造直购选择项
     */
    private static function buildSelectedItems(array $params): array
    {
        $staffId = (int)($params['staff_id'] ?? 0);
        $packageId = (int)($params['package_id'] ?? 0);
        $date = (string)($params['date'] ?? '');

        $staff = Staff::where('id', $staffId)
            ->where('status', Staff::STATUS_ENABLE)
            ->whereNull('delete_time')
            ->find();
        if (!$staff) {
            throw new \Exception('服务人员不存在或已下线');
        }

        $package = ServicePackage::where('id', $packageId)
            ->where('staff_id', $staffId)
            ->where('is_show', 1)
            ->whereNull('delete_time')
            ->find();
        if (!$package) {
            throw new \Exception('套餐不存在或已下线');
        }

        return [[
            'staff_id' => $staffId,
            'package_id' => $packageId,
            'schedule_id' => 0,
            'schedule_date' => $date,
            'time_slot' => 0,
            'price' => round((float)$package->price, 2),
            'quantity' => 1,
            'remark' => $params['remark'] ?? '',
            'staff' => [
                'id' => (int)$staff->id,
                'name' => (string)$staff->name,
                'avatar' => (string)$staff->avatar,
                'category_id' => (int)$staff->category_id,
            ],
            'package' => [
                'id' => (int)$package->id,
                'name' => (string)$package->name,
                'category_id' => (int)$package->category_id,
                'price' => round((float)$package->price, 2),
                'original_price' => round((float)$package->original_price, 2),
                'description' => (string)($package->description ?? ''),
                'image' => (string)($package->image ?? ''),
                'content' => $package->content,
            ],
        ]];
    }

    /**
     * @notes 校验预约日期是否可下单
     */
    private static function ensureScheduleAvailable(array $selectedItems): void
    {
        foreach ($selectedItems as $item) {
            [$available, $reason] = Schedule::checkAvailabilityWithReason(
                (int)$item['staff_id'],
                (string)$item['schedule_date'],
                0
            );
            if (!$available) {
                throw new \Exception($reason ?: '请重新确认预约信息');
            }
        }
    }

    /**
     * @notes 刷新用户当前直购选择的套餐临时锁
     */
    private static function refreshTempLock(int $userId, array $selectedItem): void
    {
        $lock = PackageBooking::createTempLock(
            (int)$selectedItem['package_id'],
            (int)$selectedItem['staff_id'],
            (string)$selectedItem['schedule_date'],
            0,
            $userId
        );

        if ($lock) {
            return;
        }

        $availability = PackageBooking::checkAvailability(
            (int)$selectedItem['package_id'],
            (string)$selectedItem['schedule_date'],
            (int)$selectedItem['staff_id'],
            0
        );

        throw new \Exception((string)($availability['message'] ?? '请重新确认预约信息'));
    }

    /**
     * @notes 校验用户是否持有当前套餐临时锁
     */
    private static function ensureTempLockOwned(int $userId, array $selectedItem): void
    {
        $lock = PackageBooking::where('user_id', $userId)
            ->where('package_id', (int)$selectedItem['package_id'])
            ->where('staff_id', (int)$selectedItem['staff_id'])
            ->where('booking_date', (string)$selectedItem['schedule_date'])
            ->where('time_slot', 0)
            ->where('status', PackageBooking::STATUS_TEMP_LOCK)
            ->lock(true)
            ->find();

        if (!$lock) {
            throw new \Exception('请重新确认预约信息');
        }
    }

    /**
     * @notes 汇总结算上下文
     */
    private static function buildCheckoutSummary(array $selectedItems): array
    {
        $totalAmount = 0;
        $staffIds = [];
        $categoryIds = [];

        foreach ($selectedItems as $item) {
            $totalAmount += (float)$item['price'] * (int)($item['quantity'] ?? 1);
            $staffIds[] = (int)($item['staff_id'] ?? 0);

            $packageCategoryId = (int)($item['package']['category_id'] ?? 0);
            $staffCategoryId = (int)($item['staff']['category_id'] ?? 0);
            if ($packageCategoryId > 0) {
                $categoryIds[] = $packageCategoryId;
            } elseif ($staffCategoryId > 0) {
                $categoryIds[] = $staffCategoryId;
            }
        }

        return [
            'total_amount' => round($totalAmount, 2),
            'staff_ids' => array_values(array_unique(array_filter($staffIds))),
            'category_ids' => array_values(array_unique(array_filter($categoryIds))),
        ];
    }

    /**
     * @notes 解析选中优惠券
     */
    private static function resolveSelectedCoupon(int $userId, array $summary, int $userCouponId = 0): array
    {
        $result = [
            'user_coupon_id' => 0,
            'coupon_id' => 0,
            'coupon_amount' => 0,
        ];

        if ($userCouponId <= 0) {
            return $result;
        }

        $availableCoupons = UserCoupon::getAvailableForOrder(
            $userId,
            (float)$summary['total_amount'],
            $summary['staff_ids'] ?? [],
            $summary['category_ids'] ?? []
        );

        foreach ($availableCoupons as $couponItem) {
            if ((int)$couponItem->id !== $userCouponId) {
                continue;
            }

            return [
                'user_coupon_id' => (int)$couponItem->id,
                'coupon_id' => (int)$couponItem->coupon_id,
                'coupon_amount' => round((float)($couponItem->discount_amount ?? 0), 2),
            ];
        }

        throw new \Exception('所选优惠券不可用，请重新选择');
    }

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
                $q->field('id, order_id, staff_id, staff_name, package_name, service_date, item_status, price, quantity, subtotal')
                    ->with(['staff' => function ($staffQuery) {
                        $staffQuery->field('id, name, avatar');
                    }]);
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
        $data['pay_voucher_status_desc'] = $order->pay_voucher_status_desc ?? '';

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
        try {
            $selectedItems = self::buildSelectedItems($params);
            PackageBooking::releaseByUserId($userId);
            self::ensureScheduleAvailable($selectedItems);
            self::refreshTempLock($userId, $selectedItems[0]);

            $summary = self::buildCheckoutSummary($selectedItems);
            $couponData = self::resolveSelectedCoupon(
                $userId,
                $summary,
                (int)($params['user_coupon_id'] ?? 0)
            );
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $totalAmount = (float)$summary['total_amount'];
        $couponAmount = (float)$couponData['coupon_amount'];
        $payAmount = max(0, $totalAmount - $couponAmount);

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
                'items' => $selectedItems,
                'total_amount' => round($totalAmount, 2),
                'coupon_amount' => round($couponAmount, 2),
                'pay_amount' => round($payAmount, 2),
                'coupon_id' => (int)$couponData['coupon_id'],
                'user_coupon_id' => (int)$couponData['user_coupon_id'],
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
            $userId = (int)$params['user_id'];
            $selectedItems = self::buildSelectedItems($params);
            self::ensureScheduleAvailable($selectedItems);
            self::ensureTempLockOwned($userId, $selectedItems[0]);

            $summary = self::buildCheckoutSummary($selectedItems);
            $couponData = self::resolveSelectedCoupon(
                $userId,
                $summary,
                (int)($params['user_coupon_id'] ?? 0)
            );

            $params['coupon_amount'] = $couponData['coupon_amount'];
            $params['coupon_id'] = $couponData['coupon_id'];
            $params['user_coupon_id'] = $couponData['user_coupon_id'];
            $params['service_date'] = $params['date'] ?? ($selectedItems[0]['schedule_date'] ?? '');

            // 创建订单
            [$success, $message, $order] = Order::createOrder($userId, $selectedItems, $params);
            
            if (!$success) {
                Db::rollback();
                return ['success' => false, 'message' => $message];
            }

            if ((int)$couponData['user_coupon_id'] > 0) {
                $userCoupon = UserCoupon::where('id', (int)$couponData['user_coupon_id'])
                    ->where('user_id', $userId)
                    ->lock(true)
                    ->find();
                if (!$userCoupon || !$userCoupon->use((int)$order->id)) {
                    Db::rollback();
                    return ['success' => false, 'message' => '优惠券核销失败，请重试'];
                }
            }

            Db::commit();

            OrderNotificationService::notifyStaffOnOrderCreated((int) $order->id);

            return [
                'success' => true,
                'message' => '订单创建成功',
                'order_id' => $order->id,
                'order_sn' => $order->order_sn,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'message' => $e->getMessage() ?: '请重新确认预约信息'];
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

        if (!in_array($order->order_status, [Order::STATUS_COMPLETED, Order::STATUS_REVIEWED, Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])) {
            return ['success' => false, 'message' => '只能删除已完成、已评价、已取消或已退款的订单'];
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
            'pay_voucher' => $order->pay_voucher ?? '',
            'pay_voucher_status' => $order->pay_voucher_status ?? null,
            'pay_voucher_status_desc' => $order->pay_voucher_status_desc ?? '',
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
     * @notes 上传线下支付凭证
     * @param int $orderId
     * @param int $userId
     * @param string $voucher
     * @return array
     */
    public static function uploadPayVoucher(int $orderId, int $userId, string $voucher): array
    {
        if (empty($voucher)) {
            return ['success' => false, 'message' => '请上传支付凭证'];
        }

        $order = Order::where('user_id', $userId)->find($orderId);
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在'];
        }

        if ($order->order_status != Order::STATUS_PENDING_PAY) {
            return ['success' => false, 'message' => '当前订单状态不允许上传凭证'];
        }

        $order->pay_type = Order::PAY_WAY_OFFLINE;
        $order->pay_voucher = $voucher;
        $order->pay_voucher_status = Order::VOUCHER_STATUS_PENDING;
        $order->pay_voucher_audit_admin_id = 0;
        $order->pay_voucher_audit_time = 0;
        $order->pay_voucher_audit_remark = '';
        $order->update_time = time();
        $order->save();

        OrderLog::addLog(
            $order->id,
            OrderLog::OPERATOR_USER,
            $userId,
            'upload_voucher',
            $order->order_status,
            $order->order_status,
            '上传线下支付凭证'
        );

        return ['success' => true, 'message' => '凭证已提交，请等待审核'];
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

        if ($order->order_status != Order::STATUS_PENDING_PAY) {
            return ['success' => false, 'message' => '订单状态不允许支付'];
        }

        if ($order->pay_type == Order::PAY_WAY_OFFLINE && !empty($order->pay_voucher) && (int)($order->pay_voucher_status ?? -1) === Order::VOUCHER_STATUS_PENDING) {
            return ['success' => false, 'message' => '线下支付凭证审核中，请等待审核结果'];
        }

        $payType = (int) ($params['pay_type'] ?? Payment::TYPE_FULL);
        $payWay = (int) ($params['pay_way'] ?? Payment::WAY_WECHAT);

        if ($payWay == Order::PAY_WAY_OFFLINE) {
            return ['success' => false, 'message' => '线下支付请上传支付凭证'];
        }

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

        Db::startTrans();
        try {
            // 余额支付
            if ($payWay == Order::PAY_WAY_BALANCE) {
                $user = User::find($order->user_id);
                if (!$user) {
                    throw new \Exception('用户不存在');
                }
                if ((float)$user->user_money < (float)$payAmount) {
                    throw new \Exception('余额不足');
                }

                $user->user_money = round((float)$user->user_money - (float)$payAmount, 2);
                $user->save();

                AccountLogLogic::add(
                    $order->user_id,
                    AccountLogEnum::UM_DEC_ADMIN,
                    AccountLogEnum::DEC,
                    $payAmount,
                    $order->order_sn,
                    '订单余额支付'
                );

                $payment = Payment::createPayment(
                    $order->id,
                    $order->order_sn,
                    $order->user_id,
                    $payType,
                    Payment::WAY_BALANCE,
                    $payAmount
                );

                [$paid, $message] = Payment::paySuccess($payment->payment_sn, 'BALANCE');
                if (!$paid) {
                    throw new \Exception($message ?: '余额支付失败');
                }

                Db::commit();
                return [
                    'success' => true,
                    'data' => [
                        'payment_sn' => $payment->payment_sn,
                        'pay_amount' => $payAmount,
                        'pay_status' => Payment::STATUS_PAID,
                    ],
                ];
            }

            // 组合支付（余额 + 微信）
            if ($payWay == Order::PAY_WAY_COMBINATION) {
                $user = User::find($order->user_id);
                if (!$user) {
                    throw new \Exception('用户不存在');
                }

                $balancePay = min((float)$user->user_money, (float)$payAmount);
                $remainingAmount = round((float)$payAmount - (float)$balancePay, 2);

                if ($balancePay > 0) {
                    $user->user_money = round((float)$user->user_money - (float)$balancePay, 2);
                    $user->save();

                    AccountLogLogic::add(
                        $order->user_id,
                        AccountLogEnum::UM_DEC_ADMIN,
                        AccountLogEnum::DEC,
                        $balancePay,
                        $order->order_sn,
                        '组合支付-余额抵扣'
                    );

                    $balancePayment = Payment::createPayment(
                        $order->id,
                        $order->order_sn,
                        $order->user_id,
                        $payType,
                        Payment::WAY_BALANCE,
                        $balancePay
                    );

                    [$paid, $message] = Payment::paySuccess($balancePayment->payment_sn, 'BALANCE');
                    if (!$paid) {
                        throw new \Exception($message ?: '组合支付余额扣减失败');
                    }
                }

                $order->pay_type = Order::PAY_WAY_COMBINATION;
                $order->update_time = time();
                $order->save();

                if ($remainingAmount <= 0) {
                    Db::commit();
                    return [
                        'success' => true,
                        'data' => [
                            'payment_sn' => '',
                            'pay_amount' => $payAmount,
                            'pay_status' => Payment::STATUS_PAID,
                        ],
                    ];
                }

                $payment = Payment::createPayment(
                    $order->id,
                    $order->order_sn,
                    $order->user_id,
                    $payType,
                    Payment::WAY_WECHAT,
                    $remainingAmount
                );

                Db::commit();
                return [
                    'success' => true,
                    'data' => [
                        'payment_sn' => $payment->payment_sn,
                        'pay_amount' => $remainingAmount,
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

            // 创建在线支付记录
            $payment = Payment::createPayment(
                $order->id,
                $order->order_sn,
                $order->user_id,
                $payType,
                $payWay,
                $payAmount
            );

            $order->pay_type = $payWay;
            $order->update_time = time();
            $order->save();

            Db::commit();
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
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
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
            'pending_confirm' => Order::STATUS_PENDING_CONFIRM,
            'pending_pay' => Order::STATUS_PENDING_PAY,
            'paid' => Order::STATUS_PAID,
            'in_service' => Order::STATUS_IN_SERVICE,
            'completed' => Order::STATUS_COMPLETED,
            'reviewed' => Order::STATUS_REVIEWED,
            'cancelled' => Order::STATUS_CANCELLED,
            'paused' => Order::STATUS_PAUSED,
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
     * @param array $params
     * @return array
     */
    public static function getAvailableCoupons(int $userId, array $params = []): array
    {
        try {
            $selectedItems = self::buildSelectedItems($params);
        } catch (\Exception $e) {
            return [
                'lists' => [],
                'total' => 0,
                'best_coupon' => null,
            ];
        }

        $summary = self::buildCheckoutSummary($selectedItems);
        return CouponLogic::orderAvailable([
            'user_id' => $userId,
            'order_amount' => $summary['total_amount'],
            'staff_ids' => $summary['staff_ids'],
            'category_ids' => $summary['category_ids'],
        ]);
    }

    /**
     * @notes 获取状态描述
     */
    protected static function getStatusDesc(int $status): string
    {
        $map = [
            Order::STATUS_PENDING_CONFIRM => '待确认',
            Order::STATUS_PENDING_PAY => '待支付',
            Order::STATUS_PAID => '已支付',
            Order::STATUS_IN_SERVICE => '服务中',
            Order::STATUS_COMPLETED => '已完成',
            Order::STATUS_REVIEWED => '已评价',
            Order::STATUS_CANCELLED => '已取消',
            Order::STATUS_PAUSED => '已暂停',
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
            Order::PAY_WAY_COMBINATION => '组合支付',
        ];
        return $map[$type] ?? '未知';
    }
}
