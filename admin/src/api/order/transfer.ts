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

// 转让统计
export function orderTransferStatistics(params?: any) {
    return request.get({ url: '/ops.orderTransfer/statistics', params })
}

// 转让状态选项
export function orderTransferStatusOptions() {
    return request.get({ url: '/ops.orderTransfer/statusOptions' })
}
