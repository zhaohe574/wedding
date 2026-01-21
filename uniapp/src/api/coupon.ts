import request from '@/utils/request'

// 可领取的优惠券列表
export function getAvailableCoupons(params?: any) {
    return request.get({ url: '/coupon/availableList', params })
}

// 优惠券详情
export function getCouponDetail(params: any) {
    return request.get({ url: '/coupon/detail', params })
}

// 领取优惠券
export function receiveCoupon(params: any) {
    return request.post({ url: '/coupon/receive', params })
}

// 我的优惠券列表
export function getMyCoupons(params?: any) {
    return request.get({ url: '/coupon/myCoupons', params })
}

// 我的优惠券统计
export function getMyCouponStats() {
    return request.get({ url: '/coupon/myStats' })
}

// 订单可用优惠券列表
export function getOrderAvailableCoupons(params: any) {
    return request.get({ url: '/coupon/orderAvailable', params })
}

// 计算优惠金额
export function calculateCouponDiscount(params: any) {
    return request.post({ url: '/coupon/calculate', params })
}

// 使用优惠券
export function useCoupon(params: any) {
    return request.post({ url: '/coupon/use', params })
}

// 兑换优惠券
export function exchangeCoupon(params: any) {
    return request.post({ url: '/coupon/exchange', params })
}
