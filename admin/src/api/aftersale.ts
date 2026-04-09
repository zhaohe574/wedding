import request from '@/utils/request'

// ==================== 统计 ====================

/**
 * 获取售后统计数据
 */
export function getAfterSaleStatistics() {
    return request.get({ url: '/ops.aftersaleTicket/statistics' })
}

/**
 * 获取趋势数据
 */
export function getAfterSaleTrend(params: { days?: number }) {
    return request.get({ url: '/ops.aftersaleTicket/trend', params })
}

// ==================== 工单管理 ====================

/**
 * 工单列表
 */
export function getTicketLists(params: any) {
    return request.get({ url: '/ops.aftersaleTicket/ticketLists', params })
}

/**
 * 工单详情
 */
export function getTicketDetail(id: number) {
    return request.get({ url: '/ops.aftersaleTicket/ticketDetail', params: { id } })
}

/**
 * 创建工单
 */
export function createTicket(data: any) {
    return request.post({ url: '/ops.aftersaleTicket/createTicket', data })
}

/**
 * 分配工单
 */
export function assignTicket(data: { id: number; admin_id: number }) {
    return request.post({ url: '/ops.aftersaleTicket/assignTicket', data })
}

/**
 * 处理工单
 */
export function handleTicket(data: { id: number; result: string; images?: string[] }) {
    return request.post({ url: '/ops.aftersaleTicket/handleTicket', data })
}

/**
 * 关闭工单
 */
export function closeTicket(data: { id: number; reason: string }) {
    return request.post({ url: '/ops.aftersaleTicket/closeTicket', data })
}

/**
 * 升级工单
 */
export function escalateTicket(data: { id: number }) {
    return request.post({ url: '/ops.aftersaleTicket/escalateTicket', data })
}

/**
 * 工单日志
 */
export function getTicketLogs(id: number) {
    return request.get({ url: '/ops.aftersaleTicket/ticketLogs', params: { id } })
}

// ==================== 投诉管理 ====================

/**
 * 投诉列表
 */
export function getComplaintLists(params: any) {
    return request.get({ url: '/ops.aftersaleTicket/complaintLists', params })
}

/**
 * 投诉详情
 */
export function getComplaintDetail(id: number) {
    return request.get({ url: '/ops.aftersaleTicket/complaintDetail', params: { id } })
}

/**
 * 处理投诉
 */
export function handleComplaint(data: { id: number; action: number; result: string; amount?: number }) {
    return request.post({ url: '/ops.aftersaleTicket/handleComplaint', data })
}

// ==================== 回访管理 ====================

/**
 * 回访列表
 */
export function getCallbackLists(params: any) {
    return request.get({ url: '/ops.aftersaleTicket/callbackLists', params })
}

/**
 * 回访详情
 */
export function getCallbackDetail(id: number) {
    return request.get({ url: '/ops.aftersaleTicket/callbackDetail', params: { id } })
}

/**
 * 创建回访任务
 */
export function createCallback(data: any) {
    return request.post({ url: '/ops.aftersaleTicket/createCallback', data })
}

/**
 * 完成回访
 */
export function completeCallback(data: any) {
    return request.post({ url: '/ops.aftersaleTicket/completeCallback', data })
}

/**
 * 标记无法联系
 */
export function markUnreachable(data: { id: number }) {
    return request.post({ url: '/ops.aftersaleTicket/markUnreachable', data })
}

/**
 * 问题升级
 */
export function escalateProblem(data: { id: number }) {
    return request.post({ url: '/ops.aftersaleTicket/escalateProblem', data })
}
