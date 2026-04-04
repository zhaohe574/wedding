import request from '@/utils/request'

export function getTransactionSettingsConfig() {
    return request.get({ url: '/setting.transaction_settings/getConfig' })
}

export function setTransactionSettingsConfig(params: any) {
    return request.post({ url: '/setting.transaction_settings/setConfig', params })
}
