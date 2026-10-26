import request from '@/utils/request'

export function adminDashboardOverview(params?: any) {
    return request.get({ url: '/dashboard/overview', data: params }, { isAuth: true })
}

export function adminDashboardIncomeTrend(params?: any) {
    return request.get({ url: '/dashboard/incomeTrend', data: params }, { isAuth: true })
}

export function adminDashboardOrderStats(params?: any) {
    return request.get({ url: '/dashboard/orderStats', data: params }, { isAuth: true })
}

export function adminDashboardTeamOverview(params?: any) {
    return request.get({ url: '/dashboard/teamOverview', data: params }, { isAuth: true })
}

export function adminDashboardAccess() {
    return request.get({ url: '/dashboard/access' }, { isAuth: true })
}
