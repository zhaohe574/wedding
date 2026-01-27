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

// 添加动态
export function dynamicAdd(params: any) {
    return request.post({ url: '/dynamic.dynamic/add', params })
}

// 编辑动态
export function dynamicEdit(params: any) {
    return request.post({ url: '/dynamic.dynamic/edit', params })
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

// ==================== 评论审核 ====================

// 获取评论审核配置
export function apiGetReviewConfig() {
    return request.get({ url: '/dynamic.dynamicComment/getReviewConfig' })
}

// 设置评论审核配置
export function apiSetReviewConfig(params: { enabled: number }) {
    return request.post({ url: '/dynamic.dynamicComment/setReviewConfig', params })
}

// 获取待审核评论列表
export function apiGetReviewList(params: any) {
    return request.get({ url: '/dynamic.dynamicComment/reviewList', params })
}

// 获取评论详情
export function apiGetCommentDetail(params: { id: number }) {
    return request.get({ url: '/dynamic.dynamicComment/detail', params })
}

// 审核通过评论
export function apiApproveComment(params: { id: number }) {
    return request.post({ url: '/dynamic.dynamicComment/approve', params })
}

// 拒绝评论
export function apiRejectComment(params: { id: number; remark: string }) {
    return request.post({ url: '/dynamic.dynamicComment/reject', params })
}

// 批量审核通过
export function apiBatchApproveComment(params: { ids: number[] }) {
    return request.post({ url: '/dynamic.dynamicComment/batchApprove', params })
}

// 批量拒绝
export function apiBatchRejectComment(params: { ids: number[]; remark: string }) {
    return request.post({ url: '/dynamic.dynamicComment/batchReject', params })
}

// 删除评论
export function apiDeleteComment(params: { id: number }) {
    return request.post({ url: '/dynamic.dynamicComment/delete', params })
}

// 批量删除评论
export function apiBatchDeleteComment(params: { ids: number[] }) {
    return request.post({ url: '/dynamic.dynamicComment/batchDelete', params })
}
