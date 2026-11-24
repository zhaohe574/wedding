import request from '@/utils/request'

// 跟进记录列表
export function followRecordLists(params?: any) {
    return request.get({ url: '/crm.followRecord/lists', params })
}

// 跟进记录详情
export function followRecordDetail(params: any) {
    return request.get({ url: '/crm.followRecord/detail', params })
}

// 新增跟进记录
export function followRecordAdd(params: any) {
    return request.post({ url: '/crm.followRecord/add', params })
}

// 跟进记录枚举选项
export function followRecordOptions() {
    return request.get({ url: '/crm.followRecord/options' })
}

// 可跟进客户选项（支持关键词远程搜索）
export function followRecordCustomerOptions(params?: { keyword?: string; include_all?: number }) {
    return request.get({ url: '/crm.followRecord/customerOptions', params })
}

// 跟进统计
export function followRecordStats() {
    return request.get({ url: '/crm.followRecord/stats' })
}
