// 单量月报设计器：动态字段、默认结构、归一化与预设风格库
// 渲染契约对齐 server/app/common/service/MonthlyReportService.php（normalizeDesignConfig / applyRankingAutoHeight）
// 与 MonthlyReportRenderer.php（rect 不支持渐变、文本高度防截断、二维码强留规则）

import { posterTextArtPresets } from '../poster-designer-common'

import type {
    CanvasRatioPreset,
    ColorPalette,
    DesignConfig,
    DesignLayer,
    DynamicFieldOption,
    HeroArtPreset,
    MonthlyReportTemplateType,
    StylePreset,
} from './types'

/** 二维码最小尺寸（后端强留规则） */
export const QRCODE_MIN_SIZE = 160

/** 画布高度上限（与后端 clamp 一致） */
export const CANVAS_MAX_HEIGHT = 5200

/** 画布宽度上限（与后端 clamp 一致） */
export const CANVAS_MAX_WIDTH = 2160

/** 背景压暗遮罩图层 ID（全画布纯黑半透明 rect，z = -100） */
export const BG_SCRIM_LAYER_ID = 'bg-scrim'

/** ranking 人数上限未设置时用于估算底部基线的展示人数 */
export const RANKING_ESTIMATED_COUNT = 24

/** 页面级动态变量（月报快照 variables） */
export const MONTHLY_DYNAMIC_FIELDS: DynamicFieldOption[] = [
    { label: '年份', token: '{report_year}', desc: '报告年份，如 2026' },
    { label: '月份', token: '{report_month_label}', desc: '如 2026年5月' },
    { label: '新增档期', token: '{addition_count}', desc: '本月新增档期数' },
    { label: '共执行', token: '{executed_count}', desc: '本月累计执行场次' },
    { label: '单王场次', token: '{top_count}', desc: '单王本月执行场次' },
    { label: '单王姓名', token: '{top_staff_names}', desc: '多人以顿号分隔' },
    { label: '新增标题', token: '{addition_title}' },
    { label: '新增副标题', token: '{addition_subtitle}' },
    { label: '榜单标题', token: '{ranking_title}' },
    { label: '单王标题', token: '{top_title}' },
    { label: '页脚文案', token: '{footer_note}' },
]

/** 人员卡片级动态变量（repeater 卡片子图层可用） */
export const MONTHLY_CARD_FIELDS: DynamicFieldOption[] = [
    { label: '头像素材', token: '{avatar_photo_url}' },
    { label: '半身素材', token: '{half_body_photo_url}' },
    { label: '照片（兼容）', token: '{photo_url}' },
    { label: '人员姓名', token: '{chinese_name}' },
    { label: '英文名/拼音', token: '{english_name}' },
    { label: '场次', token: '{count}' },
    { label: '排名', token: '{rank}' },
]

export function normalizeUrl(value: any): string {
    if (Array.isArray(value)) {
        return normalizeUrl(value[0])
    }
    if (typeof value === 'object' && value) {
        return normalizeUrl(value.url || value.uri || value.path)
    }
    return String(value || '').trim()
}

export function cloneDesign<T>(value: T): T {
    return JSON.parse(JSON.stringify(value ?? ({} as T))) as T
}

export function uid(prefix: string): string {
    return `${prefix}_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 7)}`
}

/** 文本高度防截断：h >= ceil(expectedLines * fontSize * lineHeight) + 24 */
export function safeTextHeight(fontSize: number, lines = 1, lineHeight = 1.15): number {
    return Math.ceil(lines * Math.max(10, fontSize) * Math.max(0.8, lineHeight)) + 24
}

export function defaultText(
    id: string,
    x: number,
    y: number,
    w: number,
    h: number,
    z: number,
    text: string,
    fontSize: number,
    color: string,
    fontWeight = '400',
    align = 'center',
    extra: Partial<DesignLayer> = {},
): DesignLayer {
    const lineHeight = Number(extra.lineHeight ?? 1.15)
    const lines = Math.max(1, String(text || '').split('\n').length)
    const minSafeH = safeTextHeight(fontSize, lines, lineHeight)
    return {
        id,
        type: 'text',
        x,
        y,
        w,
        h: Math.max(h, minSafeH),
        z,
        visible: 1,
        locked: 0,
        opacity: 1,
        rotate: 0,
        text,
        fontSize,
        fontWeight,
        lineHeight,
        align,
        color,
        scaleX: 1,
        scaleY: 1,
        letterSpacing: 0,
        fillType: 'solid',
        gradientFrom: color,
        gradientTo: color,
        gradientAngle: 90,
        fillImage: '',
        fillImageUrl: '',
        fillImageFit: 'cover',
        textStrokeColor: '#000000',
        textStrokeWidth: 0,
        textStrokeOpacity: 1,
        shadowColor: '#000000',
        shadowBlur: 0,
        shadowOffsetX: 0,
        shadowOffsetY: 0,
        shadowOpacity: 0.35,
        artPreset: 'default',
        ...extra,
    }
}

export function defaultQrcode(x: number, y: number, w: number, h: number, z: number, extra: Partial<DesignLayer> = {}): DesignLayer {
    return {
        id: uid('qrcode'),
        type: 'qrcode',
        x,
        y,
        w: Math.max(QRCODE_MIN_SIZE, w),
        h: Math.max(QRCODE_MIN_SIZE, h),
        z,
        visible: 1,
        locked: 0,
        opacity: 1,
        rotate: 0,
        src: '',
        padding: 14,
        radius: 0,
        ...extra,
    }
}

export function defaultRect(
    id: string,
    x: number,
    y: number,
    w: number,
    h: number,
    z: number,
    fill: string,
    options: Partial<DesignLayer> = {},
): DesignLayer {
    return {
        id,
        type: 'rect',
        x,
        y,
        w,
        h,
        z,
        visible: 1,
        locked: 0,
        opacity: 1,
        rotate: 0,
        fill,
        stroke: '',
        strokeWidth: 0,
        radius: 0,
        ...options,
    }
}

export function defaultLine(
    id: string,
    x: number,
    y: number,
    w: number,
    z: number,
    stroke = '#D8C08B',
    strokeWidth = 2,
): DesignLayer {
    return {
        id,
        type: 'line',
        x,
        y,
        w: Math.max(1, w),
        h: 0,
        z,
        visible: 1,
        locked: 0,
        opacity: 1,
        rotate: 0,
        stroke,
        strokeWidth,
    }
}

export function defaultRepeater(
    id: string,
    x: number,
    y: number,
    columns: number,
    cardWidth: number,
    cardHeight: number,
    gapX: number,
    gapY: number,
    z: number,
    limit: number,
    source: 'ranking' | 'top' = 'ranking',
    cardLayers: DesignLayer[] = [],
    align = 'left',
): DesignLayer {
    return {
        id,
        type: 'repeater',
        source,
        x,
        y,
        w: columns * cardWidth + Math.max(0, columns - 1) * gapX,
        h: cardHeight,
        z,
        visible: 1,
        locked: 0,
        opacity: 1,
        rotate: 0,
        columns,
        card_width: cardWidth,
        card_height: cardHeight,
        gap_x: gapX,
        gap_y: gapY,
        limit,
        align,
        card_layers: cardLayers.length ? cardLayers : defaultCardLayers(cardWidth, cardHeight),
    }
}

