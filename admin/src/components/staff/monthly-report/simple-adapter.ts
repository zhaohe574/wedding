// 单量月报设计器：快捷模式（零美工向导）纯函数适配层
// 仅依赖 ./types、./presets 与 ../poster-designer-common，不引入组件与副作用

import { wrapPosterText } from '../poster-designer-common'

import {
    BG_SCRIM_LAYER_ID,
    CANVAS_MAX_HEIGHT,
    CANVAS_MAX_WIDTH,
    COLOR_PALETTES,
    HERO_ART_PRESETS,
    QRCODE_MIN_SIZE,
    RANKING_ESTIMATED_COUNT,
    cloneDesign,
    defaultQrcode,
    normalizeDesign,
    normalizeLayer,
} from './presets'
import type {
    CanvasRatioPreset,
    ColorPalette,
    DesignConfig,
    DesignLayer,
    HealthCheckIssue,
    MonthlyReportTemplateType,
    QrcodePlacement,
    RankingGridState,
    StylePreset,
    TextSlotItem,
    ToggleModuleItem,
    TopLayoutState,
} from './types'

// ---------------------------------------------------------------------------
// 通用小工具
// ---------------------------------------------------------------------------

function clampInt(value: number, min: number, max: number): number {
    return Math.max(min, Math.min(max, Math.round(Number(value) || 0)))
}

function clampFloat(value: number, min: number, max: number): number {
    const num = Number(value)
    if (!Number.isFinite(num)) return min
    return Math.max(min, Math.min(max, num))
}

export function findLayerById(design: DesignConfig, layerId: string): DesignLayer | null {
    return design.layers.find((layer) => layer.id === layerId) || null
}

export function findRepeater(design: DesignConfig, source: 'ranking' | 'top'): DesignLayer | null {
    return design.layers.find((layer) => layer.type === 'repeater' && (layer.source || 'ranking') === source) || null
}

function cardLayerById(repeater: DesignLayer, subId: string): DesignLayer | null {
    return (repeater.card_layers || []).find((sub) => sub.id === subId) || null
}

function isLayerVisible(layer: DesignLayer): boolean {
    return Number(layer.visible ?? 1) === 1
}

function repeaterBoxHeight(repeater: DesignLayer): number {
    const columns = Math.max(1, Number(repeater.columns || 1))
    const cardHeight = Number(repeater.card_height || 300)
    const gapY = Number(repeater.gap_y || 0)
    const limit = Number(repeater.limit || 0)
    const rows = Math.max(1, Math.ceil((limit || RANKING_ESTIMATED_COUNT) / columns))
    return rows * cardHeight + Math.max(0, rows - 1) * gapY
}

function nextZ(layers: DesignLayer[]): number {
    return layers.reduce((max, layer) => Math.max(max, Number(layer.z || 0)), 0) + 1
}/** 动态变量示例值（用于体检与摘要预览，不参与渲染契约） */
export const SAMPLE_VARIABLES: Record<string, string> = {
    report_year: '2026',
    report_month_label: '2026年5月',
    addition_count: '204',
    executed_count: '233',
    top_count: '23',
    top_staff_names: '王一平、王帆',
    addition_title: '本月新增档期',
    addition_subtitle: '2026年5月新增婚礼档期',
    ranking_title: '2026年5月共计执行',
    top_title: 'THE MOST',
    footer_note: '感谢您的选择与信任',
}

export function renderSampleText(text: string): string {
    return String(text || '').replace(/\{([a-zA-Z0-9_]+)\}/g, (match, key: string) => SAMPLE_VARIABLES[key] ?? match)
}

function plainSummary(text: string, max = 10): string {
    const plain = renderSampleText(text).replace(/\s+/g, ' ').trim()
    if (!plain) return ''
    return plain.length > max ? `${plain.slice(0, max)}…` : plain
}

// ---------------------------------------------------------------------------
// 图层友好名称
// ---------------------------------------------------------------------------

const LAYER_LABEL_RULES: Array<[RegExp, string]> = [
    [/qrcode-note/, '二维码提示'],
    [/qrcode/, '二维码'],
    [/summary-label/, '汇总标签'],
    [/executed-label|top-label|addition-label|count-label/, '数据标签'],
    [/summary-count|executed-value|top-value|addition-value|total/, '汇总数字'],
    [/summary/, '汇总区'],
    [/watermark/, '背景水印'],
    [/brand-badge/, '品牌徽章'],
    [/brand-mark|brand-cn|brand/, '品牌抬头'],
    [/eyebrow-pill/, '眉标底块'],
    [/eyebrow/, '顶部眉标'],
    [/top-title/, '单王主标题'],
    [/title-bar|title-accent/, '标题饰条'],
    [/title/, '主标题'],
    [/subtitle/, '副标题'],
    [/^count$/, '数据数字'],
    [/month-label/, '月份'],
    [/month-en/, '英文月份'],
    [/year/, '年份'],
    [/nameplate/, '姓名铭牌'],
    [/names/, '单王姓名'],
    [/honor/, '荣誉文案'],
    [/award/, '奖项标注'],
    [/slogan/, '标语'],
    [/contact-cn/, '联系标题'],
    [/contact-en/, '联系英文'],
    [/contact/, '联系方式'],
    [/thanks/, '致谢英文'],
    [/footer-panel/, '页脚底板'],
    [/footer/, '页脚'],
    [/seal|medal/, '喜报印章'],
    [/arch/, '拱门画框'],
    [/paper/, '宣纸底卡'],
    [/bento/, '数据卡片'],
    [/side-panel|lintel|header-band|header-banner|header-card|header-tag/, '版式底块'],
    [/rank-badge|rank-text/, '排名角标'],
    [/card-bg|card-bottom|card-border/, '卡片框架'],
    [/center-script|center-title/, '中心装饰字'],
    [/hero/, '主题色块'],
    [/frame/, '装饰画框'],
    [/corner|v-sep|ornament|deco|rule|line/, '装饰线条'],
    [/scrim/, '背景压暗'],
    [/card-mask|mask/, '卡片托底'],
    [/english/, '英文名'],
    [/name/, '姓名'],
    [/photo/, '照片'],
    [/unit-pill|unit/, '单位'],
    [/staffs|repeater/, '人员卡片'],
]

