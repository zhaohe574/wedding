import request from '@/utils/request'

// ==================== 订单变更管理 ====================

// 变更列表
export function orderChangeLists(params?: any) {
    return request.get({ url: '/order.orderChange/lists', params })
}

// 变更详情
export function orderChangeDetail(params: any) {
    return request.get({ url: '/order.orderChange/detail', params })
}

// 审核变更
export function orderChangeAudit(params: any) {
    return request.post({ url: '/order.orderChange/audit', params })
}

// 执行变更
export function orderChangeExecute(params: any) {
    return request.post({ url: '/order.orderChange/execute', params })
}

// 变更日志
export function orderChangeLogs(params: any) {
    return request.get({ url: '/order.orderChange/logs', params })
}

// 变更统计
export function orderChangeStatistics(params?: any) {
    return request.get({ url: '/order.orderChange/statistics', params })
}

// 变更类型选项
export function orderChangeTypeOptions() {
    return request.get({ url: '/order.orderChange/typeOptions' })
}

// 变更状态选项
export function orderChangeStatusOptions() {
    return request.get({ url: '/order.orderChange/statusOptions' })
}
