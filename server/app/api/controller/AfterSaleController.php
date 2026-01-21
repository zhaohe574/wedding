<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端售后工单控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\controller\BaseApiController;
use app\api\logic\AfterSaleLogic;
use app\api\validate\AfterSaleValidate;

/**
 * 小程序端售后工单控制器
 * Class AfterSaleController
 * @package app\api\controller
 */
class AfterSaleController extends BaseApiController
{
    public array $notNeedLogin = [];

    // ==================== 工单管理 ====================

    /**
     * @notes 我的工单列表
     * @return \think\response\Json
     */
    public function ticketLists()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId;
        $result = AfterSaleLogic::getTicketLists($params);
        return $this->data($result);
    }

    /**
     * @notes 工单详情
     * @return \think\response\Json
     */
    public function ticketDetail()
    {
        $params = (new AfterSaleValidate())->goCheck('detail');
        $result = AfterSaleLogic::getTicketDetail($params['id'], $this->userId);
        if (empty($result)) {
            return $this->fail('工单不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 创建工单
     * @return \think\response\Json
     */
    public function createTicket()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('createTicket');
        $params['user_id'] = $this->userId;
        $params['source'] = 1;  // 小程序
        $result = AfterSaleLogic::createTicket($params);
        if ($result === true) {
            return $this->success('提交成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 取消工单
     * @return \think\response\Json
     */
    public function cancelTicket()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('detail');
        $result = AfterSaleLogic::cancelTicket($params['id'], $this->userId);
        if ($result === true) {
            return $this->success('取消成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 确认完成
     * @return \think\response\Json
     */
    public function confirmComplete()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('confirm');
        $result = AfterSaleLogic::confirmComplete($params['id'], $this->userId, $params['satisfaction'] ?? 5, $params['remark'] ?? '');
        if ($result === true) {
            return $this->success('确认成功');
        }
        return $this->fail($result);
    }

    // ==================== 投诉管理 ====================

    /**
     * @notes 我的投诉列表
     * @return \think\response\Json
     */
    public function complaintLists()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId;
        $result = AfterSaleLogic::getComplaintLists($params);
        return $this->data($result);
    }

    /**
     * @notes 投诉详情
     * @return \think\response\Json
     */
    public function complaintDetail()
    {
        $params = (new AfterSaleValidate())->goCheck('detail');
        $result = AfterSaleLogic::getComplaintDetail($params['id'], $this->userId);
        if (empty($result)) {
            return $this->fail('投诉记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 提交投诉
     * @return \think\response\Json
     */
    public function submitComplaint()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('submitComplaint');
        $params['user_id'] = $this->userId;
        $result = AfterSaleLogic::submitComplaint($params);
        if ($result === true) {
            return $this->success('投诉提交成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 评价投诉处理结果
     * @return \think\response\Json
     */
    public function rateComplaint()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('rate');
        $result = AfterSaleLogic::rateComplaintSatisfaction($params['id'], $this->userId, $params['satisfaction']);
        if ($result === true) {
            return $this->success('评价成功');
        }
        return $this->fail($result);
    }

    // ==================== 补拍申请 ====================

    /**
     * @notes 我的补拍申请列表
     * @return \think\response\Json
     */
    public function reshootLists()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId;
        $result = AfterSaleLogic::getReshootLists($params);
        return $this->data($result);
    }

    /**
     * @notes 补拍申请详情
     * @return \think\response\Json
     */
    public function reshootDetail()
    {
        $params = (new AfterSaleValidate())->goCheck('detail');
        $result = AfterSaleLogic::getReshootDetail($params['id'], $this->userId);
        if (empty($result)) {
            return $this->fail('申请记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 提交补拍申请
     * @return \think\response\Json
     */
    public function applyReshoot()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('applyReshoot');
        $params['user_id'] = $this->userId;
        $result = AfterSaleLogic::applyReshoot($params);
        if ($result === true) {
            return $this->success('申请提交成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 取消补拍申请
     * @return \think\response\Json
     */
    public function cancelReshoot()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('detail');
        $result = AfterSaleLogic::cancelReshoot($params['id'], $this->userId);
        if ($result === true) {
            return $this->success('取消成功');
        }
        return $this->fail($result);
    }

    // ==================== 回访问卷 ====================

    /**
     * @notes 我的回访列表
     * @return \think\response\Json
     */
    public function callbackLists()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId;
        $result = AfterSaleLogic::getCallbackLists($params);
        return $this->data($result);
    }

    /**
     * @notes 获取回访问卷
     * @return \think\response\Json
     */
    public function getQuestionnaire()
    {
        $params = (new AfterSaleValidate())->goCheck('detail');
        $result = AfterSaleLogic::getQuestionnaire($params['id'], $this->userId);
        if (empty($result)) {
            return $this->fail('问卷不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 提交回访问卷
     * @return \think\response\Json
     */
    public function submitQuestionnaire()
    {
        $params = (new AfterSaleValidate())->post()->goCheck('submitQuestionnaire');
        $result = AfterSaleLogic::submitQuestionnaire($params['id'], $this->userId, $params);
        if ($result === true) {
            return $this->success('提交成功');
        }
        return $this->fail($result);
    }

    // ==================== 统计 ====================

    /**
     * @notes 获取用户售后统计
     * @return \think\response\Json
     */
    public function myStatistics()
    {
        $result = AfterSaleLogic::getUserStatistics($this->userId);
        return $this->data($result);
    }
}
