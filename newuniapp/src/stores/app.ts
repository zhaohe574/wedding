import { defineStore } from 'pinia'
import { getConfig } from '@/api/app'
import { BRAND_FALLBACK, sealOf, type BrandProfile } from '@/constants/brand'

/**
 * 全局配置（`GET /api/index/config`）+ 品牌归一化。
 *
 * 品牌三层取值（顺序固定，不要调换）：
 *   1. **remote**  —— 本次请求拿到的 `website.shop_name / shop_logo / shop_slogan`
 *   2. **cache**   —— 上次成功下发的结果（同步读 storage，首帧就能用，避免名称「兜底→真值」跳变）
 *   3. **fallback**—— `constants/brand.ts` 里的 `BRAND_FALLBACK`
 *
 * 更新机制（小程序没有服务端推送，这是物理边界）：
 *   - 冷启动 `App.vue onLaunch` 调 `getConfig()`，成功即写缓存；
 *   - 首页下拉刷新 / 进入「关于」等关键页可 `getConfig(true)` 强制刷新；
 *   - 后台改完名称，用户**下一次冷启动**（或强制刷新）生效，不需要发版。
 *
 * 缓存只存品牌三个字段，不存整份 config：
 *   config 里有 tabbar / 登录开关 / 版本号等业务配置，缓存它们会造成「后台改了但小程序还用旧值」。
 */

const BRAND_CACHE_KEY = 'brand_profile_cache'
const BRAND_CACHE_VERSION = 1

interface BrandCache {
    v: number
    name: string
    logo: string
    slogan: string
    savedAt: number
}

interface AppState {
    config: Record<string, any>
    /** 上次成功下发的品牌（同步读，仅作首帧占位） */
    cachedBrand: BrandCache | null
    /** 本次进程内是否已成功拉到配置 */
    configLoaded: boolean
    /** 本次进程内是否拉取失败过（用于埋点 / 排查，不参与渲染分支） */
    configFailed: boolean
}

/** 去掉首尾空白，并把 null / undefined / 非字符串统一成空串 */
function text(value: unknown): string {
    return typeof value === 'string' ? value.trim() : ''
}

function readBrandCache(): BrandCache | null {
    try {
        const raw = uni.getStorageSync(BRAND_CACHE_KEY)
        if (!raw) return null
        const data = typeof raw === 'string' ? JSON.parse(raw) : raw
        if (!data || data.v !== BRAND_CACHE_VERSION) return null
        if (!text(data.name)) return null
        return {
            v: BRAND_CACHE_VERSION,
            name: text(data.name),
            logo: text(data.logo),
            slogan: text(data.slogan),
            savedAt: Number(data.savedAt) || 0
        }
    } catch {
        // 存储被清理 / JSON 损坏 / 隐私模式下不可读：当作没有缓存
        return null
    }
}

function toBrandCache(brand: BrandProfile): BrandCache {
    return {
        v: BRAND_CACHE_VERSION,
        name: brand.name,
        logo: brand.logo,
        slogan: brand.slogan,
        savedAt: Date.now()
    }
}

function writeBrandCache(cache: BrandCache): void {
    try {
        uni.setStorageSync(BRAND_CACHE_KEY, JSON.stringify(cache))
    } catch {
        // 存储写满或不可写：不影响本次会话，下次冷启动退回兜底
    }
}

/**
 * 同一时刻只允许一个 `/index/config` 在飞。
 * App.vue、首页、登录页都可能触发，不去重就会并发 3 个请求、后到的覆盖先到的。
 */
let inflight: Promise<void> | null = null

