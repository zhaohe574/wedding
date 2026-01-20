<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 资金流水控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\financial;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\financial\FinancialFlowLists;
use app\common\model\financial\FinancialFlow;

/**
 * 资金流水控制器
 * Class FlowController
 * @package app\adminapi\controller\financial
 */
class FlowController extends BaseAdminController
{
    /**
     * @notes 流水列表
     */
    public function lists()
    {
        return $this->dataLists(new FinancialFlowLists());
    }

    /**
     * @notes 流水详情
     */
    public function detail()
    {
        $id = $this->request->get('id/d', 0);
        if ($id <= 0) {
            return $this->fail('参数错误');
        }
        
        $flow = FinancialFlow::with(['user', 'staff', 'order'])->find($id);
        if (!$flow) {
            return $this->fail('流水记录不存在');
        }
        
        $data = $flow->toArray();
        $data['flow_type_text'] = FinancialFlow::getFlowTypeDesc($flow->flow_type);
        $data['biz_type_text'] = FinancialFlow::getBizTypeDesc($flow->biz_type);
        $data['pay_way_text'] = FinancialFlow::getPayWayDesc($flow->pay_way);
        
        return $this->success('获取成功', $data);
    }

    /**
     * @notes 流水统计
     */
    public function statistics()
    {
        $params = $this->request->get();
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $stats = FinancialFlow::getFlowStats($startDate, $endDate);
        $incomeByPayWay = FinancialFlow::getIncomeByPayWay($startDate, $endDate);
        
        $payWayLabels = FinancialFlow::getPayWayDesc();
        $payWayStats = [];
        foreach ($payWayLabels as $way => $label) {
            $payWayStats[] = [
                'pay_way' => $way,
                'label' => $label,
                'amount' => round($incomeByPayWay[$way] ?? 0, 2),
            ];
        }
        
        return $this->success('获取成功', [
            'total_count' => $stats['total_count'],
            'total_income' => round($stats['total_income'], 2),
            'total_expense' => round($stats['total_expense'], 2),
            'net_amount' => round($stats['total_income'] - $stats['total_expense'], 2),
            'by_pay_way' => $payWayStats,
        ]);
    }

    /**
     * @notes 流水类型选项
     */
    public function flowTypeOptions()
    {
        $types = FinancialFlow::getFlowTypeDesc();
        $result = [];
        foreach ($types as $value => $label) {
            $result[] = ['value' => $value, 'label' => $label];
        }
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 业务类型选项
     */
    public function bizTypeOptions()
    {
        $types = FinancialFlow::getBizTypeDesc();
        $result = [];
        foreach ($types as $value => $label) {
            $result[] = ['value' => $value, 'label' => $label];
        }
        return $this->success('获取成功', $result);
    }
}
