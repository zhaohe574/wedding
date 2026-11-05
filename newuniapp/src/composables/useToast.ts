/**
 * 全局 Toast 单例（体验规范 §4.5 / 视觉规范 §7.12；主理人裁决 O13）。
 * 来源：docs/redesign/03-组件样式清单与验收.md §3.2 N5。
 *
 * 契约：单例 state ref + show/hide + 同 key 800ms 去重；不承载页面状态、
 * 不含操作按钮、不阻断交互、不发起请求。业务 uni.showToast 散调用的收编
 * 属于后续页面批次（T-27~T-36 / T-37），本文件只提供能力。
 */
import { ref } from 'vue'
import { FB } from '@/constants/feedback'
import { sanitizeServerMessage } from '@/utils/resolveErrorView'

export type ToastType = 'success' | 'error' | 'warning' | 'info' | 'loading'
export interface ToastState {
    visible: boolean
    text: string
    type: ToastType
    duration: number
}

const state = ref<ToastState>({ visible: false, text: '', type: 'info', duration: 2000 })
const lastFired = new Map<string, number>()
let timer: ReturnType<typeof setTimeout> | null = null

export function useToast() {
    function show(text: string, opts: { type?: ToastType; duration?: number; key?: string } = {}) {
        const type = opts.type ?? 'info'
        const key = opts.key ?? `${type}:${text}`
        const now = Date.now()
        if (now - (lastFired.get(key) ?? 0) < 800) return   // 同一事件去重
        lastFired.set(key, now)
        if (timer) clearTimeout(timer)
        state.value = {
            visible: true,
            text,
            type,
            duration: type === 'error' ? 2500 : (opts.duration ?? 2000)
        }
        timer = setTimeout(hide, state.value.duration)
    }
    function hide() {
        state.value = { ...state.value, visible: false }
        timer = null
    }
    return { state, show, hide }
}

/**
 * 便捷方法：全站替换 uni.showToast({ title: '...' })。
 * O13：error 文案统一经 sanitizeServerMessage 清洗，不暴露 HTTP 码 / 接口信息 / request_id。
 */
export const toast = {
    success: (t: string) => useToast().show(t || FB.TOAST_SUCCESS, { type: 'success' }),
    error: (t: string) => useToast().show(sanitizeServerMessage(t, FB.ERR_SERVER), { type: 'error' }),
    info: (t: string) => useToast().show(t, { type: 'info' }),
    warning: (t: string) => useToast().show(t, { type: 'warning' }),
    loading: (t: string) => useToast().show(t || FB.LOADING, { type: 'loading' }),
    hide: () => useToast().hide()
}
