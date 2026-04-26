import request from '@/utils/request'

export function getWecomConfig() {
    return request.get({ url: '/setting.customer_service/getConfig' })
}

export function setWecomConfig(params: any) {
    return request.post({ url: '/setting.customer_service/setConfig', params })
}

export function getWecomRecipients(params?: any) {
    return request.get({ url: '/setting.wecom_recipient/lists', params })
}

export function updateWecomAdvisor(params: { id: number; wecom_userid: string }) {
    return request.post({ url: '/setting.wecom_recipient/updateAdvisor', params })
}

export function testWecomMessage(params: { wecom_userid: string; content?: string }) {
    return request.post({ url: '/setting.customer_service/testWecomMessage', params })
}
