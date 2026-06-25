import request from '@/utils/request'

const MONTHLY_REPORT_RENDER_TIMEOUT = 60 * 1000

export function monthlyReportMaterialLists(params?: any) {
    return request.get({ url: '/ops.monthlyReport/materialLists', params })
}

export function monthlyReportMaterialSave(params: any) {
    return request.post({ url: '/ops.monthlyReport/materialSave', params })
}

export function monthlyReportMaterialDelete(params: any) {
    return request.post({ url: '/ops.monthlyReport/materialDelete', params })
}

export function monthlyReportStaffOptions(params?: any) {
    return request.get({ url: '/ops.monthlyReport/staffOptions', params })
}

export function monthlyReportCategoryOptions() {
    return request.get({ url: '/ops.monthlyReport/categoryOptions' })
}

export function monthlyReportTemplateConfig(params: any) {
    return request.get({ url: '/ops.monthlyReport/templateConfig', params })
}

export function monthlyReportTemplateSave(params: any) {
    return request.post({ url: '/ops.monthlyReport/templateSave', params })
}

export function monthlyReportTemplateCopy(params: any) {
    return request.post({ url: '/ops.monthlyReport/templateCopy', params })
}

export function monthlyReportTemplateSetDefault(params: any) {
    return request.post({ url: '/ops.monthlyReport/templateSetDefault', params })
}

export function monthlyReportTemplateDisable(params: any) {
    return request.post({ url: '/ops.monthlyReport/templateDisable', params })
}

export function monthlyReportTemplatePreview(params: any) {
    return request.post({ url: '/ops.monthlyReport/templatePreview', params })
}

export function monthlyReportPreview(params: any) {
    return request.post({ url: '/ops.monthlyReport/preview', params, timeout: MONTHLY_REPORT_RENDER_TIMEOUT })
}

export function monthlyReportConfirm(params: any) {
    return request.post({ url: '/ops.monthlyReport/confirm', params, timeout: MONTHLY_REPORT_RENDER_TIMEOUT })
}

export function monthlyReportLatest(params?: any) {
    return request.get({ url: '/ops.monthlyReport/latest', params })
}

export function monthlyReportHistory(params?: any) {
    return request.get({ url: '/ops.monthlyReport/history', params })
}

export function monthlyReportDetail(params: any) {
    return request.get({ url: '/ops.monthlyReport/detail', params })
}

export function monthlyReportRegenerateAssets(params: any) {
    return request.post({ url: '/ops.monthlyReport/regenerateAssets', params, timeout: MONTHLY_REPORT_RENDER_TIMEOUT })
}
