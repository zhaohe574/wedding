import { LOGIN_HERO_DEFAULTS, type LoginHeroContent } from '@shared/contracts/decorate'

export type { LoginHeroContent }

export type LoginHeroStyles = Record<string, never>

export interface LoginHeroOptions {
    title: string
    name: string
    pageScope: string[]
    content: LoginHeroContent
    styles: LoginHeroStyles
}

export default (): LoginHeroOptions => ({
    title: '登录页顶部背景',
    name: 'login-hero',
    pageScope: ['login'],
    content: {
        ...LOGIN_HERO_DEFAULTS
    },
    styles: {}
})
