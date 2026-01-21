import request from '@/utils/request'

// ==================== 服务分类 ====================

// 分类列表
export function categoryLists(params?: any) {
    return request.get({ url: '/service.category/lists', params })
}

// 分类树形结构
export function categoryTree() {
    return request.get({ url: '/service.category/tree' })
}

// 分类详情
export function categoryDetail(params: any) {
    return request.get({ url: '/service.category/detail', params })
}

// 添加分类
export function categoryAdd(params: any) {
    return request.post({ url: '/service.category/add', params })
}

// 编辑分类
export function categoryEdit(params: any) {
    return request.post({ url: '/service.category/edit', params })
}

// 删除分类
export function categoryDelete(params: any) {
    return request.post({ url: '/service.category/delete', params })
}

// 分类状态
export function categoryChangeStatus(params: any) {
    return request.post({ url: '/service.category/changeStatus', params })
}

// 获取所有分类(下拉)
export function categoryAll() {
    return request.get({ url: '/service.category/all' })
}

// ==================== 服务套餐 ====================

// 套餐列表
export function packageLists(params?: any) {
    return request.get({ url: '/service.package/lists', params })
}

// 套餐详情
export function packageDetail(params: any) {
    return request.get({ url: '/service.package/detail', params })
}

// 添加套餐
export function packageAdd(params: any) {
    return request.post({ url: '/service.package/add', params })
}

// 编辑套餐
export function packageEdit(params: any) {
    return request.post({ url: '/service.package/edit', params })
}

// 删除套餐
export function packageDelete(params: any) {
    return request.post({ url: '/service.package/delete', params })
}

// 套餐状态
export function packageChangeStatus(params: any) {
    return request.post({ url: '/service.package/changeStatus', params })
}

// 获取所有套餐(下拉)
export function packageAll(params?: any) {
    return request.get({ url: '/service.package/all', params })
}

// 更新套餐时段价格
export function packageUpdateSlotPrices(params: any) {
    return request.post({ url: '/service.package/updateSlotPrices', params })
}

// 检查套餐可用性
export function packageCheckAvailability(params: any) {
    return request.get({ url: '/service.package/checkAvailability', params })
}

// 获取套餐预约日历
export function packageGetBookingCalendar(params: any) {
    return request.get({ url: '/service.package/getBookingCalendar', params })
}

// ==================== 风格标签 ====================

// 标签列表
export function styleTagLists(params?: any) {
    return request.get({ url: '/service.styleTag/lists', params })
}

// 标签详情
export function styleTagDetail(params: any) {
    return request.get({ url: '/service.styleTag/detail', params })
}

// 添加标签
export function styleTagAdd(params: any) {
    return request.post({ url: '/service.styleTag/add', params })
}

// 编辑标签
export function styleTagEdit(params: any) {
    return request.post({ url: '/service.styleTag/edit', params })
}

// 删除标签
export function styleTagDelete(params: any) {
    return request.post({ url: '/service.styleTag/delete', params })
}

// 标签状态
export function styleTagChangeStatus(params: any) {
    return request.post({ url: '/service.styleTag/changeStatus', params })
}

// 获取所有标签(下拉)
export function styleTagAll(params?: any) {
    return request.get({ url: '/service.styleTag/all', params })
}

// 获取标签类型选项
export function styleTagTypeOptions() {
    return request.get({ url: '/service.styleTag/typeOptions' })
}
