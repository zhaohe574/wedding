<template>
    <div class="mrd">
        <div class="mrd__toolbar">
            <div class="mrd__modes">
                <button type="button" :class="['mrd__mode', { 'is-active': mode === 'simple' }]" @click="switchMode('simple')">
                    快捷设计（推荐）
                </button>
                <button type="button" :class="['mrd__mode', { 'is-active': mode === 'pro' }]" @click="switchMode('pro')">
                    专业精调
                </button>
            </div>

            <div class="mrd__tools">
                <el-tooltip content="撤销 Ctrl+Z" placement="bottom">
                    <el-button size="small" :disabled="!canUndo" @click="undo()">撤销</el-button>
                </el-tooltip>
                <el-tooltip content="重做 Ctrl+Shift+Z" placement="bottom">
                    <el-button size="small" :disabled="!canRedo" @click="redo()">重做</el-button>
                </el-tooltip>

                <template v-if="mode === 'simple'">
                    <el-divider direction="vertical" />
                    <span class="mrd__switch-label">画布微调拖动</span>
                    <el-switch v-model="allowSimpleDrag" size="small" />
                </template>
                <template v-else>
                    <el-divider direction="vertical" />
                    <el-button size="small" @click="addTextLayer">+文本</el-button>
                    <el-button size="small" @click="addRectLayer">+矩形</el-button>
                    <el-button size="small" @click="addLineLayer">+分割线</el-button>
                    <material-picker v-model="pickerImageUrl" :limit="1">
                        <template #upload>
                            <el-button size="small">+图片</el-button>
                        </template>
                    </material-picker>
                    <el-button size="small" @click="addRepeaterLayer">+人员卡片</el-button>
                    <el-button v-if="!hasQrcode" size="small" @click="addQrcodeLayer">+二维码</el-button>
                </template>
            </div>

            <div class="mrd__right">
                <el-button size="small" :disabled="zoom <= ZOOM_MIN" @click="stepZoom(-ZOOM_STEP)">-</el-button>
                <span class="mrd__zoom-value">{{ zoom }}%</span>
                <el-button size="small" :disabled="zoom >= ZOOM_MAX" @click="stepZoom(ZOOM_STEP)">+</el-button>
                <el-button size="small" @click="resetZoom">适配</el-button>
                <el-button type="primary" size="small" @click="handlePreview">查看高清成图</el-button>
            </div>
        </div>

        <div class="mrd__body">
            <div class="mrd__canvas-area">
                <designer-canvas
                    :design="localDesign"
                    :template-type="normalizedType"
                    :preview-snapshot="previewSnapshot"
                    :mode="mode"
                    :active-layer-id="activeLayerId"
                    :zoom="zoom"
                    :base-width="resolvedBaseWidth"
                    :allow-simple-drag="allowSimpleDrag"
                    @select-layer="handleSelectLayer"
                    @clear-selection="activeLayerId = ''"
                    @update-layer-geometry="handleLayerGeometry"
                    @commit-history="pushHistorySnapshot"
                    @delete-layer="handleDeleteLayer"
                />
            </div>
            <aside class="mrd__panel">
                <simple-designer-panel
                    v-show="mode === 'simple'"
                    :design="localDesign"
                    :template-type="normalizedType"
                    :active-layer-id="activeLayerId"
                    :active-tab="simpleTab"
                    :extra-dynamic-fields="extraDynamicFields"
                    @update:design="handlePanelDesignUpdate($event, true)"
                    @update:active-tab="simpleTab = $event"
                    @select-layer="handleSelectLayer"
                    @switch-to-pro="switchMode('pro')"
                />
                <pro-designer-panel
                    v-show="mode === 'pro'"
                    :design="localDesign"
                    :template-type="normalizedType"
                    :active-layer-id="activeLayerId"
                    :active-tab="proTab"
                    :extra-dynamic-fields="extraDynamicFields"
                    @update:design="handlePanelDesignUpdate($event, false)"
                    @update:active-tab="proTab = $event"
                    @select-layer="handleSelectLayer"
                    @commit-history="pushHistorySnapshot"
                />
            </aside>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import MaterialPicker from '@/components/material/picker.vue'

