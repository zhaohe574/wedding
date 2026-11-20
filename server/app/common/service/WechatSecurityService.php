<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\enum\user\UserTerminalEnum;
use app\common\model\user\User;
use app\common\model\user\UserAuth;
use app\common\service\wechat\WeChatConfigService;
use think\facade\Cache;
use think\facade\Log;
use WpOrg\Requests\Requests;

/**
 * 微信小程序安全能力服务。
 */
class WechatSecurityService
{
    public const SCENE_PROFILE = 1;
    public const SCENE_COMMENT = 2;
    // 微信 msg_sec_check 场景映射：1 资料 / 2 评论 / 3 论坛（动态） / 4 社交日志（作品）。
    // SCENE_STAFF_CERT=5 为本地自定义场景，超出微信合法域（1-4），送检时由 normalizeTextScene 归一为评论场景。
    public const SCENE_DYNAMIC = 3;
    public const SCENE_STAFF_WORK = 4;
    public const SCENE_STAFF_CERT = 5;
    public const RISK_SCENE_REGISTER = 0;
    public const RISK_SCENE_MARKETING = 1;
    public const RISK_SCENE_UGC = 2;

    // 异步媒体检测的媒体类型
    public const MEDIA_TYPE_AUDIO = 1;
    public const MEDIA_TYPE_IMAGE = 2;

    // 单次业务提交最多送检的图片数量（小程序评价上限 9 张，留一点余量）
    private const MEDIA_CHECK_MAX_IMAGES = 10;

    private const ACCESS_TOKEN_CACHE_KEY = 'wechat_mnp_access_token';
    private const API_BASE = 'https://api.weixin.qq.com';

    /**
     * 获取用户微信风险等级，并同步写回用户表。
     */
    public static function refreshUserRiskRank(int $userId, int $scene = self::RISK_SCENE_UGC): ?int
    {
        $openid = self::getMnpOpenid($userId);
        if ($openid === '') {
            Log::warning('微信用户风险等级检测跳过：用户未绑定小程序openid，user_id=' . $userId);
            return null;
        }

        $config = WeChatConfigService::getMnpConfig();
        $appId = trim((string)($config['app_id'] ?? ''));
        if ($appId === '') {
            Log::warning('微信用户风险等级检测跳过：小程序appid未配置，user_id=' . $userId);
            return null;
        }

        $user = User::where('id', $userId)->field('id,mobile')->findOrEmpty();
        $payload = [
            'appid' => $appId,
            'openid' => $openid,
            'scene' => self::normalizeRiskScene($scene),
            'client_ip' => request()->ip(),
        ];
        if (!$user->isEmpty() && trim((string)$user->mobile) !== '') {
            $payload['mobile_no'] = (string)$user->mobile;
        }

        try {
            $response = self::postJson('/wxa/getuserriskrank', $payload);
            if ((int)($response['errcode'] ?? 0) !== 0) {
                Log::warning('微信用户风险等级检测失败：user_id=' . $userId . '，errcode=' . (string)($response['errcode'] ?? '') . '，errmsg=' . (string)($response['errmsg'] ?? ''));
                return null;
            }

            $rank = self::normalizeRiskRank((int)($response['risk_rank'] ?? 0));
            User::where('id', $userId)->update([
                'wechat_risk_rank' => $rank,
                'risk_rank_update_time' => time(),
            ]);
            Log::info('微信用户风险等级检测成功：user_id=' . $userId . '，risk_rank=' . $rank . '，unoin_id=' . (string)($response['unoin_id'] ?? ''));
            return $rank;
        } catch (\Throwable $e) {
            Log::warning('微信用户风险等级检测异常：user_id=' . $userId . '，message=' . $e->getMessage());
            return null;
        }
    }

