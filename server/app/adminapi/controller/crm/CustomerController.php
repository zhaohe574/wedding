<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\crm;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\crm\CustomerLists;
use app\adminapi\logic\crm\CustomerLogic;
use app\adminapi\validate\crm\CustomerValidate;

/**
 * 客户管理控制器
 * Class CustomerController
 * @package app\adminapi\controller\crm
 */
class CustomerController extends BaseAdminController
{
    /**
     * @notes 客户列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new CustomerLists());
    }

    /**
     * @notes 客户详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new CustomerValidate())->goCheck('detail');
        $detail = CustomerLogic::detail((int)$params['id'], $this->getCrmAdvisorScopeId(), $this->adminInfo);
        if ($detail === false) {
            return $this->fail(CustomerLogic::getError());
        }
        return $this->data($detail);
    }

    /**
     * @notes 编辑客户
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new CustomerValidate())->post()->goCheck('edit');
        $result = CustomerLogic::edit($params, $this->getCrmAdvisorScopeId(), $this->adminInfo);
        if ($result === true) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(CustomerLogic::getError());
    }

    /**
     * @notes 转移顾问
     * @return \think\response\Json
     */
    public function transferAdvisor()
    {
        $params = (new CustomerValidate())->post()->goCheck('transfer');
        $result = CustomerLogic::transferAdvisor($params, $this->adminId, $this->getCrmAdvisorScopeId(), $this->adminInfo);
        if ($result === true) {
            return $this->success('转移成功', [], 1, 1);
        }
        return $this->fail(CustomerLogic::getError());
    }

    /**
     * @notes 可承接顾问选项
     * @return \think\response\Json
     */
    public function advisorOptions()
    {
        return $this->data(CustomerLogic::advisorOptions($this->getCrmAdvisorScopeId()));
    }

    /**
     * @notes 客户选项
     * @return \think\response\Json
     */
    public function options()
    {
        return $this->data(CustomerLogic::options());
    }
}
