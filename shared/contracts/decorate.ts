// 移动端页面装修 —— 三端唯一事实源。
//
// 为什么要有这个文件：
//   后台装修编辑器、服务端归一化、小程序渲染原来各写各的字段名，
//   结果是「后台能配、前端不认」（home-popup-ad 配了但小程序没实现、
//   开屏广告只有 6 个字段、splash 页根本不存在）。
//
// 约定：
//   1. 页面结构（哪几页、每页允许哪些组件、顺序）只在这里定义；
//   2. 组件字段（默认值、取值范围、归一化）只在这里定义；
//   3. 后台编辑器按本文件的页面/组件清单渲染，不维护第二份列表；
//   4. 服务端 `app/common/service/SplashAdDecorateService.php`、
//      `app/common/service/HomeDecorateService.php` 与
//      `IndexLogic::HOME_WIDGET_ORDER` 必须与本文件保持同步（PHP 无法直接 import TS，
//      改动时两边一起改，`npm run decorate:check` 会比对字段与组件名）。

/* ------------------------------------------------------------------ *
 * 1. 页面结构
 * ------------------------------------------------------------------ */

export type DecoratePageKey = 'home' | 'user' | 'service' | 'splash'

export interface DecoratePageDef {
    /** 与 `la_decorate_page.id` 一致 */
    id: number
    /** 与 `la_decorate_page.type` 一致 */
    type: number
    key: DecoratePageKey
    /** 后台左侧菜单名 */
    name: string
    /** 允许的组件（数组顺序 = 默认渲染顺序） */
    widgets: string[]
    /** 是否支持 page-meta（标题 / 背景色 / 背景图） */
    hasMeta: boolean
    /**
     * 接入状态：
     *   live    —— 小程序已按本页配置渲染，保存即生效；
     *   pending —— 小程序尚未接入该页，后台可以配但**不会产生任何展示效果**。
     * 后台编辑器必须对 pending 页给出显式提示，避免运营以为「配了就上线了」。
     */
    status: 'live' | 'pending'
    /**
     * 该装修页对应的小程序页面路由（仅 status='live' 的页面有值）。
     * 后台预览是自绘同构版，**不打开**这个路由；它存在的意义是：
     *   1. 声明「这份配置最终落在哪个页面」，避免装修页与小程序页对不上；
     *   2. `npm run decorate:check` 会校验它确实注册在 newuniapp/src/pages.json 里。
     */
    previewRoute?: string
}

/**
 * 移动端四个装修页。
 * ⚠ 组件的 `name` 必须与小程序 `shared/contracts/widgets.ts` 的 KNOWN_WIDGETS 对得上，
 *   否则小程序会把该组件当未知类型跳过（并上报 skipped）。
 */
export const DECORATE_PAGES: DecoratePageDef[] = [
    {
        id: 1,
        type: 1,
        key: 'home',
        name: '首页装修',
        // home-feature-carousel 已下线：小程序从未渲染过它，留在白名单里只会让运营白配
        widgets: [
            'banner',
            'home-brand',
            'home-service-categories',
            'home-flow',
            'home-popup-ad'
        ],
        hasMeta: true,
        status: 'live',
        previewRoute: '/pages/index/index'
    },
    {
        id: 2,
        type: 2,
        key: 'user',
        name: '个人中心',
        widgets: ['user-info', 'wedding-countdown', 'quick-entry'],
        hasMeta: false,
        status: 'live',
        previewRoute: '/pages/user/user'
    },
    {
        id: 3,
        type: 3,
        key: 'service',
        name: '客服设置',
        widgets: ['customer-service'],
        hasMeta: false,
        status: 'pending'
    },
    {
        id: 6,
        type: 6,
        key: 'splash',
        name: '开屏广告页',
        widgets: ['splash-ad'],
        hasMeta: false,
        status: 'live',
        previewRoute: '/pages/splash/splash'
    }
]

export const DECORATE_PAGE_IDS = DECORATE_PAGES.map((p) => p.id)

/**
 * 首页组件在后台的显示名 —— 与真机页面上的区块叫法一致。
 *
 * ⚠ 这是**唯一 TS 源**：admin 各 widget 的 `options.ts` 读它，PHP 镜像在
 * `HomeDecorateService::WIDGET_TITLES`。名字存在 `la_decorate_page.data` 的
 * `widget.title` 里，老数据的旧名由服务端归一化 + 后台 `applyPageRules()` 强制覆盖。
 * 不要再把「首页轮播图 / 团队信息 / 服务分类」写回任何一处。
 */
export const HOME_WIDGET_TITLES: Record<string, string> = {
    banner: '首屏主图',
    'home-brand': '数据条',
    'home-service-categories': '四宫格入口',
    'home-flow': '服务承诺',
    'home-popup-ad': '首页弹窗广告'
}

export function findDecoratePage(id: number | string): DecoratePageDef | null {
    const num = Number(id)
    return DECORATE_PAGES.find((p) => p.id === num) || null
}

/** 某页允许的组件名；未知页返回空数组 */
export function widgetsOfPage(id: number | string): string[] {
    return findDecoratePage(id)?.widgets || []
}

/** 该组件是否属于这一页（后台过滤、服务端裁剪都用它） */
export function isWidgetAllowed(pageId: number | string, widgetName: string): boolean {
    return widgetsOfPage(pageId).includes(widgetName)
}

/* ------------------------------------------------------------------ *
 * 2. 通用工具
 * ------------------------------------------------------------------ */

