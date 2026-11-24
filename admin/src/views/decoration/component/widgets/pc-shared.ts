// PC 装修组件共享工具。
//
// 契约唯一来源：shared/contracts/pc-decorate.ts（3 个固定模块：pc-hero / pc-gallery / pc-miniprogram）。
// pcPreviewTheme 为 v8.1「高定暖墨黑」视觉令牌，仅供后台 PC 整页预览镜像（pc-preview.vue）消费；
// 手机样机内屏使用小程序同源浅色令牌（mnp* 前缀）。

export { PC_WIDGET_META, PC_WIDGET_ORDER } from '@shared/contracts/pc-decorate'
export type { PcWidgetName } from '@shared/contracts/pc-decorate'

/** 兼容数组 / PHP 数字键对象 / 其他可枚举值的宽松列表化 */
export const normalizeList = <T = any>(value: unknown): T[] => {
    if (Array.isArray(value)) return value as T[]
    if (value && typeof value === 'object') return Object.values(value as Record<string, T>)
    return []
}

/** v8.1 高定暖墨黑视觉令牌（pc-preview.vue 注入 --pv-* CSS 变量的唯一色值来源） */
export const pcPreviewTheme = {
    // 暖墨黑底
    bg: '#151210',
    bgDeep: '#110e0c',
    surface: '#1b1714',
    // 墨色文字
    ink: '#efe5d3',
    ink2: '#b9aea0',
    ink3: '#8b8073',
    // 香槟金
    gold: '#c9a96e',
    goldSoft: '#dfd0b0',
    line: 'rgba(201, 169, 110, 0.22)',
    lineStrong: 'rgba(201, 169, 110, 0.42)',
    // 手机样机内屏：小程序同源浅色令牌
    mnpBg: '#faf6ee',
    mnpSurface: '#fffdf8',
    mnpInk: '#181614',
    mnpBrand: '#c6a15b',
    mnpBrandText: '#8a6424',
    mnpLine: '#e7e0d3'
}
