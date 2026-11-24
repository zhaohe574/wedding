<?php
declare(strict_types=1);

namespace app\common\service;

/**
 * 小程序页面跳转路径映射与规范化服务
 *
 * 将历史 uniapp（packages/pages/*）路由统一转换为 newuniapp 分包路由，
 * 供服务号模板消息、小程序订阅消息、站内信、支付回调与后台保存逻辑共用。
 */
class MiniappPagePathService
{
    /**
     * 历史路径 -> newuniapp 路径映射表（均不含前导斜杠）
     */
    public const LEGACY_TO_CURRENT = [
        'packages/pages/order_detail/order_detail' => 'packages/pkg-order/pages/detail/detail',
        'packages/pages/staff_order_detail/staff_order_detail' => 'packages/pkg-staff/pages/order-detail/order-detail',
        'packages/pages/waitlist/waitlist' => 'packages/pkg-order/pages/waitlist/waitlist',
        'packages/pages/waitlist/list' => 'packages/pkg-order/pages/waitlist/waitlist',
        'packages/pages/order_change/change_detail' => 'packages/pkg-service/pages/change/detail',
        'packages/pages/order_change/pause_detail' => 'packages/pkg-service/pages/change/detail?type=pause',
        'packages/pages/aftersale/ticket_detail' => 'packages/pkg-service/pages/aftersale/ticket-detail',
        'packages/pages/couple_questionnaire/detail' => 'packages/pkg-service/pages/questionnaire/detail',
        'packages/pages/activity_registration/detail' => 'packages/pkg-user/pages/activity/activity',
        'packages/pages/dynamic_detail/dynamic_detail' => 'packages/pkg-catalog/pages/feed-detail/feed-detail',
        'packages/pages/staff_settlement/staff_settlement' => 'packages/pkg-staff/pages/settlement-list/settlement-list',
        'packages/pages/notification/index' => 'pages/message/message',
        'pages/notification/index' => 'pages/message/message',
    ];

    /**
     * 将页面路径规范化为 newuniapp 路由（保留既有 query 参数）
     */
    public static function normalize(string $page, bool $leadingSlash = false): string
    {
        $trimmed = trim($page);
        if ($trimmed === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $trimmed)) {
            return $trimmed;
        }

        [$rawRoute, $rawQuery] = array_pad(explode('?', $trimmed, 2), 2, '');
        $route = ltrim($rawRoute, '/');
        if ($route === '') {
            return '';
        }

        $mapped = self::LEGACY_TO_CURRENT[$route] ?? $route;
        if ($rawQuery !== '') {
            $mapped = self::mergeQuery($mapped, $rawQuery);
        }

        $normalized = ltrim($mapped, '/');
        return $leadingSlash ? '/' . $normalized : $normalized;
    }

    /**
     * 规范化路径并按需追加 id 参数（已含 id= 或 $businessId <= 0 时不追加）
     */
    public static function appendId(string $page, int $businessId, bool $leadingSlash = false): string
    {
        $normalized = self::normalize($page, $leadingSlash);
        if ($normalized === '' || $businessId <= 0) {
            return $normalized;
        }

        $query = explode('?', $normalized, 2)[1] ?? '';
        if ($query !== '' && preg_match('/(?:^|&)id=/', $query)) {
            return $normalized;
        }

        return $normalized . (str_contains($normalized, '?') ? '&' : '?') . 'id=' . $businessId;
    }

    /**
     * 生成符合微信模板/订阅消息要求的 pagepath（去前导斜杠 + 字符白名单校验）
     */
    public static function sanitizeWechatPagePath(string $page): string
    {
        $normalized = self::normalize($page, false);
        if ($normalized === '') {
            return '';
        }

        return preg_match('#^[A-Za-z0-9_\-/\?=&.%]+$#', $normalized) ? $normalized : '';
    }

    private static function mergeQuery(string $mapped, string $rawQuery): string
    {
        if (!str_contains($mapped, '?')) {
            return $mapped . '?' . $rawQuery;
        }

        [$mappedRoute, $mappedQuery] = explode('?', $mapped, 2);
        $mappedKeys = [];
        foreach (explode('&', $mappedQuery) as $pair) {
            if ($pair === '') {
                continue;
            }
            $key = explode('=', $pair, 2)[0];
            $mappedKeys[$key] = true;
        }

        $extraPairs = [];
        foreach (explode('&', $rawQuery) as $pair) {
            if ($pair === '') {
                continue;
            }
            $key = explode('=', $pair, 2)[0];
            if (!isset($mappedKeys[$key])) {
                $extraPairs[] = $pair;
            }
        }

        $mergedQuery = $mappedQuery;
        if (!empty($extraPairs)) {
            $mergedQuery .= ($mergedQuery !== '' ? '&' : '') . implode('&', $extraPairs);
        }

        return $mappedRoute . ($mergedQuery !== '' ? '?' . $mergedQuery : '');
    }
}
