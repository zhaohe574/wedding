import request from '@/utils/request'

// 流失预警列表
export function lossWarningLists(params?: any) {
    return request.get({ url: '/crm.lossWarning/lists', params })
}

// 流失预警详情
export function lossWarningDetail(params: any) {
    return request.get({ url: '/crm.lossWarning/detail', params })
}

// 处理预警
export function lossWarningHandle(params: any) {
    return request.post({ url: '/crm.lossWarning/handle', params })
}

// 忽略预警
export function lossWarningIgnore(params: any) {
    return request.post({ url: '/crm.lossWarning/ignore', params })
}

// 生成预警
export function lossWarningGenerate(params?: any) {
    return request.post({ url: '/crm.lossWarning/generate', params })
}

// 推送预警
export function lossWarningPush(params?: any) {
    return request.post({ url: '/crm.lossWarning/push', params })
}

// 预警统计
export function lossWarningStats(params?: any) {
    return request.get({ url: '/crm.lossWarning/stats', params })
}

// 预警选项
export function lossWarningOptions() {
    return request.get({ url: '/crm.lossWarning/options' })
}