export function getLayerTypeLabel(type: string): string {
    const map: Record<string, string> = {
        text: '文本',
        image: '图片',
        qrcode: '二维码',
        rect: '矩形',
        line: '分割线',
        repeater: '人员卡片',
    }
    return map[type] || '图层'
}

export function getFriendlyLayerLabel(layer: DesignLayer): string {
    const id = String(layer?.id || '')
    let base = ''
    for (const [pattern, label] of LAYER_LABEL_RULES) {
        if (pattern.test(id)) {
            base = label
            break
        }
    }
    if (!base) {
        base = layer?.type === 'repeater' && layer.source === 'top' ? '单王卡片' : getLayerTypeLabel(String(layer?.type || ''))
    }
    if (layer?.type === 'text') {
        const summary = plainSummary(String(layer.text || ''))
        if (summary) {
            return `${base} · ${summary}`
        }
    }
    return base
}

// ---------------------------------------------------------------------------
// 风格预设套用
// ---------------------------------------------------------------------------

const COPY_ROLE_PATTERNS: Array<{ pattern: RegExp; guard?: RegExp }> = [
    { pattern: /qrcode-note/ },
    { pattern: /brand/ },
    { pattern: /slogan/ },
    { pattern: /contact/ },
    { pattern: /footer/, guard: /footer-line/ },
]

function findPreservedCopy(design: DesignConfig, rule: { pattern: RegExp; guard?: RegExp }): string {
    for (const layer of design.layers) {
        if (layer.type !== 'text') continue
        if (!rule.pattern.test(String(layer.id || ''))) continue
        if (rule.guard && rule.guard.test(String(layer.id || ''))) continue
        const text = String(layer.text || '').trim()
        if (text) return String(layer.text)
    }
    return ''
}

/** 套用风格预设；preserveCopy=true 时回填原设计中已修改的品牌/标语/联系/页脚/二维码提示文案 */
export function applyStylePreset(currentDesign: DesignConfig, preset: StylePreset, preserveCopy = true): DesignConfig {
    const next = normalizeDesign(cloneDesign(preset.design), preset.templateType)
    if (!preserveCopy) {
        return next
    }
    for (const layer of next.layers) {
        if (layer.type !== 'text') continue
        for (const rule of COPY_ROLE_PATTERNS) {
            if (!rule.pattern.test(String(layer.id || ''))) continue
            if (rule.guard && rule.guard.test(String(layer.id || ''))) continue
            const preserved = findPreservedCopy(currentDesign, rule)
            if (preserved) layer.text = preserved
            break
        }
    }
    return next
}

// ---------------------------------------------------------------------------
// 一键配色
// ---------------------------------------------------------------------------

function isLightColor(color: string): boolean {
    const hex = String(color || '').trim()
    const match = /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.exec(hex)
    if (!match) return false
    let raw = match[1]
    if (raw.length === 3) {
        raw = raw[0] + raw[0] + raw[1] + raw[1] + raw[2] + raw[2]
    }
    const r = parseInt(raw.slice(0, 2), 16)
    const g = parseInt(raw.slice(2, 4), 16)
    const b = parseInt(raw.slice(4, 6), 16)
    return (0.299 * r + 0.587 * g + 0.114 * b) / 255 >= 0.62
}

function isFrameRect(layer: DesignLayer): boolean {
    const fill = String(layer.fill || '')
    const strokeWidth = Number(layer.strokeWidth || 0)
    return strokeWidth > 0 && /^rgba\(\s*255\s*,\s*255\s*,\s*255\s*,\s*0\s*\)$/i.test(fill)
}

function isAccentRect(layer: DesignLayer, canvasWidth: number): boolean {
    const id = String(layer.id || '')
    if (/hero-block|hero-ribbon|nameplate|rank-badge|header-accent|eyebrow-pill|unit-pill|seal-box|medal-badge|title-bar|title-accent|month-pill/.test(id)) {
        return true
    }
    const width = Number(layer.w || 0)
    const height = Number(layer.h || 0)
    return width >= 600 && height >= 300 && width >= canvasWidth * 0.5 && !/paper|arch|bento|footer|header-band|header-banner|header-card|side-panel|lintel/.test(id)
}

function textCenterInsideRect(layer: DesignLayer, rect: DesignLayer): boolean {
    const cx = Number(layer.x || 0) + Number(layer.w || 0) / 2
    const cy = Number(layer.y || 0) + Number(layer.h || 0) / 2
    return (
        cx >= Number(rect.x || 0) &&
        cx <= Number(rect.x || 0) + Number(rect.w || 0) &&
        cy >= Number(rect.y || 0) &&
        cy <= Number(rect.y || 0) + Number(rect.h || 0)
    )
}

