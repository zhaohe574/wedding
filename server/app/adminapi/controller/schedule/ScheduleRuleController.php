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
use app\common\model\schedule\ScheduleRule;
use app\common\service\StaffService;

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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) ScheduleRule::where('id', $params['id'])->value('staff_id');
            if ($staffId !== $staffScopeId) {
                return $this->fail('无权限查看');
            }
        }
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $params['staff_id'] = $staffScopeId;
        }
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) ScheduleRule::where('id', $params['id'])->value('staff_id');
            if ($staffId !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
            $params['staff_id'] = $staffScopeId;
        }
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) ScheduleRule::where('id', $params['id'])->value('staff_id');
            if ($staffId !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
        }
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) ScheduleRule::where('id', $params['id'])->value('staff_id');
            if ($staffId !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
        }
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            return $this->fail('无权限查看');
        }
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        $staffId = $staffScopeId > 0 ? $staffScopeId : (int) $params['staff_id'];
        $result = ScheduleRuleLogic::getStaffRule($staffId);
        return $this->data($result);
    }
}
