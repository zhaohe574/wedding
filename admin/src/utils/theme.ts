import colors from 'css-color-function'

const lightConfig = {
    'dark-2': 'shade(20%)',
    'light-3': 'tint(30%)',
    'light-5': 'tint(50%)',
    'light-7': 'tint(70%)',
    'light-8': 'tint(80%)',
    'light-9': 'tint(90%)'
}

const darkConfig = {
    'light-3': 'shade(20%)',
    'light-5': 'shade(30%)',
    'light-7': 'shade(50%)',
    'light-8': 'shade(60%)',
    'light-9': 'shade(70%)',
    'dark-2': 'tint(20%)'
}

const themeId = 'theme-vars'

const whiteHex = '#ffffff'

const normalizeHex = (value: string): string => {
    const color = value.trim().toLowerCase()
    if (!color.startsWith('#')) {
        return '#4a5dff'
    }
    if (color.length === 4) {
        return `#${color[1]}${color[1]}${color[2]}${color[2]}${color[3]}${color[3]}`
    }
    if (color.length === 7) {
        return color
    }
    return '#4a5dff'
}

const toRgb = (hex: string) => {
    const normalized = normalizeHex(hex).replace('#', '')
    return {
        r: parseInt(normalized.slice(0, 2), 16),
        g: parseInt(normalized.slice(2, 4), 16),
        b: parseInt(normalized.slice(4, 6), 16)
    }
}

const getLuminance = (hex: string): number => {
    const { r, g, b } = toRgb(hex)
    const channels = [r, g, b].map((channel) => {
        const v = channel / 255
        return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4
    })
    return channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722
}

const getContrastRatio = (foreground: string, background: string): number => {
    const l1 = getLuminance(foreground)
    const l2 = getLuminance(background)
    const [maxL, minL] = l1 >= l2 ? [l1, l2] : [l2, l1]
    return (maxL + 0.05) / (minL + 0.05)
}

const darkenColor = (hex: string, percent = 12): string => {
    const { r, g, b } = toRgb(hex)
    const factor = Math.max(0, 1 - percent / 100)
    const nr = Math.round(r * factor)
    const ng = Math.round(g * factor)
    const nb = Math.round(b * factor)
    return `#${nr.toString(16).padStart(2, '0')}${ng.toString(16).padStart(2, '0')}${nb.toString(16).padStart(2, '0')}`
}

const ensureAccessibleColor = (input: string, minimumRatio = 4.5): string => {
    let color = normalizeHex(input)
    let guard = 0
    while (getContrastRatio(color, whiteHex) < minimumRatio && guard < 8) {
        color = darkenColor(color, 10)
        guard++
    }
    return color
}

/**
 * @author Jason
 * @description 用于生成elementui主题的行为变量
 * 可选值有primary、success、warning、danger、error、info
 */

export const generateVars = (color: string, type = 'primary', isDark = false) => {
    const colos = {
        [`--el-color-${type}`]: color
    }
    const config: Record<string, string> = isDark ? darkConfig : lightConfig
    for (const key in config) {
        colos[`--el-color-${type}-${key}`] = `color(${color} ${config[key]})`
    }
    return colos
}

/**
 * @author Jason
 * @description 用于设置css变量
 * @param key css变量key 如 --color-primary
 * @param value css变量值 如 #f40
 * @param dom dom元素
 */
export const setCssVar = (key: string, value: string, dom = document.documentElement) => {
    dom.style.setProperty(key, value)
}

/**
 * @author Jason
 * @description 设置主题
 */
export const setTheme = (options: Record<string, string>, isDark = false) => {
    const varsMap: Record<string, string> = Object.keys(options).reduce((prev, key) => {
        return Object.assign(prev, generateVars(ensureAccessibleColor(options[key]), key, isDark))
    }, {})

    let theme = Object.keys(varsMap).reduce((prev, key) => {
        const color = colors.convert(varsMap[key])
        return `${prev}${key}:${color};`
    }, '')
    theme = `:root{${theme}}`
    let style = document.getElementById(themeId)
    if (style) {
        style.innerHTML = theme
        return
    }
    style = document.createElement('style')
    style.setAttribute('type', 'text/css')
    style.setAttribute('id', themeId)
    style.innerHTML = theme
    document.head.append(style)
}
