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
        $result = SalesAdvisorLogic::detail((int)$params['id']);
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
        if ($result === true) {
            return $this->success('添加成功', [], 1, 1);
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
        if ($result === true) {
            return $this->success('编辑成功', [], 1, 1);
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
        $result = SalesAdvisorLogic::delete($params);
        if ($result === true) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(SalesAdvisorLogic::getError());
    }

    /**
     * @notes 修改顾问状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new SalesAdvisorValidate())->post()->goCheck('status');
        $result = SalesAdvisorLogic::changeStatus($params);
        if ($result === true) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(SalesAdvisorLogic::getError());
    }

    /**
     * @notes 校准客户数
     * @return \think\response\Json
     */
    public function syncCustomerCount()
    {
        $params = (new SalesAdvisorValidate())->post()->goCheck('sync');
        $result = SalesAdvisorLogic::syncCustomerCount($params);
        if ($result === true) {
            return $this->success('校准成功', [], 1, 1);
        }
        return $this->fail(SalesAdvisorLogic::getError());
    }

    /**
     * @notes 状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        return $this->data(SalesAdvisorLogic::statusOptions());
    }
}
