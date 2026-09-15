<?php
declare(strict_types=1);

namespace app\common\service;

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


/** 后台与服务人员共用的手动建单规则，调用方负责授权与参数限制。 */
class ManualOrderService
{
    private const PAYMENT_ENTRY_MODE_ONLINE_PENDING = 'online_pending';
    private const PAYMENT_ENTRY_MODE_OFFLINE_VOUCHER = 'offline_voucher';
    private const PAYMENT_ENTRY_MODE_OFFLINE_PAID = 'offline_paid';

    public static function resolveOfflinePaymentEntryMode(array $params): string
    {
        $mode = trim((string)($params['payment_entry_mode'] ?? ''));
        return match ($mode) {
            self::PAYMENT_ENTRY_MODE_ONLINE_PENDING,
            self::PAYMENT_ENTRY_MODE_OFFLINE_VOUCHER => $mode,
            default => self::PAYMENT_ENTRY_MODE_OFFLINE_PAID,
        };
    }

    public static function getOfflinePaymentEntryModeDesc(string $entryMode): string
    {
        return match ($entryMode) {
            self::PAYMENT_ENTRY_MODE_ONLINE_PENDING => '待线上支付',
            self::PAYMENT_ENTRY_MODE_OFFLINE_VOUCHER => '待上传线下凭证',
            default => '线下已支付',
        };
    }

    public static function buildOfflineEstimatePayload(array $summary, float $discountAmount, string $entryMode): array
    {
        $payAmount = round(max((float)$summary['total_amount'] - $discountAmount, 0), 2);
        $isOfflinePaid = $entryMode === self::PAYMENT_ENTRY_MODE_OFFLINE_PAID;
        $paymentSplit = Order::calculatePaymentSplit($payAmount);
        $paymentChannel = $entryMode === self::PAYMENT_ENTRY_MODE_ONLINE_PENDING
            ? Order::PAYMENT_CHANNEL_ONLINE
            : Order::PAYMENT_CHANNEL_OFFLINE;

        return [
            'main_amount' => round((float)$summary['main_amount'], 2),
            'related_amount' => round((float)$summary['related_amount'], 2),
            'addon_amount' => round((float)$summary['addon_amount'], 2),
            'total_amount' => round((float)$summary['total_amount'], 2),
            'discount_amount' => round($discountAmount, 2),
            'pay_amount' => $payAmount,
            'payment_entry_mode' => $entryMode,
            'payment_entry_mode_desc' => self::getOfflinePaymentEntryModeDesc($entryMode),
            'payment_channel' => $paymentChannel,
            'payment_channel_desc' => Order::getPaymentChannelText($paymentChannel),
            'payment_mode' => (int)$paymentSplit['deposit_mode_enabled'] === 1 ? 'deposit' : 'full',
            'payment_mode_desc' => $isOfflinePaid
                ? '线下已支付'
                : ((int)$paymentSplit['deposit_mode_enabled'] === 1 ? '定金支付' : '全款支付'),
            'deposit_amount' => $isOfflinePaid ? 0 : round((float)$paymentSplit['deposit_amount'], 2),
            'balance_amount' => $isOfflinePaid ? 0 : round((float)$paymentSplit['balance_amount'], 2),
            'deposit_remark' => $isOfflinePaid
                ? '后台直接登记为线下已收款'
                : (string)$paymentSplit['deposit_remark'],
        ];
    }

    public static function getOfflineMainPackages(array $params): array
    {
        $staffId = (int) ($params['main_staff_id'] ?? 0);
        if ($staffId <= 0) {
            return [];
        }

        $regionContext = self::resolveOfflineRegionContext($params);
        $packages = self::getOfflinePackagesByStaff($staffId, $regionContext);
        $serviceDate = trim((string) ($params['service_date'] ?? ''));
        if ($serviceDate === '') {
            return $packages;
        }

        $result = [];
        foreach ($packages as $package) {
            $availability = \app\common\model\package\PackageBooking::checkAvailability(
                (int) ($package['id'] ?? 0),
                $serviceDate,
                $staffId,
                0
            );
            $package['package_available'] = (bool) ($availability['available'] ?? false);
            $package['package_message'] = (string) ($availability['message'] ?? '');
            if ($package['package_available']) {
                $result[] = $package;
            }
        }

        return $result;
    }

