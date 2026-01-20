<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 支付记录控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\order;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\order\PaymentLists;
use app\adminapi\logic\order\PaymentLogic;
use app\adminapi\validate\order\PaymentValidate;

/**
 * 支付记录控制器
 * Class PaymentController
 * @package app\adminapi\controller\order
 */
class PaymentController extends BaseAdminController
{
    /**
     * @notes 支付记录列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new PaymentLists());
    }

    /**
     * @notes 支付详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new PaymentValidate())->goCheck('detail');
        $result = PaymentLogic::detail($params['id']);
        if ($result === null) {
            return $this->fail('支付记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 支付统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = PaymentLogic::statistics($params);
        return $this->data($result);
    }

    /**
     * @notes 获取支付类型选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        $result = PaymentLogic::getTypeOptions();
        return $this->data($result);
    }

    /**
     * @notes 获取支付方式选项
     * @return \think\response\Json
     */
    public function wayOptions()
    {
        $result = PaymentLogic::getWayOptions();
        return $this->data($result);
    }

    /**
     * @notes 获取支付状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        $result = PaymentLogic::getStatusOptions();
        return $this->data($result);
    }
}
