import request from '@/utils/request'

/**
 * @description 获取服务分类树形结构
 * @return { Promise }
 */
export function getServiceCategories() {
    return request.get({ url: '/service/categoryTree' })
}

/**
 * @description 获取风格标签
 * @param { number } type 标签类型
 * @param { boolean } grouped 是否分组
 * @return { Promise }
 */
export function getStyleTags(data?: { type?: number; grouped?: number; category_id?: number }) {
    return request.get({ url: '/service/tags', data })
}
