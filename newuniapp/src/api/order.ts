import request from '@/request'
import type { OrderDetailResponse, OrderListItem, OrderStats, BookingPreviewResponse } from '@shared/contracts'

/**
 * 订单域。
 *
 * 关键时序（来自后端 OrderPayLogic::getPayableOrder）：
 *   create → status=0 待确认 → 商家确认或 1 小时自动确认 → status=1 待支付 → 才能 prepay
 * 所以客户端不能用「下单成功就去支付」，必须读详情里的 actions.pay。
 */

export interface OrderListParams {
    page?: number
    page_size?: number
    /** 单值或逗号分隔多值（v2 起支持多值） */
    status?: number | string
    keyword?: string
    [k: string]: any
}

export interface OrderCreateParams {
    contact_name: string
    contact_mobile: string
    service_address?: string
    remark?: string
    quote_hash?: string
    [k: string]: any
}

export interface OrderCreateResult {
    id?: number
    order_id?: number
    order_sn?: string
    need_pay?: string
    current_pay_stage?: string
}

/** 订单列表（分页风格 B：page + data） @endpoint order.lists */
export function getOrderList(params: OrderListParams) {
    return request.get<any>('/order/lists', params, { auth: true, silent: true })
}

/** 订单详情聚合（v2，1 次请求替代 5 次） @endpoint v2.order */
export function getOrderDetailV2(id: number) {
    return request.get<OrderDetailResponse>('/v2/order', { id }, { auth: true, silent: true })
}

/** 旧版订单详情（v2 未上线时降级） @endpoint order.detail */
export function getOrderDetail(params: { id: number }) {
    return request.get<any>('/order/detail', params, { auth: true, silent: true })
}

/** 下单预检：报价 + 结构化校验 + 报价指纹 @endpoint v2.booking.preview */
export function previewBooking(data: Record<string, any>) {
    return request.post<BookingPreviewResponse>('/v2/booking/preview', data, { auth: true, dedupe: false })
}

/** 旧版预览（v2 未上线时降级） @endpoint order.preview */
export function previewOrder(data: Record<string, any>) {
    return request.post<any>('/order/preview', data, { auth: true, dedupe: false })
}

/** 创建订单：必须带幂等键，防止连点建重复单 @endpoint order.create */
export function createOrder(data: OrderCreateParams) {
    return request.post<OrderCreateResult>('/order/create', data, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}

/** @endpoint order.cancel */
export function cancelOrder(data: { id: number; reason?: string }) {
    return request.post<null>('/order/cancel', data, { auth: true, loading: true })
}

/*
 * 以下接口已于 2026-11 随契约收窄一并移除（客户端零引用，且小程序不提供对应自助入口）：
 *   order.confirm       用户确认完成
 *   order.delete        删除订单
 *   order.uploadVoucher 线下支付凭证上传
 *   order.applyRefund   申请退款
 *   order.refundDetail  退款详情
 * 若后续恢复自助能力，从端点契约 contracts/endpoints.json 重新生成即可。
 */

/** 订单状态计数（11 个原始状态，客户端收敛为 6 组） @endpoint order.statistics */
export function getOrderStatistics() {
    return request.get<Record<string, number> & OrderStats>('/order/statistics', {}, { auth: true, silent: true })
}

export type { OrderListItem, OrderDetailResponse, OrderStats }
