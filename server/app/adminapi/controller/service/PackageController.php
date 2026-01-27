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
        try {
            // 直接获取ID，不使用验证器
            $id = $this->request->get('id', 0, 'intval');
            
            if (empty($id)) {
                return $this->fail('参数错误：缺少套餐ID');
            }
            
            // 直接查询，不使用Logic
            $package = \app\common\model\service\ServicePackage::find($id);
            
            if (!$package) {
                return $this->fail('套餐不存在');
            }
            
            $result = $package->toArray();
            
            // 手动添加分类名称
            if (!empty($result['category_id'])) {
                $category = \app\common\model\service\ServiceCategory::find($result['category_id']);
                if ($category) {
                    $result['category_name'] = $category->name;
                }
            }
            
            return $this->data($result);
            
        } catch (\Exception $e) {
            // 返回详细错误信息用于调试
            return $this->fail('获取套餐详情失败: ' . $e->getMessage() . ' [' . $e->getFile() . ':' . $e->getLine() . ']');
        }
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
