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
 *
 * 此前附加项只能从「服务人员 → 编辑 → 服务套餐」tab 里按人配置，没有全局入口，
 * 运营想统一看/统一上下架必须逐个人翻。本控制器把已有的 AddonLogic / AddonLists
 * 暴露成独立管理页（逻辑层零改动）。
 *
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && !$this->belongsToScope((int)$params['id'], $staffScopeId)) {
            return $this->fail('无权限操作');
        }
        $result = AddonLogic::detail((int)$params['id']);
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
        if ($staffScopeId > 0) {
            $params['staff_id'] = $staffScopeId;
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
            if (!$this->belongsToScope((int)$params['id'], $staffScopeId)) {
                return $this->fail('无权限操作');
            }
            $params['staff_id'] = $staffScopeId;
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
        if ($staffScopeId > 0 && !$this->belongsToScope((int)$params['id'], $staffScopeId)) {
            return $this->fail('无权限操作');
        }
        $result = AddonLogic::delete($params);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(AddonLogic::getError());
    }

    /**
     * @notes 修改上下架状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new AddonValidate())->post()->goCheck('status');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && !$this->belongsToScope((int)$params['id'], $staffScopeId)) {
            return $this->fail('无权限操作');
        }
        $result = AddonLogic::changeStatus($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(AddonLogic::getError());
    }

    /**
     * @notes 获取所有附加服务（下拉选择用）
     * @return \think\response\Json
     */
    public function all()
    {
        $params = $this->request->get();
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $params['staff_id'] = $staffScopeId;
        }
        return $this->data(AddonLogic::getAll($params));
    }

    /**
     * @notes 校验附加服务是否属于当前人员数据范围
     * @param int $addonId
     * @param int $staffScopeId
     * @return bool
     */
    protected function belongsToScope(int $addonId, int $staffScopeId): bool
    {
        if ($addonId <= 0 || $staffScopeId <= 0) {
            return false;
        }

        return ServiceAddon::where('id', $addonId)
            ->where('staff_id', $staffScopeId)
            ->whereNull('delete_time')
            ->count() > 0;
    }
}