export function defaultCardLayers(cardWidth: number, cardHeight: number): DesignLayer[] {
    return [
        { id: 'photo', type: 'image', x: 0, y: 0, w: cardWidth, h: cardHeight, z: 0, visible: 1, locked: 0, opacity: 1, rotate: 0, src: '{avatar_photo_url}', fit: 'cover', radius: 0 },
        { ...defaultText('english', 18, 24, cardWidth - 36, 46, 0, '{english_name}', 26, '#FFFFFF', '800', 'left') },
        { ...defaultText('name', 18, 72, cardWidth - 36, 46, 0, '*{chinese_name}', 24, '#FFFFFF', '500', 'left') },
        { ...defaultText('count', 18, 120, 140, 108, 0, '{count}', 72, '#FFF9ED', '300', 'left', { lineHeight: 1.05 }) },
    ]
}

export function defaultTopCardLayers(): DesignLayer[] {
    return [
        { id: 'photo', type: 'image', x: 0, y: 0, w: 310, h: 680, z: 0, visible: 1, locked: 0, opacity: 1, rotate: 0, src: '{half_body_photo_url}', fit: 'contain', backgroundFill: 'rgba(255,255,255,0)', radius: 0 },
    ]
}

/** 执行榜标准覆叠卡片子图层：照片 + 底部暗色托底（card-mask）+ 英文名 + 姓名 + 场次 */
export function rankingCardLayers(cardWidth: number, cardHeight: number, radius = 0, maskOpacity = 0.56, maskColor = '#0A1018'): DesignLayer[] {
    const maskHeight = Math.min(180, Math.max(110, Math.round(cardHeight * 0.42)))
    const maskY = cardHeight - maskHeight
    return [
        { id: 'photo', type: 'image', x: 0, y: 0, w: cardWidth, h: cardHeight, z: 0, visible: 1, locked: 0, opacity: 1, rotate: 0, src: '{avatar_photo_url}', fit: 'cover', radius },
        defaultRect('card-mask', 0, maskY, cardWidth, maskHeight, 0, maskColor, { opacity: maskOpacity, radius }),
        defaultText('english', 18, maskY + 12, cardWidth - 36, 44, 0, '{english_name}', 20, '#E2DCD2', '600', 'left', { letterSpacing: 1 }),
        defaultText('name', 18, maskY + 56, cardWidth - 36, 48, 0, '{chinese_name}', 26, '#FFFFFF', '600', 'left'),
        defaultText('count', 18, 14, 150, 96, 0, '{count}', 60, '#F5DFB0', '300', 'left'),
    ]
}

/** 紧排纯色横卡子图层（对齐参考图1：纯色背景 + 左侧人像 + 右对齐英文名/姓名/大数字） */
export function rankingSplitRowCardLayers(
    cardWidth: number,
    cardHeight: number,
    bgColor: string,
    textColor = '#FFFFFF',
    subTextColor = '#F2DCDC',
    countColor = '#FFFFFF',
): DesignLayer[] {
    const compact = cardWidth <= 290
    const photoW = Math.round(cardWidth * (compact ? 0.48 : 0.44))
    const photoPadTop = compact ? 14 : 16
    const textX = photoW - 4
    const textW = Math.max(70, cardWidth - textX - (compact ? 14 : 20))
    const engFs = compact ? 15 : 20
    const nameFs = compact ? 20 : 26
    const countFs = compact ? 52 : 70
    return [
        defaultRect('card-bg', 0, 0, cardWidth, cardHeight, 0, bgColor),
        { id: 'photo', type: 'image', x: 0, y: photoPadTop, w: photoW, h: cardHeight - photoPadTop, z: 0, visible: 1, locked: 0, opacity: 1, rotate: 0, src: '{avatar_photo_url}', fit: 'cover', radius: 0 },
        defaultText('english', textX, compact ? 16 : 22, textW, 38, 0, '{english_name}', engFs, subTextColor, '400', 'right', { letterSpacing: 1 }),
        defaultText('name', textX, compact ? 44 : 58, textW, 44, 0, '{chinese_name}', nameFs, textColor, '500', 'right'),
        defaultText('count', textX, compact ? 86 : 108, textW, Math.max(72, cardHeight - (compact ? 90 : 114)), 0, '{count}', countFs, countColor, '500', 'right', { lineHeight: 1.0 }),
    ]
}

function rankingGridBottom(y: number, columns: number, cardHeight: number, gapY: number, limit: number): number {
    const rows = Math.max(1, Math.ceil((limit || RANKING_ESTIMATED_COUNT) / Math.max(1, columns)))
    return y + rows * cardHeight + Math.max(0, rows - 1) * gapY
}

export function defaultDesign(type: MonthlyReportTemplateType): DesignConfig {
    if (type === 'ranking') {
        return cloneDesign(rankingPaperCrimsonDesign())
    }
    if (type === 'top') {
        return cloneDesign(topPaperCrimsonDesign())
    }
    return cloneDesign(additionPaperCrimsonDesign())
}

