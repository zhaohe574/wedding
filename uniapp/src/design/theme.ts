export type ThemePresetKey =
    | 'black-white-gold'
    | 'obsidian-gold'
    | 'midnight-rose'
    | 'navy-silver'
export type WmScene = 'consumer' | 'staff' | 'admin'

export interface WmThemeTokens {
    colors: Record<string, string>
    text: Record<string, string>
    font: Record<string, string>
    radius: Record<string, string>
    space: Record<string, string>
    shadow: Record<string, string>
    motion: Record<string, string>
    zIndex: Record<string, string>
    safe: Record<string, string>
}

export interface ThemePreset {
    key: ThemePresetKey
    name: string
    legacyThemeId: number
    primaryColor: string
    secondaryColor: string
    ctaColor: string
    accentColor: string
    pageBg: string
    pageBgSoft: string
    surface: string
    surfaceElevated: string
    surfaceOverlay: string
    textPrimary: string
    textSecondary: string
    textInverse: string
    borderColor: string
    tabbarActiveColor: string
    tabbarInactiveColor: string
    navBgColor: string
    navTextColor: 'white' | 'black'
    buttonColor: 'white' | 'black'
    maskColor: string
    heroGradient: string
    navStyle: 'solid' | 'glass'
    cardStyle: 'glass' | 'soft'
    shadowLevel: 'medium' | 'strong'
}

export interface NormalizedThemeConfig {
    presetKey: ThemePresetKey
    themeColorId: number
    themeColor1: string
    themeColor2: string
    buttonColor: 'white' | 'black'
    navigationBarColor: string
    topTextColor: 'white' | 'black'
    accentColor: string
    tabbarActiveColor: string
    tabbarInactiveColor: string
    surfaceMode: 'cinema' | 'soft'
    navStyle: 'solid' | 'glass'
    cardStyle: 'glass' | 'soft'
    shadowLevel: 'medium' | 'strong'
    preset: ThemePreset
}

const WM_BASE_TOKENS: WmThemeTokens = {
    colors: {
        'bg-page': '#FFFFFF',
        'bg-card': '#FFFFFF',
        'bg-soft': '#F7F7F7',
        'bg-mask': 'rgba(11, 11, 11, 0.54)',
        primary: '#0B0B0B',
        'primary-strong': '#000000',
        'primary-soft': '#F3F3F3',
        secondary: '#C8A45D',
        'secondary-soft': '#F8F3E7',
        rose: '#C8A45D',
        'rose-soft': '#F8F3E7',
        sage: '#4A4A4A',
        'sage-soft': '#F7F7F7',
        border: '#E5E5E5',
        'border-strong': '#C8A45D',
        success: '#4F6F5A',
        warning: '#9F7A2E',
        danger: '#8A4B45',
        info: '#596A7A'
    },
    text: {
        primary: '#111111',
        secondary: '#4A4A4A',
        tertiary: '#8A8A8A',
        inverse: '#FFFFFF'
    },
    font: {
        'family-display': 'SF Pro Display, PingFang SC, Microsoft YaHei, sans-serif',
        'family-body': 'PingFang SC, Hiragino Sans GB, Microsoft YaHei, sans-serif',
        hero: '44rpx',
        h1: '36rpx',
        h2: '32rpx',
        h3: '30rpx',
        body: '28rpx',
        meta: '24rpx',
        caption: '22rpx',
        mini: '20rpx'
    },
    radius: {
        input: '16rpx',
        control: '16rpx',
        action: '999rpx',
        chip: '999rpx',
        'card-soft': '14rpx',
        card: '16rpx',
        'action-bar': '20rpx',
        'card-glass': '18rpx',
        'card-lg': '20rpx',
        popup: '24rpx',
        shell: '24rpx',
        'tabbar-shell': '28rpx',
        'tabbar-item': '20rpx',
        pill: '999rpx'
    },
    space: {
        '2': '15rpx',
        '3': '22rpx',
        '4': '30rpx',
        '5': '37rpx',
        '6': '45rpx',
        '7': '52rpx',
        '8': '60rpx',
        '10': '75rpx',
        'page-x': '37rpx',
        'section-gap-sm': '22rpx',
        'section-gap-md': '26rpx',
        'section-gap-lg': '30rpx',
        'card-padding': '30rpx',
        'card-padding-lg': '34rpx',
        'action-top': '22rpx',
        'action-x': '37rpx',
        'action-bottom': '39rpx',
        'tabbar-top': '22rpx',
        'tabbar-x': '39rpx',
        'tabbar-bottom': '39rpx'
    },
    shadow: {
        soft: '0 8rpx 18rpx rgba(17, 17, 17, 0.04)',
        card: '0 10rpx 24rpx rgba(17, 17, 17, 0.06)',
        hero: '0 16rpx 36rpx rgba(17, 17, 17, 0.1)'
    },
    motion: {
        fast: '150ms',
        base: '220ms',
        slow: '260ms'
    },
    zIndex: {
        header: '40',
        tabbar: '80',
        action: '90',
        overlay: '200'
    },
    safe: {
        tabbar: '112rpx',
        action: '150rpx'
    }
}

