import request from '@/utils/request'

// ==================== 经营驾驶舱 ====================
// ⚠️ 前缀是 dashboard.dashboard（目录 dashboard + 类 DashboardController），
// 不是 dashboard —— 写成 /dashboard/overview 会直接「接口不存在」。
//
// 没有 access 探测接口：它未登记权限，非超管调用会被 AuthMiddleware 拒成 403。
// 入口显隐由菜单权限（page.workbench.dashboard 的 perms）负责，页面内不再二次探测。

export function dashboardOverview(params?: any) {
    return request.get({ url: '/dashboard.dashboard/overview', params })
}

export function dashboardIncomeTrend(params?: any) {
    return request.get({ url: '/dashboard.dashboard/incomeTrend', params })
}

export function dashboardOrderStats(params?: any) {
    return request.get({ url: '/dashboard.dashboard/orderStats', params })
}

export function dashboardTeamOverview(params?: any) {
    return request.get({ url: '/dashboard.dashboard/teamOverview', params })
}
