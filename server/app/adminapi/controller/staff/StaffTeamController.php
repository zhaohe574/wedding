<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务队伍控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\staff;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\staff\StaffTeamLists;
use app\adminapi\logic\staff\StaffTeamLogic;
use app\adminapi\validate\staff\StaffTeamValidate;

/**
 * 服务队伍控制器
 */
class StaffTeamController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists(new StaffTeamLists());
    }

    public function detail()
    {
        $params = (new StaffTeamValidate())->get()->goCheck('detail');
        return $this->data(StaffTeamLogic::detail((int)$params['id']));
    }

    public function add()
    {
        $params = (new StaffTeamValidate())->post()->goCheck('add');
        if (StaffTeamLogic::add($params)) {
            return $this->success('添加成功');
        }
        return $this->fail(StaffTeamLogic::getError());
    }

    public function edit()
    {
        $params = (new StaffTeamValidate())->post()->goCheck('edit');
        if (StaffTeamLogic::edit($params)) {
            return $this->success('编辑成功');
        }
        return $this->fail(StaffTeamLogic::getError());
    }

    public function delete()
    {
        $params = (new StaffTeamValidate())->post()->goCheck('delete');
        if (StaffTeamLogic::delete((int)$params['id'])) {
            return $this->success('删除成功');
        }
        return $this->fail(StaffTeamLogic::getError());
    }

    public function changeStatus()
    {
        $params = (new StaffTeamValidate())->post()->goCheck('status');
        if (StaffTeamLogic::changeStatus($params)) {
            return $this->success('操作成功');
        }
        return $this->fail(StaffTeamLogic::getError());
    }

    public function options()
    {
        return $this->data(StaffTeamLogic::options());
    }
}
