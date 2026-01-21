import request from '@/utils/request'

// 获取可用模板列表
export function getTemplateList(scene?: string) {
    return request.get('/subscribe/getTemplateList', { params: { scene } })
}

// 记录订阅结果
export function recordSubscribe(data: { template_id: string; scene?: string; result: 'accept' | 'reject' }) {
    return request.post('/subscribe/recordSubscribe', data)
}

// 批量记录订阅结果
export function batchRecordSubscribe(data: { results: Record<string, 'accept' | 'reject'>; scene?: string }) {
    return request.post('/subscribe/batchRecordSubscribe', data)
}

// 获取我的订阅状态
export function getMySubscribeStatus(templateIds?: string[]) {
    const params = templateIds ? { template_ids: templateIds.join(',') } : {}
    return request.get('/subscribe/getMySubscribeStatus', { params })
}

// 获取我的订阅列表
export function getMySubscriptions() {
    return request.get('/subscribe/getMySubscriptions')
}

// 获取我的消息记录
export function getMyMessageLogs(params?: { page_no?: number; page_size?: number }) {
    return request.get('/subscribe/getMyMessageLogs', { params })
}

// 获取需要订阅的场景列表
export function getSceneList() {
    return request.get('/subscribe/getSceneList')
}

// 检查场景订阅状态
export function checkSceneSubscribe(scene: string) {
    return request.get('/subscribe/checkSceneSubscribe', { params: { scene } })
}
