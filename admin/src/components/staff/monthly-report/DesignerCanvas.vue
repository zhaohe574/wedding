<template>
    <div class="mdc">
        <div class="mdc__viewport">
            <div class="mdc__canvas" :style="canvasStyle" @mousedown="handleCanvasMousedown">
                <img
                    v-if="backgroundImage"
                    class="mdc__background-image"
                    :src="backgroundImage"
                    :style="{ objectFit: design.background.fit === 'contain' ? 'contain' : 'cover', opacity: Number(design.background.opacity ?? 1) }"
                    alt=""
                    draggable="false"
                    @dragstart.prevent
                />
                <div
                    v-for="layer in sortedLayers"
                    :key="layer.id"
                    :class="['mdc-layer', `mdc-layer--${layer.type}`, { 'is-active': layer.id === activeLayerId, 'is-hover': hoverLayerId === layer.id, 'is-hidden': Number(layer.visible ?? 1) !== 1, 'is-draggable': canInteract(layer) }]"
                    :style="layerBoxStyle(layer)"
                    @mousedown.stop="startLayerDrag(layer, $event)"
                    @mouseenter="hoverLayerId = layer.id"
                    @mouseleave="hoverLayerId = hoverLayerId === layer.id ? '' : hoverLayerId"
                >
                    <template v-if="layer.type === 'text'">
                        <div class="mdc-layer__text" :style="textPreviewStyle(layer)">{{ renderLayerText(layer) }}</div>
                    </template>
                    <template v-else-if="layer.type === 'image'">
                        <div class="mdc-layer__image" :style="imagePreviewBoxStyle(layer)">
                            <img v-if="layerImageSrc(layer)" :src="layerImageSrc(layer)" :style="imagePreviewStyle(layer)" alt="" draggable="false" @dragstart.prevent />
                            <span v-else>图片</span>
                        </div>
                    </template>
                    <template v-else-if="layer.type === 'qrcode'">
                        <div class="mdc-layer__qr" :style="{ borderRadius: `${Number(layer.radius || 0) * canvasScale}px` }">
                            <img v-if="qrcodeSrc(layer)" :src="qrcodeSrc(layer)" alt="" draggable="false" @dragstart.prevent />
                            <span v-else>QR</span>
                        </div>
                    </template>
                    <template v-else-if="layer.type === 'rect'">
                        <div class="mdc-layer__rect" :style="rectPreviewStyle(layer)" />
                    </template>
                    <template v-else-if="layer.type === 'line'">
                        <div class="mdc-layer__line" :style="linePreviewStyle(layer)" />
                    </template>
                    <template v-else>
                        <div class="mdc-repeater" :style="repeaterPreviewStyle(layer)">
                            <div
                                v-for="(item, index) in repeaterItems(layer)"
                                :key="`${layer.id}_${index}`"
                                class="mdc-repeater__card"
                                :style="repeaterCardStyle(layer, index)"
                            >
                                <div
                                    v-for="cardLayer in normalizedCardLayers(layer)"
                                    :key="cardLayer.id"
                                    :class="['mdc-card-layer', `mdc-card-layer--${cardLayer.type}`]"
                                    :style="cardLayerStyle(cardLayer)"
                                >
                                    <template v-if="cardLayer.type === 'text'">
                                        <div class="mdc-card-layer__text" :style="textPreviewStyle(cardLayer)">{{ renderCardText(cardLayer, item) }}</div>
                                    </template>
                                    <template v-else-if="cardLayer.type === 'image'">
                                        <div class="mdc-card-layer__image" :style="imagePreviewBoxStyle(cardLayer)">
                                            <img v-if="cardImageSrc(cardLayer, item)" :src="cardImageSrc(cardLayer, item)" :style="imagePreviewStyle(cardLayer)" alt="" draggable="false" @dragstart.prevent />
                                        </div>
                                    </template>
                                    <template v-else-if="cardLayer.type === 'rect'">
                                        <div class="mdc-card-layer__rect" :style="rectPreviewStyle(cardLayer)" />
                                    </template>
                                    <template v-else>
                                        <div class="mdc-card-layer__line" :style="linePreviewStyle(cardLayer)" />
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <span
                        v-if="hoverLayerId === layer.id || layer.id === activeLayerId"
                        class="mdc-layer__badge"
                        :class="{ 'mdc-layer__badge--active': layer.id === activeLayerId }"
                    >
                        {{ getFriendlyLayerLabel(layer) }}
                    </span>
                    <span
                        v-if="layer.id === activeLayerId && canInteract(layer) && layer.type !== 'repeater'"
                        class="mdc-layer__resize-handle"
                        @mousedown.stop="startResize(layer, $event)"
                    />
                </div>

                <template v-if="snapGuides.length">
                    <div
                        v-for="(guide, index) in snapGuides"
                        :key="`snap_${index}`"
                        class="mdc__snap-guide"
                        :class="`mdc__snap-guide--${guide.orientation}`"
                        :style="snapGuideStyle(guide)"
                    />
                </template>
            </div>
        </div>

        <div class="mdc__footer">
            <span class="mdc__size">画布尺寸：{{ canvasWidth }} × {{ canvasHeight }} px</span>
            <span class="mdc__tips">{{ modeTips }}</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { CSSProperties } from 'vue'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import { buildPosterTextPreviewStyle, wrapPosterText } from '../poster-designer-common'

