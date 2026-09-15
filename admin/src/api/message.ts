import request from '@/utils/request'

// 通知设置列表
export function noticeLists(params: any) {
    return request.get({ url: '/notice.notice/settingLists', params })
}

// 通知设置详情
export function noticeDetail(params: any) {
    return request.get({ url: '/notice.notice/detail', params })
}

// 通知设置保存
export function setNoticeConfig(params: any) {
    return request.post({ url: '/notice.notice/set', params })
}

// 短信设置列表
export function smsLists() {
    return request.get({ url: '/notice.sms_config/getConfig' })
}

// 短信设置详情
export function smsDetail(params: any) {
    return request.get({ url: '/notice.sms_config/detail', params })
}

// 短信设置保存
export function setSmsConfig(params: any) {
    return request.post({ url: '/notice.sms_config/setConfig', params })
}

// 公众号通知模板列表
export function oaNotificationTemplateLists(params: any) {
    return request.get({ url: '/notification.oaNotification/templateList', params })
}

// 公众号通知模板详情
export function oaNotificationTemplateDetail(params: any) {
    return request.get({ url: '/notification.oaNotification/templateDetail', params })
}

// 保存公众号通知模板
export function setOaNotificationTemplate(params: any) {
    return request.post({ url: '/notification.oaNotification/editTemplate', params })
}

// 公众号通知发送日志
export function oaNotificationLogLists(params: any) {
    return request.get({ url: '/notification.oaNotification/logList', params })
}

// 公众号粉丝绑定状态
export function oaNotificationFollowerLists(params: any) {
    return request.get({ url: '/notification.oaNotification/followerList', params })
}

// 公众号通知灰度配置
export function oaNotificationConfig() {
    return request.get({ url: '/notification.oaNotification/config' })
}

export function saveOaNotificationConfig(params: any) {
    return request.post({ url: '/notification.oaNotification/saveConfig', params })
}

// 发送公众号测试通知
export function oaNotificationTestSend(params: any) {
    return request.post({ url: '/notification.oaNotification/testSend', params })
}

// 重试失败的服务号通知
export function retryOaNotification(params: { id: number }) {
    return request.post({ url: '/notification.oaNotification/retry', params })
}
