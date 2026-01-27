import request from '@/utils/request'

// 候补列表
export function waitlistLists(params?: any) {
    return request.get({ url: '/schedule.waitlist/lists', params })
}

// 候补详情
export function waitlistDetail(params: any) {
    return request.get({ url: '/schedule.waitlist/detail', params })
}

// 批量通知
export function waitlistBatchNotify(params: any) {
    return request.post({ url: '/schedule.waitlist/batchNotify', params })
}

// 通知
export function waitlistNotify(params: any) {
    return request.post({ url: '/schedule.waitlist/notify', params })
}

// 转正
export function waitlistConvert(params: any) {
    return request.post({ url: '/schedule.waitlist/convert', params })
}

// 失效
export function waitlistInvalidate(params: any) {
    return request.post({ url: '/schedule.waitlist/invalidate', params })
}

// 候补统计
export function waitlistStatistics(params?: any) {
    return request.get({ url: '/schedule.waitlist/statistics', params })
}
