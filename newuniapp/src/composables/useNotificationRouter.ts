/**
 * 消息跳转路由表 —— 唯一来源。
 *
 * 老端把 20+ 条 target_type → 路径的映射硬编码在 notification/index.vue 里，
 * 新增类型必漏。这里集中一张表，未知类型走兜底并上报。
 */

import { navigate } from '@/utils/link'
import { report } from '@/platform/report'

export interface NotificationLike {
    id?: number
    notify_type?: number
    target_type?: string
    target_id?: number
    params?: Record<string, any>
}

/** 通知类型（与后端 Notification::TYPE_* 对齐） */
export const NotifyType = {
    SYSTEM: 1,
    ORDER: 2,
    INTERACT: 3,
    ACTIVITY: 4
} as const

type Handler = (item: NotificationLike) => void

const ROUTES: Record<string, Handler> = {
    order: (i) => navigate({ path: '/packages/pkg-order/pages/detail/detail', query: { id: i.target_id } }),
    order_change: (i) => navigate({ path: '/packages/pkg-service/pages/change/detail', query: { id: i.target_id } }),
    ticket: (i) => navigate({ path: '/packages/pkg-service/pages/aftersale/ticket-detail', query: { id: i.target_id } }),
    aftersale: (i) => navigate({ path: '/packages/pkg-service/pages/aftersale/ticket-detail', query: { id: i.target_id } }),
    complaint: (i) => navigate({ path: '/packages/pkg-service/pages/aftersale/complaint-detail', query: { id: i.target_id } }),
    callback: (i) => navigate({ path: '/packages/pkg-service/pages/aftersale/callback-detail', query: { id: i.target_id } }),
    questionnaire: (i) => navigate({ path: '/packages/pkg-service/pages/questionnaire/detail', query: { id: i.target_id } }),
    activity: (i) => navigate({ path: '/packages/pkg-catalog/pages/case-list/case-list', query: { id: i.target_id } }),
    dynamic: (i) => navigate({ path: '/packages/pkg-catalog/pages/case-list/case-list', query: { id: i.target_id } }),
    article: (i) => navigate({ path: '/packages/pkg-catalog/pages/article-detail/article-detail', query: { id: i.target_id } }),
    staff: (i) => navigate({ path: '/packages/pkg-catalog/pages/staff-detail/staff-detail', query: { id: i.target_id } }),
    waitlist: () => navigate({ path: '/pages/order/order' }),

    // ---- 服务人员工作台（P0 补充）----
    // staff_dynamic 是工作台侧的动态审核结果通知：落点必须是工作台的动态列表，
    // 不是用户端的 case-list（那上面只有已发布的对外内容，看不到自己的待审/被拒记录）。
    staff_dynamic: () => navigate({ path: '/packages/pkg-staff/pages/dynamic-list/dynamic-list' }),
    staff_work: () => navigate({ path: '/packages/pkg-staff/pages/work-list/work-list' }),
    staff_certificate: () => navigate({ path: '/packages/pkg-staff/pages/certificate-list/certificate-list' }),
    staff_order: (i) => navigate({ path: '/packages/pkg-staff/pages/order-detail/order-detail', query: { id: i.target_id } }),
    staff_order_list: () => navigate({ path: '/packages/pkg-staff/pages/order-list/order-list' }),
    staff_schedule: () => navigate({ path: '/packages/pkg-staff/pages/schedule/schedule' }),
    staff_settlement: () => navigate({ path: '/packages/pkg-staff/pages/settlement-list/settlement-list' })
}

/** 没有 target_type 时按 notify_type 兜底 */
const TYPE_FALLBACK: Record<number, Handler> = {
    [NotifyType.ORDER]: () => navigate({ path: '/pages/order/order' }),
    [NotifyType.ACTIVITY]: () => navigate({ path: '/packages/pkg-catalog/pages/case-list/case-list' }),
    [NotifyType.INTERACT]: () => navigate({ path: '/packages/pkg-catalog/pages/case-list/case-list' })
}

export function resolveNotificationRoute(item: NotificationLike) {
    const key = (item.target_type || '').trim()

    if (key && ROUTES[key]) {
        ROUTES[key](item)
        return
    }

    const fallback = item.notify_type ? TYPE_FALLBACK[item.notify_type] : undefined
    if (fallback) {
        fallback(item)
        return
    }

    // 系统通知或未知类型：留在消息列表，只提示
    report('page_error', { message: '未知消息类型', extra: { target_type: key, notify_type: item.notify_type } })
    uni.showToast({ title: '该消息暂无可跳转页面', icon: 'none' })
}
