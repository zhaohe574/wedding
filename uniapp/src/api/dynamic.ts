import request from '@/utils/request'
import type {
    ActivityRegistration,
    ActivityRegistrationListResponse,
    ActivityRegistrationParams,
    ActivityRegistrationSubmitParams,
    ActivityRegistrationSubmitResponse,
    DynamicComment,
    DynamicDetail,
    DynamicTicket
} from '@/types/dynamic'
import type { IdParams, PaginationResponse } from '@/types/order'

// 获取动态列表
export function getDynamicList(params?: any) {
    return request.get({ url: '/dynamic/lists', params })
}

// 获取动态详情
export function getDynamicDetail(params: IdParams) {
    return request.get<DynamicDetail>({ url: '/dynamic/detail', params })
}

// 发布动态
export function publishDynamic(params: any) {
    return request.post({ url: '/dynamic/publish', params })
}

// 删除动态
export function deleteDynamic(params: any) {
    return request.post({ url: '/dynamic/delete', params })
}

// 点赞/取消点赞
export function likeDynamic(params: any) {
    return request.post({ url: '/dynamic/like', params })
}

// 收藏/取消收藏
export function collectDynamic(params: any) {
    return request.post({ url: '/dynamic/collect', params })
}

// 获取评论列表
export function getCommentList(params: any) {
    return request.get<PaginationResponse<DynamicComment>>({ url: '/dynamic/commentLists', params })
}

// 发表评论
export function addComment(params: any) {
    return request.post<{ comment_id?: number | string }>({ url: '/dynamic/addComment', params })
}

// 删除评论
export function deleteComment(params: any) {
    return request.post({ url: '/dynamic/deleteComment', params })
}

// 评论点赞
export function likeComment(params: any) {
    return request.post({ url: '/dynamic/likeComment', params: { comment_id: params.id } })
}

// 获取我的动态
export function getMyDynamics(params?: any) {
    return request.get({ url: '/dynamic/myDynamics', params })
}

// 获取我的收藏
export function getMyCollections(params?: any) {
    return request.get({ url: '/dynamic/myCollections', params })
}

// 获取我的点赞
export function getMyLikes(params?: any) {
    return request.get({ url: '/dynamic/myLikes', params })
}

// 获取热门标签
export function getHotTags() {
    return request.get({ url: '/dynamic/hotTags' })
}

// 获取消息列表
export function getNotifications(params?: any) {
    return request.get({ url: '/dynamic/notifications', params })
}

// 获取未读消息数量
export function getUnreadCount() {
    return request.get({ url: '/dynamic/unreadCount' }, { duplicateStrategy: 'join' })
}

// 标记消息已读
export function markRead(params: any) {
    return request.post({ url: '/dynamic/markRead', params })
}

// 获取活动票种
export function getActivityTickets(params: IdParams) {
    return request.get<DynamicTicket[]>({ url: '/dynamic/activityTickets', params })
}

// 提交活动报名
export function submitActivityRegistration(params: ActivityRegistrationSubmitParams) {
    return request.post<ActivityRegistrationSubmitResponse>({ url: '/dynamic/activityRegister', params }, { isAuth: true })
}

// 我的活动报名
export function getActivityRegistrations(params?: ActivityRegistrationParams) {
    return request.get<ActivityRegistrationListResponse>({ url: '/dynamic/activityRegistrations', params }, { isAuth: true })
}

// 活动报名详情
export function getActivityRegistrationDetail(params: IdParams) {
    return request.get<ActivityRegistration>({ url: '/dynamic/activityRegistrationDetail', params }, { isAuth: true })
}

// 提交取消活动报名申请
export function applyActivityRegistrationCancel(params: { id?: number | string; registration_id?: number | string; reason: string }) {
    return request.post<unknown>({ url: '/dynamic/activityCancelApply', params }, { isAuth: true })
}
