import request from '@/request'

/**
 * 活动域（M-01 接线）。
 *
 * 背景：`/dynamic/activity*` 这一组端点在 `endpoints.json` 里齐全，后端也全部有实现，
 * 但小程序 api 层此前**一个方法都没有**，导致 15 「我的活动」屏没有任何数据来源。
 * 本文件只做封装，不改后端。
 *
 * 关键事实（读 `ActivityRegistrationService` 得出，写页面时必须按这个来）：
 *   1. 报名支付是**独立于订单支付**的一套：`activityPayWay` / `activityPrepay` / `activityPayStatus`，
 *      参数是 `registration_id` 而不是 `order_id`，不要复用 `api/pay.ts`。
 *   2. 分段筛选用 `status_group`，取值只有 `all | pending_pay | registered | cancel_refund`，
 *      **没有「已结束」分组** —— 15 屏的「已结束」必须前端按活动时间（dynamic.end_time < now）派生。
 *   3. 列表逐条返回 `registration_status(_desc)`、`pay_status(_desc)`、`cancel_status(_desc)`、
 *      `ticket_price(_label)`、`latest_refund`、内嵌 `dynamic{}` 与 `ticket{}`。
 *   4. 取消报名是「提交申请 + 原因」，不是直接取消：`activityCancelApply` 需要 `reason`。
 *   5. 票种库存必须是 >=1 的整数；活动总名额由后端按各启用票种库存自动汇总，
 *      响应里的 `activity_total_quota` 只读，前端不再提交该字段。
 */

export interface ActivityTicketsParams {
    /** 动态（活动）id，后端必填 */
    dynamic_id: number
}

export interface ActivityRegisterParams {
    dynamic_id: number
    ticket_id: number
    contact_name: string
    contact_mobile: string
}

export interface ActivityPayWayParams {
    registration_id: number
}

export interface ActivityPrepayParams {
    registration_id: number
    /** 通用支付接口：2 = 微信 */
    pay_way?: number
    /** 支付完成后的回跳地址（可选） */
    redirect?: string
}

export interface ActivityPayStatusParams {
    registration_id: number
    payment_sn?: string
}

/** 15 屏分段：只有这四个，没有 finished */
export type ActivityStatusGroup = 'all' | 'pending_pay' | 'registered' | 'cancel_refund'

export interface ActivityRegistrationQuery {
    status_group?: ActivityStatusGroup
    page?: number
    page_size?: number
}

/** 票种列表（报名前选票） @endpoint dynamic.activityTickets */
export function getActivityTickets(params: ActivityTicketsParams) {
    // 后端 DynamicController::activityTickets 走 token 中间件，漏 auth 会让已登录用户也拿到
    // 「请求参数缺token」，票种直接加载不出来。同文件其它 activity* 接口都带 auth: true。
    return request.get<any[]>('/dynamic/activityTickets', params, { auth: true, silent: true })
}

/** 提交活动报名（写接口，幂等） @endpoint dynamic.activityRegister */
export function registerActivity(data: ActivityRegisterParams) {
    return request.post<{ registration_id?: number; id?: number; [k: string]: any }>(
        '/dynamic/activityRegister',
        data as any,
        { auth: true, idempotent: true, dedupe: false, loading: true }
    )
}

/** 报名支付方式 @endpoint dynamic.activityPayWay */
export function getActivityPayWay(params: ActivityPayWayParams) {
    return request.get<{ lists?: any[]; need_pay_amount?: number | string; [k: string]: any }>(
        '/dynamic/activityPayWay',
        params,
        { auth: true, silent: true }
    )
}

/** 报名预支付（返回 config 直接喂 wx.requestPayment） @endpoint dynamic.activityPrepay */
export function activityPrepay(data: ActivityPrepayParams) {
    return request.post<{ config?: Record<string, any>; payment_sn?: string; [k: string]: any }>(
        '/dynamic/activityPrepay',
        {
            registration_id: data.registration_id,
            pay_way: data.pay_way ?? 2,
            redirect: data.redirect ?? ''
        },
        { auth: true, idempotent: true, dedupe: false, loading: true }
    )
}

/** 报名支付结果（唯一可信来源，轮询用） @endpoint dynamic.activityPayStatus */
export function getActivityPayStatus(params: ActivityPayStatusParams) {
    return request.get<{ pay_status?: number; payment?: Record<string, any>; [k: string]: any }>(
        '/dynamic/activityPayStatus',
        params,
        { auth: true, silent: true, retry: 0 }
    )
}

/**
 * 我的活动报名列表。
 * 返回分页结构（`data` + `total` 等），用 `normalizePage` 归一；
 * 逐条字段见文件头说明。**没有「已结束」分组**。
 * @endpoint dynamic.activityRegistrations
 */
export function getMyActivityRegistrations(params?: ActivityRegistrationQuery) {
    return request.get<any>('/dynamic/activityRegistrations', params ?? { status_group: 'all' }, {
        auth: true,
        silent: true
    })
}

/** 报名详情（含入场码/席位等） @endpoint dynamic.activityRegistrationDetail */
export function getActivityRegistrationDetail(params: { id: number }) {
    return request.get<any>('/dynamic/activityRegistrationDetail', params, {
        auth: true,
        silent: true
    })
}

/** 提交取消报名申请（写接口，幂等；reason 必填） @endpoint dynamic.activityCancelApply */
export function cancelActivityRegistration(data: { registration_id: number; reason: string }) {
    return request.post<null>('/dynamic/activityCancelApply', data as any, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}
