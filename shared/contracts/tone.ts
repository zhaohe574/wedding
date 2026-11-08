// 状态 → 语义色调。
//
// 为什么不用具体颜色：admin 用 Element Plus 的 tag type，小程序用 CSS 变量，pc 不用色标。
// 三端各自把 Tone 映射到自己的色板，契约只统一语义。
//
// 关键约束：所有映射必须是 Record<Enum, Tone>（穷尽），
// 后端新增状态值并重新生成契约后，本文件会立刻 TS 报错 —— 这比任何 CI 脚本都可靠。

import {
    OrderStatus,
    OrderPayStatus,
    PaymentFlowStatus,
    RefundStatus,
    OrderChangeStatus,
    OrderPauseStatus,
    AfterSaleTicketStatus,
    ComplaintStatus,
    ServiceCallbackStatus,
    ReviewStatus,
    ScheduleStatus,
    QuestionnaireTaskStatus,
    StaffSettlementStatus
} from './generated/status'

export type Tone = 'neutral' | 'info' | 'primary' | 'success' | 'warning' | 'danger'

export const ORDER_STATUS_TONE: Record<OrderStatus, Tone> = {
    [OrderStatus.PENDING_CONFIRM]: 'warning',
    [OrderStatus.PENDING_PAY]: 'warning',
    [OrderStatus.PENDING_SERVICE]: 'info',
    [OrderStatus.IN_SERVICE]: 'primary',
    [OrderStatus.COMPLETED]: 'success',
    [OrderStatus.REVIEWED]: 'success',
    [OrderStatus.CANCELLED]: 'neutral',
    [OrderStatus.PAUSED]: 'neutral',
    [OrderStatus.REFUNDED]: 'neutral',
    [OrderStatus.USER_DELETED]: 'neutral',
    [OrderStatus.REFUNDING]: 'danger'
}

export const ORDER_PAY_STATUS_TONE: Record<OrderPayStatus, Tone> = {
    [OrderPayStatus.UNPAID]: 'warning',
    [OrderPayStatus.PAID]: 'success',
    [OrderPayStatus.PARTIAL_REFUND]: 'info',
    [OrderPayStatus.FULL_REFUND]: 'neutral'
}

export const PAYMENT_FLOW_STATUS_TONE: Record<PaymentFlowStatus, Tone> = {
    [PaymentFlowStatus.PENDING]: 'warning',
    [PaymentFlowStatus.PAID]: 'success',
    [PaymentFlowStatus.REFUNDED]: 'neutral',
    [PaymentFlowStatus.FAILED]: 'danger'
}

export const REFUND_STATUS_TONE: Record<RefundStatus, Tone> = {
    [RefundStatus.PENDING]: 'warning',
    [RefundStatus.APPROVED]: 'info',
    [RefundStatus.PROCESSING]: 'info',
    [RefundStatus.COMPLETED]: 'success',
    [RefundStatus.REJECTED]: 'danger',
    [RefundStatus.FAILED]: 'danger'
}

export const ORDER_CHANGE_STATUS_TONE: Record<OrderChangeStatus, Tone> = {
    [OrderChangeStatus.PENDING]: 'warning',
    [OrderChangeStatus.APPROVED]: 'info',
    [OrderChangeStatus.REJECTED]: 'danger',
    [OrderChangeStatus.EXECUTED]: 'success',
    [OrderChangeStatus.CANCELLED]: 'neutral'
}

export const ORDER_PAUSE_STATUS_TONE: Record<OrderPauseStatus, Tone> = {
    [OrderPauseStatus.PENDING]: 'warning',
    [OrderPauseStatus.PAUSED]: 'neutral',
    [OrderPauseStatus.RESUMED]: 'success',
    [OrderPauseStatus.REJECTED]: 'danger',
    [OrderPauseStatus.CANCELLED]: 'neutral'
}

export const AFTER_SALE_TICKET_STATUS_TONE: Record<AfterSaleTicketStatus, Tone> = {
    [AfterSaleTicketStatus.PENDING]: 'warning',
    [AfterSaleTicketStatus.PROCESSING]: 'info',
    [AfterSaleTicketStatus.CONFIRMING]: 'primary',
    [AfterSaleTicketStatus.COMPLETED]: 'success',
    [AfterSaleTicketStatus.CLOSED]: 'neutral',
    [AfterSaleTicketStatus.CANCELLED]: 'neutral'
}

export const COMPLAINT_STATUS_TONE: Record<ComplaintStatus, Tone> = {
    [ComplaintStatus.PENDING]: 'warning',
    [ComplaintStatus.PROCESSING]: 'info',
    [ComplaintStatus.HANDLED]: 'success',
    [ComplaintStatus.APPEALED]: 'primary',
    [ComplaintStatus.CLOSED]: 'neutral'
}

export const SERVICE_CALLBACK_STATUS_TONE: Record<ServiceCallbackStatus, Tone> = {
    [ServiceCallbackStatus.PENDING]: 'warning',
    [ServiceCallbackStatus.COMPLETED]: 'success',
    [ServiceCallbackStatus.UNREACHABLE]: 'danger',
    [ServiceCallbackStatus.CANCELLED]: 'neutral'
}

export const REVIEW_STATUS_TONE: Record<ReviewStatus, Tone> = {
    [ReviewStatus.PENDING]: 'warning',
    [ReviewStatus.APPROVED]: 'success',
    [ReviewStatus.REJECTED]: 'danger'
}

export const SCHEDULE_STATUS_TONE: Record<ScheduleStatus, Tone> = {
    [ScheduleStatus.UNAVAILABLE]: 'neutral',
    [ScheduleStatus.AVAILABLE]: 'success',
    [ScheduleStatus.BOOKED]: 'info',
    [ScheduleStatus.LOCKED]: 'warning',
    [ScheduleStatus.RESERVED]: 'neutral'
}

export const QUESTIONNAIRE_TASK_STATUS_TONE: Record<QuestionnaireTaskStatus, Tone> = {
    [QuestionnaireTaskStatus.PENDING]: 'warning',
    [QuestionnaireTaskStatus.SUBMITTED]: 'success',
    [QuestionnaireTaskStatus.CANCELLED]: 'neutral',
    [QuestionnaireTaskStatus.VIEWED]: 'info',
    [QuestionnaireTaskStatus.EXPIRED]: 'neutral'
}

export const STAFF_SETTLEMENT_STATUS_TONE: Record<StaffSettlementStatus, Tone> = {
    [StaffSettlementStatus.PENDING]: 'warning',
    [StaffSettlementStatus.SETTLED]: 'success',
    [StaffSettlementStatus.CANCELLED]: 'neutral',
    [StaffSettlementStatus.FAILED]: 'danger',
    [StaffSettlementStatus.TRANSFER_PROCESSING]: 'info',
    [StaffSettlementStatus.NO_PAYOUT]: 'neutral'
}

/** 安全取色调：未知状态一律 neutral，不崩 */
export function resolveTone(map: Record<number, Tone>, value: number | string | null | undefined): Tone {
    return map[Number(value)] ?? 'neutral'
}
