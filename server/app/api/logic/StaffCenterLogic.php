<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员中心逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\dynamic\Dynamic;
use app\common\model\notification\Notification;
use app\common\model\order\Order;
use app\common\model\order\OrderLog;
use app\common\model\order\OrderItem;
use app\common\model\schedule\Schedule;
use app\common\model\service\ServiceAddon;
use app\common\model\service\ServicePackage;
use app\common\model\service\ServicePackageAddon;
use app\common\model\service\ServicePackageRegionPrice;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffWork;
use app\common\service\OrderConfirmLetterService;
use app\common\service\OrderNotificationService;
use app\common\service\PackageRegionPriceService;
use app\common\service\StaffPriceService;
use app\common\service\StaffService;
use app\common\service\StaffTagReviewService;
use think\facade\Db;

/**
 * 服务人员中心逻辑
 * Class StaffCenterLogic
 * @package app\api\logic
 */
class StaffCenterLogic extends BaseLogic
{
    /**
     * @notes 获取服务人员ID
     */
    public static function getStaffId(int $userId): int
    {
        return StaffService::getStaffIdByUserId($userId);
    }

    /**
     * @notes 获取个人资料
     */
    public static function profile(int $userId): array
    {
        $staff = Staff::where('user_id', $userId)->find();
        if (!$staff) {
            self::setError('未绑定服务人员');
            return [];
        }

        $data = $staff->toArray();
        $fullMobile = $staff->getData('mobile_full') ?: $staff->getData('mobile');
        $data['mobile'] = $fullMobile;
        $data['mobile_full'] = $fullMobile;
        $displayPrice = StaffPriceService::getDisplayPriceByStaffId((int)$staff->id);
        $data['price'] = $displayPrice['price'];
        $data['has_price'] = $displayPrice['has_price'];
        $data['price_text'] = $displayPrice['price_text'];

        // 统计数据
        $data['orderCount'] = (int) self::buildStaffRelatedOrderBaseQuery((int) $staff->id)->count();

        $data['workCount'] = StaffWork::where('staff_id', $staff->id)
            ->where('delete_time', null)
            ->count();

        $data['packageCount'] = ServicePackage::where('staff_id', $staff->id)
            ->where('delete_time', null)
            ->count();

        $data['addonCount'] = ServiceAddon::where('staff_id', $staff->id)
            ->whereNull('delete_time')
            ->count();

        $data['scheduleCount'] = Schedule::where('staff_id', $staff->id)->count();
        $data['todoCount'] = self::getTodoCount((int) $staff->id);
        $data = array_merge($data, StaffTagReviewService::getProfileTagState((int) $staff->id));

        return $data;
    }

    /**
     * @notes 获取工作台首页数据
     */
    public static function dashboard(int $userId): array
    {
        $profile = self::profile($userId);
        if (empty($profile)) {
            return [];
        }

        $staffId = (int) ($profile['id'] ?? 0);
        $unreadMessageCount = Notification::getUnreadCount($userId);
        $pendingConfirmOrders = self::getTodoCount($staffId);
        $todayServiceCount = self::getTodayServiceCount($staffId);
        $upcomingScheduleCount = self::getUpcomingScheduleCount($staffId);

        return [
            'profile' => [
                'name' => $profile['name'] ?? '',
                'avatar' => $profile['avatar'] ?? '',
                'status' => (int) ($profile['status'] ?? 0),
                'status_desc' => $profile['status_desc'] ?? '',
                'audit_status' => (int) ($profile['audit_status'] ?? 0),
                'audit_status_desc' => $profile['audit_status_desc'] ?? '',
                'mobile' => $profile['mobile_full'] ?? ($profile['mobile'] ?? ''),
                'price_text' => $profile['price_text'] ?? '',
                'has_price' => (bool) ($profile['has_price'] ?? false),
                'category_name' => $profile['category_name'] ?? '',
            ],
            'overview' => [
                'order_count' => (int) ($profile['orderCount'] ?? 0),
                'work_count' => (int) ($profile['workCount'] ?? 0),
                'package_count' => (int) ($profile['packageCount'] ?? 0),
                'addon_count' => (int) ($profile['addonCount'] ?? 0),
                'schedule_count' => (int) ($profile['scheduleCount'] ?? 0),
            ],
            'todo' => [
                'pending_confirm_orders' => $pendingConfirmOrders,
                'today_service_count' => $todayServiceCount,
                'upcoming_7d_schedule_count' => $upcomingScheduleCount,
                'unread_message_count' => $unreadMessageCount,
                'total' => $pendingConfirmOrders + $todayServiceCount + $upcomingScheduleCount + $unreadMessageCount,
            ],
            'recent_orders' => self::getRecentOrders($staffId),
        ];
    }

    /**
     * @notes 获取订单状态统计
     */
    public static function orderStats(int $userId): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = self::buildStaffRelatedOrderBaseQuery($staffId);
        $statuses = [
            'pending_confirm' => Order::STATUS_PENDING_CONFIRM,
            'pending_pay' => Order::STATUS_PENDING_PAY,
            'paid' => Order::STATUS_PAID,
            'in_service' => Order::STATUS_IN_SERVICE,
            'completed' => Order::STATUS_COMPLETED,
            'reviewed' => Order::STATUS_REVIEWED,
            'cancelled' => Order::STATUS_CANCELLED,
            'paused' => Order::STATUS_PAUSED,
            'refunding' => Order::STATUS_REFUNDING,
            'refunded' => Order::STATUS_REFUNDED,
        ];

        $result = [
            'all' => (int) (clone $query)->count(),
        ];

        foreach ($statuses as $key => $status) {
            if ($status === Order::STATUS_PENDING_CONFIRM) {
                $result[$key] = (int) (clone self::buildPendingConfirmOrderBaseQuery($staffId))
                    ->count();
                continue;
            }

            $result[$key] = (int) (clone $query)
                ->where('order_status', $status)
                ->count();
        }

