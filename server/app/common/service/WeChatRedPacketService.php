<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 微信商家红包服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\user\UserTerminalEnum;
use app\common\model\financial\StaffSettlementRedPacket;
use app\common\service\wechat\WeChatConfigService;

/**
 * 微信商家红包 V2 接口封装
 * Class WeChatRedPacketService
 * @package app\common\service
 */
class WeChatRedPacketService
{
    private const SEND_MINI_PROGRAM_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendminiprogramhb';
    private const QUERY_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo';

    /**
     * @notes 获取红包配置
     */
    public static function getConfig(): array
    {
        $config = ConfigService::get('staff_settlement_red_packet') ?: [];
        if (!is_array($config)) {
            $config = [];
        }

        return [
            'enabled' => (int)($config['enabled'] ?? ConfigService::get('staff_settlement_red_packet', 'enabled', 0)),
            'auto_send' => (int)($config['auto_send'] ?? ConfigService::get('staff_settlement_red_packet', 'auto_send', 1)),
            'send_name' => (string)($config['send_name'] ?? ConfigService::get('staff_settlement_red_packet', 'send_name', '服务结算')),
            'wishing' => (string)($config['wishing'] ?? ConfigService::get('staff_settlement_red_packet', 'wishing', '感谢你的专业服务')),
            'act_name' => (string)($config['act_name'] ?? ConfigService::get('staff_settlement_red_packet', 'act_name', '服务人员结算')),
            'remark' => (string)($config['remark'] ?? ConfigService::get('staff_settlement_red_packet', 'remark', '服务人员结算红包')),
            'scene_id' => (string)($config['scene_id'] ?? ConfigService::get('staff_settlement_red_packet', 'scene_id', 'PRODUCT_5')),
            'notify_way' => (string)($config['notify_way'] ?? ConfigService::get('staff_settlement_red_packet', 'notify_way', 'MINI_PROGRAM_JSAPI')),
            'max_amount' => round((float)($config['max_amount'] ?? ConfigService::get('staff_settlement_red_packet', 'max_amount', 200)), 2),
            'min_amount' => round((float)($config['min_amount'] ?? ConfigService::get('staff_settlement_red_packet', 'min_amount', 1)), 2),
        ];
    }

    /**
     * @notes 保存红包配置
     */
    public static function saveConfig(array $params): array
    {
        $config = self::getConfig();
        $fields = ['enabled', 'auto_send', 'send_name', 'wishing', 'act_name', 'remark', 'scene_id', 'notify_way', 'max_amount', 'min_amount'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                $config[$field] = $params[$field];
            }
        }

        $config['enabled'] = (int)($config['enabled'] ?? 0) === 1 ? 1 : 0;
        $config['auto_send'] = (int)($config['auto_send'] ?? 1) === 1 ? 1 : 0;
        $config['max_amount'] = max(round((float)($config['max_amount'] ?? 200), 2), 1);
        $config['min_amount'] = max(round((float)($config['min_amount'] ?? 1), 2), 0.01);
        if ($config['min_amount'] > $config['max_amount']) {
            $config['min_amount'] = $config['max_amount'];
        }

        foreach ($fields as $field) {
            ConfigService::set('staff_settlement_red_packet', $field, $config[$field]);
        }

