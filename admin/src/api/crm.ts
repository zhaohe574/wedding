import request from '@/utils/request'

// ==================== 客户管理 ====================

// 客户列表
export function customerLists(params?: any) {
    return request.get({ url: '/growth.customer/lists', params })
}

// 客户详情
export function customerDetail(params: any) {
    return request.get({ url: '/growth.customer/detail', params })
}

// 添加客户
export function customerAdd(params: any) {
    return request.post({ url: '/growth.customer/add', params })
}

// 编辑客户
export function customerEdit(params: any) {
    return request.post({ url: '/growth.customer/edit', params })
}

// 删除客户
export function customerDelete(params: any) {
    return request.post({ url: '/growth.customer/delete', params })
}

// 分配顾问
export function customerAssign(params: any) {
    return request.post({ url: '/growth.customer/assign', params })
}

// 批量分配顾问
export function customerBatchAssign(params: any) {
    return request.post({ url: '/growth.customer/batchAssign', params })
}

// 标记流失
export function customerMarkLoss(params: any) {
    return request.post({ url: '/growth.customer/markLoss', params })
}

// 更新意向等级
export function customerUpdateIntention(params: any) {
    return request.post({ url: '/growth.customer/updateIntention', params })
}

// 客户统计概览
export function customerOverview(params?: any) {
    return request.get({ url: '/growth.customer/overview', params })
}

// 待跟进客户
export function customerPendingFollow(params: any) {
    return request.get({ url: '/growth.customer/pendingFollow', params })
}

// 客户分配历史
export function customerAssignHistory(params: any) {
    return request.get({ url: '/growth.customer/assignHistory', params })
}

// 可用顾问列表
export function customerAvailableAdvisors() {
    return request.get({ url: '/growth.customer/availableAdvisors' })
}

// 意向等级选项
export function customerIntentionOptions() {
    return request.get({ url: '/growth.customer/intentionOptions' })
}

// 客户状态选项
export function customerStatusOptions() {
    return request.get({ url: '/growth.customer/statusOptions' })
}

// 来源渠道选项
export function customerSourceOptions() {
    return request.get({ url: '/growth.customer/sourceOptions' })
}

// ==================== 跟进记录 ====================

// 跟进记录列表
export function followRecordLists(params?: any) {
    return request.get({ url: '/growth.followRecord/lists', params })
}

// 跟进记录详情
export function followRecordDetail(params: any) {
    return request.get({ url: '/growth.followRecord/detail', params })
}

// 添加跟进记录
export function followRecordAdd(params: any) {
    return request.post({ url: '/growth.followRecord/add', params })
}

// 编辑跟进记录
export function followRecordEdit(params: any) {
    return request.post({ url: '/growth.followRecord/edit', params })
}

// 删除跟进记录
export function followRecordDelete(params: any) {
    return request.post({ url: '/growth.followRecord/delete', params })
}

// 客户跟进记录
export function followRecordCustomerRecords(params: any) {
    return request.get({ url: '/growth.followRecord/customerRecords', params })
}

// 顾问今日跟进统计
export function followRecordAdvisorTodayStats(params: any) {
    return request.get({ url: '/growth.followRecord/advisorTodayStats', params })
}

// 重要跟进记录
export function followRecordImportantRecords(params: any) {
    return request.get({ url: '/growth.followRecord/importantRecords', params })
}

// 时间段跟进统计
export function followRecordPeriodStats(params: any) {
    return request.get({ url: '/growth.followRecord/periodStats', params })
}

// 跟进方式选项
export function followRecordTypeOptions() {
    return request.get({ url: '/growth.followRecord/typeOptions' })
}

// 跟进结果选项
export function followRecordResultOptions() {
    return request.get({ url: '/growth.followRecord/resultOptions' })
}

// ==================== 销售顾问 ====================

// 顾问列表
export function salesAdvisorLists(params?: any) {
    return request.get({ url: '/growth.advisor/lists', params })
}

// 顾问详情
export function salesAdvisorDetail(params: any) {
    return request.get({ url: '/growth.advisor/detail', params })
}

// 添加顾问
export function salesAdvisorAdd(params: any) {
    return request.post({ url: '/growth.advisor/add', params })
}

// 编辑顾问
export function salesAdvisorEdit(params: any) {
    return request.post({ url: '/growth.advisor/edit', params })
}

// 删除顾问
export function salesAdvisorDelete(params: any) {
    return request.post({ url: '/growth.advisor/delete', params })
}

// 更新顾问状态
export function salesAdvisorUpdateStatus(params: any) {
    return request.post({ url: '/growth.advisor/updateStatus', params })
}

// 转移客户
export function salesAdvisorTransferCustomers(params: any) {
    return request.post({ url: '/growth.advisor/transferCustomers', params })
}

// 顾问业绩统计
export function salesAdvisorPerformanceStats(params: any) {
    return request.get({ url: '/growth.advisor/performanceStats', params })
}

// 顾问客户列表
export function salesAdvisorCustomers(params: any) {
    return request.get({ url: '/growth.advisor/customers', params })
}

// 状态选项
export function salesAdvisorStatusOptions() {
    return request.get({ url: '/growth.advisor/statusOptions' })
}

// 顾问简单列表
export function salesAdvisorSimpleList(params?: any) {
    return request.get({ url: '/growth.advisor/simpleList', params })
}

// ==================== 流失预警 ====================

// 预警列表
export function lossWarningLists(params?: any) {
    return request.get({ url: '/growth.lossWarning/lists', params })
}

// 预警详情
export function lossWarningDetail(params: any) {
    return request.get({ url: '/growth.lossWarning/detail', params })
}

// 处理预警
export function lossWarningHandle(params: any) {
    return request.post({ url: '/growth.lossWarning/handle', params })
}

// 忽略预警
export function lossWarningIgnore(params: any) {
    return request.post({ url: '/growth.lossWarning/ignore', params })
}

// 批量处理
export function lossWarningBatchProcess(params: any) {
    return request.post({ url: '/growth.lossWarning/batchProcess', params })
}

// 手动生成预警
export function lossWarningGenerate() {
    return request.post({ url: '/growth.lossWarning/generate' })
}

// 手动创建预警
export function lossWarningCreate(params: any) {
    return request.post({ url: '/growth.lossWarning/create', params })
}

// 顾问待处理预警
export function lossWarningAdvisorPending(params: any) {
    return request.get({ url: '/growth.lossWarning/advisorPending', params })
}

// 所有待处理预警
export function lossWarningAllPending(params?: any) {
    return request.get({ url: '/growth.lossWarning/allPending', params })
}

// 预警统计
export function lossWarningStats(params?: any) {
    return request.get({ url: '/growth.lossWarning/stats', params })
}

// 预警类型选项
export function lossWarningTypeOptions() {
    return request.get({ url: '/growth.lossWarning/typeOptions' })
}

// 预警等级选项
export function lossWarningLevelOptions() {
    return request.get({ url: '/growth.lossWarning/levelOptions' })
}

// 预警状态选项
export function lossWarningStatusOptions() {
    return request.get({ url: '/growth.lossWarning/statusOptions' })
}
