import request from '@/utils/request'

// 顾问列表
export function advisorLists(params?: any) {
    return request.get({ url: '/crm.salesAdvisor/lists', params })
}

// 顾问详情
export function advisorDetail(params: any) {
    return request.get({ url: '/crm.salesAdvisor/detail', params })
}

// 新增顾问
export function advisorAdd(params: any) {
    return request.post({ url: '/crm.salesAdvisor/add', params })
}

// 编辑顾问
export function advisorEdit(params: any) {
    return request.post({ url: '/crm.salesAdvisor/edit', params })
}

// 删除顾问
export function advisorDelete(params: any) {
    return request.post({ url: '/crm.salesAdvisor/delete', params })
}

// 切换顾问状态
export function advisorChangeStatus(params: any) {
    return request.post({ url: '/crm.salesAdvisor/changeStatus', params })
}

// 校准当前客户数
export function advisorSyncCustomerCount(params: any) {
    return request.post({ url: '/crm.salesAdvisor/syncCustomerCount', params })
}

// 全量校准所有顾问负载与业绩
export function advisorSyncAllCustomerCount() {
    return request.post({ url: '/crm.salesAdvisor/syncAllCustomerCount' })
}

// 一键交接顾问活跃客户
export function advisorHandover(params: {
    from_advisor_id: number
    mode: 'target' | 'auto'
    to_advisor_id?: number
    reason?: string
}) {
    return request.post({ url: '/crm.salesAdvisor/handover', params })
}

// 顾问状态选项
export function advisorStatusOptions() {
    return request.get({ url: '/crm.salesAdvisor/statusOptions' })
}
