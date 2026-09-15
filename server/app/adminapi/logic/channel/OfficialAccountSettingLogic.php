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

namespace app\adminapi\logic\channel;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;
use app\common\service\FileService;

/**
 * 公众号设置逻辑
 * Class OfficialAccountSettingLogic
 * @package app\adminapi\logic\channel
 */
class OfficialAccountSettingLogic extends BaseLogic
{
    /**
     * @notes 获取公众号配置
     * @return array
     * @author ljj
     * @date 2022/2/16 10:08 上午
     */
    public function getConfig()
    {
        $domainName = request()->host(true);
        $qrCode = ConfigService::get('oa_setting', 'qr_code', '');
        $qrCode = empty($qrCode) ? $qrCode : FileService::getFileUrl($qrCode);
        $config = [
            'invitation_enabled' => (int)\app\common\service\wechat\OaInvitationService::enabled(),
            'invitation_verified' => (int)\app\common\service\wechat\OaInvitationService::configuration()['checks']['link_verified'],
            'invitation_config' => \app\common\service\wechat\OaInvitationService::configuration(),
            'name'              => ConfigService::get('oa_setting', 'name', ''),
            'account'           => ConfigService::get('oa_setting', 'account', ''),
            'original_id'       => ConfigService::get('oa_setting', 'original_id', ''),
            'qr_code'           => $qrCode,
            'app_id'            => ConfigService::get('oa_setting', 'app_id', ''),
            'app_secret'        => ConfigService::get('oa_setting', 'app_secret', ''),
            // 回调使用固定入口，避免多应用反向路由组件与框架返回类型不兼容。
            'url'               => rtrim(request()->domain(), '/') . '/adminapi/channel.official_account_reply/index',
            'token'             => ConfigService::get('oa_setting', 'token'),
            'encoding_aes_key'  => ConfigService::get('oa_setting', 'encoding_aes_key', ''),
            'encryption_type'   => ConfigService::get('oa_setting', 'encryption_type', 1),
            'business_domain'   => $domainName,
            'js_secure_domain'  => $domainName,
            'web_auth_domain'   => $domainName,
        ];
        return $config;
    }

    /**
     * @notes 设置公众号配置
     * @param $params
     * @author ljj
     * @date 2022/2/16 10:08 上午
     */
    public function setConfig($params)
    {
        $enable = array_key_exists('invitation_enabled', $params) ? (int)$params['invitation_enabled']
            : (int)\app\common\service\wechat\OaInvitationService::enabled();
        $appid = (string)(\app\common\service\wechat\WeChatConfigService::getMnpConfig()['app_id'] ?? '');
        $verified = (int)($params['invitation_verified'] ?? 0) === 1;
        if ($enable && (!$verified || !preg_match('/^wx[a-zA-Z0-9]{16}$/', $appid)
            || !preg_match('/^wx[a-zA-Z0-9]{16}$/', (string)$params['app_id']))) {
            throw new \RuntimeException('请先配置正式小程序，并确认已关联服务号、新版已发布且消息跳转已通过真机验证。');
        }
        \think\facade\Db::startTrans();
        try {
        $qrCode = isset($params['qr_code']) ? FileService::setFileUrl($params['qr_code']) : '';

        ConfigService::set('oa_setting','name', $params['name'] ?? '');
        ConfigService::set('oa_setting','account', $params['account'] ?? '');
        ConfigService::set('oa_setting','original_id', $params['original_id'] ?? '');
        ConfigService::set('oa_setting','qr_code', $qrCode);
        ConfigService::set('oa_setting','app_id',$params['app_id']);
        ConfigService::set('oa_setting','app_secret',$params['app_secret']);
        ConfigService::set('oa_setting','token',$params['token'] ?? '');
        ConfigService::set('oa_setting','encoding_aes_key',$params['encoding_aes_key'] ?? '');
        ConfigService::set('oa_setting','encryption_type',$params['encryption_type']);
        ConfigService::set('oa_setting', 'invitation_verified_apps', $verified ? $params['app_id'] . ':' . $appid : '');
        ConfigService::set('oa_setting', 'invitation_enabled', $enable);
        if ($enable) {
            \app\common\model\wechat\OaBindSession::where('source', 'miniapp')->where('admin_id', 0)->where('status', 0)
                ->update(['status' => 2, 'expires_time' => time(), 'update_time' => time()]);
        }
        \think\facade\Db::commit();
        } catch (\Throwable $e) { \think\facade\Db::rollback(); throw $e; }
    }
}
