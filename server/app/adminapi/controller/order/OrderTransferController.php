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
     * @notes 转让能力已下线
     * @return string
     */
    protected function getDeprecatedMessage(): string
    {
        return '功能已下线，请取消订单后重新下单';
    }

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
        return $this->fail($this->getDeprecatedMessage());
    }

    /**
     * @notes 手动完成转让
     * @return \think\response\Json
     */
    public function complete()
    {
        return $this->fail($this->getDeprecatedMessage());
    }

    /**
     * @notes 取消转让
     * @return \think\response\Json
     */
    public function cancel()
    {
        return $this->fail($this->getDeprecatedMessage());
    }

    /**
     * @notes 重发验证码
     * @return \think\response\Json
     */
    public function resendCode()
    {
        return $this->fail($this->getDeprecatedMessage());
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
