<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 首页装修默认数据与归一化
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 首页装修 5 个组件的数据契约（服务端镜像）。
 *
 * ⚠ 字段定义与 `shared/contracts/decorate.ts` 的
 *   HomeHeroContent / HomeBrandContent / HomeServiceCategoriesContent /
 *   HomeFlowContent / HomePopupAdContent 一一对应，改字段时两边一起改。
 *
 * 为什么要有这个类：
 *   原来 `adminapi/logic/decorate/DecoratePageLogic.php` 与 `api/logic/IndexLogic.php`
 *   各写了一份首页默认值与归一化，字段一改就漂移。现在两边都只委托这里，
 *   归一化统一走「白名单重建」——不再用 array_replace_recursive，否则删掉的旧字段
 *   会被默认值重新带回来，后台永远删不干净。
 *
 * 消费方：
 *   - 后台详情 / 保存：DecoratePageLogic::getDetail() / save()
 *   - 小程序接口：IndexLogic::normalizeHomePageData()
 */
class HomeDecorateService
{
    public const PAGE_ID = 1;

    /** 首页组件顺序（与 shared/contracts/decorate.ts 的 DECORATE_PAGES.home.widgets 一致） */
    public const WIDGET_ORDER = [
        'banner',
        'home-brand',
        'home-service-categories',
        'home-flow',
        'home-popup-ad',
    ];

    /**
     * 组件显示名 —— 必须等于 `HOME_WIDGET_TITLES`。
     * 库里存量数据存的是旧名（首页轮播图 / 团队信息 / 服务分类），
     * normalizeWidget() 会强制覆盖，老页面一打开就是新名。
     */
    public const WIDGET_TITLES = [
        'banner' => '首屏主图',
        'home-brand' => '数据条',
        'home-service-categories' => '四宫格入口',
        'home-flow' => '服务承诺',
        'home-popup-ad' => '首页弹窗广告',
    ];

    /** 数据条固定 3 格 */
    private const BRAND_STAT_COUNT = 3;

    /** 服务承诺步骤上限 */
    private const FLOW_MAX_ITEMS = 6;

    /**
     * 首屏主图高度（rpx，750 设计宽基准），
     * 与 shared/contracts/decorate.ts 的 HOME_HERO_HEIGHT_* 一一对应。
     * 字段名用 hero_height，避免与老数据里旧版轮播图的 content.height 脏字段撞名。
     */
    private const HERO_HEIGHT_MIN = 600;
    private const HERO_HEIGHT_MAX = 1400;
    private const HERO_HEIGHT_DEFAULT = 1054;

    /**
     * @notes 构建某个组件的默认数据
     * @param string $widgetName
     * @return array
     */
    public static function buildDefaultWidget(string $widgetName): array
    {
        return match ($widgetName) {
            'banner' => self::buildDefaultBanner(),
            'home-brand' => self::buildDefaultBrand(),
            'home-service-categories' => self::buildDefaultCategories(),
            'home-flow' => self::buildDefaultFlow(),
            'home-popup-ad' => self::buildDefaultPopup(),
            default => self::buildEmptyWidget($widgetName),
        };
    }

    /**
     * @notes 归一化单个组件（白名单重建，未知字段直接丢弃）
     * @param string $widgetName
     * @param array $widget
     * @return array
     */
    public static function normalizeWidget(string $widgetName, array $widget): array
    {
        $default = self::buildDefaultWidget($widgetName);
        $content = isset($widget['content']) && is_array($widget['content'])
            ? $widget['content']
            : [];

        $id = trim((string)($widget['id'] ?? ''));
        if ($id === '') {
            $id = (string)($default['id'] ?? uniqid($widgetName . '_', true));
        }

        return [
            'id' => $id,
            'title' => self::WIDGET_TITLES[$widgetName] ?? (string)($default['title'] ?? $widgetName),
            'name' => $widgetName,
            'pageScope' => ['home'],
            'content' => self::normalizeContent($widgetName, $content),
            'styles' => [],
        ];
    }

