import request from '@/utils/request'

// ==================== 订单暂停审核 ====================
// 后端 OrderPauseController 早已就绪（10 个 action），但后台一直没有页面，
// 导致客户在小程序提交暂停后订单卡在「已暂停(7)」无人处理。

export function orderPauseLists(params?: any) {
    return request.get({ url: '/order.orderPause/lists', params })
}

export function orderPauseDetail(params: any) {
    return request.get({ url: '/order.orderPause/detail', params })
}

export function orderPauseAudit(params: any) {
    return request.post({ url: '/order.orderPause/audit', params })
}

export function orderPauseResume(params: any) {
    return request.post({ url: '/order.orderPause/resume', params })
}

export function orderPauseExtend(params: any) {
    return request.post({ url: '/order.orderPause/extend', params })
}

export function orderPauseLogs(params: any) {
    return request.get({ url: '/order.orderPause/logs', params })
}

export function orderPauseExpiring(params?: any) {
    return request.get({ url: '/order.orderPause/expiring', params })
}

export function orderPauseStatistics(params?: any) {
    return request.get({ url: '/order.orderPause/statistics', params })
}

export function orderPauseTypeOptions() {
    return request.get({ url: '/order.orderPause/typeOptions' })
}

export function orderPauseStatusOptions() {
    return request.get({ url: '/order.orderPause/statusOptions' })
}