export type Int01 = 0 | 1

function toInt(value: unknown, fallback = 0): number {
    const n = Number(value)
    return Number.isFinite(n) ? Math.round(n) : fallback
}

export function toInt01(value: unknown, fallback: Int01 = 0): Int01 {
    return toInt(value, fallback) === 1 ? 1 : 0
}

export function clampInt(value: unknown, min: number, max: number, fallback: number): number {
    const n = toInt(value, fallback)
    return Math.max(min, Math.min(max, n))
}

function toText(value: unknown): string {
    return typeof value === 'string' ? value.trim() : ''
}

/** 链接对象归一化：只保留小程序能识别的 path/type/query */
export interface DecorateLink {
    path: string
    type?: string
    name?: string
    query?: Record<string, string>
}

export function normalizeLink(raw: unknown): DecorateLink {
    if (!raw || typeof raw !== 'object') return { path: '' }
    const source = raw as Record<string, any>
    const path = toText(source.path)
    const link: DecorateLink = { path }
    if (source.type) link.type = toText(source.type)
    if (source.name) link.name = toText(source.name)
    if (source.query && typeof source.query === 'object') {
        const query: Record<string, string> = {}
        for (const key of Object.keys(source.query)) {
            const value = source.query[key]
            if (value === null || value === undefined) continue
            query[key] = String(value)
        }
        if (Object.keys(query).length) link.query = query
    }
    return link
}

/* ------------------------------------------------------------------ *
 * 3. 开屏广告（splash-ad）
 * ------------------------------------------------------------------ */

/**
 * 主按钮可点击时机（字段名沿用历史 skip_mode）：
 *   countdown —— 按钮一直显示但不可点，`skip_delay` 秒后才可点击（默认）
 *   always    —— 一进页面就可点击
 *   never     —— 一直不可点击，只能等自动进入；若关闭自动进入，前端兜底立即可点
 */
export type SplashSkipMode = 'countdown' | 'always' | 'never'

/**
 * 展示频次：
 *   every_time     每次冷启动都展示
 *   session        每个会话一次（小程序进程内只展示一次）
 *   daily          每天一次（自然日，Asia/Shanghai）
 *   once           只展示一次（卸载重装或清缓存后重置）
 *   interval_days  间隔 N 天展示一次
 */
export type SplashFrequency = 'every_time' | 'session' | 'daily' | 'once' | 'interval_days'

export const SPLASH_SKIP_MODES: Array<{ value: SplashSkipMode; label: string }> = [
    { value: 'countdown', label: '倒计时后可点击' },
    { value: 'always', label: '立即可点击' },
    { value: 'never', label: '不可点击（只自动进入）' }
]

export const SPLASH_FREQUENCIES: Array<{ value: SplashFrequency; label: string }> = [
    { value: 'every_time', label: '每次启动' },
    { value: 'session', label: '每会话一次' },
    { value: 'daily', label: '每天一次' },
    { value: 'once', label: '仅一次' },
    { value: 'interval_days', label: '间隔天数' }
]

export interface SplashAdContent {
    /** 总开关；关闭时小程序直接进首页 */
    enabled: Int01
    /** 主素材（竖版全屏图，建议 750×1624） */
    image: string
    /** 品牌标识（可选，居中偏上，透明 PNG） */
    logo_image: string
    /** 是否自动进入（0 = 只能手动点按钮/跳过） */
    auto_enter_enabled: Int01
    /** 展示时长（秒），到点自动进入 */
    auto_seconds: number
    /** 跳过方式 */
    skip_mode: SplashSkipMode
    /** countdown 模式下，第几秒出现跳过按钮（0 = 一开始就出现） */
    skip_delay: number
    /** 主按钮文案 */
    button_text: string
    /** 展示频次 */
    frequency: SplashFrequency
    /** 每日最多展示次数，0 = 不限制（frequency 之外的二次限流） */
    max_daily_count: number
    /** interval_days 模式下的间隔天数 */
    interval_days: number
    /** 生效开始时间 'YYYY-MM-DD HH:mm:ss'，空 = 立即生效 */
    start_time: string
    /** 生效结束时间，空 = 长期有效 */
    end_time: string
}

export const SPLASH_DEFAULTS: SplashAdContent = {
    enabled: 0,
    image: '',
    logo_image: '',
    auto_enter_enabled: 1,
    auto_seconds: 3,
    skip_mode: 'countdown',
    skip_delay: 0,
    button_text: '点击进入',
    frequency: 'session',
    max_daily_count: 0,
    interval_days: 7,
    start_time: '',
    end_time: ''
}

const SKIP_MODE_SET = new Set(SPLASH_SKIP_MODES.map((m) => m.value))
const FREQUENCY_SET = new Set(SPLASH_FREQUENCIES.map((m) => m.value))

/**
 * 开屏广告内容归一化。永不抛异常。
 * 后台保存、服务端下发、小程序读取都走这一个函数，保证三端看到同一份数据。
 */