export function normalizeLayer(layer: any): DesignLayer {
    const type: DesignLayer['type'] = ['text', 'image', 'qrcode', 'rect', 'line', 'repeater'].includes(layer?.type)
        ? layer.type
        : 'text'
    const base: DesignLayer = {
        id: layer?.id || uid('layer'),
        type,
        x: Number(layer?.x || 0),
        y: Number(layer?.y || 0),
        w: Number(layer?.w || 120),
        h: Number(layer?.h ?? (type === 'line' ? 0 : 80)),
        z: Number(layer?.z || 0),
        visible: Number(layer?.visible ?? 1),
        locked: Number(layer?.locked || 0),
        opacity: Number(layer?.opacity ?? 1),
        rotate: Number(layer?.rotate || 0),
    }
    if (type === 'text') {
        return Object.assign(base, {
            text: String(layer?.text || ''),
            fontSize: Number(layer?.fontSize || 42),
            fontWeight: String(layer?.fontWeight || '400'),
            lineHeight: Number(layer?.lineHeight || 1.2),
            align: layer?.align || 'center',
            color: layer?.color || '#FFFFFF',
            scaleX: Number(layer?.scaleX || 1),
            scaleY: Number(layer?.scaleY || 1),
            letterSpacing: Number(layer?.letterSpacing || 0),
            fillType: ['solid', 'linear', 'image'].includes(layer?.fillType) ? layer.fillType : 'solid',
            gradientFrom: layer?.gradientFrom || layer?.color || '#FFFFFF',
            gradientTo: layer?.gradientTo || layer?.color || '#F8CB79',
            gradientAngle: Number(layer?.gradientAngle ?? 90),
            fillImage: normalizeUrl(layer?.fillImage || ''),
            fillImageUrl: normalizeUrl(layer?.fillImageUrl || layer?.fillImage || ''),
            fillImageFit: ['cover', 'contain', 'stretch'].includes(layer?.fillImageFit) ? layer.fillImageFit : 'cover',
            textStrokeColor: layer?.textStrokeColor || '#000000',
            textStrokeWidth: Number(layer?.textStrokeWidth || 0),
            textStrokeOpacity: Number(layer?.textStrokeOpacity ?? 1),
            shadowColor: layer?.shadowColor || '#000000',
            shadowBlur: Number(layer?.shadowBlur || 0),
            shadowOffsetX: Number(layer?.shadowOffsetX || 0),
            shadowOffsetY: Number(layer?.shadowOffsetY || 0),
            shadowOpacity: Number(layer?.shadowOpacity ?? 0.35),
            artPreset: layer?.artPreset || 'default',
        })
    }
    if (type === 'image') {
        return Object.assign(base, {
            src: normalizeUrl(layer?.src || ''),
            src_url: normalizeUrl(layer?.src_url || layer?.src || ''),
            fit: layer?.fit === 'contain' ? 'contain' : 'cover',
            backgroundFill: layer?.backgroundFill || '',
            radius: Number(layer?.radius || 0),
        })
    }
    if (type === 'qrcode') {
        return Object.assign(base, {
            w: Math.max(QRCODE_MIN_SIZE, Number(base.w || QRCODE_MIN_SIZE)),
            h: Math.max(QRCODE_MIN_SIZE, Number(base.h || QRCODE_MIN_SIZE)),
            visible: 1,
            opacity: 1,
            src: '',
            src_url: normalizeUrl(layer?.src_url || ''),
            padding: Number(layer?.padding ?? 14),
            radius: Number(layer?.radius ?? 0),
        })
    }
    if (type === 'rect') {
        return Object.assign(base, {
            fill: layer?.fill || '#FFFFFF',
            stroke: layer?.stroke || '',
            strokeWidth: Number(layer?.strokeWidth || 0),
            radius: Number(layer?.radius || 0),
        })
    }
    if (type === 'line') {
        return Object.assign(base, {
            w: Math.max(1, Number(base.w || 1)),
            stroke: layer?.stroke || '#D8C08B',
            strokeWidth: Number(layer?.strokeWidth || 2),
        })
    }
    return Object.assign(base, {
        source: layer?.source === 'top' ? 'top' : 'ranking',
        columns: Number(layer?.columns || 3),
        card_width: Number(layer?.card_width || 300),
        card_height: Number(layer?.card_height || 300),
        gap_x: Number(layer?.gap_x || 24),
        gap_y: Number(layer?.gap_y || 24),
        limit: Number(layer?.limit || 0),
        align: ['left', 'center', 'right'].includes(layer?.align) ? layer.align : 'left',
        card_layers: Array.isArray(layer?.card_layers) && layer.card_layers.length
            ? layer.card_layers.map(normalizeCardLayer)
            : defaultCardLayers(Number(layer?.card_width || 300), Number(layer?.card_height || 300)),
    })
}

export function normalizeCardLayer(layer: any): DesignLayer {
    return normalizeLayer({ ...layer, z: 0, locked: 0 })
}

export function normalizeDesign(value: any, type: MonthlyReportTemplateType = 'addition'): DesignConfig {
    const fallback = defaultDesign(type)
    const design = cloneDesign(value || ({} as any))
    const canvas: DesignConfig['canvas'] = {
        width: Number(design?.canvas?.width || fallback.canvas.width),
        height: Number(design?.canvas?.height || fallback.canvas.height),
    }
    if (type === 'ranking') {
        canvas.auto_height = Number(design?.canvas?.auto_height ?? fallback.canvas.auto_height ?? 1) === 1 ? 1 : 0
        canvas.min_height = Number(design?.canvas?.min_height || fallback.canvas.min_height || 1920)
    }
    return {
        canvas,
        background: {
            type: ['color', 'image'].includes(design?.background?.type) ? design.background.type : fallback.background.type,
            color: design?.background?.color || fallback.background.color,
            image: normalizeUrl(design?.background?.image || ''),
            image_url: normalizeUrl(design?.background?.image_url || design?.background?.image || ''),
            fit: design?.background?.fit === 'contain' ? 'contain' : 'cover',
            opacity: Number(design?.background?.opacity ?? 1),
        },
        layers: Array.isArray(design?.layers) ? design.layers.map(normalizeLayer) : fallback.layers,
    }
}

// ---------------------------------------------------------------------------
// 预设风格库：3 类月报 × 4 套 = 12 套
// 极简纸感 + 克制双色 + 极细英文标题与巨幅核心数字对比 + 充足呼吸留白
// ---------------------------------------------------------------------------

/** 新增档期 1：素纸朱红 · 居中经典（1:1 对齐参考图2） */
function additionPaperCrimsonDesign(): DesignConfig {
    return {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#F6F5F2', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 240, 88, 600, 70, 1, '格林社 · 衡水', 38, '#A60D12', '700', 'center', { letterSpacing: 4 }),
            defaultText('slogan', 180, 166, 720, 48, 2, '主持就找格林社 圆满呈现每一刻', 22, '#2B2B2B', '400', 'center', { letterSpacing: 2 }),
            defaultText('title', 60, 326, 960, 184, 3, '{addition_title}', 132, '#A60D12', '300', 'center', { letterSpacing: 10 }),
            defaultText('subtitle', 120, 504, 840, 72, 4, '{addition_subtitle}', 38, '#222222', '400', 'center', { letterSpacing: 2 }),
            defaultText('count', 60, 616, 960, 464, 5, '{addition_count}', 380, '#A60D12', '600', 'center'),
            defaultText('unit', 440, 1106, 200, 62, 6, '场', 32, '#222222', '400', 'center'),
            defaultText('thanks', 140, 1252, 800, 114, 7, 'THANKS FOR', 74, '#A60D12', '300', 'center', { letterSpacing: 6 }),
            defaultText('footer', 160, 1384, 760, 120, 8, '{footer_note}', 30, '#222222', '400', 'center', { lineHeight: 1.55 }),
            defaultText('contact-cn', 42, 1624, 360, 48, 9, '联系我们', 24, '#222222', '500', 'left'),
            defaultText('contact-en', 42, 1674, 540, 94, 10, 'CONTACT US', 56, '#A60D12', '300', 'left', { letterSpacing: 3 }),
            defaultText('contact', 42, 1776, 560, 52, 11, '河北省衡水市桃城区汇宁创业A座', 22, '#2B2B2B', '400', 'left'),
            defaultText('qrcode-note', 816, 1618, 224, 48, 12, '扫码预约主持服务', 22, '#222222', '400', 'center'),
            defaultQrcode(842, 1668, 172, 172, 13),
        ],
    }
}