/** 协调替换画布背景、主题色块、装饰线、大字/强调色、主副文字及卡片遮罩色 */
export function applyColorPalette(
    design: DesignConfig,
    palette: ColorPalette,
    _templateType: MonthlyReportTemplateType,
): DesignConfig {
    const next = cloneDesign(design)
    const canvasWidth = Number(next.canvas.width || 1080)
    next.background.color = palette.bg

    const accentRects: DesignLayer[] = []
    const surfaceRects: DesignLayer[] = []
    for (const layer of next.layers) {
        if (layer.type === 'rect') {
            const id = String(layer.id || '')
            if (/mask|scrim/.test(id)) continue
            if (isFrameRect(layer)) {
                layer.stroke = palette.accent
                continue
            }
            if (/corner|v-sep|accent-bar/.test(id)) {
                layer.fill = /v-sep/.test(id) ? palette.line : palette.accent
                continue
            }
            if (isAccentRect(layer, canvasWidth)) {
                layer.fill = palette.accent
                if (layer.stroke) layer.stroke = palette.line
                accentRects.push(layer)
            } else {
                layer.fill = palette.surface
                if (layer.stroke) layer.stroke = palette.line
                surfaceRects.push(layer)
            }
        } else if (layer.type === 'line') {
            layer.stroke = palette.line
        }
    }

    const surfaceIsLight = isLightColor(palette.surface)
    const bgIsLight = isLightColor(palette.bg)

    for (const layer of next.layers) {
        if (layer.type !== 'repeater' || !Array.isArray(layer.card_layers)) continue
        for (const sub of layer.card_layers) {
            const sid = String(sub.id || '')
            if (/mask/.test(sid)) {
                if (sub.type === 'rect') sub.fill = palette.cardMask
                continue
            }
            if (sid === 'card-border' && isFrameRect(sub)) {
                sub.stroke = palette.accent
                continue
            }
            if (sid === 'rank-badge' && sub.type === 'rect') {
                sub.fill = palette.accent
                continue
            }
            if (sid === 'rank-text' && sub.type === 'text') {
                sub.color = palette.onAccent
                continue
            }
            if ((sid === 'card-bg' || sid === 'card-bottom') && sub.type === 'rect') {
                sub.fill = palette.surface
                if (sub.stroke) sub.stroke = palette.line
                continue
            }
            if (sub.type === 'rect') sub.fill = palette.surface
            if (sub.type === 'line') sub.stroke = palette.line
        }
        const hasSplitBottom = layer.card_layers.some((sub) => sub.id === 'card-bottom')
        const hasSplitRow = !hasSplitBottom && layer.card_layers.some((sub) => sub.id === 'card-bg')
        if (hasSplitRow) {
            for (const sub of layer.card_layers) {
                if (sub.id === 'card-bg' && sub.type === 'rect') {
                    sub.fill = palette.accent
                } else if (sub.type === 'text') {
                    sub.color = palette.onAccent
                }
            }
            continue
        }
        if (hasSplitBottom) {
            for (const sub of layer.card_layers) {
                if (sub.type !== 'text' || sub.id === 'rank-text') continue
                if (sub.id === 'count') {
                    sub.color = surfaceIsLight ? palette.accent : palette.textPrimary
                } else if (sub.id === 'name') {
                    sub.color = surfaceIsLight ? '#1F2937' : '#FFFFFF'
                } else if (sub.id === 'english') {
                    sub.color = surfaceIsLight ? '#64748B' : palette.textSecondary
                }
            }
        }
    }

    for (const layer of next.layers) {
        if (layer.type !== 'text' || layer.id === BG_SCRIM_LAYER_ID) continue
        const id = String(layer.id || '')
        if (/^watermark/.test(id) || Number(layer.opacity ?? 1) <= 0.2) {
            layer.color = palette.accent
            continue
        }
        if (layer.fillType === 'linear') {
            layer.gradientFrom = palette.accent
            layer.gradientTo = palette.textPrimary
            layer.color = palette.accent
            continue
        }
        const insideAccent = [...accentRects].reverse().find((rect) => textCenterInsideRect(layer, rect))
        if (insideAccent) {
            layer.color = palette.onAccent
            continue
        }
        const insideSurface = [...surfaceRects].reverse().find((rect) => textCenterInsideRect(layer, rect))
        if (insideSurface) {
            if (Number(layer.fontSize || 0) >= 80) {
                layer.color = surfaceIsLight && isLightColor(palette.accent) ? '#1F2937' : palette.accent
            } else if (/brand|eyebrow|title|names|unit|summary-label|award/.test(id)) {
                layer.color = surfaceIsLight ? '#1F2937' : '#F8FAFC'
            } else {
                layer.color = surfaceIsLight ? '#4B5563' : '#CBD5E1'
            }
            continue
        }
        if (/qrcode-note/.test(id)) {
            layer.color = bgIsLight ? '#2B2B2B' : palette.textSecondary
            continue
        }
        if (
            Number(layer.fontSize || 0) >= 64 ||
            /^(brand|eyebrow|title|top-title|thanks|contact-en|summary-count|count|executed-value|top-value|addition-value)$/.test(id)
        ) {
            layer.color = palette.accent
            continue
        }
        if (/names|award|contact-cn|subtitle|unit/.test(id)) {
            layer.color = palette.textPrimary
            continue
        }
        layer.color = palette.textSecondary
    }
    return next
}

// ---------------------------------------------------------------------------
// 画布比例
// ---------------------------------------------------------------------------

/** 调整画布比例：智能约束上下边界与二维码位置 */
export function applyCanvasRatio(
    design: DesignConfig,
    ratio: CanvasRatioPreset,
    templateType: MonthlyReportTemplateType,
): DesignConfig {
    const next = cloneDesign(design)
    next.canvas.width = clampInt(ratio.width, 320, CANVAS_MAX_WIDTH)
    if (templateType === 'ranking' && ratio.autoHeight) {
        next.canvas.auto_height = 1
        const minHeight = clampInt(next.canvas.min_height || 1920, 480, CANVAS_MAX_HEIGHT)
        next.canvas.min_height = minHeight
        next.canvas.height = clampInt(Math.max(minHeight, Number(next.canvas.height || minHeight)), 480, CANVAS_MAX_HEIGHT)
    } else {
        if (templateType === 'ranking') {
            next.canvas.auto_height = 0
            next.canvas.min_height = clampInt(next.canvas.min_height || 1920, 480, CANVAS_MAX_HEIGHT)
        }
        next.canvas.height = clampInt(ratio.height, 480, CANVAS_MAX_HEIGHT)
    }

    const width = Number(next.canvas.width)
    const height = Number(next.canvas.height)
    for (const layer of next.layers) {
        if (layer.type === 'repeater') continue
        if (layer.type === 'qrcode') {
            const side = Math.max(QRCODE_MIN_SIZE, Number(layer.w || QRCODE_MIN_SIZE))
            layer.w = side
            layer.h = side
        }
        const layerW = Number(layer.w || 0)
        const layerH = layer.type === 'line' ? Math.max(1, Number(layer.h || 0)) : Number(layer.h || 0)
        layer.x = clampInt(Number(layer.x || 0), 0, Math.max(0, width - layerW))
        layer.y = clampInt(Number(layer.y || 0), 0, Math.max(0, height - layerH))
    }
    return next
}

// ---------------------------------------------------------------------------
// 背景压暗遮罩（纯黑 rect，z = -100，避免渐变：契约要求 rect 仅纯色 + opacity）
// ---------------------------------------------------------------------------

export function getBgScrimOpacity(design: DesignConfig): number {
    const scrim = design.layers.find((layer) => layer.id === BG_SCRIM_LAYER_ID)
    if (!scrim || scrim.type !== 'rect') return 0
    return Math.round(clampFloat(Number(scrim.opacity ?? 0), 0, 0.75) * 100) / 100
}

