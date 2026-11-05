/**
 * 微信支付封装。
 *
 * 铁律：requestPayment 的 success 只代表「用户完成了支付动作」，
 * 不代表订单已支付成功 —— 微信服务端回调可能晚几秒。
 * 业务判定必须走 /pay/payStatus 轮询。
 */

export type PayResult = 'success' | 'cancel' | 'fail'

export function requestPayment(config: Record<string, any>): Promise<PayResult> {
    return new Promise((resolve) => {
        uni.requestPayment({
            provider: 'wxpay',
            ...(config as any),
            success: () => resolve('success'),
            fail: (err: any) => {
                const msg = String(err?.errMsg || '')
                if (msg.includes('cancel')) resolve('cancel')
                else resolve('fail')
            }
        })
    })
}

/** 轮询支付结果：间隔递增，避免密集打接口 */
export const PAY_POLL_INTERVALS = [2, 4, 8, 15, 15, 30]

export function sleep(ms: number) {
    return new Promise((resolve) => setTimeout(resolve, ms))
}
