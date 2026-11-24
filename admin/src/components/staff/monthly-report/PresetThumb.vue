<template>
    <div class="preset-thumb" :style="shellStyle">
        <div class="preset-thumb__stage" :style="stageStyle">
            <img
                v-if="backgroundImage"
                class="preset-thumb__bg-image"
                :src="backgroundImage"
                :style="{ objectFit: design?.background?.fit === 'contain' ? 'contain' : 'cover', opacity: Number(design?.background?.opacity ?? 1) }"
                alt=""
            />
            <div
                v-for="layer in sortedLayers"
                :key="layer.id"
                class="preset-thumb__layer"
                :style="layerBoxStyle(layer)"
            >
                <div v-if="layer.type === 'text'" class="preset-thumb__text" :style="textStyle(layer)">
                    {{ renderText(layer) }}
                </div>
                <div v-else-if="layer.type === 'image'" class="preset-thumb__image" :style="imageBoxStyle(layer)">
                    <img v-if="layerImageSrc(layer)" :src="layerImageSrc(layer)" :style="imageStyle(layer)" alt="" />
                    <div v-else class="preset-thumb__photo-placeholder" />
                </div>
                <div v-else-if="layer.type === 'qrcode'" class="preset-thumb__qrcode" :style="qrcodeBoxStyle(layer)">
                    <div class="preset-thumb__qrcode-grid">
                        <span class="qr-corner qr-corner--tl" />
                        <span class="qr-corner qr-corner--tr" />
                        <span class="qr-corner qr-corner--bl" />
                        <span class="qr-dot qr-dot--a" />
                        <span class="qr-dot qr-dot--b" />
                        <span class="qr-dot qr-dot--c" />
                    </div>
                </div>
                <div v-else-if="layer.type === 'rect'" class="preset-thumb__rect" :style="rectStyle(layer)" />
                <div v-else-if="layer.type === 'line'" class="preset-thumb__line" :style="lineStyle(layer)" />
                <div v-else class="preset-thumb__repeater" :style="repeaterStyle(layer)">
                    <div
                        v-for="(item, idx) in repeaterItems(layer)"
                        :key="`${layer.id}_${idx}`"
                        class="preset-thumb__card"
                        :style="repeaterCardStyle(layer, idx, repeaterItems(layer).length)"
                    >
                        <div
                            v-for="sub in normalizedSubLayers(layer)"
                            :key="sub.id"
                            class="preset-thumb__sub"
                            :style="subLayerStyle(sub)"
                        >
                            <div v-if="sub.type === 'text'" class="preset-thumb__text" :style="textStyle(sub)">
                                {{ renderSubText(sub, item) }}
                            </div>
                            <div v-else-if="sub.type === 'image'" class="preset-thumb__image" :style="imageBoxStyle(sub)">
                                <div class="preset-thumb__portrait" :style="portraitStyle(sub, idx)">
                                    <div class="preset-thumb__portrait-head" />
                                    <div class="preset-thumb__portrait-body" />
                                </div>
                            </div>
                            <div v-else-if="sub.type === 'rect'" class="preset-thumb__rect" :style="rectStyle(sub)" />
                            <div v-else-if="sub.type === 'line'" class="preset-thumb__line" :style="lineStyle(sub)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="isLongPoster" class="preset-thumb__long-badge">长图滚动</div>
    </div>
</template>

<script setup lang="ts">
import type { CSSProperties } from 'vue'
import { computed } from 'vue'

import { buildPosterTextPreviewStyle, wrapPosterText } from '../poster-designer-common'

import { defaultCardLayers, normalizeUrl } from './presets'
import { renderSampleText } from './simple-adapter'
import type { DesignConfig, DesignLayer } from './types'

