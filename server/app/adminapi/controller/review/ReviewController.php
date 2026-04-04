<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\review;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\review\ReviewLogic;
use app\adminapi\lists\review\ReviewLists;
use app\adminapi\validate\review\ReviewValidate;

/**
 * 评价管理控制器
 * Class ReviewController
 * @package app\adminapi\controller\review
 */
class ReviewController extends BaseAdminController
{
    /**
     * @notes 评价列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new ReviewLists());
    }

    /**
     * @notes 评价详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new ReviewValidate())->goCheck('detail');
        $result = ReviewLogic::detail($params['id']);
        return $this->success('', $result);
    }

    /**
     * @notes 审核评价
     * @return \think\response\Json
     */
    public function audit()
    {
        $params = (new ReviewValidate())->post()->goCheck('audit');
        $params['admin_id'] = $this->adminId;
        $result = ReviewLogic::audit($params);
        if ($result === true) {
            return $this->success('审核成功');
        }
        return $this->fail(ReviewLogic::getError());
    }

    /**
     * @notes 批量审核
     * @return \think\response\Json
     */
    public function batchAudit()
    {
        $params = (new ReviewValidate())->post()->goCheck('batchAudit');
        $params['admin_id'] = $this->adminId;
        $result = ReviewLogic::batchAudit($params);
        if ($result === true) {
            return $this->success('批量审核成功');
        }
        return $this->fail(ReviewLogic::getError());
    }

    /**
     * @notes 置顶/取消置顶
     * @return \think\response\Json
     */
    public function toggleTop()
    {
        $params = (new ReviewValidate())->post()->goCheck('detail');
        $result = ReviewLogic::toggleTop($params['id']);
        if ($result === true) {
            return $this->success('操作成功');
        }
        return $this->fail(ReviewLogic::getError());
    }

    /**
     * @notes 显示/隐藏评价
     * @return \think\response\Json
     */
    public function toggleShow()
    {
        $params = (new ReviewValidate())->post()->goCheck('detail');
        $result = ReviewLogic::toggleShow($params['id']);
        if ($result === true) {
            return $this->success('操作成功');
        }
        return $this->fail(ReviewLogic::getError());
    }

    /**
     * @notes 删除评价
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new ReviewValidate())->post()->goCheck('detail');
        $result = ReviewLogic::delete($params['id']);
        if ($result === true) {
            return $this->success('删除成功');
        }
        return $this->fail(ReviewLogic::getError());
    }

    /**
     * @notes 商家回复
     * @return \think\response\Json
     */
    public function reply()
    {
        $params = (new ReviewValidate())->post()->goCheck('reply');
        $params['admin_id'] = $this->adminId;
        $result = ReviewLogic::reply($params);
        if ($result === true) {
            return $this->success('回复成功');
        }
        return $this->fail(ReviewLogic::getError());
    }

    /**
     * @notes 评价统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = ReviewLogic::statistics($params);
        return $this->success('', $result);
    }

    /**
     * @notes 人员评价排行
     * @return \think\response\Json
     */
    public function staffRanking()
    {
        $params = $this->request->get();
        $result = ReviewLogic::staffRanking($params);
        return $this->success('', $result);
    }

    /**
     * @notes 评分分布统计
     * @return \think\response\Json
     */
    public function scoreDistribution()
    {
        $params = $this->request->get();
        $result = ReviewLogic::scoreDistribution($params);
        return $this->success('', $result);
    }

    /**
     * @notes 热门标签统计
     * @return \think\response\Json
     */
    public function hotTags()
    {
        $params = $this->request->get();
        $result = ReviewLogic::hotTags($params);
        return $this->success('', $result);
    }
}