/** 新增档期 2：素纸墨绿 · 瑞士左对齐 */
function additionPaperEmeraldDesign(): DesignConfig {
    return {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#F6F5F0', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 64, 88, 520, 64, 1, '格林社 · 衡水', 34, '#1A4331', '700', 'left', { letterSpacing: 4 }),
            defaultText('month-label', 600, 94, 416, 58, 2, '{report_month_label}', 26, '#5A6962', '400', 'right', { letterSpacing: 2 }),
            defaultLine('top-rule', 64, 176, 952, 3, '#D5DDD8', 1),
            defaultText('title', 64, 286, 952, 176, 4, '{addition_title}', 124, '#1A4331', '300', 'left', { letterSpacing: 8 }),
            defaultText('subtitle', 64, 464, 952, 72, 5, '{addition_subtitle}', 38, '#222222', '400', 'left', { letterSpacing: 2 }),
            defaultText('count', 52, 624, 960, 484, 6, '{addition_count}', 396, '#1A4331', '500', 'left'),
            defaultText('unit', 64, 1134, 400, 66, 7, '新增婚礼档期 · 场', 30, '#5A6962', '400', 'left', { letterSpacing: 2 }),
            defaultLine('mid-rule', 64, 1264, 160, 8, '#1A4331', 2),
            defaultText('thanks', 64, 1304, 640, 92, 9, 'THANKS FOR', 56, '#1A4331', '300', 'left', { letterSpacing: 5 }),
            defaultText('footer', 64, 1404, 680, 120, 10, '{footer_note}', 30, '#2B2B2B', '400', 'left', { lineHeight: 1.55 }),
            defaultLine('bottom-rule', 64, 1584, 952, 11, '#D5DDD8', 1),
            defaultText('contact-cn', 64, 1632, 360, 48, 12, '联系我们', 24, '#222222', '500', 'left'),
            defaultText('contact-en', 64, 1682, 540, 92, 13, 'CONTACT US', 54, '#1A4331', '300', 'left', { letterSpacing: 3 }),
            defaultText('contact', 64, 1782, 560, 52, 14, '河北省衡水市桃城区汇宁创业A座', 22, '#5A6962', '400', 'left'),
            defaultText('qrcode-note', 816, 1626, 224, 48, 15, '扫码预约主持服务', 22, '#222222', '400', 'center'),
            defaultQrcode(842, 1676, 172, 172, 16),
        ],
    }
}

/** 新增档期 3：曜黑鎏金 · 暗调晚宴 */
function additionObsidianGoldDesign(): DesignConfig {
    return {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#151412', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 240, 92, 600, 68, 1, '格林社 · 衡水', 36, '#C9A45C', '600', 'center', { letterSpacing: 6 }),
            defaultText('slogan', 180, 168, 720, 48, 2, '主持就找格林社 圆满呈现每一刻', 22, '#9A9080', '400', 'center', { letterSpacing: 2 }),
            defaultText('title', 60, 336, 960, 180, 3, '{addition_title}', 126, '#C9A45C', '300', 'center', { letterSpacing: 12 }),
            defaultText('subtitle', 120, 512, 840, 72, 4, '{addition_subtitle}', 36, '#E8E2D6', '400', 'center', { letterSpacing: 3 }),
            defaultText('count', 60, 636, 960, 464, 5, '{addition_count}', 380, '#E8C984', '400', 'center', {
                fillType: 'linear',
                gradientFrom: '#F5DFB0',
                gradientTo: '#B8893A',
                gradientAngle: 90,
            }),
            defaultText('unit', 440, 1118, 200, 62, 6, '场', 32, '#9A9080', '400', 'center'),
            defaultLine('gold-rule', 440, 1218, 200, 7, '#6E5B37', 1),
            defaultText('thanks', 140, 1266, 800, 108, 8, 'THANKS FOR', 68, '#C9A45C', '300', 'center', { letterSpacing: 6 }),
            defaultText('footer', 160, 1392, 760, 120, 9, '{footer_note}', 28, '#CBC4B6', '400', 'center', { lineHeight: 1.55 }),
            defaultText('contact-cn', 56, 1628, 360, 48, 10, '联系我们', 24, '#9A9080', '400', 'left'),
            defaultText('contact-en', 56, 1678, 540, 92, 11, 'CONTACT US', 54, '#C9A45C', '300', 'left', { letterSpacing: 3 }),
            defaultText('contact', 56, 1780, 560, 52, 12, '河北省衡水市桃城区汇宁创业A座', 22, '#9A9080', '400', 'left'),
            defaultText('qrcode-note', 816, 1622, 224, 48, 13, '扫码预约主持服务', 22, '#CBC4B6', '400', 'center'),
            defaultQrcode(842, 1672, 172, 172, 14),
        ],
    }
}

/** 新增档期 4：暖纸砖橙 · 色带聚焦 */
function additionBandTerracottaDesign(): DesignConfig {
    return {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#F8F4EE', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 56, 86, 520, 64, 1, '格林社 · 衡水', 34, '#B84314', '700', 'left', { letterSpacing: 4 }),
            defaultText('slogan', 56, 156, 600, 46, 2, '主持就找格林社 圆满呈现每一刻', 22, '#4A433E', '400', 'left'),
            defaultText('title', 56, 304, 968, 176, 3, '{addition_title}', 124, '#B84314', '300', 'left', { letterSpacing: 8 }),
            defaultText('subtitle', 56, 480, 968, 72, 4, '{addition_subtitle}', 38, '#222222', '400', 'left', { letterSpacing: 2 }),
            defaultRect('hero-block', 0, 620, 1080, 560, 5, '#B84314'),
            defaultText('count', 60, 668, 960, 420, 6, '{addition_count}', 340, '#FFFFFF', '500', 'center'),
            defaultText('unit', 440, 1094, 200, 62, 7, '场', 32, '#FCEAE2', '400', 'center'),
            defaultText('thanks', 56, 1266, 800, 104, 8, 'THANKS FOR', 66, '#B84314', '300', 'left', { letterSpacing: 5 }),
            defaultText('footer', 56, 1384, 720, 120, 9, '{footer_note}', 30, '#2B2B2B', '400', 'left', { lineHeight: 1.55 }),
            defaultText('contact-cn', 56, 1628, 360, 48, 10, '联系我们', 24, '#222222', '500', 'left'),
            defaultText('contact-en', 56, 1678, 540, 92, 11, 'CONTACT US', 54, '#B84314', '300', 'left', { letterSpacing: 3 }),
            defaultText('contact', 56, 1780, 560, 52, 12, '河北省衡水市桃城区汇宁创业A座', 22, '#4A433E', '400', 'left'),
            defaultText('qrcode-note', 816, 1622, 224, 48, 13, '扫码预约主持服务', 22, '#222222', '400', 'center'),
            defaultQrcode(842, 1672, 172, 172, 14),
        ],
    }
}

/** 执行榜 1：素纸朱红 · 4列紧排矩阵（1:1 对齐参考图1） */
function rankingPaperCrimsonDesign(): DesignConfig {
    const columns = 4
    const cardW = 270
    const cardH = 185
    const gapX = 0
    const gapY = 0
    const limit = 24
    const repeaterY = 1130
    const baseBottom = rankingGridBottom(repeaterY, columns, cardH, gapY, limit) // 2240

    return {
        canvas: { width: 1080, height: Math.max(1920, baseBottom), auto_height: 1, min_height: 1920 },
        background: { type: 'color', color: '#F6F5F2', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 240, 48, 600, 68, 1, '格林社 · 衡水', 36, '#A60D12', '700', 'center', { letterSpacing: 4 }),
            defaultText('slogan', 180, 122, 720, 46, 2, '主持就找格林社 圆满呈现每一刻', 22, '#2B2B2B', '400', 'center', { letterSpacing: 2 }),
            defaultText('eyebrow', 80, 206, 920, 148, 3, 'REPORT', 112, '#A60D12', '300', 'center', { letterSpacing: 8 }),
            defaultText('title', 80, 354, 920, 74, 4, '{ranking_title}', 42, '#222222', '400', 'center', { letterSpacing: 2 }),
            defaultText('summary-count', 40, 434, 1000, 420, 5, '{executed_count}', 350, '#A60D12', '600', 'center'),
            defaultText('contact-cn', 42, 874, 360, 48, 6, '联系我们', 24, '#222222', '500', 'left'),
            defaultText('contact-en', 42, 922, 540, 92, 7, 'CONTACT US', 56, '#A60D12', '300', 'left', { letterSpacing: 3 }),
            defaultText('contact', 42, 1020, 580, 52, 8, '河北省衡水市桃城区汇宁创业A座', 22, '#2B2B2B', '400', 'left'),
            defaultText('qrcode-note', 816, 846, 224, 46, 9, '扫码预约主持服务', 22, '#222222', '400', 'center'),
            defaultQrcode(842, 896, 172, 172, 10),
            defaultRepeater(
                'ranking-staffs',
                0,
                repeaterY,
                columns,
                cardW,
                cardH,
                gapX,
                gapY,
                11,
                limit,
                'ranking',
                rankingSplitRowCardLayers(cardW, cardH, '#A60D12', '#FFFFFF', '#F2DCDC', '#FFFFFF'),
                'left',
            ),
        ],
    }
}

