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
use app\common\model\order\OrderItem;
use app\common\service\StaffService;

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
        $result = RefundLogic::detail((int)$params['id']);
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
        $approved = filter_var($params['approved'], FILTER_VALIDATE_BOOLEAN);
        $result = RefundLogic::audit((int)$params['id'], $this->adminId, $approved, $params['remark'] ?? '');
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
        $result = RefundLogic::confirmRefund((int)$params['id'], $this->adminId, $params['transaction_id'] ?? '');
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $orderId = (int)($params['order_id'] ?? 0);
            $totalCount = OrderItem::where('order_id', $orderId)->count();
            $ownedCount = OrderItem::where('order_id', $orderId)
                ->where('staff_id', $staffScopeId)
                ->count();
            if ($totalCount <= 0 || $totalCount !== $ownedCount) {
                return $this->fail('共享订单不支持当前退款操作');
            }
        }
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
