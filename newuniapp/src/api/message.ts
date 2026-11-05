import request from '@/request'
import type { UnreadSummary } from '@shared/contracts'

/**
 * 消息中心。
 *
 * 老端把 target_type → 路由的映射硬编码在 notification/index.vue 里（20+ 条），
 * 新端改为 composables/useNotificationRouter.ts 单一映射表 + 编译期穷尽检查。
 */

export interface NotificationItem {
    id: number
    title: string
    content: string
    notify_type?: number
    notify_type_text?: string
    target_type?: string
    target_id?: number
    params?: Record<string, any>
    is_read: number
    create_time: string
    create_time_text?: string
}

/** @endpoint notification.lists */
export function getNotificationList(params: { page?: number; page_size?: number; type?: number; is_read?: number }) {
    return request.get<any>('/notification/lists', params, { auth: true, silent: true })
}

/** @endpoint notification.detail */
export function getNotificationDetail(params: { id: number }) {
    return request.get<NotificationItem>('/notification/detail', params, { auth: true, silent: true })
}

/** @endpoint notification.unreadCount */
export function getUnreadCount() {
    return request.get<UnreadSummary & Record<string, number>>('/notification/unreadCount', {}, { auth: true, silent: true })
}

/** @endpoint notification.markRead */
export function markRead(params: { id: number }) {
    return request.post<null>('/notification/markRead', params, { auth: true, silent: true })
}

/** @endpoint notification.markAllRead */
export function markAllRead(params?: { type?: number }) {
    return request.post<null>('/notification/markAllRead', params ?? {}, { auth: true, silent: true })
}

/** @endpoint notification.delete */
export function deleteNotification(params: { id: number }) {
    return request.post<null>('/notification/delete', params, { auth: true, silent: true })
}

/** @endpoint notification.clear */
export function clearNotification(params?: { type?: number }) {
    return request.post<null>('/notification/clear', params ?? {}, { auth: true, silent: true })
}

export type { UnreadSummary }