    public static function getOfflineRoleCandidates(array $params): array
    {
        $mainStaffId = (int) ($params['main_staff_id'] ?? 0);
        $roleKey = trim((string) ($params['role_key'] ?? ''));
        if ($mainStaffId <= 0 || $roleKey === '') {
            return [];
        }

        $regionContext = self::resolveOfflineRegionContext($params);
        $serviceDate = (string) ($params['service_date'] ?? '');
        $candidates = BookingFlowService::getRoleCandidates(
            $mainStaffId,
            $roleKey,
            $regionContext,
            $serviceDate
        );

        $result = [];
        foreach ($candidates as $candidate) {
            $availability = \app\common\model\package\PackageBooking::checkAvailability(
                (int) ($candidate['package_id'] ?? 0),
                $serviceDate,
                (int) ($candidate['staff_id'] ?? 0),
                0
            );
            $candidate['package_available'] = (bool) ($availability['available'] ?? false);
            $candidate['package_message'] = (string) ($availability['message'] ?? '');
            if (($candidate['schedule_available'] ?? false) && $candidate['package_available']) {
                $result[] = $candidate;
            }
        }

        return $result;
    }

    public static function estimateOffline(array $params): array
    {
        $selection = self::buildOfflineSelection($params);
        $discountAmount = round(max((float) ($params['discount_amount'] ?? 0), 0), 2);
        $entryMode = self::resolveOfflinePaymentEntryMode($params);

        return self::buildOfflineEstimatePayload($selection['summary'], $discountAmount, $entryMode);
    }

