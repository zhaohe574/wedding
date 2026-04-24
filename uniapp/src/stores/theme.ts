import { getDecorate } from '@/api/shop'
import {
    createWmThemeTokens,
    normalizeThemeConfig,
    type WmScene,
    type WmThemeTokens
} from '@/design/theme'
import { alphaColor, tintColor } from '@/utils/color'
import { generateVars } from '@/utils/theme'
import { defineStore } from 'pinia'

interface ThemeStore {
    scene: WmScene
    presetKey: string
    primaryColor: string
    secondaryColor: string
    ctaColor: string
    accentColor: string
    minorColor: string
    surfaceColor: string
    surfaceElevatedColor: string
    surfaceOverlayColor: string
    textPrimaryColor: string
    textSecondaryColor: string
    textTertiaryColor: string
    textInverseColor: string
    borderColor: string
    borderStrongColor: string
    pageBgColor: string
    pageBgSoftColor: string
    heroGradient: string
    maskColor: string
    tabbarActiveColor: string
    tabbarInactiveColor: string
    navStyle: 'solid' | 'glass'
    cardStyle: 'glass' | 'soft'
    shadowLevel: 'medium' | 'strong'
    btnColor: string
    navColor: string
    navBgColor: string
    tokens: WmThemeTokens
    vars: string
    pageStyle: string
}

const sceneHeroGradientMap: Record<WmScene, string> = {
    consumer: 'linear-gradient(180deg, #FFFFFF 0%, #FFFFFF 68%, #F7F0DF 100%)',
    staff: 'linear-gradient(135deg, #FFFFFF 0%, #F8F7F2 58%, #F7F0DF 100%)',
    admin: 'linear-gradient(180deg, #FFFFFF 0%, #F8F7F2 100%)'
}

const defaultTokens = createWmThemeTokens()

const createCssVarRecord = (prefix: string, source: Record<string, string>) => {
    return Object.keys(source).reduce(
        (record, key) => ({
            ...record,
            [`--wm-${prefix}-${key}`]: source[key]
        }),
        {} as Record<string, string>
    )
}

