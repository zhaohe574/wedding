/**
 * 统一跳转。
 *
 * 三件事：
 *   1. 把后台装修里配置的「历史路径」映射到新路径（后台数据不会跟着改）
 *   2. 区分 tab 页（switchTab）与普通页（navigateTo）
 *   3. 目标页尚未实现时给出明确提示，而不是静默失败
 *
 * 老端有三处各自维护的路由映射表（notificationRouteMap / QUICK_ENTRY_KEY_BY_PATH / LEGACY_LINK_MAP），
 * 这里合并为唯一一张。
 */

import { report } from '@/platform/report'

/**
 * 历史路径 → 新路径。
 * 左侧是后台装修数据里已经存着的老路径，不会被改动，因此加了 route-ignore 标记，
 * 让 scripts/check-pages.mjs 跳过这些「来源」只校验「目标」。
 */
const LEGACY_PATH_MAP: Record<string, string> = {
    '/pages/schedule_query/schedule_query': '/pages/query/query', // route-ignore
    '/pages/staff_list/staff_list': '/packages/pkg-catalog/pages/staff-list/staff-list', // route-ignore
    '/pages/staff_detail/staff_detail': '/packages/pkg-catalog/pages/staff-detail/staff-detail', // route-ignore
    '/pages/staff_booking/staff_booking': '/packages/pkg-catalog/pages/booking/booking', // route-ignore
    '/pages/news/news': '/packages/pkg-catalog/pages/case-list/case-list', // route-ignore
    '/pages/news_detail/news_detail': '/packages/pkg-catalog/pages/article-detail/article-detail', // route-ignore
    '/pages/dynamic/dynamic': '/packages/pkg-catalog/pages/case-list/case-list', // route-ignore
    '/pages/dynamic_detail/dynamic_detail': '/packages/pkg-catalog/pages/case-list/case-list', // route-ignore
    '/pages/payment/payment': '/packages/pkg-order/pages/payment-result/payment-result', // route-ignore
    '/pages/order/order': '/pages/order/order',
    '/pages/user/user': '/pages/user/user',
    '/pages/index/index': '/pages/index/index',

    // 后台/装修数据里带 packages/pages 前缀的老路径（后端 ConfigService、装修组件、消息 target 都会返回这些）
    '/packages/pages/customer_service/customer_service': '/pages/customer-service/customer-service', // route-ignore
    // 这两条超过 printWidth，prettier 会把值折到下一行，导致 `route-ignore` 与路径不在同一行，
    // scripts/check-pages.mjs（按行判定）会误报「引用了未注册的页面」。故锁定单行。
    // prettier-ignore
    '/packages/pages/staff_detail/staff_detail': '/packages/pkg-catalog/pages/staff-detail/staff-detail', // route-ignore
    '/packages/pages/staff_booking/staff_booking': '/packages/pkg-catalog/pages/booking/booking', // route-ignore
    '/packages/pages/staff_list/staff_list': '/packages/pkg-catalog/pages/staff-list/staff-list', // route-ignore
    '/packages/pages/dynamic_list/dynamic_list': '/packages/pkg-catalog/pages/case-list/case-list', // route-ignore
    '/packages/pages/news/news': '/packages/pkg-catalog/pages/case-list/case-list', // route-ignore
    // prettier-ignore
    '/packages/pages/news_detail/news_detail': '/packages/pkg-catalog/pages/article-detail/article-detail', // route-ignore
    '/packages/pages/order_detail/order_detail': '/packages/pkg-order/pages/detail/detail', // route-ignore
    '/packages/pages/order_confirm/order_confirm': '/packages/pkg-catalog/pages/booking/booking', // route-ignore
    '/packages/pages/aftersale/aftersale': '/packages/pkg-service/pages/aftersale/index', // route-ignore
    '/packages/pages/customer_service/index': '/pages/customer-service/customer-service', // route-ignore

    // ---- 后台装修「移动端页面链接」可选清单里的旧路径 ----
    // 来源：admin/src/components/link/shop-pages.vue（后台选完存的是这些老路径，不会随客户端改版而变）。
    // 之前只映射了 9 条，剩下 20+ 条一点就提示「功能开发中」。以下逐条落到 v2 的对应页面。
    // 个人与设置
    '/pages/user_set/user_set': '/packages/pkg-user/pages/settings/settings', // route-ignore
    '/pages/user_data/user_data': '/packages/pkg-user/pages/profile/profile', // route-ignore
    // prettier-ignore
    '/pages/change_password/change_password': '/packages/pkg-user/pages/settings/settings', // route-ignore
    '/pages/bind_mobile/bind_mobile': '/packages/pkg-user/pages/profile/profile', // route-ignore
    // 用户资产
    // prettier-ignore
    '/packages/pages/collection/collection': '/packages/pkg-user/pages/favorite/favorite', // route-ignore
    '/pages/collection/collection': '/packages/pkg-user/pages/favorite/favorite', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_favorite/staff_favorite': '/packages/pkg-user/pages/favorite/favorite', // route-ignore
    // prettier-ignore
    '/packages/pages/my_activity/my_activity': '/packages/pkg-user/pages/activity/activity', // route-ignore
    // prettier-ignore
    '/packages/pages/activity_registration/list': '/packages/pkg-user/pages/activity/activity', // route-ignore
    '/packages/pages/notification/index': '/pages/message/message', // route-ignore
    '/pages/notification/index': '/pages/message/message', // route-ignore
    // 订单与售后
    '/pages/order/list': '/pages/order/order', // route-ignore
    // prettier-ignore
    '/packages/pages/waitlist/waitlist': '/packages/pkg-order/pages/waitlist/waitlist', // route-ignore
    '/packages/pages/order_change/list': '/packages/pkg-service/pages/change/list', // route-ignore
    // prettier-ignore
    '/packages/pages/order_change/apply_date': '/packages/pkg-service/pages/change/apply-date', // route-ignore
    // prettier-ignore
    '/packages/pages/order_change/apply_pause': '/packages/pkg-service/pages/change/apply-pause', // route-ignore
    // prettier-ignore
    '/packages/pages/order_change/apply_add_item': '/packages/pkg-service/pages/change/apply-addon', // route-ignore
    // prettier-ignore
    '/packages/pages/aftersale/index': '/packages/pkg-service/pages/aftersale/index', // route-ignore
    '/pages/aftersale/index': '/packages/pkg-service/pages/aftersale/index', // route-ignore
    // prettier-ignore
    '/packages/pages/aftersale/ticket': '/packages/pkg-service/pages/aftersale/ticket-list', // route-ignore
    // prettier-ignore
    '/packages/pages/aftersale/create_ticket': '/packages/pkg-service/pages/aftersale/ticket-create', // route-ignore
    // prettier-ignore
    '/packages/pages/aftersale/complaint': '/packages/pkg-service/pages/aftersale/complaint-list', // route-ignore
    // prettier-ignore
    '/packages/pages/aftersale/create_complaint': '/packages/pkg-service/pages/aftersale/complaint-create', // route-ignore
    // prettier-ignore
    '/packages/pages/aftersale/callback': '/packages/pkg-service/pages/aftersale/callback-list', // route-ignore
    '/packages/pages/review/list': '/packages/pkg-order/pages/review/list', // route-ignore
    '/pages/review/list': '/packages/pkg-order/pages/review/list', // route-ignore
    // 内容与协议
    '/pages/as_us/as_us': '/pages/about/about', // route-ignore
    '/packages/pages/agreement/agreement': '/pages/agreement/agreement', // route-ignore
    // prettier-ignore
    '/packages/pages/case_list/case_list': '/packages/pkg-catalog/pages/case-list/case-list', // route-ignore
    '/packages/pages/search/search': '/pages/search/search', // route-ignore
    // prettier-ignore
    '/packages/pages/payment_result/payment_result': '/packages/pkg-order/pages/payment-result/payment-result', // route-ignore
    // prettier-ignore
    '/packages/pages/dynamic_detail/dynamic_detail': '/packages/pkg-catalog/pages/feed-detail/feed-detail', // route-ignore
    // 服务工作台
    // prettier-ignore
    '/packages/pages/staff_center/staff_center': '/packages/pkg-staff/pages/center/center', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_profile/staff_profile': '/packages/pkg-staff/pages/profile/profile', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_order_list/staff_order_list': '/packages/pkg-staff/pages/order-list/order-list', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_order_detail/staff_order_detail': '/packages/pkg-staff/pages/order-detail/order-detail', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_schedule/staff_schedule': '/packages/pkg-staff/pages/schedule/schedule', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_certificate_list/staff_certificate_list': '/packages/pkg-staff/pages/certificate-list/certificate-list', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_work_list/staff_work_list': '/packages/pkg-staff/pages/work-list/work-list', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_work_detail/staff_work_detail': '/packages/pkg-staff/pages/work-list/work-list', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_package_list/staff_package_list': '/packages/pkg-staff/pages/package-list/package-list', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_addon_list/staff_addon_list': '/packages/pkg-staff/pages/addon-list/addon-list', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_dynamic_list/staff_dynamic_list': '/packages/pkg-staff/pages/dynamic-list/dynamic-list', // route-ignore
    // prettier-ignore
    '/packages/pages/admin_dashboard/admin_dashboard': '/packages/pkg-staff/pages/dashboard/dashboard', // route-ignore
    // prettier-ignore
    '/packages/pages/couple_questionnaire/detail': '/packages/pkg-service/pages/questionnaire/detail', // route-ignore
    // prettier-ignore
    '/packages/pages/order_change/pause_detail': '/packages/pkg-service/pages/change/detail', // route-ignore
    // prettier-ignore
    '/packages/pages/review/detail': '/packages/pkg-order/pages/review/list', // route-ignore
    // 服务端服务号/订阅消息模板历史跳转路径兜底（配合 App.vue onPageNotFound 防 404）
    // prettier-ignore
    '/packages/pages/activity_registration/detail': '/packages/pkg-user/pages/activity/activity', // route-ignore
    // prettier-ignore
    '/packages/pages/aftersale/ticket_detail': '/packages/pkg-service/pages/aftersale/ticket-detail', // route-ignore
    // prettier-ignore
    '/packages/pages/order_change/change_detail': '/packages/pkg-service/pages/change/detail', // route-ignore
    // prettier-ignore
    '/packages/pages/staff_settlement/staff_settlement': '/packages/pkg-staff/pages/center/center' // route-ignore
}

