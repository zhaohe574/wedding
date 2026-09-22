import { ref } from 'vue'
import { oaSubscribeStatus } from '@/api/app'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'

// 全局响应式状态：当前登录账号是否已绑定服务号
const isOaBound = ref<boolean>(false)
let checkingPromise: Promise<boolean> | null = null

export function getOaBoundCacheKey(userId: number | string) {
    return `oa_bound_status_${userId}`
}

/**
 * 检查当前登录账号的服务号绑定状态
 * @param force 是否强制刷新，忽略本地有效缓存
 */
export async function checkOaBoundStatus(force = false): Promise<boolean> {
    const user = useUserStore()
    if (!user.isLogin) {
        isOaBound.value = false
        return false
    }

    const userId = user.userInfo?.id || user.userInfo?.user_id || user.token || 'auth'
    const cacheKey = getOaBoundCacheKey(userId)

    // 若非强制刷新，先尝试使用本地缓存即时赋值，避免页面闪烁
    const cached = cache.get(cacheKey)
    if (typeof cached === 'boolean') {
        isOaBound.value = cached
        if (!force) return cached
    }

    if (checkingPromise) {
        return checkingPromise
    }

    checkingPromise = (async () => {
        try {
            const data: any = await oaSubscribeStatus()
            const bound = !!data?.bound
            isOaBound.value = bound
            cache.set(cacheKey, bound, 3600)
            return bound
        } catch {
            return isOaBound.value
        } finally {
            checkingPromise = null
        }
    })()

    return checkingPromise
}

/**
 * 手动设置当前账号绑定状态（如在解绑/完成绑定时同步）
 */
export function setOaBoundStatus(bound: boolean) {
    const user = useUserStore()
    isOaBound.value = bound
    if (user.isLogin) {
        const userId = user.userInfo?.id || user.userInfo?.user_id || user.token || 'auth'
        cache.set(getOaBoundCacheKey(userId), bound, 3600)
    }
}

// 监听跨页面全局绑定状态变更广播
if (typeof uni !== 'undefined' && uni.$on) {
    uni.$on('oa_binding_changed', (payload?: { bound?: boolean }) => {
        if (typeof payload?.bound === 'boolean') {
            setOaBoundStatus(payload.bound)
        } else {
            void checkOaBoundStatus(true)
        }
    })
}

export function useOaBound() {
    return {
        isOaBound,
        checkOaBoundStatus,
        setOaBoundStatus
    }
}
