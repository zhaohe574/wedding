<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 财务报表逻辑层
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\logic\financial;

use app\common\logic\BaseLogic;
use app\common\model\order\Order;
use app\common\model\order\Payment;
use app\common\model\order\Refund;
use app\common\model\financial\FinancialFlow;

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
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->where('collection_owner', Payment::COLLECTION_PLATFORM)
            ->sum('pay_amount');
        
        $currentRefund = FinancialFlow::whereBetween('create_time', [$startTime, $endTime])
            ->where('biz_type', FinancialFlow::BIZ_TYPE_ORDER_REFUND)
            ->sum('amount');

        $currentOrders = Order::whereBetweenTime('create_time', $startTime, $endTime)
            ->where('pay_status', '>', 0)
            ->count();

        // 收款均值的分子和分母必须使用相同付款时间、收款归属及状态。
        $receiptOrders = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->where('collection_owner', Payment::COLLECTION_PLATFORM)
            ->count('DISTINCT order_id');
        
        // 计算周期天数
        $days = (strtotime($endDate) - strtotime($startDate)) / 86400 + 1;
        
        // 上期数据
        $lastStartDate = date('Y-m-d', strtotime("-{$days} days", $startTime));
        $lastEndDate = date('Y-m-d', strtotime('-1 day', $startTime));
        $lastStartTime = strtotime($lastStartDate);
        $lastEndTime = strtotime($lastEndDate . ' 23:59:59');
        
        $lastIncome = Payment::whereBetweenTime('pay_time', $lastStartTime, $lastEndTime)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->where('collection_owner', Payment::COLLECTION_PLATFORM)
            ->sum('pay_amount');
        
        $lastRefund = FinancialFlow::whereBetween('create_time', [$lastStartTime, $lastEndTime])
            ->where('biz_type', FinancialFlow::BIZ_TYPE_ORDER_REFUND)
            ->sum('amount');
        
        // 计算增长率
        $incomeGrowth = $lastIncome > 0 ? round(($currentIncome - $lastIncome) / $lastIncome * 100, 2) : 0;
        $refundGrowth = $lastRefund > 0 ? round(($currentRefund - $lastRefund) / $lastRefund * 100, 2) : 0;
        
        // 净收入
        $netIncome = $currentIncome - $currentRefund;

        return [
            'total_income' => round($currentIncome, 2),
            'total_refund' => round($currentRefund, 2),
            'net_income' => round($netIncome, 2),
            'order_count' => $currentOrders,
            'receipt_order_count' => $receiptOrders,
            'avg_order_amount' => $receiptOrders > 0 ? round($currentIncome / $receiptOrders, 2) : 0,
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
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->where('collection_owner', Payment::COLLECTION_PLATFORM)
            ->group('pay_type')
            ->column('SUM(pay_amount) as amount, COUNT(*) as count', 'pay_type');
        
        // 按支付方式统计
        $byPayWay = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->where('collection_owner', Payment::COLLECTION_PLATFORM)
            ->group('pay_way')
            ->column('SUM(pay_amount) as amount, COUNT(*) as count', 'pay_way');
        
        $total = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->where('collection_owner', Payment::COLLECTION_PLATFORM)
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
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->where('collection_owner', Payment::COLLECTION_PLATFORM)
            ->sum('pay_amount');
        
        $byPayWay = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->where('collection_owner', Payment::COLLECTION_PLATFORM)
            ->group('pay_way')
            ->column('SUM(pay_amount) as amount, COUNT(*) as count', 'pay_way');
        
        $payWayLabels = [
            1 => '微信支付',
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
        
        $totalRefund = FinancialFlow::whereBetween('create_time', [$startTime, $endTime])
            ->where('biz_type', FinancialFlow::BIZ_TYPE_ORDER_REFUND)
            ->sum('amount');
        
        $totalIncome = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->where('collection_owner', Payment::COLLECTION_PLATFORM)
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








    public static function incomeTrend(array $params): array
    {
        $monthly = ($params['type'] ?? 'daily') === 'monthly';
        $year = max(2000, min(2100, (int)($params['year'] ?? date('Y'))));
        $start = $monthly ? $year . '-01-01' : (string)($params['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
        $end = $monthly ? $year . '-12-31' : (string)($params['end_date'] ?? date('Y-m-d'));
        $startTime = strtotime($start);
        $endTime = strtotime($end . ' 23:59:59');
        if (!$startTime || !$endTime || $endTime < $startTime || $endTime - $startTime > 366 * 86400) {
            throw new \RuntimeException('收入趋势日期范围无效，最多查询一年');
        }
        $format = $monthly ? '%m' : '%Y-%m-%d';
        $values = Payment::whereIn('pay_status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->where('collection_owner', Payment::COLLECTION_PLATFORM)
            ->whereBetween('pay_time', [$startTime, $endTime])
            ->field("DATE_FORMAT(FROM_UNIXTIME(pay_time), '" . $format . "') AS period, SUM(pay_amount) AS amount")
            ->group('period')->select()->column('amount', 'period');
        $data = [];
        for ($time = $startTime; $time <= $endTime; $time = strtotime($monthly ? '+1 month' : '+1 day', $time)) {
            $key = date($monthly ? 'm' : 'Y-m-d', $time);
            $data[$monthly ? (int)$key : $key] = round((float)($values[$key] ?? 0), 2);
        }
        return ['type' => $monthly ? 'monthly' : 'daily', 'year' => $year, 'data' => $data];
    }
}
