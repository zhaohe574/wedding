<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 登录页装修默认数据与归一化
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 登录页装修契约（服务端镜像）。
 *
 * ⚠ 字段定义与 `shared/contracts/decorate.ts` 的 `LoginHeroContent` 一一对应，
 *   小程序端读取 `GET /api/index/decorate?id=7` 的 data[0].content。
 */
class LoginDecorateService
{
    public const PAGE_ID = 7;
    public const PAGE_TYPE = 7;
    public const PAGE_NAME = '登录页';

    public const WIDGET_NAME = 'login-hero';

    /**
     * @notes 默认登录页装修配置，保证未执行 SQL 也可安全访问
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
     * @notes 默认登录页顶部背景组件
     * @return array
     */
    public static function defaultWidget(): array
    {
        return [
            'id' => 'login_hero_default',
            'title' => '登录页顶部背景',
            'name' => self::WIDGET_NAME,
            'disabled' => 1,
            'content' => [
                'background_image' => '',
            ],
            'styles' => [],
        ];
    }

    /**
     * @notes 归一化登录页装修数据
     * @param array $pageData
     * @return array
     */
    public static function normalizePage(array $pageData): array
    {
        $defaultPage = self::defaultPage();
        $pageData = array_replace($defaultPage, $pageData);

        $isJsonString = is_string($pageData['data'] ?? null);
        $data = self::decodeData($pageData['data'] ?? []);
        $widget = self::firstWidget($data);

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
     * @notes 归一化后转为数据库可保存的 JSON 字符串
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
     * @notes 补全前端渲染用绝对资源地址
     * @param array $pageData
     * @return array
     */
    public static function withAbsoluteMedia(array $pageData): array
    {
        $isJsonString = is_string($pageData['data'] ?? null);
        $data = self::decodeData($pageData['data'] ?? []);
        foreach ($data as &$widget) {
            if (!is_array($widget) || !is_array($widget['content'] ?? null)) {
                continue;
            }
            $bgImage = trim((string)($widget['content']['background_image'] ?? ''));
            if ($bgImage !== '' && strpos($bgImage, '/static/') !== 0) {
                $widget['content']['background_image'] = FileService::getFileUrl($bgImage);
            }
        }
        unset($widget);

        $pageData['data'] = $isJsonString ? self::encodeData($data) : $data;
        return $pageData;
    }

    /**
     * @notes 归一化组件 content，字段集合与 TS LoginHeroContent 对齐
     * @param array $content
     * @return array
     */
    public static function normalizeContent(array $content): array
    {
        $bgImage = trim((string)($content['background_image'] ?? ($content['image'] ?? '')));
        return [
            'background_image' => $bgImage,
        ];
    }

    /**
     * @param array $data
     * @return string
     */
    private static function encodeData(array $data): string
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
    private static function firstWidget(array $data): array
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

        $widget['id'] = (string)($widget['id'] ?? 'login_hero_default') ?: 'login_hero_default';
        $widget['title'] = '登录页顶部背景';
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
