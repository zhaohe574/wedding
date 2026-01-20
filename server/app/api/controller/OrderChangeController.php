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
 * 支持用户申请改期、换人、加项、转让、暂停
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
        $params = (new OrderChangeValidate())->goCheck('detail');
        $result = OrderChangeLogic::getChangeDetail($params['id'], $this->userId);
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
        $params = (new OrderChangeValidate())->goCheck('checkOrder');
        $result = OrderChangeLogic::checkCanChange($params['order_id'], $this->userId);
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
            $params['order_id'],
            $params['new_date'],
            $params['new_time_slot'],
            $params['reason'] ?? '',
            $params['attach_images'] ?? []
        );
        if ($result['success']) {
            return $this->success($result['message'], ['change_id' => $result['change_id']]);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 申请换人
     * @return \think\response\Json
     */
    public function applyStaffChange()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('staffChange');
        $result = OrderChangeLogic::applyStaffChange(
            $this->userId,
            $params['order_id'],
            $params['order_item_id'],
            $params['new_staff_id'],
            $params['reason'] ?? '',
            $params['attach_images'] ?? []
        );
        if ($result['success']) {
            return $this->success($result['message'], [
                'change_id' => $result['change_id'],
                'price_diff' => $result['price_diff'] ?? 0,
            ]);
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
            $params['order_id'],
            $params['staff_id'],
            $params['package_id'],
            $params['service_date'],
            $params['time_slot'],
            $params['reason'] ?? ''
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
        $result = OrderChangeLogic::cancelChange($params['id'], $this->userId);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 申请转让
     * @return \think\response\Json
     */
    public function applyTransfer()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('transfer');
        $result = OrderChangeLogic::applyTransfer(
            $this->userId,
            $params['order_id'],
            $params['to_user_name'],
            $params['to_user_mobile'],
            $params['reason'] ?? ''
        );
        if ($result['success']) {
            return $this->success($result['message'], ['transfer_id' => $result['transfer_id']]);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 取消转让申请
     * @return \think\response\Json
     */
    public function cancelTransfer()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('transferDetail');
        $result = OrderChangeLogic::cancelTransfer($params['id'], $this->userId);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 接收转让（接收方）
     * @return \think\response\Json
     */
    public function acceptTransfer()
    {
        $params = (new OrderChangeValidate())->post()->goCheck('acceptTransfer');
        $result = OrderChangeLogic::acceptTransfer(
            $params['id'],
            $params['mobile'],
            $params['code']
        );
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 转让详情
     * @return \think\response\Json
     */
    public function transferDetail()
    {
        $params = (new OrderChangeValidate())->goCheck('transferDetail');
        $result = OrderChangeLogic::getTransferDetail($params['id'], $this->userId);
        if ($result === null) {
            return $this->fail('转让记录不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 我的转让列表
     * @return \think\response\Json
     */
    public function transferLists()
    {
        $params = $this->request->get();
        $result = OrderChangeLogic::getUserTransfers($this->userId, $params);
        return $this->data($result);
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
            $params['order_id'],
            $params['pause_type'],
            $params['reason'],
            $params['start_date'],
            $params['end_date'],
            $params['proof_images'] ?? []
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
        $result = OrderChangeLogic::cancelPause($params['id'], $this->userId);
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
        $params = (new OrderChangeValidate())->goCheck('pauseDetail');
        $result = OrderChangeLogic::getPauseDetail($params['id'], $this->userId);
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
        $result = OrderChangeLogic::getTimeSlotOptions();
        return $this->data($result);
    }
}
