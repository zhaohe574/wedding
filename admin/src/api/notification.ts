import request from '@/utils/request'

// ==================== 消息通知管理 ====================

// 通知列表
export function getNotificationList(params?: any) {
    return request.get({ url: '/growth.notification/lists', params })
}

// 通知详情
export function getNotificationDetail(params: any) {
    return request.get({ url: '/growth.notification/detail', params })
}

// 发送通知（单个用户）
export function sendNotification(params: any) {
    return request.post({ url: '/growth.notification/send', params })
}

// 批量发送通知
export function batchSendNotification(params: any) {
    return request.post({ url: '/growth.notification/batchSend', params })
}

// 全员通知
export function sendToAllNotification(params: any) {
    return request.post({ url: '/growth.notification/sendToAll', params })
}

// 删除通知
export function deleteNotification(params: any) {
    return request.post({ url: '/growth.notification/delete', params })
}

// 批量删除通知
export function batchDeleteNotification(params: any) {
    return request.post({ url: '/growth.notification/batchDelete', params })
}

// 通知统计
export function getNotificationStatistics(params?: any) {
    return request.get({ url: '/growth.notification/statistics', params })
}

// 获取通知类型选项
export function getNotificationTypeOptions() {
    return request.get({ url: '/growth.notification/typeOptions' })
}

// 通知模板列表
export function getNotificationTemplates() {
    return request.get({ url: '/growth.notification/templates' })
}

// 发送趋势统计
export function getNotificationSendTrend(params?: any) {
    return request.get({ url: '/growth.notification/sendTrend', params })
}