/**
 * tabBar 页面，必须用 switchTab（navigateTo 打开 tab 页会静默失败）。
 * 改版 v2 四段：首页 / 动态 / 查档期 / 我的。
 * 订单、消息已移出 tabBar，改回普通页面（从「我的」进入），走 navigateTo。
 */
export const TAB_PAGES = new Set([
    '/pages/index/index',
    '/pages/feed/feed',
    '/pages/query/query',
    '/pages/user/user'
])

/**
 * 已实现的页面白名单。
 * 新增页面时同步登记，否则装修里配了链接也点不动 —— 这是有意的：
 * 宁可提示「功能开发中」，也不要静默跳转失败。
 */
const IMPLEMENTED = new Set<string>([
    '/pages/index/index',
    '/pages/feed/feed',
    '/pages/query/query',
    '/pages/user/user',
    '/pages/order/order',
    '/pages/message/message',
    '/pages/login/login',
    '/pages/agreement/agreement',
    '/pages/customer-service/customer-service',
    '/pages/about/about',
    '/pages/webview/webview',
    '/pages/search/search',
    '/pages/message/detail',
    '/packages/pkg-catalog/pages/staff-list/staff-list',
    '/packages/pkg-catalog/pages/staff-detail/staff-detail',
    '/packages/pkg-catalog/pages/booking/booking',
    '/packages/pkg-catalog/pages/article-detail/article-detail',
    '/packages/pkg-catalog/pages/case-list/case-list',
    '/packages/pkg-catalog/pages/feed-detail/feed-detail',
    '/packages/pkg-order/pages/detail/detail',
    '/packages/pkg-order/pages/review/list',
    '/packages/pkg-order/pages/review/publish',
    '/packages/pkg-order/pages/waitlist/waitlist',
    '/packages/pkg-order/pages/waitlist/apply',
    '/packages/pkg-order/pages/payment-result/payment-result',
    '/packages/pkg-service/pages/aftersale/index',
    '/packages/pkg-service/pages/aftersale/ticket-list',
    '/packages/pkg-service/pages/aftersale/ticket-detail',
    '/packages/pkg-service/pages/aftersale/ticket-create',
    '/packages/pkg-service/pages/aftersale/complaint-list',
    '/packages/pkg-service/pages/aftersale/complaint-create',
    '/packages/pkg-service/pages/aftersale/complaint-detail',
    '/packages/pkg-service/pages/aftersale/callback-list',
    '/packages/pkg-service/pages/aftersale/callback-detail',
    '/packages/pkg-service/pages/questionnaire/detail',
    '/packages/pkg-service/pages/concierge/concierge',
    '/packages/pkg-service/pages/change/list',
    '/packages/pkg-service/pages/change/detail',
    '/packages/pkg-service/pages/change/apply-date',
    '/packages/pkg-service/pages/change/apply-pause',
    '/packages/pkg-service/pages/change/apply-addon',
    '/packages/pkg-user/pages/profile/profile',
    '/packages/pkg-user/pages/settings/settings',
    '/packages/pkg-user/pages/favorite/favorite',
    '/packages/pkg-user/pages/activity/activity',
    '/packages/pkg-staff/pages/order-list/order-list',
    '/packages/pkg-staff/pages/order-detail/order-detail',
    '/packages/pkg-staff/pages/schedule/schedule',
    '/packages/pkg-staff/pages/center/center',
    '/packages/pkg-staff/pages/profile/profile',
    '/packages/pkg-staff/pages/profile-edit/profile-edit',
    '/packages/pkg-staff/pages/certificate-list/certificate-list',
    '/packages/pkg-staff/pages/certificate-edit/certificate-edit',
    '/packages/pkg-staff/pages/work-list/work-list',
    '/packages/pkg-staff/pages/work-edit/work-edit',
    '/packages/pkg-staff/pages/package-list/package-list',
    '/packages/pkg-staff/pages/addon-list/addon-list',
    '/packages/pkg-staff/pages/dynamic-list/dynamic-list',
    '/packages/pkg-staff/pages/dynamic-publish/dynamic-publish',
    '/packages/pkg-staff/pages/dashboard/dashboard'
])

