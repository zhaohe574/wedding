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
        'bg-page': '#F3EBE2',
        'bg-card': '#FFFDF8',
        'bg-soft': '#F8F1E7',
        'bg-subtle': '#EDE6DD',
        'bg-mask': 'rgba(26, 26, 26, 0.58)',
        primary: '#1A1A1A',
        'primary-strong': '#000000',
        'primary-soft': '#F6E2D6',
        secondary: '#D4916E',
        'secondary-strong': '#7D4C35',
        'secondary-soft': '#F6E2D6',
        champagne: '#E9C7A7',
        'champagne-soft': '#FFF7EC',
        gold: '#D4916E',
        'gold-soft': '#F6E2D6',
        clay: '#C97957',
        sage: '#71806F',
        'sage-soft': '#E8EFE6',
        mist: '#EDE6DD',
        border: '#E3D7C9',
        'border-strong': '#E9C7A7',
        success: '#71806F',
        'success-soft': '#E8EFE6',
        warning: '#D4916E',
        'warning-soft': '#F6E2D6',
        danger: '#C97957',
        'danger-soft': '#F2DDD5',
        info: '#8178B6',
        'info-soft': '#E8E6F0',
        price: '#1A1A1A',
        cta: '#1A1A1A'
    },
    text: {
        primary: '#1A1A1A',
        secondary: '#6B625A',
        tertiary: '#B4A89C',
        inverse: '#FFFDF8'
    },
    font: {
        'family-display': 'Playfair Display, Georgia, Times New Roman, serif',
        'family-body': 'Inter, PingFang SC, Hiragino Sans GB, Microsoft YaHei, sans-serif',
        hero: '60rpx',
        h1: '42rpx',
        h2: '36rpx',
        h3: '30rpx',
        body: '28rpx',
        meta: '24rpx',
        caption: '22rpx',
        mini: '20rpx'
    },
    radius: {
        input: '44rpx',
        control: '44rpx',
        action: '56rpx',
        chip: '40rpx',
        'card-soft': '32rpx',
        card: '44rpx',
        'action-bar': '44rpx',
        'card-glass': '48rpx',
        'card-lg': '60rpx',
        popup: '44rpx',
        shell: '48rpx',
        'tabbar-shell': '72rpx',
        'tabbar-item': '56rpx',
        pill: '999rpx'
    },
    space: {
        '2': '16rpx',
        '3': '24rpx',
        '4': '32rpx',
        '5': '40rpx',
        '6': '48rpx',
        '7': '56rpx',
        '8': '64rpx',
        '10': '80rpx',
        'page-x': '32rpx',
        'section-gap-sm': '20rpx',
        'section-gap-md': '28rpx',
        'section-gap-lg': '36rpx',
        'card-padding': '28rpx',
        'card-padding-lg': '36rpx',
        'action-top': '24rpx',
        'action-x': '24rpx',
        'action-bottom': '34rpx',
        'tabbar-top': '16rpx',
        'tabbar-x': '24rpx',
        'tabbar-bottom': '30rpx'
    },
    shadow: {
        soft: '0 16rpx 36rpx rgba(74, 43, 24, 0.07)',
        card: '0 20rpx 48rpx rgba(74, 43, 24, 0.10)',
        hero: '0 28rpx 68rpx rgba(74, 43, 24, 0.18)',
        action: '0 20rpx 44rpx rgba(74, 43, 24, 0.18)',
        floating: '0 24rpx 56rpx rgba(74, 43, 24, 0.16)'
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
        tabbar: '164rpx',
        action: '156rpx'
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
    primaryColor: '#1A1A1A',
    secondaryColor: '#D4916E',
    ctaColor: '#1A1A1A',
    accentColor: '#E9C7A7',
    pageBg: '#F3EBE2',
    pageBgSoft: '#F8F1E7',
    surface: '#FFFDF8',
    surfaceElevated: '#FFFDF8',
    surfaceOverlay: 'rgba(255, 253, 248, 0.96)',
    textPrimary: '#1A1A1A',
    textSecondary: '#6B625A',
    textInverse: '#FFFDF8',
    borderColor: '#E3D7C9',
    tabbarActiveColor: '#E9C7A7',
    tabbarInactiveColor: '#B4A89C',
    navBgColor: '#1A1A1A',
    navTextColor: 'white',
    buttonColor: 'white',
    maskColor: 'rgba(26, 26, 26, 0.58)',
    heroGradient: 'radial-gradient(circle at 12% 0%, rgba(212, 145, 110, 0.18) 0, transparent 34%), linear-gradient(180deg, #FFFDF8 0%, #F3EBE2 68%, #F8F1E7 100%)',
    navStyle: 'solid',
    cardStyle: 'soft',
    shadowLevel: 'medium',
    ...overrides
})

export const themePresets: Record<ThemePresetKey, ThemePreset> = {
    'black-white-gold': createPreset('black-white-gold', '黑金婚礼', 8),
    'obsidian-gold': createPreset('obsidian-gold', '曜石香槟', 8, {
        pageBgSoft: '#EDE6DD',
        surfaceOverlay: 'rgba(255, 253, 248, 0.96)',
        shadowLevel: 'strong'
    }),
    'midnight-rose': createPreset('midnight-rose', '陶土金', 6, {
        secondaryColor: '#C97957',
        accentColor: '#E9C7A7',
        pageBgSoft: '#F8F1E7',
        borderColor: '#E3D7C9'
    }),
    'navy-silver': createPreset('navy-silver', '雾灰黑金', 1, {
        primaryColor: '#1A1A1A',
        secondaryColor: '#D4916E',
        accentColor: '#E9C7A7',
        pageBgSoft: '#EDE6DD',
        borderColor: '#D9CCBD',
        tabbarActiveColor: '#E9C7A7'
    })
}

const presetEntries = Object.values(themePresets)

export const createWmThemeTokens = (scene: WmScene = 'consumer'): WmThemeTokens => {
    const tokens: WmThemeTokens = JSON.parse(JSON.stringify(WM_BASE_TOKENS))

    if (scene === 'staff') {
        tokens.colors['bg-page'] = '#F3EBE2'
        tokens.colors['bg-soft'] = '#F8F1E7'
        tokens.colors['primary-soft'] = '#F6E2D6'
        tokens.shadow.card = '0 20rpx 48rpx rgba(74, 43, 24, 0.10)'
        tokens.shadow.hero = '0 28rpx 68rpx rgba(74, 43, 24, 0.18)'
    }

    if (scene === 'admin') {
        tokens.colors['bg-page'] = '#F8F1E7'
        tokens.colors['bg-soft'] = '#EDE6DD'
        tokens.shadow.card = '0 18rpx 42rpx rgba(74, 43, 24, 0.09)'
        tokens.space['6'] = '40rpx'
        tokens.space['8'] = '56rpx'
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
