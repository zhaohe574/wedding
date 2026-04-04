import request from '@/utils/request'

export function getFeatureSwitchConfig() {
    return request.get({ url: '/setting.feature_switch/getConfig' })
}

export function setFeatureSwitchConfig(params: any) {
    return request.post({ url: '/setting.feature_switch/setConfig', params })
}
