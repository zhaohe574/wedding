import colors from 'css-color-function'

/**
 * 验证颜色是否为有效的十六进制颜色
 * @param color 颜色值
 * @returns 是否有效
 */
export const isValidHexColor = (color: string): boolean => {
    return /^#([0-9A-F]{3}){1,2}$/i.test(color)
}

/**
 * 计算两个颜色之间的对比度
 * @param color1 颜色1
 * @param color2 颜色2
 * @returns 对比度值
 */
export const calculateContrast = (color1: string, color2: string): number => {
    // 简化的对比度计算，实际应用中应使用更精确的算法
    // 这里返回一个示例值，实际实现需要完整的WCAG对比度算法
    const getLuminance = (hex: string): number => {
        const rgb = parseInt(hex.slice(1), 16)
        const r = (rgb >> 16) & 0xff
        const g = (rgb >> 8) & 0xff
        const b = (rgb >> 0) & 0xff
        return (0.299 * r + 0.587 * g + 0.114 * b) / 255
    }
    
    const lum1 = getLuminance(color1)
    const lum2 = getLuminance(color2)
    const lighter = Math.max(lum1, lum2)
    const darker = Math.min(lum1, lum2)
    return (lighter + 0.05) / (darker + 0.05)
}

const lightConfig = {
    'dark-2': 'shade(20%)',
    'light-3': 'tint(30%)',
    'light-5': 'tint(50%)',
    'light-7': 'tint(70%)',
    'light-9': 'tint(90%)'
}

const darkConfig = {
    'light-3': 'shade(20%)',
    'light-5': 'shade(30%)',
    'light-7': 'shade(50%)',
    'light-9': 'shade(70%)',
    'dark-2': 'tint(20%)'
}

/**
 * @description 用于生成主题的行为变量
 * 可选值有primary、secondary、cta、accent、success、warning、error、info
 */
export const generateVarsMap = (color: string, type = 'primary', isDark = false) => {
    // 验证颜色格式
    if (!isValidHexColor(color)) {
        console.warn(`无效的颜色值: ${color}，使用默认值`)
        color = '#7C3AED'
    }
    
    const colors = {
        [`--color-${type}`]: color
    }
    const config: Record<string, string> = isDark ? darkConfig : lightConfig
    for (const key in config) {
        colors[`--color-${type}-${key}`] = `color(${color} ${config[key]})`
    }
    return colors
}

/**
 * @description 生成主题CSS变量字符串
 * @param options 颜色配置对象，key为颜色类型，value为颜色值
 * @param extra 额外的CSS变量
 * @param isDark 是否为暗色模式
 * @returns CSS变量字符串
 */
export const generateVars = (
    options: Record<string, string>,
    extra: Record<string, string> = {},
    isDark = false
) => {
    const varsMap: Record<string, string> = Object.keys(options).reduce((prev, key) => {
        return Object.assign(prev, generateVarsMap(options[key], key, isDark))
    }, extra)

    const vars = Object.keys(varsMap).reduce((prev, key) => {
        const color = colors.convert(varsMap[key])
        return `${prev}${key}:${color};`
    }, '')
    return vars
}
