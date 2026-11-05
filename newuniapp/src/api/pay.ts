import request from '@/request'

/**
 * 支付域。
 * prepay 返回的 config 直接喂给 wx.requestPayment，不要自己拼字段。
 * 支付结果一律以 payStatus 轮询为准 —— 微信回调可能晚于 requestPayment 的 success。
 */

export interface PayWayItem {
    pay_way: number
    pay_way_desc: string
    is_default?: number
}

export interface PayWayResult {
    lists: PayWayItem[]
    need_pay: string
    need_pay_amount: number | string
    pay_remain_seconds?: number
}

export interface PrepayResult {
    config: Record<string, any>
    pay_way: number
    payment_sn: string
}

/** @endpoint pay.payWay */
export function getPayWay(data: { id: number; [k: string]: any }) {
    return request.get<PayWayResult>('/pay/payWay', data, { auth: true, silent: true })
}

/** 生成预支付单（必须幂等，否则会生成多个 payment_sn） @endpoint pay.prepay */
export function prepay(data: { id: number; pay_way?: number; [k: string]: any }) {
    return request.post<PrepayResult>('/pay/prepay', data, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}

/** 支付状态轮询（唯一可信来源） @endpoint pay.payStatus */
export function getPayStatus(data: { id: number; payment_sn?: string }) {
    return request.get<any>('/pay/payStatus', data, { auth: true, silent: true, retry: 0 })
}

/** 历史别名，兼容 payment 组件 */
export const getPayResult = getPayStatus