export function setBgScrimOpacity(design: DesignConfig, opacity: number): DesignConfig {
    const next = cloneDesign(design)
    const value = Math.round(clampFloat(opacity, 0, 0.75) * 100) / 100
    const index = next.layers.findIndex((layer) => layer.id === BG_SCRIM_LAYER_ID)
    if (value <= 0.001) {
        if (index >= 0) next.layers.splice(index, 1)
        return next
    }
    const base = {
        id: BG_SCRIM_LAYER_ID,
        type: 'rect' as const,
        x: 0,
        y: 0,
        w: Number(next.canvas.width || 1080),
        h: Number(next.canvas.height || 1920),
        z: -100,
        visible: 1,
        locked: 1,
        opacity: value,
        rotate: 0,
        fill: '#000000',
        stroke: '',
        strokeWidth: 0,
        radius: 0,
    }
    if (index >= 0) {
        next.layers[index] = normalizeLayer({ ...next.layers[index], ...base })
    } else {
        next.layers.push(normalizeLayer({ ...base }))
    }
    return next
}

// ---------------------------------------------------------------------------
// 文案内容提取（按 y 升序）
// ---------------------------------------------------------------------------

export function extractTextSlots(design: DesignConfig): TextSlotItem[] {
    return design.layers
        .filter((layer) => {
            if (layer.type !== 'text') return false
            const id = String(layer.id || '')
            if (/^watermark/.test(id) || Number(layer.opacity ?? 1) <= 0.2) return false
            return true
        })
        .slice()
        .sort((a, b) => Number(a.y || 0) - Number(b.y || 0))
        .map((layer) => {
            const fontSize = Number(layer.fontSize || 42)
            const lineHeight = Number(layer.lineHeight || 1.2)
            const text = String(layer.text || '')
            const isHeading = fontSize >= 64 || /title/.test(String(layer.id || ''))
            const isFooter = /footer|qrcode-note/.test(String(layer.id || ''))
            const multiline = text.includes('\n') || Number(layer.h || 0) > Math.ceil(fontSize * lineHeight) + 24
            return {
                layerId: layer.id,
                label: getFriendlyLayerLabel(layer),
                placeholder: plainSummary(text) || '填写文案',
                multiline,
                category: isFooter ? 'footer' : isHeading ? 'heading' : 'body',
            }
        })
}

// ---------------------------------------------------------------------------
// 语义模块显隐
// ---------------------------------------------------------------------------

interface ModuleRule {
    key: string
    label: string
    desc: string
    /** 仅按图层 id 命中的模块需要提供；repeaterOnly / required 规则可省略 */
    pattern?: RegExp
    guard?: RegExp
    repeaterOnly?: boolean
    required?: boolean
}

const MODULE_RULES: ModuleRule[] = [
    { key: 'brand', label: '品牌抬头', desc: '顶部品牌名称与眉标', pattern: /brand|eyebrow/ },
    { key: 'titles', label: '标题区', desc: '主标题与副标题', pattern: /title|subtitle/ },
    { key: 'summary', label: '数据汇总', desc: '统计数据、标签与单位', pattern: /summary|executed-|top-value|addition-value|count|total|unit|month-label|month-en|year|names|award/ },
    { key: 'footer', label: '页脚信息', desc: '底部说明、标语与联系方式', pattern: /footer|contact|slogan|honor/ },
    { key: 'deco', label: '装饰元素', desc: '分割线、画框与装饰线', pattern: /line|frame|ornament|deco/ },
    { key: 'staffs', label: '人员展示', desc: '执行榜 / 单王人员卡片', repeaterOnly: true },
    { key: 'qrcode', label: '二维码', desc: '必留模块，不可关闭', pattern: /^$/, required: true },
]

export function extractToggleModules(design: DesignConfig): ToggleModuleItem[] {
    const modules: ToggleModuleItem[] = []
    for (const rule of MODULE_RULES) {
        const layerIds = design.layers
            .filter((layer) => {
                if (rule.repeaterOnly) return layer.type === 'repeater'
                if (rule.key === 'qrcode') return layer.type === 'qrcode'
                if (layer.type === 'qrcode') return false
                if (!rule.pattern || !rule.pattern.test(String(layer.id || ''))) return false
                if (rule.guard && rule.guard.test(String(layer.id || ''))) return false
                return true
            })
            .map((layer) => layer.id)
        if (!layerIds.length) continue
        modules.push({
            key: rule.key,
            label: rule.label,
            desc: rule.desc,
            layerIds,
            visible: design.layers.filter((layer) => layerIds.includes(layer.id)).every(isLayerVisible),
            required: rule.required,
        })
    }
    return modules
}

export function toggleModuleVisibility(design: DesignConfig, layerIds: string[], visible: boolean): DesignConfig {
    const next = cloneDesign(design)
    for (const layer of next.layers) {
        if (!layerIds.includes(layer.id)) continue
        if (layer.type === 'qrcode') {
            layer.visible = 1
            continue
        }
        layer.visible = visible ? 1 : 0
    }
    return next
}

// ---------------------------------------------------------------------------
// 二维码位置与尺寸
// ---------------------------------------------------------------------------

export function applyQrcodePlacement(
    design: DesignConfig,
    placement: 'bottom-center' | 'bottom-right' | 'bottom-left',
    size: number,
): DesignConfig {
    const next = cloneDesign(design)
    const qr = next.layers.find((layer) => layer.type === 'qrcode')
    if (!qr) return next
    const side = Math.max(QRCODE_MIN_SIZE, Math.round(Number(size) || QRCODE_MIN_SIZE))
    const width = Number(next.canvas.width || 1080)
    const height = Number(next.canvas.height || 1920)
    const margin = 70
    const bottomGap = 150
    qr.w = side
    qr.h = side
    qr.visible = 1
    qr.opacity = 1
    if (placement === 'bottom-left') {
        qr.x = margin
    } else if (placement === 'bottom-right') {
        qr.x = width - side - margin
    } else {
        qr.x = Math.round((width - side) / 2)
    }
    qr.y = Math.max(0, height - side - bottomGap)
    const note = next.layers.find((layer) => layer.type === 'text' && /qrcode-note/.test(String(layer.id || '')))
    if (note) {
        note.y = Math.max(0, Number(qr.y || 0) + side + 14)
        note.x = Math.round(Number(qr.x || 0) + side / 2 - Number(note.w || 250) / 2)
    }
    return next
}

