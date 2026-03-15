<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员中心逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\dynamic\Dynamic;
use app\common\model\order\Order;
use app\common\model\order\OrderLog;
use app\common\model\order\OrderItem;
use app\common\model\schedule\Schedule;
use app\common\model\service\ServicePackage;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffPackage;
use app\common\model\staff\StaffWork;
use app\common\service\StaffPriceService;
use app\common\service\StaffService;
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
        $data['orderCount'] = OrderItem::where('staff_id', $staff->id)
            ->whereExists(function ($query) {
                $orderTable = (new Order())->getTable();
                $itemTable = (new OrderItem())->getTable();
                $query->table($orderTable . ' o')
                    ->whereRaw("o.id = {$itemTable}.order_id")
                    ->whereNull('o.delete_time');
            })
            ->distinct(true)
            ->count('order_id');

        $data['workCount'] = StaffWork::where('staff_id', $staff->id)
            ->where('delete_time', null)
            ->count();

        $data['packageCount'] = StaffPackage::where('staff_id', $staff->id)->count();

        $data['scheduleCount'] = Schedule::where('staff_id', $staff->id)->count();

        return $data;
    }

    /**
     * @notes 更新个人资料
     */
    public static function updateProfile(int $userId, array $params): bool
    {
        $staff = Staff::where('user_id', $userId)->find();
        if (!$staff) {
            self::setError('未绑定服务人员');
            return false;
        }

        if (!empty($params['mobile'])) {
            $params['mobile_full'] = $params['mobile'];
        }

        $update = [
            'name' => $params['name'],
            'avatar' => $params['avatar'] ?? $staff->avatar,
            'mobile' => $params['mobile'] ?? $staff->getData('mobile_full') ?: $staff->getData('mobile'),
            'mobile_full' => $params['mobile_full'] ?? ($staff->getData('mobile_full') ?: $staff->getData('mobile')),
            'category_id' => $params['category_id'] ?? $staff->category_id,
            'experience_years' => $params['experience_years'] ?? $staff->experience_years,
            'profile' => $params['profile'] ?? $staff->profile,
            'service_desc' => $params['service_desc'] ?? $staff->service_desc,
            'update_time' => time(),
        ];

        $staff->save($update);
        return true;
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

        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }
        return $query->paginate($pageSize)->toArray();
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

        StaffWork::create([
            'staff_id' => $staffId,
            'title' => $params['title'],
            'cover' => $params['cover'] ?? '',
            'images' => $params['images'] ?? [],
            'video' => $params['video'] ?? '',
            'description' => $params['description'] ?? '',
            'shoot_date' => $params['shoot_date'] ?? null,
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

        $work->save([
            'title' => $params['title'] ?? $work->title,
            'cover' => $params['cover'] ?? $work->cover,
            'images' => $params['images'] ?? $work->images,
            'video' => $params['video'] ?? $work->video,
            'description' => $params['description'] ?? $work->description,
            'shoot_date' => $params['shoot_date'] ?? $work->shoot_date,
            'location' => $params['location'] ?? $work->location,
            'sort' => $params['sort'] ?? $work->sort,
            'is_show' => $params['is_show'] ?? $work->is_show,
            'audit_status' => StaffWork::AUDIT_PENDING,
            'update_time' => time(),
        ]);

        return true;
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
    public static function packageLists(int $userId): array
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return [];
        }

        // 手动关联的套餐
        $configured = StaffPackage::where('staff_id', $staffId)
            ->with(['package'])
            ->select()
            ->toArray();
        $configuredIds = array_column($configured, 'package_id');

        // 人员专属套餐直接视为已关联（排除已在 staff_package 中的）
        $staffOnlyPackages = ServicePackage::where('package_type', ServicePackage::TYPE_STAFF_ONLY)
            ->where('staff_id', $staffId)
            ->where('delete_time', null)
            ->where('is_show', 1)
            ->when(!empty($configuredIds), function ($query) use ($configuredIds) {
                $query->whereNotIn('id', $configuredIds);
            })
            ->select()
            ->toArray();

        // 将专属套餐包装成与 configured 相同的结构
        foreach ($staffOnlyPackages as $pkg) {
            $configured[] = [
                'id' => 0,
                'staff_id' => $staffId,
                'package_id' => $pkg['id'],
                'custom_price' => null,
                'custom_slot_prices' => [],
                'booking_type' => $pkg['booking_type'] ?? 0,
                'allowed_time_slots' => $pkg['allowed_time_slots'] ?? [],
                'status' => 1,
                'price' => $pkg['price'] ?? 0,
                'original_price' => $pkg['original_price'] ?? null,
                'is_default' => 0,
                'is_staff_only' => true,
                'package' => $pkg,
            ];
        }

        // 合并所有已关联的套餐ID
        $allConfiguredIds = array_column($configured, 'package_id');

        // 可选套餐：仅全局套餐（排除已关联的）
        $available = ServicePackage::where('package_type', ServicePackage::TYPE_GLOBAL)
            ->where('delete_time', null)
            ->where('is_show', 1)
            ->when(!empty($allConfiguredIds), function ($query) use ($allConfiguredIds) {
                $query->whereNotIn('id', $allConfiguredIds);
            })
            ->select()
            ->toArray();

        return [
            'configured' => $configured,
            'available' => $available,
        ];
    }

    /**
     * @notes 关联套餐
     */
    public static function packageAdd(int $userId, int $packageId): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        $package = ServicePackage::find($packageId);
        if (!$package) {
            self::setError('套餐不存在');
            return false;
        }
        // 允许全局套餐和当前人员的专属套餐
        if ($package->package_type == ServicePackage::TYPE_STAFF_ONLY && $package->staff_id != $staffId) {
            self::setError('该套餐不可关联');
            return false;
        }

        $exists = StaffPackage::where('staff_id', $staffId)
            ->where('package_id', $packageId)
            ->find();
        if ($exists) {
            self::setError('套餐已关联');
            return false;
        }

        StaffPackage::create([
            'staff_id' => $staffId,
            'package_id' => $packageId,
            'price' => $package->price ?? 0,
            'original_price' => $package->original_price ?? null,
            'status' => 1,
            'booking_type' => $package->booking_type ?? null,
            'allowed_time_slots' => $package->allowed_time_slots ?? null,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return true;
    }

    /**
     * @notes 更新套餐配置
     */
    public static function packageUpdate(int $userId, int $packageId, array $params): bool
    {
        $staffId = self::getStaffId($userId);
        if ($staffId <= 0) {
            self::setError('未绑定服务人员');
            return false;
        }

        // 如果 staff_package 表中没有记录（专属套餐首次编辑），先自动创建
        $exists = StaffPackage::where('staff_id', $staffId)
            ->where('package_id', $packageId)
            ->find();
        if (!$exists) {
            $package = ServicePackage::find($packageId);
            if (!$package) {
                self::setError('套餐不存在');
                return false;
            }
            StaffPackage::create([
                'staff_id' => $staffId,
                'package_id' => $packageId,
                'price' => $package->price ?? 0,
                'original_price' => $package->original_price ?? null,
                'status' => 1,
                'booking_type' => $package->booking_type ?? null,
                'allowed_time_slots' => $package->allowed_time_slots ?? null,
                'create_time' => time(),
                'update_time' => time(),
            ]);
        }

        return StaffPackage::updatePackageConfig($staffId, $packageId, $params);
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

        return StaffPackage::where('staff_id', $staffId)
            ->where('package_id', $packageId)
            ->delete() > 0;
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

        return [
            'year' => $year,
            'month' => $month,
            'schedules' => Schedule::getMonthSchedule($staffId, $year, $month),
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
        $timeSlot = (int) ($params['time_slot'] ?? 0);
        $status = (int) $params['status'];

        $schedule = Schedule::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', $timeSlot)
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
                'time_slot' => $timeSlot,
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

        $query = Order::hasWhere('items', ['staff_id' => $staffId]);

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('order_status', $params['status']);
        }

        if (!empty($params['keyword'])) {
            $query->where('order_sn|contact_name|contact_mobile', 'like', '%' . $params['keyword'] . '%');
        }

        $list = $query->with([
                'items' => function ($q) use ($staffId) {
                    $q->field('id, order_id, staff_id, staff_name, package_name, service_date, time_slot, item_status, confirm_status, schedule_id, price, quantity, subtotal')
                        ->where('staff_id', $staffId)
                        ->with(['staff' => function ($staffQuery) {
                            $staffQuery->field('id, name, avatar');
                        }]);
                },
            ])
            ->order('id', 'desc')
            ->paginate((int) ($params['page_size'] ?? 10))
            ->toArray();

        foreach ($list['data'] as &$item) {
            $item = self::applyStaffOrderAmounts($item);
            $item['order_status_desc'] = self::getStatusDesc((int) $item['order_status']);
            $item['pay_status_desc'] = self::getPayStatusDesc((int) $item['pay_status']);
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
                    $q->field('id, order_id, staff_id, staff_name, package_name, service_date, time_slot, item_status, confirm_status, schedule_id, price, quantity, subtotal')
                        ->where('staff_id', $staffId)
                        ->with(['staff' => function ($staffQuery) {
                            $staffQuery->field('id, name, avatar');
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

        return Db::transaction(function () use ($userId, $staffId, $orderId) {
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
                        (int) ($item['time_slot'] ?? 0),
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
                $order->update_time = time();
                $order->save();

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

        $pageSize = (int) ($params['page_size'] ?? 10);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }
        return $query->paginate($pageSize)->toArray();
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
        $staffTotal = 0.0;
        foreach ($items as $item) {
            $subtotal = isset($item['subtotal']) ? (float) $item['subtotal'] : 0.0;
            if ($subtotal <= 0) {
                $price = (float) ($item['price'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 1);
                $subtotal = $price * max($quantity, 1);
            }
            $staffTotal += $subtotal;
        }
        $staffTotal = round($staffTotal, 2);

        $orderTotal = (float) ($order['total_amount'] ?? 0);
        $discountTotal = (float) ($order['discount_amount'] ?? 0);
        $couponTotal = (float) ($order['coupon_amount'] ?? 0);

        if ($orderTotal > 0 && $staffTotal > 0) {
            $ratio = $staffTotal / $orderTotal;
            $staffDiscount = round($discountTotal * $ratio, 2);
            $staffCoupon = round($couponTotal * $ratio, 2);
        } else {
            $staffDiscount = 0.0;
            $staffCoupon = 0.0;
        }

        $staffPay = round($staffTotal - $staffDiscount - $staffCoupon, 2);
        if ($staffPay < 0) {
            $staffPay = 0.0;
        }

        $order['total_amount'] = $staffTotal;
        $order['discount_amount'] = $staffDiscount;
        $order['coupon_amount'] = $staffCoupon;
        $order['pay_amount'] = $staffPay;

        return $order;
    }

    /**
     * @notes 获取订单状态描述
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

    /**
     * @notes 获取支付状态描述
     */
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

    /**
     * @notes 获取支付方式描述
     */
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
