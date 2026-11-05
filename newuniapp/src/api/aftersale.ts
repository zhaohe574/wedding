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