const SAMPLE_CARDS = [
    { rank: 1, chinese_name: '王一平', english_name: 'YIPING', count: '23' },
    { rank: 2, chinese_name: '王帆', english_name: 'WANGFAN', count: '21' },
    { rank: 3, chinese_name: '可玉', english_name: 'KEYU', count: '19' },
    { rank: 4, chinese_name: '林深', english_name: 'LINSHEN', count: '18' },
    { rank: 5, chinese_name: '顾清', english_name: 'GUQING', count: '16' },
    { rank: 6, chinese_name: '陆川', english_name: 'LUCHUAN', count: '15' },
    { rank: 7, chinese_name: '周礼', english_name: 'ZHOULI', count: '14' },
    { rank: 8, chinese_name: '陈默', english_name: 'CHENMO', count: '12' },
    { rank: 9, chinese_name: '苏阳', english_name: 'SUYANG', count: '11' },
    { rank: 10, chinese_name: '许诺', english_name: 'XUNUO', count: '10' },
    { rank: 11, chinese_name: '季白', english_name: 'JIBAI', count: '09' },
    { rank: 12, chinese_name: '沈安', english_name: 'SHENAN', count: '08' },
]

const PORTRAIT_TONES = [
    'linear-gradient(160deg, #D5B89E 0%, #8C6A52 100%)',
    'linear-gradient(160deg, #B5C4CE 0%, #5F7382 100%)',
    'linear-gradient(160deg, #CEBEB0 0%, #7C695A 100%)',
    'linear-gradient(160deg, #C7B2A8 0%, #6D554B 100%)',
]

const props = withDefaults(
    defineProps<{
        design: DesignConfig
        width?: number
    }>(),
    {
        width: 148,
    },
)

const canvasWidth = computed(() => Math.max(1, Number(props.design?.canvas?.width || 1080)))
const canvasHeight = computed(() => Math.max(1, Number(props.design?.canvas?.height || 1920)))
const boxWidth = computed(() => Math.max(60, Number(props.width || 148)))
const thumbScale = computed(() => boxWidth.value / canvasWidth.value)
const maxViewportHeight = computed(() => Math.round(boxWidth.value * 1.82))
const isLongPoster = computed(() => canvasHeight.value * thumbScale.value > maxViewportHeight.value + 16)

const shellStyle = computed<CSSProperties>(() => ({
    width: `${boxWidth.value}px`,
    height: `${Math.min(maxViewportHeight.value, Math.round(canvasHeight.value * thumbScale.value))}px`,
    background: props.design?.background?.color || '#1B1510',
}))

const stageStyle = computed<CSSProperties>(() => ({
    width: `${canvasWidth.value}px`,
    height: `${canvasHeight.value}px`,
    backgroundColor: props.design?.background?.color || '#1B1510',
    transform: `scale(${thumbScale.value})`,
    transformOrigin: 'top left',
}))

const backgroundImage = computed(() => {
    if (props.design?.background?.type !== 'image') return ''
    return normalizeUrl(props.design?.background?.image_url || props.design?.background?.image)
})

const sortedLayers = computed<DesignLayer[]>(() =>
    [...(props.design?.layers || [])]
        .filter((layer) => Number(layer.visible ?? 1) === 1)
        .sort((a, b) => Number(a.z || 0) - Number(b.z || 0)),
)

function layerWidth(layer: DesignLayer): number {
    if (layer.type === 'repeater') {
        const cols = Math.max(1, Number(layer.columns || 1))
        return cols * Number(layer.card_width || 300) + Math.max(0, cols - 1) * Number(layer.gap_x || 0)
    }
    return Number(layer.w || 120)
}

function layerHeight(layer: DesignLayer): number {
    if (layer.type === 'repeater') {
        const items = repeaterItems(layer)
        const cols = Math.max(1, Number(layer.columns || 1))
        const rows = Math.max(1, Math.ceil(items.length / cols))
        return rows * Number(layer.card_height || 300) + Math.max(0, rows - 1) * Number(layer.gap_y || 0)
    }
    return Number(layer.h ?? 80)
}

