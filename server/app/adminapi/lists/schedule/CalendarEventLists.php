<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 日历事件列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\schedule;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\model\schedule\CalendarEvent;

/**
 * 日历事件列表
 * Class CalendarEventLists
 * @package app\adminapi\lists\schedule
 */
class CalendarEventLists extends BaseAdminDataLists implements ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['is_lucky_day', 'is_holiday', 'congestion_level'],
            'between_date' => ['event_date'],
        ];
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = CalendarEvent::where($this->searchWhere)
            ->order('event_date', 'asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['lucky_events_arr'] = $item['lucky_events'] ? explode(',', $item['lucky_events']) : [];
            $item['unlucky_events_arr'] = $item['unlucky_events'] ? explode(',', $item['unlucky_events']) : [];
            $item['congestion_level_desc'] = $this->getCongestionDesc($item['congestion_level']);
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return CalendarEvent::where($this->searchWhere)->count();
    }

    /**
     * @notes 导出字段
     * @return array
     */
    public function setExcelFields(): array
    {
        return [
            'event_date' => '日期',
            'lunar_date' => '农历',
            'is_lucky_day' => '是否吉日',
            'lucky_events' => '宜',
            'unlucky_events' => '忌',
            'is_holiday' => '是否节假日',
            'holiday_name' => '节假日名称',
            'congestion_level_desc' => '拥堵等级',
            'remark' => '备注',
        ];
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '日历事件列表';
    }

    /**
     * @notes 获取拥堵等级描述
     * @param int $level
     * @return string
     */
    protected function getCongestionDesc(int $level): string
    {
        $map = [
            0 => '未知',
            1 => '低',
            2 => '中',
            3 => '高',
        ];
        return $map[$level] ?? '未知';
    }
}
