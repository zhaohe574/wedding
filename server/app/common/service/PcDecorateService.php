<?php
// +----------------------------------------------------------------------
// | PC 企业展示首页装修服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * PC 企业展示首页装修服务
 * 负责固定企业展示模块、兼容旧轮播数据，并剔除功能型跳转字段。
 */
class PcDecorateService
{
    public const PAGE_ID = 4;
    public const PAGE_TYPE = 4;

    private const WIDGET_ORDER = [
        'pc-hero',
        'pc-about',
        'pc-advantages',
        'pc-gallery',
        'pc-stats',
        'pc-contact',
    ];

    /**
     * @notes 默认页面
     * @return array
     */
    public static function defaultPage(): array
    {
        return [
            'id' => self::PAGE_ID,
            'type' => self::PAGE_TYPE,
            'name' => 'PC设置',
            'data' => json_encode(self::defaultWidgets(), JSON_UNESCAPED_UNICODE),
            'meta' => '',
        ];
    }

    /**
     * @notes 归一化页面数据
     * @param array $pageData
     * @return array
     */
    public static function normalizePage(array $pageData): array
    {
        if (empty($pageData)) {
            return self::defaultPage();
        }

        $pageData['id'] = (int)($pageData['id'] ?? self::PAGE_ID);
        $pageData['type'] = self::PAGE_TYPE;
        $pageData['name'] = $pageData['name'] ?? 'PC设置';
        $pageData['meta'] = $pageData['meta'] ?? '';
        $pageData['data'] = self::normalizeData($pageData['data'] ?? []);

        return $pageData;
    }

    /**
     * @notes 保存前归一化装修数据
     * @param mixed $data
     * @return string
     */
    public static function normalizeDataForSave($data): string
    {
        return json_encode(self::normalizeWidgetList($data), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @notes 构建默认组件
     * @return array
     */
    public static function defaultWidgets(): array
    {
        return array_map(
            fn (string $widgetName): array => self::buildDefaultWidget($widgetName),
            self::WIDGET_ORDER
        );
    }

    /**
     * @notes 归一化装修数据，保持原始字符串/数组形态
     * @param mixed $data
     * @return array|string
     */
    private static function normalizeData($data)
    {
        $isJsonString = is_string($data);
        $normalizedData = self::normalizeWidgetList($data);

        if ($isJsonString) {
            return json_encode($normalizedData, JSON_UNESCAPED_UNICODE);
        }

        return $normalizedData;
    }

    /**
     * @notes 归一化组件列表
     * @param mixed $data
     * @return array
     */
    private static function normalizeWidgetList($data): array
    {
        if (is_string($data)) {
            $decodedData = json_decode($data, true);
            $data = is_array($decodedData) ? $decodedData : [];
        }

        if (!is_array($data)) {
            $data = [];
        }

        $oldBanner = self::findOldPcBanner($data);
        $widgetMap = [];
        foreach ($data as $widget) {
            if (!is_array($widget)) {
                continue;
            }

            $widgetName = (string)($widget['name'] ?? '');
            if (!in_array($widgetName, self::WIDGET_ORDER, true)) {
                continue;
            }

            if (isset($widgetMap[$widgetName])) {
                continue;
            }

            $widgetMap[$widgetName] = self::normalizeWidget($widgetName, $widget);
        }

        if (!isset($widgetMap['pc-hero']) && !empty($oldBanner)) {
            $widgetMap['pc-hero'] = self::buildHeroFromOldBanner($oldBanner);
        }

        $widgets = [];
        foreach (self::WIDGET_ORDER as $widgetName) {
            $widgets[] = $widgetMap[$widgetName] ?? self::buildDefaultWidget($widgetName);
        }

        return $widgets;
    }

    /**
     * @notes 兼容旧 PC 轮播组件
     * @param array $data
     * @return array
     */
    private static function findOldPcBanner(array $data): array
    {
        foreach ($data as $widget) {
            if (!is_array($widget) || ($widget['name'] ?? '') !== 'pc-banner') {
                continue;
            }

            return $widget;
        }

        return [];
    }

    /**
     * @notes 用旧轮播图生成企业首屏
     * @param array $oldBanner
     * @return array
     */
    private static function buildHeroFromOldBanner(array $oldBanner): array
    {
        $widget = self::buildDefaultWidget('pc-hero');
        $items = self::normalizeListLikeValue($oldBanner['content']['data'] ?? []);
        $firstItem = is_array($items[0] ?? null) ? $items[0] : [];
        if (!empty($firstItem['image'])) {
            $widget['content']['image'] = (string)$firstItem['image'];
        }

        return $widget;
    }

    /**
     * @notes 归一化单个组件
     * @param string $widgetName
     * @param array $widget
     * @return array
     */
    private static function normalizeWidget(string $widgetName, array $widget): array
    {
        $defaultWidget = self::buildDefaultWidget($widgetName);
        $widget = array_replace_recursive($defaultWidget, $widget);
        $widget['name'] = $widgetName;
        $widget['title'] = $defaultWidget['title'];
        $widget['content'] = self::normalizeContent($widgetName, $widget['content'] ?? [], $defaultWidget['content']);
        $widget['styles'] = self::normalizeStyles($widget['styles'] ?? [], $defaultWidget['styles']);
        $widget = self::removeLinkFields($widget);

        return $widget;
    }

    /**
     * @notes 归一化组件内容
     * @param string $widgetName
     * @param array $content
     * @param array $defaultContent
     * @return array
     */
    private static function normalizeContent(string $widgetName, array $content, array $defaultContent): array
    {
        $content = array_replace_recursive($defaultContent, $content);
        $content['enabled'] = (int)($content['enabled'] ?? 1) === 0 ? 0 : 1;

        if (in_array($widgetName, ['pc-advantages', 'pc-gallery', 'pc-stats'], true)) {
            $data = self::normalizeListLikeValue($content['data'] ?? []);
            $content['data'] = !empty($data) ? array_values($data) : $defaultContent['data'];
        }

        return self::removeLinkFields($content);
    }

    /**
     * @notes 归一化可视化定位样式
     * @param array $styles
     * @param array $defaultStyles
     * @return array
     */
    private static function normalizeStyles(array $styles, array $defaultStyles): array
    {
        $styles = array_replace($defaultStyles, $styles);
        $styles['position'] = 'absolute';
        $styles['left'] = self::normalizeCssSize($styles['left'] ?? $defaultStyles['left']);
        $styles['top'] = self::normalizeCssSize($styles['top'] ?? $defaultStyles['top']);
        $styles['width'] = self::normalizeCssSize($styles['width'] ?? $defaultStyles['width']);
        $styles['height'] = self::normalizeCssSize($styles['height'] ?? $defaultStyles['height']);

        return $styles;
    }

    /**
     * @notes 归一化尺寸值
     * @param mixed $value
     * @return string
     */
    private static function normalizeCssSize($value): string
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int)$value . 'px';
        }

