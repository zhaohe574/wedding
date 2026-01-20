import request from '@/utils/request'

// ==================== 评价管理 ====================

// 评价列表
export function getReviewList(params?: any) {
    return request.get({ url: '/review.review/lists', params })
}

// 评价详情
export function getReviewDetail(params: any) {
    return request.get({ url: '/review.review/detail', params })
}

// 审核评价
export function auditReview(params: any) {
    return request.post({ url: '/review.review/audit', params })
}

// 批量审核
export function batchAuditReview(params: any) {
    return request.post({ url: '/review.review/batchAudit', params })
}

// 置顶/取消置顶
export function toggleReviewTop(params: any) {
    return request.post({ url: '/review.review/toggleTop', params })
}

// 显示/隐藏
export function toggleReviewShow(params: any) {
    return request.post({ url: '/review.review/toggleShow', params })
}

// 删除评价
export function deleteReview(params: any) {
    return request.post({ url: '/review.review/delete', params })
}

// 商家回复
export function replyReview(params: any) {
    return request.post({ url: '/review.review/reply', params })
}

// 评价统计
export function getReviewStatistics(params?: any) {
    return request.get({ url: '/review.review/statistics', params })
}

// 人员评价排行
export function getStaffRanking(params?: any) {
    return request.get({ url: '/review.review/staffRanking', params })
}

// 评分分布
export function getScoreDistribution(params?: any) {
    return request.get({ url: '/review.review/scoreDistribution', params })
}

// 热门标签
export function getHotTags(params?: any) {
    return request.get({ url: '/review.review/hotTags', params })
}

// ==================== 评价标签 ====================

// 标签列表
export function getReviewTagList(params?: any) {
    return request.get({ url: '/review.reviewTag/lists', params })
}

// 标签详情
export function getReviewTagDetail(params: any) {
    return request.get({ url: '/review.reviewTag/detail', params })
}

// 添加标签
export function addReviewTag(params: any) {
    return request.post({ url: '/review.reviewTag/add', params })
}

// 编辑标签
export function editReviewTag(params: any) {
    return request.post({ url: '/review.reviewTag/edit', params })
}

// 删除标签
export function deleteReviewTag(params: any) {
    return request.post({ url: '/review.reviewTag/delete', params })
}

// 修改状态
export function changeReviewTagStatus(params: any) {
    return request.post({ url: '/review.reviewTag/status', params })
}

// 分组标签
export function getGroupedTags() {
    return request.get({ url: '/review.reviewTag/grouped' })
}

// 类型选项
export function getTagTypeOptions() {
    return request.get({ url: '/review.reviewTag/typeOptions' })
}

// ==================== 评价申诉 ====================

// 申诉列表
export function getAppealList(params?: any) {
    return request.get({ url: '/review.reviewAppeal/lists', params })
}

// 申诉详情
export function getAppealDetail(params: any) {
    return request.get({ url: '/review.reviewAppeal/detail', params })
}

// 处理申诉
export function handleAppeal(params: any) {
    return request.post({ url: '/review.reviewAppeal/handle', params })
}

// 申诉统计
export function getAppealStatistics() {
    return request.get({ url: '/review.reviewAppeal/statistics' })
}

// 申诉类型选项
export function getAppealTypeOptions() {
    return request.get({ url: '/review.reviewAppeal/typeOptions' })
}

// 处理动作选项
export function getAppealActionOptions() {
    return request.get({ url: '/review.reviewAppeal/actionOptions' })
}

// ==================== 敏感词 ====================

// 敏感词列表
export function getSensitiveWordList(params?: any) {
    return request.get({ url: '/review.sensitiveWord/lists', params })
}

// 敏感词详情
export function getSensitiveWordDetail(params: any) {
    return request.get({ url: '/review.sensitiveWord/detail', params })
}

// 添加敏感词
export function addSensitiveWord(params: any) {
    return request.post({ url: '/review.sensitiveWord/add', params })
}

// 编辑敏感词
export function editSensitiveWord(params: any) {
    return request.post({ url: '/review.sensitiveWord/edit', params })
}

// 删除敏感词
export function deleteSensitiveWord(params: any) {
    return request.post({ url: '/review.sensitiveWord/delete', params })
}

// 批量删除
export function batchDeleteSensitiveWord(params: any) {
    return request.post({ url: '/review.sensitiveWord/batchDelete', params })
}

// 修改状态
export function changeSensitiveWordStatus(params: any) {
    return request.post({ url: '/review.sensitiveWord/status', params })
}

// 批量导入
export function importSensitiveWords(params: any) {
    return request.post({ url: '/review.sensitiveWord/import', params })
}

// 检测内容
export function checkSensitiveContent(params: any) {
    return request.post({ url: '/review.sensitiveWord/check', params })
}

// 类型选项
export function getSensitiveWordTypeOptions() {
    return request.get({ url: '/review.sensitiveWord/typeOptions' })
}

// 级别选项
export function getSensitiveWordLevelOptions() {
    return request.get({ url: '/review.sensitiveWord/levelOptions' })
}
