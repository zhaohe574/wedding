<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户流失预警列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\crm;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\crm\CustomerLossWarning;
use app\common\lists\ListsSearchInterface;

/**
 * 客户流失预警列表
 * Class CustomerLossWarningLists
 * @package app\adminapi\lists\crm
 */
class CustomerLossWarningLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['customer_id', 'advisor_id', 'warning_type', 'warning_level', 'warning_status'],
            'between_time' => ['create_time', 'handle_time'],
        ];
    }

    /**
     * @notes 列表数据
     * @return array
     */
    public function lists(): array
    {
        $lists = CustomerLossWarning::with([
            'customer' => function ($query) {
                $query->field('id, customer_name, customer_mobile, intention_level, customer_status, last_follow_time');
            },
            'advisor' => function ($query) {
                $query->field('id, advisor_name, mobile, avatar');
            }
        ])
            ->where($this->searchWhere)
            ->order($this->sortOrder ?: ['warning_level' => 'desc', 'create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['warning_type_desc'] = $this->getWarningTypeDesc($item['warning_type']);
            $item['warning_level_desc'] = $this->getLevelDesc($item['warning_level']);
            $item['warning_status_desc'] = $this->getStatusDesc($item['warning_status']);
        }

        return $lists;
    }

    /**
     * @notes 统计数量
     * @return int
     */
    public function count(): int
    {
        return CustomerLossWarning::where($this->searchWhere)->count();
    }

    /**
     * @notes 获取预警类型描述
     * @param int $type
     * @return string
     */
    private function getWarningTypeDesc(int $type): string
    {
        $map = [
            1 => '长期未跟进',
            2 => '意向下降',
            3 => '竞品流失',
            4 => '预算不足',
            5 => '其他',
        ];
        return $map[$type] ?? '未知';
    }

    /**
     * @notes 获取预警等级描述
     * @param int $level
     * @return string
     */
    private function getLevelDesc(int $level): string
    {
        $map = [
            1 => '低',
            2 => '中',
            3 => '高',
        ];
        return $map[$level] ?? '未知';
    }

    /**
     * @notes 获取处理状态描述
     * @param int $status
     * @return string
     */
    private function getStatusDesc(int $status): string
    {
        $map = [
            0 => '待处理',
            1 => '已处理',
            2 => '已忽略',
        ];
        return $map[$status] ?? '未知';
    }
}
