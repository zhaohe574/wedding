interface NavBarMetrics {
    statusBarHeight: number
    navBarHeight: number
    contentHeight: number
    capsuleWidth: number
    capsuleHeight: number
    capsuleMarginRight: number
    capsuleTop: number
    safeInset: number
    windowWidth: number
}

const DEFAULT_STATUS_BAR_HEIGHT = 0
const DEFAULT_CAPSULE_WIDTH = 88
const DEFAULT_CAPSULE_HEIGHT = 32
const DEFAULT_CAPSULE_MARGIN_RIGHT = 8
const DEFAULT_CAPSULE_TOP_GAP = 4

const resolveNavBarMetrics = (): NavBarMetrics => {
    let statusBarHeight = DEFAULT_STATUS_BAR_HEIGHT
    let windowWidth = 375
    let capsuleWidth = DEFAULT_CAPSULE_WIDTH
    let capsuleHeight = DEFAULT_CAPSULE_HEIGHT
    let capsuleMarginRight = DEFAULT_CAPSULE_MARGIN_RIGHT
    let capsuleTop = DEFAULT_CAPSULE_TOP_GAP

    try {
        const systemInfo = uni.getSystemInfoSync?.()
        statusBarHeight = Number(systemInfo?.statusBarHeight || DEFAULT_STATUS_BAR_HEIGHT)
        windowWidth = Number(systemInfo?.windowWidth || windowWidth)
    } catch (error) {
        console.warn('获取系统信息失败，使用默认导航尺寸兜底：', error)
    }

    try {
        if (typeof uni.getMenuButtonBoundingClientRect === 'function') {
            const rect = uni.getMenuButtonBoundingClientRect()
            if (rect?.width && rect?.height) {
                capsuleWidth = Number(rect.width || capsuleWidth)
                capsuleHeight = Number(rect.height || capsuleHeight)
                capsuleTop = Number(rect.top || statusBarHeight + DEFAULT_CAPSULE_TOP_GAP)
                capsuleMarginRight = Math.max(
                    windowWidth - Number(rect.right || 0),
                    DEFAULT_CAPSULE_MARGIN_RIGHT
                )
            }
        } else {
            capsuleTop = statusBarHeight + DEFAULT_CAPSULE_TOP_GAP
        }
    } catch (error) {
        capsuleTop = statusBarHeight + DEFAULT_CAPSULE_TOP_GAP
        console.warn('获取胶囊按钮信息失败，使用默认导航尺寸兜底：', error)
    }

    const topGap = Math.max(capsuleTop - statusBarHeight, DEFAULT_CAPSULE_TOP_GAP)
    const contentHeight = capsuleHeight + topGap * 2
    const safeInset = capsuleWidth + capsuleMarginRight

    return {
        statusBarHeight,
        navBarHeight: statusBarHeight + contentHeight,
        contentHeight,
        capsuleWidth,
        capsuleHeight,
        capsuleMarginRight,
        capsuleTop,
        safeInset,
        windowWidth
    }
}

export const useNavBarMetrics = () => {
    return resolveNavBarMetrics()
}
