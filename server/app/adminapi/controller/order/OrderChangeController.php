<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单变更管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\order;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\order\OrderChangeLists;
use app\adminapi\logic\order\OrderChangeLogic;
use app\adminapi\validate\order\OrderChangeValidate;
use app\common\model\order\OrderChange;
use app\common\model\order\OrderItem;
use app\common\service\StaffService;

/**
 * 订单变更管理控制器
 * 支持改期、加项申请的审核和执行
 * Class OrderChangeController
 * @package app\adminapi\controller\order
 */
class OrderChangeController extends BaseAdminController
{
    /**
     * @notes 变更申请列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new OrderChangeLists());
    }

    /**
     * @notes 变更申请详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new OrderChangeValidate())->get()->goCheck('detail');
        if ($response = $this->checkChangeScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderChangeLogic::detail((int)$params['id']);
        if ($result === null) {
            return $this->fail('变更记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 审核变更申请
     * @return \think\response\Json
     */
    public function audit()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('audit');
        if ($response = $this->checkChangeScope((int)$params['id'])) {
            return $response;
        }
        $approved = (int)$params['approved'] === 1;
        $result = OrderChangeLogic::audit(
            (int)$params['id'],
            $this->adminId,
            $approved,
            (string)($params['remark'] ?? ''),
            (string)($params['reject_reason'] ?? '')
        );
        if (true === $result) {
            return $this->success($approved ? '审核通过' : '已拒绝');
        }
        return $this->fail(OrderChangeLogic::getError());
    }

    /**
     * @notes 执行变更
     * @return \think\response\Json
     */
    public function execute()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('execute');
        if ($response = $this->checkChangeScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderChangeLogic::execute((int)$params['id'], $this->adminId);
        if (true === $result) {
            return $this->success('执行成功');
        }
        return $this->fail(OrderChangeLogic::getError());
    }

    /**
     * @notes 变更日志列表
     * @return \think\response\Json
     */
    public function logs()
    {
        $params = (new OrderChangeValidate())->get()->goCheck('logs');
        if ($response = $this->checkChangeScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderChangeLogic::logs((int)$params['id']);
        return $this->data($result);
    }

    protected function checkChangeScope(int $changeId)
    {
        $scopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($scopeId <= 0) {
            return null;
        }
        $orderId = (int)OrderChange::where('id', $changeId)->value('order_id');
        if ($orderId <= 0) {
            return $this->fail('变更记录不存在');
        }
        $total = OrderItem::where('order_id', $orderId)->where('item_status', '<>', OrderItem::STATUS_CANCELLED)->count();
        $owned = OrderItem::where('order_id', $orderId)->where('item_status', '<>', OrderItem::STATUS_CANCELLED)->where('staff_id', $scopeId)->count();
        return $total > 0 && $total === $owned ? null : $this->fail('无权操作此变更申请');
    }

    /**
     * @notes 变更统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = OrderChangeLogic::statistics($params);
        return $this->data($result);
    }

    /**
     * @notes 获取变更类型选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        $result = OrderChangeLogic::getTypeOptions();
        return $this->data($result);
    }

    /**
     * @notes 获取变更状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        $result = OrderChangeLogic::getStatusOptions();
        return $this->data($result);
    }
}
