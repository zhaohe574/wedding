<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 预约管理控制器（服务人员中心）
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\schedule;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\schedule\BookingLists;
use app\adminapi\logic\schedule\BookingLogic;
use app\adminapi\validate\schedule\BookingValidate;
use app\common\service\StaffService;

/**
 * 预约管理控制器（服务人员 my* 接口）
 * Class BookingController
 * @package app\adminapi\controller\schedule
 */
class BookingController extends BaseAdminController
{
    /**
     * @notes 我的预约列表
     * @return \think\response\Json
     */
    public function myBookings()
    {
        if ($this->getRequiredStaffScopeId() <= 0) {
            return $this->failRequiredStaffScope();
        }
        return $this->dataLists(new BookingLists());
    }

    /**
     * @notes 我的预约详情
     * @return \think\response\Json
     */
    public function myBookingDetail()
    {
        $staffId = $this->getRequiredStaffScopeId();
        if ($staffId <= 0) {
            return $this->failRequiredStaffScope();
        }

        $params = (new BookingValidate())->goCheck('detail');
        $detail = BookingLogic::detail($staffId, (int)$params['id']);
        if ($detail === null) {
            return $this->fail(BookingLogic::getError() ?: '预约项不存在');
        }
        return $this->data($detail);
    }

    /**
     * @notes 我的预约确认
     * @return \think\response\Json
     */
    public function myBookingConfirm()
    {
        $staffId = $this->getRequiredStaffScopeId();
        if ($staffId <= 0) {
            return $this->failRequiredStaffScope();
        }

        $params = (new BookingValidate())->post()->goCheck('confirm');
        $result = BookingLogic::confirm($staffId, $this->adminId, (int)$params['id']);
        if ($result) {
            return $this->success('确认成功', [], 1, 1);
        }
        return $this->fail(BookingLogic::getError() ?: '确认失败');
    }

    /**
     * @notes 我的预约取消（仅取消本人项）
     * @return \think\response\Json
     */
    public function myBookingCancel()
    {
        $staffId = $this->getRequiredStaffScopeId();
        if ($staffId <= 0) {
            return $this->failRequiredStaffScope();
        }

        $params = (new BookingValidate())->post()->goCheck('cancel');
        $result = BookingLogic::cancel(
            $staffId,
            $this->adminId,
            (int)$params['id'],
            (string)($params['reason'] ?? '')
        );
        if ($result) {
            return $this->success('取消成功', [], 1, 1);
        }
        return $this->fail(BookingLogic::getError() ?: '取消失败');
    }

    /**
     * @notes 我的预约统计
     * @return \think\response\Json
     */
    public function myBookingStatistics()
    {
        $staffId = $this->getRequiredStaffScopeId();
        if ($staffId <= 0) {
            return $this->failRequiredStaffScope();
        }

        $result = BookingLogic::statistics($staffId);
        return $this->data($result);
    }

    /**
     * @notes 获取服务人员范围ID（必须是服务人员）
     * @return int
     */
    protected function getRequiredStaffScopeId(): int
    {
        return StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
    }
}
