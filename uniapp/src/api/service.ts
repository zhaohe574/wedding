import request from '@/utils/request'

/**
 * @description 获取服务分类树形结构
 * @return { Promise }
 */
export function getServiceCategories() {
    return request.get({ url: '/service/categoryTree' })
}

/**
 * @description 获取服务套餐列表
 * @param { number } category_id 分类ID
 * @return { Promise }
 */
export function getServicePackages(data?: { category_id?: number }) {
    return request.get({ url: '/service/packages', data })
}

/**
 * @description 获取服务套餐详情
 * @param { number } id
 * @return { Promise }
 */
export function getServicePackageDetail(data: { id: number }) {
    return request.get({ url: '/service/packageDetail', data })
}

/**
 * @description 获取风格标签
 * @param { number } type 标签类型
 * @param { boolean } grouped 是否分组
 * @return { Promise }
 */
export function getStyleTags(data?: { type?: number; grouped?: number }) {
    return request.get({ url: '/service/tags', data })
}

/**
 * @description 检查套餐可用性（单日唯一限制）
 * @param { number } package_id 套餐ID
 * @param { string } date 预订日期 Y-m-d
 * @return { Promise }
 */
export function checkPackageAvailability(data: { package_id: number; date: string }) {
    return request.get({ url: '/service/checkPackageAvailability', data })
}

/**
 * @description 批量检查套餐可用性
 * @param { number[] } package_ids 套餐ID数组
 * @param { string } date 预订日期 Y-m-d
 * @return { Promise }
 */
export function batchCheckPackageAvailability(data: { package_ids: number[]; date: string }) {
    return request.post({ url: '/service/batchCheckAvailability', data })
}

/**
 * @description 获取套餐时段价格
 * @param { number } package_id 套餐ID
 * @param { number } staff_id 员工ID（可选，用于获取员工个人时段价格）
 * @return { Promise }
 */
export function getPackageSlotPrices(data: { package_id: number; staff_id?: number }) {
    return request.get({ url: '/service/packageSlotPrices', data })
}

/**
 * @description 计算套餐最终价格
 * @param { number } package_id 套餐ID
 * @param { number } staff_id 员工ID
 * @param { string } start_time 开始时间 HH:mm（可选）
 * @param { string } end_time 结束时间 HH:mm（可选）
 * @return { Promise }
 */
export function calculatePackagePrice(data: {
    package_id: number
    staff_id: number
    start_time?: string
    end_time?: string
}) {
    return request.get({ url: '/service/calculatePrice', data })
}
