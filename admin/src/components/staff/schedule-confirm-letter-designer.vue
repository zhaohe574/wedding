<template>
    <div class="letter-designer">
        <div class="designer-toolbar">
            <div class="designer-toolbar__group">
                <el-button size="small" @click="addTextLayer">文本</el-button>
                <el-popover placement="bottom-start" width="380" trigger="click" :teleported="false">
                    <template #reference>
                        <el-button size="small">动态字段</el-button>
                    </template>
                    <div class="dynamic-field-guide">
                        <div class="dynamic-field-guide__title">可用动态字段</div>
                        <div class="dynamic-field-guide__desc">点击字段会插入当前文本图层；未选中文本时会新建动态文本图层。字段只使用脱敏订单信息。</div>
                        <div class="dynamic-field-list">
                            <button
                                v-for="field in dynamicFields"
                                :key="field.token"
                                type="button"
                                class="dynamic-field-card"
                                @click="insertDynamicField(field.token)"
                            >
                                <span class="dynamic-field-card__label">{{ field.label }}</span>
                                <span class="dynamic-field-card__token">{{ field.token }}</span>
                                <span class="dynamic-field-card__desc">{{ field.desc }}</span>
                            </button>
                        </div>
                    </div>
                </el-popover>
                <el-button size="small" @click="addRectLayer">矩形</el-button>
                <el-button size="small" @click="addLineLayer">分割线</el-button>
            </div>
            <div class="designer-toolbar__group">
                <material-picker v-model="newImageUrl" :limit="1">
                    <template #upload>
                        <el-button size="small">选择图片</el-button>
                    </template>
                </material-picker>
                <el-button size="small" :disabled="!newImageUrl" @click="addImageLayer">添加图片</el-button>
                <el-button size="small" @click="resetDefault">恢复默认</el-button>
            </div>
        </div>

        <div class="designer-body">
            <div class="designer-canvas-shell">
                <div class="designer-canvas-scale" :style="{ width: `${previewWidth}px` }">
                    <div class="designer-canvas" :style="canvasStyle" @mousedown="clearSelection">
                        <div
                            v-for="layer in sortedLayers"
                            :key="layer.id"
                            :class="['designer-layer', `designer-layer--${layer.type}`, { 'is-active': activeLayer?.id === layer.id, 'is-hidden': layer.visible !== 1 }]"
                            :style="layerStyle(layer)"
                            @mousedown.stop="startDrag(layer, $event)"
                        >
                            <template v-if="layer.type === 'text'">
                                <div class="designer-layer__text" :style="textStyle(layer)">{{ renderLayerText(layer) }}</div>
                            </template>
                            <template v-else-if="layer.type === 'image'">
                                <div class="designer-layer__image-box" :style="imageBoxStyle(layer)">
                                    <img
                                        v-if="layerImageSrc(layer)"
                                        :src="layerImageSrc(layer)"
                                        :style="imageStyle(layer)"
                                        alt=""
                                        draggable="false"
                                        @dragstart.prevent
                                    />
                                    <span v-else>图片</span>
                                </div>
                            </template>
                            <template v-else-if="layer.type === 'qrcode'">
                                <div class="designer-layer__qr">
                                    <img
                                        v-if="qrcodeImageSrc(layer)"
                                        :src="qrcodeImageSrc(layer)"
                                        alt=""
                                        draggable="false"
                                        @dragstart.prevent
                                    />
                                    <span v-else>QR</span>
                                </div>
                            </template>
                            <template v-else-if="layer.type === 'rect'">
                                <div class="designer-layer__rect" :style="rectStyle(layer)" />
                            </template>
                            <template v-else>
                                <svg class="designer-layer__line" :viewBox="lineViewBox(layer)" preserveAspectRatio="none" aria-hidden="true">
                                    <line
                                        x1="0"
                                        y1="0"
                                        :x2="lineX2(layer)"
                                        :y2="lineY2(layer)"
                                        :stroke="layer.stroke || '#D8C08B'"
                                        :stroke-width="linePreviewStrokeWidth(layer)"
                                        stroke-linecap="round"
                                        vector-effect="non-scaling-stroke"
                                    />
                                </svg>
                            </template>
                            <span class="resize-handle" @mousedown.stop="startResize(layer, $event)" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="designer-panel">
                <el-tabs v-model="panelTab">
                    <el-tab-pane label="图层" name="layers">
                        <div class="layer-list">
                            <button
                                v-for="layer in sortedLayersDesc"
                                :key="layer.id"
                                :class="['layer-row', { 'is-active': activeLayer?.id === layer.id }]"
                                type="button"
                                @click="selectLayer(layer)"
                            >
                                <span>{{ layerName(layer) }}</span>
                                <el-switch
                                    :model-value="layer.visible === 1"
                                    size="small"
                                    :disabled="isRequiredQrcodeLayer(layer)"
                                    @click.stop
                                    @change="toggleLayerVisible(layer, $event)"
                                />
                            </button>
                        </div>
                        <div class="panel-actions">
                            <el-button size="small" :disabled="!activeLayer || isRequiredQrcodeLayer(activeLayer)" @click="duplicateLayer">复制</el-button>
                            <el-button size="small" :disabled="!activeLayer" @click="moveLayer(1)">上移</el-button>
                            <el-button size="small" :disabled="!activeLayer" @click="moveLayer(-1)">下移</el-button>
                            <el-button size="small" type="danger" :disabled="!activeLayer || isRequiredQrcodeLayer(activeLayer)" @click="deleteLayer">删除</el-button>
                        </div>
                    </el-tab-pane>

                    <el-tab-pane label="属性" name="props">
                        <el-form v-if="activeLayer" label-width="78px" class="designer-form">
                            <el-form-item label="位置">
                                <div class="prop-grid">
                                    <el-input-number v-model="activeLayer.x" :min="-2160" :max="4320" size="small" />
                                    <el-input-number v-model="activeLayer.y" :min="-3840" :max="7680" size="small" />
                                </div>
                            </el-form-item>
                            <el-form-item label="尺寸">
                                <div class="prop-grid">
                                    <el-input-number v-model="activeLayer.w" :min="activeLayer.type === 'qrcode' ? qrcodeMinSize : 1" :max="2160" size="small" />
                                    <el-input-number v-model="activeLayer.h" :min="layerMinHeight(activeLayer)" :max="3840" size="small" />
                                </div>
                            </el-form-item>
                            <el-form-item label="图层对齐">
                                <div class="align-shortcuts">
                                    <el-tooltip
                                        v-for="action in alignShortcutActions"
                                        :key="action.key"
                                        :content="action.label"
                                        placement="top"
                                    >
                                        <el-button
                                            class="align-shortcut-button"
                                            size="small"
                                            :disabled="activeLayer.locked === 1"
                                            :aria-label="action.label"
                                            @click="applyLayerAlign(action.key)"
                                        >
                                            <icon :name="action.icon" :size="15" />
                                        </el-button>
                                    </el-tooltip>
                                </div>
                            </el-form-item>
                            <el-form-item v-if="!isRequiredQrcodeLayer(activeLayer)" label="透明度">
                                <el-slider v-model="activeLayer.opacity" :min="0" :max="1" :step="0.05" />
                            </el-form-item>
                            <el-form-item label="旋转">
                                <el-input-number v-model="activeLayer.rotate" :min="-360" :max="360" size="small" class="w-full" />
                            </el-form-item>

                            <template v-if="activeLayer.type === 'text'">
                                <el-form-item label="内容">
                                    <div class="text-editor-field">
                                        <el-input v-model="activeLayer.text" type="textarea" :rows="4" maxlength="500" show-word-limit />
                                        <div class="dynamic-field-inline">
                                            <div class="dynamic-field-inline__head">
                                                <span>可用动态字段</span>
                                                <span>点击插入当前内容</span>
                                            </div>
                                            <div class="dynamic-field-inline__chips">
                                                <button
                                                    v-for="field in dynamicFields"
                                                    :key="field.token"
                                                    type="button"
                                                    class="dynamic-field-chip"
                                                    @click="insertDynamicField(field.token)"
                                                >
                                                    <span>{{ field.label }}</span>
                                                    <code>{{ field.token }}</code>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </el-form-item>
                                <el-form-item label="可编辑">
                                    <el-select v-model="activeLayer.field" class="w-full" clearable>
                                        <el-option label="标题" value="title" />
                                        <el-option label="副标题" value="subtitle" />
                                        <el-option label="正文模板" value="content_template" />
                                        <el-option label="页脚文案" value="footer_note" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="字号">
                                    <el-input-number v-model="activeLayer.fontSize" :min="12" :max="180" size="small" class="w-full" />
                                </el-form-item>
                                <el-form-item label="颜色">
                                    <el-color-picker v-model="activeLayer.color" />
                                </el-form-item>
                                <el-form-item label="字重">
                                    <el-select v-model="activeLayer.fontWeight" class="w-full">
                                        <el-option label="常规" value="400" />
                                        <el-option label="中等" value="500" />
                                        <el-option label="半粗" value="600" />
                                        <el-option label="粗体" value="700" />
                                        <el-option label="重体" value="900" />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="文字对齐">
                                    <div class="align-shortcuts align-shortcuts--text">
                                        <el-tooltip
                                            v-for="action in textAlignActions"
                                            :key="action.key"
                                            :content="action.label"
                                            placement="top"
                                        >
                                            <el-button
                                                class="align-shortcut-button"
                                                size="small"
                                                :type="activeLayer.align === action.key ? 'primary' : 'default'"
                                                :aria-label="action.label"
                                                @click="setTextAlign(action.key)"
                                            >
                                                <icon :name="action.icon" :size="15" />
                                            </el-button>
                                        </el-tooltip>
                                    </div>
                                </el-form-item>
                                <el-form-item label="行高">
                                    <el-input-number v-model="activeLayer.lineHeight" :min="0.8" :max="3" :step="0.05" size="small" class="w-full" />
                                </el-form-item>
                            </template>

                            <template v-if="activeLayer.type === 'image'">
                                <el-form-item label="图片">
                                    <material-picker v-model="activeLayer.src" :limit="1" />
                                </el-form-item>
                                <el-form-item label="填充">
                                    <el-radio-group v-model="activeLayer.fit">
                                        <el-radio-button label="cover">铺满</el-radio-button>
                                        <el-radio-button label="contain">完整</el-radio-button>
                                    </el-radio-group>
                                </el-form-item>
                                <el-form-item label="背景色">
                                    <div class="image-background-field">
                                        <el-color-picker v-model="activeLayer.backgroundFill" show-alpha />
                                        <el-button size="small" @click="activeLayer.backgroundFill = ''">清空</el-button>
                                    </div>
                                </el-form-item>
                                <el-form-item label="圆角">
                                    <el-input-number v-model="activeLayer.radius" :min="0" :max="240" size="small" class="w-full" />
                                </el-form-item>
                            </template>

                            <template v-if="activeLayer.type === 'qrcode'">
                                <el-form-item label="来源">
                                    <el-tag type="warning">系统统一二维码</el-tag>
                                </el-form-item>
                                <el-form-item label="内边距">
                                    <el-input-number v-model="activeLayer.padding" :min="0" :max="80" size="small" class="w-full" />
                                </el-form-item>
                                <el-form-item label="圆角">
                                    <el-input-number v-model="activeLayer.radius" :min="0" :max="120" size="small" class="w-full" />
                                </el-form-item>
                            </template>

                            <template v-if="activeLayer.type === 'rect'">
                                <el-form-item label="填充">
                                    <el-color-picker v-model="activeLayer.fill" show-alpha />
                                </el-form-item>
                                <el-form-item label="描边">
                                    <el-color-picker v-model="activeLayer.stroke" />
                                </el-form-item>
                                <el-form-item label="线宽">
                                    <el-input-number v-model="activeLayer.strokeWidth" :min="0" :max="40" size="small" class="w-full" />
                                </el-form-item>
                                <el-form-item label="圆角">
                                    <el-input-number v-model="activeLayer.radius" :min="0" :max="240" size="small" class="w-full" />
                                </el-form-item>
                            </template>

                            <template v-if="activeLayer.type === 'line'">
                                <el-form-item label="颜色">
                                    <el-color-picker v-model="activeLayer.stroke" />
                                </el-form-item>
                                <el-form-item label="线宽">
                                    <el-input-number v-model="activeLayer.strokeWidth" :min="1" :max="40" size="small" class="w-full" />
                                </el-form-item>
                            </template>
                        </el-form>
                        <el-empty v-else description="请选择图层" :image-size="80" />
                    </el-tab-pane>

                    <el-tab-pane label="背景" name="background">
                        <el-form label-width="78px" class="designer-form">
                            <el-form-item label="类型">
                                <el-radio-group v-model="localDesign.background.type">
                                    <el-radio-button label="color">纯色</el-radio-button>
                                    <el-radio-button label="image">图片</el-radio-button>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item label="颜色">
                                <el-color-picker v-model="localDesign.background.color" />
                            </el-form-item>
                            <el-form-item label="图片">
                                <material-picker v-model="localDesign.background.image" :limit="1" />
                            </el-form-item>
                            <el-form-item label="显示">
                                <el-radio-group v-model="localDesign.background.fit">
                                    <el-radio-button label="cover">铺满裁剪</el-radio-button>
                                    <el-radio-button label="contain">完整显示</el-radio-button>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item label="透明度">
                                <el-slider v-model="localDesign.background.opacity" :min="0" :max="1" :step="0.05" />
                            </el-form-item>
                        </el-form>
                    </el-tab-pane>
                </el-tabs>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, reactive, ref, watch } from 'vue'
