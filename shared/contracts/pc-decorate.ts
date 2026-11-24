// PC 官网装修契约（page.id = 4 / page.type = 4）。
//
// PC 端收敛为 3 个固定模块：电影感首屏 / 精选现场 / 小程序引导，
// 聚焦「品牌展示 + 小程序引导」；不再支持可视化自由布局，
// 且内容中不允许出现 link / cta_link / more_link 等功能型跳转字段。
//
// 唯一服务端实现：server/app/common/service/PcDecorateService.php
// 默认文案与旧数据迁移规则必须与该文件逐字保持一致，改动需双侧同步。

export const PC_DECORATE_PAGE_ID = 4
export const PC_DECORATE_PAGE_TYPE = 4

/** PC 装修固定模块顺序 */
export const PC_WIDGET_ORDER = ['pc-hero', 'pc-gallery', 'pc-miniprogram'] as const

export type PcWidgetName = (typeof PC_WIDGET_ORDER)[number]

/** 旧版 pc-contact 默认客服二维码：迁移时视为占位资源，不迁入小程序引导模块 */
export const LEGACY_DEFAULT_CONTACT_QRCODE = '/resource/image/adminapi/default/kefu01.png'

/** 旧版 pc-contact 默认地址占位文案：迁移时视为未填写，转为空字符串 */
const LEGACY_PLACEHOLDER_ADDRESS = '请在后台装修中填写企业地址'

/** 旧版模块名：仅在归一化时作为迁移来源识别，不再作为独立模块输出 */
const LEGACY_WIDGET_NAMES = ['pc-banner', 'pc-about', 'pc-advantages', 'pc-stats', 'pc-contact'] as const

// ------------------------------------------------------------------
// 内容结构
// ------------------------------------------------------------------

export interface PcHeroContent {
    enabled: 0 | 1
    brand_name: string
    brand_en: string
    title: string
    eyebrow: string
    image: string
    image_alt: string
    qr_caption: string
}

export interface PcGalleryItem {
    image: string
    alt: string
    scene: string
    title: string
    description: string
}

export interface PcGalleryContent {
    enabled: 0 | 1
    eyebrow: string
    title: string
    data: PcGalleryItem[]
}

export interface PcMiniProgramHighlightItem {
    title: string
    en_label: string
}

export interface PcMiniProgramContent {
    enabled: 0 | 1
    title: string
    highlights: PcMiniProgramHighlightItem[]
    preview_image: string
    preview_image_back: string
    qrcode: string
    qrcode_caption: string
    miniapp_name: string
    phone: string
    service_time: string
    address: string
    footer_slogan: string
}

export interface PcWidgetContentMap {
    'pc-hero': PcHeroContent
    'pc-gallery': PcGalleryContent
    'pc-miniprogram': PcMiniProgramContent
}

export interface PcDecorateWidget<K extends PcWidgetName = PcWidgetName> {
    id?: string
    title: string
    name: K
    content: PcWidgetContentMap[K]
    styles?: Record<string, any>
}

export const PC_WIDGET_META: Record<PcWidgetName, { title: string; description: string }> = {
    'pc-hero': { title: '电影感首屏', description: '全幅主视觉大片、衬线主标题与右下角扫码浮卡' },
    'pc-gallery': { title: '精选现场', description: '非对称 1+2 高定画册布局，展示三组真实仪式现场' },
    'pc-miniprogram': { title: '小程序引导', description: '双手机实景预览、三项双语体验亮点与底座扫码区' }
}

// ------------------------------------------------------------------
// 默认值（与 PcDecorateService.php 逐字一致）
// ------------------------------------------------------------------

export const PC_HERO_DEFAULTS: PcHeroContent = {
    enabled: 1,
    brand_name: '格林社',
    brand_en: 'GLINSHE',
    title: '以时间\n盛装万分之一的挚爱',
    eyebrow: 'BESPOKE CEREMONY & HOSTING',
    image: '/resource/image/adminapi/default/banner003.png',
    image_alt: '格林社高定婚礼仪式现场',
    qr_caption: '微信扫码 · 进入小程序'
}

export const PC_GALLERY_DEFAULTS: PcGalleryContent = {
    enabled: 1,
    eyebrow: '02 / SELECTED SCENES',
    title: '精选现场',
    data: [
        {
            image: '/resource/image/adminapi/default/banner003.png',
            alt: '三亚 · 澜景之夜婚礼仪式现场',
            scene: 'SCENE 01',
            title: '三亚 · 澜景之夜',
            description: '以稳定表达承接情绪，让重要瞬间自然发生。'
        },
        {
            image: '/resource/image/adminapi/default/banner001.png',
            alt: '北京 · 庭院秋叙宴会现场',
            scene: 'SCENE 02',
            title: '北京 · 庭院秋叙',
            description: '兼顾宾客体验、节奏与仪式感，让聚会松弛而体面。'
        },
        {
            image: '/resource/image/adminapi/default/banner002.png',
            alt: '杭州 · 静水长宴统筹现场',
            scene: 'SCENE 03',
            title: '杭州 · 静水长宴',
            description: '提前拆解每个细节，让执行在现场更从容。'
        }
    ]
}

