import { useAppStore } from '@/stores/app'

/**
 * 分享卡片统一出口。
 *
 * 微信分享卡片的标题 / 封面是「品牌名 + 图标」最容易被漏掉的展示位：
 * 页面里各自拼「品牌名 + 婚礼主持」会出现「后台改了名称，分享出去还是旧名」。
 * 所有 `onShareAppMessage` / `onShareTimeline` 一律走这里，标题缺省值由品牌配置补。
 *
 * query 规则：
 *  - 转发链接的 query 会原样带给接收方，凭证类参数（token / code / order_id …）
 *    必须剔除，否则「分享一次动态」等于把登录态或订单号送出去；
 *  - 落地页里的 query 会被解析（decode 失败保留原值）后与调用方传入的 query 合并，
 *    传入空值表示删除该键 —— 页面不需要自己拼 `?a=1&b=2`，也不怕拼错编码。
 */

/** 敏感 query 键（小写比对）：分享链接必须剔除，避免凭证 / 订单号随卡片外泄 */
const SENSITIVE_QUERY_KEYS = new Set([
    'token',
    'code',
    'password',
    'mobile',
    'phone',
    'session',
    'session_key',
    'openid',
    'unionid',
    'state',
    'order_id',
    'orderid'
])

export type ShareQuery = Record<string, string | number | boolean | null | undefined>

/** 分享渠道：好友卡片 5:4 横图，朋友圈单图 1:1 缩略图 */
export type ShareChannel = 'appMessage' | 'timeline'

/** 候选图允许缺省，调用方直接传接口字段不用先判 null */
export type ShareImageCandidate = string | null | undefined

/** 品牌内置首屏横图：所有候选都不可用时的最后一张 5:4 封面 */
export const DEFAULT_SHARE_COVER = '/static/images/hero-ceremony.jpg'

/** 后台默认占位头像：分享卡片里比品牌图更难看，直接跳过 */
const DEFAULT_AVATAR_PATH = '/static/images/user/default_avatar.png'

export interface SharePayload {
    title: string
    path: string
    imageUrl?: string
}

export interface TimelineSharePayload {
    title: string
    query?: string
    imageUrl?: string
}

export interface ShareOptions {
    /** 内容自身的标题（主持人名 / 动态标题）；为空则用「品牌名 + suffix」 */
    title?: string
    /** 分享落地页（可带 query，会被解析合并） */
    path: string
    /** 追加 / 覆盖的 query；敏感键与空值会被剔除 */
    query?: ShareQuery
    /** 内容封面；为空则用后台配置的品牌图标 */
    imageUrl?: string
    /** 好友转发（5:4）候选图，按顺序取第一张可用；imageUrl 排在最前保持兼容 */
    imageUrls?: ShareImageCandidate[]
    /** 朋友圈专用主候选（1:1 清晰图，通常是头像 / 品牌标） */
    timelineImageUrl?: string
    /** 朋友圈专用候选序列，优先于 imageUrl / imageUrls */
    timelineImageUrls?: ShareImageCandidate[]
    /** 标题后缀，如「婚礼主持」。只在 title 为空时参与拼接 */
    suffix?: string
    /** 标题长度上限（按 Unicode 码点），超出截断并补省略号 */
    maxTitleLength?: number
}

/** 朋友圈分享没有落地页 path，只带 title / query / imageUrl */
export type TimelineShareOptions = Omit<ShareOptions, 'path'> & { path?: string }

/**
 * 分享文案清洗：折叠连续空白、去首尾空格，并按码点截断。
 *
 * 微信卡片标题会被系统再截一次，长标题（尤其是拼接了动态正文的）留给用户的是
 * 「前半句 + 省略号」，这里先按统一上限收口，保证各页面标题观感一致。
 */
export function sanitizeShareText(raw?: string, maxLen = 36): string {
    const text = String(raw ?? '')
        .replace(/\s+/g, ' ')
        .trim()
    if (!text) return ''
    if (maxLen <= 1) return text.slice(0, 1)
    const chars = [...text]
    if (chars.length <= maxLen) return text
    return `${chars.slice(0, Math.max(1, maxLen - 1)).join('')}…`
}

/**
 * 富文本首个图片地址：长详情 / 作品介绍里的首图常是唯一的横版素材。
 * 只做字符串提取，不解析 DOM（小程序没有可用的 DOMParser）。
 */
