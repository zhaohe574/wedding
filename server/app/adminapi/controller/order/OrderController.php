<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\order;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\order\OrderLists;
use app\adminapi\lists\order\OrderLogLists;
use app\adminapi\logic\order\OrderLogic;
use app\adminapi\validate\order\OrderValidate;

/**
 * 订单管理控制器
 * Class OrderController
 * @package app\adminapi\controller\order
 */
class OrderController extends BaseAdminController
{
    /**
     * @notes 订单列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new OrderLists());
    }

    /**
     * @notes 订单详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new OrderValidate())->goCheck('detail');
        $result = OrderLogic::detail($params['id']);
        if ($result === null) {
            return $this->fail('订单不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 创建订单（后台创建）
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new OrderValidate())->post()->goCheck('add');
        $params['admin_id'] = $this->adminId;
        $result = OrderLogic::add($params);
        if (true === $result) {
            return $this->success('创建成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 编辑订单信息
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new OrderValidate())->post()->goCheck('edit');
        $params['admin_id'] = $this->adminId;
        $result = OrderLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 取消订单
     * @return \think\response\Json
     */
    public function cancel()
    {
        $params = (new OrderValidate())->post()->goCheck('cancel');
        $result = OrderLogic::cancel($params['id'], $this->adminId, $params['reason'] ?? '');
        if (true === $result) {
            return $this->success('取消成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 开始服务
     * @return \think\response\Json
     */
    public function startService()
    {
        $params = (new OrderValidate())->post()->goCheck('detail');
        $result = OrderLogic::startService($params['id'], $this->adminId);
        if (true === $result) {
            return $this->success('操作成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 完成订单
     * @return \think\response\Json
     */
    public function complete()
    {
        $params = (new OrderValidate())->post()->goCheck('detail');
        $result = OrderLogic::complete($params['id'], $this->adminId);
        if (true === $result) {
            return $this->success('操作成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 确认线下支付
     * @return \think\response\Json
     */
    public function confirmOfflinePay()
    {
        $params = (new OrderValidate())->post()->goCheck('confirmPay');
        $params['admin_id'] = $this->adminId;
        $result = OrderLogic::confirmOfflinePay($params);
        if (true === $result) {
            return $this->success('确认成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 添加备注
     * @return \think\response\Json
     */
    public function addRemark()
    {
        $params = (new OrderValidate())->post()->goCheck('remark');
        $result = OrderLogic::addRemark($params['id'], $this->adminId, $params['remark']);
        if (true === $result) {
            return $this->success('添加成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 订单操作日志
     * @return \think\response\Json
     */
    public function logs()
    {
        return $this->dataLists(new OrderLogLists());
    }

    /**
     * @notes 订单统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = OrderLogic::statistics($params);
        return $this->data($result);
    }

    /**
     * @notes 获取订单状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        $result = OrderLogic::getStatusOptions();
        return $this->data($result);
    }

    /**
     * @notes 获取支付方式选项
     * @return \think\response\Json
     */
    public function payWayOptions()
    {
        $result = OrderLogic::getPayWayOptions();
        return $this->data($result);
    }

    /**
     * @notes 导出订单
     * @return \think\response\Json
     */
    public function export()
    {
        return $this->dataLists(new OrderLists());
    }
}
