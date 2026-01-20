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

// 申请换人
export function applyStaffChange(params: any) {
    return request.post({ url: '/orderChange/applyStaffChange', params })
}

// 申请加项
export function applyAddItem(params: any) {
    return request.post({ url: '/orderChange/applyAddItem', params })
}

// 取消变更
export function cancelChange(params: any) {
    return request.post({ url: '/orderChange/cancel', params })
}

// 获取变更类型选项
export function getChangeTypeOptions() {
    return request.get({ url: '/orderChange/typeOptions' })
}

// 获取时间段选项
export function getTimeSlotOptions() {
    return request.get({ url: '/orderChange/timeSlotOptions' })
}

// ==================== 订单转让 ====================

// 转让申请列表
export function getTransferList(params?: any) {
    return request.get({ url: '/orderChange/transferLists', params })
}

// 转让详情
export function getTransferDetail(params: any) {
    return request.get({ url: '/orderChange/transferDetail', params })
}

// 申请转让
export function applyTransfer(params: any) {
    return request.post({ url: '/orderChange/applyTransfer', params })
}

// 取消转让
export function cancelTransfer(params: any) {
    return request.post({ url: '/orderChange/cancelTransfer', params })
}

// 接收转让
export function acceptTransfer(params: any) {
    return request.post({ url: '/orderChange/acceptTransfer', params })
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
