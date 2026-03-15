<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 财务报表控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\financial;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\financial\FinancialReportLogic;

/**
 * 财务报表控制器
 * Class FinancialReportController
 * @package app\adminapi\controller\financial
 */
class FinancialReportController extends BaseAdminController
{
    /**
     * @notes 财务概览
     */
    public function overview()
    {
        $params = $this->request->get();
        $result = FinancialReportLogic::overview($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 收入统计
     */
    public function incomeStats()
    {
        $params = $this->request->get();
        $result = FinancialReportLogic::incomeStats($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 支付方式分析
     */
    public function payWayAnalysis()
    {
        $params = $this->request->get();
        $result = FinancialReportLogic::payWayAnalysis($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 退款统计
     */
    public function refundStats()
    {
        $params = $this->request->get();
        $result = FinancialReportLogic::refundStats($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 成本分析
     */
    public function costAnalysis()
    {
        return $this->fail('成本分析已在精简版后台下线');
    }

    /**
     * @notes 利润分析
     */
    public function profitAnalysis()
    {
        return $this->fail('利润分析已在精简版后台下线');
    }

    /**
     * @notes 日报列表
     */
    public function dailyList()
    {
        return $this->fail('日报能力已在精简版后台下线');
    }

    /**
     * @notes 月报列表
     */
    public function monthlyList()
    {
        return $this->fail('月报能力已在精简版后台下线');
    }

    /**
     * @notes 生成日报
     */
    public function generateDaily()
    {
        return $this->fail('日报能力已在精简版后台下线');
    }

    /**
     * @notes 生成月报
     */
    public function generateMonthly()
    {
        return $this->fail('月报能力已在精简版后台下线');
    }

    /**
     * @notes 收入趋势
     */
    public function incomeTrend()
    {
        $params = $this->request->get();
        $result = FinancialReportLogic::incomeTrend($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 导出日报
     */
    public function exportDaily()
    {
        return $this->fail('日报导出已在精简版后台下线');
    }
}