function layerBoxStyle(layer: DesignLayer): CSSProperties {
    return {
        left: `${Number(layer.x || 0)}px`,
        top: `${Number(layer.y || 0)}px`,
        width: `${layerWidth(layer)}px`,
        height: `${layer.type === 'line' ? Math.max(2, Number(layer.strokeWidth || 2)) : layerHeight(layer)}px`,
        opacity: layer.type === 'qrcode' ? 1 : Number(layer.opacity ?? 1),
        zIndex: Number(layer.z || 0) + 100,
        transform: `rotate(${Number(layer.rotate || 0)}deg)`,
    }
}

function textStyle(layer: DesignLayer): CSSProperties {
    return buildPosterTextPreviewStyle(layer, 1, normalizeUrl)
}

function imageBoxStyle(layer: DesignLayer): CSSProperties {
    return {
        background: layer.backgroundFill || 'rgba(255,255,255,0.08)',
        borderRadius: `${Number(layer.radius || 0)}px`,
    }
}

function imageStyle(layer: DesignLayer): CSSProperties {
    return {
        objectFit: layer.fit === 'contain' ? 'contain' : 'cover',
        borderRadius: `${Number(layer.radius || 0)}px`,
    }
}

function qrcodeBoxStyle(layer: DesignLayer): CSSProperties {
    return {
        padding: `${Number(layer.padding ?? 16)}px`,
        borderRadius: `${Number(layer.radius ?? 14)}px`,
        background: '#FFFFFF',
    }
}

function rectStyle(layer: DesignLayer): CSSProperties {
    return {
        background: layer.fill || '#FFFFFF',
        border: Number(layer.strokeWidth || 0) > 0
            ? `${Number(layer.strokeWidth)}px solid ${layer.stroke || '#FFFFFF'}`
            : 'none',
        borderRadius: `${Number(layer.radius || 0)}px`,
    }
}

function lineStyle(layer: DesignLayer): CSSProperties {
    return {
        borderTop: `${Math.max(1, Number(layer.strokeWidth || 2))}px solid ${layer.stroke || '#D8C08B'}`,
    }
}

function repeaterItems(layer: DesignLayer) {
    const cols = Math.max(1, Number(layer.columns || 1))
    const limit = Number(layer.limit || 0)
    const maxCount = layer.source === 'top' ? Math.min(3, limit || 3) : Math.min(cols * 4, limit || 12)
    return SAMPLE_CARDS.slice(0, Math.max(1, maxCount))
}

function repeaterStyle(layer: DesignLayer): CSSProperties {
    return {
        width: `${layerWidth(layer)}px`,
        height: `${layerHeight(layer)}px`,
    }
}

function repeaterCardStyle(layer: DesignLayer, index: number, total: number): CSSProperties {
    const columns = Math.max(1, Number(layer.columns || 1))
    const cardW = Number(layer.card_width || 300)
    const cardH = Number(layer.card_height || 300)
    const gapX = Number(layer.gap_x || 0)
    const gapY = Number(layer.gap_y || 0)
    const col = index % columns
    const row = Math.floor(index / columns)
    const rowStart = row * columns
    const itemsInRow = Math.min(columns, Math.max(0, total - rowStart))
    const rowWidth = itemsInRow * cardW + Math.max(0, itemsInRow - 1) * gapX
    const fullWidth = columns * cardW + Math.max(0, columns - 1) * gapX
    const rowShift = layer.align === 'center'
        ? Math.max(0, (fullWidth - rowWidth) / 2)
        : layer.align === 'right'
            ? Math.max(0, fullWidth - rowWidth)
            : 0
    return {
        left: `${rowShift + col * (cardW + gapX)}px`,
        top: `${row * (cardH + gapY)}px`,
        width: `${cardW}px`,
        height: `${cardH}px`,
    }
}

function normalizedSubLayers(layer: DesignLayer): DesignLayer[] {
    const subs = Array.isArray(layer.card_layers) && layer.card_layers.length
        ? layer.card_layers
        : defaultCardLayers(Number(layer.card_width || 300), Number(layer.card_height || 300))
    return subs.filter((sub) => Number(sub.visible ?? 1) === 1)
}