import type { StyleValue } from 'vue'
import MaterialPicker from '@/components/material/picker.vue'
import useAppStore from '@/stores/modules/app'

const props = defineProps<{
    modelValue: Record<string, any>
    previewSnapshot?: Record<string, any>
}>()

const emit = defineEmits<{
    (event: 'update:modelValue', value: Record<string, any>): void
}>()

const previewWidth = 360
const scale = previewWidth / 1080
const qrcodeMinSize = 160
const panelTab = ref('layers')
const activeLayerId = ref('')
const newImageUrl = ref('')
const dragging = ref<any>(null)
const resizing = ref<any>(null)
let applyingExternalUpdate = false
let applyingLocalUpdate = false
const appStore = useAppStore()

const dynamicFields = [
    { label: '服务日期', token: '{service_date_label}', desc: '例：2026年08月18日' },
    { label: '客户称呼', token: '{customer_alias}', desc: '例：张姓新人，不含手机号' },
    { label: '服务类型', token: '{service_name}', desc: '例：婚礼跟拍' },
    { label: '服务城市', token: '{city_label}', desc: '例：杭州 西湖区' },
    { label: '服务人员', token: '{staff_name}', desc: '例：服务人员姓名' }
]
const canvasWidth = 1080
const canvasHeight = 1920
const alignShortcutActions = [
    { key: 'left', label: '左对齐', icon: 'el-icon-Back' },
    { key: 'horizontalCenter', label: '一键水平居中', icon: 'el-icon-Aim' },
    { key: 'right', label: '右对齐', icon: 'el-icon-Right' },
    { key: 'top', label: '顶部对齐', icon: 'el-icon-Top' },
    { key: 'verticalCenter', label: '一键垂直居中', icon: 'el-icon-FullScreen' },
    { key: 'bottom', label: '底部对齐', icon: 'el-icon-Bottom' }
] as const
const textAlignActions = [
    { key: 'left', label: '文字左对齐', icon: 'el-icon-Back' },
    { key: 'center', label: '文字居中对齐', icon: 'el-icon-Aim' },
    { key: 'right', label: '文字右对齐', icon: 'el-icon-Right' }
] as const

