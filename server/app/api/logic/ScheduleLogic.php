<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端档期业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\api\lists\staff\StaffLists;
use app\common\model\schedule\CalendarEvent;
use app\common\model\schedule\Schedule;
use app\common\model\schedule\ScheduleRule;
use app\common\model\schedule\Waitlist;
use app\common\model\staff\Staff;
use app\common\service\StaffPriceService;
use think\facade\Log;

/**
 * 小程序端档期业务逻辑
 * Class ScheduleLogic
 * @package app\api\logic
 */
class ScheduleLogic extends BaseLogic
{
    /** 日历最多向后查多少年（与前端分页范围一致） */
    private const MAX_SELECTABLE_YEARS = 10;

    /** 单次聚合查询最多覆盖多少天，防止被刷成全表扫描 */
    private const OVERVIEW_MAX_DAYS = 62;
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
        $calendarEvents = CalendarEvent::getMonthCalendar($year, $month);
        $currentDate = $startDate;

        $days = [];
        while ($currentDate <= $endDate) {
            $daySchedules = $schedules[$currentDate] ?? [];
            $calendarMeta = self::buildCalendarMeta($calendarEvents[$currentDate] ?? null);
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
                'calendar' => $calendarMeta,
                'is_lucky_day' => $calendarMeta['is_lucky_day'],
                'is_holiday' => $calendarMeta['is_holiday'],
                'holiday_name' => $calendarMeta['holiday_name'],
                'lunar_date' => $calendarMeta['lunar_date'],
                'lucky_events' => $calendarMeta['lucky_events'],
                'unlucky_events' => $calendarMeta['unlucky_events'],
                'congestion_level' => $calendarMeta['congestion_level'],
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
        $calendarEvents = CalendarEvent::getMonthCalendar($year, $month);
        $currentDate = $startDate;

        $days = [];
        while ($currentDate <= $endDate) {
            $calendarMeta = self::buildCalendarMeta($calendarEvents[$currentDate] ?? null);
            $days[] = [
                'date' => $currentDate,
                'week' => date('w', strtotime($currentDate)),
                'is_today' => $currentDate == date('Y-m-d'),
                'is_past' => strtotime($currentDate) < strtotime(date('Y-m-d')),
                'calendar' => $calendarMeta,
                'is_lucky_day' => $calendarMeta['is_lucky_day'],
                'is_holiday' => $calendarMeta['is_holiday'],
                'holiday_name' => $calendarMeta['holiday_name'],
                'lunar_date' => $calendarMeta['lunar_date'],
                'lucky_events' => $calendarMeta['lucky_events'],
                'unlucky_events' => $calendarMeta['unlucky_events'],
                'congestion_level' => $calendarMeta['congestion_level'],
                'remark' => $calendarMeta['remark'],
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
     * @notes 获取档期规则（供前端决定最早可选日期，避免前端写死提前天数）
     *
     * 全局规则缺失或读取异常时回落到 ScheduleRule 的默认值，并标记 source=default，
     * 前端据此仍能正常渲染，只是范围退化为默认值。
     *
     * @return array
     */
    public static function getRuleConfig(): array
    {
        $rule = ScheduleRule::getGlobalRule();
        $today = date('Y-m-d');
        $advanceDays = max(0, (int)($rule['advance_days'] ?? ScheduleRule::DEFAULT_ADVANCE_DAYS));

        return [
            'advance_days' => $advanceDays,
            'today' => $today,
            'min_selectable_date' => date('Y-m-d', strtotime($today . ' +' . $advanceDays . ' days')),
            'max_selectable_date' => date('Y-m-d', strtotime($today . ' +' . self::MAX_SELECTABLE_YEARS . ' years')),
            // db = 读到后台配置；default = 配置缺失，已使用兜底值
            'source' => $rule['source'] ?? 'default',
        ];
    }

    /**
     * @notes 月度档期概览：一次返回区间内每天的可约人数
     *
     * 供查档期页给整月日期铺状态角标。筛选口径复用 StaffLists（类型/标签/关键词/地区），
     * 未选地区时直接返回空 days（没有地区就没有可约口径）。
     *
     * @param array $params
     * @return array
     */
    public static function getAvailabilityOverview(array $params): array
    {
        $startDate = self::normalizeDate($params['start_date'] ?? '');
        $endDate = self::normalizeDate($params['end_date'] ?? '');

        if ($startDate === '' || $endDate === '') {
            return ['start_date' => $startDate, 'end_date' => $endDate, 'days' => []];
        }
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $dates = self::buildDateRange($startDate, $endDate, self::OVERVIEW_MAX_DAYS);
        if (empty($dates)) {
            return ['start_date' => $startDate, 'end_date' => $endDate, 'days' => []];
        }

        // 必须选到区县：没有地区上下文时不猜「全国」，返回空让前端保持无点（未知态）
        $cityCode = trim((string)($params['city_code'] ?? ''));
        $districtCode = trim((string)($params['district_code'] ?? ''));
        if ($cityCode === '' || $districtCode === '') {
            return ['start_date' => $startDate, 'end_date' => $endDate, 'days' => []];
        }

        // StaffLists 从当前请求读取 params，这里不重复传参
        $counts = (new StaffLists())->countsByDate($dates);

        $days = [];
        foreach ($dates as $date) {
            $days[] = [
                'date' => $date,
                'available_count' => (int)($counts[$date] ?? 0),
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $days,
        ];
    }

    /**
     * @notes 规范化日期为 Y-m-d，非法返回空串
     * @param mixed $date
     * @return string
     */
    private static function normalizeDate($date): string
    {
        $date = trim((string)$date);
        if ($date === '') {
            return '';
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * @notes 生成日期区间，最多 $maxDays 天
     * @param string $startDate
     * @param string $endDate
     * @param int $maxDays
     * @return array
     */
    private static function buildDateRange(string $startDate, string $endDate, int $maxDays): array
    {
        $dates = [];
        $cursor = strtotime($startDate);
        $endTs = strtotime($endDate);
        if ($cursor === false || $endTs === false) {
            return $dates;
        }

        while ($cursor <= $endTs && count($dates) < $maxDays) {
            $dates[] = date('Y-m-d', $cursor);
            $cursor = strtotime('+1 day', $cursor);
        }

        return $dates;
    }

    /**
     * @notes 获取吉日列表
     * @param array $params
     * @return array
     */
    public static function getLuckyDays(array $params): array
    {
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            return CalendarEvent::getLuckyDaysInRange(
                (string)$params['start_date'],
                (string)$params['end_date'],
                !empty($params['marriage_only'])
            );
        }

        $year = (int)($params['year'] ?? date('Y'));
        $month = (int)($params['month'] ?? date('m'));

        return CalendarEvent::getMonthLuckyDays($year, $month);
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
        $userId = (int)($params['user_id'] ?? 0);

        [$isAvailable, $message] = Schedule::checkAvailabilityForUserWithReason(
            $staffId,
            $date,
            $userId,
            0
        );
        $schedule = Schedule::where('staff_id', $staffId)
            ->where('schedule_date', $date)
            ->where('time_slot', 0)
            ->find();
        $ownedLock = $schedule
            && (int)$schedule->status === Schedule::STATUS_LOCKED
            && $userId > 0
            && (int)$schedule->lock_user_id === $userId
            && (int)$schedule->lock_expire_time > time();

        return [
            'is_available' => $isAvailable,
            'status' => $schedule ? $schedule->status : Schedule::STATUS_AVAILABLE,
            'status_desc' => $ownedLock ? '已为你锁定' : ($schedule ? $schedule->status_desc : '可预约'),
            'price' => $schedule && (float)$schedule->price > 0 ? (float)$schedule->price : null,
            'message' => $message,
        ];
    }

    /**
     * @notes 锁定档期
     * @param array $params
     * @return array
     */
    public static function lockSchedule(array $params): array
    {
        $startAt = microtime(true);
        [$success, $message] = Schedule::lockScheduleWithRedis(
            (int)$params['staff_id'],
            (string)$params['date'],
            0,
            (int)$params['user_id'],
            Schedule::LOCK_TYPE_NORMAL,
            (int)($params['lock_duration'] ?? 900),
            0,
            0
        );
        $elapsedMs = (int)round((microtime(true) - $startAt) * 1000);

        if (!$success || $elapsedMs >= 200) {
            Log::warning('预约档期锁定耗时异常', [
                'staff_id' => (int)$params['staff_id'],
                'date' => (string)$params['date'],
                'user_id' => (int)$params['user_id'],
                'success' => $success ? 1 : 0,
                'message' => $message,
                'elapsed_ms' => $elapsedMs,
            ]);
        }

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
        [$allowed] = ScheduleRule::checkBookingRule($staffId, $date);
        return $allowed;
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
            return in_array(
                (int)($schedule['status'] ?? Schedule::STATUS_AVAILABLE),
                [Schedule::STATUS_AVAILABLE, Schedule::STATUS_BOOKED],
                true
            );
        }

        $lockExpireTime = (int)($schedule['lock_expire_time'] ?? 0);
        return $lockExpireTime > 0 && $lockExpireTime < time();
    }

    /**
     * @notes 构建日历信息
     * @param array|null $event
     * @return array
     */
    protected static function buildCalendarMeta(?array $event): array
    {
        $congestionLevel = (int)($event['congestion_level'] ?? 0);
        return [
            'event_date' => (string)($event['event_date'] ?? ''),
            'lunar_date' => (string)($event['lunar_date'] ?? ''),
            'is_lucky_day' => (int)($event['is_lucky_day'] ?? 0),
            'lucky_events' => (string)($event['lucky_events'] ?? ''),
            'unlucky_events' => (string)($event['unlucky_events'] ?? ''),
            'is_holiday' => (int)($event['is_holiday'] ?? 0),
            'holiday_name' => (string)($event['holiday_name'] ?? ''),
            'congestion_level' => $congestionLevel,
            'congestion_level_text' => self::getCongestionLevelText($congestionLevel),
            'remark' => (string)($event['remark'] ?? ''),
        ];
    }

    /**
     * @notes 获取拥堵等级文案
     * @param int $level
     * @return string
     */
    protected static function getCongestionLevelText(int $level): string
    {
        $map = [
            CalendarEvent::CONGESTION_UNKNOWN => '未知',
            CalendarEvent::CONGESTION_LOW => '低',
            CalendarEvent::CONGESTION_MEDIUM => '中',
            CalendarEvent::CONGESTION_HIGH => '高',
        ];

        return $map[$level] ?? '未知';
    }
}
