<template>
    <div class="mdp-simple">
        <!-- 健康体检条 -->
        <div v-if="healthIssues.length" class="mdp-simple__health">
            <div class="mdp-simple__health-list">
                <div v-for="issue in healthIssues" :key="issue.message" class="mdp-simple__health-item">
                    <span class="mdp-simple__health-dot" />
                    <span>{{ issue.message }}</span>
                </div>
            </div>
            <el-button size="small" type="warning" plain class="mdp-simple__health-fix" @click="handleAutoFix">
                一键规范修复
            </el-button>
        </div>

        <!-- 向导步骤导航 -->
        <div class="mdp-simple__tabs">
            <button
                v-for="(tab, index) in tabs"
                :key="tab.key"
                type="button"
                :class="['mdp-simple__tab', { 'is-active': activeTab === tab.key }]"
                @click="switchTab(tab.key)"
            >
                <span class="mdp-simple__tab-step">{{ index + 1 }}</span>
                <span class="mdp-simple__tab-label">{{ tab.label }}</span>
            </button>
        </div>

        <div class="mdp-simple__body">
            <!-- Step 1 选风格 -->
            <div v-show="activeTab === 'style'" class="mdp-simple__section">
                <div class="mdp-simple__option-row">
                    <span class="mdp-simple__option-label">切换风格时保留已修改的品牌名、标语和地址</span>
                    <el-switch v-model="preserveCopy" />
                </div>
                <div class="mdp-simple__preset-grid">
                    <button
                        v-for="preset in stylePresets"
                        :key="preset.id"
                        type="button"
                        :class="['mdp-simple__preset-card', { 'is-active': effectivePresetId === preset.id }]"
                        @click="handleApplyStyle(preset)"
                    >
                        <div class="mdp-simple__preset-thumb-wrap">
                            <PresetThumb :design="preset.design" :width="164" />
                            <span v-if="effectivePresetId === preset.id" class="mdp-simple__preset-badge">当前</span>
                        </div>
                        <div class="mdp-simple__preset-meta">
                            <div class="mdp-simple__preset-name">{{ preset.name }}</div>
                            <div class="mdp-simple__preset-desc">{{ preset.description }}</div>
                            <div class="mdp-simple__preset-tagline">
                                <el-tag size="small" type="info" effect="plain">{{ preset.tag }}</el-tag>
                                <span class="mdp-simple__palette-dots">
                                    <i v-for="color in preset.palettePreview" :key="color" :style="{ background: color }" />
                                </span>
                            </div>
                        </div>
                    </button>
                </div>
                <div class="mdp-simple__section-footer">
                    <el-button text size="small" type="primary" @click="handleResetDefault">恢复系统默认版式</el-button>
                </div>
            </div>

            <!-- Step 2 配色背景 -->
            <div v-show="activeTab === 'theme'" class="mdp-simple__section">
                <div class="mdp-simple__block">
                    <div class="mdp-simple__block-title">一键配色方案</div>
                    <div class="mdp-simple__palette-grid">
                        <button
                            v-for="palette in COLOR_PALETTES"
                            :key="palette.id"
                            type="button"
                            class="mdp-simple__palette-card"
                            @click="handleApplyPalette(palette)"
                        >
                            <span class="mdp-simple__palette-dots">
                                <i :style="{ background: palette.bg }" />
                                <i :style="{ background: palette.accent }" />
                                <i :style="{ background: palette.textPrimary }" />
                            </span>
                            <span class="mdp-simple__palette-name">{{ palette.name }}</span>
                        </button>
                    </div>
                </div>

                <div class="mdp-simple__block">
                    <div class="mdp-simple__block-title">画布背景</div>
                    <el-radio-group
                        :model-value="design.background.type"
                        size="small"
                        @update:model-value="handleBackgroundTypeChange"
                    >
                        <el-radio-button label="color">纯色</el-radio-button>
                        <el-radio-button label="image">图片底图</el-radio-button>
                    </el-radio-group>

                    <div v-if="design.background.type !== 'image'" class="mdp-simple__inline-row">
                        <el-color-picker
                            :model-value="design.background.color"
                            show-alpha
                            @update:model-value="handleBackgroundColorChange"
                        />
                        <span class="mdp-simple__swatch-row">
                            <button
                                v-for="color in recommendedColors"
                                :key="color"
                                type="button"
                                class="mdp-simple__swatch"
                                :style="{ background: color }"
                                :title="color"
                                @click="handleBackgroundColorChange(color)"
                            />
                        </span>
                    </div>

                    <div v-else class="mdp-simple__stack-row">
                        <MaterialPicker
                            :model-value="design.background.image"
                            :limit="1"
                            @update:model-value="handleBackgroundImageChange"
                        />
                        <el-radio-group
                            :model-value="design.background.fit"
                            size="small"
                            @update:model-value="handleBackgroundFitChange"
                        >
                            <el-radio-button label="cover">铺满</el-radio-button>
                            <el-radio-button label="contain">完整</el-radio-button>
                        </el-radio-group>
                        <div class="mdp-simple__option-row">
                            <span class="mdp-simple__option-label">底图透明度</span>
                            <el-slider
                                class="mdp-simple__slider"
                                :model-value="Number(design.background.opacity ?? 1)"
                                :min="0"
                                :max="1"
                                :step="0.05"
                                @update:model-value="handleBackgroundOpacityChange"
                            />
                        </div>
                    </div>

                    <div class="mdp-simple__option-row mdp-simple__option-row--top">
                        <span class="mdp-simple__option-label">背景压暗遮罩（防文字看不清）</span>
                        <el-slider
                            class="mdp-simple__slider"
                            :model-value="scrimPercent"
                            :min="0"
                            :max="75"
                            :step="5"
                            :format-tooltip="(val: number) => `${val}%`"
                            @update:model-value="handleScrimChange"
                        />
                    </div>
                </div>

                <div class="mdp-simple__block">
                    <div class="mdp-simple__block-title">画布尺寸比例</div>
                    <div class="mdp-simple__ratio-grid">
                        <button
                            v-for="ratio in ratioPresets"
                            :key="ratio.id"
                            type="button"
                            class="mdp-simple__ratio-card"
                            @click="handleApplyRatio(ratio)"
                        >
                            <span>{{ ratio.label }}</span>
                            <small>{{ ratio.desc }}</small>
                        </button>
                    </div>
                    <template v-if="templateType === 'ranking'">
                        <div class="mdp-simple__option-row">
                            <span class="mdp-simple__option-label">按上榜人数自动拉长海报</span>
                            <el-switch
                                :model-value="Number(design.canvas.auto_height ?? 0) === 1"
                                @change="handleAutoHeightChange"
                            />
                        </div>
                        <div v-if="Number(design.canvas.auto_height ?? 0) === 1" class="mdp-simple__option-row">
                            <span class="mdp-simple__option-label">最小高度（px）</span>
                            <el-input-number
                                :model-value="minHeight"
                                :min="480"
                                :max="5200"
                                size="small"
                                @update:model-value="handleMinHeightChange"
                            />
                        </div>
                    </template>
                </div>
            </div>

            <!-- Step 3 内容排版 -->
            <div v-show="activeTab === 'content'" class="mdp-simple__section">
                <div class="mdp-simple__block">
                    <div class="mdp-simple__block-title">重点大字风格</div>
                    <div class="mdp-simple__hero-list">
                        <button
                            v-for="preset in HERO_ART_PRESETS"
                            :key="preset.value"
                            type="button"
                            class="mdp-simple__hero-pill"
                            @click="handleApplyHero(preset.value)"
                        >
                            <span>{{ preset.label }}</span>
                            <small>{{ preset.description }}</small>
                        </button>
                    </div>
                </div>

                <div v-if="rankingState" class="mdp-simple__block">
                    <div class="mdp-simple__block-title">人员展示排版</div>
                    <div class="mdp-simple__option-row">
                        <span class="mdp-simple__option-label">每行人数</span>
                        <el-radio-group
                            :model-value="rankingState.columns"
                            size="small"
                            @update:model-value="handleRankingColumns"
                        >
                            <el-radio-button :label="2">2 人</el-radio-button>
                            <el-radio-button :label="3">3 人</el-radio-button>
                            <el-radio-button :label="4">4 人</el-radio-button>
                        </el-radio-group>
                    </div>
                    <div class="mdp-simple__option-row">
                        <span class="mdp-simple__option-label">卡片间距</span>
                        <el-radio-group
                            :model-value="rankingState.spacing"
                            size="small"
                            @update:model-value="handleRankingSpacing"
                        >
                            <el-radio-button label="compact">紧凑</el-radio-button>
                            <el-radio-button label="normal">适中</el-radio-button>
                            <el-radio-button label="loose">舒展</el-radio-button>
                        </el-radio-group>
                    </div>
                    <div class="mdp-simple__option-row">
                        <span class="mdp-simple__option-label">卡片圆角</span>
                        <el-radio-group
                            :model-value="rankingState.radius"
                            size="small"
                            @update:model-value="handleRankingRadius"
                        >
                            <el-radio-button :label="0">直角</el-radio-button>
                            <el-radio-button :label="16">微圆角</el-radio-button>
                            <el-radio-button :label="32">大圆角</el-radio-button>
                        </el-radio-group>
                    </div>
                    <div class="mdp-simple__option-row">
                        <span class="mdp-simple__option-label">卡片比例</span>
                        <el-radio-group
                            :model-value="rankingState.cardAspect"
                            size="small"
                            @update:model-value="handleRankingAspect"
                        >
                            <el-radio-button label="square">方形</el-radio-button>
                            <el-radio-button label="portrait">竖卡</el-radio-button>
                        </el-radio-group>
                    </div>
                    <div class="mdp-simple__option-row">
                        <span class="mdp-simple__option-label">最多展示人数</span>
                        <el-input-number
                            :model-value="rankingState.limit"
                            :min="0"
                            :max="120"
                            size="small"
                            @update:model-value="handleRankingLimit"
                        />
                    </div>
                    <div class="mdp-simple__option-row mdp-simple__option-row--top">
                        <span class="mdp-simple__option-label">卡片上显示的信息</span>
                        <el-checkbox-group :model-value="rankingInfoSelection" size="small" @update:model-value="handleRankingInfoChange">
                            <el-checkbox label="english">英文名</el-checkbox>
                            <el-checkbox label="chinese">姓名</el-checkbox>
                            <el-checkbox label="count">场次</el-checkbox>
                            <el-checkbox label="mask">底部文字暗色托底</el-checkbox>
                        </el-checkbox-group>
                    </div>
                </div>

                <div v-else-if="topState" class="mdp-simple__block">
                    <div class="mdp-simple__block-title">人员展示排版</div>
                    <div class="mdp-simple__option-row">
                        <span class="mdp-simple__option-label">最多展示人数</span>
                        <el-radio-group
                            :model-value="topState.limit"
                            size="small"
                            @update:model-value="handleTopLimit"
                        >
                            <el-radio-button :label="1">1 人</el-radio-button>
                            <el-radio-button :label="2">2 人</el-radio-button>
                            <el-radio-button :label="3">3 人</el-radio-button>
                        </el-radio-group>
                    </div>
                    <div class="mdp-simple__option-row">
                        <span class="mdp-simple__option-label">照片适配</span>
                        <el-radio-group
                            :model-value="topState.fit"
                            size="small"
                            @update:model-value="handleTopFit"
                        >
                            <el-radio-button label="contain">完整展示</el-radio-button>
                            <el-radio-button label="cover">铺满裁剪</el-radio-button>
                        </el-radio-group>
                    </div>
                    <div class="mdp-simple__option-row">
                        <span class="mdp-simple__option-label">照片圆角</span>
                        <el-slider
                            class="mdp-simple__slider"
                            :model-value="topState.radius"
                            :min="0"
                            :max="120"
                            :step="4"
                            @update:model-value="handleTopRadius"
                        />
                    </div>
                    <div class="mdp-simple__option-row">
                        <span class="mdp-simple__option-label">主题色块</span>
                        <el-switch
                            :model-value="topState.showHeroBlock"
                            @change="handleTopHeroBlock"
                        />
                    </div>
                </div>

                <div class="mdp-simple__block">
                    <div class="mdp-simple__block-title">文案内容编辑</div>
                    <div
                        v-for="slot in textSlots"
                        :key="slot.layerId"
                        :ref="(el) => setSlotRef(slot.layerId, el)"
                        :class="['mdp-simple__text-slot', { 'is-highlight': highlightId === slot.layerId }]"
                    >
                        <div class="mdp-simple__text-slot-head">
                            <label>{{ slot.label }}</label>
                            <el-popover placement="bottom-end" :width="300" trigger="click">
                                <template #reference>
                                    <el-button size="small" text type="primary">插入数据</el-button>
                                </template>
                                <div class="mdp-simple__field-list">
                                    <button
                                        v-for="field in allDynamicFields"
                                        :key="field.token"
                                        type="button"
                                        @click="handleInsertField(slot.layerId, field.token)"
                                    >
                                        <span>{{ field.label }}</span>
                                        <code>{{ field.token }}</code>
                                    </button>
                                </div>
                            </el-popover>
                        </div>
                        <el-input
                            v-if="!slot.multiline"
                            :model-value="textValueOf(slot.layerId)"
                            :placeholder="slot.placeholder"
                            maxlength="500"
                            @focus="handleSlotFocus(slot.layerId)"
                            @update:model-value="handleTextChange(slot.layerId, $event)"
                        />
                        <el-input
                            v-else
                            type="textarea"
                            :rows="3"
                            :model-value="textValueOf(slot.layerId)"
                            :placeholder="slot.placeholder"
                            maxlength="500"
                            show-word-limit
                            @focus="handleSlotFocus(slot.layerId)"
                            @update:model-value="handleTextChange(slot.layerId, $event)"
                        />
                    </div>
                </div>

                <div class="mdp-simple__block">
                    <div class="mdp-simple__block-title">二维码与模块显隐</div>
                    <div class="mdp-simple__option-row">
                        <span class="mdp-simple__option-label">二维码位置</span>
                        <el-radio-group
                            :model-value="qrcodeState.placement"
                            size="small"
                            @update:model-value="handleQrcodePlacementChange"
                        >
                            <el-radio-button label="bottom-center">底部居中</el-radio-button>
                            <el-radio-button label="bottom-right">右下角</el-radio-button>
                            <el-radio-button label="bottom-left">左下角</el-radio-button>
                        </el-radio-group>
                    </div>
                    <div class="mdp-simple__option-row">
                        <span class="mdp-simple__option-label">二维码大小</span>
                        <el-radio-group
                            :model-value="qrcodeState.size"
                            size="small"
                            @update:model-value="handleQrcodeSizeChange"
                        >
                            <el-radio-button v-for="tier in qrcodeSizeTiers" :key="tier.value" :label="tier.value">
                                {{ tier.label }}
                            </el-radio-button>
                        </el-radio-group>
                    </div>
                    <div class="mdp-simple__module-list">
                        <div v-for="module in toggleModules" :key="module.key" class="mdp-simple__module-row">
                            <div class="mdp-simple__module-info">
                                <label>
                                    {{ module.label }}
                                    <el-tag v-if="module.required" size="small" type="info" effect="plain">系统必留</el-tag>
                                </label>
                                <small>{{ module.desc }}</small>
                            </div>
                            <el-switch
                                :model-value="module.visible"
                                :disabled="Boolean(module.required)"
                                @change="handleToggleModule(module, $event)"
                            />
                        </div>
                    </div>
                </div>

                <div class="mdp-simple__upgrade">
                    <span>需要自由新增图片、矩形或精确调整像素位置？</span>
                    <el-button size="small" type="primary" plain @click="emit('switch-to-pro')">
                        切换到专业精调
                    </el-button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { ComponentPublicInstance } from 'vue'
