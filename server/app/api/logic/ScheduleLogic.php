<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端档期业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\schedule\Schedule;
use app\common\model\schedule\ScheduleRule;
use app\common\model\schedule\Waitlist;
use app\common\model\staff\Staff;
use app\common\service\StaffPriceService;

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
        $staffId = (int)$params['staff_id'];
        $year = (int)($params['year'] ?? date('Y'));
        $month = (int)($params['month'] ?? date('m'));

        $schedules = Schedule::getMonthSchedule($staffId, $year, $month);
        $rule = ScheduleRule::getStaffRule($staffId);

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $currentDate = $startDate;

        $days = [];
        while ($currentDate <= $endDate) {
            $daySchedules = $schedules[$currentDate] ?? [];
            $isAvailable = self::checkDateAvailable($staffId, $currentDate, $rule)
                && self::isDayScheduleAvailable($daySchedules);

            $days[$currentDate] = [
                'date' => $currentDate,
                'schedules' => $daySchedules,
                'schedule' => $daySchedules[Schedule::TIME_SLOT_ALL] ?? null,
                'is_available' => $isAvailable,
                'slot_availability' => [
                    Schedule::TIME_SLOT_ALL => $isAvailable,
                ],
                'is_lucky_day' => 0,
                'is_holiday' => 0,
                'holiday_name' => '',
                'lunar_date' => '',
            ];

            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        $staff = Staff::field('id, name, avatar')->find($staffId);
        $staffData = $staff ? $staff->toArray() : null;
        if ($staffData) {
            $displayPrice = StaffPriceService::getDisplayPriceByStaffId((int)$staffData['id']);
            $staffData['price'] = $displayPrice['price'];
            $staffData['has_price'] = $displayPrice['has_price'];
            $staffData['price_text'] = $displayPrice['price_text'];
        }

        return [
            'staff' => $staffData,
            'year' => $year,
            'month' => $month,
            'rule' => $rule,
            'days' => $days,
        ];
    }

    /**
     * @notes 获取月度日历
     * @param array $params
     * @return array
     */
    public static function getMonthCalendar(array $params): array
    {
        $year = (int)($params['year'] ?? date('Y'));
        $month = (int)($params['month'] ?? date('m'));

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $currentDate = $startDate;

        $days = [];
        while ($currentDate <= $endDate) {
            $days[] = [
                'date' => $currentDate,
                'week' => date('w', strtotime($currentDate)),
                'is_today' => $currentDate == date('Y-m-d'),
                'is_past' => strtotime($currentDate) < strtotime(date('Y-m-d')),
                'is_lucky_day' => 0,
                'is_holiday' => 0,
                'holiday_name' => '',
                'lunar_date' => '',
                'lucky_events' => '',
                'congestion_level' => 0,
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
        return [];
    }

    /**
     * @notes 检查档期是否可预约
     * @param array $params
     * @return array
     */
    public static function checkAvailable(array $params): array
    {
        $staffId = (int)$params['staff_id'];
        $date = (string)$params['date'];

        $isAvailable = Schedule::isAvailable($staffId, $date, 0);
        $schedule = Schedule::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', 0)
            ->find();

        return [
            'is_available' => $isAvailable,
            'status' => $schedule ? $schedule->status : Schedule::STATUS_AVAILABLE,
            'status_desc' => $schedule ? $schedule->status_desc : '可预约',
            'price' => $schedule && (float)$schedule->price > 0 ? (float)$schedule->price : null,
        ];
    }

    /**
     * @notes 锁定档期
     * @param array $params
     * @return array
     */
    public static function lockSchedule(array $params): array
    {
        [$success, $message] = Schedule::lockScheduleWithRedis(
            (int)$params['staff_id'],
            (string)$params['date'],
            0,
            (int)$params['user_id'],
            Schedule::LOCK_TYPE_NORMAL,
            (int)($params['lock_duration'] ?? 900)
        );

        return ['success' => $success, 'message' => $message];
    }

    /**
     * @notes 批量锁定档期
     * @param array $params
     * @return array
     */
    public static function batchLockSchedule(array $params): array
    {
        $scheduleList = [];
        foreach (($params['schedules'] ?? []) as $schedule) {
            $scheduleList[] = [
                (int)($schedule[0] ?? 0),
                (string)($schedule[1] ?? ''),
                0,
            ];
        }

        [$success, $message, $lockedSchedules] = Schedule::batchLockWithRedis(
            $scheduleList,
            (int)$params['user_id'],
            Schedule::LOCK_TYPE_NORMAL,
            (int)($params['lock_duration'] ?? 900)
        );

        return [
            'success' => $success,
            'message' => $message,
            'locked_schedules' => $lockedSchedules,
        ];
    }

    /**
     * @notes 释放锁定
     * @param array $params
     * @return array
     */
    public static function releaseLock(array $params): array
    {
        $schedule = Schedule::where('staff_id', (int)$params['staff_id'])
            ->where('schedule_date', (string)$params['date'])
            ->where('time_slot', 0)
            ->find();

        if (!$schedule) {
            return ['success' => false, 'message' => '档期不存在'];
        }

        if ((int)$schedule->lock_user_id !== (int)$params['user_id']) {
            return ['success' => false, 'message' => '无权释放此档期'];
        }

        $result = Schedule::releaseLock((int)$schedule->id);
        return ['success' => $result, 'message' => $result ? '释放成功' : '释放失败'];
    }

    /**
     * @notes 加入候补
     * @param array $params
     * @return array
     */
    public static function joinWaitlist(array $params): array
    {
        $date = (string)($params['date'] ?? '');
        if ($date === '') {
            return ['success' => false, 'message' => '请选择日期', 'waitlist_id' => null];
        }

        $targetDate = strtotime($date);
        $todayDate = strtotime(date('Y-m-d'));
        if ($targetDate === false) {
            return ['success' => false, 'message' => '日期格式错误', 'waitlist_id' => null];
        }
        if ($targetDate <= $todayDate) {
            $message = $targetDate === $todayDate ? '当天不支持候补' : '不能选择过去日期';
            return ['success' => false, 'message' => $message, 'waitlist_id' => null];
        }

        $packageId = (int)($params['package_id'] ?? 0);
        if ($packageId <= 0) {
            return ['success' => false, 'message' => '请选择套餐', 'waitlist_id' => null];
        }

        [$success, $message, $waitlistId] = Waitlist::addToWaitlist(
            (int)$params['user_id'],
            (int)$params['staff_id'],
            $date,
            0,
            $packageId,
            (string)($params['remark'] ?? ''),
            72
        );

        return [
            'success' => $success,
            'message' => $message,
            'waitlist_id' => $waitlistId,
            'waitlist_ids' => $waitlistId ? [$waitlistId] : [],
            'batch_no' => '',
        ];
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

    /**
     * @notes 检查日期是否可预约
     * @param int $staffId
     * @param string $date
     * @param array|null $rule
     * @return bool
     */
    protected static function checkDateAvailable(int $staffId, string $date, ?array $rule): bool
    {
        if (strtotime($date) <= strtotime(date('Y-m-d'))) {
            return false;
        }

        if ($rule && !empty($rule['rest_days'])) {
            $weekDay = date('w', strtotime($date));
            $restDays = is_array($rule['rest_days']) ? $rule['rest_days'] : explode(',', (string)$rule['rest_days']);
            if (in_array($weekDay, $restDays) || in_array((string)$weekDay, $restDays, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @notes 判断某日是否可预约
     * @param array $daySchedules
     * @return bool
     */
    protected static function isDayScheduleAvailable(array $daySchedules): bool
    {
        if (empty($daySchedules[Schedule::TIME_SLOT_ALL])) {
            return true;
        }

        $schedule = $daySchedules[Schedule::TIME_SLOT_ALL];
        if ((int)($schedule['status'] ?? Schedule::STATUS_AVAILABLE) !== Schedule::STATUS_LOCKED) {
            return (int)($schedule['status'] ?? Schedule::STATUS_AVAILABLE) === Schedule::STATUS_AVAILABLE;
        }

        $lockExpireTime = (int)($schedule['lock_expire_time'] ?? 0);
        return $lockExpireTime > 0 && $lockExpireTime < time();
    }
}
