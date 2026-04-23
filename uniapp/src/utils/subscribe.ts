/**
 * 微信小程序订阅消息工具类
 */
import {
    checkSceneSubscribe,
    recordSubscribe,
    batchRecordSubscribe,
    getSceneList
} from '@/api/subscribe'

// 场景类型
export type SubscribeScene =
    | 'order_confirm' // 订单确认
    | 'schedule_remind' // 档期提醒
    | 'refund_result' // 退款结果
    | 'ticket_update' // 工单更新
    | 'waitlist_release' // 候补释放
    | 'waitlist_expired' // 候补失效

interface SubscribeResult {
    success: boolean
    templateId?: string
    errMsg?: string
}

interface SceneInfo {
    scene: string
    name: string
    description: string
    template_id: string
    template_name: string
}

let sceneCache: SceneInfo[] | null = null

/**
 * 请求单个订阅消息授权
 * @param templateId 模板ID
 * @param scene 场景
 * @returns 订阅结果
 */
export async function requestSubscribe(
    templateId: string,
    scene?: string
): Promise<SubscribeResult> {
    // #ifdef MP-WEIXIN
    return new Promise((resolve) => {
        uni.requestSubscribeMessage({
            tmplIds: [templateId],
            success: async (res: any) => {
                const result = res[templateId]
                const accepted = result === 'accept'

                // 记录订阅结果到后端
                try {
                    await recordSubscribe({
                        template_id: templateId,
                        scene: scene || '',
                        result: accepted ? 'accept' : 'reject'
                    })
                } catch (e) {
                    console.error('记录订阅结果失败', e)
                }

                resolve({
                    success: accepted,
                    templateId,
                    errMsg: accepted ? '' : '用户拒绝订阅'
                })
            },
            fail: (err: any) => {
                resolve({
                    success: false,
                    templateId,
                    errMsg: err.errMsg || '订阅失败'
                })
            }
        })
    })
    // #endif

    // #ifndef MP-WEIXIN
    return { success: false, errMsg: '当前平台不支持订阅消息' }
    // #endif
}

/**
 * 请求多个订阅消息授权
 * @param templateIds 模板ID数组
 * @param scene 场景
 * @returns 订阅结果
 */
export async function requestMultiSubscribe(
    templateIds: string[],
    scene?: string
): Promise<Record<string, SubscribeResult>> {
    // #ifdef MP-WEIXIN
    return new Promise((resolve) => {
        // 微信一次最多支持请求 5 个模板
        const ids = templateIds.slice(0, 5)

        uni.requestSubscribeMessage({
            tmplIds: ids,
            success: async (res: any) => {
                const results: Record<string, SubscribeResult> = {}
                const backendResults: Record<string, 'accept' | 'reject'> = {}

                ids.forEach((id) => {
                    const result = res[id]
                    const accepted = result === 'accept'
                    results[id] = {
                        success: accepted,
                        templateId: id,
                        errMsg: accepted ? '' : '用户拒绝订阅'
                    }
                    backendResults[id] = accepted ? 'accept' : 'reject'
                })

                // 批量记录订阅结果
                try {
                    await batchRecordSubscribe({
                        results: backendResults,
                        scene: scene || ''
                    })
                } catch (e) {
                    console.error('记录订阅结果失败', e)
                }

                resolve(results)
            },
            fail: (err: any) => {
                const results: Record<string, SubscribeResult> = {}
                ids.forEach((id) => {
                    results[id] = {
                        success: false,
                        templateId: id,
                        errMsg: err.errMsg || '订阅失败'
                    }
                })
                resolve(results)
            }
        })
    })
    // #endif

    // #ifndef MP-WEIXIN
    const results: Record<string, SubscribeResult> = {}
    templateIds.forEach((id) => {
        results[id] = { success: false, templateId: id, errMsg: '当前平台不支持订阅消息' }
    })
    return results
    // #endif
}

/**
 * 根据场景请求订阅授权
 * @param scene 场景标识
 * @returns 订阅结果
 */
