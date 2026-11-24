<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端订单变更控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\OrderChangeLogic;
use app\api\validate\OrderChangeValidate;

/**
 * 小程序端订单变更控制器
 * 支持用户申请改期、加项、暂停
 * Class OrderChangeController
 * @package app\api\controller
 */
class OrderChangeController extends BaseApiController
{
    /**
     * @notes 我的变更申请列表
     * @return \think\response\Json
     */
    public function lists()
    {
        $params = $this->request->get();
        $result = OrderChangeLogic::getUserChanges($this->userId, $params);
        return $this->data($result);
    }

    /**
     * @notes 变更申请详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new OrderChangeValidate())->get()->goCheck('detail');
        $result = OrderChangeLogic::getChangeDetail((int)$params['id'], $this->userId);
        if ($result === null) {
            return $this->fail('变更记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 检查订单是否可变更
     * @return \think\response\Json
     */
    public function checkCanChange()
    {
        $params = (new OrderChangeValidate())->get()->goCheck('checkOrder');
        $result = OrderChangeLogic::checkCanChange((int)$params['order_id'], $this->userId);
        return $this->data($result);
    }

    /**
     * @notes 申请改期
     * @return \think\response\Json
     */
    public function applyDateChange()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('dateChange');
        $result = OrderChangeLogic::applyDateChange(
            $this->userId,
            (int)$params['order_id'],
            (string)$params['new_date'],
            (string)($params['reason'] ?? ''),
            is_array($params['attach_images'] ?? null) ? $params['attach_images'] : []
        );
        if ($result['success']) {
            return $this->success($result['message'], ['change_id' => $result['change_id']]);
        }
        return $this->fail($result['message']);
    }


    /**
     * @notes 申请加项
     * @return \think\response\Json
     */
    public function applyAddItem()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('addItem');
        $result = OrderChangeLogic::applyAddItem(
            $this->userId,
            (int)$params['order_id'],
            (int)$params['staff_id'],
            (int)$params['package_id'],
            (string)$params['service_date'],
            (string)($params['reason'] ?? '')
        );
        if ($result['success']) {
            return $this->success($result['message'], [
                'change_id' => $result['change_id'],
                'add_price' => $result['add_price'] ?? 0,
            ]);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 取消变更申请
     * @return \think\response\Json
     */
    public function cancel()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('detail');
        $result = OrderChangeLogic::cancelChange((int)$params['id'], $this->userId);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 申请暂停
     * @return \think\response\Json
     */
    public function applyPause()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('pause');
        $result = OrderChangeLogic::applyPause(
            $this->userId,
            (int)$params['order_id'],
            (int)$params['pause_type'],
            (string)$params['reason'],
            (string)$params['start_date'],
            (string)$params['end_date'],
            is_array($params['proof_images'] ?? null) ? $params['proof_images'] : []
        );
        if ($result['success']) {
            return $this->success($result['message'], ['pause_id' => $result['pause_id']]);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 取消暂停申请
     * @return \think\response\Json
     */
    public function cancelPause()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('pauseDetail');
        $result = OrderChangeLogic::cancelPause((int)$params['id'], $this->userId);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 暂停详情
     * @return \think\response\Json
     */
    public function pauseDetail()
    {
        $params = (new OrderChangeValidate())->get()->goCheck('pauseDetail');
        $result = OrderChangeLogic::getPauseDetail((int)$params['id'], $this->userId);
        if ($result === null) {
            return $this->fail('暂停记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 我的暂停列表
     * @return \think\response\Json
     */
    public function pauseLists()
    {
        $params = $this->request->get();
        $result = OrderChangeLogic::getUserPauses($this->userId, $params);
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
     * @notes 获取暂停类型选项
     * @return \think\response\Json
     */
    public function pauseTypeOptions()
    {
        $result = OrderChangeLogic::getPauseTypeOptions();
        return $this->data($result);
    }

    /**
     * @notes 获取时间段选项
     * @return \think\response\Json
     */
    public function timeSlotOptions()
    {
        return $this->fail('场次能力已下线');
    }
}
