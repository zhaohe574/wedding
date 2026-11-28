// 装修（decorate）数据契约。
//
// 后端 /api/index/index 返回的 page.data / page.meta 是 JSON 字符串，
// 内容由后台 33 个 widget 拼装而成。老端的致命写法是直接取：
//   state.pages = JSON.parse(data?.page?.data)
//   uni.setNavigationBarTitle({ title: state.meta[0].content.title })   // meta 为空即崩
//
// 本文件提供：解析 + 白名单 + 默认值 + 未知 widget 上报，保证装修数据异常时页面仍可渲染。

export type DecorateWidgetName =
    | 'banner'
    | 'search'
    | 'nav'
    | 'middle-banner'
    | 'home-brand'
    | 'home-feature-carousel'
    | 'home-service-categories'
    | 'home-recommend'
    | 'home-lucky-days'
    | 'home-flow'
    | 'home-popup-ad'
    | 'splash-ad'
    | 'user-banner'
    | 'user-info'
    | 'wedding-countdown'
    | 'quick-entry'
    | 'my-service'
    | 'customer-service'
    | 'login-hero'

export interface DecorateWidget<T = Record<string, any>> {
    name: string
    content: T
    styles?: Record<string, any>
}

export interface PageMetaContent {
    title: string
    bg_type: number
    bg_color: string
    bg_image: string
}

export interface DecoratePage {
    widgets: DecorateWidget[]
    meta: PageMetaContent
    /** 被跳过的 widget（未知类型或结构非法），用于上报 */
    skipped: string[]
}

/** 首页允许的 widget（与 IndexLogic::HOME_WIDGET_ORDER 保持一致） */
export const HOME_WIDGET_ORDER: DecorateWidgetName[] = [
    'banner',
    'home-brand',
    'home-service-categories',
    'home-recommend',
    'home-lucky-days',
    'home-flow',
    'home-popup-ad'
]

/** 全部已知 widget，其余一律跳过 */
export const KNOWN_WIDGETS: DecorateWidgetName[] = [
    ...HOME_WIDGET_ORDER,
    // 已从首页白名单下线，但历史 page.data 里可能仍在；保留识别能力，避免误报 skipped
    'home-feature-carousel',
    'splash-ad',
    'search',
    'nav',
    'middle-banner',
    'user-banner',
    'user-info',
    'wedding-countdown',
    'quick-entry',
    'my-service',
    'customer-service',
    'login-hero'
]

export const DEFAULT_PAGE_META: PageMetaContent = {
    title: '',
    bg_type: 1,
    bg_color: '#F8F6F0',
    bg_image: ''
}

/** 人员详情 banner 高度上限，与后端校验保持一致（REG-006） */
export const BANNER_MAX_HEIGHT = 900

export function clampBannerHeight(value: unknown, fallback = 400): number {
    const n = Number(value)
    if (!Number.isFinite(n) || n <= 0) return fallback
    return Math.min(Math.round(n), BANNER_MAX_HEIGHT)
}

function safeParseArray(raw: unknown): unknown[] {
    if (Array.isArray(raw)) return raw
    if (typeof raw !== 'string' || !raw.trim()) return []
    try {
        const parsed = JSON.parse(raw)
        return Array.isArray(parsed) ? parsed : []
    } catch {
        return []
    }
}

function isWidget(item: unknown): item is DecorateWidget {
    return !!item && typeof item === 'object' && typeof (item as DecorateWidget).name === 'string'
}

/**
 * 解析装修页数据。永不抛异常，永不返回 undefined。
 * @param rawData page.data（字符串或数组）
 * @param rawMeta page.meta（字符串或数组）
 */
export function parseDecoratePage(rawData: unknown, rawMeta: unknown): DecoratePage {
    const skipped: string[] = []

    const widgets = safeParseArray(rawData)
        .filter(isWidget)
        .filter((w) => {
            const known = (KNOWN_WIDGETS as string[]).includes(w.name)
            if (!known) skipped.push(w.name)
            return known
        })
        .map((w) => ({
            name: w.name,
            content: (w.content && typeof w.content === 'object' ? w.content : {}) as Record<string, any>,
            styles: w.styles && typeof w.styles === 'object' ? w.styles : {}
        }))

    const metaList = safeParseArray(rawMeta)
    const first = metaList.find((m) => m && typeof m === 'object') as { content?: Partial<PageMetaContent> } | undefined
    const c = first?.content ?? {}

    const meta: PageMetaContent = {
        title: typeof c.title === 'string' && c.title ? c.title : DEFAULT_PAGE_META.title,
        bg_type: Number(c.bg_type) === 2 ? 2 : 1,
        bg_color: typeof c.bg_color === 'string' && c.bg_color ? c.bg_color : DEFAULT_PAGE_META.bg_color,
        bg_image: typeof c.bg_image === 'string' ? c.bg_image : ''
    }

    return { widgets, meta, skipped }
}

/** 按 name 取第一个 widget 的 content，缺省返回空对象 */
export function findWidget<T = Record<string, any>>(page: DecoratePage, name: DecorateWidgetName): T | null {
    const hit = page.widgets.find((w) => w.name === name)
    return hit ? (hit.content as T) : null
}