import { computed, nextTick, ref, watch } from 'vue'

import MaterialPicker from '@/components/material/picker.vue'

import PresetThumb from './PresetThumb.vue'
import {
    CANVAS_RATIO_PRESETS,
    COLOR_PALETTES,
    HERO_ART_PRESETS,
    MONTHLY_DYNAMIC_FIELDS,
    STYLE_PRESETS,
    cloneDesign,
    defaultDesign,
    normalizeDesign,
} from './presets'
import {
    applyCanvasRatio,
    applyColorPalette,
    applyHeroArtStyle,
    applyQrcodePlacement,
    applyStylePreset,
    autoFixLayout,
    extractTextSlots,
    extractToggleModules,
    getBgScrimOpacity,
    getQrcodePlacementState,
    isSameDesign,
    readRankingGridState,
    readTopLayoutState,
    recommendedBackgroundColors,
    runDesignHealthCheck,
    setBgScrimOpacity,
    toggleModuleVisibility,
    updateRankingGridLayout,
    updateTopStaffLayout,
} from './simple-adapter'
import type {
    CanvasRatioPreset,
    ColorPalette,
    DesignConfig,
    DesignLayer,
    DynamicFieldOption,
    MonthlyReportTemplateType,
    QrcodePlacement,
    RankingGridState,
    SimplePanelTab,
    StylePreset,
    TextSlotItem,
    ToggleModuleItem,
    TopLayoutState,
} from './types'

