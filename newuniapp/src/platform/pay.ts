/**
 * 微信支付封装。
 *
 * 铁律：requestPayment 的 success 只代表「用户完成了支付动作」，
 * 不代表订单已支付成功 —— 微信服务端回调可能晚几秒。
 * 业务判定必须走 /pay/payStatus 轮询。
 *
 * 测试环境：后端开启支付沙箱时，prepay 返回的 config 带 `mock: true`，
 * 这里改为弹出本地测试面板，不调用 wx.requestPayment。
 */

import { mockConfirmPay } from '@/api/pay'
import type { MockPayConfig } from '@/api/types/pay'

export type PayResult = 'success' | 'cancel' | 'fail'

/** 识别后端沙箱预支付配置：mock 标记 + 流水号 + 业务来源三者齐全 */
function isMockPayConfig(config: Record<string, any>): config is MockPayConfig {
    return (
        config?.mock === true &&
        typeof config?.payment_sn === 'string' &&
        config.payment_sn.length > 0 &&
        typeof config?.from === 'string'
    )
}

/** 沙箱确认失败只做轻提示，不阻断页面既有的轮询 / 重试逻辑 */
function toastMockError(err: any) {
    uni.showToast({ title: String(err?.message || '沙箱确认失败'), icon: 'none' })
}

/** 沙箱支付面板：5 个选项覆盖成功 / 延迟回调 / 待确认 / 取消 / 失败 */
function requestMockPayment(config: MockPayConfig): Promise<PayResult> {
    return new Promise((resolve) => {
        const amount = Number(config.amount ?? 0)
        const amountText = Number.isFinite(amount) && amount > 0 ? `¥${amount.toFixed(2)}` : ''
        uni.showActionSheet({
            alertText: ['测试沙箱支付', amountText, config.subject].filter(Boolean).join(' · '),
            itemList: [
                '模拟支付成功（立即到账）',
                '模拟支付成功（延迟4秒·测轮询）',
                '模拟仅回跳不回调（测待确认）',
                '模拟用户取消支付',
                '模拟支付失败'
            ],
            success: (res) => {
                const action = Number(res.tapIndex)
                if (action === 0) {
                    mockConfirmPay({ from: config.from, payment_sn: config.payment_sn })
                        .then(() => resolve('success'))
                        .catch((err: any) => {
                            toastMockError(err)
                            resolve('fail')
                        })
                    return
                }
                if (action === 1) {
                    // 延迟回调：页面先进入「支付结果确认中」轮询态，4 秒后自动切成功
                    setTimeout(() => {
                        mockConfirmPay({ from: config.from, payment_sn: config.payment_sn }).catch(
                            () => undefined
                        )
                    }, 4000)
                    resolve('success')
                    return
                }
                if (action === 2) {
                    // 只回跳不回调：验证轮询超时进入 unconfirmed 与「刷新结果」
                    resolve('success')
                    return
                }
                if (action === 3) {
                    resolve('cancel')
                    return
                }
                resolve('fail')
            },
            fail: () => resolve('cancel')
        })
    })
}

export function requestPayment(config: Record<string, any>): Promise<PayResult> {
    if (isMockPayConfig(config)) {
        return requestMockPayment(config)
    }
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
