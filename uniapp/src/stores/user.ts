import { getUserCenter } from '@/api/user'
import { TEMP_TOKEN_KEY, TOKEN_KEY } from '@/enums/constantEnums'
import cache from '@/utils/cache'
import { defineStore } from 'pinia'

const TEMP_TOKEN_EXPIRE_SECONDS = 10 * 60
const TEMP_TOKEN_STORAGE_KEY = `app_${TEMP_TOKEN_KEY}_raw`
const TEMP_TOKEN_STORAGE_EXPIRE_KEY = `app_${TEMP_TOKEN_KEY}_raw_expire`

const normalizeToken = (token: unknown) => String(token || '').trim()

const getNowSeconds = () => Math.round(Date.now() / 1000)

const setRawTempToken = (token: string) => {
    try {
        uni.setStorageSync(TEMP_TOKEN_STORAGE_KEY, token)
        uni.setStorageSync(
            TEMP_TOKEN_STORAGE_EXPIRE_KEY,
            getNowSeconds() + TEMP_TOKEN_EXPIRE_SECONDS
        )
    } catch (error) {}
}

const getRawTempToken = () => {
    try {
        const expire = Number(uni.getStorageSync(TEMP_TOKEN_STORAGE_EXPIRE_KEY) || 0)
        if (expire && expire < getNowSeconds()) {
            uni.removeStorageSync(TEMP_TOKEN_STORAGE_KEY)
            uni.removeStorageSync(TEMP_TOKEN_STORAGE_EXPIRE_KEY)
            return ''
        }
        return normalizeToken(uni.getStorageSync(TEMP_TOKEN_STORAGE_KEY))
    } catch (error) {
        return ''
    }
}

const removeRawTempToken = () => {
    try {
        uni.removeStorageSync(TEMP_TOKEN_STORAGE_KEY)
        uni.removeStorageSync(TEMP_TOKEN_STORAGE_EXPIRE_KEY)
    } catch (error) {}
}

interface UserSate {
    userInfo: Record<string, any>
    token: string | null
    temToken: string | null
}

interface LogoutOptions {
    clearTempToken?: boolean
}

export const useUserStore = defineStore({
    id: 'userStore',
    state: (): UserSate => ({
        userInfo: {},
        token: normalizeToken(cache.get(TOKEN_KEY)) || null,
        temToken: normalizeToken(cache.get(TEMP_TOKEN_KEY)) || null
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
            }
        },
        login(token: string) {
            const nextToken = normalizeToken(token)
            this.token = nextToken || null
            if (nextToken) {
                cache.set(TOKEN_KEY, nextToken)
            } else {
                cache.remove(TOKEN_KEY)
            }
        },
        setTemToken(token: string) {
            const nextToken = normalizeToken(token)
            if (!nextToken) {
                this.clearTemToken()
                return null
            }

            this.temToken = nextToken
            cache.set(TEMP_TOKEN_KEY, nextToken, TEMP_TOKEN_EXPIRE_SECONDS)
            setRawTempToken(nextToken)
            return nextToken
        },
        restoreTemToken() {
            const nextToken = normalizeToken(this.temToken) || normalizeToken(cache.get(TEMP_TOKEN_KEY)) || getRawTempToken()
            if (nextToken) {
                cache.set(TEMP_TOKEN_KEY, nextToken, TEMP_TOKEN_EXPIRE_SECONDS)
                setRawTempToken(nextToken)
            }
            this.temToken = nextToken || null
            return this.temToken
        },
        clearTemToken() {
            this.temToken = null
            cache.remove(TEMP_TOKEN_KEY)
            removeRawTempToken()
        },
        logout(options: LogoutOptions = {}) {
            const { clearTempToken = true } = options
            this.token = null
            this.userInfo = {}
            cache.remove(TOKEN_KEY)
            if (clearTempToken) {
                this.clearTemToken()
            }
        }
    }
})
