import request from '@/utils/request'
import type {
    IdParams,
    OrderApiItem,
    OrderCreateParams,
    OrderCreateResponse,
    OrderListParams,
    OrderPreviewResponse,
    OrderStatistics,
    PaginationResponse
} from '@/types/order'

// 获取订单列表
export function getOrderList(params?: OrderListParams) {
    return request.get<PaginationResponse<OrderApiItem>>({ url: '/order/lists', params })
}

// 获取订单详情
export function getOrderDetail(params: IdParams) {
    return request.get<OrderApiItem>({ url: '/order/detail', params })
}

// 创建订单
export function createOrder(params: OrderCreateParams) {
    return request.post<OrderCreateResponse>({ url: '/order/create', params }, { isAuth: true })
}

// 订单预览
export function previewOrder(params: Record<string, unknown>) {
    return request.post<OrderPreviewResponse>(
        { url: '/order/preview', params },
        { isAuth: true, ignoreCancel: true }
    )
}

// 取消订单
export function cancelOrder(params: IdParams & { reason?: string }) {
    return request.post<unknown>({ url: '/order/cancel', params })
}

// 确认完成订单
export function confirmOrder(params: IdParams) {
    return request.post<unknown>({ url: '/order/confirm', params })
}

// 删除订单
export function deleteOrder(params: IdParams) {
    return request.post<unknown>({ url: '/order/delete', params })
}

// 获取支付信息
export function getPayInfo(params: IdParams) {
    return request.get<unknown>({ url: '/order/getPayInfo', params })
}

// 发起支付
export function orderPay(params: Record<string, unknown>) {
    return request.post<unknown>({ url: '/order/pay', params })
}

// 上传线下支付凭证
export function uploadPayVoucher(params: IdParams & { voucher: string }) {
    return request.post<unknown>({ url: '/order/uploadVoucher', params })
}

// 支付尾款
export function orderPayBalance(params: IdParams) {
    return request.post<unknown>({ url: '/order/payBalance', params })
}

// 申请退款
export function applyRefund(params: IdParams & { reason: string }) {
    return request.post<unknown>({ url: '/order/applyRefund', params })
}

// 获取退款详情
export function getRefundDetail(params: IdParams) {
    return request.get<unknown>({ url: '/order/refundDetail', params })
}

// 订单统计
export function getOrderStatistics() {
    return request.get<Partial<OrderStatistics>>({ url: '/order/statistics' })
}
