/**
 * 17 我的咨询 / 24 客服管家。
 *
 * ⚠ 最大功能缺口（P0，G-13）：
 *   后端只有一个 customer_service.startConsult，返回「当前该找谁 + 联系方式」；
 *   会话本体走的是**微信原生客服**（button open-type="contact"），
 *   平台侧没有 conversation / message 表可查、也没有发送接口。
 *   设计稿 17 屏是一整屏 IM（消息流、日期分隔、新消息线、订单卡片消息、快捷问题、输入框），
 *   在现有后端上**无法用真数据实现**。可选路线只有两条，需产品裁决：
 *     a) 17 屏改造成「顾问名片 + 进入微信客服」，删掉消息流（推荐，成本 0.5 人天）
 *     b) 后端新建会话能力（conversation/messages/send + 未读），成本 ≥ 15 人天，且要与微信客服双通道并存
 */

import type { BitFlag } from './common'

/** startConsult 返回的联系人（现网 customer-service 页已在消费） */
export interface ConsultContactVO {
    name: string
    role: string
    mobile: string
    avatar: string
    service_time: string
    tips: string
    /** ✘ F-37 「在线 · 平均 3 分钟回复」 */
    online?: BitFlag
    reply_avg_minutes?: number
}

/** POST /customer_service/startConsult */
export interface ConsultResultVO {
    entry_type: 'advisor' | 'fallback'
    customer_id: number
    advisor_id: number
    is_new_assignment: boolean
    contact: ConsultContactVO
    customer_service_chat: { enabled: boolean; mode: string }
}

/** 会话内一条消息。✘ G-13 后端无此模型 */
export interface ChatMessageVO {
    id: number
    conversation_id: number
    /** staff / user / system */
    sender: 'staff' | 'user' | 'system'
    sender_name?: string
    sender_avatar?: string
    type: 'text' | 'image' | 'order_card' | 'system'
    content: string
    /** order_card 消息的载荷 */
    order_card?: {
        order_id: number
        title: string
        subtitle?: string
        amount?: number | string
        footer?: string
    }
    created_at: number
    read?: BitFlag
}

/** 会话头 + 上下文。✘ G-13 */
export interface ConversationVO {
    id: number
    advisor: ConsultContactVO
    /** 关联订单条 */
    context_order?: { order_id: number; title: string }
    /** 「以下为新消息」分界 */
    last_read_message_id?: number
    quick_questions?: string[]
    unread_count?: number
}

/** 24 屏「能帮到什么 / 服务承诺」四格。✘ F-38 无接口，现为静态文案 */
export interface ConsultCapabilityVO {
    label: string
    value: string
}

/** 24 屏客服管家整屏数据 */
export interface ConciergeScreenVO {
    advisor: ConsultContactVO
    capabilities: ConsultCapabilityVO[]
    promises: Array<{ title: string; desc: string }>
    faq: Array<{ id: number; question: string; answer?: string }>
    /** 微信原生客服是否可用（对应 open-type="contact"） */
    wechat_chat_enabled: boolean
    /** 是否有电话专线 */
    phone_available: boolean
}
