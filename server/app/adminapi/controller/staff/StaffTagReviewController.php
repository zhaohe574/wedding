<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员标签审核控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\staff;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\staff\StaffTagReviewLists;
use app\adminapi\logic\staff\StaffTagReviewLogic;
use app\adminapi\validate\staff\StaffTagReviewValidate;

class StaffTagReviewController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists(new StaffTagReviewLists());
    }

    public function detail()
    {
        $params = (new StaffTagReviewValidate())->goCheck('detail');
        $result = StaffTagReviewLogic::detail((int) $params['id']);
        if (empty($result)) {
            return $this->fail(StaffTagReviewLogic::getError());
        }

        return $this->data($result);
    }

    public function approve()
    {
        $params = (new StaffTagReviewValidate())->post()->goCheck('approve');
        $result = StaffTagReviewLogic::approve((int) $params['id'], $this->adminId);
        if ($result) {
            return $this->success('审核通过');
        }

        return $this->fail(StaffTagReviewLogic::getError());
    }

    public function reject()
    {
        $params = (new StaffTagReviewValidate())->post()->goCheck('reject');
        $result = StaffTagReviewLogic::reject((int) $params['id'], $this->adminId, (string) $params['reject_reason']);
        if ($result) {
            return $this->success('已拒绝');
        }

        return $this->fail(StaffTagReviewLogic::getError());
    }
}
