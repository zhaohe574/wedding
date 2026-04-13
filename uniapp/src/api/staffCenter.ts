import request from '@/utils/request'

// 工作台
export function staffCenterDashboard() {
    return request.get({ url: '/staff_center/dashboard' }, { isAuth: true })
}

export function staffCenterOrderStats() {
    return request.get({ url: '/staff_center/orderStats' }, { isAuth: true })
}

// 个人资料
export function staffCenterProfile() {
    return request.get({ url: '/staff_center/profile' }, { isAuth: true })
}

export function staffCenterUpdateProfile(data: any) {
    return request.post({ url: '/staff_center/updateProfile', data }, { isAuth: true })
}

// 证书
export function staffCenterCertificateLists(data?: any) {
    return request.get({ url: '/staff_center/certificateLists', data }, { isAuth: true })
}

export function staffCenterCertificateDetail(data: any) {
    return request.get({ url: '/staff_center/certificateDetail', data }, { isAuth: true })
}

export function staffCenterCertificateAdd(data: any) {
    return request.post({ url: '/staff_center/certificateAdd', data }, { isAuth: true })
}

export function staffCenterCertificateEdit(data: any) {
    return request.post({ url: '/staff_center/certificateEdit', data }, { isAuth: true })
}

export function staffCenterCertificateDelete(data: any) {
    return request.post({ url: '/staff_center/certificateDelete', data }, { isAuth: true })
}

// 作品
export function staffCenterWorkLists(data?: any) {
    return request.get({ url: '/staff_center/workLists', data }, { isAuth: true })
}

export function staffCenterWorkDetail(data: any) {
    return request.get({ url: '/staff_center/workDetail', data }, { isAuth: true })
}

export function staffCenterWorkAdd(data: any) {
    return request.post({ url: '/staff_center/workAdd', data }, { isAuth: true })
}

export function staffCenterWorkEdit(data: any) {
    return request.post({ url: '/staff_center/workEdit', data }, { isAuth: true })
}

export function staffCenterWorkDelete(data: any) {
    return request.post({ url: '/staff_center/workDelete', data }, { isAuth: true })
}

// 套餐
export function staffCenterPackageLists(data?: any) {
    return request.get({ url: '/staff_center/packageLists', data }, { isAuth: true })
}

export function staffCenterPackageDetail(data: any) {
    return request.get({ url: '/staff_center/packageDetail', data }, { isAuth: true })
}

export function staffCenterPackageAdd(data: any) {
    return request.post({ url: '/staff_center/packageAdd', data }, { isAuth: true })
}

export function staffCenterPackageUpdate(data: any) {
    return request.post({ url: '/staff_center/packageUpdate', data }, { isAuth: true })
}

export function staffCenterPackageRemove(data: any) {
    return request.post({ url: '/staff_center/packageRemove', data }, { isAuth: true })
}

// 附加项
export function staffCenterAddonLists(data?: any) {
    return request.get({ url: '/staff_center/addonLists', data }, { isAuth: true })
}

export function staffCenterAddonDetail(data: any) {
    return request.get({ url: '/staff_center/addonDetail', data }, { isAuth: true })
}

export function staffCenterAddonAdd(data: any) {
    return request.post({ url: '/staff_center/addonAdd', data }, { isAuth: true })
}

export function staffCenterAddonUpdate(data: any) {
    return request.post({ url: '/staff_center/addonUpdate', data }, { isAuth: true })
}

export function staffCenterAddonRemove(data: any) {
    return request.post({ url: '/staff_center/addonRemove', data }, { isAuth: true })
}

// 档期
export function staffCenterScheduleMonth(data: any) {
    return request.get({ url: '/staff_center/scheduleMonth', data }, { isAuth: true })
}

export function staffCenterScheduleSetStatus(data: any) {
    return request.post({ url: '/staff_center/scheduleSetStatus', data }, { isAuth: true })
}

// 订单
export function staffCenterOrderLists(data?: any) {
    return request.get({ url: '/staff_center/orderLists', data }, { isAuth: true })
}

export function staffCenterOrderDetail(data: any) {
    return request.get({ url: '/staff_center/orderDetail', data }, { isAuth: true })
}

export function staffCenterOrderConfirm(data: any) {
    return request.post({ url: '/staff_center/orderConfirm', data }, { isAuth: true })
}

export function staffCenterOrderStartService(data: any) {
    return request.post({ url: '/staff_center/orderStartService', data }, { isAuth: true })
}

export function staffCenterOrderComplete(data: any) {
    return request.post({ url: '/staff_center/orderComplete', data }, { isAuth: true })
}

export function staffCenterOrderConfirmLetterGenerate(data: any) {
    return request.post({ url: '/staff_center/orderConfirmLetterGenerate', data }, { isAuth: true })
}

export function staffCenterOrderConfirmLetterSaveAssets(data: any) {
    return request.post(
        { url: '/staff_center/orderConfirmLetterSaveAssets', data },
        { isAuth: true }
    )
}

export function staffCenterOrderConfirmLetterPush(data: any) {
    return request.post({ url: '/staff_center/orderConfirmLetterPush', data }, { isAuth: true })
}

export function staffCenterOrderConfirmLetterDetail(data: any) {
    return request.get({ url: '/staff_center/orderConfirmLetterDetail', data }, { isAuth: true })
}

export function staffCenterOrderConfirmLetterHistory(data: any) {
    return request.get({ url: '/staff_center/orderConfirmLetterHistory', data }, { isAuth: true })
}

export function staffCenterOrderConfirmLetterRegenerateAssets(data: any) {
    return request.post(
        { url: '/staff_center/orderConfirmLetterRegenerateAssets', data },
        { isAuth: true }
    )
}

// 动态
export function staffCenterDynamicLists(data?: any) {
    return request.get({ url: '/staff_center/dynamicLists', data }, { isAuth: true })
}

export function staffCenterDynamicDetail(data: any) {
    return request.get({ url: '/staff_center/dynamicDetail', data }, { isAuth: true })
}

export function staffCenterDynamicAdd(data: any) {
    return request.post({ url: '/staff_center/dynamicAdd', data }, { isAuth: true })
}

export function staffCenterDynamicEdit(data: any) {
    return request.post({ url: '/staff_center/dynamicEdit', data }, { isAuth: true })
}

export function staffCenterDynamicDelete(data: any) {
    return request.post({ url: '/staff_center/dynamicDelete', data }, { isAuth: true })
}
