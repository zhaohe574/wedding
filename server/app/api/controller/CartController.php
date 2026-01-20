<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端购物车控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\CartLogic;
use app\api\validate\CartValidate;

/**
 * 小程序端购物车控制器
 * Class CartController
 * @package app\api\controller
 */
class CartController extends BaseApiController
{
    /**
     * @notes 获取购物车列表
     * @return \think\response\Json
     */
    public function lists()
    {
        $result = CartLogic::getUserCart($this->userId);
        return $this->data($result);
    }

    /**
     * @notes 添加到购物车
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new CartValidate())->post()->goCheck('add');
        $params['user_id'] = $this->userId;
        $result = CartLogic::addToCart($params);
        if ($result['success']) {
            return $this->success($result['message'], ['cart_id' => $result['cart_id']]);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 更新购物车项
     * @return \think\response\Json
     */
    public function update()
    {
        $params = (new CartValidate())->post()->goCheck('update');
        $result = CartLogic::updateCartItem($params['id'], $this->userId, $params);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 删除购物车项
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new CartValidate())->post()->goCheck('delete');
        $result = CartLogic::removeFromCart($params['id'], $this->userId);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 批量删除
     * @return \think\response\Json
     */
    public function batchDelete()
    {
        $params = (new CartValidate())->post()->goCheck('batchDelete');
        $count = CartLogic::batchRemove($params['ids'], $this->userId);
        return $this->success('成功删除 ' . $count . ' 项');
    }

    /**
     * @notes 切换选中状态
     * @return \think\response\Json
     */
    public function toggleSelect()
    {
        $params = (new CartValidate())->post()->goCheck('delete');
        $result = CartLogic::toggleSelect($params['id'], $this->userId);
        return $result ? $this->success('操作成功') : $this->fail('操作失败');
    }

    /**
     * @notes 全选/取消全选
     * @return \think\response\Json
     */
    public function selectAll()
    {
        $params = $this->request->post();
        $selected = !empty($params['selected']);
        CartLogic::selectAll($this->userId, $selected);
        return $this->success('操作成功');
    }

    /**
     * @notes 计算总价
     * @return \think\response\Json
     */
    public function calculate()
    {
        $result = CartLogic::calculateTotal($this->userId);
        return $this->data($result);
    }

    /**
     * @notes 检查冲突
     * @return \think\response\Json
     */
    public function checkConflicts()
    {
        $result = CartLogic::checkConflicts($this->userId);
        return $this->data($result);
    }

    /**
     * @notes 清空购物车
     * @return \think\response\Json
     */
    public function clear()
    {
        $count = CartLogic::clearCart($this->userId);
        return $this->success('已清空 ' . $count . ' 项');
    }

    /**
     * @notes 获取购物车数量
     * @return \think\response\Json
     */
    public function count()
    {
        $count = CartLogic::getCartCount($this->userId);
        return $this->data(['count' => $count]);
    }

    /**
     * @notes 生成分享码
     * @return \think\response\Json
     */
    public function generateShareCode()
    {
        $params = (new CartValidate())->post()->goCheck('delete');
        $shareCode = CartLogic::generateShareCode($params['id'], $this->userId);
        if ($shareCode) {
            return $this->data(['share_code' => $shareCode]);
        }
        return $this->fail('生成分享码失败');
    }

    /**
     * @notes 通过分享码获取购物车项
     * @return \think\response\Json
     */
    public function getByShareCode()
    {
        $params = (new CartValidate())->goCheck('shareCode');
        $result = CartLogic::getByShareCode($params['share_code']);
        if ($result) {
            return $this->data($result);
        }
        return $this->fail('分享码无效或已过期');
    }

    /**
     * @notes 保存为方案
     * @return \think\response\Json
     */
    public function savePlan()
    {
        $params = (new CartValidate())->post()->goCheck('savePlan');
        $params['user_id'] = $this->userId;
        $result = CartLogic::createPlan($params);
        if ($result['success']) {
            return $this->success($result['message'], ['plan_id' => $result['plan_id']]);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 获取我的方案列表
     * @return \think\response\Json
     */
    public function myPlans()
    {
        $result = CartLogic::getUserPlans($this->userId);
        return $this->data($result);
    }

    /**
     * @notes 获取方案详情
     * @return \think\response\Json
     */
    public function planDetail()
    {
        $params = (new CartValidate())->goCheck('planDetail');
        $result = CartLogic::getPlanDetail($params['plan_id'], $this->userId);
        if ($result) {
            return $this->data($result);
        }
        return $this->fail('方案不存在');
    }

    /**
     * @notes 删除方案
     * @return \think\response\Json
     */
    public function deletePlan()
    {
        $params = (new CartValidate())->post()->goCheck('planDetail');
        $result = CartLogic::deletePlan($params['plan_id'], $this->userId);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 设为默认方案
     * @return \think\response\Json
     */
    public function setDefaultPlan()
    {
        $params = (new CartValidate())->post()->goCheck('planDetail');
        $result = CartLogic::setDefaultPlan($params['plan_id'], $this->userId);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 通过分享码复制方案
     * @return \think\response\Json
     */
    public function copyPlanByShareCode()
    {
        $params = (new CartValidate())->post()->goCheck('shareCode');
        $result = CartLogic::copyPlanToCart($params['share_code'], $this->userId);
        if ($result['success']) {
            return $this->success($result['message'], ['copied_count' => $result['copied_count']]);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 比较方案
     * @return \think\response\Json
     */
    public function comparePlans()
    {
        $params = (new CartValidate())->goCheck('compare');
        $result = CartLogic::comparePlans($params['plan_id_1'], $params['plan_id_2'], $this->userId);
        if ($result) {
            return $this->data($result);
        }
        return $this->fail('方案不存在');
    }
}
