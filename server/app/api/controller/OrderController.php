<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订单控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\OrderLogic;
use app\api\validate\OrderValidate;

/**
 * 小程序端订单控制器
 * Class OrderController
 * @package app\api\controller
 */
class OrderController extends BaseApiController
{
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
            return $this->fail('订单不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 创建订单（从购物车）
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
        return $this->fail($result['message']);
    }

    /**
     * @notes 订单预览（结算页）
     * @return \think\response\Json
     */
    public function preview()
    {
        $params = $this->request->post();
        $result = OrderLogic::previewOrder($this->userId, $params);
        if ($result['success']) {
            return $this->data($result['data']);
        }
        return $this->fail($result['message']);
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
        return $this->fail($result['message']);
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
        return $this->fail($result['message']);
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
        return $this->fail($result['message']);
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
        return $this->fail('订单不存在');
    }

    /**
     * @notes 发起支付
     * @return \think\response\Json
     */
    public function pay()
    {
        $params = (new OrderValidate())->post()->goCheck('pay');
        $params['id'] = (int)$params['id'];
        $params['user_id'] = $this->userId;
        $result = OrderLogic::createPayment($params);
        if ($result['success']) {
            return $this->data($result['data']);
        }
        return $this->fail($result['message']);
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
        return $this->fail($result['message']);
    }

    /**
     * @notes 支付尾款
     * @return \think\response\Json
     */
    public function payBalance()
    {
        $params = (new OrderValidate())->post()->goCheck('pay');
        $params['id'] = (int)$params['id'];
        $params['user_id'] = $this->userId;
        $params['pay_type'] = 2; // 尾款
        $result = OrderLogic::createPayment($params);
        if ($result['success']) {
            return $this->data($result['data']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 申请退款
     * @return \think\response\Json
     */
    public function applyRefund()
    {
        $params = (new OrderValidate())->post()->goCheck('refund');
        $orderId = (int)$params['id'];
        $result = OrderLogic::applyRefund($orderId, $this->userId, $params['amount'], $params['reason']);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
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
     * @notes 订单统计（各状态数量）
     * @return \think\response\Json
     */
    public function statistics()
    {
        $result = OrderLogic::getUserOrderStatistics($this->userId);
        return $this->data($result);
    }

    /**
     * @notes 可用优惠券列表
     * @return \think\response\Json
     */
    public function availableCoupons()
    {
        $params = $this->request->get();
        $result = OrderLogic::getAvailableCoupons($this->userId, $params['amount'] ?? 0);
        return $this->data($result);
    }
}
