import request from '@/utils/request'

// 文章分类列表
export function articleCateLists(params?: any) {
    return request.get({ url: '/content.articleCategory/lists', params })
}
// 文章分类列表
export function articleCateAll(params?: any) {
    return request.get({ url: '/content.articleCategory/all', params })
}

// 添加文章分类
export function articleCateAdd(params: any) {
    return request.post({ url: '/content.articleCategory/add', params })
}

// 编辑文章分类
export function articleCateEdit(params: any) {
    return request.post({ url: '/content.articleCategory/edit', params })
}

// 删除文章分类
export function articleCateDelete(params: any) {
    return request.post({ url: '/content.articleCategory/delete', params })
}

// 文章分类详情
export function articleCateDetail(params: any) {
    return request.get({ url: '/content.articleCategory/detail', params })
}

// 文章分类状态
export function articleCateStatus(params: any) {
    return request.post({ url: '/content.articleCategory/updateStatus', params })
}

// 文章列表
export function articleLists(params?: any) {
    return request.get({ url: '/content.article/lists', params })
}
// 文章列表
export function articleAll(params?: any) {
    return request.get({ url: '/article/all', params })
}

// 添加文章
export function articleAdd(params: any) {
    return request.post({ url: '/content.article/add', params })
}

// 编辑文章
export function articleEdit(params: any) {
    return request.post({ url: '/content.article/edit', params })
}

// 删除文章
export function articleDelete(params: any) {
    return request.post({ url: '/content.article/delete', params })
}

// 文章详情
export function articleDetail(params: any) {
    return request.get({ url: '/content.article/detail', params })
}

// 文章分类状态
export function articleStatus(params: any) {
    return request.post({ url: '/content.article/updateStatus', params })
}
