import request from '@/utils/request'

// ==================== 订单转让管理 ====================

// 转让列表
export function orderTransferLists(params?: any) {
    return request.get({ url: '/ops.orderTransfer/lists', params })
}

// 转让详情
export function orderTransferDetail(params: any) {
    return request.get({ url: '/ops.orderTransfer/detail', params })
}

// 审核转让
export function orderTransferAudit(params: any) {
    return request.post({ url: '/ops.orderTransfer/audit', params })
}

// 完成转让
export function orderTransferComplete(params: any) {
    return request.post({ url: '/ops.orderTransfer/complete', params })
}

// 取消转让
export function orderTransferCancel(params: any) {
    return request.post({ url: '/ops.orderTransfer/cancel', params })
}

// 重发验证码
export function orderTransferResendCode(params: any) {
    return request.post({ url: '/ops.orderTransfer/resendCode', params })
}

// 转让日志
export function orderTransferLogs(params: any) {
    return request.get({ url: '/ops.orderTransfer/logs', params })
}

// 转让统计
export function orderTransferStatistics(params?: any) {
    return request.get({ url: '/ops.orderTransfer/statistics', params })
}

// 转让状态选项
export function orderTransferStatusOptions() {
    return request.get({ url: '/ops.orderTransfer/statusOptions' })
}
