import { defineStore } from 'pinia'
import { ADMIN_TOKEN_KEY } from '@/enums/constantEnums'
import cache from '@/utils/cache'
import { adminLogin, adminLogout } from '@/api/admin'

interface AdminState {
    token: string | null
    adminInfo: Record<string, any>
}

export const useAdminStore = defineStore({
    id: 'adminStore',
    state: (): AdminState => ({
        token: cache.get(ADMIN_TOKEN_KEY) || null,
        adminInfo: {}
    }),
    getters: {
        isLogin: (state) => !!state.token
    },
    actions: {
        async login(payload: { account: string; password: string }) {
            const data = await adminLogin({
                ...payload,
                terminal: 2
            })
            this.token = data.token
            this.adminInfo = data
            cache.set(ADMIN_TOKEN_KEY, data.token)
            return data
        },
        async logout() {
            try {
                await adminLogout()
            } finally {
                this.token = ''
                this.adminInfo = {}
                cache.remove(ADMIN_TOKEN_KEY)
            }
        }
    }
})