        $value = trim((string)$value);
        return $value === '' ? '0px' : $value;
    }

    /**
     * @notes 兼容数字键对象和 JSON 字符串列表
     * @param mixed $value
     * @return array
     */
    private static function normalizeListLikeValue($value): array
    {
        if (is_string($value)) {
            $decodedValue = json_decode($value, true);
            $value = is_array($decodedValue) ? $decodedValue : [];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values($value);
    }

    /**
     * @notes 剔除功能型跳转字段
     * @param mixed $node
     * @return mixed
     */
    private static function removeLinkFields($node)
    {
        if (!is_array($node)) {
            return $node;
        }

        $result = [];
        foreach ($node as $key => $value) {
            if ($key === 'link' || $key === 'cta_link' || $key === 'more_link') {
                continue;
            }

            $result[$key] = self::removeLinkFields($value);
        }

        return $result;
    }

    /**
     * @notes 构建默认组件
     * @param string $widgetName
     * @return array
     */
    private static function buildDefaultWidget(string $widgetName): array
    {
        return match ($widgetName) {
            'pc-about' => [
                'id' => uniqid('pc_about_', true),
                'title' => '品牌介绍',
                'name' => 'pc-about',
                'content' => [
                    'enabled' => 1,
                    'eyebrow' => 'ABOUT US',
                    'title' => '以专业流程完成每一次重要表达',
                    'subtitle' => '我们为企业发布、品牌活动、礼仪庆典与高端仪式提供主持与现场统筹支持。',
                    'description' => '从前期沟通、流程梳理、主持文本到现场控场，团队用成熟方法帮助客户把重要场合表达得更清晰、更稳妥。',
                    'image' => '/resource/image/adminapi/default/banner002.png',
                    'points' => ['流程策划', '主持执行', '现场统筹'],
                ],
                'styles' => self::style(620, 560),
            ],
            'pc-advantages' => [
                'id' => uniqid('pc_advantages_', true),
                'title' => '核心优势',
                'name' => 'pc-advantages',
                'content' => [
                    'enabled' => 1,
                    'eyebrow' => 'CAPABILITIES',
                    'title' => '把控节奏、表达与现场秩序',
                    'subtitle' => '适配企业展示、发布会、庆典仪式、商务活动等不同场景。',
                    'data' => [
                        ['title' => '表达策略', 'description' => '先明确活动目标，再拆解台词、流程与现场节奏。'],
                        ['title' => '流程统筹', 'description' => '对接人员、环节、物料与时间点，减少现场不确定性。'],
                        ['title' => '审美统一', 'description' => '文案、画面、音乐与仪式感保持同一品牌语气。'],
                    ],
                ],
                'styles' => self::style(1180, 430),
            ],
            'pc-gallery' => [
                'id' => uniqid('pc_gallery_', true),
                'title' => '展示图集',
                'name' => 'pc-gallery',
                'content' => [
                    'enabled' => 1,
                    'eyebrow' => 'SHOWCASE',
                    'title' => '真实场景中的专业呈现',
                    'subtitle' => '用于展示企业活动、仪式现场、团队环境与服务质感。',
                    'data' => [
                        ['image' => '/resource/image/adminapi/default/banner003.png', 'title' => '企业发布现场', 'description' => '稳定推进流程，强化品牌表达。'],
                        ['image' => '/resource/image/adminapi/default/banner001.png', 'title' => '庆典仪式现场', 'description' => '兼顾秩序、情绪与仪式感。'],
                        ['image' => '/resource/image/adminapi/default/banner002.png', 'title' => '团队服务场景', 'description' => '让细节在现场自然发生。'],
                    ],
                ],
                'styles' => self::style(1610, 600),
            ],
            'pc-stats' => [
                'id' => uniqid('pc_stats_', true),
                'title' => '数据背书',
                'name' => 'pc-stats',
                'content' => [
                    'enabled' => 1,
                    'eyebrow' => 'TRACK RECORD',
                    'title' => '长期服务沉淀',
                    'subtitle' => '用持续稳定的交付能力支撑每一次公开亮相。',
                    'data' => [
                        ['value' => '1000+', 'label' => '活动服务经验', 'description' => '覆盖仪式、发布与商务场景'],
                        ['value' => '98%', 'label' => '客户好评率', 'description' => '来自长期合作与现场反馈'],
                        ['value' => '30+', 'label' => '覆盖城市', 'description' => '支持跨区域活动执行'],
                    ],
                ],
                'styles' => self::style(2210, 320),
            ],
            'pc-contact' => [
                'id' => uniqid('pc_contact_', true),
                'title' => '联系信息',
                'name' => 'pc-contact',
                'content' => [
                    'enabled' => 1,
                    'eyebrow' => 'CONTACT',
                    'title' => '让重要场合被清晰表达',
                    'subtitle' => '欢迎通过电话、二维码或地址信息进一步了解团队。',
                    'phone' => '1888888888',
                    'service_time' => '周一至周日 09:30 - 19:00',
                    'address' => '请在后台装修中填写企业地址',
                    'qrcode' => '/resource/image/adminapi/default/kefu01.png',
                    'remark' => '欢迎通过上述方式进一步了解团队服务与合作信息。',
                ],
                'styles' => self::style(2530, 480),
            ],
            default => [
                'id' => uniqid('pc_hero_', true),
                'title' => '企业首屏',
                'name' => 'pc-hero',
                'content' => [
                    'enabled' => 1,
                    'eyebrow' => 'PROFESSIONAL EVENT HOSTING',
                    'title' => '专业主持与企业活动表达服务',
                    'subtitle' => '以稳健控场、清晰表达和高级审美，服务每一次重要亮相。',
                    'description' => 'PC 首页定位为企业展示窗口，集中呈现团队能力、服务场景与联系方式。',
                    'image' => '/resource/image/adminapi/default/banner003.png',
                    'image_caption' => '企业活动 · 仪式表达 · 现场统筹',
                    'badges' => ['企业活动', '品牌发布', '礼仪庆典'],
                ],
                'styles' => self::style(0, 620),
            ],
        };
    }

    /**
     * @notes 默认可视化定位样式
     * @param int $top
     * @param int $height
     * @return array
     */
    private static function style(int $top, int $height): array
    {
        return [
            'position' => 'absolute',
            'left' => '0px',
            'top' => $top . 'px',
            'width' => '1200px',
            'height' => $height . 'px',
        ];
    }
}
