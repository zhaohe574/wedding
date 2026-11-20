import {
    SPLASH_STATE_EMPTY,
    markSplashShown,
    normalizeSplashContent,
    parseDecoratePage,
    shouldShowSplash,
    type SplashAdContent,
    type SplashDecision,
    type SplashRuntimeState
} from '@shared/contracts'
import { getDecorate } from '@/api/home'
import { report } from '@/platform/report'

/**
 * 开屏广告运行时：配置缓存 + 频次状态 + 判定。
 *
 * 为什么配置要落本地缓存：
 *   开屏页是小程序首页（pages[0]），必须在**首帧之前**就决定「展示广告还是直接进首页」。
 *   等网络回来再决定，用户会先看到一次白屏或先看到首页再被广告盖住。
 *   所以：冷启动先用上次缓存的配置判定（同步、零延迟），本次请求只负责更新下一次。
 *
 * 状态分层：
 *   - 持久化：lastShownAt / totalCount / lastShownDate / todayCount（uni storage）
 *   - 进程内：sessionShown（模块变量，小程序被杀进程后自动重置）
 *   两者合成 SplashRuntimeState 交给 shared 的纯函数判定。
 */

const CONFIG_KEY = 'splash_config'
const STATE_KEY = 'splash_state'
/**
 * 配置缓存版本。
 * v2（2026-11-19）：接口开始下发绝对素材地址，旧缓存里的相对 uri 在开屏阶段
 * 拿不到 domain（/index/config 还没回来），直接丢弃重拉。
 */
const CONFIG_VERSION = 2

interface SplashConfigCache {
    v: number
    savedAt: number
    content: SplashAdContent
}

interface SplashStateCache {
    lastShownAt: number
    totalCount: number
    lastShownDate: string
    todayCount: number
}

/** 本次进程内是否已经展示过（不落盘） */
let sessionShown = false

function readJson<T>(key: string): T | null {
    try {
        const raw = uni.getStorageSync(key)
        if (!raw) return null
        const data = typeof raw === 'string' ? JSON.parse(raw) : raw
        return data && typeof data === 'object' ? (data as T) : null
    } catch {
        return null
    }
}

function writeJson(key: string, value: unknown): void {
    try {
        uni.setStorageSync(key, JSON.stringify(value))
    } catch {
        // 存储不可写不影响本次展示，下次冷启动退回「不展示」
    }
}

/** 读上次缓存的开屏配置（同步，用于首帧判定） */
export function readSplashConfig(): SplashAdContent | null {
    const cache = readJson<SplashConfigCache>(CONFIG_KEY)
    if (!cache || cache.v !== CONFIG_VERSION || !cache.content) return null
    return normalizeSplashContent(cache.content)
}

function writeSplashConfig(content: SplashAdContent): void {
    const payload: SplashConfigCache = {
        v: CONFIG_VERSION,
        savedAt: Date.now(),
        content
    }
    writeJson(CONFIG_KEY, payload)
}

/** 读频次状态（持久化部分 + 本次会话标记） */
export function readSplashState(): SplashRuntimeState {
    const cached = readJson<SplashStateCache>(STATE_KEY)
    return {
        lastShownAt: Number(cached?.lastShownAt) || 0,
        totalCount: Number(cached?.totalCount) || 0,
        lastShownDate: typeof cached?.lastShownDate === 'string' ? cached.lastShownDate : '',
        todayCount: Number(cached?.todayCount) || 0,
        sessionShown
    }
}

/** 记录一次曝光：持久化计数 + 置位会话标记 */
export function commitSplashShown(): void {
    const next = markSplashShown(readSplashState())
    sessionShown = true
    const payload: SplashStateCache = {
        lastShownAt: next.lastShownAt,
        totalCount: next.totalCount,
        lastShownDate: next.lastShownDate,
        todayCount: next.todayCount
    }
    writeJson(STATE_KEY, payload)
}

/**
 * 拉取开屏配置并写缓存。
 * 失败静默（开屏不能拖累启动），返回 null 由调用方走「不展示」分支。
 */
export async function fetchSplashConfig(): Promise<SplashAdContent | null> {
    try {
        const page = await getDecorate(6)
        // data 可能是 JSON 字符串也可能是数组（服务端按入库形态原样下发），
        // 统一交给 parseDecoratePage 解析，不在这里各写一份兼容逻辑。
        const parsed = parseDecoratePage(page?.data, page?.meta)
        const widget = parsed.widgets.find((item) => item.name === 'splash-ad')
        if (!widget) return null
        const content = normalizeSplashContent(widget.content)
        writeSplashConfig(content)
        return content
    } catch (error) {
        report('api_error', {
            endpoint: 'index.decorate',
            message: '开屏广告配置加载失败'
        })
        return null
    }
}

/** 用「配置 + 状态」判定是否展示（纯逻辑在 shared，三端一致） */
export function resolveSplash(content: SplashAdContent | null): SplashDecision {
    if (!content) return { show: false, reason: 'disabled' }
    return shouldShowSplash(content, readSplashState())
}

/** 调试用：清空频次状态（设置页/开发者菜单可调用） */
export function resetSplashState(): void {
    sessionShown = false
    try {
        uni.removeStorageSync(STATE_KEY)
    } catch {
        // ignore
    }
}

/**
 * 本进程是否还应该处理「冷启动开屏」。
 *
 * 首页是 pages[0] 之后，每次首页实例重建都会走到这里；用模块级标记保证
 * 只有进程内第一次真正冷启动展示，登录回跳、reLaunch 回首页都不再重新盖开屏。
 *
 * 分享卡片 / 扫码进入首页时保留原约定：不展示开屏，直接看目标内容。
 * 场景值来自微信启动参数，取不到时按正常启动处理。
 */
const SPLASH_BYPASS_SCENES = new Set([1007, 1008, 1011, 1012, 1013, 1014, 1044, 1089, 1090])
let splashEntryConsumed = false

export function claimSplashEntry(): boolean {
    if (splashEntryConsumed) return false
    splashEntryConsumed = true

    // #ifdef MP-WEIXIN
    try {
        const api = uni as any
        const options =
            (typeof api.getEnterOptionsSync === 'function' && api.getEnterOptionsSync()) ||
            (typeof api.getLaunchOptionsSync === 'function' && api.getLaunchOptionsSync()) ||
            null
        if (options && SPLASH_BYPASS_SCENES.has(Number(options.scene))) return false
    } catch {
        // 启动参数不可用：按普通冷启动处理，不影响开屏判定
    }
    // #endif

    return true
}

export { SPLASH_STATE_EMPTY }
