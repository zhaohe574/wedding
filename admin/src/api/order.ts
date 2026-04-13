import request from '@/utils/request'

// ==================== 订单管理 ====================

// 订单列表
export function orderLists(params?: any) {
    return request.get({ url: '/ops.order/lists', params })
}

// 订单详情
export function orderDetail(params: any) {
    return request.get({ url: '/ops.order/detail', params })
}

// 创建订单
export function orderAdd(params: any) {
    return request.post({ url: '/ops.order/add', params })
}

// 编辑订单
export function orderEdit(params: any) {
    return request.post({ url: '/ops.order/edit', params })
}

// 取消订单
export function orderCancel(params: any) {
    return request.post({ url: '/ops.order/cancel', params })
}

// 确认订单
export function orderConfirm(params: any) {
    return request.post({ url: '/ops.order/confirm', params })
}

// 开始服务
export function orderStartService(params: any) {
    return request.post({ url: '/ops.order/startService', params })
}

// 完成订单
export function orderComplete(params: any) {
    return request.post({ url: '/ops.order/complete', params })
}

// 删除订单
export function orderDelete(params: any) {
    return request.post({ url: '/ops.order/delete', params })
}

// 确认线下支付
export function orderConfirmOfflinePay(params: any) {
    return request.post({ url: '/ops.order/confirmOfflinePay', params })
}

// 审核线下支付凭证
export function orderAuditVoucher(params: any) {
    return request.post({ url: '/ops.order/auditVoucher', params })
}

// 添加备注
export function orderAddRemark(params: any) {
    return request.post({ url: '/ops.order/addRemark', params })
}

// 订单日志
export function orderLogs(params: any) {
    return request.get({ url: '/ops.order/logs', params })
}

// 订单统计
export function orderStatistics(params?: any) {
    return request.get({ url: '/ops.order/statistics', params })
}

// 后台建单支付预估
export function orderEstimatePayment(params: any) {
    return request.post({ url: '/ops.order/estimatePayment', params })
}

// 线下建单主套餐
export function orderOfflineMainPackages(params: any) {
    return request.get({ url: '/ops.order/offlineMainPackages', params })
}

// 线下建单协作角色候选
export function orderOfflineRoleCandidates(params: any) {
    return request.get({ url: '/ops.order/offlineRoleCandidates', params })
}

// 线下建单金额预估
export function orderEstimateOffline(params: any) {
    return request.post({ url: '/ops.order/estimateOffline', params })
}

// 新增线下订单
export function orderAddOffline(params: any) {
    return request.post({ url: '/ops.order/addOffline', params })
}

// 订单状态选项
export function orderStatusOptions() {
    return request.get({ url: '/ops.order/statusOptions' })
}

// 支付方式选项
export function orderPayWayOptions() {
    return request.get({ url: '/ops.order/payWayOptions' })
}

// ==================== 订单确认函 ====================

export function orderConfirmLetterGenerate(params: any) {
    return request.post({ url: '/ops.order/confirmLetterGenerate', params })
}

export function orderConfirmLetterPush(params: any) {
    return request.post({ url: '/ops.order/confirmLetterPush', params })
}

export function orderConfirmLetterDetail(params: any) {
    return request.get({ url: '/ops.order/confirmLetterDetail', params })
}

export function orderConfirmLetterHistory(params: any) {
    return request.get({ url: '/ops.order/confirmLetterHistory', params })
}

export function orderConfirmLetterAssets(params: any) {
    return request.post({ url: '/ops.order/confirmLetterAssets', params })
}

// ==================== 退款管理 ====================

// 退款列表
export function refundLists(params?: any) {
    return request.get({ url: '/ops.refund/lists', params })
}

// 退款详情
export function refundDetail(params: any) {
    return request.get({ url: '/ops.refund/detail', params })
}

// 审核退款
export function refundAudit(params: any) {
    return request.post({ url: '/ops.refund/audit', params })
}

// 确认退款
export function refundConfirm(params: any) {
    return request.post({ url: '/ops.refund/confirmRefund', params })
}

// 管理员发起退款
export function refundApply(params: any) {
    return request.post({ url: '/ops.refund/apply', params })
}

// 退款统计
export function refundStatistics(params?: any) {
    return request.get({ url: '/ops.refund/statistics', params })
}

// 退款状态选项
export function refundStatusOptions() {
    return request.get({ url: '/ops.refund/statusOptions' })
}

// ==================== 支付记录 ====================

// 支付列表
export function paymentLists(params?: any) {
    return request.get({ url: '/ops.payment/lists', params })
}

// 支付详情
export function paymentDetail(params: any) {
    return request.get({ url: '/ops.payment/detail', params })
}

// 支付统计
export function paymentStatistics(params?: any) {
    return request.get({ url: '/ops.payment/statistics', params })
}

// 支付类型选项
export function paymentTypeOptions() {
    return request.get({ url: '/ops.payment/typeOptions' })
}

// 支付方式选项
export function paymentWayOptions() {
    return request.get({ url: '/ops.payment/wayOptions' })
}

// 支付状态选项
export function paymentStatusOptions() {
    return request.get({ url: '/ops.payment/statusOptions' })
}
