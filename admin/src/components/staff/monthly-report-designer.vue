<template>
    <div class="monthly-report-designer">
        <div class="designer-toolbar">
            <div class="designer-toolbar__group">
                <el-button size="small" @click="addTextLayer">文本</el-button>
                <el-popover placement="bottom-start" width="420" trigger="click" :teleported="false">
                    <template #reference>
                        <el-button size="small">动态字段</el-button>
                    </template>
                    <div class="field-guide">
                        <div class="field-guide__title">月报字段</div>
                        <div class="field-guide__list">
                            <button
                                v-for="field in dynamicFields"
                                :key="field.token"
                                type="button"
                                class="field-guide__item"
                                @click="insertDynamicField(field.token)"
                            >
                                <span>{{ field.label }}</span>
                                <code>{{ field.token }}</code>
                            </button>
                        </div>
                    </div>
                </el-popover>
                <el-button size="small" @click="addRectLayer">矩形</el-button>
                <el-button size="small" @click="addLineLayer">分割线</el-button>
                <el-button size="small" @click="addQrcodeLayer">二维码</el-button>
                <el-button size="small" @click="addRepeaterLayer">人员卡片</el-button>
            </div>
            <div class="designer-toolbar__group">
                <material-picker v-model="newImageUrl" :limit="1">
                    <template #upload>
                        <el-button size="small">选择图片</el-button>
                    </template>
                </material-picker>
                <el-button size="small" :disabled="!normalizeUrl(newImageUrl)" @click="addImageLayer">添加图片</el-button>
                <el-button size="small" @click="resetDefault">恢复默认</el-button>
                <el-button size="small" type="primary" plain @click="$emit('preview')">刷新预览</el-button>
            </div>
        </div>

        <div class="designer-body">
            <div class="canvas-shell">
                <div class="canvas-wrap" :style="{ width: `${resolvedPreviewWidth}px` }">
                    <div class="canvas" :style="canvasStyle" @mousedown="clearSelection">
                        <img
                            v-if="backgroundImage"
                            class="canvas__background-image"
                            :src="backgroundImage"
                            :style="{ objectFit: localDesign.background.fit === 'contain' ? 'contain' : 'cover', opacity: localDesign.background.opacity }"
                            alt=""
                            draggable="false"
                        />
                        <div
                            v-for="layer in sortedLayers"
                            :key="layer.id"
                            :class="['layer', `layer--${layer.type}`, { 'is-active': activeLayer?.id === layer.id, 'is-hidden': layer.visible !== 1 }]"
                            :style="layerBoxStyle(layer)"
                            @mousedown.stop="startLayerDrag(layer, $event)"
                        >
                            <template v-if="layer.type === 'text'">
                                <div class="layer__text" :style="textPreviewStyle(layer)">{{ renderLayerText(layer) }}</div>
                            </template>
                            <template v-else-if="layer.type === 'image'">
                                <div class="layer__image" :style="imagePreviewBoxStyle(layer)">
                                    <img v-if="layerImageSrc(layer)" :src="layerImageSrc(layer)" :style="imagePreviewStyle(layer)" alt="" draggable="false" @dragstart.prevent />
                                    <span v-else>图片</span>
                                </div>
                            </template>
                            <template v-else-if="layer.type === 'qrcode'">
                                <div class="layer__qr">
                                    <img v-if="qrcodeSrc(layer)" :src="qrcodeSrc(layer)" alt="" draggable="false" @dragstart.prevent />
                                    <span v-else>QR</span>
                                </div>
                            </template>
                            <template v-else-if="layer.type === 'rect'">
                                <div class="layer__rect" :style="rectPreviewStyle(layer)" />
                            </template>
                            <template v-else-if="layer.type === 'line'">
                                <div class="layer__line" :style="linePreviewStyle(layer)" />
                            </template>
                            <template v-else>
                                <div class="repeater-preview" :style="repeaterPreviewStyle(layer)">
                                    <div
                                        v-for="(item, index) in repeaterItems(layer)"
                                        :key="`${layer.id}_${index}`"
                                        class="repeater-card"
                                        :style="repeaterCardStyle(layer, index)"
                                    >
                                        <div
                                            v-for="cardLayer in normalizedCardLayers(layer)"
                                            :key="cardLayer.id"
                                            :class="['card-layer', `card-layer--${cardLayer.type}`]"
                                            :style="cardLayerStyle(cardLayer)"
                                        >
                                            <template v-if="cardLayer.type === 'text'">
                                                <div class="card-layer__text" :style="cardTextStyle(cardLayer)">{{ renderCardText(cardLayer, item) }}</div>
                                            </template>
                                            <template v-else-if="cardLayer.type === 'image'">
                                                <div class="card-layer__image" :style="cardImageBoxStyle(cardLayer)">
                                                    <img v-if="cardImageSrc(cardLayer, item)" :src="cardImageSrc(cardLayer, item)" :style="cardImageStyle(cardLayer)" alt="" draggable="false" />
                                                </div>
                                            </template>
                                            <template v-else-if="cardLayer.type === 'rect'">
                                                <div class="card-layer__rect" :style="cardRectStyle(cardLayer)" />
                                            </template>
                                            <template v-else>
                                                <div class="card-layer__line" :style="cardLineStyle(cardLayer)" />
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <span v-if="activeLayer?.id === layer.id && layer.type !== 'repeater'" class="resize-handle" @mousedown.stop="startResize(layer, $event)" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="designer-panel">
                <el-tabs v-model="panelTab">
                    <el-tab-pane label="画布" name="canvas">
                        <el-form label-width="84px" class="designer-form">
                            <el-form-item label="画布宽高">
                                <div class="prop-grid">
                                    <el-input-number v-model="localDesign.canvas.width" :min="320" :max="2160" size="small" />
                                    <el-input-number v-model="localDesign.canvas.height" :min="480" :max="5200" size="small" />
                                </div>
                            </el-form-item>
                            <el-form-item v-if="templateType === 'ranking'" label="自动高度">
                                <el-switch v-model="localDesign.canvas.auto_height" :active-value="1" :inactive-value="0" />
                            </el-form-item>
                            <el-form-item v-if="templateType === 'ranking'" label="最小高度">
                                <el-input-number v-model="localDesign.canvas.min_height" :min="480" :max="5200" size="small" />
                            </el-form-item>
                            <el-form-item label="背景类型">
                                <el-radio-group v-model="localDesign.background.type">
                                    <el-radio-button label="color">纯色</el-radio-button>
                                    <el-radio-button label="image">图片</el-radio-button>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item label="背景色">
                                <el-color-picker v-model="localDesign.background.color" show-alpha />
                            </el-form-item>
                            <el-form-item label="背景图">
                                <material-picker v-model="localDesign.background.image" :limit="1" />
                            </el-form-item>
                            <el-form-item label="图片填充">
                                <el-radio-group v-model="localDesign.background.fit">
                                    <el-radio-button label="cover">铺满</el-radio-button>
                                    <el-radio-button label="contain">完整</el-radio-button>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item label="透明度">
                                <el-slider v-model="localDesign.background.opacity" :min="0" :max="1" :step="0.05" />
                            </el-form-item>
                        </el-form>
                    </el-tab-pane>

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
                                <el-switch :model-value="layer.visible === 1" size="small" :disabled="isRequiredQrcodeLayer(layer)" @click.stop @change="toggleLayerVisible(layer, $event)" />
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
                        <el-empty v-if="!activeLayer" description="请选择一个图层" :image-size="72" />
                        <el-form v-else label-width="84px" class="designer-form">
                            <el-form-item label="位置">
                                <div class="prop-grid">
                                    <el-input-number v-model="activeLayer.x" :min="-2160" :max="4320" size="small" />
                                    <el-input-number v-model="activeLayer.y" :min="-5200" :max="10400" size="small" />
                                </div>
                            </el-form-item>
                            <el-form-item v-if="activeLayer.type !== 'repeater'" label="尺寸">
                                <div class="prop-grid">
                                    <el-input-number v-model="activeLayer.w" :min="activeLayer.type === 'qrcode' ? qrcodeMinSize : 1" :max="2160" size="small" />
                                    <el-input-number v-model="activeLayer.h" :min="layerMinHeight(activeLayer)" :max="5200" size="small" />
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
                            <el-form-item label="层级">
                                <el-input-number v-model="activeLayer.z" :min="-999" :max="999" size="small" class="w-full" />
                            </el-form-item>
                            <el-form-item v-if="activeLayer.type !== 'qrcode'" label="透明度">
                                <el-slider v-model="activeLayer.opacity" :min="0" :max="1" :step="0.05" />
                            </el-form-item>
                            <el-form-item label="旋转">
                                <el-input-number v-model="activeLayer.rotate" :min="-360" :max="360" size="small" class="w-full" />
                            </el-form-item>

                            <template v-if="activeLayer.type === 'text'">
                                <text-layer-fields :layer="activeLayer" :fields="dynamicFields" @insert="insertDynamicField" />
                            </template>

                            <template v-if="activeLayer.type === 'image'">
                                <el-form-item label="图片">
                                    <material-picker v-model="activeLayer.src" :limit="1" />
                                </el-form-item>
                                <image-layer-fields :layer="activeLayer" />
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
                                <rect-layer-fields :layer="activeLayer" />
                            </template>

                            <template v-if="activeLayer.type === 'line'">
                                <line-layer-fields :layer="activeLayer" />
                            </template>

                            <template v-if="activeLayer.type === 'repeater'">
                                <el-form-item label="数据源">
                                    <el-radio-group v-model="activeLayer.source">
                                        <el-radio-button label="ranking">执行榜</el-radio-button>
                                        <el-radio-button label="top">单王</el-radio-button>
                                    </el-radio-group>
                                </el-form-item>
                                <el-form-item label="列数/限制">
                                    <div class="prop-grid">
                                        <el-input-number v-model="activeLayer.columns" :min="1" :max="6" size="small" />
                                        <el-input-number v-model="activeLayer.limit" :min="0" :max="120" size="small" />
                                    </div>
                                </el-form-item>
                                <el-form-item label="卡片宽高">
                                    <div class="prop-grid">
                                        <el-input-number v-model="activeLayer.card_width" :min="80" :max="2160" size="small" />
                                        <el-input-number v-model="activeLayer.card_height" :min="80" :max="2160" size="small" />
                                    </div>
                                </el-form-item>
                                <el-form-item label="间距">
                                    <div class="prop-grid">
                                        <el-input-number v-model="activeLayer.gap_x" :min="0" :max="320" size="small" />
                                        <el-input-number v-model="activeLayer.gap_y" :min="0" :max="320" size="small" />
                                    </div>
                                </el-form-item>
                                <el-form-item label="排列">
                                    <el-radio-group v-model="activeLayer.align">
                                        <el-radio-button label="left">左</el-radio-button>
                                        <el-radio-button label="center">中</el-radio-button>
                                        <el-radio-button label="right">右</el-radio-button>
                                    </el-radio-group>
                                </el-form-item>
                                <el-divider content-position="left">卡片子图层</el-divider>
                                <div class="card-layer-toolbar">
                                    <el-button size="small" @click="addCardLayer('text')">文本</el-button>
                                    <el-button size="small" @click="addCardLayer('image')">照片</el-button>
                                    <el-button size="small" @click="addCardLayer('rect')">矩形</el-button>
                                    <el-button size="small" @click="addCardLayer('line')">分割线</el-button>
                                </div>
                                <div class="card-layer-list">
                                    <button
                                        v-for="(cardLayer, index) in activeLayer.card_layers"
                                        :key="cardLayer.id"
                                        :class="['card-layer-row', { 'is-active': activeCardLayerIndex === index }]"
                                        type="button"
                                        @click="activeCardLayerIndex = index"
                                    >
                                        <span>{{ cardLayerName(cardLayer) }}</span>
                                        <el-switch
                                            :model-value="cardLayer.visible !== 0"
                                            size="small"
                                            @click.stop
                                            @change="cardLayer.visible = $event ? 1 : 0"
                                        />
                                    </button>
                                </div>
                                <div class="panel-actions">
                                    <el-button size="small" :disabled="!activeCardLayer" @click="duplicateCardLayer">复制子层</el-button>
                                    <el-button size="small" type="danger" :disabled="!activeCardLayer" @click="deleteCardLayer">删除子层</el-button>
                                </div>
                                <el-form v-if="activeCardLayer" label-width="84px" class="designer-form card-layer-form">
                                    <el-form-item label="位置">
                                        <div class="prop-grid">
                                            <el-input-number v-model="activeCardLayer.x" :min="-2160" :max="4320" size="small" />
                                            <el-input-number v-model="activeCardLayer.y" :min="-5200" :max="10400" size="small" />
                                        </div>
                                    </el-form-item>
                                    <el-form-item label="尺寸">
                                        <div class="prop-grid">
                                            <el-input-number v-model="activeCardLayer.w" :min="1" :max="2160" size="small" />
                                            <el-input-number v-model="activeCardLayer.h" :min="activeCardLayer.type === 'line' ? 0 : 1" :max="2160" size="small" />
                                        </div>
                                    </el-form-item>
                                    <el-form-item label="透明度">
                                        <el-slider v-model="activeCardLayer.opacity" :min="0" :max="1" :step="0.05" />
                                    </el-form-item>
                                    <template v-if="activeCardLayer.type === 'text'">
                                        <text-layer-fields :layer="activeCardLayer" :fields="cardFields" @insert="insertCardField" />
                                    </template>
                                    <template v-if="activeCardLayer.type === 'image'">
                                        <el-form-item label="图片字段">
                                            <el-input v-model="activeCardLayer.src" maxlength="500" />
                                        </el-form-item>
                                        <div class="field-chips">
                                            <button v-for="field in cardFields" :key="field.token" type="button" @click="insertCardField(field.token)">
                                                {{ field.label }}
                                            </button>
                                        </div>
                                        <image-layer-fields :layer="activeCardLayer" />
                                    </template>
                                    <template v-if="activeCardLayer.type === 'rect'">
                                        <rect-layer-fields :layer="activeCardLayer" />
                                    </template>
                                    <template v-if="activeCardLayer.type === 'line'">
                                        <line-layer-fields :layer="activeCardLayer" />
                                    </template>
                                </el-form>
                            </template>
                        </el-form>
                    </el-tab-pane>
                </el-tabs>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, nextTick, onBeforeUnmount, ref, resolveComponent, watch } from 'vue'