export function getQrcodePlacementState(design: DesignConfig): QrcodePlacement {
    const qr = design.layers.find((layer) => layer.type === 'qrcode')
    if (!qr) return { placement: 'bottom-center', size: QRCODE_MIN_SIZE }
    const size = Math.max(QRCODE_MIN_SIZE, Number(qr.w || QRCODE_MIN_SIZE))
    const width = Number(design.canvas.width || 1080)
    const height = Number(design.canvas.height || 1920)
    const centerX = Number(qr.x || 0) + size / 2
    let placement: QrcodePlacement['placement'] = 'bottom-center'
    if (centerX < width / 3) placement = 'bottom-left'
    else if (centerX > (width * 2) / 3) placement = 'bottom-right'
    if (Number(qr.y || 0) < height / 2) placement = 'bottom-center'
    return { placement, size }
}

// ---------------------------------------------------------------------------
// 重点大字风格
// ---------------------------------------------------------------------------

const HERO_TARGET_PATTERNS: Record<MonthlyReportTemplateType, RegExp> = {
    addition: /count|title/,
    ranking: /title|summary-count/,
    top: /top-title|title/,
}

/** 将零基础主大字样式应用到画布上最大的标题/数字文本层 */
export function applyHeroArtStyle(
    design: DesignConfig,
    presetValue: string,
    templateType: MonthlyReportTemplateType,
): DesignConfig {
    const next = cloneDesign(design)
    const preset = HERO_ART_PRESETS.find((item) => item.value === presetValue) || HERO_ART_PRESETS[0]
    const all = next.layers.filter((layer) => layer.type === 'text' && isLayerVisible(layer))
    if (!all.length) return next
    const pattern = HERO_TARGET_PATTERNS[templateType]
    const preferred = all.filter((layer) => pattern.test(String(layer.id || '')))
    const pool = preferred.length ? preferred : all
    const target = pool.reduce((best, cur) => (Number(cur.fontSize || 0) > Number(best.fontSize || 0) ? cur : best))
    Object.assign(target, cloneDesign(preset.config))
    return next
}

// ---------------------------------------------------------------------------
// 执行榜网格布局（含自动高度基线联动）
// ---------------------------------------------------------------------------

const SPACING_MAP: Record<RankingGridState['spacing'], number> = {
    compact: 16,
    normal: 28,
    loose: 44,
}

function readSpacing(gapX: number): RankingGridState['spacing'] {
    if (gapX <= 18) return 'compact'
    if (gapX <= 40) return 'normal'
    return 'loose'
}

export function readRankingGridState(design: DesignConfig): RankingGridState | null {
    const repeater = findRepeater(design, 'ranking')
    if (!repeater) return null
    const photo = cardLayerById(repeater, 'photo')
    const mask = cardLayerById(repeater, 'card-mask')
    const radiusRaw = Number(photo?.radius || 0)
    const cardWidth = Number(repeater.card_width || 300)
    const cardHeight = Number(repeater.card_height || 300)
    return {
        columns: clampInt(Number(repeater.columns || 3), 2, 4) as RankingGridState['columns'],
        spacing: readSpacing(Number(repeater.gap_x || 0)),
        radius: (radiusRaw >= 24 ? 32 : radiusRaw >= 8 ? 16 : 0) as RankingGridState['radius'],
        cardAspect: cardHeight > cardWidth * 1.15 ? 'portrait' : 'square',
        showMask: mask ? isLayerVisible(mask) : true,
        showEnglish: (() => {
            const sub = cardLayerById(repeater, 'english')
            return sub ? isLayerVisible(sub) : true
        })(),
        showChinese: (() => {
            const sub = cardLayerById(repeater, 'name')
            return sub ? isLayerVisible(sub) : true
        })(),
        showCount: (() => {
            const sub = cardLayerById(repeater, 'count')
            return sub ? isLayerVisible(sub) : true
        })(),
        limit: Number(repeater.limit || 0),
    }
}

