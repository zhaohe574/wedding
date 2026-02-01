<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 购物车模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\cart;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use app\common\model\staff\StaffPackage;
use app\common\model\service\ServicePackage;
use app\common\model\schedule\Schedule;
use app\common\model\package\PackageBooking;
use think\facade\Db;

/**
 * 购物车模型
 * Class Cart
 * @package app\common\model\cart
 */
class Cart extends BaseModel
{
    protected $name = 'cart';

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 关联套餐
     * @return \think\model\relation\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo(ServicePackage::class, 'package_id', 'id');
    }

    /**
     * @notes 时间段描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getTimeSlotDescAttr($value, $data): string
    {
        $map = [
            0 => '全天',
            1 => '早礼',
            2 => '午宴',
            3 => '晚宴',
        ];
        return $map[$data['time_slot']] ?? '未知';
    }

    /**
     * @notes 添加到购物车
     * @param int $userId
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $packageId
     * @param string $remark
     * @return array [bool $success, string $message, int|null $cartId]
     */
    public static function addToCart(int $userId, int $staffId, string $date, int $timeSlot = 0, int $packageId = 0, string $remark = ''): array
    {
        if ($packageId <= 0) {
            return [false, '请选择套餐', null];
        }

        [$valid, $message] = ServicePackage::validateTimeSlots($packageId, $staffId, [$timeSlot]);
        if (!$valid) {
            return [false, $message, null];
        }

        // 检查是否已存在相同项
        $exists = self::where('user_id', $userId)
            ->where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', $timeSlot)
            ->find();

        if ($exists) {
            return [false, '该服务已在购物车中', $exists->id];
        }

        // 检查档期是否可用
        if (!Schedule::isAvailable($staffId, $date, $timeSlot)) {
            return [false, '该档期不可预约', null];
        }

        $tempLockCreated = false;
        if ($packageId > 0) {
            $tempLock = PackageBooking::createTempLock($packageId, $staffId, $date, $timeSlot, $userId);
            if (!$tempLock) {
                $availability = PackageBooking::checkAvailability($packageId, $date, $staffId, $timeSlot);
                $message = $availability['available'] ?? false
                    ? '套餐锁定失败，请稍后重试'
                    : ($availability['message'] ?? '套餐锁定失败');
                return [false, $message, null];
            }
            $tempLockCreated = true;
        }

        // 获取价格
        $price = self::calculateItemPrice($staffId, $packageId, $date, $timeSlot);

        try {
            $cart = self::create([
                'user_id' => $userId,
                'staff_id' => $staffId,
                'package_id' => $packageId,
                'schedule_date' => $date,
                'time_slot' => $timeSlot,
                'price' => $price,
                'quantity' => 1,
                'remark' => $remark,
                'is_selected' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return [true, '添加成功', $cart->id];
        } catch (\Exception $e) {
            if ($tempLockCreated) {
                PackageBooking::releaseBySelection($userId, $packageId, $staffId, $date, $timeSlot);
            }
            return [false, '添加失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 批量添加到购物车（多场次）
     * @param int $userId
     * @param int $staffId
     * @param string $date
     * @param array $timeSlots
     * @param int $packageId
     * @param string $remark
     * @return array [bool $success, string $message, array $cartIds]
     */
    public static function addToCartMultiple(int $userId, int $staffId, string $date, array $timeSlots, int $packageId, string $remark = ''): array
    {
        $timeSlots = array_values(array_unique(array_map('intval', $timeSlots)));
        if (empty($timeSlots)) {
            return [false, '请选择时间段', []];
        }

        if ($packageId <= 0) {
            return [false, '请选择套餐', []];
        }

        [$valid, $message] = ServicePackage::validateTimeSlots($packageId, $staffId, $timeSlots);
        if (!$valid) {
            return [false, $message, []];
        }

        foreach ($timeSlots as $timeSlot) {
            if ($timeSlot < 0 || $timeSlot > 3) {
                return [false, '时间段参数错误', []];
            }
            $exists = self::where('user_id', $userId)
                ->where('staff_id', $staffId)
                ->where('schedule_date', $date)
                ->where('time_slot', $timeSlot)
                ->find();
            if ($exists) {
                return [false, '该服务已在购物车中', []];
            }

            if (!Schedule::isAvailable($staffId, $date, $timeSlot)) {
                return [false, '该档期不可预约', []];
            }
        }

        $lockedSelections = [];
        if ($packageId > 0) {
            foreach ($timeSlots as $timeSlot) {
                $tempLock = PackageBooking::createTempLock($packageId, $staffId, $date, (int)$timeSlot, $userId);
                if (!$tempLock) {
                    foreach ($lockedSelections as $locked) {
                        PackageBooking::releaseBySelection($userId, $locked[0], $locked[1], $locked[2], $locked[3]);
                    }
                    $availability = PackageBooking::checkAvailability($packageId, $date, $staffId, (int)$timeSlot);
                    $message = $availability['available'] ?? false
                        ? '套餐锁定失败，请稍后重试'
                        : ($availability['message'] ?? '套餐锁定失败');
                    return [false, $message, []];
                }
                $lockedSelections[] = [$packageId, $staffId, $date, (int)$timeSlot];
            }
        }

        Db::startTrans();
        $cartIds = [];
        try {
            foreach ($timeSlots as $timeSlot) {
                $price = self::calculateItemPrice($staffId, $packageId, $date, $timeSlot);

                $cart = self::create([
                    'user_id' => $userId,
                    'staff_id' => $staffId,
                    'package_id' => $packageId,
                    'schedule_date' => $date,
                    'time_slot' => $timeSlot,
                    'price' => $price,
                    'quantity' => 1,
                    'remark' => $remark,
                    'is_selected' => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
                $cartIds[] = $cart->id;
            }
            Db::commit();
            return [true, '添加成功', $cartIds];
        } catch (\Exception $e) {
            Db::rollback();
            foreach ($lockedSelections as $locked) {
                PackageBooking::releaseBySelection($userId, $locked[0], $locked[1], $locked[2], $locked[3]);
            }
            return [false, '添加失败：' . $e->getMessage(), []];
        }
    }

    /**
     * @notes 计算单项价格
     * @param int $staffId
     * @param int $packageId
     * @param string $date
     * @return float
     */
    public static function calculateItemPrice(int $staffId, int $packageId, string $date, int $timeSlot = 0): float
    {
        if ($packageId <= 0) {
            return 0.00;
        }

        $staffPackage = StaffPackage::where('staff_id', $staffId)
            ->where('package_id', $packageId)
            ->where('status', 1)
            ->find();

        if ($staffPackage) {
            $customSlotPrice = $staffPackage->getCustomSlotPriceByTimeSlot($timeSlot);
            if ($customSlotPrice !== null) {
                return $customSlotPrice;
            }
            if ($staffPackage->custom_price !== null && $staffPackage->custom_price !== '') {
                return (float)$staffPackage->custom_price;
            }
        }

        $package = ServicePackage::find($packageId);
        if ($package) {
            $slotPrice = $package->getSlotPriceByTimeSlot($timeSlot);
            if ($slotPrice !== null) {
                return $slotPrice;
            }
            return (float)$package->price;
        }

        return 0.00;
    }

    /**
     * @notes 更新购物车项
     * @param int $cartId
     * @param int $userId
     * @param array $data
     * @return array [bool $success, string $message]
     */
    public static function updateCartItem(int $cartId, int $userId, array $data): array
    {
        $cart = self::where('id', $cartId)
            ->where('user_id', $userId)
            ->find();

        if (!$cart) {
            return [false, '购物车项不存在'];
        }

        $changed = false;
        $newDate = (string)$cart->schedule_date;
        $newTimeSlot = (int)$cart->time_slot;
        $newLockCreated = false;

        // 如果修改了日期或时间段，需要重新检查档期
        if (isset($data['schedule_date']) || isset($data['time_slot'])) {
            $newDate = $data['schedule_date'] ?? $cart->schedule_date;
            $newTimeSlot = $data['time_slot'] ?? $cart->time_slot;

            $changed = $newDate !== $cart->schedule_date || (int)$newTimeSlot !== (int)$cart->time_slot;
            if ($changed) {
                $exists = self::where('user_id', $userId)
                    ->where('staff_id', $cart->staff_id)
                    ->where('schedule_date', $newDate)
                    ->where('time_slot', $newTimeSlot)
                    ->where('id', '<>', $cart->id)
                    ->find();
                if ($exists) {
                    return [false, '该服务已在购物车中'];
                }

                if ($cart->package_id > 0) {
                    [$valid, $message] = ServicePackage::validateTimeSlots(
                        (int)$cart->package_id,
                        (int)$cart->staff_id,
                        [(int)$newTimeSlot]
                    );
                    if (!$valid) {
                        return [false, $message];
                    }
                }
            }

            if (!Schedule::isAvailable($cart->staff_id, $newDate, $newTimeSlot)) {
                return [false, '该档期不可预约'];
            }

            // 重新计算价格
            $data['price'] = self::calculateItemPrice($cart->staff_id, $cart->package_id, $newDate, $newTimeSlot);
            if ($changed && $cart->package_id > 0) {
                $tempLock = PackageBooking::createTempLock(
                    (int)$cart->package_id,
                    (int)$cart->staff_id,
                    (string)$newDate,
                    (int)$newTimeSlot,
                    $userId
                );
                if (!$tempLock) {
                    $availability = PackageBooking::checkAvailability(
                        (int)$cart->package_id,
                        (string)$newDate,
                        (int)$cart->staff_id,
                        (int)$newTimeSlot
                    );
                    $message = $availability['available'] ?? false
                        ? '套餐锁定失败，请稍后重试'
                        : ($availability['message'] ?? '套餐锁定失败');
                    return [false, $message];
                }
                $newLockCreated = true;
            }
        }

        $data['update_time'] = time();
        try {
            $cart->save($data);
        } catch (\Exception $e) {
            if ($newLockCreated) {
                PackageBooking::releaseBySelection(
                    $userId,
                    (int)$cart->package_id,
                    (int)$cart->staff_id,
                    (string)$newDate,
                    (int)$newTimeSlot
                );
            }
            return [false, '更新失败：' . $e->getMessage()];
        }

        if ($changed && $cart->package_id > 0) {
            PackageBooking::releaseBySelection(
                $userId,
                (int)$cart->package_id,
                (int)$cart->staff_id,
                (string)$cart->schedule_date,
                (int)$cart->time_slot
            );
        }

        return [true, '更新成功'];
    }

    /**
     * @notes 删除购物车项
     * @param int $cartId
     * @param int $userId
     * @return array [bool $success, string $message]
     */
    public static function removeFromCart(int $cartId, int $userId): array
    {
        $cart = self::where('id', $cartId)
            ->where('user_id', $userId)
            ->find();

        if (!$cart) {
            return [false, '购物车项不存在'];
        }

        if ($cart->package_id > 0) {
            PackageBooking::releaseBySelection(
                $userId,
                (int)$cart->package_id,
                (int)$cart->staff_id,
                (string)$cart->schedule_date,
                (int)$cart->time_slot
            );
        }

        $result = $cart->delete();

        return $result ? [true, '删除成功'] : [false, '删除失败'];
    }

    /**
     * @notes 批量删除购物车项
     * @param array $cartIds
     * @param int $userId
     * @return int 删除数量
     */
    public static function batchRemove(array $cartIds, int $userId): int
    {
        $items = self::whereIn('id', $cartIds)
            ->where('user_id', $userId)
            ->select();

        foreach ($items as $item) {
            if ($item->package_id > 0) {
                PackageBooking::releaseBySelection(
                    $userId,
                    (int)$item->package_id,
                    (int)$item->staff_id,
                    (string)$item->schedule_date,
                    (int)$item->time_slot
                );
            }
        }

        return self::whereIn('id', $cartIds)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * @notes 切换选中状态
     * @param int $cartId
     * @param int $userId
     * @return bool
     */
    public static function toggleSelect(int $cartId, int $userId): bool
    {
        $cart = self::where('id', $cartId)
            ->where('user_id', $userId)
            ->find();

        if (!$cart) {
            return false;
        }

        $cart->is_selected = $cart->is_selected ? 0 : 1;
        $cart->update_time = time();
        return $cart->save();
    }

    /**
     * @notes 全选/取消全选
     * @param int $userId
     * @param bool $selected
     * @return int
     */
    public static function selectAll(int $userId, bool $selected = true): int
    {
        return self::where('user_id', $userId)->update([
            'is_selected' => $selected ? 1 : 0,
            'update_time' => time(),
        ]);
    }

    /**
     * @notes 获取用户购物车列表
     * @param int $userId
     * @param bool $selectedOnly 是否只返回选中的
     * @return array
     */
    public static function getUserCart(int $userId, bool $selectedOnly = false): array
    {
        $query = self::where('user_id', $userId);

        if ($selectedOnly) {
            $query->where('is_selected', 1);
        }

        $items = $query->with(['staff' => function ($q) {
                $q->field('id, name, avatar, category_id, price');
            }, 'package' => function ($q) {
                $q->field('id, name, price');
            }])
            ->order('create_time', 'desc')
            ->select()
            ->toArray();

        // 检查每项的档期是否仍然可用
        foreach ($items as &$item) {
            [$available, $reason] = Schedule::checkAvailabilityWithReason(
                (int)$item['staff_id'],
                (string)$item['schedule_date'],
                (int)$item['time_slot']
            );
            $item['is_available'] = $available;
            $item['unavailable_reason'] = $available ? '' : $reason;
        }

        return $items;
    }

    /**
     * @notes 计算购物车总价
     * @param int $userId
     * @param bool $selectedOnly 是否只计算选中的
     * @return array [float $totalPrice, int $totalCount, array $conflicts]
     */
    public static function calculateTotal(int $userId, bool $selectedOnly = true): array
    {
        $items = self::getUserCart($userId, $selectedOnly);
        
        $totalPrice = 0;
        $totalCount = 0;
        $conflicts = [];

        foreach ($items as $item) {
            if (!$item['is_available']) {
                $timeSlotDesc = $item['time_slot_desc'] ?? (new self())->getTimeSlotDescAttr('', [
                    'time_slot' => $item['time_slot'] ?? 0,
                ]);
                $conflicts[] = [
                    'cart_id' => $item['id'],
                    'staff_name' => $item['staff']['name'] ?? '未知',
                    'date' => $item['schedule_date'],
                    'time_slot' => $timeSlotDesc,
                ];
                continue;
            }
            $totalPrice += $item['price'] * $item['quantity'];
            $totalCount++;
        }

        return [
            round($totalPrice, 2),
            $totalCount,
            $conflicts,
        ];
    }

    /**
     * @notes 检查购物车内是否有冲突（同一人员同一日期）
     * @param int $userId
     * @return array 冲突项
     */
    public static function checkConflicts(int $userId): array
    {
        $items = self::where('user_id', $userId)
            ->where('is_selected', 1)
            ->select()
            ->toArray();

        $conflicts = [];
        $checkedPairs = []; // 记录已检查的配对，避免重复

        foreach ($items as $index => $item) {
            foreach ($items as $otherIndex => $other) {
                // 跳过相同项和不同人员或不同日期
                if ($item['id'] == $other['id'] || 
                    $item['staff_id'] != $other['staff_id'] || 
                    $item['schedule_date'] != $other['schedule_date']) {
                    continue;
                }

                // 生成唯一配对键，避免重复检测
                $pairKey = min($item['id'], $other['id']) . '_' . max($item['id'], $other['id']);
                if (isset($checkedPairs[$pairKey])) {
                    continue;
                }
                $checkedPairs[$pairKey] = true;

                $hasConflict = false;
                $conflictMessage = '';

                // 检测冲突类型
                if ($item['time_slot'] == 0 || $other['time_slot'] == 0) {
                    // 全天预约与其他时间段冲突
                    $hasConflict = true;
                    $conflictMessage = '全天预约与其他时间段冲突';
                } elseif ($item['time_slot'] == $other['time_slot']) {
                    // 同一时间段的重复预约
                    $hasConflict = true;
                    $slotNames = [0 => '全天', 1 => '早礼', 2 => '午宴', 3 => '晚宴'];
                    $conflictMessage = '同一人员在' . $item['schedule_date'] . '的' . ($slotNames[$item['time_slot']] ?? '未知') . '时段重复预约';
                }

                if ($hasConflict) {
                    $conflicts[] = [
                        'type' => 'time_conflict',
                        'cart_ids' => [$item['id'], $other['id']],
                        'staff_id' => $item['staff_id'],
                        'date' => $item['schedule_date'],
                        'time_slots' => [$item['time_slot'], $other['time_slot']],
                        'message' => $conflictMessage,
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * @notes 生成分享码
     * @param int $cartId
     * @param int $userId
     * @return string|null
     */
    public static function generateShareCode(int $cartId, int $userId): ?string
    {
        $cart = self::where('id', $cartId)
            ->where('user_id', $userId)
            ->find();

        if (!$cart) {
            return null;
        }

        $shareCode = md5($cartId . $userId . time() . mt_rand(1000, 9999));
        $shareCode = substr($shareCode, 0, 16);

        $cart->share_code = $shareCode;
        $cart->update_time = time();
        $cart->save();

        return $shareCode;
    }

    /**
     * @notes 通过分享码获取购物车项
     * @param string $shareCode
     * @return array|null
     */
    public static function getByShareCode(string $shareCode): ?array
    {
        $cart = self::where('share_code', $shareCode)
            ->with(['staff', 'package'])
            ->find();

        return $cart ? $cart->toArray() : null;
    }

    /**
     * @notes 清空用户购物车
     * @param int $userId
     * @return int
     */
    public static function clearCart(int $userId): int
    {
        $items = self::where('user_id', $userId)->select();
        foreach ($items as $item) {
            if ($item->package_id > 0) {
                PackageBooking::releaseBySelection(
                    $userId,
                    (int)$item->package_id,
                    (int)$item->staff_id,
                    (string)$item->schedule_date,
                    (int)$item->time_slot
                );
            }
        }

        return self::where('user_id', $userId)->delete();
    }

    /**
     * @notes 获取购物车数量
     * @param int $userId
     * @return int
     */
    public static function getCartCount(int $userId): int
    {
        return self::where('user_id', $userId)->count();
    }
}
