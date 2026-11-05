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
export function getArticleList(params: { page?: number; page_size?: number; cate_id?: number; keyword?: string }) {
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

/** @endpoint article.collect */
export function getArticleCollect(params?: { page?: number; page_size?: number }) {
    return request.get<any>('/article/collect', params ?? {}, { auth: true, silent: true })
}

/** 案例流（只读动态） @endpoint dynamic.lists */
export function getCaseList(params: { page?: number; page_size?: number; tag?: string }) {
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

export type { ArticleCard, ReviewCard }
