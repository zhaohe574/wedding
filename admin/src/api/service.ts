import request from '@/utils/request'

// ==================== 服务分类 ====================

// 分类列表
export function categoryLists(params?: any) {
    return request.get({ url: '/ops.category/lists', params })
}

// 分类树形结构
export function categoryTree() {
    return request.get({ url: '/ops.category/tree' })
}

// 分类详情
export function categoryDetail(params: any) {
    return request.get({ url: '/ops.category/detail', params })
}

// 添加分类
export function categoryAdd(params: any) {
    return request.post({ url: '/ops.category/add', params })
}

// 编辑分类
export function categoryEdit(params: any) {
    return request.post({ url: '/ops.category/edit', params })
}

// 删除分类
export function categoryDelete(params: any) {
    return request.post({ url: '/ops.category/delete', params })
}

// 分类状态
export function categoryChangeStatus(params: any) {
    return request.post({ url: '/ops.category/changeStatus', params })
}

// 获取所有分类(下拉)
export function categoryAll() {
    return request.get({ url: '/ops.category/all' })
}

// ==================== 服务套餐 ====================

// 套餐列表
export function packageLists(params?: any) {
    return request.get({ url: '/ops.package/lists', params })
}

// 套餐详情
export function packageDetail(params: any) {
    return request.get({ url: '/ops.package/detail', params })
}

// 添加套餐
export function packageAdd(params: any) {
    return request.post({ url: '/ops.package/add', params })
}

// 编辑套餐
export function packageEdit(params: any) {
    return request.post({ url: '/ops.package/edit', params })
}

// 删除套餐
export function packageDelete(params: any) {
    return request.post({ url: '/ops.package/delete', params })
}

// 套餐状态
export function packageChangeStatus(params: any) {
    return request.post({ url: '/ops.package/changeStatus', params })
}

// 获取所有套餐(下拉)
export function packageAll(params?: any) {
    return request.get({ url: '/ops.package/all', params })
}

// ==================== 服务地区 ====================

// 服务地区列表
export function regionLists(params?: any) {
    return request.get({ url: '/ops.region/lists', params })
}

// 全部城市选项
export function regionCityOptions() {
    return request.get({ url: '/ops.region/cityOptions' })
}

// 已启用城市选项
export function regionEnabledCityOptions() {
    return request.get({ url: '/ops.region/enabledCityOptions' })
}

// 城市下区县选项
export function regionDistrictOptions(params: { city_code: string }) {
    return request.get({ url: '/ops.region/districtOptions', params })
}

// 添加服务地区
export function regionAdd(params: any) {
    return request.post({ url: '/ops.region/add', params })
}

// 编辑服务地区
export function regionEdit(params: any) {
    return request.post({ url: '/ops.region/edit', params })
}

// 删除服务地区
export function regionDelete(params: any) {
    return request.post({ url: '/ops.region/delete', params })
}

// 切换服务地区状态
export function regionChangeStatus(params: any) {
    return request.post({ url: '/ops.region/changeStatus', params })
}

// ==================== 风格标签 ====================

// 标签列表
export function styleTagLists(params?: any) {
    return request.get({ url: '/ops.styleTag/lists', params })
}

// 标签详情
export function styleTagDetail(params: any) {
    return request.get({ url: '/ops.styleTag/detail', params })
}

// 添加标签
export function styleTagAdd(params: any) {
    return request.post({ url: '/ops.styleTag/add', params })
}

// 编辑标签
export function styleTagEdit(params: any) {
    return request.post({ url: '/ops.styleTag/edit', params })
}

// 删除标签
export function styleTagDelete(params: any) {
    return request.post({ url: '/ops.styleTag/delete', params })
}

// 标签状态
export function styleTagChangeStatus(params: any) {
    return request.post({ url: '/ops.styleTag/changeStatus', params })
}

// 获取所有标签(下拉)
export function styleTagAll(params?: any) {
    return request.get({ url: '/ops.styleTag/all', params })
}

// 获取标签类型选项
export function styleTagTypeOptions() {
    return request.get({ url: '/ops.styleTag/typeOptions' })
}
