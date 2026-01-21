<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 优惠券管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\coupon;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\coupon\CouponLogic;
use app\adminapi\lists\coupon\CouponLists;
use app\adminapi\lists\coupon\UserCouponLists;
use app\adminapi\validate\coupon\CouponValidate;

/**
 * 优惠券管理控制器
 * Class CouponController
 * @package app\adminapi\controller\coupon
 */
class CouponController extends BaseAdminController
{
    /**
     * @notes 优惠券列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new CouponLists());
    }

    /**
     * @notes 优惠券详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new CouponValidate())->goCheck('detail');
        $result = CouponLogic::detail($params['id']);
        return $this->success('', $result);
    }

    /**
     * @notes 添加优惠券
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new CouponValidate())->post()->goCheck('add');
        $result = CouponLogic::add($params);
        if ($result === true) {
            return $this->success('添加成功');
        }
        return $this->fail(CouponLogic::getError());
    }

    /**
     * @notes 编辑优惠券
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new CouponValidate())->post()->goCheck('edit');
        $result = CouponLogic::edit($params);
        if ($result === true) {
            return $this->success('编辑成功');
        }
        return $this->fail(CouponLogic::getError());
    }

    /**
     * @notes 删除优惠券
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new CouponValidate())->post()->goCheck('detail');
        $result = CouponLogic::delete($params['id']);
        if ($result === true) {
            return $this->success('删除成功');
        }
        return $this->fail(CouponLogic::getError());
    }

    /**
     * @notes 启用/禁用优惠券
     * @return \think\response\Json
     */
    public function toggleStatus()
    {
        $params = (new CouponValidate())->post()->goCheck('detail');
        $result = CouponLogic::toggleStatus($params['id']);
        if ($result === true) {
            return $this->success('操作成功');
        }
        return $this->fail(CouponLogic::getError());
    }

    /**
     * @notes 手动发放优惠券
     * @return \think\response\Json
     */
    public function send()
    {
        $params = (new CouponValidate())->post()->goCheck('send');
        $result = CouponLogic::send($params);
        if ($result === true) {
            return $this->success('发放成功');
        }
        return $this->fail(CouponLogic::getError());
    }

    /**
     * @notes 批量发放优惠券
     * @return \think\response\Json
     */
    public function batchSend()
    {
        $params = (new CouponValidate())->post()->goCheck('batchSend');
        $result = CouponLogic::batchSend($params);
        if ($result === true) {
            return $this->success('批量发放成功');
        }
        return $this->fail(CouponLogic::getError());
    }

    /**
     * @notes 用户优惠券列表
     * @return \think\response\Json
     */
    public function userCouponLists()
    {
        return $this->dataLists(new UserCouponLists());
    }

    /**
     * @notes 撤回用户优惠券
     * @return \think\response\Json
     */
    public function revoke()
    {
        $params = (new CouponValidate())->post()->goCheck('revoke');
        $result = CouponLogic::revoke($params['user_coupon_id']);
        if ($result === true) {
            return $this->success('撤回成功');
        }
        return $this->fail(CouponLogic::getError());
    }

    /**
     * @notes 优惠券统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = CouponLogic::statistics($params);
        return $this->success('', $result);
    }

    /**
     * @notes 优惠券使用统计
     * @return \think\response\Json
     */
    public function useStatistics()
    {
        $params = $this->request->get();
        $result = CouponLogic::useStatistics($params);
        return $this->success('', $result);
    }

    /**
     * @notes 优惠券排行榜
     * @return \think\response\Json
     */
    public function ranking()
    {
        $params = $this->request->get();
        $result = CouponLogic::ranking($params);
        return $this->success('', $result);
    }

    /**
     * @notes 获取优惠券类型选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        $result = CouponLogic::typeOptions();
        return $this->success('', $result);
    }

    /**
     * @notes 获取启用的优惠券列表（下拉选择用）
     * @return \think\response\Json
     */
    public function enabledList()
    {
        $result = CouponLogic::enabledList();
        return $this->success('', $result);
    }
}