import type { CSSProperties } from 'vue'
import MaterialPicker from '@/components/material/picker.vue'
import {
    buildPosterTextPreviewStyle,
    posterAlignShortcutActions,
    posterTextArtPresets,
    type PosterLayerAlignAction,
} from './poster-designer-common'

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({}),
    },
    previewSnapshot: {
        type: Object,
        default: () => ({}),
    },
    templateType: {
        type: String,
        default: 'addition',
    },
    previewWidth: {
        type: Number,
        default: 290,
    },
})

const emit = defineEmits(['update:modelValue', 'preview'])

const resolvedPreviewWidth = computed(() => Math.max(180, Math.min(520, Number(props.previewWidth || 290))))
const qrcodeMinSize = 160
const panelTab = ref('layers')
const newImageUrl = ref('')
const activeLayerId = ref('')
const activeCardLayerIndex = ref(0)
const syncing = ref(false)
const localDesign = ref<any>(normalizeDesign(props.modelValue, props.templateType))
const dragging = ref<any | null>(null)
const dragChanged = ref(false)
const resizing = ref<any | null>(null)
const resizeChanged = ref(false)
const alignShortcutActions = posterAlignShortcutActions

const dynamicFields = [
    { label: '月份', token: '{report_month_label}' },
    { label: '新增档期', token: '{addition_count}' },
    { label: '共执行', token: '{executed_count}' },
    { label: '单王场次', token: '{top_count}' },
    { label: '新增标题', token: '{addition_title}' },
    { label: '新增副标题', token: '{addition_subtitle}' },
    { label: '榜单标题', token: '{ranking_title}' },
    { label: '单王标题', token: '{top_title}' },
    { label: '页脚文案', token: '{footer_note}' },
]

