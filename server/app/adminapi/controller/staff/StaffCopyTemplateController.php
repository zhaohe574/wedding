<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 平台统一话术模板控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\staff;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\staff\StaffCopyTemplateLists;
use app\adminapi\logic\staff\StaffCopyTemplateLogic;
use app\adminapi\validate\staff\StaffCopyTemplateValidate;
use app\common\service\StaffService;
use think\response\Json;

/**
 * 平台统一话术模板控制器（staff_id=0，仅平台管理员可维护）
 */
class StaffCopyTemplateController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists(new StaffCopyTemplateLists());
    }

    public function detail()
    {
        $params = (new StaffCopyTemplateValidate())->get()->goCheck('detail');
        $result = StaffCopyTemplateLogic::detail((int)$params['id']);
        if (empty($result)) {
            return $this->fail(StaffCopyTemplateLogic::getError());
        }
        return $this->data($result);
    }

    public function add()
    {
        if ($deny = $this->denyStaffRole()) {
            return $deny;
        }
        $params = (new StaffCopyTemplateValidate())->post()->goCheck('add');
        if (StaffCopyTemplateLogic::add($params)) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(StaffCopyTemplateLogic::getError());
    }

    public function edit()
    {
        if ($deny = $this->denyStaffRole()) {
            return $deny;
        }
        $params = (new StaffCopyTemplateValidate())->post()->goCheck('edit');
        if (StaffCopyTemplateLogic::edit($params)) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(StaffCopyTemplateLogic::getError());
    }

    public function delete()
    {
        if ($deny = $this->denyStaffRole()) {
            return $deny;
        }
        $params = (new StaffCopyTemplateValidate())->post()->goCheck('delete');
        if (StaffCopyTemplateLogic::delete($params)) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffCopyTemplateLogic::getError());
    }

    public function changeStatus()
    {
        if ($deny = $this->denyStaffRole()) {
            return $deny;
        }
        $params = (new StaffCopyTemplateValidate())->post()->goCheck('status');
        if (StaffCopyTemplateLogic::changeStatus($params)) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(StaffCopyTemplateLogic::getError());
    }

    public function sceneOptions()
    {
        return $this->data(StaffCopyTemplateLogic::sceneOptions());
    }

    public function importDefaults()
    {
        if ($deny = $this->denyStaffRole()) {
            return $deny;
        }
        if (!$this->request->isPost()) {
            return $this->fail('请求方式错误');
        }
        return $this->data(StaffCopyTemplateLogic::importDefaults());
    }

    /**
     * @notes 服务人员角色禁止维护平台统一话术
     */
    private function denyStaffRole(): ?Json
    {
        if (StaffService::isStaffRole($this->adminInfo)) {
            return $this->fail('仅平台管理员可维护统一话术模板');
        }
        return null;
    }
}
