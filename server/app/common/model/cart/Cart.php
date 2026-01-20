<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 购物车模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\cart;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use app\common\model\service\ServicePackage;
use app\common\model\schedule\Schedule;
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
            1 => '上午',
            2 => '下午',
            3 => '晚上',
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

        // 获取价格
        $price = self::calculateItemPrice($staffId, $packageId, $date);

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
            return [false, '添加失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 计算单项价格
     * @param int $staffId
     * @param int $packageId
     * @param string $date
     * @return float
     */
    public static function calculateItemPrice(int $staffId, int $packageId, string $date): float
    {
        // 优先使用档期特价
        $schedule = Schedule::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->find();
        
        if ($schedule && $schedule->price > 0) {
            return (float)$schedule->price;
        }

        // 使用套餐价格
        if ($packageId > 0) {
            $package = ServicePackage::find($packageId);
            if ($package) {
                return (float)$package->price;
            }
        }

        // 使用工作人员基础价格
        $staff = Staff::find($staffId);
        if ($staff) {
            return (float)$staff->price;
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

        // 如果修改了日期或时间段，需要重新检查档期
        if (isset($data['schedule_date']) || isset($data['time_slot'])) {
            $newDate = $data['schedule_date'] ?? $cart->schedule_date;
            $newTimeSlot = $data['time_slot'] ?? $cart->time_slot;

            if (!Schedule::isAvailable($cart->staff_id, $newDate, $newTimeSlot)) {
                return [false, '该档期不可预约'];
            }

            // 重新计算价格
            $data['price'] = self::calculateItemPrice($cart->staff_id, $cart->package_id, $newDate);
        }

        $data['update_time'] = time();
        $cart->save($data);

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
        $result = self::where('id', $cartId)
            ->where('user_id', $userId)
            ->delete();

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
            $item['is_available'] = Schedule::isAvailable(
                $item['staff_id'],
                $item['schedule_date'],
                $item['time_slot']
            );
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
                $conflicts[] = [
                    'cart_id' => $item['id'],
                    'staff_name' => $item['staff']['name'] ?? '未知',
                    'date' => $item['schedule_date'],
                    'time_slot' => $item['time_slot_desc'],
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
        $checked = [];

        foreach ($items as $item) {
            $key = $item['staff_id'] . '_' . $item['schedule_date'];
            
            // 全天与其他时间段冲突
            if ($item['time_slot'] == 0) {
                foreach ($items as $other) {
                    if ($other['id'] != $item['id'] && 
                        $other['staff_id'] == $item['staff_id'] && 
                        $other['schedule_date'] == $item['schedule_date']) {
                        $conflicts[] = [
                            'type' => 'time_conflict',
                            'cart_ids' => [$item['id'], $other['id']],
                            'message' => '全天预约与其他时间段冲突',
                        ];
                    }
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
