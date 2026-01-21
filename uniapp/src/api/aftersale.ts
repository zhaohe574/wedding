import request from '@/utils/request'

// ==================== 统计 ====================

/**
 * 获取我的售后统计
 */
export function getMyStatistics() {
    return request.get('/aftersale/myStatistics')
}

// ==================== 工单管理 ====================

/**
 * 我的工单列表
 */
export function getTicketLists(params: { page?: number; limit?: number; status?: number }) {
    return request.get('/aftersale/ticketLists', params)
}

/**
 * 工单详情
 */
export function getTicketDetail(id: number) {
    return request.get('/aftersale/ticketDetail', { id })
}

/**
 * 创建工单
 */
export function createTicket(data: {
    type: number
    title: string
    content?: string
    images?: string[]
    order_id?: number
}) {
    return request.post('/aftersale/createTicket', data)
}

/**
 * 取消工单
 */
export function cancelTicket(id: number) {
    return request.post('/aftersale/cancelTicket', { id })
}

/**
 * 确认完成
 */
export function confirmComplete(data: { id: number; satisfaction?: number; remark?: string }) {
    return request.post('/aftersale/confirmComplete', data)
}

// ==================== 投诉管理 ====================

/**
 * 我的投诉列表
 */
export function getComplaintLists(params: { page?: number; limit?: number; status?: number }) {
    return request.get('/aftersale/complaintLists', params)
}

/**
 * 投诉详情
 */
export function getComplaintDetail(id: number) {
    return request.get('/aftersale/complaintDetail', { id })
}

/**
 * 提交投诉
 */
export function submitComplaint(data: {
    type: number
    level: number
    title: string
    content?: string
    images?: string[]
    videos?: string[]
    order_id?: number
    staff_id?: number
    expect_result?: string
}) {
    return request.post('/aftersale/submitComplaint', data)
}

/**
 * 评价投诉处理
 */
export function rateComplaint(data: { id: number; satisfaction: number }) {
    return request.post('/aftersale/rateComplaint', data)
}

// ==================== 补拍申请 ====================

/**
 * 我的补拍申请列表
 */
export function getReshootLists(params: { page?: number; limit?: number; status?: number }) {
    return request.get('/aftersale/reshootLists', params)
}

/**
 * 补拍申请详情
 */
export function getReshootDetail(id: number) {
    return request.get('/aftersale/reshootDetail', { id })
}

/**
 * 提交补拍申请
 */
export function applyReshoot(data: {
    order_id: number
    type: number
    reason_type: number
    reason?: string
    images?: string[]
    expect_date?: string
    expect_time_slot?: string
    staff_id?: number
    order_item_id?: number
}) {
    return request.post('/aftersale/applyReshoot', data)
}

/**
 * 取消补拍申请
 */
export function cancelReshoot(id: number) {
    return request.post('/aftersale/cancelReshoot', { id })
}

// ==================== 回访问卷 ====================

/**
 * 我的回访列表
 */
export function getCallbackLists(params: { page?: number; limit?: number; status?: number }) {
    return request.get('/aftersale/callbackLists', params)
}

/**
 * 获取回访问卷
 */
export function getQuestionnaire(id: number) {
    return request.get('/aftersale/getQuestionnaire', { id })
}

/**
 * 提交回访问卷
 */
export function submitQuestionnaire(data: {
    id: number
    score?: number
    score_service?: number
    score_professional?: number
    score_punctual?: number
    score_overall?: number
    feedback?: string
    questionnaire_id?: number
    answers?: any[]
}) {
    return request.post('/aftersale/submitQuestionnaire', data)
}
