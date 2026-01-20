<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单转让管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\order;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\order\OrderTransferLists;
use app\adminapi\logic\order\OrderTransferLogic;
use app\adminapi\validate\order\OrderTransferValidate;

/**
 * 订单转让管理控制器
 * Class OrderTransferController
 * @package app\adminapi\controller\order
 */
class OrderTransferController extends BaseAdminController
{
    /**
     * @notes 转让申请列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new OrderTransferLists());
    }

    /**
     * @notes 转让申请详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new OrderTransferValidate())->goCheck('detail');
        $result = OrderTransferLogic::detail($params['id']);
        if ($result === null) {
            return $this->fail('转让记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 审核转让申请
     * @return \think\response\Json
     */
    public function audit()
    {
        $params = (new OrderTransferValidate())->post()->goCheck('audit');
        $result = OrderTransferLogic::audit(
            $params['id'],
            $this->adminId,
            $params['approved'],
            $params['remark'] ?? '',
            $params['reject_reason'] ?? ''
        );
        if (true === $result) {
            return $this->success($params['approved'] ? '审核通过' : '已拒绝');
        }
        return $this->fail(OrderTransferLogic::getError());
    }

    /**
     * @notes 手动完成转让
     * @return \think\response\Json
     */
    public function complete()
    {
        $params = (new OrderTransferValidate())->post()->goCheck('detail');
        $result = OrderTransferLogic::complete($params['id'], $this->adminId);
        if (true === $result) {
            return $this->success('转让完成');
        }
        return $this->fail(OrderTransferLogic::getError());
    }

    /**
     * @notes 取消转让
     * @return \think\response\Json
     */
    public function cancel()
    {
        $params = (new OrderTransferValidate())->post()->goCheck('cancel');
        $result = OrderTransferLogic::cancel(
            $params['id'],
            $this->adminId,
            $params['reason'] ?? ''
        );
        if (true === $result) {
            return $this->success('已取消');
        }
        return $this->fail(OrderTransferLogic::getError());
    }

    /**
     * @notes 重发验证码
     * @return \think\response\Json
     */
    public function resendCode()
    {
        $params = (new OrderTransferValidate())->post()->goCheck('resendCode');
        $result = OrderTransferLogic::resendCode($params['id'], $this->adminId);
        if (true === $result) {
            return $this->success('验证码已发送');
        }
        return $this->fail(OrderTransferLogic::getError());
    }

    /**
     * @notes 转让统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = OrderTransferLogic::statistics($params);
        return $this->data($result);
    }

    /**
     * @notes 获取转让状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        $result = OrderTransferLogic::getStatusOptions();
        return $this->data($result);
    }
}