/** 执行榜 2：素纸藏青 · 3列宽卡画册 */
function rankingPaperNavyDesign(): DesignConfig {
    const columns = 3
    const cardW = 324
    const cardH = 220
    const gapX = 18
    const gapY = 20
    const limit = 24
    const repeaterY = 580
    const baseBottom = rankingGridBottom(repeaterY, columns, cardH, gapY, limit) // 2480
    const footerY = baseBottom + 64 // 2544

    return {
        canvas: { width: 1080, height: footerY + 340, auto_height: 1, min_height: 1920 },
        background: { type: 'color', color: '#F5F6F8', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 56, 68, 500, 60, 1, '格林社 · 衡水', 34, '#163A5F', '700', 'left', { letterSpacing: 4 }),
            defaultText('eyebrow', 580, 74, 444, 52, 2, 'MONTHLY REPORT', 24, '#5C6B7C', '300', 'right', { letterSpacing: 4 }),
            defaultLine('top-rule', 56, 148, 968, 3, '#DCE2EA', 1),
            defaultText('title', 56, 198, 580, 94, 4, '{ranking_title}', 48, '#1B2532', '500', 'left'),
            defaultText('footer', 56, 306, 560, 110, 5, '{footer_note}', 26, '#5C6B7C', '400', 'left', { lineHeight: 1.5 }),
            defaultText('summary-count', 640, 184, 384, 240, 6, '{executed_count}', 190, '#163A5F', '500', 'right'),
            defaultText('summary-label', 640, 412, 384, 52, 7, '本月共计执行场次', 26, '#5C6B7C', '400', 'right'),
            defaultLine('mid-rule', 56, 514, 968, 8, '#DCE2EA', 1),
            defaultRepeater(
                'ranking-staffs',
                36,
                repeaterY,
                columns,
                cardW,
                cardH,
                gapX,
                gapY,
                9,
                limit,
                'ranking',
                rankingSplitRowCardLayers(cardW, cardH, '#163A5F', '#FFFFFF', '#BDD0E4', '#FFFFFF'),
                'left',
            ),
            defaultText('contact-cn', 56, footerY + 24, 360, 48, 10, '联系我们', 24, '#1B2532', '500', 'left'),
            defaultText('contact-en', 56, footerY + 74, 540, 92, 11, 'CONTACT US', 54, '#163A5F', '300', 'left', { letterSpacing: 3 }),
            defaultText('contact', 56, footerY + 174, 560, 52, 12, '河北省衡水市桃城区汇宁创业A座', 22, '#5C6B7C', '400', 'left'),
            defaultText('qrcode-note', 808, footerY + 8, 224, 46, 13, '扫码预约主持服务', 22, '#1B2532', '400', 'center'),
            defaultQrcode(834, footerY + 58, 172, 172, 14),
        ],
    }
}

/** 执行榜 3：曜黑鎏金 · 3列大片方阵 */
function rankingObsidianGoldDesign(): DesignConfig {
    const columns = 3
    const cardW = 316
    const cardH = 316
    const gapX = 24
    const gapY = 26
    const limit = 24
    const repeaterY = 396
    const baseBottom = rankingGridBottom(repeaterY, columns, cardH, gapY, limit) // 3106
    const footerY = baseBottom + 64 // 3170

    return {
        canvas: { width: 1080, height: footerY + 460, auto_height: 1, min_height: 1920 },
        background: { type: 'color', color: '#151412', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 240, 58, 600, 62, 1, '格林社 · 衡水', 34, '#C9A45C', '600', 'center', { letterSpacing: 6 }),
            defaultText('eyebrow', 80, 142, 920, 118, 2, 'REPORT', 92, '#C9A45C', '300', 'center', { letterSpacing: 10 }),
            defaultText('title', 80, 268, 920, 68, 3, '{ranking_title}', 38, '#EAE4D8', '400', 'center', { letterSpacing: 2 }),
            defaultLine('top-rule', 440, 346, 200, 4, '#6E5B37', 1),
            defaultRepeater(
                'ranking-staffs',
                42,
                repeaterY,
                columns,
                cardW,
                cardH,
                gapX,
                gapY,
                5,
                limit,
                'ranking',
                rankingCardLayers(cardW, cardH, 0, 0.62, '#0A0908'),
                'left',
            ),
            defaultLine('footer-rule', 42, footerY, 996, 6, '#3B3427', 1),
            defaultText('summary-count', 42, footerY + 26, 480, 236, 7, '{executed_count}', 190, '#C9A45C', '400', 'left'),
            defaultText('summary-label', 42, footerY + 250, 480, 54, 8, '本月共计执行场次', 26, '#9A9080', '400', 'left'),
            defaultText('footer', 42, footerY + 314, 620, 62, 9, '{footer_note}', 26, '#CBC4B6', '400', 'left'),
            defaultText('qrcode-note', 814, footerY + 62, 224, 46, 10, '扫码预约主持服务', 22, '#CBC4B6', '400', 'center'),
            defaultQrcode(840, footerY + 112, 172, 172, 11),
        ],
    }
}

/** 执行榜 4：素纸墨绿 · 2列画廊横卡 */
function rankingPaperEmeraldDesign(): DesignConfig {
    const columns = 2
    const cardW = 540
    const cardH = 260
    const gapX = 0
    const gapY = 0
    const limit = 12
    const repeaterY = 1090
    const baseBottom = rankingGridBottom(repeaterY, columns, cardH, gapY, limit) // 2650

    return {
        canvas: { width: 1080, height: Math.max(1920, baseBottom), auto_height: 1, min_height: 1920 },
        background: { type: 'color', color: '#F6F5F0', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 48, 60, 520, 62, 1, '格林社 · 衡水', 34, '#1A4331', '700', 'left', { letterSpacing: 4 }),
            defaultText('slogan', 48, 126, 600, 46, 2, '主持就找格林社 圆满呈现每一刻', 22, '#5A6962', '400', 'left'),
            defaultText('eyebrow', 560, 62, 472, 114, 3, 'REPORT', 86, '#1A4331', '300', 'right', { letterSpacing: 6 }),
            defaultLine('top-rule', 48, 198, 984, 4, '#D5DDD8', 1),
            defaultText('title', 48, 256, 984, 72, 5, '{ranking_title}', 42, '#222222', '400', 'center', { letterSpacing: 2 }),
            defaultText('summary-count', 48, 344, 984, 400, 6, '{executed_count}', 330, '#1A4331', '500', 'center'),
            defaultText('contact-cn', 48, 822, 360, 48, 7, '联系我们', 24, '#222222', '500', 'left'),
            defaultText('contact-en', 48, 872, 540, 92, 8, 'CONTACT US', 54, '#1A4331', '300', 'left', { letterSpacing: 3 }),
            defaultText('contact', 48, 972, 580, 52, 9, '河北省衡水市桃城区汇宁创业A座', 22, '#5A6962', '400', 'left'),
            defaultText('qrcode-note', 816, 796, 224, 46, 10, '扫码预约主持服务', 22, '#222222', '400', 'center'),
            defaultQrcode(842, 846, 172, 172, 11),
            defaultRepeater(
                'ranking-staffs',
                0,
                repeaterY,
                columns,
                cardW,
                cardH,
                gapX,
                gapY,
                12,
                limit,
                'ranking',
                rankingSplitRowCardLayers(cardW, cardH, '#1A4331', '#FFFFFF', '#C6D8CE', '#E5D3A3'),
                'left',
            ),
        ],
    }
}