const createPreset = (
    key: ThemePresetKey,
    name: string,
    legacyThemeId: number,
    overrides: Partial<ThemePreset> = {}
): ThemePreset => ({
    key,
    name,
    legacyThemeId,
    primaryColor: '#0B0B0B',
    secondaryColor: '#C8A45D',
    ctaColor: '#0B0B0B',
    accentColor: '#C8A45D',
    pageBg: '#FFFFFF',
    pageBgSoft: '#F7F7F7',
    surface: '#FFFFFF',
    surfaceElevated: '#FFFFFF',
    surfaceOverlay: 'rgba(255, 255, 255, 0.96)',
    textPrimary: '#111111',
    textSecondary: '#4A4A4A',
    textInverse: '#FFFFFF',
    borderColor: '#E5E5E5',
    tabbarActiveColor: '#0B0B0B',
    tabbarInactiveColor: '#9A9388',
    navBgColor: '#000000',
    navTextColor: 'white',
    buttonColor: 'white',
    maskColor: 'rgba(11, 11, 11, 0.54)',
    heroGradient: 'linear-gradient(180deg, #FFFFFF 0%, #FFFFFF 70%, #F8F3E7 100%)',
    navStyle: 'solid',
    cardStyle: 'soft',
    shadowLevel: 'medium',
    ...overrides
})

export const themePresets: Record<ThemePresetKey, ThemePreset> = {
    'black-white-gold': createPreset('black-white-gold', '黑白香槟金', 8),
    'obsidian-gold': createPreset('obsidian-gold', '曜石香槟金', 8, {
        pageBgSoft: '#F7F7F7',
        surfaceOverlay: 'rgba(255, 255, 255, 0.96)',
        shadowLevel: 'strong'
    }),
    'midnight-rose': createPreset('midnight-rose', '午夜玫瑰金', 6, {
        secondaryColor: '#C8A45D',
        accentColor: '#C8A45D',
        pageBgSoft: '#F7F7F7',
        borderColor: '#E5E5E5'
    }),
    'navy-silver': createPreset('navy-silver', '海军银灰', 1, {
        primaryColor: '#0B0B0B',
        secondaryColor: '#C8A45D',
        accentColor: '#C8A45D',
        pageBgSoft: '#F7F7F7',
        borderColor: '#E5E5E5',
        tabbarActiveColor: '#0B0B0B'
    })
}

const presetEntries = Object.values(themePresets)

export const createWmThemeTokens = (scene: WmScene = 'consumer'): WmThemeTokens => {
    const tokens: WmThemeTokens = JSON.parse(JSON.stringify(WM_BASE_TOKENS))

    if (scene === 'staff') {
        tokens.colors['bg-soft'] = '#F7F7F7'
        tokens.shadow.hero = '0 14rpx 34rpx rgba(17, 17, 17, 0.1)'
    }

    if (scene === 'admin') {
        tokens.colors['bg-soft'] = '#F7F7F7'
        tokens.shadow.card = '0 8rpx 20rpx rgba(17, 17, 17, 0.06)'
        tokens.space['6'] = '20rpx'
        tokens.space['8'] = '28rpx'
    }

    return tokens
}

export const getThemePreset = (presetKey?: string): ThemePreset => {
    if (presetKey && themePresets[presetKey as ThemePresetKey]) {
        return themePresets[presetKey as ThemePresetKey]
    }
    return themePresets['black-white-gold']
}

const inferPresetKeyByLegacyId = (themeColorId?: number): ThemePresetKey => {
    const target = presetEntries.find((item) => item.legacyThemeId === Number(themeColorId))
    return target?.key ?? 'black-white-gold'
}

const inferPresetKeyByColor = (color?: string): ThemePresetKey => {
    const value = String(color || '').toLowerCase()
    if (value) return 'black-white-gold'
    return 'black-white-gold'
}

export const normalizeThemeConfig = (rawData: any): NormalizedThemeConfig => {
    const source = rawData && typeof rawData === 'object' && !Array.isArray(rawData) ? rawData : {}
    const presetKey =
        typeof source.presetKey === 'string'
            ? (source.presetKey as ThemePresetKey)
            : source.themeColorId
            ? inferPresetKeyByLegacyId(source.themeColorId)
            : inferPresetKeyByColor(source.themeColor1)
    const preset = getThemePreset(presetKey)

    return {
        presetKey: preset.key,
        themeColorId: Number(source.themeColorId || preset.legacyThemeId),
        themeColor1: preset.primaryColor,
        themeColor2: preset.secondaryColor,
        buttonColor: preset.buttonColor,
        navigationBarColor: preset.navBgColor,
        topTextColor: preset.navTextColor,
        accentColor: preset.accentColor,
        tabbarActiveColor: preset.tabbarActiveColor,
        tabbarInactiveColor: preset.tabbarInactiveColor,
        surfaceMode: 'soft',
        navStyle: preset.navStyle,
        cardStyle: preset.cardStyle,
        shadowLevel: preset.shadowLevel,
        preset
    }
}

export const themePresetOptions = presetEntries.map((item) => ({
    label: item.name,
    value: item.key,
    color1: item.primaryColor,
    color2: item.secondaryColor,
    accentColor: item.accentColor,
    buttonColor: item.buttonColor,
    navBgColor: item.navBgColor
}))