export const PC_MINIPROGRAM_DEFAULTS: PcMiniProgramContent = {
    enabled: 1,
    title: '将极致仪式\n收入指尖',
    highlights: [
        { title: '实时查档', en_label: 'REAL-TIME CALENDAR' },
        { title: '严选团队', en_label: 'CURATED TEAM' },
        { title: '全程透明', en_label: 'FULL TRANSPARENCY' }
    ],
    preview_image: '',
    preview_image_back: '',
    qrcode: '',
    qrcode_caption: '扫码预约专属咨询',
    miniapp_name: '格林社婚礼服务',
    phone: '',
    service_time: '',
    address: '',
    footer_slogan: 'GLINSHE CEREMONY HOUSE'
}

// ------------------------------------------------------------------
// 解析与归一化
// ------------------------------------------------------------------

function isPlainObject(value: unknown): value is Record<string, unknown> {
    return !!value && typeof value === 'object' && !Array.isArray(value)
}

/** 兼容 PHP json_encode 数字键对象（{0:..,1:..}） */
function isNumericKeyObject(value: unknown): value is Record<string, unknown> {
    if (!isPlainObject(value)) return false
    const keys = Object.keys(value)
    return keys.length > 0 && keys.every((key) => /^\d+$/.test(key))
}

/**
 * 兼容 JSON 字符串、数组与 PHP 数字键对象。永不抛异常，失败返回空数组。
 */
function safeParseArray(raw: unknown): unknown[] {
    if (Array.isArray(raw)) return raw
    if (typeof raw === 'string') {
        if (!raw.trim()) return []
        try {
            const parsed: unknown = JSON.parse(raw)
            if (Array.isArray(parsed)) return parsed
            if (isNumericKeyObject(parsed)) return Object.values(parsed)
        } catch {
            return []
        }
        return []
    }
    if (isNumericKeyObject(raw)) return Object.values(raw)
    return []
}

/** 内容对象可能是 JSON 字符串（历史数据），宽松还原为对象 */
function toContentObject(raw: unknown): Record<string, unknown> | undefined {
    if (typeof raw === 'string') {
        if (!raw.trim()) return undefined
        try {
            const parsed: unknown = JSON.parse(raw)
            return isPlainObject(parsed) ? parsed : undefined
        } catch {
            return undefined
        }
    }
    return isPlainObject(raw) ? raw : undefined
}

/** enabled 仅当等价于 0 时为 0，否则一律 1（与 PHP (int) 强转语义一致） */
function normalizeEnabled(value: unknown): 0 | 1 {
    return Number(value ?? 1) === 0 ? 0 : 1
}

/** 标量字段：字符串（含空串）覆盖默认值，其余类型回退默认值 */
function strOrDefault(raw: Record<string, unknown> | undefined, key: string, fallback: string): string {
    const value = raw?.[key]
    return typeof value === 'string' ? value : fallback
}

function strOrFallback(value: unknown, fallback: string): string {
    return typeof value === 'string' ? value : fallback
}

/**
 * 列表逐项归一化：始终输出与 defaults 等长的列表。
 * - 传入列表无有效对象时整体使用 defaults；
 * - 有效项按索引与 defaults 逐字段合并（传入非空字符串覆盖，空字段回退默认项）；
 * - 不足补 defaults 剩余项，超出截断到 defaults 长度（3 条）。
 */
function mergeListItems<T extends object>(
    rawList: unknown[],
    defaults: T[],
    fields: readonly (keyof T & string)[]
): T[] {
    const validItems = rawList.filter(isPlainObject)
    return defaults.map((defaultItem, index) => {
        const raw = validItems[index]
        if (!raw) return { ...defaultItem }
        const item = { ...defaultItem } as Record<string, unknown>
        for (const field of fields) {
            const value = raw[field]
            if (typeof value === 'string' && value !== '') item[field] = value
        }
        return item as T
    })
}