type LayerAlignAction = typeof alignShortcutActions[number]['key']
type TextAlignAction = typeof textAlignActions[number]['key']

const localDesign = reactive<any>(createDefaultDesign())

const sortedLayers = computed(() => [...localDesign.layers].sort((a, b) => Number(a.z || 0) - Number(b.z || 0)))
const sortedLayersDesc = computed(() => [...sortedLayers.value].reverse())
const activeLayer = computed<any>(() => localDesign.layers.find((layer: any) => layer.id === activeLayerId.value))

const canvasStyle = computed(() => {
    const background = localDesign.background || {}
    const style: Record<string, string> = {
        width: `${canvasWidth * scale}px`,
        height: `${canvasHeight * scale}px`,
        backgroundColor: background.color || '#191713',
    }
    const bgImage = formatImageUrl(background.image_url || background.image)
    if (background.type === 'image' && bgImage) {
        style.backgroundImage = `url(${bgImage})`
        style.backgroundSize = background.fit === 'contain' ? 'contain' : 'cover'
        style.backgroundPosition = 'center'
        style.backgroundRepeat = 'no-repeat'
    }
    return style
})

watch(
    () => props.modelValue,
    (value) => {
        if (applyingLocalUpdate) {
            return
        }
        applyingExternalUpdate = true
        Object.assign(localDesign, normalizeDesign(value || createDefaultDesign()))
        if (!activeLayerId.value && localDesign.layers[0]) {
            activeLayerId.value = localDesign.layers[0].id
        }
        nextTick(() => {
            applyingExternalUpdate = false
        })
    },
    { immediate: true }
)