    /**
     * 文本内容安全检测。
     *
     * 失败语义是 fail-open：接口超时、access_token 获取失败、errcode 非 0、返回结构异常
     * 都会走 passResult()，即 hit=false。这是可用性优先的有意取舍 —— 微信接口抖动时
     * 不能让用户发不出评价，违规兜底依赖“命中进待审 + 人工审核”这条链路，而不是靠拒绝。
     * 调用方不得把 hit=false 当成“内容一定合规”来用。
     */
    public static function checkText(int $userId, string $content, int $scene, array $extra = []): array
    {
        $content = trim($content);
        if ($content === '') {
            return self::passResult('内容为空，跳过检测');
        }

        if ((int)ConfigService::get('feature_switch', 'wechat_text_check_enabled', 1) !== 1) {
            return self::passResult('微信文本检测未开启');
        }

        $openid = self::getMnpOpenid($userId);
        if ($openid === '') {
            Log::warning('微信文本安全检测跳过：用户未绑定小程序openid，user_id=' . $userId . '，scene=' . $scene);
            return self::passResult('用户未绑定小程序openid，跳过检测');
        }

        $payload = [
            'content' => mb_substr($content, 0, 2500, 'UTF-8'),
            'version' => 2,
            'scene' => self::normalizeTextScene($scene),
            'openid' => $openid,
        ];
        foreach (['title', 'nickname', 'signature'] as $field) {
            if (isset($extra[$field]) && trim((string)$extra[$field]) !== '') {
                $payload[$field] = mb_substr(trim((string)$extra[$field]), 0, 2500, 'UTF-8');
            }
        }

        try {
            $response = self::postJson('/wxa/msg_sec_check', $payload);
            if ((int)($response['errcode'] ?? 0) !== 0) {
                Log::warning('微信文本安全检测失败：user_id=' . $userId . '，scene=' . $scene . '，errcode=' . (string)($response['errcode'] ?? '') . '，errmsg=' . (string)($response['errmsg'] ?? ''));
                return self::passResult('微信文本检测失败，按通过处理', $response);
            }

            $result = self::buildCheckResult($response, self::thresholdForScene($scene));
            Log::info(
                '微信文本安全检测完成：user_id=' . $userId
                . '，scene=' . $scene
                . '，hit=' . ($result['hit'] ? '1' : '0')
                . '，suggest=' . $result['suggest']
                . '，label=' . $result['label']
                . '，prob=' . $result['prob']
                . '，trace_id=' . $result['trace_id']
            );
            return $result;
        } catch (\Throwable $e) {
            Log::warning('微信文本安全检测异常：user_id=' . $userId . '，scene=' . $scene . '，message=' . $e->getMessage());
            return self::passResult('微信文本检测异常，按通过处理');
        }
    }

