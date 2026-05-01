import { useAppStore } from '@/stores/app'

export const MINI_PROGRAM_REVIEW_MODE_TIP = '小程序送审模式已开启，暂不支持该操作'

export const isMiniProgramReviewMode = () => {
    const appStore = useAppStore()
    return Number(appStore.config?.feature_switch?.mini_program_review_mode || 0) === 1
}

export const ensureMiniProgramReviewModeConfig = async () => {
    const appStore = useAppStore()
    if (!appStore.configLoaded) {
        await appStore.getConfig().catch(() => ({}))
    }
    return isMiniProgramReviewMode()
}

export const showMiniProgramReviewModeTip = (title = MINI_PROGRAM_REVIEW_MODE_TIP) => {
    uni.showToast({
        title,
        icon: 'none'
    })
}

export const leaveBlockedMiniProgramReviewPage = () => {
    showMiniProgramReviewModeTip()
    setTimeout(() => {
        const pages = getCurrentPages()
        if (pages.length > 1) {
            uni.navigateBack()
            return
        }
        uni.switchTab({
            url: '/pages/index/index',
            fail: () => {
                uni.reLaunch({ url: '/pages/index/index' })
            }
        })
    }, 900)
}
