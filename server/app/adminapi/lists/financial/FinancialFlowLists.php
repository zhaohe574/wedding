<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 资金流水列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\financial;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\financial\FinancialFlow;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsExcelInterface;

/**
 * 资金流水列表
 * Class FinancialFlowLists
 * @package app\adminapi\lists\financial
 */
class FinancialFlowLists extends BaseAdminDataLists implements ListsSearchInterface, ListsExcelInterface
{
    /**
     * @notes 搜索条件
     */
    public function setSearch(): array
    {
        return [
            '=' => ['flow_type', 'biz_type', 'pay_way', 'user_id', 'staff_id', 'order_id', 'direction'],
            '%like%' => ['flow_sn', 'biz_sn'],
        ];
    }

    /**
     * @notes 列表
     */
    public function lists(): array
    {
        $query = FinancialFlow::with(['user', 'staff', 'order'])
            ->where($this->searchWhere);
        
        // 时间范围
        if (!empty($this->params['start_date']) && !empty($this->params['end_date'])) {
            $query->whereBetweenTime('create_time', $this->params['start_date'], $this->params['end_date'] . ' 23:59:59');
        }
        
        // 金额范围
        if (!empty($this->params['min_amount'])) {
            $query->where('amount', '>=', $this->params['min_amount']);
        }
        if (!empty($this->params['max_amount'])) {
            $query->where('amount', '<=', $this->params['max_amount']);
        }
        
        $list = $query->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        
        foreach ($list as &$item) {
            $item['flow_type_text'] = FinancialFlow::getFlowTypeDesc($item['flow_type']);
            $item['biz_type_text'] = FinancialFlow::getBizTypeDesc($item['biz_type']);
            $item['pay_way_text'] = FinancialFlow::getPayWayDesc($item['pay_way']);
            $item['direction_text'] = $item['direction'] == 1 ? '收入' : '支出';
        }
        
        return $list;
    }

    /**
     * @notes 总数
     */
    public function count(): int
    {
        $query = FinancialFlow::where($this->searchWhere);
        
        if (!empty($this->params['start_date']) && !empty($this->params['end_date'])) {
            $query->whereBetweenTime('create_time', $this->params['start_date'], $this->params['end_date'] . ' 23:59:59');
        }
        
        if (!empty($this->params['min_amount'])) {
            $query->where('amount', '>=', $this->params['min_amount']);
        }
        if (!empty($this->params['max_amount'])) {
            $query->where('amount', '<=', $this->params['max_amount']);
        }
        
        return $query->count();
    }

    /**
     * @notes 导出字段
     */
    public function setExcelFields(): array
    {
        return [
            'flow_sn' => '流水编号',
            'flow_type_text' => '流水类型',
            'biz_type_text' => '业务类型',
            'biz_sn' => '业务编号',
            'amount' => '金额',
            'direction_text' => '方向',
            'pay_way_text' => '支付方式',
            'remark' => '备注',
            'create_time' => '创建时间',
        ];
    }

    /**
     * @notes 导出文件名
     */
    public function setFileName(): string
    {
        return '资金流水记录';
    }
}