    /**
     * 图片内容安全异步检测（wxa/media_check_async）。
     *
     * 微信侧是“提交 + 回调”模型：本次调用只负责把图片 URL 送检并拿到 trace_id，
     * 违规结论由微信消息推送回来（见 MediaCheckService::handleCallback）。
     *
     * 实测坑：接口文档写 media_url 是数组，但当前线上接口只接受**单个 URL 字符串**，
     * 传数组会直接返回 errcode=47001 data format error。所以这里按“一张图一次请求”发送，
     * 每张图各自拿一个 trace_id，回调也能一一对上（见 content_media_check_task，一行一张图）。
     *
     * 失败语义与 checkText 一致，fail-open：提交失败只记录日志并返回 submitted=false，
     * 不阻断用户发布。调用方必须把“没拿到结论”当成“待人工审核”，不能当成“通过”。
     *
     * @param int $userId 用户ID（用于取 openid）
     * @param array $mediaUrls 图片地址列表，非 http(s) 或空值会被过滤
     * @param int $scene 场景值，复用文本场景表：1 资料 / 2 评论 / 3 论坛 / 4 社交日志
     * @return array{submitted:bool,media_count:int,submitted_count:int,items:array,message:string}
     */
    public static function checkMediaAsync(int $userId, array $mediaUrls, int $scene = self::SCENE_COMMENT): array
    {
        $mediaUrls = self::normalizeMediaUrls($mediaUrls);
        $result = [
            'submitted' => false,
            'media_count' => count($mediaUrls),
            'submitted_count' => 0,
            'items' => [],
            'message' => '',
        ];

        if (empty($mediaUrls)) {
            $result['message'] = '无图片，跳过检测';
            return $result;
        }

        if ((int)ConfigService::get('feature_switch', 'wechat_media_check_enabled', 1) !== 1) {
            $result['message'] = '微信图片检测未开启';
            return $result;
        }

        $openid = self::getMnpOpenid($userId);
        if ($openid === '') {
            Log::warning('微信图片安全检测跳过：用户未绑定小程序openid，user_id=' . $userId . '，scene=' . $scene);
            $result['message'] = '用户未绑定小程序openid，跳过检测';
            return $result;
        }

        $scene = self::normalizeTextScene($scene);
        foreach ($mediaUrls as $mediaUrl) {
            $payload = [
                'media_url' => $mediaUrl,
                'media_type' => self::MEDIA_TYPE_IMAGE,
                'version' => 2,
                'scene' => $scene,
                'openid' => $openid,
            ];

            $item = ['media_url' => $mediaUrl, 'submitted' => false, 'trace_id' => '', 'response' => []];
            try {
                $response = self::postJson('/wxa/media_check_async', $payload);
                $item['response'] = $response;
                if ((int)($response['errcode'] ?? 0) !== 0) {
                    Log::warning('微信图片安全检测提交失败：user_id=' . $userId . '，scene=' . $scene . '，errcode=' . (string)($response['errcode'] ?? '') . '，errmsg=' . (string)($response['errmsg'] ?? ''));
                } else {
                    $item['trace_id'] = (string)($response['trace_id'] ?? '');
                    $item['submitted'] = $item['trace_id'] !== '';
                }
            } catch (\Throwable $e) {
                Log::warning('微信图片安全检测提交异常：user_id=' . $userId . '，scene=' . $scene . '，message=' . $e->getMessage());
            }

            $result['items'][] = $item;
            if ($item['submitted']) {
                $result['submitted_count']++;
            }
        }

        $result['submitted'] = $result['submitted_count'] > 0;
        $result['message'] = $result['submitted_count'] . '/' . $result['media_count'] . ' 张图片已提交微信检测';
        Log::info('微信图片安全检测提交完成：user_id=' . $userId . '，scene=' . $scene . '，' . $result['message']);

        return $result;
    }

    public static function getMnpOpenid(int $userId): string
    {
        return trim((string)UserAuth::where([
            'user_id' => $userId,
            'terminal' => UserTerminalEnum::WECHAT_MMP,
        ])->value('openid'));
    }

    public static function riskRankText(int $rank): string
    {
        return match ($rank) {
            0 => '正常',
            1 => '低风险',
            2 => '中风险',
            3 => '高风险',
            4 => '极高风险',
            default => '未知',
        };
    }

    private static function postJson(string $path, array $payload): array
    {
        $accessToken = self::getAccessToken();
        $url = self::API_BASE . $path . '?access_token=' . urlencode($accessToken);
        $response = Requests::post(
            $url,
            ['Content-Type' => 'application/json'],
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ['timeout' => 5]
        );
        $data = json_decode((string)$response->body, true);
        return is_array($data) ? $data : ['errcode' => -1, 'errmsg' => 'invalid json response'];
    }

    private static function getAccessToken(): string
    {
        $config = WeChatConfigService::getMnpConfig();
        $appId = trim((string)($config['app_id'] ?? ''));
        $secret = trim((string)($config['secret'] ?? ''));
        if ($appId === '' || $secret === '') {
            throw new \RuntimeException('请先设置小程序配置');
        }

        $cacheKey = self::ACCESS_TOKEN_CACHE_KEY . ':' . md5($appId);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $url = self::API_BASE . '/cgi-bin/token?grant_type=client_credential&appid='
            . urlencode($appId) . '&secret=' . urlencode($secret);
        $response = Requests::get($url, [], ['timeout' => 5]);
        $data = json_decode((string)$response->body, true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new \RuntimeException('获取微信access_token失败：' . (string)($data['errmsg'] ?? '未知错误'));
        }

        $ttl = max(60, (int)($data['expires_in'] ?? 7200) - 300);
        Cache::set($cacheKey, (string)$data['access_token'], $ttl);
        return (string)$data['access_token'];
    }

