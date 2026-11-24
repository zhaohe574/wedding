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
namespace app\common\service\wechat;


use EasyWeChat\Kernel\Exceptions\Exception;
use EasyWeChat\OfficialAccount\Application;


/**
 * 公众号相关
 * Class WeChatOaService
 * @package app\common\service\wechat
 */
class WeChatOaService
{

    protected $app;

    protected $config;


    public function __construct()
    {
        $this->config = $this->getConfig();
        $this->app = new Application($this->config);
    }


    /**
     * @notes easywechat服务端
     * @return \EasyWeChat\Kernel\Contracts\Server|\EasyWeChat\OfficialAccount\Server
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \Throwable
     * @author 段誉
     * @date 2023/2/27 14:22
     */
    public function getServer()
    {
        return $this->app->getServer();
    }

    public function getFollowerInfo(string $openid): array
    {
        return $this->app->getClient()->get('cgi-bin/user/info', ['openid' => $openid, 'lang' => 'zh_CN'])->toArray();
    }


    /**
     * @notes 配置
     * @return array
     * @throws Exception
     * @author 段誉
     * @date 2023/2/27 12:03
     */
    protected function getConfig()
    {
        $config = WeChatConfigService::getOaConfig();
        if (empty($config['app_id']) || empty($config['secret'])) {
            throw new Exception('请先设置公众号配置');
        }
        return $config;
    }




    /**
     * @notes 创建公众号菜单
     * @param array $buttons
     * @param array $matchRule
     * @return \EasyWeChat\Kernel\HttpClient\Response|\Symfony\Contracts\HttpClient\ResponseInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author 段誉
     * @date 2023/2/27 12:07
     */
    public function createMenu(array $buttons, array $matchRule = [])
    {
        if (!empty($matchRule)) {
            return $this->app->getClient()->postJson('cgi-bin/menu/addconditional', [
                'button' => $buttons,
                'matchrule' => $matchRule,
            ]);
        }

        return $this->app->getClient()->postJson('cgi-bin/menu/create', ['button' => $buttons]);
    }

    /**
     * 发送公众号模板消息。
     *
     * 公众号模板消息不消耗小程序订阅次数，是否可发送由粉丝关注状态和微信侧模板规则决定。
     */
    public function sendTemplateMessage(array $payload): array
    {
        $response = $this->app->getClient()->postJson('cgi-bin/message/template/send', $payload);
        return $response->toArray(false);
    }

    /**
     * 获取已添加至账号下所有模板列表。
     *
     * 对应微信公众号官方接口：GET /cgi-bin/template/get_all_private_template
     * 统一走 EasyWeChat 客户端；令牌失效（40001/40014/42001）时刷新后重试一次。
     */
    public function getAllPrivateTemplates(): array
    {
        $result = $this->app->getClient()->get('cgi-bin/template/get_all_private_template')->toArray(false);
        if (in_array((int)($result['errcode'] ?? 0), [40001, 40014, 42001], true)) {
            try {
                $this->refreshAccessToken();
            } catch (\Throwable $e) {
                // 刷新失败时返回原始错误结果，交由上层提示。
                return $result;
            }
            $result = $this->app->getClient()->get('cgi-bin/template/get_all_private_template')->toArray(false);
        }
        return $result;
    }


    /**
     * 强制刷新已失效的公众号 AccessToken。
     * EasyWechat 会使用其共享缓存保存令牌，本方法仅在微信返回令牌失效时调用。
     */
    public function refreshAccessToken(): void
    {
        $accessToken = $this->app->getAccessToken();
        if (method_exists($accessToken, 'refresh')) {
            $accessToken->refresh();
        }
    }

    /**
     * 创建带绑定场景值的临时二维码。
     */
    public function createTemporaryQrCode(string $sceneValue, int $expireSeconds = 900): array
    {
        $sceneValue = trim($sceneValue);
        if ($sceneValue === '') {
            throw new Exception('公众号绑定场景值不能为空');
        }

        $response = $this->app->getClient()->postJson('cgi-bin/qrcode/create', [
            'expire_seconds' => max(60, min($expireSeconds, 2592000)),
            'action_name' => 'QR_STR_SCENE',
            'action_info' => [
                'scene' => [
                    'scene_str' => $sceneValue,
                ],
            ],
        ]);

        return $response->toArray(false);
    }



}
