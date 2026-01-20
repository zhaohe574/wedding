import request from '@/utils/request'

// ==================== 订单转让管理 ====================

// 转让列表
export function orderTransferLists(params?: any) {
    return request.get({ url: '/order.orderTransfer/lists', params })
}

// 转让详情
export function orderTransferDetail(params: any) {
    return request.get({ url: '/order.orderTransfer/detail', params })
}

// 审核转让
export function orderTransferAudit(params: any) {
    return request.post({ url: '/order.orderTransfer/audit', params })
}

// 完成转让
export function orderTransferComplete(params: any) {
    return request.post({ url: '/order.orderTransfer/complete', params })
}

// 取消转让
export function orderTransferCancel(params: any) {
    return request.post({ url: '/order.orderTransfer/cancel', params })
}

// 重发验证码
export function orderTransferResendCode(params: any) {
    return request.post({ url: '/order.orderTransfer/resendCode', params })
}

// 转让日志
export function orderTransferLogs(params: any) {
    return request.get({ url: '/order.orderTransfer/logs', params })
}

// 转让统计
export function orderTransferStatistics(params?: any) {
    return request.get({ url: '/order.orderTransfer/statistics', params })
}

// 转让状态选项
export function orderTransferStatusOptions() {
    return request.get({ url: '/order.orderTransfer/statusOptions' })
}
