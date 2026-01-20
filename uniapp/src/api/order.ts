import request from '@/utils/request'

// 获取订单列表
export function getOrderList(params?: any) {
    return request.get({ url: '/order/lists', params })
}

// 获取订单详情
export function getOrderDetail(params: any) {
    return request.get({ url: '/order/detail', params })
}

// 创建订单
export function createOrder(params: any) {
    return request.post({ url: '/order/create', params })
}

// 订单预览
export function previewOrder(params: any) {
    return request.post({ url: '/order/preview', params })
}

// 取消订单
export function cancelOrder(params: any) {
    return request.post({ url: '/order/cancel', params })
}

// 确认完成订单
export function confirmOrder(params: any) {
    return request.post({ url: '/order/confirm', params })
}

// 删除订单
export function deleteOrder(params: any) {
    return request.post({ url: '/order/delete', params })
}

// 获取支付信息
export function getPayInfo(params: any) {
    return request.get({ url: '/order/getPayInfo', params })
}

// 发起支付
export function orderPay(params: any) {
    return request.post({ url: '/order/pay', params })
}

// 支付尾款
export function orderPayBalance(params: any) {
    return request.post({ url: '/order/payBalance', params })
}

// 申请退款
export function applyRefund(params: any) {
    return request.post({ url: '/order/applyRefund', params })
}

// 获取退款详情
export function getRefundDetail(params: any) {
    return request.get({ url: '/order/refundDetail', params })
}

// 订单统计
export function getOrderStatistics() {
    return request.get({ url: '/order/statistics' })
}

// 获取可用优惠券
export function getAvailableCoupons(params?: any) {
    return request.get({ url: '/order/availableCoupons', params })
}