/** 单王 helper：半身照图 */
function topCardPhoto(width: number, height: number): DesignLayer {
    return {
        id: 'photo',
        type: 'image',
        x: 0,
        y: 0,
        w: width,
        h: height,
        z: 0,
        visible: 1,
        locked: 0,
        opacity: 1,
        rotate: 0,
        src: '{half_body_photo_url}',
        fit: 'contain',
        backgroundFill: 'rgba(255,255,255,0)',
        radius: 0,
    }
}

/** 单王 1：素纸朱红 · 破框红带封面（对齐参考图3神韵） */
function topPaperCrimsonDesign(): DesignConfig {
    return {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#F6F5F2', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 46, 62, 420, 68, 1, '格林社 · 衡水', 38, '#A60D12', '700', 'left', { letterSpacing: 3 }),
            defaultText('slogan', 46, 134, 460, 48, 2, '主持就找格林社 圆满呈现每一刻', 21, '#2B2B2B', '400', 'left'),
            defaultText('top-title', 380, 56, 654, 344, 3, 'THE\nMOST', 150, '#A60D12', '300', 'right', { lineHeight: 0.98, letterSpacing: 8 }),
            defaultText('award', 480, 404, 554, 68, 4, '月度单王', 44, '#222222', '400', 'right', { letterSpacing: 3 }),
            defaultText('month-label', 480, 476, 554, 62, 5, '{report_month_label} · {report_year}', 34, '#A60D12', '400', 'right', { letterSpacing: 2 }),
            defaultRect('hero-block', 0, 740, 1080, 480, 6, '#A60D12'),
            defaultRepeater('top-staffs', 50, 540, 3, 310, 680, 25, 0, 7, 3, 'top', [topCardPhoto(310, 680)], 'center'),
            defaultText('eyebrow', 46, 1266, 320, 52, 8, 'TOP.1', 32, '#A60D12', '600', 'left', { letterSpacing: 2 }),
            defaultText('names', 46, 1320, 560, 76, 9, '{top_staff_names}', 48, '#1E1E1E', '500', 'left'),
            defaultText('honor', 46, 1408, 560, 108, 10, '以实力 荣获本月人气之星\n以专业 获得新人广泛认可', 26, '#555555', '400', 'left', { lineHeight: 1.48 }),
            defaultText('count-label', 660, 1266, 374, 52, 11, '本月共计主持', 28, '#222222', '400', 'right'),
            defaultText('count', 620, 1322, 348, 198, 12, '{top_count}', 164, '#A60D12', '500', 'right'),
            defaultText('unit', 980, 1424, 54, 56, 13, '场', 28, '#222222', '400', 'left'),
            defaultLine('bottom-rule', 46, 1574, 988, 14, '#E2DFD9', 1),
            defaultText('contact-cn', 46, 1624, 360, 48, 15, '联系我们', 24, '#222222', '500', 'left'),
            defaultText('contact-en', 46, 1674, 540, 94, 16, 'CONTACT US', 56, '#A60D12', '300', 'left', { letterSpacing: 3 }),
            defaultText('contact', 46, 1776, 560, 52, 17, '河北省衡水市桃城区汇宁创业A座', 22, '#2B2B2B', '400', 'left'),
            defaultText('qrcode-note', 816, 1618, 224, 48, 18, '扫码预约主持服务', 22, '#222222', '400', 'center'),
            defaultQrcode(842, 1668, 172, 172, 19),
        ],
    }
}

/** 单王 2：素纸墨绿 · 居中杂志封面 */
function topPaperEmeraldDesign(): DesignConfig {
    return {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#F6F5F0', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 240, 62, 600, 64, 1, '格林社 · 衡水', 34, '#1A4331', '700', 'center', { letterSpacing: 5 }),
            defaultText('top-title', 80, 150, 920, 184, 2, '{top_title}', 136, '#1A4331', '300', 'center', { letterSpacing: 10 }),
            defaultText('award', 140, 342, 800, 60, 3, '月度单王 · {report_month_label}', 32, '#5A6962', '400', 'center', { letterSpacing: 3 }),
            defaultRect('hero-block', 64, 620, 952, 560, 4, '#1A4331'),
            defaultRepeater('top-staffs', 64, 480, 3, 300, 700, 26, 0, 5, 3, 'top', [topCardPhoto(300, 700)], 'center'),
            defaultText('names', 140, 1218, 800, 76, 6, '{top_staff_names}', 46, '#1A4331', '600', 'center'),
            defaultText('honor', 140, 1296, 800, 58, 7, '以实力 荣获本月人气之星 · 获得新人广泛认可', 26, '#5A6962', '400', 'center'),
            defaultLine('mid-rule', 64, 1372, 952, 8, '#D5DDD8', 1),
            defaultText('executed-label', 64, 1396, 300, 48, 9, '共执行场次', 24, '#5A6962', '400', 'center'),
            defaultText('executed-value', 64, 1446, 300, 108, 10, '{executed_count}', 72, '#1A4331', '500', 'center'),
            defaultRect('v-sep-1', 378, 1418, 1, 112, 11, '#D5DDD8'),
            defaultText('top-label', 390, 1396, 300, 48, 9, '单王场次', 24, '#5A6962', '400', 'center'),
            defaultText('top-value', 390, 1446, 300, 108, 10, '{top_count}', 72, '#1A4331', '700', 'center'),
            defaultRect('v-sep-2', 700, 1418, 1, 112, 11, '#D5DDD8'),
            defaultText('addition-label', 716, 1396, 300, 48, 9, '新增档期', 24, '#5A6962', '400', 'center'),
            defaultText('addition-value', 716, 1446, 300, 108, 10, '{addition_count}', 72, '#1A4331', '500', 'center'),
            defaultLine('bottom-rule', 64, 1582, 952, 12, '#D5DDD8', 1),
            defaultText('contact-cn', 64, 1624, 360, 48, 13, '联系我们', 24, '#222222', '500', 'left'),
            defaultText('contact-en', 64, 1674, 540, 92, 14, 'CONTACT US', 54, '#1A4331', '300', 'left', { letterSpacing: 3 }),
            defaultText('contact', 64, 1776, 560, 52, 15, '河北省衡水市桃城区汇宁创业A座', 22, '#5A6962', '400', 'left'),
            defaultText('qrcode-note', 816, 1618, 224, 48, 16, '扫码预约主持服务', 22, '#222222', '400', 'center'),
            defaultQrcode(842, 1668, 172, 172, 17),
        ],
    }
}

