/**
 * 15 我的活动 / 02·13 屏内嵌活动卡。
 *
 * 现状：端点在 endpoints.json 里存在（dynamic.activityTickets / activityRegister /
 * activityPayWay / activityPrepay / activityPayStatus / activityRegistrations /
 * activityRegistrationDetail / activityCancelApply），
 * 但 **api 层一个方法都没有**（G-12：api 层缺方法，非后端缺端点）。
 * 因此 15 屏当前无任何数据来源 —— 这是必须补齐的一屏。
 */

import type { Money, TimeText } from './common'
import type { FeedEventBrief } from './feed'

/** 活动报名状态机。◐ 需与后端 activityRegistrations 的 status 取值对齐后写死 */
export type ActivityStatusKey = 'pending_pay' | 'upcoming' | 'checked_in' | 'finished' | 'cancelled'

/** 票种（单人票 / 双人票 / A 区 3 排） */
export interface ActivityTicketTypeVO {
    key: string
    label: string
    price: Money
    /** ◐ 席位描述「A 区 3 排」 */
    seat_text?: string
    stock?: number
}

/** 可报名活动（15 屏「近期可报名」三张卡） */
export interface ActivityBriefVO {
    id: number
    title: string
    cover?: string
    date_text?: string
    time_text?: string
    address?: string
    price_from?: Money
    remaining_seats?: number
    /** ◐ 报名截止时间，用于置灰 */
    register_deadline?: TimeText
}

/** 我的报名记录（15 屏主列表） */
export interface ActivityRegistrationVO {
    id: number
    activity_id: number
    title: string
    cover?: string
    status: ActivityStatusKey
    status_desc?: string
    /** 「11.14 周六 14:00」 */
    date_text?: string
    time_text?: string
    address?: string
    ticket_type?: string
    ticket_price?: Money
    seat_text?: string
    register_time?: TimeText
    /** 距开始天数（「还有 8 天」） */
    days_to_start?: number
    /** ✘ F-23 待支付时必须给死线，否则用户以为名额一直保留 */
    pay_remain_seconds?: number
    /** ✘ F-23 「凭报名手机号签到 / 出示入场码」需要入场码 */
    entry_code?: string
    entry_qr_url?: string
    /** ✘ F-23 已结束活动「看回顾」 */
    review_url?: string
    /** ◐ 取消原因 */
    cancel_reason?: string
}

/** 15 屏分段与置顶卡 */
export interface ActivityScreenVO {
    /** 全部 / 待参加 / 已结束 的计数 */
    counts: { all: number; upcoming: number; finished: number }
    /** 最近一场，单独置顶渲染 */
    next?: ActivityRegistrationVO
    list: ActivityRegistrationVO[]
    /** 近期可报名 */
    recommend: ActivityBriefVO[]
    /** 送审/开关：活动模块是否开放 */
    enabled: boolean
}

/** 02/13 屏活动卡复用 */
export type ActivityCardBrief = FeedEventBrief
