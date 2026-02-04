import request from '@/utils/request'

/**
 * @description 获取工作人员列表
 * @return { Promise }
 */
export function getStaffList(data: Record<string, any>) {
    return request.get({ url: '/staff/lists', data })
}

/**
 * @description 获取推荐工作人员
 * @param { number } limit 数量限制
 * @return { Promise }
 */
export function getRecommendStaff(data?: { limit?: number }) {
    return request.get({ url: '/staff/recommend', data })
}

/**
 * @description 获取工作人员详情
 * @param { number } id
 * @return { Promise }
 */
export function getStaffDetail(data: { id: number }) {
    return request.get({ url: '/staff/detail', data })
}

/**
 * @description 获取工作人员作品
 * @param { number } staff_id
 * @return { Promise }
 */
export function getStaffWorks(data: { staff_id: number }) {
    return request.get({ url: '/staff/works', data })
}

/**
 * @description 工作人员作品列表（支持搜索）
 * @param data
 * @return { Promise }
 */
export function getWorkLists(data: Record<string, any>) {
    return request.get({ url: '/staff/workLists', data })
}

/**
 * @description 收藏/取消收藏工作人员
 * @param { number } id
 * @return { Promise }
 */
export function toggleStaffFavorite(data: { id: number }) {
    return request.post({ url: '/staff/toggleFavorite', data }, { isAuth: true })
}

/**
 * @description 我收藏的工作人员
 * @return { Promise }
 */
export function getMyFavoriteStaff() {
    return request.get({ url: '/staff/myFavorites' }, { isAuth: true })
}

/**
 * @description 获取工作人员关联的套餐
 * @param { number } staff_id
 * @return { Promise }
 */
export function getStaffPackages(data: { staff_id: number }) {
    return request.get({ url: '/staff/packages', data })
}
