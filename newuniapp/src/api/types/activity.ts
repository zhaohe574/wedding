/**
 * 15 我的活动 / 02·13 屏内嵌活动卡类型定义（与 ActivityRegistrationService 对齐）。
 */

import type { Money, TimeText } from './common'
import type { FeedEventBrief } from './feed'

/** 活动报名状态机：0=待支付, 1=已报名, 2=取消审核中, 3=已取消, 4=退款处理中, 5=退款失败 */
export type ActivityStatusKey = 'pending_pay' | 'upcoming' | 'checked_in' | 'finished' | 'cancelled'

/** 票种（单人票 / 双人票 / A 区 3 排） */
export interface ActivityTicketTypeVO {
    key: string
    label: string
    price: Money
    seat_text?: string
    stock?: number
}

/** 可报名活动简卡 */
export interface ActivityBriefVO {
    id: number
    title: string
    cover?: string
    date_text?: string
    time_text?: string
    address?: string
    price_from?: Money
    remaining_seats?: number
    register_deadline?: TimeText
}

export interface ActivityLatestRefundVO {
    id: number
    refund_sn: string
    refund_amount: number
    actual_refund_amount: number
    refund_status: number
    refund_status_desc: string
    refund_reason: string
    audit_remark: string
    refund_msg: string
    refund_time: number
    create_time: number
}

export interface ActivityDynamicSnapshotVO {
    id: number
    title?: string
    images?: string[]
    location?: string
    activity_start_time?: number
    activity_end_time?: number
    activity_signup_deadline?: number
}

/** 我的活动报名记录（对齐后端 formatRegistrationArray） */
export interface ActivityRegistrationVO {
    id: number
    dynamic_id: number
    ticket_id: number
    contact_name: string
    contact_mobile: string
    remark?: string
    ticket_name: string
    ticket_price: number | string
    ticket_price_label: string
    pay_amount: number | string
    payment_sn?: string
    registration_status: number
    registration_status_desc: string
    pay_status: number
    pay_status_desc: string
    pay_time: number
    cancel_status: number
    cancel_status_desc: string
    cancel_reason?: string
    cancel_reject_reason?: string
    cancel_apply_time?: number
    cancel_audit_time?: number
    checkin_code: string
    checkin_status: number
    checkin_status_desc: string
    checkin_time: number
    pay_deadline_time: number
    pay_remain_seconds: number
    activity_start_time: number
    activity_end_time: number
    activity_location: string
    activity_cover: string
    is_activity_ended: number
    can_cancel: number
    cancel_mode: 'none' | 'direct' | 'audit'
    cancel_disabled_reason?: string
    create_time: number
    latest_refund: ActivityLatestRefundVO | null
    dynamic?: ActivityDynamicSnapshotVO | null
    ticket?: Record<string, any> | null
    [key: string]: any
}

/** 15 屏 4 分段真实计数 */
export interface ActivityGroupCountsVO {
    all: number
    upcoming: number
    pending_pay: number
    history: number
}

/** 02/13 屏活动卡复用 */
export type ActivityCardBrief = FeedEventBrief
