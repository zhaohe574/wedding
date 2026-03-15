import request from '@/utils/request'

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

// 用户编辑
export function adjustMoney(params: any) {
    return request.post({ url: '/content.user/adjustMoney', params })
}
