<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订单逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\logic\OrderPayLogic;
use app\common\model\order\Order;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderLog;
use app\common\model\order\Payment;
use app\common\model\order\RefundItem;
use app\common\model\package\PackageBooking;
use app\common\model\order\Refund;
use app\common\model\schedule\Schedule;
use app\common\model\service\ServicePackage;
use app\common\model\staff\Staff;
use app\common\service\BookingFlowService;
use app\common\service\OrderConfirmLetterService;
use app\common\service\OrderNotificationService;
use app\common\service\OrderRefundService;
use app\common\service\PackageRegionPriceService;
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
    private static function buildSelectedItems(array $params, int $userId = 0): array
    {
        $staffId = (int)($params['staff_id'] ?? 0);
        $packageId = (int)($params['package_id'] ?? 0);
        $date = (string)($params['date'] ?? '');
        $regionContext = self::resolveRegionContext($params);

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

        $resolvedPrice = PackageRegionPriceService::resolvePackagePrice($packageId, $regionContext);
        if (!($resolvedPrice['available'] ?? false)) {
            throw new \Exception('当前套餐暂不支持所选区县');
        }

        $selectedItems = [[
            'staff_id' => $staffId,
            'package_id' => $packageId,
            'schedule_id' => 0,
            'schedule_date' => $date,
            'time_slot' => 0,
            'price' => round((float)($resolvedPrice['price'] ?? 0), 2),
            'quantity' => 1,
            'item_type' => OrderItem::TYPE_SERVICE,
            'item_meta' => [
                'role_key' => '',
                'role_label' => '',
            ],
            'remark' => $params['remark'] ?? '',
            'region_context' => $regionContext,
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
                'price' => round((float)($resolvedPrice['price'] ?? 0), 2),
                'original_price' => round((float)$package->original_price, 2),
                'description' => (string)($package->description ?? ''),
                'image' => (string)($package->image ?? ''),
            ],
            'addons' => [],
        ]];

        $selectedAddons = BookingFlowService::resolveSelectedAddons(
            (int)$staff->id,
            BookingFlowService::normalizeAddonIds($params['addon_ids'] ?? [])
        );
        foreach ($selectedAddons as $addon) {
            $selectedItems[] = [
                'staff_id' => $staffId,
                'package_id' => 0,
                'schedule_id' => 0,
                'schedule_date' => $date,
                'time_slot' => 0,
                'price' => round((float)($addon['price'] ?? 0), 2),
                'quantity' => 1,
                'item_type' => OrderItem::TYPE_CUSTOM_OPTION,
                'item_meta' => [
                    'addon_id' => (int)($addon['id'] ?? 0),
                    'label' => (string)($addon['name'] ?? ''),
                ],
                'remark' => '',
                'region_context' => $regionContext,
                'staff' => [
                    'id' => (int)$staff->id,
                    'name' => (string)$staff->name,
                    'avatar' => (string)$staff->avatar,
                    'category_id' => (int)$staff->category_id,
                ],
                'package' => [
                    'id' => (int)($addon['id'] ?? 0),
                    'name' => (string)($addon['name'] ?? ''),
                    'category_id' => (int)($addon['category_id'] ?? $staff->category_id),
                    'price' => round((float)($addon['price'] ?? 0), 2),
                    'original_price' => round((float)($addon['original_price'] ?? $addon['price'] ?? 0), 2),
                    'description' => (string)($addon['description'] ?? ''),
                    'image' => (string)($addon['image'] ?? ''),
                ],
                'addons' => [],
            ];
        }

        foreach ([
            BookingFlowService::ROLE_BUTLER => [
                'staff_id' => (int)($params['butler_staff_id'] ?? 0),
                'package_id' => (int)($params['butler_package_id'] ?? 0),
            ],
            BookingFlowService::ROLE_DIRECTOR => [
                'staff_id' => (int)($params['director_staff_id'] ?? 0),
                'package_id' => (int)($params['director_package_id'] ?? 0),
            ],
        ] as $roleKey => $selection) {
            if ($selection['staff_id'] <= 0 || $selection['package_id'] <= 0) {
                continue;
            }

            $candidate = BookingFlowService::resolveSelectedRoleCandidate(
                $staffId,
                $roleKey,
                $selection['staff_id'],
                $selection['package_id'],
                $regionContext,
                $date,
                $userId
            );

            $selectedItems[] = [
                'staff_id' => (int)$candidate['staff_id'],
                'package_id' => (int)$candidate['package_id'],
                'schedule_id' => 0,
                'schedule_date' => $date,
                'time_slot' => 0,
                'price' => round((float)($candidate['price'] ?? 0), 2),
                'quantity' => 1,
                'item_type' => OrderItem::TYPE_RELATED_STAFF,
                'item_meta' => [
                    'role_key' => $roleKey,
                    'role_label' => (string)($candidate['role_label'] ?? BookingFlowService::getRoleLabel($roleKey)),
                ],
                'remark' => '',
                'region_context' => $regionContext,
                'staff' => [
                    'id' => (int)$candidate['staff_id'],
                    'name' => (string)($candidate['name'] ?? ''),
                    'avatar' => (string)($candidate['avatar'] ?? ''),
                    'category_id' => (int)($candidate['category_id'] ?? 0),
                ],
                'package' => [
                    'id' => (int)$candidate['package_id'],
                    'name' => (string)($candidate['package_name'] ?? ''),
                    'category_id' => (int)($candidate['category_id'] ?? 0),
                    'price' => round((float)($candidate['price'] ?? 0), 2),
                    'original_price' => round((float)($candidate['original_price'] ?? 0), 2),
                    'description' => (string)($candidate['package_description'] ?? ''),
                    'image' => '',
                ],
                'addons' => [],
            ];
        }

        return $selectedItems;
    }

    /**
     * @notes 校验预约日期是否可下单
     */
    private static function ensureScheduleAvailable(array $selectedItems, int $userId = 0): void
    {
        foreach ($selectedItems as $item) {
            if (!self::itemRequiresScheduleLock($item)) {
                continue;
            }

            [$available, $reason] = Schedule::checkAvailabilityForUserWithReason(
                (int)$item['staff_id'],
                (string)$item['schedule_date'],
                $userId,
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
        if (!self::itemRequiresPackageLock($selectedItem)) {
            return;
        }

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
        if (!self::itemRequiresPackageLock($selectedItem)) {
            return;
        }

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
        $serviceAmount = 0;
        $addonAmount = 0;
        $staffIds = [];
        $categoryIds = [];

        foreach ($selectedItems as $item) {
            $itemAmount = round((float)$item['price'] * (int)($item['quantity'] ?? 1), 2);
            if ((int)($item['item_type'] ?? OrderItem::TYPE_SERVICE) === OrderItem::TYPE_SERVICE) {
                $serviceAmount += $itemAmount;
            } else {
                $addonAmount += $itemAmount;
            }
            $staffIds[] = (int)($item['staff_id'] ?? 0);

            $packageCategoryId = (int)($item['package']['category_id'] ?? 0);
            $staffCategoryId = (int)($item['staff']['category_id'] ?? 0);
            if ($packageCategoryId > 0) {
                $categoryIds[] = $packageCategoryId;
            } elseif ($staffCategoryId > 0) {
                $categoryIds[] = $staffCategoryId;
            }

            foreach (($item['addons'] ?? []) as $addon) {
                $addonAmount += (float)($addon['price'] ?? 0) * (int)($addon['quantity'] ?? 1);
            }
        }

        $serviceAmount = round($serviceAmount, 2);
        $addonAmount = round($addonAmount, 2);

        return [
            'service_amount' => $serviceAmount,
            'addon_amount' => $addonAmount,
            'total_amount' => round($serviceAmount + $addonAmount, 2),
            'staff_ids' => array_values(array_unique(array_filter($staffIds))),
            'category_ids' => array_values(array_unique(array_filter($categoryIds))),
        ];
    }

    /**
     * @notes 获取用户订单列表
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getUserOrders(int $userId, array $params): array
    {
        $query = Order::where('user_id', $userId)
            ->where('order_status', '<>', Order::STATUS_USER_DELETED);

        // 状态筛选
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('order_status', $params['status']);
        }

        // 搜索
        if (!empty($params['keyword'])) {
            $query->where('order_sn|contact_name|contact_mobile', 'like', '%' . $params['keyword'] . '%');
        }

        $list = $query->with(['items' => function ($q) {
                $q->field('id, order_id, staff_id, staff_name, package_name, service_date, item_status, item_type, item_meta, price, quantity, subtotal')
                    ->order('id', 'asc')
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
            $payStatusDisplay = Order::buildPayStatusDisplayFromState($item);
            $item['pay_status_display_key'] = $payStatusDisplay['key'];
            $item['pay_status_display_desc'] = $payStatusDisplay['desc'];
            $item['payment_channel'] = Order::resolvePaymentChannel(
                $item['payment_channel'] ?? null,
                $item['pay_type'] ?? null,
                $item['pay_voucher'] ?? ''
            );
            $item['payment_channel_desc'] = Order::getPaymentChannelText((int)$item['payment_channel']);
            $item['service_region_text'] = implode(' ', array_filter([
                trim((string)($item['service_province'] ?? '')),
                trim((string)($item['service_city'] ?? '')),
                trim((string)($item['service_district'] ?? '')),
            ]));

            $item = array_merge($item, Order::buildPaymentSummaryFromState($item));

            $item = array_merge($item, Order::buildConfirmTimeoutSummaryFromState(
                (int)($item['order_status'] ?? Order::STATUS_PENDING_CONFIRM),
                (int)($item['confirm_deadline_time'] ?? 0)
            ));
            $item = array_merge($item, Order::buildPayTimeoutSummaryFromState(
                (int)($item['order_status'] ?? Order::STATUS_PENDING_PAY),
                (int)($item['pay_deadline_time'] ?? 0)
            ));
            $item['can_user_complete'] = (int)($item['order_status'] ?? -1) === Order::STATUS_IN_SERVICE
                && Order::canUserCompleteService();

            if (
                (int)($item['payment_channel'] ?? Order::PAYMENT_CHANNEL_ONLINE) === Order::PAYMENT_CHANNEL_OFFLINE
                && !empty($item['pay_voucher'])
                && (int)($item['pay_voucher_status'] ?? -1) === Order::VOUCHER_STATUS_PENDING
            ) {
                $item['pay_deadline_time'] = 0;
                $item['pay_remain_seconds'] = 0;
                $item['pay_timeout_action_desc'] = '';
            }
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
                }, 'addons']);
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

        if ((int)$order->order_status === Order::STATUS_USER_DELETED) {
            return null;
        }

        if (Order::syncExpiredAutoCancel($order)) {
            $order = Order::with([
                'items' => function ($query) {
                    $query->with(['staff' => function ($q) {
                        $q->field('id, name, avatar');
                    }, 'addons']);
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

            if ((int)$order->order_status === Order::STATUS_USER_DELETED) {
                return null;
            }
        }

        $data = $order->toArray();
        $data['order_status_desc'] = self::getStatusDesc((int)$order->order_status);
        $data['pay_status_desc'] = self::getPayStatusDesc((int)$order->pay_status);
        $payStatusDisplay = Order::buildPayStatusDisplayFromState($data);
        $data['pay_status_display_key'] = $payStatusDisplay['key'];
        $data['pay_status_display_desc'] = $payStatusDisplay['desc'];
        $data['pay_type_desc'] = self::getPayTypeDesc((int)$order->pay_type);
        $data['payment_channel_desc'] = $order->payment_channel_desc;
        $data['pay_voucher_status_desc'] = $order->pay_voucher_status_desc ?? '';
        $data['service_region_text'] = $order->service_region_text;
        $data = array_merge($data, Order::getPaymentSummary($order));
        $data = array_merge($data, Order::getPayTimeoutSummary($order));
        $data = array_merge($data, Order::getConfirmTimeoutSummary($order));
        $data['can_user_complete'] = (int)$order->order_status === Order::STATUS_IN_SERVICE
            && Order::canUserCompleteService();
        $data = array_merge($data, self::buildOrderStatusGuide($data));
        $data['service_amount'] = round(
            max(0, (float)($data['total_amount'] ?? 0) - (float)($data['addon_amount'] ?? 0)),
            2
        );
        $data['refundable_amount'] = OrderRefundService::getRefundableAmount((int)$order->id);
        $data['refund_apply_amount'] = $data['refundable_amount'];
        $data['can_user_refund'] = OrderRefundService::canUserApplyRefund($order);

        // 获取退款信息
        $refundQuery = Refund::where('order_id', $orderId);
        if (RefundItem::isTableReady()) {
            $refundQuery = $refundQuery->with([
                'refundItems' => function ($query) {
                    $query->with(['payment']);
                },
            ]);
        }

        $refund = $refundQuery
            ->order('id', 'desc')
            ->find();
        if ($refund) {
            $refundData = $refund->toArray();
            $refundData['refund_items'] = $refundData['refund_items'] ?? [];
            $refundData['refund_status_desc'] = $refund->refund_status_desc;
            $refundData['refund_type_desc'] = $refund->refund_type_desc;
            $refundData['can_confirm_offline'] = (int)$refund->can_confirm_offline;
            $data['refund'] = $refundData;
        } else {
            $data['refund'] = null;
        }

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
            $selectedItems = self::buildSelectedItems($params, $userId);
            if ($userId > 0) {
                PackageBooking::releaseByUserId($userId);
            }
            self::ensureScheduleAvailable($selectedItems, $userId);
            if ($userId > 0) {
                foreach ($selectedItems as $selectedItem) {
                    self::refreshTempLock($userId, $selectedItem);
                }
            }

            $summary = self::buildCheckoutSummary($selectedItems);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $totalAmount = (float)$summary['total_amount'];
        $payAmount = max(0, $totalAmount);

        $paymentSplit = Order::calculatePaymentSplit((float) $payAmount);
        $paymentSummary = Order::buildPaymentSummaryFromState([
            'total_amount' => round($totalAmount, 2),
            'pay_amount' => round($payAmount, 2),
            'deposit_amount' => round((float) $paymentSplit['deposit_amount'], 2),
            'balance_amount' => round((float) $paymentSplit['balance_amount'], 2),
            'paid_amount' => 0,
            'order_status' => Order::STATUS_PENDING_CONFIRM,
            'pay_status' => Order::PAY_STATUS_UNPAID,
            'deposit_paid' => 0,
            'balance_paid' => 0,
            'payment_channel' => Order::PAYMENT_CHANNEL_ONLINE,
            'deposit_remark_snapshot' => (string) $paymentSplit['deposit_remark'],
        ]);

        return [
            'success' => true,
            'data' => array_merge([
                'items' => $selectedItems,
                'service_amount' => round((float)$summary['service_amount'], 2),
                'addon_amount' => round((float)$summary['addon_amount'], 2),
                'deposit_type' => (string) $paymentSplit['deposit_type'],
                'deposit_value' => round((float) $paymentSplit['deposit_value'], 2),
                'deposit_remark' => (string) $paymentSplit['deposit_remark'],
            ], $paymentSummary)
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
            $selectedItems = self::buildSelectedItems($params, $userId);
            self::ensureScheduleAvailable($selectedItems, $userId);
            foreach ($selectedItems as $selectedItem) {
                self::ensureTempLockOwned($userId, $selectedItem);
            }

            $summary = self::buildCheckoutSummary($selectedItems);
            $params['service_date'] = $params['date'] ?? ($selectedItems[0]['schedule_date'] ?? '');

            // 创建订单
            [$success, $message, $order] = Order::createOrder($userId, $selectedItems, $params);
            
            if (!$success) {
                Db::rollback();
                return ['success' => false, 'message' => $message];
            }

            Db::commit();

            OrderNotificationService::notifyUserOnOrderCreated((int) $order->id);
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
     * @notes 解析并校验服务地区
     * @param array $params
     * @return array
     */
    private static function resolveRegionContext(array $params): array
    {
        return PackageRegionPriceService::validateEnabledRegion([
            'province_code' => (string)($params['province_code'] ?? ''),
            'province_name' => (string)($params['province_name'] ?? ''),
            'city_code' => (string)($params['city_code'] ?? ''),
            'city_name' => (string)($params['city_name'] ?? ''),
            'district_code' => (string)($params['district_code'] ?? ''),
            'district_name' => (string)($params['district_name'] ?? ''),
        ]);
    }

    /**
     * @notes 是否需要校验档期
     * @param array $item
     * @return bool
     */
    private static function itemRequiresScheduleLock(array $item): bool
    {
        return in_array(
            (int)($item['item_type'] ?? OrderItem::TYPE_SERVICE),
            [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF],
            true
        );
    }

    /**
     * @notes 是否需要锁定套餐
     * @param array $item
     * @return bool
     */
    private static function itemRequiresPackageLock(array $item): bool
    {
        return self::itemRequiresScheduleLock($item) && (int)($item['package_id'] ?? 0) > 0;
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

        [$success, $message] = Order::completeOrder($orderId, $userId, OrderLog::OPERATOR_USER, 'user');
        return ['success' => $success, 'message' => $message];
    }

    /**
     * @notes 删除订单（标记为用户删除）
     * @param int $orderId
     * @param int $userId
     * @return array
     */
    public static function deleteOrder(int $orderId, int $userId): array
    {
        Db::startTrans();
        try {
            $order = Order::where('user_id', $userId)
                ->lock(true)
                ->find($orderId);
            if (!$order) {
                Db::rollback();
                return ['success' => false, 'message' => '订单不存在'];
            }

            if ((int)$order->order_status === Order::STATUS_USER_DELETED) {
                Db::rollback();
                return ['success' => false, 'message' => '订单已删除'];
            }

            if (!in_array($order->order_status, [Order::STATUS_COMPLETED, Order::STATUS_REVIEWED, Order::STATUS_CANCELLED, Order::STATUS_REFUNDED], true)) {
                Db::rollback();
                return ['success' => false, 'message' => '只能删除已完成、已评价、已取消或已退款的订单'];
            }

            $beforeStatus = (int)$order->order_status;
            $order->order_status = Order::STATUS_USER_DELETED;
            $order->update_time = time();
            $order->save();

            OrderLog::addLog(
                (int)$order->id,
                OrderLog::OPERATOR_USER,
                $userId,
                'user_delete',
                $beforeStatus,
                Order::STATUS_USER_DELETED,
                '用户删除订单'
            );

            Db::commit();
            return ['success' => true, 'message' => '删除成功'];
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'message' => $e->getMessage() ?: '删除失败'];
        }
    }

    /**
     * @notes 获取支付信息
     * @param int $orderId
     * @param int $userId
     * @return array|null
     */
    public static function getPayInfo(int $orderId, int $userId): ?array
    {
        $order = Order::where('user_id', $userId)
            ->where('order_status', '<>', Order::STATUS_USER_DELETED)
            ->find($orderId);
        if (!$order) {
            return null;
        }

        if (Order::syncExpiredAutoCancel($order)) {
            $order = Order::where('user_id', $userId)
                ->where('order_status', '<>', Order::STATUS_USER_DELETED)
                ->find($orderId);
            if (!$order) {
                return null;
            }
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
            'pay_deadline_time' => (int)($order->pay_deadline_time ?? 0),
            'pay_remain_seconds' => $order->getPayRemainSeconds(),
        ];

        return array_merge($info, Order::getPaymentSummary($order), Order::getConfirmTimeoutSummary($order));
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

        if ($order->isOfflineVoucherPending()) {
            return ['success' => false, 'message' => '线下支付凭证审核中，请等待审核结果'];
        }

        if ($order->getResolvedPaymentChannel() !== Order::PAYMENT_CHANNEL_OFFLINE) {
            return ['success' => false, 'message' => '该订单需线上支付，暂不支持上传线下凭证'];
        }

        $order->pay_type = Order::PAY_WAY_OFFLINE;
        $order->payment_channel = Order::PAYMENT_CHANNEL_OFFLINE;
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
        return OrderPayLogic::legacyCreatePayment($params);
    }

    /**
     * @notes 申请退款
     * @param int $orderId
     * @param int $userId
     * @param string $reason
     * @return array
     */
    public static function applyRefund(int $orderId, int $userId, string $reason): array
    {
        [$success, $message, $refund] = Refund::applyRefund($orderId, $userId, $reason);
        if ($success && $refund) {
            OrderNotificationService::notifyUserAndStaffOnRefundApplied((int)$refund->id);
        }
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
        $refundQuery = Refund::where('order_id', $orderId);
        if (RefundItem::isTableReady()) {
            $refundQuery = $refundQuery->with([
                'refundItems' => function ($query) {
                    $query->with(['payment']);
                },
            ]);
        }

        $refund = $refundQuery
            ->where('user_id', $userId)
            ->order('id', 'desc')
            ->find();

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
            'refunding' => Order::STATUS_REFUNDING,
            'refund' => Order::STATUS_REFUNDED,
        ] as $key => $status) {
            $counts[$key] = Order::where('user_id', $userId)
                ->where('order_status', '<>', Order::STATUS_USER_DELETED)
                ->where('order_status', $status)
                ->count();
        }
        $counts['pending_service'] = (int)($counts['paid'] ?? 0);
        $counts['all'] = Order::where('user_id', $userId)
            ->where('order_status', '<>', Order::STATUS_USER_DELETED)
            ->count();

        return $counts;
    }

    /**
     * @notes 构建用户侧订单状态说明
     */
    protected static function buildOrderStatusGuide(array $order): array
    {
        $status = (int)($order['order_status'] ?? -1);
        $paymentChannel = (int)($order['payment_channel'] ?? Order::PAYMENT_CHANNEL_ONLINE);
        $voucherPending = $paymentChannel === Order::PAYMENT_CHANNEL_OFFLINE
            && (int)($order['pay_voucher_status'] ?? -1) === Order::VOUCHER_STATUS_PENDING;
        $canUserComplete = (int)($order['can_user_complete'] ?? 0) === 1;

        $guide = [
            'status_summary' => '订单状态已更新，请留意后续进度。',
            'waiting_for' => '等待平台同步',
            'next_action_text' => '进入订单详情查看最新安排'
        ];

        switch ($status) {
            case Order::STATUS_PENDING_CONFIRM:
                $guide = [
                    'status_summary' => '订单已提交，正在等待服务人员确认档期与接单安排。',
                    'waiting_for' => '等待服务人员确认档期',
                    'next_action_text' => '确认通过后进入支付流程'
                ];
                break;
            case Order::STATUS_PENDING_PAY:
                if ($voucherPending) {
                    $guide = [
                        'status_summary' => '线下付款凭证审核中，审核结果会直接同步到当前订单。',
                        'waiting_for' => '等待后台审核支付凭证',
                        'next_action_text' => '审核通过后订单进入待服务状态'
                    ];
                    break;
                }

                if ($paymentChannel === Order::PAYMENT_CHANNEL_OFFLINE) {
                    $guide = [
                        'status_summary' => '当前订单需线下付款，完成付款后请尽快上传支付凭证。',
                        'waiting_for' => '等待你完成付款并上传凭证',
                        'next_action_text' => '凭证审核通过后进入待服务状态'
                    ];
                    break;
                }

                $guide = [
                    'status_summary' => '订单待支付，支付完成后即可锁定当前服务安排。',
                    'waiting_for' => '等待你完成支付',
                    'next_action_text' => '支付成功后进入待服务状态'
                ];
                break;
            case Order::STATUS_PAID:
                $guide = [
                    'status_summary' => '订单已完成支付，当前档期与服务安排已锁定。',
                    'waiting_for' => '等待服务日期到来或后台开工',
                    'next_action_text' => '服务开始后自动进入执行中'
                ];
                break;
            case Order::STATUS_IN_SERVICE:
                $guide = [
                    'status_summary' => '当前服务执行中，平台会持续同步履约进度。',
                    'waiting_for' => $canUserComplete ? '等待你确认服务完成' : '等待服务人员或后台推进',
                    'next_action_text' => $canUserComplete ? '确认完成后可进入评价流程' : '如需调整服务，可在更多操作中发起申请'
                ];
                break;
            case Order::STATUS_COMPLETED:
                $guide = [
                    'status_summary' => '服务已完成，若无异常可继续前往评价。',
                    'waiting_for' => '当前无需等待',
                    'next_action_text' => '前往评价或保留订单作为后续售后依据'
                ];
                break;
            case Order::STATUS_REVIEWED:
                $guide = [
                    'status_summary' => '订单与评价已闭环，本次服务记录已归档。',
                    'waiting_for' => '当前无需等待',
                    'next_action_text' => '可查看评价详情或再次预约服务'
                ];
                break;
            case Order::STATUS_CANCELLED:
                $guide = [
                    'status_summary' => '当前订单已取消，不再继续占用档期或进入履约流程。',
                    'waiting_for' => '当前无需等待',
                    'next_action_text' => '如仍需服务，可重新下单预约'
                ];
                break;
            case Order::STATUS_PAUSED:
                $guide = [
                    'status_summary' => '订单当前处于暂停状态，暂停结束后会继续履约。',
                    'waiting_for' => '等待平台恢复履约或处理暂停申请',
                    'next_action_text' => '可在“我的申请”中查看暂停进度'
                ];
                break;
            case Order::STATUS_REFUNDING:
                $guide = [
                    'status_summary' => '退款申请处理中，到账结果会同步更新到订单与消息中心。',
                    'waiting_for' => '等待平台审核或支付渠道完成退款',
                    'next_action_text' => '到账后订单状态会自动更新'
                ];
                break;
            case Order::STATUS_REFUNDED:
                $guide = [
                    'status_summary' => '订单已退款，本次交易流程已结束。',
                    'waiting_for' => '当前无需等待',
                    'next_action_text' => '如仍需预约，可重新发起下单'
                ];
                break;
        }

        return $guide;
    }

    /**
     * @notes 获取状态描述
     */
    protected static function getStatusDesc(int $status): string
    {
        return Order::getStatusText($status);
    }

    protected static function getPayStatusDesc(int $status): string
    {
        return Order::getPayStatusText($status);
    }

    protected static function getPayTypeDesc(int $type): string
    {
        return Order::getPayWayText($type);
    }

    public static function getConfirmLetterCurrent(int $orderId, int $userId): ?array
    {
        return OrderConfirmLetterService::currentForUser($orderId, $userId);
    }

    public static function getConfirmLetterById(int $letterId, int $userId): ?array
    {
        return OrderConfirmLetterService::byIdForUser($letterId, $userId);
    }
}
