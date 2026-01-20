import request from '@/utils/request'

// 获取工作人员档期
export function getStaffSchedule(params: any) {
    return request.get({ url: '/schedule/staffSchedule', params })
}

// 获取月度日历
export function getMonthCalendar(params?: any) {
    return request.get({ url: '/schedule/monthCalendar', params })
}

// 获取吉日列表
export function getLuckyDays(params?: any) {
    return request.get({ url: '/schedule/luckyDays', params })
}

// 检查档期是否可预约
export function checkScheduleAvailable(params: any) {
    return request.get({ url: '/schedule/checkAvailable', params })
}

// 锁定档期
export function lockSchedule(params: any) {
    return request.post({ url: '/schedule/lockSchedule', params })
}

// 释放档期锁定
export function releaseScheduleLock(params: any) {
    return request.post({ url: '/schedule/releaseLock', params })
}

// 加入候补
export function joinWaitlist(params: any) {
    return request.post({ url: '/schedule/joinWaitlist', params })
}

// 我的候补列表
export function getMyWaitlist(params?: any) {
    return request.get({ url: '/schedule/myWaitlist', params })
}

// 取消候补
export function cancelWaitlist(params: any) {
    return request.post({ url: '/schedule/cancelWaitlist', params })
}
