<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 移动端经营看板控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\adminapi\logic\dashboard\DashboardLogic;
use app\common\service\DashboardAccessService;

/**
 * 移动端经营看板控制器
 * 说明：小程序用户没有后台 token，无法直接访问 adminapi。
 * 本控制器只做「小程序用户 -> 绑定后台管理员 -> RBAC 节点」的鉴权桥接，
 * 数据实现统一在 app\adminapi\logic\dashboard\DashboardLogic，不再维护第二份财务统计逻辑。
 * 权限来源：la_admin.user_id 绑定关系 + la_system_menu 节点授权，未登记节点默认拒绝。
 * Class DashboardController
 * @package app\api\controller
 */
class DashboardController extends BaseApiController
{
    /**
     * @notes 当前用户是否可访问经营看板（供移动端控制入口显隐）
     */
    public function access()
    {
        $canView = DashboardAccessService::canAccess($this->userId, DashboardAccessService::NODE_OVERVIEW);
        return $this->data(['can_view' => $canView]);
    }

    /**
     * @notes 财务概览
     */
    public function overview()
    {
        if (!$this->checkAccess(DashboardAccessService::NODE_OVERVIEW)) {
            return $this->denied();
        }

        return $this->data(DashboardLogic::overview($this->request->get()));
    }

    /**
     * @notes 收入趋势
     */
    public function incomeTrend()
    {
        if (!$this->checkAccess(DashboardAccessService::NODE_INCOME_TREND)) {
            return $this->denied();
        }

        return $this->data(DashboardLogic::incomeTrend($this->request->get()));
    }

    /**
     * @notes 订单统计
     */
    public function orderStats()
    {
        if (!$this->checkAccess(DashboardAccessService::NODE_ORDER_STATS)) {
            return $this->denied();
        }

        return $this->data(DashboardLogic::orderStats($this->request->get()));
    }

    /**
     * @notes 团队总览
     */
    public function teamOverview()
    {
        if (!$this->checkAccess(DashboardAccessService::NODE_TEAM_OVERVIEW)) {
            return $this->denied();
        }

        return $this->data(DashboardLogic::teamOverview($this->request->get()));
    }

    /**
     * @notes 校验当前用户是否具备指定看板节点权限
     */
    protected function checkAccess(string $node): bool
    {
        return DashboardAccessService::canAccess($this->userId, $node);
    }

    /**
     * @notes 无权限返回
     */
    protected function denied()
    {
        if (!DashboardAccessService::isFeatureEnabled()) {
            return $this->fail('经营看板已关闭');
        }

        return $this->fail('暂无权限访问经营看板');
    }
}
