<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 日历事件业务逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\schedule;

use app\common\logic\BaseLogic;
use app\common\model\schedule\CalendarEvent;

/**
 * 日历事件业务逻辑
 * Class CalendarEventLogic
 * @package app\adminapi\logic\schedule
 */
class CalendarEventLogic extends BaseLogic
{
    /**
     * @notes 事件详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $event = CalendarEvent::find($id);
        if (!$event) {
            return [];
        }
        $data = $event->toArray();
        $data['lucky_events_arr'] = $event->lucky_events_arr;
        $data['unlucky_events_arr'] = $event->unlucky_events_arr;
        $data['congestion_level_desc'] = $event->congestion_level_desc;
        return $data;
    }

    /**
     * @notes 获取月度日历
     * @param array $params
     * @return array
     */
    public static function getMonthCalendar(array $params): array
    {
        $year = $params['year'] ?? date('Y');
        $month = $params['month'] ?? date('m');

        $events = CalendarEvent::getMonthCalendar((int)$year, (int)$month);

        // 补全月份每一天
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $currentDate = $startDate;

        $result = [];
        while ($currentDate <= $endDate) {
            $result[$currentDate] = $events[$currentDate] ?? [
                'event_date' => $currentDate,
                'is_lucky_day' => 0,
                'is_holiday' => 0,
                'lunar_date' => '',
                'lucky_events' => '',
                'unlucky_events' => '',
            ];
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        return [
            'year' => $year,
            'month' => $month,
            'days' => $result,
        ];
    }

    /**
     * @notes 添加事件
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            // 检查日期是否已存在
            $exists = CalendarEvent::where('event_date', $params['event_date'])->find();
            if ($exists) {
                self::setError('该日期已存在事件记录');
                return false;
            }

            CalendarEvent::create([
                'event_date' => $params['event_date'],
                'lunar_date' => $params['lunar_date'] ?? '',
                'is_lucky_day' => $params['is_lucky_day'] ?? 0,
                'lucky_events' => is_array($params['lucky_events'] ?? '') ? implode(',', $params['lucky_events']) : ($params['lucky_events'] ?? ''),
                'unlucky_events' => is_array($params['unlucky_events'] ?? '') ? implode(',', $params['unlucky_events']) : ($params['unlucky_events'] ?? ''),
                'is_holiday' => $params['is_holiday'] ?? 0,
                'holiday_name' => $params['holiday_name'] ?? '',
                'congestion_level' => $params['congestion_level'] ?? 0,
                'remark' => $params['remark'] ?? '',
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑事件
     * @param array $params
     * @return bool
     */
    public static function edit(array $params): bool
    {
        try {
            $event = CalendarEvent::find($params['id']);
            if (!$event) {
                self::setError('事件不存在');
                return false;
            }

            // 如果修改了日期，检查是否冲突
            if (isset($params['event_date']) && $params['event_date'] != $event->event_date) {
                $exists = CalendarEvent::where('event_date', $params['event_date'])
                    ->where('id', '<>', $params['id'])
                    ->find();
                if ($exists) {
                    self::setError('该日期已存在事件记录');
                    return false;
                }
            }

            $event->event_date = $params['event_date'] ?? $event->event_date;
            $event->lunar_date = $params['lunar_date'] ?? $event->lunar_date;
            $event->is_lucky_day = $params['is_lucky_day'] ?? $event->is_lucky_day;
            $event->lucky_events = is_array($params['lucky_events'] ?? '') ? implode(',', $params['lucky_events']) : ($params['lucky_events'] ?? $event->lucky_events);
            $event->unlucky_events = is_array($params['unlucky_events'] ?? '') ? implode(',', $params['unlucky_events']) : ($params['unlucky_events'] ?? $event->unlucky_events);
            $event->is_holiday = $params['is_holiday'] ?? $event->is_holiday;
            $event->holiday_name = $params['holiday_name'] ?? $event->holiday_name;
            $event->congestion_level = $params['congestion_level'] ?? $event->congestion_level;
            $event->remark = $params['remark'] ?? $event->remark;
            $event->update_time = time();
            $event->save();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除事件
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        try {
            CalendarEvent::destroy($params['id']);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
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
     * @notes 获取节假日列表
     * @param array $params
     * @return array
     */
    public static function getHolidays(array $params): array
    {
        $days = $params['days'] ?? 90;
        return CalendarEvent::getUpcomingHolidays((int)$days);
    }

    /**
     * @notes 批量导入
     * @param array $params
     * @return array|false
     */
    public static function batchImport(array $params)
    {
        try {
            $data = $params['data'] ?? [];
            if (empty($data)) {
                self::setError('导入数据不能为空');
                return false;
            }

            [$success, $failed] = CalendarEvent::batchImport($data);
            return ['success' => $success, 'failed' => $failed];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取拥堵等级选项
     * @return array
     */
    public static function getCongestionOptions(): array
    {
        return CalendarEvent::getCongestionLevelOptions();
    }
}
