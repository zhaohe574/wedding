import { defineStore } from 'pinia'

/**
 * 外观模式（system / light / dark）。
 *
 * 分工（不要越界）：
 *   - CSS 侧：tokens.css 与 tn-theme.scss 各自输出「page 浅色 → @media 暗色 →
 *     .scheme-light / .scheme-dark 手动覆盖」三段。系统暗色不经过任何 JS，首帧就生效。
 *   - JS 侧（本文件）：只做三件事 ——
 *       1. 记录用户手动选择（Storage 持久化，键 = STORAGE_KEY）；
 *       2. 把偏好翻译成 shell 根节点的类名（.scheme-light / .scheme-dark）与 H5 的
 *          <meta name="color-scheme"> / <html> 类名，保证手动覆盖能压过系统 @media；
 *       3. 给小程序原生窗口（uni.setBackgroundColor / setNavigationBarColor）提供
 *          六位 hex —— 这两个 API 只接受字面色值，所以 hex 集中收口在本文件，
 *          页面与组件不要再散写。
 *
 * 原生 hex 放在 .ts 里还有一个原因：门禁脚本对 .ts 豁免「hex 字面色值」规则，
 * 而 .vue 里散写会被判违规。
 */

export type ColorSchemePreference = 'system' | 'light' | 'dark'
export type ResolvedColorScheme = 'light' | 'dark'

/** Storage 键：与 index.html 的防闪屏脚本逐字一致，改名必须两处同步 */
export const STORAGE_KEY = 'color_scheme_preference'

/**
 * 小程序原生窗口底色。
 *   page    —— 普通页窗口底（= --c-bg）
 *   surface —— 卡片白 / 暖白的页面底（= --c-surface）
 *   darkHero—— 沉浸深色头图页（首页 / 开屏），两种模式都保持墨黑，避免转场闪白
 */
const NATIVE_BG = {
    light: { page: '#faf6ee', surface: '#fffdf8', darkHero: '#14110f' },
    dark: { page: '#141210', surface: '#1e1b18', darkHero: '#14110f' }
} as const

/** 状态栏前景色：light = 白字（压深色背景），dark = 黑字（压浅色背景） */
const NATIVE_FRONT = {
    light: '#ffffff',
    dark: '#000000'
} as const

export type NativeWindowSlot = 'page' | 'surface' | 'darkHero'

interface ColorSchemeState {
    /** 用户选择：system 表示跟随系统（Storage 中不落值） */
    preference: ColorSchemePreference
    /** 当前系统的明暗（system 偏好下的实际取值，也是「跟随系统」文案的来源） */
    systemTheme: ResolvedColorScheme
    /** init 只跑一次：App.onLaunch 与页面组件都可能触发 */
    initialized: boolean
}

function readPreference(): ColorSchemePreference {
    try {
        const raw = uni.getStorageSync(STORAGE_KEY)
        return raw === 'light' || raw === 'dark' ? raw : 'system'
    } catch {
        return 'system'
    }
}

/**
 * 读系统明暗。
 *   小程序：getAppBaseInfo().theme（基础库 2.20.1+），回退 getSystemInfoSync().theme；
 *   H5：matchMedia('(prefers-color-scheme: dark)')；
 *   其它端：没有可靠的系统信号，按浅色处理。
 */
function detectSystemTheme(): ResolvedColorScheme {
    // #ifdef MP-WEIXIN
    try {
        const api = uni as any
        const theme = api.getAppBaseInfo?.()?.theme ?? api.getSystemInfoSync?.()?.theme
        return theme === 'dark' ? 'dark' : 'light'
    } catch {
        return 'light'
    }
    // #endif

    // #ifdef H5
    try {
        return window.matchMedia?.('(prefers-color-scheme: dark)')?.matches ? 'dark' : 'light'
    } catch {
        return 'light'
    }
    // #endif

    return 'light'
}

