<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\order;

use app\common\logic\BaseLogic;
use app\common\logic\OrderPayLogic;
use app\common\model\aftersale\ServiceCallback;
use app\common\model\financial\FinancialFlow;
use app\common\model\order\Order;
use app\common\model\order\OrderChange;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderItemAddon;
use app\common\model\order\OrderLog;
use app\common\model\order\Payment;
use app\common\service\MoneyService;
use app\common\model\service\ServicePackage;
use app\common\model\service\ServicePackageAddon;
use app\common\model\schedule\Schedule;
use app\common\model\staff\Staff;
use app\common\model\user\User;
use app\common\service\BookingFlowService;
use app\common\service\StaffScheduleConfirmLetterService;
use app\common\service\OrderNotificationService;
use app\common\service\OrderRefundService;
use app\common\service\PackageRegionPriceService;
use think\facade\Db;

/**
 * 订单业务逻辑
 * Class OrderLogic
 * @package app\adminapi\logic\order
 */
class OrderLogic extends BaseLogic
{
    private const PAYMENT_ENTRY_MODE_ONLINE_PENDING = 'online_pending';
    private const PAYMENT_ENTRY_MODE_OFFLINE_VOUCHER = 'offline_voucher';
    private const PAYMENT_ENTRY_MODE_OFFLINE_PAID = 'offline_paid';

