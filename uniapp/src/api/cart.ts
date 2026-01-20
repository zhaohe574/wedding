import request from '@/utils/request'

// 获取购物车列表
export function getCartList() {
    return request.get({ url: '/cart/lists' })
}

// 添加到购物车
export function addToCart(params: any) {
    return request.post({ url: '/cart/add', params })
}

// 更新购物车项
export function updateCartItem(params: any) {
    return request.post({ url: '/cart/update', params })
}

// 删除购物车项
export function deleteCartItem(params: any) {
    return request.post({ url: '/cart/delete', params })
}

// 批量删除
export function batchDeleteCart(params: any) {
    return request.post({ url: '/cart/batchDelete', params })
}

// 切换选中状态
export function toggleCartSelect(params: any) {
    return request.post({ url: '/cart/toggleSelect', params })
}

// 全选/取消全选
export function selectAllCart(params: any) {
    return request.post({ url: '/cart/selectAll', params })
}

// 计算总价
export function calculateCartTotal() {
    return request.get({ url: '/cart/calculate' })
}

// 检查冲突
export function checkCartConflicts() {
    return request.get({ url: '/cart/checkConflicts' })
}

// 清空购物车
export function clearCart() {
    return request.post({ url: '/cart/clear' })
}

// 获取购物车数量
export function getCartCount() {
    return request.get({ url: '/cart/count' })
}

// 生成分享码
export function generateCartShareCode(params: any) {
    return request.post({ url: '/cart/generateShareCode', params })
}

// 通过分享码获取购物车项
export function getCartByShareCode(params: any) {
    return request.get({ url: '/cart/getByShareCode', params })
}

// 保存为方案
export function saveCartPlan(params: any) {
    return request.post({ url: '/cart/savePlan', params })
}

// 获取我的方案列表
export function getMyCartPlans() {
    return request.get({ url: '/cart/myPlans' })
}

// 获取方案详情
export function getCartPlanDetail(params: any) {
    return request.get({ url: '/cart/planDetail', params })
}

// 删除方案
export function deleteCartPlan(params: any) {
    return request.post({ url: '/cart/deletePlan', params })
}

// 设为默认方案
export function setDefaultCartPlan(params: any) {
    return request.post({ url: '/cart/setDefaultPlan', params })
}

// 通过分享码复制方案
export function copyPlanByShareCode(params: any) {
    return request.post({ url: '/cart/copyPlanByShareCode', params })
}

// 比较方案
export function compareCartPlans(params: any) {
    return request.get({ url: '/cart/comparePlans', params })
}
