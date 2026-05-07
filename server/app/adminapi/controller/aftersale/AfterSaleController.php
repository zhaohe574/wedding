<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 售后工单控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\aftersale;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\aftersale\AfterSaleLogic;
use app\adminapi\lists\aftersale\TicketLists;
use app\adminapi\lists\aftersale\ComplaintLists;
use app\adminapi\lists\aftersale\CallbackLists;
use app\adminapi\lists\aftersale\MyTicketLists;
use app\adminapi\lists\aftersale\MyComplaintLists;
use app\adminapi\lists\aftersale\MyCallbackLists;
use app\adminapi\validate\aftersale\AfterSaleValidate;
use app\common\service\StaffService;

/**
 * 售后工单控制器
 * Class AfterSaleController
 * @package app\adminapi\controller\aftersale
 */
class AfterSaleController extends BaseAdminController
{
    /**
     * @notes 获取服务人员中心数据范围
     * @return int
     */
    protected function getRequiredStaffScopeId(): int
    {
        return StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
    }

    // ==================== 工单管理 ====================

    /**
     * @notes 工单列表
     * @return \think\response\Json
     */
    public function ticketLists()
    {
        return $this->dataLists(new TicketLists());
    }

    /**
     * @notes 工单详情
     * @return \think\response\Json
     */
    public function ticketDetail()
    {
        $params = (new AfterSaleValidate())->get()->goCheck('ticketDetail');
        $result = AfterSaleLogic::getTicketDetail((int)$params['id']);
        return $this->data($result);
    }

