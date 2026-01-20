<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 时间轴管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\timeline;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\timeline\TimelineLists;
use app\adminapi\logic\timeline\TimelineLogic;
use app\adminapi\validate\timeline\TimelineValidate;

/**
 * 时间轴管理控制器
 * Class TimelineController
 * @package app\adminapi\controller\timeline
 */
class TimelineController extends BaseAdminController
{
    /**
     * @notes 时间轴任务列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new TimelineLists());
    }

    /**
     * @notes 任务详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new TimelineValidate())->goCheck('detail');
        $result = TimelineLogic::detail($params['id']);
        if ($result === null) {
            return $this->fail('任务不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 获取订单的时间轴
     * @return \think\response\Json
     */
    public function orderTimeline()
    {
        $params = (new TimelineValidate())->goCheck('orderTimeline');
        $result = TimelineLogic::getOrderTimeline($params['order_id']);
        return $this->data($result);
    }

    /**
     * @notes 添加任务
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new TimelineValidate())->post()->goCheck('add');
        $result = TimelineLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功');
        }
        return $this->fail(TimelineLogic::getError());
    }

    /**
     * @notes 编辑任务
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new TimelineValidate())->post()->goCheck('edit');
        $result = TimelineLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功');
        }
        return $this->fail(TimelineLogic::getError());
    }

    /**
     * @notes 删除任务
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new TimelineValidate())->post()->goCheck('delete');
        $result = TimelineLogic::delete($params['id']);
        if (true === $result) {
            return $this->success('删除成功');
        }
        return $this->fail(TimelineLogic::getError());
    }

    /**
     * @notes 完成任务
     * @return \think\response\Json
     */
    public function complete()
    {
        $params = (new TimelineValidate())->post()->goCheck('complete');
        $result = TimelineLogic::complete($params['id'], $this->adminId, $params['complete_remark'] ?? '');
        if (true === $result) {
            return $this->success('任务已完成');
        }
        return $this->fail(TimelineLogic::getError());
    }

    /**
     * @notes 取消完成状态
     * @return \think\response\Json
     */
    public function uncomplete()
    {
        $params = (new TimelineValidate())->post()->goCheck('delete');
        $result = TimelineLogic::uncomplete($params['id']);
        if (true === $result) {
            return $this->success('已取消完成状态');
        }
        return $this->fail(TimelineLogic::getError());
    }

    /**
     * @notes 根据模板生成时间轴
     * @return \think\response\Json
     */
    public function generate()
    {
        $params = (new TimelineValidate())->post()->goCheck('generate');
        $result = TimelineLogic::generateFromTemplate($params['order_id'], $params['template_id'] ?? 0);
        if (true === $result) {
            return $this->success('时间轴生成成功');
        }
        return $this->fail(TimelineLogic::getError());
    }

    /**
     * @notes 清除系统生成的任务
     * @return \think\response\Json
     */
    public function clearSystemTasks()
    {
        $params = (new TimelineValidate())->post()->goCheck('orderTimeline');
        $result = TimelineLogic::clearSystemTasks($params['order_id']);
        if (true === $result) {
            return $this->success('清除成功');
        }
        return $this->fail(TimelineLogic::getError());
    }

    /**
     * @notes 获取时间轴统计
     * @return \think\response\Json
     */
    public function stats()
    {
        $params = (new TimelineValidate())->goCheck('orderTimeline');
        $result = TimelineLogic::getStats($params['order_id']);
        return $this->data($result);
    }

    /**
     * @notes 获取可用模板列表
     * @return \think\response\Json
     */
    public function templates()
    {
        $result = TimelineLogic::getTemplates();
        return $this->data($result);
    }
}
