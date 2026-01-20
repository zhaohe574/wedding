<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 成本记录列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\financial;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\financial\CostRecord;
use app\common\lists\ListsSearchInterface;

/**
 * 成本记录列表
 * Class CostRecordLists
 * @package app\adminapi\lists\financial
 */
class CostRecordLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     */
    public function setSearch(): array
    {
        return [
            '=' => ['order_id', 'staff_id', 'cost_type', 'status'],
            '%like%' => ['cost_name'],
        ];
    }

    /**
     * @notes 列表
     */
    public function lists(): array
    {
        $query = CostRecord::with(['order', 'staff'])
            ->where($this->searchWhere);
        
        // 服务日期范围
        if (!empty($this->params['start_date']) && !empty($this->params['end_date'])) {
            $query->whereBetween('service_date', [$this->params['start_date'], $this->params['end_date']]);
        }
        
        $list = $query->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        
        foreach ($list as &$item) {
            $item['cost_type_text'] = CostRecord::getCostTypeDesc($item['cost_type']);
            $item['status_text'] = CostRecord::getStatusDesc($item['status']);
        }
        
        return $list;
    }

    /**
     * @notes 总数
     */
    public function count(): int
    {
        $query = CostRecord::where($this->searchWhere);
        
        if (!empty($this->params['start_date']) && !empty($this->params['end_date'])) {
            $query->whereBetween('service_date', [$this->params['start_date'], $this->params['end_date']]);
        }
        
        return $query->count();
    }
}
