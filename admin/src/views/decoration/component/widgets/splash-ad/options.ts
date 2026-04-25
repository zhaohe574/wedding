export type SplashAdFrequency = 'session' | 'daily' | 'every_time' | 'first_visit'

export interface SplashAdContent {
    enabled: 0 | 1
    image: string
    auto_enter_enabled: 0 | 1
    auto_seconds: number
    frequency: SplashAdFrequency
    button_text: string
}

export interface SplashAdStyles {
    button_bg_color: string
    button_text_color: string
    button_border_color: string
    button_border_radius: number
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
        auto_enter_enabled: 1,
        auto_seconds: 3,
        frequency: 'session',
        button_text: '点击进入'
    },
    styles: {
        button_bg_color: '#FFFFFF',
        button_text_color: '#333333',
        button_border_color: '#FFFFFF',
        button_border_radius: 24
    }
})
