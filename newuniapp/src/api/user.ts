import request from '@/request'
import type { MeResponse } from '@shared/contracts'

export interface UserCenter {
    id: number
    nickname: string
    avatar: string
    mobile: string
    sex?: number
    create_time?: string
    [k: string]: any
}

/** 个人中心聚合（v2，1 次请求替代 8 次） @endpoint v2.me */
export function getMe() {
    return request.get<MeResponse>('/v2/me', {}, { auth: true, silent: true })
}

/** 旧版个人中心（v2 未上线时的降级路径） @endpoint user.center */
export function getUserCenter() {
    return request.get<UserCenter>('/user/center', {}, { auth: true, silent: true })
}

/** @endpoint user.info */
export function getUserInfo(header?: Record<string, string>) {
    return request.get<UserCenter>('/user/info', {}, { auth: true, header })
}

/** @endpoint user.setInfo */
export function setUserInfo(data: Partial<UserCenter>) {
    return request.post<null>('/user/setInfo', data, { auth: true })
}

/** @endpoint user.setProfile */
export function setProfile(data: { nickname?: string; avatar?: string; sex?: number }) {
    // silent：失败反馈由 profile 页的按钮下方红字承担（01 §6.0「错误写在字段下方、不弹 toast」）。
    // 保留 toast 会变成「两条提示说同一件事、位置还不同」，用户会以为发生了两个错误。
    // 全站仅 profile.vue 一个调用点，改 silent 无连带影响。
    return request.post<null>('/user/setProfile', data, { auth: true, silent: true })
}

/** 婚礼日期（我的页倒计时） @endpoint user.weddingDate */
export function getWeddingDate() {
    return request.get<{ date: string; days_remaining: number; venue?: string }>('/user/weddingDate', {}, { auth: true, silent: true })
}

/** 服务号订阅状态 @endpoint wechat.oaSubscribeStatus */
export interface OaSubscribeStatus {
    /** 是否已绑定平台账号 */
    bound: boolean
    /** 微信关注状态：followed / unfollowed / unknown */
    follow_status: 'followed' | 'unfollowed' | 'unknown'
    /** 是否具备服务号提醒接收资格 */
    can_receive: boolean
    /** 服务号通知渠道是否可用（不可用时绑定保留，仅推送失效） */
    channel_available: boolean
    /** 服务号名称（引导卡展示） */
    official_name?: string
    /** 服务号微信号（引导卡展示） */
    official_account?: string
    /** 服务号原始 ID（gh_ 开头，用于微信小程序直达服务号主页/会话） */
    official_original_id?: string
    /** 服务号二维码图片地址（可选，用于预览扫码关注） */
    official_qr_url?: string
    /** 今天是否已选择「不再提醒」 */
    reminder_snoozed_today?: boolean
    /** 「不再提醒」生效截止时间（秒级时间戳） */
    reminder_skip_until?: number
    [k: string]: any
}

/** 专属绑定邀请状态 @endpoint wechat.oaInvitationStatus / oaInvitationConfirm */
export interface OaInvitationStatusResult {
    /** pending / completed / expired 等 */
    state: string
    can_confirm: boolean
    message: string
    /** 邀请过期时间（秒级时间戳） */
    expires_time?: number
    /** 邀请确认成功时带回的最新绑定状态 */
    binding?: Partial<OaSubscribeStatus>
    [k: string]: any
}

export function getOaSubscribeStatus() {
    return request.get<OaSubscribeStatus>('/wechat/oaSubscribeStatus', {}, { auth: true, silent: true })
}

/**
 * 提醒弹窗前置检查专用：2 秒超时 + 不重试 + 不去重（每次提交前都要最新状态），
 * 且 token 失效不跳登录（skipAuthRedirect）—— 提醒是软引导，不能打断业务提交。
 */
export function getOaReminderStatus() {
    return request.get<OaSubscribeStatus>('/wechat/oaSubscribeStatus', {}, {
        auth: true,
        silent: true,
        timeout: 2000,
        retry: 0,
        skipAuthRedirect: true,
        dedupe: false
    })
}

/** 「今天不再提醒」的跨设备保存（失败静默，本机已即时生效） */
export function skipOaReminderToday() {
    return request.post<{ reminder_snoozed_today: boolean; reminder_skip_until: number }>(
        '/wechat/oaReminderSkipToday',
        {},
        { auth: true, silent: true, timeout: 2000, retry: 0, skipAuthRedirect: true }
    )
}

/** 读取专属绑定邀请状态 */
export function getOaInvitationStatus(invitation: string) {
    return request.post<OaInvitationStatusResult>('/wechat/oaInvitationStatus', { invitation }, {
        auth: true,
        silent: true,
        retry: 0
    })
}

/** 确认专属绑定邀请（绑定平台账号并开启服务号通知） */
export function confirmOaInvitation(invitation: string) {
    return request.post<OaInvitationStatusResult>('/wechat/oaInvitationConfirm', { invitation }, {
        auth: true,
        silent: true,
        retry: 0
    })
}

/** 解除服务号绑定（站内消息保留） */
export function unbindOaSubscribe() {
    return request.post<null>('/wechat/oaSubscribeUnbind', {}, { auth: true, retry: 0 })
}

/** 小程序订阅消息模板 @endpoint wechat.mnpSubscribeTemplates */
export interface MnpSubscribeTemplate {
    scene: string
    template_id: string
    title: string
}

export interface MnpSubscribeTemplatesResponse {
    templates: MnpSubscribeTemplate[]
    tmpl_ids: string[]
}

export function getMnpSubscribeTemplates(data: { scenes: string }) {
    return request.get<MnpSubscribeTemplatesResponse>('/wechat/mnpSubscribeTemplates', data, {
        auth: false,
        silent: true
    })
}
