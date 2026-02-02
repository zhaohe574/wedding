import adminRequest from '@/utils/request/admin'

export function adminLogin(data: any) {
    return adminRequest.post({ url: '/login/account', data }, { withToken: false, isAuth: false })
}

export function adminLogout() {
    return adminRequest.post({ url: '/login/logout' })
}

export function adminOverview(params?: any) {
    return adminRequest.get({ url: '/financial.financial_report/overview', data: params })
}

export function adminIncomeTrend(params?: any) {
    return adminRequest.get({ url: '/financial.financial_report/incomeTrend', data: params })
}

export function adminOrderStats(params?: any) {
    return adminRequest.get({ url: '/order.order/statistics', data: params })
}
