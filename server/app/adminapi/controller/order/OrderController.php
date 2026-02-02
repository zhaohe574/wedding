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
use app\common\model\order\OrderItem;
use app\common\service\StaffService;

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
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderLogic::detail((int)$params['id']);
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $items = $params['items'] ?? [];
            if (empty($items)) {
                return $this->fail('请选择服务人员');
            }
            foreach ($items as $item) {
                if ((int)($item['staff_id'] ?? 0) !== $staffScopeId) {
                    return $this->fail('无权限操作');
                }
            }
        }
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
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
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
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderLogic::cancel((int)$params['id'], $this->adminId, $params['reason'] ?? '');
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
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderLogic::startService((int)$params['id'], $this->adminId);
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
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderLogic::complete((int)$params['id'], $this->adminId);
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
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        $params['admin_id'] = $this->adminId;
        $result = OrderLogic::confirmOfflinePay($params);
        if (true === $result) {
            return $this->success('确认成功');
        }
        return $this->fail(OrderLogic::getError());
    }

    /**
     * @notes 审核线下支付凭证
     * @return \think\response\Json
     */
    public function auditVoucher()
    {
        $params = (new OrderValidate())->post()->goCheck('auditVoucher');
        if ($response = $this->checkOrderScope((int)$params['id'])) {
            return $response;
        }
        $params['admin_id'] = $this->adminId;
        $result = OrderLogic::auditPayVoucher($params);
        if (true === $result) {
            return $this->success('审核成功');
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
        if ($this->checkOrderScope((int)$params['id']) !== null) {
            return $this->checkOrderScope((int)$params['id']);
        }
        $result = OrderLogic::addRemark((int)$params['id'], $this->adminId, $params['remark']);
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $orderId = (int) $this->request->get('order_id', 0);
            if ($orderId <= 0) {
                return $this->fail('无权限操作');
            }
            $exists = OrderItem::where('order_id', $orderId)
                ->where('staff_id', $staffScopeId)
                ->find();
            if (!$exists) {
                return $this->fail('无权限操作');
            }
        }
        return $this->dataLists(new OrderLogLists());
    }

    /**
     * @notes 订单统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $params['staff_id'] = $staffScopeId;
        }
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

    /**
     * @notes 校验订单数据范围
     * @param int $orderId
     * @return \think\response\Json|null
     */
    protected function checkOrderScope(int $orderId)
    {
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId <= 0) {
            return null;
        }
        $exists = OrderItem::where('order_id', $orderId)
            ->where('staff_id', $staffScopeId)
            ->find();
        if (!$exists) {
            return $this->fail('无权限操作');
        }
        return null;
    }
}
