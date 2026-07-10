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
    private const CARD_MODE_MINI_FIRST = 'mini_first';
    private const CARD_MODE_BACKEND_ONLY = 'backend_only';
    private const SEND_CHANNEL_TEXT = 'text';
    private const SEND_CHANNEL_TEXTCARD = 'textcard';
    private const SEND_CHANNEL_TEXTCARD_FALLBACK = 'textcard_fallback';
    private const SEND_CHANNEL_MINI_PROGRAM_CARD = 'mini_program_card';

    private static string $lastError = '';
    private static string $lastSendChannel = '';

    public static function getLastError(): string
    {
        return self::$lastError;
    }

    public static function getLastSendChannel(): string
    {
        return self::$lastSendChannel;
    }

    public static function getLastSendChannelDesc(): string
    {
        return match (self::$lastSendChannel) {
            self::SEND_CHANNEL_MINI_PROGRAM_CARD => '小程序模板卡片',
            self::SEND_CHANNEL_TEXTCARD_FALLBACK => '已降级后台链接卡片',
            self::SEND_CHANNEL_TEXTCARD => '后台链接卡片',
            self::SEND_CHANNEL_TEXT => '文本消息',
            default => '',
        };
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
     * 给顾问发送内部卡片消息。
     */
    public static function sendTextCardToAdvisor(
        int|array $advisorIds,
        string $title,
        string $description,
        string $url,
        string $buttonText = '查看详情',
        array $options = []
    ): bool {
        $ids = is_array($advisorIds) ? $advisorIds : [$advisorIds];
        if (empty($ids)) {
            return false;
        }

        $userIds = SalesAdvisor::whereIn('id', array_map('intval', $ids))
            ->where('wecom_userid', '<>', '')
            ->column('wecom_userid');

        return self::sendTextCardToUsers($userIds, $title, $description, $url, $buttonText, $options);
    }

    /**
     * 给服务人员发送内部卡片消息。
     */
    public static function sendTextCardToStaff(
        int|array $staffIds,
        string $title,
        string $description,
        string $url,
        string $buttonText = '查看详情',
        array $options = []
    ): bool {
        $ids = is_array($staffIds) ? $staffIds : [$staffIds];
        if (empty($ids)) {
            return false;
        }

        $userIds = Staff::whereIn('id', array_map('intval', $ids))
            ->where('wecom_userid', '<>', '')
            ->column('wecom_userid');

        return self::sendTextCardToUsers($userIds, $title, $description, $url, $buttonText, $options);
    }

    /**
     * 组装企业微信卡片描述。
     */
    public static function buildTextCardDescription(
        string $label,
        string $headline,
        array $fields,
        string $notice = ''
    ): string {
        $lines = [];
        if (trim($label) !== '') {
            $lines[] = '<div class="gray">' . self::escapeTextCardText($label) . '</div>';
        }
        if (trim($headline) !== '') {
            $lines[] = '<div class="normal">' . self::escapeTextCardText($headline) . '</div>';
        }

        foreach ($fields as $name => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $lines[] = self::escapeTextCardText((string) $name) . '：' . self::escapeTextCardText($value);
        }

        if (trim($notice) !== '') {
            $lines[] = '<div class="highlight">' . self::escapeTextCardText($notice) . '</div>';
        }

        return implode('<br>', $lines);
    }

    /**
     * 构造后台页面跳转地址。
     */
    public static function buildBackendUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            $path = '/admin';
        }
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        $domain = self::resolveRequestDomain();
        if ($domain !== '') {
            return rtrim($domain, '/') . $path;
        }

        return $path;
    }

    /**
     * 构造企业微信模板卡片可用的小程序页面路径。
     */
    public static function buildMiniProgramPagePath(string $path, array $query = []): string
    {
        $path = self::normalizeMiniProgramPagePath($path);
        if ($path === '') {
            return '';
        }

        $params = [];
        foreach ($query as $key => $value) {
            $key = trim((string) $key);
            if ($key === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $params[$key] = (string) $value;
        }

        if (empty($params)) {
            return $path;
        }

        return $path . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * 构造企微通知小程序中转页路径。
     */
    public static function buildWecomNoticePagePath(string $scene, int $targetId = 0, array $query = []): string
    {
        $params = array_merge([
            'scene' => preg_replace('/[^a-zA-Z0-9_\\-]/', '', $scene) ?: 'notice',
            'target_id' => max(0, $targetId),
        ], $query);

        return self::buildMiniProgramPagePath('packages/pages/wecom_notice/wecom_notice', $params);
    }

    /**
     * 转义企业微信卡片文本。
     */
    public static function escapeTextCardText(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
        string $buttonText = '查看详情',
        array $options = []
    ): bool {
        return self::sendMessageToUsers(
            $userIds,
            '卡片消息',
            static fn (array $config, array $normalizedUserIds): array => self::sendCardToUsersWithConfig(
                $config,
                $normalizedUserIds,
                $title,
                $description,
                $url,
                $buttonText,
                $options
            )
        );
    }

    /**
     * 统一执行企业微信内部消息发送。
     */
    private static function sendMessageToUsers(array $userIds, string $messageType, callable $sender): bool
    {
        self::$lastError = '';
        self::$lastSendChannel = '';
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

        self::$lastSendChannel = (string)($result['_send_channel'] ?? '');
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

        return self::withSendChannel(self::httpPostJson(
            'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . $accessToken,
            $payload
        ), self::SEND_CHANNEL_TEXT);
    }

    private static function sendCardToUsersWithConfig(
        array $config,
        array $userIds,
        string $title,
        string $description,
        string $url,
        string $buttonText,
        array $options
    ): array {
        $miniPagePath = self::normalizeMiniProgramPagePath((string)($options['mini_pagepath'] ?? ''));
        if (self::shouldSendMiniProgramCard($config, $miniPagePath)) {
            $result = self::sendTemplateCardToUsersWithConfig(
                $config,
                $userIds,
                $title,
                $description,
                $miniPagePath,
                $buttonText
            );
            if ((int)($result['errcode'] ?? -1) === 0) {
                return $result;
            }

            Log::warning('企业微信内部小程序模板卡片发送失败，改用后台链接卡片：' . self::formatSendError($result));
            $fallback = self::sendTextCardToUsersWithConfig($config, $userIds, $title, $description, $url, $buttonText);
            if ((int)($fallback['errcode'] ?? -1) === 0) {
                $fallback['_send_channel'] = self::SEND_CHANNEL_TEXTCARD_FALLBACK;
            }
            return $fallback;
        }

        return self::sendTextCardToUsersWithConfig($config, $userIds, $title, $description, $url, $buttonText);
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

        return self::withSendChannel(self::httpPostJson(
            'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . $accessToken,
            $payload
        ), self::SEND_CHANNEL_TEXTCARD);
    }

    private static function sendTemplateCardToUsersWithConfig(
        array $config,
        array $userIds,
        string $title,
        string $description,
        string $pagePath,
        string $buttonText
    ): array {
        $accessToken = self::getAccessToken($config);
        if (empty($accessToken)) {
            return ['errcode' => -1, 'errmsg' => '获取 access_token 失败'];
        }

        $plainLines = self::extractPlainDescriptionLines($description);
        $mainDesc = self::limitText(implode("\n", array_slice($plainLines, 0, 3)), 128);
        if ($mainDesc === '') {
            $mainDesc = self::limitText($buttonText, 128);
        }

        $templateCard = [
            'card_type' => 'text_notice',
            'source' => [
                'desc' => '婚庆管理系统',
                'desc_color' => 0,
            ],
            'main_title' => [
                'title' => self::limitText($title, 36),
                'desc' => $mainDesc,
            ],
            'sub_title_text' => self::limitText($buttonText, 64),
            'card_action' => [
                'type' => 2,
                'appid' => (string)$config['mnp_app_id'],
                'pagepath' => $pagePath,
            ],
        ];

        $horizontalList = self::buildTemplateCardHorizontalList($plainLines);
        if (!empty($horizontalList)) {
            $templateCard['horizontal_content_list'] = $horizontalList;
        }

        $payload = [
            'touser' => implode('|', $userIds),
            'msgtype' => 'template_card',
            'agentid' => (int)$config['agent_id'],
            'template_card' => $templateCard,
            'safe' => 0,
            'enable_id_trans' => 0,
            'enable_duplicate_check' => 0,
        ];

        return self::withSendChannel(self::httpPostJson(
            'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . $accessToken,
            $payload
        ), self::SEND_CHANNEL_MINI_PROGRAM_CARD);
    }

    /**
     * 获取企业微信配置。
     */
    private static function getConfig(): array
    {
        return [
            'enabled' => (int) ConfigService::get('customer_service', 'wecom_enabled', 0) === 1,
            'corp_id' => self::resolveCorpId(),
            'secret' => trim((string) ConfigService::get('customer_service', 'wecom_secret', '')),
            'agent_id' => (int) ConfigService::get('customer_service', 'wecom_agent_id', 0),
            'card_mode' => self::normalizeCardMode(ConfigService::get('customer_service', 'wecom_card_mode', self::CARD_MODE_MINI_FIRST)),
            'mnp_app_id' => trim((string) ConfigService::get('mnp_setting', 'app_id', '')),
        ];
    }

    private static function resolveCorpId(): string
    {
        $corpId = trim((string) ConfigService::get('customer_service', 'wecom_corp_id'));
        if ($corpId !== '') {
            return $corpId;
        }

        $fallbacks = [
            config('project.customer_service.wecom_corp_id'),
            env('customer_service.wecom_corp_id', ''),
            env('wecom.corp_id', ''),
        ];

        foreach ($fallbacks as $fallback) {
            $corpId = trim((string) $fallback);
            if ($corpId !== '') {
                return $corpId;
            }
        }

        return '';
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

    private static function shouldSendMiniProgramCard(array $config, string $miniPagePath): bool
    {
        return $config['card_mode'] === self::CARD_MODE_MINI_FIRST
            && trim((string)($config['mnp_app_id'] ?? '')) !== ''
            && $miniPagePath !== '';
    }

    private static function normalizeCardMode($value): string
    {
        $value = trim((string)$value);
        return in_array($value, [self::CARD_MODE_MINI_FIRST, self::CARD_MODE_BACKEND_ONLY], true)
            ? $value
            : self::CARD_MODE_MINI_FIRST;
    }

    private static function normalizeMiniProgramPagePath(string $path): string
    {
        $path = trim($path);
        $path = preg_replace('/\\s+/', '', $path) ?: '';
        return ltrim($path, '/');
    }

    private static function extractPlainDescriptionLines(string $description): array
    {
        $text = str_replace(["<br />", "<br/>", "<br>"], "\n", $description);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lines = preg_split('/\\r\\n|\\r|\\n/', $text) ?: [];
        return array_values(array_filter(array_map(static function ($line): string {
            return trim(preg_replace('/\\s+/u', ' ', (string)$line) ?: '');
        }, $lines), static fn (string $line): bool => $line !== ''));
    }

    private static function buildTemplateCardHorizontalList(array $lines): array
    {
        $list = [];
        foreach ($lines as $line) {
            if (!preg_match('/^([^：:]{1,20})[：:](.+)$/u', $line, $matches)) {
                continue;
            }
            $key = trim((string)($matches[1] ?? ''));
            $value = trim((string)($matches[2] ?? ''));
            if ($key === '' || $value === '') {
                continue;
            }
            $list[] = [
                'keyname' => self::limitText($key, 12),
                'value' => self::limitText($value, 64),
            ];
            if (count($list) >= 6) {
                break;
            }
        }
        return $list;
    }

    private static function limitText(string $text, int $length): string
    {
        $text = trim($text);
        if ($length <= 0 || mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }
        return mb_substr($text, 0, max(0, $length - 1), 'UTF-8') . '…';
    }

    private static function withSendChannel(array $result, string $channel): array
    {
        $result['_send_channel'] = $channel;
        return $result;
    }

    /**
     * 解析当前可用域名，兼容命令行定时任务。
     */
    private static function resolveRequestDomain(): string
    {
        try {
            $domain = trim((string) request()->domain());
            if ($domain !== '') {
                return $domain;
            }
        } catch (\Throwable $e) {
        }

        $domain = self::normalizeDomain(ConfigService::get('web_page', 'page_url', ''));
        if ($domain !== '') {
            return $domain;
        }

        return '';
    }

    /**
     * 规范化域名配置。
     */
    private static function normalizeDomain($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (!str_starts_with($value, 'http://') && !str_starts_with($value, 'https://')) {
            $value = 'https://' . $value;
        }

        $parts = parse_url($value);
        if (empty($parts['host'])) {
            return '';
        }

        $domain = ($parts['scheme'] ?? 'https') . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $domain .= ':' . $parts['port'];
        }

        return $domain;
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
