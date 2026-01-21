import request from '@/utils/request'

// 消息列表
export function getNotificationList(params?: any) {
    return request.get({ url: '/notification/lists', params })
}

// 消息详情
export function getNotificationDetail(params: any) {
    return request.get({ url: '/notification/detail', params })
}

// 未读数量
export function getUnreadCount() {
    return request.get({ url: '/notification/unreadCount' })
}

// 标记已读
export function markNotificationRead(params: any) {
    return request.post({ url: '/notification/markRead', params })
}

// 全部标记已读
export function markAllNotificationRead(params?: any) {
    return request.post({ url: '/notification/markAllRead', params })
}

// 删除消息
export function deleteNotification(params: any) {
    return request.post({ url: '/notification/delete', params })
}

// 清空消息
export function clearNotification(params?: any) {
    return request.post({ url: '/notification/clear', params })
}