import {
    defaultCardLayers,
    normalizeUrl,
    QRCODE_MIN_SIZE,
} from './presets'
import { getFriendlyLayerLabel, renderSampleText } from './simple-adapter'
import type {
    DesignConfig,
    DesignLayer,
    DesignerMode,
    LayerGeometryPayload,
    MonthlyReportTemplateType,
    SnapGuide,
} from './types'

interface DragState {
    layerId: string
    startX: number
    startY: number
    originX: number
    originY: number
    changed: boolean
}

interface ResizeState {
    layerId: string
    startX: number
    startY: number
    originW: number
    originH: number
    changed: boolean
}

const props = withDefaults(
    defineProps<{
        design: DesignConfig
        templateType: MonthlyReportTemplateType
        previewSnapshot?: Record<string, any>
        mode: DesignerMode
        activeLayerId: string
        zoom?: number
        baseWidth?: number
        allowSimpleDrag?: boolean
    }>(),
    {
        previewSnapshot: () => ({}),
        zoom: 100,
        baseWidth: 380,
        allowSimpleDrag: false,
    },
)

const emit = defineEmits<{
    (e: 'select-layer', layerId: string): void
    (e: 'clear-selection'): void
    (e: 'update-layer-geometry', payload: LayerGeometryPayload): void
    (e: 'commit-history'): void
    (e: 'delete-layer', layerId: string): void
}>()

const SNAP_THRESHOLD = 8
const SAFE_MARGIN = 36

const localDesign = ref<DesignConfig>(cloneDesign(props.design))
const dragging = ref<DragState | null>(null)
const resizing = ref<ResizeState | null>(null)
const snapGuides = ref<SnapGuide[]>([])
const hoverLayerId = ref('')

watch(
    () => props.design,
    (value) => {
        if (dragging.value || resizing.value) return
        localDesign.value = cloneDesign(value)
    },
    { deep: true },
)

function cloneDesign(value: DesignConfig): DesignConfig {
    return JSON.parse(JSON.stringify(value)) as DesignConfig
}

// ---------------------------------------------------------------------------
// 画布几何
// ---------------------------------------------------------------------------

const canvasWidth = computed(() => Number(localDesign.value?.canvas?.width || 1080))
const canvasHeight = computed(() => Number(localDesign.value?.canvas?.height || 1920))

const canvasScale = computed(() => (props.baseWidth * (props.zoom / 100)) / Math.max(1, canvasWidth.value))

const canvasStyle = computed<CSSProperties>(() => ({
    width: `${canvasWidth.value * canvasScale.value}px`,
    height: `${canvasHeight.value * canvasScale.value}px`,
    backgroundColor: localDesign.value?.background?.color || '#1B1510',
}))

const backgroundImage = computed(() => {
    if (localDesign.value?.background?.type !== 'image') return ''
    return normalizeUrl(localDesign.value?.background?.image_url || localDesign.value?.background?.image)
})

const sortedLayers = computed<DesignLayer[]>(() => {
    return [...(localDesign.value?.layers || [])].sort((a, b) => Number(a.z || 0) - Number(b.z || 0))
})

const modeTips = computed(() => {
    if (props.mode === 'pro') {
        return '拖拽移动 · 右下角手柄缩放 · 方向键微调（Shift ×10）· Delete 删除图层'
    }
    return props.allowSimpleDrag
        ? '点击选中元素后在右侧修改 · 拖拽调整位置 · 方向键微调（Shift ×10）'
        : '点击选中元素后在右侧修改，无需手动拖拽，避免误碰版式'
})

