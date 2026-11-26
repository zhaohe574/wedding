/** 设备与系统信息（安全区、状态栏、机型） */

export interface DeviceInfo {
    statusBarHeight: number
    safeAreaBottom: number
    windowWidth: number
    windowHeight: number
    platform: string
    brand?: string
    model?: string
    system?: string
    sdkVersion?: string
}

let cached: DeviceInfo | null = null

/**
 * 安全区底部（px）统一求解。
 *
 * 真机踩坑：安卓getWindowInfo()/getSystemInfoSync() 会把三大金刚虚拟导航栏高度从
 * windowHeight 里扣掉，而 safeArea.bottom 是物理屏坐标，直接拿 screenHeight 相减
 * 会把系统按键栏重复算成安全区，底部多出一条空白；iOS 则必须用物理屏坐标相减。
 * 部分机型 safeAreaInsets.bottom 直接可用，优先取它（最准，不用推算）。
 */
function resolveSafeAreaBottom(info: any, platform: string): number {
    const insetBottom = Number(info?.safeAreaInsets?.bottom)
    if (Number.isFinite(insetBottom) && insetBottom >= 0 && insetBottom <= 64) {
        return Math.round(insetBottom)
    }

    let value = 0
    if (info?.safeArea && typeof info.safeArea.bottom === 'number') {
        const screenHeight = Number(info.screenHeight ?? 0)
        const windowHeight = Number(info.windowHeight ?? 0)
        const safeBottom = Number(info.safeArea.bottom)

        if (
            platform !== 'ios' &&
            windowHeight > 0 &&
            windowHeight < screenHeight &&
            safeBottom <= windowHeight
        ) {
            // 安卓：虚拟导航栏高度已含在 windowHeight 扣除量里，用可视窗口底推算，避免重复计入按键栏
            value = Math.max(0, windowHeight - safeBottom)
        } else {
            value = Math.max(0, screenHeight - safeBottom)
        }
    }

    // 异常值保护：脏机型可能给出数百 px 的离谱值，超出物理合理区间一律按 0 处理
    if (!Number.isFinite(value) || value < 0 || value > 64) return 0
    return Math.round(value)
}

/**
 * 优先用微信新版分包 API（getWindowInfo / getDeviceInfo / getAppBaseInfo）。
 * getSystemInfoSync 自基础库 2.20 起已废弃，会刷 deprecation 警告；
 * 但老基础库没有新 API，所以必须保留回退分支。
 *
 * 走 uni.* 而不是直接调 wx.* —— 避免依赖 wx 全局类型声明，H5 下也安全。
 */
function readFromNewApis(): Partial<DeviceInfo> | null {
    const api: any = uni as any
    if (typeof api.getWindowInfo !== 'function') return null

    try {
        const win = api.getWindowInfo()
        const dev = typeof api.getDeviceInfo === 'function' ? api.getDeviceInfo() : {}
        const base = typeof api.getAppBaseInfo === 'function' ? api.getAppBaseInfo() : {}
        const platform = String(dev.platform ?? 'devtools')

        return {
            statusBarHeight: Number(win.statusBarHeight ?? 0),
            safeAreaBottom: resolveSafeAreaBottom(
                { ...win, safeAreaInsets: win.safeAreaInsets },
                platform
            ),
            windowWidth: Number(win.windowWidth ?? 375),
            windowHeight: Number(win.windowHeight ?? 667),
            platform,
            brand: dev.brand,
            model: dev.model,
            system: dev.system,
            sdkVersion: base.SDKVersion
        }
    } catch {
        return null
    }
}

function readFromSystemInfo(): Partial<DeviceInfo> {
    try {
        const sys = uni.getSystemInfoSync() as any

        return {
            statusBarHeight: Number(sys.statusBarHeight ?? 0),
            safeAreaBottom: resolveSafeAreaBottom(sys, String(sys.platform ?? 'devtools')),
            windowWidth: Number(sys.windowWidth ?? 375),
            windowHeight: Number(sys.windowHeight ?? 667),
            platform: String(sys.platform ?? 'devtools'),
            brand: sys.brand,
            model: sys.model,
            system: sys.system,
            sdkVersion: sys.SDKVersion
        }
    } catch {
        return {}
    }
}

export function getDeviceInfo(): DeviceInfo {
    if (cached) return cached

    const source = readFromNewApis() || readFromSystemInfo()

    cached = {
        statusBarHeight: source.statusBarHeight ?? 0,
        safeAreaBottom: source.safeAreaBottom ?? 0,
        windowWidth: source.windowWidth ?? 375,
        windowHeight: source.windowHeight ?? 667,
        platform: source.platform ?? 'devtools',
        brand: source.brand,
        model: source.model,
        system: source.system,
        sdkVersion: source.sdkVersion
    }
    return cached
}

/** 自定义导航栏总高度（px）：状态栏 + 胶囊行 44 */
export function getNavBarHeight(): number {
    return getDeviceInfo().statusBarHeight + 44
}
