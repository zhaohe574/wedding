<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\schedule;

use app\common\model\BaseModel;
use app\common\model\staff\Staff;
use app\common\service\RedisLockService;
use think\facade\Cache;

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
    const TIME_SLOT_MORNING = 1;    // 上午
    const TIME_SLOT_AFTERNOON = 2;  // 下午
    const TIME_SLOT_EVENING = 3;    // 晚上

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
     * @param $value
     * @param $data
     * @return string
     */
    public function getTimeSlotDescAttr($value, $data)
    {
        $map = [
            self::TIME_SLOT_ALL => '全天',
            self::TIME_SLOT_MORNING => '上午',
            self::TIME_SLOT_AFTERNOON => '下午',
            self::TIME_SLOT_EVENING => '晚上',
        ];
        return $map[$data['time_slot']] ?? '未知';
    }

    /**
     * @notes 状态描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusDescAttr($value, $data)
    {
        $map = [
            self::STATUS_UNAVAILABLE => '不可用',
            self::STATUS_AVAILABLE => '可预约',
            self::STATUS_BOOKED => '已预约',
            self::STATUS_LOCKED => '已锁定',
            self::STATUS_RESERVED => '内部预留',
        ];
        return $map[$data['status']] ?? '未知';
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
        $schedule = self::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', $timeSlot)
            ->find();

        // 没有档期记录，检查规则
        if (!$schedule) {
            return self::checkRuleAvailable($staffId, $date);
        }

        // 检查锁定是否过期
        if ($schedule->status == self::STATUS_LOCKED && $schedule->lock_expire_time > 0) {
            if ($schedule->lock_expire_time < time()) {
                // 锁定已过期，自动释放
                self::releaseLock($schedule->id);
                return true;
            }
        }

        return $schedule->status == self::STATUS_AVAILABLE;
    }

    /**
     * @notes 检查规则是否允许预约
     * @param int $staffId
     * @param string $date
     * @return bool
     */
    protected static function checkRuleAvailable(int $staffId, string $date): bool
    {
        // 获取规则（优先个人规则，其次全局规则）
        $rule = ScheduleRule::where('staff_id', $staffId)
            ->where('is_enabled', 1)
            ->find();
        
        if (!$rule) {
            $rule = ScheduleRule::where('staff_id', 0)
                ->where('is_enabled', 1)
                ->find();
        }

        if (!$rule) {
            return true; // 无规则，默认可预约
        }

        // 检查提前预约天数
        $daysDiff = (strtotime($date) - strtotime(date('Y-m-d'))) / 86400;
        if ($daysDiff < $rule->advance_days) {
            return false;
        }

        // 检查休息日
        if ($rule->rest_days) {
            $weekDay = date('w', strtotime($date));
            $restDays = explode(',', $rule->rest_days);
            if (in_array($weekDay, $restDays)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @notes 锁定档期（乐观锁）
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $userId
     * @param int $lockType
     * @param int $lockDuration 锁定时长（秒）
     * @return array [bool $success, string $message]
     */
    public static function lockSchedule(int $staffId, string $date, int $timeSlot, int $userId, int $lockType = self::LOCK_TYPE_NORMAL, int $lockDuration = 900): array
    {
        $schedule = self::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', $timeSlot)
            ->find();

        if ($schedule) {
            // 检查状态
            if ($schedule->status != self::STATUS_AVAILABLE) {
                return [false, '该档期已被预约或锁定'];
            }

            // 乐观锁更新
            $result = self::where('id', $schedule->id)
                ->where('version', $schedule->version)
                ->update([
                    'status' => self::STATUS_LOCKED,
                    'lock_type' => $lockType,
                    'lock_user_id' => $userId,
                    'lock_expire_time' => time() + $lockDuration,
                    'version' => $schedule->version + 1,
                    'update_time' => time(),
                ]);

            if ($result) {
                return [true, '锁定成功'];
            } else {
                return [false, '档期已被其他用户抢占'];
            }
        } else {
            // 创建新档期记录并锁定
            try {
                self::create([
                    'staff_id' => $staffId,
                    'schedule_date' => $date,
                    'time_slot' => $timeSlot,
                    'status' => self::STATUS_LOCKED,
                    'lock_type' => $lockType,
                    'lock_user_id' => $userId,
                    'lock_expire_time' => time() + $lockDuration,
                    'version' => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
                return [true, '锁定成功'];
            } catch (\Exception $e) {
                return [false, '锁定失败：' . $e->getMessage()];
            }
        }
    }

    /**
     * @notes 释放锁定
     * @param int $scheduleId
     * @return bool
     */
    public static function releaseLock(int $scheduleId): bool
    {
        return self::where('id', $scheduleId)->update([
            'status' => self::STATUS_AVAILABLE,
            'lock_type' => self::LOCK_TYPE_NORMAL,
            'lock_user_id' => 0,
            'lock_expire_time' => 0,
            'update_time' => time(),
        ]) > 0;
    }

    /**
     * @notes 确认预约（锁定转预约）
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $orderId
     * @param int $userId
     * @return array [bool $success, string $message]
     */
    public static function confirmBooking(int $staffId, string $date, int $timeSlot, int $orderId, int $userId): array
    {
        $schedule = self::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', $timeSlot)
            ->find();

        if (!$schedule) {
            return [false, '档期不存在'];
        }

        // 检查是否是该用户锁定的
        if ($schedule->status == self::STATUS_LOCKED && $schedule->lock_user_id != $userId) {
            return [false, '该档期已被其他用户锁定'];
        }

        // 乐观锁更新
        $result = self::where('id', $schedule->id)
            ->where('version', $schedule->version)
            ->update([
                'status' => self::STATUS_BOOKED,
                'order_id' => $orderId,
                'lock_type' => self::LOCK_TYPE_NORMAL,
                'lock_user_id' => 0,
                'lock_expire_time' => 0,
                'version' => $schedule->version + 1,
                'update_time' => time(),
            ]);

        if ($result) {
            return [true, '预约成功'];
        } else {
            return [false, '预约失败，请重试'];
        }
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
            ->select()
            ->toArray();

        // 转换为日期索引的数组
        $result = [];
        foreach ($schedules as $schedule) {
            $date = $schedule['schedule_date'];
            if (!isset($result[$date])) {
                $result[$date] = [];
            }
            $result[$date][$schedule['time_slot']] = $schedule;
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
            ['value' => self::TIME_SLOT_MORNING, 'label' => '上午'],
            ['value' => self::TIME_SLOT_AFTERNOON, 'label' => '下午'],
            ['value' => self::TIME_SLOT_EVENING, 'label' => '晚上'],
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
     * @notes 使用Redis分布式锁安全锁定档期
     * @param int $staffId
     * @param string $date
     * @param int $timeSlot
     * @param int $userId
     * @param int $lockType
     * @param int $lockDuration
     * @return array [bool $success, string $message]
     */
    public static function lockScheduleWithRedis(int $staffId, string $date, int $timeSlot, int $userId, int $lockType = self::LOCK_TYPE_NORMAL, int $lockDuration = 900): array
    {
        return RedisLockService::lockScheduleWithRedis(
            $staffId,
            $date,
            $timeSlot,
            $userId,
            function () use ($staffId, $date, $timeSlot, $userId, $lockType, $lockDuration) {
                // 在获取分布式锁后执行实际的档期锁定逻辑
                return self::lockSchedule($staffId, $date, $timeSlot, $userId, $lockType, $lockDuration);
            }
        );
    }

    /**
     * @notes 批量锁定档期（带Redis分布式锁）
     * @param array $scheduleList [[staffId, date, timeSlot], ...]
     * @param int $userId
     * @param int $lockType
     * @param int $lockDuration
     * @return array [bool $success, string $message, array $lockedSchedules]
     */
    public static function batchLockWithRedis(array $scheduleList, int $userId, int $lockType = self::LOCK_TYPE_NORMAL, int $lockDuration = 900): array
    {
        return RedisLockService::batchLockSchedules(
            $scheduleList,
            $userId,
            function () use ($scheduleList, $userId, $lockType, $lockDuration) {
                $lockedSchedules = [];
                $failed = false;
                $failMessage = '';

                foreach ($scheduleList as $schedule) {
                    [$success, $message] = self::lockSchedule(
                        $schedule[0],
                        $schedule[1],
                        $schedule[2] ?? 0,
                        $userId,
                        $lockType,
                        $lockDuration
                    );

                    if ($success) {
                        $lockedSchedules[] = $schedule;
                    } else {
                        $failed = true;
                        $failMessage = $message;
                        break;
                    }
                }

                // 如果有失败，回滚已锁定的档期
                if ($failed) {
                    foreach ($lockedSchedules as $locked) {
                        $existingSchedule = self::where('staff_id', $locked[0])
                            ->where('schedule_date', $locked[1])
                            ->where('time_slot', $locked[2] ?? 0)
                            ->find();
                        if ($existingSchedule) {
                            self::releaseLock($existingSchedule->id);
                        }
                    }
                    return [false, $failMessage, []];
                }

                return [true, '批量锁定成功', $lockedSchedules];
            }
        );
    }
}