watch(
    localDesign,
    () => {
        if (applyingExternalUpdate) {
            return
        }
        applyingLocalUpdate = true
        emit('update:modelValue', normalizeDesign(localDesign))
        nextTick(() => {
            applyingLocalUpdate = false
        })
    },
    { deep: true }
)

function createDefaultDesign() {
    return {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#191713', image: '', fit: 'cover', opacity: 1 },
        layers: [
            createRectLayer('frame', 86, 148, 908, 1624, 1, {
                fill: '#12100D',
                stroke: '#D8C08B',
                strokeWidth: 2,
                radius: 42,
                opacity: 0.38
            }),
            createTextLayer('title', 180, 310, 720, 130, 2, '档期已定', {
                fontSize: 96,
                fontWeight: '700',
                lineHeight: 1.18,
                color: '#FFF7E6',
                editable: 1,
                field: 'title'
            }),
            createTextLayer('subtitle', 180, 452, 720, 60, 3, 'SCHEDULE RESERVED', {
                fontSize: 30,
                fontWeight: '500',
                lineHeight: 1.2,
                color: '#D8C08B',
                editable: 1,
                field: 'subtitle'
            }),
            createLineLayer('divider', 270, 560, 540, 0, 4),
            createTextLayer('content', 160, 720, 760, 360, 5, '{service_date_label}，{customer_alias}的{service_name}档期已确认，感谢信任。', {
                fontSize: 54,
                lineHeight: 1.42,
                color: '#FFF7E6',
                editable: 1,
                field: 'content_template'
            }),
            createTextLayer('date', 260, 1110, 560, 60, 6, '{service_date_label}', {
                fontSize: 34,
                fontWeight: '500',
                lineHeight: 1.2,
                color: '#D8C08B'
            }),
            createTextLayer('service', 260, 1170, 560, 60, 7, '{service_name}', {
                fontSize: 34,
                fontWeight: '500',
                lineHeight: 1.2,
                color: '#D8C08B'
            }),
            createTextLayer('city', 260, 1230, 560, 60, 8, '{city_label}', {
                fontSize: 34,
                fontWeight: '500',
                lineHeight: 1.2,
                color: '#D8C08B'
            }),
            {
                ...baseDesignLayer('qrcode', 'qrcode', 438, 1398, 204, 204, 9),
                visible: 1,
                src: '',
                padding: 24,
                radius: 18,
                editable: 0,
                field: ''
            },
            createTextLayer('footer', 180, 1640, 720, 92, 10, '档期已预定，感谢信任与选择。', {
                fontSize: 30,
                lineHeight: 1.45,
                color: '#B7A27A',
                editable: 1,
                field: 'footer_note'
            }),
            createTextLayer('staff', 260, 1780, 560, 48, 11, '{staff_name}', {
                fontSize: 28,
                lineHeight: 1.2,
                color: '#B7A27A'
            })
        ]
    }
}

