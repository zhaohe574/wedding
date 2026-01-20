import request from '@/utils/request'

// ==================== 档期管理 ====================

// 档期列表
export function scheduleLists(params?: any) {
    return request.get({ url: '/schedule.schedule/lists', params })
}

// 月度档期日历
export function scheduleMonthCalendar(params?: any) {
    return request.get({ url: '/schedule.schedule/monthCalendar', params })
}

// 档期详情
export function scheduleDetail(params: any) {
    return request.get({ url: '/schedule.schedule/detail', params })
}

// 设置档期状态
export function scheduleSetStatus(params: any) {
    return request.post({ url: '/schedule.schedule/setStatus', params })
}

// 批量设置档期
export function scheduleBatchSet(params: any) {
    return request.post({ url: '/schedule.schedule/batchSet', params })
}

// 锁定档期
export function scheduleLock(params: any) {
    return request.post({ url: '/schedule.schedule/lock', params })
}

// 释放锁定
export function scheduleUnlock(params: any) {
    return request.post({ url: '/schedule.schedule/unlock', params })
}

// 内部预留
export function scheduleReserve(params: any) {
    return request.post({ url: '/schedule.schedule/reserve', params })
}

// 锁定记录
export function scheduleLockRecords(params?: any) {
    return request.get({ url: '/schedule.schedule/lockRecords', params })
}

// 时间段选项
export function scheduleTimeSlotOptions() {
    return request.get({ url: '/schedule.schedule/timeSlotOptions' })
}

// 状态选项
export function scheduleStatusOptions() {
    return request.get({ url: '/schedule.schedule/statusOptions' })
}

// 档期统计
export function scheduleStatistics(params?: any) {
    return request.get({ url: '/schedule.schedule/statistics', params })
}

// ==================== 档期规则 ====================

// 规则列表
export function scheduleRuleLists(params?: any) {
    return request.get({ url: '/schedule.scheduleRule/lists', params })
}

// 规则详情
export function scheduleRuleDetail(params: any) {
    return request.get({ url: '/schedule.scheduleRule/detail', params })
}

// 添加规则
export function scheduleRuleAdd(params: any) {
    return request.post({ url: '/schedule.scheduleRule/add', params })
}

// 编辑规则
export function scheduleRuleEdit(params: any) {
    return request.post({ url: '/schedule.scheduleRule/edit', params })
}

// 删除规则
export function scheduleRuleDelete(params: any) {
    return request.post({ url: '/schedule.scheduleRule/delete', params })
}

// 切换规则状态
export function scheduleRuleChangeStatus(params: any) {
    return request.post({ url: '/schedule.scheduleRule/changeStatus', params })
}

// 全局规则
export function scheduleRuleGlobal() {
    return request.get({ url: '/schedule.scheduleRule/globalRule' })
}

// 工作人员规则
export function scheduleRuleStaff(params: any) {
    return request.get({ url: '/schedule.scheduleRule/staffRule', params })
}

// ==================== 日历事件(黄历) ====================

// 事件列表
export function calendarEventLists(params?: any) {
    return request.get({ url: '/schedule.calendarEvent/lists', params })
}

// 月度日历
export function calendarEventMonthCalendar(params?: any) {
    return request.get({ url: '/schedule.calendarEvent/monthCalendar', params })
}

// 事件详情
export function calendarEventDetail(params: any) {
    return request.get({ url: '/schedule.calendarEvent/detail', params })
}

// 添加事件
export function calendarEventAdd(params: any) {
    return request.post({ url: '/schedule.calendarEvent/add', params })
}

// 编辑事件
export function calendarEventEdit(params: any) {
    return request.post({ url: '/schedule.calendarEvent/edit', params })
}

// 删除事件
export function calendarEventDelete(params: any) {
    return request.post({ url: '/schedule.calendarEvent/delete', params })
}

// 吉日列表
export function calendarEventLuckyDays(params?: any) {
    return request.get({ url: '/schedule.calendarEvent/luckyDays', params })
}

// 节假日列表
export function calendarEventHolidays(params?: any) {
    return request.get({ url: '/schedule.calendarEvent/holidays', params })
}

// 批量导入
export function calendarEventBatchImport(params: any) {
    return request.post({ url: '/schedule.calendarEvent/batchImport', params })
}

// 拥堵等级选项
export function calendarEventCongestionOptions() {
    return request.get({ url: '/schedule.calendarEvent/congestionOptions' })
}
