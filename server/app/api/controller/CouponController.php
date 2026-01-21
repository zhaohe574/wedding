<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端优惠券控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\CouponLogic;
use app\api\validate\CouponValidate;

/**
 * 小程序端优惠券控制器
 * Class CouponController
 * @package app\api\controller
 */
class CouponController extends BaseApiController
{
    public array $notNeedLogin = ['availableList', 'detail'];

    /**
     * @notes 可领取的优惠券列表
     * @return \think\response\Json
     */
    public function availableList()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId ?? 0;
        $result = CouponLogic::availableList($params);
        return $this->success('', $result);
    }

    /**
     * @notes 优惠券详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new CouponValidate())->goCheck('detail');
        $params['user_id'] = $this->userId ?? 0;
        $result = CouponLogic::detail($params);
        if ($result === false) {
            return $this->fail(CouponLogic::getError());
        }
        return $this->success('', $result);
    }

    /**
     * @notes 领取优惠券
     * @return \think\response\Json
     */
    public function receive()
    {
        $params = (new CouponValidate())->post()->goCheck('receive');
        $params['user_id'] = $this->userId;
        $result = CouponLogic::receive($params);
        if ($result === false) {
            return $this->fail(CouponLogic::getError());
        }
        return $this->success('领取成功', $result);
    }

    /**
     * @notes 我的优惠券列表
     * @return \think\response\Json
     */
    public function myCoupons()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId;
        $result = CouponLogic::myCoupons($params);
        return $this->success('', $result);
    }

    /**
     * @notes 我的优惠券数量统计
     * @return \think\response\Json
     */
    public function myStats()
    {
        $result = CouponLogic::myStats($this->userId);
        return $this->success('', $result);
    }

    /**
     * @notes 订单可用优惠券列表
     * @return \think\response\Json
     */
    public function orderAvailable()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId;
        $result = CouponLogic::orderAvailable($params);
        return $this->success('', $result);
    }

    /**
     * @notes 计算优惠金额
     * @return \think\response\Json
     */
    public function calculate()
    {
        $params = (new CouponValidate())->post()->goCheck('calculate');
        $params['user_id'] = $this->userId;
        $result = CouponLogic::calculate($params);
        if ($result === false) {
            return $this->fail(CouponLogic::getError());
        }
        return $this->success('', $result);
    }

    /**
     * @notes 使用优惠券（下单时调用）
     * @return \think\response\Json
     */
    public function use()
    {
        $params = (new CouponValidate())->post()->goCheck('use');
        $params['user_id'] = $this->userId;
        $result = CouponLogic::useCoupon($params);
        if ($result === false) {
            return $this->fail(CouponLogic::getError());
        }
        return $this->success('使用成功', $result);
    }

    /**
     * @notes 兑换码兑换优惠券
     * @return \think\response\Json
     */
    public function exchange()
    {
        $params = (new CouponValidate())->post()->goCheck('exchange');
        $params['user_id'] = $this->userId;
        $result = CouponLogic::exchange($params);
        if ($result === false) {
            return $this->fail(CouponLogic::getError());
        }
        return $this->success('兑换成功', $result);
    }
}