const props = withDefaults(
    defineProps<{
        design: DesignConfig
        templateType: MonthlyReportTemplateType
        activeLayerId: string
        activeTab: SimplePanelTab
        extraDynamicFields?: DynamicFieldOption[]
    }>(),
    {
        extraDynamicFields: () => [],
    },
)

const emit = defineEmits<{
    (e: 'update:design', nextDesign: DesignConfig): void
    (e: 'update:activeTab', tab: SimplePanelTab): void
    (e: 'select-layer', layerId: string): void
    (e: 'switch-to-pro'): void
}>()

const allDynamicFields = computed(() => [...MONTHLY_DYNAMIC_FIELDS, ...(props.extraDynamicFields || [])])

const tabs: Array<{ key: SimplePanelTab; label: string }> = [
    { key: 'style', label: '选风格' },
    { key: 'theme', label: '配色背景' },
    { key: 'content', label: '内容排版' },
]

const qrcodeSizeTiers = [
    { label: '小', value: 168 },
    { label: '中', value: 196 },
    { label: '大', value: 228 },
]

const preserveCopy = ref(true)
const activePresetId = ref('')
const highlightId = ref('')
let highlightTimer: number | null = null

watch(
    () => props.templateType,
    () => {
        activePresetId.value = ''
    },
)

