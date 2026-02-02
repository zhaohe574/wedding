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
        if (true === $result) {
            return $this->success('保存成功', [], 1, 1);
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

        $params = $this->request->get();
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

        $params = (new StaffCenterValidate())->goCheck('workDelete');
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

        $result = StaffCenterLogic::packageLists($this->userId);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 关联套餐
     */
    public function packageAdd()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('packageAdd');
        $result = StaffCenterLogic::packageAdd($this->userId, (int) $params['package_id']);
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
     * @notes 动态列表
     */
    public function dynamicLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = $this->request->get();
        $result = StaffCenterLogic::dynamicLists($this->userId, $params);
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
            return $this->success('发布成功', [], 1, 1);
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
            return $this->success('保存成功', [], 1, 1);
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