const cardFields = [
    { label: '照片（兼容）', token: '{photo_url}' },
    { label: '头像素材', token: '{avatar_photo_url}' },
    { label: '半身素材', token: '{half_body_photo_url}' },
    { label: '英文名/拼音', token: '{english_name}' },
    { label: '人员姓名', token: '{chinese_name}' },
    { label: '场次', token: '{count}' },
    { label: '排名', token: '{rank}' },
]

const textArtPresets = posterTextArtPresets

function propertyGrid(children: any[]) {
    return h('div', { class: 'text-property-grid' }, children)
}

function propertyItem(label: string, control: any) {
    return h('div', { class: 'text-property-item' }, [
        h('label', label),
        control,
    ])
}

function collapseItem(title: string, name: string, children: any[]) {
    return h(resolveCollapseItem(), { title, name }, () => children)
}

const TextLayerFields = defineComponent({
    props: {
        layer: { type: Object, required: true },
        fields: { type: Array, default: () => [] },
    },
    emits: ['insert'],
    setup(componentProps, { emit: componentEmit }) {
        const activePanels = ref(['content', 'fields', 'layout'])
        return () => h(resolveCollapse(), {
            modelValue: activePanels.value,
            'onUpdate:modelValue': (value: string[]) => {
                activePanels.value = value
            },
            class: 'designer-property-collapse',
        }, () => [
            collapseItem('内容', 'content', [
                h(resolveInputTextarea(), {
                    modelValue: componentProps.layer.text,
                    'onUpdate:modelValue': (value: string) => {
                        componentProps.layer.text = value
                    },
                    type: 'textarea',
                    rows: 3,
                    maxlength: 500,
                    showWordLimit: true,
                }),
            ]),
            collapseItem('动态字段', 'fields', [
                h('div', { class: 'field-chips field-chips--compact' }, (componentProps.fields as any[]).map((field: any) => h('button', {
                    type: 'button',
                    onClick: () => componentEmit('insert', field.token),
                }, field.label))),
            ]),
            collapseItem('艺术字预设', 'preset', [
                h('div', { class: 'field-chips' }, textArtPresets.map((preset) => h('button', {
                    type: 'button',
                    class: componentProps.layer.artPreset === preset.value ? 'is-active' : '',
                    onClick: () => applyTextArtPreset(componentProps.layer, preset.value),
                }, preset.label))),
            ]),
            collapseItem('基础排版', 'layout', [
                propertyGrid([
                    propertyItem('字号', h(resolveInputNumber(), {
                    modelValue: componentProps.layer.fontSize,
                    'onUpdate:modelValue': (value: number) => {
                        componentProps.layer.fontSize = value
                    },
                    min: 10,
                    max: 400,
                    size: 'small',
                })),
                    propertyItem('字重', h(resolveSelect(), {
                    modelValue: componentProps.layer.fontWeight,
                    'onUpdate:modelValue': (value: string) => {
                        componentProps.layer.fontWeight = value
                    },
                }, () => ['300', '400', '500', '600', '700', '800', '900'].map((value) => h(resolveOption(), { label: value, value })))),
                    propertyItem('颜色', h(resolveColorPicker(), {
                    modelValue: componentProps.layer.color,
                    'onUpdate:modelValue': (value: string) => {
                        componentProps.layer.color = value
                    },
                    showAlpha: true,
                })),
                    propertyItem('对齐', h(resolveRadioGroup(), {
                    modelValue: componentProps.layer.align,
                    'onUpdate:modelValue': (value: string) => {
                        componentProps.layer.align = value
                    },
                    class: 'segmented-control',
                }, () => ['left', 'center', 'right'].map((value) => h(resolveRadioButton(), {
                    label: value,
                }, () => ({ left: '左', center: '中', right: '右' } as any)[value])))),
                    propertyItem('行高', h(resolveInputNumber(), {
                    modelValue: componentProps.layer.lineHeight,
                    'onUpdate:modelValue': (value: number) => {
                        componentProps.layer.lineHeight = value
                    },
                    min: 0.8,
                    max: 3,
                    step: 0.05,
                    size: 'small',
                })),
                    propertyItem('横向拉伸', h(resolveInputNumber(), {
                    modelValue: componentProps.layer.scaleX,
                    'onUpdate:modelValue': (value: number) => {
                        componentProps.layer.scaleX = value
                    },
                    min: 0.2,
                    max: 3,
                    step: 0.05,
                    size: 'small',
                })),
                    propertyItem('纵向拉伸', h(resolveInputNumber(), {
                    modelValue: componentProps.layer.scaleY,
                    'onUpdate:modelValue': (value: number) => {
                        componentProps.layer.scaleY = value
                    },
                    min: 0.2,
                    max: 3,
                    step: 0.05,
                    size: 'small',
                })),
                    propertyItem('字间距', h(resolveInputNumber(), {
                    modelValue: componentProps.layer.letterSpacing,
                    'onUpdate:modelValue': (value: number) => {
                        componentProps.layer.letterSpacing = value
                    },
                    min: -20,
                    max: 80,
                    step: 1,
                    size: 'small',
                })),
                ]),
            ]),
            collapseItem('填充', 'fill', [
                h(resolveRadioGroup(), {
                    modelValue: componentProps.layer.fillType,
                    'onUpdate:modelValue': (value: string) => {
                        componentProps.layer.fillType = value
                    },
                    class: 'segmented-control segmented-control--wide',
                }, () => [
                    h(resolveRadioButton(), { label: 'solid' }, () => '纯色'),
                    h(resolveRadioButton(), { label: 'linear' }, () => '渐变'),
                    h(resolveRadioButton(), { label: 'image' }, () => '图片'),
                ]),
                ...(componentProps.layer.fillType === 'linear' ? [
                    propertyGrid([
                        propertyItem('渐变起色', h(resolveColorPicker(), {
                        modelValue: componentProps.layer.gradientFrom,
                        'onUpdate:modelValue': (value: string) => {
                            componentProps.layer.gradientFrom = value
                        },
                        showAlpha: true,
                    })),
                        propertyItem('渐变止色', h(resolveColorPicker(), {
                        modelValue: componentProps.layer.gradientTo,
                        'onUpdate:modelValue': (value: string) => {
                            componentProps.layer.gradientTo = value
                        },
                        showAlpha: true,
                    })),
                        propertyItem('渐变角度', h(resolveInputNumber(), {
                        modelValue: componentProps.layer.gradientAngle,
                        'onUpdate:modelValue': (value: number) => {
                            componentProps.layer.gradientAngle = value
                        },
                        min: 0,
                        max: 360,
                        step: 1,
                        size: 'small',
                    })),
                    ]),
                ] : []),
                ...(componentProps.layer.fillType === 'image' ? [
                    h('div', { class: 'text-property-item text-property-item--full' }, [
                        h('label', '填充图片'),
                        h(MaterialPicker, {
                        modelValue: componentProps.layer.fillImage,
                        'onUpdate:modelValue': (value: any) => {
                            componentProps.layer.fillImage = value
                            componentProps.layer.fillImageUrl = normalizeUrl(value)
                        },
                        limit: 1,
                        }),
                    ]),
                    h('div', { class: 'text-property-item text-property-item--full' }, [
                        h('label', '图片填充'),
                        h(resolveRadioGroup(), {
                        modelValue: componentProps.layer.fillImageFit,
                        'onUpdate:modelValue': (value: string) => {
                            componentProps.layer.fillImageFit = value
                        },
                        class: 'segmented-control segmented-control--wide',
                    }, () => [
                        h(resolveRadioButton(), { label: 'cover' }, () => '铺满'),
                        h(resolveRadioButton(), { label: 'contain' }, () => '完整'),
                        h(resolveRadioButton(), { label: 'stretch' }, () => '拉伸'),
                        ]),
                    ]),
                ] : []),
            ]),
            collapseItem('描边', 'stroke', [
                propertyGrid([
                    propertyItem('描边色', h(resolveColorPicker(), {
                    modelValue: componentProps.layer.textStrokeColor,
                    'onUpdate:modelValue': (value: string) => {
                        componentProps.layer.textStrokeColor = value
                    },
                    showAlpha: true,
                })),
                    propertyItem('描边宽', h(resolveInputNumber(), {
                    modelValue: componentProps.layer.textStrokeWidth,
                    'onUpdate:modelValue': (value: number) => {
                        componentProps.layer.textStrokeWidth = value
                    },
                    min: 0,
                    max: 24,
                    step: 1,
                    size: 'small',
                })),
                    propertyItem('描边透明', h(resolveInputNumber(), {
                    modelValue: componentProps.layer.textStrokeOpacity,
                    'onUpdate:modelValue': (value: number) => {
                        componentProps.layer.textStrokeOpacity = value
                    },
                    min: 0,
                    max: 1,
                    step: 0.05,
                    size: 'small',
                })),
                ]),
            ]),
            collapseItem('阴影', 'shadow', [
                propertyGrid([
                    propertyItem('阴影色', h(resolveColorPicker(), {
                    modelValue: componentProps.layer.shadowColor,
                    'onUpdate:modelValue': (value: string) => {
                        componentProps.layer.shadowColor = value
                    },
                    showAlpha: true,
                })),
                    propertyItem('阴影模糊', h(resolveInputNumber(), {
                    modelValue: componentProps.layer.shadowBlur,
                    'onUpdate:modelValue': (value: number) => {
                        componentProps.layer.shadowBlur = value
                    },
                    min: 0,
                    max: 80,
                    step: 1,
                    size: 'small',
                })),
                    propertyItem('阴影X', h(resolveInputNumber(), {
                    modelValue: componentProps.layer.shadowOffsetX,
                    'onUpdate:modelValue': (value: number) => {
                        componentProps.layer.shadowOffsetX = value
                    },
                    min: -120,
                    max: 120,
                    step: 1,
                    size: 'small',
                })),
                    propertyItem('阴影Y', h(resolveInputNumber(), {
                    modelValue: componentProps.layer.shadowOffsetY,
                    'onUpdate:modelValue': (value: number) => {
                        componentProps.layer.shadowOffsetY = value
                    },
                    min: -120,
                    max: 120,
                    step: 1,
                    size: 'small',
                })),
                    propertyItem('阴影透明', h(resolveInputNumber(), {
                    modelValue: componentProps.layer.shadowOpacity,
                    'onUpdate:modelValue': (value: number) => {
                        componentProps.layer.shadowOpacity = value
                    },
                    min: 0,
                    max: 1,
                    step: 0.05,
                    size: 'small',
                })),
                ]),
            ]),
        ])
    },
})