    /**
     * @notes 创建工单
     * @return \think\response\Json
     */
    public function createTicket()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('createTicket');
        $params['source'] = 2;  // 后台创建
        $result = AfterSaleLogic::createTicket($params);
        if ($result === true) {
            return $this->success('创建成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 分配工单
     * @return \think\response\Json
     */
    public function assignTicket()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('assignTicket');
        $result = AfterSaleLogic::assignTicket((int)$params['id'], (int)$params['admin_id'], (int)$this->adminId);
        if ($result === true) {
            return $this->success('分配成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 处理工单
     * @return \think\response\Json
     */
    public function handleTicket()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('handleTicket');
        $result = AfterSaleLogic::handleTicket((int)$params['id'], (int)$this->adminId, $params['result'], $params['images'] ?? []);
        if ($result === true) {
            return $this->success('处理成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 关闭工单
     * @return \think\response\Json
     */
    public function closeTicket()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('closeTicket');
        $result = AfterSaleLogic::closeTicket((int)$params['id'], (int)$this->adminId, $params['reason']);
        if ($result === true) {
            return $this->success('关闭成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 升级工单
     * @return \think\response\Json
     */
    public function escalateTicket()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('ticketDetail');
        $result = AfterSaleLogic::escalateTicket((int)$params['id'], (int)$this->adminId);
        if ($result === true) {
            return $this->success('升级成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 工单日志
     * @return \think\response\Json
     */
    public function ticketLogs()
    {
        $params = (new AfterSaleValidate())->get()->goCheck('ticketDetail');
        $result = AfterSaleLogic::getTicketLogs((int)$params['id']);
        return $this->data($result);
    }

    // ==================== 服务人员中心-我的工单 ====================

    /**
     * @notes 我的工单列表
     * @return \think\response\Json
     */
    public function myTicketLists()
    {
        if ($this->getRequiredStaffScopeId() <= 0) {
            return $this->failRequiredStaffScope();
        }
        return $this->dataLists(new MyTicketLists());
    }

    /**
     * @notes 我的工单详情
     * @return \think\response\Json
     */
    public function myTicketDetail()
    {
        if ($this->getRequiredStaffScopeId() <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new AfterSaleValidate())->get()->goCheck('ticketDetail');
        $result = AfterSaleLogic::getMyTicketDetail((int)$params['id'], (int)$this->adminId);
        return $this->data($result);
    }

    /**
     * @notes 处理我的工单
     * @return \think\response\Json
     */
    public function myHandleTicket()
    {
        if ($this->getRequiredStaffScopeId() <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new AfterSaleValidate())->post()->goCheck('handleTicket');
        $result = AfterSaleLogic::handleMyTicket((int)$params['id'], (int)$this->adminId, $params['result'], $params['images'] ?? []);
        if ($result === true) {
            return $this->success('处理成功');
        }
        return $this->fail($result);
    }

    // ==================== 投诉管理 ====================

    /**
     * @notes 投诉列表
     * @return \think\response\Json
     */
    public function complaintLists()
    {
        return $this->dataLists(new ComplaintLists());
    }

    /**
     * @notes 投诉详情
     * @return \think\response\Json
     */
    public function complaintDetail()
    {
        $params = (new AfterSaleValidate())->get()->goCheck('complaintDetail');
        $result = AfterSaleLogic::getComplaintDetail((int)$params['id']);
        return $this->data($result);
    }

    /**
     * @notes 处理投诉
     * @return \think\response\Json
     */
    public function handleComplaint()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('handleComplaint');
        $result = AfterSaleLogic::handleComplaint((int)$params['id'], (int)$this->adminId, $params);
        if ($result === true) {
            return $this->success('处理成功');
        }
        return $this->fail($result);
    }

    // ==================== 服务人员中心-我的投诉 ====================

    /**
     * @notes 我的投诉列表
     * @return \think\response\Json
     */
    public function myComplaintLists()
    {
        if ($this->getRequiredStaffScopeId() <= 0) {
            return $this->failRequiredStaffScope();
        }
        return $this->dataLists(new MyComplaintLists());
    }

    /**
     * @notes 我的投诉详情
     * @return \think\response\Json
     */
    public function myComplaintDetail()
    {
        $staffId = $this->getRequiredStaffScopeId();
        if ($staffId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new AfterSaleValidate())->get()->goCheck('complaintDetail');
        $result = AfterSaleLogic::getMyComplaintDetail((int)$params['id'], $staffId);
        return $this->data($result);
    }

    // ==================== 回访管理 ====================

    /**
     * @notes 回访列表
     * @return \think\response\Json
     */
    public function callbackLists()
    {
        return $this->dataLists(new CallbackLists());
    }

    /**
     * @notes 回访详情
     * @return \think\response\Json
     */
    public function callbackDetail()
    {
        $params = (new AfterSaleValidate())->get()->goCheck('callbackDetail');
        $result = AfterSaleLogic::getCallbackDetail((int)$params['id']);
        return $this->data($result);
    }

    /**
     * @notes 创建回访任务
     * @return \think\response\Json
     */
    public function createCallback()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('createCallback');
        $result = AfterSaleLogic::createCallback($params);
        if ($result === true) {
            return $this->success('创建成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 完成回访
     * @return \think\response\Json
     */
    public function completeCallback()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('completeCallback');
        $result = AfterSaleLogic::completeCallback((int)$params['id'], (int)$this->adminId, $params);
        if ($result === true) {
            return $this->success('回访完成');
        }
        return $this->fail($result);
    }

    /**
     * @notes 标记无法联系
     * @return \think\response\Json
     */
    public function markUnreachable()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('callbackDetail');
        $result = AfterSaleLogic::markUnreachable((int)$params['id'], (int)$this->adminId);
        if ($result === true) {
            return $this->success('标记成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 问题升级
     * @return \think\response\Json
     */
    public function escalateProblem()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('callbackDetail');
        $result = AfterSaleLogic::escalateProblem((int)$params['id'], (int)$this->adminId);
        if (is_array($result)) {
            return $this->success('升级成功', ['ticket_id' => $result['ticket_id']]);
        }
        return $this->fail($result);
    }

    // ==================== 服务人员中心-我的回访 ====================

    /**
     * @notes 我的回访列表
     * @return \think\response\Json
     */
    public function myCallbackLists()
    {
        if ($this->getRequiredStaffScopeId() <= 0) {
            return $this->failRequiredStaffScope();
        }
        return $this->dataLists(new MyCallbackLists());
    }

    /**
     * @notes 我的回访详情
     * @return \think\response\Json
     */
    public function myCallbackDetail()
    {
        $staffId = $this->getRequiredStaffScopeId();
        if ($staffId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new AfterSaleValidate())->get()->goCheck('callbackDetail');
        $result = AfterSaleLogic::getMyCallbackDetail((int)$params['id'], $staffId);
        return $this->data($result);
    }

    /**
     * @notes 完成我的回访
     * @return \think\response\Json
     */
    public function myCompleteCallback()
    {
        $staffId = $this->getRequiredStaffScopeId();
        if ($staffId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new AfterSaleValidate())->post()->goCheck('completeCallback');
        $result = AfterSaleLogic::completeMyCallback((int)$params['id'], (int)$this->adminId, $staffId, $params);
        if ($result === true) {
            return $this->success('回访完成');
        }
        return $this->fail($result);
    }

    /**
     * @notes 标记我的回访无法联系
     * @return \think\response\Json
     */
    public function myMarkUnreachable()
    {
        $staffId = $this->getRequiredStaffScopeId();
        if ($staffId <= 0) {
            return $this->failRequiredStaffScope();
        }
        $params = (new AfterSaleValidate())->post()->goCheck('callbackDetail');
        $result = AfterSaleLogic::markMyCallbackUnreachable((int)$params['id'], (int)$this->adminId, $staffId);
        if ($result === true) {
            return $this->success('标记成功');
        }
        return $this->fail($result);
    }

    // ==================== 统计数据 ====================

    /**
     * @notes 获取统计数据
     * @return \think\response\Json
     */
    public function statistics()
    {
        $result = AfterSaleLogic::getStatistics();
        return $this->data($result);
    }

    /**
     * @notes 获取趋势数据
     * @return \think\response\Json
     */
    public function trend()
    {
        $days = $this->request->get('days', 7);
        $result = AfterSaleLogic::getTrend((int)$days);
        return $this->data($result);
    }

    /**
     * @notes 获取售后设置
     * @return \think\response\Json
     */
    public function getConfig()
    {
        return $this->data(AfterSaleLogic::getConfig());
    }

    /**
     * @notes 保存售后设置
     * @return \think\response\Json
     */
    public function setConfig()
    {
        $params = $this->request->post();
        $result = AfterSaleLogic::setConfig($params);
        if ($result === true) {
            return $this->success('保存成功');
        }
        return $this->fail($result);
    }
}
