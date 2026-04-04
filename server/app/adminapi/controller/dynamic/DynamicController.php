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
use app\common\model\dynamic\Dynamic;
use app\common\service\DynamicOwnerService;
use app\common\service\StaffService;

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
     * @notes 添加动态
     * @return \think\response\Json
     */
    public function add()
    {
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            return $this->fail('无权限操作');
        }
        $params = (new DynamicValidate())->post()->goCheck('add');
        $result = DynamicLogic::add($this->adminId, $params);
        if (true === $result) {
            return $this->success('发布成功');
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 编辑动态
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new DynamicValidate())->post()->goCheck('edit');
        if ($response = $this->checkDynamicScope((int)$params['id'])) {
            return $response;
        }
        $result = DynamicLogic::edit((int)$params['id'], $params);
        if (true === $result) {
            return $this->success('编辑成功');
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 动态详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new DynamicValidate())->goCheck('detail');
        if ($response = $this->checkDynamicScope((int)$params['id'])) {
            return $response;
        }
        $result = DynamicLogic::detail((int)$params['id']);
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            return $this->fail('无权限操作');
        }
        $params = (new DynamicValidate())->post()->goCheck('audit');
        $result = DynamicLogic::audit((int)$params['id'], $this->adminId, (bool)$params['approved'], $params['remark'] ?? '');
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            return $this->fail('无权限操作');
        }
        $params = (new DynamicValidate())->post()->goCheck('offline');
        $result = DynamicLogic::offline((int)$params['id'], $this->adminId, $params['reason'] ?? '');
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            return $this->fail('无权限操作');
        }
        $params = (new DynamicValidate())->post()->goCheck('setTop');
        $result = DynamicLogic::setTop((int)$params['id'], (int)$params['is_top']);
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            return $this->fail('无权限操作');
        }
        $params = (new DynamicValidate())->post()->goCheck('setHot');
        $result = DynamicLogic::setHot((int)$params['id'], (int)$params['is_hot']);
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
        if ($response = $this->checkDynamicScope((int)$params['id'])) {
            return $response;
        }
        $result = DynamicLogic::delete((int)$params['id'], $this->adminId);
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            return $this->fail('无权限操作');
        }
        return $this->dataLists(new DynamicCommentLists());
    }

    /**
     * @notes 删除评论
     * @return \think\response\Json
     */
    public function deleteComment()
    {
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            return $this->fail('无权限操作');
        }
        $params = (new DynamicValidate())->post()->goCheck('commentId');
        $result = DynamicLogic::deleteComment((int)$params['comment_id'], $this->adminId);
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            return $this->fail('无权限操作');
        }
        $params = (new DynamicValidate())->post()->goCheck('setCommentTop');
        $result = DynamicLogic::setCommentTop((int)$params['comment_id'], (int)$params['is_top']);
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
        $staffScopeId = StaffService::getStaffScopeId($this->adminId, $this->adminInfo);
        if ($staffScopeId > 0) {
            return $this->fail('无权限操作');
        }
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

    /**
     * @notes 校验动态数据范围
     * @param int $dynamicId
     * @return \think\response\Json|null
     */
    protected function checkDynamicScope(int $dynamicId)
    {
        $ownerContext = $this->getDynamicOwnerContext();
        if (!DynamicOwnerService::isStaffContext($ownerContext)) {
            return null;
        }
        if (!DynamicOwnerService::isResolvedContext($ownerContext)) {
            return $this->fail(DynamicOwnerService::getOwnerViewDeniedMessage());
        }
        $dynamic = DynamicOwnerService::findOwnedStaffDynamic(
            $dynamicId,
            (int)$ownerContext['owner_staff_id']
        );
        if (!$dynamic) {
            return $this->fail('无权限操作');
        }
        return null;
    }

    /**
     * @notes 我的动态列表
     * @return \think\response\Json
     */
    public function myDynamics()
    {
        $ownerContext = $this->getDynamicOwnerContext();
        if (!DynamicOwnerService::isStaffContext($ownerContext)) {
            return $this->fail('无权限操作');
        }
        return $this->dataLists(new DynamicLists());
    }

    /**
     * @notes 我的动态详情
     * @return \think\response\Json
     */
    public function myDynamicDetail()
    {
        $ownerContext = $this->getDynamicOwnerContext();
        if (!DynamicOwnerService::isStaffContext($ownerContext)) {
            return $this->fail('无权限操作');
        }
        if (!DynamicOwnerService::isResolvedContext($ownerContext)) {
            return $this->fail(DynamicOwnerService::getOwnerViewDeniedMessage());
        }

        $params = (new DynamicValidate())->goCheck('detail');
        $dynamic = DynamicOwnerService::findOwnedStaffDynamic(
            (int)$params['id'],
            (int)$ownerContext['owner_staff_id']
        );
        if (!$dynamic) {
            return $this->fail('无权限操作');
        }

        $result = DynamicLogic::detail((int)$params['id']);
        if ($result === null) {
            return $this->fail('动态不存在');
        }
        return $this->data($result);
    }

    /**
     * @notes 发布我的动态
     * @return \think\response\Json
     */
    public function myDynamicAdd()
    {
        $ownerContext = $this->getDynamicOwnerContext();
        if (!DynamicOwnerService::isStaffContext($ownerContext)) {
            return $this->fail('无权限操作');
        }
        if (!DynamicOwnerService::isResolvedContext($ownerContext)) {
            return $this->fail(DynamicOwnerService::getOwnerManageDeniedMessage());
        }
        $staffScopeId = (int)$ownerContext['owner_staff_id'];
        $params = (new DynamicValidate())->post()->goCheck('add');
        if ((int)($params['dynamic_type'] ?? 0) === Dynamic::TYPE_ACTIVITY) {
            return $this->fail('活动仅支持管理员发布');
        }
        $result = DynamicLogic::staffAdd($staffScopeId, $params);
        if (true === $result) {
            return $this->success('发布成功', [], 1, 1);
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 编辑我的动态
     * @return \think\response\Json
     */
    public function myDynamicEdit()
    {
        $ownerContext = $this->getDynamicOwnerContext();
        if (!DynamicOwnerService::isStaffContext($ownerContext)) {
            return $this->fail('无权限操作');
        }
        if (!DynamicOwnerService::isResolvedContext($ownerContext)) {
            return $this->fail(DynamicOwnerService::getOwnerManageDeniedMessage());
        }
        $staffScopeId = (int)$ownerContext['owner_staff_id'];
        $params = (new DynamicValidate())->post()->goCheck('edit');
        if ((int)($params['dynamic_type'] ?? 0) === Dynamic::TYPE_ACTIVITY) {
            return $this->fail('活动仅支持管理员发布');
        }
        $result = DynamicLogic::staffEdit($staffScopeId, (int)$params['id'], $params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 删除我的动态
     * @return \think\response\Json
     */
    public function myDynamicDelete()
    {
        $ownerContext = $this->getDynamicOwnerContext();
        if (!DynamicOwnerService::isStaffContext($ownerContext)) {
            return $this->fail('无权限操作');
        }
        if (!DynamicOwnerService::isResolvedContext($ownerContext)) {
            return $this->fail(DynamicOwnerService::getOwnerManageDeniedMessage());
        }
        $staffScopeId = (int)$ownerContext['owner_staff_id'];
        $params = (new DynamicValidate())->post()->goCheck('detail');
        $result = DynamicLogic::staffDelete($staffScopeId, (int)$params['id']);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(DynamicLogic::getError());
    }

    /**
     * @notes 我的动态类型选项
     * @return \think\response\Json
     */
    public function myDynamicTypeOptions()
    {
        $ownerContext = $this->getDynamicOwnerContext();
        if (!DynamicOwnerService::isStaffContext($ownerContext)) {
            return $this->fail('无权限操作');
        }
        $options = array_values(array_filter(
            DynamicLogic::getTypeOptions(),
            static fn(array $item): bool => (int)($item['value'] ?? 0) !== Dynamic::TYPE_ACTIVITY
        ));
        return $this->data($options);
    }

    /**
     * @notes 我的动态状态选项
     * @return \think\response\Json
     */
    public function myDynamicStatusOptions()
    {
        $ownerContext = $this->getDynamicOwnerContext();
        if (!DynamicOwnerService::isStaffContext($ownerContext)) {
            return $this->fail('无权限操作');
        }
        return $this->data(DynamicLogic::getStatusOptions());
    }

    /**
     * @notes 获取动态归属上下文
     */
    protected function getDynamicOwnerContext(): array
    {
        return DynamicOwnerService::resolveStaffOwnerContext($this->adminId, $this->adminInfo);
    }
}
