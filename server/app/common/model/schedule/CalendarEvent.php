<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 黄历/吉日模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\schedule;

use app\common\model\BaseModel;

/**
 * 黄历/吉日模型
 * Class CalendarEvent
 * @package app\common\model\schedule
 */
class CalendarEvent extends BaseModel
{
    protected $name = 'calendar_event';

    // 拥堵等级
    const CONGESTION_UNKNOWN = 0;   // 未知
    const CONGESTION_LOW = 1;       // 低
    const CONGESTION_MEDIUM = 2;    // 中
    const CONGESTION_HIGH = 3;      // 高

    /**
     * @notes 宜事项数组获取器
     * @param $value
     * @return array
     */
    public function getLuckyEventsArrAttr($value, $data): array
    {
        if (empty($data['lucky_events'])) {
            return [];
        }
        return explode(',', $data['lucky_events']);
    }

    /**
     * @notes 忌事项数组获取器
     * @param $value
     * @return array
     */
    public function getUnluckyEventsArrAttr($value, $data): array
    {
        if (empty($data['unlucky_events'])) {
            return [];
        }
        return explode(',', $data['unlucky_events']);
    }

    /**
     * @notes 拥堵等级描述获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getCongestionLevelDescAttr($value, $data): string
    {
        $map = [
            self::CONGESTION_UNKNOWN => '未知',
            self::CONGESTION_LOW => '低',
            self::CONGESTION_MEDIUM => '中',
            self::CONGESTION_HIGH => '高',
        ];
        return $map[$data['congestion_level']] ?? '未知';
    }

    /**
     * @notes 获取指定日期的黄历信息
     * @param string $date
     * @return array|null
     */
    public static function getDateInfo(string $date): ?array
    {
        $event = self::where('event_date', $date)->find();
        return $event ? $event->toArray() : null;
    }

    /**
     * @notes 获取月度吉日列表
     * @param int $year
     * @param int $month
     * @return array
     */
    public static function getMonthLuckyDays(int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        return self::where('is_lucky_day', 1)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->order('event_date', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取月度日历数据（包含所有事件）
     * @param int $year
     * @param int $month
     * @return array
     */
    public static function getMonthCalendar(int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $events = self::whereBetween('event_date', [$startDate, $endDate])
            ->order('event_date', 'asc')
            ->select()
            ->toArray();

        // 转换为日期索引的数组
        $result = [];
        foreach ($events as $event) {
            $result[$event['event_date']] = $event;
        }

        return $result;
    }

    /**
     * @notes 获取指定范围内的吉日
     * @param string $startDate
     * @param string $endDate
     * @param bool $marriageOnly 是否只返回适合结婚的吉日
     * @return array
     */
    public static function getLuckyDaysInRange(string $startDate, string $endDate, bool $marriageOnly = false): array
    {
        $query = self::where('is_lucky_day', 1)
            ->whereBetween('event_date', [$startDate, $endDate]);

        if ($marriageOnly) {
            $query->where('lucky_events', 'like', '%嫁娶%');
        }

        return $query->order('event_date', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取近期节假日
     * @param int $days 未来多少天
     * @return array
     */
    public static function getUpcomingHolidays(int $days = 90): array
    {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$days} days"));

        return self::where('is_holiday', 1)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->order('event_date', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 检查日期是否为吉日
     * @param string $date
     * @return bool
     */
    public static function isLuckyDay(string $date): bool
    {
        $event = self::where('event_date', $date)
            ->where('is_lucky_day', 1)
            ->find();
        return $event !== null;
    }

    /**
     * @notes 检查日期是否为节假日
     * @param string $date
     * @return bool
     */
    public static function isHoliday(string $date): bool
    {
        $event = self::where('event_date', $date)
            ->where('is_holiday', 1)
            ->find();
        return $event !== null;
    }

    /**
     * @notes 获取拥堵等级选项
     * @return array
     */
    public static function getCongestionLevelOptions(): array
    {
        return [
            ['value' => self::CONGESTION_UNKNOWN, 'label' => '未知'],
            ['value' => self::CONGESTION_LOW, 'label' => '低'],
            ['value' => self::CONGESTION_MEDIUM, 'label' => '中'],
            ['value' => self::CONGESTION_HIGH, 'label' => '高'],
        ];
    }

    /**
     * @notes 批量导入黄历数据
     * @param array $data
     * @return array [int $success, int $failed]
     */
    public static function batchImport(array $data): array
    {
        $success = 0;
        $failed = 0;

        foreach ($data as $item) {
            try {
                $exists = self::where('event_date', $item['event_date'])->find();
                if ($exists) {
                    $exists->save($item);
                } else {
                    self::create(array_merge($item, [
                        'create_time' => time(),
                        'update_time' => time(),
                    ]));
                }
                $success++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return [$success, $failed];
    }
}