/** 单王 3：曜黑鎏金 · 暗调荣誉大片 */
function topObsidianGoldDesign(): DesignConfig {
    return {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#151412', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('top-title', 56, 68, 600, 326, 1, 'THE\nMOST', 140, '#C9A45C', '300', 'left', { lineHeight: 0.98, letterSpacing: 8 }),
            defaultText('brand', 660, 76, 364, 60, 2, '格林社 · 衡水', 32, '#E8E2D6', '600', 'right', { letterSpacing: 4 }),
            defaultText('award', 640, 144, 384, 56, 3, '月度单王', 34, '#C9A45C', '400', 'right', { letterSpacing: 3 }),
            defaultText('month-label', 640, 206, 384, 52, 4, '{report_month_label}', 26, '#9A9080', '400', 'right', { letterSpacing: 2 }),
            defaultLine('stage-top-line', 56, 424, 968, 5, '#6E5B37', 1),
            defaultRect('hero-block', 56, 540, 968, 640, 6, '#1E1B16'),
            defaultRepeater('top-staffs', 56, 470, 3, 306, 710, 25, 0, 7, 3, 'top', [topCardPhoto(306, 710)], 'center'),
            defaultLine('stage-bottom-line', 56, 1180, 968, 8, '#6E5B37', 1),
            defaultText('names', 56, 1234, 620, 76, 9, '{top_staff_names}', 48, '#F5DFB0', '500', 'left'),
            defaultText('honor', 56, 1318, 620, 108, 10, '以实力 荣获本月人气之星\n以专业 获得新人广泛认可', 26, '#9A9080', '400', 'left', { lineHeight: 1.48 }),
            defaultText('count-label', 680, 1236, 344, 52, 11, '本月共计主持', 26, '#9A9080', '400', 'right'),
            defaultText('count', 640, 1296, 328, 198, 12, '{top_count}', 160, '#E8C984', '400', 'right', {
                fillType: 'linear',
                gradientFrom: '#F5DFB0',
                gradientTo: '#B8893A',
                gradientAngle: 90,
            }),
            defaultText('unit', 980, 1396, 54, 56, 13, '场', 28, '#C9A45C', '400', 'left'),
            defaultLine('bottom-rule', 56, 1574, 968, 14, '#3B3427', 1),
            defaultText('contact-cn', 56, 1624, 360, 48, 15, '联系我们', 24, '#9A9080', '400', 'left'),
            defaultText('contact-en', 56, 1674, 540, 92, 16, 'CONTACT US', 54, '#C9A45C', '300', 'left', { letterSpacing: 3 }),
            defaultText('contact', 56, 1776, 560, 52, 17, '河北省衡水市桃城区汇宁创业A座', 22, '#9A9080', '400', 'left'),
            defaultText('qrcode-note', 816, 1618, 224, 48, 18, '扫码预约主持服务', 22, '#CBC4B6', '400', 'center'),
            defaultQrcode(842, 1668, 172, 172, 19),
        ],
    }
}

/** 单王 4：素纸藏青 · 侧栏画报封面 */
function topPaperNavyDesign(): DesignConfig {
    return {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#F5F6F8', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 56, 62, 520, 62, 1, '格林社 · 衡水', 34, '#163A5F', '700', 'left', { letterSpacing: 4 }),
            defaultText('month-label', 600, 68, 424, 52, 2, '{report_month_label}', 26, '#5C6B7C', '400', 'right', { letterSpacing: 2 }),
            defaultText('top-title', 56, 152, 968, 178, 3, '{top_title}', 132, '#163A5F', '300', 'left', { letterSpacing: 8 }),
            defaultRect('hero-block', 56, 400, 620, 1060, 4, '#163A5F'),
            defaultRepeater('top-staffs', 68, 480, 3, 190, 960, 12, 0, 5, 3, 'top', [topCardPhoto(190, 960)], 'center'),
            defaultText('eyebrow', 716, 412, 308, 52, 6, 'TOP.1', 32, '#163A5F', '600', 'left', { letterSpacing: 2 }),
            defaultText('award', 716, 472, 308, 60, 7, '月度单王', 38, '#1B2532', '400', 'left'),
            defaultLine('side-rule', 716, 558, 120, 8, '#163A5F', 2),
            defaultText('names', 716, 604, 308, 150, 9, '{top_staff_names}', 44, '#1B2532', '600', 'left', { lineHeight: 1.25 }),
            defaultText('count-label', 716, 860, 308, 52, 10, '本月共计主持', 26, '#5C6B7C', '400', 'left'),
            defaultText('count', 716, 922, 240, 210, 11, '{top_count}', 168, '#163A5F', '500', 'left'),
            defaultText('unit', 964, 1030, 60, 56, 12, '场', 28, '#1B2532', '400', 'left'),
            defaultText('honor', 716, 1216, 308, 180, 13, '以实力荣获人气之星\n以专业获新人认可', 24, '#5C6B7C', '400', 'left', { lineHeight: 1.5 }),
            defaultLine('bottom-rule', 56, 1564, 968, 14, '#DCE2EA', 1),
            defaultText('contact-cn', 56, 1618, 360, 48, 15, '联系我们', 24, '#1B2532', '500', 'left'),
            defaultText('contact-en', 56, 1668, 540, 92, 16, 'CONTACT US', 54, '#163A5F', '300', 'left', { letterSpacing: 3 }),
            defaultText('contact', 56, 1770, 560, 52, 17, '河北省衡水市桃城区汇宁创业A座', 22, '#5C6B7C', '400', 'left'),
            defaultText('qrcode-note', 816, 1612, 224, 48, 18, '扫码预约主持服务', 22, '#1B2532', '400', 'center'),
            defaultQrcode(842, 1662, 172, 172, 19),
        ],
    }
}

function buildPreset(
    id: string,
    name: string,
    tag: string,
    description: string,
    templateType: MonthlyReportTemplateType,
    palettePreview: [string, string, string],
    design: DesignConfig,
): StylePreset {
    return { id, name, tag, description, templateType, palettePreview, design: normalizeDesign(design, templateType) }
}

