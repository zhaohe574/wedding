<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 吉日设置控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\schedule;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\schedule\CalendarEventLists;
use app\adminapi\logic\schedule\CalendarEventLogic;
use app\adminapi\validate\schedule\CalendarEventValidate;

/**
 * 吉日设置控制器
 * Class CalendarEventController
 * @package app\adminapi\controller\schedule
 */
class CalendarEventController extends BaseAdminController
{
    /**
     * @notes 吉日列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new CalendarEventLists());
    }

    /**
     * @notes 吉日详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new CalendarEventValidate())->get()->goCheck('detail');
        return $this->data(CalendarEventLogic::detail((int)$params['id']));
    }

    /**
     * @notes 保存吉日设置
     * @return \think\response\Json
     */
    public function save()
    {
        $params = (new CalendarEventValidate())->post()->goCheck('save');
        $result = CalendarEventLogic::save($params);
        if ($result === true) {
            return $this->success('保存成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 删除吉日设置
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new CalendarEventValidate())->post()->goCheck('delete');
        $result = CalendarEventLogic::delete((int)$params['id']);
        if ($result === true) {
            return $this->success('删除成功');
        }
        return $this->fail($result);
    }

    /**
     * @notes 批量保存吉日设置
     * @return \think\response\Json
     */
    public function batchSave()
    {
        $params = (new CalendarEventValidate())->post()->goCheck('batchSave');
        $result = CalendarEventLogic::batchSave($params);
        if (is_int($result)) {
            return $this->success('成功设置 ' . $result . ' 天吉日信息');
        }
        return $this->fail($result);
    }

    /**
     * @notes 拥堵等级选项
     * @return \think\response\Json
     */
    public function congestionLevelOptions()
    {
        return $this->data(CalendarEventLogic::congestionLevelOptions());
    }
}
