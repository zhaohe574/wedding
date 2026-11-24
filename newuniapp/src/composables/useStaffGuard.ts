import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getMe, getUserCenter } from '@/api/user'
import { isApiError } from '@/request/error'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { toLogin } from '@/utils/login'

export type StaffGuardPhase = 'checking' | 'allowed' | 'denied' | 'error'
export type StaffGuardReason = '' | 'unauth' | 'not_staff' | 'disabled'

interface AccessSnapshot {
    token: string
    isStaff: boolean
    staffCenterEnabled: boolean
    fetchedAt: number
}

const CACHE_TTL_MS = 60_000

let snapshot: AccessSnapshot | null = null
let inflight: { token: string; promise: Promise<AccessSnapshot> } | null = null

export function invalidateStaffGuardCache(): void {
    snapshot = null
    inflight = null
}

async function resolveAccess(token: string, appStore: ReturnType<typeof useAppStore>): Promise<AccessSnapshot> {
    const readAppSwitch = () =>
        (appStore.config as any)?.feature_switch?.staff_center ??
        (appStore.config as any)?.index?.feature_switch?.staff_center

    try {
        const res: any = await getMe()
        const degraded = Array.isArray(res?.degraded) ? res.degraded : []
        if (res && !degraded.includes('user')) {
            if (res.feature_switch && typeof res.feature_switch === 'object') {
                appStore.config = {
                    ...(appStore.config || {}),
                    feature_switch: {
                        ...((appStore.config as any)?.feature_switch || {}),
                        ...res.feature_switch
                    }
                }
            }
            const appSwitch = readAppSwitch()
            return {
                token,
                isStaff: Number(res?.roles?.is_staff ?? 0) === 1,
                staffCenterEnabled: Number(res?.feature_switch?.staff_center ?? appSwitch ?? 1) === 1,
                fetchedAt: Date.now()
            }
        }
    } catch (err) {
        if (!isApiError(err) || err.errorCode !== 'V2_UNAVAILABLE') {
            throw err
        }
    }

    const center: any = await getUserCenter()
    const appSwitch = readAppSwitch()
    return {
        token,
        isStaff: Number(center?.is_staff ?? 0) === 1,
        staffCenterEnabled: Number(appSwitch ?? 1) === 1,
        fetchedAt: Date.now()
    }
}

/**
 * 服务人员分包页面级访问守卫（pkg-staff 全页复用）。
 *
 * 进入任意 pkg-staff 页面前强校验：登录态 + roles.is_staff === 1 + feature_switch.staff_center === 1，
 * 拦截未登录或普通用户在页面渲染与发 /staff_center/* 请求之前。
 */
export function useStaffGuard(onRetry?: () => void | Promise<void>) {
    const userStore = useUserStore()
    const appStore = useAppStore()

    const guardPhase = ref<StaffGuardPhase>('checking')
    const guardReason = ref<StaffGuardReason>('')
    const guardError = ref<Record<string, any> | Error | string | null>(null)
    const guardAllowed = computed(() => guardPhase.value === 'allowed')

    function applySnapshot(next: AccessSnapshot): boolean {
        guardError.value = null
        if (!next.staffCenterEnabled) {
            guardPhase.value = 'denied'
            guardReason.value = 'disabled'
            return false
        }
        if (!next.isStaff) {
            guardPhase.value = 'denied'
            guardReason.value = 'not_staff'
            return false
        }
        guardPhase.value = 'allowed'
        guardReason.value = ''
        return true
    }

    async function verify(force = false): Promise<boolean> {
        if (!userStore.isLogin || !userStore.token) {
            snapshot = null
            guardPhase.value = 'denied'
            guardReason.value = 'unauth'
            guardError.value = null
            return false
        }

        const token = userStore.token
        if (!force && snapshot && snapshot.token === token && Date.now() - snapshot.fetchedAt < CACHE_TTL_MS) {
            return applySnapshot(snapshot)
        }

        if (guardPhase.value !== 'allowed') {
            guardPhase.value = 'checking'
        }
        guardError.value = null

        try {
            let pending = inflight
            if (!pending || pending.token !== token || force) {
                const promise = resolveAccess(token, appStore).finally(() => {
                    if (inflight && inflight.promise === promise) {
                        inflight = null
                    }
                })
                pending = { token, promise }
                inflight = pending
            }

            const result = await pending.promise
            if (!userStore.isLogin || userStore.token !== token) {
                guardPhase.value = 'denied'
                guardReason.value = 'unauth'
                guardError.value = null
                return false
            }

            snapshot = result
            return applySnapshot(result)
        } catch (e) {
            if (!userStore.isLogin || (isApiError(e) && e.retryStrategy === 'relogin')) {
                guardPhase.value = 'denied'
                guardReason.value = 'unauth'
                guardError.value = null
                return false
            }
            guardPhase.value = 'error'
            guardReason.value = ''
            guardError.value = (e as Record<string, any> | Error | string | null) ?? null
            return false
        }
    }

    function onGuardRetry(): void {
        invalidateStaffGuardCache()
        if (onRetry) {
            void onRetry()
        } else {
            void verify(true)
        }
    }

    function onGuardAction(): void {
        if (guardReason.value === 'unauth') {
            toLogin()
            return
        }
        if (getCurrentPages().length > 1) {
            uni.navigateBack()
            return
        }
        uni.switchTab({ url: '/pages/index/index' })
    }

    onShow(() => {
        if (guardPhase.value === 'denied' && guardReason.value === 'unauth' && userStore.isLogin) {
            onGuardRetry()
        }
    })

    return {
        guardPhase,
        guardReason,
        guardError,
        guardAllowed,
        verify,
        onGuardRetry,
        onGuardAction
    }
}
