<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\order;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderLog;
use app\common\model\order\Payment;
use app\common\model\schedule\Schedule;
use app\common\model\user\User;
use think\facade\Db;

/**
 * 订单业务逻辑
 * Class OrderLogic
 * @package app\adminapi\logic\order
 */
class OrderLogic extends BaseLogic
{
    /**
     * @notes 获取订单详情
     * @param int $id
     * @return array|null
     */
    public static function detail(int $id): ?array
    {
        $order = Order::with([
            'user' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            },
            'items' => function ($query) {
                $query->with(['staff' => function ($q) {
                    $q->field('id, name, avatar');
                }]);
            },
            'payments',
            'logs' => function ($query) {
                $query->order('create_time', 'desc');
            }
        ])->find($id);

        if (!$order) {
            return null;
        }

        $data = $order->toArray();
        $data['order_status_desc'] = $order->order_status_desc;
        $data['pay_status_desc'] = $order->pay_status_desc;
        $data['pay_type_desc'] = $order->pay_type_desc;
        $data['pay_voucher_status_desc'] = $order->pay_voucher_status_desc ?? '';

        return $data;
    }

    /**
     * @notes 后台创建订单
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            // 验证用户
            $user = User::find($params['user_id']);
            if (!$user) {
                self::setError('用户不存在');
                return false;
            }

            // 计算订单金额
            $totalAmount = 0;
            $items = $params['items'] ?? [];
            
            foreach ($items as &$item) {
                $itemTotal = $item['price'] * ($item['quantity'] ?? 1);
                $item['subtotal'] = $itemTotal;
                $totalAmount += $itemTotal;
            }

            // 优惠计算
            $discountAmount = $params['discount_amount'] ?? 0;
            $payAmount = $totalAmount - $discountAmount;

            // 定金/尾款模式
            $depositAmount = 0;
            $balanceAmount = 0;
            if (!empty($params['deposit_ratio'])) {
                $depositAmount = round($payAmount * $params['deposit_ratio'] / 100, 2);
                $balanceAmount = $payAmount - $depositAmount;
            }

            // 创建订单
            $order = Order::create([
                'order_sn' => Order::generateOrderSn(),
                'user_id' => $params['user_id'],
                'order_type' => $params['order_type'] ?? Order::TYPE_NORMAL,
                'order_status' => Order::STATUS_PENDING_PAY,
                'pay_status' => Order::PAY_STATUS_UNPAID,
                'paid_amount' => 0,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'coupon_amount' => 0,
                'pay_amount' => $payAmount,
                'deposit_amount' => $depositAmount,
                'balance_amount' => $balanceAmount,
                'service_date' => $params['service_date'] ?? null,
                'service_time_slot' => $params['time_slot'] ?? 0,
                'service_address' => $params['service_address'] ?? '',
                'contact_name' => $params['contact_name'] ?? '',
                'contact_mobile' => $params['contact_mobile'] ?? '',
                'wedding_date' => $params['wedding_date'] ?? null,
                'wedding_venue' => $params['wedding_venue'] ?? '',
                'admin_remark' => $params['admin_remark'] ?? '',
                'source' => Order::SOURCE_ADMIN,
                'pay_type' => Order::PAY_WAY_NONE,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 创建订单项
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'staff_id' => $item['staff_id'] ?? 0,
                    'package_id' => $item['package_id'] ?? 0,
                    'schedule_id' => $item['schedule_id'] ?? 0,
                    'service_date' => $item['service_date'] ?? $params['service_date'],
                    'time_slot' => $item['time_slot'] ?? 0,
                    'staff_name' => $item['staff_name'] ?? '',
                    'package_name' => $item['package_name'] ?? '',
                    'price' => $item['price'],
                    'quantity' => $item['quantity'] ?? 1,
                    'subtotal' => $item['subtotal'],
                    'remark' => $item['remark'] ?? '',
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

                // 锁定档期
                if (!empty($item['schedule_id'])) {
                    Schedule::confirmBooking(
                        $item['staff_id'],
                        $item['service_date'] ?? $params['service_date'],
                        $item['time_slot'] ?? 0,
                        $order->id,
                        $params['user_id']
                    );
                }
            }

            // 记录日志
            OrderLog::addLog($order->id, OrderLog::OPERATOR_ADMIN, $params['admin_id'], 'create', 0, Order::STATUS_PENDING_PAY, '后台创建订单');

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑订单
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $order = Order::find($params['id']);
            if (!$order) {
                self::setError('订单不存在');
                return false;
            }

            // 只能编辑未完成的订单
            if (in_array($order->order_status, [Order::STATUS_COMPLETED, Order::STATUS_REVIEWED, Order::STATUS_CANCELLED, Order::STATUS_PAUSED, Order::STATUS_REFUNDED])) {
                self::setError('当前订单状态不允许编辑');
                return false;
            }

            $updateData = [];
            $allowFields = ['service_date', 'service_time_slot', 'service_address', 'contact_name', 'contact_mobile', 'wedding_date', 'wedding_venue', 'admin_remark'];
            
            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['update_time'] = time();
                Order::where('id', $params['id'])->update($updateData);

                // 记录日志
                OrderLog::addLog($params['id'], OrderLog::OPERATOR_ADMIN, $params['admin_id'], 'edit', $order->order_status, $order->order_status, '编辑订单信息');
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 取消订单
     * @param int $orderId
     * @param int $adminId
     * @param string $reason
     * @return bool
     */
    public static function cancel(int $orderId, int $adminId, string $reason = ''): bool
    {
        [$success, $message] = Order::cancelOrder($orderId, $adminId, OrderLog::OPERATOR_ADMIN, $reason);
        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 开始服务
     * @param int $orderId
     * @param int $adminId
     * @return bool
     */
    public static function startService(int $orderId, int $adminId): bool
    {
        try {
            $order = Order::find($orderId);
            if (!$order) {
                self::setError('订单不存在');
                return false;
            }

            if ($order->order_status != Order::STATUS_PAID) {
                self::setError('只有已支付的订单才能开始服务');
                return false;
            }

            $beforeStatus = $order->order_status;
            $order->order_status = Order::STATUS_IN_SERVICE;
            $order->start_service_time = time();
            $order->update_time = time();
            $order->save();

            // 更新订单项状态
            OrderItem::where('order_id', $orderId)->update([
                'item_status' => 1,
                'update_time' => time(),
            ]);

            // 记录日志
            OrderLog::addLog($orderId, OrderLog::OPERATOR_ADMIN, $adminId, 'start_service', $beforeStatus, Order::STATUS_IN_SERVICE, '开始服务');

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 完成订单
     * @param int $orderId
     * @param int $adminId
     * @return bool
     */
    public static function complete(int $orderId, int $adminId): bool
    {
        [$success, $message] = Order::completeOrder($orderId, $adminId, OrderLog::OPERATOR_ADMIN);
        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 确认线下支付
     * @param array $params
     * @return bool
     */
    public static function confirmOfflinePay(array $params): bool
    {
        Db::startTrans();
        try {
            $order = Order::find($params['id']);
            if (!$order) {
                self::setError('订单不存在');
                return false;
            }

            if ($order->order_status != Order::STATUS_PENDING_PAY) {
                self::setError('当前订单状态不允许此操作');
                return false;
            }

            $payType = $params['pay_type'] ?? Payment::TYPE_FULL;
            $payAmount = $params['pay_amount'] ?? $order->pay_amount;

            if ($payType == Payment::TYPE_DEPOSIT && $order->deposit_paid) {
                self::setError('定金已支付');
                return false;
            }

            if ($payType == Payment::TYPE_BALANCE && !$order->deposit_paid) {
                self::setError('请先支付定金');
                return false;
            }

            // 创建支付记录
            $payment = Payment::create([
                'payment_sn' => Payment::generatePaymentSn(),
                'order_id' => $order->id,
                'order_sn' => $order->order_sn,
                'user_id' => $order->user_id,
                'pay_type' => $payType,
                'pay_way' => Payment::WAY_OFFLINE,
                'pay_amount' => $payAmount,
                'pay_status' => Payment::STATUS_PAID,
                'pay_time' => time(),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 更新订单状态
            if ($payType == Payment::TYPE_DEPOSIT) {
                $order->deposit_paid = 1;
                $order->pay_time = time();
                // 定金模式下，支付定金后仍为待支付状态
            } else {
                // 全款或尾款
                if ($payType == Payment::TYPE_BALANCE) {
                    $order->balance_paid = 1;
                }
                $order->order_status = Order::STATUS_PAID;
                $order->pay_status = Order::PAY_STATUS_PAID;
                $order->pay_time = time();
            }

            $order->pay_type = Order::PAY_WAY_OFFLINE;
            $order->paid_amount = round((float)($order->paid_amount ?? 0) + (float)$payAmount, 2);
            $order->update_time = time();
            $order->save();

            // 记录日志
            $action = $payType == Payment::TYPE_DEPOSIT ? 'pay_deposit' : ($payType == Payment::TYPE_BALANCE ? 'pay_balance' : 'pay');
            OrderLog::addLog($order->id, OrderLog::OPERATOR_ADMIN, $params['admin_id'], $action, Order::STATUS_PENDING_PAY, $order->order_status, '确认线下支付，金额：' . $payAmount);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 添加备注
     * @param int $orderId
     * @param int $adminId
     * @param string $remark
     * @return bool
     */
    public static function addRemark(int $orderId, int $adminId, string $remark): bool
    {
        try {
            $order = Order::find($orderId);
            if (!$order) {
                self::setError('订单不存在');
                return false;
            }

            $order->admin_remark = $remark;
            $order->update_time = time();
            $order->save();

            // 记录日志
            OrderLog::addLog($orderId, OrderLog::OPERATOR_ADMIN, $adminId, 'remark', $order->order_status, $order->order_status, '添加备注：' . $remark);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 订单统计
     * @param array $params
     * @return array
     */
    public static function statistics(array $params): array
    {
        // 时间范围
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        $staffId = (int)($params['staff_id'] ?? 0);
        
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');

        $query = Order::whereBetween('create_time', [$startTime, $endTime]);
        if ($staffId > 0) {
            $query->whereIn('id', function ($subQuery) use ($staffId) {
                $subQuery->name('order_item')
                    ->where('staff_id', $staffId)
                    ->field('order_id');
            });
        }

        // 总订单数
        $totalOrders = (clone $query)->count();

        // 各状态订单数
        $statusCounts = [];
        foreach ([
            Order::STATUS_PENDING_CONFIRM => '待确认',
            Order::STATUS_PENDING_PAY => '待支付',
            Order::STATUS_PAID => '已支付',
            Order::STATUS_IN_SERVICE => '服务中',
            Order::STATUS_COMPLETED => '已完成',
            Order::STATUS_REVIEWED => '已评价',
            Order::STATUS_CANCELLED => '已取消',
            Order::STATUS_PAUSED => '已暂停',
            Order::STATUS_REFUNDED => '已退款',
        ] as $status => $label) {
            $statusCounts[] = [
                'status' => $status,
                'label' => $label,
                'count' => (clone $query)->where('order_status', $status)->count(),
            ];
        }

        // 总金额
        $totalAmount = (clone $query)->sum('total_amount');
        $paidAmount = (clone $query)->where('pay_status', Order::PAY_STATUS_PAID)->sum('pay_amount');

        // 今日数据
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = time();
        $todayQuery = Order::whereBetween('create_time', [$todayStart, $todayEnd]);
        if ($staffId > 0) {
            $todayQuery->whereIn('id', function ($subQuery) use ($staffId) {
                $subQuery->name('order_item')
                    ->where('staff_id', $staffId)
                    ->field('order_id');
            });
        }
        $todayOrders = (clone $todayQuery)->count();
        $todayPaidOrders = (clone $todayQuery)->where('pay_status', Order::PAY_STATUS_PAID)->count();
        $todayAmount = (clone $todayQuery)->where('pay_status', Order::PAY_STATUS_PAID)->sum('pay_amount');

        return [
            'total_orders' => $totalOrders,
            'status_counts' => $statusCounts,
            'total_amount' => round($totalAmount, 2),
            'paid_amount' => round($paidAmount, 2),
            'today' => [
                'orders' => $todayOrders,
                'paid_orders' => $todayPaidOrders,
                'amount' => round($todayAmount, 2),
            ],
        ];
    }

    /**
     * @notes 获取订单状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return Order::getStatusOptions();
    }

    /**
     * @notes 获取支付方式选项
     * @return array
     */
    public static function getPayWayOptions(): array
    {
        return Order::getPayWayOptions();
    }

    /**
     * @notes 审核线下支付凭证
     * @param array $params
     * @return bool
     */
    public static function auditPayVoucher(array $params): bool
    {
        Db::startTrans();
        try {
            $order = Order::find($params['id']);
            if (!$order) {
                self::setError('订单不存在');
                return false;
            }

            if ($order->order_status != Order::STATUS_PENDING_PAY) {
                self::setError('当前订单状态不允许此操作');
                return false;
            }

            if ($order->pay_type != Order::PAY_WAY_OFFLINE || empty($order->pay_voucher)) {
                self::setError('订单未提交线下支付凭证');
                return false;
            }

            if ((int)($order->pay_voucher_status ?? -1) !== Order::VOUCHER_STATUS_PENDING) {
                self::setError('凭证已审核，请勿重复操作');
                return false;
            }

            $approved = (int)($params['approved'] ?? 0) === 1;
            $remark = $params['remark'] ?? '';

            $order->pay_voucher_status = $approved ? Order::VOUCHER_STATUS_APPROVED : Order::VOUCHER_STATUS_REJECTED;
            $order->pay_voucher_audit_admin_id = $params['admin_id'];
            $order->pay_voucher_audit_time = time();
            $order->pay_voucher_audit_remark = $remark;

            if ($approved) {
                $payType = Payment::TYPE_FULL;
                $payAmount = $order->pay_amount;

                if ($order->deposit_amount > 0) {
                    if (!$order->deposit_paid) {
                        $payType = Payment::TYPE_DEPOSIT;
                        $payAmount = $order->deposit_amount;
                        $order->deposit_paid = 1;
                        $order->pay_time = time();
                    } elseif (!$order->balance_paid) {
                        $payType = Payment::TYPE_BALANCE;
                        $payAmount = $order->balance_amount;
                        $order->balance_paid = 1;
                        $order->order_status = Order::STATUS_PAID;
                        $order->pay_status = Order::PAY_STATUS_PAID;
                        $order->pay_time = time();
                    } else {
                        self::setError('订单已完成支付');
                        return false;
                    }
                } else {
                    $order->order_status = Order::STATUS_PAID;
                    $order->pay_status = Order::PAY_STATUS_PAID;
                    $order->pay_time = time();
                }

                $payment = Payment::create([
                    'payment_sn' => Payment::generatePaymentSn(),
                    'order_id' => $order->id,
                    'order_sn' => $order->order_sn,
                    'user_id' => $order->user_id,
                    'pay_type' => $payType,
                    'pay_way' => Payment::WAY_OFFLINE,
                    'pay_amount' => $payAmount,
                    'pay_status' => Payment::STATUS_PAID,
                    'pay_time' => time(),
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

                $order->paid_amount = round((float)($order->paid_amount ?? 0) + (float)$payAmount, 2);
                $order->update_time = time();
                $order->save();

                $action = $payType == Payment::TYPE_DEPOSIT ? 'pay_deposit' : ($payType == Payment::TYPE_BALANCE ? 'pay_balance' : 'pay');
                OrderLog::addLog(
                    $order->id,
                    OrderLog::OPERATOR_ADMIN,
                    $params['admin_id'],
                    $action,
                    Order::STATUS_PENDING_PAY,
                    $order->order_status,
                    '线下凭证审核通过，金额：' . $payment->pay_amount
                );
            } else {
                $order->update_time = time();
                $order->save();

                OrderLog::addLog(
                    $order->id,
                    OrderLog::OPERATOR_ADMIN,
                    $params['admin_id'],
                    'voucher_reject',
                    $order->order_status,
                    $order->order_status,
                    '线下凭证审核拒绝' . ($remark ? '：' . $remark : '')
                );
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }
}
