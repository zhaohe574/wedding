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
        'categories', 'categoryTree', 'tags'
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
        return $this->fail('独立套餐入口已下线，请从人员详情进入预约');
    }

    /**
     * @notes 服务套餐列表（支持搜索与分页）
     * @return \think\response\Json
     */
    public function packageLists()
    {
        return $this->fail('独立套餐入口已下线，请从人员详情进入预约');
    }

    /**
     * @notes 服务套餐详情
     * @return \think\response\Json
     */
    public function packageDetail()
    {
        return $this->fail('独立套餐入口已下线，请从人员详情进入预约');
    }

    /**
     * @notes 风格标签列表
     * @return \think\response\Json
     */
    public function tags()
    {
        $type = $this->request->get('type/d', 0);
        $grouped = $this->request->get('grouped/d', 0);
        $categoryId = $this->request->get('category_id/d', 0);
        $result = ServiceLogic::tags($type, (bool)$grouped, $categoryId);
        return $this->data($result);
    }

    /**
     * @notes 检查套餐可用性（单日唯一限制）
     * @return \think\response\Json
     */
    public function checkPackageAvailability()
    {
        return $this->fail('独立套餐入口已下线，请从人员详情进入预约');
    }

    /**
     * @notes 批量检查套餐可用性
     * @return \think\response\Json
     */
    public function batchCheckAvailability()
    {
        return $this->fail('独立套餐入口已下线，请从人员详情进入预约');
    }

    /**
     * @notes 获取套餐时段价格
     * @return \think\response\Json
     */
    public function packageSlotPrices()
    {
        return $this->fail('场次价格能力已下线');
    }

    /**
     * @notes 计算套餐最终价格
     * @return \think\response\Json
     */
    public function calculatePrice()
    {
        return $this->fail('场次价格能力已下线');
    }
}
