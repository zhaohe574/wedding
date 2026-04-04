import request from '@/utils/request'

// 我的评价列表
export function getMyReviews(params?: any) {
    return request.get({ url: '/review/myReviews', params })
}

// 待评价订单列表
export function getPendingOrders(params?: any) {
    return request.get({ url: '/review/pendingOrders', params })
}

// 服务人员评价列表
export function getStaffReviews(params: any) {
    return request.get({ url: '/review/staffReviews', params })
}

// 评价详情
export function getReviewDetail(params: any) {
    return request.get({ url: '/review/reviewDetail', params })
}

// 发布评价
export function publishReview(params: any) {
    return request.post({ url: '/review/publish', params })
}

// 追评
export function appendReview(params: any) {
    return request.post({ url: '/review/append', params })
}

// 点赞/取消点赞
export function toggleReviewLike(params: any) {
    return request.post({ url: '/review/toggleLike', params })
}

// 获取评价标签
export function getReviewTags(params?: any) {
    return request.get({ url: '/review/tags', params })
}

// 获取奖励规则
export function getRewardRules() {
    return request.get({ url: '/review/rewardRules' })
}

// 申请晒单奖励
export function applyShareReward(params: any) {
    return request.post({ url: '/review/applyShareReward', params })
}

// 服务人员评价统计
export function getStaffReviewStats(params: any) {
    return request.get({ url: '/review/staffStats', params })
}

// 提交申诉
export function submitAppeal(params: any) {
    return request.post({ url: '/review/submitAppeal', params })
}
