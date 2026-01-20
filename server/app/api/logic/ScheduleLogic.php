<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端档期业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\schedule\Schedule;
use app\common\model\schedule\ScheduleRule;
use app\common\model\schedule\CalendarEvent;
use app\common\model\schedule\Waitlist;
use app\common\model\staff\Staff;

/**
 * 小程序端档期业务逻辑
 * Class ScheduleLogic
 * @package app\api\logic
 */
class ScheduleLogic extends BaseLogic
{
    /**
     * @notes 获取工作人员档期
     * @param array $params
     * @return array
     */
    public static function getStaffSchedule(array $params): array
    {
        $staffId = $params['staff_id'];
        $year = $params['year'] ?? date('Y');
        $month = $params['month'] ?? date('m');

        // 获取档期数据
        $schedules = Schedule::getMonthSchedule($staffId, (int)$year, (int)$month);

        // 获取规则
        $rule = ScheduleRule::getStaffRule($staffId);

        // 获取黄历数据
        $calendarEvents = CalendarEvent::getMonthCalendar((int)$year, (int)$month);

        // 生成每日状态
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $currentDate = $startDate;

        $days = [];
        while ($currentDate <= $endDate) {
            $daySchedules = $schedules[$currentDate] ?? [];
            $calendarEvent = $calendarEvents[$currentDate] ?? null;

            // 判断是否可预约
            $isAvailable = self::checkDateAvailable($staffId, $currentDate, $rule);

            $days[$currentDate] = [
                'date' => $currentDate,
                'schedules' => $daySchedules,
                'is_available' => $isAvailable,
                'is_lucky_day' => $calendarEvent['is_lucky_day'] ?? 0,
                'is_holiday' => $calendarEvent['is_holiday'] ?? 0,
                'holiday_name' => $calendarEvent['holiday_name'] ?? '',
                'lunar_date' => $calendarEvent['lunar_date'] ?? '',
            ];

            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        // 获取工作人员信息
        $staff = Staff::field('id, name, avatar, price')->find($staffId);

        return [
            'staff' => $staff ? $staff->toArray() : null,
            'year' => $year,
            'month' => $month,
            'rule' => $rule,
            'days' => $days,
        ];
    }

    /**
     * @notes 检查日期是否可预约
     * @param int $staffId
     * @param string $date
     * @param array|null $rule
     * @return bool
     */
    protected static function checkDateAvailable(int $staffId, string $date, ?array $rule): bool
    {
        // 检查是否过期
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            return false;
        }

        // 检查规则
        if ($rule) {
            // 提前预约天数
            $daysDiff = (strtotime($date) - strtotime(date('Y-m-d'))) / 86400;
            if ($daysDiff < ($rule['advance_days'] ?? 0)) {
                return false;
            }

            // 休息日
            if (!empty($rule['rest_days'])) {
                $weekDay = date('w', strtotime($date));
                $restDays = is_array($rule['rest_days']) ? $rule['rest_days'] : explode(',', $rule['rest_days']);
                if (in_array($weekDay, $restDays)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @notes 获取月度日历（含吉日）
     * @param array $params
     * @return array
     */
    public static function getMonthCalendar(array $params): array
    {
        $year = $params['year'] ?? date('Y');
        $month = $params['month'] ?? date('m');

        $events = CalendarEvent::getMonthCalendar((int)$year, (int)$month);

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $currentDate = $startDate;

        $days = [];
        while ($currentDate <= $endDate) {
            $event = $events[$currentDate] ?? null;
            $days[] = [
                'date' => $currentDate,
                'week' => date('w', strtotime($currentDate)),
                'is_today' => $currentDate == date('Y-m-d'),
                'is_past' => strtotime($currentDate) < strtotime(date('Y-m-d')),
                'is_lucky_day' => $event['is_lucky_day'] ?? 0,
                'is_holiday' => $event['is_holiday'] ?? 0,
                'holiday_name' => $event['holiday_name'] ?? '',
                'lunar_date' => $event['lunar_date'] ?? '',
                'lucky_events' => $event['lucky_events'] ?? '',
                'congestion_level' => $event['congestion_level'] ?? 0,
            ];
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        return [
            'year' => $year,
            'month' => $month,
            'days' => $days,
        ];
    }

    /**
     * @notes 获取吉日列表
     * @param array $params
     * @return array
     */
    public static function getLuckyDays(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-d');
        $endDate = $params['end_date'] ?? date('Y-m-d', strtotime('+90 days'));
        $marriageOnly = !empty($params['marriage_only']);

        return CalendarEvent::getLuckyDaysInRange($startDate, $endDate, $marriageOnly);
    }

    /**
     * @notes 检查档期是否可预约
     * @param array $params
     * @return array
     */
    public static function checkAvailable(array $params): array
    {
        $staffId = $params['staff_id'];
        $date = $params['date'];
        $timeSlot = $params['time_slot'] ?? 0;

        $isAvailable = Schedule::isAvailable($staffId, $date, $timeSlot);

        // 获取当前状态
        $schedule = Schedule::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', $timeSlot)
            ->find();

        return [
            'is_available' => $isAvailable,
            'status' => $schedule ? $schedule->status : 1,
            'status_desc' => $schedule ? $schedule->status_desc : '可预约',
            'price' => $schedule && $schedule->price > 0 ? $schedule->price : null,
        ];
    }

    /**
     * @notes 锁定档期（使用Redis分布式锁防超卖）
     * @param array $params
     * @return array
     */
    public static function lockSchedule(array $params): array
    {
        // 使用Redis分布式锁保证并发安全
        [$success, $message] = Schedule::lockScheduleWithRedis(
            $params['staff_id'],
            $params['date'],
            $params['time_slot'] ?? 0,
            $params['user_id'],
            Schedule::LOCK_TYPE_NORMAL,
            $params['lock_duration'] ?? 900
        );

        return ['success' => $success, 'message' => $message];
    }

    /**
     * @notes 批量锁定档期（使用Redis分布式锁防超卖）
     * @param array $params
     * @return array
     */
    public static function batchLockSchedule(array $params): array
    {
        $scheduleList = $params['schedules']; // [[staffId, date, timeSlot], ...]
        $userId = $params['user_id'];
        $lockDuration = $params['lock_duration'] ?? 900;

        // 使用Redis分布式锁批量锁定
        [$success, $message, $lockedSchedules] = Schedule::batchLockWithRedis(
            $scheduleList,
            $userId,
            Schedule::LOCK_TYPE_NORMAL,
            $lockDuration
        );

        return [
            'success' => $success,
            'message' => $message,
            'locked_schedules' => $lockedSchedules
        ];
    }

    /**
     * @notes 释放锁定
     * @param array $params
     * @return array
     */
    public static function releaseLock(array $params): array
    {
        $schedule = Schedule::where('staff_id', $params['staff_id'])
            ->where('schedule_date', $params['date'])
            ->where('time_slot', $params['time_slot'] ?? 0)
            ->find();

        if (!$schedule) {
            return ['success' => false, 'message' => '档期不存在'];
        }

        // 只能释放自己锁定的
        if ($schedule->lock_user_id != $params['user_id']) {
            return ['success' => false, 'message' => '无权释放此档期'];
        }

        $result = Schedule::releaseLock($schedule->id);
        return ['success' => $result, 'message' => $result ? '释放成功' : '释放失败'];
    }

    /**
     * @notes 加入候补
     * @param array $params
     * @return array
     */
    public static function joinWaitlist(array $params): array
    {
        [$success, $message, $waitlistId] = Waitlist::addToWaitlist(
            $params['user_id'],
            $params['staff_id'],
            $params['date'],
            $params['time_slot'] ?? 0,
            $params['package_id'] ?? 0,
            $params['remark'] ?? ''
        );

        return ['success' => $success, 'message' => $message, 'waitlist_id' => $waitlistId];
    }

    /**
     * @notes 获取用户候补列表
     * @param int $userId
     * @param array $params
     * @return array
     */
    public static function getUserWaitlist(int $userId, array $params): array
    {
        $status = isset($params['status']) ? (int)$params['status'] : -1;
        return Waitlist::getUserWaitlist($userId, $status);
    }

    /**
     * @notes 取消候补
     * @param int $waitlistId
     * @param int $userId
     * @return array
     */
    public static function cancelWaitlist(int $waitlistId, int $userId): array
    {
        return Waitlist::cancelWaitlist($waitlistId, $userId);
    }
}
