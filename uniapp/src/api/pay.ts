import request from '@/utils/request'
import type { PayRequestParams, PayResultResponse, PayWayResponse, PrepayResponse } from '@/types/order'

//支付方式
export function getPayWay(data: PayRequestParams) {
    if (data?.from === 'activity_registration') {
        return request.get<PayWayResponse>({ url: '/dynamic/activityPayWay', data }, { isAuth: true })
    }
    return request.get<PayWayResponse>({ url: '/pay/payWay', data }, { isAuth: true })
}

// 预支付
export function prepay(data: PayRequestParams) {
    if (data?.from === 'activity_registration') {
        return request.post<PrepayResponse>({ url: '/dynamic/activityPrepay', data }, { isAuth: true })
    }
    return request.post<PrepayResponse>({ url: '/pay/prepay', data }, { isAuth: true })
}

// 预支付
export function getPayResult(data: PayRequestParams) {
    if (data?.from === 'activity_registration') {
        return request.get<PayResultResponse>({ url: '/dynamic/activityPayStatus', data }, { isAuth: true })
    }
    return request.get<PayResultResponse>({ url: '/pay/payStatus', data }, { isAuth: true })
}
