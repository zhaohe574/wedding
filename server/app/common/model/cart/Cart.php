<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 购物车模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\cart;

use app\common\model\BaseModel;
use app\common\model\package\PackageBooking;
use app\common\model\schedule\Schedule;
use app\common\model\service\ServicePackage;
use app\common\model\staff\Staff;
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
     * @notes 添加到购物车
     * @param int $userId
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $packageId
     * @param string $remark
     * @return array
     */
    public static function addToCart(int $userId, int $staffId, string $date, int $timeSlot = 0, int $packageId = 0, string $remark = ''): array
    {
        $timeSlot = self::normalizeTimeSlot($timeSlot);
        if ($packageId <= 0) {
            return [false, '请选择套餐', null];
        }

        [$valid, $message] = ServicePackage::validateTimeSlots($packageId, $staffId, [$timeSlot]);
        if (!$valid) {
            return [false, $message, null];
        }

        $exists = self::where('user_id', $userId)
            ->where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', $timeSlot)
            ->find();
        if ($exists) {
            return [false, '该服务已在购物车中', $exists->id];
        }

        if (!Schedule::isAvailable($staffId, $date, $timeSlot)) {
            return [false, '该档期不可预约', null];
        }

        $tempLock = PackageBooking::createTempLock($packageId, $staffId, $date, $timeSlot, $userId);
        if (!$tempLock) {
            $availability = PackageBooking::checkAvailability($packageId, $date, $staffId, $timeSlot);
            $message = $availability['available'] ?? false
                ? '套餐锁定失败，请稍后重试'
                : ($availability['message'] ?? '套餐锁定失败');
            return [false, $message, null];
        }

        try {
            $cart = self::create([
                'user_id' => $userId,
                'staff_id' => $staffId,
                'package_id' => $packageId,
                'schedule_date' => $date,
                'time_slot' => $timeSlot,
                'price' => self::calculateItemPrice($staffId, $packageId, $date, $timeSlot),
                'quantity' => 1,
                'remark' => $remark,
                'is_selected' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return [true, '添加成功', $cart->id];
        } catch (\Throwable $e) {
            PackageBooking::releaseBySelection($userId, $packageId, $staffId, $date, $timeSlot);
            return [false, '添加失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 兼容旧多场次调用，统一折叠为全天
     * @param int $userId
     * @param int $staffId
     * @param string $date
     * @param array $timeSlots
     * @param int $packageId
     * @param string $remark
     * @return array
     */
    public static function addToCartMultiple(int $userId, int $staffId, string $date, array $timeSlots, int $packageId, string $remark = ''): array
    {
        [$success, $message, $cartId] = self::addToCart($userId, $staffId, $date, 0, $packageId, $remark);
        return [$success, $message, $cartId ? [$cartId] : []];
    }

    /**
     * @notes 计算单项价格
     * @param int $staffId
     * @param int $packageId
     * @param string $date
     * @param int $timeSlot
     * @return float
     */
    public static function calculateItemPrice(int $staffId, int $packageId, string $date, int $timeSlot = 0): float
    {
        return \app\common\service\StaffPriceService::calculateOrderItemPrice($staffId, $packageId, 0);
    }

    /**
     * @notes 更新购物车项
     * @param int $cartId
     * @param int $userId
     * @param array $data
     * @return array
     */
    public static function updateCartItem(int $cartId, int $userId, array $data): array
    {
        $cart = self::where('id', $cartId)
            ->where('user_id', $userId)
            ->find();
        if (!$cart) {
            return [false, '购物车项不存在'];
        }

        $newDate = (string)($data['schedule_date'] ?? $data['date'] ?? $cart->schedule_date);
        $oldDate = (string)$cart->schedule_date;
        $changed = $newDate !== (string)$cart->schedule_date;

        if ($changed) {
            $exists = self::where('user_id', $userId)
                ->where('staff_id', $cart->staff_id)
                ->where('schedule_date', $newDate)
                ->where('time_slot', 0)
                ->where('id', '<>', $cart->id)
                ->find();
            if ($exists) {
                return [false, '该服务已在购物车中'];
            }

            if (!Schedule::isAvailable((int)$cart->staff_id, $newDate, 0)) {
                return [false, '该档期不可预约'];
            }

            $tempLock = PackageBooking::createTempLock(
                (int)$cart->package_id,
                (int)$cart->staff_id,
                $newDate,
                0,
                $userId
            );
            if (!$tempLock) {
                $availability = PackageBooking::checkAvailability(
                    (int)$cart->package_id,
                    $newDate,
                    (int)$cart->staff_id,
                    0
                );
                $message = $availability['available'] ?? false
                    ? '套餐锁定失败，请稍后重试'
                    : ($availability['message'] ?? '套餐锁定失败');
                return [false, $message];
            }
        }

        try {
            $cart->save([
                'schedule_date' => $newDate,
                'time_slot' => 0,
                'price' => self::calculateItemPrice((int)$cart->staff_id, (int)$cart->package_id, $newDate, 0),
                'remark' => $data['remark'] ?? $cart->remark,
                'update_time' => time(),
            ]);
        } catch (\Throwable $e) {
            if ($changed) {
                PackageBooking::releaseBySelection($userId, (int)$cart->package_id, (int)$cart->staff_id, $newDate, 0);
            }
            return [false, '更新失败：' . $e->getMessage()];
        }

        if ($changed) {
            PackageBooking::releaseBySelection(
                $userId,
                (int)$cart->package_id,
                (int)$cart->staff_id,
                $oldDate,
                0
            );
        }

        return [true, '更新成功'];
    }

    /**
     * @notes 删除购物车项
     * @param int $cartId
     * @param int $userId
     * @return array
     */
    public static function removeFromCart(int $cartId, int $userId): array
    {
        $cart = self::where('id', $cartId)
            ->where('user_id', $userId)
            ->find();
        if (!$cart) {
            return [false, '购物车项不存在'];
        }

        if ((int)$cart->package_id > 0) {
            PackageBooking::releaseBySelection($userId, (int)$cart->package_id, (int)$cart->staff_id, (string)$cart->schedule_date, 0);
        }

        return $cart->delete() ? [true, '删除成功'] : [false, '删除失败'];
    }

    /**
     * @notes 批量删除购物车项
     * @param array $cartIds
     * @param int $userId
     * @return int
     */
    public static function batchRemove(array $cartIds, int $userId): int
    {
        Db::startTrans();
        try {
            $items = self::whereIn('id', $cartIds)
                ->where('user_id', $userId)
                ->select();
            foreach ($items as $item) {
                if ((int)$item->package_id > 0) {
                    PackageBooking::releaseBySelection($userId, (int)$item->package_id, (int)$item->staff_id, (string)$item->schedule_date, 0);
                }
            }

            $count = self::whereIn('id', $cartIds)
                ->where('user_id', $userId)
                ->delete();
            Db::commit();
            return $count;
        } catch (\Throwable $e) {
            Db::rollback();
            return 0;
        }
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
        return (bool)$cart->save();
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
     * @param bool $selectedOnly
     * @return array
     */
    public static function getUserCart(int $userId, bool $selectedOnly = false): array
    {
        $query = self::where('user_id', $userId);
        if ($selectedOnly) {
            $query->where('is_selected', 1);
        }

        $items = $query->with([
                'staff' => function ($q) {
                    $q->field('id, name, avatar, category_id');
                },
                'package' => function ($q) {
                    $q->field('id, name, price');
                },
            ])
            ->order('create_time', 'desc')
            ->select()
            ->toArray();

        foreach ($items as &$item) {
            [$available, $reason] = Schedule::checkAvailabilityWithReason(
                (int)$item['staff_id'],
                (string)$item['schedule_date'],
                0
            );
            $item['is_available'] = $available;
            $item['unavailable_reason'] = $available ? '' : $reason;
            unset($item['time_slot']);
        }

        return $items;
    }

    /**
     * @notes 计算购物车总价
     * @param int $userId
     * @param bool $selectedOnly
     * @return array
     */
    public static function calculateTotal(int $userId, bool $selectedOnly = true): array
    {
        $items = self::getUserCart($userId, $selectedOnly);
        $totalPrice = 0;
        $totalCount = 0;
        $conflicts = [];

        foreach ($items as $item) {
            if (!$item['is_available']) {
                $conflicts[] = [
                    'cart_id' => $item['id'],
                    'staff_name' => $item['staff']['name'] ?? '未知',
                    'date' => $item['schedule_date'],
                ];
                continue;
            }
            $totalPrice += (float)$item['price'] * (int)$item['quantity'];
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
     * @return array
     */
    public static function checkConflicts(int $userId): array
    {
        $items = self::where('user_id', $userId)
            ->where('is_selected', 1)
            ->select()
            ->toArray();

        $conflicts = [];
        $checkedPairs = [];
        foreach ($items as $item) {
            foreach ($items as $other) {
                if ($item['id'] == $other['id']
                    || $item['staff_id'] != $other['staff_id']
                    || $item['schedule_date'] != $other['schedule_date']) {
                    continue;
                }

                $pairKey = min($item['id'], $other['id']) . '_' . max($item['id'], $other['id']);
                if (isset($checkedPairs[$pairKey])) {
                    continue;
                }
                $checkedPairs[$pairKey] = true;

                $conflicts[] = [
                    'type' => 'date_conflict',
                    'cart_ids' => [$item['id'], $other['id']],
                    'staff_id' => $item['staff_id'],
                    'date' => $item['schedule_date'],
                    'message' => '同一人员在同一天不能重复预约',
                ];
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

        $shareCode = substr(md5($cartId . $userId . time() . mt_rand(1000, 9999)), 0, 16);
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
        if (!$cart) {
            return null;
        }

        $data = $cart->toArray();
        unset($data['time_slot']);
        return $data;
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
            if ((int)$item->package_id > 0) {
                PackageBooking::releaseBySelection($userId, (int)$item->package_id, (int)$item->staff_id, (string)$item->schedule_date, 0);
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

    /**
     * @notes 统一按全天处理旧 time_slot 输入
     * @param int $timeSlot
     * @return int
     */
    protected static function normalizeTimeSlot(int $timeSlot): int
    {
        return 0;
    }
}