        return $result;
    }

    /**
     * @notes 获取服务人员待办数量
     */
    private static function getTodoCount(int $staffId): int
    {
        return (int) (clone self::buildPendingConfirmOrderBaseQuery($staffId))
            ->count();
    }

    /**
     * @notes 获取今日服务数量
     */
    private static function getTodayServiceCount(int $staffId): int
    {
        $today = date('Y-m-d');

        return (int) Order::whereNotIn('order_status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])
            ->whereIn('id', function ($subQuery) use ($staffId, $today) {
                self::applyStaffOrderIdSubQuery($subQuery, $staffId);
                $subQuery->where('service_date', $today)
                    ->where('item_status', '<>', OrderItem::STATUS_CANCELLED);
            })
            ->count();
    }

    /**
     * @notes 获取未来7日安排数量
     */
    private static function getUpcomingScheduleCount(int $staffId): int
    {
        $startDate = date('Y-m-d', strtotime('+1 day'));
        $endDate = date('Y-m-d', strtotime('+7 day'));

        return (int) Schedule::where('staff_id', $staffId)
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->whereIn('status', [Schedule::STATUS_BOOKED, Schedule::STATUS_LOCKED, Schedule::STATUS_RESERVED])
            ->count();
    }

    /**
     * @notes 获取最近订单
     */
    private static function getRecentOrders(int $staffId): array
    {
        $orderTable = (new Order())->getTable();

        $orders = self::buildStaffRelatedOrderBaseQuery($staffId)
            ->with([
                'items' => function ($query) use ($staffId) {
                    $query->field('id, order_id, staff_id, package_name, service_date, confirm_status, item_status, item_type')
                        ->where('staff_id', $staffId)
                        ->order('id', 'asc');
                },
            ])
            ->field([
                $orderTable . '.id' => 'id',
                $orderTable . '.order_sn',
                $orderTable . '.order_status',
                $orderTable . '.pay_status',
                $orderTable . '.confirm_deadline_time',
                $orderTable . '.pay_amount',
                $orderTable . '.service_address',
                $orderTable . '.contact_name',
                $orderTable . '.contact_mobile',
                $orderTable . '.create_time',
            ])
            ->order($orderTable . '.id', 'desc')
            ->limit(5)
            ->select()
            ->toArray();

        $result = [];
        foreach ($orders as $order) {
            $result[] = self::formatRecentOrder($order);
        }

        return $result;
    }

    /**
     * @notes 格式化最近订单
     */
    private static function formatRecentOrder(array $order): array
    {
        $items = $order['items'] ?? [];
        $displayItems = array_values(array_filter($items, [self::class, 'isStaffDisplayOrderItem']));
        $serviceDates = array_values(array_filter(array_map(function ($item) {
            return (string) ($item['service_date'] ?? '');
        }, $displayItems)));
        sort($serviceDates);

        $packageNames = array_values(array_filter(array_unique(array_map(function ($item) {
            return (string) ($item['package_name'] ?? '');
        }, $displayItems))));
        $pendingConfirmCount = count(array_filter($displayItems, function ($item) {
            return (int) ($item['confirm_status'] ?? 0) === 0
                && (int) ($item['item_status'] ?? OrderItem::STATUS_PENDING) !== OrderItem::STATUS_CANCELLED;
        }));

        return array_merge([
            'id' => (int) ($order['id'] ?? 0),
            'order_sn' => $order['order_sn'] ?? '',
            'service_date' => $serviceDates[0] ?? '',
            'contact_name' => $order['contact_name'] ?? '',
            'contact_mobile' => $order['contact_mobile'] ?? '',
            'service_address' => $order['service_address'] ?? '',
            'order_status' => (int) ($order['order_status'] ?? 0),
            'order_status_desc' => self::getStatusDesc((int) ($order['order_status'] ?? 0)),
            'pay_amount' => round((float) ($order['pay_amount'] ?? 0), 2),
            'item_count' => count($displayItems),
            'package_names' => $packageNames,
            'pending_confirm_count' => $pendingConfirmCount,
        ], Order::buildPayTimeoutSummaryFromState(
            (int) ($order['order_status'] ?? Order::STATUS_PENDING_PAY),
            (int) ($order['pay_deadline_time'] ?? 0)
        ), Order::buildConfirmTimeoutSummaryFromState(
            (int) ($order['order_status'] ?? Order::STATUS_PENDING_CONFIRM),
            (int) ($order['confirm_deadline_time'] ?? 0)
        ));
    }

    /**
     * @notes 构造服务人员订单基础查询
     */
    private static function buildStaffOrderQuery(int $staffId)
    {
        $orderTable = (new Order())->getTable();

        return OrderItem::alias('oi')
            ->join($orderTable . ' o', 'o.id = oi.order_id')
            ->where('oi.staff_id', $staffId)
            ->whereIn('oi.item_type', self::getStaffStatItemTypes())
            ->whereNull('o.delete_time');
    }

    /**
     * @notes 构造当前服务人员本人待确认订单查询
     */
    private static function buildPendingConfirmOrderQuery(int $staffId)
    {
        return self::buildStaffOrderQuery($staffId)
            ->where('oi.confirm_status', 0)
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->where('o.order_status', Order::STATUS_PENDING_CONFIRM);
    }

    /**
     * @notes 构造服务人员相关订单基础查询
     */
    private static function buildStaffRelatedOrderBaseQuery(int $staffId)
    {
        return Order::whereIn('id', function ($subQuery) use ($staffId) {
            self::applyStaffOrderIdSubQuery($subQuery, $staffId);
        });
    }

    /**
     * @notes 构造当前服务人员本人待确认订单基础查询
     */
    private static function buildPendingConfirmOrderBaseQuery(int $staffId)
    {
        return Order::where('order_status', Order::STATUS_PENDING_CONFIRM)
            ->whereIn('id', function ($subQuery) use ($staffId) {
                self::applyPendingConfirmOrderIdSubQuery($subQuery, $staffId);
            });
    }

    /**
     * @notes 服务人员订单统计只统计主服务与关联人员项
     */
    private static function getStaffStatItemTypes(): array
    {
        return [OrderItem::TYPE_SERVICE, OrderItem::TYPE_RELATED_STAFF];
    }

    /**
     * @notes 当前服务人员相关订单ID子查询
     */
    private static function applyStaffOrderIdSubQuery($subQuery, int $staffId): void
    {
        $subQuery->name('order_item')
            ->where('staff_id', $staffId)
            ->whereIn('item_type', self::getStaffStatItemTypes())
            ->field('order_id');
    }

    /**
     * @notes 当前服务人员本人待确认订单ID子查询
     */
    private static function applyPendingConfirmOrderIdSubQuery($subQuery, int $staffId): void
    {
        self::applyStaffOrderIdSubQuery($subQuery, $staffId);
        $subQuery
            ->where('confirm_status', 0)
            ->where('item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->field('order_id');
    }

    /**
     * @notes 订单卡片与最近订单展示仅统计主服务与关联人员项
     */
    private static function isStaffDisplayOrderItem(array $item): bool
    {
        return in_array(
            (int) ($item['item_type'] ?? OrderItem::TYPE_SERVICE),
            self::getStaffStatItemTypes(),
            true
        );
    }

    /**
     * @notes 更新个人资料
     */
    public static function updateProfile(int $userId, array $params): array|bool
    {
        $staff = Staff::where('user_id', $userId)->find();
        if (!$staff) {
            self::setError('未绑定服务人员');
            return false;
        }

        Db::startTrans();
        try {
            if (!empty($params['mobile'])) {
                $params['mobile_full'] = $params['mobile'];
            }

            $oldCategoryId = (int)$staff->category_id;
            $newCategoryId = (int)($params['category_id'] ?? $oldCategoryId);
            $rawMobile = $staff->getData('mobile_full') ?: $staff->getData('mobile');

            $update = [
                'name' => $params['name'],
                'avatar' => $params['avatar'] ?? $staff->avatar,
                'mobile' => $params['mobile'] ?? $rawMobile,
                'mobile_full' => $params['mobile_full'] ?? $rawMobile,
                'category_id' => $newCategoryId,
                'experience_years' => $params['experience_years'] ?? $staff->experience_years,
                'profile' => $params['profile'] ?? $staff->profile,
                'service_desc' => $params['service_desc'] ?? $staff->service_desc,
                'long_detail' => $params['long_detail'] ?? $staff->long_detail,
                'update_time' => time(),
            ];

            $staff->save($update);

            $tagResult = [
                'action' => 'applied',
                'message' => '标签未变化',
            ];
            if (array_key_exists('tag_ids', $params)) {
                $tagResult = StaffTagReviewService::handleSelfTagUpdate(
                    (int) $staff->id,
                    $newCategoryId,
                    is_array($params['tag_ids']) ? $params['tag_ids'] : [],
                    [
                        'source' => \app\common\model\staff\StaffTagApply::SOURCE_UNIAPP,
                        'submit_user_id' => $userId,
                    ]
                );
            }

            if ($newCategoryId > 0 && $newCategoryId !== $oldCategoryId) {
                self::syncOwnedPackageCategory((int)$staff->id, $newCategoryId);
                self::syncOwnedAddonCategory((int)$staff->id, $newCategoryId);
            }

            Db::commit();
            return [
                'tag_action' => $tagResult['action'] ?? 'applied',
                'tag_message' => $tagResult['message'] ?? '',
            ];
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 作品列表
     */
    public static function workLists(int $userId, array $params): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = StaffWork::where('staff_id', $staffId)
            ->order('sort', 'desc')
            ->order('id', 'desc');

        if (isset($params['is_show']) && $params['is_show'] !== '') {
            $query->where('is_show', (int) $params['is_show']);
        }

        if (isset($params['audit_status']) && $params['audit_status'] !== '') {
            $query->where('audit_status', (int) $params['audit_status']);
        } elseif (isset($params['is_show']) && $params['is_show'] !== '') {
            $query->where('audit_status', StaffWork::AUDIT_PASS);
        } else {
            $query->where('audit_status', '<>', StaffWork::AUDIT_REJECT);
        }

        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }

        $result = $query->paginate($pageSize)->toArray();
        $result['summary'] = [
            'total' => (int) StaffWork::where('staff_id', $staffId)
                ->where('audit_status', '<>', StaffWork::AUDIT_REJECT)
                ->count(),
            'visible_count' => (int) StaffWork::where('staff_id', $staffId)
                ->where('audit_status', StaffWork::AUDIT_PASS)
                ->where('is_show', 1)
                ->count(),
            'hidden_count' => (int) StaffWork::where('staff_id', $staffId)
                ->where('audit_status', StaffWork::AUDIT_PASS)
                ->where('is_show', 0)
                ->count(),
            'pending_audit_count' => (int) StaffWork::where('staff_id', $staffId)->where('audit_status', StaffWork::AUDIT_PENDING)->count(),
            'rejected_count' => (int) StaffWork::where('staff_id', $staffId)->where('audit_status', StaffWork::AUDIT_REJECT)->count(),
        ];

        return $result;
    }

    /**
     * @notes 作品详情
     */
    public static function workDetail(int $userId, int $id): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $work = StaffWork::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$work) {
            self::setError('作品不存在');
            return [];
        }

        return $work->toArray();
    }

    /**
     * @notes 添加作品
     */
    public static function workAdd(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $shootDate = self::normalizeNullableField($params, 'shoot_date');

        StaffWork::create([
            'staff_id' => $staffId,
            'title' => $params['title'],
            'cover' => $params['cover'] ?? '',
            'images' => $params['images'] ?? [],
            'video' => $params['video'] ?? '',
            'description' => $params['description'] ?? '',
            'shoot_date' => $shootDate['value'],
            'location' => $params['location'] ?? '',
            'sort' => $params['sort'] ?? 0,
            'is_show' => $params['is_show'] ?? 1,
            'audit_status' => StaffWork::AUDIT_PENDING,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return true;
    }

    /**
     * @notes 编辑作品
     */
    public static function workEdit(int $userId, int $id, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $work = StaffWork::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$work) {
            self::setError('作品不存在');
            return false;
        }

        $shootDate = self::normalizeNullableField($params, 'shoot_date');

        $saveData = [
            'title' => $params['title'] ?? $work->title,
            'cover' => $params['cover'] ?? $work->cover,
            'images' => $params['images'] ?? $work->images,
            'video' => $params['video'] ?? $work->video,
            'description' => $params['description'] ?? $work->description,
            'location' => $params['location'] ?? $work->location,
            'sort' => $params['sort'] ?? $work->sort,
            'is_show' => $params['is_show'] ?? $work->is_show,
            'audit_status' => StaffWork::AUDIT_PENDING,
            'update_time' => time(),
        ];

        if ($shootDate['exists']) {
            $saveData['shoot_date'] = $shootDate['value'];
        }

        $work->save($saveData);

        return true;
    }

    /**
     * @notes 将可选字段中的空字符串统一归一化为null
     */
    private static function normalizeNullableField(array $params, string $field): array
    {
        if (!array_key_exists($field, $params)) {
            return ['exists' => false, 'value' => null];
        }

        $value = $params[$field];
        if ($value === null) {
            return ['exists' => true, 'value' => null];
        }

        if (is_string($value) && trim($value) === '') {
            return ['exists' => true, 'value' => null];
        }

        return ['exists' => true, 'value' => $value];
    }

    /**
     * @notes 删除作品
     */
    public static function workDelete(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $work = StaffWork::where('id', $id)->where('staff_id', $staffId)->find();
        if (!$work) {
            self::setError('作品不存在');
            return false;
        }

        $work->delete();
        return true;
    }

    /**
     * @notes 套餐列表
     */
    public static function packageLists(int $userId, array $params = []): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = ServicePackage::where('staff_id', $staffId)
            ->where('delete_time', null)
            ->field('id, staff_id, category_id, name, price, original_price, description, image, duration, sort, is_show, is_recommend')
            ->append(['category_name'])
            ->order('sort', 'desc')
            ->order('id', 'desc');

        if (isset($params['is_show']) && $params['is_show'] !== '') {
            $query->where('is_show', (int) $params['is_show']);
        }
        if (isset($params['is_recommend']) && $params['is_recommend'] !== '') {
            $query->where('is_recommend', (int) $params['is_recommend']);
        }

        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }

        return $query->paginate($pageSize)->toArray();
    }

    /**
     * @notes 套餐详情
     */
    public static function packageDetail(int $userId, int $packageId): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $package = ServicePackage::where('id', $packageId)
            ->where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->append(['category_name'])
            ->find();
        if (!$package) {
            self::setError('套餐不存在');
            return [];
        }

        $data = $package->toArray();
        $list = PackageRegionPriceService::attachRegionPrices([$data]);
        return $list[0] ?? $data;
    }

    /**
     * @notes 添加套餐
     */
    public static function packageAdd(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        try {
            Db::transaction(function () use ($staffId, $params) {
                $package = ServicePackage::create(self::buildPackagePayload($staffId, $params));
                PackageRegionPriceService::syncPackageRegionPrices(
                    (int) $package->id,
                    $staffId,
                    is_array($params['region_prices'] ?? null) ? $params['region_prices'] : []
                );
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 更新套餐
     */
    public static function packageUpdate(int $userId, int $packageId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $package = ServicePackage::where('id', $packageId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$package) {
            self::setError('套餐不存在');
            return false;
        }

        try {
            Db::transaction(function () use ($package, $staffId, $params) {
                $package->save(self::buildPackagePayload($staffId, $params, false));
                PackageRegionPriceService::syncPackageRegionPrices(
                    (int) $package->id,
                    $staffId,
                    is_array($params['region_prices'] ?? null) ? $params['region_prices'] : []
                );
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 移除套餐
     */
    public static function packageRemove(int $userId, int $packageId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $package = ServicePackage::where('id', $packageId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$package) {
            self::setError('套餐不存在');
            return false;
        }

        ServicePackageAddon::clearByPackageId($packageId);
        ServicePackageRegionPrice::where('package_id', $packageId)->delete();
        $package->delete();
        return true;
    }

    /**
     * @notes 附加服务列表
     */
    public static function addonLists(int $userId, array $params = []): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = ServiceAddon::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->field('id, staff_id, category_id, name, price, original_price, description, image, sort, is_show')
            ->append(['category_name'])
            ->order('sort', 'desc')
            ->order('id', 'desc');

        if (isset($params['is_show']) && $params['is_show'] !== '') {
            $query->where('is_show', (int) $params['is_show']);
        }

        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }

        return $query->paginate($pageSize)->toArray();
    }

    /**
     * @notes 附加服务详情
     */
    public static function addonDetail(int $userId, int $addonId): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $addon = ServiceAddon::where('id', $addonId)
            ->where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->append(['category_name'])
            ->find();
        if (!$addon) {
            self::setError('附加服务不存在');
            return [];
        }

        return $addon->toArray();
    }

    /**
     * @notes 添加附加服务
     */
    public static function addonAdd(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        try {
            Db::transaction(function () use ($staffId, $params) {
                ServiceAddon::create(self::buildAddonPayload($staffId, $params));
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 更新附加服务
     */
    public static function addonUpdate(int $userId, int $addonId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $addon = ServiceAddon::where('id', $addonId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$addon) {
            self::setError('附加服务不存在');
            return false;
        }

        try {
            Db::transaction(function () use ($addon, $staffId, $params) {
                $addon->save(self::buildAddonPayload($staffId, $params, false));
            });
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除附加服务
     */
    public static function addonRemove(int $userId, int $addonId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $addon = ServiceAddon::where('id', $addonId)
            ->where('staff_id', $staffId)
            ->find();
        if (!$addon) {
            self::setError('附加服务不存在');
            return false;
        }

        ServicePackageAddon::clearByAddonId($addonId);
        $addon->delete();
        return true;
    }

    /**
     * @notes 月度档期
     */
    public static function scheduleMonth(int $userId, int $year, int $month): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $schedules = Schedule::getMonthSchedule($staffId, $year, $month);
        $pendingServiceOrders = self::getSchedulePendingServiceOrders($staffId, $year, $month);

        return [
            'year' => $year,
            'month' => $month,
            'schedules' => $schedules,
            'month_summary' => self::buildScheduleMonthSummary($year, $month, $schedules, $pendingServiceOrders),
            'pending_service_orders' => $pendingServiceOrders,
        ];
    }

    /**
     * @notes 获取档期页待履约订单摘要
     * @param int $staffId
     * @param int $year
     * @param int $month
     * @return array
     */
    private static function getSchedulePendingServiceOrders(int $staffId, int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $rows = OrderItem::alias('oi')
            ->leftJoin('la_order o', 'o.id = oi.order_id')
            ->field([
                'oi.order_id',
                'oi.package_name',
                'oi.service_date',
                'o.order_sn',
                'o.order_status',
                'o.contact_name',
                'o.contact_mobile',
                'o.service_address',
            ])
            ->where('oi.staff_id', $staffId)
            ->whereIn('oi.item_type', self::getStaffStatItemTypes())
            ->where('oi.item_status', '<>', OrderItem::STATUS_CANCELLED)
            ->whereBetween('oi.service_date', [$startDate, $endDate])
            ->where('o.order_status', Order::STATUS_PENDING_SERVICE)
            ->whereNull('o.delete_time')
            ->order('oi.service_date', 'asc')
            ->order('oi.order_id', 'asc')
            ->select()
            ->toArray();

        $grouped = [];
        foreach ($rows as $row) {
            $groupKey = (string)$row['service_date'] . '#' . (int)$row['order_id'];
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'order_id' => (int)$row['order_id'],
                    'order_sn' => (string)($row['order_sn'] ?? ''),
                    'service_date' => (string)($row['service_date'] ?? ''),
                    'contact_name' => (string)($row['contact_name'] ?? ''),
                    'contact_mobile' => (string)($row['contact_mobile'] ?? ''),
                    'service_address' => (string)($row['service_address'] ?? ''),
                    'package_names' => [],
                    'item_count' => 0,
                    'order_status' => (int)($row['order_status'] ?? Order::STATUS_PENDING_SERVICE),
                    'order_status_desc' => self::getStatusDesc((int)($row['order_status'] ?? Order::STATUS_PENDING_SERVICE)),
                    'can_staff_start' => (int)($row['order_status'] ?? Order::STATUS_PENDING_SERVICE) === Order::STATUS_PENDING_SERVICE ? 1 : 0,
                ];
            }

            $packageName = trim((string)($row['package_name'] ?? ''));
            if ($packageName !== '' && !in_array($packageName, $grouped[$groupKey]['package_names'], true)) {
                $grouped[$groupKey]['package_names'][] = $packageName;
            }
            $grouped[$groupKey]['item_count']++;
        }

        $result = [];
        foreach ($grouped as $item) {
            $packageNames = $item['package_names'];
            $item['package_summary'] = empty($packageNames)
                ? '待确认服务内容'
                : implode('、', array_slice($packageNames, 0, 2))
                    . (count($packageNames) > 2 ? ' 等 ' . count($packageNames) . ' 项' : '');
            unset($item['package_names']);
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @notes 构建档期页月度汇总
     * @param int $year
     * @param int $month
     * @param array $schedules
     * @param array $pendingServiceOrders
     * @return array
     */
    private static function buildScheduleMonthSummary(
        int $year,
        int $month,
        array $schedules,
        array $pendingServiceOrders
    ): array {
        $today = date('Y-m-d');
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $pendingServiceDates = array_flip(array_column($pendingServiceOrders, 'service_date'));

        $availableDays = 0;
        $occupiedDays = 0;
        $unavailableDays = 0;

        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            if ($currentDate < $today) {
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                continue;
            }

            $status = (int)($schedules[$currentDate][Schedule::TIME_SLOT_ALL]['status'] ?? Schedule::STATUS_AVAILABLE);
            if (isset($pendingServiceDates[$currentDate])) {
                $occupiedDays++;
            } elseif ($status === Schedule::STATUS_UNAVAILABLE) {
                $unavailableDays++;
            } elseif (in_array($status, [Schedule::STATUS_BOOKED, Schedule::STATUS_LOCKED, Schedule::STATUS_RESERVED], true)) {
                $occupiedDays++;
            } else {
                $availableDays++;
            }

            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        return [
            'available_days' => $availableDays,
            'occupied_days' => $occupiedDays,
            'unavailable_days' => $unavailableDays,
            'pending_service_count' => count($pendingServiceOrders),
        ];
    }

    /**
     * @notes 设置档期状态
     */
    public static function scheduleSetStatus(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $date = $params['date'];
        $status = (int) $params['status'];

        $schedule = Schedule::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', Schedule::TIME_SLOT_ALL)
            ->find();

        if ($schedule && in_array((int) $schedule->status, [Schedule::STATUS_BOOKED, Schedule::STATUS_LOCKED, Schedule::STATUS_RESERVED], true)) {
            self::setError('该档期状态不可修改');
            return false;
        }

        if ($schedule) {
            $schedule->status = $status;
            $schedule->remark = $params['remark'] ?? '';
            $schedule->update_time = time();
            $schedule->save();
        } else {
            Schedule::create([
                'staff_id' => $staffId,
                'schedule_date' => $date,
                'time_slot' => Schedule::TIME_SLOT_ALL,
                'status' => $status,
                'remark' => $params['remark'] ?? '',
                'version' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);
        }

        return true;
    }

    /**
     * @notes 订单列表
     */
    public static function orderLists(int $userId, array $params): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $status = isset($params['status']) && $params['status'] !== ''
            ? (int) $params['status']
            : null;
        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }

        if ($status === Order::STATUS_PENDING_CONFIRM) {
            $query = Order::where('order_status', Order::STATUS_PENDING_CONFIRM)
                ->whereIn('id', function ($subQuery) use ($staffId) {
                    self::applyPendingConfirmOrderIdSubQuery($subQuery, $staffId);
                });
        } else {
            $query = self::buildStaffRelatedOrderBaseQuery($staffId);
        }

        if ($status !== null && $status !== Order::STATUS_PENDING_CONFIRM) {
            $query->where('order_status', $status);
        }

        if (!empty($params['keyword'])) {
            $query->where('order_sn|contact_name|contact_mobile', 'like', '%' . $params['keyword'] . '%');
        }

        $list = $query->with([
                'items' => function ($q) use ($staffId) {
                        $q->field('id, order_id, staff_id, staff_name, package_name, package_description, service_date, item_status, confirm_status, schedule_id, item_type, item_meta, price, quantity, subtotal')
                        ->where('staff_id', $staffId)
                        ->with(['staff' => function ($staffQuery) {
                            $staffQuery->field('id, name, avatar');
                        }, 'addons' => function ($addonQuery) {
                            $addonQuery->field('id, order_item_id, addon_id, addon_name, price, quantity, subtotal');
                        }]);
                },
            ])
            ->order('id', 'desc')
            ->paginate($pageSize)
            ->toArray();

        foreach ($list['data'] as &$item) {
            $item = self::applyStaffOrderAmounts($item);
            $item['order_status_desc'] = self::getStatusDesc((int) $item['order_status']);
            $item['pay_status_desc'] = self::getPayStatusDesc((int) $item['pay_status']);
            $item['can_staff_start'] = (int)($item['order_status'] ?? -1) === Order::STATUS_PENDING_SERVICE ? 1 : 0;
            $item['can_staff_complete'] = (int)($item['order_status'] ?? -1) === Order::STATUS_IN_SERVICE
                && Order::canStaffCompleteService();
            $item = array_merge($item, Order::buildPayTimeoutSummaryFromState(
                (int) ($item['order_status'] ?? Order::STATUS_PENDING_PAY),
                (int) ($item['pay_deadline_time'] ?? 0)
            ));
            $item = array_merge($item, Order::buildConfirmTimeoutSummaryFromState(
                (int) ($item['order_status'] ?? Order::STATUS_PENDING_CONFIRM),
                (int) ($item['confirm_deadline_time'] ?? 0)
            ));
        }
        unset($item);

        return $list;
    }

    /**
     * @notes 订单详情
     */
    public static function orderDetail(int $userId, int $orderId): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $order = Order::with([
                'items' => function ($q) use ($staffId) {
                        $q->field('id, order_id, staff_id, staff_name, package_name, package_description, service_date, item_status, confirm_status, schedule_id, item_type, item_meta, price, quantity, subtotal')
                        ->where('staff_id', $staffId)
                        ->with(['staff' => function ($staffQuery) {
                            $staffQuery->field('id, name, avatar');
                        }, 'addons' => function ($addonQuery) {
                            $addonQuery->field('id, order_item_id, addon_id, addon_name, price, quantity, subtotal');
                        }]);
                },
            ])
            ->hasWhere('items', ['staff_id' => $staffId])
            ->find($orderId);

        if (!$order) {
            self::setError('订单不存在');
            return [];
        }

        $data = $order->toArray();
        $data = self::applyStaffOrderAmounts($data);
        $data['order_status_desc'] = self::getStatusDesc((int) $order->order_status);
        $data['pay_status_desc'] = self::getPayStatusDesc((int) $order->pay_status);
        $data['pay_type_desc'] = self::getPayTypeDesc((int) $order->pay_type);
        $data = array_merge($data, Order::getPaymentSummary($order));
        $data = array_merge($data, Order::getPayTimeoutSummary($order));
        $data = array_merge($data, Order::getConfirmTimeoutSummary($order));
        $data['can_staff_start'] = (int)$order->order_status === Order::STATUS_PENDING_SERVICE ? 1 : 0;
        $data['can_staff_complete'] = (int)$order->order_status === Order::STATUS_IN_SERVICE
            && Order::canStaffCompleteService();

        return $data;
    }

    /**
     * @notes 确认订单
     */
    public static function orderConfirm(int $userId, int $orderId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $shouldNotifyUser = false;

        $result = Db::transaction(function () use ($userId, $staffId, $orderId, &$shouldNotifyUser) {
            // 加锁查询，防止并发确认
            $order = Order::with(['items'])
                ->where('id', $orderId)
                ->lock(true)
                ->find();

            if (!$order) {
                self::setError('订单不存在');
                return false;
            }

            if (is_array($order->items ?? null)) {
                $allItems = $order->items;
            } elseif (is_object($order->items ?? null) && method_exists($order->items, 'toArray')) {
                $allItems = $order->items->toArray();
            } else {
                $allItems = [];
            }
            $staffItems = array_values(array_filter($allItems, function ($item) use ($staffId) {
                return (int) ($item['staff_id'] ?? 0) === $staffId;
            }));
            if (empty($staffItems)) {
                self::setError('无权确认该订单');
                return false;
            }

            if ((int) $order->order_status !== Order::STATUS_PENDING_CONFIRM) {
                self::setError('当前订单状态不可确认');
                return false;
            }

            $pendingItems = array_values(array_filter($staffItems, function ($item) {
                return (int) ($item['confirm_status'] ?? 0) === 0;
            }));
            if (empty($pendingItems)) {
                self::setError('已确认，无需重复操作');
                return false;
            }

            // 确认档期并标记订单项
            foreach ($pendingItems as $item) {
                if (!empty($item['schedule_id'])) {
                    [$ok, $msg] = Schedule::confirmBooking(
                        (int) $item['staff_id'],
                        (string) $item['service_date'],
                        0,
                        (int) $order->id,
                        (int) $order->user_id
                    );
                    if (!$ok) {
                        throw new \Exception($msg);
                    }
                }

                OrderItem::where('id', $item['id'])->update([
                    'confirm_status' => 1,
                    'update_time' => time(),
                ]);
            }

            // 全部订单项均已确认则推进订单状态
            $remain = OrderItem::where('order_id', $order->id)
                ->where('confirm_status', 0)
                ->count();

            if ($remain === 0) {
                $beforeStatus = (int) $order->order_status;
                $order->order_status = Order::STATUS_PENDING_PAY;
                $order->confirm_deadline_time = 0;
                $order->update_time = time();
                $order->save();
                Order::syncPendingPayDeadline($order);
                $shouldNotifyUser = true;

                OrderLog::addLog(
                    $order->id,
                    OrderLog::OPERATOR_USER,
                    $userId,
                    'confirm',
                    $beforeStatus,
                    Order::STATUS_PENDING_PAY,
                    '全部服务人员已确认，进入待支付'
                );
            }

            return true;
        });

        if ($result && $shouldNotifyUser) {
            OrderNotificationService::notifyUserOnOrderConfirmed($orderId);
        }

        return $result;
    }

    /**
     * @notes 完成服务
     */
    public static function orderComplete(int $userId, int $orderId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $order = Order::with(['items'])->find($orderId);
        if (!$order) {
            self::setError('订单不存在');
            return false;
        }

        $staffItems = is_array($order->items ?? null)
            ? $order->items
            : ((is_object($order->items ?? null) && method_exists($order->items, 'toArray')) ? $order->items->toArray() : []);
        $matchedItems = array_values(array_filter($staffItems, function ($item) use ($staffId) {
            return (int)($item['staff_id'] ?? 0) === $staffId;
        }));

        if (empty($matchedItems)) {
            self::setError('无权操作该订单');
            return false;
        }

        [$success, $message] = Order::completeOrder($orderId, $userId, OrderLog::OPERATOR_USER, 'staff');
        if (!$success) {
            self::setError($message);
            return false;
        }

        return true;
    }

    /**
     * @notes 开始履约
     */
    public static function orderStartService(int $userId, int $orderId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $order = Order::with(['items'])->find($orderId);
        if (!$order) {
            self::setError('订单不存在');
            return false;
        }

        $staffItems = is_array($order->items ?? null)
            ? $order->items
            : ((is_object($order->items ?? null) && method_exists($order->items, 'toArray')) ? $order->items->toArray() : []);
        $matchedItems = array_values(array_filter($staffItems, function ($item) use ($staffId) {
            return (int)($item['staff_id'] ?? 0) === $staffId;
        }));

        if (empty($matchedItems)) {
            self::setError('无权操作该订单');
            return false;
        }

        [$success, $message] = Order::startService(
            $orderId,
            $userId,
            OrderLog::OPERATOR_USER,
            '服务人员开始履约'
        );
        if (!$success) {
            self::setError($message);
            return false;
        }

        return true;
    }

    /**
     * @notes 动态列表
     */
    public static function dynamicLists(int $userId, array $params): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $query = Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
            ->where('staff_id', $staffId)
            ->order('create_time', 'desc');

        $summaryQuery = Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
            ->where('staff_id', $staffId);

        $dynamicType = (int) ($params['dynamic_type'] ?? 0);
        if ($dynamicType > 0) {
            $query->where('dynamic_type', $dynamicType);
        }

        $status = $params['status'] ?? null;
        if ($status !== null && $status !== '' && in_array((int) $status, [
            Dynamic::STATUS_PENDING,
            Dynamic::STATUS_PUBLISHED,
            Dynamic::STATUS_OFFLINE,
            Dynamic::STATUS_REJECTED,
        ], true)) {
            $query->where('status', (int) $status);
        }

        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }

        $result = $query->paginate($pageSize)->toArray();
        foreach (($result['data'] ?? []) as $index => $item) {
            $result['data'][$index] = self::formatStaffDynamicItem($item);
        }
        $result['summary'] = [
            'total' => (int) $summaryQuery->count(),
            'published_count' => (int) Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
                ->where('staff_id', $staffId)
                ->where('status', Dynamic::STATUS_PUBLISHED)
                ->count(),
            'pending_count' => (int) Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
                ->where('staff_id', $staffId)
                ->where('status', Dynamic::STATUS_PENDING)
                ->count(),
            'offline_count' => (int) Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
                ->where('staff_id', $staffId)
                ->where('status', Dynamic::STATUS_OFFLINE)
                ->count(),
            'rejected_count' => (int) Dynamic::where('user_type', Dynamic::USER_TYPE_STAFF)
                ->where('staff_id', $staffId)
                ->where('status', Dynamic::STATUS_REJECTED)
                ->count(),
        ];

        return $result;
    }

    /**
     * @notes 动态详情
     */
    public static function dynamicDetail(int $userId, int $id): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        $dynamic = Dynamic::where('id', $id)
            ->where('staff_id', $staffId)
            ->where('user_type', Dynamic::USER_TYPE_STAFF)
            ->append(['tags_arr', 'dynamic_type_desc', 'status_desc', 'allow_comment_desc'])
            ->find();
        if (!$dynamic) {
            self::setError('动态不存在');
            return [];
        }

        return self::formatStaffDynamicItem($dynamic->toArray());
    }

    /**
     * @notes 发布动态
     */
    public static function dynamicAdd(int $userId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        [$success, $message] = Dynamic::publish($staffId, Dynamic::USER_TYPE_STAFF, [
            'staff_id' => $staffId,
            'dynamic_type' => $params['dynamic_type'] ?? Dynamic::TYPE_IMAGE_TEXT,
            'title' => $params['title'] ?? '',
            'content' => $params['content'],
            'images' => $params['images'] ?? [],
            'video_url' => $params['video_url'] ?? '',
            'video_cover' => $params['video_cover'] ?? '',
            'location' => $params['location'] ?? '',
            'latitude' => $params['latitude'] ?? 0,
            'longitude' => $params['longitude'] ?? 0,
            'tags' => $params['tags'] ?? '',
            'allow_comment' => $params['allow_comment'] ?? 1,
            'order_id' => $params['order_id'] ?? 0,
        ]);

        if (!$success) {
            self::setError($message);
            return false;
        }
        return true;
    }

    /**
     * @notes 编辑动态
     */
    public static function dynamicEdit(int $userId, int $id, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $dynamic = Dynamic::where('id', $id)
            ->where('staff_id', $staffId)
            ->where('user_type', Dynamic::USER_TYPE_STAFF)
            ->find();
        if (!$dynamic) {
            self::setError('动态不存在');
            return false;
        }

        $dynamic->dynamic_type = $params['dynamic_type'] ?? $dynamic->dynamic_type;
        $dynamic->title = $params['title'] ?? $dynamic->title;
        $dynamic->content = $params['content'] ?? $dynamic->content;
        $dynamic->images = $params['images'] ?? $dynamic->images;
        $dynamic->video_url = $params['video_url'] ?? $dynamic->video_url;
        $dynamic->video_cover = $params['video_cover'] ?? $dynamic->video_cover;
        $dynamic->location = $params['location'] ?? $dynamic->location;
        $dynamic->latitude = $params['latitude'] ?? $dynamic->latitude;
        $dynamic->longitude = $params['longitude'] ?? $dynamic->longitude;
        $dynamic->tags = is_array($params['tags'] ?? '') ? implode(',', $params['tags']) : ($params['tags'] ?? $dynamic->tags);
        $dynamic->allow_comment = $params['allow_comment'] ?? $dynamic->allow_comment;
        $dynamic->status = Dynamic::STATUS_PENDING;
        $dynamic->update_time = time();
        $dynamic->save();

        return true;
    }

    /**
     * @notes 删除动态
     */
    public static function dynamicDelete(int $userId, int $id): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $dynamic = Dynamic::where('id', $id)
            ->where('staff_id', $staffId)
            ->where('user_type', Dynamic::USER_TYPE_STAFF)
            ->find();
        if (!$dynamic) {
            self::setError('动态不存在');
            return false;
        }

        $dynamic->delete();
        return true;
    }

    /**
     * @notes 仅保留当前服务人员相关金额
     */
    protected static function applyStaffOrderAmounts(array $order): array
    {
        $items = $order['items'] ?? [];
        $staffServiceAmount = 0.0;
        $staffAddonAmount = 0.0;
        foreach ($items as $index => $item) {
            $subtotal = isset($item['subtotal']) ? (float) $item['subtotal'] : 0.0;
            if ($subtotal <= 0) {
                $price = (float) ($item['price'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 1);
                $subtotal = $price * max($quantity, 1);
            }
            $addonAmount = 0.0;
            foreach (($item['addons'] ?? []) as $addon) {
                $addonAmount += (float)($addon['subtotal'] ?? $addon['price'] ?? 0);
            }
            $addonAmount = round($addonAmount, 2);
            $items[$index]['addon_amount'] = $addonAmount;
            if ((int)($item['item_type'] ?? OrderItem::TYPE_SERVICE) === OrderItem::TYPE_SERVICE) {
                $staffServiceAmount += $subtotal;
            } else {
                $staffAddonAmount += $subtotal;
            }
            $staffAddonAmount += $addonAmount;
        }
        $order['items'] = $items;
        $staffServiceAmount = round($staffServiceAmount, 2);
        $staffAddonAmount = round($staffAddonAmount, 2);
        $staffTotal = round($staffServiceAmount + $staffAddonAmount, 2);

        $orderTotal = (float) ($order['total_amount'] ?? 0);
        $discountTotal = (float) ($order['discount_amount'] ?? 0);

        if ($orderTotal > 0 && $staffTotal > 0) {
            $ratio = $staffTotal / $orderTotal;
            $staffDiscount = round($discountTotal * $ratio, 2);
        } else {
            $staffDiscount = 0.0;
        }

        $staffPay = round($staffTotal - $staffDiscount, 2);
        if ($staffPay < 0) {
            $staffPay = 0.0;
        }

        $order['service_amount'] = $staffServiceAmount;
        $order['addon_amount'] = $staffAddonAmount;
        $order['total_amount'] = $staffTotal;
        $order['discount_amount'] = $staffDiscount;
        $order['pay_amount'] = $staffPay;

        return $order;
    }

    /**
     * @notes 获取订单状态描述
     */
    protected static function getStatusDesc(int $status): string
    {
        return Order::getStatusText($status);
    }

    /**
     * @notes 获取支付状态描述
     */
    protected static function getPayStatusDesc(int $status): string
    {
        return Order::getPayStatusText($status);
    }

    /**
     * @notes 获取支付方式描述
     */
    protected static function getPayTypeDesc(int $type): string
    {
        return Order::getPayWayText($type);
    }

    /**
     * @notes 统一服务人员动态状态反馈
     * @param array $item
     * @return array
     */
    protected static function formatStaffDynamicItem(array $item): array
    {
        $status = (int)($item['status'] ?? Dynamic::STATUS_PENDING);
        $remark = trim((string)($item['audit_remark'] ?? ''));

        $item['status_desc'] = self::getDynamicStatusDesc($status);
        $item['dynamic_type_desc'] = $item['dynamic_type_desc'] ?? self::getDynamicTypeDesc((int)($item['dynamic_type'] ?? 0));
        $item['allow_comment_desc'] = ((int)($item['allow_comment'] ?? 1) === 1) ? '允许评论' : '禁止评论';
        $item['status_reason'] = self::getDynamicStatusReason($status, $remark);
        $item['status_hint'] = self::getDynamicStatusHint($status);
        $item['handled_time'] = (int)($item['audit_time'] ?? 0);

        return $item;
    }

    /**
     * @notes 获取动态状态描述
     * @param int $status
     * @return string
     */
    protected static function getDynamicStatusDesc(int $status): string
    {
        return match ($status) {
            Dynamic::STATUS_PENDING => '待审核',
            Dynamic::STATUS_PUBLISHED => '已发布',
            Dynamic::STATUS_OFFLINE => '已下架',
            Dynamic::STATUS_REJECTED => '已拒绝',
            default => '未知',
        };
    }

    /**
     * @notes 获取动态类型描述
     * @param int $type
     * @return string
     */
    protected static function getDynamicTypeDesc(int $type): string
    {
        return match ($type) {
            Dynamic::TYPE_IMAGE_TEXT => '图文',
            Dynamic::TYPE_VIDEO => '视频',
            Dynamic::TYPE_CASE => '案例',
            Dynamic::TYPE_ACTIVITY => '活动',
            default => '动态',
        };
    }

    /**
     * @notes 获取动态状态原因
     * @param int $status
     * @param string $remark
     * @return string
     */
    protected static function getDynamicStatusReason(int $status, string $remark): string
    {
        if (in_array($status, [Dynamic::STATUS_OFFLINE, Dynamic::STATUS_REJECTED], true)) {
            return $remark ?: '暂无补充说明';
        }

        if ($status === Dynamic::STATUS_PENDING) {
            return '内容已提交，等待后台审核。';
        }

        if ($status === Dynamic::STATUS_PUBLISHED) {
            return '当前内容已对用户可见。';
        }

        return '';
    }

    /**
     * @notes 获取动态状态操作提示
     * @param int $status
     * @return string
     */
    protected static function getDynamicStatusHint(int $status): string
    {
        return match ($status) {
            Dynamic::STATUS_PENDING => '审核通过后会自动展示到用户端。',
            Dynamic::STATUS_PUBLISHED => '可继续编辑内容；如被下架会在这里同步处理原因。',
            Dynamic::STATUS_OFFLINE => '修改内容后可重新提交审核。',
            Dynamic::STATUS_REJECTED => '请根据原因调整内容后重新提交审核。',
            default => '',
        };
    }

    /**
     * @notes 构造套餐写入载荷
     * @param int $staffId
     * @param array $params
     * @param bool $withCreateFields
     * @return array
     */
    protected static function buildPackagePayload(int $staffId, array $params, bool $withCreateFields = true): array
    {
        $payload = [
            'staff_id' => $staffId,
            'category_id' => self::resolveStaffCategoryId($staffId),
            'name' => (string)($params['name'] ?? ''),
            'price' => round((float)($params['price'] ?? 0), 2),
            'original_price' => round((float)($params['original_price'] ?? 0), 2),
            'description' => (string)($params['description'] ?? ''),
            'image' => (string)($params['image'] ?? ''),
            'sort' => (int)($params['sort'] ?? 0),
            'is_show' => (int)($params['is_show'] ?? 1),
            'is_recommend' => (int)($params['is_recommend'] ?? 0),
            'duration' => (int)($params['duration'] ?? 0),
            'update_time' => time(),
        ];

        if ($withCreateFields) {
            $payload['create_time'] = time();
        }

        return $payload;
    }

    /**
     * @notes 构造附加服务写入载荷
     * @param int $staffId
     * @param array $params
     * @param bool $withCreateFields
     * @return array
     */
    protected static function buildAddonPayload(int $staffId, array $params, bool $withCreateFields = true): array
    {
        $payload = [
            'staff_id' => $staffId,
            'category_id' => self::resolveStaffCategoryId($staffId),
            'name' => (string)($params['name'] ?? ''),
            'price' => round((float)($params['price'] ?? 0), 2),
            'original_price' => round((float)($params['original_price'] ?? 0), 2),
            'description' => (string)($params['description'] ?? ''),
            'image' => (string)($params['image'] ?? ''),
            'sort' => (int)($params['sort'] ?? 0),
            'is_show' => (int)($params['is_show'] ?? 1),
            'update_time' => time(),
        ];

        if ($withCreateFields) {
            $payload['create_time'] = time();
        }

        return $payload;
    }

    /**
     * @notes 同步人员名下套餐分类
     * @param int $staffId
     * @param int $categoryId
     * @return void
     */
    protected static function syncOwnedPackageCategory(int $staffId, int $categoryId): void
    {
        if ($staffId <= 0 || $categoryId <= 0) {
            return;
        }

        ServicePackage::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->update([
                'category_id' => $categoryId,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 同步人员名下附加服务分类
     * @param int $staffId
     * @param int $categoryId
     * @return void
     */
    protected static function syncOwnedAddonCategory(int $staffId, int $categoryId): void
    {
        if ($staffId <= 0 || $categoryId <= 0) {
            return;
        }

        ServiceAddon::where('staff_id', $staffId)
            ->whereNull('delete_time')
            ->update([
                'category_id' => $categoryId,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 读取所属人员当前服务分类
     * @param int $staffId
     * @return int
     */
    protected static function resolveStaffCategoryId(int $staffId): int
    {
        $staff = Staff::find($staffId);
        if (!$staff) {
            throw new \Exception('所属人员不存在');
        }

        $categoryId = (int)($staff->category_id ?? 0);
        if ($categoryId <= 0) {
            throw new \Exception('请先设置服务分类');
        }

        return $categoryId;
    }

    public static function orderConfirmLetterGenerate(int $userId, int $orderId)
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        if (!self::buildStaffRelatedOrderBaseQuery($staffId)->where('id', $orderId)->find()) {
            self::setError('无权限操作该订单确认函');
            return false;
        }
        try {
            return OrderConfirmLetterService::generate($orderId, 'staff', $staffId, $staffId);
        } catch (\Throwable $e) {
            self::setError(OrderConfirmLetterService::normalizeErrorMessage($e->getMessage()));
            return false;
        }
    }

    public static function orderConfirmLetterSaveAssets(int $userId, array $params)
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        $letter = \app\common\model\order\OrderConfirmLetter::find((int) $params['letter_id']);
        if (!$letter || !self::buildStaffRelatedOrderBaseQuery($staffId)->where('id', (int) $letter->order_id)->find()) {
            self::setError('无权限操作该订单确认函');
            return false;
        }
        try {
            OrderConfirmLetterService::saveAssets((int) $params['letter_id'], (string) $params['snapshot_hash'], (string) $params['full_image_url'], (string) $params['thumb_image_url']);
            return ['letter_id' => (int) $params['letter_id'], 'assets_saved' => true];
        } catch (\Throwable $e) {
            self::setError(OrderConfirmLetterService::normalizeErrorMessage($e->getMessage()));
            return false;
        }
    }

    public static function orderConfirmLetterPush(int $userId, int $letterId)
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        $letter = \app\common\model\order\OrderConfirmLetter::find($letterId);
        if (!$letter || !self::buildStaffRelatedOrderBaseQuery($staffId)->where('id', (int) $letter->order_id)->find()) {
            self::setError('无权限操作该订单确认函');
            return false;
        }
        try {
            return OrderConfirmLetterService::push($letterId, $staffId);
        } catch (\Throwable $e) {
            self::setError(OrderConfirmLetterService::normalizeErrorMessage($e->getMessage()));
            return false;
        }
    }

    public static function orderConfirmLetterDetail(int $userId, int $letterId): ?array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return null;
        }
        $letter = \app\common\model\order\OrderConfirmLetter::find($letterId);
        if (!$letter || !self::buildStaffRelatedOrderBaseQuery($staffId)->where('id', (int) $letter->order_id)->find()) {
            self::setError('无权限操作该订单确认函');
            return null;
        }
        return OrderConfirmLetterService::detailForOrder($letterId, (int) $letter->order_id);
    }

    public static function orderConfirmLetterHistory(int $userId, int $orderId)
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }
        if (!self::buildStaffRelatedOrderBaseQuery($staffId)->where('id', $orderId)->find()) {
            self::setError('无权限操作该订单确认函');
            return false;
        }
        return OrderConfirmLetterService::history($orderId);
    }
}