// ---------------------------------------------------------------------------
// 健康体检
// ---------------------------------------------------------------------------

const healthIssues = computed(() => runDesignHealthCheck(props.design))

function handleAutoFix() {
    emit('update:design', autoFixLayout(props.design, props.templateType))
}

// ---------------------------------------------------------------------------
// Tab 切换
// ---------------------------------------------------------------------------

function switchTab(tab: SimplePanelTab) {
    if (tab === props.activeTab) return
    emit('update:activeTab', tab)
}

// ---------------------------------------------------------------------------
// 选风格
// ---------------------------------------------------------------------------

const stylePresets = computed<StylePreset[]>(() => STYLE_PRESETS.filter((preset) => preset.templateType === props.templateType))

const effectivePresetId = computed(() => {
    if (activePresetId.value) return activePresetId.value
    return stylePresets.value.find((preset) => isSameDesign(props.design, preset.design, props.templateType))?.id || ''
})

function handleApplyStyle(preset: StylePreset) {
    activePresetId.value = preset.id
    emit('update:design', applyStylePreset(props.design, preset, preserveCopy.value))
}

function handleResetDefault() {
    activePresetId.value = stylePresets.value[0]?.id || ''
    emit('update:design', normalizeDesign(defaultDesign(props.templateType), props.templateType))
}

