import request from '@/utils/request'

// ==================== 动态管理 ====================

// 动态列表
export function dynamicLists(params?: any) {
    return request.get({ url: '/dynamic.dynamic/lists', params })
}

// 动态详情
export function dynamicDetail(params: any) {
    return request.get({ url: '/dynamic.dynamic/detail', params })
}

// 审核动态
export function dynamicAudit(params: any) {
    return request.post({ url: '/dynamic.dynamic/audit', params })
}

// 下架动态
export function dynamicOffline(params: any) {
    return request.post({ url: '/dynamic.dynamic/offline', params })
}

// 设置置顶
export function dynamicSetTop(params: any) {
    return request.post({ url: '/dynamic.dynamic/setTop', params })
}

// 设置热门
export function dynamicSetHot(params: any) {
    return request.post({ url: '/dynamic.dynamic/setHot', params })
}

// 删除动态
export function dynamicDelete(params: any) {
    return request.post({ url: '/dynamic.dynamic/delete', params })
}

// 评论列表
export function dynamicCommentLists(params?: any) {
    return request.get({ url: '/dynamic.dynamic/commentLists', params })
}

// 删除评论
export function dynamicDeleteComment(params: any) {
    return request.post({ url: '/dynamic.dynamic/deleteComment', params })
}

// 设置评论置顶
export function dynamicSetCommentTop(params: any) {
    return request.post({ url: '/dynamic.dynamic/setCommentTop', params })
}

// 动态统计
export function dynamicStatistics(params?: any) {
    return request.get({ url: '/dynamic.dynamic/statistics', params })
}

// 动态类型选项
export function dynamicTypeOptions() {
    return request.get({ url: '/dynamic.dynamic/typeOptions' })
}

// 动态状态选项
export function dynamicStatusOptions() {
    return request.get({ url: '/dynamic.dynamic/statusOptions' })
}