/** 按当前卡片尺寸重排执行榜卡片子图层（自适应标准覆叠卡 / 拍立得分体卡 / 左图右文纯色横卡） */
function relayoutRankingCardLayers(repeater: DesignLayer): void {
    const cardWidth = Number(repeater.card_width || 300)
    const cardHeight = Number(repeater.card_height || 300)
    const radius = Number(cardLayerById(repeater, 'photo')?.radius || 0)
    const hasSplitBottom = Boolean(cardLayerById(repeater, 'card-bottom'))
    const hasSplitRow = !hasSplitBottom && Boolean(cardLayerById(repeater, 'card-bg'))
    const compact = cardWidth <= 290

    if (hasSplitRow) {
        const photoW = Math.round(cardWidth * (compact ? 0.48 : 0.44))
        const photoPadTop = compact ? 12 : 16
        const textX = photoW - 4
        const textW = Math.max(60, cardWidth - textX - (compact ? 14 : 20))
        const engFs = compact ? 15 : 20
        const nameFs = compact ? 20 : 26
        const countFs = compact ? 48 : 66
        const countTop = Math.min(cardHeight - 76, compact ? 84 : 104)
        for (const sub of repeater.card_layers || []) {
            if (sub.id === 'card-bg') {
                sub.x = 0
                sub.y = 0
                sub.w = cardWidth
                sub.h = cardHeight
                sub.radius = radius
            } else if (sub.id === 'photo') {
                sub.x = 0
                sub.y = photoPadTop
                sub.w = photoW
                sub.h = Math.max(40, cardHeight - photoPadTop)
                sub.radius = radius
            } else if (sub.id === 'english') {
                sub.x = textX
                sub.y = compact ? 14 : 20
                sub.w = textW
                sub.h = 38
                sub.fontSize = engFs
            } else if (sub.id === 'name') {
                sub.x = textX
                sub.y = compact ? 42 : 56
                sub.w = textW
                sub.h = 44
                sub.fontSize = nameFs
            } else if (sub.id === 'count') {
                sub.x = textX
                sub.y = Math.max(68, countTop)
                sub.w = textW
                sub.h = Math.max(68, cardHeight - Math.max(68, countTop) - 4)
                sub.fontSize = countFs
            }
        }
        return
    }

    if (hasSplitBottom) {
        const pad = compact ? 10 : 14
        const bottomH = clampInt(Math.round(cardHeight * 0.27), 96, 154)
        const photoH = Math.max(100, cardHeight - bottomH - pad * 2 - 6)
        const bottomY = pad + photoH + 6
        const countW = Math.round(cardWidth * 0.34)
        const nameW = Math.max(60, cardWidth - pad * 2 - countW - 12)
        for (const sub of repeater.card_layers || []) {
            if (sub.id === 'card-bg') {
                sub.x = 0
                sub.y = 0
                sub.w = cardWidth
                sub.h = cardHeight
                sub.radius = Math.max(12, radius)
            } else if (sub.id === 'photo') {
                sub.x = pad
                sub.y = pad
                sub.w = cardWidth - pad * 2
                sub.h = photoH
                sub.radius = Math.max(8, radius - 4)
            } else if (sub.id === 'card-bottom') {
                sub.x = pad
                sub.y = bottomY
                sub.w = cardWidth - pad * 2
                sub.h = bottomH
            } else if (sub.id === 'rank-badge' || sub.id === 'rank-text') {
                sub.x = pad + 12
                sub.y = pad + 12
                sub.w = compact ? 74 : 96
                sub.h = compact ? 32 : 40
            } else if (sub.id === 'english') {
                sub.x = pad + 8
                sub.y = bottomY + 8
                sub.w = nameW
                sub.h = 40
                sub.fontSize = compact ? 16 : 21
            } else if (sub.id === 'name') {
                sub.x = pad + 8
                sub.y = bottomY + 46
                sub.w = nameW
                sub.h = 52
                sub.fontSize = compact ? 22 : 28
            } else if (sub.id === 'count') {
                sub.x = cardWidth - pad - countW - 8
                sub.y = bottomY + 14
                sub.w = countW
                sub.h = Math.max(60, bottomH - 20)
                sub.fontSize = compact ? 46 : 64
            }
        }
        return
    }

    const maskHeight = Math.min(200, Math.max(112, Math.round(cardHeight * 0.42)))
    const maskY = cardHeight - maskHeight
    const hasBottomRightCount = Boolean(cardLayerById(repeater, 'rank-badge'))
    for (const sub of repeater.card_layers || []) {
        if (sub.id === 'photo') {
            sub.x = 0
            sub.y = 0
            sub.w = cardWidth
            sub.h = cardHeight
            sub.radius = radius
        } else if (sub.id === 'card-border') {
            sub.x = 0
            sub.y = 0
            sub.w = cardWidth
            sub.h = cardHeight
            sub.radius = radius
        } else if (sub.id === 'card-mask') {
            sub.x = 0
            sub.y = maskY
            sub.w = cardWidth
            sub.h = maskHeight
            sub.radius = radius
        } else if (sub.id === 'rank-badge' || sub.id === 'rank-text') {
            sub.x = compact ? 12 : 16
            sub.y = compact ? 12 : 16
            sub.w = compact ? 64 : 84
            sub.h = compact ? 30 : 38
            if (sub.id === 'rank-text') sub.fontSize = compact ? 18 : 22
        } else if (sub.id === 'english') {
            sub.x = compact ? 14 : 18
            sub.y = maskY + (compact ? 12 : 16)
            sub.w = hasBottomRightCount ? Math.max(60, Math.round(cardWidth * 0.56)) : Math.max(40, cardWidth - 36)
            sub.h = 44
            sub.fontSize = compact ? 16 : 21
        } else if (sub.id === 'name') {
            sub.x = compact ? 14 : 18
            sub.y = maskY + (compact ? 46 : 58)
            sub.w = hasBottomRightCount ? Math.max(60, Math.round(cardWidth * 0.56)) : Math.max(40, cardWidth - 36)
            sub.h = 52
            sub.fontSize = compact ? 22 : 28
        } else if (sub.id === 'count') {
            if (hasBottomRightCount) {
                const cw = Math.max(60, Math.round(cardWidth * 0.34))
                sub.x = cardWidth - cw - (compact ? 12 : 18)
                sub.y = maskY + (compact ? 22 : 34)
                sub.w = cw
                sub.h = 88
                sub.fontSize = compact ? 48 : 64
                sub.align = 'right'
            } else {
                sub.x = compact ? 14 : 18
                sub.y = 14
                sub.w = 150
                sub.h = 100
                sub.fontSize = compact ? 50 : 64
                sub.align = 'left'
            }
        }
    }
}

/**
 * 更新执行榜网格：列数 / 间距 / 圆角 / 方卡竖卡 / 遮罩 / 子层显隐 / 人数上限。
 * 调整后按 delta = newBaseBottom - oldBaseBottom 平移底部图层，并同步 canvas.height
 * （上限 5200，下限 min_height || 1920），保证底部汇总/品牌/二维码绝不与卡片重叠。
 */