export const useColorSchemeStore = defineStore({
    id: 'colorSchemeStore',
    state: (): ColorSchemeState => ({
        preference: readPreference(),
        systemTheme: detectSystemTheme(),
        initialized: false
    }),
    getters: {
        /** 实际生效的明暗：手动选择优先，否则跟系统 */
        resolved(state): ResolvedColorScheme {
            return state.preference === 'system' ? state.systemTheme : state.preference
        },
        isDark(): boolean {
            return this.resolved === 'dark'
        },
        /**
         * 挂在 page-shell 根节点的类名。跟随系统时返回空串 —— 那时由 @media 决定，
         * 额外加类反而会把系统切换锁死。
         */
        shellClass(state): '' | 'scheme-light' | 'scheme-dark' {
            if (state.preference === 'system') return ''
            return state.preference === 'dark' ? 'scheme-dark' : 'scheme-light'
        },
        /** 设置页三态选项的当前值文案 */
        label(state): string {
            if (state.preference === 'dark') return '深色模式'
            if (state.preference === 'light') return '浅色模式'
            return '跟随系统'
        },
        /** 「跟随系统」副文案：当前系统是深还是浅 */
        systemLabel(state): string {
            return state.systemTheme === 'dark' ? '深色' : '浅色'
        }
    },
    actions: {
        /** 幂等初始化：App.onLaunch 调用一次即可，重复调用无副作用 */
        init() {
            if (this.initialized) return
            this.initialized = true

            this.systemTheme = detectSystemTheme()
            this.syncH5Dom(this.preference)

            // #ifdef MP-WEIXIN
            try {
                const wxApi = (globalThis as any).wx
                const subscribe =
                    typeof wxApi?.onThemeChange === 'function'
                        ? (cb: (res: { theme?: string }) => void) => wxApi.onThemeChange(cb)
                        : typeof (uni as any).onThemeChange === 'function'
                          ? (cb: (res: { theme?: string }) => void) => (uni as any).onThemeChange(cb)
                          : null
                if (typeof subscribe === 'function') {
                    subscribe((res: { theme?: string }) => {
                        this.syncSystemTheme(res?.theme)
                    })
                }
            } catch {
                /* 低版本基础库没有 onThemeChange：退化为首帧值，不阻断启动 */
            }
            // #endif

            // #ifdef H5
            try {
                const mql = window.matchMedia?.('(prefers-color-scheme: dark)')
                if (mql) {
                    const onChange = (event: MediaQueryListEvent | MediaQueryList) => {
                        this.syncSystemTheme(event.matches ? 'dark' : 'light')
                    }
                    if (typeof mql.addEventListener === 'function') {
                        mql.addEventListener('change', onChange)
                    } else {
                        // Safari < 14
                        mql.addListener?.(onChange)
                    }
                }
                // 多标签页：别的标签页改了偏好时同步过来（storage 事件不会在本页触发）
                window.addEventListener('storage', (event) => {
                    if (event.key !== STORAGE_KEY) return
                    this.preference = readPreference()
                    this.syncH5Dom(this.preference)
                })
            } catch {
                /* 隐私模式 / 老浏览器：保留首帧值 */
            }
            // #endif
        },

        /** 落系统明暗：系统主题变化回调的统一入口 */
        syncSystemTheme(theme?: string) {
            this.systemTheme = theme === 'dark' ? 'dark' : 'light'
        },

        /** 主动重读系统明暗：从后台切回 / 模拟器切换外观后调用 */
        refreshSystemTheme() {
            this.systemTheme = detectSystemTheme()
        },

        /** 用户选择三态之一：写 Storage（system 时清除）+ 同步 H5 DOM */
        setPreference(next: ColorSchemePreference) {
            this.preference = next
            try {
                if (next === 'system') uni.removeStorageSync(STORAGE_KEY)
                else uni.setStorageSync(STORAGE_KEY, next)
            } catch {
                /* 存储不可写：本次会话仍生效，冷启动回到跟随系统 */
            }
            this.syncH5Dom(next)
        },

        /**
         * H5 手动覆盖落到 DOM：
         *   <meta name="color-scheme"> 让 UA 原生控件（滚动条 / 输入框）跟着变；
         *   <html> / <body> 上的 .scheme-* 让 tokens.css / tn-theme.scss 的覆盖段生效。
         * 跟随系统时把两者都复位，交还给 @media。
         */
        syncH5Dom(pref: ColorSchemePreference) {
            // #ifdef H5
            try {
                const meta = document.querySelector('meta[name="color-scheme"]')
                if (meta) meta.setAttribute('content', pref === 'system' ? 'light dark' : pref)

                const roots = [document.documentElement, document.body].filter(Boolean)
                for (const root of roots) {
                    root.classList.remove('scheme-light', 'scheme-dark')
                    if (pref === 'light' || pref === 'dark') root.classList.add('scheme-' + pref)
                }

                const root = document.documentElement
                if (pref === 'light' || pref === 'dark') {
                    root.dataset.colorScheme = pref
                } else {
                    delete root.dataset.colorScheme
                }
            } catch {
                /* 无 DOM 环境（SSR / 预渲染）：忽略 */
            }
            // #endif
        },

        /**
         * 原生导航（状态栏）配色。
         * @param barTone  导航条自身的基调（light = 压在深色头图上，需要白字）
         * @param darkHero true = 沉浸深色头图页，底色固定墨黑，不跟模式走
         */
        getNativeNavColors(barTone: 'dark' | 'light', darkHero = false) {
            const bg = NATIVE_BG[this.resolved]
            return {
                frontColor: NATIVE_FRONT[barTone],
                backgroundColor: darkHero ? bg.darkHero : bg.page
            }
        },

        /** 小程序窗口底色（下拉回弹 / 页面转场露出的那一层） */
        getNativeWindowBg(bg: NativeWindowSlot) {
            return NATIVE_BG[this.resolved][bg]
        },

        /**
         * 判断传入色值是否属于装修默认的浅白 / 米白系底色。
         *
         * 用途：装修下发的内联 background-color 是写死在数据里的，
         * 暗色下会变成「白卡 + 浅字」的失配。文字色靠 token 自动反转，
         * 白底却没跟着变，所以调用方在暗色下遇到这些默认值时应当忽略内联底色，
         * 回落到 --c-surface。
         *
         * 正则放在 .ts 里：门禁脚本对 .ts 豁免 hex 字面量，.vue 里写会判 R3-hex。
         */
        isLightDefaultHex(color: unknown): boolean {
            if (typeof color !== 'string') return false
            return /^#(?:fffdf8|ffffff|fff|faf6ee|f5eddd|f5efe4)$/i.test(color.trim())
        }
    }
})
