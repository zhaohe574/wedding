<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订单控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\OrderLogic;
use app\api\validate\OrderValidate;
use app\common\enum\ErrorCodeEnum;

/**
 * 小程序端订单控制器
 * Class OrderController
 * @package app\api\controller
 */
class OrderController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @notes 我的订单列表
     * @return \think\response\Json
     */
    public function lists()
    {
        $params = $this->request->get();
        $result = OrderLogic::getUserOrders($this->userId, $params);
        return $this->data($result);
    }

    /**
     * @notes 订单详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new OrderValidate())->goCheck('detail');
        $orderId = (int)$params['id'];
        $result = OrderLogic::getOrderDetail($orderId, $this->userId);
        if ($result === null) {
            return $this->fail('订单不存在', [], 0, 1, ErrorCodeEnum::ORDER_NOT_FOUND);
        }
        return $this->data($result);
    }

    /**
     * @notes 创建订单（直购）
     * @return \think\response\Json
     */
    public function create()
    {
        $params = (new OrderValidate())->post()->goCheck('create');
        $params['user_id'] = $this->userId;
        $result = OrderLogic::createOrder($params);
        if ($result['success']) {
            return $this->success($result['message'], [
                'order_id' => $result['order_id'],
                'order_sn' => $result['order_sn'],
            ]);
        }
        return $this->fail($result['message'], [], 0, 1, $result['error_code'] ?? null);
    }

    /**
     * @notes 订单预览（直购确认页）
     * @return \think\response\Json
     */
    public function preview()
    {
        $params = (new OrderValidate())->post()->goCheck('selection');
        $result = OrderLogic::previewOrder($this->userId, $params);
        if ($result['success']) {
            return $this->data($result['data']);
        }
        return $this->fail($result['message'], [], 0, 1, $result['error_code'] ?? null);
    }

    /**
     * @notes 取消订单
     * @return \think\response\Json
     */
    public function cancel()
    {
        $params = (new OrderValidate())->post()->goCheck('cancel');
        $orderId = (int)$params['id'];
        $result = OrderLogic::cancelOrder($orderId, $this->userId, $params['reason'] ?? '');
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message'], [], 0, 1, $result['error_code'] ?? null);
    }

    /**
     * @notes 确认完成
     * @return \think\response\Json
     */
    public function confirm()
    {
        $params = (new OrderValidate())->post()->goCheck('detail');
        $orderId = (int)$params['id'];
        $result = OrderLogic::confirmComplete($orderId, $this->userId);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message'], [], 0, 1, $result['error_code'] ?? null);
    }

    /**
     * @notes 删除订单
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new OrderValidate())->post()->goCheck('detail');
        $orderId = (int)$params['id'];
        $result = OrderLogic::deleteOrder($orderId, $this->userId);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message'], [], 0, 1, $result['error_code'] ?? null);
    }

    /**
     * @notes 获取支付信息
     * @return \think\response\Json
     */
    public function getPayInfo()
    {
        $params = (new OrderValidate())->goCheck('detail');
        $orderId = (int)$params['id'];
        $result = OrderLogic::getPayInfo($orderId, $this->userId);
        if ($result) {
            return $this->data($result);
        }
        return $this->fail('订单不存在', [], 0, 1, ErrorCodeEnum::ORDER_NOT_FOUND);
    }

    /**
     * @notes 上传线下支付凭证
     * @return \think\response\Json
     */
    public function uploadVoucher()
    {
        $params = (new OrderValidate())->post()->goCheck('uploadVoucher');
        $orderId = (int)$params['id'];
        $result = OrderLogic::uploadPayVoucher($orderId, $this->userId, $params['voucher']);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message'], [], 0, 1, $result['error_code'] ?? null);
    }

    /**
     * @notes 申请退款
     * @return \think\response\Json
     */
    public function applyRefund()
    {
        $params = (new OrderValidate())->post()->goCheck('refund');
        $orderId = (int)$params['id'];
        $result = OrderLogic::applyRefund($orderId, $this->userId, $params['reason']);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message'], [], 0, 1, $result['error_code'] ?? null);
    }

    /**
     * @notes 获取退款详情
     * @return \think\response\Json
     */
    public function refundDetail()
    {
        $params = (new OrderValidate())->goCheck('detail');
        $orderId = (int)$params['id'];
        $result = OrderLogic::getRefundDetail($orderId, $this->userId);
        if ($result) {
            return $this->data($result);
        }
        return $this->fail('退款记录不存在');
    }

    /**
     * @notes 获取协作服务重新挑选候选人
     * @return \think\response\Json
     */
    public function reselectCandidates()
    {
        $params = (new OrderValidate())->goCheck('reselectCandidates');
        $result = OrderLogic::getReselectCandidates(
            $this->userId,
            (int)$params['order_id'],
            (int)$params['order_item_id']
        );
        if ($result === null) {
            return $this->fail(OrderLogic::getError(), [], 0, 1, ErrorCodeEnum::ORDER_STATUS_INVALID);
        }
        return $this->data($result);
    }

    /**
     * @notes 重新挑选协作服务人员
     * @return \think\response\Json
     */
    public function reselectCollaborator()
    {
        $params = (new OrderValidate())->post()->goCheck('reselectCollaborator');
        $success = OrderLogic::reselectCollaborator(
            $this->userId,
            (int)$params['order_id'],
            (int)$params['order_item_id'],
            (int)$params['staff_id'],
            (int)$params['package_id']
        );
        if ($success) {
            return $this->success('已重新挑选协作人员，等待服务人员确认', [], 1, 1);
        }
        return $this->fail(OrderLogic::getError(), [], 0, 1, ErrorCodeEnum::ORDER_STATUS_INVALID);
    }

    /**
     * @notes 关闭协作服务重新挑选提醒
     * @return \think\response\Json
     */
    public function dismissReselect()
    {
        $params = (new OrderValidate())->post()->goCheck('dismissReselect');
        $success = OrderLogic::dismissReselect(
            $this->userId,
            (int)$params['order_id'],
            (int)$params['order_item_id']
        );
        if ($success) {
            return $this->success('已关闭重新挑选提醒', [], 1, 1);
        }
        return $this->fail(OrderLogic::getError(), [], 0, 1, ErrorCodeEnum::ORDER_STATUS_INVALID);
    }

    /**
     * @notes 订单统计（各状态数量）
     * @return \think\response\Json
     */
    public function statistics()
    {
        $result = OrderLogic::getUserOrderStatistics($this->userId);
        return $this->data($result);
    }
}
