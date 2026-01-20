<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 日历事件（黄历/吉日）管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\schedule;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\schedule\CalendarEventLists;
use app\adminapi\logic\schedule\CalendarEventLogic;
use app\adminapi\validate\schedule\CalendarEventValidate;

/**
 * 日历事件（黄历/吉日）管理控制器
 * Class CalendarEventController
 * @package app\adminapi\controller\schedule
 */
class CalendarEventController extends BaseAdminController
{
    /**
     * @notes 事件列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new CalendarEventLists());
    }

    /**
     * @notes 获取月度日历
     * @return \think\response\Json
     */
    public function monthCalendar()
    {
        $params = (new CalendarEventValidate())->goCheck('calendar');
        $result = CalendarEventLogic::getMonthCalendar($params);
        return $this->data($result);
    }

    /**
     * @notes 事件详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new CalendarEventValidate())->goCheck('detail');
        $result = CalendarEventLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 添加事件
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new CalendarEventValidate())->post()->goCheck('add');
        $result = CalendarEventLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功');
        }
        return $this->fail(CalendarEventLogic::getError());
    }

    /**
     * @notes 编辑事件
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new CalendarEventValidate())->post()->goCheck('edit');
        $result = CalendarEventLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功');
        }
        return $this->fail(CalendarEventLogic::getError());
    }

    /**
     * @notes 删除事件
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new CalendarEventValidate())->post()->goCheck('delete');
        $result = CalendarEventLogic::delete($params);
        if (true === $result) {
            return $this->success('删除成功');
        }
        return $this->fail(CalendarEventLogic::getError());
    }

    /**
     * @notes 获取吉日列表
     * @return \think\response\Json
     */
    public function luckyDays()
    {
        $params = $this->request->get();
        $result = CalendarEventLogic::getLuckyDays($params);
        return $this->data($result);
    }

    /**
     * @notes 获取节假日列表
     * @return \think\response\Json
     */
    public function holidays()
    {
        $params = $this->request->get();
        $result = CalendarEventLogic::getHolidays($params);
        return $this->data($result);
    }

    /**
     * @notes 批量导入
     * @return \think\response\Json
     */
    public function batchImport()
    {
        $params = (new CalendarEventValidate())->post()->goCheck('import');
        $result = CalendarEventLogic::batchImport($params);
        if ($result !== false) {
            return $this->success('成功导入 ' . $result['success'] . ' 条，失败 ' . $result['failed'] . ' 条');
        }
        return $this->fail(CalendarEventLogic::getError());
    }

    /**
     * @notes 获取拥堵等级选项
     * @return \think\response\Json
     */
    public function congestionOptions()
    {
        $result = CalendarEventLogic::getCongestionOptions();
        return $this->data($result);
    }
}
