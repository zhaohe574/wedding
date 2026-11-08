/**
 * 16 通知与提醒。
 *
 * 现状：notify_type 只有 4 类（1 系统 / 2 订单 / 3 互动 / 4 活动，见 useNotificationRouter 的 NotifyType），
 * 设计稿 16 屏的分类 chips 是「订单 / 活动 / 服务」—— **「服务」类后端没有**（F-35），
 * 「互动」类设计稿没用到。两者对不上，必须先定口径再画 tab。
 *
 * 「删除已读」也没有对应端点：现有 notification.delete 只删单条，clear 按 type 清空（G-16）。
 */

import type { BitFlag, TimeText } from './common'

/** 分类 tab：与后端 NotifyType 一一对应，另加「全部」 */
export type NotifyTabKey = 'all' | 'order' | 'activity' | 'service' | 'interact' | 'system'

export interface NotifyTab {
    key: NotifyTabKey
    label: string
    count?: number
    unread?: number
}

/** 条目右下角的动作按钮（「查看订单」「出示入场码」） */
export interface NotifyActionVO {
    label: string
    /** 复用 useNotificationRouter 的 target_type */
    target_type: string
    target_id?: number
    /** 直达动作：跳页 / 复制 / 打开小程序码 */
    kind?: 'page' | 'copy' | 'qr'
}

export interface NotificationVO {
    id: number
    title: string
    content: string
    /** 1 系统 2 订单 3 互动 4 活动 */
    notify_type: number
    notify_type_text?: string
    /** 图标语义：order / ticket / chat / check / bell / wallet */
    icon_key?: string
    target_type?: string
    target_id?: number
    params?: Record<string, any>
    is_read: BitFlag
    create_time: string
    create_time_text?: TimeText
    /** ✘ F-36 可动手的按钮文案与去向，后端目前只有 target_type，按钮文案靠前端映射 */
    action?: NotifyActionVO
}

/** 「今天 · 2 条」分组 */
export interface NotificationGroupVO {
    label: string
    date_key: string
    items: NotificationVO[]
}

/** 16 屏头部统计 */
export interface NotificationHeaderVO {
    unread: number
    total: number
    /** ✘ G-16 「删除已读」需要后端支持 */
    can_clear_read: boolean
}
