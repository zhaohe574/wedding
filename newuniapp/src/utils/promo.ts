import {
    markSplashShown,
    normalizeHomePopupContent,
    shouldShowHomePopup,
    type HomePopupAdContent,
    type SplashDecision,
    type SplashRuntimeState
} from '@shared/contracts'

/**
 * 首页弹窗广告的频次状态。
 *
 * 与开屏广告分开存（不同 storage key、不同 session 标记），
 * 否则「开屏展示过」会把「弹窗每会话一次」直接吃掉。
 */

const STATE_KEY = 'home_popup_state'

interface PromoStateCache {
    lastShownAt: number
    totalCount: number
    lastShownDate: string
    todayCount: number
}

let sessionShown = false

export function readPopupState(): SplashRuntimeState {
    let cached: PromoStateCache | null = null
    try {
        const raw = uni.getStorageSync(STATE_KEY)
        const data = raw ? (typeof raw === 'string' ? JSON.parse(raw) : raw) : null
        cached = data && typeof data === 'object' ? (data as PromoStateCache) : null
    } catch {
        cached = null
    }

    return {
        lastShownAt: Number(cached?.lastShownAt) || 0,
        totalCount: Number(cached?.totalCount) || 0,
        lastShownDate: typeof cached?.lastShownDate === 'string' ? cached.lastShownDate : '',
        todayCount: Number(cached?.todayCount) || 0,
        sessionShown
    }
}

export function commitPopupShown(): void {
    const next = markSplashShown(readPopupState())
    sessionShown = true
    try {
        const payload: PromoStateCache = {
            lastShownAt: next.lastShownAt,
            totalCount: next.totalCount,
            lastShownDate: next.lastShownDate,
            todayCount: next.todayCount
        }
        uni.setStorageSync(STATE_KEY, JSON.stringify(payload))
    } catch {
        // 存储不可写时只影响频次记忆，不影响本次展示
    }
}

/** 归一化 + 判定一步到位，页面不用自己拼规则 */
export function resolvePopup(raw: unknown): {
    content: HomePopupAdContent
    decision: SplashDecision
} {
    const content = normalizeHomePopupContent(raw)
    return { content, decision: shouldShowHomePopup(content, readPopupState()) }
}
