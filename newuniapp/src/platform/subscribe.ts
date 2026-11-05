/**
 * 订阅消息。
 *
 * 模板 ID 一律从后端 /wechat/mnpSubscribeTemplates 取，不在前端硬编码 ——
 * 换 appid 或改模板时只改后台配置。
 */

import { getMnpSubscribeTemplates } from '@/api/user'

export type SubscribeScene =
    | 'order_confirm' // 订单确认
    | 'order_pay' // 支付成功
    | 'service_remind' // 服务提醒
    | 'aftersale_reply' // 售后回复
    | 'questionnaire' // 问卷待填

let templateCache: Record<string, string> | null = null

async function loadTemplates(scenes: string[]): Promise<string[]> {
    try {
        const list = await getMnpSubscribeTemplates({ scenes: scenes.join(',') })
        const map: Record<string, string> = {}
        for (const item of list || []) {
            if (item?.scene && item?.template_id) map[item.scene] = item.template_id
        }
        templateCache = { ...(templateCache || {}), ...map }
        return scenes.map((s) => map[s]).filter(Boolean)
    } catch {
        return []
    }
}

/**
 * 请求订阅授权。必须在用户点击事件的同步调用栈里触发，否则微信会拒绝。
 * @returns 用户是否同意（至少一个模板）
 */
export async function requestSubscribe(scenes: SubscribeScene[]): Promise<boolean> {
    // #ifdef MP-WEIXIN
    const ids = await loadTemplates(scenes)
    if (!ids.length) return false

    return new Promise((resolve) => {
        uni.requestSubscribeMessage({
            tmplIds: ids,
            success: (res: any) => {
                resolve(ids.some((id) => res[id] === 'accept'))
            },
            fail: () => resolve(false)
        })
    })
    // #endif
    // #ifndef MP-WEIXIN
    return false
    // #endif
}

/** 已缓存的模板 ID（调试用） */
export function getCachedTemplates() {
    return templateCache
}