import DesignerCanvas from './monthly-report/DesignerCanvas.vue'
import ProDesignerPanel from './monthly-report/ProDesignerPanel.vue'
import SimpleDesignerPanel from './monthly-report/SimpleDesignerPanel.vue'
import {
    cloneDesign,
    defaultLine,
    defaultQrcode,
    defaultRect,
    defaultRepeater,
    defaultText,
    normalizeDesign,
    normalizeUrl,
    uid,
} from './monthly-report/presets'
import type {
    DesignConfig,
    DesignLayer,
    DesignerMode,
    DynamicFieldOption,
    LayerGeometryPayload,
    MonthlyReportTemplateType,
    ProPanelTab,
    SimplePanelTab,
} from './monthly-report/types'

const props = withDefaults(
    defineProps<{
        modelValue?: Record<string, any>
        previewSnapshot?: Record<string, any>
        templateType?: string
        previewWidth?: number
        extraDynamicFields?: DynamicFieldOption[]
    }>(),
    {
        modelValue: () => ({}),
        previewSnapshot: () => ({}),
        templateType: 'addition',
        previewWidth: 380,
        extraDynamicFields: () => [],
    },
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: DesignConfig): void
    (e: 'preview'): void
    (e: 'dirty-change', dirty: boolean): void
}>()

const HISTORY_LIMIT = 30
const HISTORY_DEBOUNCE = 300
const ZOOM_MIN = 50
const ZOOM_MAX = 150
const ZOOM_STEP = 10

const TEMPLATE_TYPES: MonthlyReportTemplateType[] = ['addition', 'ranking', 'top']

// ---------------------------------------------------------------------------
// 状态
// ---------------------------------------------------------------------------

const normalizedType = computed<MonthlyReportTemplateType>(() =>
    TEMPLATE_TYPES.includes(props.templateType as MonthlyReportTemplateType)
        ? (props.templateType as MonthlyReportTemplateType)
        : 'addition',
)

const localDesign = ref<DesignConfig>(normalizeDesign(props.modelValue, normalizedType.value))
const mode = ref<DesignerMode>('simple')
const simpleTab = ref<SimplePanelTab>('style')
const proTab = ref<ProPanelTab>('props')
const activeLayerId = ref('')
const allowSimpleDrag = ref(false)
const zoom = ref(100)
const pickerImageUrl = ref('')

let historyStack: string[] = []
let historyIndex = -1
let historyTimer: ReturnType<typeof setTimeout> | null = null

const resolvedBaseWidth = computed(() => Math.max(180, Math.min(520, Number(props.previewWidth || 380))))
const canUndo = computed(() => historyIndex > 0)
const canRedo = computed(() => historyIndex >= 0 && historyIndex < historyStack.length - 1)
const hasQrcode = computed(() => localDesign.value.layers.some((layer) => layer.type === 'qrcode'))

// ---------------------------------------------------------------------------
// 外部同步与历史栈
// ---------------------------------------------------------------------------

let lastEmittedRaw = ''

function syncOut() {
    const payload = JSON.parse(JSON.stringify(localDesign.value)) as DesignConfig
    lastEmittedRaw = JSON.stringify(payload)
    emit('update:modelValue', payload)
    emit('dirty-change', true)
}

function clearHistoryTimer() {
    if (historyTimer) {
        clearTimeout(historyTimer)
        historyTimer = null
    }
}

function resetHistory(design: DesignConfig) {
    clearHistoryTimer()
    historyStack = [JSON.stringify(design)]
    historyIndex = 0
}

function pushHistorySnapshot() {
    clearHistoryTimer()
    const snapshot = JSON.stringify(localDesign.value)
    if (historyStack[historyIndex] === snapshot) return
    historyStack = [...historyStack.slice(0, historyIndex + 1), snapshot]
    if (historyStack.length > HISTORY_LIMIT) {
        historyStack = historyStack.slice(historyStack.length - HISTORY_LIMIT)
    }
    historyIndex = historyStack.length - 1
}

