<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务套餐管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\service;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\service\PackageLists;
use app\adminapi\logic\service\PackageLogic;
use app\adminapi\validate\service\PackageValidate;
use app\common\model\service\ServicePackage;
use app\common\service\StaffService;

/**
 * 服务套餐管理控制器
 * Class PackageController
 * @package app\adminapi\controller\service
 */
class PackageController extends BaseAdminController
{
    /**
     * @notes 套餐列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new PackageLists());
    }

    /**
     * @notes 套餐详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new PackageValidate())->goCheck('detail');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && !$this->belongsToScope((int)$params['id'], $staffScopeId)) {
            return $this->fail('无权限操作');
        }
        $result = PackageLogic::detail((int)$params['id']);
        if (empty($result)) {
            return $this->fail('套餐不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 添加套餐
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new PackageValidate())->post()->goCheck('add');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $params['staff_id'] = $staffScopeId;
        }
        $result = PackageLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(PackageLogic::getError());
    }

    /**
     * @notes 编辑套餐
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new PackageValidate())->post()->goCheck('edit');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            if (!$this->belongsToScope((int)$params['id'], $staffScopeId)) {
                return $this->fail('无权限操作');
            }
            $params['staff_id'] = $staffScopeId;
        }
        $result = PackageLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(PackageLogic::getError());
    }

    /**
     * @notes 删除套餐
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new PackageValidate())->post()->goCheck('delete');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && !$this->belongsToScope((int)$params['id'], $staffScopeId)) {
            return $this->fail('无权限操作');
        }
        $result = PackageLogic::delete($params);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(PackageLogic::getError());
    }

    /**
     * @notes 修改套餐状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new PackageValidate())->post()->goCheck('status');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && !$this->belongsToScope((int)$params['id'], $staffScopeId)) {
            return $this->fail('无权限操作');
        }
        $result = PackageLogic::changeStatus($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(PackageLogic::getError());
    }

    /**
     * @notes 获取所有套餐(用于下拉选择)
     * @return \think\response\Json
     */
    public function all()
    {
        $params = $this->request->get();
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $params['staff_id'] = $staffScopeId;
        }
        $result = PackageLogic::getAll($params);
        return $this->data($result);
    }

    /**
     * @notes 校验套餐是否属于当前人员数据范围
     * @param int $packageId
     * @param int $staffScopeId
     * @return bool
     */
    protected function belongsToScope(int $packageId, int $staffScopeId): bool
    {
        if ($packageId <= 0 || $staffScopeId <= 0) {
            return false;
        }

        return ServicePackage::where('id', $packageId)
            ->where('staff_id', $staffScopeId)
            ->whereNull('delete_time')
            ->count() > 0;
    }

}
