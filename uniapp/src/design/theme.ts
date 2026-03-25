export type ThemePresetKey = 'obsidian-gold' | 'midnight-rose' | 'navy-silver'

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

export const themePresets: Record<ThemePresetKey, ThemePreset> = {
    'obsidian-gold': {
        key: 'obsidian-gold',
        name: '曜金夜幕',
        legacyThemeId: 8,
        primaryColor: '#C6A86A',
        secondaryColor: '#8D6B3B',
        ctaColor: '#D97706',
        accentColor: '#E8C98E',
        pageBg: '#0B0D12',
        pageBgSoft: '#141922',
        surface: '#F6F2EA',
        surfaceElevated: '#FFFDF8',
        surfaceOverlay: 'rgba(255, 248, 236, 0.86)',
        textPrimary: '#151A23',
        textSecondary: '#5D6472',
        textInverse: '#FFF8EA',
        borderColor: 'rgba(198, 168, 106, 0.24)',
        tabbarActiveColor: '#C6A86A',
        tabbarInactiveColor: '#8D93A1',
        navBgColor: '#0F141D',
        navTextColor: 'white',
        buttonColor: 'white',
        maskColor: 'rgba(8, 10, 16, 0.58)',
        heroGradient:
            'linear-gradient(145deg, rgba(10,13,18,0.98) 0%, rgba(25,32,45,0.96) 52%, rgba(76,58,29,0.94) 100%)',
        navStyle: 'glass',
        cardStyle: 'glass',
        shadowLevel: 'strong'
    },
    'midnight-rose': {
        key: 'midnight-rose',
        name: '午夜玫瑰',
        legacyThemeId: 6,
        primaryColor: '#A54F72',
        secondaryColor: '#E0A3BC',
        ctaColor: '#C7744B',
        accentColor: '#F3D5C0',
        pageBg: '#120F16',
        pageBgSoft: '#1B1521',
        surface: '#FCF6F7',
        surfaceElevated: '#FFFDFD',
        surfaceOverlay: 'rgba(255, 246, 247, 0.88)',
        textPrimary: '#1D1620',
        textSecondary: '#6B5B66',
        textInverse: '#FFF5F7',
        borderColor: 'rgba(165, 79, 114, 0.2)',
        tabbarActiveColor: '#C97D9F',
        tabbarInactiveColor: '#94838F',
        navBgColor: '#211924',
        navTextColor: 'white',
        buttonColor: 'white',
        maskColor: 'rgba(16, 11, 19, 0.58)',
        heroGradient:
            'linear-gradient(145deg, rgba(19,15,23,0.98) 0%, rgba(53,31,44,0.96) 48%, rgba(123,65,90,0.92) 100%)',
        navStyle: 'glass',
        cardStyle: 'glass',
        shadowLevel: 'strong'
    },
    'navy-silver': {
        key: 'navy-silver',
        name: '深蓝银辉',
        legacyThemeId: 1,
        primaryColor: '#58759D',
        secondaryColor: '#91A8C9',
        ctaColor: '#3A6EA5',
        accentColor: '#D5DCE6',
        pageBg: '#0D1521',
        pageBgSoft: '#162235',
        surface: '#F4F7FB',
        surfaceElevated: '#FFFFFF',
        surfaceOverlay: 'rgba(244, 247, 251, 0.88)',
        textPrimary: '#152033',
        textSecondary: '#5C6B81',
        textInverse: '#F8FBFF',
        borderColor: 'rgba(88, 117, 157, 0.18)',
        tabbarActiveColor: '#7E95B8',
        tabbarInactiveColor: '#8994A6',
        navBgColor: '#162235',
        navTextColor: 'white',
        buttonColor: 'white',
        maskColor: 'rgba(10, 16, 25, 0.54)',
        heroGradient:
            'linear-gradient(145deg, rgba(13,21,33,0.98) 0%, rgba(23,34,53,0.96) 48%, rgba(77,94,121,0.92) 100%)',
        navStyle: 'solid',
        cardStyle: 'soft',
        shadowLevel: 'medium'
    }
}

const presetEntries = Object.values(themePresets)

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
    if (['#fd498f', '#fa444d', '#a54f72'].includes(value)) {
        return 'midnight-rose'
    }
    if (['#2f80ed', '#56ccf2', '#58759d'].includes(value)) {
        return 'navy-silver'
    }
    return 'obsidian-gold'
}

export const normalizeThemeConfig = (rawData: any): NormalizedThemeConfig => {
    const source =
        rawData && typeof rawData === 'object' && !Array.isArray(rawData) ? rawData : {}
    const presetKey =
        typeof source.presetKey === 'string'
            ? (source.presetKey as ThemePresetKey)
            : source.themeColorId
              ? inferPresetKeyByLegacyId(source.themeColorId)
              : inferPresetKeyByColor(source.themeColor1)
    const preset = getThemePreset(presetKey)
    const topTextColor =
        source.topTextColor === 'white' || source.topTextColor === 'black'
            ? source.topTextColor
            : preset.navTextColor

    return {
        presetKey: preset.key,
        themeColorId: Number(source.themeColorId || preset.legacyThemeId),
        themeColor1: source.themeColor1 || preset.primaryColor,
        themeColor2: source.themeColor2 || preset.secondaryColor,
        buttonColor:
            source.buttonColor === 'black' || source.buttonColor === 'white'
                ? source.buttonColor
                : preset.buttonColor,
        navigationBarColor: source.navigationBarColor || preset.navBgColor,
        topTextColor,
        accentColor: source.accentColor || preset.accentColor,
        tabbarActiveColor: source.tabbarActiveColor || preset.tabbarActiveColor,
        tabbarInactiveColor: source.tabbarInactiveColor || preset.tabbarInactiveColor,
        surfaceMode: source.surfaceMode === 'soft' ? 'soft' : 'cinema',
        navStyle: source.navStyle === 'solid' ? 'solid' : preset.navStyle,
        cardStyle: source.cardStyle === 'soft' ? 'soft' : preset.cardStyle,
        shadowLevel: source.shadowLevel === 'medium' ? 'medium' : preset.shadowLevel,
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