    /**
     * @notes 首页整页数据归一化：裁掉未知组件、按固定顺序补默认值、同类型去重
     * @param mixed $data JSON 字符串或数组
     * @return mixed 与入参同类型
     */
    public static function filterPageData($data)
    {
        $isJsonString = is_string($data);
        $list = $isJsonString ? self::decodeData($data) : (is_array($data) ? array_values($data) : []);

        $byName = [];
        foreach ($list as $widget) {
            if (!is_array($widget)) {
                continue;
            }
            $name = (string)($widget['name'] ?? '');
            if (!in_array($name, self::WIDGET_ORDER, true) || isset($byName[$name])) {
                continue;
            }
            $byName[$name] = self::normalizeWidget($name, $widget);
        }

        $normalized = [];
        foreach (self::WIDGET_ORDER as $name) {
            $normalized[] = $byName[$name] ?? self::buildDefaultWidget($name);
        }

        return $isJsonString ? self::encodeData($normalized) : $normalized;
    }

    /**
     * @notes 按组件分发内容归一化
     * @param string $widgetName
     * @param array $content
     * @return array
     */
    private static function normalizeContent(string $widgetName, array $content): array
    {
        return match ($widgetName) {
            'banner' => self::normalizeHeroContent($content),
            'home-brand' => self::normalizeBrandContent($content),
            'home-service-categories' => self::normalizeCategoriesContent($content),
            'home-flow' => self::normalizeFlowContent($content),
            'home-popup-ad' => self::normalizePopupContent($content),
            default => $content,
        };
    }

    /* ------------------------------------------------------------------ *
     * 首屏主图（banner）
     * ------------------------------------------------------------------ */

    /**
     * @notes 首屏主图内容：单图，data 长度恒为 1
     * @param array $content
     * @return array
     */
    private static function normalizeHeroContent(array $content): array
    {
        $items = self::listValue($content['data'] ?? []);
        $first = $items[0] ?? [];
        if (!is_array($first)) {
            $first = [];
        }

        // 旧版「是否显示」= '0' 表示运营关掉了这张图，等价于没配自定义首屏：
        // 真机回落到内置主图 + 默认文案，与改造前视觉一致。
        if (self::flag01($first['is_show'] ?? '1') === '0') {
            $first = [];
        }

        return [
            'enabled' => self::int01($content['enabled'] ?? 1),
            'hero_height' => self::clampInt(
                $content['hero_height'] ?? null,
                self::HERO_HEIGHT_MIN,
                self::HERO_HEIGHT_MAX,
                self::HERO_HEIGHT_DEFAULT
            ),
            'data' => [self::normalizeHeroItem($first)],
        ];
    }

    /**
     * @notes 首屏主图数据项（只保留真机消费的 16 个字段）
     * @param array $item
     * @return array
     */
    private static function normalizeHeroItem(array $item): array
    {
        return [
            'image' => (string)($item['image'] ?? ''),
            'slogan' => self::limitText((string)($item['slogan'] ?? ''), 30),
            'slogan_show' => self::flag01($item['slogan_show'] ?? '1'),
            'slogan_color' => self::normalizeHexColor((string)($item['slogan_color'] ?? '')),
            'title' => self::limitText((string)($item['title'] ?? ''), 60),
            'title_show' => self::flag01($item['title_show'] ?? '1'),
            'subtitle' => self::limitText((string)($item['subtitle'] ?? ''), 40),
            'subtitle_show' => self::flag01($item['subtitle_show'] ?? '1'),
            'primary_text' => self::limitText((string)($item['primary_text'] ?? ''), 12),
            'primary_show' => self::flag01($item['primary_show'] ?? '1'),
            'primary_link' => self::normalizeLink($item['primary_link'] ?? []),
            'secondary_text' => self::limitText((string)($item['secondary_text'] ?? ''), 16),
            'secondary_show' => self::flag01($item['secondary_show'] ?? '1'),
            'secondary_link' => self::normalizeLink($item['secondary_link'] ?? []),
            'title_color' => self::normalizeHexColor((string)($item['title_color'] ?? '')),
            'subtitle_color' => self::normalizeHexColor((string)($item['subtitle_color'] ?? '')),
        ];
    }

    /**
     * @notes 默认首屏主图组件
     * @return array
     */
    private static function buildDefaultBanner(): array
    {
        return [
            'id' => uniqid('banner_', true),
            'title' => self::WIDGET_TITLES['banner'],
            'name' => 'banner',
            'pageScope' => ['home'],
            'content' => [
                'enabled' => 1,
                'hero_height' => self::HERO_HEIGHT_DEFAULT,
                'data' => [self::normalizeHeroItem([])],
            ],
            'styles' => [],
        ];
    }

    /* ------------------------------------------------------------------ *
     * 数据条（home-brand）
     * ------------------------------------------------------------------ */

