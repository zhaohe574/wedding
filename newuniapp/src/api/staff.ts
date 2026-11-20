import request from '@/request'

/**
 * 服务人员中心（只读）。
 *
 * 后端整套 /staff_center/* 有 59 个接口，但小程序端只做「我的订单」和「我的档期」两件事，
 * 其余（接单、结案、结算、证书、作品管理）仍在后台做。
 * 入口受 feature_switch.staff_center 与用户 is_staff 双重控制。
 */

/** @endpoint staff_center.orderLists */
export function getStaffOrderList(params: { page?: number; page_size?: number; status?: number }) {
    return request.get<any>('/staff_center/orderLists', params, { auth: true, silent: true })
}

/** @endpoint staff_center.orderDetail */
export function getStaffOrderDetail(params: { id: number }) {
    return request.get<any>('/staff_center/orderDetail', params, { auth: true, silent: true })
}

/** @endpoint staff_center.scheduleMonth */
export function getStaffScheduleMonth(params: { year: number; month: number }) {
    return request.get<any>('/staff_center/scheduleMonth', params, { auth: true, silent: true })
}

/** @endpoint staff_center.profile */
export function getStaffProfile() {
    return request.get<any>('/staff_center/profile', {}, { auth: true, silent: true })
}