// ---------------------------------------------------------------------------
// 配色背景
// ---------------------------------------------------------------------------

const recommendedColors = computed(() => recommendedBackgroundColors())

const scrimPercent = computed(() => Math.round(getBgScrimOpacity(props.design) * 100))

const ratioPresets = computed<CanvasRatioPreset[]>(() =>
    CANVAS_RATIO_PRESETS.filter((ratio) => props.templateType === 'ranking' || ratio.id !== 'ranking-auto'),
)

const minHeight = computed(() => Number(props.design.canvas.min_height || 1920))

function handleApplyPalette(palette: ColorPalette) {
    emit('update:design', applyColorPalette(props.design, palette, props.templateType))
}

function handleBackgroundTypeChange(value: any) {
    const next = cloneDesign(props.design)
    next.background.type = value === 'image' ? 'image' : 'color'
    emit('update:design', next)
}

function handleBackgroundColorChange(value: any) {
    const next = cloneDesign(props.design)
    next.background.color = String(value || next.background.color)
    emit('update:design', next)
}

function handleBackgroundImageChange(value: any) {
    const next = cloneDesign(props.design)
    next.background.image = String(value || '')
    emit('update:design', next)
}

function handleBackgroundFitChange(value: any) {
    const next = cloneDesign(props.design)
    next.background.fit = value === 'contain' ? 'contain' : 'cover'
    emit('update:design', next)
}

