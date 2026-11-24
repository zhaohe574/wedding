/**
 * 订阅消息。
 *
 * 模板 ID 一律从后端 /wechat/mnpSubscribeTemplates 取，不在前端硬编码 ——
 * 换 appid 或改模板时只改后台配置。
 *
 * ⚠️ 微信限制：uni.requestSubscribeMessage 必须在用户 tap 的**同步调用栈**内发起。
 * 先 await 网络请求再弹订阅 = 脱离手势栈，微信会静默拒绝（无弹窗、fail 回调）。
 * 所以 requestSubscribe 优先同步读 templateCache，只有缓存未命中才降级走网络。
 */
import { getMnpSubscribeTemplates } from '@/api/user'

export type SubscribeScene =
    | 'questionnaire_update' // 问卷更新提醒
    | 'activity_update' // 活动通知
    | 'order_confirm' // 订单确认
    | 'order_pay' // 支付成功
    | 'service_remind' // 服务提醒
    | 'aftersale_reply' // 售后回复
    | 'questionnaire' // 问卷待填

let templateCache: Record<string, string> | null = null

/** 按传入 scenes 顺序取已缓存的模板 ID，去重、剔空 */
function readCachedIds(scenes: string[]): string[] {
    const ids: string[] = []
    for (const scene of scenes) {
        const id = templateCache?.[scene]
        if (id && !ids.includes(id)) ids.push(id)
    }
    return ids
}

/**
 * 拉取模板并写入缓存。后端返回 `{ templates: [...], tmpl_ids: [...] }`，
 * 旧版本后端是裸数组形态，两者都兼容。
 */
async function loadTemplates(scenes: string[]): Promise<string[]> {
    try {
        const res = await getMnpSubscribeTemplates({ scenes: scenes.join(',') })
        // 新形态：信封 data 里的 templates 数组；旧形态：data 本身就是数组
        const list: Array<{ scene: string; template_id: string }> = Array.isArray(res)
            ? (res as unknown as Array<{ scene: string; template_id: string }>)
            : (res?.templates as Array<{ scene: string; template_id: string }> | undefined) || []
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
 * 预加载订阅模板（onLoad / onShow / 弹窗打开时调用），提前填充缓存，
 * 让用户真正点击提交时 requestSubscribe 能同步命中手势栈。
 */
export function preloadSubscribeTemplates(scenes: SubscribeScene[]): Promise<string[]> {
    // 已全部命中缓存就不必再发请求
    if (scenes.every((scene) => templateCache?.[scene])) {
        return Promise.resolve(readCachedIds(scenes))
    }
    return loadTemplates(scenes)
}

/**
 * 请求订阅授权。必须在用户点击事件的同步调用栈里触发，否则微信会拒绝。
 * @returns 用户是否同意（至少一个模板）；任何失败都不抛异常，返回 false 放行业务提交
 */
export async function requestSubscribe(scenes: SubscribeScene[]): Promise<boolean> {
    try {
        // #ifdef MP-WEIXIN
        // 优先同步读缓存：不 await 网络，保证 requestSubscribeMessage 还在手势同步栈内
        let ids = readCachedIds(scenes)
        if (!ids.length) {
            // 缓存未命中才降级走网络（此时弹窗可能被微信拒绝，fail 已兜底返回 false）
            ids = await loadTemplates(scenes)
        }
        if (!ids.length) return false

        return await new Promise<boolean>((resolve) => {
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
    } catch {
        // 订阅是可选增强：任何异常都不能阻断业务提交
        return false
    }
}

/** 已缓存的模板 ID（调试用） */
export function getCachedTemplates() {
    return templateCache
}
