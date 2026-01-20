<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 财务报表逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\financial;

use app\common\logic\BaseLogic;
use app\common\model\financial\FinancialFlow;
use app\common\model\financial\FinancialDaily;
use app\common\model\financial\FinancialMonthly;
use app\common\model\financial\CostRecord;
use app\common\model\order\Order;
use app\common\model\order\Payment;
use app\common\model\order\Refund;

/**
 * 财务报表逻辑层
 * Class FinancialReportLogic
 * @package app\adminapi\logic\financial
 */
class FinancialReportLogic extends BaseLogic
{
    /**
     * @notes 财务概览
     */
    public static function overview(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');
        
        // 本期数据
        $currentIncome = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->where('pay_status', 1)
            ->sum('pay_amount');
        
        $currentRefund = Refund::whereBetweenTime('refund_time', $startTime, $endTime)
            ->where('refund_status', 3)
            ->sum('refund_amount');
        
        $currentCost = CostRecord::whereBetween('service_date', [$startDate, $endDate])
            ->where('status', CostRecord::STATUS_CONFIRMED)
            ->sum('cost_amount');
        
        $currentOrders = Order::whereBetweenTime('create_time', $startTime, $endTime)
            ->where('pay_status', '>', 0)
            ->count();
        
        // 计算周期天数
        $days = (strtotime($endDate) - strtotime($startDate)) / 86400 + 1;
        
        // 上期数据
        $lastStartDate = date('Y-m-d', strtotime("-{$days} days", $startTime));
        $lastEndDate = date('Y-m-d', strtotime('-1 day', $startTime));
        $lastStartTime = strtotime($lastStartDate);
        $lastEndTime = strtotime($lastEndDate . ' 23:59:59');
        
        $lastIncome = Payment::whereBetweenTime('pay_time', $lastStartTime, $lastEndTime)
            ->where('pay_status', 1)
            ->sum('pay_amount');
        
        $lastRefund = Refund::whereBetweenTime('refund_time', $lastStartTime, $lastEndTime)
            ->where('refund_status', 3)
            ->sum('refund_amount');
        
        // 计算增长率
        $incomeGrowth = $lastIncome > 0 ? round(($currentIncome - $lastIncome) / $lastIncome * 100, 2) : 0;
        $refundGrowth = $lastRefund > 0 ? round(($currentRefund - $lastRefund) / $lastRefund * 100, 2) : 0;
        
        // 净收入和毛利润
        $netIncome = $currentIncome - $currentRefund;
        $grossProfit = $netIncome - $currentCost;
        $profitRate = $netIncome > 0 ? round($grossProfit / $netIncome * 100, 2) : 0;
        
        return [
            'total_income' => round($currentIncome, 2),
            'total_refund' => round($currentRefund, 2),
            'net_income' => round($netIncome, 2),
            'total_cost' => round($currentCost, 2),
            'gross_profit' => round($grossProfit, 2),
            'profit_rate' => $profitRate,
            'order_count' => $currentOrders,
            'avg_order_amount' => $currentOrders > 0 ? round($currentIncome / $currentOrders, 2) : 0,
            'income_growth' => $incomeGrowth,
            'refund_growth' => $refundGrowth,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days,
            ],
        ];
    }

    /**
     * @notes 收入统计
     */
    public static function incomeStats(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');
        
        // 按支付类型统计
        $byPayType = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->where('pay_status', 1)
            ->group('pay_type')
            ->column('SUM(pay_amount) as amount, COUNT(*) as count', 'pay_type');
        
        // 按支付方式统计
        $byPayWay = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->where('pay_status', 1)
            ->group('pay_way')
            ->column('SUM(pay_amount) as amount, COUNT(*) as count', 'pay_way');
        
        $total = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->where('pay_status', 1)
            ->sum('pay_amount');
        
        return [
            'total' => round($total, 2),
            'by_pay_type' => [
                'deposit' => [
                    'amount' => round($byPayType[1]['amount'] ?? 0, 2),
                    'count' => $byPayType[1]['count'] ?? 0,
                    'label' => '定金',
                ],
                'balance' => [
                    'amount' => round($byPayType[2]['amount'] ?? 0, 2),
                    'count' => $byPayType[2]['count'] ?? 0,
                    'label' => '尾款',
                ],
                'full' => [
                    'amount' => round($byPayType[3]['amount'] ?? 0, 2),
                    'count' => $byPayType[3]['count'] ?? 0,
                    'label' => '全款',
                ],
            ],
            'by_pay_way' => [
                'wechat' => [
                    'amount' => round($byPayWay[1]['amount'] ?? 0, 2),
                    'count' => $byPayWay[1]['count'] ?? 0,
                    'label' => '微信支付',
                ],
                'alipay' => [
                    'amount' => round($byPayWay[2]['amount'] ?? 0, 2),
                    'count' => $byPayWay[2]['count'] ?? 0,
                    'label' => '支付宝',
                ],
                'balance' => [
                    'amount' => round($byPayWay[3]['amount'] ?? 0, 2),
                    'count' => $byPayWay[3]['count'] ?? 0,
                    'label' => '余额支付',
                ],
                'offline' => [
                    'amount' => round($byPayWay[4]['amount'] ?? 0, 2),
                    'count' => $byPayWay[4]['count'] ?? 0,
                    'label' => '线下支付',
                ],
            ],
        ];
    }

    /**
     * @notes 支付方式分析
     */
    public static function payWayAnalysis(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');
        
        $total = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->where('pay_status', 1)
            ->sum('pay_amount');
        
        $byPayWay = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->where('pay_status', 1)
            ->group('pay_way')
            ->column('SUM(pay_amount) as amount, COUNT(*) as count', 'pay_way');
        
        $payWayLabels = [
            1 => '微信支付',
            2 => '支付宝',
            3 => '余额支付',
            4 => '线下支付',
        ];
        
        $result = [];
        foreach ($payWayLabels as $way => $label) {
            $amount = round($byPayWay[$way]['amount'] ?? 0, 2);
            $count = $byPayWay[$way]['count'] ?? 0;
            $result[] = [
                'pay_way' => $way,
                'label' => $label,
                'amount' => $amount,
                'count' => $count,
                'ratio' => $total > 0 ? round($amount / $total * 100, 2) : 0,
            ];
        }
        
        return [
            'total' => round($total, 2),
            'list' => $result,
        ];
    }

    /**
     * @notes 退款统计
     */
    public static function refundStats(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');
        
        // 退款状态统计
        $byStatus = Refund::whereBetweenTime('create_time', $startTime, $endTime)
            ->group('refund_status')
            ->column('SUM(refund_amount) as amount, COUNT(*) as count', 'refund_status');
        
        // 退款类型统计
        $byType = Refund::whereBetweenTime('create_time', $startTime, $endTime)
            ->group('refund_type')
            ->column('SUM(refund_amount) as amount, COUNT(*) as count', 'refund_type');
        
        $totalRefund = Refund::whereBetweenTime('refund_time', $startTime, $endTime)
            ->where('refund_status', 3)
            ->sum('refund_amount');
        
        $totalIncome = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->where('pay_status', 1)
            ->sum('pay_amount');
        
        return [
            'total_refund' => round($totalRefund, 2),
            'refund_rate' => $totalIncome > 0 ? round($totalRefund / $totalIncome * 100, 2) : 0,
            'by_status' => [
                'pending' => ['amount' => round($byStatus[0]['amount'] ?? 0, 2), 'count' => $byStatus[0]['count'] ?? 0, 'label' => '待审核'],
                'approved' => ['amount' => round($byStatus[1]['amount'] ?? 0, 2), 'count' => $byStatus[1]['count'] ?? 0, 'label' => '审核通过'],
                'processing' => ['amount' => round($byStatus[2]['amount'] ?? 0, 2), 'count' => $byStatus[2]['count'] ?? 0, 'label' => '退款中'],
                'refunded' => ['amount' => round($byStatus[3]['amount'] ?? 0, 2), 'count' => $byStatus[3]['count'] ?? 0, 'label' => '已退款'],
                'rejected' => ['amount' => round($byStatus[4]['amount'] ?? 0, 2), 'count' => $byStatus[4]['count'] ?? 0, 'label' => '已拒绝'],
            ],
            'by_type' => [
                'user' => ['amount' => round($byType[1]['amount'] ?? 0, 2), 'count' => $byType[1]['count'] ?? 0, 'label' => '用户申请'],
                'admin' => ['amount' => round($byType[2]['amount'] ?? 0, 2), 'count' => $byType[2]['count'] ?? 0, 'label' => '管理员操作'],
                'system' => ['amount' => round($byType[3]['amount'] ?? 0, 2), 'count' => $byType[3]['count'] ?? 0, 'label' => '系统自动'],
            ],
        ];
    }

    /**
     * @notes 成本分析
     */
    public static function costAnalysis(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $costStats = CostRecord::getCostStats($startDate, $endDate);
        
        $totalIncome = Payment::whereBetweenTime('pay_time', strtotime($startDate), strtotime($endDate . ' 23:59:59'))
            ->where('pay_status', 1)
            ->sum('pay_amount');
        
        return [
            'total_cost' => round($costStats['total'], 2),
            'cost_rate' => $totalIncome > 0 ? round($costStats['total'] / $totalIncome * 100, 2) : 0,
            'by_type' => [
                'labor' => ['amount' => round($costStats['labor'], 2), 'label' => '人工成本'],
                'material' => ['amount' => round($costStats['material'], 2), 'label' => '物料成本'],
                'transport' => ['amount' => round($costStats['transport'], 2), 'label' => '交通成本'],
                'equipment' => ['amount' => round($costStats['equipment'], 2), 'label' => '设备成本'],
                'other' => ['amount' => round($costStats['other'], 2), 'label' => '其他成本'],
            ],
        ];
    }

    /**
     * @notes 利润分析
     */
    public static function profitAnalysis(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');
        
        $income = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->where('pay_status', 1)
            ->sum('pay_amount');
        
        $refund = Refund::whereBetweenTime('refund_time', $startTime, $endTime)
            ->where('refund_status', 3)
            ->sum('refund_amount');
        
        $cost = CostRecord::whereBetween('service_date', [$startDate, $endDate])
            ->where('status', CostRecord::STATUS_CONFIRMED)
            ->sum('cost_amount');
        
        $netIncome = $income - $refund;
        $grossProfit = $netIncome - $cost;
        
        return [
            'income' => round($income, 2),
            'refund' => round($refund, 2),
            'net_income' => round($netIncome, 2),
            'cost' => round($cost, 2),
            'gross_profit' => round($grossProfit, 2),
            'gross_profit_rate' => $netIncome > 0 ? round($grossProfit / $netIncome * 100, 2) : 0,
        ];
    }

    /**
     * @notes 日报列表
     */
    public static function dailyList(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        $page = intval($params['page'] ?? 1);
        $pageSize = intval($params['page_size'] ?? 20);
        
        $query = FinancialDaily::whereBetween('report_date', [$startDate, $endDate])
            ->order('report_date', 'desc');
        
        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();
        
        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * @notes 月报列表
     */
    public static function monthlyList(array $params): array
    {
        $year = intval($params['year'] ?? date('Y'));
        
        $list = FinancialMonthly::where('report_year', $year)
            ->order('report_month', 'desc')
            ->select()
            ->toArray();
        
        return [
            'year' => $year,
            'list' => $list,
        ];
    }

    /**
     * @notes 生成日报
     */
    public static function generateDaily(array $params)
    {
        try {
            $startDate = $params['start_date'] ?? date('Y-m-d');
            $endDate = $params['end_date'] ?? date('Y-m-d');
            
            $count = FinancialDaily::generateBatchReports($startDate, $endDate);
            return $count;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 生成月报
     */
    public static function generateMonthly(array $params)
    {
        try {
            $year = intval($params['year'] ?? date('Y'));
            $month = intval($params['month'] ?? date('n'));
            
            FinancialMonthly::generateMonthlyReport($year, $month);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 收入趋势
     */
    public static function incomeTrend(array $params): array
    {
        $type = $params['type'] ?? 'daily';
        
        if ($type === 'monthly') {
            $year = intval($params['year'] ?? date('Y'));
            return [
                'type' => 'monthly',
                'year' => $year,
                'data' => FinancialMonthly::getMonthlyTrend($year, 'total_income'),
            ];
        } else {
            $startDate = $params['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $params['end_date'] ?? date('Y-m-d');
            return [
                'type' => 'daily',
                'data' => FinancialDaily::getTrend($startDate, $endDate, 'total_income'),
            ];
        }
    }

    /**
     * @notes 导出日报
     */
    public static function exportDaily(array $params): array
    {
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $list = FinancialDaily::whereBetween('report_date', [$startDate, $endDate])
            ->order('report_date', 'asc')
            ->select()
            ->toArray();
        
        // 这里可以生成Excel文件，暂时返回数据
        return [
            'count' => count($list),
            'list' => $list,
        ];
    }
}