function handleBackgroundOpacityChange(value: any) {
    const next = cloneDesign(props.design)
    next.background.opacity = Number(value)
    emit('update:design', next)
}

function handleScrimChange(value: any) {
    emit('update:design', setBgScrimOpacity(props.design, Number(value) / 100))
}

function handleApplyRatio(ratio: CanvasRatioPreset) {
    emit('update:design', applyCanvasRatio(props.design, ratio, props.templateType))
}

function handleAutoHeightChange(value: any) {
    const next = cloneDesign(props.design)
    next.canvas.auto_height = value ? 1 : 0
    emit('update:design', next)
}

function handleMinHeightChange(value: any) {
    const next = cloneDesign(props.design)
    next.canvas.min_height = Number(value) || 1920
    emit('update:design', next)
}

// ---------------------------------------------------------------------------
// 内容排版：重点大字
// ---------------------------------------------------------------------------

function handleApplyHero(presetValue: string) {
    emit('update:design', applyHeroArtStyle(props.design, presetValue, props.templateType))
}

// ---------------------------------------------------------------------------
// 内容排版：人员展示
// ---------------------------------------------------------------------------

const rankingState = computed<RankingGridState | null>(() =>
    props.templateType === 'ranking' ? readRankingGridState(props.design) : null,
)

const topState = computed<TopLayoutState | null>(() =>
    props.templateType === 'top' ? readTopLayoutState(props.design) : null,
)

const rankingInfoSelection = computed<string[]>(() => {
    const state = rankingState.value
    if (!state) return []
    const keys: string[] = []
    if (state.showEnglish) keys.push('english')
    if (state.showChinese) keys.push('chinese')
    if (state.showCount) keys.push('count')
    if (state.showMask) keys.push('mask')
    return keys
})

function patchRanking(patch: Partial<RankingGridState>) {
    emit('update:design', updateRankingGridLayout(props.design, patch))
}

function handleRankingColumns(value: any) {
    patchRanking({ columns: Number(value) as RankingGridState['columns'] })
}

function handleRankingSpacing(value: any) {
    patchRanking({ spacing: value as RankingGridState['spacing'] })
}

function handleRankingRadius(value: any) {
    patchRanking({ radius: Number(value) as RankingGridState['radius'] })
}

function handleRankingAspect(value: any) {
    patchRanking({ cardAspect: value as RankingGridState['cardAspect'] })
}

function handleRankingLimit(value: any) {
    patchRanking({ limit: Number(value) || 0 })
}

function handleRankingInfoChange(keys: any) {
    const list = Array.isArray(keys) ? (keys as string[]) : []
    patchRanking({
        showEnglish: list.includes('english'),
        showChinese: list.includes('chinese'),
        showCount: list.includes('count'),
        showMask: list.includes('mask'),
    })
}

function patchTop(patch: Partial<TopLayoutState>) {
    emit('update:design', updateTopStaffLayout(props.design, patch))
}

function handleTopLimit(value: any) {
    patchTop({ limit: Number(value) as TopLayoutState['limit'] })
}

function handleTopFit(value: any) {
    patchTop({ fit: value === 'cover' ? 'cover' : 'contain' })
}

function handleTopRadius(value: any) {
    patchTop({ radius: Number(value) || 0 })
}

function handleTopHeroBlock(value: any) {
    patchTop({ showHeroBlock: Boolean(value) })
}

// ---------------------------------------------------------------------------
// 内容排版：文案编辑
// ---------------------------------------------------------------------------

const textSlots = computed<TextSlotItem[]>(() => extractTextSlots(props.design))

const slotRefs = new Map<string, HTMLElement>()

function setSlotRef(layerId: string, el: Element | ComponentPublicInstance | null) {
    if (el) slotRefs.set(layerId, el as HTMLElement)
    else slotRefs.delete(layerId)
}

