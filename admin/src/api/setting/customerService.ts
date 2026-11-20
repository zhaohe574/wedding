import request from '@/utils/request'

// ==================== 客服与咨询配置 ====================
// 小程序「统一客服」兜底卡片读的是 customer_service 这组配置：
// name / role / service_time / tips / mobile。后台此前没有任何入口，只能改库。

export function customerServiceGetConfig() {
    return request.get({ url: '/setting.customerService/getConfig' })
}

export function customerServiceSetConfig(params: any) {
    return request.post({ url: '/setting.customerService/setConfig', params })
}
