<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 动态管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\dynamic;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\dynamic\DynamicLists;
use app\adminapi\lists\dynamic\DynamicCommentLists;
use app\adminapi\logic\dynamic\DynamicLogic;
use app\adminapi\validate\dynamic\DynamicValidate;

/**
 * 动态管理控制器
 * Class DynamicController
 * @package app\adminapi\controller\dynamic
 */
class DynamicController extends BaseAdminController
{
    /**
     * @notes 动态列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new DynamicLists());
    }

    /**
     * @notes 动态详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new DynamicValidate())->goCheck('detail');
        $result = DynamicLogic::detail($params['id']);
        if ($result === null) {
            return $this->fail('动态不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 审核动态
     * @return \think\response\Json
     */
    public function audit()
    {
        $params = (new DynamicValidate())->post()->goCheck('audit');
        $result = DynamicLogic::audit($params['id'], $this->adminId, $params['approved'], $params['remark'] ?? '');
        if (true === $result) {
            return $this->success('审核成功');
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 下架动态
     * @return \think\response\Json
     */
    public function offline()
    {
        $params = (new DynamicValidate())->post()->goCheck('offline');
        $result = DynamicLogic::offline($params['id'], $this->adminId, $params['reason'] ?? '');
        if (true === $result) {
            return $this->success('下架成功');
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 设置置顶
     * @return \think\response\Json
     */
    public function setTop()
    {
        $params = (new DynamicValidate())->post()->goCheck('setTop');
        $result = DynamicLogic::setTop($params['id'], $params['is_top']);
        if (true === $result) {
            return $this->success('设置成功');
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 设置热门
     * @return \think\response\Json
     */
    public function setHot()
    {
        $params = (new DynamicValidate())->post()->goCheck('setHot');
        $result = DynamicLogic::setHot($params['id'], $params['is_hot']);
        if (true === $result) {
            return $this->success('设置成功');
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 删除动态
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new DynamicValidate())->post()->goCheck('detail');
        $result = DynamicLogic::delete($params['id'], $this->adminId);
        if (true === $result) {
            return $this->success('删除成功');
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 评论列表
     * @return \think\response\Json
     */
    public function commentLists()
    {
        return $this->dataLists(new DynamicCommentLists());
    }

    /**
     * @notes 删除评论
     * @return \think\response\Json
     */
    public function deleteComment()
    {
        $params = (new DynamicValidate())->post()->goCheck('commentId');
        $result = DynamicLogic::deleteComment($params['comment_id'], $this->adminId);
        if (true === $result) {
            return $this->success('删除成功');
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 设置评论置顶
     * @return \think\response\Json
     */
    public function setCommentTop()
    {
        $params = (new DynamicValidate())->post()->goCheck('setCommentTop');
        $result = DynamicLogic::setCommentTop($params['comment_id'], $params['is_top']);
        if (true === $result) {
            return $this->success('设置成功');
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 动态统计
     * @return \think\response\Json
     */
    public function statistics()
    {
        $params = $this->request->get();
        $result = DynamicLogic::statistics($params);
        return $this->data($result);
    }

    /**
     * @notes 获取动态类型选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        $result = DynamicLogic::getTypeOptions();
        return $this->data($result);
    }

    /**
     * @notes 获取动态状态选项
     * @return \think\response\Json
     */
    public function statusOptions()
    {
        $result = DynamicLogic::getStatusOptions();
        return $this->data($result);
    }
}