export interface LinkConfig {
    path?: string
    type?: string
    query?: Record<string, any>
    [key: string]: any
}

function buildQuery(query?: Record<string, any>): string {
    if (!query) return ''
    const parts = Object.keys(query)
        .filter((k) => query[k] !== undefined && query[k] !== null && query[k] !== '')
        .map((k) => `${encodeURIComponent(k)}=${encodeURIComponent(String(query[k]))}`)
    return parts.length ? `?${parts.join('&')}` : ''
}

/** 解析链接配置为最终 url */
export function resolveLink(link?: LinkConfig | string): string | null {
    if (!link) return null
    const raw = typeof link === 'string' ? link : link.path
    if (!raw) return null
    if (raw.startsWith('http')) return raw

    const path = LEGACY_PATH_MAP[raw] || raw
    const query = typeof link === 'string' ? '' : buildQuery(link.query)
    return `${path}${query}`
}

/** 统一跳转入口 */
export function navigate(link?: LinkConfig | string, fallbackTab = '/pages/index/index') {
    const url = resolveLink(link)
    if (!url) {
        report('page_error', { message: '跳转目标为空' })
        return
    }

    // 外链走 webview
    if (url.startsWith('http')) {
        uni.navigateTo({ url: `/pages/webview/webview?url=${encodeURIComponent(url)}` })
        return
    }

    const path = url.split('?')[0]

    if (!IMPLEMENTED.has(path)) {
        report('page_error', { message: '目标页未实现', endpoint: path })
        uni.showToast({ title: '功能开发中', icon: 'none' })
        return
    }

    if (TAB_PAGES.has(path)) {
        uni.switchTab({ url: path })
        return
    }

    uni.navigateTo({ url, fail: () => uni.switchTab({ url: fallbackTab }) })
}

