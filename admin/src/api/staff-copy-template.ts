import request from '@/utils/request'

export function staffCopyTemplateLists(params?: any) {
    return request.get({ url: '/ops.staffCopyTemplate/lists', params })
}

export function staffCopyTemplateDetail(params: { id: number }) {
    return request.get({ url: '/ops.staffCopyTemplate/detail', params })
}

export function staffCopyTemplateAdd(params: Record<string, any>) {
    return request.post({ url: '/ops.staffCopyTemplate/add', params })
}

export function staffCopyTemplateEdit(params: Record<string, any>) {
    return request.post({ url: '/ops.staffCopyTemplate/edit', params })
}

export function staffCopyTemplateDelete(params: { id: number }) {
    return request.post({ url: '/ops.staffCopyTemplate/delete', params })
}

export function staffCopyTemplateChangeStatus(params: { id: number; status: number }) {
    return request.post({ url: '/ops.staffCopyTemplate/changeStatus', params })
}

export function staffCopyTemplateSceneOptions() {
    return request.get({ url: '/ops.staffCopyTemplate/sceneOptions' })
}

export function staffCopyTemplateImportDefaults() {
    return request.post({ url: '/ops.staffCopyTemplate/importDefaults' })
}
