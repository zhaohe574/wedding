import { defineStore } from 'pinia'
import { TOKEN_KEY } from '@/enums/constantEnums'
import cache from '@/utils/cache'
import { useInteractionStore } from './interaction'
import { useBadgeStore } from './badge'

interface UserState {
    userInfo: Record<string, any>
    token: string | null
    temToken: string | null
}

/**
 * 用户态。
 *
 * token 存储键必须是 `app_token`（cache 前缀 app_ + TOKEN_KEY token）——
 * 与老端一致，升级后老用户无需重新登录。改这个键会导致全员掉线。
 */
export const useUserStore = defineStore({
    id: 'userStore',
    state: (): UserState => ({
        userInfo: {},
        token: cache.get(TOKEN_KEY) || null,
        temToken: null
    }),
    getters: {
        isLogin: (state) => !!state.token,
        userId: (state) => Number(state.userInfo?.id ?? 0)
    },
    actions: {
        setUserInfo(info: Record<string, any>) {
            this.userInfo = info || {}
        },
        login(token: string) {
            this.token = token
            // 正式登录态落库后临时凭证即作废
            this.temToken = null
            cache.set(TOKEN_KEY, token)
        },
        /** 未落库临时凭证：need_bind_mobile 拦截时暂存，登录页凭它完成强制绑定 */
        setTemToken(token: string | null) {
            this.temToken = token || null
        },
        logout() {
            this.token = null
            this.userInfo = {}
            cache.remove(TOKEN_KEY)
            // 刻意不动 temToken：并发 need_bind_mobile 拦截时是「先 logout 再暂存临时凭证」，
            // 在这里清掉会把刚存的凭证抹掉；由调用方按需 setTemToken(null)
            // 会话级点赞快照不能跨账号继承
            useInteractionStore().reset()
            // 角标同理：换账号后上一个账号的未读 / 待办不能残留
            useBadgeStore().clear()
        }
    }
})
