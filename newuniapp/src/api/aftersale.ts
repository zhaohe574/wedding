import request from '@/request'
import type { AfterSaleTicketStatus } from '@shared/contracts'

/**
 * 售后域：工单 / 投诉 / 回访三条独立链路。
 * 后端是三个独立模型，客户端也分开，不做「一个售后页装三种」。
 */

export interface TicketListItem {
    id: number
    ticket_sn?: string
    type: number
    status: AfterSaleTicketStatus
    status_desc?: string
    title?: string
    content?: string
    create_time: string
    [k: string]: any
}

export interface CreateTicketParams {
    type: number
    title: string
    content?: string
    images?: string[]
    order_id?: number
    staff_id?: number
    priority?: number
    contact_name?: string
    contact_mobile?: string
}

/** @endpoint after_sale.myStatistics */
export function getAfterSaleStatistics() {
    return request.get<any>('/after_sale/myStatistics', {}, { auth: true, silent: true })
}

/** 工单列表（分页 page + limit） @endpoint after_sale.ticketLists */
export function getTicketList(params: { page?: number; limit?: number; status?: number }) {
    return request.get<any>('/after_sale/ticketLists', params, { auth: true, silent: true })
}

/** @endpoint after_sale.ticketDetail */
export function getTicketDetail(params: { id: number }) {
    return request.get<any>('/after_sale/ticketDetail', params, { auth: true, silent: true })
}