function subLayerStyle(sub: DesignLayer): CSSProperties {
    return {
        left: `${Number(sub.x || 0)}px`,
        top: `${Number(sub.y || 0)}px`,
        width: `${Number(sub.w || 120)}px`,
        height: `${sub.type === 'line' ? Math.max(2, Number(sub.strokeWidth || 2)) : Number(sub.h || 80)}px`,
        opacity: Number(sub.opacity ?? 1),
        transform: `rotate(${Number(sub.rotate || 0)}deg)`,
    }
}

function portraitStyle(sub: DesignLayer, index: number): CSSProperties {
    return {
        background: sub.fit === 'contain' ? 'transparent' : PORTRAIT_TONES[index % PORTRAIT_TONES.length],
        borderRadius: `${Number(sub.radius || 0)}px`,
    }
}

function layerImageSrc(layer: DesignLayer): string {
    return normalizeUrl(layer.src_url || layer.src)
}

function renderText(layer: DesignLayer): string {
    return wrapPosterText(renderSampleText(String(layer.text || '')), layer)
}

function renderSubText(sub: DesignLayer, item: Record<string, any>): string {
    const replaced = String(sub.text || '').replace(/\{([a-zA-Z0-9_]+)\}/g, (m, key: string) => {
        if (item[key] !== undefined) return String(item[key])
        return renderSampleText(m)
    })
    return wrapPosterText(replaced, sub)
}
</script>

<style scoped lang="scss">
.preset-thumb {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);
}

.preset-thumb__stage {
    position: relative;
    overflow: hidden;
    pointer-events: none;
    user-select: none;
}

.preset-thumb__bg-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.preset-thumb__layer,
.preset-thumb__sub {
    position: absolute;
}

.preset-thumb__text {
    width: 100%;
    height: 100%;
    white-space: pre-wrap;
    overflow: hidden;
}

.preset-thumb__image,
.preset-thumb__rect,
.preset-thumb__line {
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.preset-thumb__image img {
    display: block;
    width: 100%;
    height: 100%;
}

.preset-thumb__photo-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(150deg, rgba(255, 255, 255, 0.25), rgba(15, 23, 42, 0.2));
}

.preset-thumb__portrait {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.preset-thumb__portrait-head {
    position: absolute;
    left: 50%;
    top: 18%;
    width: 34%;
    height: 28%;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.55);
    transform: translateX(-50%);
}

.preset-thumb__portrait-body {
    position: absolute;
    left: 50%;
    bottom: -6%;
    width: 72%;
    height: 48%;
    border-radius: 50% 50% 0 0;
    background: rgba(255, 255, 255, 0.42);
    transform: translateX(-50%);
}

.preset-thumb__qrcode {
    width: 100%;
    height: 100%;
    box-sizing: border-box;
}

.preset-thumb__qrcode-grid {
    position: relative;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.08);
    border-radius: 6px;
}

.qr-corner {
    position: absolute;
    width: 32%;
    height: 32%;
    border: 14px solid #1f2937;
    box-sizing: border-box;

    &--tl {
        left: 6%;
        top: 6%;
    }

    &--tr {
        right: 6%;
        top: 6%;
    }

    &--bl {
        left: 6%;
        bottom: 6%;
    }
}

.qr-dot {
    position: absolute;
    width: 16%;
    height: 16%;
    background: #1f2937;

    &--a {
        right: 12%;
        bottom: 12%;
    }

    &--b {
        left: 42%;
        top: 42%;
    }

    &--c {
        right: 28%;
        bottom: 28%;
    }
}

.preset-thumb__repeater {
    position: relative;
}

.preset-thumb__card {
    position: absolute;
}

.preset-thumb__long-badge {
    position: absolute;
    right: 6px;
    bottom: 6px;
    padding: 2px 6px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.68);
    color: #fff;
    font-size: 10px;
    line-height: 1.4;
    pointer-events: none;
}
</style>