    public static function create(array $params, int $source = Order::SOURCE_ADMIN, int $creatorUserId = 0): array
    {
        $transactionStarted = false;
        $notifyOrderId = 0;
        $notifyPayType = Payment::TYPE_FULL;
        $notifyStatusText = '';
        $notifyContentTemplate = '';
        $notifyTitle = '';
        try {
            $selection = self::buildOfflineSelection($params);
            $userId = self::resolveOfflineOrderUserId($params);
            $summary = $selection['summary'];
            $discountAmount = round(max((float) ($params['discount_amount'] ?? 0), 0), 2);
            $payAmount = round(max((float) $summary['total_amount'] - $discountAmount, 0), 2);
            $entryMode = self::resolveOfflinePaymentEntryMode($params);
            $isOfflinePaid = $entryMode === self::PAYMENT_ENTRY_MODE_OFFLINE_PAID;
            $receipt = $isOfflinePaid ? Payment::validateOfflineReceipt($params) : [];
            $paymentChannel = $entryMode === self::PAYMENT_ENTRY_MODE_ONLINE_PENDING
                ? Order::PAYMENT_CHANNEL_ONLINE
                : Order::PAYMENT_CHANNEL_OFFLINE;
            $paymentSplit = Order::calculatePaymentSplit($payAmount);
            $now = time();

            Db::startTrans();
            $transactionStarted = true;
            $order = Order::create([
                'order_sn' => Order::generateOrderSn(),
                'user_id' => $userId,
                'order_type' => Order::TYPE_NORMAL,
                'order_status' => $isOfflinePaid ? Order::STATUS_PENDING_SERVICE : Order::STATUS_PENDING_PAY,
                'pay_status' => $isOfflinePaid ? Order::PAY_STATUS_PAID : Order::PAY_STATUS_UNPAID,
                'paid_amount' => $isOfflinePaid ? $payAmount : 0,
                'total_amount' => round((float) $summary['total_amount'], 2),
                'addon_amount' => round((float) $summary['addon_amount'], 2),
                'discount_amount' => $discountAmount,
                'pay_amount' => $payAmount,
                'deposit_amount' => $isOfflinePaid ? 0 : round((float) $paymentSplit['deposit_amount'], 2),
                'balance_amount' => $isOfflinePaid ? 0 : round((float) $paymentSplit['balance_amount'], 2),
                'deposit_mode_enabled' => $isOfflinePaid ? 0 : (int) $paymentSplit['deposit_mode_enabled'],
                'deposit_type_snapshot' => $isOfflinePaid ? '' : (string) $paymentSplit['deposit_type'],
                'deposit_value_snapshot' => $isOfflinePaid ? 0 : (float) $paymentSplit['deposit_value'],
                'deposit_remark_snapshot' => $isOfflinePaid ? '' : (string) $paymentSplit['deposit_remark'],
                'deposit_paid' => 0,
                'balance_paid' => 0,
                'service_date' => $params['service_date'] ?? null,
                'service_time_slot' => 0,
                'service_address' => $params['service_address'] ?? '',
                'service_province_code' => $selection['region_context']['province_code'] ?? '',
                'service_province' => $selection['region_context']['province_name'] ?? '',
                'service_city_code' => $selection['region_context']['city_code'] ?? '',
                'service_city' => $selection['region_context']['city_name'] ?? '',
                'service_district_code' => $selection['region_context']['district_code'] ?? '',
                'service_district' => $selection['region_context']['district_name'] ?? '',
                'contact_name' => $params['contact_name'] ?? '',
                'contact_mobile' => $params['contact_mobile'] ?? '',
                'admin_remark' => $params['admin_remark'] ?? '',
                'source' => $source,
                'creator_user_id' => $creatorUserId,
                'pay_type' => $isOfflinePaid ? Order::PAY_WAY_OFFLINE : Order::PAY_WAY_NONE,
                'pay_voucher' => $receipt['pay_voucher'] ?? '',
                'pay_voucher_status' => $isOfflinePaid ? Order::VOUCHER_STATUS_APPROVED : Order::VOUCHER_STATUS_PENDING,
                'pay_voucher_audit_admin_id' => $isOfflinePaid ? (int)$params['admin_id'] : 0,
                'pay_voucher_audit_time' => $isOfflinePaid ? $now : 0,
                'payment_channel' => $paymentChannel,
                'pay_time' => $isOfflinePaid ? $now : 0,
                'confirm_deadline_time' => 0,
                'pay_deadline_time' => 0,
                'create_time' => $now,
                'update_time' => $now,
            ]);

            $mainOrderItemId = 0;
            foreach ($selection['selected_items'] as $item) {
                $orderItem = OrderItem::create([
                    'order_id' => (int) $order->id,
                    'staff_id' => (int) ($item['staff_id'] ?? 0),
                    'package_id' => (int) ($item['package_id'] ?? 0),
                    'schedule_id' => 0,
                    'service_date' => (string) ($item['service_date'] ?? ''),
                    'time_slot' => 0,
                    'staff_name' => (string) ($item['staff_name'] ?? ''),
                    'package_name' => (string) ($item['package_name'] ?? ''),
                    'package_description' => OrderItem::resolvePackageDescription(
                        (int) ($item['package_id'] ?? 0),
                        (string) ($item['package_description'] ?? '')
                    ),
                    'price' => round((float) ($item['price'] ?? 0), 2),
                    'quantity' => 1,
                    'subtotal' => round((float) ($item['price'] ?? 0), 2),
                    'item_type' => (int) ($item['item_type'] ?? OrderItem::TYPE_SERVICE),
                    'item_meta' => $item['item_meta'] ?? [],
                    'confirm_status' => 1,
                    'remark' => (string) ($item['remark'] ?? ''),
                    'create_time' => $now,
                    'update_time' => $now,
                ]);

                if ((int) ($item['item_type'] ?? OrderItem::TYPE_SERVICE) === OrderItem::TYPE_SERVICE) {
                    $mainOrderItemId = (int) $orderItem->id;
                }

            }

            if ($mainOrderItemId > 0 && !empty($selection['addons'])) {
                OrderItemAddon::createSnapshots(
                    (int) $order->id,
                    $mainOrderItemId,
                    $selection['addons'],
                    OrderItemAddon::SOURCE_ORDER
                );
            }

            if ($isOfflinePaid) {
                Order::lockSchedulesAfterFirstPayment($order);
                $payment = Payment::create([
                    'payment_sn' => Payment::generatePaymentSn(),
                    'order_id' => (int) $order->id,
                    'order_sn' => (string) $order->order_sn,
                    'user_id' => $userId,
                    'pay_type' => Payment::TYPE_FULL,
                    'pay_way' => Payment::WAY_OFFLINE,
                    'collection_owner' => $receipt['collection_owner'],
                    'pay_voucher' => $receipt['pay_voucher'],
                    'pay_amount' => $payAmount,
                    'pay_status' => Payment::STATUS_PAID,
                    'pay_time' => $now,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
                self::recordSuccessfulPaymentFlow($order, $payment, (int)($params['admin_id'] ?? 0));
            } else {
                Order::syncPendingPayDeadline($order, $now);
            }

            $logAction = 'create_offline';
            $logContent = '后台创建线下订单并登记为待服务';
            $afterStatus = Order::STATUS_PENDING_SERVICE;
            if ($entryMode === self::PAYMENT_ENTRY_MODE_ONLINE_PENDING) {
                $logAction = 'create_online_pending';
                $logContent = '后台创建订单，待用户线上支付';
                $afterStatus = Order::STATUS_PENDING_PAY;
                $notifyStatusText = '后台已创建订单，待线上支付';
                $notifyContentTemplate = '订单%s已创建，请尽快完成线上支付。';
                $notifyTitle = '后台已为您创建订单';
            } elseif ($entryMode === self::PAYMENT_ENTRY_MODE_OFFLINE_VOUCHER) {
                $logAction = 'create_offline_voucher';
                $logContent = '后台创建订单，待用户上传线下支付凭证';
                $afterStatus = Order::STATUS_PENDING_PAY;
                $notifyStatusText = '后台已创建订单，待上传线下凭证';
                $notifyContentTemplate = '订单%s已创建，请线下付款后上传支付凭证。';
                $notifyTitle = '后台已为您创建订单';
            }

            if ($source === Order::SOURCE_STAFF) {
                $logContent = '服务人员录入本人订单，待付款或收款审核';
                $notifyTitle = '服务人员已为您录入订单';
                $notifyStatusText = '已录入订单，待付款';
                $notifyContentTemplate = $paymentChannel === Order::PAYMENT_CHANNEL_ONLINE
                    ? '订单%s已录入，请核对信息后完成支付。'
                    : '订单%s已录入，线下收款待后台核实。';
            }
            OrderLog::addLog(
                (int) $order->id,
                $creatorUserId > 0 ? OrderLog::OPERATOR_USER : OrderLog::OPERATOR_ADMIN,
                $creatorUserId > 0 ? $creatorUserId : (int) ($params['admin_id'] ?? 0),
                $logAction,
                0,
                $afterStatus,
                $logContent
            );

            $notifyOrderId = (int)$order->id;

            if ($isOfflinePaid && $notifyOrderId > 0) {
                OrderNotificationService::notifyUserAndStaffOnPaymentSuccess($notifyOrderId, $notifyPayType, true);
            } elseif ($notifyOrderId > 0) {
                OrderNotificationService::notifyUserOnOrderConfirmed($notifyOrderId, $notifyStatusText, $notifyContentTemplate, $notifyTitle, 'order_created');
            }

            Db::commit();

            if ($isOfflinePaid && $notifyOrderId > 0) {
                OrderNotificationService::notifyUserAndStaffOnPaymentSuccess($notifyOrderId, $notifyPayType);
            }

            if (!$isOfflinePaid && $notifyOrderId > 0) {
                OrderNotificationService::notifyUserOnOrderConfirmed($notifyOrderId, $notifyStatusText, $notifyContentTemplate, $notifyTitle, 'order_created');
            }

            return ['order_id' => (int)$order->id, 'order_sn' => (string)$order->order_sn, 'order_status' => (int)$order->order_status];
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                Db::rollback();
            }
            throw $e;
        }
    }

    public static function buildOfflineSelection(array $params): array
    {
        $serviceDate = trim((string) ($params['service_date'] ?? ''));
        $mainStaffId = (int) ($params['main_staff_id'] ?? 0);
        $mainPackageId = (int) ($params['main_package_id'] ?? 0);
        if ($serviceDate === '') {
            throw new \InvalidArgumentException('请选择服务日期');
        }
        if ($mainStaffId <= 0) {
            throw new \InvalidArgumentException('请选择主服务人员');
        }
        if ($mainPackageId <= 0) {
            throw new \InvalidArgumentException('请选择主套餐');
        }

        [$available, $message] = Schedule::checkAvailabilityWithReason($mainStaffId, $serviceDate, 0);
        if (!$available) {
            throw new \InvalidArgumentException($message ?: '主服务档期不可用');
        }

        $regionContext = self::resolveOfflineRegionContext($params);
        $packages = self::getOfflinePackagesByStaff($mainStaffId, $regionContext);
        $packageMap = [];
        foreach ($packages as $package) {
            $packageMap[(int) ($package['id'] ?? 0)] = $package;
        }

        $mainPackage = $packageMap[$mainPackageId] ?? null;
        if (!$mainPackage) {
            throw new \InvalidArgumentException('主套餐不存在、已下架或当前地区不可用');
        }

        $mainPackageAvailability = \app\common\model\package\PackageBooking::checkAvailability(
            $mainPackageId,
            $serviceDate,
            $mainStaffId,
            0
        );
        if (!($mainPackageAvailability['available'] ?? false)) {
            throw new \InvalidArgumentException((string) ($mainPackageAvailability['message'] ?? '主套餐已被占用'));
        }

        $selectedItems = [[
            'staff_id' => $mainStaffId,
            'package_id' => $mainPackageId,
            'service_date' => $serviceDate,
            'staff_name' => (string) ($mainPackage['staff_name'] ?? ''),
            'package_name' => (string) ($mainPackage['name'] ?? ''),
            'package_description' => (string) ($mainPackage['description'] ?? ''),
            'price' => round((float) ($mainPackage['price'] ?? 0), 2),
            'item_type' => OrderItem::TYPE_SERVICE,
            'item_meta' => [
                'role_key' => '',
                'role_label' => '',
            ],
            'remark' => '',
        ]];

        $addons = BookingFlowService::resolveSelectedAddons(
            $mainStaffId,
            $mainPackageId,
            BookingFlowService::normalizeAddonIds($params['addon_ids'] ?? [])
        );

        $relatedItems = [];
        foreach ([
            BookingFlowService::ROLE_BUTLER => [
                'staff_id' => (int) ($params['butler_staff_id'] ?? 0),
                'package_id' => (int) ($params['butler_package_id'] ?? 0),
            ],
            BookingFlowService::ROLE_DIRECTOR => [
                'staff_id' => (int) ($params['director_staff_id'] ?? 0),
                'package_id' => (int) ($params['director_package_id'] ?? 0),
            ],
        ] as $roleKey => $selection) {
            $selectedStaffId = (int) ($selection['staff_id'] ?? 0);
            $selectedPackageId = (int) ($selection['package_id'] ?? 0);
            if (($selectedStaffId > 0 && $selectedPackageId <= 0) || ($selectedStaffId <= 0 && $selectedPackageId > 0)) {
                throw new \InvalidArgumentException('协作角色请选择完整的人员与套餐');
            }
            if ($selectedStaffId <= 0) {
                continue;
            }

            $candidate = BookingFlowService::resolveSelectedRoleCandidate(
                $mainStaffId,
                $roleKey,
                $selectedStaffId,
                $selectedPackageId,
                $regionContext,
                $serviceDate
            );

            $candidatePackageAvailability = \app\common\model\package\PackageBooking::checkAvailability(
                (int) ($candidate['package_id'] ?? 0),
                $serviceDate,
                (int) ($candidate['staff_id'] ?? 0),
                0
            );
            if (!($candidatePackageAvailability['available'] ?? false)) {
                throw new \InvalidArgumentException((string) ($candidatePackageAvailability['message'] ?? '协作角色套餐已被占用'));
            }

            $relatedItems[] = [
                'staff_id' => (int) ($candidate['staff_id'] ?? 0),
                'package_id' => (int) ($candidate['package_id'] ?? 0),
                'service_date' => $serviceDate,
                'staff_name' => (string) ($candidate['name'] ?? ''),
                'package_name' => (string) ($candidate['package_name'] ?? ''),
                'package_description' => (string) ($candidate['package_description'] ?? ''),
                'price' => round((float) ($candidate['price'] ?? 0), 2),
                'item_type' => OrderItem::TYPE_RELATED_STAFF,
                'item_meta' => [
                    'role_key' => $roleKey,
                    'role_label' => (string) ($candidate['role_label'] ?? BookingFlowService::getRoleLabel($roleKey)),
                ],
                'remark' => '',
            ];
        }

        $selectedItems = array_merge($selectedItems, $relatedItems);

        $mainAmount = round((float) ($selectedItems[0]['price'] ?? 0), 2);
        $relatedAmount = round(array_reduce($relatedItems, static function (float $carry, array $item): float {
            return $carry + round((float) ($item['price'] ?? 0), 2);
        }, 0.0), 2);
        $addonAmount = round(array_reduce($addons, static function (float $carry, array $addon): float {
            return $carry + round((float) ($addon['price'] ?? 0), 2);
        }, 0.0), 2);

        return [
            'region_context' => $regionContext,
            'main_package' => $mainPackage,
            'addons' => $addons,
            'selected_items' => $selectedItems,
            'summary' => [
                'main_amount' => $mainAmount,
                'related_amount' => $relatedAmount,
                'addon_amount' => $addonAmount,
                'total_amount' => round($mainAmount + $relatedAmount + $addonAmount, 2),
            ],
        ];
    }

    public static function resolveOfflineOrderUserId(array $params): int
    {
        $bindMode = trim((string) ($params['bind_mode'] ?? ''));
        if ($bindMode === 'temp') {
            return 0;
        }

        $userId = (int) ($params['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new \InvalidArgumentException('请选择用户');
        }

        $user = User::find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('用户不存在');
        }

        return $userId;
    }

    public static function getOfflinePackagesByStaff(int $staffId, array $regionContext): array
    {
        $staffName = (string) Staff::where('id', $staffId)->value('name');
        $packages = ServicePackage::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->where('is_show', 1)
            ->order('sort desc, id desc')
            ->field('id, staff_id, category_id, name, price, original_price, description, image, sort, is_show, is_recommend')
            ->select()
            ->toArray();

        $packages = PackageRegionPriceService::applyResolvedPrices($packages, $regionContext, true);
        $packages = ServicePackageAddon::attachAddonIds($packages);
        foreach ($packages as &$package) {
            $package['staff_name'] = $staffName;
        }

        return $packages;
    }

    public static function resolveOfflineRegionContext(array $params): array
    {
        return PackageRegionPriceService::validateEnabledRegion([
            'province_code' => (string) ($params['province_code'] ?? ''),
            'province_name' => (string) ($params['province_name'] ?? ''),
            'city_code' => (string) ($params['city_code'] ?? ''),
            'city_name' => (string) ($params['city_name'] ?? ''),
            'district_code' => (string) ($params['district_code'] ?? ''),
            'district_name' => (string) ($params['district_name'] ?? ''),
        ]);
    }

    public static function recordSuccessfulPaymentFlow(Order $order, Payment $payment, int $adminId): void
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
}