    /**
     * @notes 数据条内容：只有开关 + 3 格统计，旧的 greeting / team_name / cta 已删除
     * @param array $content
     * @return array
     */
    private static function normalizeBrandContent(array $content): array
    {
        $list = self::listValue($content['stats'] ?? []);
        $stats = [];
        for ($index = 0; $index < self::BRAND_STAT_COUNT; $index++) {
            $item = $list[$index] ?? [];
            $stats[] = self::normalizeBrandStat(is_array($item) ? $item : []);
        }

        return [
            'enabled' => self::int01($content['enabled'] ?? 1),
            'stats' => $stats,
        ];
    }

    /**
     * @notes 单格统计
     * @param array $item
     * @return array
     */
    private static function normalizeBrandStat(array $item): array
    {
        return [
            'value' => self::limitText((string)($item['value'] ?? ''), 12),
            'label' => self::limitText((string)($item['label'] ?? ''), 12),
        ];
    }

    /**
     * @notes 默认数据条组件：默认留空，真机三格全空时整块不显示
     * @return array
     */
    private static function buildDefaultBrand(): array
    {
        return [
            'id' => uniqid('home_brand_', true),
            'title' => self::WIDGET_TITLES['home-brand'],
            'name' => 'home-brand',
            'pageScope' => ['home'],
            'content' => [
                'enabled' => 1,
                'stats' => array_fill(0, self::BRAND_STAT_COUNT, ['value' => '', 'label' => '']),
            ],
            'styles' => [],
        ];
    }

    /* ------------------------------------------------------------------ *
     * 四宫格入口（home-service-categories）
     * ------------------------------------------------------------------ */

    /**
     * @notes 四宫格内容：不合并默认值，运营删空就是删空
     * @param array $content
     * @return array
     */
    private static function normalizeCategoriesContent(array $content): array
    {
        $list = self::listValue($content['data'] ?? []);
        $items = [];
        foreach ($list as $item) {
            $items[] = self::normalizeCategoryItem(is_array($item) ? $item : []);
        }

        return [
            'enabled' => self::int01($content['enabled'] ?? 1),
            'data' => $items,
        ];
    }

    /**
     * @notes 单个入口：标题 / 副标题 / 图 / 版式 / 文字位置 / 文字对齐 / 跳转 / 是否显示
     * @param array $item
     * @return array
     */
    private static function normalizeCategoryItem(array $item): array
    {
        $size = (string)($item['size'] ?? '');
        $textPosition = (string)($item['text_position'] ?? '');
        $textAlign = (string)($item['text_align'] ?? '');

        return [
            'is_show' => self::flag01($item['is_show'] ?? '1'),
            'title' => self::limitText((string)($item['title'] ?? ''), 16),
            'subtitle' => self::limitText((string)($item['subtitle'] ?? ''), 24),
            'image' => (string)($item['image'] ?? ''),
            'size' => in_array($size, ['large', 'small', 'wide'], true) ? $size : 'small',
            'text_position' => in_array($textPosition, ['top', 'middle', 'bottom'], true) ? $textPosition : 'bottom',
            'text_align' => in_array($textAlign, ['left', 'center', 'right'], true) ? $textAlign : 'left',
            'link' => self::normalizeLink($item['link'] ?? []),
        ];
    }

    /**
     * @notes 默认四宫格：与小程序 QUICK_FALLBACK 同文案同路由（1 大 + 2 小 + 1 通栏）
     * @return array
     */
    private static function buildDefaultCategories(): array
    {
        return [
            'id' => uniqid('home_service_', true),
            'title' => self::WIDGET_TITLES['home-service-categories'],
            'name' => 'home-service-categories',
            'pageScope' => ['home'],
            'content' => [
                'enabled' => 1,
                'data' => [
                    self::normalizeCategoryItem([
                        'title' => '查档期',
                        'subtitle' => 'SCHEDULE QUERY',
                        'size' => 'large',
                        'text_position' => 'bottom',
                        'text_align' => 'left',
                        'link' => ['path' => '/pages/query/query'],
                    ]),
                    self::normalizeCategoryItem([
                        'title' => '选主持',
                        'subtitle' => 'WEDDING HOST',
                        'size' => 'small',
                        'text_position' => 'bottom',
                        'text_align' => 'left',
                        'link' => ['path' => '/packages/pkg-catalog/pages/staff-list/staff-list'],
                    ]),
                    self::normalizeCategoryItem([
                        'title' => '真实案例',
                        'subtitle' => 'REAL CASES',
                        'size' => 'small',
                        'text_position' => 'bottom',
                        'text_align' => 'left',
                        'link' => ['path' => '/packages/pkg-catalog/pages/case-list/case-list'],
                    ]),
                    self::normalizeCategoryItem([
                        'title' => '在线咨询',
                        'subtitle' => 'CONCIERGE SERVICE',
                        'size' => 'wide',
                        'text_position' => 'bottom',
                        'text_align' => 'left',
                        'link' => ['path' => '/pages/customer-service/customer-service'],
                    ]),
                ],
            ],
            'styles' => [],
        ];
    }