export async function requestSubscribeByScene(scene: SubscribeScene): Promise<SubscribeResult> {
    try {
        // 检查场景订阅状态
        const sceneInfo = await checkSceneSubscribe(scene)

        if (!sceneInfo.need_subscribe) {
            // 已订阅或场景未配置
            return {
                success: true,
                templateId: sceneInfo.template_id || '',
                errMsg: ''
            }
        }

        // 请求订阅授权
        return await requestSubscribe(sceneInfo.template_id, scene)
    } catch (e: any) {
        return {
            success: false,
            errMsg: e.message || '检查订阅状态失败'
        }
    }
}

/**
 * 获取所有场景信息
 * @returns 场景列表
 */
export async function getAllScenes(): Promise<SceneInfo[]> {
    if (sceneCache) {
        return sceneCache
    }

    try {
        const res = await getSceneList()
        const scenes = Array.isArray(res) ? res : []
        sceneCache = scenes
        return scenes
    } catch (e) {
        console.error('获取场景列表失败', e)
        return []
    }
}

export function setSceneCache(scenes: SceneInfo[]) {
    sceneCache = Array.isArray(scenes) ? scenes : []
}

export function clearSceneCache() {
    sceneCache = null
}

function extractSceneTemplateIds(scenes: SceneInfo[], sceneNames: string[]) {
    return Array.from(
        new Set(
            scenes
                .filter((scene) => sceneNames.includes(scene.scene))
                .map((scene) => scene.template_id)
                .filter(Boolean)
        )
    )
}

/**
 * 订单相关场景订阅
 * 在用户点击提交订单后的授权动作中调用，一次性请求多个订单相关的订阅授权
 */
export async function subscribeOrderScenes(): Promise<boolean> {
    try {
        const scenes = await getAllScenes()
        const templateIds = extractSceneTemplateIds(scenes, ['order_confirm', 'schedule_remind'])

        if (templateIds.length === 0) {
            return true
        }

        const results = await requestMultiSubscribe(templateIds, 'order')

        // 检查是否有成功的订阅
        return Object.values(results).some((r) => r.success)
    } catch (e) {
        console.error('订阅订单场景失败', e)
        return false
    }
}

/**
 * 售后相关场景订阅
 */
export async function subscribeAfterSaleScenes(): Promise<boolean> {
    try {
        const scenes = await getAllScenes()
        const templateIds = extractSceneTemplateIds(scenes, ['ticket_update', 'refund_result'])

        if (templateIds.length === 0) {
            return true
        }

        const results = await requestMultiSubscribe(templateIds, 'aftersale')
        return Object.values(results).some((r) => r.success)
    } catch (e) {
        console.error('订阅售后场景失败', e)
        return false
    }
}

/**
 * 候补相关场景订阅
 */
export async function subscribeWaitlistScenes(): Promise<boolean> {
    try {
        const scenes = await getAllScenes()
        const templateIds = extractSceneTemplateIds(scenes, [
            'waitlist_release',
            'waitlist_expired'
        ])

        if (templateIds.length === 0) {
            return true
        }

        const results = await requestMultiSubscribe(templateIds, 'waitlist')
        return Object.values(results).some((r) => r.success)
    } catch (e) {
        console.error('订阅候补场景失败', e)
        return false
    }
}

/**
 * 显示订阅提示弹窗
 * @param title 标题
 * @param content 内容
 * @param confirmCallback 确认回调
 */
export function showSubscribeTip(title: string, content: string, confirmCallback: () => void) {
    uni.showModal({
        title,
        content,
        confirmText: '去订阅',
        cancelText: '暂不订阅',
        success: (res) => {
            if (res.confirm) {
                confirmCallback()
            }
        }
    })
}

export default {
    requestSubscribe,
    requestMultiSubscribe,
    requestSubscribeByScene,
    getAllScenes,
    subscribeOrderScenes,
    subscribeAfterSaleScenes,
    subscribeWaitlistScenes,
    showSubscribeTip
}