const ImageLayerFields = defineComponent({
    props: {
        layer: { type: Object, required: true },
    },
    setup(componentProps) {
        return () => h('div', { class: 'layer-field-block layer-field-grid' }, [
            h('label', '填充'),
            h(resolveRadioGroup(), {
                modelValue: componentProps.layer.fit,
                'onUpdate:modelValue': (value: string) => {
                    componentProps.layer.fit = value
                },
            }, () => ['cover', 'contain'].map((value) => h(resolveRadioButton(), {
                label: value,
            }, () => value === 'cover' ? '铺满' : '完整'))),
            h('label', '底色'),
            h(resolveColorPicker(), {
                modelValue: componentProps.layer.backgroundFill,
                'onUpdate:modelValue': (value: string) => {
                    componentProps.layer.backgroundFill = value
                },
                showAlpha: true,
            }),
            h('label', '圆角'),
            h(resolveInputNumber(), {
                modelValue: componentProps.layer.radius,
                'onUpdate:modelValue': (value: number) => {
                    componentProps.layer.radius = value
                },
                min: 0,
                max: 260,
                size: 'small',
            }),
        ])
    },
})

const RectLayerFields = defineComponent({
    props: {
        layer: { type: Object, required: true },
    },
    setup(componentProps) {
        return () => h('div', { class: 'layer-field-block layer-field-grid' }, [
            h('label', '填充'),
            h(resolveColorPicker(), {
                modelValue: componentProps.layer.fill,
                'onUpdate:modelValue': (value: string) => {
                    componentProps.layer.fill = value
                },
                showAlpha: true,
            }),
            h('label', '描边'),
            h(resolveColorPicker(), {
                modelValue: componentProps.layer.stroke,
                'onUpdate:modelValue': (value: string) => {
                    componentProps.layer.stroke = value
                },
                showAlpha: true,
            }),
            h('label', '线宽'),
            h(resolveInputNumber(), {
                modelValue: componentProps.layer.strokeWidth,
                'onUpdate:modelValue': (value: number) => {
                    componentProps.layer.strokeWidth = value
                },
                min: 0,
                max: 40,
                size: 'small',
            }),
            h('label', '圆角'),
            h(resolveInputNumber(), {
                modelValue: componentProps.layer.radius,
                'onUpdate:modelValue': (value: number) => {
                    componentProps.layer.radius = value
                },
                min: 0,
                max: 260,
                size: 'small',
            }),
        ])
    },
})

const LineLayerFields = defineComponent({
    props: {
        layer: { type: Object, required: true },
    },
    setup(componentProps) {
        return () => h('div', { class: 'layer-field-block layer-field-grid' }, [
            h('label', '颜色'),
            h(resolveColorPicker(), {
                modelValue: componentProps.layer.stroke,
                'onUpdate:modelValue': (value: string) => {
                    componentProps.layer.stroke = value
                },
                showAlpha: true,
            }),
            h('label', '线宽'),
            h(resolveInputNumber(), {
                modelValue: componentProps.layer.strokeWidth,
                'onUpdate:modelValue': (value: number) => {
                    componentProps.layer.strokeWidth = value
                },
                min: 1,
                max: 40,
                size: 'small',
            }),
        ])
    },
})

watch(
    () => props.modelValue,
    (value) => {
        if (dragging.value || resizing.value) return
        syncing.value = true
        localDesign.value = normalizeDesign(value, props.templateType)
        nextTick(() => {
            syncing.value = false
        })
    },
    { deep: true }
)

watch(
    localDesign,
    (value) => {
        if (syncing.value) return
        if (dragging.value) {
            dragChanged.value = true
            return
        }
        if (resizing.value) {
            resizeChanged.value = true
            return
        }
        emitDesignUpdate(value)
    },
    { deep: true }
)

function emitDesignUpdate(value = localDesign.value) {
    emit('update:modelValue', clone(value))
}

const canvasScale = computed(() => {
    const width = Number(localDesign.value?.canvas?.width || 1080)
    return resolvedPreviewWidth.value / Math.max(1, width)
})

const canvasStyle = computed(() => {
    const width = Number(localDesign.value?.canvas?.width || 1080)
    const height = Number(localDesign.value?.canvas?.height || 1920)
    return {
        width: `${width * canvasScale.value}px`,
        height: `${height * canvasScale.value}px`,
        backgroundColor: localDesign.value?.background?.color || '#1B1510',
    }
})

const backgroundImage = computed(() => {
    if (localDesign.value?.background?.type !== 'image') return ''
    return normalizeUrl(localDesign.value?.background?.image_url || localDesign.value?.background?.image)
})

const sortedLayers = computed(() => {
    return [...(localDesign.value.layers || [])].sort((a, b) => Number(a.z || 0) - Number(b.z || 0))
})

const sortedLayersDesc = computed(() => {
    return [...sortedLayers.value].reverse()
})

const activeLayer = computed<any | null>(() => {
    return (localDesign.value.layers || []).find((item: any) => item.id === activeLayerId.value) || null
})

const activeCardLayer = computed<any | null>(() => {
    if (!activeLayer.value || activeLayer.value.type !== 'repeater') return null
    return activeLayer.value.card_layers?.[activeCardLayerIndex.value] || null
})

