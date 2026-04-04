<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 晒单奖励管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\review;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\review\ReviewShareRewardLists;
use app\adminapi\logic\review\ReviewShareRewardLogic;
use app\adminapi\validate\review\ReviewShareRewardValidate;

class ReviewShareRewardController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists(new ReviewShareRewardLists());
    }

    public function detail()
    {
        $params = (new ReviewShareRewardValidate())->goCheck('detail');
        return $this->success('', ReviewShareRewardLogic::detail((int)$params['id']));
    }

    public function audit()
    {
        $params = (new ReviewShareRewardValidate())->post()->goCheck('audit');
        $params['admin_id'] = $this->adminId;
        $result = ReviewShareRewardLogic::audit($params);
        if ($result) {
            return $this->success('审核成功');
        }
        return $this->fail(ReviewShareRewardLogic::getError());
    }
}
