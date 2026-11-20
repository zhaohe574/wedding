<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 开屏广告装修默认数据与归一化
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 开屏广告装修页数据契约（服务端镜像）。
 *
 * ⚠ 字段定义与 `shared/contracts/decorate.ts` 的 `SplashAdContent` 一一对应，
 *   改字段时两边必须一起改，否则会出现「后台能配、小程序不认」。
 *   小程序端读取的是 `GET /api/index/decorate?id=6` 的 data[0].content。
 */
class SplashAdDecorateService
{
    public const PAGE_ID = 6;
    public const PAGE_TYPE = 6;
    public const PAGE_NAME = '开屏广告页';

    public const WIDGET_NAME = 'splash-ad';

    /** 跳过方式 */
    public const SKIP_MODES = ['countdown', 'always', 'never'];

    /** 展示频次 */
    public const FREQUENCIES = ['every_time', 'session', 'daily', 'once', 'interval_days'];

    /** 历史值 → 新值，避免旧配置被归一化成默认值 */
    private const FREQUENCY_ALIASES = [
        'first_visit' => 'once',
        'every_home_entry' => 'every_time',
    ];

    /**
     * @notes 默认开屏广告装修页，默认关闭，保证未执行 SQL 时也可安全回显/访问
     * @return array
     */
    public static function defaultPage(): array
    {
        return [
            'id' => self::PAGE_ID,
            'type' => self::PAGE_TYPE,
            'name' => self::PAGE_NAME,
            'data' => [self::defaultWidget()],
            'meta' => '',
        ];
    }

    /**
     * @notes 默认开屏广告组件
     * @return array
     */
    public static function defaultWidget(): array
    {
        return [
            'id' => 'splash_ad_default',
            'title' => self::PAGE_NAME,
            'name' => self::WIDGET_NAME,
            'disabled' => 1,
            'content' => [
                'enabled' => 0,
                'image' => '',
                'logo_image' => '',
                'auto_enter_enabled' => 1,
                'auto_seconds' => 3,
                'skip_mode' => 'countdown',
                'skip_delay' => 0,
                'button_text' => '点击进入',
                'frequency' => 'session',
                'max_daily_count' => 0,
                'interval_days' => 7,
                'start_time' => '',
                'end_time' => '',
            ],
            'styles' => [],
        ];
    }

    /**
     * @notes 归一化开屏广告装修页，兼容 JSON 字符串、数组、数字键对象和异常数据
     * @param array $pageData
     * @return array
     */
    public static function normalizePage(array $pageData): array
    {
        $defaultPage = self::defaultPage();
        $pageData = array_replace($defaultPage, $pageData);

        $isJsonString = is_string($pageData['data'] ?? null);
        $data = self::decodeData($pageData['data'] ?? []);
        $widget = self::firstSplashWidget($data);

        $normalizedData = [self::normalizeWidget($widget)];
        $pageData['id'] = (int)($pageData['id'] ?? self::PAGE_ID);
        $pageData['type'] = self::PAGE_TYPE;
        $pageData['name'] = (string)(($pageData['name'] ?? '') ?: self::PAGE_NAME);
        $pageData['data'] = $isJsonString
            ? self::encodeData($normalizedData)
            : $normalizedData;
        $pageData['meta'] = $pageData['meta'] ?? '';

        return $pageData;
    }

    /**
     * @notes 归一化后转为数据库可保存的 JSON 数据
     * @param mixed $data
     * @return string
     */
    public static function normalizeDataForSave($data): string
    {
        $pageData = self::normalizePage([
            'id' => self::PAGE_ID,
            'type' => self::PAGE_TYPE,
            'name' => self::PAGE_NAME,
            'data' => $data,
            'meta' => '',
        ]);

        if (is_string($pageData['data'])) {
            return $pageData['data'];
        }

        return self::encodeData($pageData['data']);
    }

    /**
     * @notes 把主图 / 品牌标识补成绝对地址，仅用于小程序接口下发。
     *
     * 后台素材以相对 uri 入库（material-picker 带 exclude-domain），而开屏配置会被
     * 小程序缓存在本地、在 `/index/config` 之前渲染，此时前端还拿不到 domain。
     * 所以在这里一次性补全，缓存里存的也是可直接渲染的绝对地址。
     *
     * ⚠ 只给 API 用：后台详情仍返回相对路径，避免 material-picker 把绝对地址再存回库里。
     *
     * @param array $page normalizePage() 的结果（data 可能是 JSON 字符串）
     * @return array
     */
    public static function withAbsoluteMedia(array $page): array
    {
        $isJsonString = is_string($page['data'] ?? null);
        $data = self::decodeData($page['data'] ?? []);

        foreach ($data as &$widget) {
            if (!is_array($widget) || (string)($widget['name'] ?? '') !== self::WIDGET_NAME) {
                continue;
            }
            $content = isset($widget['content']) && is_array($widget['content'])
                ? $widget['content']
                : [];
            $content['image'] = self::absoluteMedia((string)($content['image'] ?? ''));
            $content['logo_image'] = self::absoluteMedia((string)($content['logo_image'] ?? ''));
            $widget['content'] = $content;
        }
        unset($widget);

        $page['data'] = $isJsonString ? self::encodeData($data) : $data;

        return $page;
    }