function normalizeDesign(value: any, type = 'addition') {
    const fallback = defaultDesign(type)
    const design = clone(value || {})
    const canvas: any = {
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
        layers: Array.isArray(design.layers) ? design.layers.map(normalizeLayer) : fallback.layers,
    }
}

function defaultDesign(type: string) {
    if (type === 'ranking') {
        return {
            canvas: { width: 1080, height: 3600, auto_height: 1, min_height: 1920 },
            background: { type: 'color', color: '#185C81', image: '', fit: 'cover', opacity: 1 },
            layers: [
                defaultRepeater('ranking', 30, 72, 3, 300, 300, 42, 42, 1, 24),
                defaultText('total', 30, 2960, 480, 220, 2, '{executed_count}', 210, '#FFF9ED', '300', 'left'),
                defaultText('summary', 34, 3180, 620, 62, 3, '*{ranking_title}', 40, '#FFFFFF', '400', 'left'),
                defaultQrcode(40, 3340, 170, 170, 4),
            ],
        }
    }
    if (type === 'top') {
        return {
            canvas: { width: 1080, height: 1920 },
            background: { type: 'color', color: '#F8F3EC', image: '', fit: 'cover', opacity: 1 },
            layers: [
                defaultText('top-title', 0, 18, 1080, 255, 1, '{top_title}', 196, '#C84A00', '900', 'center'),
                { id: 'hero-block', type: 'rect', x: 12, y: 620, w: 1056, h: 770, z: 2, fill: '#C84A00', stroke: '', strokeWidth: 0, radius: 0, visible: 1, opacity: 1, rotate: 0 },
                defaultRepeater('top-staffs', 42, 360, 3, 310, 860, 22, 0, 3, 3, 'top', defaultTopCardLayers(), 'center'),
                defaultText('brand-mark', 420, 1080, 240, 150, 4, '格林社\nGREEN SOCIETY CLUB\n衡水', 56, '#FFFFFF', '800', 'center'),
                defaultText('slogan', 230, 1282, 620, 58, 5, '主持就找格林社  圆满呈现每一刻', 38, '#FFFFFF', '700', 'center'),
                defaultText('year', 14, 1468, 190, 82, 6, '2026', 72, '#C84A00', '300', 'left'),
                defaultText('month-en', 210, 1474, 180, 76, 7, 'JAN.\n{report_month_label}', 26, '#111111', '700', 'left'),
                defaultText('award', 34, 1574, 330, 72, 8, '*月度单王', 52, '#111111', '400', 'left'),
                defaultText('names', 385, 1416, 310, 54, 9, '{top_staff_names}', 34, '#111111', '500', 'center'),
                defaultText('center-title', 385, 1468, 310, 205, 10, 'VIDING\nWANGCE', 76, '#C84A00', '900', 'center'),
                defaultText('center-script', 360, 1518, 360, 150, 11, 'yiding\nwangce', 60, '#111111', '300', 'center'),
                defaultText('honor', 330, 1666, 420, 86, 12, '以实力 荣获本月人气之星\n以专业 获得新人广泛认可', 28, '#111111', '400', 'center'),
                defaultText('count-label', 760, 1478, 260, 60, 13, '本月共计主持', 34, '#C84A00', '500', 'right'),
                defaultText('count', 735, 1525, 225, 135, 14, '{top_count}', 128, '#111111', '300', 'right'),
                defaultText('unit', 970, 1604, 48, 40, 15, '场', 24, '#C84A00', '500', 'left'),
                defaultText('contact', 14, 1768, 360, 120, 16, '*联系我们\nCONTACT US\n河北省衡水市桃城区汇宁创业A座', 25, '#111111', '400', 'left'),
                defaultQrcode(858, 1740, 170, 170, 17),
                defaultText('qrcode-note', 812, 1906, 250, 36, 18, '扫码预约主持服务', 22, '#111111', '500', 'center'),
            ],
        }
    }
    return {
        canvas: { width: 1080, height: 1920 },
        background: { type: 'color', color: '#1B1510', image: '', fit: 'cover', opacity: 1 },
        layers: [
            defaultText('brand', 250, 150, 580, 80, 1, '格林社 · 衡水', 42, '#F4D08E', '700'),
            defaultText('title', 130, 390, 820, 120, 2, '{addition_title}', 96, '#F8CB79', '300'),
            defaultText('subtitle', 180, 520, 720, 70, 3, '{addition_subtitle}', 34, '#FFFFFF', '500'),
            defaultText('count', 55, 660, 970, 440, 4, '{addition_count}', 360, '#F5D18B', '800'),
            defaultText('unit', 500, 1135, 80, 54, 5, '场', 36, '#FFFFFF', '600'),
            defaultQrcode(438, 1570, 204, 204, 8),
        ],
    }
}

