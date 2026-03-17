<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 附加服务管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\service;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\service\AddonLists;
use app\adminapi\logic\service\AddonLogic;
use app\adminapi\validate\service\AddonValidate;
use app\common\model\service\ServiceAddon;
use app\common\service\StaffService;

/**
 * 附加服务管理控制器
 * Class AddonController
 * @package app\adminapi\controller\service
 */
class AddonController extends BaseAdminController
{
    /**
     * @notes 附加服务列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new AddonLists());
    }

    /**
     * @notes 附加服务详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new AddonValidate())->goCheck('detail');
        $addonId = (int)$params['id'];
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $addon = ServiceAddon::find($addonId);
            if (!$addon || (int)$addon->staff_id !== $staffScopeId) {
                return $this->fail('无权限查看');
            }
        }

        $result = AddonLogic::detail($addonId);
        if (empty($result)) {
            return $this->fail('附加服务不存在');
        }

        return $this->data($result);
    }

    /**
     * @notes 添加附加服务
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new AddonValidate())->post()->goCheck('add');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && (int)$params['staff_id'] !== $staffScopeId) {
            return $this->fail('无权限操作');
        }

        $result = AddonLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }

        return $this->fail(AddonLogic::getError());
    }

    /**
     * @notes 编辑附加服务
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new AddonValidate())->post()->goCheck('edit');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $addon = ServiceAddon::find((int)$params['id']);
            if (!$addon || (int)$addon->staff_id !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
            if ((int)$params['staff_id'] !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
        }

        $result = AddonLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }

        return $this->fail(AddonLogic::getError());
    }

    /**
     * @notes 删除附加服务
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new AddonValidate())->post()->goCheck('delete');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $addon = ServiceAddon::find((int)$params['id']);
            if (!$addon || (int)$addon->staff_id !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
        }

        $result = AddonLogic::delete($params);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }

        return $this->fail(AddonLogic::getError());
    }

    /**
     * @notes 修改附加服务状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new AddonValidate())->post()->goCheck('status');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $addon = ServiceAddon::find((int)$params['id']);
            if (!$addon || (int)$addon->staff_id !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
        }

        $result = AddonLogic::changeStatus($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }

        return $this->fail(AddonLogic::getError());
    }

    /**
     * @notes 获取全部附加服务
     * @return \think\response\Json
     */
    public function all()
    {
        $params = $this->request->get();
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $params['staff_id'] = $staffScopeId;
        }

        $result = AddonLogic::getAll($params);
        return $this->data($result);
    }
}
