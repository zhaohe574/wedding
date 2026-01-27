<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态评论审核控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\dynamic;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\dynamic\DynamicCommentReviewLists;
use app\adminapi\logic\dynamic\DynamicCommentLogic;
use app\adminapi\validate\dynamic\DynamicCommentValidate;

/**
 * 动态评论审核控制器
 * Class DynamicCommentController
 * @package app\adminapi\controller\dynamic
 */
class DynamicCommentController extends BaseAdminController
{
    /**
     * @notes 获取待审核评论列表
     * @return \think\response\Json
     */
    public function reviewList()
    {
        return $this->dataLists(new DynamicCommentReviewLists());
    }

    /**
     * @notes 获取评论详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new DynamicCommentValidate())->goCheck('detail');
        $result = DynamicCommentLogic::detail((int)$params['id']);
        if ($result === null) {
            return $this->fail('评论不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 审核通过评论
     * @return \think\response\Json
     */
    public function approve()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('approve');
        $result = DynamicCommentLogic::approve((int)$params['id'], $this->adminId);
        if ($result) {
            return $this->success('审核通过');
        }
        return $this->fail(DynamicCommentLogic::getError());
    }

    /**
     * @notes 拒绝评论
     * @return \think\response\Json
     */
    public function reject()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('reject');
        $result = DynamicCommentLogic::reject((int)$params['id'], $this->adminId, $params['remark'] ?? '');
        if ($result) {
            return $this->success('已拒绝');
        }
        return $this->fail(DynamicCommentLogic::getError());
    }


    /**
     * @notes 批量审核通过
     * @return \think\response\Json
     */
    public function batchApprove()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('batchApprove');
        $ids = array_map('intval', $params['ids']);
        $result = DynamicCommentLogic::batchApprove($ids, $this->adminId);
        return $this->success('批量审核完成', $result);
    }

    /**
     * @notes 批量拒绝
     * @return \think\response\Json
     */
    public function batchReject()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('batchReject');
        $ids = array_map('intval', $params['ids']);
        $result = DynamicCommentLogic::batchReject($ids, $this->adminId, $params['remark'] ?? '');
        return $this->success('批量拒绝完成', $result);
    }

    /**
     * @notes 获取评论审核配置
     * @return \think\response\Json
     */
    public function getReviewConfig()
    {
        $config = DynamicCommentLogic::getReviewConfig();
        return $this->data(['enabled' => $config]);
    }

    /**
     * @notes 设置评论审核配置
     * @return \think\response\Json
     */
    public function setReviewConfig()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('setConfig');
        $result = DynamicCommentLogic::setReviewConfig((int)$params['enabled']);
        if ($result) {
            return $this->success('设置成功');
        }
        return $this->fail(DynamicCommentLogic::getError());
    }

    /**
     * @notes 删除评论
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('delete');
        $result = DynamicCommentLogic::delete((int)$params['id'], $this->adminId);
        if ($result) {
            return $this->success('删除成功');
        }
        return $this->fail(DynamicCommentLogic::getError());
    }

    /**
     * @notes 批量删除评论
     * @return \think\response\Json
     */
    public function batchDelete()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('batchDelete');
        $ids = array_map('intval', $params['ids']);
        $result = DynamicCommentLogic::batchDelete($ids, $this->adminId);
        return $this->success('批量删除完成', $result);
    }
}
