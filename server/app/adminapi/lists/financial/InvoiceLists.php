<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 发票列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\financial;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\financial\Invoice;
use app\common\lists\ListsSearchInterface;

/**
 * 发票列表
 * Class InvoiceLists
 * @package app\adminapi\lists\financial
 */
class InvoiceLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     */
    public function setSearch(): array
    {
        return [
            '=' => ['order_id', 'user_id', 'invoice_type', 'title_type', 'status'],
            '%like%' => ['invoice_sn', 'invoice_no', 'invoice_title'],
        ];
    }

    /**
     * @notes 列表
     */
    public function lists(): array
    {
        $list = Invoice::with(['user', 'order'])
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        
        foreach ($list as &$item) {
            $item['invoice_type_text'] = Invoice::getTypeDesc($item['invoice_type']);
            $item['title_type_text'] = Invoice::getTitleTypeDesc($item['title_type']);
            $item['status_text'] = Invoice::getStatusDesc($item['status']);
        }
        
        return $list;
    }

    /**
     * @notes 总数
     */
    public function count(): int
    {
        return Invoice::where($this->searchWhere)->count();
    }
}
