<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 客户流失预警管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\crm;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\crm\CustomerLossWarningLists;
use app\adminapi\logic\crm\CustomerLossWarningLogic;

/**
 * 客户流失预警管理控制器
 * Class CustomerLossWarningController
 * @package app\adminapi\controller\crm
 */
class CustomerLossWarningController extends BaseAdminController
{
    /**
     * @notes 预警列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new CustomerLossWarningLists());
    }

    /**
     * @notes 预警详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = $this->request->get('id', 0);
        if ($id <= 0) {
            return $this->fail('请选择预警记录');
        }
        $result = CustomerLossWarningLogic::detail((int)$id);
        if ($result === null) {
            return $this->fail('预警记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 处理预警
     * @return \think\response\Json
     */
    public function handle()
    {
        $id = $this->request->post('id', 0);
        $remark = $this->request->post('remark', '');

        if ($id <= 0) {
            return $this->fail('请选择预警记录');
        }

        $result = CustomerLossWarningLogic::handle((int)$id, $remark);
        if (true === $result) {
            return $this->success('处理成功');
        }
        return $this->fail(CustomerLossWarningLogic::getError());
    }

    /**
     * @notes 忽略预警
     * @return \think\response\Json
     */
    public function ignore()
    {
        $id = $this->request->post('id', 0);
        $remark = $this->request->post('remark', '');

        if ($id <= 0) {
            return $this->fail('请选择预警记录');
        }

        $result = CustomerLossWarningLogic::ignore((int)$id, $remark);
        if (true === $result) {
            return $this->success('忽略成功');
        }
        return $this->fail(CustomerLossWarningLogic::getError());
    }

    /**
     * @notes 批量处理预警
     * @return \think\response\Json
     */
    public function batchProcess()
    {
        $warningIds = $this->request->post('warning_ids', []);
        $action = $this->request->post('action', 'handle');
        $remark = $this->request->post('remark', '');

        if (empty($warningIds) || !is_array($warningIds)) {
            return $this->fail('请选择预警记录');
        }

        if (!in_array($action, ['handle', 'ignore'])) {
            return $this->fail('操作类型不正确');
        }

        $result = CustomerLossWarningLogic::batchProcess($warningIds, $action, $remark);
        return $this->data($result);
    }

    /**
     * @notes 手动生成流失预警
     * @return \think\response\Json
     */
    public function generate()
    {
        $count = CustomerLossWarningLogic::generateWarnings();
        return $this->success("成功生成 {$count} 条预警");
    }

    /**
     * @notes 手动创建预警
     * @return \think\response\Json
     */
    public function create()
    {
        $customerId = $this->request->post('customer_id', 0);
        $warningType = $this->request->post('warning_type', 0);
        $warningLevel = $this->request->post('warning_level', 1);
        $reason = $this->request->post('reason', '');

        if ($customerId <= 0) {
            return $this->fail('请选择客户');
        }
        if ($warningType <= 0) {
            return $this->fail('请选择预警类型');
        }

        $result = CustomerLossWarningLogic::createWarning(
            (int)$customerId,
            (int)$warningType,
            (int)$warningLevel,
            $reason
        );
        if (true === $result) {
            return $this->success('创建成功');
        }
        return $this->fail(CustomerLossWarningLogic::getError());
    }

    /**
     * @notes 获取顾问待处理预警
     * @return \think\response\Json
     */
    public function advisorPending()
    {
        $advisorId = $this->request->get('advisor_id', 0);
        if ($advisorId <= 0) {
            return $this->fail('请指定顾问');
        }
        $result = CustomerLossWarningLogic::getAdvisorPendingWarnings((int)$advisorId);
        return $this->data($result);
    }

    /**
     * @notes 获取所有待处理预警
     * @return \think\response\Json
     */
    public function allPending()
    {
        $level = $this->request->get('level', 0);
        $result = CustomerLossWarningLogic::getAllPendingWarnings((int)$level);
        return $this->data($result);
    }

    /**
     * @notes 预警统计
     * @return \think\response\Json
     */
    public function stats()
    {
        $advisorId = $this->request->get('advisor_id', 0);
        $result = CustomerLossWarningLogic::getWarningStats((int)$advisorId);
        return $this->data($result);
    }

    /**
     * @notes 预警类型选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        return $this->data(CustomerLossWarningLogic::getTypeOptions());
    }

    /**
     * @notes 预警等级选项
     * @return \think\response\Json
     */
    public function levelOptions()
    {
        return $this->data(CustomerLossWarningLogic::getLevelOptions());
    }

    /**
     * @notes 处理状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        return $this->data(CustomerLossWarningLogic::getStatusOptions());
    }
}
