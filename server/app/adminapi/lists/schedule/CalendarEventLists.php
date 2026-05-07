<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 吉日设置列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\schedule;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\schedule\CalendarEvent;

/**
 * 吉日设置列表
 * Class CalendarEventLists
 * @package app\adminapi\lists\schedule
 */
class CalendarEventLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['is_lucky_day', 'is_holiday', 'congestion_level'],
            '%like%' => ['lunar_date', 'holiday_name', 'remark'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $lists = $this->applyDateFilter(CalendarEvent::where($this->searchWhere))
            ->order('event_date', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item = $this->formatEvent($item);
        }

        return $lists;
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        return $this->applyDateFilter(CalendarEvent::where($this->searchWhere))->count();
    }

    /**
     * @notes 应用日期筛选
     * @param mixed $query
     * @return mixed
     */
    private function applyDateFilter($query)
    {
        if (!empty($this->params['event_date'])) {
            $query->where('event_date', (string)$this->params['event_date']);
        }
        if (!empty($this->params['start_date']) && !empty($this->params['end_date'])) {
            $query->whereBetween('event_date', [
                (string)$this->params['start_date'],
                (string)$this->params['end_date'],
            ]);
        }
        return $query;
    }

    /**
     * @notes 格式化吉日信息
     * @param array $event
     * @return array
     */
    private function formatEvent(array $event): array
    {
        $event['is_lucky_day'] = (int)($event['is_lucky_day'] ?? 0);
        $event['is_holiday'] = (int)($event['is_holiday'] ?? 0);
        $event['congestion_level'] = (int)($event['congestion_level'] ?? CalendarEvent::CONGESTION_UNKNOWN);
        $event['congestion_level_text'] = $this->getCongestionLevelText($event['congestion_level']);
        $event['congestion_level_desc'] = $event['congestion_level_text'];
        return $event;
    }

    /**
     * @notes 获取拥堵等级文案
     * @param int $level
     * @return string
     */
    private function getCongestionLevelText(int $level): string
    {
        $map = [];
        foreach (CalendarEvent::getCongestionLevelOptions() as $option) {
            $map[(int)$option['value']] = (string)$option['label'];
        }
        return $map[$level] ?? '未知';
    }
}