    /**
     * @notes 相对 uri → 绝对地址；已是 http(s) 的原样返回
     * @param string $uri
     * @return string
     */
    private static function absoluteMedia(string $uri): string
    {
        $uri = trim($uri);
        if ($uri === '') {
            return '';
        }

        return FileService::getFileUrl($uri);
    }

    /**
     * @notes 归一化单个组件内容（后台保存、接口下发共用）
     * @param mixed $content
     * @return array
     */
    public static function normalizeContent($content): array
    {
        $content = is_array($content) ? $content : [];
        $defaults = self::defaultWidget()['content'];
        $content = array_replace($defaults, $content);

        $skipMode = (string)($content['skip_mode'] ?? 'countdown');
        if (!in_array($skipMode, self::SKIP_MODES, true)) {
            $skipMode = 'countdown';
        }

        $frequency = (string)($content['frequency'] ?? 'session');
        if (isset(self::FREQUENCY_ALIASES[$frequency])) {
            $frequency = self::FREQUENCY_ALIASES[$frequency];
        }
        if (!in_array($frequency, self::FREQUENCIES, true)) {
            $frequency = 'session';
        }

        $autoSeconds = self::clampInt($content['auto_seconds'] ?? 3, 1, 10, 3);
        $skipDelay = self::clampInt($content['skip_delay'] ?? 0, 0, 10, 0);
        // 跳过按钮晚于展示时长出现等于没有，统一夹到展示时长内
        $skipDelay = min($skipDelay, $autoSeconds);

        return [
            'enabled' => (int)($content['enabled'] ?? 0) === 1 ? 1 : 0,
            'image' => (string)($content['image'] ?? ''),
            'logo_image' => (string)($content['logo_image'] ?? ($content['logoImage'] ?? ($content['logo'] ?? ''))),
            'auto_enter_enabled' => (int)($content['auto_enter_enabled'] ?? ($content['auto_enter'] ?? 1)) === 1 ? 1 : 0,
            'auto_seconds' => $autoSeconds,
            'skip_mode' => $skipMode,
            'skip_delay' => $skipDelay,
            'button_text' => (string)($content['button_text'] ?? '') ?: '点击进入',
            'frequency' => $frequency,
            'max_daily_count' => self::clampInt($content['max_daily_count'] ?? 0, 0, 99, 0),
            'interval_days' => self::clampInt($content['interval_days'] ?? 7, 1, 365, 7),
            'start_time' => trim((string)($content['start_time'] ?? '')),
            'end_time' => trim((string)($content['end_time'] ?? '')),
        ];
    }

    /**
     * @param mixed $value
     * @param int $min
     * @param int $max
     * @param int $fallback
     * @return int
     */
    private static function clampInt($value, int $min, int $max, int $fallback): int
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        $number = (int)$value;
        return max($min, min($max, $number));
    }

    /**
     * @param mixed $data
     * @return string
     */
    private static function encodeData($data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : '[]';
    }

    /**
     * @param mixed $data
     * @return array
     */
    private static function decodeData($data): array
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($data)) {
            return [];
        }

        if (self::isListLike($data)) {
            return array_values($data);
        }

        if (($data['name'] ?? '') === self::WIDGET_NAME) {
            return [$data];
        }

        return [];
    }

    /**
     * @param array $data
     * @return array
     */
    private static function firstSplashWidget(array $data): array
    {
        foreach ($data as $widget) {
            if (is_array($widget) && (string)($widget['name'] ?? '') === self::WIDGET_NAME) {
                return $widget;
            }
        }

        return [];
    }

    /**
     * @param array $widget
     * @return array
     */
    private static function normalizeWidget(array $widget): array
    {
        $defaultWidget = self::defaultWidget();
        $widget = array_replace_recursive($defaultWidget, $widget);
        $content = is_array($widget['content'] ?? null) ? $widget['content'] : [];

        $widget['id'] = (string)($widget['id'] ?? 'splash_ad_default') ?: 'splash_ad_default';
        $widget['title'] = self::PAGE_NAME;
        $widget['name'] = self::WIDGET_NAME;
        $widget['disabled'] = 1;
        $widget['content'] = self::normalizeContent($content);
        $widget['styles'] = [];

        return $widget;
    }

    /**
     * @param array $data
     * @return bool
     */
    private static function isListLike(array $data): bool
    {
        if ($data === []) {
            return true;
        }

        foreach (array_keys($data) as $key) {
            if (is_int($key) || (is_string($key) && ctype_digit($key))) {
                continue;
            }

            return false;
        }

        return true;
    }
}
