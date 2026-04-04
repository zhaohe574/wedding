/**
 * 颜色工具：提供基础的混色与透明度处理
 * 说明：
 * - 只支持 #RRGGBB 格式输入，异常时回退到默认主色
 */
const fallbackColor = '#E85A4F'

const presetColorMap: Record<string, string> = {
    white: '#FFFFFF',
    black: '#000000'
}

const parseHex = (color: string): { r: number; g: number; b: number } => {
    const normalized = normalizeHexColor(color) || fallbackColor
    const value = normalized.replace('#', '')
    const num = parseInt(value, 16)
    return {
        r: (num >> 16) & 0xff,
        g: (num >> 8) & 0xff,
        b: num & 0xff
    }
}

export const normalizeHexColor = (color?: string): string => {
    if (!color) return ''

    const value = presetColorMap[color.trim().toLowerCase()] || color.trim()

    if (/^#?([a-fA-F0-9]{6})$/.test(value)) {
        return value.startsWith('#') ? value.toUpperCase() : `#${value.toUpperCase()}`
    }

    if (/^#?([a-fA-F0-9]{3})$/.test(value)) {
        const compact = value.replace('#', '')
        const [r, g, b] = compact
        return `#${r}${r}${g}${g}${b}${b}`.toUpperCase()
    }

    return ''
}

const getRelativeLuminance = (hexColor: string) => {
    const normalized = normalizeHexColor(hexColor)
    if (!normalized) return 0

    const channels = [0, 2, 4].map(
        (start) => parseInt(normalized.slice(start + 1, start + 3), 16) / 255
    )
    const [red, green, blue] = channels.map((channel) =>
        channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4
    )

    return 0.2126 * red + 0.7152 * green + 0.0722 * blue
}

export const getContrastRatio = (foregroundColor: string, backgroundColor: string) => {
    const foregroundLuminance = getRelativeLuminance(foregroundColor)
    const backgroundLuminance = getRelativeLuminance(backgroundColor)
    const lighter = Math.max(foregroundLuminance, backgroundLuminance)
    const darker = Math.min(foregroundLuminance, backgroundLuminance)

    return (lighter + 0.05) / (darker + 0.05)
}

export const resolveReadableTextColor = (
    backgroundColor: string,
    preferredColor: string,
    contrastThreshold = 4.5
) => {
    const normalizedBackgroundColor = normalizeHexColor(backgroundColor)
    const normalizedPreferredColor = normalizeHexColor(preferredColor)
    const fallbackLightTextColor = '#FFFFFF'
    const fallbackDarkTextColor = '#1E2432'

    if (!normalizedBackgroundColor) {
        return normalizedPreferredColor || fallbackLightTextColor
    }

    if (
        normalizedPreferredColor &&
        getContrastRatio(normalizedPreferredColor, normalizedBackgroundColor) >= contrastThreshold
    ) {
        return normalizedPreferredColor
    }

    const lightTextContrast = getContrastRatio(fallbackLightTextColor, normalizedBackgroundColor)
    const darkTextContrast = getContrastRatio(fallbackDarkTextColor, normalizedBackgroundColor)

    return lightTextContrast >= darkTextContrast ? fallbackLightTextColor : fallbackDarkTextColor
}

/**
 * 按比例将颜色与白色混合，生成浅色变体（tint）
 * @param color 基础色
 * @param percent 0~1 之间，数值越大越接近白色
 */
export const tintColor = (color: string, percent = 0.3): string => {
    const { r, g, b } = parseHex(color)
    const mix = (channel: number) => Math.round(channel + (255 - channel) * percent)
    const nr = mix(r)
    const ng = mix(g)
    const nb = mix(b)
    return `#${[nr, ng, nb].map((v) => v.toString(16).padStart(2, '0')).join('')}`
}

/**
 * 按比例将颜色与黑色混合，生成深色变体（shade）
 * @param color 基础色
 * @param percent 0~1 之间，数值越大越接近黑色
 */
export const shadeColor = (color: string, percent = 0.2): string => {
    const { r, g, b } = parseHex(color)
    const mix = (channel: number) => Math.round(channel * (1 - percent))
    const nr = mix(r)
    const ng = mix(g)
    const nb = mix(b)
    return `#${[nr, ng, nb].map((v) => v.toString(16).padStart(2, '0')).join('')}`
}

/**
 * 生成带透明度的 rgba 字符串
 * @param color 基础色
 * @param alpha 0~1 透明度
 */
export const alphaColor = (color: string, alpha = 0.2): string => {
    const { r, g, b } = parseHex(color)
    return `rgba(${r}, ${g}, ${b}, ${alpha})`
}
