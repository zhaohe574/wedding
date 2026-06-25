import type { CSSProperties } from 'vue'

export const posterAlignShortcutActions = [
    { key: 'left', label: '左对齐', icon: 'el-icon-Back' },
    { key: 'horizontalCenter', label: '一键水平居中', icon: 'el-icon-Aim' },
    { key: 'right', label: '右对齐', icon: 'el-icon-Right' },
    { key: 'top', label: '顶部对齐', icon: 'el-icon-Top' },
    { key: 'verticalCenter', label: '一键垂直居中', icon: 'el-icon-FullScreen' },
    { key: 'bottom', label: '底部对齐', icon: 'el-icon-Bottom' },
] as const

export const posterTextAlignActions = [
    { key: 'left', label: '文字左对齐', icon: 'el-icon-Back' },
    { key: 'center', label: '文字居中对齐', icon: 'el-icon-Aim' },
    { key: 'right', label: '文字右对齐', icon: 'el-icon-Right' },
] as const

export type PosterLayerAlignAction = typeof posterAlignShortcutActions[number]['key']
export type PosterTextAlignAction = typeof posterTextAlignActions[number]['key']

export const posterTextArtPresets = [
    {
        label: '默认',
        value: 'default',
        config: {
            fillType: 'solid',
            textStrokeWidth: 0,
            shadowBlur: 0,
            scaleX: 1,
            scaleY: 1,
            letterSpacing: 0,
        },
    },
    {
        label: '金色描边',
        value: 'gold_stroke',
        config: {
            fillType: 'linear',
            gradientFrom: '#FFF4B8',
            gradientTo: '#C78A22',
            gradientAngle: 90,
            textStrokeColor: '#7A4300',
            textStrokeWidth: 2,
            textStrokeOpacity: 0.85,
            shadowColor: '#3B2508',
            shadowBlur: 8,
            shadowOffsetX: 0,
            shadowOffsetY: 4,
            shadowOpacity: 0.28,
            scaleX: 1,
            scaleY: 1.12,
            letterSpacing: 1,
        },
    },
    {
        label: '白字投影',
        value: 'white_shadow',
        config: {
            fillType: 'solid',
            color: '#FFFFFF',
            textStrokeColor: '#000000',
            textStrokeWidth: 1,
            textStrokeOpacity: 0.24,
            shadowColor: '#000000',
            shadowBlur: 10,
            shadowOffsetX: 0,
            shadowOffsetY: 5,
            shadowOpacity: 0.36,
            scaleX: 1,
            scaleY: 1.06,
            letterSpacing: 0,
        },
    },
    {
        label: '橙色渐变',
        value: 'orange_gradient',
        config: {
            fillType: 'linear',
            gradientFrom: '#FFE0A3',
            gradientTo: '#C94F00',
            gradientAngle: 90,
            textStrokeColor: '#7C2D00',
            textStrokeWidth: 1,
            textStrokeOpacity: 0.42,
            shadowColor: '#7C2D00',
            shadowBlur: 6,
            shadowOffsetX: 0,
            shadowOffsetY: 3,
            shadowOpacity: 0.22,
            scaleX: 1,
            scaleY: 1.08,
            letterSpacing: 0,
        },
    },
    {
        label: '图片填充标题',
        value: 'image_fill_title',
        config: {
            fillType: 'image',
            fillImageFit: 'cover',
            textStrokeColor: '#FFFFFF',
            textStrokeWidth: 1,
            textStrokeOpacity: 0.48,
            shadowColor: '#000000',
            shadowBlur: 8,
            shadowOffsetX: 0,
            shadowOffsetY: 4,
            shadowOpacity: 0.26,
            scaleX: 1,
            scaleY: 1.12,
            letterSpacing: 1,
        },
    },
] as const

export function buildPosterTextPreviewStyle(layer: any, scale: number, resolveImageUrl: (value: any) => string): CSSProperties {
    const fillType = layer.fillType || 'solid'
    const style: CSSProperties = {
        color: layer.color || '#FFFFFF',
        fontSize: `${Number(layer.fontSize || 42) * scale}px`,
        fontWeight: layer.fontWeight || '400',
        lineHeight: Number(layer.lineHeight || 1.2),
        textAlign: layer.align || 'center',
        letterSpacing: `${Number(layer.letterSpacing || 0) * scale}px`,
        transform: `scale(${Number(layer.scaleX || 1)}, ${Number(layer.scaleY || 1)})`,
        transformOrigin: layer.align === 'left' ? 'left top' : layer.align === 'right' ? 'right top' : 'center top',
        textShadow: buildPosterTextShadow(layer, scale),
        WebkitTextStroke: Number(layer.textStrokeWidth || 0) > 0
            ? `${Number(layer.textStrokeWidth || 0) * scale}px ${rgbaWithOpacity(layer.textStrokeColor || '#000000', Number(layer.textStrokeOpacity ?? 1))}`
            : undefined,
    }
    if (fillType === 'linear') {
        style.color = 'transparent'
        style.backgroundImage = `linear-gradient(${Number(layer.gradientAngle ?? 90)}deg, ${layer.gradientFrom || layer.color || '#FFFFFF'}, ${layer.gradientTo || layer.color || '#F8CB79'})`
        style.WebkitBackgroundClip = 'text'
        ;(style as any).backgroundClip = 'text'
    } else if (fillType === 'image') {
        const imageUrl = resolveImageUrl(layer.fillImageUrl || layer.fillImage)
        if (imageUrl) {
            const fit = layer.fillImageFit === 'contain' ? 'contain' : layer.fillImageFit === 'stretch' ? '100% 100%' : 'cover'
            style.color = 'transparent'
            style.backgroundImage = `url("${imageUrl}")`
            style.backgroundRepeat = 'no-repeat'
            style.backgroundPosition = 'center'
            style.backgroundSize = fit
            style.WebkitBackgroundClip = 'text'
            ;(style as any).backgroundClip = 'text'
        }
    }
    return style
}

export function buildPosterTextShadow(layer: any, scale: number) {
    if (Number(layer.shadowBlur || 0) <= 0 && Number(layer.shadowOffsetX || 0) === 0 && Number(layer.shadowOffsetY || 0) === 0) {
        return undefined
    }
    return `${Number(layer.shadowOffsetX || 0) * scale}px ${Number(layer.shadowOffsetY || 0) * scale}px ${Number(layer.shadowBlur || 0) * scale}px ${rgbaWithOpacity(layer.shadowColor || '#000000', Number(layer.shadowOpacity ?? 0.35))}`
}

export function rgbaWithOpacity(color: string, opacity: number) {
    const normalizedOpacity = Math.max(0, Math.min(1, Number(opacity)))
    const hex = String(color || '').trim()
    if (/^#[0-9a-fA-F]{3}$/.test(hex)) {
        const r = parseInt(hex[1] + hex[1], 16)
        const g = parseInt(hex[2] + hex[2], 16)
        const b = parseInt(hex[3] + hex[3], 16)
        return `rgba(${r}, ${g}, ${b}, ${normalizedOpacity})`
    }
    if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
        const r = parseInt(hex.slice(1, 3), 16)
        const g = parseInt(hex.slice(3, 5), 16)
        const b = parseInt(hex.slice(5, 7), 16)
        return `rgba(${r}, ${g}, ${b}, ${normalizedOpacity})`
    }
    return hex || `rgba(0, 0, 0, ${normalizedOpacity})`
}