export function extractFirstHtmlImage(html?: string): string {
    const source = String(html ?? '')
    if (!source) return ''
    const hit = /<img\b[^>]*?\bsrc\s*=\s*(?:"([^"]+)"|'([^']+)'|([^\s"'=<>`]+))/i.exec(source)
    if (!hit) return ''
    return String(hit[1] || hit[2] || hit[3] || '')
        .replace(/&amp;/g, '&')
        .trim()
}

/**
 * 候选图可用性：分享卡片只认真实位图。
 * 默认头像、data: 内联图、矢量 / 音视频文件在接收方手机上要么空白要么不显示，统一跳过。
 */
function isValidShareImageCandidate(value: string): boolean {
    if (!value) return false
    if (/^data:/i.test(value)) return false
    const path = value.split(/[?#]/)[0]
    if (!path) return false
    if (path === DEFAULT_AVATAR_PATH || path.endsWith(DEFAULT_AVATAR_PATH)) return false
    if (/\.(svg|mp4|mov|m4v|webm|m3u8|avi|mkv|mp3|wav|aac)$/i.test(path)) return false
    return true
}

/**
 * 分享封面解析：候选图 → 品牌首屏横图 → 内置横图 → 品牌图标，逐个校验可用性。
 *
 * 后台下发的图片多是 `/storage/...` 这类相对路径，微信分享卡片只认完整 URL；
 * 但域名为空时拼出来的 `/storage/...` 在接收方手机上同样是坏图。
 * 所以：能用就返回完整地址，不能用就跳过，全部不可用时返回 undefined
 * （让微信回落到页面截图），绝不返回空串或缺域名的相对路径。
 *
 * 频道差异只在默认兜底顺序：好友卡片优先横版首屏图，朋友圈优先方形品牌标。
 */
export function resolveShareImage(
    raw?: ShareImageCandidate | ShareImageCandidate[],
    channel: ShareChannel = 'appMessage'
): string | undefined {
    const appStore = useAppStore()
    const domain = String(appStore.config?.domain || '')
        .trim()
        .replace(/\/+$/, '')
    const shareCover = appStore.shareCoverUrl
    const brandLogo = appStore.brand.logo

    const ownCandidates = Array.isArray(raw) ? raw : [raw]
    const candidates =
        channel === 'appMessage'
            ? [...ownCandidates, shareCover, DEFAULT_SHARE_COVER, brandLogo]
            : [...ownCandidates, brandLogo, shareCover, DEFAULT_SHARE_COVER]

    for (const candidate of candidates) {
        const value = String(candidate ?? '').trim()
        if (!isValidShareImageCandidate(value)) continue
        if (/^https?:\/\//.test(value) || value.startsWith('/static/')) return value
        if (value.startsWith('/')) {
            if (domain) return `${domain}${value}`
            continue
        }
        if (domain) return `${domain}/${value}`
    }
    return undefined
}

/** 把 query 对象编码成 `k=v&k=v`；剔除敏感键与空值 */
export function buildShareQuery(query?: ShareQuery): string {
    if (!query) return ''
    const parts: string[] = []
    for (const key of Object.keys(query)) {
        if (SENSITIVE_QUERY_KEYS.has(key.toLowerCase())) continue
        const value = query[key]
        if (value === undefined || value === null || value === '') continue
        parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`)
    }
    return parts.join('&')
}

/** 拆分 `path?query`，query 保持原始编码，交给 parseQueryString 安全解码 */
function splitSharePath(path?: string): { pathname: string; rawQuery: string } {
    const raw = String(path || '')
    const qIndex = raw.indexOf('?')
    if (qIndex < 0) return { pathname: raw, rawQuery: '' }
    return { pathname: raw.slice(0, qIndex), rawQuery: raw.slice(qIndex + 1) }
}

/** decodeURIComponent 遇到非法百分号编码会抛错，此时保留原值而不是让整条分享链路崩掉 */
function safeDecode(value: string): string {
    try {
        return decodeURIComponent(value)
    } catch {
        return value
    }
}

/** 解析落地页自带 query，并按敏感键规则过滤 */
function parseQueryString(raw: string): Record<string, string> {
    const result: Record<string, string> = {}
    if (!raw) return result
    for (const pair of raw.split('&')) {
        if (!pair) continue
        const eq = pair.indexOf('=')
        const key = safeDecode(eq >= 0 ? pair.slice(0, eq) : pair)
        if (!key) continue
        if (SENSITIVE_QUERY_KEYS.has(key.toLowerCase())) continue
        result[key] = eq >= 0 ? safeDecode(pair.slice(eq + 1)) : ''
    }
    return result
}

/**
 * 落地页 query 与调用方 query 合并：
 * 调用方传入的键覆盖同名原值，传入 undefined / null / '' 表示删除该键。
 */
function composeShareQuery(path: string | undefined, query?: ShareQuery): string {
    const merged = parseQueryString(splitSharePath(path).rawQuery)
    if (query) {
        for (const key of Object.keys(query)) {
            const value = query[key]
            if (value === undefined || value === null || value === '') {
                delete merged[key]
                continue
            }
            merged[key] = String(value)
        }
    }
    return buildShareQuery(merged)
}

/**
 * 分享落地页：统一成以单个 `/` 开头，query 已清洗。
 * 空路径回退首页 —— 分享卡片点开必须有一个能打开的页面。
 */
export function buildSharePath(path: string, query?: ShareQuery): string {
    const trimmed = splitSharePath(path).pathname.trim()
    const base = trimmed ? `/${trimmed.replace(/^\/+/, '')}` : '/pages/index/index'
    const qs = composeShareQuery(path, query)
    return qs ? `${base}?${qs}` : base
}

/** buildShare / buildTimelineShare 共享的标题与封面解析，避免两条链路各写一套 */
function resolveShareMeta(
    options: Pick<
        ShareOptions,
        'title' | 'suffix' | 'maxTitleLength' | 'imageUrl' | 'imageUrls' | 'timelineImageUrl' | 'timelineImageUrls'
    >,
    channel: ShareChannel
): { title: string; imageUrl?: string } {
    const brand = useAppStore().brand
    const title =
        sanitizeShareText(options.title, options.maxTitleLength) ||
        sanitizeShareText(`${brand.name}${options.suffix || ''}`, options.maxTitleLength)

    const sharedCandidates: ShareImageCandidate[] = [
        options.imageUrl,
        ...(options.imageUrls || [])
    ]
    const timelineCandidates = [
        options.timelineImageUrl,
        ...(options.timelineImageUrls || [])
    ]
    const candidates =
        channel === 'timeline' &&
        (Boolean(options.timelineImageUrl) || (options.timelineImageUrls || []).length > 0)
            ? [...timelineCandidates, ...sharedCandidates]
            : sharedCandidates

    return { title, imageUrl: resolveShareImage(candidates, channel) }
}

export function buildShare(options: ShareOptions): SharePayload {
    const { title, imageUrl } = resolveShareMeta(options, 'appMessage')
    const payload: SharePayload = { title, path: buildSharePath(options.path, options.query) }
    // imageUrl 传 undefined 时微信会用页面截图，比传空串更稳
    if (imageUrl) payload.imageUrl = imageUrl
    return payload
}

export function buildTimelineShare(options: TimelineShareOptions): TimelineSharePayload {
    const { title, imageUrl } = resolveShareMeta(options, 'timeline')
    // 朋友圈分享只有 query，没有 path：绝不能把 path 字段带出去
    const payload: TimelineSharePayload = { title }
    const query = composeShareQuery(options.path, options.query)
    if (query) payload.query = query
    if (imageUrl) payload.imageUrl = imageUrl
    return payload
}

/**
 * 打开右上角菜单的转发入口。
 *
 * `timeline: false` 用于受 StaffGuard 保护的页面：朋友圈单页模式下无法跳登录 /
 * 其它页面，导出这类入口只会让接收方看到空白，所以只保留「发送给朋友」。
 */
export function enableShareMenu(options?: { timeline?: boolean }): void {
    // #ifdef MP-WEIXIN
    if (options?.timeline === false) {
        uni.showShareMenu({
            withShareTicket: true,
            menus: ['shareAppMessage'],
            fail: () => undefined
        })
        uni.hideShareMenu({ hideShareItems: ['shareTimeline'], fail: () => undefined })
        return
    }
    uni.showShareMenu({
        withShareTicket: true,
        menus: ['shareAppMessage', 'shareTimeline'],
        fail: () => undefined
    })
    // #endif
}

/**
 * 是否处于朋友圈单页模式（scene=1154）。
 *
 * 单页模式下没有页面栈、不能跳转、隐私弹窗会直接盖住内容，冷启动阶段要跳过弹窗。
 */
export function isSinglePageMode(scene?: number): boolean {
    if (Number(scene) === 1154) return true
    // #ifdef MP-WEIXIN
    try {
        if (Number((uni as any).getLaunchOptionsSync?.()?.scene) === 1154) return true
    } catch {
        return false
    }
    // #endif
    return false
}
