<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\schedule;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use app\common\service\RedisLockService;

/**
 * 档期模型
 * Class Schedule
 * @package app\common\model\schedule
 */
class Schedule extends BaseModel
{
    protected $name = 'schedule';

    // 时间段
    const TIME_SLOT_ALL = 0;        // 全天
    const TIME_SLOT_MORNING = 1;    // 早礼（已停用，仅兼容旧数据）
    const TIME_SLOT_AFTERNOON = 2;  // 午宴（已停用，仅兼容旧数据）
    const TIME_SLOT_EVENING = 3;    // 晚宴（已停用，仅兼容旧数据）

    // 状态
    const STATUS_UNAVAILABLE = 0;   // 不可用
    const STATUS_AVAILABLE = 1;     // 可预约
    const STATUS_BOOKED = 2;        // 已预约
    const STATUS_LOCKED = 3;        // 已锁定
    const STATUS_RESERVED = 4;      // 内部预留

    // 锁定类型
    const LOCK_TYPE_NORMAL = 0;     // 正常
    const LOCK_TYPE_VIP = 1;        // VIP锁定
    const LOCK_TYPE_INTERNAL = 2;   // 内部预留

    /**
     * @notes 关联工作人员
     * @return \think\model\relation\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    /**
     * @notes 时间段描述获取器
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getTimeSlotDescAttr($value, $data): string
    {
        return '全天';
    }

    /**
     * @notes 状态描述获取器
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getStatusDescAttr($value, $data): string
    {
        $map = [
            self::STATUS_UNAVAILABLE => '不可用',
            self::STATUS_AVAILABLE => '可预约',
            self::STATUS_BOOKED => '已预约',
            self::STATUS_LOCKED => '已锁定',
            self::STATUS_RESERVED => '内部预留',
        ];
        return $map[(int)($data['status'] ?? self::STATUS_AVAILABLE)] ?? '未知';
    }

    /**
     * @notes 检查档期是否可预约
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @return bool
     */
    public static function isAvailable(int $staffId, string $date, int $timeSlot = 0): bool
    {
        [$available] = self::checkAvailabilityForUserWithReason($staffId, $date, 0, $timeSlot);
        return $available;
    }

    /**
     * @notes 兼容旧调用的可用性检查
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @return bool
     */
    public static function checkAvailable(int $staffId, string $date, int $timeSlot = 0): bool
    {
        return self::isAvailable($staffId, $date, $timeSlot);
    }

    /**
     * @notes 检查档期是否可预约并返回原因
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @return array
     */
    public static function checkAvailabilityWithReason(int $staffId, string $date, int $timeSlot = 0): array
    {
        return self::checkAvailabilityForUserWithReason($staffId, $date, 0, $timeSlot);
    }

    /**
     * @notes 检查档期是否可预约并返回原因（允许识别当前用户持有的有效锁）
     * @param int $staffId
     * @param string $date
     * @param int $userId
     * @param int $timeSlot
     * @return array
     */
    public static function checkAvailabilityForUserWithReason(int $staffId, string $date, int $userId = 0, int $timeSlot = 0): array
    {
        [$ruleAllowed, $ruleReason] = ScheduleRule::checkBookingRule($staffId, $date);
        if (!$ruleAllowed) {
            return [false, $ruleReason];
        }

        return self::isScheduleRecordAvailableWithReason(
            self::findDateSchedule($staffId, $date),
            $userId
        );
    }

    /**
     * @notes 使用 Redis 分布式锁安全锁定档期
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $userId
     * @param int $lockType
     * @param int $lockDuration
     * @return array
     */
    public static function lockScheduleWithRedis(
        int $staffId,
        string $date,
        int $timeSlot,
        int $userId,
        int $lockType = self::LOCK_TYPE_NORMAL,
        int $lockDuration = 900,
        int $lockAcquireRetryCount = 3,
        int $lockAcquireRetryDelay = 100
    ): array
    {
        [$ruleAllowed, $ruleReason] = ScheduleRule::checkBookingRule($staffId, $date);
        if (!$ruleAllowed) {
            return [false, $ruleReason ?: '该日期不可预约'];
        }

        $schedule = self::findDateSchedule($staffId, $date);
        $renewResult = self::tryRenewOwnedLockFast($schedule, $userId, $lockType, $lockDuration);
        if ($renewResult !== null) {
            return $renewResult;
        }

        return RedisLockService::lockScheduleWithRedis(
            $staffId,
            $date,
            self::TIME_SLOT_ALL,
            $userId,
            function () use ($staffId, $date, $userId, $lockType, $lockDuration) {
                return self::lockSchedule($staffId, $date, self::TIME_SLOT_ALL, $userId, $lockType, $lockDuration);
            },
            5,
            $lockAcquireRetryCount,
            $lockAcquireRetryDelay
        );
    }

