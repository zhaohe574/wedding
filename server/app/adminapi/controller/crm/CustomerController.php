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
        $result = CustomerLogic::detail($params['id']);
        if ($result === null) {
            return $this->fail('客户不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 添加客户
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new CustomerValidate())->post()->goCheck('add');
        $result = CustomerLogic::add($params, $this->adminId);
        if (true === $result) {
            return $this->success('添加成功');
        }
        return $this->fail(CustomerLogic::getError());
    }

    /**
     * @notes 编辑客户
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new CustomerValidate())->post()->goCheck('edit');
        $result = CustomerLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功');
        }
        return $this->fail(CustomerLogic::getError());
    }

    /**
     * @notes 删除客户
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new CustomerValidate())->post()->goCheck('delete');
        $result = CustomerLogic::delete($params['id']);
        if (true === $result) {
            return $this->success('删除成功');
        }
        return $this->fail(CustomerLogic::getError());
    }

    /**
     * @notes 分配顾问
     * @return \think\response\Json
     */
    public function assign()
    {
        $params = (new CustomerValidate())->post()->goCheck('assign');
        $result = CustomerLogic::assignAdvisor(
            $params['id'],
            $params['advisor_id'],
            $this->adminId,
            $params['reason'] ?? ''
        );
        if (true === $result) {
            return $this->success('分配成功');
        }
        return $this->fail(CustomerLogic::getError());
    }

    /**
     * @notes 批量分配顾问
     * @return \think\response\Json
     */
    public function batchAssign()
    {
        $customerIds = $this->request->post('customer_ids', []);
        $advisorId = $this->request->post('advisor_id', 0);
        $reason = $this->request->post('reason', '');

        if (empty($customerIds) || !is_array($customerIds)) {
            return $this->fail('请选择客户');
        }
        if ($advisorId <= 0) {
            return $this->fail('请选择顾问');
        }

        $result = CustomerLogic::batchAssign($customerIds, (int)$advisorId, $this->adminId, $reason);
        return $this->data($result);
    }

    /**
     * @notes 标记流失
     * @return \think\response\Json
     */
    public function markLoss()
    {
        $params = (new CustomerValidate())->post()->goCheck('loss');
        $result = CustomerLogic::markAsLost($params['id'], $params['loss_reason'] ?? '');
        if (true === $result) {
            return $this->success('标记成功');
        }
        return $this->fail(CustomerLogic::getError());
    }

    /**
     * @notes 更新意向等级
     * @return \think\response\Json
     */
    public function updateIntention()
    {
        $params = (new CustomerValidate())->post()->goCheck('updateIntention');
        $result = CustomerLogic::updateIntention(
            $params['id'],
            $params['intention_level'],
            $params['intention_score'] ?? 0
        );
        if (true === $result) {
            return $this->success('更新成功');
        }
        return $this->fail(CustomerLogic::getError());
    }

    /**
     * @notes 客户统计概览
     * @return \think\response\Json
     */
    public function overview()
    {
        $advisorId = $this->request->get('advisor_id', 0);
        $result = CustomerLogic::getOverview((int)$advisorId);
        return $this->data($result);
    }

    /**
     * @notes 待跟进客户列表
     * @return \think\response\Json
     */
    public function pendingFollow()
    {
        $advisorId = $this->request->get('advisor_id', 0);
        if ($advisorId <= 0) {
            return $this->fail('请指定顾问');
        }
        $result = CustomerLogic::getPendingFollow((int)$advisorId);
        return $this->data($result);
    }

    /**
     * @notes 客户分配历史
     * @return \think\response\Json
     */
    public function assignHistory()
    {
        $params = (new CustomerValidate())->goCheck('detail');
        $result = CustomerLogic::getAssignHistory($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 可用顾问列表
     * @return \think\response\Json
     */
    public function availableAdvisors()
    {
        $result = CustomerLogic::getAvailableAdvisors();
        return $this->data($result);
    }

    /**
     * @notes 意向等级选项
     * @return \think\response\Json
     */
    public function intentionOptions()
    {
        return $this->data(\app\common\model\crm\Customer::getIntentionOptions());
    }

    /**
     * @notes 客户状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        return $this->data(\app\common\model\crm\Customer::getStatusOptions());
    }

    /**
     * @notes 来源渠道选项
     * @return \think\response\Json
     */
    public function sourceOptions()
    {
        return $this->data(\app\common\model\crm\Customer::getSourceOptions());
    }
}
