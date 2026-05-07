<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 吉日设置逻辑
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\schedule;

use app\common\logic\BaseLogic;
use app\common\model\schedule\CalendarEvent;
use think\facade\Db;

/**
 * 吉日设置逻辑
 * Class CalendarEventLogic
 * @package app\adminapi\logic\schedule
 */
class CalendarEventLogic extends BaseLogic
{
    private const MIN_DATE = '2020-01-01';
    private const MAX_DATE = '2100-12-31';

    /**
     * @notes 获取详情
     * @param int $id
     * @return array
     */
    public static function detail(int $id): array
    {
        $event = CalendarEvent::find($id);
        return $event ? self::formatEvent($event->toArray()) : [];
    }

    /**
     * @notes 保存单日吉日设置
     * @param array $params
     * @return bool|string
     */
    public static function save(array $params)
    {
        $date = self::normalizeDate((string)($params['event_date'] ?? ''));
        if (!self::isDateInAllowedRange($date)) {
            return self::dateRangeMessage();
        }

        $data = self::buildSaveData($params);
        $data['event_date'] = $date;

        try {
            $event = CalendarEvent::where('event_date', $date)->find();
            if ($event) {
                $data['update_time'] = time();
                $event->save($data);
            } else {
                $data['create_time'] = time();
                $data['update_time'] = time();
                CalendarEvent::create($data);
            }
            return true;
        } catch (\Exception $e) {
            return '保存失败：' . $e->getMessage();
        }
    }

    /**
     * @notes 删除吉日设置
     * @param int $id
     * @return bool|string
     */
    public static function delete(int $id)
    {
        try {
            $event = CalendarEvent::find($id);
            if (!$event) {
                return '吉日记录不存在';
            }
            $event->delete();
            return true;
        } catch (\Exception $e) {
            return '删除失败：' . $e->getMessage();
        }
    }

    /**
     * @notes 批量保存吉日设置
     * @param array $params
     * @return int|string
     */
    public static function batchSave(array $params)
    {
        $startDate = self::normalizeDate((string)($params['start_date'] ?? ''));
        $endDate = self::normalizeDate((string)($params['end_date'] ?? ''));
        if (!self::isDateInAllowedRange($startDate) || !self::isDateInAllowedRange($endDate)) {
            return self::dateRangeMessage();
        }
        if (strtotime($startDate) > strtotime($endDate)) {
            return '结束日期不能早于开始日期';
        }

        $data = self::buildSaveData($params);
        $count = 0;

        Db::startTrans();
        try {
            for ($time = strtotime($startDate); $time <= strtotime($endDate); $time += 86400) {
                $date = date('Y-m-d', $time);
                $event = CalendarEvent::where('event_date', $date)->find();
                $saveData = array_merge($data, [
                    'event_date' => $date,
                    'update_time' => time(),
                ]);
                if ($event) {
                    $event->save($saveData);
                } else {
                    $saveData['create_time'] = time();
                    CalendarEvent::create($saveData);
                }
                $count++;
            }
            Db::commit();
            return $count;
        } catch (\Exception $e) {
            Db::rollback();
            return '批量设置失败：' . $e->getMessage();
        }
    }

    /**
     * @notes 拥堵等级选项
     * @return array
     */
    public static function congestionLevelOptions(): array
    {
        return CalendarEvent::getCongestionLevelOptions();
    }

    /**
     * @notes 格式化吉日信息
     * @param array $event
     * @return array
     */
    public static function formatEvent(array $event): array
    {
        $event['is_lucky_day'] = (int)($event['is_lucky_day'] ?? 0);
        $event['is_holiday'] = (int)($event['is_holiday'] ?? 0);
        $event['congestion_level'] = (int)($event['congestion_level'] ?? CalendarEvent::CONGESTION_UNKNOWN);
        $event['congestion_level_text'] = self::getCongestionLevelText($event['congestion_level']);
        $event['congestion_level_desc'] = $event['congestion_level_text'];
        return $event;
    }

    /**
     * @notes 构建保存数据
     * @param array $params
     * @return array
     */
    private static function buildSaveData(array $params): array
    {
        return [
            'lunar_date' => self::trimText((string)($params['lunar_date'] ?? ''), 20),
            'is_lucky_day' => !empty($params['is_lucky_day']) ? 1 : 0,
            'lucky_events' => self::trimText((string)($params['lucky_events'] ?? ''), 255),
            'unlucky_events' => self::trimText((string)($params['unlucky_events'] ?? ''), 255),
            'is_holiday' => !empty($params['is_holiday']) ? 1 : 0,
            'holiday_name' => self::trimText((string)($params['holiday_name'] ?? ''), 50),
            'congestion_level' => max(0, min(3, (int)($params['congestion_level'] ?? 0))),
            'remark' => self::trimText((string)($params['remark'] ?? ''), 255),
        ];
    }

    /**
     * @notes 标准化日期
     * @param string $date
     * @return string
     */
    private static function normalizeDate(string $date): string
    {
        $timestamp = strtotime($date);
        return $timestamp > 0 ? date('Y-m-d', $timestamp) : '';
    }

    /**
     * @notes 日期是否在允许范围内
     * @param string $date
     * @return bool
     */
    private static function isDateInAllowedRange(string $date): bool
    {
        if ($date === '') {
            return false;
        }
        return $date >= self::MIN_DATE && $date <= self::MAX_DATE;
    }

    /**
     * @notes 日期范围提示
     * @return string
     */
    private static function dateRangeMessage(): string
    {
        return '日期必须在' . self::MIN_DATE . '至' . self::MAX_DATE . '之间';
    }

    /**
     * @notes 获取拥堵等级文案
     * @param int $level
     * @return string
     */
    private static function getCongestionLevelText(int $level): string
    {
        $map = [];
        foreach (CalendarEvent::getCongestionLevelOptions() as $option) {
            $map[(int)$option['value']] = (string)$option['label'];
        }
        return $map[$level] ?? '未知';
    }

    /**
     * @notes 截断文本
     * @param string $value
     * @param int $maxLength
     * @return string
     */
    private static function trimText(string $value, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        return mb_strlen($value, 'UTF-8') > $maxLength
            ? mb_substr($value, 0, $maxLength, 'UTF-8')
            : $value;
    }
}
