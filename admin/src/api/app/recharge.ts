import request from '@/utils/request'

export function getRechargeConfig() {
    return request.get({ url: '/finance.recharge/getConfig' })
}

// 设置
export function setRechargeConfig(params: any) {
    return request.post({ url: '/finance.recharge/setConfig', params })
}
