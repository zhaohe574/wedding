import request from '@/utils/request'

// ==================== 模板管理 ====================

// 获取模板列表
export function getTemplateList(params?: any) {
    return request.get({ url: '/subscribe.subscribe/templateList', params })
}

// 获取模板详情
export function getTemplateDetail(id: number) {
    return request.get({ url: '/subscribe.subscribe/templateDetail', params: { id } })
}

// 添加模板
export function addTemplate(data: any) {
    return request.post({ url: '/subscribe.subscribe/addTemplate', data })
}

// 编辑模板
export function editTemplate(data: any) {
    return request.post({ url: '/subscribe.subscribe/editTemplate', data })
}

// 删除模板
export function deleteTemplate(id: number) {
    return request.post({ url: '/subscribe.subscribe/deleteTemplate', data: { id } })
}

// 切换模板状态
export function toggleTemplateStatus(id: number) {
    return request.post({ url: '/subscribe.subscribe/toggleTemplateStatus', data: { id } })
}

// 获取场景选项
export function getSceneOptions() {
    return request.get({ url: '/subscribe.subscribe/getSceneOptions' })
}

// ==================== 场景配置 ====================

// 获取场景列表
export function getSceneList(params?: any) {
    return request.get({ url: '/subscribe.subscribe/sceneList', params })
}

// 获取场景详情
export function getSceneDetail(id: number) {
    return request.get({ url: '/subscribe.subscribe/sceneDetail', params: { id } })
}

// 编辑场景配置
export function editScene(data: any) {
    return request.post({ url: '/subscribe.subscribe/editScene', data })
}

// 切换场景状态
export function toggleSceneStatus(id: number) {
    return request.post({ url: '/subscribe.subscribe/toggleSceneStatus', data: { id } })
}

// 绑定模板到场景
export function bindTemplate(data: { scene_id: number; template_id: string }) {
    return request.post({ url: '/subscribe.subscribe/bindTemplate', data })
}

// ==================== 发送记录 ====================

// 获取发送记录列表
export function getMessageLogList(params?: any) {
    return request.get({ url: '/subscribe.subscribe/logList', params })
}

// 获取发送记录详情
export function getMessageLogDetail(id: number) {
    return request.get({ url: '/subscribe.subscribe/logDetail', params: { id } })
}

// 重试发送
export function retryMessageLog(id: number) {
    return request.post({ url: '/subscribe.subscribe/retryLog', data: { id } })
}

// ==================== 统计数据 ====================

// 获取发送统计
export function getStatistics(params?: any) {
    return request.get({ url: '/subscribe.subscribe/getStatistics', params })
}

// 获取发送趋势
export function getTrend(days?: number) {
    return request.get({ url: '/subscribe.subscribe/getTrend', params: { days } })
}

// 获取场景统计
export function getSceneStatistics(params?: any) {
    return request.get({ url: '/subscribe.subscribe/getSceneStatistics', params })
}

// ==================== 测试发送 ====================

// 测试发送消息
export function testSend(data: { user_id: number; scene: string; data?: any; page?: string }) {
    return request.post({ url: '/subscribe.subscribe/testSend', data })
}
