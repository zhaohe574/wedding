import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import {
    ensureSceneAccess,
    getCurrentPageRoutePath,
    navigateByBackContract,
    resolvePageShellProtocol
} from '@/utils/page-contract'

export const ensureStaffCenterAccess = async (): Promise<boolean> => {
    const appStore = useAppStore()
    const userStore = useUserStore()

    if (!appStore.config?.feature_switch) {
        await appStore.getConfig()
    }

    if (
        userStore.isLogin &&
        (!userStore.userInfo ||
            Object.keys(userStore.userInfo).length === 0 ||
            typeof userStore.userInfo.is_staff === 'undefined')
    ) {
        await userStore.getUser()
    }

    const access = ensureSceneAccess({
        scene: 'staff',
        featureSwitch: appStore.config?.feature_switch,
        userInfo: userStore.userInfo,
        isLogin: userStore.isLogin
    })

    if (!access.allowed) {
        const routePath = getCurrentPageRoutePath()
        const protocol = resolvePageShellProtocol({
            routePath,
            declaredScene: 'staff'
        })

        uni.showToast({ title: access.reason || '无权限访问', icon: 'none' })
        setTimeout(() => navigateByBackContract(protocol), 1200)
        return false
    }
    return true
}