        return $config;
    }

    /**
     * @notes 是否启用微信红包结算
     */
    public static function isEnabled(): bool
    {
        return (int)self::getConfig()['enabled'] === 1;
    }

    /**
     * @notes 发放小程序红包
     */
    public function sendMiniProgramRedPacket(StaffSettlementRedPacket $packet): array
    {
        $wechatConfig = $this->getWechatConfig();
        $redPacketConfig = self::getConfig();

        $payload = [
            'nonce_str' => $this->nonceStr(),
            'mch_billno' => (string)$packet->mch_billno,
            'mch_id' => (string)$wechatConfig['mch_id'],
            'wxappid' => (string)$wechatConfig['app_id'],
            'send_name' => (string)$packet->send_name,
            're_openid' => (string)$packet->openid,
            'total_amount' => (int)$packet->amount_fen,
            'total_num' => (int)$packet->total_num,
            'wishing' => (string)$packet->wishing,
            'act_name' => (string)$packet->act_name,
            'remark' => (string)$packet->remark,
            'notify_way' => (string)$redPacketConfig['notify_way'],
        ];

        $sceneId = trim((string)$packet->scene_id);
        if ($sceneId !== '') {
            $payload['scene_id'] = $sceneId;
        }

        $payload['sign'] = $this->sign($payload, (string)$wechatConfig['secret_key']);
        $packet->markSending($this->maskPayload($payload));

        $response = $this->postXml(self::SEND_MINI_PROGRAM_URL, $payload, $wechatConfig);
        $success = ($response['return_code'] ?? '') === 'SUCCESS'
            && ($response['result_code'] ?? '') === 'SUCCESS';

        if (!$success) {
            $reason = (string)($response['err_code_des'] ?? $response['return_msg'] ?? '微信红包发放失败');
            return [
                'success' => false,
                'retry_same_billno' => in_array((string)($response['err_code'] ?? ''), ['SYSTEMERROR', 'PROCESSING'], true),
                'message' => $reason,
                'response' => $response,
            ];
        }

        return [
            'success' => true,
            'message' => '红包已发放，待服务人员领取',
            'response' => [
                'send_listid' => (string)($response['send_listid'] ?? ''),
                'package' => (string)($response['package'] ?? ''),
                'status' => (string)($response['status'] ?? 'SENT'),
                'raw' => $response,
            ],
        ];
    }

    /**
     * @notes 查询红包状态
     */
    public function queryRedPacket(StaffSettlementRedPacket $packet): array
    {
        $wechatConfig = $this->getWechatConfig();
        $payload = [
            'nonce_str' => $this->nonceStr(),
            'mch_billno' => (string)$packet->mch_billno,
            'mch_id' => (string)$wechatConfig['mch_id'],
            'appid' => (string)$wechatConfig['app_id'],
            'bill_type' => 'MCHT',
        ];
        $payload['sign'] = $this->sign($payload, (string)$wechatConfig['secret_key']);

        $response = $this->postXml(self::QUERY_URL, $payload, $wechatConfig);
        $success = ($response['return_code'] ?? '') === 'SUCCESS'
            && ($response['result_code'] ?? '') === 'SUCCESS';

        if (!$success) {
            return [
                'success' => false,
                'message' => (string)($response['err_code_des'] ?? $response['return_msg'] ?? '微信红包查询失败'),
                'response' => $response,
            ];
        }

        return [
            'success' => true,
            'message' => '查询成功',
            'response' => $response,
            'status' => (string)($response['status'] ?? ''),
        ];
    }

    /**
     * @notes 获取并校验微信配置
     */
    protected function getWechatConfig(): array
    {
        $payConfig = WeChatConfigService::getPayConfigByTerminal(UserTerminalEnum::WECHAT_MMP);
        $mnpConfig = WeChatConfigService::getMnpConfig();
        $mchId = trim((string)($payConfig['mch_id'] ?? ''));
        $secretKey = trim((string)($payConfig['secret_key'] ?? ''));
        $appId = trim((string)($mnpConfig['app_id'] ?? ''));
        $certPath = (string)($payConfig['certificate'] ?? '');
        $keyPath = (string)($payConfig['private_key'] ?? '');

        if ($mchId === '' || $secretKey === '' || $appId === '') {
            throw new \RuntimeException('微信支付商户号、API密钥或小程序AppID未配置');
        }
        if ($certPath === '' || !is_file($certPath) || filesize($certPath) <= 0) {
            throw new \RuntimeException('微信支付API证书未配置或证书文件不可用');
        }
        if ($keyPath === '' || !is_file($keyPath) || filesize($keyPath) <= 0) {
            throw new \RuntimeException('微信支付API私钥未配置或私钥文件不可用');
        }

        return [
            'mch_id' => $mchId,
            'secret_key' => $secretKey,
            'app_id' => $appId,
            'certificate' => $certPath,
            'private_key' => $keyPath,
        ];
    }

    /**
     * @notes XML POST
     */
    protected function postXml(string $url, array $payload, array $wechatConfig): array
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('cURL初始化失败');
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $this->toXml($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSLCERT => $wechatConfig['certificate'],
            CURLOPT_SSLKEY => $wechatConfig['private_key'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml'],
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($body === false || $errno) {
            throw new \RuntimeException('微信红包请求失败：' . ($error ?: 'cURL错误' . $errno));
        }

        return $this->fromXml((string)$body);
    }

    /**
     * @notes 签名
     */
    protected function sign(array $payload, string $secretKey): string
    {
        ksort($payload);
        $parts = [];
        foreach ($payload as $key => $value) {
            if ($key === 'sign' || $value === '' || $value === null) {
                continue;
            }
            $parts[] = $key . '=' . $value;
        }
        $parts[] = 'key=' . $secretKey;
        return strtoupper(md5(implode('&', $parts)));
    }

    /**
     * @notes 数组转XML
     */
    protected function toXml(array $payload): string
    {
        $xml = '<xml>';
        foreach ($payload as $key => $value) {
            $value = (string)$value;
            if (is_numeric($value)) {
                $xml .= sprintf('<%s>%s</%s>', $key, $value, $key);
            } else {
                $xml .= sprintf('<%s><![CDATA[%s]]></%s>', $key, $value, $key);
            }
        }
        return $xml . '</xml>';
    }

    /**
     * @notes XML转数组
     */
    protected function fromXml(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $backup = PHP_VERSION_ID < 80000 ? libxml_disable_entity_loader(true) : false;
        $object = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader($backup);
        }

        if ($object === false) {
            return ['return_code' => 'FAIL', 'return_msg' => '微信返回XML解析失败', 'raw' => $xml];
        }

        return json_decode(json_encode($object, JSON_UNESCAPED_UNICODE), true) ?: [];
    }

    /**
     * @notes 生成随机串
     */
    protected function nonceStr(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @notes 脱敏请求参数
     */
    protected function maskPayload(array $payload): array
    {
        if (isset($payload['sign'])) {
            $payload['sign'] = '***';
        }
        if (isset($payload['re_openid'])) {
            $payload['re_openid'] = $this->maskOpenid((string)$payload['re_openid']);
        }
        return $payload;
    }

    /**
     * @notes 脱敏openid
     */
    protected function maskOpenid(string $openid): string
    {
        if (strlen($openid) <= 8) {
            return '***';
        }
        return substr($openid, 0, 4) . '***' . substr($openid, -4);
    }
}