function textValueOf(layerId: string): string {
    const layer = props.design.layers.find((item) => item.id === layerId)
    return layer && layer.type === 'text' ? String(layer.text || '') : ''
}

function handleTextChange(layerId: string, value: any) {
    const next = cloneDesign(props.design)
    const layer: DesignLayer | undefined = next.layers.find((item) => item.id === layerId)
    if (layer && layer.type === 'text') {
        layer.text = String(value ?? '')
        emit('update:design', next)
    }
}

function handleInsertField(layerId: string, token: string) {
    const next = cloneDesign(props.design)
    const layer: DesignLayer | undefined = next.layers.find((item) => item.id === layerId)
    if (layer && layer.type === 'text') {
        layer.text = `${String(layer.text || '')}${token}`
        emit('update:design', next)
    }
}

function handleSlotFocus(layerId: string) {
    emit('select-layer', layerId)
}

watch(
    () => props.activeLayerId,
    (layerId) => {
        if (!layerId) return
        if (!textSlots.value.some((slot) => slot.layerId === layerId)) return
        nextTick(() => {
            const el = slotRefs.get(layerId)
            if (!el) return
            el.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
            highlightId.value = layerId
            if (highlightTimer) window.clearTimeout(highlightTimer)
            highlightTimer = window.setTimeout(() => {
                highlightId.value = ''
                highlightTimer = null
            }, 1800)
        })
    },
)

// ---------------------------------------------------------------------------
// 二维码与模块显隐
// ---------------------------------------------------------------------------

const qrcodeState = computed<QrcodePlacement>(() => getQrcodePlacementState(props.design))

const toggleModules = computed<ToggleModuleItem[]>(() => extractToggleModules(props.design))

function handleQrcodePlacementChange(value: any) {
    const placement = (value as QrcodePlacement['placement']) || 'bottom-center'
    emit('update:design', applyQrcodePlacement(props.design, placement, qrcodeState.value.size))
}

function handleQrcodeSizeChange(value: any) {
    const size = Number(value) || qrcodeState.value.size
    emit('update:design', applyQrcodePlacement(props.design, qrcodeState.value.placement, size))
}

function handleToggleModule(module: ToggleModuleItem, value: any) {
    if (module.required) return
    emit('update:design', toggleModuleVisibility(props.design, module.layerIds, Boolean(value)))
}
</script>

<style scoped lang="scss">
.mdp-simple {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.mdp-simple__health {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 12px;
    border: 1px solid #f3d19e;
    border-radius: 10px;
    background: #fdf6ec;

    &-list {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    &-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: #8a5a16;
        line-height: 1.4;
    }

    &-dot {
        flex: none;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #e6a23c;
    }

    &-fix {
        flex: none;
    }
}

.mdp-simple__tabs {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    padding: 4px;
    border: 1px solid #e8ecf3;
    border-radius: 12px;
    background: #f5f7fa;
}

.mdp-simple__tab {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    height: 36px;
    border: none;
    border-radius: 9px;
    background: transparent;
    color: #6b7280;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s ease;

    &.is-active {
        background: #fff;
        color: #1f2d3d;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.1);
    }

    &:not(.is-active):hover {
        color: #303133;
    }
}

.mdp-simple__tab-step {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: rgba(64, 158, 255, 0.12);
    color: #409eff;
    font-size: 12px;
    font-weight: 600;
}

.mdp-simple__tab.is-active .mdp-simple__tab-step {
    background: #409eff;
    color: #fff;
}

.mdp-simple__body {
    min-height: 320px;
}

.mdp-simple__section {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.mdp-simple__block {
    padding: 12px;
    border: 1px solid #e8ecf3;
    border-radius: 10px;
    background: #fff;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.mdp-simple__block-title {
    font-size: 13px;
    font-weight: 600;
    color: #1f2d3d;
}

.mdp-simple__option-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 32px;

    &--top {
        align-items: flex-start;

        .mdp-simple__slider {
            margin-top: 4px;
        }
    }
}

.mdp-simple__option-label {
    flex: none;
    max-width: 62%;
    font-size: 13px;
    color: #4b5563;
}

.mdp-simple__slider {
    flex: 1;
    min-width: 0;
}

.mdp-simple__preset-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.mdp-simple__preset-card {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 8px;
    border: 1px solid #e8ecf3;
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
    text-align: left;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;

    &:hover {
        border-color: #409eff;
        box-shadow: 0 4px 14px rgba(64, 158, 255, 0.16);
    }

    &.is-active {
        border-color: #409eff;
        background: #f4f9ff;
        box-shadow: 0 4px 14px rgba(64, 158, 255, 0.18);
    }
}

.mdp-simple__preset-thumb-wrap {
    position: relative;
    display: flex;
    justify-content: center;
    padding: 4px;
    border-radius: 8px;
    background: #f3f5f9;
}

.mdp-simple__preset-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 2px 7px;
    border-radius: 999px;
    background: #409eff;
    color: #fff;
    font-size: 10px;
    font-weight: 600;
}

