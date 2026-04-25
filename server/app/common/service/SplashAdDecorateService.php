<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 开屏广告装修默认数据与归一化
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 开屏广告装修页数据契约。
 */
class SplashAdDecorateService
{
    public const PAGE_ID = 6;
    public const PAGE_TYPE = 6;
    public const PAGE_NAME = '开屏广告页';

    private const WIDGET_NAME = 'splash-ad';
    private const FREQUENCIES = ['session', 'daily', 'every_time', 'first_visit'];

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
                'auto_enter_enabled' => 1,
                'auto_seconds' => 3,
                'frequency' => 'session',
                'button_text' => '点击进入',
            ],
            'styles' => [
                'button_bg_color' => '#FFFFFF',
                'button_text_color' => '#333333',
                'button_border_color' => '#FFFFFF',
                'button_border_radius' => 24,
            ],
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
        $pageData['name'] = (string)($pageData['name'] ?: self::PAGE_NAME);
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
        $styles = is_array($widget['styles'] ?? null) ? $widget['styles'] : [];

        $enabled = (int)($content['enabled'] ?? 0) === 1 ? 1 : 0;
        $autoEnter = (int)($content['auto_enter_enabled'] ?? $content['auto_enter'] ?? 1) === 1 ? 1 : 0;
        $autoSeconds = max(1, min(10, (int)($content['auto_seconds'] ?? 3)));
        $frequency = (string)($content['frequency'] ?? 'session');
        if (!in_array($frequency, self::FREQUENCIES, true)) {
            $frequency = 'session';
        }

        $widget['id'] = (string)($widget['id'] ?? 'splash_ad_default') ?: 'splash_ad_default';
        $widget['title'] = self::PAGE_NAME;
        $widget['name'] = self::WIDGET_NAME;
        $widget['disabled'] = 1;
        $widget['content'] = [
            'enabled' => $enabled,
            'image' => (string)($content['image'] ?? ''),
            'auto_enter_enabled' => $autoEnter,
            'auto_seconds' => $autoSeconds,
            'frequency' => $frequency,
            'button_text' => (string)($content['button_text'] ?? '点击进入') ?: '点击进入',
        ];
        $widget['styles'] = [
            'button_bg_color' => self::normalizeColor($styles['button_bg_color'] ?? '#FFFFFF', '#FFFFFF'),
            'button_text_color' => self::normalizeColor($styles['button_text_color'] ?? '#333333', '#333333'),
            'button_border_color' => self::normalizeColor($styles['button_border_color'] ?? '#FFFFFF', '#FFFFFF'),
            'button_border_radius' => max(0, min(60, (int)($styles['button_border_radius'] ?? 24))),
        ];

        return $widget;
    }

    /**
     * @param mixed $value
     * @param string $fallback
     * @return string
     */
    private static function normalizeColor($value, string $fallback): string
    {
        $value = (string)$value;
        return preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $value) ? $value : $fallback;
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
