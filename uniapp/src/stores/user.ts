import { getUserCenter } from '@/api/user'
import { TOKEN_KEY } from '@/enums/constantEnums'
import cache from '@/utils/cache'
import { defineStore } from 'pinia'

interface UserSate {
    userInfo: Record<string, any>
    token: string | null
    temToken: string | null
}
export const useUserStore = defineStore({
    id: 'userStore',
    state: (): UserSate => ({
        userInfo: {},
        token: cache.get(TOKEN_KEY) || null,
        temToken: null
    }),
    getters: {
        isLogin: (state) => !!state.token
    },
    actions: {
        async getUser() {
            // 如果没有 token，跳过获取用户信息
            if (!this.token && !this.temToken) {
                return
            }
            
            try {
                const data = await getUserCenter({
                    token: this.token || this.temToken
                })
                this.userInfo = data
            } catch (error) {
                // 静默处理错误，避免在未登录时显示错误提示
                console.log('获取用户信息失败:', error)
            }
        },
        login(token: string) {
            this.token = token
            cache.set(TOKEN_KEY, token)
        },
        logout() {
            this.token = ''
            this.userInfo = {}
            cache.remove(TOKEN_KEY)
        }
    }
})