function findLayer(layerId: string): DesignLayer | null {
    return (localDesign.value?.layers || []).find((layer) => layer.id === layerId) || null
}

function canInteract(layer: DesignLayer): boolean {
    return (props.mode === 'pro' || props.allowSimpleDrag) && Number(layer.locked || 0) !== 1
}

function layerWidth(layer: DesignLayer): number {
    if (layer.type === 'repeater') {
        return Number(layer.columns || 1) * Number(layer.card_width || 300) + Math.max(0, Number(layer.columns || 1) - 1) * Number(layer.gap_x || 0)
    }
    return Number(layer.w || 120)
}

function repeaterRowHeight(layer: DesignLayer): number {
    const count = repeaterItems(layer).length || 1
    const rows = Math.max(1, Math.ceil(count / Math.max(1, Number(layer.columns || 1))))
    return rows * Number(layer.card_height || 300) + Math.max(0, rows - 1) * Number(layer.gap_y || 0)
}

function layerHeight(layer: DesignLayer): number {
    if (layer.type === 'repeater') {
        return repeaterRowHeight(layer)
    }
    return Number(layer.h ?? 80)
}

// ---------------------------------------------------------------------------
// 图层预览样式（与原 monthly-report-designer.vue 几何算法一致）
// ---------------------------------------------------------------------------

function layerBoxStyle(layer: DesignLayer): CSSProperties {
    const scale = canvasScale.value
    return {
        left: `${Number(layer.x || 0) * scale}px`,
        top: `${Number(layer.y || 0) * scale}px`,
        width: `${layerWidth(layer) * scale}px`,
        height: `${layerHeight(layer) * scale}px`,
        opacity: layer.type === 'qrcode' ? 1 : Number(layer.opacity ?? 1),
        zIndex: Number(layer.z || 0) + 1000,
        transform: `rotate(${Number(layer.rotate || 0)}deg)`,
    }
}

function textPreviewStyle(layer: DesignLayer): CSSProperties {
    return buildPosterTextPreviewStyle(layer, canvasScale.value, normalizeUrl)
}

function imagePreviewBoxStyle(layer: DesignLayer): CSSProperties {
    return {
        background: layer.backgroundFill || 'rgba(255,255,255,0.12)',
        borderRadius: `${Number(layer.radius || 0) * canvasScale.value}px`,
    }
}

function imagePreviewStyle(layer: DesignLayer): CSSProperties {
    return {
        objectFit: layer.fit === 'contain' ? 'contain' : 'cover',
        borderRadius: `${Number(layer.radius || 0) * canvasScale.value}px`,
    }
}

function rectPreviewStyle(layer: DesignLayer): CSSProperties {
    return {
        background: layer.fill || '#FFFFFF',
        border: Number(layer.strokeWidth || 0) > 0 ? `${Number(layer.strokeWidth) * canvasScale.value}px solid ${layer.stroke || '#FFFFFF'}` : 'none',
        borderRadius: `${Number(layer.radius || 0) * canvasScale.value}px`,
    }
}

function linePreviewStyle(layer: DesignLayer): CSSProperties {
    return {
        borderTop: `${Math.max(1, Number(layer.strokeWidth || 2) * canvasScale.value)}px solid ${layer.stroke || '#D8C08B'}`,
    }
}

function repeaterPreviewStyle(layer: DesignLayer): CSSProperties {
    return {
        width: `${layerWidth(layer) * canvasScale.value}px`,
        height: `${layerHeight(layer) * canvasScale.value}px`,
    }
}

function repeaterCardStyle(layer: DesignLayer, index: number): CSSProperties {
    const columns = Math.max(1, Number(layer.columns || 1))
    const col = index % columns
    const row = Math.floor(index / columns)
    const items = repeaterItems(layer)
    const rowStart = row * columns
    const itemsInRow = Math.min(columns, Math.max(0, items.length - rowStart))
    const rowWidth = itemsInRow * Number(layer.card_width || 300) + Math.max(0, itemsInRow - 1) * Number(layer.gap_x || 0)
    const fullWidth = columns * Number(layer.card_width || 300) + Math.max(0, columns - 1) * Number(layer.gap_x || 0)
    const rowShift = layer.align === 'center'
        ? Math.max(0, (fullWidth - rowWidth) / 2)
        : layer.align === 'right'
            ? Math.max(0, fullWidth - rowWidth)
            : 0
    const scale = canvasScale.value
    return {
        left: `${(rowShift + col * (Number(layer.card_width || 300) + Number(layer.gap_x || 0))) * scale}px`,
        top: `${row * (Number(layer.card_height || 300) + Number(layer.gap_y || 0)) * scale}px`,
        width: `${Number(layer.card_width || 300) * scale}px`,
        height: `${Number(layer.card_height || 300) * scale}px`,
    }
}