    /**
     * @notes 批量锁定档期（带 Redis 分布式锁）
     * @param array $scheduleList
     * @param int $userId
     * @param int $lockType
     * @param int $lockDuration
     * @return array
     */
    public static function batchLockWithRedis(array $scheduleList, int $userId, int $lockType = self::LOCK_TYPE_NORMAL, int $lockDuration = 900): array
    {
        $normalizedSchedules = [];
        foreach ($scheduleList as $schedule) {
            $normalizedSchedules[] = [
                (int)($schedule[0] ?? 0),
                (string)($schedule[1] ?? ''),
                self::TIME_SLOT_ALL,
            ];
        }

        return RedisLockService::batchLockSchedules(
            $normalizedSchedules,
            $userId,
            function () use ($normalizedSchedules, $userId, $lockType, $lockDuration) {
                $lockedSchedules = [];
                foreach ($normalizedSchedules as $schedule) {
                    [$success, $message] = self::lockSchedule(
                        (int)$schedule[0],
                        (string)$schedule[1],
                        self::TIME_SLOT_ALL,
                        $userId,
                        $lockType,
                        $lockDuration
                    );
                    if (!$success) {
                        foreach ($lockedSchedules as $locked) {
                            $existingSchedule = self::findDateSchedule((int)$locked[0], (string)$locked[1]);
                            if ($existingSchedule) {
                                self::releaseLock((int)$existingSchedule->id);
                            }
                        }
                        return [false, $message, []];
                    }
                    $lockedSchedules[] = $schedule;
                }

                return [true, '批量锁定成功', $lockedSchedules];
            }
        );
    }

