export type SplashAdFrequency = 'session' | 'daily' | 'every_time' | 'first_visit'

export interface SplashAdContent {
    enabled: 0 | 1
    image: string
    auto_enter: 0 | 1
    auto_seconds: number
    frequency: SplashAdFrequency
    button_text: string
}

export interface SplashAdStyles {
    button_bg_color: string
    button_text_color: string
    button_radius: number
    mask_color: string
}

export interface SplashAdOptions {
    title: string
    name: string
    pageScope: string[]
    content: SplashAdContent
    styles: SplashAdStyles
}

export default (): SplashAdOptions => ({
    title: '开屏广告',
    name: 'splash-ad',
    pageScope: ['splash'],
    content: {
        enabled: 0,
        image: '',
        auto_enter: 1,
        auto_seconds: 3,
        frequency: 'session',
        button_text: '点击进入'
    },
    styles: {
        button_bg_color: '#111111',
        button_text_color: '#FFFFFF',
        button_radius: 999,
        mask_color: 'rgba(0, 0, 0, 0.24)'
    }
})
