import { ref, type Ref } from 'vue'

/**
 * 写操作防重复提交 composable（08 下单支付、11 问卷、19 候补、23 售后、15 活动报名）。
 *
 * 为什么不能只用一个 `submitting` ref：
 *   设计稿 08 屏的「微信支付」是底部固定实心按钮，误触率最高。用户点两下时，第二次点击
 *   发生在第一次 await 返回之前。若只有 `if (submitting) return`，两次调用会在同一个 tick 里
 *   都通过判断（取决于事件循环），最坏情况发出两次 prepay → 两条 payment_sn。
 *
 * 三层保护：
 *   1. `pending` 复用：在飞期间再点直接返回同一个 Promise（连点不产生新请求）
 *   2. `lockedUntil` 冷却窗口：失败/完成后 N 毫秒内忽略点击（覆盖「抬起手指再点一次」）
 *   3. `submitting`：给按钮 disabled / loading 用
 *
 * 与请求层 `idempotent: true` 的关系：请求层负责「同一次调用的重试不重复建单」，
 * 本 composable 负责「用户手抖的第二次调用根本不发」。两者都要有。
 */

export interface UseIdempotentSubmitOptions {
    /** 真正提交的动作；抛错视为失败，会自动解锁 */
    action: () => Promise<void>
    /** 冷却毫秒，默认 1200 */
    cooldown?: number
    /** 失败回调（请求层已 toast 时这里只做埋点；默认吞掉异常，避免模板里 await 丢失导致未捕获拒绝） */
    onError?: (error: unknown) => void
}

export interface UseIdempotentSubmitReturn {
    /** 给按钮的 disabled / loading */
    submitting: Ref<boolean>
    /** 直接绑 @click */
    run: () => Promise<void>
}

export function useIdempotentSubmit(options: UseIdempotentSubmitOptions): UseIdempotentSubmitReturn {
    const submitting = ref(false)
    const cooldown = options.cooldown ?? 1200

    let pending: Promise<void> | null = null
    let lockedUntil = 0

    function run(): Promise<void> {
        if (pending) return pending
        if (Date.now() < lockedUntil) return Promise.resolve()

        submitting.value = true
        pending = (async () => {
            try {
                await options.action()
            } catch (error) {
                options.onError?.(error)
            } finally {
                submitting.value = false
                lockedUntil = Date.now() + cooldown
                pending = null
            }
        })()

        return pending
    }

    return { submitting, run }
}
