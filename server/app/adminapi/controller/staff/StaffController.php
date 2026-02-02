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
use app\common\model\staff\StaffBanner;
use app\common\service\StaffService;

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
        try {
            $params = (new StaffValidate())->goCheck('detail');
            $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
            if ($staffScopeId > 0 && (int) $params['id'] !== $staffScopeId) {
                return $this->fail('无权限查看');
            }
            $result = StaffLogic::detail((int) $params['id']);
            return $this->data($result);
        } catch (\Throwable $e) {
            return $this->fail(
                '详情加载失败: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(),
                [],
                0,
                1
            );
        }
    }

    /**
     * @notes 添加工作人员
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new StaffValidate())->post()->goCheck('add');
        $result = StaffLogic::add($params);
        if (false !== $result) {
            return $this->success('添加成功', $result ?: [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 编辑工作人员
     * @return \think\response\Json
     */
    public function edit()
    {
        try {
            $params = (new StaffValidate())->post()->goCheck('edit');
            $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
            if ($staffScopeId > 0 && (int) $params['id'] !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
            $result = StaffLogic::edit($params);
            if (true === $result) {
                return $this->success('编辑成功', [], 1, 1);
            }
            return $this->fail(StaffLogic::getError());
        } catch (\Throwable $e) {
            return $this->fail(
                '保存失败: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(),
                [],
                0,
                1
            );
        }
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
        $staffScopeId = \app\common\service\StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $params['ids'] = [$staffScopeId];
        }
        $result = StaffLogic::getAll($params);
        return $this->data($result);
    }

    /**
     * @notes 重置后台账号密码
     * @return \think\response\Json
     */
    public function resetAdminPassword()
    {
        $params = (new StaffValidate())->post()->goCheck('resetAdmin');
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && (int) $params['id'] !== $staffScopeId) {
            return $this->fail('无权限操作');
        }
        $result = StaffLogic::resetAdminPassword((int)$params['id']);
        if ($result !== false) {
            return $this->success('重置成功', $result, 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 工作人员统计数据
     * @return \think\response\Json
     */
    public function statistics()
    {
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        $result = StaffLogic::statistics($staffScopeId);
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && $staffId !== $staffScopeId) {
            return $this->fail('无权限操作');
        }

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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = $staffScopeId;
        }

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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && $staffId !== $staffScopeId) {
            return $this->fail('无权限操作');
        }

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
     * @notes 编辑员工专属套餐
     * @return \think\response\Json
     */
    public function updateStaffPackage()
    {
        $params = $this->request->post();
        $staffId = intval($params['staff_id'] ?? 0);
        $packageId = intval($params['package_id'] ?? 0);
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && $staffId !== $staffScopeId) {
            return $this->fail('无权限操作');
        }

        if ($staffId <= 0 || $packageId <= 0) {
            return $this->fail('参数错误');
        }

        if (empty($params['name'])) {
            return $this->fail('请输入套餐名称');
        }

        $result = StaffLogic::updateStaffPackage($staffId, $packageId, $params);
        if (true === $result) {
            return $this->success('更新成功', [], 1, 1);
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && $staffId !== $staffScopeId) {
            return $this->fail('无权限操作');
        }

        if ($staffId <= 0 || $packageId <= 0) {
            return $this->fail('参数错误');
        }

        $data = [];
        if (isset($params['price'])) {
            $data['price'] = $params['price'];
        }
        if (array_key_exists('original_price', $params)) {
            $data['original_price'] = $params['original_price'];
        }
        if (isset($params['custom_price'])) {
            $data['custom_price'] = $params['custom_price'];
        }
        if (isset($params['custom_slot_prices'])) {
            $data['custom_slot_prices'] = $params['custom_slot_prices'];
        }
        if (array_key_exists('booking_type', $params)) {
            $data['booking_type'] = $params['booking_type'];
        }
        if (isset($params['allowed_time_slots'])) {
            $data['allowed_time_slots'] = $params['allowed_time_slots'];
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0 && $staffId !== $staffScopeId) {
            return $this->fail('无权限操作');
        }

        if ($staffId <= 0 || $packageId <= 0) {
            return $this->fail('参数错误');
        }

        $result = StaffLogic::deleteStaffPackage($staffId, $packageId);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 获取人员轮播图列表
     * @return \think\response\Json
     */
    public function getBannerList()
    {
        $staffId = intval($this->request->get('staff_id', 0));
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = $staffScopeId;
        }
        if ($staffId <= 0) {
            return $this->fail('参数错误');
        }

        $result = StaffLogic::getBannerList($staffId);
        return $this->data($result);
    }

    /**
     * @notes 添加轮播图
     * @return \think\response\Json
     */
    public function addBanner()
    {
        $params = $this->request->post();
        $staffId = intval($params['staff_id'] ?? 0);
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = $staffScopeId;
            $params['staff_id'] = $staffScopeId;
        }

        if ($staffId <= 0) {
            return $this->fail('参数错误');
        }

        if (empty($params['file_url'])) {
            return $this->fail('请上传文件');
        }

        $type = intval($params['type'] ?? 1);
        if ($type === 2 && empty($params['cover_url'])) {
            return $this->fail('视频需要上传封面图');
        }

        $result = StaffLogic::addBanner($staffId, $params);
        if ($result) {
            return $this->success('添加成功', ['id' => $result], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 编辑轮播图
     * @return \think\response\Json
     */
    public function editBanner()
    {
        $params = $this->request->post();
        $id = intval($params['id'] ?? 0);

        if ($id <= 0) {
            return $this->fail('参数错误');
        }
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) StaffBanner::where('id', $id)->value('staff_id');
            if ($staffId !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
        }

        $result = StaffLogic::editBanner($id, $params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 删除轮播图
     * @return \think\response\Json
     */
    public function deleteBanner()
    {
        $params = $this->request->post();
        $id = intval($params['id'] ?? 0);

        if ($id <= 0) {
            return $this->fail('参数错误');
        }
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = (int) StaffBanner::where('id', $id)->value('staff_id');
            if ($staffId !== $staffScopeId) {
                return $this->fail('无权限操作');
            }
        }

        $result = StaffLogic::deleteBanner($id);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 更新轮播图排序
     * @return \think\response\Json
     */
    public function sortBanner()
    {
        $params = $this->request->post();
        $staffId = intval($params['staff_id'] ?? 0);
        $sortData = $params['sort_data'] ?? [];
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = $staffScopeId;
            $params['staff_id'] = $staffScopeId;
        }

        if ($staffId <= 0 || empty($sortData)) {
            return $this->fail('参数错误');
        }

        $result = StaffLogic::sortBanner($staffId, $sortData);
        if (true === $result) {
            return $this->success('排序成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }

    /**
     * @notes 更新轮播图配置
     * @return \think\response\Json
     */
    public function updateBannerConfig()
    {
        $params = $this->request->post();
        $staffId = intval($params['staff_id'] ?? 0);
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            $staffId = $staffScopeId;
            $params['staff_id'] = $staffScopeId;
        }

        if ($staffId <= 0) {
            return $this->fail('参数错误');
        }

        $result = StaffLogic::updateBannerConfig($staffId, $params);
        if (true === $result) {
            return $this->success('配置成功', [], 1, 1);
        }
        return $this->fail(StaffLogic::getError());
    }
}
