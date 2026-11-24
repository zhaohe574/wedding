<?php
// +----------------------------------------------------------------------
// | PC 企业展示首页装修服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * PC 企业展示首页装修服务
 * PC 端收敛为 3 个固定模块：电影感首屏 / 精选现场 / 小程序引导，
 * 聚焦「品牌展示 + 小程序引导」；不再支持可视化自由布局，
 * 且内容中不允许出现 link / cta_link / more_link 等功能型跳转字段。
 *
 * 唯一前端契约：shared/contracts/pc-decorate.ts，
 * 默认文案与旧数据迁移规则必须与其逐字保持一致，改动需双侧同步。
 */
class PcDecorateService
{
    public const PAGE_ID = 4;
    public const PAGE_TYPE = 4;

    /** PC 装修固定模块顺序 */
    private const WIDGET_ORDER = [
        'pc-hero',
        'pc-gallery',
        'pc-miniprogram',
    ];

    /** 旧版模块名：仅在归一化时作为迁移来源识别，不再作为独立模块输出 */
    private const LEGACY_WIDGET_NAMES = ['pc-banner', 'pc-about', 'pc-advantages', 'pc-stats', 'pc-contact'];

    /** 旧版 pc-contact 默认客服二维码：迁移时视为占位资源，不迁入小程序引导模块 */
    private const LEGACY_DEFAULT_CONTACT_QRCODE = '/resource/image/adminapi/default/kefu01.png';

    /** 旧版 pc-contact 默认地址占位文案：迁移时视为未填写，转为空字符串 */
    private const LEGACY_PLACEHOLDER_ADDRESS = '请在后台装修中填写企业地址';

    /** 模块标题（与契约 PC_WIDGET_META 一致） */
    private const WIDGET_TITLES = [
        'pc-hero' => '电影感首屏',
        'pc-gallery' => '精选现场',
        'pc-miniprogram' => '小程序引导',
    ];

    /** 电影感首屏默认内容（与契约 PC_HERO_DEFAULTS 逐字一致） */
    private const PC_HERO_DEFAULT_CONTENT = [
        'enabled' => 1,
        'brand_name' => '格林社',
        'brand_en' => 'GLINSHE',
        'title' => "以时间\n盛装万分之一的挚爱",
        'eyebrow' => 'BESPOKE CEREMONY & HOSTING',
        'image' => '/resource/image/adminapi/default/banner003.png',
        'image_alt' => '格林社高定婚礼仪式现场',
        'qr_caption' => '微信扫码 · 进入小程序',
    ];

    /** 精选现场默认内容（与契约 PC_GALLERY_DEFAULTS 逐字一致） */
    private const PC_GALLERY_DEFAULT_CONTENT = [
        'enabled' => 1,
        'eyebrow' => '02 / SELECTED SCENES',
        'title' => '精选现场',
        'data' => [
            ['image' => '/resource/image/adminapi/default/banner003.png', 'alt' => '三亚 · 澜景之夜婚礼仪式现场', 'scene' => 'SCENE 01', 'title' => '三亚 · 澜景之夜', 'description' => '以稳定表达承接情绪，让重要瞬间自然发生。'],
            ['image' => '/resource/image/adminapi/default/banner001.png', 'alt' => '北京 · 庭院秋叙宴会现场', 'scene' => 'SCENE 02', 'title' => '北京 · 庭院秋叙', 'description' => '兼顾宾客体验、节奏与仪式感，让聚会松弛而体面。'],
            ['image' => '/resource/image/adminapi/default/banner002.png', 'alt' => '杭州 · 静水长宴统筹现场', 'scene' => 'SCENE 03', 'title' => '杭州 · 静水长宴', 'description' => '提前拆解每个细节，让执行在现场更从容。'],
        ],
    ];