export const useAppStore = defineStore({
    id: 'appStore',
    state: (): AppState => ({
        config: {
            domain: ''
        },
        cachedBrand: readBrandCache(),
        configLoaded: false,
        configFailed: false
    }),
    getters: {
        getWebsiteConfig: (state) => state.config.website || {},
        getLoginConfig: (state) => state.config.login || {},
        getTabbarConfig: (state) => state.config.tabbar || [],
        getStyleConfig: (state) => state.config.style || {},
        getH5Config: (state) => state.config.webPage || {},
        getCopyrightConfig: (state) => state.config.copyright || [],

        /**
         * 品牌唯一出口。任何展示位都从这里取，不要读 `config.website` 拼字段。
         * 字段级独立回落：后台只填了名称没传 LOGO 时，名称用 remote、图标继续用缓存/兜底。
         */
        brand(state): BrandProfile {
            const website = (state.config?.website || {}) as Record<string, unknown>
            const cached = state.cachedBrand

            const name = text(website.shop_name) || cached?.name || BRAND_FALLBACK.name
            return {
                name,
                logo: text(website.shop_logo) || cached?.logo || BRAND_FALLBACK.logo,
                slogan: text(website.shop_slogan) || cached?.slogan || BRAND_FALLBACK.slogan,
                seal: sealOf(name)
            }
        },

        /** 当前品牌值的来源，仅用于排查（设置页 / 日志），不参与渲染分支 */
        brandSource(state): 'remote' | 'cache' | 'fallback' {
            const website = (state.config?.website || {}) as Record<string, unknown>
            if (text(website.shop_name)) return 'remote'
            if (state.cachedBrand?.name) return 'cache'
            return 'fallback'
        }
    },
    actions: {
        getImageUrl(url: string) {
            return url.indexOf('http') ? `${this.config.domain}${url}` : url
        },
        /**
         * 合并一份「部分配置」。
         *
         * 首页 `GET /v2/home` 会顺带回一份 config 子集（只有 domain / website 等），
         * 比 `/index/config` 更早到达。这里把它并进来，让品牌位尽早拿到真值，
         * 但**不**把 `configLoaded` 置 true —— 否则 `/index/config` 会被「已加载」短路掉，
         * 登录开关 / tabbar 这些字段就永远拿不到了。
         *
         * 合并规则：已有非空值优先，部分配置只补空缺。
         */
        applyConfig(partial: Record<string, any> | null | undefined) {
            if (!partial || typeof partial !== 'object') return

            const current = (this.config || {}) as Record<string, any>

            // website 逐字段合并：已有非空值优先，部分配置只补空缺
            const mergedWebsite: Record<string, any> = { ...(partial.website || {}) }
            const currentWebsite = (current.website || {}) as Record<string, any>
            for (const key of Object.keys(currentWebsite)) {
                const value = currentWebsite[key]
                if (value !== '' && value !== null && value !== undefined) {
                    mergedWebsite[key] = value
                }
            }

            // 顶层同理。不能直接 `{ ...partial, ...current }` —— 初始 state 里的
            // `domain: ''` 是空值，会把 partial 带回来的真实域名覆盖掉。
            const merged: Record<string, any> = { ...partial }
            for (const key of Object.keys(current)) {
                if (key === 'website') continue
                const value = current[key]
                if (value !== '' && value !== null && value !== undefined) {
                    merged[key] = value
                }
            }
            merged.website = mergedWebsite

            this.config = merged
            this.cachedBrand = toBrandCache(this.brand)
            writeBrandCache(this.cachedBrand)
        },
        /**
         * 拉取全局配置。
         * @param force true = 忽略「已加载过」直接再拉一次（下拉刷新 / 从后台改完回来验活用）
         */
        async getConfig(force = false): Promise<void> {
            if (inflight) return inflight
            if (this.configLoaded && !force) return

            inflight = (async () => {
                try {
                    const data = await getConfig()
                    if (!data || typeof data !== 'object') {
                        this.configFailed = true
                        return
                    }
                    this.config = data
                    this.configLoaded = true
                    this.configFailed = false
                    // 只把品牌写缓存：其它配置项（登录开关 / tabbar / 版本）每次都要拿新的
                    this.cachedBrand = toBrandCache(this.brand)
                    writeBrandCache(this.cachedBrand)
                } catch (error) {
                    this.configFailed = true
                    throw error
                } finally {
                    inflight = null
                }
            })()

            return inflight
        }
    }
})
