import request from '@/utils/request'

// 线下建单只使用完整手机号精确查找客户。
export function getOrderCustomerOptions(params: { keyword: string }) {
    return request.get({ url: '/ops.order/customerOptions', params }, { ignoreCancelToken: true })
}

// 用户列表
export function getUserList(params: any) {
    return request.get({ url: '/content.user/lists', params }, { ignoreCancelToken: true })
}

// 用户详情
export function getUserDetail(params: any) {
    return request.get({ url: '/content.user/detail', params })
}

// 用户编辑
export function userEdit(params: any) {
    return request.post({ url: '/content.user/edit', params })
}
