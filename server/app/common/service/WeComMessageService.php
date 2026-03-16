<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 企业微信内部消息服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

use app\common\model\crm\SalesAdvisor;
use app\common\model\staff\Staff;
use think\facade\Cache;
use think\facade\Log;

/**
 * 企业微信内部消息服务。
 * 用于顾问与服务人员的内部通知，不影响主业务流程。
 */
class WeComMessageService
{
    private const TOKEN_CACHE_KEY = 'wecom_internal_access_token';

    /**
     * 给顾问发送内部文本消息。
     */
    public static function sendToAdvisor(int|array $advisorIds, string $content): bool
    {
        $ids = is_array($advisorIds) ? $advisorIds : [$advisorIds];
        if (empty($ids)) {
            return false;
        }

        $userIds = SalesAdvisor::whereIn('id', array_map('intval', $ids))
            ->where('wecom_userid', '<>', '')
            ->column('wecom_userid');

        return self::sendTextToUsers($userIds, $content);
    }

    /**
     * 给服务人员发送内部文本消息。
     */
    public static function sendToStaff(int|array $staffIds, string $content): bool
    {
        $ids = is_array($staffIds) ? $staffIds : [$staffIds];
        if (empty($ids)) {
            return false;
        }

        $userIds = Staff::whereIn('id', array_map('intval', $ids))
            ->where('wecom_userid', '<>', '')
            ->column('wecom_userid');

        return self::sendTextToUsers($userIds, $content);
    }

    /**
     * 批量发送企业微信文本消息。
     */
    public static function sendTextToUsers(array $userIds, string $content): bool
    {
        $userIds = array_values(array_unique(array_filter(array_map(static fn ($item) => trim((string) $item), $userIds))));
        if (empty($userIds)) {
            Log::info('企业微信内部消息跳过：未找到可发送成员');
            return false;
        }

        $config = self::getConfig();
        if (!$config['enabled']) {
            Log::info('企业微信内部消息跳过：企业微信内部通知未启用');
            return false;
        }

        if (empty($config['corp_id']) || empty($config['secret']) || empty($config['agent_id'])) {
            Log::info('企业微信内部消息跳过：缺少 corp_id / secret / agent_id 配置');
            return false;
        }

        $accessToken = self::getAccessToken($config);
        if (empty($accessToken)) {
            Log::error('企业微信内部消息发送失败：获取 access_token 失败');
            return false;
        }

        $payload = [
            'touser' => implode('|', $userIds),
            'msgtype' => 'text',
            'agentid' => (int) $config['agent_id'],
            'text' => [
                'content' => $content,
            ],
            'safe' => 0,
            'enable_id_trans' => 0,
            'enable_duplicate_check' => 0,
        ];

        $result = self::httpPostJson(
            'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . $accessToken,
            $payload
        );

        if (($result['errcode'] ?? -1) !== 0) {
            Log::error('企业微信内部消息发送失败：' . ($result['errmsg'] ?? '未知错误'));
            return false;
        }

        return true;
    }

    /**
     * 获取企业微信配置。
     */
    private static function getConfig(): array
    {
        return [
            'enabled' => (int) ConfigService::get('customer_service', 'wecom_enabled', 0) === 1,
            'corp_id' => trim((string) ConfigService::get('customer_service', 'wecom_corp_id', '')),
            'secret' => trim((string) ConfigService::get('customer_service', 'wecom_secret', '')),
            'agent_id' => (int) ConfigService::get('customer_service', 'wecom_agent_id', 0),
        ];
    }

    /**
     * 获取 access_token。
     */
    private static function getAccessToken(array $config): ?string
    {
        $cacheKey = self::TOKEN_CACHE_KEY . ':' . md5($config['corp_id'] . '|' . $config['secret']);
        $token = Cache::get($cacheKey);
        if (!empty($token)) {
            return (string) $token;
        }

        $url = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid='
            . urlencode($config['corp_id'])
            . '&corpsecret='
            . urlencode($config['secret']);
        $result = self::httpGetJson($url);
        if (($result['errcode'] ?? -1) !== 0 || empty($result['access_token'])) {
            return null;
        }

        $expiresIn = max(300, (int) ($result['expires_in'] ?? 7200) - 300);
        Cache::set($cacheKey, (string) $result['access_token'], $expiresIn);
        return (string) $result['access_token'];
    }

    /**
     * GET JSON 请求。
     */
    private static function httpGetJson(string $url): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('企业微信 GET 请求失败：' . $error);
            return [];
        }

        return json_decode((string) $response, true) ?: [];
    }

    /**
     * POST JSON 请求。
     */
    private static function httpPostJson(string $url, array $data): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('企业微信 POST 请求失败：' . $error);
            return [];
        }

        return json_decode((string) $response, true) ?: [];
    }
}
