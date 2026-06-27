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
                    'title' => '不是把流程走完，而是让每一段关系被看见',
                    'subtitle' => '我们为婚礼仪式、品牌庆典、企业活动与私享宴会提供主持表达和现场流程统筹。',
                    'description' => '从前期沟通、仪式脚本、音乐节点到现场控场，团队以成熟流程协调新人、家庭、场地方和执行团队，让现场节奏自然、情绪饱满、表达得体。',
                    'image' => '/resource/image/adminapi/default/banner002.png',
                    'image_alt' => '格林社品牌服务现场',
                    'caption_title' => '仪式不是流程清单',
                    'caption_text' => '而是人物关系、现场秩序与情绪峰值的共同呈现。',
                    'points' => ['需求沟通', '仪式脚本', '现场控场'],
                ],
                'styles' => self::style(820, 700),
            ],
            'pc-advantages' => [
                'id' => uniqid('pc_advantages_', true),
                'title' => '核心优势',
                'name' => 'pc-advantages',
                'content' => [
                    'enabled' => 1,
                    'eyebrow' => 'CAPABILITIES',
                    'title' => '从表达、节奏、秩序到画面统一落地',
                    'subtitle' => '适配婚礼仪式、答谢晚宴、企业庆典、品牌发布等不同场景。',
                    'data' => [
                        ['kicker' => 'Script', 'title' => '仪式文本定制', 'description' => '围绕人物关系与活动目标，打磨有分寸感的主持文本。'],
                        ['kicker' => 'Rhythm', 'title' => '全流程节奏管理', 'description' => '梳理环节、人员、物料与时间点，降低现场不确定性。'],
                        ['kicker' => 'Aesthetic', 'title' => '现场审美协同', 'description' => '让文案、音乐、影像与仪式氛围保持统一的品牌语气。'],
                    ],
                ],
                'styles' => self::style(1520, 640),
            ],
            'pc-gallery' => [
                'id' => uniqid('pc_gallery_', true),
                'title' => '展示图集',
                'name' => 'pc-gallery',
                'content' => [
                    'enabled' => 1,
                    'eyebrow' => 'SHOWCASE',
                    'title' => '真实现场中的仪式质感',
                    'subtitle' => '用于展示婚礼仪式、庆典活动、团队服务和现场统筹的专业质感。',
                    'data' => [
                        ['image' => '/resource/image/adminapi/default/banner003.png', 'alt' => '婚礼仪式现场', 'scene' => 'Wedding', 'title' => '婚礼仪式现场', 'description' => '以稳定表达承接情绪，让重要瞬间自然发生。'],
                        ['image' => '/resource/image/adminapi/default/banner001.png', 'alt' => '高端庆典现场', 'scene' => 'Event', 'title' => '高端庆典现场', 'description' => '兼顾秩序、节奏与仪式感，强化现场记忆点。'],
                        ['image' => '/resource/image/adminapi/default/banner002.png', 'alt' => '团队统筹服务', 'scene' => 'Team', 'title' => '团队统筹服务', 'description' => '提前拆解每个细节，让执行在现场更从容。'],
                    ],
                ],
                'styles' => self::style(2160, 760),
            ],
            'pc-stats' => [
                'id' => uniqid('pc_stats_', true),
                'title' => '数据背书',
                'name' => 'pc-stats',
                'content' => [
                    'enabled' => 1,
                    'eyebrow' => 'TRACK RECORD',
                    'title' => '长期服务沉淀',
                    'subtitle' => '用持续稳定的交付能力，支撑每一次重要亮相。',
                    'data' => [
                        ['value' => '1000+', 'label' => '活动服务经验', 'description' => '覆盖婚礼、庆典与商务场景'],
                        ['value' => '98%', 'label' => '客户好评率', 'description' => '来自长期合作与现场反馈'],
                        ['value' => '30+', 'label' => '覆盖城市', 'description' => '支持跨区域活动执行'],
                    ],
                ],
                'styles' => self::style(2920, 430),
            ],
            'pc-contact' => [
                'id' => uniqid('pc_contact_', true),
                'title' => '联系信息',
                'name' => 'pc-contact',
                'content' => [
                    'enabled' => 1,
                    'eyebrow' => 'CONTACT',
                    'title' => '把重要时刻交给更稳的现场团队',
                    'subtitle' => '欢迎通过电话、二维码或地址信息进一步了解团队。',
                    'phone' => '1888888888',
                    'service_time' => '周一至周日 09:30 - 19:00',
                    'address' => '请在后台装修中填写企业地址',
                    'qrcode' => '/resource/image/adminapi/default/kefu01.png',
                    'qrcode_alt' => '格林社联系二维码',
                    'remark' => '欢迎通过上述方式进一步了解团队服务与合作信息。',
                    'action_text' => '拨打电话预约',
                    'footer_slogan' => '婚礼主持 · 仪式统筹 · 活动呈现',
                ],
                'styles' => self::style(3350, 560),
            ],
            default => [
                'id' => uniqid('pc_hero_', true),
                'title' => '企业首屏',
                'name' => 'pc-hero',
                'content' => [
                    'enabled' => 1,
                    'eyebrow' => 'GLINSHE CEREMONY HOUSE',
                    'brand_name' => '格林社婚礼服务',
                    'brand_tagline' => 'Ceremony House',
                    'title' => '让婚礼现场成为值得回看的仪式',
                    'subtitle' => '以高级审美、稳健控场和细致统筹，呈现婚礼仪式与重要活动现场。',
                    'description' => 'PC 首页定位为企业展示窗口，集中呈现品牌气质、主持能力、仪式统筹、案例现场与联系信息。',
                    'image' => '/resource/image/adminapi/default/banner003.png',
                    'image_alt' => '格林社婚礼仪式现场',
                    'image_caption' => '婚礼主持 · 仪式统筹 · 活动呈现',
                    'panel_eyebrow' => 'Scene Direction',
                    'panel_description' => '从沟通、脚本、音乐节点到现场控场，保持审美和情绪在同一个节奏里。',
                    'primary_action' => '联系顾问',
                    'secondary_action' => '查看案例',
                    'badges' => ['婚礼主持', '仪式统筹', '高端庆典'],
                ],
                'styles' => self::style(0, 820),
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
