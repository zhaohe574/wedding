import request from '@/utils/request'
import type {
    NotificationActionParams,
    NotificationBatchActionParams,
    NotificationClearResult,
    NotificationItem,
    NotificationListParams,
    NotificationListResponse,
    NotificationUnreadCount
} from '@/types/notification'

// 消息列表
export function getNotificationList(params?: NotificationListParams) {
    return request.get<NotificationListResponse>({ url: '/notification/lists', params })
}

// 消息详情
export function getNotificationDetail(params: NotificationActionParams) {
    return request.get<NotificationItem>({ url: '/notification/detail', params })
}

// 未读数量
export function getUnreadCount() {
    return request.get<NotificationUnreadCount>(
        { url: '/notification/unreadCount' },
        { duplicateStrategy: 'join' }
    )
}

// 标记已读
export function markNotificationRead(params: NotificationActionParams) {
    return request.post({ url: '/notification/markRead', params })
}

// 全部标记已读
export function markAllNotificationRead(params?: NotificationBatchActionParams) {
    return request.post({ url: '/notification/markAllRead', params })
}

// 删除消息
export function deleteNotification(params: NotificationActionParams) {
    return request.post({ url: '/notification/delete', params })
}

// 清空消息
export function clearNotification(params?: NotificationBatchActionParams) {
    return request.post<NotificationClearResult>({ url: '/notification/clear', params })
}
