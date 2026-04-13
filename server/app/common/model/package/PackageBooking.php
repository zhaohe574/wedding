<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\package;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\schedule\Waitlist;
use app\common\model\service\ServicePackage;
use app\common\model\staff\Staff;
use think\facade\Db;

/**
 * 套餐预订记录模型
 * 口径：按 人员 + 日期 + 套餐 唯一锁定，time_slot 全量归一为 0。
 */
class PackageBooking extends BaseModel
{
    protected $name = 'package_booking';

    // 预订状态常量
    const STATUS_RELEASED = 0;    // 已释放
    const STATUS_TEMP_LOCK = 1;   // 临时锁定
    const STATUS_CONFIRMED = 2;   // 已确认

    // 临时锁定有效期（秒）
    const LOCK_DURATION = 900;

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
     * @param int $packageId
     * @param string $date
     * @param int $staffId
     * @param int $timeSlot
     * @return array
     */
    public static function checkAvailability(int $packageId, string $date, int $staffId = 0, int $timeSlot = 0): array
    {
        self::clearExpiredLocks();

        $booking = self::where('package_id', $packageId)
            ->where('booking_date', $date)
            ->where('staff_id', $staffId)
            ->where('time_slot', 0)
            ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED])
            ->find();

        if ($booking) {
            $statusDesc = (int)$booking->status === self::STATUS_CONFIRMED ? '已被预订' : '正在被他人选购中';
            return [
                'available' => false,
                'message' => "该套餐在 {$date} {$statusDesc}",
                'booking' => $booking->toArray(),
            ];
        }

        return [
            'available' => true,
            'message' => '可预订',
            'booking' => null,
        ];
    }

    /**
     * @notes 创建临时锁定
     * @param int $packageId
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $userId
     * @param string $startTime
     * @param string $endTime
     * @return self|null
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
        Db::startTrans();
        try {
            self::clearExpiredLocks();

            $existingBooking = self::where('package_id', $packageId)
                ->where('booking_date', $date)
                ->where('staff_id', $staffId)
                ->where('time_slot', 0)
                ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED])
                ->lock(true)
                ->find();

            if ($existingBooking) {
                if ((int)$existingBooking->status === self::STATUS_TEMP_LOCK && (int)$existingBooking->user_id === $userId) {
                    $existingBooking->lock_expire_time = time() + self::LOCK_DURATION;
                    $existingBooking->update_time = time();
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
                ->where('time_slot', 0)
                ->where('status', self::STATUS_RELEASED)
                ->lock(true)
                ->find();

            if ($releasedBooking) {
                $releasedBooking->save([
                    'status' => self::STATUS_TEMP_LOCK,
                    'time_slot' => 0,
                    'lock_expire_time' => time() + self::LOCK_DURATION,
                    'user_id' => $userId,
                    'order_id' => null,
                    'order_item_id' => null,
                    'start_time' => $startTime ?: null,
                    'end_time' => $endTime ?: null,
                    'version' => (int)$releasedBooking->version + 1,
                    'update_time' => time(),
                ]);
                Db::commit();
                return $releasedBooking;
            }

            $booking = new self();
            $booking->package_id = $packageId;
            $booking->staff_id = $staffId;
            $booking->booking_date = $date;
            $booking->time_slot = 0;
            $booking->start_time = $startTime ?: null;
            $booking->end_time = $endTime ?: null;
            $booking->user_id = $userId;
            $booking->status = self::STATUS_TEMP_LOCK;
            $booking->lock_expire_time = time() + self::LOCK_DURATION;
            $booking->version = 1;
            $booking->create_time = time();
            $booking->update_time = time();
            $booking->save();

            Db::commit();
            return $booking;
        } catch (\Throwable $e) {
            Db::rollback();
            return null;
        }
    }

    /**
     * @notes 确认预订
     * @param int $orderId
     * @param int $orderItemId
     * @return bool
     */
    public function confirmBooking(int $orderId, int $orderItemId = 0): bool
    {
        if ((int)$this->status !== self::STATUS_TEMP_LOCK) {
            return false;
        }

        Db::startTrans();
        try {
            $result = self::where('id', $this->id)
                ->where('version', $this->version)
                ->where('status', self::STATUS_TEMP_LOCK)
                ->update([
                    'status' => self::STATUS_CONFIRMED,
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                    'time_slot' => 0,
                    'lock_expire_time' => null,
                    'version' => (int)$this->version + 1,
                    'update_time' => time(),
                ]);

            if (!$result) {
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
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
                    'version' => (int)$this->version + 1,
                    'update_time' => time(),
                ]);

            if (!$result) {
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * @notes 根据订单 ID 释放预订
     * @param int $orderId
     * @return int
     */
    public static function releaseByOrderId(int $orderId): int
    {
        $bookings = self::where('order_id', $orderId)
            ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED])
            ->field('staff_id, booking_date, time_slot, status')
            ->select()
            ->toArray();

        $released = self::where('order_id', $orderId)
            ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED])
            ->update([
                'status' => self::STATUS_RELEASED,
                'lock_expire_time' => null,
                'update_time' => time(),
            ]);

        if ($released > 0) {
            self::notifyWaitlistsForReleasedBookings($bookings);
        }

        return $released;
    }

    /**
     * @notes 档期释放后通知候补用户
     * @param array $bookings
     * @return void
     */
    private static function notifyWaitlistsForReleasedBookings(array $bookings): void
    {
        $releaseKeys = [];
        foreach ($bookings as $booking) {
            if ((int) ($booking['status'] ?? self::STATUS_RELEASED) !== self::STATUS_CONFIRMED) {
                continue;
            }

            $staffId = (int) ($booking['staff_id'] ?? 0);
            $bookingDate = (string) ($booking['booking_date'] ?? '');
            if ($staffId <= 0 || $bookingDate === '') {
                continue;
            }

            $releaseKeys[$staffId . '|' . $bookingDate] = [
                'staff_id' => $staffId,
                'booking_date' => $bookingDate,
                'time_slot' => 0,
            ];
        }

        foreach ($releaseKeys as $release) {
            Waitlist::notifyWaitlistUsers(
                (int) $release['staff_id'],
                (string) $release['booking_date'],
                (int) $release['time_slot']
            );
        }
    }

    /**
     * @notes 确认用户选择
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
        return self::upsertConfirmedBooking($packageId, $staffId, $date, $userId, $orderId, $orderItemId);
    }

    /**
     * @notes 根据用户 ID 释放临时锁定
     * @param int $userId
     * @return int
     */
    public static function releaseByUserId(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_TEMP_LOCK)
            ->update([
                'status' => self::STATUS_RELEASED,
                'lock_expire_time' => null,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 释放指定选择的临时锁定
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
            ->where('time_slot', 0)
            ->where('status', self::STATUS_TEMP_LOCK)
            ->update([
                'status' => self::STATUS_RELEASED,
                'lock_expire_time' => null,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 清理过期的临时锁定
     * @return int
     */
    public static function clearExpiredLocks(): int
    {
        return self::where('status', self::STATUS_TEMP_LOCK)
            ->whereNotNull('lock_expire_time')
            ->where('lock_expire_time', '<', time())
            ->update([
                'status' => self::STATUS_RELEASED,
                'time_slot' => 0,
                'update_time' => time(),
            ]);
    }

    /**
     * @notes 获取套餐在日期范围内的预订情况
     * @param int $packageId
     * @param string $startDate
     * @param string $endDate
     * @param int $staffId
     * @param int|null $timeSlot
     * @return array
     */
    public static function getBookingsByDateRange(int $packageId, string $startDate, string $endDate, int $staffId = 0, ?int $timeSlot = null): array
    {
        self::clearExpiredLocks();

        $query = self::where('package_id', $packageId)
            ->where('booking_date', '>=', $startDate)
            ->where('booking_date', '<=', $endDate)
            ->where('time_slot', 0)
            ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED]);

        if ($staffId > 0) {
            $query->where('staff_id', $staffId);
        }

        return $query->column('status', 'booking_date');
    }

    /**
     * @notes 批量检查多个套餐的可用性
     * @param array $packageIds
     * @param string $date
     * @param int $staffId
     * @param int $timeSlot
     * @return array
     */
    public static function batchCheckAvailability(array $packageIds, string $date, int $staffId = 0, int $timeSlot = 0): array
    {
        self::clearExpiredLocks();

        $bookedPackages = self::whereIn('package_id', $packageIds)
            ->where('booking_date', $date)
            ->where('staff_id', $staffId)
            ->where('time_slot', 0)
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
     * @param int $userId
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
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getStatusDescAttr($value, $data): string
    {
        $statusMap = [
            self::STATUS_RELEASED => '已释放',
            self::STATUS_TEMP_LOCK => '临时锁定',
            self::STATUS_CONFIRMED => '已确认',
        ];
        return $statusMap[(int)($data['status'] ?? -1)] ?? '未知';
    }

    /**
     * @notes 创建或更新已确认锁单
     * @param int $packageId
     * @param int $staffId
     * @param string $date
     * @param int $userId
     * @param int $orderId
     * @param int $orderItemId
     * @return bool
     */
    protected static function upsertConfirmedBooking(
        int $packageId,
        int $staffId,
        string $date,
        int $userId,
        int $orderId,
        int $orderItemId = 0
    ): bool {
        self::clearExpiredLocks();

        Db::startTrans();
        try {
            $currentBooking = null;
            if ($orderItemId > 0) {
                $currentBooking = self::where('order_item_id', $orderItemId)->lock(true)->find();
            }

            $ownedTempLockQuery = self::where('user_id', $userId)
                ->where('package_id', $packageId)
                ->where('staff_id', $staffId)
                ->where('booking_date', $date)
                ->where('time_slot', 0)
                ->where('status', self::STATUS_TEMP_LOCK);

            if ($currentBooking) {
                $ownedTempLockQuery->where('id', '<>', (int)$currentBooking->id);
            }

            $ownedTempLock = $ownedTempLockQuery->lock(true)->find();

            $conflictQuery = self::where('package_id', $packageId)
                ->where('staff_id', $staffId)
                ->where('booking_date', $date)
                ->where('time_slot', 0)
                ->whereIn('status', [self::STATUS_TEMP_LOCK, self::STATUS_CONFIRMED]);

            if ($currentBooking) {
                $conflictQuery->where('id', '<>', (int)$currentBooking->id);
            }

            if ($ownedTempLock) {
                $conflictQuery->where('id', '<>', (int)$ownedTempLock->id);
            }

            $conflict = $conflictQuery->lock(true)->find();
            if ($conflict) {
                Db::rollback();
                return false;
            }

            if ($currentBooking) {
                if ($ownedTempLock) {
                    $ownedTempLock->save([
                        'status' => self::STATUS_RELEASED,
                        'order_id' => null,
                        'order_item_id' => null,
                        'lock_expire_time' => null,
                        'version' => (int)$ownedTempLock->version + 1,
                        'update_time' => time(),
                    ]);
                }

                $currentBooking->save([
                    'package_id' => $packageId,
                    'staff_id' => $staffId,
                    'booking_date' => $date,
                    'time_slot' => 0,
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                    'user_id' => $userId,
                    'status' => self::STATUS_CONFIRMED,
                    'lock_expire_time' => null,
                    'version' => (int)$currentBooking->version + 1,
                    'update_time' => time(),
                ]);
                Db::commit();
                return true;
            }

            if ($ownedTempLock) {
                $ownedTempLock->save([
                    'package_id' => $packageId,
                    'staff_id' => $staffId,
                    'booking_date' => $date,
                    'time_slot' => 0,
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                    'user_id' => $userId,
                    'status' => self::STATUS_CONFIRMED,
                    'lock_expire_time' => null,
                    'version' => (int)$ownedTempLock->version + 1,
                    'update_time' => time(),
                ]);
                Db::commit();
                return true;
            }

            $releasedBooking = self::where('package_id', $packageId)
                ->where('staff_id', $staffId)
                ->where('booking_date', $date)
                ->where('time_slot', 0)
                ->where('status', self::STATUS_RELEASED)
                ->lock(true)
                ->find();

            if ($releasedBooking) {
                $releasedBooking->save([
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                    'user_id' => $userId,
                    'status' => self::STATUS_CONFIRMED,
                    'lock_expire_time' => null,
                    'version' => (int)$releasedBooking->version + 1,
                    'update_time' => time(),
                ]);
                Db::commit();
                return true;
            }

            self::create([
                'package_id' => $packageId,
                'staff_id' => $staffId,
                'booking_date' => $date,
                'time_slot' => 0,
                'order_id' => $orderId,
                'order_item_id' => $orderItemId,
                'user_id' => $userId,
                'status' => self::STATUS_CONFIRMED,
                'lock_expire_time' => null,
                'version' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            return false;
        }
    }
}
