import request from '@/utils/request'

// ==================== 档期管理 ====================

// 档期列表
export function scheduleLists(params?: any) {
    return request.get({ url: '/ops.schedule/lists', params })
}

// 月度档期日历
export function scheduleMonthCalendar(params?: any) {
    return request.get({ url: '/ops.schedule/monthCalendar', params })
}

// 档期详情
export function scheduleDetail(params: any) {
    return request.get({ url: '/ops.schedule/detail', params })
}

// 设置档期状态
export function scheduleSetStatus(params: any) {
    return request.post({ url: '/ops.schedule/setStatus', params })
}

// 批量设置档期
export function scheduleBatchSet(params: any) {
    return request.post({ url: '/ops.schedule/batchSet', params })
}

// 锁定档期
export function scheduleLock(params: any) {
    return request.post({ url: '/ops.schedule/lock', params })
}

// 释放锁定
export function scheduleUnlock(params: any) {
    return request.post({ url: '/ops.schedule/unlock', params })
}

// 内部预留
export function scheduleReserve(params: any) {
    return request.post({ url: '/ops.schedule/reserve', params })
}

// 锁定记录
export function scheduleLockRecords(params?: any) {
    return request.get({ url: '/ops.schedule/lockRecords', params })
}

// 时间段选项
export function scheduleTimeSlotOptions() {
    return request.get({ url: '/ops.schedule/timeSlotOptions' })
}

// 状态选项
export function scheduleStatusOptions() {
    return request.get({ url: '/ops.schedule/statusOptions' })
}

// 档期统计
export function scheduleStatistics(params?: any) {
    return request.get({ url: '/ops.schedule/statistics', params })
}

// ==================== 档期规则 ====================

// 规则列表
export function scheduleRuleLists(params?: any) {
    return request.get({ url: '/ops.scheduleRule/lists', params })
}

// 规则详情
export function scheduleRuleDetail(params: any) {
    return request.get({ url: '/ops.scheduleRule/detail', params })
}

// 添加规则
export function scheduleRuleAdd(params: any) {
    return request.post({ url: '/ops.scheduleRule/add', params })
}

// 编辑规则
export function scheduleRuleEdit(params: any) {
    return request.post({ url: '/ops.scheduleRule/edit', params })
}

// 删除规则
export function scheduleRuleDelete(params: any) {
    return request.post({ url: '/ops.scheduleRule/delete', params })
}

// 切换规则状态
export function scheduleRuleChangeStatus(params: any) {
    return request.post({ url: '/ops.scheduleRule/changeStatus', params })
}

// 全局规则
export function scheduleRuleGlobal() {
    return request.get({ url: '/ops.scheduleRule/globalRule' })
}

// 工作人员规则
export function scheduleRuleStaff(params: any) {
    return request.get({ url: '/ops.scheduleRule/staffRule', params })
}