function cardLayerStyle(layer: DesignLayer): CSSProperties {
    const scale = canvasScale.value
    return {
        left: `${Number(layer.x || 0) * scale}px`,
        top: `${Number(layer.y || 0) * scale}px`,
        width: `${Number(layer.w || 120) * scale}px`,
        height: `${Number(layer.h || 80) * scale}px`,
        opacity: Number(layer.opacity ?? 1),
        transform: `rotate(${Number(layer.rotate || 0)}deg)`,
    }
}

// ---------------------------------------------------------------------------
// 预览文本与素材
// ---------------------------------------------------------------------------

const hasPreviewSnapshot = computed(() => {
    const snapshot: Record<string, any> = props.previewSnapshot || {}
    return Boolean(
        snapshot.variables || snapshot.ranking_staffs || snapshot.top_staffs || snapshot.qrcode_image || snapshot.qrcode_url || snapshot.report_month_label,
    )
})

function templateVars(): Record<string, any> {
    const snapshot: Record<string, any> = props.previewSnapshot || {}
    const variables: Record<string, any> = (snapshot.variables || {}) as Record<string, any>
    return {
        ...variables,
        report_year: variables.report_year ?? snapshot.report_year ?? '',
        report_month_label: snapshot.report_month_label || variables.report_month_label || '',
        addition_count: variables.addition_count ?? '',
        executed_count: variables.executed_count ?? '',
        top_count: variables.top_count ?? '',
        top_staff_names: topStaffNamesPreview(),
        addition_title: variables.addition_title ?? '',
        addition_subtitle: variables.addition_subtitle ?? '',
        ranking_title: variables.ranking_title ?? '',
        top_title: variables.top_title ?? '',
        footer_note: variables.footer_note ?? '',
    }
}

function renderTemplate(template: string, variables: Record<string, any>): string {
    return String(template || '').replace(/\{([a-zA-Z0-9_]+)\}/g, (match, key: string) => {
        const value = variables[key]
        return value === undefined || value === null || String(value) === '' ? match : String(value)
    })
}

function renderLayerText(layer: DesignLayer): string {
    const raw = String(layer.text || '')
    const rendered = renderTemplate(raw, templateVars())
    return wrapPosterText(hasPreviewSnapshot.value ? rendered : renderSampleText(rendered), layer)
}

function renderCardText(layer: DesignLayer, item: Record<string, any>): string {
    const raw = String(layer.text || '')
    const merged = renderTemplate(raw, { ...templateVars(), ...item })
    return wrapPosterText(hasPreviewSnapshot.value ? merged : renderSampleText(merged), layer)
}

function layerImageSrc(layer: DesignLayer): string {
    return normalizeUrl(layer.src_url || layer.src)
}

function qrcodeSrc(layer: DesignLayer): string {
    const snapshot: Record<string, any> = props.previewSnapshot || {}
    return normalizeUrl(layer.src_url || snapshot.qrcode_image || snapshot.qrcode_url)
}

function cardImageSrc(layer: DesignLayer, item: Record<string, any>): string {
    return normalizeUrl(renderTemplate(layer.src || '', { ...templateVars(), ...item }))
}

function repeaterItems(layer: DesignLayer): Record<string, any>[] {
    const snapshot: Record<string, any> = props.previewSnapshot || {}
    const rows = layer.source === 'top' ? snapshot.top_staffs : snapshot.ranking_staffs
    const fallback = [
        { photo_url: '', avatar_photo_url: '', half_body_photo_url: '', english_name: 'KEYU', chinese_name: '可玉', count: '21', rank: 1 },
        { photo_url: '', avatar_photo_url: '', half_body_photo_url: '', english_name: 'WANGFAN', chinese_name: '王帆', count: '22', rank: 2 },
        { photo_url: '', avatar_photo_url: '', half_body_photo_url: '', english_name: 'YIPING', chinese_name: '王一平', count: '23', rank: 3 },
    ]
    const items = Array.isArray(rows) && rows.length ? (rows as Record<string, any>[]) : fallback
    const limit = Number(layer.limit || 0)
    return limit > 0 ? items.slice(0, limit) : items.slice(0, 24)
}

