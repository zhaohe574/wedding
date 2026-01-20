<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 退款管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\order;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\order\RefundLists;
use app\adminapi\logic\order\RefundLogic;
use app\adminapi\validate\order\RefundValidate;

/**
 * 退款管理控制器
 * Class RefundController
 * @package app\adminapi\controller\order
 */
class RefundController extends BaseAdminController
{
    /**
     * @notes 退款列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new RefundLists());
    }

    /**
     * @notes 退款详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new RefundValidate())->goCheck('detail');
        $result = RefundLogic::detail($params['id']);
        if ($result === null) {
            return $this->fail('退款记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 审核退款
     * @return \think\response\Json
     */
    public function audit()
    {
        $params = (new RefundValidate())->post()->goCheck('audit');
        $result = RefundLogic::audit($params['id'], $this->adminId, $params['approved'], $params['remark'] ?? '');
        if (true === $result) {
            return $this->success('审核成功');
        }
        return $this->fail(RefundLogic::getError());
    }

    /**
     * @notes 确认退款完成（线下退款）
     * @return \think\response\Json
     */
    public function confirmRefund()
    {
        $params = (new RefundValidate())->post()->goCheck('confirm');
        $result = RefundLogic::confirmRefund($params['id'], $this->adminId, $params['transaction_id'] ?? '');
        if (true === $result) {
            return $this->success('确认成功');
        }
        return $this->fail(RefundLogic::getError());
    }

    /**
     * @notes 管理员发起退款
     * @return \think\response\Json
     */
    public function apply()
    {
        $params = (new RefundValidate())->post()->goCheck('apply');
        $params['admin_id'] = $this->adminId;
        $result = RefundLogic::adminApply($params);
        if (true === $result) {
            return $this->success('退款申请成功');
        }
        return $this->fail(RefundLogic::getError());
    }

    /**
     * @notes 退款统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = RefundLogic::statistics($params);
        return $this->data($result);
    }

    /**
     * @notes 获取退款状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        $result = RefundLogic::getStatusOptions();
        return $this->data($result);
    }
}