export function updateRankingGridLayout(design: DesignConfig, patch: Partial<RankingGridState>): DesignConfig {
    const next = cloneDesign(design)
    const repeater = findRepeater(next, 'ranking')
    if (!repeater) return next

    const canvasWidth = Number(next.canvas.width || 1080)
    const minHeight = clampInt(next.canvas.min_height || 1920, 480, CANVAS_MAX_HEIGHT)

    const oldColumns = Math.max(1, Number(repeater.columns || 3))
    const oldCardHeight = Number(repeater.card_height || 300)
    const oldGapY = Number(repeater.gap_y || 24)
    const oldLimit = Number(repeater.limit || 0)
    const oldRows = Math.max(1, Math.ceil((oldLimit || RANKING_ESTIMATED_COUNT) / oldColumns))
    const oldBaseBottom = Number(repeater.y || 0) + oldRows * oldCardHeight + Math.max(0, oldRows - 1) * oldGapY

    const columns = clampInt(patch.columns ? Number(patch.columns) : oldColumns, 1, 6)
    const preserveZeroGap = Number(repeater.gap_x || 0) === 0 && Number(repeater.x || 0) === 0 && patch.spacing == null
    const spacing: RankingGridState['spacing'] = patch.spacing ?? readSpacing(Number(repeater.gap_x || 24))
    const gap = preserveZeroGap ? 0 : SPACING_MAP[spacing]
    const hasSplitRow = !cardLayerById(repeater, 'card-bottom') && Boolean(cardLayerById(repeater, 'card-bg'))
    const cardAspect: RankingGridState['cardAspect'] = patch.cardAspect ?? readRankingGridState(design)?.cardAspect ?? 'square'

    const structureChanged = patch.columns != null || patch.spacing != null || patch.cardAspect != null
    let cardWidth = Number(repeater.card_width || 300)
    let cardHeight = Number(repeater.card_height || 300)
    if (structureChanged) {
        const margin = gap === 0 ? 0 : 36
        cardWidth = Math.max(120, Math.min(560, Math.floor((canvasWidth - margin * 2 - (columns - 1) * gap) / columns)))
        if (hasSplitRow) {
            cardHeight = cardAspect === 'portrait' ? Math.round(cardWidth * 0.95) : Math.round(cardWidth * 0.68)
        } else {
            cardHeight = cardAspect === 'portrait' ? Math.round(cardWidth * 1.4) : cardWidth
        }
    }

    const limit = clampInt(patch.limit != null ? Number(patch.limit) : oldLimit, 0, 120)
    const rowsForLimit = Math.max(1, Math.ceil((limit || RANKING_ESTIMATED_COUNT) / columns))
    const oldHeight = Number(next.canvas.height || minHeight)
    const hasBottomLayers = next.layers.some((layer) => layer.id !== repeater.id && Number(layer.y || 0) >= oldBaseBottom)
    const oldFooterSpan = hasBottomLayers ? Math.max(360, oldHeight - oldBaseBottom) : 0
    const maxRepeaterBottom = CANVAS_MAX_HEIGHT - oldFooterSpan
    const maxAllowedCardH = Math.max(120, Math.floor((maxRepeaterBottom - Number(repeater.y || 0) - Math.max(0, rowsForLimit - 1) * gap) / rowsForLimit))
    if (cardHeight > maxAllowedCardH) {
        cardHeight = maxAllowedCardH
        if (!hasSplitRow && cardAspect === 'square') {
            cardWidth = Math.max(120, cardHeight)
        }
    }

    repeater.columns = columns
    repeater.card_width = cardWidth
    repeater.card_height = cardHeight
    repeater.gap_x = gap
    repeater.gap_y = gap
    repeater.limit = limit
    repeater.w = columns * cardWidth + Math.max(0, columns - 1) * gap
    repeater.x = clampInt(Math.round((canvasWidth - repeater.w) / 2), 0, Math.max(0, canvasWidth - repeater.w))

    if (patch.radius != null) {
        const radius = clampInt(Number(patch.radius), 0, 260)
        for (const sub of repeater.card_layers || []) {
            if (sub.id === 'photo' || sub.id === 'card-mask' || sub.id === 'card-bg' || sub.id === 'card-border') {
                sub.radius = radius
            }
        }
    }
    if (patch.showMask != null) {
        const mask = cardLayerById(repeater, 'card-mask')
        if (mask) mask.visible = patch.showMask ? 1 : 0
    }
    if (patch.showEnglish != null) {
        const sub = cardLayerById(repeater, 'english')
        if (sub) sub.visible = patch.showEnglish ? 1 : 0
    }
    if (patch.showChinese != null) {
        const sub = cardLayerById(repeater, 'name')
        if (sub) sub.visible = patch.showChinese ? 1 : 0
    }
    if (patch.showCount != null) {
        const sub = cardLayerById(repeater, 'count')
        if (sub) sub.visible = patch.showCount ? 1 : 0
    }
    relayoutRankingCardLayers(repeater)

    const newRows = Math.max(1, Math.ceil((limit || RANKING_ESTIMATED_COUNT) / columns))
    const newBaseBottom = Number(repeater.y || 0) + newRows * cardHeight + Math.max(0, newRows - 1) * gap
    const delta = newBaseBottom - oldBaseBottom
    if (delta !== 0) {
        for (const layer of next.layers) {
            if (layer.id === repeater.id) continue
            if (Number(layer.y || 0) >= oldBaseBottom) {
                layer.y = clampInt(Number(layer.y || 0) + delta, -5200, 10400)
            }
        }
        next.canvas.height = clampInt(oldHeight + delta, Math.max(minHeight, 1920), CANVAS_MAX_HEIGHT)
    }
    return next
}

// ---------------------------------------------------------------------------
// 单王版式
// ---------------------------------------------------------------------------

export function readTopLayoutState(design: DesignConfig): TopLayoutState | null {
    const repeater = findRepeater(design, 'top')
    if (!repeater) return null
    const photo = cardLayerById(repeater, 'photo')
    const hero = design.layers.find((layer) => layer.id === 'hero-block' && layer.type === 'rect')
    return {
        limit: clampInt(Number(repeater.limit || 1), 1, 3) as TopLayoutState['limit'],
        fit: photo?.fit === 'cover' ? 'cover' : 'contain',
        radius: Number(photo?.radius || 0),
        showHeroBlock: hero ? isLayerVisible(hero) : false,
    }
}

export function updateTopStaffLayout(design: DesignConfig, patch: Partial<TopLayoutState>): DesignConfig {
    const next = cloneDesign(design)
    const repeater = findRepeater(next, 'top')
    if (!repeater) return next
    if (patch.limit != null) {
        repeater.limit = clampInt(Number(patch.limit), 1, 3)
    }
    for (const sub of repeater.card_layers || []) {
        if (sub.id !== 'photo') continue
        if (patch.fit) sub.fit = patch.fit
        if (patch.radius != null) sub.radius = clampInt(Number(patch.radius), 0, 260)
    }
    if (patch.showHeroBlock != null) {
        const hero = next.layers.find((layer) => layer.id === 'hero-block' && layer.type === 'rect')
        if (hero) {
            hero.visible = patch.showHeroBlock ? 1 : 0
        } else if (patch.showHeroBlock) {
            const columns = Math.max(1, Number(repeater.columns || 1))
            const cardWidth = Number(repeater.card_width || 300)
            const cardHeight = Number(repeater.card_height || 300)
            const gapX = Number(repeater.gap_x || 0)
            const boxWidth = columns * cardWidth + Math.max(0, columns - 1) * gapX
            next.layers.push(
                normalizeLayer({
                    id: 'hero-block',
                    type: 'rect',
                    x: Math.max(0, Number(repeater.x || 0) - 40),
                    y: Math.max(0, Number(repeater.y || 0) - 40),
                    w: boxWidth + 80,
                    h: repeaterBoxHeight(repeater) + 80,
                    z: Math.max(-999, Number(repeater.z || 0) - 1),
                    visible: 1,
                    locked: 0,
                    opacity: 0.35,
                    rotate: 0,
                    fill: '#0F172A',
                    stroke: '',
                    strokeWidth: 0,
                    radius: 24,
                }),
            )
        }
    }
    return next
}

