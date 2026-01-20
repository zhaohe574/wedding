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

/**
 * 订单变更管理控制器
 * 支持改期、换人、加项申请的审核和执行
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
        $params = (new OrderChangeValidate())->goCheck('detail');
        $result = OrderChangeLogic::detail($params['id']);
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
        $result = OrderChangeLogic::audit(
            $params['id'],
            $this->adminId,
            $params['approved'],
            $params['remark'] ?? '',
            $params['reject_reason'] ?? ''
        );
        if (true === $result) {
            return $this->success($params['approved'] ? '审核通过' : '已拒绝');
        }
        return $this->fail(OrderChangeLogic::getError());
    }

    /**
     * @notes 执行变更
     * @return \think\response\Json
     */
    public function execute()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('detail');
        $result = OrderChangeLogic::execute($params['id'], $this->adminId);
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
        $params = (new OrderChangeValidate())->goCheck('logs');
        $result = OrderChangeLogic::logs($params['id']);
        return $this->data($result);
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
