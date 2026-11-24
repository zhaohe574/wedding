import request from '@/request'

/**
 * 登录与账号。
 * 小程序只走微信登录；账号密码接口是 H5 遗留，新端不提供入口。
 */

export interface MnpLoginResult {
    token: string
    is_new_user: number
    id?: number
    user_id?: number
    sn?: string
    nickname?: string
    avatar?: string
    mobile?: string
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

/** 新用户补头像昵称（同时将后端 is_new_user 置为 0） @endpoint login.updateUser */
// auth 必须为 true：后端需登录态校验临时 token；历史误标 false（靠请求层自动附带兜底，语义纠正）
// silent：失败提示由引导弹框内红字承担（与 profile 页 setProfile 同一口径），不再叠一条请求层 toast
export function updateUser(
    data: { avatar: string; nickname: string; [k: string]: any },
    header?: Record<string, string>
) {
    return request.post<null>('/login/updateUser', data, { auth: true, silent: true, header })
}

/** 绑定微信（手机号登录后补绑定） @endpoint login.mnpAuthBind */
export function mnpAuthBind(data: { code: string; [k: string]: any }) {
    return request.post<null>('/login/mnpAuthBind', data, { auth: true })
}

/** @endpoint login.logout */
export function logout() {
    return request.post<null>('/login/logout', {}, { auth: false, silent: true })
}

/** 手机号快速验证：getPhoneNumber 返回的 code 换手机号（后端成功时可能返回空 data） @endpoint user.getMobileByMnp */
export function getMobileByMnp(
    data: { code: string; [k: string]: any },
    header?: Record<string, string>
) {
    return request.post<{ mobile?: string } | null>('/user/getMobileByMnp', data, {
        auth: true,
        header
    })
}

/** 短信验证码绑定手机（备用通道） @endpoint user.bindMobile */
export function bindMobile(data: { mobile: string; code: string; [k: string]: any }) {
    return request.post<null>('/user/bindMobile', data, { auth: true })
}

/** @endpoint user.changePassword */
export function changePassword(data: { old_password: string; password: string; password_confirm: string }) {
    return request.post<null>('/user/changePassword', data, { auth: true })
}

/**
 * 发送短信验证码（服务人员工作台「更换手机号」专用封装）。
 *
 * @endpoint sms.sendCode —— 与 src/api/app.ts 的 smsSend 同端点同方法（POST），不重复建通道。
 * 契约终版（已核实 server 端源码，前端按此口径，勿再改）：
 *   · **POST**：SmsController::sendCode 用 SendSmsValidate->post() 校验，
 *     GET 请求参数拿不到直接报「请求方式错误/请输入手机号」，一发就失败；
 *   · **scene='BGSJHM'**：SmsLogic::sendCode 调 NoticeEnum::getSceneByTag($params['scene'])
 *     是纯 tag 字符串映射表（YZMDL/BDSJHM/BGSJHM/ZHDLHM→101..104）。
 *     ⚠ 数字 103（NoticeEnum::CHANGE_MOBILE_CAPTCHA）只用于后端 verify 侧，
 *     前端传 103 查不到 tag 会抛『场景值异常』——历史上这里踩过，别改回数字；
 *   · auth: true   改绑手机的发码在登录态下调用（后端 notNeedLogin，带 token 兼容）；
 *   · silent: true 60s 倒计时由页面控制，失败提示也由页面自己发 ——
 *     不 silent 会变成「请求层 toast + 页面提示」两条说同一件事。
 * 验证码本体 6 位数字、5 分钟有效、校验上限 5 次、同一手机号 60s 频控（前端倒计时按钮对应它）。
 * updateProfile 的 code 字段校验是 4-8 位数字（regex），6 位天然满足。
 */
export function sendSmsCode(data: { mobile: string; scene?: string }) {
    return request.post<{ code?: string; expire?: number }>(
        '/sms/sendCode',
        { scene: 'BGSJHM', ...data },
        { auth: true, silent: true }
    )
}

/** 忘记密码（免登录） @endpoint user.resetPassword */
export function resetPassword(data: { mobile: string; code: string; password: string; password_confirm: string }) {
    return request.post<null>('/user/resetPassword', data, { auth: false })
}
