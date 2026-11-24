import request from '@/request'
import type { OrderDetailResponse, OrderListItem, OrderStats, BookingPreviewResponse } from '@shared/contracts'
import type { PendingReselectItem, ReselectCandidate, V2OrderResponse } from './types'

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

/**
 * 订单详情聚合（v2，1 次请求替代 5 次）。
 *
 * 返回类型用 `V2OrderResponse`：`actions` 补齐了 shared 里缺的 6 个键（含 `apply_refund`）。
 * **注意 v2 不含 `refundable_amount`**，可退金额要读 v1 `getOrderDetail()` 根层或 `getRefundDetail()`。
 * @endpoint v2.order
 */
export function getOrderDetailV2(id: number) {
    return request.get<V2OrderResponse>('/v2/order', { id }, { auth: true, silent: true })
}

/** 旧版订单详情（v2 未上线时降级） @endpoint order.detail */
export function getOrderDetail(params: { id: number }) {
    return request.get<any>('/order/detail', params, { auth: true, silent: true })
}

/**
 * 下单预检：报价 + 结构化校验 + 报价指纹
 *
 * ⚠ 路径修正（C-02）：契约 `endpoints.json` 里 `v2.bookingPreview → /api/v2/bookingPreview`。
 * 此前写成 `/v2/booking/preview`，请求必 404；而 404 曾触发全局 v2 熔断，
 * 把 v2.home / v2.me / v2.staff / v2.order 一起关掉。现路径已对齐契约。
 * @endpoint v2.bookingPreview
 */
export function previewBooking(data: Record<string, any>) {
    return request.post<BookingPreviewResponse>('/v2/bookingPreview', data, { auth: true, dedupe: false })
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

/**
 * 协作人员取消后，拉取待重选服务项与同档期候选人。
 * @endpoint order.reselectCandidates
 */
export function getOrderReselectCandidates(params: { order_id: number; order_item_id: number }) {
    return request.get<{ item: PendingReselectItem; candidates: ReselectCandidate[] }>(
        '/order/reselectCandidates',
        params,
        { auth: true, silent: true }
    )
}

/** 为用户重新挑选协作服务人员 @endpoint order.reselectCollaborator */
export function reselectOrderCollaborator(data: {
    order_id: number
    order_item_id: number
    staff_id: number
    package_id: number
}) {
    return request.post<null>('/order/reselectCollaborator', data, {
        auth: true,
        dedupe: false,
        loading: true
    })
}

/** 用户确认不需要重新挑选协作人员 @endpoint order.dismissReselect */
export function dismissOrderReselect(data: { order_id: number; order_item_id: number }) {
    return request.post<null>('/order/dismissReselect', data, {
        auth: true,
        dedupe: false,
        loading: true
    })
}

/**
 * 上传线下支付凭证（M-09 恢复）。
 *
 * 后端 `OrderController::uploadVoucher` 只接 `{ id, voucher }`，`voucher` 是凭证图片地址（maxLength 500）。
 * 提交后进入后台审核，**提交凭证不代表到账**；审核结果通过 `pay_voucher_status`（0 待审 / 1 通过 / 2 驳回）
 * 与 `pay_voucher_audit_remark` 在订单详情里读取。
 *
 * 何时需要：`order.detail.offline_collection_enabled === 1` 且 `payment_channel === 2`（线下）时，
 * 用户线下付款后要在这里提交凭证。幂等语义由后端保证（同一订单重复提交会覆盖待审凭证）。
 * @endpoint order.uploadVoucher
 */
export function uploadPayVoucher(data: { id: number; voucher: string }) {
    return request.post<null>('/order/uploadVoucher', data, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}

/*
 * 2026-11 契约变更记录：
 *   order.confirm       用户确认完成 —— 仍不提供（小程序无此入口，由后台/服务人员推进）
 *   order.delete        删除订单 —— 仍不提供（用户已删除状态仅由后台置）
 *   order.uploadVoucher 线下凭证上传 —— **已恢复**，见本文件 uploadPayVoucher()
 *   order.applyRefund   申请退款 —— **已恢复**，按要求放在 src/api/aftersale.ts（售后域）
 *   order.refundDetail  退款详情 —— **已恢复**，同上
 *   order.getPayInfo    支付信息 —— 端点存在，v2/order 的 pay 块已覆盖，暂不封装
 */

/** 订单状态计数（11 个原始状态，客户端收敛为 6 组） @endpoint order.statistics */
export function getOrderStatistics() {
    return request.get<Record<string, number> & OrderStats>('/order/statistics', {}, { auth: true, silent: true })
}

export type { OrderListItem, OrderDetailResponse, OrderStats }
