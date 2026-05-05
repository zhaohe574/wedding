import request from '@/utils/request'

// 客户列表
export function customerLists(params?: any) {
    return request.get({ url: '/crm.customer/lists', params })
}

// 客户详情
export function customerDetail(params: any) {
    return request.get({ url: '/crm.customer/detail', params })
}

// 编辑客户
export function customerEdit(params: any) {
    return request.post({ url: '/crm.customer/edit', params })
}

// 转移顾问
export function customerTransferAdvisor(params: any) {
    return request.post({ url: '/crm.customer/transferAdvisor', params })
}

// 可承接顾问选项
export function customerAdvisorOptions(params?: any) {
    return request.get({ url: '/crm.customer/advisorOptions', params })
}

// 客户枚举选项
export function customerOptions() {
    return request.get({ url: '/crm.customer/options' })
}
