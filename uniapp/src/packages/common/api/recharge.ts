import request from '@/utils/request'

export interface RechargeRequest {
    money: string
}

export interface RechargeResponse {
    order_id: number
    from: string
}

export interface RechargeRecordItem {
    id: number
    tips: string
    order_amount: string | number
    create_time: string
}

export interface RechargeRecordResponse {
    lists: RechargeRecordItem[]
}

export interface RechargeConfigResponse {
    user_money: string
    min_amount: number
    status?: number | boolean
}

export interface RechargeRecordRequest {
    page_no: number
    page_size: number
}

//充值
export function recharge(data: RechargeRequest) {
    return request.post<RechargeResponse>({ url: '/recharge/recharge', data }, { isAuth: true })
}

//充值记录
export function rechargeRecord(data: RechargeRecordRequest) {
    return request.get<RechargeRecordResponse>({ url: '/recharge/lists', data }, { isAuth: true })
}

// 充值配置
export function rechargeConfig() {
    return request.get<RechargeConfigResponse>({ url: '/recharge/config' }, { isAuth: true })
}