function scheduleHistoryPush(delay = HISTORY_DEBOUNCE) {
    clearHistoryTimer()
    historyTimer = setTimeout(() => {
        historyTimer = null
        pushHistorySnapshot()
    }, delay)
}

function pruneActiveLayer() {
    if (activeLayerId.value && !localDesign.value.layers.some((layer) => layer.id === activeLayerId.value)) {
        activeLayerId.value = ''
    }
}

function applyHistorySnapshot() {
    const snapshot = historyStack[historyIndex]
    if (snapshot === undefined) return
    clearHistoryTimer()
    localDesign.value = JSON.parse(snapshot) as DesignConfig
    pruneActiveLayer()
    syncOut()
}

function undo() {
    if (!canUndo.value) return
    historyIndex -= 1
    applyHistorySnapshot()
}

function redo() {
    if (!canRedo.value) return
    historyIndex += 1
    applyHistorySnapshot()
}

watch(
    () => props.modelValue,
    (value) => {
        if (JSON.stringify(value || {}) === lastEmittedRaw) return
        const next = normalizeDesign(value, normalizedType.value)
        if (JSON.stringify(next) === JSON.stringify(localDesign.value)) return
        localDesign.value = next
        activeLayerId.value = ''
        resetHistory(next)
        emit('dirty-change', false)
    },
    { deep: true },
)

// ---------------------------------------------------------------------------
// 子组件事件编排
// ---------------------------------------------------------------------------

function switchMode(next: DesignerMode) {
    mode.value = next
}

function handleSelectLayer(layerId: string) {
    activeLayerId.value = layerId
    if (mode.value === 'simple') {
        simpleTab.value = 'content'
    } else {
        proTab.value = 'props'
    }
}

function handlePanelDesignUpdate(nextDesign: DesignConfig, fromSimple: boolean) {
    localDesign.value = cloneDesign(nextDesign)
    pruneActiveLayer()
    syncOut()
    if (fromSimple) {
        pushHistorySnapshot()
    } else {
        scheduleHistoryPush()
    }
}

function handleLayerGeometry(payload: LayerGeometryPayload) {
    const layer = localDesign.value.layers.find((item) => item.id === payload.layerId)
    if (!layer) return
    layer.x = payload.x
    layer.y = payload.y
    if (payload.w !== undefined) layer.w = payload.w
    if (payload.h !== undefined) layer.h = payload.h
    syncOut()
}

function handleDeleteLayer(layerId: string) {
    const index = localDesign.value.layers.findIndex((item) => item.id === layerId)
    if (index < 0) return
    localDesign.value.layers.splice(index, 1)
    if (activeLayerId.value === layerId) activeLayerId.value = ''
    syncOut()
    pushHistorySnapshot()
}

// ---------------------------------------------------------------------------
// 专业模式快捷添加图层
// ---------------------------------------------------------------------------

function nextZ(): number {
    return localDesign.value.layers.reduce((max, layer) => Math.max(max, Number(layer.z || 0)), 0) + 1
}

function centerPosition(w: number, h: number): { x: number; y: number } {
    return {
        x: Math.round((Number(localDesign.value.canvas.width || 1080) - w) / 2),
        y: Math.round((Number(localDesign.value.canvas.height || 1920) - h) / 2),
    }
}

function appendLayer(layer: DesignLayer) {
    localDesign.value.layers.push(layer)
    activeLayerId.value = layer.id
    syncOut()
    pushHistorySnapshot()
}

function addTextLayer() {
    const { x, y } = centerPosition(480, 80)
    appendLayer(defaultText(uid('text'), x, y, 480, 80, nextZ(), '新增文本', 56, '#FFFFFF', '700'))
}

function addRectLayer() {
    const { x, y } = centerPosition(320, 200)
    appendLayer(defaultRect(uid('rect'), x, y, 320, 200, nextZ(), 'rgba(255,255,255,0.35)'))
}

function addLineLayer() {
    const { x, y } = centerPosition(480, 0)
    appendLayer(defaultLine(uid('line'), x, y, 480, nextZ()))
}

