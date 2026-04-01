import request from '@/utils/request'

// ==================== 我的资料 ====================

export function myProfile() {
    return request.get({ url: '/ops.staff/myProfile' })
}

export function myProfileUpdate(params: any) {
    return request.post({ url: '/ops.staff/myProfileUpdate', params })
}

export function myProfilePackageConfig(params?: any) {
    return request.get({ url: '/ops.staff/myProfilePackageConfig', params })
}

export function myProfileRegionEnabledCityOptions() {
    return request.get({ url: '/ops.staff/myProfileRegionEnabledCityOptions' })
}

export function myProfileRegionDistrictOptions(params: { city_code: string }) {
    return request.get({ url: '/ops.staff/myProfileRegionDistrictOptions', params })
}

export function myProfileConfigurePackages(params: any) {
    return request.post({ url: '/ops.staff/myProfileConfigurePackages', params })
}

export function myProfileUpdatePackageConfig(params: any) {
    return request.post({ url: '/ops.staff/myProfileUpdatePackageConfig', params })
}

export function myProfileCreatePackage(params: any) {
    return request.post({ url: '/ops.staff/myProfileCreatePackage', params })
}

export function myProfileUpdateStaffPackage(params: any) {
    return request.post({ url: '/ops.staff/myProfileUpdateStaffPackage', params })
}

export function myProfileDeletePackage(params: any) {
    return request.post({ url: '/ops.staff/myProfileDeletePackage', params })
}

export function myProfileAddonList(params?: any) {
    return request.get({ url: '/ops.staff/myProfileAddonList', params })
}

export function myProfileAddonAdd(params: any) {
    return request.post({ url: '/ops.staff/myProfileAddonAdd', params })
}

export function myProfileAddonUpdate(params: any) {
    return request.post({ url: '/ops.staff/myProfileAddonUpdate', params })
}

export function myProfileAddonDelete(params: any) {
    return request.post({ url: '/ops.staff/myProfileAddonDelete', params })
}

export function myProfileBannerList(params?: any) {
    return request.get({ url: '/ops.staff/myProfileBannerList', params })
}

export function myProfileBannerAdd(params: any) {
    return request.post({ url: '/ops.staff/myProfileBannerAdd', params })
}

export function myProfileBannerEdit(params: any) {
    return request.post({ url: '/ops.staff/myProfileBannerEdit', params })
}

export function myProfileBannerDelete(params: any) {
    return request.post({ url: '/ops.staff/myProfileBannerDelete', params })
}

export function myProfileBannerSort(params: any) {
    return request.post({ url: '/ops.staff/myProfileBannerSort', params })
}

export function myProfileBannerConfig(params: any) {
    return request.post({ url: '/ops.staff/myProfileBannerConfig', params })
}

// ==================== 档期日历 ====================

export function myCalendar(params?: any) {
    return request.get({ url: '/ops.schedule/myCalendar', params })
}

export function myCalendarSetStatus(params: any) {
    return request.post({ url: '/ops.schedule/myCalendarSetStatus', params })
}

export function myCalendarBatchSet(params: any) {
    return request.post({ url: '/ops.schedule/myCalendarBatchSet', params })
}

export function myCalendarUnlock(params: any) {
    return request.post({ url: '/ops.schedule/myCalendarUnlock', params })
}

export function myCalendarStatistics(params?: any) {
    return request.get({ url: '/ops.schedule/myCalendarStatistics', params })
}

// ==================== 档期规则 ====================

export function myRules(params?: any) {
    return request.get({ url: '/ops.scheduleRule/myRules', params })
}

export function myRuleDetail(params: any) {
    return request.get({ url: '/ops.scheduleRule/myRuleDetail', params })
}

export function myRuleSave(params: any) {
    return request.post({ url: '/ops.scheduleRule/myRuleSave', params })
}

export function myRuleDelete(params: any) {
    return request.post({ url: '/ops.scheduleRule/myRuleDelete', params })
}

export function myRuleChangeStatus(params: any) {
    return request.post({ url: '/ops.scheduleRule/myRuleChangeStatus', params })
}

export function myRuleTemplate() {
    return request.get({ url: '/ops.scheduleRule/myRuleTemplate' })
}

// ==================== 预约列表 ====================

export function myBookings(params?: any) {
    return request.get({ url: '/ops.booking/myBookings', params })
}

export function myBookingDetail(params: any) {
    return request.get({ url: '/ops.booking/myBookingDetail', params })
}

export function myBookingConfirm(params: any) {
    return request.post({ url: '/ops.booking/myBookingConfirm', params })
}

export function myBookingCancel(params: any) {
    return request.post({ url: '/ops.booking/myBookingCancel', params })
}

export function myBookingStatistics() {
    return request.get({ url: '/ops.booking/myBookingStatistics' })
}

// ==================== 候补列表 ====================

export function myWaitlist(params?: any) {
    return request.get({ url: '/ops.waitlist/myWaitlist', params })
}

export function myWaitlistDetail(params: any) {
    return request.get({ url: '/ops.waitlist/myWaitlistDetail', params })
}

export function myWaitlistBatchNotify(params: any) {
    return request.post({ url: '/ops.waitlist/myWaitlistBatchNotify', params })
}

export function myWaitlistNotify(params: any) {
    return request.post({ url: '/ops.waitlist/myWaitlistNotify', params })
}

export function myWaitlistConvert(params: any) {
    return request.post({ url: '/ops.waitlist/myWaitlistConvert', params })
}

export function myWaitlistInvalidate(params: any) {
    return request.post({ url: '/ops.waitlist/myWaitlistInvalidate', params })
}

export function myWaitlistStatistics() {
    return request.get({ url: '/ops.waitlist/myWaitlistStatistics' })
}

// ==================== 订单列表 ====================

export function myOrders(params?: any) {
    return request.get({ url: '/ops.order/myOrders', params })
}

export function myOrderDetail(params: any) {
    return request.get({ url: '/ops.order/myOrderDetail', params })
}

export function myOrderConfirm(params: any) {
    return request.post({ url: '/ops.order/myOrderConfirm', params })
}

export function myOrderStartService(params: any) {
    return request.post({ url: '/ops.order/myOrderStartService', params })
}

export function myOrderComplete(params: any) {
    return request.post({ url: '/ops.order/myOrderComplete', params })
}

export function myOrderStatistics(params?: any) {
    return request.get({ url: '/ops.order/myOrderStatistics', params })
}

// ==================== 动态管理 ====================

export function myDynamics(params?: any) {
    return request.get({ url: '/growth.dynamic/myDynamics', params })
}

export function myDynamicDetail(params: any) {
    return request.get({ url: '/growth.dynamic/myDynamicDetail', params })
}

export function myDynamicAdd(params: any) {
    return request.post({ url: '/growth.dynamic/myDynamicAdd', params })
}

export function myDynamicEdit(params: any) {
    return request.post({ url: '/growth.dynamic/myDynamicEdit', params })
}

export function myDynamicDelete(params: any) {
    return request.post({ url: '/growth.dynamic/myDynamicDelete', params })
}

export function myDynamicTypeOptions() {
    return request.get({ url: '/growth.dynamic/myDynamicTypeOptions' })
}

export function myDynamicStatusOptions() {
    return request.get({ url: '/growth.dynamic/myDynamicStatusOptions' })
}