    /**
     * @notes 服务人员确认订单（确认本人名下全部待确认项）
     * @param int $orderId
     * @param int $staffId
     * @param int $adminId
     * @return bool
     */
    public static function confirmByStaff(int $orderId, int $staffId, int $adminId): bool
    {
        $shouldNotifyUser = false;
        try {
            $result = Db::transaction(function () use ($orderId, $staffId, $adminId, &$shouldNotifyUser) {
                /** @var Order|null $order */
                $order = Order::where('id', $orderId)
                    ->lock(true)
                    ->find();

                if (!$order) {
                    self::setError('订单不存在');
                    return false;
                }

                if ((int)$order->order_status !== Order::STATUS_PENDING_CONFIRM) {
                    self::setError('当前订单状态不可确认');
                    return false;
                }

                $pendingItems = OrderItem::where('order_id', $orderId)
                    ->where('staff_id', $staffId)
                    ->where('confirm_status', 0)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                    ->lock(true)
                    ->select();

                if ($pendingItems->isEmpty()) {
                    self::setError('已确认或无可确认项');
                    return false;
                }

                foreach ($pendingItems as $item) {
                    $item->confirm_status = 1;
                    $item->update_time = time();
                    $item->save();
                }

                $remain = OrderItem::where('order_id', $orderId)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                    ->where('confirm_status', 0)
                    ->count();

                if ($remain === 0) {
                    $beforeStatus = (int)$order->order_status;
                    $order->order_status = Order::STATUS_PENDING_PAY;
                    $order->confirm_deadline_time = 0;
                    $order->update_time = time();
                    $order->save();
                    Order::syncPendingPayDeadline($order);
                    $shouldNotifyUser = true;

                    OrderLog::addLog(
                        $orderId,
                        OrderLog::OPERATOR_ADMIN,
                        $adminId,
                        'confirm',
                        $beforeStatus,
                        Order::STATUS_PENDING_PAY,
                        '服务人员确认全部本人项目，订单进入待支付'
                    );
                } else {
                    OrderLog::addLog(
                        $orderId,
                        OrderLog::OPERATOR_ADMIN,
                        $adminId,
                        'confirm_item',
                        (int)$order->order_status,
                        (int)$order->order_status,
                        '服务人员确认订单中的本人待确认项目'
                    );
                }

                return true;
            });

            if ($result && $shouldNotifyUser) {
                OrderNotificationService::notifyUserOnOrderConfirmed($orderId);
            }

            return $result;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 管理员确认订单（确认整单全部待确认项）
     * @param int $orderId
     * @param int $adminId
     * @return bool
     */
    public static function confirmByAdmin(int $orderId, int $adminId): bool
    {
        $shouldNotifyUser = false;
        try {
            $result = Db::transaction(function () use ($orderId, $adminId, &$shouldNotifyUser) {
                /** @var Order|null $order */
                $order = Order::where('id', $orderId)
                    ->lock(true)
                    ->find();

                if (!$order) {
                    self::setError('订单不存在');
                    return false;
                }

                if ((int)$order->order_status !== Order::STATUS_PENDING_CONFIRM) {
                    self::setError('当前订单状态不可确认');
                    return false;
                }

                $pendingItems = OrderItem::where('order_id', $orderId)
                    ->where('confirm_status', 0)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                    ->lock(true)
                    ->select();

                if ($pendingItems->isEmpty()) {
                    self::setError('已确认或无可确认项');
                    return false;
                }

                foreach ($pendingItems as $item) {
                    $item->confirm_status = 1;
                    $item->update_time = time();
                    $item->save();
                }

                $remain = OrderItem::where('order_id', $orderId)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
                    ->where('confirm_status', 0)
                    ->count();

                if ($remain === 0) {
                    $beforeStatus = (int)$order->order_status;
                    $order->order_status = Order::STATUS_PENDING_PAY;
                    $order->confirm_deadline_time = 0;
                    $order->update_time = time();
                    $order->save();
                    Order::syncPendingPayDeadline($order);
                    $shouldNotifyUser = true;

                    OrderLog::addLog(
                        $orderId,
                        OrderLog::OPERATOR_ADMIN,
                        $adminId,
                        'confirm',
                        $beforeStatus,
                        Order::STATUS_PENDING_PAY,
                        '管理员确认整单全部待确认项目，订单进入待支付'
                    );
                } else {
                    OrderLog::addLog(
                        $orderId,
                        OrderLog::OPERATOR_ADMIN,
                        $adminId,
                        'confirm_item',
                        (int)$order->order_status,
                        (int)$order->order_status,
                        '管理员确认订单中的部分待确认项目'
                    );
                }

                return true;
            });

            if ($result && $shouldNotifyUser) {
                OrderNotificationService::notifyUserOnOrderConfirmed($orderId);
            }

            return $result;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

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
                }, 'addons']);
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
        $data['receipt_requests'] = \app\common\service\OrderReceiptService::history($id);
        $data['receipt_pending'] = \app\common\service\OrderReceiptService::pending($id);
        $data['source_desc'] = (int)$order->source === Order::SOURCE_STAFF ? '服务人员录入' : ((int)$order->source === Order::SOURCE_ADMIN ? '后台' : '小程序');
        $data['order_status_desc'] = $order->order_status_desc;
        $data['pay_status_desc'] = $order->pay_status_desc;
        $payStatusDisplay = Order::buildPayStatusDisplayFromState($data);
        $data['pay_status_display_key'] = $payStatusDisplay['key'];
        $data['pay_status_display_desc'] = $payStatusDisplay['desc'];
        $data['pay_type_desc'] = $order->pay_type_desc;
        $data['payment_channel_desc'] = $order->payment_channel_desc;
        $data['pay_voucher_status_desc'] = $order->pay_voucher_status_desc ?? '';
        $data['service_region_text'] = $order->service_region_text;
        $data = array_merge($data, Order::getPaymentSummary($order));
        $data = array_merge($data, Order::getPayTimeoutSummary($order));
        $data = array_merge($data, Order::getConfirmTimeoutSummary($order));
        $data['refundable_amount'] = OrderRefundService::getRefundableAmount((int)$order->id);
        $data['can_admin_refund'] = OrderRefundService::canAdminApplyRefund($order);
        $data['admin_refund_modes'] = ['full', 'partial'];

        return $data;
    }

