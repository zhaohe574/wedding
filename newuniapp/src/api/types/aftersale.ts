/**
 * 18 订单售后 / 23 售后申请。
 *
 * 现状：
 *   · 改期链路完整（order_change.applyDateChange，幂等，api 层已有）✔
 *   · 退款链路断（G-14）：端点在 endpoints.json 仍存在
 *     （order.applyRefund / order.refundDetail），但 src/api/order.ts 于 2026-11 主动删除，
 *     设计稿 18 屏「退款申请」与 23 屏「申请退款」tab 因此没有入口。
 *     openapi-core.yaml 与 业务状态与接口.md 都仍把退款列为小程序能力 → **文档与代码冲突**。
 *   · 改期原因 / 时段选项端点存在（typeOptions / pauseTypeOptions / timeSlotOptions），api 层无方法（G-15）。
 */

import type { AfterSaleTicketStatus, OrderChangeStatus, OrderPauseStatus } from '@shared/contracts'
import type { DateString, Money, TimeText } from './common'

/** 18 屏顶部订单摘要 */
export interface AfterSaleOrderBriefVO {
    order_id: number
    order_sn: string
    staff_name?: string
    staff_avatar?: string
    package_name?: string
    service_date?: string
    venue?: string
    paid_amount?: Money
    /** 「已付定金 ¥1,740」 */
    paid_label?: string
}

/** 18 屏处理中卡片（改期申请 · 审核中） */
export interface ChangeRequestVO {
    id: number
    order_id: number
    /** 1 改期 2 加项 3 暂停 —— 与 OrderChangeType 对齐 */
    change_type?: number
    status: OrderChangeStatus
    status_desc?: string
    reason?: string
    /** ✘ F-39 提交时间需要 display 文案 */
    create_time: TimeText
    /** ✘ F-39 目标日期（审核中的卡片要写「目标日期 2027.05.27」） */
    target_dates?: DateString[]
    /** ✘ F-39 「24 小时内确认」的承诺时间 */
    expect_reply_time?: TimeText
    attach_images?: string[]
}

/** 23 屏暂停申请（保底，设计稿未画但端点存在） */
export interface PauseRequestVO {
    id: number
    order_id: number
    pause_type: number
    status: OrderPauseStatus
    status_desc?: string
    start_date?: DateString
    end_date?: DateString
    reason?: string
    create_time: TimeText
}

/** ✘ G-14 退款申请（后端有端点，api 层已删） */
export interface RefundRequestVO {
    id: number
    order_id: number
    refund_sn?: string
    status: number
    status_desc?: string
    refund_amount?: Money
    refundable_amount?: Money
    reason?: string
    create_time?: TimeText
    /** 审核进度文案 */
    progress_text?: string
}

/** 23 屏「改期原因」chips。✘ G-15 需要选项接口（现在只能前端写死枚举，会与后台口径漂移） */
export interface ChangeReasonOptionVO {
    key: string
    label: string
}

/** 售后申请提交入参（与 order_change.applyDateChange 一致，唯一差异：设计稿要多选日期） */
export interface DateChangePayload {
    order_id: number
    /** 单日期为后端现有契约 */
    new_date: DateString
    /** ✘ F-40 设计稿「可多选，提高成功率」→ 需后端支持数组，否则前端只提交首选并在 UI 上说明 */
    new_dates?: DateString[]
    reason?: string
    attach_images?: string[]
}

/** 24/18 屏 FAQ 一条。✘ G-17 无 FAQ 接口，建议 index.faq?scene= 或 policy 承载 */
export interface FaqItemVO {
    id: number
    question: string
    answer?: string
    scene?: string
}

/** 18 屏入口项 */
export type AfterSaleEntryKey = 'change_date' | 'refund' | 'support'

export interface AfterSaleEntryVO {
    key: AfterSaleEntryKey
    title: string
    desc: string
    /** 后端是否开放（退款未开放时置灰并说明原因，而不是点了报错） */
    enabled: boolean
    reason?: string
}

/** 工单/投诉/回访三链路仍由 after_sale.* 承载，列表行结构见 src/api/aftersale.ts 的 TicketListItem */
export type { AfterSaleTicketStatus }
