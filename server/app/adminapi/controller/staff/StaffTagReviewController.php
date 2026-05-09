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
use app\common\model\staff\StaffTagApply;
use app\common\service\StaffService;

class StaffTagReviewController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists(new StaffTagReviewLists());
    }

    public function detail()
    {
        $params = (new StaffTagReviewValidate())->goCheck('detail');
        if ($response = $this->checkApplyScope((int)$params['id'])) {
            return $response;
        }
        $result = StaffTagReviewLogic::detail((int) $params['id']);
        if (empty($result)) {
            return $this->fail(StaffTagReviewLogic::getError());
        }

        return $this->data($result);
    }

    public function approve()
    {
        $params = (new StaffTagReviewValidate())->post()->goCheck('approve');
        if ($response = $this->checkApplyScope((int)$params['id'])) {
            return $response;
        }
        $result = StaffTagReviewLogic::approve((int) $params['id'], $this->adminId);
        if ($result) {
            return $this->success('审核通过');
        }

        return $this->fail(StaffTagReviewLogic::getError());
    }

    public function reject()
    {
        $params = (new StaffTagReviewValidate())->post()->goCheck('reject');
        if ($response = $this->checkApplyScope((int)$params['id'])) {
            return $response;
        }
        $result = StaffTagReviewLogic::reject((int) $params['id'], $this->adminId, (string) $params['reject_reason']);
        if ($result) {
            return $this->success('已拒绝');
        }

        return $this->fail(StaffTagReviewLogic::getError());
    }

    /**
     * @notes 校验服务人员角色的数据范围
     */
    protected function checkApplyScope(int $id)
    {
        if (!StaffService::isStaffRole($this->adminInfo)) {
            return null;
        }

        $staffId = (int) StaffTagApply::where('id', $id)->value('staff_id');
        if (!StaffService::canAccessStaff($this->adminId, $this->adminInfo, $staffId)) {
            return $this->fail('无权限操作');
        }

        return null;
    }
}