    private static function buildCheckResult(array $response, int $threshold): array
    {
        $result = is_array($response['result'] ?? null) ? $response['result'] : [];
        $detail = is_array($response['detail'] ?? null) ? $response['detail'] : [];
        $suggest = (string)($result['suggest'] ?? '');
        $label = (int)($result['label'] ?? 0);
        $prob = self::maxValidProb($detail);
        if ($prob < 0 && isset($result['prob'])) {
            $prob = self::normalizeProb($result['prob']);
        }

        $hit = false;
        if ($label !== 100 && $suggest !== 'pass') {
            if ($suggest === 'risky') {
                $hit = $prob < 0 || $prob >= $threshold;
            } elseif ($suggest === 'review' && (int)ConfigService::get('feature_switch', 'wechat_text_check_review_as_hit', 1) === 1) {
                $hit = $prob < 0 || $prob >= $threshold;
            }
        }

        return [
            'hit' => $hit,
            'message' => $hit ? '内容可能存在违规风险，请修改后重试' : '通过',
            'suggest' => $suggest,
            'label' => $label,
            'prob' => $prob,
            'trace_id' => (string)($response['trace_id'] ?? ''),
            'response' => $response,
        ];
    }

    private static function passResult(string $message, array $response = []): array
    {
        return [
            'hit' => false,
            'message' => $message,
            'suggest' => 'pass',
            'label' => 100,
            'prob' => -1,
            'trace_id' => (string)($response['trace_id'] ?? ''),
            'response' => $response,
        ];
    }

    private static function maxValidProb(array $detail): int
    {
        $max = -1;
        foreach ($detail as $item) {
            if (!is_array($item) || (int)($item['errcode'] ?? 0) !== 0) {
                continue;
            }
            if (!isset($item['prob'])) {
                continue;
            }
            $max = max($max, self::normalizeProb($item['prob']));
        }
        return $max;
    }

    private static function normalizeProb($prob): int
    {
        $value = (float)$prob;
        if ($value > 0 && $value <= 1) {
            $value *= 100;
        }

        return max(0, min(100, (int)round($value)));
    }

    private static function thresholdForScene(int $scene): int
    {
        $name = $scene === self::SCENE_PROFILE ? 'wechat_text_check_profile_prob' : 'wechat_text_check_comment_prob';
        return max(0, min(100, (int)ConfigService::get('feature_switch', $name, $scene === self::SCENE_PROFILE ? 80 : 70)));
    }

    private static function normalizeTextScene(int $scene): int
    {
        // 微信 msg_sec_check 场景合法值 1-4；SCENE_STAFF_CERT=5 超出微信场景域，
        // 送检时归一为评论场景（2），本地日志仍按原场景值区分。
        if ($scene === self::SCENE_STAFF_CERT) {
            return self::SCENE_COMMENT;
        }
        return in_array(
            $scene,
            [self::SCENE_PROFILE, self::SCENE_COMMENT, self::SCENE_DYNAMIC, self::SCENE_STAFF_WORK],
            true
        )
            ? $scene
            : self::SCENE_COMMENT;
    }

    /**
     * 清洗待检测的图片地址：去空、去重、只保留 http(s)，并按微信上限截断。
     *
     * @param array $mediaUrls
     * @return array
     */
    private static function normalizeMediaUrls(array $mediaUrls): array
    {
        $urls = [];
        foreach ($mediaUrls as $url) {
            $url = trim((string)$url);
            if ($url === '' || !preg_match('#^https?://#i', $url)) {
                continue;
            }
            $urls[$url] = true;
        }

        return array_slice(array_keys($urls), 0, self::MEDIA_CHECK_MAX_IMAGES);
    }

    private static function normalizeRiskScene(int $scene): int
    {
        return in_array($scene, [self::RISK_SCENE_REGISTER, self::RISK_SCENE_MARKETING, self::RISK_SCENE_UGC], true)
            ? $scene
            : self::RISK_SCENE_UGC;
    }

    private static function normalizeRiskRank(int $rank): int
    {
        return max(0, min(4, $rank));
    }
}
