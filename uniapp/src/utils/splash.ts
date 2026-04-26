import { getDecorate } from '@/api/shop'
import cache from '@/utils/cache'

export const SPLASH_PAGE_ID = 6
export const SPLASH_PAGE_PATH = '/pages/splash/splash'
export const SPLASH_HOME_PATH = '/pages/index/index'
export const SPLASH_STORAGE_KEY = 'splash_ad_shown'

export type SplashFrequency = 'session' | 'daily' | 'every_time' | 'first_visit'

export interface SplashAdConfig {
    enabled: boolean
    image: string
    logoImage: string
    autoEnterEnabled: boolean
    autoSeconds: number
    frequency: SplashFrequency
    buttonText: string
}

export const DEFAULT_SPLASH_CONFIG: SplashAdConfig = {
    enabled: false,
    image: '',
    logoImage: '',
    autoEnterEnabled: true,
    autoSeconds: 3,
    frequency: 'session',
    buttonText: '点击进入'
}

export const getDefaultSplashConfig = (): SplashAdConfig => ({
    ...DEFAULT_SPLASH_CONFIG
})

let sessionShown = false
let splashHomeBypassArmed = false

const normalizeBoolean = (value: unknown, fallback = false) => {
    if (value === true || value === 1 || value === '1' || value === 'true') return true
    if (value === false || value === 0 || value === '0' || value === 'false') return false
    return fallback
}

const normalizeSeconds = (value: unknown) => {
    const parsed = Number(value)
    if (!Number.isFinite(parsed)) return DEFAULT_SPLASH_CONFIG.autoSeconds
    return Math.min(Math.max(Math.round(parsed), 1), 10)
}

const normalizeFrequency = (value: unknown): SplashFrequency => {
    const frequency = String(value || '').trim()
    if (['every_time', 'every_launch', 'every', 'always', '每次'].includes(frequency)) {
        return 'every_time'
    }
    if (['session', 'once_session', '每会话', '每会话一次'].includes(frequency)) return 'session'
    if (
        ['first_visit', 'once', 'once_forever', 'forever', '仅一次', '首次访问'].includes(frequency)
    ) {
        return 'first_visit'
    }
    return 'daily'
}

const safeParseJson = (value: unknown) => {
    if (typeof value !== 'string') return value
    try {
        return JSON.parse(value)
    } catch (error) {
        return value
    }
}

const listFromUnknown = (value: unknown): Record<string, any>[] => {
    const parsed = safeParseJson(value)
    if (Array.isArray(parsed)) {
        return parsed.filter((item) => item && typeof item === 'object') as Record<string, any>[]
    }
    if (parsed && typeof parsed === 'object') {
        const record = parsed as Record<string, any>
        const keys = Object.keys(record)
        if (keys.length && keys.every((key) => /^\d+$/.test(key))) {
            return keys
                .sort((a, b) => Number(a) - Number(b))
                .map((key) => record[key])
                .filter((item) => item && typeof item === 'object') as Record<string, any>[]
        }
        return [record]
    }
    return []
}

const firstSplashWidget = (value: unknown): Record<string, any> => {
    const records = listFromUnknown(value)
    return records.find((item) => item.name === 'splash-ad') || records[0] || {}
}

const extractSplashSource = (pageData: Record<string, any>) => {
    const widget = firstSplashWidget(pageData.data)
    const content = firstSplashWidget(widget.content)
    const styles = firstSplashWidget(widget.styles)

    return {
        ...widget,
        ...content,
        ...styles
    }
}

export const normalizeSplashConfig = (
    pageData: Record<string, any> | null | undefined
): SplashAdConfig => {
    const source = extractSplashSource(pageData || {})
    const buttonText = String(source.button_text || source.buttonText || '').trim()

    return {
        enabled: normalizeBoolean(source.enabled ?? source.is_show ?? source.status, false),
        image: String(source.image || '').trim(),
        logoImage: String(source.logo_image ?? source.logoImage ?? source.logo ?? '').trim(),
        autoEnterEnabled: normalizeBoolean(
            source.auto_enter_enabled ?? source.autoEnterEnabled ?? source.auto_enter,
            true
        ),
        autoSeconds: normalizeSeconds(source.auto_seconds ?? source.autoSeconds ?? source.seconds),
        frequency: normalizeFrequency(
            source.frequency ?? source.show_frequency ?? source.showFrequency
        ),
        buttonText: buttonText || DEFAULT_SPLASH_CONFIG.buttonText
    }
}

export const fetchSplashConfig = async () => {
    const data = await getDecorate({ id: SPLASH_PAGE_ID })
    return normalizeSplashConfig(data || {})
}

export const fetchSplashConfigSafely = async (timeoutMs = 3000): Promise<SplashAdConfig> => {
    let timer: ReturnType<typeof setTimeout> | null = null
    const timeoutConfig = new Promise<SplashAdConfig>((resolve) => {
        timer = setTimeout(() => {
            resolve(getDefaultSplashConfig())
        }, timeoutMs)
    })

    try {
        return await Promise.race([fetchSplashConfig(), timeoutConfig])
    } catch (error) {
        console.error('开屏广告配置获取失败', error)
        return getDefaultSplashConfig()
    } finally {
        if (timer) {
            clearTimeout(timer)
        }
    }
}

const todayKey = () => {
    const date = new Date()
    const year = date.getFullYear()
    const month = `${date.getMonth() + 1}`.padStart(2, '0')
    const day = `${date.getDate()}`.padStart(2, '0')
    return `${year}-${month}-${day}`
}

export const armSplashHomeBypass = () => {
    splashHomeBypassArmed = true
}

export const consumeSplashHomeBypass = () => {
    if (!splashHomeBypassArmed) return false
    splashHomeBypassArmed = false
    return true
}

export const shouldBypassSplash = (query: Record<string, any> = {}) => {
    const bypassValue = query.skipSplash ?? query.skip_splash ?? query.noSplash ?? query.no_splash
    return normalizeBoolean(bypassValue, false)
}

export const shouldShowSplash = (config: SplashAdConfig, query: Record<string, any> = {}) => {
    if (shouldBypassSplash(query)) return false
    if (!config.enabled || !config.image) return false

    if (config.frequency === 'every_time') return true
    if (config.frequency === 'session') return !sessionShown

    const shown = cache.get(SPLASH_STORAGE_KEY) as {
        date?: string
        firstVisit?: boolean
    } | null

    if (config.frequency === 'first_visit') return !shown?.firstVisit
    return shown?.date !== todayKey()
}

export const markSplashShown = (frequency: SplashFrequency) => {
    if (frequency === 'session') {
        sessionShown = true
        return
    }

    if (frequency === 'first_visit') {
        cache.set(SPLASH_STORAGE_KEY, { firstVisit: true })
        return
    }

    if (frequency === 'daily') {
        cache.set(SPLASH_STORAGE_KEY, { date: todayKey() })
    }
}
