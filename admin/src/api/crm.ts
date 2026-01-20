import request from '@/utils/request'

// ==================== 客户管理 ====================

// 客户列表
export function customerLists(params?: any) {
    return request.get({ url: '/crm.customer/lists', params })
}

// 客户详情
export function customerDetail(params: any) {
    return request.get({ url: '/crm.customer/detail', params })
}

// 添加客户
export function customerAdd(params: any) {
    return request.post({ url: '/crm.customer/add', params })
}

// 编辑客户
export function customerEdit(params: any) {
    return request.post({ url: '/crm.customer/edit', params })
}

// 删除客户
export function customerDelete(params: any) {
    return request.post({ url: '/crm.customer/delete', params })
}

// 分配顾问
export function customerAssign(params: any) {
    return request.post({ url: '/crm.customer/assign', params })
}

// 批量分配顾问
export function customerBatchAssign(params: any) {
    return request.post({ url: '/crm.customer/batchAssign', params })
}

// 标记流失
export function customerMarkLoss(params: any) {
    return request.post({ url: '/crm.customer/markLoss', params })
}

// 更新意向等级
export function customerUpdateIntention(params: any) {
    return request.post({ url: '/crm.customer/updateIntention', params })
}

// 客户统计概览
export function customerOverview(params?: any) {
    return request.get({ url: '/crm.customer/overview', params })
}

// 待跟进客户
export function customerPendingFollow(params: any) {
    return request.get({ url: '/crm.customer/pendingFollow', params })
}

// 客户分配历史
export function customerAssignHistory(params: any) {
    return request.get({ url: '/crm.customer/assignHistory', params })
}

// 可用顾问列表
export function customerAvailableAdvisors() {
    return request.get({ url: '/crm.customer/availableAdvisors' })
}

// 意向等级选项
export function customerIntentionOptions() {
    return request.get({ url: '/crm.customer/intentionOptions' })
}

// 客户状态选项
export function customerStatusOptions() {
    return request.get({ url: '/crm.customer/statusOptions' })
}

// 来源渠道选项
export function customerSourceOptions() {
    return request.get({ url: '/crm.customer/sourceOptions' })
}

// ==================== 跟进记录 ====================

// 跟进记录列表
export function followRecordLists(params?: any) {
    return request.get({ url: '/crm.followRecord/lists', params })
}

// 跟进记录详情
export function followRecordDetail(params: any) {
    return request.get({ url: '/crm.followRecord/detail', params })
}

// 添加跟进记录
export function followRecordAdd(params: any) {
    return request.post({ url: '/crm.followRecord/add', params })
}

// 编辑跟进记录
export function followRecordEdit(params: any) {
    return request.post({ url: '/crm.followRecord/edit', params })
}

// 删除跟进记录
export function followRecordDelete(params: any) {
    return request.post({ url: '/crm.followRecord/delete', params })
}

// 客户跟进记录
export function followRecordCustomerRecords(params: any) {
    return request.get({ url: '/crm.followRecord/customerRecords', params })
}

// 顾问今日跟进统计
export function followRecordAdvisorTodayStats(params: any) {
    return request.get({ url: '/crm.followRecord/advisorTodayStats', params })
}

// 重要跟进记录
export function followRecordImportantRecords(params: any) {
    return request.get({ url: '/crm.followRecord/importantRecords', params })
}

// 时间段跟进统计
export function followRecordPeriodStats(params: any) {
    return request.get({ url: '/crm.followRecord/periodStats', params })
}

// 跟进方式选项
export function followRecordTypeOptions() {
    return request.get({ url: '/crm.followRecord/typeOptions' })
}

// 跟进结果选项
export function followRecordResultOptions() {
    return request.get({ url: '/crm.followRecord/resultOptions' })
}

// ==================== 销售顾问 ====================

// 顾问列表
export function salesAdvisorLists(params?: any) {
    return request.get({ url: '/crm.salesAdvisor/lists', params })
}

// 顾问详情
export function salesAdvisorDetail(params: any) {
    return request.get({ url: '/crm.salesAdvisor/detail', params })
}

// 添加顾问
export function salesAdvisorAdd(params: any) {
    return request.post({ url: '/crm.salesAdvisor/add', params })
}

// 编辑顾问
export function salesAdvisorEdit(params: any) {
    return request.post({ url: '/crm.salesAdvisor/edit', params })
}

// 删除顾问
export function salesAdvisorDelete(params: any) {
    return request.post({ url: '/crm.salesAdvisor/delete', params })
}

// 更新顾问状态
export function salesAdvisorUpdateStatus(params: any) {
    return request.post({ url: '/crm.salesAdvisor/updateStatus', params })
}

// 转移客户
export function salesAdvisorTransferCustomers(params: any) {
    return request.post({ url: '/crm.salesAdvisor/transferCustomers', params })
}

// 顾问业绩统计
export function salesAdvisorPerformanceStats(params: any) {
    return request.get({ url: '/crm.salesAdvisor/performanceStats', params })
}

// 顾问客户列表
export function salesAdvisorCustomers(params: any) {
    return request.get({ url: '/crm.salesAdvisor/customers', params })
}

// 状态选项
export function salesAdvisorStatusOptions() {
    return request.get({ url: '/crm.salesAdvisor/statusOptions' })
}

// 顾问简单列表
export function salesAdvisorSimpleList(params?: any) {
    return request.get({ url: '/crm.salesAdvisor/simpleList', params })
}

// ==================== 流失预警 ====================

// 预警列表
export function lossWarningLists(params?: any) {
    return request.get({ url: '/crm.customerLossWarning/lists', params })
}

// 预警详情
export function lossWarningDetail(params: any) {
    return request.get({ url: '/crm.customerLossWarning/detail', params })
}

// 处理预警
export function lossWarningHandle(params: any) {
    return request.post({ url: '/crm.customerLossWarning/handle', params })
}

// 忽略预警
export function lossWarningIgnore(params: any) {
    return request.post({ url: '/crm.customerLossWarning/ignore', params })
}

// 批量处理
export function lossWarningBatchProcess(params: any) {
    return request.post({ url: '/crm.customerLossWarning/batchProcess', params })
}

// 手动生成预警
export function lossWarningGenerate() {
    return request.post({ url: '/crm.customerLossWarning/generate' })
}

// 手动创建预警
export function lossWarningCreate(params: any) {
    return request.post({ url: '/crm.customerLossWarning/create', params })
}

// 顾问待处理预警
export function lossWarningAdvisorPending(params: any) {
    return request.get({ url: '/crm.customerLossWarning/advisorPending', params })
}

// 所有待处理预警
export function lossWarningAllPending(params?: any) {
    return request.get({ url: '/crm.customerLossWarning/allPending', params })
}

// 预警统计
export function lossWarningStats(params?: any) {
    return request.get({ url: '/crm.customerLossWarning/stats', params })
}

// 预警类型选项
export function lossWarningTypeOptions() {
    return request.get({ url: '/crm.customerLossWarning/typeOptions' })
}

// 预警等级选项
export function lossWarningLevelOptions() {
    return request.get({ url: '/crm.customerLossWarning/levelOptions' })
}

// 预警状态选项
export function lossWarningStatusOptions() {
    return request.get({ url: '/crm.customerLossWarning/statusOptions' })
}
