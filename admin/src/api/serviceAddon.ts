import request from '@/utils/request'

// ==================== 附加项（全局清单） ====================
// 与「服务人员 → 编辑 → 服务套餐」里按人配置的入口并存：这里是全量视角，
// 方便统一上下架与跨人员排查。

export function addonLists(params?: any) {
    return request.get({ url: '/service.addon/lists', params })
}

export function addonDetail(params: any) {
    return request.get({ url: '/service.addon/detail', params })
}

export function addonAdd(params: any) {
    return request.post({ url: '/service.addon/add', params })
}

export function addonEdit(params: any) {
    return request.post({ url: '/service.addon/edit', params })
}

export function addonDelete(params: any) {
    return request.post({ url: '/service.addon/delete', params })
}

export function addonChangeStatus(params: any) {
    return request.post({ url: '/service.addon/changeStatus', params })
}

export function addonAll(params?: any) {
    return request.get({ url: '/service.addon/all', params })
}
