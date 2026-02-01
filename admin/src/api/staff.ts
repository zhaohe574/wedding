import request from '@/utils/request'

// 工作人员列表
export function staffLists(params?: any) {
    return request.get({ url: '/staff.staff/lists', params })
}

// 工作人员详情
export function staffDetail(params: any) {
    return request.get({ url: '/staff.staff/detail', params })
}

// 添加工作人员
export function staffAdd(params: any) {
    return request.post({ url: '/staff.staff/add', params })
}

// 编辑工作人员
export function staffEdit(params: any) {
    return request.post({ url: '/staff.staff/edit', params })
}

// 删除工作人员
export function staffDelete(params: any) {
    return request.post({ url: '/staff.staff/delete', params })
}

// 修改工作人员状态
export function staffChangeStatus(params: any) {
    return request.post({ url: '/staff.staff/changeStatus', params })
}

// 获取所有工作人员(下拉)
export function staffAll(params?: any) {
    return request.get({ url: '/staff.staff/all', params })
}

// 工作人员统计
export function staffStatistics() {
    return request.get({ url: '/staff.staff/statistics' })
}

// ==================== 套餐配置管理 ====================

// 配置员工套餐关联
export function staffConfigurePackages(params: any) {
    return request.post({ url: '/staff.staff/configurePackages', params })
}

// 获取员工套餐配置
export function staffGetPackageConfig(params: any) {
    return request.get({ url: '/staff.staff/getPackageConfig', params })
}

// 创建员工专属套餐
export function staffCreatePackage(params: any) {
    return request.post({ url: '/staff.staff/createStaffPackage', params })
}

// 编辑员工专属套餐
export function staffUpdateStaffPackage(params: any) {
    return request.post({ url: '/staff.staff/updateStaffPackage', params })
}

// 更新单个套餐配置
export function staffUpdatePackageConfig(params: any) {
    return request.post({ url: '/staff.staff/updatePackageConfig', params })
}

// 删除员工专属套餐
export function staffDeletePackage(params: any) {
    return request.post({ url: '/staff.staff/deleteStaffPackage', params })
}

// ==================== 作品管理 ====================

// 作品列表
export function staffWorkLists(params?: any) {
    return request.get({ url: '/staff.staffWork/lists', params })
}

// 作品详情
export function staffWorkDetail(params: any) {
    return request.get({ url: '/staff.staffWork/detail', params })
}

// 添加作品
export function staffWorkAdd(params: any) {
    return request.post({ url: '/staff.staffWork/add', params })
}

// 编辑作品
export function staffWorkEdit(params: any) {
    return request.post({ url: '/staff.staffWork/edit', params })
}

// 删除作品
export function staffWorkDelete(params: any) {
    return request.post({ url: '/staff.staffWork/delete', params })
}

// 作品状态
export function staffWorkChangeStatus(params: any) {
    return request.post({ url: '/staff.staffWork/changeStatus', params })
}

// 设为封面
export function staffWorkSetCover(params: any) {
    return request.post({ url: '/staff.staffWork/setCover', params })
}

// ==================== 证书管理 ====================

// 证书列表
export function staffCertificateLists(params?: any) {
    return request.get({ url: '/staff.staffCertificate/lists', params })
}

// 证书详情
export function staffCertificateDetail(params: any) {
    return request.get({ url: '/staff.staffCertificate/detail', params })
}

// 添加证书
export function staffCertificateAdd(params: any) {
    return request.post({ url: '/staff.staffCertificate/add', params })
}

// 编辑证书
export function staffCertificateEdit(params: any) {
    return request.post({ url: '/staff.staffCertificate/edit', params })
}

// 删除证书
export function staffCertificateDelete(params: any) {
    return request.post({ url: '/staff.staffCertificate/delete', params })
}

// 审核证书
export function staffCertificateAudit(params: any) {
    return request.post({ url: '/staff.staffCertificate/audit', params })
}

// ==================== 轮播图管理 ====================

// 获取轮播图列表
export function staffBannerList(params: any) {
    return request.get({ url: '/staff.staff/getBannerList', params })
}

// 添加轮播图
export function staffBannerAdd(params: any) {
    return request.post({ url: '/staff.staff/addBanner', params })
}

// 编辑轮播图
export function staffBannerEdit(params: any) {
    return request.post({ url: '/staff.staff/editBanner', params })
}

// 删除轮播图
export function staffBannerDelete(params: any) {
    return request.post({ url: '/staff.staff/deleteBanner', params })
}

// 更新轮播图排序
export function staffBannerSort(params: any) {
    return request.post({ url: '/staff.staff/sortBanner', params })
}

// 更新轮播图配置
export function staffBannerUpdateConfig(params: any) {
    return request.post({ url: '/staff.staff/updateBannerConfig', params })
}
