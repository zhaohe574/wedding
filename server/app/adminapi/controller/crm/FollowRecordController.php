<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 跟进记录控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\crm;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\crm\FollowRecordLists;
use app\adminapi\logic\crm\FollowRecordLogic;
use app\adminapi\validate\crm\FollowRecordValidate;

/**
 * 跟进记录控制器
 */
class FollowRecordController extends BaseAdminController
{
    /**
     * @notes 跟进记录列表
     */
    public function lists()
    {
        return $this->dataLists(new FollowRecordLists());
    }

    /**
     * @notes 跟进记录详情
     */
    public function detail()
    {
        $params = (new FollowRecordValidate())->goCheck('detail');
        $detail = FollowRecordLogic::detail((int)$params['id'], $this->getCrmAdvisorScopeId(), $this->adminInfo);
        if ($detail === false) {
            return $this->fail(FollowRecordLogic::getError());
        }
        return $this->data($detail);
    }

    /**
     * @notes 新增跟进记录
     */
    public function add()
    {
        $params = (new FollowRecordValidate())->post()->goCheck('add');
        $result = FollowRecordLogic::add($params, $this->adminId, $this->getCrmAdvisorScopeId(), $this->adminInfo);
        if ($result === true) {
            return $this->success('新增成功', [], 1, 1);
        }
        return $this->fail(FollowRecordLogic::getError());
    }

    /**
     * @notes 跟进记录选项
     */
    public function options()
    {
        return $this->data(FollowRecordLogic::options());
    }

    /**
     * @notes 可跟进客户选项
     */
    public function customerOptions()
    {
        return $this->data(FollowRecordLogic::customerOptions($this->getCrmAdvisorScopeId()));
    }
}