function baseDesignLayer(id: string, type: string, x: number, y: number, w: number, h: number, z: number) {
    return {
        id,
        type,
        x,
        y,
        w,
        h,
        z,
        visible: 1,
        locked: 0,
        opacity: 1,
        rotate: 0
    }
}

function createTextLayer(id: string, x: number, y: number, w: number, h: number, z: number, text: string, extra: Record<string, any> = {}) {
    return {
        ...baseDesignLayer(id, 'text', x, y, w, h, z),
        text,
        fontSize: 42,
        fontWeight: '400',
        lineHeight: 1.35,
        align: 'center',
        color: '#FFF7E6',
        editable: 0,
        field: '',
        ...extra
    }
}

function createRectLayer(id: string, x: number, y: number, w: number, h: number, z: number, extra: Record<string, any> = {}) {
    return {
        ...baseDesignLayer(id, 'rect', x, y, w, h, z),
        fill: '#FFFFFF',
        stroke: '#D8C08B',
        strokeWidth: 2,
        radius: 0,
        ...extra
    }
}

function createLineLayer(id: string, x: number, y: number, w: number, h: number, z: number, extra: Record<string, any> = {}) {
    return {
        ...baseDesignLayer(id, 'line', x, y, w, h, z),
        stroke: '#D8C08B',
        strokeWidth: 2,
        ...extra
    }
}

function normalizeDesign(value: any) {
    const design = value && typeof value === 'object' ? value : createDefaultDesign()
    const layers = normalizeLayers(Array.isArray(design.layers) ? design.layers : [])
    return {
        canvas: { width: 1080, height: 1920 },
        background: {
            type: ['color', 'image'].includes(design.background?.type) ? design.background.type : 'color',
            color: design.background?.color || '#191713',
            image: design.background?.image || '',
            image_url: design.background?.image_url || design.background?.image || '',
            fit: design.background?.fit === 'contain' ? 'contain' : 'cover',
            opacity: Number(design.background?.opacity ?? 1)
        },
        layers
    }
}

function normalizeLayers(layers: any[]) {
    const normalized = layers.map((layer: any, index: number) => ({
            id: layer.id || `layer_${Date.now()}_${index}`,
            type: layer.type || 'text',
            x: Number(layer.x ?? 100),
            y: Number(layer.y ?? 100),
            w: Number(layer.w ?? 360),
            h: Number(layer.h ?? 120),
            z: Number(layer.z ?? index),
            visible: Number(layer.visible ?? 1),
            locked: Number(layer.locked ?? 0),
            opacity: Number(layer.opacity ?? 1),
            rotate: Number(layer.rotate ?? 0),
            text: layer.text ?? '',
            fontSize: Number(layer.fontSize ?? 42),
            fontWeight: String(layer.fontWeight ?? '400'),
            lineHeight: Number(layer.lineHeight ?? 1.35),
            align: layer.align || 'center',
            color: layer.color || '#FFF7E6',
            editable: Number(layer.editable ?? 0),
            field: layer.field || '',
            src: layer.src || '',
            src_url: layer.src_url || layer.src || '',
            fit: layer.fit || 'cover',
            backgroundFill: layer.backgroundFill || '',
            radius: Number(layer.radius ?? 0),
            fill: layer.fill || '#FFFFFF',
            stroke: layer.stroke || '#D8C08B',
            strokeWidth: Number(layer.strokeWidth ?? 2),
            padding: Number(layer.padding ?? 18)
        }))
    const qrcodeIndex = normalized.findIndex((layer: any) => layer.type === 'qrcode')
    if (qrcodeIndex < 0) {
        normalized.push(createQrcodeLayer())
    } else {
        const qrcodeLayer = normalizeQrcodeLayer(normalized[qrcodeIndex])
        normalized.splice(qrcodeIndex, 1, qrcodeLayer)
        for (let index = normalized.length - 1; index >= 0; index--) {
            if (index !== qrcodeIndex && normalized[index]?.type === 'qrcode') {
                normalized.splice(index, 1)
            }
        }
    }
    return normalized
}

function layerStyle(layer: any) {
    return {
        left: `${Number(layer.x || 0) * scale}px`,
        top: `${Number(layer.y || 0) * scale}px`,
        width: `${Number(layer.w || 1) * scale}px`,
        height: `${layerPreviewHeight(layer) * scale}px`,
        opacity: isRequiredQrcodeLayer(layer) ? 1 : (layer.visible === 1 ? Number(layer.opacity ?? 1) : 0.24),
        transform: `rotate(${Number(layer.rotate || 0)}deg)`,
        zIndex: Number(layer.z || 0),
    }
}

function layerPreviewHeight(layer: any) {
    const height = Number(layer.h ?? 1)
    if (layer.type === 'line') {
        return Math.max(1, Math.abs(height), Number(layer.strokeWidth || 2))
    }
    return Math.max(1, height)
}

