/**
 * @description 获取文章分类
 * @return { Promise }
 */
export function getArticleCate() {
    return $request.get({ url: '/article/cate' })
}

/**
 * @description 获取文章列表
 * @return { Promise }
 */
export function getArticleList(params: Record<string, unknown>) {
    return $request.get({ url: '/article/lists', params })
}

/**
 * @description 获取资讯中心
 * @return { Promise }
 */
export function getArticleCenter() {
    return $request.get({ url: '/pc/infoCenter' })
}

/**
 * @description 文章详情
 * @return { Promise }
 */
export function getArticleDetail(params: {
    id: string | string[]
    source?: string | string[]
}) {
    return $request.get({ url: '/pc/articleDetail', params })
}
