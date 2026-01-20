import request from '@/utils/request'

// ==================== 财务报表 ====================

// 财务概览
export function getFinancialOverview(params?: any) {
    return request.get({ url: '/financial.financialReport/overview', params })
}

// 收入统计
export function getIncomeStats(params?: any) {
    return request.get({ url: '/financial.financialReport/incomeStats', params })
}

// 支付方式分析
export function getPayWayAnalysis(params?: any) {
    return request.get({ url: '/financial.financialReport/payWayAnalysis', params })
}

// 退款统计
export function getRefundStats(params?: any) {
    return request.get({ url: '/financial.financialReport/refundStats', params })
}

// 成本分析
export function getCostAnalysis(params?: any) {
    return request.get({ url: '/financial.financialReport/costAnalysis', params })
}

// 利润分析
export function getProfitAnalysis(params?: any) {
    return request.get({ url: '/financial.financialReport/profitAnalysis', params })
}

// 日报列表
export function getDailyList(params?: any) {
    return request.get({ url: '/financial.financialReport/dailyList', params })
}

// 月报列表
export function getMonthlyList(params?: any) {
    return request.get({ url: '/financial.financialReport/monthlyList', params })
}

// 生成日报
export function generateDaily(data: any) {
    return request.post({ url: '/financial.financialReport/generateDaily', data })
}

// 生成月报
export function generateMonthly(data: any) {
    return request.post({ url: '/financial.financialReport/generateMonthly', data })
}

// 收入趋势
export function getIncomeTrend(params?: any) {
    return request.get({ url: '/financial.financialReport/incomeTrend', params })
}

// 导出日报
export function exportDaily(params?: any) {
    return request.get({ url: '/financial.financialReport/exportDaily', params })
}

// ==================== 资金流水 ====================

// 流水列表
export function getFlowList(params?: any) {
    return request.get({ url: '/financial.flow/lists', params })
}

// 流水详情
export function getFlowDetail(params: { id: number }) {
    return request.get({ url: '/financial.flow/detail', params })
}

// 流水统计
export function getFlowStatistics(params?: any) {
    return request.get({ url: '/financial.flow/statistics', params })
}

// 流水类型选项
export function getFlowTypeOptions() {
    return request.get({ url: '/financial.flow/flowTypeOptions' })
}

// 业务类型选项
export function getBizTypeOptions() {
    return request.get({ url: '/financial.flow/bizTypeOptions' })
}

// ==================== 结算管理 ====================

// 结算列表
export function getSettlementList(params?: any) {
    return request.get({ url: '/financial.settlement/lists', params })
}

// 结算详情
export function getSettlementDetail(params: { id: number }) {
    return request.get({ url: '/financial.settlement/detail', params })
}

// 执行结算
export function doSettle(data: { id: number }) {
    return request.post({ url: '/financial.settlement/settle', data })
}

// 批量结算
export function batchSettle(data: { ids: number[] }) {
    return request.post({ url: '/financial.settlement/batchSettle', data })
}

// 取消结算
export function cancelSettlement(data: { id: number }) {
    return request.post({ url: '/financial.settlement/cancel', data })
}

// 结算统计
export function getSettlementStatistics(params?: any) {
    return request.get({ url: '/financial.settlement/statistics', params })
}

// 人员结算汇总
export function getStaffSettlementSummary(params?: any) {
    return request.get({ url: '/financial.settlement/staffSummary', params })
}

// 批次列表
export function getBatchList(params?: any) {
    return request.get({ url: '/financial.settlement/batchLists', params })
}

// 创建结算批次
export function createBatch(data: any) {
    return request.post({ url: '/financial.settlement/createBatch', data })
}

// 审核批次
export function auditBatch(data: { batch_id: number; status: number; remark?: string }) {
    return request.post({ url: '/financial.settlement/auditBatch', data })
}

// 执行批次
export function executeBatch(data: { id: number }) {
    return request.post({ url: '/financial.settlement/executeBatch', data })
}

// 取消批次
export function cancelBatch(data: { id: number }) {
    return request.post({ url: '/financial.settlement/cancelBatch', data })
}

// 结算配置列表
export function getSettlementConfigList() {
    return request.get({ url: '/financial.settlement/configLists' })
}

// 添加结算配置
export function addSettlementConfig(data: any) {
    return request.post({ url: '/financial.settlement/addConfig', data })
}

// 编辑结算配置
export function editSettlementConfig(data: any) {
    return request.post({ url: '/financial.settlement/editConfig', data })
}

// 删除结算配置
export function deleteSettlementConfig(data: { id: number }) {
    return request.post({ url: '/financial.settlement/deleteConfig', data })
}

// ==================== 成本管理 ====================

// 成本列表
export function getCostList(params?: any) {
    return request.get({ url: '/financial.cost/lists', params })
}

// 成本详情
export function getCostDetail(params: { id: number }) {
    return request.get({ url: '/financial.cost/detail', params })
}

// 添加成本
export function addCost(data: any) {
    return request.post({ url: '/financial.cost/add', data })
}

// 编辑成本
export function editCost(data: any) {
    return request.post({ url: '/financial.cost/edit', data })
}

// 删除成本
export function deleteCost(data: { id: number }) {
    return request.post({ url: '/financial.cost/delete', data })
}

// 确认成本
export function confirmCost(data: { id: number }) {
    return request.post({ url: '/financial.cost/confirm', data })
}

// 批量确认成本
export function batchConfirmCost(data: { ids: number[] }) {
    return request.post({ url: '/financial.cost/batchConfirm', data })
}

// 成本统计
export function getCostStatistics(params?: any) {
    return request.get({ url: '/financial.cost/statistics', params })
}

// 成本类型选项
export function getCostTypeOptions() {
    return request.get({ url: '/financial.cost/typeOptions' })
}

// ==================== 发票管理 ====================

// 发票列表
export function getInvoiceList(params?: any) {
    return request.get({ url: '/financial.invoice/lists', params })
}

// 发票详情
export function getInvoiceDetail(params: { id: number }) {
    return request.get({ url: '/financial.invoice/detail', params })
}

// 开票
export function issueInvoice(data: { id: number; invoice_no: string; invoice_url?: string }) {
    return request.post({ url: '/financial.invoice/issue', data })
}

// 开票失败
export function failInvoice(data: { id: number; fail_reason: string }) {
    return request.post({ url: '/financial.invoice/fail', data })
}

// 作废发票
export function voidInvoice(data: { id: number; void_reason: string }) {
    return request.post({ url: '/financial.invoice/void', data })
}

// 发票统计
export function getInvoiceStatistics(params?: any) {
    return request.get({ url: '/financial.invoice/statistics', params })
}

// 发票类型选项
export function getInvoiceTypeOptions() {
    return request.get({ url: '/financial.invoice/typeOptions' })
}
