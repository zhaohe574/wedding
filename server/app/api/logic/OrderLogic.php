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
use app\common\model\package\PackageBooking;
use app\common\model\order\Refund;
use app\common\model\schedule\Schedule;
use app\common\model\service\ServicePackage;
use app\common\model\staff\Staff;
use app\common\service\BookingFlowService;
use app\common\service\OrderNotificationService;
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
    private static function buildSelectedItems(array $params): array
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
                $date
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
    private static function ensureScheduleAvailable(array $selectedItems): void
    {
        foreach ($selectedItems as $item) {
            if (!self::itemRequiresScheduleLock($item)) {
                continue;
            }

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
            $item['service_region_text'] = implode(' ', array_filter([
                trim((string)($item['service_province'] ?? '')),
                trim((string)($item['service_city'] ?? '')),
                trim((string)($item['service_district'] ?? '')),
            ]));
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
        $data['order_status_desc'] = self::getStatusDesc($order->order_status);
        $data['pay_status_desc'] = self::getPayStatusDesc($order->pay_status);
        $data['pay_type_desc'] = self::getPayTypeDesc($order->pay_type);
        $data['pay_voucher_status_desc'] = $order->pay_voucher_status_desc ?? '';
        $data['service_region_text'] = $order->service_region_text;
        $data['pay_deadline_time'] = (int)($order->pay_deadline_time ?? 0);
        $data['pay_remain_seconds'] = $order->getPayRemainSeconds();
        $data['service_amount'] = round(
            max(0, (float)($data['total_amount'] ?? 0) - (float)($data['addon_amount'] ?? 0)),
            2
        );

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
            if ($userId > 0) {
                PackageBooking::releaseByUserId($userId);
            }
            self::ensureScheduleAvailable($selectedItems);
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
                'service_amount' => round((float)$summary['service_amount'], 2),
                'addon_amount' => round((float)$summary['addon_amount'], 2),
                'total_amount' => round($totalAmount, 2),
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
            $userId = (int)$params['user_id'];
            $selectedItems = self::buildSelectedItems($params);
            self::ensureScheduleAvailable($selectedItems);
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

        [$success, $message] = Order::completeOrder($orderId, $userId, OrderLog::OPERATOR_USER);
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
        return OrderPayLogic::legacyCreatePayment($params);
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
            $counts[$key] = Order::where('user_id', $userId)
                ->where('order_status', '<>', Order::STATUS_USER_DELETED)
                ->where('order_status', $status)
                ->count();
        }
        $counts['all'] = Order::where('user_id', $userId)
            ->where('order_status', '<>', Order::STATUS_USER_DELETED)
            ->count();

        return $counts;
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
            Order::STATUS_USER_DELETED => '用户已删除',
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