function isRequiredQrcodeLayer(layer: any) {
    return layer?.type === 'qrcode'
}

function createQrcodeLayer() {
    return normalizeQrcodeLayer({
        ...baseDesignLayer('qrcode', 'qrcode', 438, 1398, 204, 204, 9),
        padding: 18,
        radius: 18
    })
}

function normalizeQrcodeLayer(layer: any) {
    return {
        ...layer,
        id: 'qrcode',
        type: 'qrcode',
        visible: 1,
        opacity: 1,
        w: Math.max(qrcodeMinSize, Number(layer.w || 204)),
        h: Math.max(qrcodeMinSize, Number(layer.h || 204)),
        src: '',
        src_url: layer.src_url || '',
        editable: 0,
        field: '',
        padding: Number(layer.padding ?? 18),
        radius: Number(layer.radius ?? 18)
    }
}

function layerLayoutHeight(layer: any) {
    const height = Number(layer.h ?? 1)
    if (layer.type === 'line') {
        return Math.max(0, Math.abs(height))
    }
    return Math.max(1, height)
}

function layerMinHeight(layer: any) {
    if (layer?.type === 'line') {
        return 0
    }
    return layer?.type === 'qrcode' ? qrcodeMinSize : 1
}

function qrcodeImageSrc(layer: any) {
    return formatImageUrl(layer?.src_url || props.previewSnapshot?.qrcode_image || '')
}

function layerImageSrc(layer: any) {
    return formatImageUrl(layer?.src_url || layer?.src || '')
}

function formatImageUrl(url: string) {
    return appStore.getImageUrl(String(url || ''))
}

function textStyle(layer: any) {
    return {
        color: layer.color || '#FFF7E6',
        fontSize: `${Number(layer.fontSize || 42) * scale}px`,
        fontWeight: layer.fontWeight || '400',
        lineHeight: String(layer.lineHeight || 1.35),
        textAlign: layer.align || 'center',
    }
}

function rectStyle(layer: any) {
    return {
        background: layer.fill || '#FFFFFF',
        border: `${Number(layer.strokeWidth || 0) * scale}px solid ${layer.stroke || 'transparent'}`,
        borderRadius: `${Number(layer.radius || 0) * scale}px`
    }
}

function imageBoxStyle(layer: any): StyleValue {
    return {
        background: layer.backgroundFill || 'transparent',
        borderRadius: `${Number(layer.radius || 0) * scale}px`
    }
}

function imageStyle(layer: any): StyleValue {
    return {
        objectFit: layer.fit === 'contain' ? 'contain' : 'cover',
        borderRadius: `${Number(layer.radius || 0) * scale}px`
    }
}

function renderLayerText(layer: any) {
    const vars = props.previewSnapshot?.variables || props.previewSnapshot || {}
    return String(layer.text || '').replace(/\{([a-zA-Z0-9_]+)\}/g, (_match, key) => String(vars[key] ?? `{${key}}`))
}

function lineViewBox(layer: any) {
    return `0 0 ${lineX2(layer)} ${Math.max(1, Math.abs(lineY2(layer)))}`
}

function lineX2(layer: any) {
    return Math.max(1, Number(layer.w || 1))
}

function lineY2(layer: any) {
    return Number(layer.h || 0)
}

function linePreviewStrokeWidth(layer: any) {
    return Math.max(1, Number(layer.strokeWidth || 2) * scale)
}

function layerName(layer: any) {
    const map: Record<string, string> = { text: '文本', image: '图片', qrcode: '二维码', rect: '矩形', line: '分割线' }
    return `${map[layer.type] || '图层'} · ${layer.id}`
}

function selectLayer(layer: any) {
    activeLayerId.value = layer.id
}

function toggleLayerVisible(layer: any, value: string | number | boolean) {
    if (isRequiredQrcodeLayer(layer)) {
        layer.visible = 1
        return
    }
    layer.visible = value ? 1 : 0
}

function clearSelection() {
    activeLayerId.value = ''
}

function baseLayer(type: string) {
    const z = localDesign.layers.length ? Math.max(...localDesign.layers.map((layer: any) => Number(layer.z || 0))) + 1 : 1
    return {
        id: `${type}_${Date.now()}`,
        type,
        x: 260,
        y: 760,
        w: 560,
        h: 140,
        z,
        visible: 1,
        locked: 0,
        opacity: 1,
        rotate: 0
    }
}

function pushLayer(layer: any) {
    localDesign.layers.push(layer)
    activeLayerId.value = layer.id
    panelTab.value = 'props'
}

function addTextLayer() {
    pushLayer({
        ...baseLayer('text'),
        text: '自定义文字',
        fontSize: 48,
        fontWeight: '500',
        lineHeight: 1.35,
        align: 'center',
        color: '#FFF7E6',
        editable: 1,
        field: ''
    })
}

function addVariableLayer() {
    pushLayer({
        ...baseLayer('text'),
        text: '{service_date_label} · {service_name}',
        fontSize: 38,
        fontWeight: '500',
        lineHeight: 1.3,
        align: 'center',
        color: '#D8C08B',
        editable: 0,
        field: ''
    })
}

