import { BACK_URL } from '@/enums/constantEnums'
import cache from '@/utils/cache'

export type PageRecoveryKind = 'network' | 'auth' | 'empty' | 'business' | 'unknown'

export interface PageRecoveryError {
    kind: PageRecoveryKind
    title: string
    message: string
    actionText: string
}

const AUTH_PATTERNS = ['token', '登录', '登陆', '未授权', '请先登录', '认证', '身份']
const NETWORK_PATTERNS = ['timeout', 'timed out', 'fail', 'failed', 'request:fail', 'network', '网络', '超时', '断开']

const toText = (error: unknown, fallback = '页面加载失败，请稍后重试') => {
    if (typeof error === 'string') {
        return error || fallback
    }

    const value = error as any
    return String(value?.msg || value?.message || value?.errMsg || value?.data?.msg || fallback)
}

export const normalizePageRecoveryError = (
    error: unknown,
    fallback = '页面加载失败，请稍后重试'
): PageRecoveryError => {
    const message = toText(error, fallback)
    const lowerMessage = message.toLowerCase()

    if (AUTH_PATTERNS.some((keyword) => lowerMessage.includes(keyword.toLowerCase()))) {
        return {
            kind: 'auth',
            title: '登录状态已失效',
            message: '请重新登录后再继续查看或操作。',
            actionText: '去登录'
        }
    }

    if (NETWORK_PATTERNS.some((keyword) => lowerMessage.includes(keyword.toLowerCase()))) {
        return {
            kind: 'network',
            title: '网络连接异常',
            message: '当前网络不稳定，请检查连接后重试。',
            actionText: '重新加载'
        }
    }

    if (!message || message === fallback) {
        return {
            kind: 'unknown',
            title: '页面加载失败',
            message: fallback,
            actionText: '重新加载'
        }
    }

    return {
        kind: 'business',
        title: '暂时无法展示',
        message,
        actionText: '重新加载'
    }
}

export const goLoginWithBack = (backUrl?: string) => {
    if (backUrl) {
        cache.set(BACK_URL, backUrl)
    }

    uni.navigateTo({ url: '/pages/login/login' })
}

export const goHome = () => {
    uni.switchTab({ url: '/pages/index/index' })
}

export const goOrderList = () => {
    uni.reLaunch({ url: '/pages/order/order' })
}

export const goBackOrHome = () => {
    if (getCurrentPages().length > 1) {
        uni.navigateBack()
        return
    }

    goHome()
}
