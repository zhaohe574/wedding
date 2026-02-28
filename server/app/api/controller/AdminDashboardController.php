<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 移动端管理员看板控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\AdminDashboardLogic;

/**
 * 移动端管理员看板控制器
 * Class AdminDashboardController
 * @package app\api\controller
 */
class AdminDashboardController extends BaseApiController
{
    /**
     * @notes 财务概览
     */
    public function overview()
    {
        if (!$this->checkAccess()) {
            return $this->fail(AdminDashboardLogic::getError());
        }

        $params = $this->request->get();
        $result = AdminDashboardLogic::overview($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 收入趋势
     */
    public function incomeTrend()
    {
        if (!$this->checkAccess()) {
            return $this->fail(AdminDashboardLogic::getError());
        }

        $params = $this->request->get();
        $result = AdminDashboardLogic::incomeTrend($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 订单统计
     */
    public function orderStats()
    {
        if (!$this->checkAccess()) {
            return $this->fail(AdminDashboardLogic::getError());
        }

        $params = $this->request->get();
        $result = AdminDashboardLogic::orderStats($params);
        return $this->success('获取成功', $result);
    }

    /**
     * @notes 校验访问权限
     */
    protected function checkAccess(): bool
    {
        return AdminDashboardLogic::canAccess($this->userId);
    }
}