function insertDynamicField(token: string) {
    if (activeLayer.value?.type === 'text') {
        activeLayer.value.text = appendDynamicToken(activeLayer.value.text, token)
        panelTab.value = 'props'
        return
    }

    pushLayer({
        ...baseLayer('text'),
        text: token,
        fontSize: 38,
        fontWeight: '500',
        lineHeight: 1.3,
        align: 'center',
        color: '#D8C08B',
        editable: 0,
        field: ''
    })
}

function appendDynamicToken(value: any, token: string) {
    const text = String(value || '').trim()
    if (!text) {
        return token
    }
    const joiner = /[\s，。；、,.]$/.test(text) ? '' : ' '
    return `${text}${joiner}${token}`
}

function addImageLayer() {
    if (!newImageUrl.value) return
    pushLayer({
        ...baseLayer('image'),
        src: newImageUrl.value,
        src_url: newImageUrl.value,
        fit: 'cover',
        backgroundFill: '',
        radius: 16
    })
    newImageUrl.value = ''
}

function addRectLayer() {
    pushLayer({
        ...baseLayer('rect'),
        fill: '#12100D',
        stroke: '#D8C08B',
        strokeWidth: 2,
        radius: 24
    })
}

function addLineLayer() {
    pushLayer({
        ...baseLayer('line'),
        h: 0,
        stroke: '#D8C08B',
        strokeWidth: 2
    })
}

function duplicateLayer() {
    if (!activeLayer.value) return
    if (isRequiredQrcodeLayer(activeLayer.value)) return
    pushLayer({
        ...JSON.parse(JSON.stringify(activeLayer.value)),
        id: `${activeLayer.value.type}_${Date.now()}`,
        x: Number(activeLayer.value.x || 0) + 32,
        y: Number(activeLayer.value.y || 0) + 32,
        z: Math.max(...localDesign.layers.map((layer: any) => Number(layer.z || 0))) + 1
    })
}

function deleteLayer() {
    if (!activeLayer.value) return
    if (isRequiredQrcodeLayer(activeLayer.value)) return
    const index = localDesign.layers.findIndex((layer: any) => layer.id === activeLayer.value.id)
    if (index >= 0) {
        localDesign.layers.splice(index, 1)
        activeLayerId.value = localDesign.layers[0]?.id || ''
    }
}

function moveLayer(delta: number) {
    if (!activeLayer.value) return
    activeLayer.value.z = Number(activeLayer.value.z || 0) + delta
}

function applyLayerAlign(action: LayerAlignAction) {
    const layer = activeLayer.value
    if (!layer || layer.locked === 1) return
    const width = Math.max(1, Number(layer.w || 1))
    const height = layerLayoutHeight(layer)
    switch (action) {
        case 'left':
            layer.x = 0
            break
        case 'horizontalCenter':
            layer.x = Math.round((canvasWidth - width) / 2)
            break
        case 'right':
            layer.x = Math.round(canvasWidth - width)
            break
        case 'top':
            layer.y = 0
            break
        case 'verticalCenter':
            layer.y = Math.round((canvasHeight - height) / 2)
            break
        case 'bottom':
            layer.y = Math.round(canvasHeight - height)
            break
    }
}

function setTextAlign(action: TextAlignAction) {
    if (!activeLayer.value || activeLayer.value.type !== 'text') return
    activeLayer.value.align = action
}

function resetDefault() {
    Object.assign(localDesign, normalizeDesign(createDefaultDesign()))
}

function startDrag(layer: any, event: MouseEvent) {
    if (layer.locked === 1) return
    activeLayerId.value = layer.id
    dragging.value = {
        layer,
        startX: event.clientX,
        startY: event.clientY,
        originX: Number(layer.x || 0),
        originY: Number(layer.y || 0)
    }
    window.addEventListener('mousemove', onDrag)
    window.addEventListener('mouseup', stopDrag)
}

function onDrag(event: MouseEvent) {
    if (!dragging.value) return
    const dx = (event.clientX - dragging.value.startX) / scale
    const dy = (event.clientY - dragging.value.startY) / scale
    dragging.value.layer.x = Math.round(dragging.value.originX + dx)
    dragging.value.layer.y = Math.round(dragging.value.originY + dy)
}

function stopDrag() {
    dragging.value = null
    window.removeEventListener('mousemove', onDrag)
    window.removeEventListener('mouseup', stopDrag)
}

function startResize(layer: any, event: MouseEvent) {
    if (layer.locked === 1) return
    activeLayerId.value = layer.id
    resizing.value = {
        layer,
        startX: event.clientX,
        startY: event.clientY,
        originW: Number(layer.w || 1),
        originH: Number(layer.h || 1)
    }
    window.addEventListener('mousemove', onResize)
    window.addEventListener('mouseup', stopResize)
}