export const STYLE_PRESETS: StylePreset[] = [
    // 新增档期 · 4 套
    buildPreset(
        'addition-paper-crimson',
        '素纸朱红',
        '居中经典',
        '暖白纸感大留白 + 极细红英文标题 + 巨幅朱红数字，克制耐看',
        'addition',
        ['#F6F5F2', '#A60D12', '#222222'],
        additionPaperCrimsonDesign(),
    ),
    buildPreset(
        'addition-paper-emerald',
        '素纸墨绿',
        '瑞士左对齐',
        '象牙纸白左对齐杂志构图 + 极细分隔线，清新雅致',
        'addition',
        ['#F6F5F0', '#1A4331', '#5A6962'],
        additionPaperEmeraldDesign(),
    ),
    buildPreset(
        'addition-obsidian-gold',
        '曜黑鎏金',
        '暗调晚宴',
        '哑光炭黑底 + 极细香槟金标题 + 流金大数字，沉稳大气',
        'addition',
        ['#151412', '#C9A45C', '#F5DFB0'],
        additionObsidianGoldDesign(),
    ),
    buildPreset(
        'addition-band-terracotta',
        '暖纸砖橙',
        '色带聚焦',
        '暖米纸底 + 中段通栏砖橙色带承托巨幅白字，醒目利落',
        'addition',
        ['#F8F4EE', '#B84314', '#FFFFFF'],
        additionBandTerracottaDesign(),
    ),
    // 执行榜 · 4 套
    buildPreset(
        'ranking-paper-crimson',
        '素纸朱红',
        '4列紧排矩阵',
        '上报告大数字 + 下 4 列零间距纯红矩阵，左人像右数据，成套经典',
        'ranking',
        ['#F6F5F2', '#A60D12', '#FFFFFF'],
        rankingPaperCrimsonDesign(),
    ),
    buildPreset(
        'ranking-paper-navy',
        '素纸藏青',
        '3列宽卡画册',
        '素白底左文右数 + 3 列疏朗藏青横卡 + 底部留白联系码',
        'ranking',
        ['#F5F6F8', '#163A5F', '#FFFFFF'],
        rankingPaperNavyDesign(),
    ),
    buildPreset(
        'ranking-obsidian-gold',
        '曜黑鎏金',
        '3列大片方阵',
        '哑光炭黑底 + 3 列全图压底人像方阵 + 底部香槟金大数字',
        'ranking',
        ['#151412', '#C9A45C', '#EAE4D8'],
        rankingObsidianGoldDesign(),
    ),
    buildPreset(
        'ranking-paper-emerald',
        '素纸墨绿',
        '2列画廊横卡',
        '象牙纸白上报告 + 下 2 列墨绿左图右文大横卡，适合精致小团队',
        'ranking',
        ['#F6F5F0', '#1A4331', '#E5D3A3'],
        rankingPaperEmeraldDesign(),
    ),
    // 单王 · 4 套
    buildPreset(
        'top-paper-crimson',
        '素纸朱红',
        '破框红带封面',
        '右上极细 THE MOST + 居中破框半身像红带 + 下方清晰三栏数据',
        'top',
        ['#F6F5F2', '#A60D12', '#222222'],
        topPaperCrimsonDesign(),
    ),
    buildPreset(
        'top-paper-emerald',
        '素纸墨绿',
        '居中杂志封面',
        '象牙纸白居中大字 + 墨绿纯色舞台破框人物 + 极简三栏数字',
        'top',
        ['#F6F5F0', '#1A4331', '#5A6962'],
        topPaperEmeraldDesign(),
    ),
    buildPreset(
        'top-obsidian-gold',
        '曜黑鎏金',
        '暗调荣誉大片',
        '哑光炭黑底 + 双极细金线夹出人物舞台 + 流金渐变单王场次',
        'top',
        ['#151412', '#C9A45C', '#F5DFB0'],
        topObsidianGoldDesign(),
    ),
    buildPreset(
        'top-paper-navy',
        '素纸藏青',
        '侧栏画报封面',
        '左 60% 藏青人物舞台 + 右 38% 留白纵向单王信息与大数字',
        'top',
        ['#F5F6F8', '#163A5F', '#1B2532'],
        topPaperNavyDesign(),
    ),
]

/** 一键配色方案（8 套克制高雅配色） */
export const COLOR_PALETTES: ColorPalette[] = [
    { id: 'paper-crimson', name: '素纸朱红', bg: '#F6F5F2', surface: '#ECEAE4', accent: '#A60D12', textPrimary: '#222222', textSecondary: '#555555', onAccent: '#FFFFFF', line: '#DFDDD6', cardMask: '#2B0406' },
    { id: 'obsidian-gold', name: '曜黑鎏金', bg: '#151412', surface: '#1E1B16', accent: '#C9A45C', textPrimary: '#EAE4D8', textSecondary: '#9A9080', onAccent: '#151412', line: '#4A3F2B', cardMask: '#0A0908' },
    { id: 'paper-emerald', name: '素纸墨绿', bg: '#F6F5F0', surface: '#EAEDE8', accent: '#1A4331', textPrimary: '#1A4331', textSecondary: '#5A6962', onAccent: '#FFFFFF', line: '#D5DDD8', cardMask: '#0B1E16' },
    { id: 'paper-navy', name: '素纸藏青', bg: '#F5F6F8', surface: '#EAEEF3', accent: '#163A5F', textPrimary: '#1B2532', textSecondary: '#5C6B7C', onAccent: '#FFFFFF', line: '#DCE2EA', cardMask: '#091828' },
    { id: 'band-terracotta', name: '暖纸砖橙', bg: '#F8F4EE', surface: '#EFE6DA', accent: '#B84314', textPrimary: '#222222', textSecondary: '#6B5C52', onAccent: '#FFFFFF', line: '#E4D8CA', cardMask: '#2E1105' },
    { id: 'wine-paper', name: '哑光酒红', bg: '#5E1318', surface: '#490D11', accent: '#E5C484', textPrimary: '#F7EFE2', textSecondary: '#DFC7A0', onAccent: '#5E1318', line: '#8C4348', cardMask: '#2E0709' },
    { id: 'mist-charcoal', name: '雾灰炭黑', bg: '#EFEFEF', surface: '#E4E4E4', accent: '#1F1F1F', textPrimary: '#1F1F1F', textSecondary: '#666666', onAccent: '#FFFFFF', line: '#D4D4D4', cardMask: '#111111' },
    { id: 'deep-ocean', name: '深海星蓝', bg: '#0F324C', surface: '#164263', accent: '#E8D5AE', textPrimary: '#FFFFFF', textSecondary: '#B5CEE0', onAccent: '#0F324C', line: '#2C5C80', cardMask: '#081A29' },
]

/** 画布比例预设 */
export const CANVAS_RATIO_PRESETS: CanvasRatioPreset[] = [
    { id: 'portrait', label: '标准竖屏', desc: '1080 × 1920 · 9:16 通用海报', width: 1080, height: 1920 },
    { id: 'compact', label: '紧凑卡片', desc: '1080 × 1440 · 3:4 会话更聚焦', width: 1080, height: 1440 },
    { id: 'ranking-auto', label: '执行榜自动长图', desc: '按上榜人数自动拉长', width: 1080, height: 3600, autoHeight: true },
]

function artPresetConfig(value: string): Record<string, any> {
    const found = posterTextArtPresets.find((item) => item.value === value)
    return found ? cloneDesign(found.config as Record<string, any>) : {}
}

/** 重点大字风格（零基础中文主大字样式卡片） */
export const HERO_ART_PRESETS: HeroArtPreset[] = [
    { label: '纯净原色', value: 'default', description: '保留图层原色，干净利落', config: { ...artPresetConfig('default'), artPreset: 'default' } },
    { label: '流金质感', value: 'gold_stroke', description: '金色渐变 + 细描边，庄重大气', config: { ...artPresetConfig('gold_stroke'), artPreset: 'gold_stroke' } },
    { label: '暖橙渐变', value: 'orange_gradient', description: '暖橙渐变，喜庆醒目', config: { ...artPresetConfig('orange_gradient'), artPreset: 'orange_gradient' } },
    { label: '白字投影', value: 'white_shadow', description: '白字立体投影，照片底更清晰', config: { ...artPresetConfig('white_shadow'), artPreset: 'white_shadow' } },
]
