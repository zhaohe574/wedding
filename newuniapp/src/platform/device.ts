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

        const screenHeight = Number(win.screenHeight ?? 0)
        const safeAreaBottom =
            win.safeArea && typeof win.safeArea.bottom === 'number'
                ? Math.max(0, screenHeight - win.safeArea.bottom)
                : 0

        return {
            statusBarHeight: Number(win.statusBarHeight ?? 0),
            safeAreaBottom,
            windowWidth: Number(win.windowWidth ?? 375),
            windowHeight: Number(win.windowHeight ?? 667),
            platform: String(dev.platform ?? 'devtools'),
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
        const safeArea = sys.safeArea
        const safeAreaBottom =
            safeArea && typeof safeArea.bottom === 'number' && typeof sys.screenHeight === 'number'
                ? Math.max(0, sys.screenHeight - safeArea.bottom)
                : 0

        return {
            statusBarHeight: Number(sys.statusBarHeight ?? 0),
            safeAreaBottom,
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
