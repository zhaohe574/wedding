import { ref, type Ref } from 'vue'

/**
 * 轮询 composable（09 支付结果、15 活动报名支付、20 候补倒计时对齐）。
 *
 * 铁律（沿用现网 payment-result 已正确的做法，新页面不许改）：
 *   1. 轮询超时只能得出 **unconfirmed**（本 composable 的 `result = 'timeout'`），
 *      **绝不能据此判定失败** —— 微信回调可能晚到。
 *   2. 间隔递增，避免密集打接口；用完即停，不做无限轮询。
 *   3. 页面 onUnload 必须调用 stop()，否则离开页面还在打接口。
 *   4. task 抛错视为「本次没查到」，继续下一轮（网络抖动不该终止轮询）。
 */

export interface UsePollingOptions {
    /** 间隔秒数数组，例如 [1, 2, 3, 5, 8, 13]；跑完即 timeout */
    intervals: number[]
    /** 返回 true 表示达成终态，停止轮询 */
    task: () => Promise<boolean>
    /** 达成终态 */
    onDone?: () => void
    /** 间隔用尽仍未达成（注意：这**不是**失败） */
    onTimeout?: () => void
}

export type PollingResult = 'done' | 'timeout' | 'aborted' | null

export interface UsePollingReturn {
    running: Ref<boolean>
    /** 已执行轮数，用于「正在确认第 3/6 次」 */
    attempt: Ref<number>
    result: Ref<PollingResult>
    start: () => Promise<void>
    stop: () => void
}

function delay(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms))
}

export function usePolling(options: UsePollingOptions): UsePollingReturn {
    const running = ref(false)
    const attempt = ref(0)
    const result = ref<PollingResult>(null) as Ref<PollingResult>

    /** 令牌：每次 start/stop 自增，旧循环看到不匹配即退出 */
    let token = 0

    async function start() {
        if (running.value) return
        running.value = true
        result.value = null
        attempt.value = 0
        const my = ++token

        for (const seconds of options.intervals) {
            const wait = Math.max(0, Number(seconds) || 0) * 1000
            if (wait > 0) await delay(wait)
            if (my !== token) {
                running.value = false
                return
            }

            attempt.value += 1
            let done = false
            try {
                done = await options.task()
            } catch {
                // 单次失败继续轮询：查不到 ≠ 没付
                done = false
            }
            if (my !== token) {
                running.value = false
                return
            }
            if (done) {
                result.value = 'done'
                running.value = false
                options.onDone?.()
                return
            }
        }

        if (my === token) {
            result.value = 'timeout'
            running.value = false
            options.onTimeout?.()
        }
    }

    function stop() {
        token += 1
        if (running.value) result.value = 'aborted'
        running.value = false
    }

    return { running, attempt, result, start, stop }
}