/** 返回上一页，无栈时回首页 */
export function back(fallbackTab = '/pages/index/index') {
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: fallbackTab })
}

/**
 * 处理微信原生页面找不到（onPageNotFound）：
 * 微信服务号/订阅消息卡片直达旧包路径时，映射并重定向至新版对应页面；未知路径或防环回首页。
 */
export function redirectNotFound(rawPath?: string, query?: Record<string, any>): void {
    const cleaned = String(rawPath || '').trim()
    if (!cleaned) {
        report('page_error', { message: '页面不存在', endpoint: '' })
        uni.switchTab({ url: '/pages/index/index' })
        return
    }
    const normalized = cleaned.startsWith('/') ? cleaned : `/${cleaned}`
    const targetUrl = resolveLink({ path: normalized, query })
    const targetPath = targetUrl ? targetUrl.split('?')[0] : ''

    if (!targetUrl || !targetPath || !IMPLEMENTED.has(targetPath) || targetPath === normalized) {
        report('page_error', { message: '页面不存在', endpoint: normalized })
        uni.switchTab({ url: '/pages/index/index' })
        return
    }

    if (TAB_PAGES.has(targetPath)) {
        uni.switchTab({ url: targetPath })
        return
    }

    uni.redirectTo({
        url: targetUrl,
        fail: () =>
            uni.reLaunch({
                url: targetUrl,
                fail: () => uni.switchTab({ url: '/pages/index/index' })
            })
    })
}
