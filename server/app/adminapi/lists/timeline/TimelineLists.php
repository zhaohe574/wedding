<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 时间轴列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\timeline;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\timeline\OrderTimeline;
use app\common\lists\ListsSearchInterface;

/**
 * 时间轴任务列表
 * Class TimelineLists
 * @package app\adminapi\lists\timeline
 */
class TimelineLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['order_id', 'user_id', 'task_type', 'is_completed', 'is_system'],
            '%like%' => ['task_title'],
            'between_date' => ['trigger_date', 'wedding_date'],
        ];
    }

    /**
     * @notes 自定义查询条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];

        // 状态筛选
        if (!empty($this->params['status'])) {
            $today = date('Y-m-d');
            switch ($this->params['status']) {
                case 'completed':
                    $where[] = ['is_completed', '=', 1];
                    break;
                case 'pending':
                    $where[] = ['is_completed', '=', 0];
                    $where[] = ['trigger_date', '>=', $today];
                    break;
                case 'overdue':
                    $where[] = ['is_completed', '=', 0];
                    $where[] = ['trigger_date', '<', $today];
                    break;
                case 'today':
                    $where[] = ['trigger_date', '=', $today];
                    break;
            }
        }

        return $where;
    }

    /**
     * @notes 列表数据
     * @return array
     */
    public function lists(): array
    {
        $lists = OrderTimeline::with([
            'order' => function ($query) {
                $query->field('id, order_sn, user_id, wedding_date, contact_name');
            },
            'user' => function ($query) {
                $query->field('id, nickname, avatar, mobile');
            }
        ])
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->order($this->sortOrder ?: ['trigger_date' => 'asc', 'sort' => 'desc', 'id' => 'asc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['task_type_desc'] = $this->getTaskTypeDesc($item['task_type']);
            $item['is_completed_desc'] = $item['is_completed'] ? '已完成' : '未完成';
            $item['status'] = $this->getTaskStatus($item);
            $item['status_desc'] = $this->getStatusDesc($item['status']);
        }

        return $lists;
    }

    /**
     * @notes 统计数量
     * @return int
     */
    public function count(): int
    {
        return OrderTimeline::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }

    /**
     * @notes 获取任务类型描述
     * @param int $type
     * @return string
     */
    private function getTaskTypeDesc(int $type): string
    {
        $map = [
            1 => '准备物料',
            2 => '确认事项',
            3 => '沟通联系',
            4 => '现场安排',
            5 => '其他',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 获取任务状态
     * @param array $item
     * @return string
     */
    private function getTaskStatus(array $item): string
    {
        if ($item['is_completed']) {
            return 'completed';
        }

        $triggerDate = strtotime($item['trigger_date']);
        $today = strtotime(date('Y-m-d'));

        if ($triggerDate < $today) {
            return 'overdue';
        } elseif ($triggerDate == $today) {
            return 'today';
        }
        return 'pending';
    }

    /**
     * @notes 获取状态描述
     * @param string $status
     * @return string
     */
    private function getStatusDesc(string $status): string
    {
        $map = [
            'completed' => '已完成',
            'pending' => '待完成',
            'overdue' => '已逾期',
            'today' => '今日任务',
        ];
        return $map[$status] ?? '未知';
    }
}
