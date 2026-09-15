<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

namespace app\api\controller;

use app\api\logic\WechatLogic;


/**
 * 微信
 * Class WechatController
 * @package app\api\controller
 */
class WechatController extends BaseApiController
{
    public array $notNeedLogin = [];



    /**
     * 获取公众号通知绑定入口。
     */
    public function oaSubscribeEntry()
    {
        $result = WechatLogic::oaSubscribeEntry($this->userId);
        if ($result === false) {
            return $this->fail(WechatLogic::getError());
        }

        return $this->data($result);
    }

    /**
     * 获取公众号通知绑定状态。
     */
    public function oaSubscribeStatus()
    {
        return $this->data(WechatLogic::oaSubscribeStatus($this->userId));
    }

    public function oaGuideClaim()
    {
        return $this->data(['show' => \app\common\service\wechat\WechatOaBindingService::claimGuide($this->userId)]);
    }

    public function oaReminderSkipToday()
    {
        if (!$this->request->isPost()) return $this->fail('请使用 POST 请求');
        try {
            return $this->data(\app\common\service\wechat\WechatOaBindingService::skipReminderToday($this->userId));
        } catch (\Throwable $e) { return $this->fail('暂时无法保存提醒偏好'); }
    }

    public function oaSubscribeConfirm()
    {
        try {
            return $this->data(\app\common\service\wechat\WechatOaBindingService::confirm(
                $this->userId, (string) $this->request->post('binding_code', '')
            ));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function oaSubscribeUnbind()
    {
        \app\common\service\wechat\WechatOaBindingService::unbind($this->userId);
        return $this->success('已解除绑定');
    }

    /** 查询服务号邀请，只读且不建立账号关联。 */
    public function oaInvitationStatus()
    {
        if (!$this->request->isPost()) return $this->fail('请使用 POST 请求');
        try {
            return $this->data(\app\common\service\wechat\OaInvitationService::status(
                $this->userId, (string)$this->request->post('invitation', '')));
        } catch (\Throwable $e) { return $this->fail('邀请状态暂不可用，请稍后重试。'); }
    }

    /** 本人确认当前平台账号与服务号微信的绑定。 */
    public function oaInvitationConfirm()
    {
        if (!$this->request->isPost()) return $this->fail('请使用 POST 请求');
        try {
            return $this->data(\app\common\service\wechat\OaInvitationService::confirm(
                $this->userId, (string)$this->request->post('invitation', '')));
        } catch (\Throwable $e) {
            return $this->fail(get_class($e) === \RuntimeException::class
                ? $e->getMessage() : '绑定暂未完成，请刷新状态后重试。');
        }
    }

    public function oaSubscribeQrCode()
    {
        try {
            return $this->data(\app\common\service\wechat\WechatOaBindingService::getQrCode($this->userId,
                (string)$this->request->get('binding_code', '')));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }
    public function claimAdminBinding()
    {
        return $this->fail('账号关联由授权管理员统一管理');
    }
}
