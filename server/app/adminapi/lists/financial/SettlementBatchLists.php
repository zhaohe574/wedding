<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 结算批次列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\financial;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\financial\SettlementBatch;
use app\common\lists\ListsSearchInterface;

/**
 * 结算批次列表
 * Class SettlementBatchLists
 * @package app\adminapi\lists\financial
 */
class SettlementBatchLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     */
    public function setSearch(): array
    {
        return [
            '=' => ['status'],
            '%like%' => ['batch_sn', 'batch_name'],
        ];
    }

    /**
     * @notes 列表
     */
    public function lists(): array
    {
        $list = SettlementBatch::where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        
        foreach ($list as &$item) {
            $item['status_text'] = SettlementBatch::getStatusDesc($item['status']);
        }
        
        return $list;
    }

    /**
     * @notes 总数
     */
    public function count(): int
    {
        return SettlementBatch::where($this->searchWhere)->count();
    }
}
