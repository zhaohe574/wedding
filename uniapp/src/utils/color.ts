/**
 * 颜色工具：提供基础的混色与透明度处理
 * 说明：
 * - 只支持 #RRGGBB 格式输入，异常时回退到默认主色
 */
const fallbackColor = '#7C3AED'

const parseHex = (color: string): { r: number; g: number; b: number } => {
    const hex = /^#?([a-fA-F0-9]{6})$/.exec(color)
    const value = hex ? hex[1] : fallbackColor.replace('#', '')
    const num = parseInt(value, 16)
    return {
        r: (num >> 16) & 0xff,
        g: (num >> 8) & 0xff,
        b: num & 0xff
    }
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
