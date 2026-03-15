import request from '@/utils/request'

// ==================== 优惠券管理 ====================

// 优惠券列表
export function getCouponList(params?: any) {
    return request.get({ url: '/growth.campaign/lists', params })
}

// 优惠券详情
export function getCouponDetail(params: any) {
    return request.get({ url: '/growth.campaign/detail', params })
}

// 添加优惠券
export function addCoupon(params: any) {
    return request.post({ url: '/growth.campaign/add', params })
}

// 编辑优惠券
export function editCoupon(params: any) {
    return request.post({ url: '/growth.campaign/edit', params })
}

// 删除优惠券
export function deleteCoupon(params: any) {
    return request.post({ url: '/growth.campaign/delete', params })
}

// 启用/禁用优惠券
export function toggleCouponStatus(params: any) {
    return request.post({ url: '/growth.campaign/toggleStatus', params })
}

// 手动发放优惠券
export function sendCoupon(params: any) {
    return request.post({ url: '/growth.campaign/send', params })
}

// 批量发放优惠券
export function batchSendCoupon(params: any) {
    return request.post({ url: '/growth.campaign/batchSend', params })
}

// 用户优惠券列表
export function getUserCouponList(params?: any) {
    return request.get({ url: '/growth.campaign/userCouponLists', params })
}

// 撤回用户优惠券
export function revokeCoupon(params: any) {
    return request.post({ url: '/growth.campaign/revoke', params })
}

// 优惠券统计
export function getCouponStatistics(params?: any) {
    return request.get({ url: '/growth.campaign/statistics', params })
}

// 优惠券使用统计（按时间）
export function getCouponUseStatistics(params?: any) {
    return request.get({ url: '/growth.campaign/useStatistics', params })
}

// 优惠券排行榜
export function getCouponRanking(params?: any) {
    return request.get({ url: '/growth.campaign/ranking', params })
}

// 获取优惠券类型选项
export function getCouponTypeOptions() {
    return request.get({ url: '/growth.campaign/typeOptions' })
}

// 获取启用的优惠券列表（下拉选择用）
export function getEnabledCouponList() {
    return request.get({ url: '/growth.campaign/enabledList' })
}
