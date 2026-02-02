import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'

export const ensureStaffCenterAccess = async (): Promise<boolean> => {
    const appStore = useAppStore()
    const userStore = useUserStore()

    if (!appStore.config?.feature_switch) {
        await appStore.getConfig()
    }

    if (appStore.config?.feature_switch?.staff_center !== 1 || !userStore.userInfo?.is_staff) {
        uni.showToast({ title: '无权限访问', icon: 'none' })
        setTimeout(() => uni.navigateBack(), 1200)
        return false
    }
    return true
}