/** 提交工单（需幂等，避免重复建单） @endpoint after_sale.createTicket */
export function createTicket(data: CreateTicketParams) {
    return request.post<{ id: number }>('/after_sale/createTicket', data as any, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}

/** @endpoint after_sale.cancelTicket */
export function cancelTicket(params: { id: number }) {
    return request.post<null>('/after_sale/cancelTicket', params, { auth: true, loading: true })
}

/** 用户确认工单完成 @endpoint after_sale.confirmComplete */
export function confirmTicketComplete(data: { id: number; satisfaction?: number; remark?: string }) {
    return request.post<null>('/after_sale/confirmComplete', data, { auth: true, loading: true })
}

/** 用户拒绝处理结果 @endpoint after_sale.rejectComplete */
export function rejectTicketComplete(data: { id: number; reason: string }) {
    return request.post<null>('/after_sale/rejectComplete', data, { auth: true, loading: true })
}

/** @endpoint after_sale.complaintLists */
export function getComplaintList(params: { page?: number; limit?: number; status?: number }) {
    return request.get<any>('/after_sale/complaintLists', params, { auth: true, silent: true })
}

/** @endpoint after_sale.complaintDetail */
export function getComplaintDetail(params: { id: number }) {
    return request.get<any>('/after_sale/complaintDetail', params, { auth: true, silent: true })
}

/** @endpoint after_sale.submitComplaint */
export function submitComplaint(data: {
    order_id?: number
    type?: number
    title?: string
    content?: string
    level?: number
    expect_result?: string
    images?: string[]
    [k: string]: any
}) {
    return request.post<{ id: number }>('/after_sale/submitComplaint', data as any, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}

/** 投诉满意度评分 @endpoint after_sale.rateComplaint */
export function rateComplaint(data: { id: number; score: number; feedback?: string }) {
    return request.post<null>('/after_sale/rateComplaint', data, { auth: true, loading: true })
}

/** @endpoint after_sale.callbackLists */
export function getCallbackList(params: { page?: number; limit?: number }) {
    return request.get<any>('/after_sale/callbackLists', params, { auth: true, silent: true })
}

/**
 * 回访问卷题目。返回 callback 本体 + questionnaire:{id,title,description,questions[]}
 * @endpoint after_sale.getQuestionnaire
 */
export function getCallbackQuestionnaire(params: { id: number }) {
    return request.get<any>('/after_sale/getQuestionnaire', params, { auth: true, silent: true })
}

/**
 * 提交回访问卷。
 * 注意 payload 结构：{ questionnaire_id, answers[], score_overall? } —— 不是扁平的 answers。
 * @endpoint after_sale.submitQuestionnaire
 */
export function submitCallbackQuestionnaire(data: {
    id: number
    questionnaire_id?: number
    answers: Array<{ key: string; value: any }>
    score_overall?: number
}) {
    return request.post<null>('/after_sale/submitQuestionnaire', data as any, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}

// ================================================================
// 退款（C-04 裁决后恢复；23 屏「申请退款」）
// ================================================================
//
// 与售后工单（ticket/complaint/callback）是**两套模型**，不要混：
//   · 工单 → after_sale.*，类型是「服务单 / 投诉 / 回访」
//   · 退款 → order.applyRefund，模型是 Refund + RefundItem，按原支付流水拆分
//
// 【23 屏表单字段拼装规则 —— 实现前必读】
// 后端 `POST /order/applyRefund` 只接 `{ id, reason }`，而 `reason` 的校验是 `max:255`。
// 也就是说：**表单只有「退款原因（单选）」+「补充说明（选填）」，两者拼成一个 reason 字符串**：
//
//   reason = 原因标签 + （有补充说明时）'：' + 补充说明
//   例：reason = '行程变更：婚礼改到明年，已与顾问沟通过'
//   例：reason = '行程变更'                       // 没填补充说明，不加冒号
//
// 三条硬约束：
//   1. **不出现可退金额输入框**。金额由服务端 `Refund::applyRefund` → `OrderRefundService::getRefundableAmount()`
//      计算，客户端传金额会被忽略，传了也没用。
//   2. **不出现凭证上传**。当前接口不收 `images`（后端要加字段才能收，已列缺口）；
//      想留凭证只能引导用户走「人工客服」。
//   3. 拼装后总长必须 ≤ 255，超出要截断补充说明（见 buildRefundReason）。
//
// 【可退金额怎么展示】
//   唯一来源是 `getRefundDetail()` 返回的 `refund_amount` /
//   或订单详情里的 `refundable_amount`（`api/order.ts: getOrderDetail` / v2 的 `order.payment`）。
//   **拿不到就不展示金额**，不要按比例算、不要用定金减手续费推算。
//
// 【什么时候能退】
//   仅当 `order_status ∈ {2 待服务, 3 服务中}` 且无未处理退款申请且可退额 > 0。
//   订单详情已直接给 `can_user_refund`（布尔）与 `refundable_amount`，页面读这两个字段即可，
//   不要在页面里重算状态组合。

/** 后端 `reason` 校验上限（OrderValidate: `reason => max:255`） */
export const REFUND_REASON_MAX_LENGTH = 255

/** 23 屏退款表单的原始输入（两个控件，不是一个输入框） */
export interface RefundFormInput {
    /** 单选：退款原因，直接作为 reason 的前缀 */
    reason_label: string
    /** 选填：补充说明，会拼在「原因：」之后 */
    supplement?: string
}

/**
 * 把「原因 + 补充说明」拼成后端要的单个 `reason` 字符串。
 *
 * 规则（与后端 max:255 对齐，超出时优先保留原因标签、截断补充说明）：
 *   - 无补充说明 → 返回原因标签本身
 *   - 有补充说明 → `${reason_label}：${supplement}`，总长超 255 时截断 supplement
 *   - 两者都空 → 抛错（后端会拒，早失败早提示）
 */
export function buildRefundReason(input: RefundFormInput): string {
    const label = String(input.reason_label ?? '').trim()
    const supplement = String(input.supplement ?? '').trim()
    if (!label) {
        throw new Error('请选择退款原因')
    }
    if (!supplement) {
        return label.slice(0, REFUND_REASON_MAX_LENGTH)
    }
    const sep = '：'
    const room = REFUND_REASON_MAX_LENGTH - label.length - sep.length
    if (room <= 0) {
        return label.slice(0, REFUND_REASON_MAX_LENGTH)
    }
    return `${label}${sep}${supplement.slice(0, room)}`
}

/**
 * 申请退款（写接口，幂等）。
 *
 * 入参只有 `{ id, reason }`；`reason` 用 `buildRefundReason()` 拼，不要自己拼。
 * 提交成功后订单进入 `order_status = 10 退款中`，之后状态以订单详情为准。
 * @endpoint order.applyRefund
 */
export function applyRefund(data: { id: number; reason: string }) {
    const reason = String(data.reason ?? '').trim()
    if (!reason) {
        throw new Error('请填写退款原因')
    }
    return request.post<null>(
        '/order/applyRefund',
        { id: data.id, reason: reason.slice(0, REFUND_REASON_MAX_LENGTH) },
        { auth: true, idempotent: true, dedupe: false, loading: true }
    )
}

/**
 * 退款详情（最近一条）。
 *
 * 后端返回 `Refund` 模型 + `refund_items[]`（按原支付流水拆分）+ `refund_status_desc` +
 * `refund_type_desc` + `can_confirm_offline`。**退款金额只从这里或订单详情的
 * `refundable_amount` 读，拿不到就不展示。**
 * 没有退款记录时后端返回业务失败 `退款记录不存在`（code=0），页面按「暂无退款」处理而不是报错。
 * @endpoint order.refundDetail
 */
export function getRefundDetail(params: { id: number }) {
    return request.get<{
        id?: number
        refund_sn?: string
        order_id?: number
        refund_amount?: number | string
        actual_refund_amount?: number | string
        refund_status?: number
        refund_status_desc?: string
        refund_type?: number
        refund_type_desc?: string
        refund_reason?: string
        can_confirm_offline?: number
        create_time?: number | string
        refund_items?: Array<Record<string, any>>
        [k: string]: any
    }>('/order/refundDetail', params, { auth: true, silent: true })
}
