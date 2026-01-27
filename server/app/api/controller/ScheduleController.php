<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端档期控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\ScheduleLogic;
use app\api\validate\ScheduleValidate;

/**
 * 小程序端档期控制器
 * Class ScheduleController
 * @package app\api\controller
 */
class ScheduleController extends BaseApiController
{
    public array $notNeedLogin = ['staffSchedule', 'monthCalendar', 'luckyDays', 'checkAvailable'];

    /**
     * @notes 获取工作人员档期
     * @return \think\response\Json
     */
    public function staffSchedule()
    {
        $params = (new ScheduleValidate())->goCheck('staffSchedule');
        $result = ScheduleLogic::getStaffSchedule($params);
        return $this->data($result);
    }

    /**
     * @notes 获取月度日历（含吉日标记）
     * @return \think\response\Json
     */
    public function monthCalendar()
    {
        $params = (new ScheduleValidate())->goCheck('calendar');
        $result = ScheduleLogic::getMonthCalendar($params);
        return $this->data($result);
    }

    /**
     * @notes 获取吉日列表
     * @return \think\response\Json
     */
    public function luckyDays()
    {
        $params = $this->request->get();
        $result = ScheduleLogic::getLuckyDays($params);
        return $this->data($result);
    }

    /**
     * @notes 检查档期是否可预约
     * @return \think\response\Json
     */
    public function checkAvailable()
    {
        $params = (new ScheduleValidate())->goCheck('check');
        $result = ScheduleLogic::checkAvailable($params);
        return $this->data($result);
    }

    /**
     * @notes 锁定档期（加入购物车时调用）
     * @return \think\response\Json
     */
    public function lockSchedule()
    {
        $params = (new ScheduleValidate())->post()->goCheck('lock');
        $params['user_id'] = $this->userId;
        $result = ScheduleLogic::lockSchedule($params);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 释放锁定
     * @return \think\response\Json
     */
    public function releaseLock()
    {
        $params = (new ScheduleValidate())->post()->goCheck('release');
        $params['user_id'] = $this->userId;
        $result = ScheduleLogic::releaseLock($params);
        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 加入候补
     * @return \think\response\Json
     */
    public function joinWaitlist()
    {
        $params = (new ScheduleValidate())->post()->goCheck('waitlist');
        $params['user_id'] = $this->userId;
        $result = ScheduleLogic::joinWaitlist($params);
        if ($result['success']) {
            return $this->success($result['message'], ['waitlist_id' => $result['waitlist_id']]);
        }
        return $this->fail($result['message']);
    }

    /**
     * @notes 我的候补列表
     * @return \think\response\Json
     */
    public function myWaitlist()
    {
        $params = $this->request->get();
        $result = ScheduleLogic::getUserWaitlist($this->userId, $params);
        return $this->data($result);
    }

    /**
     * @notes 取消候补
     * @return \think\response\Json
     */
    public function cancelWaitlist()
    {
        $params = (new ScheduleValidate())->post()->goCheck('cancelWaitlist');
        // 确保 id 是整数类型
        $result = ScheduleLogic::cancelWaitlist((int)$params['id'], $this->userId);
        // $result 是索引数组 [bool, string]
        if ($result[0]) {
            return $this->success($result[1]);
        }
        return $this->fail($result[1]);
    }
}