// ---------------------------------------------------------------------------
// 体检与自动修复
// ---------------------------------------------------------------------------

function textNeededHeight(layer: DesignLayer): number {
    const wrapped = wrapPosterText(renderSampleText(String(layer.text || '')), layer)
    const lines = Math.max(1, wrapped.split('\n').length)
    const fontSize = Math.max(10, Number(layer.fontSize || 42))
    const lineHeight = Math.max(0.8, Number(layer.lineHeight || 1.2))
    return Math.ceil(lines * fontSize * lineHeight) + 20
}

/** 依据后端渲染契约做设计体检：文本截断 / 出界 / 二维码缺失或过小 */
export function runDesignHealthCheck(design: DesignConfig): HealthCheckIssue[] {
    const issues: HealthCheckIssue[] = []
    const width = Number(design.canvas.width || 1080)
    const height = Number(design.canvas.height || 1920)

    for (const layer of design.layers) {
        if (!isLayerVisible(layer)) continue
        const label = getFriendlyLayerLabel(layer)
        if (layer.type === 'text') {
            const needed = textNeededHeight(layer)
            if (Number(layer.h || 0) + 0.5 < needed) {
                issues.push({
                    level: 'warning',
                    message: `「${label}」文本框高度不足，文字可能被截断`,
                    fixAction: 'center-overflow',
                })
            }
        }
        if (layer.type !== 'repeater') {
            const outOfBounds =
                Number(layer.x || 0) < -2 ||
                Number(layer.y || 0) < -2 ||
                Number(layer.x || 0) + Number(layer.w || 0) > width + 2 ||
                Number(layer.y || 0) + (layer.type === 'line' ? Math.max(1, Number(layer.h || 0)) : Number(layer.h || 0)) > height + 2
            if (outOfBounds) {
                issues.push({
                    level: 'warning',
                    message: `「${label}」超出画布边界`,
                    fixAction: 'center-overflow',
                })
            }
        }
    }

    const qr = design.layers.find((layer) => layer.type === 'qrcode')
    if (!qr) {
        issues.push({ level: 'warning', message: '设计中缺少二维码，发布前需要补上', fixAction: 'ensure-qrcode' })
    } else {
        const side = Math.max(Number(qr.w || 0), Number(qr.h || 0))
        if (side < QRCODE_MIN_SIZE) {
            issues.push({ level: 'warning', message: '二维码尺寸过小，扫码成功率低', fixAction: 'ensure-qrcode' })
        }
        if (
            Number(qr.x || 0) < -2 ||
            Number(qr.y || 0) < -2 ||
            Number(qr.x || 0) + Number(qr.w || 0) > width + 2 ||
            Number(qr.y || 0) + Number(qr.h || 0) > height + 2
        ) {
            issues.push({ level: 'warning', message: '二维码超出画布边界', fixAction: 'ensure-qrcode' })
        }
    }

    return issues
}

/** 一键规范修复：补齐文本高度、收回出界图层、确保二维码强留规则 */
export function autoFixLayout(design: DesignConfig, _templateType: MonthlyReportTemplateType): DesignConfig {
    const next = cloneDesign(design)
    const width = Number(next.canvas.width || 1080)
    const height = Number(next.canvas.height || 1920)

    for (const layer of next.layers) {
        if (layer.type === 'repeater' || !isLayerVisible(layer)) continue
        if (layer.type === 'text') {
            const needed = textNeededHeight(layer)
            if (Number(layer.h || 0) < needed) layer.h = needed
        }
        layer.x = clampInt(Number(layer.x || 0), 0, Math.max(0, width - Number(layer.w || 0)))
        layer.y = clampInt(Number(layer.y || 0), 0, Math.max(0, height - Math.max(1, Number(layer.h || 0))))
    }

    let qr = next.layers.find((layer) => layer.type === 'qrcode')
    if (!qr) {
        qr = normalizeLayer(
            defaultQrcode(Math.max(0, width - QRCODE_MIN_SIZE - 70), Math.max(0, height - QRCODE_MIN_SIZE - 150), QRCODE_MIN_SIZE, QRCODE_MIN_SIZE, nextZ(next.layers)),
        )
        next.layers.push(qr)
    } else {
        qr.w = Math.max(QRCODE_MIN_SIZE, Number(qr.w || QRCODE_MIN_SIZE))
        qr.h = Math.max(QRCODE_MIN_SIZE, Number(qr.h || QRCODE_MIN_SIZE))
        qr.x = clampInt(Number(qr.x || 0), 0, Math.max(0, width - Number(qr.w || 0)))
        qr.y = clampInt(Number(qr.y || 0), 0, Math.max(0, height - Number(qr.h || 0)))
        qr.visible = 1
        qr.opacity = 1
    }
    return next
}

// ---------------------------------------------------------------------------
// 供面板使用的其他小工具
// ---------------------------------------------------------------------------

/** 判断当前设计是否与某个预设完全一致（用于选中态展示） */
export function isSameDesign(a: DesignConfig, b: DesignConfig, templateType: MonthlyReportTemplateType): boolean {
    return JSON.stringify(normalizeDesign(a, templateType)) === JSON.stringify(normalizeDesign(b, templateType))
}

/** 推荐取色圆点（来自 8 套配色背景色） */
export function recommendedBackgroundColors(): string[] {
    return COLOR_PALETTES.map((palette) => palette.bg)
}
