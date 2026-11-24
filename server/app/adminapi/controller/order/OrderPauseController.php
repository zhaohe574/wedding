<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单暂停管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\order;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\order\OrderPauseLists;
use app\adminapi\logic\order\OrderPauseLogic;
use app\adminapi\validate\order\OrderPauseValidate;
use app\common\model\order\OrderItem;
use app\common\model\order\OrderPause;
use app\common\service\StaffService;

/**
 * 订单暂停管理控制器
 * Class OrderPauseController
 * @package app\adminapi\controller\order
 */
class OrderPauseController extends BaseAdminController
{
    /**
     * @notes 暂停申请列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new OrderPauseLists());
    }

    /**
     * @notes 暂停申请详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new OrderPauseValidate())->goCheck('detail');
        if ($response = $this->checkPauseScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderPauseLogic::detail((int)$params['id']);
        if ($result === null) {
            return $this->fail('暂停记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 审核暂停申请
     * @return \think\response\Json
     */
    public function audit()
    {
        $params = (new OrderPauseValidate())->post()->goCheck('audit');
        if ($response = $this->checkPauseScope((int)$params['id'])) {
            return $response;
        }
        $approved = (int)$params['approved'] === 1;
        $result = OrderPauseLogic::audit(
            (int)$params['id'],
            $this->adminId,
            $approved,
            (string)($params['remark'] ?? ''),
            (string)($params['reject_reason'] ?? '')
        );
        if (true === $result) {
            return $this->success($approved ? '审核通过' : '已拒绝');
        }
        return $this->fail(OrderPauseLogic::getError());
    }

    /**
     * @notes 恢复订单
     * @return \think\response\Json
     */
    public function resume()
    {
        $params = (new OrderPauseValidate())->post()->goCheck('resume');
        if ($response = $this->checkPauseScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderPauseLogic::resume(
            (int)$params['id'],
            $this->adminId,
            (string)($params['new_service_date'] ?? ''),
            (string)($params['remark'] ?? '')
        );
        if (true === $result) {
            return $this->success('订单已恢复');
        }
        return $this->fail(OrderPauseLogic::getError());
    }

    /**
     * @notes 延长暂停时间
     * @return \think\response\Json
     */
    public function extend()
    {
        $params = (new OrderPauseValidate())->post()->goCheck('extend');
        if ($response = $this->checkPauseScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderPauseLogic::extend(
            (int)$params['id'],
            $this->adminId,
            (string)$params['new_end_date'],
            (string)($params['remark'] ?? '')
        );
        if (true === $result) {
            return $this->success('延期成功');
        }
        return $this->fail(OrderPauseLogic::getError());
    }

    /**
     * @notes 暂停操作日志列表
     * @return \think\response\Json
     */
    public function logs()
    {
        $params = (new OrderPauseValidate())->get()->goCheck('logs');
        if ($response = $this->checkPauseScope((int)$params['id'])) {
            return $response;
        }
        $result = OrderPauseLogic::logs((int)$params['id']);
        return $this->data($result);
    }

    protected function checkPauseScope(int $pauseId)
    {
        $scopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($scopeId <= 0) {
            return null;
        }
        $orderId = (int)OrderPause::where('id', $pauseId)->value('order_id');
        if ($orderId <= 0) {
            return $this->fail('暂停记录不存在');
        }
        $total = OrderItem::where('order_id', $orderId)->where('item_status', '<>', OrderItem::STATUS_CANCELLED)->count();
        $owned = OrderItem::where('order_id', $orderId)->where('item_status', '<>', OrderItem::STATUS_CANCELLED)->where('staff_id', $scopeId)->count();
        return $total > 0 && $total === $owned ? null : $this->fail('无权操作此暂停申请');
    }

    /**
     * @notes 即将到期的暂停订单
     * @return \think\response\Json
     */
    public function expiring()
    {
        $params = $this->request->get();
        $days = intval($params['days'] ?? 7);
        $result = OrderPauseLogic::expiring($days);
        return $this->data($result);
    }

    /**
     * @notes 暂停统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = OrderPauseLogic::statistics($params);
        return $this->data($result);
    }

    /**
     * @notes 获取暂停类型选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        $result = OrderPauseLogic::getTypeOptions();
        return $this->data($result);
    }

    /**
     * @notes 获取暂停状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        $result = OrderPauseLogic::getStatusOptions();
        return $this->data($result);
    }
}
