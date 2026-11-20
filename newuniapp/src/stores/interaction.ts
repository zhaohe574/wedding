import { defineStore } from 'pinia'

/**
 * 动态点赞的会话内快照。
 *
 * 为什么需要它：列表页和详情页各自请求 `is_liked`，一旦接口口径/登录态不一致
 * （例如详情请求时 token 过期，后端返回 is_liked=false），同一篇动态会出现
 * 「列表红、详情黑」的状态漂移。这里把两页的点赞态和点赞数收敛到一份快照：
 *  · 用户在本会话内点过赞/取消赞 → setDynamicLike 覆盖接口值；
 *  · 列表首次加载 → seedDynamicLike 用列表接口值播种；
 *  · 详情只读快照、不反向播种，避免深链先打开详情时用未登录态污染快照；
 *  · 返回列表时 onShow 用快照回填，避免详情里点的赞回到列表又变回去。
 *
 * 只存内存，不落缓存：页面刷新后以服务端为准，避免本地脏状态长期覆盖接口。
 */
export interface DynamicLikeSnapshot {
    liked: boolean
    count: number
}

interface InteractionState {
    dynamicLikes: Record<number, DynamicLikeSnapshot>
}

export const useInteractionStore = defineStore({
    id: 'interactionStore',
    state: (): InteractionState => ({
        dynamicLikes: {}
    }),
    actions: {
        /** 用户动作或接口回执后的权威本地值 */
        setDynamicLike(id: number, liked: boolean, count: number) {
            const key = Number(id)
            if (!key) return
            this.dynamicLikes[key] = {
                liked: !!liked,
                count: Math.max(0, Number(count || 0))
            }
        },
        /** 只读快照；没有本地动作时返回 undefined，由调用方决定是否播种 */
        getDynamicLike(id: number): DynamicLikeSnapshot | undefined {
            return this.dynamicLikes[Number(id)]
        },
        /** 首次从接口拿到状态时播种，已有本地动作则不覆盖 */
        seedDynamicLike(id: number, liked: boolean, count: number) {
            const key = Number(id)
            if (!key || this.dynamicLikes[key]) return
            this.dynamicLikes[key] = {
                liked: !!liked,
                count: Math.max(0, Number(count || 0))
            }
        },
        /** 退出登录时清空，避免下一个账号继承上一个账号的点赞态 */
        reset() {
            this.dynamicLikes = {}
        }
    }
})