    /** 小程序引导默认内容（与契约 PC_MINIPROGRAM_DEFAULTS 逐字一致） */
    private const PC_MINIPROGRAM_DEFAULT_CONTENT = [
        'enabled' => 1,
        'title' => "将极致仪式\n收入指尖",
        'highlights' => [
            ['title' => '实时查档', 'en_label' => 'REAL-TIME CALENDAR'],
            ['title' => '严选团队', 'en_label' => 'CURATED TEAM'],
            ['title' => '全程透明', 'en_label' => 'FULL TRANSPARENCY'],
        ],
        'preview_image' => '',
        'preview_image_back' => '',
        'qrcode' => '',
        'qrcode_caption' => '扫码预约专属咨询',
        'miniapp_name' => '格林社婚礼服务',
        'phone' => '',
        'service_time' => '',
        'address' => '',
        'footer_slogan' => 'GLINSHE CEREMONY HOUSE',
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
     * @notes 归一化组件列表：永不抛异常，永远输出 3 个固定模块（按 WIDGET_ORDER 排序），
     * 旧模块（pc-banner / pc-about / pc-advantages / pc-stats / pc-contact）只作迁移来源，不再输出。
     * @param mixed $data
     * @return array
     */
    private static function normalizeWidgetList($data): array
    {
        $widgetMap = [];
        $legacyContents = [];

        foreach (self::safeParseList($data) as $item) {
            $widget = self::toContentObject($item);
            if ($widget === null) {
                continue;
            }

            $widgetName = is_string($widget['name'] ?? null) ? $widget['name'] : '';
            if (in_array($widgetName, self::WIDGET_ORDER, true)) {
                if (!isset($widgetMap[$widgetName])) {
                    $widgetMap[$widgetName] = $widget;
                }
            } elseif (in_array($widgetName, self::LEGACY_WIDGET_NAMES, true)) {
                if (!isset($legacyContents[$widgetName])) {
                    $legacyContents[$widgetName] = self::toContentObject($widget['content'] ?? null) ?? [];
                }
            }
        }

        $hasHero = isset($widgetMap['pc-hero']);
        $heroContent = self::normalizeHeroContent($widgetMap['pc-hero']['content'] ?? null);
        // 无损迁移：无 pc-hero 但存在旧 pc-banner 时，取轮播首图作为品牌首屏主视觉
        if (!$hasHero) {
            $bannerImage = self::firstBannerImage($legacyContents['pc-banner'] ?? null);
            if ($bannerImage !== '') {
                $heroContent['image'] = $bannerImage;
            }
        }

        $galleryContent = self::normalizeGalleryContent($widgetMap['pc-gallery']['content'] ?? null);

        $hasMiniProgram = isset($widgetMap['pc-miniprogram']);
        $miniProgramContent = self::normalizeMiniProgramContent(
            $widgetMap['pc-miniprogram']['content'] ?? null,
            $hasMiniProgram ? null : ($legacyContents['pc-contact'] ?? null)
        );

        $contents = [
            'pc-hero' => $heroContent,
            'pc-gallery' => $galleryContent,
            'pc-miniprogram' => $miniProgramContent,
        ];

        $widgets = [];
        foreach (self::WIDGET_ORDER as $widgetName) {
            $rawWidget = $widgetMap[$widgetName] ?? null;
            $defaultWidget = self::buildDefaultWidget($widgetName);
            $widgets[] = [
                'id' => isset($rawWidget['id']) && is_string($rawWidget['id'])
                    ? $rawWidget['id']
                    : $defaultWidget['id'],
                'title' => $defaultWidget['title'],
                'name' => $widgetName,
                'content' => self::removeLinkFields($contents[$widgetName]),
                'styles' => [],
            ];
        }

        return $widgets;
    }

    /**
     * @notes 归一化电影感首屏内容
     * @param mixed $raw
     * @return array
     */
    private static function normalizeHeroContent($raw): array
    {
        $rawObj = self::toContentObject($raw);
        $content = [
            'enabled' => self::normalizeEnabled($rawObj['enabled'] ?? null),
            'brand_name' => self::strOrDefault($rawObj, 'brand_name', self::PC_HERO_DEFAULT_CONTENT['brand_name']),
            'brand_en' => self::strOrDefault($rawObj, 'brand_en', self::PC_HERO_DEFAULT_CONTENT['brand_en']),
            'title' => self::strOrDefault($rawObj, 'title', self::PC_HERO_DEFAULT_CONTENT['title']),
            'eyebrow' => self::strOrDefault($rawObj, 'eyebrow', self::PC_HERO_DEFAULT_CONTENT['eyebrow']),
            'image' => self::strOrDefault($rawObj, 'image', self::PC_HERO_DEFAULT_CONTENT['image']),
            'image_alt' => self::strOrDefault($rawObj, 'image_alt', self::PC_HERO_DEFAULT_CONTENT['image_alt']),
            'qr_caption' => self::strOrDefault($rawObj, 'qr_caption', self::PC_HERO_DEFAULT_CONTENT['qr_caption']),
        ];

        // 兼容旧字段 brand_tagline：brand_en 缺失或非字符串，但 brand_tagline 为非空字符串时迁入
        $brandTagline = $rawObj['brand_tagline'] ?? null;
        if (!is_string($rawObj['brand_en'] ?? null) && is_string($brandTagline) && $brandTagline !== '') {
            $content['brand_en'] = $brandTagline;
        }

        return $content;
    }

    /**
     * @notes 归一化精选现场内容
     * @param mixed $raw
     * @return array
     */
    private static function normalizeGalleryContent($raw): array
    {
        $rawObj = self::toContentObject($raw);

        return [
            'enabled' => self::normalizeEnabled($rawObj['enabled'] ?? null),
            'eyebrow' => self::strOrDefault($rawObj, 'eyebrow', self::PC_GALLERY_DEFAULT_CONTENT['eyebrow']),
            'title' => self::strOrDefault($rawObj, 'title', self::PC_GALLERY_DEFAULT_CONTENT['title']),
            'data' => self::mergeListItems(
                self::safeParseList($rawObj['data'] ?? null),
                self::PC_GALLERY_DEFAULT_CONTENT['data'],
                ['image', 'alt', 'scene', 'title', 'description']
            ),
        ];
    }

    /**
     * @notes 归一化小程序引导内容
     * @param mixed $raw
     * @param mixed $legacyContactRaw 旧 pc-contact.content，仅在没有 pc-miniprogram 模块时传入
     * @return array
     */
    private static function normalizeMiniProgramContent($raw, $legacyContactRaw = null): array
    {
        $rawObj = self::toContentObject($raw);
        $content = [
            'enabled' => self::normalizeEnabled($rawObj['enabled'] ?? null),
            'title' => self::strOrDefault($rawObj, 'title', self::PC_MINIPROGRAM_DEFAULT_CONTENT['title']),
            'highlights' => self::mergeListItems(
                self::safeParseList($rawObj['highlights'] ?? null),
                self::PC_MINIPROGRAM_DEFAULT_CONTENT['highlights'],
                ['title', 'en_label']
            ),
            'preview_image' => self::strOrDefault($rawObj, 'preview_image', self::PC_MINIPROGRAM_DEFAULT_CONTENT['preview_image']),
            'preview_image_back' => self::strOrDefault($rawObj, 'preview_image_back', self::PC_MINIPROGRAM_DEFAULT_CONTENT['preview_image_back']),
            'qrcode' => self::strOrDefault($rawObj, 'qrcode', self::PC_MINIPROGRAM_DEFAULT_CONTENT['qrcode']),
            'qrcode_caption' => self::strOrDefault($rawObj, 'qrcode_caption', self::PC_MINIPROGRAM_DEFAULT_CONTENT['qrcode_caption']),
            'miniapp_name' => self::strOrDefault($rawObj, 'miniapp_name', self::PC_MINIPROGRAM_DEFAULT_CONTENT['miniapp_name']),
            'phone' => self::strOrDefault($rawObj, 'phone', self::PC_MINIPROGRAM_DEFAULT_CONTENT['phone']),
            'service_time' => self::strOrDefault($rawObj, 'service_time', self::PC_MINIPROGRAM_DEFAULT_CONTENT['service_time']),
            'address' => self::strOrDefault($rawObj, 'address', self::PC_MINIPROGRAM_DEFAULT_CONTENT['address']),
            'footer_slogan' => self::strOrDefault($rawObj, 'footer_slogan', self::PC_MINIPROGRAM_DEFAULT_CONTENT['footer_slogan']),
        ];

        // 无损迁移：仅在没有 pc-miniprogram 模块时，从旧 pc-contact.content 迁移联络信息
        $contact = self::toContentObject($legacyContactRaw);
        if ($contact !== null) {
            $content['phone'] = self::strOrFallback($contact['phone'] ?? null, $content['phone']);
            $content['service_time'] = self::strOrFallback($contact['service_time'] ?? null, $content['service_time']);
            $address = self::strOrFallback($contact['address'] ?? null, $content['address']);
            $content['address'] = $address === self::LEGACY_PLACEHOLDER_ADDRESS ? '' : $address;
            $content['footer_slogan'] = self::strOrFallback($contact['footer_slogan'] ?? null, $content['footer_slogan']);
            $qrcode = self::strOrFallback($contact['qrcode'] ?? null, '');
            $content['qrcode'] = $qrcode !== '' && $qrcode !== self::LEGACY_DEFAULT_CONTACT_QRCODE ? $qrcode : '';
        }

        return $content;
    }

    /**
     * @notes 旧 pc-banner.content.data 的首个有效对象图片，用于无 pc-hero 时回填品牌首屏主视觉
     * @param mixed $bannerContent
     * @return string
     */
    private static function firstBannerImage($bannerContent): string
    {
        if (!is_array($bannerContent)) {
            return '';
        }

        $items = self::safeParseList($bannerContent['data'] ?? null);
        foreach ($items as $item) {
            if (!self::isContentObject($item)) {
                continue;
            }

            return is_string($item['image'] ?? null) ? $item['image'] : '';
        }

        return '';
    }

    /**
     * @notes 列表逐项归一化：始终输出与 $defaults 等长的列表。
     * 有效项按索引与默认项逐字段合并（传入非空字符串覆盖对应索引默认项，不足补默认项）。
     * @param array $rawList
     * @param array $defaults
     * @param array $fields
     * @return array
     */
    private static function mergeListItems(array $rawList, array $defaults, array $fields): array
    {
        $validItems = array_values(array_filter($rawList, fn ($item) => self::isContentObject($item)));

        $result = [];
        foreach ($defaults as $index => $defaultItem) {
            $raw = $validItems[$index] ?? null;
            if ($raw === null) {
                $result[] = $defaultItem;
                continue;
            }

            $item = $defaultItem;
            foreach ($fields as $field) {
                $value = $raw[$field] ?? null;
                if (is_string($value) && $value !== '') {
                    $item[$field] = $value;
                }
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @notes 兼容 JSON 字符串、列表数组与数字键对象（PHP json_encode 数字键产生的 {0:..,1:..}），
     * 永不抛异常，失败返回空数组。
     * @param mixed $raw
     * @return array
     */
    private static function safeParseList($raw): array
    {
        if (is_object($raw)) {
            $decoded = json_decode(json_encode($raw), true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (is_array($raw)) {
            return self::hasNonNumericStringKeys($raw) ? [] : array_values($raw);
        }

        if (is_string($raw)) {
            if (trim($raw) === '') {
                return [];
            }

            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return self::hasNonNumericStringKeys($decoded) ? [] : array_values($decoded);
            }
        }

        return [];
    }

    /**
     * @notes 是否存在非数字字符串键（对应契约 isNumericKeyObject 的反向判断）
     * @param array $value
     * @return bool
     */
    private static function hasNonNumericStringKeys(array $value): bool
    {
        foreach (array_keys($value) as $key) {
            if (is_string($key) && !ctype_digit($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @notes 是否为内容对象（对应契约 isPlainObject：对象而非列表）
     * @param mixed $value
     * @return bool
     */
    private static function isContentObject($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        if ($value === []) {
            return true;
        }

        foreach (array_keys($value) as $key) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @notes 内容对象可能是 JSON 字符串（历史数据），宽松还原为对象；非对象返回 null
     * @param mixed $raw
     * @return array|null
     */
    private static function toContentObject($raw): ?array
    {
        if (is_object($raw)) {
            $decoded = json_decode(json_encode($raw), true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (is_string($raw)) {
            if (trim($raw) === '') {
                return null;
            }

            $decoded = json_decode($raw, true);
            return self::isContentObject($decoded) ? $decoded : null;
        }

        return self::isContentObject($raw) ? $raw : null;
    }

    /**
     * @notes 标量字段：字符串（含空串）覆盖默认值，其余类型回退默认值
     * @param array|null $raw
     * @param string $key
     * @param string $default
     * @return string
     */
    private static function strOrDefault(?array $raw, string $key, string $default): string
    {
        $value = $raw[$key] ?? null;
        return is_string($value) ? $value : $default;
    }

    /**
     * @notes 字符串优先回退：传入字符串（含空串）时使用传入值，否则使用回退值
     * @param mixed $value
     * @param string $fallback
     * @return string
     */
    private static function strOrFallback($value, string $fallback): string
    {
        return is_string($value) ? $value : $fallback;
    }

    /**
     * @notes enabled 仅当等价于 0 时为 0，否则一律 1
     * @param mixed $value
     * @return int
     */
    private static function normalizeEnabled($value): int
    {
        return (int)($value ?? 1) === 0 ? 0 : 1;
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
     * @notes 构建默认组件（styles 统一为空数组，不再生成 position: absolute 坐标）
     * @param string $widgetName
     * @return array
     */
    private static function buildDefaultWidget(string $widgetName): array
    {
        return [
            'id' => uniqid(self::widgetIdPrefix($widgetName), true),
            'title' => self::WIDGET_TITLES[$widgetName] ?? $widgetName,
            'name' => $widgetName,
            'content' => self::defaultContent($widgetName),
            'styles' => [],
        ];
    }

    /**
     * @notes 默认组件内容（与契约 PC_*_DEFAULTS 逐字一致）
     * @param string $widgetName
     * @return array
     */
    private static function defaultContent(string $widgetName): array
    {
        return match ($widgetName) {
            'pc-hero' => self::PC_HERO_DEFAULT_CONTENT,
            'pc-gallery' => self::PC_GALLERY_DEFAULT_CONTENT,
            'pc-miniprogram' => self::PC_MINIPROGRAM_DEFAULT_CONTENT,
            default => [],
        };
    }

    /**
     * @notes 默认组件 id 前缀
     * @param string $widgetName
     * @return string
     */
    private static function widgetIdPrefix(string $widgetName): string
    {
        return match ($widgetName) {
            'pc-hero' => 'pc_hero_',
            'pc-gallery' => 'pc_gallery_',
            'pc-miniprogram' => 'pc_miniprogram_',
            default => 'pc_widget_',
        };
    }
}
