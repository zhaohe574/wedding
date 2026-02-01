<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端工作人员控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\lists\staff\StaffLists as ApiStaffLists;
use app\api\logic\StaffLogic;

/**
 * 工作人员控制器（小程序端）
 * Class StaffController
 * @package app\api\controller
 */
class StaffController extends BaseApiController
{
    public array $notNeedLogin = ['lists', 'detail', 'works', 'recommend', 'packages'];

    /**
     * @notes 工作人员列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new ApiStaffLists());
    }

    /**
     * @notes 推荐工作人员
     * @return \think\response\Json
     */
    public function recommend()
    {
        $limit = $this->request->get('limit/d', 10);
        $result = StaffLogic::recommend($limit);
        return $this->data($result);
    }

    /**
     * @notes 工作人员详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = $this->request->get('id/d');
        if (!$id) {
            return $this->fail('参数错误');
        }
        $result = StaffLogic::detail($id, $this->userId);
        if (empty($result)) {
            return $this->fail('工作人员不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 工作人员作品列表
     * @return \think\response\Json
     */
    public function works()
    {
        $staffId = $this->request->get('staff_id/d');
        if (!$staffId) {
            return $this->fail('参数错误');
        }
        $result = StaffLogic::works($staffId);
        return $this->data($result);
    }

    /**
     * @notes 工作人员套餐列表
     * @return \think\response\Json
     */
    public function packages()
    {
        $staffId = $this->request->get('staff_id/d');
        if (!$staffId) {
            return $this->fail('参数错误');
        }
        $result = StaffLogic::packages($staffId);
        return $this->data($result);
    }

    /**
     * @notes 收藏/取消收藏工作人员
     * @return \think\response\Json
     */
    public function toggleFavorite()
    {
        $staffId = $this->request->post('id/d');
        if (!$staffId) {
            return $this->fail('参数错误');
        }
        $result = StaffLogic::toggleFavorite($staffId, $this->userId);
        return $this->success($result ? '收藏成功' : '取消收藏成功');
    }

    /**
     * @notes 我收藏的工作人员
     * @return \think\response\Json
     */
    public function myFavorites()
    {
        $result = StaffLogic::myFavorites($this->userId);
        return $this->data($result);
    }
}
