<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 财务日报模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use app\common\model\order\Order;
use app\common\model\order\Payment;
use app\common\model\order\Refund;
use think\facade\Db;

/**
 * 财务日报模型
 * Class FinancialDaily
 * @package app\common\model\financial
 */
class FinancialDaily extends BaseModel
{
    protected $name = 'financial_daily';

    /**
     * @notes 生成指定日期的日报
     */
    public static function generateDailyReport(string $date): self
    {
        $startTime = strtotime($date . ' 00:00:00');
        $endTime = strtotime($date . ' 23:59:59');
        
        // 查询是否已存在
        $report = self::where('report_date', $date)->find();
        if (!$report) {
            $report = new self();
            $report->report_date = $date;
        }
        
        // 订单统计
        $orderStats = Order::whereBetweenTime('create_time', $startTime, $endTime)
            ->field([
                'COUNT(*) as order_count',
                'SUM(CASE WHEN pay_status > 0 THEN 1 ELSE 0 END) as paid_order_count',
            ])
            ->find();
        
        $report->order_count = $orderStats['order_count'] ?? 0;
        $report->paid_order_count = $orderStats['paid_order_count'] ?? 0;
        
        // 收入统计
        $paymentStats = Payment::whereBetweenTime('pay_time', $startTime, $endTime)
            ->where('pay_status', 1)
            ->field([
                'SUM(pay_amount) as total_income',
                'SUM(CASE WHEN pay_type = 1 THEN pay_amount ELSE 0 END) as deposit_income',
                'SUM(CASE WHEN pay_type = 2 THEN pay_amount ELSE 0 END) as balance_income',
                'SUM(CASE WHEN pay_way = 1 THEN pay_amount ELSE 0 END) as wechat_income',
                'SUM(CASE WHEN pay_way = 2 THEN pay_amount ELSE 0 END) as alipay_income',
                'SUM(CASE WHEN pay_way = 3 THEN pay_amount ELSE 0 END) as balance_pay_income',
                'SUM(CASE WHEN pay_way = 4 THEN pay_amount ELSE 0 END) as offline_income',
            ])
            ->find();
        
        $report->total_income = $paymentStats['total_income'] ?? 0;
        $report->deposit_income = $paymentStats['deposit_income'] ?? 0;
        $report->balance_income = $paymentStats['balance_income'] ?? 0;
        $report->wechat_income = $paymentStats['wechat_income'] ?? 0;
        $report->alipay_income = $paymentStats['alipay_income'] ?? 0;
        $report->balance_pay_income = $paymentStats['balance_pay_income'] ?? 0;
        $report->offline_income = $paymentStats['offline_income'] ?? 0;
        
        // 退款统计
        $refundStats = Refund::whereBetweenTime('refund_time', $startTime, $endTime)
            ->where('refund_status', 3)
            ->field([
                'COUNT(*) as refund_order_count',
                'SUM(refund_amount) as total_refund',
            ])
            ->find();
        
        $report->refund_order_count = $refundStats['refund_order_count'] ?? 0;
        $report->total_refund = $refundStats['total_refund'] ?? 0;
        
        // 成本统计
        $costStats = CostRecord::where('service_date', $date)
            ->where('status', CostRecord::STATUS_CONFIRMED)
            ->field([
                'SUM(cost_amount) as total_cost',
                'SUM(CASE WHEN cost_type = 1 THEN cost_amount ELSE 0 END) as staff_cost',
                'SUM(CASE WHEN cost_type = 2 THEN cost_amount ELSE 0 END) as material_cost',
                'SUM(CASE WHEN cost_type NOT IN (1,2) THEN cost_amount ELSE 0 END) as other_cost',
            ])
            ->find();
        
        $report->total_cost = $costStats['total_cost'] ?? 0;
        $report->staff_cost = $costStats['staff_cost'] ?? 0;
        $report->material_cost = $costStats['material_cost'] ?? 0;
        $report->other_cost = $costStats['other_cost'] ?? 0;
        
        // 人员结算统计
        $settlementStats = StaffSettlement::whereBetweenTime('settle_time', $startTime, $endTime)
            ->where('status', StaffSettlement::STATUS_SETTLED)
            ->sum('actual_amount');
        
        $report->total_settlement = $settlementStats ?? 0;
        
        // 计算利润
        $report->platform_income = $report->total_income - $report->total_refund - $report->total_settlement;
        $report->gross_profit = $report->total_income - $report->total_refund - $report->total_cost;
        $report->net_profit = $report->gross_profit - $report->total_settlement;
        $report->profit_rate = $report->total_income > 0 
            ? round($report->net_profit / $report->total_income * 100, 2) 
            : 0;
        
        // 用户统计
        $report->new_user_count = Db::name('user')
            ->whereBetweenTime('create_time', $startTime, $endTime)
            ->count();
        
        $report->active_user_count = Order::whereBetweenTime('create_time', $startTime, $endTime)
            ->group('user_id')
            ->count();
        
        $report->save();
        return $report;
    }

    /**
     * @notes 批量生成日报
     */
    public static function generateBatchReports(string $startDate, string $endDate): int
    {
        $count = 0;
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        while ($current <= $end) {
            self::generateDailyReport(date('Y-m-d', $current));
            $current = strtotime('+1 day', $current);
            $count++;
        }
        
        return $count;
    }

    /**
     * @notes 获取日期范围汇总
     */
    public static function getSummary(string $startDate, string $endDate): array
    {
        return self::whereBetween('report_date', [$startDate, $endDate])
            ->field([
                'SUM(order_count) as order_count',
                'SUM(paid_order_count) as paid_order_count',
                'SUM(refund_order_count) as refund_order_count',
                'SUM(total_income) as total_income',
                'SUM(total_refund) as total_refund',
                'SUM(total_cost) as total_cost',
                'SUM(total_settlement) as total_settlement',
                'SUM(net_profit) as net_profit',
                'SUM(new_user_count) as new_user_count',
            ])
            ->find()
            ->toArray();
    }

    /**
     * @notes 获取趋势数据
     */
    public static function getTrend(string $startDate, string $endDate, string $field = 'total_income'): array
    {
        return self::whereBetween('report_date', [$startDate, $endDate])
            ->order('report_date', 'asc')
            ->column($field, 'report_date');
    }
}