    /**
     * @notes 锁定档期
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $userId
     * @param int $lockType
     * @param int $lockDuration
     * @return array
     */
    public static function lockSchedule(int $staffId, string $date, int $timeSlot, int $userId, int $lockType = self::LOCK_TYPE_NORMAL, int $lockDuration = 900): array
    {
        [$ruleAllowed, $ruleReason] = ScheduleRule::checkBookingRule($staffId, $date);
        if (!$ruleAllowed) {
            return [false, $ruleReason ?: '该日期不可预约'];
        }

        $schedule = self::findDateSchedule($staffId, $date);
        if ($schedule) {
            if ($schedule->status == self::STATUS_LOCKED && (int)$schedule->lock_expire_time > 0 && (int)$schedule->lock_expire_time < time()) {
                self::releaseLock((int)$schedule->id);
                $schedule = self::findDateSchedule($staffId, $date);
            }

            if ($schedule && !in_array((int)$schedule->status, [self::STATUS_AVAILABLE, self::STATUS_LOCKED, self::STATUS_BOOKED], true)) {
                return [false, self::buildUnavailableReason((int)$schedule->status)];
            }

            if ($schedule && (int)$schedule->status === self::STATUS_LOCKED) {
                if ((int)$schedule->lock_user_id === $userId) {
                    $schedule->save([
                        'lock_type' => $lockType,
                        'lock_user_id' => $userId,
                        'lock_expire_time' => time() + $lockDuration,
                        'update_time' => time(),
                    ]);
                    return [true, '锁定成功', (int)$schedule->id, 'schedule_id' => (int)$schedule->id];
                }

                return [false, '该日期已被锁定'];
            }

            $result = self::where('id', (int)$schedule->id)
                ->where('version', (int)$schedule->version)
                ->update([
                    'time_slot' => self::TIME_SLOT_ALL,
                    'status' => self::STATUS_LOCKED,
                    'lock_type' => $lockType,
                    'lock_user_id' => $userId,
                    'lock_expire_time' => time() + $lockDuration,
                    'version' => (int)$schedule->version + 1,
                    'update_time' => time(),
                ]);

            if (!$result) {
                return [false, '档期已被其他用户抢占'];
            }

            return [true, '锁定成功', (int)$schedule->id, 'schedule_id' => (int)$schedule->id];
        }

        try {
            $schedule = self::create([
                'staff_id' => $staffId,
                'schedule_date' => $date,
                'time_slot' => self::TIME_SLOT_ALL,
                'status' => self::STATUS_LOCKED,
                'lock_type' => $lockType,
                'lock_user_id' => $userId,
                'lock_expire_time' => time() + $lockDuration,
                'version' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            return [true, '锁定成功', (int)$schedule->id, 'schedule_id' => (int)$schedule->id];
        } catch (\Throwable $e) {
            return [false, '锁定失败：' . $e->getMessage()];
        }
    }

    /**
     * @notes 临时锁定档期
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $userId
     * @param int $lockDuration
     * @return array
     */
    public static function temporaryLock(int $staffId, string $date, int $timeSlot, int $userId, int $lockDuration = 900): array
    {
        [$success, $message, $scheduleId] = self::lockSchedule(
            $staffId,
            $date,
            self::TIME_SLOT_ALL,
            $userId,
            self::LOCK_TYPE_NORMAL,
            $lockDuration
        );

        return [
            'success' => $success,
            'message' => $message,
            'schedule_id' => (int)($scheduleId ?? 0),
        ];
    }

    /**
     * @notes 确认预约
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $orderId
     * @param int $userId
     * @return array
     */
    public static function confirmBooking(int $staffId, string $date, int $timeSlot, int $orderId, int $userId): array
    {
        $schedule = self::findDateSchedule($staffId, $date);
        if (!$schedule) {
            try {
                $schedule = self::create([
                    'staff_id' => $staffId,
                    'schedule_date' => $date,
                    'time_slot' => self::TIME_SLOT_ALL,
                    'status' => self::STATUS_BOOKED,
                    'order_id' => $orderId,
                    'lock_type' => self::LOCK_TYPE_NORMAL,
                    'lock_user_id' => 0,
                    'lock_expire_time' => 0,
                    'version' => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
                return [true, '预约成功', (int)$schedule->id, 'schedule_id' => (int)$schedule->id];
            } catch (\Throwable $e) {
                return [false, '预约失败：' . $e->getMessage()];
            }
        }

        if ($schedule->status == self::STATUS_LOCKED && (int)$schedule->lock_expire_time > 0 && (int)$schedule->lock_expire_time < time()) {
            self::releaseLock((int)$schedule->id);
            $schedule = self::findDateSchedule($staffId, $date);
        }

        if ($schedule && (int)$schedule->status === self::STATUS_BOOKED && (int)$schedule->order_id === $orderId) {
            return [true, '预约成功', (int)$schedule->id, 'schedule_id' => (int)$schedule->id];
        }

        if ($schedule && (int)$schedule->status === self::STATUS_LOCKED && (int)$schedule->lock_user_id > 0 && (int)$schedule->lock_user_id !== $userId) {
            return [false, '该日期已被其他用户锁定'];
        }

        if ($schedule && !in_array((int)$schedule->status, [self::STATUS_AVAILABLE, self::STATUS_LOCKED, self::STATUS_BOOKED], true)) {
            return [false, self::buildUnavailableReason((int)$schedule->status)];
        }

        $result = self::where('id', (int)$schedule->id)
            ->where('version', (int)$schedule->version)
            ->update([
                'time_slot' => self::TIME_SLOT_ALL,
                'status' => self::STATUS_BOOKED,
                'order_id' => $orderId,
                'lock_type' => self::LOCK_TYPE_NORMAL,
                'lock_user_id' => 0,
                'lock_expire_time' => 0,
                'version' => (int)$schedule->version + 1,
                'update_time' => time(),
            ]);

        if (!$result) {
            return [false, '预约失败，请重试'];
        }

        return [true, '预约成功', (int)$schedule->id, 'schedule_id' => (int)$schedule->id];
    }

    /**
     * @notes 释放锁定/预约
     * @param int $scheduleId
     * @return bool
     */
    public static function releaseLock(int $scheduleId): bool
    {
        $schedule = self::find($scheduleId);
        if (!$schedule) {
            return false;
        }

        return self::where('id', $scheduleId)->update([
            'time_slot' => self::TIME_SLOT_ALL,
            'status' => self::STATUS_AVAILABLE,
            'order_id' => 0,
            'lock_type' => self::LOCK_TYPE_NORMAL,
            'lock_user_id' => 0,
            'lock_expire_time' => 0,
            'version' => (int)$schedule->version + 1,
            'update_time' => time(),
        ]) > 0;
    }

    /**
     * @notes 获取工作人员月度档期
     * @param int $staffId
     * @param int $year
     * @param int $month
     * @return array
     */
    public static function getMonthSchedule(int $staffId, int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $schedules = self::where('staff_id', $staffId)
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->where('time_slot', self::TIME_SLOT_ALL)
            ->select()
            ->toArray();

        $result = [];
        foreach ($schedules as $schedule) {
            $result[$schedule['schedule_date']] = [
                self::TIME_SLOT_ALL => $schedule,
            ];
        }

        return $result;
    }

    /**
     * @notes 获取时间段选项
     * @return array
     */
    public static function getTimeSlotOptions(): array
    {
        return [
            ['value' => self::TIME_SLOT_ALL, 'label' => '全天'],
        ];
    }

    /**
     * @notes 获取状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_UNAVAILABLE, 'label' => '不可用'],
            ['value' => self::STATUS_AVAILABLE, 'label' => '可预约'],
            ['value' => self::STATUS_BOOKED, 'label' => '已预约'],
            ['value' => self::STATUS_LOCKED, 'label' => '已锁定'],
            ['value' => self::STATUS_RESERVED, 'label' => '内部预留'],
        ];
    }

    /**
     * @notes 判断单条档期记录是否可用
     * @param Schedule|null $schedule
     * @return bool
     */
    protected static function isScheduleRecordAvailable(?self $schedule): bool
    {
        if (!$schedule) {
            return true;
        }

        if ((int)$schedule->status === self::STATUS_LOCKED && (int)$schedule->lock_expire_time > 0 && (int)$schedule->lock_expire_time < time()) {
            self::releaseLock((int)$schedule->id);
            return true;
        }

        return in_array((int)$schedule->status, [self::STATUS_AVAILABLE, self::STATUS_BOOKED], true);
    }

    /**
     * @notes 判断单条档期记录是否可用并返回原因
     * @param Schedule|null $schedule
     * @return array
     */
    protected static function isScheduleRecordAvailableWithReason(?self $schedule, int $userId = 0): array
    {
        if (!$schedule) {
            return [true, ''];
        }

        if ((int)$schedule->status === self::STATUS_LOCKED && (int)$schedule->lock_expire_time > 0 && (int)$schedule->lock_expire_time < time()) {
            self::releaseLock((int)$schedule->id);
            return [true, ''];
        }

        if ((int)$schedule->status === self::STATUS_AVAILABLE) {
            return [true, ''];
        }

        if ((int)$schedule->status === self::STATUS_BOOKED) {
            return [true, ''];
        }

        if (
            (int)$schedule->status === self::STATUS_LOCKED
            && $userId > 0
            && (int)$schedule->lock_user_id === $userId
            && (int)$schedule->lock_expire_time > time()
        ) {
            return [true, ''];
        }

        return [false, self::buildUnavailableReason((int)$schedule->status)];
    }

    /**
     * @notes 生成不可用原因
     * @param int $status
     * @return string
     */
    protected static function buildUnavailableReason(int $status): string
    {
        $map = [
            self::STATUS_UNAVAILABLE => '该日期已设置为不可用',
            self::STATUS_BOOKED => '该日期已被预约',
            self::STATUS_LOCKED => '该日期已被锁定',
            self::STATUS_RESERVED => '该日期为内部预留',
        ];

        return $map[$status] ?? '该日期不可用';
    }

    /**
     * @notes 检查规则是否允许预约
     * @param int $staffId
     * @param string $date
     * @return bool
     */
    protected static function checkRuleAvailable(int $staffId, string $date): bool
    {
        [$allowed] = ScheduleRule::checkBookingRule($staffId, $date);
        return $allowed;
    }

    /**
     * @notes 查询指定人员指定日期的全天档期
     * @param int $staffId
     * @param string $date
     * @return Schedule|null
     */
    protected static function findDateSchedule(int $staffId, string $date): ?self
    {
        return self::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', self::TIME_SLOT_ALL)
            ->find();
    }

    /**
     * @notes 当前用户已持有有效锁时，直接续期，绕过分布式锁等待
     * @param Schedule|null $schedule
     * @param int $userId
     * @param int $lockType
     * @param int $lockDuration
     * @return array|null
     */
    protected static function tryRenewOwnedLockFast(
        ?self $schedule,
        int $userId,
        int $lockType,
        int $lockDuration
    ): ?array {
        if (
            !$schedule ||
            (int)$schedule->status !== self::STATUS_LOCKED ||
            (int)$schedule->lock_user_id !== $userId ||
            (int)$schedule->lock_expire_time <= time()
        ) {
            return null;
        }

        $updated = self::where('id', (int)$schedule->id)
            ->where('status', self::STATUS_LOCKED)
            ->where('lock_user_id', $userId)
            ->where('lock_expire_time', '>', time())
            ->update([
                'lock_type' => $lockType,
                'lock_user_id' => $userId,
                'lock_expire_time' => time() + $lockDuration,
                'update_time' => time(),
            ]);

        if (!$updated) {
            return null;
        }

        return [true, '锁定成功', (int)$schedule->id, 'schedule_id' => (int)$schedule->id];
    }
}
