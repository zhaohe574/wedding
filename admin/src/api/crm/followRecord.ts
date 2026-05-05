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

// 可跟进客户选项
export function followRecordCustomerOptions(params?: any) {
    return request.get({ url: '/crm.followRecord/customerOptions', params })
}
