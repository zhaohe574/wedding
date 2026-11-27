import { onMounted, onUnmounted, ref, watch } from 'vue'
import type { Ref } from 'vue'

/**
 * 数字递增回退方案（01 总方案 §3.7）。
 *
 * 只用于「首次进入 / 数据到位」一次，不用于每秒倒计时。
 * 主方案是 `<rolling-number>` 的 GPU 滚轮；这里给 Canvas、非滚轮场景兜底。
 * 缓动固定 easeOutQuint（1 - (1-p)^5），与滚轮的长尾指数减速同观感。
 */

export interface CountUpOptions {
    /** 总时长（ms），默认 1100 */
    duration?: number
    /** 保留小数位，默认 0 */
    decimals?: number
}

const DEFAULT_DURATION = 1100

function roundTo(value: number, decimals: number): number {
    const factor = Math.pow(10, decimals)
    return Math.round(value * factor) / factor
}

/** H5 读 matchMedia；小程序没有该 API，直接返回 false（走动画） */
function prefersReducedMotion(): boolean {
    try {
        const matchMedia = (globalThis as any).matchMedia
        return typeof matchMedia === 'function' ? !!matchMedia('(prefers-reduced-motion: reduce)').matches : false
    } catch {
        return false
    }
}

/** 小程序运行时可能没有 rAF：退化为 16ms 定时器，语义一致 */
function scheduleFrame(cb: () => void): number {
    if (typeof requestAnimationFrame === 'function') return requestAnimationFrame(cb)
    return setTimeout(cb, 16) as unknown as number
}

function cancelFrame(id: number): void {
    if (typeof cancelAnimationFrame === 'function') cancelAnimationFrame(id)
    else clearTimeout(id as unknown as ReturnType<typeof setTimeout>)
}

export function useCountUp(target: Ref<number>, opts: CountUpOptions = {}) {
    const duration = opts.duration ?? DEFAULT_DURATION
    const decimals = opts.decimals ?? 0
    const display = ref(roundTo(Number(target.value) || 0, decimals))

    let frame = 0
    let disposed = false

    function stop() {
        if (!frame) return
        cancelFrame(frame)
        frame = 0
    }

    function run() {
        if (disposed) return
        stop()

        const to = Number(target.value) || 0
        const from = Number(display.value) || 0

        // 减弱动效：直接赋终值，不注册任何逐帧回调
        if (prefersReducedMotion() || duration <= 0) {
            display.value = roundTo(to, decimals)
            return
        }

        const start = Date.now()
        const tick = () => {
            if (disposed) return
            const progress = Math.min(1, (Date.now() - start) / duration)
            const eased = 1 - Math.pow(1 - progress, 5)
            display.value = roundTo(from + (to - from) * eased, decimals)
            if (progress < 1) {
                frame = scheduleFrame(tick)
            } else {
                display.value = roundTo(to, decimals)
                frame = 0
            }
        }
        frame = scheduleFrame(tick)
    }

    onMounted(run)
    watch(target, run)
    onUnmounted(() => {
        disposed = true
        stop()
    })

    return display
}