export const useThemeStore = defineStore({
    id: 'themeStore',
    state: (): ThemeStore => ({
        scene: 'consumer',
        presetKey: 'black-white-gold',
        primaryColor: '#0B0B0B',
        secondaryColor: '#C8A45D',
        ctaColor: '#0B0B0B',
        accentColor: '#C8A45D',
        minorColor: '#C8A45D',
        surfaceColor: '#FFFFFF',
        surfaceElevatedColor: '#FFFFFF',
        surfaceOverlayColor: 'rgba(255, 255, 255, 0.94)',
        textPrimaryColor: '#111111',
        textSecondaryColor: '#5F5A50',
        textTertiaryColor: '#9A9388',
        textInverseColor: '#FFFFFF',
        borderColor: '#E7E2D6',
        borderStrongColor: '#D8C28A',
        pageBgColor: '#FFFFFF',
        pageBgSoftColor: '#F8F7F2',
        heroGradient: sceneHeroGradientMap.consumer,
        maskColor: 'rgba(11, 11, 11, 0.54)',
        tabbarActiveColor: '#0B0B0B',
        tabbarInactiveColor: '#9A9388',
        navStyle: 'glass',
        cardStyle: 'glass',
        shadowLevel: 'medium',
        btnColor: 'white',
        navColor: '#111111',
        navBgColor: '#FFFFFF',
        tokens: defaultTokens,
        vars: '',
        pageStyle: ''
    }),
    actions: {
        setScene(scene: WmScene) {
            if (this.scene === scene) {
                return
            }
            this.scene = scene
            this.updateVars()
        },
        applyTheme(themeConfig: ReturnType<typeof normalizeThemeConfig>) {
            const { preset } = themeConfig
            this.presetKey = themeConfig.presetKey
            this.primaryColor = themeConfig.themeColor1
            this.secondaryColor = themeConfig.themeColor2
            this.ctaColor = preset.ctaColor
            this.accentColor = themeConfig.accentColor
            this.minorColor = themeConfig.accentColor
            this.surfaceColor = preset.pageBg
            this.surfaceElevatedColor = preset.surfaceElevated
            this.surfaceOverlayColor = preset.surfaceOverlay
            this.textPrimaryColor = preset.textPrimary
            this.textSecondaryColor = preset.textSecondary
            this.textTertiaryColor = defaultTokens.text.tertiary
            this.textInverseColor = preset.textInverse
            this.borderColor = preset.borderColor
            this.borderStrongColor = defaultTokens.colors['border-strong']
            this.pageBgColor = preset.pageBg
            this.pageBgSoftColor = preset.pageBgSoft
            this.heroGradient = sceneHeroGradientMap[this.scene]
            this.maskColor = preset.maskColor
            this.tabbarActiveColor = themeConfig.tabbarActiveColor
            this.tabbarInactiveColor = themeConfig.tabbarInactiveColor
            this.navStyle = themeConfig.navStyle
            this.cardStyle = themeConfig.cardStyle
            this.shadowLevel = themeConfig.shadowLevel
            this.btnColor = themeConfig.buttonColor
            this.navColor = themeConfig.topTextColor === 'white' ? '#FFFFFF' : '#111111'
            this.navBgColor = themeConfig.navigationBarColor
            this.updateVars()
        },
        async getTheme() {
            const data = await getDecorate({
                id: 5
            })

            let themeData = data.data
            if (typeof themeData === 'string') {
                try {
                    themeData = JSON.parse(themeData)
                } catch (error) {
                    console.error('解析主题数据失败：', error)
                    return
                }
            }

            this.applyTheme(normalizeThemeConfig(themeData))
        },
        setTheme(color: string) {
            this.primaryColor = color
            this.updateVars()
        },
        setSecondaryColor(color: string) {
            this.secondaryColor = color
            this.updateVars()
        },
        setCtaColor(color: string) {
            this.ctaColor = color
            this.updateVars()
        },
        setAccentColor(color: string) {
            this.accentColor = color
            this.minorColor = color
            this.updateVars()
        },
        updateVars() {
            const tokens = createWmThemeTokens(this.scene)
            this.tokens = tokens
            this.heroGradient = sceneHeroGradientMap[this.scene]

            const wmVars = {
                ...createCssVarRecord('color', {
                    ...tokens.colors,
                    'text-primary': this.textPrimaryColor,
                    'text-secondary': this.textSecondaryColor,
                    'text-tertiary': this.textTertiaryColor,
                    'text-inverse': this.textInverseColor
                }),
                ...createCssVarRecord('text', {
                    primary: this.textPrimaryColor,
                    secondary: this.textSecondaryColor,
                    tertiary: this.textTertiaryColor,
                    inverse: this.textInverseColor
                }),
                ...createCssVarRecord('font', tokens.font),
                ...createCssVarRecord('radius', tokens.radius),
                ...createCssVarRecord('space', tokens.space),
                ...createCssVarRecord('shadow', tokens.shadow),
                ...createCssVarRecord('motion', tokens.motion),
                ...createCssVarRecord('z', tokens.zIndex),
                ...createCssVarRecord('safe', tokens.safe),
                '--wm-scene': this.scene,
                '--wm-nav-bg': this.navBgColor,
                '--wm-nav-text': this.navColor,
                '--wm-hero-gradient': this.heroGradient,
                '--wm-mask-color': this.maskColor,
                '--wm-tabbar-active': this.tabbarActiveColor,
                '--wm-tabbar-inactive': this.tabbarInactiveColor,
                '--wm-safe-bottom-tabbar': `calc(${tokens.safe.tabbar} + env(safe-area-inset-bottom))`,
                '--wm-safe-bottom-action': `calc(${tokens.safe.action} + env(safe-area-inset-bottom))`
            }

            this.vars = generateVars(
                {
                    primary: this.primaryColor,
                    secondary: this.secondaryColor,
                    cta: this.ctaColor,
                    accent: this.accentColor,
                    success: tokens.colors.success,
                    warning: tokens.colors.warning,
                    error: tokens.colors.danger,
                    info: tokens.colors.info
                },
                {
                    ...wmVars,
                    '--color-minor': this.minorColor,
                    '--color-btn-text': this.btnColor,
                    '--color-main': this.textPrimaryColor,
                    '--color-content': this.textSecondaryColor,
                    '--color-muted': this.textTertiaryColor,
                    '--color-light': this.borderColor,
                    '--color-page': this.surfaceColor,
                    '--cinema-primary': this.primaryColor,
                    '--cinema-secondary': this.secondaryColor,
                    '--cinema-accent': this.accentColor,
                    '--cinema-page-bg': this.pageBgColor,
                    '--cinema-page-bg-soft': this.pageBgSoftColor,
                    '--cinema-surface': this.surfaceColor,
                    '--cinema-surface-elevated': this.surfaceElevatedColor,
                    '--cinema-surface-overlay': this.surfaceOverlayColor,
                    '--cinema-surface-muted': this.pageBgSoftColor,
                    '--cinema-text-primary': this.textPrimaryColor,
                    '--cinema-text-secondary': this.textSecondaryColor,
                    '--cinema-text-tertiary': this.textTertiaryColor,
                    '--cinema-text-inverse': this.textInverseColor,
                    '--cinema-border': this.borderColor,
                    '--cinema-border-strong': this.borderStrongColor,
                    '--cinema-primary-soft': alphaColor(this.primaryColor, 0.1),
                    '--cinema-secondary-soft': alphaColor(this.secondaryColor, 0.12),
                    '--cinema-primary-border': alphaColor(this.primaryColor, 0.24),
                    '--cinema-secondary-border': alphaColor(this.secondaryColor, 0.22),
                    '--cinema-primary-ring': alphaColor(this.primaryColor, 0.14),
                    '--cinema-tabbar-active': this.tabbarActiveColor,
                    '--cinema-tabbar-inactive': this.tabbarInactiveColor,
                    '--cinema-nav-bg': this.navBgColor,
                    '--cinema-nav-glass': alphaColor(this.surfaceElevatedColor, 0.94),
                    '--cinema-nav-text': this.navColor,
                    '--cinema-mask': this.maskColor,
                    '--cinema-hero-gradient': this.heroGradient,
                    '--cinema-skeleton-start': tintColor(this.primaryColor, 0.9),
                    '--cinema-skeleton-middle': tintColor(this.primaryColor, 0.78),
                    '--cinema-card-style': this.cardStyle,
                    '--cinema-shadow-soft': tokens.shadow.soft,
                    '--cinema-shadow-medium': tokens.shadow.card,
                    '--cinema-shadow-strong': tokens.shadow.hero,
                    '--cinema-radius-sm': tokens.radius.input,
                    '--cinema-radius-md': tokens.radius.card,
                    '--cinema-radius-lg': tokens.radius['card-lg'],
                    '--cinema-space-xs': tokens.space['3'],
                    '--cinema-space-sm': tokens.space['4'],
                    '--cinema-space-md': tokens.space['6'],
                    '--cinema-space-lg': tokens.space['8'],
                    '--cinema-space-xl': tokens.space['10'],
                    '--cinema-motion-fast': tokens.motion.fast,
                    '--cinema-motion-base': tokens.motion.base,
                    '--cinema-motion-slow': tokens.motion.slow
                }
            )

            this.pageStyle = this.vars
        }
    }
})
