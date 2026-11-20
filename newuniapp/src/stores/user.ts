import { defineStore } from 'pinia'
import { TOKEN_KEY } from '@/enums/constantEnums'
import cache from '@/utils/cache'
import { useInteractionStore } from './interaction'

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
            cache.set(TOKEN_KEY, token)
        },
        logout() {
            this.token = null
            this.userInfo = {}
            cache.remove(TOKEN_KEY)
            // 会话级点赞快照不能跨账号继承
            useInteractionStore().reset()
        }
    }
})