function topStaffNamesPreview(): string {
    const snapshot: Record<string, any> = props.previewSnapshot || {}
    const rows = snapshot.top_staffs
    const fallback = ['王一平', '王策']
    const names = Array.isArray(rows) && rows.length
        ? (rows as Record<string, any>[]).map((item) => String(item?.chinese_name || '').trim()).filter(Boolean)
        : fallback
    return names.join('、')
}

function normalizedCardLayers(layer: DesignLayer): DesignLayer[] {
    return Array.isArray(layer.card_layers) && layer.card_layers.length
        ? layer.card_layers
        : defaultCardLayers(Number(layer.card_width || 300), Number(layer.card_height || 300))
}

// ---------------------------------------------------------------------------
// 选择与清空
// ---------------------------------------------------------------------------

function handleCanvasMousedown() {
    if (dragging.value || resizing.value) return
    emit('clear-selection')
}

function startLayerDrag(layer: DesignLayer, event: MouseEvent) {
    if (event.button !== 0) return
    hoverLayerId.value = layer.id
    emit('select-layer', layer.id)
    if (!canInteract(layer)) return
    event.preventDefault()
    stopResize()
    stopLayerDrag()
    dragging.value = {
        layerId: layer.id,
        startX: event.clientX,
        startY: event.clientY,
        originX: Number(layer.x || 0),
        originY: Number(layer.y || 0),
        changed: false,
    }
    window.addEventListener('mousemove', handleLayerDrag)
    window.addEventListener('mouseup', stopLayerDrag)
}

function stopLayerDrag() {
    const shouldCommit = Boolean(dragging.value && dragging.value.changed)
    dragging.value = null
    snapGuides.value = []
    window.removeEventListener('mousemove', handleLayerDrag)
    window.removeEventListener('mouseup', stopLayerDrag)
    if (shouldCommit) emit('commit-history')
}

function computeSnap(layer: DesignLayer, rawX: number, rawY: number): { x: number; y: number; guides: SnapGuide[] } {
    const width = layerWidth(layer)
    const height = layer.type === 'line' ? Math.max(1, Number(layer.h || 0)) : layerHeight(layer)
    const guides: SnapGuide[] = []
    const others = sortedLayers.value.filter((item) => {
        if (item.id === layer.id) return false
        if (Number(item.visible ?? 1) !== 1) return false
        if (item.id === 'bg-scrim') return false
        return true
    })

    const pickBest = (candidates: Array<{ position: number; target: number }>): { position: number; delta: number } | null => {
        let best: { position: number; delta: number } | null = null
        for (const candidate of candidates) {
            const delta = candidate.position - candidate.target
            if (Math.abs(delta) <= SNAP_THRESHOLD && (!best || Math.abs(delta) < Math.abs(best.delta))) {
                best = { position: candidate.position, delta }
            }
        }
        return best
    }

    const left = rawX
    const centerX = rawX + width / 2
    const right = rawX + width
    const verticalCandidates: Array<{ position: number; target: number }> = [
        { position: canvasWidth.value / 2, target: centerX },
        { position: SAFE_MARGIN, target: left },
        { position: canvasWidth.value - SAFE_MARGIN, target: right },
    ]
    for (const other of others) {
        const otherWidth = layerWidth(other)
        const otherX = Number(other.x || 0)
        verticalCandidates.push({ position: otherX, target: left })
        verticalCandidates.push({ position: otherX + otherWidth / 2, target: centerX })
        verticalCandidates.push({ position: otherX + otherWidth, target: right })
    }
    const bestX = pickBest(verticalCandidates)

    const top = rawY
    const middleY = rawY + height / 2
    const bottom = rawY + height
    const horizontalCandidates: Array<{ position: number; target: number }> = [
        { position: canvasHeight.value / 2, target: middleY },
        { position: SAFE_MARGIN, target: top },
        { position: canvasHeight.value - SAFE_MARGIN, target: bottom },
    ]
    for (const other of others) {
        const otherHeight = other.type === 'repeater' ? repeaterRowHeight(other) : Number(other.h || 0)
        const otherY = Number(other.y || 0)
        horizontalCandidates.push({ position: otherY, target: top })
        horizontalCandidates.push({ position: otherY + otherHeight / 2, target: middleY })
        horizontalCandidates.push({ position: otherY + otherHeight, target: bottom })
    }
    const bestY = pickBest(horizontalCandidates)

    const x = bestX ? Math.round(rawX + bestX.delta) : Math.round(rawX)
    const y = bestY ? Math.round(rawY + bestY.delta) : Math.round(rawY)
    if (bestX) guides.push({ orientation: 'vertical', position: Math.round(bestX.position) })
    if (bestY) guides.push({ orientation: 'horizontal', position: Math.round(bestY.position) })
    return { x, y, guides }
}

