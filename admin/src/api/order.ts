import request from '@/utils/request'

// ==================== 订单管理 ====================

// 订单列表
export function orderLists(params?: any) {
    return request.get({ url: '/order.order/lists', params })
}

// 订单详情
export function orderDetail(params: any) {
    return request.get({ url: '/order.order/detail', params })
}

// 创建订单
export function orderAdd(params: any) {
    return request.post({ url: '/order.order/add', params })
}

// 编辑订单
export function orderEdit(params: any) {
    return request.post({ url: '/order.order/edit', params })
}

// 取消订单
export function orderCancel(params: any) {
    return request.post({ url: '/order.order/cancel', params })
}

// 开始服务
export function orderStartService(params: any) {
    return request.post({ url: '/order.order/startService', params })
}

// 完成订单
export function orderComplete(params: any) {
    return request.post({ url: '/order.order/complete', params })
}

// 确认线下支付
export function orderConfirmOfflinePay(params: any) {
    return request.post({ url: '/order.order/confirmOfflinePay', params })
}

// 审核线下支付凭证
export function orderAuditVoucher(params: any) {
    return request.post({ url: '/order.order/auditVoucher', params })
}

// 添加备注
export function orderAddRemark(params: any) {
    return request.post({ url: '/order.order/addRemark', params })
}

// 订单日志
export function orderLogs(params: any) {
    return request.get({ url: '/order.order/logs', params })
}

// 订单统计
export function orderStatistics(params?: any) {
    return request.get({ url: '/order.order/statistics', params })
}

// 订单状态选项
export function orderStatusOptions() {
    return request.get({ url: '/order.order/statusOptions' })
}

// 支付方式选项
export function orderPayWayOptions() {
    return request.get({ url: '/order.order/payWayOptions' })
}

// ==================== 退款管理 ====================

// 退款列表
export function refundLists(params?: any) {
    return request.get({ url: '/order.refund/lists', params })
}

// 退款详情
export function refundDetail(params: any) {
    return request.get({ url: '/order.refund/detail', params })
}

// 审核退款
export function refundAudit(params: any) {
    return request.post({ url: '/order.refund/audit', params })
}

// 确认退款
export function refundConfirm(params: any) {
    return request.post({ url: '/order.refund/confirmRefund', params })
}

// 管理员发起退款
export function refundApply(params: any) {
    return request.post({ url: '/order.refund/apply', params })
}

// 退款统计
export function refundStatistics(params?: any) {
    return request.get({ url: '/order.refund/statistics', params })
}

// 退款状态选项
export function refundStatusOptions() {
    return request.get({ url: '/order.refund/statusOptions' })
}

// ==================== 支付记录 ====================

// 支付列表
export function paymentLists(params?: any) {
    return request.get({ url: '/order.payment/lists', params })
}

// 支付详情
export function paymentDetail(params: any) {
    return request.get({ url: '/order.payment/detail', params })
}

// 支付统计
export function paymentStatistics(params?: any) {
    return request.get({ url: '/order.payment/statistics', params })
}

// 支付类型选项
export function paymentTypeOptions() {
    return request.get({ url: '/order.payment/typeOptions' })
}

// 支付方式选项
export function paymentWayOptions() {
    return request.get({ url: '/order.payment/wayOptions' })
}

// 支付状态选项
export function paymentStatusOptions() {
    return request.get({ url: '/order.payment/statusOptions' })
}