export function normalizePcHeroContent(raw: unknown): PcHeroContent {
    const rawObj = toContentObject(raw)
    const content: PcHeroContent = {
        enabled: normalizeEnabled(rawObj?.enabled),
        brand_name: strOrDefault(rawObj, 'brand_name', PC_HERO_DEFAULTS.brand_name),
        brand_en: strOrDefault(rawObj, 'brand_en', PC_HERO_DEFAULTS.brand_en),
        title: strOrDefault(rawObj, 'title', PC_HERO_DEFAULTS.title),
        eyebrow: strOrDefault(rawObj, 'eyebrow', PC_HERO_DEFAULTS.eyebrow),
        image: strOrDefault(rawObj, 'image', PC_HERO_DEFAULTS.image),
        image_alt: strOrDefault(rawObj, 'image_alt', PC_HERO_DEFAULTS.image_alt),
        qr_caption: strOrDefault(rawObj, 'qr_caption', PC_HERO_DEFAULTS.qr_caption)
    }
    // 兼容旧字段 brand_tagline：brand_en 缺失或非字符串，但 brand_tagline 为非空字符串时迁入
    const brandTagline = rawObj?.brand_tagline
    if (typeof rawObj?.brand_en !== 'string' && typeof brandTagline === 'string' && brandTagline !== '') {
        content.brand_en = brandTagline
    }
    return content
}

export function normalizePcGalleryContent(raw: unknown): PcGalleryContent {
    const rawObj = toContentObject(raw)
    const content: PcGalleryContent = {
        enabled: normalizeEnabled(rawObj?.enabled),
        eyebrow: strOrDefault(rawObj, 'eyebrow', PC_GALLERY_DEFAULTS.eyebrow),
        title: strOrDefault(rawObj, 'title', PC_GALLERY_DEFAULTS.title),
        data: []
    }
    content.data = mergeListItems(safeParseArray(rawObj?.data), PC_GALLERY_DEFAULTS.data, [
        'image',
        'alt',
        'scene',
        'title',
        'description'
    ])
    return content
}

export function normalizePcMiniProgramContent(raw: unknown, legacyContactRaw?: unknown): PcMiniProgramContent {
    const rawObj = toContentObject(raw)
    const content: PcMiniProgramContent = {
        enabled: normalizeEnabled(rawObj?.enabled),
        title: strOrDefault(rawObj, 'title', PC_MINIPROGRAM_DEFAULTS.title),
        highlights: [],
        preview_image: strOrDefault(rawObj, 'preview_image', PC_MINIPROGRAM_DEFAULTS.preview_image),
        preview_image_back: strOrDefault(
            rawObj,
            'preview_image_back',
            PC_MINIPROGRAM_DEFAULTS.preview_image_back
        ),
        qrcode: strOrDefault(rawObj, 'qrcode', PC_MINIPROGRAM_DEFAULTS.qrcode),
        qrcode_caption: strOrDefault(rawObj, 'qrcode_caption', PC_MINIPROGRAM_DEFAULTS.qrcode_caption),
        miniapp_name: strOrDefault(rawObj, 'miniapp_name', PC_MINIPROGRAM_DEFAULTS.miniapp_name),
        phone: strOrDefault(rawObj, 'phone', PC_MINIPROGRAM_DEFAULTS.phone),
        service_time: strOrDefault(rawObj, 'service_time', PC_MINIPROGRAM_DEFAULTS.service_time),
        address: strOrDefault(rawObj, 'address', PC_MINIPROGRAM_DEFAULTS.address),
        footer_slogan: strOrDefault(rawObj, 'footer_slogan', PC_MINIPROGRAM_DEFAULTS.footer_slogan)
    }
    // 历史项只有 description 无 en_label 时，仅覆盖 title，不覆盖默认 en_label
    content.highlights = mergeListItems(safeParseArray(rawObj?.highlights), PC_MINIPROGRAM_DEFAULTS.highlights, [
        'title',
        'en_label'
    ])
    // 无损迁移：仅在不存在 pc-miniprogram 模块时（由 parsePcDecorateWidgets 控制），
    // 从旧 pc-contact.content 迁移联络信息
    const contact = toContentObject(legacyContactRaw)
    if (contact) {
        content.phone = strOrFallback(contact.phone, content.phone)
        content.service_time = strOrFallback(contact.service_time, content.service_time)
        const address = strOrFallback(contact.address, content.address)
        content.address = address === LEGACY_PLACEHOLDER_ADDRESS ? '' : address
        content.footer_slogan = strOrFallback(contact.footer_slogan, content.footer_slogan)
        const qrcode = strOrFallback(contact.qrcode, '')
        content.qrcode = qrcode !== '' && qrcode !== LEGACY_DEFAULT_CONTACT_QRCODE ? qrcode : ''
    }
    return content
}

