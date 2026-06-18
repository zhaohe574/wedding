import request from '@/utils/request'
import type {
    ActivityRefundAuditParams,
    ActivityRefundListParams,
    ActivityRefundRow,
    ActivityRegistrationListParams,
    ActivityRegistrationRow,
    AuditParams,
    CommentListParams,
    DynamicFormData,
    DynamicItem,
    DynamicListParams,
    DynamicStatistics,
    IdParams,
    OfflineParams,
    PaginationResponse,
    ReviewListParams,
    ToggleParams
} from '@/types/dynamic'

// ==================== 动态管理 ====================

// 动态列表
export function dynamicLists(params?: DynamicListParams) {
    return request.get<PaginationResponse<DynamicItem>>({ url: '/growth.dynamic/lists', params })
}

// 动态详情
export function dynamicDetail(params: IdParams) {
    return request.get<DynamicItem>({ url: '/growth.dynamic/detail', params })
}

// 添加动态
export function dynamicAdd(params: DynamicFormData) {
    return request.post({ url: '/growth.dynamic/add', params })
}

// 编辑动态
export function dynamicEdit(params: DynamicFormData) {
    return request.post({ url: '/growth.dynamic/edit', params })
}

// 审核动态
export function dynamicAudit(params: AuditParams) {
    return request.post({ url: '/growth.dynamic/audit', params })
}

// 下架动态
export function dynamicOffline(params: OfflineParams) {
    return request.post({ url: '/growth.dynamic/offline', params })
}

// 设置置顶
export function dynamicSetTop(params: ToggleParams) {
    return request.post({ url: '/growth.dynamic/setTop', params })
}

// 设置热门
export function dynamicSetHot(params: ToggleParams) {
    return request.post({ url: '/growth.dynamic/setHot', params })
}

// 删除动态
export function dynamicDelete(params: IdParams) {
    return request.post({ url: '/growth.dynamic/delete', params })
}

// 评论列表
export function dynamicCommentLists(params?: CommentListParams) {
    return request.get({ url: '/growth.dynamic/commentLists', params })
}

// 删除评论
export function dynamicDeleteComment(params: IdParams) {
    return request.post({ url: '/growth.dynamic/deleteComment', params })
}

// 设置评论置顶
export function dynamicSetCommentTop(params: ToggleParams) {
    return request.post({ url: '/growth.dynamic/setCommentTop', params })
}

// 动态统计
export function dynamicStatistics(params?: DynamicListParams) {
    return request.get<DynamicStatistics>({ url: '/growth.dynamic/statistics', params })
}

// 动态类型选项
export function dynamicTypeOptions() {
    return request.get({ url: '/growth.dynamic/typeOptions' })
}

// 动态状态选项
export function dynamicStatusOptions() {
    return request.get({ url: '/growth.dynamic/statusOptions' })
}

// 活动报名名单
export function activityRegistrationLists(params?: ActivityRegistrationListParams) {
    return request.get<PaginationResponse<ActivityRegistrationRow>>({
        url: '/growth.dynamic/activityRegistrations',
        params
    })
}

// 活动退款/取消申请列表
export function activityRefundLists(params?: ActivityRefundListParams) {
    return request.get<PaginationResponse<ActivityRefundRow>>({
        url: '/growth.dynamic/activityRefunds',
        params
    })
}

// 审核活动退款/取消申请
export function activityRefundAudit(params: ActivityRefundAuditParams) {
    return request.post({ url: '/growth.dynamic/activityRefundAudit', params })
}

// 导出活动报名数据
export function activityRegistrationExport(params?: ActivityRegistrationListParams) {
    return request.get<PaginationResponse<ActivityRegistrationRow> | ActivityRegistrationRow[]>({
        url: '/growth.dynamic/activityRegistrationExport',
        params
    })
}

// ==================== 评论审核 ====================

// 获取待审核评论列表
export function apiGetReviewList(params: ReviewListParams) {
    return request.get({ url: '/growth.dynamicComment/reviewList', params })
}

// 获取评论详情
export function apiGetCommentDetail(params: { id: number }) {
    return request.get({ url: '/growth.dynamicComment/detail', params })
}

// 审核通过评论
export function apiApproveComment(params: { id: number }) {
    return request.post({ url: '/growth.dynamicComment/approve', params })
}

// 拒绝评论
export function apiRejectComment(params: { id: number; remark: string }) {
    return request.post({ url: '/growth.dynamicComment/reject', params })
}

// 批量审核通过
export function apiBatchApproveComment(params: { ids: number[] }) {
    return request.post({ url: '/growth.dynamicComment/batchApprove', params })
}

// 批量拒绝
export function apiBatchRejectComment(params: { ids: number[]; remark: string }) {
    return request.post({ url: '/growth.dynamicComment/batchReject', params })
}

// 删除评论
export function apiDeleteComment(params: { id: number }) {
    return request.post({ url: '/growth.dynamicComment/delete', params })
}

// 批量删除评论
export function apiBatchDeleteComment(params: { ids: number[] }) {
    return request.post({ url: '/growth.dynamicComment/batchDelete', params })
}
