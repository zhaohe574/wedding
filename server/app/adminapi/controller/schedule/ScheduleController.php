<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\schedule;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\schedule\ScheduleLists;
use app\adminapi\logic\schedule\ScheduleLogic;
use app\adminapi\validate\schedule\ScheduleValidate;

/**
 * 档期管理控制器
 * Class ScheduleController
 * @package app\adminapi\controller\schedule
 */
class ScheduleController extends BaseAdminController
{
    /**
     * @notes 档期列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new ScheduleLists());
    }

    /**
     * @notes 获取月度档期（日历视图）
     * @return \think\response\Json
     */
    public function monthCalendar()
    {
        $params = (new ScheduleValidate())->goCheck('calendar');
        $result = ScheduleLogic::getMonthCalendar($params);
        return $this->data($result);
    }

    /**
     * @notes 档期详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new ScheduleValidate())->goCheck('detail');
        $result = ScheduleLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 设置档期状态
     * @return \think\response\Json
     */
    public function setStatus()
    {
        $params = (new ScheduleValidate())->post()->goCheck('setStatus');
        $result = ScheduleLogic::setStatus($params);
        if (true === $result) {
            return $this->success('设置成功');
        }
        return $this->fail(ScheduleLogic::getError());
    }

    /**
     * @notes 批量设置档期
     * @return \think\response\Json
     */
    public function batchSet()
    {
        $params = (new ScheduleValidate())->post()->goCheck('batchSet');
        $result = ScheduleLogic::batchSet($params);
        if ($result !== false) {
            return $this->success('成功设置 ' . $result . ' 条档期');
        }
        return $this->fail(ScheduleLogic::getError());
    }

    /**
     * @notes 锁定档期
     * @return \think\response\Json
     */
    public function lock()
    {
        $params = (new ScheduleValidate())->post()->goCheck('lock');
        $params['admin_id'] = $this->adminId;
        $result = ScheduleLogic::lockSchedule($params);
        if (true === $result) {
            return $this->success('锁定成功');
        }
        return $this->fail(ScheduleLogic::getError());
    }

    /**
     * @notes 释放锁定
     * @return \think\response\Json
     */
    public function unlock()
    {
        $params = (new ScheduleValidate())->post()->goCheck('unlock');
        $params['admin_id'] = $this->adminId;
        $result = ScheduleLogic::unlockSchedule($params);
        if (true === $result) {
            return $this->success('释放成功');
        }
        return $this->fail(ScheduleLogic::getError());
    }

    /**
     * @notes 内部预留
     * @return \think\response\Json
     */
    public function reserve()
    {
        $params = (new ScheduleValidate())->post()->goCheck('reserve');
        $params['admin_id'] = $this->adminId;
        $result = ScheduleLogic::reserveSchedule($params);
        if (true === $result) {
            return $this->success('预留成功');
        }
        return $this->fail(ScheduleLogic::getError());
    }

    /**
     * @notes 获取锁定记录
     * @return \think\response\Json
     */
    public function lockRecords()
    {
        $params = $this->request->get();
        $result = ScheduleLogic::getLockRecords($params);
        return $this->data($result);
    }

    /**
     * @notes 获取时间段选项
     * @return \think\response\Json
     */
    public function timeSlotOptions()
    {
        $result = ScheduleLogic::getTimeSlotOptions();
        return $this->data($result);
    }

    /**
     * @notes 获取状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        $result = ScheduleLogic::getStatusOptions();
        return $this->data($result);
    }

    /**
     * @notes 档期统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = ScheduleLogic::statistics($params);
        return $this->data($result);
    }
}
