import request from '@/utils/request'

// ==================== 订单变更 ====================

// 变更申请列表
export function getChangeList(params?: any) {
    return request.get({ url: '/orderChange/lists', params })
}

// 变更申请详情
export function getChangeDetail(params: any) {
    return request.get({ url: '/orderChange/detail', params })
}

// 检查订单是否可变更
export function checkCanChange(params: any) {
    return request.get({ url: '/orderChange/checkCanChange', params })
}

// 申请改期
export function applyDateChange(params: any) {
    return request.post({ url: '/orderChange/applyDateChange', params })
}

// 申请加项
export function applyAddItem(params: any) {
    return request.post({ url: '/orderChange/applyAddItem', params })
}

// 申请附加服务变更
export function applyAddonChange(params: any) {
    return request.post({ url: '/orderChange/applyAddonChange', params })
}

// 取消变更
export function cancelChange(params: any) {
    return request.post({ url: '/orderChange/cancel', params })
}

// 获取变更类型选项
export function getChangeTypeOptions() {
    return request.get({ url: '/orderChange/typeOptions' })
}

// ==================== 订单暂停 ====================

// 暂停申请列表
export function getPauseList(params?: any) {
    return request.get({ url: '/orderChange/pauseLists', params })
}

// 暂停详情
export function getPauseDetail(params: any) {
    return request.get({ url: '/orderChange/pauseDetail', params })
}

// 申请暂停
export function applyPause(params: any) {
    return request.post({ url: '/orderChange/applyPause', params })
}

// 取消暂停
export function cancelPause(params: any) {
    return request.post({ url: '/orderChange/cancelPause', params })
}

// 获取暂停类型选项
export function getPauseTypeOptions() {
    return request.get({ url: '/orderChange/pauseTypeOptions' })
}
