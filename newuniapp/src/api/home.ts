import request from '@/request'
import type { HomeResponse } from '@shared/contracts'

/**
 * 首页与装修。
 *
 * 主题（decorate id=5）与开屏（id=6）必须独立请求：
 * 主题失败要有本地默认色，开屏失败要静默跳过，都不能拖累首页。
 */

/** 首页聚合（v2，1 次请求替代 4 次） @endpoint v2.home */
export function getHome() {
    return request.get<HomeResponse>('/v2/home', {}, { silent: true })
}

/** 旧版首页（v2 未上线时的降级路径） @endpoint index.index */
export function getIndex() {
    return request.get<{ page: { data: string; meta: string }; article: any[] }>('/index/index', {}, { silent: true })
}

/** 装修页：1=首页，2=个人中心，3=客服设置，5=主题色，6=开屏广告，7=登录页 @endpoint index.decorate */
export function getDecorate(id: number) {
    return request.get<{ type?: number; name?: string; data?: string; meta?: string }>(
        '/index/decorate',
        { id },
        { silent: true }
    )
}

/** @endpoint index.config */
export function getConfig() {
    return request.get<any>('/index/config', {}, { silent: true })
}

/** @endpoint index.policy */
export function getPolicy(data: { type: string }) {
    return request.get<{ title: string; content: string }>('/index/policy', data, { silent: true })
}

/**
 * 热搜词。后端返回 { status, data: [{name, sort}] }，status=0 表示后台关闭热搜。
 * @endpoint search.hotLists
 */
export function getHotSearch() {
    return request.get<{ status: number; data: Array<{ name: string; sort?: number }> }>(
        '/search/hotLists',
        {},
        { silent: true }
    )
}
