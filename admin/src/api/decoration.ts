import request from '@/utils/request'

// 页面装修详情
export function getDecoratePages(params: any) {
    return request.get({ url: '/experience.decorate.page/detail', params }, { ignoreCancelToken: true })
}

// 页面装修保存
export function setDecoratePages(params: any) {
    return request.post({ url: '/experience.decorate.page/save', params })
}

// 获取首页文章数据
export function getDecorateArticle(params?: any) {
    return request.get({ url: '/experience.decorate.data/article', params }, { ignoreCancelToken: true })
}

// 底部导航详情
export function getDecorateTabbar(params?: any) {
    return request.get({ url: '/experience.decorate.tabbar/detail', params })
}

// 底部导航保存
export function setDecorateTabbar(params: any) {
    return request.post({ url: '/experience.decorate.tabbar/save', params })
}

// pc装修数据
export function getDecoratePc() {
    return request.get({ url: '/experience.decorate.data/pc' })
}

// 获取公告列表（装修组件选择器用）
export function getDecorateNoticeList(params?: any) {
    return request.get({ url: '/experience.decorate.data/noticeList', params })
}

// 获取话题列表（装修组件选择器用）
export function getDecorateTopicList(params?: any) {
    return request.get({ url: '/experience.decorate.data/topicList', params })
}

// 获取活动列表（装修组件选择器用）
export function getDecorateActivityList(params?: any) {
    return request.get({ url: '/experience.decorate.data/activityList', params })
}
