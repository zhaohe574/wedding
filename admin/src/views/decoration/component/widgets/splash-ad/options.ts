import {
    SPLASH_DEFAULTS,
    type SplashAdContent,
    type SplashFrequency,
    type SplashSkipMode
} from '@shared/contracts/decorate'

export type { SplashAdContent, SplashFrequency, SplashSkipMode }

export type SplashAdStyles = Record<string, never>

export interface SplashAdOptions {
    title: string
    name: string
    pageScope: string[]
    content: SplashAdContent
    styles: SplashAdStyles
}

/**
 * 开屏广告组件的默认值。
 *
 * ⚠ 直接复用 `shared/contracts/decorate.ts` 的 `SPLASH_DEFAULTS`，
 *   不在这里写第二份 —— 后台默认值、服务端归一化默认值、小程序兜底默认值必须是同一份。
 */
export default (): SplashAdOptions => ({
    title: '开屏广告',
    name: 'splash-ad',
    pageScope: ['splash'],
    content: { ...SPLASH_DEFAULTS },
    styles: {}
})
