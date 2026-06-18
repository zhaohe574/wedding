export type NumericValue = number | string

export type TagType = 'success' | 'warning' | 'info' | 'danger' | 'primary'

export interface PaginationResponse<T> {
    data?: T[]
    lists?: T[]
    count?: number
    total?: number
    extend?: Record<string, unknown>
    [key: string]: unknown
}

export interface IdParams {
    id: NumericValue
}

export interface ToggleParams extends IdParams {
    is_top?: number
    is_hot?: number
}

export interface AuditParams extends IdParams {
    approved: number
    remark?: string
}

export interface OfflineParams extends IdParams {
    reason: string
}

export interface DynamicListParams {
    dynamic_type?: NumericValue | ''
    status?: NumericValue | ''
    user_type?: NumericValue | ''
    is_top?: NumericValue | ''
    is_hot?: NumericValue | ''
    content?: string
    page_no?: number
    page_size?: number
    [key: string]: unknown
}

export interface DynamicPublisher {
    id?: NumericValue
    nickname?: string
    avatar?: string
    mobile?: string
    [key: string]: unknown
}

export interface ActivityTicketForm {
    id?: NumericValue
    name: string
    price: number
    stock: number
    sale_start_time: number
    sale_end_time: number
    status: number
    sort: number
    [key: string]: unknown
}

export interface DynamicFormData {
    id: NumericValue
    dynamic_type: number
    title: string
    content: string
    images: string[]
    video: string
    video_cover: string
    location: string
    tags: string[]
    allow_comment: number
    status: number
    activity_start_time: number
    activity_signup_deadline: number
    activity_signup_enabled: number
    activity_total_quota: number
    activity_tickets: ActivityTicketForm[]
}

export interface DynamicItem {
    id: NumericValue
    user_type?: NumericValue
    dynamic_type?: number
    title?: string
    content?: string
    video?: string
    video_cover?: string
    location?: string
    allow_comment?: number
    status?: number
    activity_start_time?: number
    activity_signup_deadline?: number
    activity_signup_enabled?: number
    activity_total_quota?: number
    publisher?: DynamicPublisher
    user_type_desc?: string
    type_desc?: string
    status_desc?: string
    images?: string[]
    tags?: string[] | string
    video_url?: string
    create_time?: NumericValue
    update_time?: NumericValue
    view_count?: NumericValue
    like_count?: NumericValue
    comment_count?: NumericValue
    collect_count?: NumericValue
    share_count?: NumericValue
    is_top?: NumericValue
    is_hot?: NumericValue
    activity_registered_count?: NumericValue
    activity_tickets?: ActivityTicketForm[]
    [key: string]: unknown
}

export interface DynamicStatusCount {
    status: NumericValue
    count: number
}

export interface DynamicStatistics {
    status_counts?: DynamicStatusCount[]
    [key: string]: unknown
}

export interface ActivityRegistrationFilters {
    registration_status: NumericValue | ''
    pay_status: NumericValue | ''
    cancel_status: NumericValue | ''
    keyword: string
}

export interface ActivityRegistrationListParams extends Partial<ActivityRegistrationFilters> {
    dynamic_id: NumericValue
    page_size?: number
    [key: string]: unknown
}

export interface ActivityRefundFilters {
    refund_status: NumericValue | ''
}

export interface ActivityRefundListParams extends Partial<ActivityRefundFilters> {
    dynamic_id: NumericValue
    page_size?: number
    [key: string]: unknown
}

export interface ActivityRefundRow {
    id?: NumericValue
    refund_status?: NumericValue
    refund_status_desc?: string
    refund_amount?: NumericValue
    actual_refund_amount?: NumericValue
    refund_reason?: string
    audit_remark?: string
    create_time?: NumericValue
    registration?: ActivityRegistrationRow
    [key: string]: unknown
}

export interface ActivityRegistrationRow {
    id?: NumericValue
    dynamic_id?: NumericValue
    dynamic?: DynamicItem
    user?: DynamicPublisher
    contact_name?: string
    contact_mobile?: string
    ticket_name?: string
    ticket_price?: NumericValue
    ticket_price_label?: string
    pay_amount?: NumericValue
    registration_status?: NumericValue
    registration_status_desc?: string
    pay_status?: NumericValue
    pay_status_desc?: string
    cancel_status?: NumericValue
    cancel_status_desc?: string
    cancel_reason?: string
    latest_refund?: ActivityRefundRow
    remark?: string
    create_time?: NumericValue
    pay_time?: NumericValue
    [key: string]: unknown
}

export interface ActivityRefundAuditParams extends IdParams {
    approved: 0 | 1
    remark?: string
}

export interface ActivitySignupStatus {
    text: string
    type: TagType
}

export type CsvCellValue = string | number | null | undefined
export type CsvRow = Record<string, CsvCellValue>

export type CommentListParams = Record<string, unknown>
export type ReviewListParams = Record<string, unknown>
