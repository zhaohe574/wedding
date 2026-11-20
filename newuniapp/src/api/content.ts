import request from '@/request'
import type { ArticleCard, ReviewCard } from '@shared/contracts'

/**
 * 内容域：资讯 / 案例（只读）。
 * 动态的发布、评论、点赞一律不接 —— 后端 dynamic/publish 已硬编码返回失败，
 * 送审模式下评论也被禁用。
 */

export interface ArticleCate {
    id: number
    name: string
}

/** @endpoint article.cate */
export function getArticleCate() {
    return request.get<ArticleCate[]>('/article/cate', {}, { silent: true })
}

/** @endpoint article.lists */
// 后端走 BaseDataLists，只认 page_no/page_size（BaseDataLists.php initPage）；历史曾误传 page 导致翻页永远停在第 1 页
export function getArticleList(params: { page_no?: number; page_size?: number; cate_id?: number; keyword?: string }) {
    return request.get<any>('/article/lists', params, { silent: true })
}

/** @endpoint article.detail */
export function getArticleDetail(params: { id: number }) {
    return request.get<any>('/article/detail', params, { silent: true })
}

/** @endpoint article.addCollect */
export function addArticleCollect(params: { article_id: number }) {
    return request.post<null>('/article/addCollect', params, { auth: true })
}

/** @endpoint article.cancelCollect */
export function cancelArticleCollect(params: { article_id: number }) {
    return request.post<null>('/article/cancelCollect', params, { auth: true })
}

// article/collect 封装已删除：全仓零消费方（2026-11-19 审查确认的死代码）

/** 案例流（只读动态） @endpoint dynamic.lists */
export function getCaseList(params: {
    page?: number
    page_size?: number
    /** 1=图文 2=视频 4=活动；不传=全部 */
    dynamic_type?: number
    /** create_time=最新 hot=最热 */
    order_by?: 'create_time' | 'hot'
    order_dir?: 'asc' | 'desc'
    tag?: string
}) {
    return request.get<any>('/dynamic/lists', params, { silent: true })
}

/** @endpoint dynamic.detail */
export function getCaseDetail(params: { id: number }) {
    return request.get<any>('/dynamic/detail', params, { silent: true })
}

/** @endpoint dynamic.hotTags */
export function getCaseHotTags() {
    return request.get<string[]>('/dynamic/hotTags', {}, { silent: true })
}

/** @endpoint dynamic.like */
export function toggleCaseLike(params: { id: number }) {
    return request.post<{ is_like: number }>('/dynamic/like', params, { auth: true })
}

/** @endpoint review.staffReviews */
export function getStaffReviews(params: { staff_id: number; page?: number; page_size?: number }) {
    return request.get<any>('/review/staffReviews', params, { silent: true })
}

/** @endpoint review.staffStats */
export function getStaffReviewStats(params: { staff_id: number }) {
    return request.get<any>('/review/staffStats', params, { silent: true })
}

/** 待评价的订单项 @endpoint review.pendingOrders */
export function getPendingReviews() {
    return request.get<any>('/review/pendingOrders', {}, { auth: true, silent: true })
}

/** 我的评价列表 @endpoint review.myReviews */
export function getMyReviews(params?: { page?: number; page_size?: number }) {
    return request.get<any>('/review/myReviews', params ?? { page: 1, page_size: 20 }, { auth: true, silent: true })
}

/** 评价标签 @endpoint review.tags */
export function getReviewTags(params?: { staff_id?: number }) {
    return request.get<any>('/review/tags', params ?? {}, { silent: true })
}

export interface PublishReviewParams {
    order_item_id: number
    score: number
    score_service?: number
    score_professional?: number
    score_punctual?: number
    score_effect?: number
    content?: string
    images?: string[]
    is_anonymous?: number
    tag_ids?: number[]
    custom_tags?: string[]
}

/** 发表评价（需幂等，避免重复提交） @endpoint review.publish */
export function publishReview(data: PublishReviewParams) {
    return request.post<null>('/review/publish', data as any, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}

/** 评价详情 @endpoint review.reviewDetail */
export function getReviewDetail(params: { id: number }) {
    return request.get<any>('/review/reviewDetail', params, { silent: true })
}

// ---------------------------------------------------------------- 动态评论（M-02 接线）
// 13 屏「动态详情」评论区此前没有任何方法可调。
// ⚠ 两道门禁，写页面时必须都判：
//   1. 后端 `addComment` 里 `MiniProgramReviewModeService::enabled()` 为真时直接返回失败，
//      提示语由后端给（`feature_switch.mini_program_review_mode`）。所以**发评论入口要按开关隐藏**，
//      不能等点了再报错。
//   2. 评论列表本身不受送审限制，但列表为空时不要显示「暂无评论」以外的操作引导。

export interface CommentListParams {
    /** 后端必填（走 path 参数 /d，非 0） */
    dynamic_id: number
    page?: number
    page_size?: number
    /** 'hot'（默认，置顶 + 点赞数 + 时间）| 'new'（按时间倒序） */
    sort?: 'hot' | 'new'
    /** 传入即查某条评论的回复列表 */
    parent_id?: number
}

/** 评论列表 @endpoint dynamic.commentLists */
export function getCommentList(params: CommentListParams) {
    return request.get<any>('/dynamic/commentLists', params, { silent: true })
}

/** 发表评论（送审模式下会被后端拒绝） @endpoint dynamic.addComment */
export function addComment(data: {
    dynamic_id: number
    content: string
    parent_id?: number
    reply_user_id?: number
    images?: string[]
}) {
    return request.post<{ comment_id: number }>('/dynamic/addComment', data as any, {
        auth: true,
        idempotent: true,
        dedupe: false,
        loading: true
    })
}

/** 删除本人评论 @endpoint dynamic.deleteComment */
export function deleteComment(data: { comment_id: number }) {
    return request.post<null>('/dynamic/deleteComment', data, { auth: true, loading: true })
}

/** 评论点赞/取消（toggle 语义，不可重试，前端需乐观更新 + 失败回滚） @endpoint dynamic.likeComment */
export function likeComment(data: { comment_id: number }) {
    return request.post<{ is_liked: number | boolean }>('/dynamic/likeComment', data, {
        auth: true,
        silent: true
    })
}

export type { ArticleCard, ReviewCard }
