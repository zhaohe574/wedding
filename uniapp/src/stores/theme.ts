import { getDecorate } from '@/api/shop'
import { normalizeThemeConfig } from '@/design/theme'
import { alphaColor, tintColor } from '@/utils/color'
import { generateVars } from '@/utils/theme'
import { defineStore } from 'pinia'

interface ThemeStore {
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
    textInverseColor: string
    borderColor: string
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
    vars: string
    pageStyle: string
}
export const useThemeStore = defineStore({
    id: 'themeStore',
    state: (): ThemeStore => ({
        presetKey: 'obsidian-gold',
        primaryColor: '#C6A86A',
        secondaryColor: '#8D6B3B',
        ctaColor: '#D97706',
        accentColor: '#E8C98E',
        minorColor: '#E8C98E',
        surfaceColor: '#F6F2EA',
        surfaceElevatedColor: '#FFFDF8',
        surfaceOverlayColor: 'rgba(255, 248, 236, 0.86)',
        textPrimaryColor: '#151A23',
        textSecondaryColor: '#5D6472',
        textInverseColor: '#FFF8EA',
        borderColor: 'rgba(198, 168, 106, 0.24)',
        pageBgColor: '#0B0D12',
        pageBgSoftColor: '#141922',
        heroGradient:
            'linear-gradient(145deg, rgba(10,13,18,0.98) 0%, rgba(25,32,45,0.96) 52%, rgba(76,58,29,0.94) 100%)',
        maskColor: 'rgba(8, 10, 16, 0.58)',
        tabbarActiveColor: '#C6A86A',
        tabbarInactiveColor: '#8D93A1',
        navStyle: 'glass',
        cardStyle: 'glass',
        shadowLevel: 'strong',
        btnColor: 'white',
        navColor: '#ffffff',
        navBgColor: '#0F141D',
        vars: '',
        pageStyle: ''
    }),
    actions: {
        applyTheme(themeConfig: ReturnType<typeof normalizeThemeConfig>) {
            const { preset } = themeConfig
            this.presetKey = themeConfig.presetKey
            this.primaryColor = themeConfig.themeColor1
            this.secondaryColor = themeConfig.themeColor2
            this.ctaColor = preset.ctaColor
            this.accentColor = themeConfig.accentColor
            this.minorColor = themeConfig.accentColor
            this.surfaceColor = preset.surface
            this.surfaceElevatedColor = preset.surfaceElevated
            this.surfaceOverlayColor = preset.surfaceOverlay
            this.textPrimaryColor = preset.textPrimary
            this.textSecondaryColor = preset.textSecondary
            this.textInverseColor = preset.textInverse
            this.borderColor = preset.borderColor
            this.pageBgColor = preset.pageBg
            this.pageBgSoftColor = preset.pageBgSoft
            this.heroGradient = preset.heroGradient
            this.maskColor = preset.maskColor
            this.tabbarActiveColor = themeConfig.tabbarActiveColor
            this.tabbarInactiveColor = themeConfig.tabbarInactiveColor
            this.navStyle = themeConfig.navStyle
            this.cardStyle = themeConfig.cardStyle
            this.shadowLevel = themeConfig.shadowLevel
            this.btnColor = themeConfig.buttonColor
            this.navColor = themeConfig.topTextColor === 'white' ? '#ffffff' : '#111827'
            this.navBgColor = themeConfig.navigationBarColor
            this.updateVars()
        },
        async getTheme() {
            const data = await getDecorate({
                id: 5
            })

            // 处理 data.data 字段，可能是字符串或对象
            let themeData = data.data
            if (typeof themeData === 'string') {
                try {
                    themeData = JSON.parse(themeData)
                } catch (e) {
                    console.error('解析主题数据失败:', e)
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
            this.updateVars()
        },
        updateVars() {
            this.vars = generateVars(
                {
                    primary: this.primaryColor,
                    secondary: this.secondaryColor,
                    cta: this.ctaColor,
                    accent: this.accentColor,
                    success: '#2F7D58',
                    warning: '#C98524',
                    error: '#B44A3A',
                    info: '#607086'
                },
                {
                    '--color-minor': this.minorColor,
                    '--color-btn-text': this.btnColor,
                    '--color-main': this.textPrimaryColor,
                    '--color-content': this.textSecondaryColor,
                    '--color-muted': this.textSecondaryColor,
                    '--color-light': this.borderColor,
                    '--color-page': this.surfaceColor,
                    '--cinema-page-bg': this.pageBgColor,
                    '--cinema-page-bg-soft': this.pageBgSoftColor,
                    '--cinema-surface': this.surfaceColor,
                    '--cinema-surface-elevated': this.surfaceElevatedColor,
                    '--cinema-surface-overlay': this.surfaceOverlayColor,
                    '--cinema-surface-muted': tintColor(this.primaryColor, 0.93),
                    '--cinema-text-primary': this.textPrimaryColor,
                    '--cinema-text-secondary': this.textSecondaryColor,
                    '--cinema-text-inverse': this.textInverseColor,
                    '--cinema-border': this.borderColor,
                    '--cinema-primary-soft': alphaColor(this.primaryColor, 0.1),
                    '--cinema-secondary-soft': alphaColor(this.secondaryColor, 0.1),
                    '--cinema-primary-border': alphaColor(this.primaryColor, 0.24),
                    '--cinema-secondary-border': alphaColor(this.secondaryColor, 0.22),
                    '--cinema-primary-ring': alphaColor(this.primaryColor, 0.12),
                    '--cinema-tabbar-active': this.tabbarActiveColor,
                    '--cinema-tabbar-inactive': this.tabbarInactiveColor,
                    '--cinema-nav-bg': this.navBgColor,
                    '--cinema-nav-glass': alphaColor(this.navBgColor, 0.88),
                    '--cinema-nav-text': this.navColor,
                    '--cinema-mask': this.maskColor,
                    '--cinema-hero-gradient': this.heroGradient,
                    '--cinema-skeleton-start': tintColor(this.primaryColor, 0.92),
                    '--cinema-skeleton-middle': tintColor(this.primaryColor, 0.8),
                    '--cinema-card-style': this.cardStyle,
                    '--cinema-shadow-soft': '0 18rpx 44rpx rgba(8, 10, 16, 0.08)',
                    '--cinema-shadow-medium': '0 20rpx 52rpx rgba(8, 10, 16, 0.12)',
                    '--cinema-shadow-strong': '0 24rpx 60rpx rgba(8, 10, 16, 0.18)',
                    '--cinema-radius-sm': '18rpx',
                    '--cinema-radius-md': '24rpx',
                    '--cinema-radius-lg': '32rpx',
                    '--cinema-space-xs': '12rpx',
                    '--cinema-space-sm': '16rpx',
                    '--cinema-space-md': '24rpx',
                    '--cinema-space-lg': '32rpx',
                    '--cinema-space-xl': '40rpx',
                    '--cinema-motion-fast': '150ms',
                    '--cinema-motion-base': '220ms',
                    '--cinema-motion-slow': '260ms'
                }
            )
            this.pageStyle = this.vars
        }
    }
})