function addQrcodeLayer() {
    const { x, y } = centerPosition(180, 180)
    appendLayer(defaultQrcode(x, y, 180, 180, nextZ()))
}

function addRepeaterLayer() {
    const isTop = normalizedType.value === 'top'
    const cardWidth = isTop ? 310 : 300
    const cardHeight = isTop ? 720 : 300
    const { x, y } = centerPosition(3 * cardWidth + 2 * 24, cardHeight)
    appendLayer(
        defaultRepeater(
            uid('repeater'),
            Math.max(0, x),
            Math.max(0, y),
            3,
            cardWidth,
            cardHeight,
            24,
            isTop ? 0 : 24,
            nextZ(),
            0,
            isTop ? 'top' : 'ranking',
            [],
            isTop ? 'center' : 'left',
        ),
    )
}

watch(pickerImageUrl, (value) => {
    const src = normalizeUrl(value)
    if (!src) return
    pickerImageUrl.value = ''
    const { x, y } = centerPosition(420, 300)
    appendLayer({
        id: uid('image'),
        type: 'image',
        x: Math.max(0, x),
        y: Math.max(0, y),
        w: 420,
        h: 300,
        z: nextZ(),
        visible: 1,
        locked: 0,
        opacity: 1,
        rotate: 0,
        src,
        src_url: src,
        fit: 'cover',
        backgroundFill: '',
        radius: 0,
    })
})

// ---------------------------------------------------------------------------
// 缩放与预览
// ---------------------------------------------------------------------------

function stepZoom(step: number) {
    zoom.value = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, zoom.value + step))
}

function resetZoom() {
    zoom.value = 100
}

function handlePreview() {
    emit('preview')
}

// ---------------------------------------------------------------------------
// 快捷键：Ctrl/Cmd+Z 撤销，Ctrl/Cmd+Shift+Z 或 Ctrl/Cmd+Y 重做
// ---------------------------------------------------------------------------

function isEditableTarget(target: EventTarget | null): boolean {
    const element = target as HTMLElement | null
    if (!element || !element.tagName) return false
    const tag = element.tagName.toUpperCase()
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || Boolean(element.isContentEditable)
}

function handleKeydown(event: KeyboardEvent) {
    if (isEditableTarget(event.target)) return
    if (!(event.ctrlKey || event.metaKey)) return
    const key = String(event.key || '').toLowerCase()
    if (key === 'z') {
        event.preventDefault()
        if (event.shiftKey) {
            redo()
        } else {
            undo()
        }
    } else if (key === 'y') {
        event.preventDefault()
        redo()
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown)
    resetHistory(localDesign.value)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown)
    clearHistoryTimer()
})
</script>

<style scoped lang="scss">
.mrd {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.mrd__toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 10px 12px;
    border: 1px solid #e8ecf3;
    border-radius: 10px;
    background: #fff;
}

.mrd__modes {
    display: inline-flex;
    overflow: hidden;
    border: 1px solid #dcdfe6;
    border-radius: 6px;
}

.mrd__mode {
    padding: 6px 14px;
    border: none;
    background: #fff;
    color: #606266;
    font-size: 13px;
    cursor: pointer;

    &.is-active {
        background: #409eff;
        color: #fff;
    }
}

.mrd__tools {
    display: flex;
    align-items: center;
    flex: 1;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
}

.mrd__switch-label {
    font-size: 12px;
    color: #6b7280;
}

.mrd__right {
    display: flex;
    align-items: center;
    gap: 6px;
}

.mrd__zoom-value {
    min-width: 44px;
    text-align: center;
    font-size: 12px;
    color: #303133;
}

.mrd__body {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 440px;
    gap: 14px;
    align-items: start;
}

.mrd__canvas-area {
    min-width: 0;
}

.mrd__panel {
    border: 1px solid #e8ecf3;
    border-radius: 10px;
    background: #fff;
    overflow: auto;
    max-height: calc(100vh - 240px);
}

@media (max-width: 1200px) {
    .mrd__body {
        grid-template-columns: 1fr;
    }

    .mrd__panel {
        max-height: none;
    }
}
</style>