    /**
     * @notes 计算工作人员可见订单金额
     * @param array $order
     * @param int $staffId
     * @return array
     */
    public static function applyStaffVisibleOrderAmounts(array $order, int $staffId): array
    {
        $items = $order['items'] ?? [];
        $serviceAmount = 0.0;
        $addonAmount = 0.0;

        foreach ($items as $index => $item) {
            if ((int)($item['staff_id'] ?? 0) !== $staffId) {
                continue;
            }

            $subtotal = isset($item['subtotal']) ? (float)$item['subtotal'] : 0.0;
            if ($subtotal <= 0) {
                $price = (float)($item['price'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 1);
                $subtotal = $price * max($quantity, 1);
            }

            $itemAddonAmount = 0.0;
            foreach (($item['addons'] ?? []) as $addon) {
                $itemAddonAmount += (float)($addon['subtotal'] ?? $addon['price'] ?? 0);
            }
            $itemAddonAmount = round($itemAddonAmount, 2);
            $items[$index]['addon_amount'] = $itemAddonAmount;

            $serviceAmount += $subtotal;
            $addonAmount += $itemAddonAmount;
        }

        $order['items'] = $items;
        $serviceAmount = round($serviceAmount, 2);
        $addonAmount = round($addonAmount, 2);
        $visibleTotal = round($serviceAmount + $addonAmount, 2);

        $originTotalAmount = (float)($order['total_amount'] ?? 0);
        $originPayAmount = (float)($order['pay_amount'] ?? 0);
        $discountTotal = (float)($order['discount_amount'] ?? 0);
        $paidTotal = (float)($order['paid_amount'] ?? 0);

        $staffDiscount = 0.0;
        if ($originTotalAmount > 0 && $visibleTotal > 0) {
            $staffDiscount = round($discountTotal * ($visibleTotal / $originTotalAmount), 2);
        }

        $staffPayAmount = round($visibleTotal - $staffDiscount, 2);
        if ($staffPayAmount < 0) {
            $staffPayAmount = 0.0;
        }

        $staffPaidAmount = 0.0;
        if ($originPayAmount > 0 && $staffPayAmount > 0 && $paidTotal > 0) {
            $staffPaidAmount = round($paidTotal * ($staffPayAmount / $originPayAmount), 2);
            if ($staffPaidAmount > $staffPayAmount) {
                $staffPaidAmount = $staffPayAmount;
            }
        }

        $order['service_amount'] = $serviceAmount;
        $order['addon_amount'] = $addonAmount;
        $order['total_amount'] = $visibleTotal;
        $order['discount_amount'] = $staffDiscount;
        $order['pay_amount'] = $staffPayAmount;
        $order['paid_amount'] = $staffPaidAmount;

        return $order;
    }

    /**
     * @notes 后台建单支付预估
     * @param array $params
     * @return array
     */
    public static function estimatePayment(array $params): array
    {
        $items = $params['items'] ?? [];
        $totalAmount = 0.0;

        foreach ($items as $item) {
            $price = (float) ($item['price'] ?? 0);
            $quantity = max((int) ($item['quantity'] ?? 1), 1);
            $totalAmount += $price * $quantity;
        }

        $totalAmount = round($totalAmount, 2);
        $discountAmount = round(max((float) ($params['discount_amount'] ?? 0), 0), 2);
        $payAmount = round(max($totalAmount - $discountAmount, 0), 2);
        $paymentSplit = Order::calculatePaymentSplit($payAmount);

        return [
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'pay_amount' => $payAmount,
            'deposit_amount' => round((float) $paymentSplit['deposit_amount'], 2),
            'balance_amount' => round((float) $paymentSplit['balance_amount'], 2),
            'payment_mode' => (int) $paymentSplit['deposit_mode_enabled'] === 1 ? 'deposit' : 'full',
            'payment_mode_desc' => (int) $paymentSplit['deposit_mode_enabled'] === 1 ? '定金支付' : '全款支付',
            'deposit_type' => (string) $paymentSplit['deposit_type'],
            'deposit_value' => round((float) $paymentSplit['deposit_value'], 2),
            'deposit_remark' => (string) $paymentSplit['deposit_remark'],
        ];
    }

    /**
     * @notes 解析后台建单付款录入模式
     * @param array $params
     * @return string
     */
    private static function resolveOfflinePaymentEntryMode(array $params): string
    {
        return \app\common\service\ManualOrderService::resolveOfflinePaymentEntryMode($params);
    }

    /**
     * @notes 获取后台建单付款录入模式文案
     * @param string $entryMode
     * @return string
     */
    private static function getOfflinePaymentEntryModeDesc(string $entryMode): string
    {
        return \app\common\service\ManualOrderService::getOfflinePaymentEntryModeDesc($entryMode);
    }

    /**
     * @notes 构造后台建单金额预估
     * @param array $summary
     * @param float $discountAmount
     * @param string $entryMode
     * @return array
     */
    private static function buildOfflineEstimatePayload(array $summary, float $discountAmount, string $entryMode): array
    {
        return \app\common\service\ManualOrderService::buildOfflineEstimatePayload($summary, $discountAmount, $entryMode);
    }

    /**
     * @notes 获取线下建单主套餐
     * @param array $params
     * @return array
     */
    public static function getOfflineMainPackages(array $params): array
    {
        return \app\common\service\ManualOrderService::getOfflineMainPackages($params);
    }

    /**
     * @notes 获取线下建单协作角色候选人
     * @param array $params
     * @return array
     */
    public static function getOfflineRoleCandidates(array $params): array
    {
        return \app\common\service\ManualOrderService::getOfflineRoleCandidates($params);
    }

    /**
     * @notes 线下建单金额预估
     * @param array $params
     * @return array
     */
    public static function estimateOffline(array $params): array
    {
        return \app\common\service\ManualOrderService::estimateOffline($params);
    }

    /**
     * @notes 新增线下订单
     * @param array $params
     * @return bool
     */
    public static function addOffline(array $params): bool
    {
        try {
            \app\common\service\ManualOrderService::create($params);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 后台创建订单
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        $createdOrderId = 0;

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

            $paymentSplit = Order::calculatePaymentSplit((float) $payAmount);
            $depositAmount = (float) $paymentSplit['deposit_amount'];
            $balanceAmount = (float) $paymentSplit['balance_amount'];

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
                'pay_amount' => $payAmount,
                'deposit_amount' => $depositAmount,
                'balance_amount' => $balanceAmount,
                'deposit_mode_enabled' => (int) $paymentSplit['deposit_mode_enabled'],
                'deposit_type_snapshot' => (string) $paymentSplit['deposit_type'],
                'deposit_value_snapshot' => (float) $paymentSplit['deposit_value'],
                'deposit_remark_snapshot' => (string) $paymentSplit['deposit_remark'],
                'service_date' => $params['service_date'] ?? null,
                'service_time_slot' => 0,
                'service_address' => $params['service_address'] ?? '',
                'service_province_code' => $params['province_code'] ?? '',
                'service_province' => $params['province_name'] ?? '',
                'service_city_code' => $params['city_code'] ?? '',
                'service_city' => $params['city_name'] ?? '',
                'service_district_code' => $params['district_code'] ?? '',
                'service_district' => $params['district_name'] ?? '',
                'contact_name' => $params['contact_name'] ?? '',
                'contact_mobile' => $params['contact_mobile'] ?? '',
                'admin_remark' => $params['admin_remark'] ?? '',
                'source' => Order::SOURCE_ADMIN,
                'pay_type' => Order::PAY_WAY_NONE,
                'payment_channel' => Order::PAYMENT_CHANNEL_ONLINE,
                'confirm_deadline_time' => 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 创建订单项
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'staff_id' => $item['staff_id'] ?? 0,
                    'package_id' => $item['package_id'] ?? 0,
                    'schedule_id' => 0,
                    'service_date' => $item['service_date'] ?? $params['service_date'],
                    'time_slot' => 0,
                    'staff_name' => $item['staff_name'] ?? '',
                    'package_name' => $item['package_name'] ?? '',
                    'package_description' => OrderItem::resolvePackageDescription(
                        (int)($item['package_id'] ?? 0),
                        (string)($item['package_description'] ?? '')
                    ),
                    'price' => $item['price'],
                    'quantity' => $item['quantity'] ?? 1,
                    'subtotal' => $item['subtotal'],
                    'confirm_status' => 1,
                    'remark' => $item['remark'] ?? '',
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

            }

            Order::syncPendingPayDeadline($order);

            // 记录日志
            OrderLog::addLog($order->id, OrderLog::OPERATOR_ADMIN, $params['admin_id'], 'create', 0, Order::STATUS_PENDING_PAY, '后台创建订单');

            $createdOrderId = (int)$order->id;

            if ($createdOrderId > 0) {
                OrderNotificationService::notifyUserOnOrderConfirmed($createdOrderId, '后台已为您创建订单', '订单%s已创建，请尽快完成支付。', '订单已创建，请支付', 'order_created');
            }

            Db::commit();

            if ($createdOrderId > 0) {
                OrderNotificationService::notifyUserOnOrderConfirmed($createdOrderId, '后台已为您创建订单', '订单%s已创建，请尽快完成支付。', '订单已创建，请支付', 'order_created');
            }
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
            if (in_array($order->order_status, [Order::STATUS_COMPLETED, Order::STATUS_REVIEWED, Order::STATUS_CANCELLED, Order::STATUS_PAUSED, Order::STATUS_REFUNDED, Order::STATUS_USER_DELETED], true)) {
                self::setError('当前订单状态不允许编辑');
                return false;
            }

            if (isset($params['service_date']) && $params['service_date'] !== (string)$order->service_date) {
                throw new \RuntimeException('服务日期请通过改期操作修改');
            }
            $updateData = [];
            $allowFields = [
                'service_address',
                'service_province_code',
                'service_province',
                'service_city_code',
                'service_city',
                'service_district_code',
                'service_district',
                'contact_name',
                'contact_mobile',
                'admin_remark'
            ];

            if (isset($params['province_code'])) {
                $params['service_province_code'] = $params['province_code'];
            }
            if (isset($params['province_name'])) {
                $params['service_province'] = $params['province_name'];
            }
            if (isset($params['city_code'])) {
                $params['service_city_code'] = $params['city_code'];
            }
            if (isset($params['city_name'])) {
                $params['service_city'] = $params['city_name'];
            }
            if (isset($params['district_code'])) {
                $params['service_district_code'] = $params['district_code'];
            }
            if (isset($params['district_name'])) {
                $params['service_district'] = $params['district_name'];
            }
            
            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['update_time'] = time();
                Order::where('id', $params['id'])->update($updateData);

                StaffScheduleConfirmLetterService::markOutdatedByOrderId((int) $params['id']);

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
        [$success, $message] = Order::startService($orderId, $adminId, OrderLog::OPERATOR_ADMIN, '开始服务');
        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
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
     * @notes 后台直接改期
     * @param array $params
     * @param int $staffScopeId
     * @return array|false
     */
    public static function directReschedule(array $params, int $staffScopeId = 0)
    {
        $orderId = (int)($params['id'] ?? 0);
        $serviceDate = (string)($params['service_date'] ?? '');
        $adminId = (int)($params['admin_id'] ?? 0);
        $reason = (string)($params['reason'] ?? '');

        [$success, $message, $changeId] = OrderChange::directDateReschedule(
            $orderId,
            $serviceDate,
            $adminId,
            $reason,
            $staffScopeId
        );
        if (!$success) {
            self::setError($message);
            return false;
        }

        OrderNotificationService::notifyUserOnDateChangeExecuted($changeId);
        OrderNotificationService::notifyStaffOnDateChangeExecuted($changeId);

        return [
            'change_id' => $changeId,
            'service_date' => date('Y-m-d', strtotime($serviceDate)),
        ];
    }

    /**
     * @notes 删除订单（后台软删除）
     * @param int $orderId
     * @param int $adminId
     * @return bool
     */
    public static function delete(int $orderId, int $adminId): bool
    {
        Db::startTrans();
        try {
            $order = Order::where('id', $orderId)
                ->lock(true)
                ->find();
            if (!$order) {
                self::setError('订单不存在');
                Db::rollback();
                return false;
            }

            if ((int)$order->order_status !== Order::STATUS_USER_DELETED) {
                self::setError('仅支持删除用户已删除的订单');
                Db::rollback();
                return false;
            }

            $beforeStatus = (int)$order->order_status;
            $targetOrderId = (int)$order->id;
            $order->delete();

            OrderLog::addLog(
                $targetOrderId,
                OrderLog::OPERATOR_ADMIN,
                $adminId,
                'admin_delete',
                $beforeStatus,
                $beforeStatus,
                '后台彻底删除订单'
            );

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 确认线下支付
     * @param array $params
     * @return bool
     */
    public static function confirmOfflinePay(array $params): bool
    {
        $notifyOrderId = 0;
        $notifyPayType = Payment::TYPE_FULL;
        $notifyCompleted = false;

        Db::startTrans();
        try {
            $order = Order::where('id', (int)$params['id'])->lock(true)->find();
            if (!$order) {
                throw new \RuntimeException('订单不存在');
            }

            if ((int)$order->order_status !== Order::STATUS_PENDING_PAY) {
                throw new \RuntimeException('当前订单状态不允许此操作');
            }

            if ($order->getResolvedPaymentChannel() !== Order::PAYMENT_CHANNEL_OFFLINE) {
                throw new \RuntimeException('当前订单不是线下付款订单');
            }

            if ($order->isOfflineVoucherPending()) {
                throw new \RuntimeException('当前订单已提交支付凭证，请先完成凭证审核');
            }

            $payContext = OrderPayLogic::getCurrentPayContext($order);
            if ($payContext === false) {
                throw new \RuntimeException(OrderPayLogic::getError() ?: '当前订单无需支付');
            }

            $payType = (int)$payContext['pay_type'];
            $expectedPayAmount = round((float)$payContext['pay_amount'], 2);
            $payAmount = round((float)($params['pay_amount'] ?? $expectedPayAmount), 2);
            if (isset($params['pay_type']) && (int)$params['pay_type'] !== $payType) {
                throw new \RuntimeException('支付阶段已变化，请刷新订单后重试');
            }
            self::validateOfflinePayAmount($order, $payType, $payAmount, $expectedPayAmount);
            $receipt = Payment::validateOfflineReceipt($params);

            // 先写入支付记录，提交后再处理冲突订单，避免并发抢档交叉持锁。
            if (Order::isFirstPaidStage($payType)) {
                Order::lockSchedulesAfterFirstPayment($order);
            }

            // 创建支付记录
            $payment = Payment::create([
                'payment_sn' => Payment::generatePaymentSn(),
                'order_id' => $order->id,
                'order_sn' => $order->order_sn,
                'user_id' => $order->user_id,
                'pay_type' => $payType,
                'pay_way' => Payment::WAY_OFFLINE,
                    'collection_owner' => $receipt['collection_owner'],
                    'pay_voucher' => $receipt['pay_voucher'],
                'pay_amount' => $payAmount,
                'pay_status' => Payment::STATUS_PAID,
                'pay_time' => time(),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            Order::applyPaidStateAfterPayment($order, (int)$payType, (int)$payment->pay_time);
            $order->pay_type = Order::PAY_WAY_OFFLINE;
            $order->payment_channel = Order::PAYMENT_CHANNEL_OFFLINE;
            $order->paid_amount = round((float)($order->paid_amount ?? 0) + (float)$payAmount, 2);
            StaffScheduleConfirmLetterService::markOutdatedByOrderId((int)$order->id);
            $voucher = trim((string)($params['voucher'] ?? ''));
            if ($voucher !== '') {
                $order->pay_voucher = $voucher;
                $order->pay_voucher_status = Order::VOUCHER_STATUS_APPROVED;
                $order->pay_voucher_audit_admin_id = (int)$params['admin_id'];
                $order->pay_voucher_audit_time = time();
                $order->pay_voucher_audit_remark = '后台确认线下收款';
            }
            $order->update_time = time();
            $order->save();

            self::recordSuccessfulPaymentFlow($order, $payment, (int)$params['admin_id']);

            // 记录日志
            $action = $payType == Payment::TYPE_DEPOSIT ? 'pay_deposit' : ($payType == Payment::TYPE_BALANCE ? 'pay_balance' : 'pay');
            OrderLog::addLog(
                $order->id,
                OrderLog::OPERATOR_ADMIN,
                $params['admin_id'],
                $action,
                Order::STATUS_PENDING_PAY,
                $order->order_status,
                '确认线下支付，金额：' . $payAmount . ($voucher !== '' ? '，已上传凭证' : '')
            );

            $notifyOrderId = (int)$order->id;
            $notifyPayType = (int)$payType;
            $notifyCompleted = (int)$order->order_status === Order::STATUS_COMPLETED;

            Db::commit();

            if ($notifyOrderId > 0) {
                OrderNotificationService::notifyUserAndStaffOnPaymentSuccess($notifyOrderId, $notifyPayType);
                if ($notifyCompleted) {
                    ServiceCallback::autoCreateAfterServiceCallback($notifyOrderId);
                    OrderNotificationService::notifyOnOrderCompleted($notifyOrderId);
                }
            }
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
        $startDate = trim((string)($params['start_date'] ?? ''));
        $endDate = trim((string)($params['end_date'] ?? ''));
        $staffId = (int)($params['staff_id'] ?? 0);

        $query = Order::where([]);
        if ($startDate !== '' || $endDate !== '') {
            $startTime = strtotime($startDate !== '' ? $startDate : '1970-01-01');
            $endTime = strtotime(($endDate !== '' ? $endDate : date('Y-m-d')) . ' 23:59:59');
            $query->whereBetween('create_time', [$startTime, $endTime]);
        }

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
            Order::STATUS_PENDING_SERVICE => '待服务',
            Order::STATUS_IN_SERVICE => '服务中',
            Order::STATUS_COMPLETED => '已完成',
            Order::STATUS_REVIEWED => '已评价',
            Order::STATUS_CANCELLED => '已取消',
            Order::STATUS_PAUSED => '已暂停',
            Order::STATUS_REFUNDING => '退款中',
            Order::STATUS_REFUNDED => '已退款',
            Order::STATUS_USER_DELETED => '用户已删除',
        ] as $status => $label) {
            $statusCounts[] = [
                'status' => $status,
                'label' => $label,
                'count' => (clone $query)->where('order_status', $status)->count(),
            ];
        }

        // 总金额
        $totalAmount = (clone $query)->sum('total_amount');
        $paidAmount = (clone $query)->sum('paid_amount');

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
        $todayPaidOrders = (clone $todayQuery)->where('paid_amount', '>', 0)->count();
        $todayAmount = (clone $todayQuery)->sum('paid_amount');

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
        if (\app\common\service\OrderReceiptService::pending((int)$params['id'])) {
            self::setError('请在独立收款申请中审核，不得重复审核旧凭证');
            return false;
        }
        $notifyOrderId = 0;
        $notifyPayType = Payment::TYPE_FULL;
        $notifyVoucherRejected = false;
        $notifyCompleted = false;

        Db::startTrans();
        try {
            $order = Order::where('id', (int)$params['id'])->lock(true)->find();
            if (!$order) {
                throw new \RuntimeException('订单不存在');
            }

            if ((int)$order->order_status !== Order::STATUS_PENDING_PAY) {
                throw new \RuntimeException('当前订单状态不允许此操作');
            }

            if ($order->getResolvedPaymentChannel() !== Order::PAYMENT_CHANNEL_OFFLINE) {
                throw new \RuntimeException('当前订单不是线下付款订单');
            }

            if (empty($order->pay_voucher)) {
                throw new \RuntimeException('订单未提交线下支付凭证');
            }

            if ((int)($order->pay_voucher_status ?? -1) !== Order::VOUCHER_STATUS_PENDING) {
                throw new \RuntimeException('凭证已审核，请勿重复操作');
            }

            $approved = (int)($params['approved'] ?? 0) === 1;
            $remark = $params['remark'] ?? '';

            $order->payment_channel = Order::PAYMENT_CHANNEL_OFFLINE;
            $order->pay_voucher_status = $approved ? Order::VOUCHER_STATUS_APPROVED : Order::VOUCHER_STATUS_REJECTED;
            $order->pay_voucher_audit_admin_id = $params['admin_id'];
            $order->pay_voucher_audit_time = time();
            $order->pay_voucher_audit_remark = $remark;

            if ($approved) {
                $payContext = OrderPayLogic::getCurrentPayContext($order);
                if ($payContext === false) {
                    throw new \RuntimeException(OrderPayLogic::getError() ?: '订单已完成支付');
                }

                $payType = (int)$payContext['pay_type'];
                $payAmount = round((float)$payContext['pay_amount'], 2);
                self::validateOfflinePayAmount($order, $payType, $payAmount, $payAmount);
                $receipt = Payment::validateOfflineReceipt(array_merge($params, ['voucher' => $order->pay_voucher]));

                if (Order::isFirstPaidStage($payType)) {
                    Order::lockSchedulesAfterFirstPayment($order);
                }

                $order->pay_type = Order::PAY_WAY_OFFLINE;
                $payment = Payment::create([
                    'payment_sn' => Payment::generatePaymentSn(),
                    'order_id' => $order->id,
                    'order_sn' => $order->order_sn,
                    'user_id' => $order->user_id,
                    'pay_type' => $payType,
                    'pay_way' => Payment::WAY_OFFLINE,
                    'collection_owner' => $receipt['collection_owner'],
                    'pay_voucher' => $receipt['pay_voucher'],
                    'pay_amount' => $payAmount,
                    'pay_status' => Payment::STATUS_PAID,
                    'pay_time' => time(),
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

                Order::applyPaidStateAfterPayment($order, (int)$payType, (int)$payment->pay_time);
                $order->paid_amount = round((float)($order->paid_amount ?? 0) + (float)$payAmount, 2);
                StaffScheduleConfirmLetterService::markOutdatedByOrderId((int)$order->id);
                $order->update_time = time();
                $order->save();

                self::recordSuccessfulPaymentFlow($order, $payment, (int)$params['admin_id']);

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

                $notifyOrderId = (int)$order->id;
                $notifyPayType = (int)$payType;
                $notifyCompleted = (int)$order->order_status === Order::STATUS_COMPLETED;
            } else {
                if ($order->isInFirstPendingPaymentStage()) {
                    Order::syncPendingPayDeadline($order, time(), false);
                }
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

                $notifyOrderId = (int)$order->id;
                $notifyVoucherRejected = true;
            }

            Db::commit();

            if ($notifyVoucherRejected && $notifyOrderId > 0) {
                OrderNotificationService::notifyUserOnOfflineVoucherRejected($notifyOrderId);
            }

            if (!$notifyVoucherRejected && $notifyOrderId > 0) {
                OrderNotificationService::notifyUserAndStaffOnPaymentSuccess($notifyOrderId, $notifyPayType);
                if ($notifyCompleted) {
                    ServiceCallback::autoCreateAfterServiceCallback($notifyOrderId);
                    OrderNotificationService::notifyOnOrderCompleted($notifyOrderId);
                }
            }
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 确认到账不得修改报价，必须与当前阶段应收一致
     */
    private static function validateOfflinePayAmount(Order $order, int $payType, float $payAmount, float $expectedPayAmount): void
    {
        if ($payType === Payment::TYPE_DEPOSIT) {
            throw new \RuntimeException('定金必须通过微信支付');
        }
        $amount = MoneyService::yuanToFen($payAmount);
        $remaining = MoneyService::yuanToFen($order->pay_amount) - MoneyService::yuanToFen($order->paid_amount);
        if ($amount <= 0 || $amount > $remaining) {
            throw new \RuntimeException('收款金额必须大于零且不能超过剩余应收');
        }
        if ($amount !== MoneyService::yuanToFen($expectedPayAmount)) {
            throw new \RuntimeException('收款金额必须等于当前阶段应收金额');
        }
    }

    /**
     * @notes 记录支付资金流水
     * @param Order $order
     * @param Payment $payment
     * @param int $adminId
     * @return void
     */
    private static function recordSuccessfulPaymentFlow(Order $order, Payment $payment, int $adminId): void
    {
        if (!$payment->isPlatformCollection()) {
            return;
        }
        $exists = FinancialFlow::where('biz_type', FinancialFlow::BIZ_TYPE_ORDER_PAY)
            ->where('biz_id', (int)$payment->id)
            ->find();
        if ($exists) {
            return;
        }

        FinancialFlow::createFlow([
            'flow_type' => FinancialFlow::FLOW_TYPE_INCOME,
            'biz_type' => FinancialFlow::BIZ_TYPE_ORDER_PAY,
            'biz_id' => (int)$payment->id,
            'biz_sn' => (string)$payment->payment_sn,
            'order_id' => (int)$order->id,
            'user_id' => (int)$order->user_id,
            'amount' => round((float)$payment->pay_amount, 2),
            'direction' => FinancialFlow::DIRECTION_IN,
            'pay_way' => FinancialFlow::PAY_WAY_OFFLINE,
            'transaction_id' => (string)($payment->transaction_id ?? ''),
            'remark' => '订单线下支付入账',
            'operator_type' => OrderLog::OPERATOR_ADMIN,
            'operator_id' => $adminId,
        ]);
    }

    /**
     * @notes 构造线下建单选择结果
     * @param array $params
     * @return array
     */
    protected static function buildOfflineSelection(array $params): array
    {
        return \app\common\service\ManualOrderService::buildOfflineSelection($params);
    }

    /**
     * @notes 解析线下建单用户
     * @param array $params
     * @return int
     */
    protected static function resolveOfflineOrderUserId(array $params): int
    {
        return \app\common\service\ManualOrderService::resolveOfflineOrderUserId($params);
    }

    /**
     * @notes 获取线下建单可选套餐
     * @param int $staffId
     * @param array $regionContext
     * @return array
     */
    protected static function getOfflinePackagesByStaff(int $staffId, array $regionContext): array
    {
        return \app\common\service\ManualOrderService::getOfflinePackagesByStaff($staffId, $regionContext);
    }

    /**
     * @notes 解析线下建单地区上下文
     * @param array $params
     * @return array
     */
    protected static function resolveOfflineRegionContext(array $params): array
    {
        return \app\common\service\ManualOrderService::resolveOfflineRegionContext($params);
    }
}
