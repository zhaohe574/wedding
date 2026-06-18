import type { NumericValue, PaginationResponse } from './order'

export interface DynamicTicket {
    id?: NumericValue
    name?: string
    price?: NumericValue
    price_label?: string
    remaining_count?: NumericValue
    can_buy?: NumericValue
    buy_disabled_reason?: string
    sale_start_time?: NumericValue
    sale_end_time?: NumericValue
    status?: NumericValue
    sort?: NumericValue
    [key: string]: unknown
}

export interface ActivityInfo {
    tickets?: DynamicTicket[]
    activity_registration_id?: NumericValue
    activity_has_registered?: NumericValue
    activity_can_register?: NumericValue
    activity_price_label?: string
    activity_registration_status_text?: string
    activity_disabled_reason?: string
    activity_start_time?: NumericValue
    activity_signup_deadline?: NumericValue
    activity_total_quota?: NumericValue
    activity_remaining_count?: NumericValue
    [key: string]: unknown
}

export interface DynamicDetail {
    id?: NumericValue
    title?: string
    content?: string
    images?: string[] | string
    tags?: string[] | string
    dynamic_type?: NumericValue
    allow_comment?: NumericValue
    is_liked?: boolean | NumericValue
    like_count?: NumericValue
    comment_count?: NumericValue
    view_count?: NumericValue
    video?: string
    video_url?: string
    video_cover?: string
    activity?: ActivityInfo
    [key: string]: unknown
}

export interface DynamicComment {
    id?: NumericValue
    comment_id?: NumericValue
    parent_id?: NumericValue
    content?: string
    avatar?: string
    nickname?: string
    user_nickname?: string
    create_time?: string | number
    like_count?: NumericValue
    is_liked?: boolean | NumericValue
    allow_delete?: boolean | NumericValue
    comment?: DynamicComment[]
    comment_count?: NumericValue
    [key: string]: unknown
}

export interface ActivityRegistrationRefund {
    id?: NumericValue
    refund_sn?: string
    refund_amount?: NumericValue
    actual_refund_amount?: NumericValue
    refund_status?: NumericValue
    refund_status_desc?: string
    refund_reason?: string
    audit_remark?: string
    refund_msg?: string
    refund_time?: string | number
    create_time?: string | number
    [key: string]: unknown
}

export interface ActivityRegistration {
    id?: NumericValue
    dynamic_id?: NumericValue
    ticket_id?: NumericValue
    ticket_name?: string
    ticket_price?: NumericValue
    ticket_price_label?: string
    pay_amount?: NumericValue
    contact_name?: string
    contact_mobile?: string
    remark?: string
    registration_status?: NumericValue
    registration_status_desc?: string
    pay_status?: NumericValue
    pay_status_desc?: string
    cancel_status?: NumericValue
    cancel_status_desc?: string
    cancel_reason?: string
    cancel_reject_reason?: string
    latest_refund?: ActivityRegistrationRefund | null
    create_time?: string | number
    pay_time?: string | number
    dynamic?: DynamicDetail
    ticket?: DynamicTicket
    user?: {
        id?: NumericValue
        nickname?: string
        avatar?: string
        mobile?: string
        [key: string]: unknown
    }
    [key: string]: unknown
}

export interface ActivityRegistrationParams {
    page?: number
    page_size?: number
    dynamic_id?: NumericValue
    ticket_id?: NumericValue
    status_group?: string
    registration_status?: NumericValue | ''
    pay_status?: NumericValue | ''
    cancel_status?: NumericValue | ''
    keyword?: string
}

export interface ActivityRegistrationSubmitParams {
    dynamic_id: NumericValue
    ticket_id: NumericValue
    contact_name: string
    contact_mobile: string
    remark?: string
}

export interface ActivityRegistrationSubmitResponse {
    registration_id?: NumericValue
    need_pay?: NumericValue
    [key: string]: unknown
}

export type ActivityRegistrationListResponse = PaginationResponse<ActivityRegistration>