function normalizeLayer(layer: any) {
    const type = ['text', 'image', 'qrcode', 'rect', 'line', 'repeater'].includes(layer?.type) ? layer.type : 'text'
    const base: any = {
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
            w: Math.max(qrcodeMinSize, Number(base.w || qrcodeMinSize)),
            h: Math.max(qrcodeMinSize, Number(base.h || qrcodeMinSize)),
            src: '',
            src_url: normalizeUrl(layer?.src_url || ''),
            padding: Number(layer?.padding || 18),
            radius: Number(layer?.radius || 18),
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

function normalizeCardLayer(layer: any) {
    return normalizeLayer({ ...layer, z: 0, locked: 0 })
}

function layerBoxStyle(layer: any) {
    const scale = canvasScale.value
    const style: any = {
        left: `${Number(layer.x || 0) * scale}px`,
        top: `${Number(layer.y || 0) * scale}px`,
        width: `${layerWidth(layer) * scale}px`,
        height: `${layerHeight(layer) * scale}px`,
        opacity: layer.type === 'qrcode' ? 1 : Number(layer.opacity ?? 1),
        zIndex: Number(layer.z || 0) + 1000,
        transform: `rotate(${Number(layer.rotate || 0)}deg)`,
    }
    return style
}

function layerWidth(layer: any) {
    if (layer.type === 'repeater') {
        return Number(layer.columns || 1) * Number(layer.card_width || 300) + Math.max(0, Number(layer.columns || 1) - 1) * Number(layer.gap_x || 0)
    }
    return Number(layer.w || 120)
}

function layerHeight(layer: any) {
    if (layer.type === 'repeater') {
        const count = repeaterItems(layer).length || 1
        const rows = Math.max(1, Math.ceil(count / Math.max(1, Number(layer.columns || 1))))
        return rows * Number(layer.card_height || 300) + Math.max(0, rows - 1) * Number(layer.gap_y || 0)
    }
    return Number(layer.h ?? 80)
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

function textPreviewStyle(layer: any) {
    return buildPosterTextPreviewStyle(layer, canvasScale.value, normalizeUrl)
}

function imagePreviewBoxStyle(layer: any) {
    return {
        background: layer.backgroundFill || 'rgba(255,255,255,0.12)',
        borderRadius: `${Number(layer.radius || 0) * canvasScale.value}px`,
    }
}

function imagePreviewStyle(layer: any): CSSProperties {
    return {
        objectFit: layer.fit === 'contain' ? 'contain' : 'cover',
        borderRadius: `${Number(layer.radius || 0) * canvasScale.value}px`,
    }
}

function rectPreviewStyle(layer: any) {
    return {
        background: layer.fill || '#FFFFFF',
        border: Number(layer.strokeWidth || 0) > 0 ? `${Number(layer.strokeWidth) * canvasScale.value}px solid ${layer.stroke || '#FFFFFF'}` : 'none',
        borderRadius: `${Number(layer.radius || 0) * canvasScale.value}px`,
    }
}

function linePreviewStyle(layer: any) {
    return {
        borderTop: `${Math.max(1, Number(layer.strokeWidth || 2) * canvasScale.value)}px solid ${layer.stroke || '#D8C08B'}`,
    }
}

function repeaterPreviewStyle(layer: any) {
    return {
        width: `${layerWidth(layer) * canvasScale.value}px`,
        height: `${layerHeight(layer) * canvasScale.value}px`,
    }
}

function repeaterCardStyle(layer: any, index: number) {
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

function cardLayerStyle(layer: any) {
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

function cardTextStyle(layer: any) {
    return textPreviewStyle(layer)
}

function cardImageBoxStyle(layer: any) {
    return imagePreviewBoxStyle(layer)
}

function cardImageStyle(layer: any) {
    return imagePreviewStyle(layer)
}

function cardRectStyle(layer: any) {
    return rectPreviewStyle(layer)
}

function cardLineStyle(layer: any) {
    return linePreviewStyle(layer)
}

function renderLayerText(layer: any) {
    return wrapTextForRenderer(renderTemplate(layer.text || '', previewVars()), layer)
}

function renderCardText(layer: any, item: any) {
    return wrapTextForRenderer(renderTemplate(layer.text || '', { ...previewVars(), ...item }), layer)
}

function wrapTextForRenderer(text: string, layer: any) {
    const normalizedText = String(text || '').replace(/\s+/gu, ' ').trim()
    if (!normalizedText) {
        return ''
    }
    const fontSize = Math.max(10, Number(layer.fontSize || 42))
    const lineHeight = Math.max(0.8, Number(layer.lineHeight || 1.25))
    const maxLines = Math.max(1, Math.floor(Number(layer.h || 80) / Math.max(1, fontSize * lineHeight)))
    const maxChars = Math.max(1, Math.floor(Number(layer.w || 120) / Math.max(1, fontSize * 0.58)))
    const chars = Array.from(normalizedText)
    const lines: string[] = []
    let line = ''
    for (const char of chars) {
        line += char
        if (Array.from(line).length >= maxChars) {
            lines.push(line)
            line = ''
            if (lines.length >= maxLines) {
                break
            }
        }
    }
    if (line && lines.length < maxLines) {
        lines.push(line)
    }
    return lines.join('\n')
}

function layerImageSrc(layer: any) {
    return normalizeUrl(layer.src_url || layer.src)
}

function qrcodeSrc(layer: any) {
    return normalizeUrl(layer.src_url || props.previewSnapshot?.qrcode_image || props.previewSnapshot?.qrcode_url)
}

function cardImageSrc(layer: any, item: any) {
    return normalizeUrl(renderTemplate(layer.src || '', { ...previewVars(), ...item }))
}

function previewVars() {
    return {
        ...(props.previewSnapshot?.variables || {}),
        report_month_label: props.previewSnapshot?.report_month_label || '2026年5月',
        addition_count: props.previewSnapshot?.variables?.addition_count || '204',
        executed_count: props.previewSnapshot?.variables?.executed_count || '233',
        top_count: props.previewSnapshot?.variables?.top_count || '23',
        addition_title: props.previewSnapshot?.variables?.addition_title || 'ADDITION',
        addition_subtitle: props.previewSnapshot?.variables?.addition_subtitle || '2026年5月新增婚礼档期',
        ranking_title: props.previewSnapshot?.variables?.ranking_title || '2026年5月共计执行',
        top_title: props.previewSnapshot?.variables?.top_title || 'THE MOST',
        top_staff_names: props.previewSnapshot?.variables?.top_staff_names || topStaffNamesPreview(),
        footer_note: props.previewSnapshot?.variables?.footer_note || '感谢您的选择',
    }
}

function renderTemplate(template: string, variables: Record<string, any>) {
    return String(template || '').replace(/\{([a-zA-Z0-9_]+)\}/g, (_, key) => String(variables[key] ?? ''))
}

function repeaterItems(layer: any) {
    const rows = layer.source === 'top' ? props.previewSnapshot?.top_staffs : props.previewSnapshot?.ranking_staffs
    const fallback = [
        { photo_url: '', avatar_photo_url: '', half_body_photo_url: '', english_name: 'KEYU', chinese_name: '可玉', count: '21', rank: 1 },
        { photo_url: '', avatar_photo_url: '', half_body_photo_url: '', english_name: 'WANGFAN', chinese_name: '王帆', count: '22', rank: 2 },
        { photo_url: '', avatar_photo_url: '', half_body_photo_url: '', english_name: 'YIPING', chinese_name: '王一平', count: '23', rank: 3 },
    ]
    const items = Array.isArray(rows) && rows.length ? rows : fallback
    return Number(layer.limit || 0) > 0 ? items.slice(0, Number(layer.limit)) : items.slice(0, 24)
}

function topStaffNamesPreview() {
    const rows = props.previewSnapshot?.top_staffs
    const fallback = ['王一平', '王策']
    const names = Array.isArray(rows) && rows.length
        ? rows.map((item: any) => String(item?.chinese_name || '').trim()).filter(Boolean)
        : fallback
    return names.join('、')
}

function normalizedCardLayers(layer: any) {
    return Array.isArray(layer.card_layers) && layer.card_layers.length ? layer.card_layers : defaultCardLayers(Number(layer.card_width || 300), Number(layer.card_height || 300))
}

function selectLayer(layer: any) {
    activeLayerId.value = layer.id
    activeCardLayerIndex.value = 0
    panelTab.value = 'props'
}

function startLayerDrag(layer: any, event: MouseEvent) {
    if (event.button !== 0 || layer.locked === 1) return
    event.preventDefault()
    stopResize()
    stopLayerDrag()
    selectLayer(layer)
    dragging.value = {
        layerId: layer.id,
        startX: event.clientX,
        startY: event.clientY,
        originX: Number(layer.x || 0),
        originY: Number(layer.y || 0),
    }
    dragChanged.value = false
    window.addEventListener('mousemove', handleLayerDrag)
    window.addEventListener('mouseup', stopLayerDrag)
}

function handleLayerDrag(event: MouseEvent) {
    if (!dragging.value) return
    event.preventDefault()
    const layer = (localDesign.value.layers || []).find((item: any) => item.id === dragging.value.layerId)
    if (!layer) {
        stopLayerDrag()
        return
    }
    const scale = Math.max(0.01, canvasScale.value)
    const deltaX = (event.clientX - dragging.value.startX) / scale
    const deltaY = (event.clientY - dragging.value.startY) / scale
    layer.x = Math.round(dragging.value.originX + deltaX)
    layer.y = Math.round(dragging.value.originY + deltaY)
}

function stopLayerDrag() {
    const shouldEmit = Boolean(dragging.value && dragChanged.value)
    dragging.value = null
    window.removeEventListener('mousemove', handleLayerDrag)
    window.removeEventListener('mouseup', stopLayerDrag)
    if (shouldEmit) {
        dragChanged.value = false
        emitDesignUpdate()
    } else {
        dragChanged.value = false
    }
}

onBeforeUnmount(() => {
    stopLayerDrag()
    stopResize()
})

function clearSelection() {
    if (dragging.value || resizing.value) return
    activeLayerId.value = ''
}

function startResize(layer: any, event: MouseEvent) {
    if (event.button !== 0 || layer.locked === 1 || layer.type === 'repeater') return
    event.preventDefault()
    stopLayerDrag()
    stopResize()
    selectLayer(layer)
    resizing.value = {
        layerId: layer.id,
        startX: event.clientX,
        startY: event.clientY,
        originW: Number(layer.w || 1),
        originH: Number(layer.h || 1),
    }
    resizeChanged.value = false
    window.addEventListener('mousemove', handleResize)
    window.addEventListener('mouseup', stopResize)
}

function handleResize(event: MouseEvent) {
    if (!resizing.value) return
    event.preventDefault()
    const layer = (localDesign.value.layers || []).find((item: any) => item.id === resizing.value.layerId)
    if (!layer) {
        stopResize()
        return
    }
    const scale = Math.max(0.01, canvasScale.value)
    const deltaX = (event.clientX - resizing.value.startX) / scale
    const deltaY = (event.clientY - resizing.value.startY) / scale
    const minSize = layer.type === 'qrcode' ? qrcodeMinSize : 1
    const minHeight = layer.type === 'line' ? 0 : minSize
    layer.w = Math.max(minSize, Math.round(resizing.value.originW + deltaX))
    layer.h = Math.max(minHeight, Math.round(resizing.value.originH + deltaY))
}

function stopResize() {
    const shouldEmit = Boolean(resizing.value && resizeChanged.value)
    resizing.value = null
    window.removeEventListener('mousemove', handleResize)
    window.removeEventListener('mouseup', stopResize)
    if (shouldEmit) {
        resizeChanged.value = false
        emitDesignUpdate()
    } else {
        resizeChanged.value = false
    }
}

function addLayer(layer: any) {
    localDesign.value.layers.push(normalizeLayer(layer))
    activeLayerId.value = layer.id
    panelTab.value = 'props'
}

function addTextLayer() {
    addLayer(defaultText(uid('text'), 120, 160, 420, 80, nextZ(), '双击编辑文本', 48, '#FFFFFF', '600'))
}

function addImageLayer() {
    const src = normalizeUrl(newImageUrl.value)
    if (!src) return
    addLayer({ id: uid('image'), type: 'image', x: 120, y: 120, w: 260, h: 260, z: nextZ(), src, fit: 'cover', visible: 1, opacity: 1, rotate: 0, radius: 0 })
    newImageUrl.value = ''
}

function addRectLayer() {
    addLayer({ id: uid('rect'), type: 'rect', x: 120, y: 120, w: 320, h: 120, z: nextZ(), fill: '#FFFFFF', stroke: '', strokeWidth: 0, radius: 0, visible: 1, opacity: 1, rotate: 0 })
}

function addLineLayer() {
    addLayer({ id: uid('line'), type: 'line', x: 120, y: 120, w: 420, h: 0, z: nextZ(), stroke: '#D8C08B', strokeWidth: 4, visible: 1, opacity: 1, rotate: 0 })
}

function addQrcodeLayer() {
    addLayer(defaultQrcode(120, 120, 180, 180, nextZ()))
}

function addRepeaterLayer() {
    addLayer(defaultRepeater(uid('repeater'), 40, 160, props.templateType === 'top' ? 3 : 2, props.templateType === 'top' ? 310 : 300, props.templateType === 'top' ? 860 : 300, props.templateType === 'top' ? 22 : 24, 24, nextZ(), props.templateType === 'top' ? 3 : 12, props.templateType === 'top' ? 'top' : 'ranking', props.templateType === 'top' ? defaultTopCardLayers() : [], props.templateType === 'top' ? 'center' : 'left'))
}

function addCardLayer(type: string) {
    if (!activeLayer.value || activeLayer.value.type !== 'repeater') return
    const id = uid(`card_${type}`)
    const layer = type === 'image'
        ? { id, type: 'image', x: 0, y: 0, w: 160, h: 160, src: activeLayer.value.source === 'top' ? '{half_body_photo_url}' : '{avatar_photo_url}', fit: 'cover', radius: 0, visible: 1, opacity: 1, rotate: 0 }
        : type === 'rect'
          ? { id, type: 'rect', x: 0, y: 0, w: 160, h: 80, fill: '#FFFFFF', stroke: '', strokeWidth: 0, radius: 0, visible: 1, opacity: 1, rotate: 0 }
          : type === 'line'
            ? { id, type: 'line', x: 0, y: 0, w: 160, h: 0, stroke: '#D8C08B', strokeWidth: 2, visible: 1, opacity: 1, rotate: 0 }
            : defaultText(id, 16, 16, 180, 42, 0, '{english_name}', 28, '#FFFFFF', '700', 'left')
    activeLayer.value.card_layers.push(normalizeCardLayer(layer))
    activeCardLayerIndex.value = activeLayer.value.card_layers.length - 1
}

function insertDynamicField(token: string) {
    if (activeLayer.value?.type === 'text') {
        activeLayer.value.text = `${activeLayer.value.text || ''}${token}`
        return
    }
    addLayer(defaultText(uid('text'), 120, 160, 420, 80, nextZ(), token, 48, '#FFFFFF', '600'))
}

function insertCardField(token: string) {
    if (!activeCardLayer.value) return
    if (activeCardLayer.value.type === 'image') {
        activeCardLayer.value.src = token
        return
    }
    activeCardLayer.value.text = `${activeCardLayer.value.text || ''}${token}`
}

function duplicateLayer() {
    if (!activeLayer.value) return
    if (isRequiredQrcodeLayer(activeLayer.value)) return
    const copy = clone(activeLayer.value)
    copy.id = uid(copy.type)
    copy.x = Number(copy.x || 0) + 24
    copy.y = Number(copy.y || 0) + 24
    copy.z = nextZ()
    localDesign.value.layers.push(copy)
    activeLayerId.value = copy.id
}

function deleteLayer() {
    if (!activeLayer.value) return
    if (isRequiredQrcodeLayer(activeLayer.value)) return
    localDesign.value.layers = localDesign.value.layers.filter((item: any) => item.id !== activeLayer.value.id)
    activeLayerId.value = ''
}

function moveLayer(step: number) {
    if (!activeLayer.value) return
    activeLayer.value.z = Number(activeLayer.value.z || 0) + step
}

function duplicateCardLayer() {
    if (!activeLayer.value || !activeCardLayer.value) return
    const copy = clone(activeCardLayer.value)
    copy.id = uid(copy.type)
    copy.x = Number(copy.x || 0) + 12
    copy.y = Number(copy.y || 0) + 12
    activeLayer.value.card_layers.push(copy)
    activeCardLayerIndex.value = activeLayer.value.card_layers.length - 1
}

function deleteCardLayer() {
    if (!activeLayer.value || !activeCardLayer.value) return
    activeLayer.value.card_layers.splice(activeCardLayerIndex.value, 1)
    activeCardLayerIndex.value = Math.max(0, activeCardLayerIndex.value - 1)
}

function toggleLayerVisible(layer: any, value: any) {
    if (isRequiredQrcodeLayer(layer)) {
        layer.visible = 1
        return
    }
    layer.visible = value ? 1 : 0
}

function isRequiredQrcodeLayer(layer: any) {
    return layer?.type === 'qrcode'
}

function applyLayerAlign(action: PosterLayerAlignAction) {
    const layer = activeLayer.value
    if (!layer || layer.locked === 1) return
    const canvasWidth = Number(localDesign.value?.canvas?.width || 1080)
    const canvasHeight = Number(localDesign.value?.canvas?.height || 1920)
    const width = Math.max(1, Number(layer.w || layerWidth(layer) || 1))
    const height = layer.type === 'repeater' ? layerHeight(layer) : layerLayoutHeight(layer)
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

function resetDefault() {
    localDesign.value = normalizeDesign(defaultDesign(props.templateType), props.templateType)
    activeLayerId.value = ''
}

function applyTextArtPreset(layer: any, presetValue: string) {
    const preset = textArtPresets.find((item) => item.value === presetValue) || textArtPresets[0]
    Object.assign(layer, {
        ...clone(preset.config),
        artPreset: preset.value,
    })
}

function layerName(layer: any) {
    const map: Record<string, string> = {
        text: '文本',
        image: '图片',
        qrcode: '二维码',
        rect: '矩形',
        line: '分割线',
        repeater: layer.source === 'top' ? '单王卡片' : '榜单卡片',
    }
    return `${map[layer.type] || '图层'} · ${layer.id}`
}

function cardLayerName(layer: any) {
    const map: Record<string, string> = { text: '文本', image: '图片', rect: '矩形', line: '分割线' }
    return `${map[layer.type] || '子层'} · ${layer.id}`
}

function nextZ() {
    const max = Math.max(0, ...(localDesign.value.layers || []).map((item: any) => Number(item.z || 0)))
    return max + 1
}

function defaultText(id: string, x: number, y: number, w: number, h: number, z: number, text: string, fontSize: number, color: string, fontWeight = '400', align = 'center') {
    return {
        id,
        type: 'text',
        x,
        y,
        w,
        h,
        z,
        visible: 1,
        locked: 0,
        opacity: 1,
        rotate: 0,
        text,
        fontSize,
        fontWeight,
        lineHeight: 1.15,
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
    }
}

function defaultQrcode(x: number, y: number, w: number, h: number, z: number) {
    return { id: uid('qrcode'), type: 'qrcode', x, y, w, h, z, visible: 1, locked: 0, opacity: 1, rotate: 0, src: '', padding: 18, radius: 12 }
}

function defaultRepeater(id: string, x: number, y: number, columns: number, cardWidth: number, cardHeight: number, gapX: number, gapY: number, z: number, limit: number, source = 'ranking', cardLayers: any[] = [], align = 'left') {
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

function defaultCardLayers(cardWidth: number, cardHeight: number) {
    return [
        { id: 'photo', type: 'image', x: 0, y: 0, w: cardWidth, h: cardHeight, src: '{avatar_photo_url}', fit: 'cover', radius: 0, visible: 1, opacity: 1, rotate: 0 },
        { ...defaultText('english', 18, 24, cardWidth - 36, 36, 0, '{english_name}', 28, '#FFFFFF', '800', 'left') },
        { ...defaultText('name', 18, 66, cardWidth - 36, 34, 0, '*{chinese_name}', 24, '#FFFFFF', '500', 'left') },
        { ...defaultText('count', 18, 110, 120, 90, 0, '{count}', 78, '#FFF9ED', '300', 'left'), lineHeight: 1.05 },
    ]
}

function defaultTopCardLayers() {
    return [
        { id: 'photo', type: 'image', x: 0, y: 0, w: 310, h: 860, src: '{half_body_photo_url}', fit: 'contain', backgroundFill: 'rgba(255,255,255,0)', radius: 0, visible: 1, opacity: 1, rotate: 0 },
    ]
}

function normalizeUrl(value: any) {
    if (Array.isArray(value)) {
        return normalizeUrl(value[0])
    }
    if (typeof value === 'object' && value) {
        return normalizeUrl(value.url || value.uri || value.path)
    }
    return String(value || '').trim()
}

function clone<T>(value: T): T {
    return JSON.parse(JSON.stringify(value || {}))
}

function uid(prefix: string) {
    return `${prefix}_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 7)}`
}

function resolveInputTextarea() {
    return resolveComponent('el-input')
}

function resolveInputNumber() {
    return resolveComponent('el-input-number')
}

function resolveSelect() {
    return resolveComponent('el-select')
}

function resolveOption() {
    return resolveComponent('el-option')
}

function resolveColorPicker() {
    return resolveComponent('el-color-picker')
}

function resolveRadioGroup() {
    return resolveComponent('el-radio-group')
}

function resolveRadioButton() {
    return resolveComponent('el-radio-button')
}

function resolveCollapse() {
    return resolveComponent('el-collapse')
}

function resolveCollapseItem() {
    return resolveComponent('el-collapse-item')
}
</script>

<style scoped lang="scss">
.monthly-report-designer {
    border: 1px solid #ebeef5;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}

.designer-toolbar {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    border-bottom: 1px solid #ebeef5;

    &__group {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
}

.designer-body {
    display: grid;
    grid-template-columns: minmax(360px, 420px) minmax(560px, 1fr);
    gap: 16px;
    min-height: 680px;
}

.canvas-shell {
    min-height: 680px;
    padding: 18px;
    background: #f5f7fa;
    overflow: auto;
    border: 1px solid #e8ecf3;
    border-radius: 8px;
}

.canvas-wrap {
    margin: 0 auto;
}

.canvas {
    position: relative;
    overflow: hidden;
    box-shadow: 0 12px 40px rgba(15, 23, 42, 0.18);

    &__background-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }
}

.layer {
    position: absolute;
    cursor: move;
    outline: 1px dashed transparent;
    user-select: none;

    &.is-active {
        outline-color: #409eff;
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
        padding: 10%;
        background: #fff;
    }

    &__line {
        width: 100%;
        height: 100%;
    }
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

.layer.is-active .resize-handle {
    display: block;
}

.repeater-preview {
    position: relative;
    width: 100%;
    height: 100%;
    outline: 1px dashed rgba(255, 255, 255, 0.45);
}

.repeater-card,
.card-layer {
    position: absolute;
}

.card-layer {
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
    }
}

.designer-panel {
    border-left: 1px solid #ebeef5;
    padding: 12px;
    overflow: auto;
}

.designer-form {
    :deep(.el-form-item) {
        margin-bottom: 14px;
    }
}

.prop-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 8px;
    width: 100%;
}

.layer-list,
.card-layer-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 220px;
    overflow: auto;
}

.layer-row,
.card-layer-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    min-height: 40px;
    padding: 0 10px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    color: #303133;
    text-align: left;

    &.is-active {
        border-color: #409eff;
        background: #ecf5ff;
    }
}

.panel-actions,
.card-layer-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.field-guide {
    &__title {
        font-weight: 600;
        margin-bottom: 10px;
    }

    &__list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    &__item {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 8px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        background: #fff;
        text-align: left;

        code {
            color: #8a5a16;
            font-size: 12px;
        }
    }
}

.field-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin: 8px 0 12px;

    button {
        height: 26px;
        padding: 0 8px;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #f8fafc;
        color: #606266;
        font-size: 12px;
    }

    button.is-active {
        border-color: #409eff;
        background: #ecf5ff;
        color: #1677d2;
        font-weight: 600;
    }
}

.field-chips--compact {
    margin-bottom: 0;
}

.layer-field-block {
    margin-bottom: 12px;

    > label {
        display: block;
        margin-bottom: 6px;
        color: #606266;
        font-size: 13px;
    }
}

.layer-field-grid {
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr);
    align-items: center;
    gap: 10px 8px;
}

.align-shortcuts {
    display: grid;
    grid-template-columns: repeat(3, 32px);
    gap: 8px;
}

.align-shortcut-button {
    width: 32px;
    height: 30px;
    padding: 0;
    margin-left: 0 !important;
}

.segmented-control--wide {
    margin-top: 2px;
}

.designer-panel {
    :deep(.text-property-grid) {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 10px;
    }

    :deep(.text-property-item) {
        min-width: 0;
    }

    :deep(.text-property-item > label) {
        display: block;
        margin-bottom: 6px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.2;
    }

    :deep(.text-property-item--full) {
        grid-column: 1 / -1;
    }

    :deep(.field-chips) {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0;
    }

    :deep(.field-chips button) {
        height: 28px;
        padding: 0 10px;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #fff;
        color: #606266;
        font-size: 12px;
    }

    :deep(.field-chips button.is-active) {
        border-color: #409eff;
        background: #ecf5ff;
        color: #1677d2;
        font-weight: 600;
    }

    :deep(.designer-property-collapse .el-input-number),
    :deep(.designer-property-collapse .el-select),
    :deep(.designer-property-collapse .el-input),
    :deep(.designer-property-collapse .el-radio-group) {
        width: 100%;
        max-width: 100%;
    }

    :deep(.designer-property-collapse .el-input-number .el-input__inner) {
        text-align: center;
    }

    :deep(.segmented-control) {
        display: flex;
    }

    :deep(.segmented-control .el-radio-button) {
        flex: 1;
        min-width: 0;
    }

    :deep(.segmented-control .el-radio-button__inner) {
        width: 100%;
        padding: 7px 8px;
    }
}

.designer-property-collapse {
    border-top: 1px solid #edf0f5;
    border-bottom: none;

    :deep(.el-collapse-item__header) {
        height: 40px;
        padding: 0 12px;
        border: 1px solid #edf0f5;
        border-bottom: none;
        border-radius: 8px 8px 0 0;
        background: #fbfcff;
        color: #1f2d3d;
        font-weight: 600;
    }

    :deep(.el-collapse-item__wrap) {
        border: 1px solid #edf0f5;
        border-top: none;
        border-radius: 0 0 8px 8px;
        margin-bottom: 10px;
    }

    :deep(.el-collapse-item__content) {
        padding: 12px;
    }
}

.card-layer-form {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px dashed #e5e7eb;
    max-height: 520px;
    overflow: auto;
}
</style>
