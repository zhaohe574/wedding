<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 经营看板控制器（后台 RBAC 侧）
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\dashboard;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\dashboard\DashboardLogic;

/**
 * 经营看板控制器
 * 权限节点：dashboard.dashboard/overview、incomeTrend、orderStats、teamOverview
 * Class DashboardController
 * @package app\adminapi\controller\dashboard
 */
class DashboardController extends BaseAdminController
{
    /**
     * @notes 财务概览
     */
    public function overview()
    {
        $result = DashboardLogic::overview($this->request->get());
        return $this->data($result);
    }

    /**
     * @notes 收入趋势
     */
    public function incomeTrend()
    {
        $result = DashboardLogic::incomeTrend($this->request->get());
        return $this->data($result);
    }

    /**
     * @notes 订单统计
     */
    public function orderStats()
    {
        $result = DashboardLogic::orderStats($this->request->get());
        return $this->data($result);
    }

    /**
     * @notes 团队总览
     */
    public function teamOverview()
    {
        $result = DashboardLogic::teamOverview($this->request->get());
        return $this->data($result);
    }
}
