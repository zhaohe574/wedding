import request from '@/utils/request'

// ==================== 订单暂停管理 ====================

// 暂停列表
export function orderPauseLists(params?: any) {
    return request.get({ url: '/order.orderPause/lists', params })
}

// 暂停详情
export function orderPauseDetail(params: any) {
    return request.get({ url: '/order.orderPause/detail', params })
}

// 审核暂停
export function orderPauseAudit(params: any) {
    return request.post({ url: '/order.orderPause/audit', params })
}

// 恢复订单
export function orderPauseResume(params: any) {
    return request.post({ url: '/order.orderPause/resume', params })
}

// 延长暂停
export function orderPauseExtend(params: any) {
    return request.post({ url: '/order.orderPause/extend', params })
}

// 即将到期列表
export function orderPauseExpiring(params?: any) {
    return request.get({ url: '/order.orderPause/expiring', params })
}

// 暂停日志
export function orderPauseLogs(params: any) {
    return request.get({ url: '/order.orderPause/logs', params })
}

// 暂停统计
export function orderPauseStatistics(params?: any) {
    return request.get({ url: '/order.orderPause/statistics', params })
}

// 暂停类型选项
export function orderPauseTypeOptions() {
    return request.get({ url: '/order.orderPause/typeOptions' })
}

// 暂停状态选项
export function orderPauseStatusOptions() {
    return request.get({ url: '/order.orderPause/statusOptions' })
}
