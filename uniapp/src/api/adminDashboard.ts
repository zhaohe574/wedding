import request from '@/utils/request'

export function adminDashboardOverview(params?: any) {
    return request.get({ url: '/admin_dashboard/overview', data: params }, { isAuth: true })
}

export function adminDashboardIncomeTrend(params?: any) {
    return request.get({ url: '/admin_dashboard/incomeTrend', data: params }, { isAuth: true })
}

export function adminDashboardOrderStats(params?: any) {
    return request.get({ url: '/admin_dashboard/orderStats', data: params }, { isAuth: true })
}

export function adminDashboardTeamOverview(params?: any) {
    return request.get({ url: '/admin_dashboard/teamOverview', data: params }, { isAuth: true })
}