.mdp-simple__preset-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.mdp-simple__preset-name {
    font-size: 13px;
    font-weight: 600;
    color: #1f2d3d;
}

.mdp-simple__preset-desc {
    font-size: 11px;
    line-height: 1.4;
    color: #6b7280;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.mdp-simple__preset-tagline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.mdp-simple__palette-dots {
    display: inline-flex;
    align-items: center;
    gap: 4px;

    i {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.12);
    }
}

.mdp-simple__section-footer {
    display: flex;
    justify-content: center;
}

.mdp-simple__palette-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}

.mdp-simple__palette-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 10px 6px;
    border: 1px solid #e8ecf3;
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
    transition: border-color 0.2s ease;

    &:hover {
        border-color: #409eff;
    }
}

.mdp-simple__palette-name {
    font-size: 12px;
    color: #4b5563;
}

.mdp-simple__inline-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mdp-simple__stack-row {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
}

.mdp-simple__swatch-row {
    display: inline-flex;
    gap: 6px;
    flex-wrap: wrap;
}

.mdp-simple__swatch {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 1px solid rgba(15, 23, 42, 0.12);
    cursor: pointer;
    padding: 0;
}

.mdp-simple__ratio-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}

.mdp-simple__ratio-card {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 10px;
    border: 1px solid #e8ecf3;
    border-radius: 10px;
    background: #fff;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.2s ease;

    span {
        font-size: 13px;
        font-weight: 600;
        color: #1f2d3d;
    }

    small {
        font-size: 11px;
        color: #8a94a6;
        line-height: 1.3;
    }

    &:hover {
        border-color: #409eff;
    }
}

.mdp-simple__hero-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.mdp-simple__hero-pill {
    display: flex;
    flex-direction: column;
    gap: 3px;
    padding: 9px 12px;
    border: 1px solid #e8ecf3;
    border-radius: 999px;
    background: #fff;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.2s ease;

    span {
        font-size: 13px;
        font-weight: 600;
        color: #1f2d3d;
    }

    small {
        font-size: 11px;
        color: #8a94a6;
    }

    &:hover {
        border-color: #409eff;
    }
}

.mdp-simple__text-slot {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 10px;
    border: 1px solid transparent;
    border-radius: 8px;
    transition: background-color 0.3s ease, border-color 0.3s ease;

    &.is-highlight {
        border-color: #409eff;
        background: #ecf5ff;
    }
}

.mdp-simple__text-slot-head {
    display: flex;
    align-items: center;
    justify-content: space-between;

    label {
        font-size: 13px;
        color: #4b5563;
        font-weight: 600;
    }
}

.mdp-simple__field-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 260px;
    overflow: auto;

    button {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 6px 10px;
        border: 1px solid #e8ecf3;
        border-radius: 8px;
        background: #fff;
        font-size: 12px;
        color: #303133;
        cursor: pointer;

        code {
            color: #8a5a16;
            font-size: 11px;
        }

        &:hover {
            border-color: #409eff;
        }
    }
}

.mdp-simple__module-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.mdp-simple__module-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 8px 10px;
    border: 1px solid #eef1f6;
    border-radius: 8px;
    background: #fbfcfe;
}

.mdp-simple__module-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;

    label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #1f2d3d;
    }

    small {
        font-size: 11px;
        color: #8a94a6;
    }
}

.mdp-simple__upgrade {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    border: 1px dashed #c6d4e6;
    border-radius: 10px;
    background: #f8fafd;
    font-size: 13px;
    color: #4b5563;
}
</style>
