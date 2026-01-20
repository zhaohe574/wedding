<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 销售顾问管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\crm;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\crm\SalesAdvisorLists;
use app\adminapi\logic\crm\SalesAdvisorLogic;
use app\adminapi\validate\crm\SalesAdvisorValidate;

/**
 * 销售顾问管理控制器
 * Class SalesAdvisorController
 * @package app\adminapi\controller\crm
 */
class SalesAdvisorController extends BaseAdminController
{
    /**
     * @notes 顾问列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new SalesAdvisorLists());
    }

    /**
     * @notes 顾问详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new SalesAdvisorValidate())->goCheck('detail');
        $result = SalesAdvisorLogic::detail($params['id']);
        if ($result === null) {
            return $this->fail('顾问不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 添加顾问
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new SalesAdvisorValidate())->post()->goCheck('add');
        $result = SalesAdvisorLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功');
        }
        return $this->fail(SalesAdvisorLogic::getError());
    }

    /**
     * @notes 编辑顾问
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new SalesAdvisorValidate())->post()->goCheck('edit');
        $result = SalesAdvisorLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功');
        }
        return $this->fail(SalesAdvisorLogic::getError());
    }

    /**
     * @notes 删除顾问
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new SalesAdvisorValidate())->post()->goCheck('delete');
        $result = SalesAdvisorLogic::delete($params['id']);
        if (true === $result) {
            return $this->success('删除成功');
        }
        return $this->fail(SalesAdvisorLogic::getError());
    }

    /**
     * @notes 更新顾问状态
     * @return \think\response\Json
     */
    public function updateStatus()
    {
        $params = (new SalesAdvisorValidate())->post()->goCheck('updateStatus');
        $result = SalesAdvisorLogic::updateStatus($params['id'], $params['status']);
        if (true === $result) {
            return $this->success('更新成功');
        }
        return $this->fail(SalesAdvisorLogic::getError());
    }

    /**
     * @notes 转移客户
     * @return \think\response\Json
     */
    public function transferCustomers()
    {
        $fromAdvisorId = $this->request->post('from_advisor_id', 0);
        $toAdvisorId = $this->request->post('to_advisor_id', 0);
        $reason = $this->request->post('reason', '');

        if ($fromAdvisorId <= 0 || $toAdvisorId <= 0) {
            return $this->fail('请选择顾问');
        }

        if ($fromAdvisorId == $toAdvisorId) {
            return $this->fail('不能转移给同一个顾问');
        }

        $result = SalesAdvisorLogic::transferCustomers(
            (int)$fromAdvisorId,
            (int)$toAdvisorId,
            $this->adminId,
            $reason
        );
        
        if ($result['success'] > 0) {
            return $this->success("成功转移 {$result['success']} 个客户", $result);
        }
        return $this->fail(SalesAdvisorLogic::getError() ?: '转移失败');
    }

    /**
     * @notes 获取顾问业绩统计
     * @return \think\response\Json
     */
    public function performanceStats()
    {
        $advisorId = $this->request->get('advisor_id', 0);
        $startDate = $this->request->get('start_date', '');
        $endDate = $this->request->get('end_date', '');

        if ($advisorId <= 0) {
            return $this->fail('请指定顾问');
        }

        $result = SalesAdvisorLogic::getPerformanceStats((int)$advisorId, $startDate, $endDate);
        return $this->data($result);
    }

    /**
     * @notes 获取顾问客户列表
     * @return \think\response\Json
     */
    public function customers()
    {
        $advisorId = $this->request->get('advisor_id', 0);
        if ($advisorId <= 0) {
            return $this->fail('请指定顾问');
        }
        $result = SalesAdvisorLogic::getAdvisorCustomers((int)$advisorId);
        return $this->data($result);
    }

    /**
     * @notes 状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        return $this->data(SalesAdvisorLogic::getStatusOptions());
    }

    /**
     * @notes 顾问简单列表(下拉选择用)
     * @return \think\response\Json
     */
    public function simpleList()
    {
        $onlyAvailable = $this->request->get('only_available', 0);
        $result = SalesAdvisorLogic::getSimpleList((bool)$onlyAvailable);
        return $this->data($result);
    }
}
