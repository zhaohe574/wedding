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

/** 手机号验证码登录返回（与 mnpLogin 字段不同：这个直接带昵称头像） */
export interface AccountLoginResult {
    nickname?: string
    sn?: string
    mobile?: string
    avatar?: string
    token: string
}

/**
 * 手机号验证码登录（M-10 接线）。
 *
 * 后端 `LoginController::account` 支持两种 scene：
 *   1 = 账号/手机号 + 密码
 *   2 = 手机号 + 验证码   ← 本方法
 * 参数固定 `terminal: 1`（UserTerminalEnum::WECHAT_MMP，小程序端唯一合法值）。
 *
 * 三件必须知道的事：
 *   1. **受后台开关控制**：`scene` 必须在 `ConfigService::get('login','login_way')` 里，
 *      否则返回「不支持的登录方式」。前端渲染入口前先读 `appStore.getLoginConfig.login_way`
 *      （字符串数组，含 '2' 才显示验证码登录）。
 *   2. **不会自动注册**：号码不存在直接返回业务失败「用户不存在」。
 *      设计稿「未注册的手机号将自动创建账号」与后端行为相反，文案要改。
 *   3. 发码用 `smsSend({ mobile, scene: 'YZMDL' })`；同一手机号 1 分钟 1 条，验证码 5 分钟有效、6 位数字、最多校验 5 次。
 * @endpoint login.account
 */
export function accountLogin(data: { account: string; code: string }, extra?: { [k: string]: any }) {
    return request.post<AccountLoginResult>(
        '/login/account',
        {
            terminal: 1,
            scene: 2,
            account: String(data.account ?? '').trim(),
            code: String(data.code ?? '').trim(),
            ...(extra || {})
        },
        { auth: false }
    )
}

/** 手机号 + 密码登录（备用通道，scene=1；小程序第一屏不提供入口） @endpoint login.account */
export function passwordLogin(data: { account: string; password: string }) {
    return request.post<AccountLoginResult>(
        '/login/account',
        { terminal: 1, scene: 1, account: String(data.account ?? '').trim(), password: data.password },
        { auth: false }
    )
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
