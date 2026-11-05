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
export function getUserInfo() {
    return request.get<UserCenter>('/user/info', {}, { auth: true })
}

/** @endpoint user.setInfo */
export function setUserInfo(data: Partial<UserCenter>) {
    return request.post<null>('/user/setInfo', data, { auth: true })
}

/** @endpoint user.setProfile */
export function setProfile(data: { nickname?: string; avatar?: string; sex?: number }) {
    return request.post<null>('/user/setProfile', data, { auth: true })
}

/** 婚礼日期（我的页倒计时） @endpoint user.weddingDate */
export function getWeddingDate() {
    return request.get<{ date: string; days_remaining: number; venue?: string }>('/user/weddingDate', {}, { auth: true, silent: true })
}

/** 服务号订阅状态 @endpoint wechat.oaSubscribeStatus */
export function getOaSubscribeStatus() {
    return request.get<any>('/wechat/oaSubscribeStatus', {}, { auth: true, silent: true })
}

/** 小程序订阅消息模板 @endpoint wechat.mnpSubscribeTemplates */
export function getMnpSubscribeTemplates(data: { scenes: string }) {
    return request.get<Array<{ scene: string; template_id: string; title: string }>>(
        '/wechat/mnpSubscribeTemplates',
        data,
        { auth: false, silent: true }
    )
}