function handleLayerDrag(event: MouseEvent) {
    if (!dragging.value) return
    event.preventDefault()
    const layer = findLayer(dragging.value.layerId)
    if (!layer) {
        stopLayerDrag()
        return
    }
    const scale = Math.max(0.01, canvasScale.value)
    const rawX = dragging.value.originX + (event.clientX - dragging.value.startX) / scale
    const rawY = dragging.value.originY + (event.clientY - dragging.value.startY) / scale
    const snapped = computeSnap(layer, rawX, rawY)
    if (snapped.x !== Number(layer.x || 0) || snapped.y !== Number(layer.y || 0)) {
        layer.x = snapped.x
        layer.y = snapped.y
        dragging.value.changed = true
        snapGuides.value = snapped.guides
        emit('update-layer-geometry', { layerId: layer.id, x: layer.x, y: layer.y })
    }
}

function snapGuideStyle(guide: SnapGuide): CSSProperties {
    if (guide.orientation === 'vertical') {
        return { left: `${guide.position * canvasScale.value}px`, top: 0, height: '100%' }
    }
    return { top: `${guide.position * canvasScale.value}px`, left: 0, width: '100%' }
}

// ---------------------------------------------------------------------------
// 缩放手柄
// ---------------------------------------------------------------------------

function startResize(layer: DesignLayer, event: MouseEvent) {
    if (event.button !== 0 || !canInteract(layer) || layer.type === 'repeater') return
    event.preventDefault()
    stopLayerDrag()
    stopResize()
    resizing.value = {
        layerId: layer.id,
        startX: event.clientX,
        startY: event.clientY,
        originW: Number(layer.w || 1),
        originH: Number(layer.h || 1),
        changed: false,
    }
    window.addEventListener('mousemove', handleResize)
    window.addEventListener('mouseup', stopResize)
}

function handleResize(event: MouseEvent) {
    if (!resizing.value) return
    event.preventDefault()
    const layer = findLayer(resizing.value.layerId)
    if (!layer) {
        stopResize()
        return
    }
    const scale = Math.max(0.01, canvasScale.value)
    const deltaX = (event.clientX - resizing.value.startX) / scale
    const deltaY = (event.clientY - resizing.value.startY) / scale
    const minSize = layer.type === 'qrcode' ? QRCODE_MIN_SIZE : 1
    const minHeight = layer.type === 'line' ? 0 : minSize
    const nextW = Math.max(minSize, Math.round(resizing.value.originW + deltaX))
    const nextH = Math.max(minHeight, Math.round(resizing.value.originH + deltaY))
    if (nextW !== Number(layer.w || 0) || nextH !== Number(layer.h || 0)) {
        layer.w = nextW
        layer.h = nextH
        resizing.value.changed = true
        emit('update-layer-geometry', { layerId: layer.id, x: Number(layer.x || 0), y: Number(layer.y || 0), w: nextW, h: nextH })
    }
}

function stopResize() {
    const shouldCommit = Boolean(resizing.value && resizing.value.changed)
    resizing.value = null
    window.removeEventListener('mousemove', handleResize)
    window.removeEventListener('mouseup', stopResize)
    if (shouldCommit) emit('commit-history')
}

// ---------------------------------------------------------------------------
// 键盘快捷键
// ---------------------------------------------------------------------------

function isEditableTarget(target: EventTarget | null): boolean {
    const element = target as HTMLElement | null
    if (!element || !element.tagName) return false
    const tag = element.tagName.toUpperCase()
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || Boolean(element.isContentEditable)
}

