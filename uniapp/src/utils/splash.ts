import { getDecorate } from '@/api/shop'
import cache from '@/utils/cache'

export const SPLASH_PAGE_ID = 6
export const SPLASH_HOME_PATH = '/pages/index/index'
export const SPLASH_STORAGE_KEY = 'splash_ad_shown'

export type SplashFrequency = 'every_launch' | 'once_session' | 'once_day' | 'once'

export interface SplashAdConfig {
    enabled: boolean
    image: string
    title: string
    subtitle: string
    buttonText: string
    duration: number
    frequency: SplashFrequency
    backgroundColor: string
    textColor: string
}

const DEFAULT_SPLASH_CONFIG: SplashAdConfig = {
    enabled: false,
    image: '',
    title: '',
    subtitle: '',
    buttonText: '进入首页',
    duration: 3,
    frequency: 'once_day',
    backgroundColor: '#000000',
    textColor: '#ffffff'
}

let sessionShown = false

const normalizeBoolean = (value: unknown, fallback = false) => {
    if (value === true || value === 1 || value === '1' || value === 'true') return true
    if (value === false || value === 0 || value === '0' || value === 'false') return false
    return fallback
}

const normalizeDuration = (value: unknown) => {
    const parsed = Number(value)
    if (!Number.isFinite(parsed)) return DEFAULT_SPLASH_CONFIG.duration
    return Math.min(Math.max(Math.round(parsed), 1), 10)
}

const normalizeFrequency = (value: unknown): SplashFrequency => {
    const frequency = String(value || '').trim()
    if (['every_launch', 'every', 'always', '每次'].includes(frequency)) return 'every_launch'
    if (['once_session', 'session', '每会话'].includes(frequency)) return 'once_session'
    if (['once', 'once_forever', 'forever', '仅一次'].includes(frequency)) return 'once'
    return 'once_day'
}

const safeParseJson = (value: unknown) => {
    if (typeof value !== 'string') return value
    try {
        return JSON.parse(value)
    } catch (error) {
        return value
    }
}

const firstRecord = (value: unknown): Record<string, any> => {
    const parsed = safeParseJson(value)
    if (Array.isArray(parsed)) {
        const splashWidget = parsed.find((item) => {
            const name = String(item?.name || '').toLowerCase()
            return name.includes('splash') || name.includes('open-screen') || name.includes('ad')
        })
        return (splashWidget || parsed[0] || {}) as Record<string, any>
    }
    if (parsed && typeof parsed === 'object') return parsed as Record<string, any>
    return {}
}

const extractContent = (pageData: Record<string, any>) => {
    const dataRecord = firstRecord(pageData.data)
    const metaRecord = firstRecord(pageData.meta)
    const content = firstRecord(dataRecord.content)
    const styles = firstRecord(dataRecord.styles)

    return {
        ...metaRecord,
        ...dataRecord,
        ...content,
        ...styles
    }
}

export const normalizeSplashConfig = (pageData: Record<string, any> | null | undefined): SplashAdConfig => {
    const source = extractContent(pageData || {})
    const image = String(source.image || source.cover || source.poster || source.bg_image || '').trim()

    return {
        enabled: normalizeBoolean(source.enabled ?? source.is_show ?? source.status, false),
        image,
        title: String(source.title || '').trim(),
        subtitle: String(source.subtitle || source.description || '').trim(),
        buttonText: String(source.button_text || source.buttonText || source.btn_text || '进入首页').trim(),
        duration: normalizeDuration(source.duration ?? source.countdown ?? source.seconds),
        frequency: normalizeFrequency(source.frequency ?? source.show_frequency ?? source.showFrequency),
        backgroundColor: String(source.background_color || source.backgroundColor || source.bg_color || '#000000'),
        textColor: String(source.text_color || source.textColor || '#ffffff')
    }
}

export const fetchSplashConfig = async () => {
    const data = await getDecorate({ id: SPLASH_PAGE_ID })
    return normalizeSplashConfig(data || {})
}

const todayKey = () => {
    const date = new Date()
    const year = date.getFullYear()
    const month = `${date.getMonth() + 1}`.padStart(2, '0')
    const day = `${date.getDate()}`.padStart(2, '0')
    return `${year}-${month}-${day}`
}

const getSessionShown = () => sessionShown

const setSessionShown = () => {
    sessionShown = true
}

export const shouldBypassSplash = (query: Record<string, any> = {}) => {
    const bypassValue = query.skipSplash ?? query.skip_splash ?? query.noSplash ?? query.no_splash
    return normalizeBoolean(bypassValue, false)
}

export const shouldShowSplash = (config: SplashAdConfig, query: Record<string, any> = {}) => {
    if (shouldBypassSplash(query)) return false
    if (!config.enabled || !config.image) return false

    if (config.frequency === 'every_launch') return true

    if (config.frequency === 'once_session') {
        return !getSessionShown()
    }

    const shown = cache.get(SPLASH_STORAGE_KEY) as { date?: string; forever?: boolean } | null
    if (config.frequency === 'once') {
        return !shown?.forever
    }

    return shown?.date !== todayKey()
}

export const markSplashShown = (frequency: SplashFrequency) => {
    if (frequency === 'once_session') {
        setSessionShown()
        return
    }

    if (frequency === 'once') {
        cache.set(SPLASH_STORAGE_KEY, { forever: true })
        return
    }

    if (frequency === 'once_day') {
        cache.set(SPLASH_STORAGE_KEY, { date: todayKey() })
    }
}
