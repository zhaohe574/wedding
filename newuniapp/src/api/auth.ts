import request from '@/request'

/**
 * 登录与账号。
 * 小程序只走微信登录；账号密码接口是 H5 遗留，新端不提供入口。
 */

export interface MnpLoginResult {
    token: string
    is_new_user: number
    mobile?: string
    user_id?: number
}

/** 微信一键登录（wx.login → code） @endpoint login.mnpLogin */
export function mnpLogin(data: { code: string; [k: string]: any }) {
    return request.post<MnpLoginResult>('/login/mnpLogin', data, { auth: false })
}

/** 新用户补头像昵称（需要临时 token） @endpoint login.updateUser */
export function updateUser(data: { headimgurl?: string; nickname?: string; [k: string]: any }) {
    return request.post<{ token?: string }>('/login/updateUser', data, { auth: false })
}

/** 绑定微信（手机号登录后补绑定） @endpoint login.mnpAuthBind */
export function mnpAuthBind(data: { code: string; [k: string]: any }) {
    return request.post<null>('/login/mnpAuthBind', data, { auth: true })
}

/** @endpoint login.logout */
export function logout() {
    return request.post<null>('/login/logout', {}, { auth: false, silent: true })
}

/** 手机号快速验证：getPhoneNumber 返回的 code 换手机号 @endpoint user.getMobileByMnp */
export function getMobileByMnp(data: { code: string; [k: string]: any }) {
    return request.post<{ mobile: string }>('/user/getMobileByMnp', data, { auth: true })
}

/** 短信验证码绑定手机（备用通道） @endpoint user.bindMobile */
export function bindMobile(data: { mobile: string; code: string; [k: string]: any }) {
    return request.post<null>('/user/bindMobile', data, { auth: true })
}

/** @endpoint user.changePassword */
export function changePassword(data: { old_password: string; password: string; password_confirm: string }) {
    return request.post<null>('/user/changePassword', data, { auth: true })
}

/** 忘记密码（免登录） @endpoint user.resetPassword */
export function resetPassword(data: { mobile: string; code: string; password: string; password_confirm: string }) {
    return request.post<null>('/user/resetPassword', data, { auth: false })
}