function onResize(event: MouseEvent) {
    if (!resizing.value) return
    const dx = (event.clientX - resizing.value.startX) / scale
    const dy = (event.clientY - resizing.value.startY) / scale
    const minSize = resizing.value.layer.type === 'qrcode' ? qrcodeMinSize : 1
    const minHeight = resizing.value.layer.type === 'line' ? 0 : minSize
    resizing.value.layer.w = Math.max(minSize, Math.round(resizing.value.originW + dx))
    resizing.value.layer.h = Math.max(minHeight, Math.round(resizing.value.originH + dy))
}

function stopResize() {
    resizing.value = null
    window.removeEventListener('mousemove', onResize)
    window.removeEventListener('mouseup', stopResize)
}
</script>

<style scoped lang="scss">
.letter-designer {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.designer-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    border: 1px solid #e8ecf3;
    border-radius: 8px;
    background: #fff;
}

.designer-toolbar__group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.designer-body {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 16px;
    align-items: start;
}

.designer-canvas-shell {
    min-height: 720px;
    border-radius: 8px;
    background: #f5f7fb;
    border: 1px solid #e8ecf3;
    padding: 18px;
    overflow: auto;
}

.designer-canvas-scale {
    margin: 0 auto;
}

.designer-canvas {
    position: relative;
    overflow: hidden;
    box-shadow: 0 18px 48px rgba(31, 27, 22, 0.16);
}

.designer-layer {
    position: absolute;
    cursor: move;
    box-sizing: border-box;
    outline: 1px dashed transparent;
}

.designer-layer.is-active {
    outline-color: #409eff;
}

.designer-layer.is-hidden {
    filter: grayscale(1);
}

.designer-layer__image-box,
.designer-layer img,
.designer-layer__rect {
    width: 100%;
    height: 100%;
    display: block;
}

.designer-layer__image-box {
    overflow: hidden;
}

.designer-layer img {
    pointer-events: none;
    user-select: none;
    -webkit-user-drag: none;
}

.designer-layer__text {
    width: 100%;
    height: 100%;
    overflow: hidden;
    word-break: break-word;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
    box-sizing: border-box;
}

.designer-layer__qr {
    width: 100%;
    height: 100%;
    display: grid;
    place-items: center;
    border-radius: 6px;
    background: #fff;
    color: #1f1b16;
    font-weight: 700;
}

.designer-layer__qr img {
    width: 84%;
    height: 84%;
    object-fit: contain;
    display: block;
}

.designer-layer__line {
    width: 100%;
    height: 100%;
    display: block;
    overflow: visible;
}

.resize-handle {
    position: absolute;
    right: -5px;
    bottom: -5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #409eff;
    cursor: nwse-resize;
    display: none;
}

.designer-layer.is-active .resize-handle {
    display: block;
}

.designer-panel {
    border: 1px solid #e8ecf3;
    border-radius: 8px;
    background: #fff;
    padding: 12px;
}

.layer-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 360px;
    overflow: auto;
}

.layer-row {
    height: 38px;
    border: 1px solid #e8ecf3;
    border-radius: 8px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 10px;
    color: #3f4654;
}

.layer-row.is-active {
    border-color: #409eff;
    color: #1677ff;
}

.panel-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.designer-form {
    :deep(.el-form-item) {
        margin-bottom: 12px;
    }
}

.dynamic-field-guide {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dynamic-field-guide__title {
    font-size: 14px;
    font-weight: 700;
    color: #1f2933;
}

.dynamic-field-guide__desc {
    font-size: 12px;
    line-height: 1.6;
    color: #7b8794;
}

.dynamic-field-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.dynamic-field-card {
    min-width: 0;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #e8ecf3;
    background: #fff;
    display: flex;
    flex-direction: column;
    gap: 4px;
    text-align: left;
    cursor: pointer;
}

.dynamic-field-card:hover {
    border-color: #409eff;
    background: #f6f8ff;
}

.dynamic-field-card__label {
    font-size: 13px;
    font-weight: 700;
    color: #1f2933;
}

.dynamic-field-card__token {
    font-size: 12px;
    color: #1677ff;
    word-break: break-all;
}

.dynamic-field-card__desc {
    font-size: 12px;
    color: #8b95a5;
}

.text-editor-field {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dynamic-field-inline {
    padding: 10px;
    border-radius: 8px;
    background: #f7f9fc;
    border: 1px solid #e8ecf3;
}

.dynamic-field-inline__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 12px;
    color: #7b8794;
}

.dynamic-field-inline__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.dynamic-field-chip {
    min-height: 30px;
    padding: 0 9px;
    border-radius: 6px;
    border: 1px solid #d8e2f0;
    background: #fff;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #1f2933;
    cursor: pointer;
}

.dynamic-field-chip code {
    font-size: 11px;
    color: #1677ff;
}

.image-background-field {
    display: flex;
    align-items: center;
    gap: 10px;
}

.align-shortcuts {
    display: grid;
    grid-template-columns: repeat(3, 32px);
    gap: 8px;
}

.align-shortcuts--text {
    grid-template-columns: repeat(3, 32px);
}

.align-shortcut-button {
    width: 32px;
    height: 30px;
    padding: 0;
    margin-left: 0 !important;
}

.prop-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    width: 100%;
}

@media (max-width: 1280px) {
    .designer-body {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
