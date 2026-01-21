import request from '@/utils/request'

// ==================== 统计 ====================

/**
 * 获取售后统计数据
 */
export function getAfterSaleStatistics() {
    return request.get({ url: '/aftersale.aftersale/statistics' })
}

/**
 * 获取趋势数据
 */
export function getAfterSaleTrend(params: { days?: number }) {
    return request.get({ url: '/aftersale.aftersale/trend', params })
}

// ==================== 工单管理 ====================

/**
 * 工单列表
 */
export function getTicketLists(params: any) {
    return request.get({ url: '/aftersale.aftersale/ticketLists', params })
}

/**
 * 工单详情
 */
export function getTicketDetail(id: number) {
    return request.get({ url: '/aftersale.aftersale/ticketDetail', params: { id } })
}

/**
 * 创建工单
 */
export function createTicket(data: any) {
    return request.post({ url: '/aftersale.aftersale/createTicket', data })
}

/**
 * 分配工单
 */
export function assignTicket(data: { id: number; admin_id: number }) {
    return request.post({ url: '/aftersale.aftersale/assignTicket', data })
}

/**
 * 处理工单
 */
export function handleTicket(data: { id: number; result: string; images?: string[] }) {
    return request.post({ url: '/aftersale.aftersale/handleTicket', data })
}

/**
 * 关闭工单
 */
export function closeTicket(data: { id: number; reason: string }) {
    return request.post({ url: '/aftersale.aftersale/closeTicket', data })
}

/**
 * 升级工单
 */
export function escalateTicket(data: { id: number }) {
    return request.post({ url: '/aftersale.aftersale/escalateTicket', data })
}

/**
 * 工单日志
 */
export function getTicketLogs(id: number) {
    return request.get({ url: '/aftersale.aftersale/ticketLogs', params: { id } })
}

// ==================== 投诉管理 ====================

/**
 * 投诉列表
 */
export function getComplaintLists(params: any) {
    return request.get({ url: '/aftersale.aftersale/complaintLists', params })
}

/**
 * 投诉详情
 */
export function getComplaintDetail(id: number) {
    return request.get({ url: '/aftersale.aftersale/complaintDetail', params: { id } })
}

/**
 * 处理投诉
 */
export function handleComplaint(data: { id: number; action: number; result: string; amount?: number }) {
    return request.post({ url: '/aftersale.aftersale/handleComplaint', data })
}

// ==================== 补拍申请 ====================

/**
 * 补拍申请列表
 */
export function getReshootLists(params: any) {
    return request.get({ url: '/aftersale.aftersale/reshootLists', params })
}

/**
 * 补拍申请详情
 */
export function getReshootDetail(id: number) {
    return request.get({ url: '/aftersale.aftersale/reshootDetail', params: { id } })
}

/**
 * 审核补拍申请
 */
export function auditReshoot(data: { id: number; approved: number; is_free?: number; fee?: number; remark?: string }) {
    return request.post({ url: '/aftersale.aftersale/auditReshoot', data })
}

/**
 * 安排补拍
 */
export function scheduleReshoot(data: { id: number; schedule_date: string; new_staff_id?: number }) {
    return request.post({ url: '/aftersale.aftersale/scheduleReshoot', data })
}

/**
 * 完成补拍
 */
export function completeReshoot(data: { id: number; remark?: string }) {
    return request.post({ url: '/aftersale.aftersale/completeReshoot', data })
}

// ==================== 回访管理 ====================

/**
 * 回访列表
 */
export function getCallbackLists(params: any) {
    return request.get({ url: '/aftersale.aftersale/callbackLists', params })
}

/**
 * 回访详情
 */
export function getCallbackDetail(id: number) {
    return request.get({ url: '/aftersale.aftersale/callbackDetail', params: { id } })
}

/**
 * 创建回访任务
 */
export function createCallback(data: any) {
    return request.post({ url: '/aftersale.aftersale/createCallback', data })
}

/**
 * 完成回访
 */
export function completeCallback(data: any) {
    return request.post({ url: '/aftersale.aftersale/completeCallback', data })
}

/**
 * 标记无法联系
 */
export function markUnreachable(data: { id: number }) {
    return request.post({ url: '/aftersale.aftersale/markUnreachable', data })
}

/**
 * 问题升级
 */
export function escalateProblem(data: { id: number }) {
    return request.post({ url: '/aftersale.aftersale/escalateProblem', data })
}