export function normalizeSplashContent(raw: unknown): SplashAdContent {
    const source = (raw && typeof raw === 'object' ? raw : {}) as Record<string, any>

    const skipMode = toText(source.skip_mode) as SplashSkipMode
    const frequency = toText(source.frequency) as SplashFrequency

    return {
        enabled: toInt01(source.enabled, 0),
        image: toText(source.image),
        logo_image: toText(source.logo_image || source.logoImage || source.logo),
        auto_enter_enabled: toInt01(source.auto_enter_enabled ?? source.auto_enter, 1),
        auto_seconds: clampInt(source.auto_seconds, 1, 10, SPLASH_DEFAULTS.auto_seconds),
        skip_mode: SKIP_MODE_SET.has(skipMode) ? skipMode : SPLASH_DEFAULTS.skip_mode,
        // 跳过早于 0 秒或晚于展示时长都没有意义，统一夹在 [0, auto_seconds]
        skip_delay: clampInt(source.skip_delay, 0, 10, SPLASH_DEFAULTS.skip_delay),
        button_text: toText(source.button_text) || SPLASH_DEFAULTS.button_text,
        frequency: FREQUENCY_SET.has(frequency) ? frequency : SPLASH_DEFAULTS.frequency,
        max_daily_count: clampInt(source.max_daily_count, 0, 99, SPLASH_DEFAULTS.max_daily_count),
        interval_days: clampInt(source.interval_days, 1, 365, SPLASH_DEFAULTS.interval_days),
        start_time: toText(source.start_time),
        end_time: toText(source.end_time)
    }
}

/** 归一化后 `skip_delay` 不超过展示时长 */
export function effectiveSkipDelay(content: SplashAdContent): number {
    return Math.min(content.skip_delay, content.auto_seconds)
}

/** 后台配置是否「有内容可展示」：开关打开 + 有主图 */
export function isSplashConfigured(content: SplashAdContent): boolean {
    return content.enabled === 1 && !!content.image
}

/* ------------------------------------------------------------------ *
 * 4. 展示频次判定（后台预览 / 小程序共用同一套规则）
 * ------------------------------------------------------------------ */

export interface SplashRuntimeState {
    /** 上次展示时间戳（ms） */
    lastShownAt: number
    /** 累计展示次数 */
    totalCount: number
    /** 上次展示的自然日 'YYYY-MM-DD' */
    lastShownDate: string
    /** 该自然日内已展示次数 */
    todayCount: number
    /** 本次进程内是否已展示过 */
    sessionShown: boolean
}

export const SPLASH_STATE_EMPTY: SplashRuntimeState = {
    lastShownAt: 0,
    totalCount: 0,
    lastShownDate: '',
    todayCount: 0,
    sessionShown: false
}

export type SplashSkipReason =
    | 'disabled'
    | 'no-image'
    | 'not-started'
    | 'ended'
    | 'session-shown'
    | 'daily-shown'
    | 'once-shown'
    | 'interval-not-reached'
    | 'daily-limit'

export interface SplashDecision {
    show: boolean
    reason?: SplashSkipReason
}

