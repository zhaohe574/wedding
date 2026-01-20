<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 财务月报模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\financial;

use app\common\model\BaseModel;
use think\facade\Db;

/**
 * 财务月报模型
 * Class FinancialMonthly
 * @package app\common\model\financial
 */
class FinancialMonthly extends BaseModel
{
    protected $name = 'financial_monthly';

    /**
     * @notes 生成指定月份的月报
     */
    public static function generateMonthlyReport(int $year, int $month): self
    {
        // 查询是否已存在
        $report = self::where('report_year', $year)->where('report_month', $month)->find();
        if (!$report) {
            $report = new self();
            $report->report_year = $year;
            $report->report_month = $month;
        }
        
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // 从日报汇总数据
        $dailyStats = FinancialDaily::whereBetween('report_date', [$startDate, $endDate])
            ->field([
                'SUM(order_count) as order_count',
                'SUM(paid_order_count) as paid_order_count',
                'SUM(refund_order_count) as refund_order_count',
                'SUM(total_income) as total_income',
                'SUM(wechat_income) as wechat_income',
                'SUM(alipay_income) as alipay_income',
                'SUM(balance_pay_income) as balance_pay_income',
                'SUM(offline_income) as offline_income',
                'SUM(total_refund) as total_refund',
                'SUM(total_cost) as total_cost',
                'SUM(staff_cost) as staff_cost',
                'SUM(material_cost) as material_cost',
                'SUM(other_cost) as other_cost',
                'SUM(total_settlement) as total_settlement',
                'SUM(platform_income) as platform_income',
                'SUM(gross_profit) as gross_profit',
                'SUM(net_profit) as net_profit',
                'SUM(new_user_count) as new_user_count',
                'SUM(active_user_count) as active_user_count',
            ])
            ->find();
        
        // 赋值基本数据
        $report->order_count = $dailyStats['order_count'] ?? 0;
        $report->paid_order_count = $dailyStats['paid_order_count'] ?? 0;
        $report->refund_order_count = $dailyStats['refund_order_count'] ?? 0;
        $report->total_income = $dailyStats['total_income'] ?? 0;
        $report->wechat_income = $dailyStats['wechat_income'] ?? 0;
        $report->alipay_income = $dailyStats['alipay_income'] ?? 0;
        $report->balance_pay_income = $dailyStats['balance_pay_income'] ?? 0;
        $report->offline_income = $dailyStats['offline_income'] ?? 0;
        $report->total_refund = $dailyStats['total_refund'] ?? 0;
        $report->total_cost = $dailyStats['total_cost'] ?? 0;
        $report->staff_cost = $dailyStats['staff_cost'] ?? 0;
        $report->material_cost = $dailyStats['material_cost'] ?? 0;
        $report->other_cost = $dailyStats['other_cost'] ?? 0;
        $report->total_settlement = $dailyStats['total_settlement'] ?? 0;
        $report->platform_income = $dailyStats['platform_income'] ?? 0;
        $report->gross_profit = $dailyStats['gross_profit'] ?? 0;
        $report->net_profit = $dailyStats['net_profit'] ?? 0;
        $report->new_user_count = $dailyStats['new_user_count'] ?? 0;
        $report->active_user_count = $dailyStats['active_user_count'] ?? 0;
        
        // 完成订单数
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');
        $report->complete_order_count = Db::name('order')
            ->whereBetweenTime('complete_time', $startTime, $endTime)
            ->where('order_status', 3)
            ->count();
        
        // 计算平均订单金额
        $report->avg_order_amount = $report->paid_order_count > 0 
            ? round($report->total_income / $report->paid_order_count, 2) 
            : 0;
        
        // 计算利润率
        $report->profit_rate = $report->total_income > 0 
            ? round($report->net_profit / $report->total_income * 100, 2) 
            : 0;
        
        // 计算转化率 (支付订单/总订单)
        $report->conversion_rate = $report->order_count > 0 
            ? round($report->paid_order_count / $report->order_count * 100, 2) 
            : 0;
        
        // 计算同比增长率 (与去年同月比较)
        $lastYearReport = self::where('report_year', $year - 1)
            ->where('report_month', $month)
            ->find();
        if ($lastYearReport && $lastYearReport->total_income > 0) {
            $report->yoy_growth_rate = round(
                ($report->total_income - $lastYearReport->total_income) / $lastYearReport->total_income * 100, 
                2
            );
        } else {
            $report->yoy_growth_rate = 0;
        }
        
        // 计算环比增长率 (与上月比较)
        $lastMonth = $month - 1;
        $lastYear = $year;
        if ($lastMonth == 0) {
            $lastMonth = 12;
            $lastYear = $year - 1;
        }
        $lastMonthReport = self::where('report_year', $lastYear)
            ->where('report_month', $lastMonth)
            ->find();
        if ($lastMonthReport && $lastMonthReport->total_income > 0) {
            $report->mom_growth_rate = round(
                ($report->total_income - $lastMonthReport->total_income) / $lastMonthReport->total_income * 100, 
                2
            );
        } else {
            $report->mom_growth_rate = 0;
        }
        
        $report->save();
        return $report;
    }

    /**
     * @notes 获取年度汇总
     */
    public static function getYearSummary(int $year): array
    {
        return self::where('report_year', $year)
            ->field([
                'SUM(order_count) as order_count',
                'SUM(paid_order_count) as paid_order_count',
                'SUM(complete_order_count) as complete_order_count',
                'SUM(refund_order_count) as refund_order_count',
                'SUM(total_income) as total_income',
                'SUM(total_refund) as total_refund',
                'SUM(total_cost) as total_cost',
                'SUM(net_profit) as net_profit',
                'SUM(new_user_count) as new_user_count',
            ])
            ->find()
            ->toArray();
    }

    /**
     * @notes 获取月度趋势
     */
    public static function getMonthlyTrend(int $year, string $field = 'total_income'): array
    {
        $data = self::where('report_year', $year)
            ->order('report_month', 'asc')
            ->column($field, 'report_month');
        
        // 补全12个月的数据
        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $result[$i] = $data[$i] ?? 0;
        }
        return $result;
    }

    /**
     * @notes 获取近N个月的趋势
     */
    public static function getRecentMonthsTrend(int $months = 12, string $field = 'total_income'): array
    {
        $result = [];
        $current = time();
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = strtotime("-{$i} months", $current);
            $year = (int) date('Y', $date);
            $month = (int) date('n', $date);
            $key = sprintf('%04d-%02d', $year, $month);
            
            $report = self::where('report_year', $year)
                ->where('report_month', $month)
                ->find();
            
            $result[$key] = $report ? $report->$field : 0;
        }
        
        return $result;
    }
}
