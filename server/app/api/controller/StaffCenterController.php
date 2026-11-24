<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员中心
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\logic\StaffCenterLogic;
use app\api\validate\StaffCenterValidate;
use app\common\enum\ErrorCodeEnum;
use app\common\service\ConfigService;
use app\common\service\JsonService;

/**
 * 服务人员中心控制器
 * Class StaffCenterController
 * @package app\api\controller
 */
class StaffCenterController extends BaseApiController
{
    private function manualOrderAction(string $action)
    {
        if (!$this->checkFeatureSwitch()) return $this->fail('服务人员中心已关闭', [], 0, 1, ErrorCodeEnum::FEATURE_OFF);
        if (!$this->request->isPost()) return $this->fail('请使用 POST 请求', [], 0, 1, ErrorCodeEnum::VALIDATE_FAILED);
        try {
            $params = $this->request->post();
            $service = \app\common\service\StaffManualOrderService::class;
            if ($action === 'customers') $result = $service::customers($this->userId, (string)($params['mobile'] ?? ''));
            elseif ($action === 'receipt') $result = \app\common\service\OrderReceiptService::submit($this->userId, $params);
            else $result = $service::$action($this->userId, $params);
            return $this->data($result);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function orderCustomerLookup() { return $this->manualOrderAction('customers'); }
    public function manualOrderOptions() { return $this->manualOrderAction('options'); }
    public function manualOrderPreview() { return $this->manualOrderAction('preview'); }
    public function manualOrderCreate() { return $this->manualOrderAction('create'); }
    public function orderReceiptSubmit() { return $this->manualOrderAction('receipt'); }

    /**
     * @notes 功能开关校验
     */
    protected function checkFeatureSwitch(): bool
    {
        if ((int) ConfigService::get('feature_switch', 'staff_center', 1) !== 1) {
            JsonService::throw('服务人员中心已关闭', [], 0, 1, ErrorCodeEnum::FEATURE_OFF);
        }
        return true;
    }

    /**
     * @notes 写能力灰度开关校验（仅覆盖写操作 action，读操作不校验）
     *
     * staff_center_write 默认 0（关闭）。开关打开后小程序端「资料 / 动态 / 作品 / 证书」
     * 写入口才生效；后台经 FeatureSwitchLogic 配置灰度。void 签名下通过
     * JsonService::throw（HttpResponseException）直接中断请求，响应结构与 fail() 一致。
     */
    private function checkWriteSwitch(): void
    {
        if ((int) ConfigService::get('feature_switch', 'staff_center_write', 1) !== 1) {
            JsonService::throw('功能灰度中，暂未开放', [], 0, 1, ErrorCodeEnum::FEATURE_OFF);
        }
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

        // 写能力灰度开关
        $this->checkWriteSwitch();

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

        // 写能力灰度开关
        $this->checkWriteSwitch();

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

        // 写能力灰度开关
        $this->checkWriteSwitch();

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

        // 写能力灰度开关
        $this->checkWriteSwitch();

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

        // 写能力灰度开关
        $this->checkWriteSwitch();

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

        // 写能力灰度开关
        $this->checkWriteSwitch();

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

        // 写能力灰度开关
        $this->checkWriteSwitch();

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
        $this->checkWriteSwitch();

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
        $this->checkWriteSwitch();

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
        $this->checkWriteSwitch();

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
        $this->checkWriteSwitch();

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
        $this->checkWriteSwitch();

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
        $this->checkWriteSwitch();

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
     * @notes 全年已预约档期
     */
    public function scheduleBookedYear()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('scheduleBookedYear');
        $result = StaffCenterLogic::scheduleBookedYear($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    public function manualScheduleAdd()
    {
        if (!$this->checkFeatureSwitch()) return $this->fail('服务人员中心已关闭');
        $params = (new StaffCenterValidate())->post()->goCheck('manualScheduleAdd');
        $result = StaffCenterLogic::manualScheduleAdd($this->userId, $params);
        return $result === false ? $this->fail(StaffCenterLogic::getError()) : $this->success('添加成功', $result, 1, 1);
    }

    public function manualScheduleDetail()
    {
        if (!$this->checkFeatureSwitch()) return $this->fail('服务人员中心已关闭');
        $params = (new StaffCenterValidate())->get()->goCheck('manualScheduleDetail');
        $result = StaffCenterLogic::manualScheduleDetail($this->userId, (int)$params['manual_schedule_id']);
        return $result === false ? $this->fail(StaffCenterLogic::getError()) : $this->data($result);
    }

    public function manualScheduleEdit()
    {
        if (!$this->checkFeatureSwitch()) return $this->fail('服务人员中心已关闭');
        $params = (new StaffCenterValidate())->post()->goCheck('manualScheduleEdit');
        $result = StaffCenterLogic::manualScheduleEdit($this->userId, $params);
        return $result === false ? $this->fail(StaffCenterLogic::getError()) : $this->success('修改成功', $result, 1, 1);
    }

    public function manualScheduleCancel()
    {
        if (!$this->checkFeatureSwitch()) return $this->fail('服务人员中心已关闭');
        $params = (new StaffCenterValidate())->post()->goCheck('manualScheduleAction');
        $result = StaffCenterLogic::manualScheduleCancel($this->userId, $params);
        return $result ? $this->success('已取消', [], 1, 1) : $this->fail(StaffCenterLogic::getError());
    }

    public function manualScheduleComplete()
    {
        if (!$this->checkFeatureSwitch()) return $this->fail('服务人员中心已关闭');
        $params = (new StaffCenterValidate())->post()->goCheck('manualScheduleAction');
        $result = StaffCenterLogic::manualScheduleComplete($this->userId, $params);
        return $result ? $this->success('已完成', [], 1, 1) : $this->fail(StaffCenterLogic::getError());
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

    /**
     * @notes 取消订单 / 拒绝接单
     */
    public function orderCancel()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        (new StaffCenterValidate())->post()->goCheck('orderCancel');
        $id = (int)$this->request->post('id/d', 0);
        $reason = trim((string)$this->request->post('reason/s', ''));
        $result = StaffCenterLogic::orderCancel($this->userId, $id, $reason);
        if (true === $result) {
            return $this->success('订单已取消', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    public function orderConfirmLetterGenerate()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        $params = (new StaffCenterValidate())->post()->goCheck('orderConfirmLetterGenerate');
        $result = StaffCenterLogic::orderConfirmLetterGenerate(
            $this->userId,
            (int)($params['order_id'] ?? 0),
            (int)($params['config_id'] ?? 0),
            (int)($params['manual_schedule_id'] ?? 0)
        );
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    public function scheduleConfirmLetterConfig()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        $result = StaffCenterLogic::scheduleConfirmLetterConfig(
            $this->userId,
            (int)$this->request->get('config_id', 0)
        );
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    public function scheduleConfirmLetterSaveConfig()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        $params = (new StaffCenterValidate())->post()->goCheck('scheduleConfirmLetterConfig');
        $result = StaffCenterLogic::scheduleConfirmLetterSaveConfig($this->userId, $params);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('保存成功', $result, 1, 1);
    }

    public function scheduleConfirmLetterPreview()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        $params = (new StaffCenterValidate())->post()->goCheck('scheduleConfirmLetterConfig');
        $result = StaffCenterLogic::scheduleConfirmLetterPreview($this->userId, $params);
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
        $result = StaffCenterLogic::orderConfirmLetterHistory($this->userId, (int)($params['order_id'] ?? 0), (int)($params['manual_schedule_id'] ?? 0));
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
     * @notes 我的结算
     */
    public function settlementLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('settlementLists');
        $result = StaffCenterLogic::settlementLists($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 获取转账确认参数
     */
    public function settlementReceive()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('settlementReceive');
        $result = StaffCenterLogic::settlementReceive($this->userId, (int)$params['id']);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 同步转账状态
     */
    public function settlementSync()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('settlementReceive');
        $result = StaffCenterLogic::settlementSync($this->userId, (int)$params['id']);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('同步成功', $result, 1, 1);
    }

    /**
     * @notes 候补名单列表
     */
    public function waitlistLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('waitlistLists');
        $result = StaffCenterLogic::waitlistLists($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 通知候补客户预约
     */
    public function waitlistNotify()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('waitlistNotify');
        $result = StaffCenterLogic::waitlistNotify($this->userId, (int)$params['id']);
        if (true === $result) {
            return $this->success('已通知客户预约', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 服务日作战面板
     */
    public function serviceDayPanel()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('serviceDayPanel');
        $result = StaffCenterLogic::serviceDayPanel($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 电子名片信息
     */
    public function businessCard()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $result = StaffCenterLogic::businessCard($this->userId);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 文案模板库列表
     */
    public function copyTemplateLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('copyTemplateLists');
        $result = StaffCenterLogic::copyTemplateLists($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 保存自定义文案模板
     */
    public function copyTemplateSave()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('copyTemplateSave');
        $result = StaffCenterLogic::copyTemplateSave($this->userId, $params);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('保存成功', $result, 1, 1);
    }

    /**
     * @notes 删除自定义文案模板
     */
    public function copyTemplateDelete()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('copyTemplateAction');
        $result = StaffCenterLogic::copyTemplateDelete($this->userId, (int)$params['id']);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 记录文案模板使用次数
     */
    public function copyTemplateUse()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('copyTemplateAction');
        $result = StaffCenterLogic::copyTemplateUse($this->userId, (int)$params['id']);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
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

        // 写能力灰度开关
        $this->checkWriteSwitch();

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

        // 写能力灰度开关
        $this->checkWriteSwitch();

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

        // 写能力灰度开关
        $this->checkWriteSwitch();

        $params = (new StaffCenterValidate())->post()->goCheck('dynamicDelete');
        $result = StaffCenterLogic::dynamicDelete($this->userId, (int) $params['id']);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 新人档案列表
     */
    public function coupleLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('coupleLists');
        $result = StaffCenterLogic::coupleLists($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 新人档案详情
     */
    public function coupleDetail()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('coupleDetail');
        $result = StaffCenterLogic::coupleDetail(
            $this->userId,
            (string)$params['source_type'],
            (int)$params['target_id']
        );
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 发送或补发新人问卷
     */
    public function coupleSendQuestionnaire()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('coupleSendQuestionnaire');
        $result = StaffCenterLogic::coupleSendQuestionnaire($this->userId, (int)$params['order_id']);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('已发送问卷提醒', $result, 1, 1);
    }

    /**
     * @notes 保存服务手记
     */
    public function serviceNoteSave()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('serviceNoteSave');
        $result = StaffCenterLogic::serviceNoteSave($this->userId, $params);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('保存成功', $result, 1, 1);
    }

    /**
     * @notes 置顶或取消置顶服务手记
     */
    public function serviceNoteTogglePin()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('serviceNoteAction');
        $result = StaffCenterLogic::serviceNoteTogglePin($this->userId, (int)$params['id']);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('操作成功', $result, 1, 1);
    }

    /**
     * @notes 删除服务手记
     */
    public function serviceNoteDelete()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('serviceNoteAction');
        $result = StaffCenterLogic::serviceNoteDelete($this->userId, (int)$params['id']);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 跟进提醒列表
     */
    public function followupLists()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('followupLists');
        $result = StaffCenterLogic::followupLists($this->userId, $params);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 保存跟进提醒
     */
    public function followupSave()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('followupSave');
        $result = StaffCenterLogic::followupSave($this->userId, $params);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('保存成功', $result, 1, 1);
    }

    /**
     * @notes 更新跟进提醒状态（完成/重开/顺延/忽略）
     */
    public function followupAction()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('followupAction');
        $result = StaffCenterLogic::followupAction(
            $this->userId,
            (int)$params['id'],
            (string)$params['action']
        );
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('操作成功', $result, 1, 1);
    }

    /**
     * @notes 删除跟进提醒
     */
    public function followupDelete()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('followupDelete');
        $result = StaffCenterLogic::followupDelete($this->userId, (int)$params['id']);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 一键初始化标准婚礼 Checklist
     */
    public function checklistInitPresets()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('checklistInitPresets');
        $result = StaffCenterLogic::checklistInitPresets(
            $this->userId,
            (string)$params['source_type'],
            (int)$params['target_id']
        );
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('已生成标准清单', $result, 1, 1);
    }

    /**
     * @notes 保存婚礼 Checklist 事项
     */
    public function checklistSave()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('checklistSave');
        $result = StaffCenterLogic::checklistSave($this->userId, $params);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('保存成功', $result, 1, 1);
    }

    /**
     * @notes 切换婚礼 Checklist 完成态
     */
    public function checklistToggle()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('checklistItemAction');
        $result = StaffCenterLogic::checklistToggle($this->userId, (int)$params['id']);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('状态已更新', $result, 1, 1);
    }

    /**
     * @notes 删除婚礼 Checklist 事项
     */
    public function checklistDelete()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('checklistItemAction');
        $result = StaffCenterLogic::checklistDelete($this->userId, (int)$params['id']);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 我的团队概览与近14天档期热力视图
     */
    public function teamOverview()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $result = StaffCenterLogic::teamOverview($this->userId);
        if (empty($result) && StaffCenterLogic::getError()) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 队长更新团队信息
     */
    public function teamUpdateInfo()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('teamUpdateInfo');
        $result = StaffCenterLogic::teamUpdateInfo($this->userId, $params);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('团队信息已更新', $result, 1, 1);
    }

    /**
     * @notes 搜索可添加进团队的候选服务人员
     */
    public function teamMemberCandidates()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->get()->goCheck('teamMemberCandidates');
        $result = StaffCenterLogic::teamMemberCandidates(
            $this->userId,
            (string)($params['keyword'] ?? '')
        );
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 队长添加成员至团队
     */
    public function teamMemberAdd()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('teamMemberAdd');
        $result = StaffCenterLogic::teamMemberAdd($this->userId, (int)$params['target_staff_id']);
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('已添加团队成员', $result, 1, 1);
    }

    /**
     * @notes 队长启停团队成员状态
     */
    public function teamMemberToggleStatus()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('teamMemberToggleStatus');
        $result = StaffCenterLogic::teamMemberToggleStatus(
            $this->userId,
            (int)$params['member_id'],
            (int)$params['status']
        );
        if ($result === false) {
            return $this->fail(StaffCenterLogic::getError());
        }
        return $this->success('成员状态已更新', $result, 1, 1);
    }

