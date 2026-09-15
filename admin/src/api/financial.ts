import request from '@/utils/request'

// ==================== 财务报表 ====================

// 财务概览
export function getFinancialOverview(params?: any) {
    return request.get({ url: '/finance.financialReport/overview', params })
}

// 收入统计
export function getIncomeStats(params?: any) {
    return request.get({ url: '/finance.financialReport/incomeStats', params })
}

// 支付方式分析
export function getPayWayAnalysis(params?: any) {
    return request.get({ url: '/finance.financialReport/payWayAnalysis', params })
}

// 退款统计
export function getRefundStats(params?: any) {
    return request.get({ url: '/finance.financialReport/refundStats', params })
}

// 收入趋势
export function getIncomeTrend(params?: any) {
    return request.get({ url: '/finance.financialReport/incomeTrend', params })
}

// ==================== 资金流水 ====================

// 流水列表
export function getFlowList(params?: any) {
    return request.get({ url: '/finance.flow/lists', params })
}

// 流水详情
export function getFlowDetail(params: { id: number }) {
    return request.get({ url: '/finance.flow/detail', params })
}

// 流水统计
export function getFlowStatistics(params?: any) {
    return request.get({ url: '/finance.flow/statistics', params })
}

// 流水类型选项
export function getFlowTypeOptions() {
    return request.get({ url: '/finance.flow/flowTypeOptions' })
}

// 业务类型选项
export function getBizTypeOptions() {
    return request.get({ url: '/finance.flow/bizTypeOptions' })
}

// ==================== 结算管理 ====================

// 结算列表
export function getSettlementList(params?: any) {
    return request.get({ url: '/finance.settlement/lists', params })
}

// 结算详情
export function getSettlementDetail(params: { id: number }) {
    return request.get({ url: '/finance.settlement/detail', params })
}

// 执行结算
export function doSettle(data: { id: number }) {
    return request.post({ url: '/finance.settlement/settle', data })
}

// 批量结算
export function batchSettle(data: { ids: number[] }) {
    return request.post({ url: '/finance.settlement/batchSettle', data })
}

// 手动生成结算记录
export function generateSettlements(data: { start_date?: string; end_date?: string }) {
    return request.post({ url: '/finance.settlement/generate', data })
}

// 取消结算
export function cancelSettlement(data: { id: number }) {
    return request.post({ url: '/finance.settlement/cancel', data })
}

// 重试转账
export function retrySettlementTransfer(data: { id: number }) {
    return request.post({ url: '/finance.settlement/retryTransfer', data })
}

// 同步转账状态
export function syncSettlementTransfer(data?: { id?: number }) {
    return request.post({ url: '/finance.settlement/syncTransfer', data: data || {} })
}



// 转账明细
export function getSettlementTransferDetail(params: { id: number }) {
    return request.get({ url: '/finance.settlement/transferDetail', params })
}

// 转账配置
export function getSettlementTransferConfig() {
    return request.get({ url: '/finance.settlement/transferConfig' })
}

// 保存转账配置
export function saveSettlementTransferConfig(data: any) {
    return request.post({ url: '/finance.settlement/saveTransferConfig', data })
}

// 结算统计
export function getSettlementStatistics(params?: any) {
    return request.get({ url: '/finance.settlement/statistics', params })
}

// 人员结算汇总
export function getStaffSettlementSummary(params?: any) {
    return request.get({ url: '/finance.settlement/staffSummary', params })
}

// 批次列表
export function getBatchList(params?: any) {
    return request.get({ url: '/finance.settlement/batchLists', params })
}

// 创建结算批次
export function createBatch(data: any) {
    return request.post({ url: '/finance.settlement/createBatch', data })
}

// 审核批次
export function auditBatch(data: { batch_id: number; status: number; remark?: string }) {
    return request.post({ url: '/finance.settlement/auditBatch', data })
}

// 执行批次
export function executeBatch(data: { id: number }) {
    return request.post({ url: '/finance.settlement/executeBatch', data })
}

// 取消批次
export function cancelBatch(data: { id: number }) {
    return request.post({ url: '/finance.settlement/cancelBatch', data })
}

// 结算配置列表
export function getSettlementConfigList() {
    return request.get({ url: '/finance.settlement/configLists' })
}

// 添加结算配置
export function addSettlementConfig(data: any) {
    return request.post({ url: '/finance.settlement/addConfig', data })
}

// 编辑结算配置
export function editSettlementConfig(data: any) {
    return request.post({ url: '/finance.settlement/editConfig', data })
}

// 删除结算配置
export function deleteSettlementConfig(data: { id: number }) {
    return request.post({ url: '/finance.settlement/deleteConfig', data })
}
