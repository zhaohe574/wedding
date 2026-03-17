<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端购物车控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

/**
 * 小程序端购物车控制器
 * Class CartController
 * @package app\api\controller
 */
class CartController extends BaseApiController
{
    /**
     * @notes 统一下线响应
     * @return \think\response\Json
     */
    private function offlineResponse()
    {
        return $this->fail('购物车功能已下线，请直接下单');
    }

    public function lists()
    {
        return $this->offlineResponse();
    }

    public function add()
    {
        return $this->offlineResponse();
    }

    public function update()
    {
        return $this->offlineResponse();
    }

    public function delete()
    {
        return $this->offlineResponse();
    }

    public function batchDelete()
    {
        return $this->offlineResponse();
    }

    public function toggleSelect()
    {
        return $this->offlineResponse();
    }

    public function selectAll()
    {
        return $this->offlineResponse();
    }

    public function calculate()
    {
        return $this->offlineResponse();
    }

    public function checkConflicts()
    {
        return $this->offlineResponse();
    }

    public function clear()
    {
        return $this->offlineResponse();
    }

    public function count()
    {
        return $this->offlineResponse();
    }

    public function generateShareCode()
    {
        return $this->offlineResponse();
    }

    public function getByShareCode()
    {
        return $this->offlineResponse();
    }

    public function savePlan()
    {
        return $this->offlineResponse();
    }

    public function myPlans()
    {
        return $this->offlineResponse();
    }

    public function planDetail()
    {
        return $this->offlineResponse();
    }

    public function deletePlan()
    {
        return $this->offlineResponse();
    }

    public function setDefaultPlan()
    {
        return $this->offlineResponse();
    }

    public function cancelDefaultPlan()
    {
        return $this->offlineResponse();
    }

    public function copyPlanByShareCode()
    {
        return $this->offlineResponse();
    }

    public function savePlanByShareCode()
    {
        return $this->offlineResponse();
    }

    public function applyPlanToCart()
    {
        return $this->offlineResponse();
    }

    public function comparePlans()
    {
        return $this->offlineResponse();
    }

    public function generatePlanShareCode()
    {
        return $this->offlineResponse();
    }

    public function getPlanByShareCode()
    {
        return $this->offlineResponse();
    }
}
