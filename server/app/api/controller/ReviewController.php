<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 小程序端评价控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\ReviewLogic;
use app\api\validate\ReviewValidate;

/**
 * 小程序端评价控制器
 * Class ReviewController
 * @package app\api\controller
 */
class ReviewController extends BaseApiController
{
    public array $notNeedLogin = ['staffReviews', 'reviewDetail', 'tags'];

    /**
     * @notes 我的评价列表
     * @return \think\response\Json
     */
    public function myReviews()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId;
        $result = ReviewLogic::myReviews($params);
        return $this->success('', $result);
    }

    /**
     * @notes 待评价订单列表
     * @return \think\response\Json
     */
    public function pendingOrders()
    {
        $params = $this->request->get();
        $params['user_id'] = $this->userId;
        $result = ReviewLogic::pendingOrders($params);
        return $this->success('', $result);
    }

    /**
     * @notes 服务人员评价列表
     * @return \think\response\Json
     */
    public function staffReviews()
    {
        $params = $this->request->get();
        $params['current_user_id'] = $this->userId ?? 0;
        $result = ReviewLogic::staffReviews($params);
        return $this->success('', $result);
    }

    /**
     * @notes 评价详情
     * @return \think\response\Json
     */
    public function reviewDetail()
    {
        $params = (new ReviewValidate())->goCheck('detail');
        $params['current_user_id'] = $this->userId ?? 0;
        $result = ReviewLogic::detail($params);
        if ($result === false) {
            return $this->fail(ReviewLogic::getError());
        }
        return $this->success('', $result);
    }

    /**
     * @notes 发布评价
     * @return \think\response\Json
     */
    public function publish()
    {
        $params = (new ReviewValidate())->post()->goCheck('publish');
        $params['user_id'] = $this->userId;
        $result = ReviewLogic::publish($params);
        if ($result === false) {
            return $this->fail(ReviewLogic::getError());
        }
        return $this->success('评价发布成功', $result);
    }

    /**
     * @notes 追评
     * @return \think\response\Json
     */
    public function append()
    {
        $params = (new ReviewValidate())->post()->goCheck('append');
        $params['user_id'] = $this->userId;
        $result = ReviewLogic::append($params);
        if ($result === false) {
            return $this->fail(ReviewLogic::getError());
        }
        return $this->success('追评成功');
    }

    /**
     * @notes 点赞/取消点赞
     * @return \think\response\Json
     */
    public function toggleLike()
    {
        $params = (new ReviewValidate())->post()->goCheck('detail');
        $result = ReviewLogic::toggleLike($params['id'], $this->userId);
        return $this->success($result ? '点赞成功' : '取消点赞');
    }

    /**
     * @notes 获取评价标签
     * @return \think\response\Json
     */
    public function tags()
    {
        $score = $this->request->get('score', 5);
        $result = ReviewLogic::getTagsByScore((int)$score);
        return $this->success('', $result);
    }

    /**
     * @notes 获取评价奖励规则
     * @return \think\response\Json
     */
    public function rewardRules()
    {
        $result = ReviewLogic::getRewardRules();
        return $this->success('', $result);
    }

    /**
     * @notes 申请晒单奖励
     * @return \think\response\Json
     */
    public function applyShareReward()
    {
        $params = (new ReviewValidate())->post()->goCheck('shareReward');
        $params['user_id'] = $this->userId;
        $result = ReviewLogic::applyShareReward($params);
        if ($result === false) {
            return $this->fail(ReviewLogic::getError());
        }
        return $this->success('申请已提交');
    }

    /**
     * @notes 服务人员评价统计
     * @return \think\response\Json
     */
    public function staffStats()
    {
        $staffId = $this->request->get('staff_id');
        if (empty($staffId)) {
            return $this->fail('服务人员ID不能为空');
        }
        $result = ReviewLogic::staffStats((int)$staffId);
        return $this->success('', $result);
    }

    /**
     * @notes 提交申诉
     * @return \think\response\Json
     */
    public function submitAppeal()
    {
        $params = (new ReviewValidate())->post()->goCheck('appeal');
        $params['appeal_user_id'] = $this->userId;
        $result = ReviewLogic::submitAppeal($params);
        if ($result === false) {
            return $this->fail(ReviewLogic::getError());
        }
        return $this->success('申诉已提交');
    }
}