    /* ------------------------------------------------------------------ *
     * 服务承诺（home-flow）
     * ------------------------------------------------------------------ */

    /**
     * @notes 服务承诺内容（字段形状未变）
     * @param array $content
     * @return array
     */
    private static function normalizeFlowContent(array $content): array
    {
        $list = self::listValue($content['data'] ?? []);
        $items = [];
        foreach (array_slice($list, 0, self::FLOW_MAX_ITEMS) as $item) {
            if (!is_array($item)) {
                continue;
            }
            // 空标题不丢弃：前台会过滤，但配置要保留，否则运营改到一半保存就丢行
            $items[] = self::normalizeFlowItem($item);
        }

        return [
            'enabled' => self::int01($content['enabled'] ?? 1),
            'title' => self::limitText((string)($content['title'] ?? ''), 24),
            'data' => $items,
        ];
    }

    /**
     * @notes 单个承诺步骤
     * @param array $item
     * @return array
     */
    private static function normalizeFlowItem(array $item): array
    {
        return [
            'tag' => self::limitText((string)($item['tag'] ?? ''), 8),
            'title' => self::limitText((string)($item['title'] ?? ''), 32),
            'desc' => self::limitText((string)($item['desc'] ?? ''), 80),
            'is_show' => self::flag01($item['is_show'] ?? '1'),
        ];
    }

    /**
     * @notes 默认服务承诺组件
     * @return array
     */
    private static function buildDefaultFlow(): array
    {
        return [
            'id' => uniqid('home_flow_', true),
            'title' => self::WIDGET_TITLES['home-flow'],
            'name' => 'home-flow',
            'pageScope' => ['home'],
            'content' => [
                'enabled' => 1,
                'title' => '一次交付，全程在场',
                'data' => [
                    self::normalizeFlowItem([
                        'tag' => 'T-30',
                        'title' => '流程梳理与台本共创',
                        'desc' => '一对一访谈，把两个家庭的故事写成台上五分钟。',
                    ]),
                    self::normalizeFlowItem([
                        'tag' => 'T-01',
                        'title' => '全流程彩排',
                        'desc' => '主持人到场走位，音乐、灯光、动线与伴郎伴娘逐一确认。',
                    ]),
                    self::normalizeFlowItem([
                        'tag' => 'T-0',
                        'title' => '提前两小时到场',
                        'desc' => '全程在场控场，突发状况由主持人兜底，不让新人分心。',
                    ]),
                ],
            ],
            'styles' => [],
        ];
    }

    /* ------------------------------------------------------------------ *
     * 首页弹窗广告（home-popup-ad）
     * ------------------------------------------------------------------ */

