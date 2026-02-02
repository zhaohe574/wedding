import request from '@/utils/request'

// 个人资料
export function staffCenterProfile() {
    return request.get({ url: '/staff_center/profile' }, { isAuth: true })
}

export function staffCenterUpdateProfile(data: any) {
    return request.post({ url: '/staff_center/updateProfile', data }, { isAuth: true })
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
export function staffCenterPackageLists() {
    return request.get({ url: '/staff_center/packageLists' }, { isAuth: true })
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

// 档期
export function staffCenterScheduleMonth(data: any) {
    return request.get({ url: '/staff_center/scheduleMonth', data }, { isAuth: true })
}

export function staffCenterScheduleSetStatus(data: any) {
    return request.post({ url: '/staff_center/scheduleSetStatus', data }, { isAuth: true })
}

// 动态
export function staffCenterDynamicLists(data?: any) {
    return request.get({ url: '/staff_center/dynamicLists', data }, { isAuth: true })
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
