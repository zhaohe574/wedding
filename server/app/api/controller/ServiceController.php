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
    public array $notNeedLogin = ['categories', 'categoryTree', 'packages', 'packageDetail', 'tags'];

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
}
