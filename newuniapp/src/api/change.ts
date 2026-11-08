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

/**
 * 订单是否可变更（含不可变更原因）。
 *
 * 后端返回 `{ can_change, message, order:{ id, order_sn, service_date, order_status, is_paused } }`。
 * 判定规则（`OrderChange::checkCanChange`）：`order_status ∈ {2 待服务, 3 服务中}`
 * + 无待处理变更申请 + 订单未暂停。
 *
 * **18 屏的「改期」入口显隐与置灰直接用 `can_change` / `message`**，不要在页面里重算状态组合。
 * @endpoint order_change.checkCanChange
 */
export function checkCanChange(params: { order_id: number }) {
    return request.get<{
        /**
         * 后端 `OrderChange::checkCanChange` 返回的是 PHP bool，但存量页面（change/apply-date.vue）
         * 用 `=== 0` 判断不可变更，所以这里显式保留 number 以兼容两种写法。
         * **新代码统一用 `Boolean(can_change)` 或 `!can_change` 判断**，不要再写 `=== 1`。
         */
        can_change: boolean | number
        message: string
        order?: {
            id: number
            order_sn: string
            service_date?: string
            order_status: number
            is_paused?: number | boolean
        }
    }>('/order_change/checkCanChange', params, { auth: true, silent: true })
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

// ---------------------------------------------------------------- 选项接口（M-04 接线）
// 23 屏「改期原因」chips 需要枚举来源。后端实际可用的只有下面两个。

export interface OrderOptionItem {
    value: number
    label: string
}

/** 变更类型选项：1 改期 / 2 加项 / 3 附加服务变更 @endpoint order_change.typeOptions */
export function getChangeTypeOptions() {
    return request.get<OrderOptionItem[]>('/order_change/typeOptions', {}, { silent: true })
}

/**
 * 暂停类型选项：疫情 / 突发事件 / 个人原因 / 其他。
 *
 * ⚠ 语义偏差提醒：这是**暂停**的原因枚举，但后端没有单独的「改期原因」枚举，
 * 23 屏改期原因目前只能复用它（或由前端维护一份常量并标注来源）。
 * 设计稿写的「家庭原因 / 工作冲突 / 场地变更 / 其他」与后端枚举**不一致**，
 * 需要产品确认是改稿还是让后端补 `dateChangeReasonOptions`（我已列缺口）。
 * @endpoint order_change.pauseTypeOptions
 */
export function getPauseTypeOptions() {
    return request.get<OrderOptionItem[]>('/order_change/pauseTypeOptions', {}, { silent: true })
}

/*
 * `order_change.timeSlotOptions` 不封装：后端 `OrderChangeController::timeSlotOptions`
 * 直接 `return $this->fail('场次能力已下线')`，封装出来只会让页面弹一个「能力已下线」的错。
 * 若将来恢复，再按 `[{ value: 0, label: '全天' }]` 的形态补。
 */