/** 'YYYY-MM-DD'（按设备本地时区，与后台 start_time/end_time 的解释一致） */
export function dateKey(date: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

/** 'YYYY-MM-DD HH:mm:ss' / 'YYYY-MM-DD' → 时间戳；解析失败返回 null */
export function parseDateTime(value: string): number | null {
    const text = (value || '').trim()
    if (!text) return null
    // iOS 不认 '2026-01-01 08:00:00'，必须换成 '/' 分隔
    const normalized = text.includes('T') ? text : text.replace(/-/g, '/')
    const ts = new Date(normalized).getTime()
    return Number.isFinite(ts) ? ts : null
}

/**
 * 判定这次启动是否应该展示开屏广告。
 * 纯函数：不读存储、不发请求，方便后台预览和小程序复用同一套规则。
 */
export function shouldShowSplash(
    content: SplashAdContent,
    state: SplashRuntimeState = SPLASH_STATE_EMPTY,
    now: Date = new Date()
): SplashDecision {
    if (!isSplashConfigured(content)) {
        return { show: false, reason: content.enabled === 1 ? 'no-image' : 'disabled' }
    }

    const nowTs = now.getTime()

    const startTs = parseDateTime(content.start_time)
    if (startTs !== null && nowTs < startTs) return { show: false, reason: 'not-started' }

    const endTs = parseDateTime(content.end_time)
    if (endTs !== null && nowTs > endTs) return { show: false, reason: 'ended' }

    // 频次：先判主规则
    switch (content.frequency) {
        case 'session':
            if (state.sessionShown) return { show: false, reason: 'session-shown' }
            break
        case 'daily':
            if (state.lastShownDate && state.lastShownDate === dateKey(now)) {
                return { show: false, reason: 'daily-shown' }
            }
            break
        case 'once':
            if (state.totalCount > 0) return { show: false, reason: 'once-shown' }
            break
        case 'interval_days': {
            const days = Math.max(1, content.interval_days)
            const elapsed = nowTs - state.lastShownAt
            if (state.lastShownAt > 0 && elapsed < days * 86400000) {
                return { show: false, reason: 'interval-not-reached' }
            }
            break
        }
        case 'every_time':
        default:
            break
    }

    // 频次：再判每日上限（0 = 不限）
    if (content.max_daily_count > 0) {
        const today = dateKey(now)
        const used = state.lastShownDate === today ? state.todayCount : 0
        if (used >= content.max_daily_count) return { show: false, reason: 'daily-limit' }
    }

    return { show: true }
}

/** 判定结果的可读文案（后台预览、日志用） */
export const SPLASH_SKIP_REASON_TEXT: Record<SplashSkipReason, string> = {
    disabled: '总开关未开启',
    'no-image': '未上传开屏素材',
    'not-started': '未到生效开始时间',
    ended: '已过生效结束时间',
    'session-shown': '本次会话已展示过',
    'daily-shown': '今天已展示过',
    'once-shown': '已经展示过一次',
    'interval-not-reached': '未达到间隔天数',
    'daily-limit': '已达今日展示上限'
}

/** 记录一次展示后的新状态（小程序写入本地存储前调用） */
export function markSplashShown(
    state: SplashRuntimeState,
    now: Date = new Date()
): SplashRuntimeState {
    const today = dateKey(now)
    return {
        lastShownAt: now.getTime(),
        totalCount: state.totalCount + 1,
        lastShownDate: today,
        todayCount: state.lastShownDate === today ? state.todayCount + 1 : 1,
        sessionShown: true
    }
}

/* ------------------------------------------------------------------ *
 * 5. 首页弹窗广告（home-popup-ad）
 * ------------------------------------------------------------------ */

/**
 * 与开屏广告的区别：
 *   开屏 = 冷启动第一屏；弹窗 = 进首页之后才弹，用户已经在浏览了。
 * 所以弹窗多了「展示时机（立即 / 延迟）」和「总次数上限」，少了「跳过方式」。
 */
export type HomePopupFrequency =
    | 'daily'
    | 'session'
    | 'every_home_entry'
    | 'once'
    | 'interval_days'
    | 'custom_limit'

export type HomePopupType = 'image' | 'text' | 'image_text'

export interface HomePopupAdContent {
    enabled: Int01
    type: HomePopupType
    title: string
    content: string
    image: string
    background_color: string
    button_text: string
    link: DecorateLink
    frequency: HomePopupFrequency
    show_timing: 'page_ready' | 'delay'
    delay_seconds: number
    interval_days: number
    max_total_count: number
    max_daily_count: number
    start_time: string
    end_time: string
    /** 点关闭是否算一次曝光（影响频次计数口径） */
    close_counts_as_shown: Int01
    show_close: Int01
}

export const HOME_POPUP_DEFAULTS: HomePopupAdContent = {
    enabled: 0,
    type: 'image_text',
    title: '',
    content: '',
    image: '',
    background_color: '#FFFDF8',
    button_text: '查看详情',
    link: { path: '' },
    frequency: 'daily',
    show_timing: 'page_ready',
    delay_seconds: 0,
    interval_days: 7,
    max_total_count: 0,
    max_daily_count: 1,
    start_time: '',
    end_time: '',
    close_counts_as_shown: 1,
    show_close: 1
}

const POPUP_TYPES = new Set<HomePopupType>(['image', 'text', 'image_text'])
const POPUP_FREQUENCIES = new Set<HomePopupFrequency>([
    'daily',
    'session',
    'every_home_entry',
    'once',
    'interval_days',
    'custom_limit'
])

export function normalizeHomePopupContent(raw: unknown): HomePopupAdContent {
    const source = (raw && typeof raw === 'object' ? raw : {}) as Record<string, any>
    const type = toText(source.type) as HomePopupType
    const frequency = toText(source.frequency) as HomePopupFrequency

    return {
        enabled: toInt01(source.enabled, 0),
        type: POPUP_TYPES.has(type) ? type : HOME_POPUP_DEFAULTS.type,
        title: toText(source.title),
        content: toText(source.content),
        image: toText(source.image),
        background_color: toText(source.background_color) || HOME_POPUP_DEFAULTS.background_color,
        button_text: toText(source.button_text) || HOME_POPUP_DEFAULTS.button_text,
        link: normalizeLink(source.link),
        frequency: POPUP_FREQUENCIES.has(frequency)
            ? frequency
            : HOME_POPUP_DEFAULTS.frequency,
        show_timing: source.show_timing === 'delay' ? 'delay' : 'page_ready',
        delay_seconds: clampInt(source.delay_seconds, 0, 30, 0),
        interval_days: clampInt(source.interval_days, 1, 365, 7),
        max_total_count: clampInt(source.max_total_count, 0, 999, 0),
        max_daily_count: clampInt(source.max_daily_count, 0, 99, 1),
        start_time: toText(source.start_time),
        end_time: toText(source.end_time),
        close_counts_as_shown: toInt01(source.close_counts_as_shown, 1),
        show_close: toInt01(source.show_close, 1)
    }
}

/** 弹窗是否有可展示的内容 */
export function isPopupConfigured(content: HomePopupAdContent): boolean {
    if (content.enabled !== 1) return false
    if (content.type === 'image') return !!content.image
    if (content.type === 'text') return !!content.title || !!content.content
    return !!content.image || !!content.title || !!content.content
}

/**
 * 弹窗展示判定。
 * @param state 与开屏共用同一套运行时状态结构（不同业务用不同 storage key 隔离）
 */
export function shouldShowHomePopup(
    content: HomePopupAdContent,
    state: SplashRuntimeState = SPLASH_STATE_EMPTY,
    now: Date = new Date()
): SplashDecision {
    if (!isPopupConfigured(content)) {
        return { show: false, reason: content.enabled === 1 ? 'no-image' : 'disabled' }
    }

    const nowTs = now.getTime()
    const startTs = parseDateTime(content.start_time)
    if (startTs !== null && nowTs < startTs) return { show: false, reason: 'not-started' }
    const endTs = parseDateTime(content.end_time)
    if (endTs !== null && nowTs > endTs) return { show: false, reason: 'ended' }

    const today = dateKey(now)

    switch (content.frequency) {
        case 'session':
            if (state.sessionShown) return { show: false, reason: 'session-shown' }
            break
        case 'daily':
            if (state.lastShownDate === today) return { show: false, reason: 'daily-shown' }
            break
        case 'once':
            if (state.totalCount > 0) return { show: false, reason: 'once-shown' }
            break
        case 'interval_days': {
            const days = Math.max(1, content.interval_days)
            if (state.lastShownAt > 0 && nowTs - state.lastShownAt < days * 86400000) {
                return { show: false, reason: 'interval-not-reached' }
            }
            break
        }
        case 'custom_limit':
        case 'every_home_entry':
        default:
            break
    }

    if (content.max_total_count > 0 && state.totalCount >= content.max_total_count) {
        return { show: false, reason: 'once-shown' }
    }

    if (content.max_daily_count > 0) {
        const used = state.lastShownDate === today ? state.todayCount : 0
        if (used >= content.max_daily_count) return { show: false, reason: 'daily-limit' }
    }

    return { show: true }
}

/* ------------------------------------------------------------------ *
 * 5.1 首页数据条（home-brand）
 * ------------------------------------------------------------------ */

export interface HomeBrandStat {
    value: string
    label: string
}

export interface HomeBrandContent {
    enabled: Int01
    stats: HomeBrandStat[]
}

/** 数据条固定 3 格：真机是三等分的一行，多出来的没有位置放 */
export const HOME_BRAND_MAX_STATS = 3

/**
 * 默认值刻意留空，不预写「1000+ / 98% / 30+」——
 * 装修未配置时真机整块不显示，比在新站首页编三个假数字更诚实。
 * 后台表单的 placeholder 仍会给示例，运营照着填即可。
 */
export const HOME_BRAND_DEFAULTS: HomeBrandContent = {
    enabled: 1,
    stats: [
        { value: '', label: '' },
        { value: '', label: '' },
        { value: '', label: '' }
    ]
}

export function normalizeHomeBrandContent(raw: unknown): HomeBrandContent {
    const source = (raw && typeof raw === 'object' ? raw : {}) as Record<string, any>
    const list = Array.isArray(source.stats) ? source.stats : []
    const stats: HomeBrandStat[] = []
    for (let i = 0; i < HOME_BRAND_MAX_STATS; i += 1) {
        const item = list[i]
        const row = (item && typeof item === 'object' ? item : {}) as Record<string, any>
        stats.push({
            value: toText(row.value),
            label: toText(row.label)
        })
    }
    return {
        enabled: toInt01(source.enabled, 1),
        stats
    }
}

/* ------------------------------------------------------------------ *
 * 5.2 首页四宫格入口（home-service-categories）
 * ------------------------------------------------------------------ */

export interface HomeServiceCategoryItem {
    /** '0' = 这个入口不显示（配置保留，随时可开回来） */
    is_show: '0' | '1'
    title: string
    image: string
    link: DecorateLink
}

export interface HomeServiceCategoriesContent {
    enabled: Int01
    data: HomeServiceCategoryItem[]
}

/**
 * 默认四条与小程序 `QUICK_FALLBACK` 完全一致（同文案、同路由），
 * 保证「装修没配过」和「装修配了默认值」看到的是同一屏。
 */
export const HOME_SERVICE_CATEGORIES_DEFAULTS: HomeServiceCategoriesContent = {
    enabled: 1,
    data: [
        {
            is_show: '1',
            title: '查档期',
            image: '',
            link: { path: '/pages/query/query' }
        },
        {
            is_show: '1',
            title: '选主持',
            image: '',
            link: { path: '/packages/pkg-catalog/pages/staff-list/staff-list' }
        },
        {
            is_show: '1',
            title: '真实案例',
            image: '',
            link: { path: '/packages/pkg-catalog/pages/case-list/case-list' }
        },
        {
            is_show: '1',
            title: '在线咨询',
            image: '',
            link: { path: '/pages/customer-service/customer-service' }
        }
    ]
}

export function normalizeHomeServiceCategoryItem(raw: unknown): HomeServiceCategoryItem {
    const source = (raw && typeof raw === 'object' ? raw : {}) as Record<string, any>
    return {
        is_show: String(source.is_show ?? '1') === '0' ? '0' : '1',
        title: toText(source.title),
        image: toText(source.image),
        link: normalizeLink(source.link)
    }
}

export function normalizeHomeServiceCategoriesContent(
    raw: unknown
): HomeServiceCategoriesContent {
    const source = (raw && typeof raw === 'object' ? raw : {}) as Record<string, any>
    const list = Array.isArray(source.data) ? source.data : []
    return {
        enabled: toInt01(source.enabled, 1),
        // 不合并默认值：运营删空就是删空，真机不该凭空冒出四条
        data: list.map((item) => normalizeHomeServiceCategoryItem(item))
    }
}

/* ------------------------------------------------------------------ *
 * 6. 首页首屏「图片上浮文字」（banner.content.data[0]）
 * ------------------------------------------------------------------ */

/**
 * 首屏主图的数据项。
 *
 * 首屏只有一张图，`data` 长度恒为 1（服务端保存时截断），
 * 不再保留 `is_show / bg / bg_color / name / slogan_top / link` 这些
 * 旧版轮播图时代、真机从未消费的字段。
 */
export interface HomeHeroItem {
    image: string
    slogan: string
    slogan_show: '0' | '1'
    slogan_color: string
    title: string
    title_show: '0' | '1'
    subtitle: string
    subtitle_show: '0' | '1'
    primary_text: string
    primary_show: '0' | '1'
    secondary_text: string
    secondary_show: '0' | '1'
    title_color: string
    subtitle_color: string
}

export interface HomeHeroContent {
    enabled: Int01
    data: HomeHeroItem[]
}

export const HOME_HERO_DEFAULTS: HomeHeroContent = {
    enabled: 1,
    data: [
        {
            image: '',
            slogan: '',
            slogan_show: '1',
            slogan_color: '',
            title: '',
            title_show: '1',
            subtitle: '',
            subtitle_show: '1',
            primary_text: '',
            primary_show: '1',
            secondary_text: '',
            secondary_show: '1',
            title_color: '',
            subtitle_color: ''
        }
    ]
}

function heroShowFlag(value: unknown): '0' | '1' {
    return String(value ?? '1') === '0' ? '0' : '1'
}

function normalizeHomeHeroItem(raw: unknown): HomeHeroItem {
    const source = (raw && typeof raw === 'object' ? raw : {}) as Record<string, any>
    return {
        image: toText(source.image),
        slogan: toText(source.slogan),
        slogan_show: heroShowFlag(source.slogan_show),
        slogan_color: toText(source.slogan_color),
        title: toText(source.title),
        title_show: heroShowFlag(source.title_show),
        subtitle: toText(source.subtitle),
        subtitle_show: heroShowFlag(source.subtitle_show),
        primary_text: toText(source.primary_text),
        primary_show: heroShowFlag(source.primary_show),
        secondary_text: toText(source.secondary_text),
        secondary_show: heroShowFlag(source.secondary_show),
        title_color: toText(source.title_color),
        subtitle_color: toText(source.subtitle_color)
    }
}

/**
 * 首屏主图归一化。
 *
 * 老数据里的 `data[0].is_show === '0'` 表示「运营关掉了这张图」，
 * 语义等价于「没配自定义首屏」→ 归一化成空 item，真机回落到内置主图 + 默认文案。
 */
export function normalizeHomeHeroContent(raw: unknown): HomeHeroContent {
    const source = (raw && typeof raw === 'object' ? raw : {}) as Record<string, any>
    const list = Array.isArray(source.data) ? source.data : []
    const first = list[0]
    const item = first && typeof first === 'object' ? (first as Record<string, any>) : {}
    const disabled = String(item.is_show ?? '1') === '0'

    return {
        enabled: toInt01(source.enabled, 1),
        data: [normalizeHomeHeroItem(disabled ? {} : item)]
    }
}

/** 真机 / 预览统一入口：widget 缺失或关闭时返回 null（页面走内置主图与默认文案） */
export function resolveHomeHeroItem(raw: unknown): HomeHeroItem | null {
    const content = normalizeHomeHeroContent(raw)
    if (content.enabled !== 1) return null
    return content.data[0] || null
}

/**
 * 运营未配置时的兜底文案。
 *
 * 后台装修预览与小程序**引用同一份**，避免「预览写死一份、真机写死另一份」。
 * 字段与 banner.content.data[0] 一一对应；留空一律回落到这里。
 */
export interface HomeHeroFallback {
    /** 宣传语（eyebrow）。留空时小程序还会再尝试品牌 Slogan，两者都空则整行不渲染 */
    slogan: string
    /** 主标题，`\n` 分行 */
    title: string
    /** 副标题 / 服务范围 */
    subtitle: string
    /** 主按钮文案 */
    primary_text: string
    /** 次按钮文案；留空时页面拼「了解 + 品牌名」 */
    secondary_text: string
}

export const HOME_HERO_FALLBACK: HomeHeroFallback = {
    slogan: '',
    title: '把一生一次的仪式\n交给值得信赖的声音',
    subtitle: '西式 · 中式 · 商务主持 · 全程统筹',
    primary_text: '查询档期',
    secondary_text: ''
}

/** Hero 主标题硬上限：超过 4 行会把 CTA 顶出首屏 */
export const HERO_TITLE_MAX_LINES = 4

/**
 * 主标题分行：空值走兜底，按 `\n` 拆分，逐行 trim 去空，最多 4 行。
 * 后台预览与小程序共用，保证断行一致。
 */
export function splitHeroTitle(raw: unknown, fallback: string = HOME_HERO_FALLBACK.title): string[] {
    const text = typeof raw === 'string' && raw.trim() ? raw : fallback
    const lines = text
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
        .slice(0, HERO_TITLE_MAX_LINES)
    return lines.length ? lines : fallback.split('\n')
}

/* ------------------------------------------------------------------ *
 * 7. 首页底部「服务承诺」（home-flow）
 * ------------------------------------------------------------------ */

export interface HomeFlowItem {
    /** 左侧时间标记，如 T-30 / T-01 / T-0 */
    tag: string
    title: string
    desc: string
    is_show: '0' | '1'
}

export interface HomeFlowContent {
    enabled: Int01
    title: string
    data: HomeFlowItem[]
}

/** 服务承诺默认值：与改造前小程序写死的三行逐字一致，升级后视觉不变 */
export const HOME_FLOW_DEFAULTS: HomeFlowContent = {
    enabled: 1,
    title: '一次交付，全程在场',
    data: [
        {
            tag: 'T-30',
            title: '流程梳理与台本共创',
            desc: '一对一访谈，把两个家庭的故事写成台上五分钟。',
            is_show: '1'
        },
        {
            tag: 'T-01',
            title: '全流程彩排',
            desc: '主持人到场走位，音乐、灯光、动线与伴郎伴娘逐一确认。',
            is_show: '1'
        },
        {
            tag: 'T-0',
            title: '提前两小时到场',
            desc: '全程在场控场，突发状况由主持人兜底，不让新人分心。',
            is_show: '1'
        }
    ]
}

/** 服务承诺步骤硬上限：超过 6 条首页会过长 */
export const HOME_FLOW_MAX_ITEMS = 6

function flowText(value: unknown, max: number): string {
    return typeof value === 'string' ? value.trim().slice(0, max) : ''
}

function cloneHomeFlowDefaults(): HomeFlowContent {
    return {
        enabled: HOME_FLOW_DEFAULTS.enabled,
        title: HOME_FLOW_DEFAULTS.title,
        data: HOME_FLOW_DEFAULTS.data.map((item) => ({ ...item }))
    }
}

/** 标题留空时回退默认标题，避免出现只有列表没有标题的区块 */
export function homeFlowTitle(raw: unknown): string {
    return flowText(raw, 24) || HOME_FLOW_DEFAULTS.title
}

/**
 * 归一化 home-flow.content。
 *
 * - widget 缺失（null / 非对象）→ 返回默认 3 条（克隆，不返回常量引用）；
 * - widget 存在但 data 为空 / 条目全被隐藏 → 返回空 data，由页面整块隐藏。
 */
export function normalizeHomeFlowContent(raw: unknown): HomeFlowContent {
    if (!raw || typeof raw !== 'object') return cloneHomeFlowDefaults()

    const source = raw as Record<string, any>
    const list = Array.isArray(source.data) ? source.data : []
    const data = list
        .filter((item: unknown) => !!item && typeof item === 'object')
        .slice(0, HOME_FLOW_MAX_ITEMS)
        .map((item: any) => ({
            tag: flowText(item.tag, 8),
            title: flowText(item.title, 32),
            desc: flowText(item.desc, 80),
            is_show: String(item.is_show ?? '1') === '0' ? ('0' as const) : ('1' as const)
        }))
        .filter((item) => !!item.title)

    return {
        enabled: Number(source.enabled) === 0 ? 0 : 1,
        title: homeFlowTitle(source.title),
        data
    }
}

/* ------------------------------------------------------------------ *
 * 8. 首页首屏上浮文字：显示开关 + 样式
 * ------------------------------------------------------------------ */

export interface HomeHeroStyle {
    /** 空串 = 用设计稿默认色，不写死具体色值 */
    title_color: string
    subtitle_color: string
}

export const HOME_HERO_STYLE_DEFAULTS: HomeHeroStyle = {
    title_color: '',
    subtitle_color: ''
}

export interface HomeHeroText {
    slogan: string
    titleLines: string[]
    subtitle: string
    primaryText: string
    secondaryText: string
    showSlogan: boolean
    showTitle: boolean
    showSubtitle: boolean
    showPrimary: boolean
    showSecondary: boolean
    style: HomeHeroStyle
}

/** 只有明确配成 '0' 才隐藏；未配置一律显示，老数据升级后视觉不变 */
function heroFlag(value: unknown): boolean {
    return String(value ?? '1') !== '0'
}

function heroText(value: unknown): string {
    return typeof value === 'string' ? value.trim() : ''
}

/**
 * 首屏上浮文字的最终形态：显示开关 + 兜底文案 + 样式。
 *
 * 后台预览与小程序都调这一个函数 —— 谁也别再自己拼一份 `||` 回退链，
 * 否则「预览有、真机没有」会再次出现。
 */
export function resolveHomeHeroText(
    item: unknown,
    options: { brandName?: string; brandSlogan?: string } = {}
): HomeHeroText {
    const raw = (item && typeof item === 'object' ? item : {}) as Record<string, any>
    const brandName = heroText(options.brandName)
    const brandSlogan = heroText(options.brandSlogan)

    // 宣传语的回退链：装修 → 站点 Slogan → 不显示
    const slogan = heroText(raw.slogan) || brandSlogan
    const subtitle = heroText(raw.subtitle) || HOME_HERO_FALLBACK.subtitle
    const primaryText = heroText(raw.primary_text) || HOME_HERO_FALLBACK.primary_text
    const secondaryText =
        heroText(raw.secondary_text) ||
        (brandName ? `了解${brandName}` : HOME_HERO_FALLBACK.secondary_text)

    return {
        slogan,
        titleLines: splitHeroTitle(raw.title),
        subtitle,
        primaryText,
        secondaryText,
        showSlogan: heroFlag(raw.slogan_show) && !!slogan,
        showTitle: heroFlag(raw.title_show),
        showSubtitle: heroFlag(raw.subtitle_show),
        showPrimary: heroFlag(raw.primary_show),
        showSecondary: heroFlag(raw.secondary_show) && !!secondaryText,
        style: {
            title_color: heroText(raw.title_color),
            subtitle_color: heroText(raw.subtitle_color)
        }
    }
}

/* ------------------------------------------------------------------ *
 * 9. 个人中心婚期卡片（wedding-countdown）
 * ------------------------------------------------------------------ */

/**
 * 婚期卡片的可配置文案。
 *
 * 数字（剩余天数）、待确认事项、场地都来自 `/v2/me`，不在装修里配；
 * 这里只保留运营真正会改的文案与总开关，避免后台出现「配了没反应」的字段。
 */
export interface WeddingCountdownContent {
    /** 总开关；关闭后个人中心不渲染婚期卡（含未填婚期引导） */
    enabled: Int01
    /** 卡片顶部英文小标 */
    label: string
    /** 有婚期时的单位文案，如「天后，就是我们的婚礼」 */
    unit_text: string
    /** 未填婚期时的标题 */
    empty_title: string
    /** 未填婚期时的说明 */
    empty_desc: string
    /** 未填婚期时的按钮文案 */
    empty_action_text: string
}

export const WEDDING_COUNTDOWN_DEFAULTS: WeddingCountdownContent = {
    enabled: 1,
    label: 'COUNTDOWN TO THE DAY',
    unit_text: '天后，就是我们的婚礼',
    empty_title: '还没预约',
    empty_desc: '预约档期后，这里会显示婚礼倒计时',
    empty_action_text: '去预约'
}

/** 婚期卡片内容归一化。永不抛异常，三端共用。 */
export function normalizeWeddingCountdownContent(raw: unknown): WeddingCountdownContent {
    const source = (raw && typeof raw === 'object' ? raw : {}) as Record<string, any>
    const emptyTitle = toText(source.empty_title)
    const emptyDesc = toText(source.empty_desc)
    const emptyAction = toText(source.empty_action_text)

    return {
        enabled: toInt01(source.enabled, WEDDING_COUNTDOWN_DEFAULTS.enabled),
        label: toText(source.label) || WEDDING_COUNTDOWN_DEFAULTS.label,
        unit_text: toText(source.unit_text) || WEDDING_COUNTDOWN_DEFAULTS.unit_text,
        // 旧默认值「还没填婚期 / 填上婚期… / 去填写」以手填为前提，统一迁到预约口径
        empty_title:
            !emptyTitle || emptyTitle === '还没填婚期'
                ? WEDDING_COUNTDOWN_DEFAULTS.empty_title
                : emptyTitle,
        empty_desc:
            !emptyDesc || emptyDesc === '填上婚期，我们帮你倒排筹备节奏'
                ? WEDDING_COUNTDOWN_DEFAULTS.empty_desc
                : emptyDesc,
        empty_action_text:
            !emptyAction || emptyAction === '去填写'
                ? WEDDING_COUNTDOWN_DEFAULTS.empty_action_text
                : emptyAction
    }
}

/* ------------------------------------------------------------------ *
 * 10. 个人中心「我的服务」快捷入口（quick-entry）
 * ------------------------------------------------------------------ */

export interface QuickEntryItem {
    /** 用于匹配实时统计（favorite / activity / waitlist…） */
    key: string
    title: string
    subtitle?: string
    /** 历史字段：图片图标地址；为空时前端按 key 回退内置图标 */
    icon?: string
    link: DecorateLink
    is_show: '0' | '1'
}

export interface QuickEntryContent {
    enabled: Int01
    /** 面板标题，默认「我的服务」 */
    title: string
    data: QuickEntryItem[]
}

/**
 * 默认值 = 个人中心现在硬编码的四行，接入后界面不变。
 * ⚠ 不要改回旧模板那 8 条（订单/活动/评价/通知…），会和「我的订单」面板重复。
 */
export const QUICK_ENTRY_DEFAULTS: QuickEntryContent = {
    enabled: 1,
    title: '我的服务',
    data: [
        {
            key: 'schedule',
            title: '我的档期',
            link: { path: '/pages/order/order' },
            is_show: '1'
        },
        {
            key: 'favorite',
            title: '我的收藏',
            link: { path: '/packages/pkg-user/pages/favorite/favorite' },
            is_show: '1'
        },
        {
            key: 'activity',
            title: '我的活动',
            link: { path: '/packages/pkg-user/pages/activity/activity' },
            is_show: '1'
        },
        {
            key: 'consult',
            title: '我的咨询',
            link: { path: '/packages/pkg-service/pages/concierge/concierge' },
            is_show: '1'
        }
    ]
}

/** 旧模板默认的 8 条；检测到就迁到新默认，避免真机突然多出一排重复入口 */
const LEGACY_QUICK_ENTRY_KEYS = [
    'order',
    'activity',
    'review',
    'notification',
    'favorite',
    'aftersale',
    'waitlist',
    'settings'
]

function normalizeQuickEntryItem(raw: unknown): QuickEntryItem | null {
    if (!raw || typeof raw !== 'object') return null
    const source = raw as Record<string, any>
    const title = toText(source.title)
    if (!title) return null

    return {
        key: toText(source.key),
        title,
        subtitle: toText(source.subtitle) || undefined,
        icon: toText(source.icon) || undefined,
        link: normalizeLink(source.link),
        is_show: String(source.is_show ?? '1') === '0' ? '0' : '1'
    }
}

export function normalizeQuickEntryContent(raw: unknown): QuickEntryContent {
    const source = (raw && typeof raw === 'object' ? raw : {}) as Record<string, any>
    const rawList = Array.isArray(source.data) ? source.data : []
    const list = rawList
        .map((item) => normalizeQuickEntryItem(item))
        .filter((item): item is QuickEntryItem => !!item)

    const isLegacyDefault =
        list.length === LEGACY_QUICK_ENTRY_KEYS.length &&
        LEGACY_QUICK_ENTRY_KEYS.every((key, index) => list[index].key === key)

    return {
        enabled: toInt01(source.enabled, QUICK_ENTRY_DEFAULTS.enabled),
        title: toText(source.title) || QUICK_ENTRY_DEFAULTS.title,
        data: (!list.length || isLegacyDefault
            ? QUICK_ENTRY_DEFAULTS.data.map((item) => ({ ...item, link: { ...item.link } }))
            : list)
    }
}
