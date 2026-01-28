<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

namespace app\common\model\package;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\service\ServicePackage;
use app\common\model\staff\Staff;
use think\facade\Db;

/**
 * 套餐预订记录模型
 * 用于实现单日唯一预订限制和临时锁定机制
 * Class PackageBooking
 * @package app\common\model\package
 */
class PackageBooking extends BaseModel
{
    protected $name = 'package_booking';

    // 预订状态常量
    const STATUS_RELEASED = 0;    // 已释放
    const STATUS_TEMP_LOCK = 1;   // 临时锁定
    const STATUS_CONFIRMED = 2;   // 已确认

    // 临时锁定有效期（秒）
    const LOCK_DURATION = 900; // 15分钟

    /**
     * @notes 关联套餐
     * @return \think\model\relation\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo(ServicePackage::class, 'package_id', 'id');
    }

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 关联订单
     * @return \think\model\relation\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @notes 检查套餐在指定日期是否可用
     * @param int $packageId 套餐ID
     * @param string $date 预订日期 Y-m-d
     * @param int $staffId 服务人员ID
     * @param int $timeSlot 时间段
     * @return array ['available' => bool, 'message' => string, 'booking' => array|null]
     */
    public static function checkAvailability(int $packageId, string $date, int $staffId = 0, int $timeSlot = 0): array
    {
        // 先清理过期的临时锁定
        self::clearExpiredLocks();

        // 查询该套餐在该日期是否有有效预订（临时锁定或已确认）
        $booking = self::where('package_id', $packageId)
            ->where('booking_date', $date)
            ->where('staff_id', $staffId)
            ->where('time_slot', $timeSlot)
            ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED])
            ->find();

        if ($booking) {
            $statusDesc = $booking->status == self::STATUS_CONFIRMED ? '已被预订' : '正在被他人选购中';
            return [
                'available' => false,
                'message' => "该套餐在 {$date} {$statusDesc}",
                'booking' => $booking->toArray()
            ];
        }

        return [
            'available' => true,
            'message' => '可预订',
            'booking' => null
        ];
    }

    /**
     * @notes 创建临时锁定
     * @param int $packageId 套餐ID
     * @param int $staffId 服务人员ID
     * @param string $date 预订日期
     * @param int $timeSlot 时间段
     * @param int $userId 用户ID
     * @param string $startTime 开始时间（可选）
     * @param string $endTime 结束时间（可选）
     * @return self|null 成功返回模型实例，失败返回null
     */
    public static function createTempLock(
        int $packageId,
        int $staffId,
        string $date,
        int $timeSlot,
        int $userId,
        string $startTime = '',
        string $endTime = ''
    ): ?self {
        // 使用数据库事务和悲观锁
        Db::startTrans();
        try {
            // 先清理过期锁定
            self::clearExpiredLocks();

            // 悲观锁检查是否已有有效预订
            $existingBooking = self::where('package_id', $packageId)
                ->where('booking_date', $date)
                ->where('staff_id', $staffId)
                ->where('time_slot', $timeSlot)
                ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED])
                ->lock(true)
                ->find();

            if ($existingBooking) {
                if ($existingBooking->status == self::STATUS_TEMP_LOCK && (int)$existingBooking->user_id === $userId) {
                    $existingBooking->lock_expire_time = time() + self::LOCK_DURATION;
                    $existingBooking->save();
                    Db::commit();
                    return $existingBooking;
                }
                Db::rollback();
                return null;
            }

            $releasedBooking = self::where('package_id', $packageId)
                ->where('booking_date', $date)
                ->where('staff_id', $staffId)
                ->where('time_slot', $timeSlot)
                ->where('status', self::STATUS_RELEASED)
                ->lock(true)
                ->find();

            if ($releasedBooking) {
                $releasedBooking->status = self::STATUS_TEMP_LOCK;
                $releasedBooking->lock_expire_time = time() + self::LOCK_DURATION;
                $releasedBooking->user_id = $userId;
                $releasedBooking->order_id = null;
                $releasedBooking->order_item_id = null;
                $releasedBooking->start_time = $startTime ?: null;
                $releasedBooking->end_time = $endTime ?: null;
                $releasedBooking->version = $releasedBooking->version + 1;
                $releasedBooking->update_time = time();
                $releasedBooking->save();
                Db::commit();
                return $releasedBooking;
            }

            // 检查该用户是否已有该套餐的临时锁定（避免重复锁定）
            $userLock = self::where('package_id', $packageId)
                ->where('booking_date', $date)
                ->where('staff_id', $staffId)
                ->where('time_slot', $timeSlot)
                ->where('user_id', $userId)
                ->where('status', self::STATUS_TEMP_LOCK)
                ->find();

            if ($userLock) {
                // 延长锁定时间
                $userLock->lock_expire_time = time() + self::LOCK_DURATION;
                $userLock->save();
                Db::commit();
                return $userLock;
            }

            // 创建新的临时锁定
            $booking = new self();
            $booking->package_id = $packageId;
            $booking->staff_id = $staffId;
            $booking->booking_date = $date;
            $booking->time_slot = $timeSlot;
            $booking->start_time = $startTime ?: null;
            $booking->end_time = $endTime ?: null;
            $booking->user_id = $userId;
            $booking->status = self::STATUS_TEMP_LOCK;
            $booking->lock_expire_time = time() + self::LOCK_DURATION;
            $booking->version = 1;
            $booking->create_time = time();
            $booking->save();

            Db::commit();
            return $booking;
        } catch (\Exception $e) {
            Db::rollback();
            return null;
        }
    }

    /**
     * @notes 确认预订（临时锁定转为已确认）
     * @param int $orderId 订单ID
     * @param int $orderItemId 订单项ID
     * @return bool
     */
    public function confirmBooking(int $orderId, int $orderItemId = 0): bool
    {
        if ($this->status != self::STATUS_TEMP_LOCK) {
            return false;
        }

        Db::startTrans();
        try {
            // 使用乐观锁更新
            $result = self::where('id', $this->id)
                ->where('version', $this->version)
                ->where('status', self::STATUS_TEMP_LOCK)
                ->update([
                    'status' => self::STATUS_CONFIRMED,
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                    'lock_expire_time' => null,
                    'version' => $this->version + 1,
                    'update_time' => time()
                ]);

            if ($result) {
                Db::commit();
                return true;
            }

            Db::rollback();
            return false;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * @notes 释放预订
     * @return bool
     */
    public function releaseBooking(): bool
    {
        Db::startTrans();
        try {
            $result = self::where('id', $this->id)
                ->where('version', $this->version)
                ->update([
                    'status' => self::STATUS_RELEASED,
                    'lock_expire_time' => null,
                    'version' => $this->version + 1,
                    'update_time' => time()
                ]);

            if ($result) {
                Db::commit();
                return true;
            }

            Db::rollback();
            return false;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * @notes 根据订单ID释放预订
     * @param int $orderId 订单ID
     * @return int 释放的记录数
     */
    public static function releaseByOrderId(int $orderId): int
    {
        return self::where('order_id', $orderId)
            ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED])
            ->update([
                'status' => self::STATUS_RELEASED,
                'lock_expire_time' => null,
                'update_time' => time()
            ]);
    }

    /**
     * @notes 确认指定场次的预订（按用户选择）
     * @param int $userId
     * @param int $packageId
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $orderId
     * @param int $orderItemId
     * @return bool
     */
    public static function confirmSelection(
        int $userId,
        int $packageId,
        int $staffId,
        string $date,
        int $timeSlot,
        int $orderId,
        int $orderItemId = 0
    ): bool {
        self::clearExpiredLocks();

        $booking = self::where('package_id', $packageId)
            ->where('staff_id', $staffId)
            ->where('booking_date', $date)
            ->where('time_slot', $timeSlot)
            ->where('user_id', $userId)
            ->where('status', self::STATUS_TEMP_LOCK)
            ->find();

        if ($booking) {
            return $booking->confirmBooking($orderId, $orderItemId);
        }

        $existing = self::where('package_id', $packageId)
            ->where('staff_id', $staffId)
            ->where('booking_date', $date)
            ->where('time_slot', $timeSlot)
            ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED])
            ->find();

        if ($existing) {
            return false;
        }

        $releasedBooking = self::where('package_id', $packageId)
            ->where('staff_id', $staffId)
            ->where('booking_date', $date)
            ->where('time_slot', $timeSlot)
            ->where('status', self::STATUS_RELEASED)
            ->lock(true)
            ->find();

        if ($releasedBooking) {
            $releasedBooking->status = self::STATUS_CONFIRMED;
            $releasedBooking->order_id = $orderId;
            $releasedBooking->order_item_id = $orderItemId;
            $releasedBooking->user_id = $userId;
            $releasedBooking->lock_expire_time = null;
            $releasedBooking->version = $releasedBooking->version + 1;
            $releasedBooking->update_time = time();
            $releasedBooking->save();
            return true;
        }

        self::create([
            'package_id' => $packageId,
            'staff_id' => $staffId,
            'booking_date' => $date,
            'time_slot' => $timeSlot,
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'user_id' => $userId,
            'status' => self::STATUS_CONFIRMED,
            'lock_expire_time' => null,
            'version' => 1,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return true;
    }

    /**
     * @notes 根据用户ID释放临时锁定
     * @param int $userId 用户ID
     * @return int 释放的记录数
     */
    public static function releaseByUserId(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_TEMP_LOCK)
            ->update([
                'status' => self::STATUS_RELEASED,
                'lock_expire_time' => null,
                'update_time' => time()
            ]);
    }

    /**
     * @notes 按场次释放临时锁定
     * @param int $userId
     * @param int $packageId
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @return int
     */
    public static function releaseBySelection(int $userId, int $packageId, int $staffId, string $date, int $timeSlot): int
    {
        return self::where('user_id', $userId)
            ->where('package_id', $packageId)
            ->where('staff_id', $staffId)
            ->where('booking_date', $date)
            ->where('time_slot', $timeSlot)
            ->where('status', self::STATUS_TEMP_LOCK)
            ->update([
                'status' => self::STATUS_RELEASED,
                'lock_expire_time' => null,
                'update_time' => time()
            ]);
    }

    /**
     * @notes 清理过期的临时锁定
     * @return int 清理的记录数
     */
    public static function clearExpiredLocks(): int
    {
        return self::where('status', self::STATUS_TEMP_LOCK)
            ->whereNotNull('lock_expire_time')
            ->where('lock_expire_time', '<', time())
            ->update([
                'status' => self::STATUS_RELEASED,
                'update_time' => time()
            ]);
    }

    /**
     * @notes 获取套餐在日期范围内的预订情况
     * @param int $packageId 套餐ID
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @return array 日期 => 状态 的映射
     */
    public static function getBookingsByDateRange(int $packageId, string $startDate, string $endDate, int $staffId = 0, ?int $timeSlot = null): array
    {
        self::clearExpiredLocks();

        $query = self::where('package_id', $packageId)
            ->where('booking_date', '>=', $startDate)
            ->where('booking_date', '<=', $endDate)
            ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED]);

        if ($staffId > 0) {
            $query->where('staff_id', $staffId);
        }

        if ($timeSlot !== null) {
            $query->where('time_slot', $timeSlot);
        }

        $bookings = $query->column('status', 'booking_date');

        return $bookings;
    }

    /**
     * @notes 批量检查多个套餐的可用性
     * @param array $packageIds 套餐ID数组
     * @param string $date 预订日期
     * @return array package_id => available 的映射
     */
    public static function batchCheckAvailability(array $packageIds, string $date, int $staffId = 0, int $timeSlot = 0): array
    {
        self::clearExpiredLocks();

        $bookedPackages = self::whereIn('package_id', $packageIds)
            ->where('booking_date', $date)
            ->where('staff_id', $staffId)
            ->where('time_slot', $timeSlot)
            ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED])
            ->column('package_id');

        $result = [];
        foreach ($packageIds as $packageId) {
            $result[$packageId] = !in_array($packageId, $bookedPackages);
        }

        return $result;
    }

    /**
     * @notes 获取用户的临时锁定记录
     * @param int $userId 用户ID
     * @return array
     */
    public static function getUserTempLocks(int $userId): array
    {
        self::clearExpiredLocks();

        return self::where('user_id', $userId)
            ->where('status', self::STATUS_TEMP_LOCK)
            ->with(['package'])
            ->select()
            ->toArray();
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data)
    {
        $statusMap = [
            self::STATUS_RELEASED => '已释放',
            self::STATUS_TEMP_LOCK => '临时锁定',
            self::STATUS_CONFIRMED => '已确认',
        ];
        return $statusMap[$data['status']] ?? '未知';
    }
}
