<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员中心
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\StaffCenterLogic;
use app\api\validate\StaffCenterValidate;
use app\common\service\ConfigService;

/**
 * 服务人员中心控制器
 * Class StaffCenterController
 * @package app\api\controller
 */
class StaffCenterController extends BaseApiController
{
    /**
     * @notes 功能开关校验
     */
    protected function checkFeatureSwitch(): bool
    {
        if ((int) ConfigService::get('feature_switch', 'staff_center', 1) !== 1) {
            $this->fail('服务人员中心已关闭');
            return false;
        }
        return true;
    }

    /**
     * @notes 工作台首页
     */
    public function dashboard()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $result = StaffCenterLogic::dashboard($this->userId);
        if (empty($result)) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 订单状态统计
     */
    public function orderStats()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $result = StaffCenterLogic::orderStats($this->userId);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 个人资料
     */
    public function profile()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $result = StaffCenterLogic::profile($this->userId);
        if (empty($result)) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 更新个人资料
     */
    public function updateProfile()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('profile');
        $result = StaffCenterLogic::updateProfile($this->userId, $params);
        if (false !== $result) {
            $tagAction = is_array($result) ? ($result['tag_action'] ?? 'applied') : 'applied';
            $message = $tagAction === 'pending'
                ? '资料已保存，标签修改已提交审核'
                : '保存成功';
            return $this->success($message, is_array($result) ? $result : [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 证书列表
     */
    public function certificateLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('certificateLists');
        $result = StaffCenterLogic::certificateLists($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 证书详情
     */
    public function certificateDetail()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('certificateDetail');
        $result = StaffCenterLogic::certificateDetail($this->userId, (int) $params['id']);
        if (empty($result)) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 添加证书
     */
    public function certificateAdd()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('certificateAdd');
        $result = StaffCenterLogic::certificateAdd($this->userId, $params);
        if (true === $result) {
            return $this->success('提交成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 编辑证书
     */
    public function certificateEdit()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('certificateEdit');
        $result = StaffCenterLogic::certificateEdit($this->userId, (int) $params['id'], $params);
        if (true === $result) {
            return $this->success('已重新提交审核', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 删除证书
     */
    public function certificateDelete()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('certificateDelete');
        $result = StaffCenterLogic::certificateDelete($this->userId, (int) $params['id']);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 作品列表
     */
    public function workLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('workLists');
        $result = StaffCenterLogic::workLists($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 作品详情
     */
    public function workDetail()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('workDetail');
        $result = StaffCenterLogic::workDetail($this->userId, (int) $params['id']);
        if (empty($result)) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 添加作品
     */
    public function workAdd()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('workAdd');
        $result = StaffCenterLogic::workAdd($this->userId, $params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 编辑作品
     */
    public function workEdit()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('workEdit');
        $result = StaffCenterLogic::workEdit($this->userId, (int) $params['id'], $params);
        if (true === $result) {
            return $this->success('保存成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 删除作品
     */
    public function workDelete()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('workDelete');
        $result = StaffCenterLogic::workDelete($this->userId, (int) $params['id']);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 套餐列表
     */
    public function packageLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('packageLists');
        $result = StaffCenterLogic::packageLists($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 套餐详情
     */
    public function packageDetail()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('packageDetail');
        $result = StaffCenterLogic::packageDetail($this->userId, (int) $params['package_id']);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 新增套餐
     */
    public function packageAdd()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('packageAdd');
        $result = StaffCenterLogic::packageAdd($this->userId, $params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 更新套餐配置
     */
    public function packageUpdate()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('packageUpdate');
        $result = StaffCenterLogic::packageUpdate($this->userId, (int) $params['package_id'], $params);
        if (true === $result) {
            return $this->success('保存成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 移除套餐
     */
    public function packageRemove()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('packageRemove');
        $result = StaffCenterLogic::packageRemove($this->userId, (int) $params['package_id']);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 附加服务列表
     */
    public function addonLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('addonLists');
        $result = StaffCenterLogic::addonLists($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 附加服务详情
     */
    public function addonDetail()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('addonDetail');
        $result = StaffCenterLogic::addonDetail($this->userId, (int) $params['addon_id']);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 新增附加服务
     */
    public function addonAdd()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('addonAdd');
        $result = StaffCenterLogic::addonAdd($this->userId, $params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 更新附加服务
     */
    public function addonUpdate()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('addonUpdate');
        $result = StaffCenterLogic::addonUpdate($this->userId, (int) $params['addon_id'], $params);
        if (true === $result) {
            return $this->success('保存成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 删除附加服务
     */
    public function addonRemove()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('addonRemove');
        $result = StaffCenterLogic::addonRemove($this->userId, (int) $params['addon_id']);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 月度档期
     */
    public function scheduleMonth()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $year = (int) $this->request->get('year', date('Y'));
        $month = (int) $this->request->get('month', date('m'));
        $result = StaffCenterLogic::scheduleMonth($this->userId, $year, $month);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 设置档期状态
     */
    public function scheduleSetStatus()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('scheduleSet');
        $result = StaffCenterLogic::scheduleSetStatus($this->userId, $params);
        if (true === $result) {
            return $this->success('设置成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 订单列表
     */
    public function orderLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('orderLists');
        $result = StaffCenterLogic::orderLists($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 订单详情
     */
    public function orderDetail()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('orderDetail');
        $result = StaffCenterLogic::orderDetail($this->userId, (int) $params['id']);
        if (empty($result)) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 确认订单
     */
    public function orderConfirm()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('orderConfirm');
        $result = StaffCenterLogic::orderConfirm($this->userId, (int) $params['id']);
        if (true === $result) {
            return $this->success('确认成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 完成服务
     */
    public function orderComplete()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('orderComplete');
        $result = StaffCenterLogic::orderComplete($this->userId, (int) $params['id']);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 开始履约
     */
    public function orderStartService()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('orderStartService');
        $result = StaffCenterLogic::orderStartService($this->userId, (int) $params['id']);
        if (true === $result) {
            return $this->success('开始履约成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    public function orderConfirmLetterGenerate()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        $params = (new StaffCenterValidate())->post()->goCheck('orderConfirmLetterGenerate');
        $result = StaffCenterLogic::orderConfirmLetterGenerate($this->userId, (int) $params['order_id']);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    public function orderConfirmLetterSaveAssets()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        $params = (new StaffCenterValidate())->post()->goCheck('orderConfirmLetterAsset');
        $result = StaffCenterLogic::orderConfirmLetterSaveAssets($this->userId, $params);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('保存成功', $result, 1, 1);
    }

    public function orderConfirmLetterPush()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        $params = (new StaffCenterValidate())->post()->goCheck('orderConfirmLetterPush');
        $result = StaffCenterLogic::orderConfirmLetterPush($this->userId, (int) $params['letter_id']);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('推送成功', $result, 1, 1);
    }

    public function orderConfirmLetterDetail()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        $params = (new StaffCenterValidate())->get()->goCheck('orderConfirmLetterDetail');
        $result = StaffCenterLogic::orderConfirmLetterDetail($this->userId, (int) $params['letter_id']);
        if ($result === null) {
            return $this->fail(StaffCenterLogic::getError() ?: '确认函不存在');
        }
        return $this->data($result);
    }

    public function orderConfirmLetterHistory()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        $params = (new StaffCenterValidate())->get()->goCheck('orderConfirmLetterHistory');
        $result = StaffCenterLogic::orderConfirmLetterHistory($this->userId, (int) $params['order_id']);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    public function orderConfirmLetterRegenerateAssets()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        $params = (new StaffCenterValidate())->post()->goCheck('orderConfirmLetterAsset');
        $result = StaffCenterLogic::orderConfirmLetterSaveAssets($this->userId, $params);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('图片缓存已更新', $result, 1, 1);
    }

    /**
     * @notes 动态列表
     */
    public function dynamicLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('dynamicLists');
        $result = StaffCenterLogic::dynamicLists($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 动态详情
     */
    public function dynamicDetail()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('dynamicDetail');
        $result = StaffCenterLogic::dynamicDetail($this->userId, (int) $params['id']);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 发布动态
     */
    public function dynamicAdd()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('dynamicAdd');
        $result = StaffCenterLogic::dynamicAdd($this->userId, $params);
        if (true === $result) {
            return $this->success('发布成功，已提交审核', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 编辑动态
     */
    public function dynamicEdit()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('dynamicEdit');
        $result = StaffCenterLogic::dynamicEdit($this->userId, (int) $params['id'], $params);
        if (true === $result) {
            return $this->success('保存成功，已重新提交审核', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 删除动态
     */
    public function dynamicDelete()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('dynamicDelete');
        $result = StaffCenterLogic::dynamicDelete($this->userId, (int) $params['id']);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }
}
