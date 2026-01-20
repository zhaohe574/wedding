import request from '@/utils/request'

// 获取动态列表
export function getDynamicList(params?: any) {
    return request.get({ url: '/dynamic/lists', params })
}

// 获取动态详情
export function getDynamicDetail(params: any) {
    return request.get({ url: '/dynamic/detail', params })
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
    return request.get({ url: '/dynamic/commentLists', params })
}

// 发表评论
export function addComment(params: any) {
    return request.post({ url: '/dynamic/addComment', params })
}

// 删除评论
export function deleteComment(params: any) {
    return request.post({ url: '/dynamic/deleteComment', params })
}

// 评论点赞
export function likeComment(params: any) {
    return request.post({ url: '/dynamic/likeComment', params })
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

// 关注/取消关注
export function toggleFollow(params: any) {
    return request.post({ url: '/dynamic/follow', params })
}

// 获取我的关注
export function getMyFollowing(params?: any) {
    return request.get({ url: '/dynamic/myFollowing', params })
}

// 获取我的粉丝
export function getMyFans(params?: any) {
    return request.get({ url: '/dynamic/myFans', params })
}

// 获取消息列表
export function getNotifications(params?: any) {
    return request.get({ url: '/dynamic/notifications', params })
}

// 获取未读消息数量
export function getUnreadCount() {
    return request.get({ url: '/dynamic/unreadCount' })
}

// 标记消息已读
export function markRead(params: any) {
    return request.post({ url: '/dynamic/markRead', params })
}
