<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 工作人员管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\staff;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\staff\StaffLists;
use app\adminapi\logic\staff\StaffLogic;
use app\adminapi\validate\staff\StaffValidate;

/**
 * 工作人员管理控制器
 * Class StaffController
 * @package app\adminapi\controller\staff
 */
class StaffController extends BaseAdminController
{
    /**
     * @notes 工作人员列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new StaffLists());
    }

    /**
     * @notes 工作人员详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new StaffValidate())->goCheck('detail');
        $result = StaffLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 添加工作人员
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new StaffValidate())->post()->goCheck('add');
        $result = StaffLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 编辑工作人员
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new StaffValidate())->post()->goCheck('edit');
        $result = StaffLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 删除工作人员
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new StaffValidate())->post()->goCheck('delete');
        StaffLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 修改工作人员状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new StaffValidate())->post()->goCheck('status');
        $result = StaffLogic::changeStatus($params);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 获取所有工作人员(用于下拉选择)
     * @return \think\response\Json
     */
    public function all()
    {
        $params = $this->request->get();
        $result = StaffLogic::getAll($params);
        return $this->data($result);
    }

    /**
     * @notes 工作人员统计数据
     * @return \think\response\Json
     */
    public function statistics()
    {
        $result = StaffLogic::statistics();
        return $this->data($result);
    }

    /**
     * @notes 配置员工套餐关联
     * @return \think\response\Json
     */
    public function configurePackages()
    {
        $params = $this->request->post();
        $staffId = intval($params['staff_id'] ?? 0);
        $packages = $params['packages'] ?? [];

        if ($staffId <= 0) {
            return $this->fail('请选择员工');
        }

        $result = StaffLogic::configurePackages($staffId, $packages);
        if (true === $result) {
            return $this->success('配置成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 获取员工套餐配置
     * @return \think\response\Json
     */
    public function getPackageConfig()
    {
        $staffId = intval($this->request->get('staff_id', 0));
        $includeGlobal = boolval($this->request->get('include_global', false));

        if ($staffId <= 0) {
            return $this->fail('请选择员工');
        }

        $result = StaffLogic::getPackageConfig($staffId, $includeGlobal);
        return $this->data($result);
    }

    /**
     * @notes 创建员工专属套餐
     * @return \think\response\Json
     */
    public function createStaffPackage()
    {
        $params = $this->request->post();
        $staffId = intval($params['staff_id'] ?? 0);

        if ($staffId <= 0) {
            return $this->fail('请选择员工');
        }

        // 验证套餐数据
        if (empty($params['name'])) {
            return $this->fail('请输入套餐名称');
        }

        $packageId = StaffLogic::createStaffPackage($staffId, $params);
        if ($packageId) {
            return $this->success('创建成功', ['package_id' => $packageId], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 更新单个套餐配置
     * @return \think\response\Json
     */
    public function updatePackageConfig()
    {
        $params = $this->request->post();
        $staffId = intval($params['staff_id'] ?? 0);
        $packageId = intval($params['package_id'] ?? 0);

        if ($staffId <= 0 || $packageId <= 0) {
            return $this->fail('参数错误');
        }

        $data = [];
        if (isset($params['custom_price'])) {
            $data['custom_price'] = $params['custom_price'];
        }
        if (isset($params['custom_slot_prices'])) {
            $data['custom_slot_prices'] = $params['custom_slot_prices'];
        }
        if (isset($params['status'])) {
            $data['status'] = $params['status'];
        }

        $result = StaffLogic::updatePackageConfig($staffId, $packageId, $data);
        if (true === $result) {
            return $this->success('更新成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 删除员工专属套餐
     * @return \think\response\Json
     */
    public function deleteStaffPackage()
    {
        $params = $this->request->post();
        $staffId = intval($params['staff_id'] ?? 0);
        $packageId = intval($params['package_id'] ?? 0);

        if ($staffId <= 0 || $packageId <= 0) {
            return $this->fail('参数错误');
        }

        $result = StaffLogic::deleteStaffPackage($staffId, $packageId);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }
}
