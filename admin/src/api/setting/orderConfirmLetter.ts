import request from '@/utils/request'

export function getOrderConfirmLetterConfig() {
    return request.get({ url: '/setting.order_confirm_letter/getConfig' })
}

export function setOrderConfirmLetterConfig(params: any) {
    return request.post({ url: '/setting.order_confirm_letter/setConfig', params })
}

export function getOrderConfirmLetterFonts() {
    return request.get({ url: '/setting.order_confirm_letter/fontLists' })
}

export function setOrderConfirmLetterFont(params: any) {
    return request.post({ url: '/setting.order_confirm_letter/setFont', params })
}

export function deleteOrderConfirmLetterFont(params: any) {
    return request.post({ url: '/setting.order_confirm_letter/deleteFont', params })
}

export function checkOrderConfirmLetterFont() {
    return request.get({ url: '/setting.order_confirm_letter/checkFont' })
}