    /**
     * @notes 队长移除团队成员
     */
    public function teamMemberRemove()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }

        $params = (new StaffCenterValidate())->post()->goCheck('teamMemberRemove');
        $result = StaffCenterLogic::teamMemberRemove($this->userId, (int)$params['member_id']);
        if (true === $result) {
            return $this->success('已移除团队成员', [], 1, 1);
        }
        return $this->fail(StaffCenterLogic::getError());
    }

    /**
     * @notes 活动入场核销预览（扫码/输码）
     */
    public function activityCheckinPreview()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        $staffId = StaffCenterLogic::getStaffId($this->userId);
        if ($staffId <= 0) {
            return $this->fail('无服务人员核销权限');
        }

        $code = (string)($this->request->param('code', ''));
        $registrationId = (int)($this->request->param('registration_id/d', 0));
        [$success, $message, $data] = \app\common\service\ActivityRegistrationService::previewCheckin($code, $registrationId);
        return $success ? $this->data($data) : $this->fail($message);
    }

    /**
     * @notes 执行活动签到核销
     */
    public function activityCheckin()
    {
        if (!$this->checkFeatureSwitch()) {
            return $this->fail('服务人员中心已关闭');
        }
        if (!$this->request->isPost()) {
            return $this->fail('请使用 POST 请求');
        }
        $staffId = StaffCenterLogic::getStaffId($this->userId);
        if ($staffId <= 0) {
            return $this->fail('无服务人员核销权限');
        }

        $params = $this->request->post();
        $code = (string)($params['code'] ?? '');
        $registrationId = (int)($params['registration_id'] ?? 0);
        [$success, $message, $data] = \app\common\service\ActivityRegistrationService::performCheckin(
            $code,
            $registrationId,
            \app\common\model\dynamic\ActivityRegistration::CHECKIN_OPERATOR_STAFF,
            $staffId
        );
        return $success ? $this->success($message, $data) : $this->fail($message, $data);
    }
}
