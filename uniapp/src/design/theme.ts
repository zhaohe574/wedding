export type ThemePresetKey = 'obsidian-gold' | 'midnight-rose' | 'navy-silver'
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
        'bg-page': '#FCFBF9',
        'bg-card': '#FFFFFFE8',
        'bg-soft': '#FFF7F4',
        'bg-mask': 'rgba(30, 36, 50, 0.46)',
        primary: '#E85A4F',
        'primary-strong': '#D84D43',
        'primary-soft': '#FFF1EE',
        secondary: '#C99B73',
        'secondary-soft': '#F8EFE7',
        border: '#EFE6E1',
        'border-strong': '#F4C7BF',
        success: '#2F7D58',
        warning: '#C98524',
        danger: '#B44A3A',
        info: '#607086'
    },
    text: {
        primary: '#1E2432',
        secondary: '#7F7B78',
        tertiary: '#B4ACA8',
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
        input: '34rpx',
        control: '34rpx',
        action: '34rpx',
        chip: '37rpx',
        'card-soft': '37rpx',
        card: '45rpx',
        'action-bar': '45rpx',
        'card-glass': '49rpx',
        'card-lg': '52rpx',
        popup: '52rpx',
        shell: '67rpx',
        'tabbar-shell': '60rpx',
        'tabbar-item': '52rpx',
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
        soft: '0 14rpx 32rpx rgba(214, 185, 167, 0.16)',
        card: '0 18rpx 36rpx rgba(214, 185, 167, 0.20)',
        hero: '0 24rpx 56rpx rgba(177, 108, 95, 0.18)'
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
        tabbar: '177rpx',
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
    primaryColor: '#E85A4F',
    secondaryColor: '#C99B73',
    ctaColor: '#E85A4F',
    accentColor: '#C99B73',
    pageBg: '#FCFBF9',
    pageBgSoft: '#FFF7F4',
    surface: '#FCFBF9',
    surfaceElevated: '#FFFFFF',
    surfaceOverlay: 'rgba(255, 255, 255, 0.88)',
    textPrimary: '#1E2432',
    textSecondary: '#7F7B78',
    textInverse: '#FFFFFF',
    borderColor: '#EFE6E1',
    tabbarActiveColor: '#E85A4F',
    tabbarInactiveColor: '#9D918B',
    navBgColor: '#FCFBF9',
    navTextColor: 'black',
    buttonColor: 'white',
    maskColor: 'rgba(30, 36, 50, 0.46)',
    heroGradient: 'linear-gradient(180deg, #FFF5F1 0%, #FCFBF9 68%, #F7F1ED 100%)',
    navStyle: 'glass',
    cardStyle: 'glass',
    shadowLevel: 'medium',
    ...overrides
})

export const themePresets: Record<ThemePresetKey, ThemePreset> = {
    'obsidian-gold': createPreset('obsidian-gold', '暖白珊瑚', 8),
    'midnight-rose': createPreset('midnight-rose', '柔粉珊瑚', 6, {
        secondaryColor: '#D7AFA1',
        accentColor: '#D7AFA1'
    }),
    'navy-silver': createPreset('navy-silver', '香槟珊瑚', 1, {
        secondaryColor: '#CBB7A4',
        accentColor: '#CBB7A4'
    })
}

const presetEntries = Object.values(themePresets)

export const createWmThemeTokens = (scene: WmScene = 'consumer'): WmThemeTokens => {
    const tokens: WmThemeTokens = JSON.parse(JSON.stringify(WM_BASE_TOKENS))

    if (scene === 'staff') {
        tokens.shadow.hero = '0 20rpx 44rpx rgba(192, 130, 115, 0.16)'
    }

    if (scene === 'admin') {
        tokens.shadow.card = '0 12rpx 28rpx rgba(214, 185, 167, 0.16)'
        tokens.space['6'] = '20rpx'
        tokens.space['8'] = '28rpx'
    }

    return tokens
}

export const getThemePreset = (presetKey?: string): ThemePreset => {
    if (presetKey && themePresets[presetKey as ThemePresetKey]) {
        return themePresets[presetKey as ThemePresetKey]
    }
    return themePresets['obsidian-gold']
}

const inferPresetKeyByLegacyId = (themeColorId?: number): ThemePresetKey => {
    const target = presetEntries.find((item) => item.legacyThemeId === Number(themeColorId))
    return target?.key ?? 'obsidian-gold'
}

const inferPresetKeyByColor = (color?: string): ThemePresetKey => {
    const value = String(color || '').toLowerCase()
    if (['#fd498f', '#fa444d', '#a54f72', '#c97d9f'].includes(value)) {
        return 'midnight-rose'
    }
    if (['#2f80ed', '#56ccf2', '#58759d', '#7e95b8'].includes(value)) {
        return 'navy-silver'
    }
    return 'obsidian-gold'
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
        buttonColor:
            source.buttonColor === 'black' || source.buttonColor === 'white'
                ? source.buttonColor
                : preset.buttonColor,
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