/** 旧 pc-banner.content.data[0].image，用于无 pc-hero 时回填品牌首屏主视觉 */
function firstBannerImage(bannerContent: Record<string, unknown> | undefined): string {
    const items = safeParseArray(bannerContent?.data)
    const first = items.find(isPlainObject)
    const image = first?.image
    return typeof image === 'string' ? image : ''
}

function isPcWidgetName(name: string): name is PcWidgetName {
    return (PC_WIDGET_ORDER as readonly string[]).includes(name)
}

/**
 * 解析 PC 装修数据。永不抛异常，永远输出 3 个固定模块（按 PC_WIDGET_ORDER 排序）。
 * 旧模块（pc-banner / pc-about / pc-advantages / pc-stats / pc-contact）只作迁移来源，不再输出。
 */
export function parsePcDecorateWidgets(rawData: unknown): PcDecorateWidget[] {
    const newWidgets = new Map<PcWidgetName, Record<string, unknown>>()
    const legacyContents = new Map<string, Record<string, unknown>>()

    for (const item of safeParseArray(rawData)) {
        const widget = toContentObject(item)
        if (!widget) continue
        const name = typeof widget.name === 'string' ? widget.name : ''
        if (isPcWidgetName(name)) {
            if (!newWidgets.has(name)) newWidgets.set(name, widget)
        } else if ((LEGACY_WIDGET_NAMES as readonly string[]).includes(name)) {
            if (!legacyContents.has(name)) legacyContents.set(name, toContentObject(widget.content) ?? {})
        }
    }

    const hasHero = newWidgets.has('pc-hero')
    const heroContent = normalizePcHeroContent(newWidgets.get('pc-hero')?.content)
    // 无损迁移：无 pc-hero 但存在旧 pc-banner 时，取轮播首图作为品牌首屏主视觉
    const bannerImage = hasHero ? '' : firstBannerImage(legacyContents.get('pc-banner'))
    const hero = bannerImage ? { ...heroContent, image: bannerImage } : heroContent

    const galleryContent = normalizePcGalleryContent(newWidgets.get('pc-gallery')?.content)
    const hasMiniProgram = newWidgets.has('pc-miniprogram')
    const miniProgramContent = normalizePcMiniProgramContent(
        newWidgets.get('pc-miniprogram')?.content,
        hasMiniProgram ? undefined : legacyContents.get('pc-contact')
    )

    const heroRaw = newWidgets.get('pc-hero')
    const galleryRaw = newWidgets.get('pc-gallery')
    const miniProgramRaw = newWidgets.get('pc-miniprogram')

    return [
        {
            id: typeof heroRaw?.id === 'string' ? heroRaw.id : undefined,
            title: PC_WIDGET_META['pc-hero'].title,
            name: 'pc-hero',
            content: hero,
            styles: {}
        },
        {
            id: typeof galleryRaw?.id === 'string' ? galleryRaw.id : undefined,
            title: PC_WIDGET_META['pc-gallery'].title,
            name: 'pc-gallery',
            content: galleryContent,
            styles: {}
        },
        {
            id: typeof miniProgramRaw?.id === 'string' ? miniProgramRaw.id : undefined,
            title: PC_WIDGET_META['pc-miniprogram'].title,
            name: 'pc-miniprogram',
            content: miniProgramContent,
            styles: {}
        }
    ]
}

/** 构建 3 个固定模块的默认装修数据（未落库前的初始形态，无 id） */
export function createDefaultPcWidgets(): PcDecorateWidget[] {
    return [
        {
            title: PC_WIDGET_META['pc-hero'].title,
            name: 'pc-hero',
            content: { ...PC_HERO_DEFAULTS },
            styles: {}
        },
        {
            title: PC_WIDGET_META['pc-gallery'].title,
            name: 'pc-gallery',
            content: {
                ...PC_GALLERY_DEFAULTS,
                data: PC_GALLERY_DEFAULTS.data.map((item) => ({ ...item }))
            },
            styles: {}
        },
        {
            title: PC_WIDGET_META['pc-miniprogram'].title,
            name: 'pc-miniprogram',
            content: {
                ...PC_MINIPROGRAM_DEFAULTS,
                highlights: PC_MINIPROGRAM_DEFAULTS.highlights.map((item) => ({ ...item }))
            },
            styles: {}
        }
    ]
}

/** 按 name 取固定模块；parsePcDecorateWidgets 的输出恒含全部模块，缺失时回退默认值 */
export function findPcWidget<K extends PcWidgetName>(
    widgets: PcDecorateWidget[],
    name: K
): PcDecorateWidget<K> {
    const hit = widgets.find((widget) => widget.name === name)
    if (hit) return hit as PcDecorateWidget<K>
    return createDefaultPcWidgets().find(
        (widget) => widget.name === name
    ) as PcDecorateWidget<K>
}
