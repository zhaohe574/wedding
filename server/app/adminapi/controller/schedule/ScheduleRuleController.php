<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 档期规则管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\schedule;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\schedule\ScheduleRuleLists;
use app\adminapi\logic\schedule\ScheduleRuleLogic;
use app\adminapi\validate\schedule\ScheduleRuleValidate;

/**
 * 档期规则管理控制器
 * Class ScheduleRuleController
 * @package app\adminapi\controller\schedule
 */
class ScheduleRuleController extends BaseAdminController
{
    /**
     * @notes 规则列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new ScheduleRuleLists());
    }

    /**
     * @notes 规则详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new ScheduleRuleValidate())->goCheck('detail');
        $result = ScheduleRuleLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 添加规则
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new ScheduleRuleValidate())->post()->goCheck('add');
        $result = ScheduleRuleLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功');
        }
        return $this->fail(ScheduleRuleLogic::getError());
    }

    /**
     * @notes 编辑规则
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new ScheduleRuleValidate())->post()->goCheck('edit');
        $result = ScheduleRuleLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功');
        }
        return $this->fail(ScheduleRuleLogic::getError());
    }

    /**
     * @notes 删除规则
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new ScheduleRuleValidate())->post()->goCheck('delete');
        $result = ScheduleRuleLogic::delete($params);
        if (true === $result) {
            return $this->success('删除成功');
        }
        return $this->fail(ScheduleRuleLogic::getError());
    }

    /**
     * @notes 切换启用状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new ScheduleRuleValidate())->post()->goCheck('status');
        $result = ScheduleRuleLogic::changeStatus($params);
        if (true === $result) {
            return $this->success('操作成功');
        }
        return $this->fail(ScheduleRuleLogic::getError());
    }

    /**
     * @notes 获取全局规则
     * @return \think\response\Json
     */
    public function globalRule()
    {
        $result = ScheduleRuleLogic::getGlobalRule();
        return $this->data($result);
    }

    /**
     * @notes 获取工作人员规则
     * @return \think\response\Json
     */
    public function staffRule()
    {
        $params = (new ScheduleRuleValidate())->goCheck('staffRule');
        $result = ScheduleRuleLogic::getStaffRule($params['staff_id']);
        return $this->data($result);
    }
}
