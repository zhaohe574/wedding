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
        $result = PackageLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @notes 添加套餐
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new PackageValidate())->post()->goCheck('add');
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
        $result = PackageLogic::getAll($params);
        return $this->data($result);
    }

    /**
     * @notes 更新套餐时段价格
     * @return \think\response\Json
     */
    public function updateSlotPrices()
    {
        $params = (new PackageValidate())->post()->goCheck('slotPrices');
        $result = PackageLogic::updateSlotPrices($params);
        if (true === $result) {
            return $this->success('保存成功', [], 1, 1);
        }
        return $this->fail(PackageLogic::getError());
    }

    /**
     * @notes 检查套餐可用性
     * @return \think\response\Json
     */
    public function checkAvailability()
    {
        $packageId = $this->request->get('package_id', 0, 'intval');
        $date = $this->request->get('date', '');
        $staffId = $this->request->get('staff_id', 0, 'intval');
        
        if (empty($packageId) || empty($date)) {
            return $this->fail('参数错误');
        }
        
        $result = PackageLogic::checkAvailability($packageId, $date, $staffId);
        return $this->data($result);
    }

    /**
     * @notes 获取套餐预约日历
     * @return \think\response\Json
     */
    public function getBookingCalendar()
    {
        $packageId = $this->request->get('package_id', 0, 'intval');
        $startDate = $this->request->get('start_date', '');
        $endDate = $this->request->get('end_date', '');
        
        if (empty($packageId) || empty($startDate) || empty($endDate)) {
            return $this->fail('参数错误');
        }
        
        $result = PackageLogic::getBookingCalendar($packageId, $startDate, $endDate);
        return $this->data($result);
    }
}
