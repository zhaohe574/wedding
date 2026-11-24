// 单量月报设计器共享类型定义
// 渲染契约对齐 server/app/common/service/MonthlyReportService.php 与 MonthlyReportRenderer.php

export type MonthlyReportTemplateType = 'addition' | 'ranking' | 'top'

export type DesignerMode = 'simple' | 'pro'

export type SimplePanelTab = 'style' | 'theme' | 'content'

export type ProPanelTab = 'props' | 'layers' | 'canvas'

export type DesignLayerType = 'text' | 'image' | 'qrcode' | 'rect' | 'line' | 'repeater'

export interface DesignCanvas {
    width: number
    height: number
    /** 仅 ranking 模板：1 = 按人数自动拉长画布 */
    auto_height?: number
    /** 仅 ranking 模板：自动高度下限 */
    min_height?: number
    [key: string]: any
}

export interface DesignBackground {
    type: 'color' | 'image'
    color: string
    image: string
    image_url?: string
    fit: 'cover' | 'contain'
    opacity: number
    [key: string]: any
}

export interface DesignLayer {
    id: string
    type: DesignLayerType
    x: number
    y: number
    w: number
    h: number
    z: number
    visible: number
    locked: number
    opacity: number
    rotate: number
    // text
    text?: string
    fontSize?: number
    fontWeight?: string
    lineHeight?: number
    align?: string
    color?: string
    scaleX?: number
    scaleY?: number
    letterSpacing?: number
    fillType?: 'solid' | 'linear' | 'image'
    gradientFrom?: string
    gradientTo?: string
    gradientAngle?: number
    fillImage?: string
    fillImageUrl?: string
    fillImageFit?: 'cover' | 'contain' | 'stretch'
    textStrokeColor?: string
    textStrokeWidth?: number
    textStrokeOpacity?: number
    shadowColor?: string
    shadowBlur?: number
    shadowOffsetX?: number
    shadowOffsetY?: number
    shadowOpacity?: number
    artPreset?: string
    // image / qrcode
    src?: string
    src_url?: string
    fit?: 'cover' | 'contain'
    backgroundFill?: string
    radius?: number
    padding?: number
    // rect
    fill?: string
    stroke?: string
    strokeWidth?: number
    // repeater
    source?: 'ranking' | 'top'
    columns?: number
    card_width?: number
    card_height?: number
    gap_x?: number
    gap_y?: number
    limit?: number
    card_layers?: DesignLayer[]
    [key: string]: any
}

export interface DesignConfig {
    canvas: DesignCanvas
    background: DesignBackground
    layers: DesignLayer[]
}

export interface StylePreset {
    id: string
    name: string
    tag: string
    description: string
    templateType: MonthlyReportTemplateType
    palettePreview: [string, string, string]
    design: DesignConfig
}

export interface ColorPalette {
    id: string
    name: string
    bg: string
    surface: string
    accent: string
    textPrimary: string
    textSecondary: string
    onAccent: string
    line: string
    cardMask: string
}

export interface CanvasRatioPreset {
    id: string
    label: string
    desc: string
    width: number
    height: number
    /** 执行榜自动长图：跟随人数拉长，仅 ranking 可用 */
    autoHeight?: boolean
}

export interface SnapGuide {
    orientation: 'vertical' | 'horizontal'
    position: number
}

export interface DynamicFieldOption {
    label: string
    token: string
    desc?: string
}

export interface TextSlotItem {
    layerId: string
    label: string
    placeholder: string
    multiline: boolean
    category: 'heading' | 'body' | 'footer'
}

export interface ToggleModuleItem {
    key: string
    label: string
    desc: string
    layerIds: string[]
    visible: boolean
    /** 二维码等必留模块不可关闭 */
    required?: boolean
}

export interface RankingGridState {
    columns: 2 | 3 | 4
    spacing: 'compact' | 'normal' | 'loose'
    radius: 0 | 16 | 32
    cardAspect: 'square' | 'portrait'
    showMask: boolean
    showEnglish: boolean
    showChinese: boolean
    showCount: boolean
    limit: number
}

export interface TopLayoutState {
    limit: 1 | 2 | 3
    fit: 'contain' | 'cover'
    radius: number
    showHeroBlock: boolean
}

export interface HealthCheckIssue {
    level: 'warning' | 'info'
    message: string
    fixAction?: 'center-overflow' | 'ensure-qrcode'
}

export interface HeroArtPreset {
    label: string
    value: string
    description: string
    config: Record<string, any>
}

export interface QrcodePlacement {
    placement: 'bottom-center' | 'bottom-right' | 'bottom-left'
    size: number
}

export interface LayerGeometryPayload {
    layerId: string
    x: number
    y: number
    w?: number
    h?: number
}
