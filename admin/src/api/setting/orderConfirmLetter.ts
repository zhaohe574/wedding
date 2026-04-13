import request from '@/utils/request'

export function getOrderConfirmLetterConfig() {
    return request.get({ url: '/setting.order_confirm_letter/getConfig' })
}

export function setOrderConfirmLetterConfig(params: any) {
    return request.post({ url: '/setting.order_confirm_letter/setConfig', params })
}
