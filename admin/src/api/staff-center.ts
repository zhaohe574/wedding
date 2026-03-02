import request from '@/utils/request'

// ==================== 我的资料 ====================

export function myProfile() {
    return request.get({ url: '/staff.staff/myProfile' })
}

export function myProfileUpdate(params: any) {
    return request.post({ url: '/staff.staff/myProfileUpdate', params })
}

export function myProfilePackageConfig(params?: any) {
    return request.get({ url: '/staff.staff/myProfilePackageConfig', params })
}

export function myProfileUpdatePackageConfig(params: any) {
    return request.post({ url: '/staff.staff/myProfileUpdatePackageConfig', params })
}

export function myProfileCreatePackage(params: any) {
    return request.post({ url: '/staff.staff/myProfileCreatePackage', params })
}

export function myProfileUpdateStaffPackage(params: any) {
    return request.post({ url: '/staff.staff/myProfileUpdateStaffPackage', params })
}

export function myProfileDeletePackage(params: any) {
    return request.post({ url: '/staff.staff/myProfileDeletePackage', params })
}

export function myProfileBannerList(params?: any) {
    return request.get({ url: '/staff.staff/myProfileBannerList', params })
}

export function myProfileBannerAdd(params: any) {
    return request.post({ url: '/staff.staff/myProfileBannerAdd', params })
}

export function myProfileBannerEdit(params: any) {
    return request.post({ url: '/staff.staff/myProfileBannerEdit', params })
}

export function myProfileBannerDelete(params: any) {
    return request.post({ url: '/staff.staff/myProfileBannerDelete', params })
}

export function myProfileBannerSort(params: any) {
    return request.post({ url: '/staff.staff/myProfileBannerSort', params })
}

export function myProfileBannerConfig(params: any) {
    return request.post({ url: '/staff.staff/myProfileBannerConfig', params })
}

// ==================== 档期日历 ====================

export function myCalendar(params?: any) {
    return request.get({ url: '/schedule.schedule/myCalendar', params })
}

export function myCalendarSetStatus(params: any) {
    return request.post({ url: '/schedule.schedule/myCalendarSetStatus', params })
}

export function myCalendarBatchSet(params: any) {
    return request.post({ url: '/schedule.schedule/myCalendarBatchSet', params })
}

export function myCalendarUnlock(params: any) {
    return request.post({ url: '/schedule.schedule/myCalendarUnlock', params })
}

export function myCalendarStatistics(params?: any) {
    return request.get({ url: '/schedule.schedule/myCalendarStatistics', params })
}

// ==================== 档期规则 ====================

export function myRules(params?: any) {
    return request.get({ url: '/schedule.scheduleRule/myRules', params })
}

export function myRuleDetail(params: any) {
    return request.get({ url: '/schedule.scheduleRule/myRuleDetail', params })
}

export function myRuleSave(params: any) {
    return request.post({ url: '/schedule.scheduleRule/myRuleSave', params })
}

export function myRuleDelete(params: any) {
    return request.post({ url: '/schedule.scheduleRule/myRuleDelete', params })
}

export function myRuleChangeStatus(params: any) {
    return request.post({ url: '/schedule.scheduleRule/myRuleChangeStatus', params })
}

export function myRuleTemplate() {
    return request.get({ url: '/schedule.scheduleRule/myRuleTemplate' })
}

// ==================== 预约列表 ====================

export function myBookings(params?: any) {
    return request.get({ url: '/schedule.booking/myBookings', params })
}

export function myBookingDetail(params: any) {
    return request.get({ url: '/schedule.booking/myBookingDetail', params })
}

export function myBookingConfirm(params: any) {
    return request.post({ url: '/schedule.booking/myBookingConfirm', params })
}

export function myBookingCancel(params: any) {
    return request.post({ url: '/schedule.booking/myBookingCancel', params })
}

export function myBookingStatistics() {
    return request.get({ url: '/schedule.booking/myBookingStatistics' })
}

// ==================== 候补列表 ====================

export function myWaitlist(params?: any) {
    return request.get({ url: '/schedule.waitlist/myWaitlist', params })
}

export function myWaitlistDetail(params: any) {
    return request.get({ url: '/schedule.waitlist/myWaitlistDetail', params })
}

export function myWaitlistBatchNotify(params: any) {
    return request.post({ url: '/schedule.waitlist/myWaitlistBatchNotify', params })
}

export function myWaitlistNotify(params: any) {
    return request.post({ url: '/schedule.waitlist/myWaitlistNotify', params })
}

export function myWaitlistConvert(params: any) {
    return request.post({ url: '/schedule.waitlist/myWaitlistConvert', params })
}

export function myWaitlistInvalidate(params: any) {
    return request.post({ url: '/schedule.waitlist/myWaitlistInvalidate', params })
}

export function myWaitlistStatistics() {
    return request.get({ url: '/schedule.waitlist/myWaitlistStatistics' })
}

// ==================== 订单列表 ====================

export function myOrders(params?: any) {
    return request.get({ url: '/order.order/myOrders', params })
}

export function myOrderDetail(params: any) {
    return request.get({ url: '/order.order/myOrderDetail', params })
}

export function myOrderStartService(params: any) {
    return request.post({ url: '/order.order/myOrderStartService', params })
}

export function myOrderComplete(params: any) {
    return request.post({ url: '/order.order/myOrderComplete', params })
}

export function myOrderStatistics(params?: any) {
    return request.get({ url: '/order.order/myOrderStatistics', params })
}

// ==================== 动态管理 ====================

export function myDynamics(params?: any) {
    return request.get({ url: '/dynamic.dynamic/myDynamics', params })
}

export function myDynamicDetail(params: any) {
    return request.get({ url: '/dynamic.dynamic/myDynamicDetail', params })
}

export function myDynamicAdd(params: any) {
    return request.post({ url: '/dynamic.dynamic/myDynamicAdd', params })
}

export function myDynamicEdit(params: any) {
    return request.post({ url: '/dynamic.dynamic/myDynamicEdit', params })
}

export function myDynamicDelete(params: any) {
    return request.post({ url: '/dynamic.dynamic/myDynamicDelete', params })
}

export function myDynamicTypeOptions() {
    return request.get({ url: '/dynamic.dynamic/myDynamicTypeOptions' })
}

export function myDynamicStatusOptions() {
    return request.get({ url: '/dynamic.dynamic/myDynamicStatusOptions' })
}