    /**
     * @notes 弹窗内容（18 个字段，全部被真机消费）
     * @param array $content
     * @return array
     */
    private static function normalizePopupContent(array $content): array
    {
        $type = (string)($content['type'] ?? 'image_text');
        if (!in_array($type, ['image', 'text', 'image_text'], true)) {
            $type = 'image_text';
        }

        $frequency = (string)($content['frequency'] ?? 'daily');
        if ($frequency === 'every_time') {
            $frequency = 'every_home_entry';
        }
        if (!in_array($frequency, ['daily', 'session', 'every_home_entry', 'once', 'interval_days', 'custom_limit'], true)) {
            $frequency = 'daily';
        }

        $showTiming = (string)($content['show_timing'] ?? 'page_ready');
        if (!in_array($showTiming, ['page_ready', 'delay'], true)) {
            $showTiming = 'page_ready';
        }

        $buttonText = self::limitText((string)($content['button_text'] ?? '查看详情'), 12);

        return [
            'enabled' => self::int01($content['enabled'] ?? 0),
            'type' => $type,
            'title' => self::limitText((string)($content['title'] ?? ''), 24),
            'content' => self::limitText((string)($content['content'] ?? ''), 160),
            'image' => (string)($content['image'] ?? ''),
            'background_color' => self::normalizeHexColor((string)($content['background_color'] ?? '#FFFDF8')) ?: '#FFFDF8',
            'button_text' => $buttonText ?: '查看详情',
            'link' => self::normalizeLink($content['link'] ?? []),
            'frequency' => $frequency,
            'show_timing' => $showTiming,
            'delay_seconds' => self::clampInt($content['delay_seconds'] ?? 0, 0, 30, 0),
            'interval_days' => self::clampInt($content['interval_days'] ?? 7, 1, 365, 7),
            'max_total_count' => self::clampInt($content['max_total_count'] ?? 0, 0, 999, 0),
            'max_daily_count' => self::clampInt($content['max_daily_count'] ?? 1, 0, 99, 1),
            'start_time' => trim((string)($content['start_time'] ?? '')),
            'end_time' => trim((string)($content['end_time'] ?? '')),
            'close_counts_as_shown' => self::int01($content['close_counts_as_shown'] ?? 1),
            'show_close' => self::int01($content['show_close'] ?? 1),
        ];
    }

    /**
     * @notes 默认弹窗组件（默认关闭）
     * @return array
     */
    private static function buildDefaultPopup(): array
    {
        return [
            'id' => uniqid('home_popup_', true),
            'title' => self::WIDGET_TITLES['home-popup-ad'],
            'name' => 'home-popup-ad',
            'pageScope' => ['home'],
            'content' => self::normalizePopupContent([]),
            'styles' => [],
        ];
    }

    /* ------------------------------------------------------------------ *
     * 工具
     * ------------------------------------------------------------------ */

    /**
     * @notes 未知组件的空壳，避免 filterPageData 因拼写错误整页崩掉
     * @param string $widgetName
     * @return array
     */
    private static function buildEmptyWidget(string $widgetName): array
    {
        return [
            'id' => uniqid($widgetName . '_', true),
            'title' => $widgetName,
            'name' => $widgetName,
            'pageScope' => ['home'],
            'content' => [],
            'styles' => [],
        ];
    }

    /**
     * @notes 0/1 归一化：缺省按 1（启用），只有明确 0 才关闭
     * @param mixed $value
     * @return int
     */
    private static function int01($value): int
    {
        return (int)$value === 0 ? 0 : 1;
    }

    /**
     * @notes 字符串开关 '0'/'1'：缺省按 '1'，后台 el-switch 依赖字符串值
     * @param mixed $value
     * @return string
     */
    private static function flag01($value): string
    {
        return (string)($value ?? '1') === '0' ? '0' : '1';
    }

    /**
     * @notes 文本截断：按字符（中文不按字节）
     * @param string $value
     * @param int $length
     * @return string
     */
    private static function limitText(string $value, int $length): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length, 'UTF-8');
        }

        return substr($value, 0, $length);
    }

    /**
     * @notes 颜色：只接受 hex，空串 = 用设计稿默认色
     * @param string $value
     * @return string
     */
    private static function normalizeHexColor(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1) {
            return strtoupper($value);
        }

        if (preg_match('/^#[0-9a-fA-F]{3}$/', $value) === 1) {
            return strtoupper($value);
        }

        return '';
    }

    /**
     * @notes 链接归一化：委托 DecorateLinkService 统一映射历史路径并做参数白名单清洗
     * @param mixed $link
     * @return array
     */
    private static function normalizeLink($link): array
    {
        return DecorateLinkService::normalize($link);
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
        // null / '' / 非数值（含 bool、数组）都视为未配置，回落默认值而不是夹到边界
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $fallback;
        }

        return max($min, min($max, (int)round((float)$value)));
    }

    /**
     * @notes 兼容数字键对象 / JSON 字符串列表
     * @param mixed $value
     * @return array
     */
    private static function listValue($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values($value);
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

        if ($data === []) {
            return [];
        }

        foreach (array_keys($data) as $key) {
            if (!is_int($key) && !(is_string($key) && ctype_digit($key))) {
                // 单个组件对象（历史脏数据）：包成列表
                return (($data['name'] ?? '') !== '') ? [$data] : [];
            }
        }

        return array_values($data);
    }
}
