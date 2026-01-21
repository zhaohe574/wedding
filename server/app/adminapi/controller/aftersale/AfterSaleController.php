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
use app\adminapi\lists\aftersale\ReshootLists;
use app\adminapi\lists\aftersale\CallbackLists;
use app\adminapi\validate\aftersale\AfterSaleValidate;

/**
 * 售后工单控制器
 * Class AfterSaleController
 * @package app\adminapi\controller\aftersale
 */
class AfterSaleController extends BaseAdminController
{
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
        $params = (new AfterSaleValidate())->goCheck('ticketDetail');
        $result = AfterSaleLogic::getTicketDetail($params['id']);
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
        $result = AfterSaleLogic::assignTicket($params['id'], $params['admin_id'], $this->adminId);
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
        $result = AfterSaleLogic::handleTicket($params['id'], $this->adminId, $params['result'], $params['images'] ?? []);
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
        $result = AfterSaleLogic::closeTicket($params['id'], $this->adminId, $params['reason']);
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
        $result = AfterSaleLogic::escalateTicket($params['id'], $this->adminId);
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
        $params = (new AfterSaleValidate())->goCheck('ticketDetail');
        $result = AfterSaleLogic::getTicketLogs($params['id']);
        return $this->data($result);
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
        $params = (new AfterSaleValidate())->goCheck('complaintDetail');
        $result = AfterSaleLogic::getComplaintDetail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 处理投诉
     * @return \think\response\Json
     */
    public function handleComplaint()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('handleComplaint');
        $result = AfterSaleLogic::handleComplaint($params['id'], $this->adminId, $params);
        if ($result === true) {
            return $this->success('处理成功');
        }
        return $this->fail($result);
    }

    // ==================== 补拍申请管理 ====================

    /**
     * @notes 补拍申请列表
     * @return \think\response\Json
     */
    public function reshootLists()
    {
        return $this->dataLists(new ReshootLists());
    }

    /**
     * @notes 补拍申请详情
     * @return \think\response\Json
     */
    public function reshootDetail()
    {
        $params = (new AfterSaleValidate())->goCheck('reshootDetail');
        $result = AfterSaleLogic::getReshootDetail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 审核补拍申请
     * @return \think\response\Json
     */
    public function auditReshoot()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('auditReshoot');
        $result = AfterSaleLogic::auditReshoot($params['id'], $this->adminId, $params['approved'], $params);
        if ($result === true) {
            return $this->success('审核成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 安排补拍
     * @return \think\response\Json
     */
    public function scheduleReshoot()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('scheduleReshoot');
        $result = AfterSaleLogic::scheduleReshoot($params['id'], $this->adminId, $params);
        if ($result === true) {
            return $this->success('安排成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 完成补拍
     * @return \think\response\Json
     */
    public function completeReshoot()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('completeReshoot');
        $result = AfterSaleLogic::completeReshoot($params['id'], $this->adminId, $params['remark'] ?? '');
        if ($result === true) {
            return $this->success('完成成功');
        }
        return $this->fail($result);
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
        $params = (new AfterSaleValidate())->goCheck('callbackDetail');
        $result = AfterSaleLogic::getCallbackDetail($params['id']);
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
        $result = AfterSaleLogic::completeCallback($params['id'], $this->adminId, $params);
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
        $result = AfterSaleLogic::markUnreachable($params['id'], $this->adminId);
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
        $result = AfterSaleLogic::escalateProblem($params['id'], $this->adminId);
        if (is_array($result)) {
            return $this->success('升级成功', ['ticket_id' => $result['ticket_id']]);
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
}
