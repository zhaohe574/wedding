import { getMnpSubscribeTemplates } from '@/api/app'

export interface MnpSubscribeResult {
    success: boolean
    skipped?: boolean
    accepted: string[]
    rejected: string[]
    error?: string
}

/**
 * 触发微信小程序订阅消息授权弹窗
 * @param scenes 业务场景代码列表，如 ['activity_update'] 或 ['questionnaire_update']
 * @returns 授权结果 Promise，保证绝不抛出未捕获异常
 */
export async function requestMnpSubscribe(scenes: string[] = []): Promise<MnpSubscribeResult> {
    try {
        // #ifndef MP-WEIXIN
        // 非微信小程序环境（如 H5、App），直接跳过
        return { success: true, skipped: true, accepted: [], rejected: [] }
        // #endif

        // 1. 获取后端当前配置并启用的订阅消息模板 ID
        const sceneParam = scenes.join(',')
        const res: any = await getMnpSubscribeTemplates(sceneParam)
        const tmplIds: string[] = Array.isArray(res?.tmpl_ids) ? res.tmpl_ids : []

        if (!tmplIds.length) {
            return { success: true, skipped: true, accepted: [], rejected: [] }
        }

        // 2. 限制单次最多唤起 3 个模板（微信 API 硬性上限）
        const validTmplIds = tmplIds.slice(0, 3)

        // 3. 调用微信原生 requestSubscribeMessage
        return await new Promise<MnpSubscribeResult>((resolve) => {
            if (typeof uni.requestSubscribeMessage !== 'function') {
                return resolve({ success: true, skipped: true, accepted: [], rejected: [] })
            }

            uni.requestSubscribeMessage({
                tmplIds: validTmplIds,
                success: (result: any) => {
                    const accepted: string[] = []
                    const rejected: string[] = []

                    validTmplIds.forEach((id) => {
                        if (result[id] === 'accept') {
                            accepted.push(id)
                        } else {
                            rejected.push(id)
                        }
                    })

                    resolve({ success: true, accepted, rejected })
                },
                fail: (err: any) => {
                    // 微信端拒绝或接口调用失败，静默降级不阻断业务
                    resolve({
                        success: false,
                        error: err?.errMsg || '用户取消授权',
                        accepted: [],
                        rejected: validTmplIds
                    })
                }
            })
        })
    } catch (e: any) {
        return {
            success: false,
            error: e?.message || '订阅授权异常',
            accepted: [],
            rejected: []
        }
    }
}
