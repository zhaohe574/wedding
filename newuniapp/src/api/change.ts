import request from '@/request'
import type { OrderChangeStatus, OrderPauseStatus } from '@shared/contracts'

/**
 * 订单变更：改期 / 加项 / 暂停。
 *
 * 三条申请链路参数完全不同，不要合并成一个函数：
 *   改期：order_id + new_date + reason? + attach_images?
 *   加项：order_id + staff_id + package_id + service_date + reason?
 *   暂停：order_id + pause_type(1-4) + start_date + end_date + reason(必填) + proof_images?
 */

export interface ChangeListItem {
    id: number
    order_id: number
    change_type: number
    status: OrderChangeStatus
    status_desc?: string
    reason?: string
    create_time: string
    [k: string]: any
}

export interface PauseListItem {
    id: number
    order_id: number
    pause_type: number
    status: OrderPauseStatus
    status_desc?: string
    start_date?: string
    end_date?: string
    reason?: string
    create_time: string
    [k: string]: any
}

/** 变更记录列表 @endpoint order_change.lists */
export function getChangeList(params: { page?: number; limit?: number; order_id?: number; status?: number }) {
    return request.get<any>('/order_change/lists', params, { auth: true, silent: true })
}

/** @endpoint order_change.detail */
export function getChangeDetail(params: { id: number }) {
    return request.get<any>('/order_change/detail', params, { auth: true, silent: true })
}

/** 订单是否可变更（含可变更类型与限制说明） @endpoint order_change.checkCanChange */
export function checkCanChange(params: { order_id: number }) {
    return request.get<any>('/order_change/checkCanChange', params, { auth: true, silent: true })
}

/** 申请改期（幂等，避免重复提交） @endpoint order_change.applyDateChange */
export function applyDateChange(data: {
    order_id: number
    new_date: string
    reason?: string
    attach_images?: string[]
}) {
    return request.post<{ change_id: number }>('/order_change/applyDateChange', data as any, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}

/** 申请加项 @endpoint order_change.applyAddItem */
export function applyAddItem(data: {
    order_id: number
    staff_id: number
    package_id: number
    service_date: string
    reason?: string
}) {
    return request.post<{ change_id: number; add_price?: number }>('/order_change/applyAddItem', data as any, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}

/** 申请暂停 @endpoint order_change.applyPause */
export function applyPause(data: {
    order_id: number
    pause_type: number
    reason: string
    start_date: string
    end_date: string
    proof_images?: string[]
}) {
    return request.post<{ pause_id: number }>('/order_change/applyPause', data as any, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}

/** 取消变更申请 @endpoint order_change.cancel */
export function cancelChange(params: { id: number }) {
    return request.post<null>('/order_change/cancel', params, { auth: true, loading: true })
}

/** 暂停详情 @endpoint order_change.pauseDetail */
export function getPauseDetail(params: { id: number }) {
    return request.get<any>('/order_change/pauseDetail', params, { auth: true, silent: true })
}

/** 我的暂停列表 @endpoint order_change.pauseLists */
export function getPauseList(params: { page?: number; limit?: number; order_id?: number }) {
    return request.get<any>('/order_change/pauseLists', params, { auth: true, silent: true })
}

/** 取消暂停 @endpoint order_change.cancelPause */
export function cancelPause(params: { id: number }) {
    return request.post<null>('/order_change/cancelPause', params, { auth: true, loading: true })
}
