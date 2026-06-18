import type { NumericValue, PaginationResponse } from './order'

export interface NotificationItem {
    id?: NumericValue
    title?: string
    content?: string
    notify_type?: NumericValue
    notify_type_text?: string
    target_type?: string
    target_id?: NumericValue
    is_read?: NumericValue
    create_time?: NumericValue
    create_time_text?: string
    [key: string]: unknown
}

export interface NotificationListParams {
    page?: number
    page_size?: number
    limit?: number
    notify_type?: NumericValue
    [key: string]: unknown
}

export interface NotificationUnreadCount {
    total?: number
    system?: number
    order?: number
    interact?: number
    types?: Record<string, number>
    [key: string]: unknown
}

export interface NotificationActionParams {
    id: NumericValue
}

export interface NotificationBatchActionParams {
    notify_type?: NumericValue
    read_status?: NumericValue
}

export interface NotificationClearResult {
    count?: NumericValue
    [key: string]: unknown
}

export type NotificationListResponse = PaginationResponse<NotificationItem>