function handleKeydown(event: KeyboardEvent) {
    if (isEditableTarget(event.target)) return
    if (!props.activeLayerId) return
    const layer = findLayer(props.activeLayerId)
    if (!layer) return

    if (event.key === 'ArrowUp' || event.key === 'ArrowDown' || event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        if (Number(layer.locked || 0) === 1) return
        event.preventDefault()
        const step = event.shiftKey ? 10 : 1
        const deltaX = event.key === 'ArrowLeft' ? -step : event.key === 'ArrowRight' ? step : 0
        const deltaY = event.key === 'ArrowUp' ? -step : event.key === 'ArrowDown' ? step : 0
        layer.x = Number(layer.x || 0) + deltaX
        layer.y = Number(layer.y || 0) + deltaY
        emit('update-layer-geometry', { layerId: layer.id, x: layer.x, y: layer.y })
        emit('commit-history')
        return
    }

    if ((event.key === 'Delete' || event.key === 'Backspace') && props.mode === 'pro' && layer.type !== 'qrcode') {
        event.preventDefault()
        emit('delete-layer', layer.id)
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown)
    stopLayerDrag()
    stopResize()
})
</script>

<style scoped lang="scss">
.mdc {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.mdc__viewport {
    padding: 16px;
    background: #f5f7fa;
    border: 1px solid #e8ecf3;
    border-radius: 10px;
    overflow: auto;
}

.mdc__canvas {
    position: relative;
    margin: 0 auto;
    overflow: hidden;
    box-shadow: 0 12px 40px rgba(15, 23, 42, 0.18);

    &__background-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }
}

.mdc__snap-guide {
    position: absolute;
    z-index: 4000;
    pointer-events: none;

    &--vertical {
        top: 0;
        width: 0;
        border-left: 1px dashed #e0218a;
    }

    &--horizontal {
        left: 0;
        height: 0;
        border-top: 1px dashed #e0218a;
    }
}

.mdc-layer {
    position: absolute;
    outline: 1px dashed transparent;
    user-select: none;

    &.is-draggable {
        cursor: move;
    }

    &.is-active {
        outline: 1.5px solid #409eff;
    }

    &.is-hidden {
        opacity: 0.35 !important;
    }

    &__text {
        width: 100%;
        height: 100%;
        white-space: pre-wrap;
        overflow: hidden;
        word-break: normal;
        overflow-wrap: normal;
    }

    &__image,
    &__qr,
    &__rect {
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    &__image img,
    &__qr img {
        width: 100%;
        height: 100%;
        display: block;
        pointer-events: none;
        user-select: none;
        -webkit-user-drag: none;
    }

    &__image span,
    &__qr span {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: rgba(255, 255, 255, 0.7);
        background: rgba(0, 0, 0, 0.16);
        font-size: 12px;
    }

    &__qr {
        padding: 8%;
        background: #fff;
        overflow: hidden;
    }

    &__line {
        width: 100%;
        height: 100%;
    }

    &__badge {
        position: absolute;
        top: 2px;
        left: 2px;
        max-width: calc(100% - 4px);
        padding: 1px 7px;
        border-radius: 999px;
        background: rgba(17, 24, 39, 0.82);
        color: #fff;
        font-size: 11px;
        line-height: 18px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        pointer-events: none;
        z-index: 2000;

        &--active {
            background: #409eff;
        }
    }

    &__resize-handle {
        position: absolute;
        right: -5px;
        bottom: -5px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #409eff;
        border: 1.5px solid #fff;
        box-sizing: content-box;
        cursor: nwse-resize;
        z-index: 2100;
    }
}

.mdc-repeater {
    position: relative;
    width: 100%;
    height: 100%;
    outline: 1px dashed rgba(255, 255, 255, 0.45);
}

.mdc-repeater__card,
.mdc-card-layer {
    position: absolute;
}

.mdc-card-layer {
    overflow: hidden;

    &__text {
        width: 100%;
        height: 100%;
        white-space: pre-wrap;
        word-break: normal;
        overflow-wrap: normal;
        overflow: hidden;
    }

    &__image,
    &__rect {
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    &__image img {
        width: 100%;
        height: 100%;
        display: block;
        pointer-events: none;
    }
}

.mdc__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 8px 12px;
    border: 1px solid #e8ecf3;
    border-radius: 8px;
    background: #fff;
    font-size: 12px;
    color: #6b7280;

    .mdc__size {
        color: #303133;
        font-weight: 600;
        white-space: nowrap;
    }

    .mdc__tips {
        text-align: right;
    }
}
</style>
