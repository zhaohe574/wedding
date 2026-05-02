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
    private const TOKEN_INVALID_ERRCODES = [40001, 40014, 42001];

    private static string $lastError = '';

    public static function getLastError(): string
    {
        return self::$lastError;
    }

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
     * 给服务人员发送内部卡片消息。
     */
    public static function sendTextCardToStaff(
        int|array $staffIds,
        string $title,
        string $description,
        string $url,
        string $buttonText = '查看详情'
    ): bool {
        $ids = is_array($staffIds) ? $staffIds : [$staffIds];
        if (empty($ids)) {
            return false;
        }

        $userIds = Staff::whereIn('id', array_map('intval', $ids))
            ->where('wecom_userid', '<>', '')
            ->column('wecom_userid');

        return self::sendTextCardToUsers($userIds, $title, $description, $url, $buttonText);
    }

    /**
     * 批量发送企业微信文本消息。
     */
    public static function sendTextToUsers(array $userIds, string $content): bool
    {
        return self::sendMessageToUsers(
            $userIds,
            '文本消息',
            static fn (array $config, array $normalizedUserIds): array => self::sendTextToUsersWithConfig(
                $config,
                $normalizedUserIds,
                $content
            )
        );
    }

    /**
     * 批量发送企业微信卡片消息。
     */
    public static function sendTextCardToUsers(
        array $userIds,
        string $title,
        string $description,
        string $url,
        string $buttonText = '查看详情'
    ): bool {
        return self::sendMessageToUsers(
            $userIds,
            '卡片消息',
            static fn (array $config, array $normalizedUserIds): array => self::sendTextCardToUsersWithConfig(
                $config,
                $normalizedUserIds,
                $title,
                $description,
                $url,
                $buttonText
            )
        );
    }

    /**
     * 统一执行企业微信内部消息发送。
     */
    private static function sendMessageToUsers(array $userIds, string $messageType, callable $sender): bool
    {
        self::$lastError = '';
        $userIds = array_values(array_unique(array_filter(array_map(static fn ($item) => trim((string) $item), $userIds))));
        if (empty($userIds)) {
            self::setLastError('未找到可发送成员');
            Log::info('企业微信内部' . $messageType . '跳过：' . self::$lastError);
            return false;
        }

        $config = self::getConfig();
        if (!$config['enabled']) {
            self::setLastError('企业微信内部通知未启用');
            Log::info('企业微信内部' . $messageType . '跳过：' . self::$lastError);
            return false;
        }

        if (empty($config['corp_id']) || empty($config['secret']) || empty($config['agent_id'])) {
            self::setLastError('缺少 corp_id / secret / agent_id 配置');
            Log::info('企业微信内部' . $messageType . '跳过：' . self::$lastError);
            return false;
        }

        $result = $sender($config, $userIds);
        if (in_array((int) ($result['errcode'] ?? -1), self::TOKEN_INVALID_ERRCODES, true)) {
            Cache::delete(self::getTokenCacheKey($config));
            Log::warning('企业微信内部' . $messageType . ' token 失效，已清理缓存并重试一次：' . ($result['errmsg'] ?? ''));
            $result = $sender($config, $userIds);
        }

        if (($result['errcode'] ?? -1) !== 0) {
            $detail = self::formatSendError($result);
            self::setLastError($detail);
            Log::error('企业微信内部' . $messageType . '发送失败：' . $detail);
            return false;
        }

        Log::info('企业微信内部' . $messageType . '发送成功：touser=' . implode('|', $userIds));
        return true;
    }

    private static function sendTextToUsersWithConfig(array $config, array $userIds, string $content): array
    {
        $accessToken = self::getAccessToken($config);
        if (empty($accessToken)) {
            return ['errcode' => -1, 'errmsg' => '获取 access_token 失败'];
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

        return self::httpPostJson(
            'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . $accessToken,
            $payload
        );
    }

    private static function sendTextCardToUsersWithConfig(
        array $config,
        array $userIds,
        string $title,
        string $description,
        string $url,
        string $buttonText
    ): array {
        $accessToken = self::getAccessToken($config);
        if (empty($accessToken)) {
            return ['errcode' => -1, 'errmsg' => '获取 access_token 失败'];
        }

        $payload = [
            'touser' => implode('|', $userIds),
            'msgtype' => 'textcard',
            'agentid' => (int) $config['agent_id'],
            'textcard' => [
                'title' => $title,
                'description' => $description,
                'url' => $url,
                'btntxt' => $buttonText,
            ],
            'safe' => 0,
            'enable_id_trans' => 0,
            'enable_duplicate_check' => 0,
        ];

        return self::httpPostJson(
            'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . $accessToken,
            $payload
        );
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
        $cacheKey = self::getTokenCacheKey($config);
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

    private static function getTokenCacheKey(array $config): string
    {
        return self::TOKEN_CACHE_KEY . ':' . md5($config['corp_id'] . '|' . $config['secret']);
    }

    /**
     * GET JSON 请求。
     */
    private static function httpGetJson(string $url): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            self::setLastError('GET 请求失败：' . $error);
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            self::setLastError('POST 请求失败：' . $error);
            Log::error('企业微信 POST 请求失败：' . $error);
            return [];
        }

        return json_decode((string) $response, true) ?: [];
    }

    private static function formatSendError(array $result): string
    {
        $parts = [sprintf('errcode=%s', (string) ($result['errcode'] ?? -1))];
        $parts[] = 'errmsg=' . (string) ($result['errmsg'] ?? '未知错误');
        foreach (['invaliduser', 'invalidparty', 'invalidtag'] as $key) {
            if (!empty($result[$key])) {
                $parts[] = $key . '=' . (string) $result[$key];
            }
        }
        return implode('；', $parts);
    }

    private static function setLastError(string $error): void
    {
        self::$lastError = $error;
    }
}
