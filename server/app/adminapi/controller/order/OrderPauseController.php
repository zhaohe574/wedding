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
        $result = OrderPauseLogic::detail($params['id']);
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
        $result = OrderPauseLogic::audit(
            $params['id'],
            $this->adminId,
            $params['approved'],
            $params['remark'] ?? '',
            $params['reject_reason'] ?? ''
        );
        if (true === $result) {
            return $this->success($params['approved'] ? '审核通过' : '已拒绝');
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
        $result = OrderPauseLogic::resume(
            $params['id'],
            $this->adminId,
            $params['new_service_date'] ?? '',
            $params['remark'] ?? ''
        );
        if (true === $result) {
            return $this->success('订单已恢复');
        }
        return $this->fail(OrderPauseLogic::getError());
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
