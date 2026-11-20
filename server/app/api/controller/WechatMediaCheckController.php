<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 微信消息推送回调（图片内容安全）
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\common\service\ConfigService;
use app\common\service\MediaCheckService;
use think\facade\Log;
use think\response\Json;

/**
 * 微信小程序消息推送入口。
 *
 * 只处理 Event=wxa_media_check（图片/音频异步检测结果），其余事件按“收到但忽略”返回，
 * 避免把同一个推送地址配给其他业务时互相干扰。
 *
 * 需要在微信公众平台「开发管理 → 开发设置 → 消息推送」里：
 *   URL          https://<域名>/api/wechat_media_check/callback
 *   Token        与后台配置 mnp_setting.token 一致
 *   EncodingAESKey 与 mnp_setting.encoding_aes_key 一致（明文模式可留空）
 *   数据格式     JSON
 *
 * Class WechatMediaCheckController
 * @package app\api\controller
 */
class WechatMediaCheckController extends BaseApiController
{
    public array $notNeedLogin = ['callback'];

    /**
     * 微信服务器地址校验（GET）+ 事件推送（POST）。
     *
     * @return string|Json
     */
    public function callback()
    {
        $token = trim((string)ConfigService::get('mnp_setting', 'token', ''));
        if ($token === '') {
            Log::warning('微信消息推送回调被拒绝：未配置 mnp_setting.token');
            return json(['errcode' => 40001, 'errmsg' => 'token not configured']);
        }

        $signature = (string)$this->request->param('signature', '');
        $msgSignature = (string)$this->request->param('msg_signature', '');
        $timestamp = (string)$this->request->param('timestamp', '');
        $nonce = (string)$this->request->param('nonce', '');

        // 接入校验
        if ($this->request->isGet()) {
            $echostr = (string)$this->request->param('echostr', '');
            if ($echostr === '' || !$this->verifySignature($token, $timestamp, $nonce, $signature)) {
                Log::warning('微信消息推送接入校验失败：signature=' . $signature);
                return json(['errcode' => 40002, 'errmsg' => 'invalid signature']);
            }
            return $echostr;
        }

        $raw = (string)$this->request->getContent();
        $payload = $this->parsePayload($raw);

        // 安全模式：先按 msg_signature = sha1(sort(token, timestamp, nonce, encrypt)) 校验，再解密
        if (isset($payload['Encrypt'])) {
            $aesKey = trim((string)ConfigService::get('mnp_setting', 'encoding_aes_key', ''));
            if ($aesKey === '') {
                Log::warning('微信消息推送为加密模式，但未配置 mnp_setting.encoding_aes_key');
                return json(['errcode' => 40003, 'errmsg' => 'aes key not configured']);
            }
            if (!$this->verifySignature($token, $timestamp, $nonce, $msgSignature, (string)$payload['Encrypt'])) {
                Log::warning('微信消息推送签名校验失败（加密模式）');
                return json(['errcode' => 40004, 'errmsg' => 'invalid msg_signature']);
            }
            $decrypted = $this->decryptMessage($aesKey, (string)$payload['Encrypt']);
            if ($decrypted === '') {
                Log::warning('微信消息推送解密失败');
                return json(['errcode' => 40005, 'errmsg' => 'decrypt failed']);
            }
            $payload = $this->parsePayload($decrypted);
        } elseif ($signature !== '' && !$this->verifySignature($token, $timestamp, $nonce, $signature)) {
            Log::warning('微信消息推送签名校验失败（明文模式）');
            return json(['errcode' => 40006, 'errmsg' => 'invalid signature']);
        }

        $msgType = (string)($payload['MsgType'] ?? '');
        $event = (string)($payload['Event'] ?? '');
        if ($msgType === 'event' && $event === 'wxa_media_check') {
            try {
                MediaCheckService::handleCallback($payload);
            } catch (\Throwable $e) {
                Log::error('图片检测回调处理异常：' . $e->getMessage());
                // 返回失败让微信重试，回调具备幂等（定论任务不会被重复改写）
                return json(['errcode' => 500, 'errmsg' => 'handle failed']);
            }
            return 'success';
        }

        Log::info('微信消息推送收到未处理事件：' . $msgType . '/' . $event);
        return 'success';
    }

    /**
     * 签名校验：sha1(sort(token, timestamp, nonce[, encrypt])).
     */
    private function verifySignature(string $token, string $timestamp, string $nonce, string $signature, string $encrypt = ''): bool
    {
        if ($signature === '' || $timestamp === '' || $nonce === '') {
            return false;
        }

        $parts = [$token, $timestamp, $nonce];
        if ($encrypt !== '') {
            $parts[] = $encrypt;
        }
        sort($parts, SORT_STRING);

        return hash_equals(sha1(implode('', $parts)), $signature);
    }

    /**
     * 兼容 JSON / XML / form 三种推送体。
     */
    private function parsePayload(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (str_starts_with($raw, '<')) {
            $xml = @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xml !== false) {
                $data = json_decode(json_encode($xml), true);
                return is_array($data) ? $data : [];
            }
        }

        return [];
    }

    /**
     * 安全模式报文解密（AES-256-CBC，key = base64(EncodingAESKey + '=')，iv = key 前 16 字节）。
     */
    private function decryptMessage(string $aesKey, string $encrypt): string
    {
        $key = base64_decode($aesKey . '=', true);
        if ($key === false || strlen($key) !== 32) {
            return '';
        }

        $cipher = base64_decode($encrypt, true);
        if ($cipher === false || $cipher === '') {
            return '';
        }

        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, substr($key, 0, 16));
        if ($plain === false || strlen($plain) < 20) {
            return '';
        }

        // 去 PKCS#7 填充
        $pad = ord(substr($plain, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }
        $plain = substr($plain, 0, strlen($plain) - $pad);

        // 结构：16字节随机串 + 4字节长度(网络序) + 明文 + appid
        $length = unpack('N', substr($plain, 16, 4))[1] ?? 0;
        $message = substr($plain, 20, (int)$length);

        return $message === false ? '' : (string)$message;
    }
}
