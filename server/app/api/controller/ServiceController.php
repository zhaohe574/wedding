<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端服务控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\ServiceLogic;

/**
 * 服务控制器（小程序端）
 * Class ServiceController
 * @package app\api\controller
 */
class ServiceController extends BaseApiController
{
    public array $notNeedLogin = [
        'categories', 'categoryTree', 'packages', 'packageDetail', 'tags',
        'checkPackageAvailability', 'batchCheckAvailability', 'packageSlotPrices', 'calculatePrice'
    ];

    /**
     * @notes 服务分类列表
     * @return \think\response\Json
     */
    public function categories()
    {
        $pid = $this->request->get('pid/d', 0);
        $result = ServiceLogic::categories($pid);
        return $this->data($result);
    }

    /**
     * @notes 服务分类树形结构
     * @return \think\response\Json
     */
    public function categoryTree()
    {
        $result = ServiceLogic::categoryTree();
        return $this->data($result);
    }

    /**
     * @notes 服务套餐列表
     * @return \think\response\Json
     */
    public function packages()
    {
        $categoryId = $this->request->get('category_id/d', 0);
        $result = ServiceLogic::packages($categoryId);
        return $this->data($result);
    }

    /**
     * @notes 服务套餐详情
     * @return \think\response\Json
     */
    public function packageDetail()
    {
        $id = $this->request->get('id/d');
        if (!$id) {
            return $this->fail('参数错误');
        }
        $result = ServiceLogic::packageDetail($id);
        if (empty($result)) {
            return $this->fail('套餐不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 风格标签列表
     * @return \think\response\Json
     */
    public function tags()
    {
        $type = $this->request->get('type/d', 0);
        $grouped = $this->request->get('grouped/d', 0);
        $result = ServiceLogic::tags($type, (bool)$grouped);
        return $this->data($result);
    }

    /**
     * @notes 检查套餐可用性（单日唯一限制）
     * @return \think\response\Json
     */
    public function checkPackageAvailability()
    {
        $packageId = $this->request->get('package_id/d');
        $date = $this->request->get('date/s', '');
        $staffId = $this->request->get('staff_id/d', 0);
        $timeSlot = $this->request->get('time_slot/d', 0);

        if (empty($packageId) || empty($date)) {
            return $this->fail('参数错误');
        }

        $result = ServiceLogic::checkPackageAvailability($packageId, $date, $staffId, $timeSlot);
        return $this->data($result);
    }

    /**
     * @notes 批量检查套餐可用性
     * @return \think\response\Json
     */
    public function batchCheckAvailability()
    {
        $packageIds = $this->request->post('package_ids/a', []);
        $date = $this->request->post('date/s', '');
        $staffId = $this->request->post('staff_id/d', 0);
        $timeSlot = $this->request->post('time_slot/d', 0);

        if (empty($packageIds) || empty($date)) {
            return $this->fail('参数错误');
        }

        $result = ServiceLogic::batchCheckAvailability($packageIds, $date, $staffId, $timeSlot);
        return $this->data($result);
    }

    /**
     * @notes 获取套餐时段价格
     * @return \think\response\Json
     */
    public function packageSlotPrices()
    {
        $packageId = $this->request->get('package_id/d');
        $staffId = $this->request->get('staff_id/d', 0);

        if (empty($packageId)) {
            return $this->fail('参数错误');
        }

        $result = ServiceLogic::getPackageSlotPrices($packageId, $staffId);
        return $this->data($result);
    }

    /**
     * @notes 计算套餐最终价格
     * @return \think\response\Json
     */
    public function calculatePrice()
    {
        $packageId = $this->request->get('package_id/d');
        $staffId = $this->request->get('staff_id/d', 0);
        $startTime = $this->request->get('start_time/s', '');
        $endTime = $this->request->get('end_time/s', '');
        $timeSlot = $this->request->get('time_slot/d', -1);

        if (empty($packageId)) {
            return $this->fail('参数错误');
        }

        $result = ServiceLogic::calculatePrice($packageId, $staffId, $startTime, $endTime, $timeSlot);
        return $this->data($result);
    }
}
