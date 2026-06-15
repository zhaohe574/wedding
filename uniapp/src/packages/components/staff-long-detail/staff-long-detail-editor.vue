<template>
    <view class="staff-long-detail-editor">
        <view class="staff-long-detail-editor__toolbar">
            <view class="staff-long-detail-editor__toolbar-btn" @click="addText">
                <text class="staff-long-detail-editor__toolbar-btn-text">添加文本</text>
            </view>
            <view class="staff-long-detail-editor__toolbar-btn" @click="addImage">
                <text class="staff-long-detail-editor__toolbar-btn-text">添加图片</text>
            </view>
        </view>

        <view v-if="!blocks.length" class="staff-long-detail-editor__empty">
            <text>还没有长图详情内容，点击上方按钮开始装修。</text>
        </view>

        <view v-else class="staff-long-detail-editor__list">
            <view
                v-for="(block, index) in blocks"
                :key="block.id"
                class="staff-long-detail-editor__card"
            >
                <view class="staff-long-detail-editor__card-head">
                    <view class="staff-long-detail-editor__card-meta">
                        <text class="staff-long-detail-editor__card-index">#{{ index + 1 }}</text>
                        <text class="staff-long-detail-editor__card-type">
                            {{ block.type === 'image' ? '图片模块' : '文本模块' }}
                        </text>
                    </view>
                    <view class="staff-long-detail-editor__card-actions">
                        <text
                            :class="['staff-long-detail-editor__action', { disabled: index === 0 }]"
                            @click="move(index, -1)"
                            >上移</text
                        >
                        <text
                            :class="[
                                'staff-long-detail-editor__action',
                                { disabled: index === blocks.length - 1 }
                            ]"
                            @click="move(index, 1)"
                        >
                            下移
                        </text>
                        <text
                            class="staff-long-detail-editor__action staff-long-detail-editor__action--danger"
                            @click="remove(index)"
                        >
                            删除
                        </text>
                    </view>
                </view>

                <template v-if="block.type === 'image'">
                    <view class="staff-long-detail-editor__subhead">
                        <text class="staff-long-detail-editor__subhead-text">图片列表</text>
                        <text class="staff-long-detail-editor__subhead-tip">按顺序无间距展示</text>
                    </view>

                    <view class="staff-long-detail-editor__image-grid">
                        <view
                            v-for="(image, imageIndex) in block.images"
                            :key="`${block.id}-${imageIndex}`"
                            class="staff-long-detail-editor__image-item"
                        >
                            <image
                                :src="image"
                                mode="aspectFill"
                                class="staff-long-detail-editor__image-preview"
                                @click="previewImages(block.images, imageIndex)"
                            />
                            <view
                                class="staff-long-detail-editor__image-remove"
                                @click="removeImage(index, imageIndex)"
                            >
                                <text>×</text>
                            </view>
                        </view>

                        <view
                            class="staff-long-detail-editor__image-adder"
                            @click="selectImages(index)"
                        >
                            <text class="staff-long-detail-editor__image-adder-plus">+</text>
                            <text class="staff-long-detail-editor__image-adder-text">
                                {{ imageUploading ? '上传中' : '添加图片' }}
                            </text>
                        </view>
                    </view>
                </template>

                <template v-else>
                    <view class="staff-long-detail-editor__subhead">
                        <text class="staff-long-detail-editor__subhead-text">文本内容</text>
                    </view>

                    <textarea
                        v-model="block.content"
                        class="staff-long-detail-editor__textarea"
                        placeholder="请输入要展示的文本内容"
                        maxlength="3000"
                        :auto-height="true"
                        :show-confirm-bar="false"
                    />

                    <view class="staff-long-detail-editor__style-group">
                        <view class="staff-long-detail-editor__style-row">
                            <text class="staff-long-detail-editor__style-label">字号</text>
                            <view class="staff-long-detail-editor__chip-group">
                                <view
                                    v-for="option in fontSizeOptions"
                                    :key="option.value"
                                    :class="[
                                        'staff-long-detail-editor__chip',
                                        {
                                            'is-active': block.style.fontSize === option.value
                                        }
                                    ]"
                                    @click="block.style.fontSize = option.value"
                                >
                                    <text>{{ option.label }}</text>
                                </view>
                            </view>
                        </view>

                        <view class="staff-long-detail-editor__style-row">
                            <text class="staff-long-detail-editor__style-label">对齐</text>
                            <view class="staff-long-detail-editor__chip-group">
                                <view
                                    v-for="option in alignOptions"
                                    :key="option.value"
                                    :class="[
                                        'staff-long-detail-editor__chip',
                                        {
                                            'is-active': block.style.align === option.value
                                        }
                                    ]"
                                    @click="block.style.align = option.value"
                                >
                                    <text>{{ option.label }}</text>
                                </view>
                            </view>
                        </view>

                        <view class="staff-long-detail-editor__style-row">
                            <text class="staff-long-detail-editor__style-label">粗细</text>
                            <view class="staff-long-detail-editor__chip-group">
                                <view
                                    :class="[
                                        'staff-long-detail-editor__chip',
                                        { 'is-active': !block.style.bold }
                                    ]"
                                    @click="block.style.bold = false"
                                >
                                    <text>常规</text>
                                </view>
                                <view
                                    :class="[
                                        'staff-long-detail-editor__chip',
                                        { 'is-active': block.style.bold }
                                    ]"
                                    @click="block.style.bold = true"
                                >
                                    <text>加粗</text>
                                </view>
                            </view>
                        </view>

                        <view
                            class="staff-long-detail-editor__style-row staff-long-detail-editor__style-row--color"
                        >
                            <text class="staff-long-detail-editor__style-label">颜色</text>
                            <view class="staff-long-detail-editor__color-panel">
                                <view class="staff-long-detail-editor__color-current">
                                    <view
                                        class="staff-long-detail-editor__color-current-dot"
                                        :style="{ backgroundColor: normalizeHexColor(block.style.color) }"
                                    ></view>
                                    <text class="staff-long-detail-editor__color-current-value">
                                        {{ normalizeHexColor(block.style.color) }}
                                    </text>
                                    <view
                                        class="staff-long-detail-editor__palette-button"
                                        @click="openColorPicker(index)"
                                    >
                                        <text>色盘</text>
                                    </view>
                                </view>

                                <view class="staff-long-detail-editor__color-group">
                                    <view
                                        v-for="color in colorOptions"
                                        :key="color.value"
                                        :class="[
                                            'staff-long-detail-editor__color',
                                            {
                                                'is-active':
                                                    normalizeHexColor(block.style.color) ===
                                                    color.value
                                            }
                                        ]"
                                        :style="{ backgroundColor: color.value }"
                                        @click="block.style.color = color.value"
                                    >
                                        <view class="staff-long-detail-editor__color-inner"></view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </template>
            </view>
        </view>

        <view
            v-if="colorPickerVisible"
            class="staff-long-detail-editor__picker-mask"
            @click="closeColorPicker"
        ></view>

        <view
            v-if="colorPickerVisible"
            class="staff-long-detail-editor__picker-drawer"
            @click.stop
        >
            <view class="staff-long-detail-editor__picker-handle"></view>

            <view class="staff-long-detail-editor__picker-head">
                <view class="staff-long-detail-editor__picker-title-group">
                    <text class="staff-long-detail-editor__picker-title">文字颜色</text>
                    <text class="staff-long-detail-editor__picker-hex">{{ pickerPreviewHex }}</text>
                </view>

                <view
                    class="staff-long-detail-editor__picker-preview"
                    :style="{ backgroundColor: pickerPreviewHex }"
                ></view>
            </view>

            <view
                class="staff-long-detail-editor__sv-panel"
                :style="{ backgroundColor: pickerHueHex }"
                @touchstart="handleSvTouch"
                @touchmove.stop.prevent="handleSvTouch"
            >
                <view class="staff-long-detail-editor__sv-white"></view>
                <view class="staff-long-detail-editor__sv-black"></view>
                <view
                    class="staff-long-detail-editor__sv-cursor"
                    :style="svCursorStyle"
                ></view>
            </view>

            <view
                class="staff-long-detail-editor__hue-slider"
                @touchstart="handleHueTouch"
                @touchmove.stop.prevent="handleHueTouch"
            >
                <view
                    class="staff-long-detail-editor__hue-cursor"
                    :style="hueCursorStyle"
                ></view>
            </view>

            <view class="staff-long-detail-editor__hex-field">
                <text class="staff-long-detail-editor__hex-label">HEX</text>
                <input
                    class="staff-long-detail-editor__hex-input"
                    :value="pickerHexDraft"
                    maxlength="7"
                    placeholder="#111111"
                    placeholder-class="staff-long-detail-editor__hex-placeholder"
                    @input="handleHexInput"
                />
            </view>

            <view class="staff-long-detail-editor__picker-actions">
                <view
                    class="staff-long-detail-editor__picker-action staff-long-detail-editor__picker-action--light"
                    @click="closeColorPicker"
                >
                    <text>取消</text>
                </view>
                <view
                    class="staff-long-detail-editor__picker-action staff-long-detail-editor__picker-action--dark"
                    @click="applyPickerColor"
                >
                    <text>应用颜色</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance, nextTick, ref, watch } from 'vue'
import { uploadImage } from '@/api/app'
import type { LongDetailBlock } from './utils'
import {
    createImageBlock,
    createTextBlock,
    parseLongDetailDraftContent,
    stringifyLongDetailDraftContent
} from './utils'

const props = defineProps<{
    modelValue?: string
}>()

const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void
    (event: 'uploading-change', value: boolean): void
}>()

const fontSizeOptions = [
    { label: '小', value: 'sm' },
    { label: '中', value: 'md' },
    { label: '大', value: 'lg' }
] as const

const alignOptions = [
    { label: '左对齐', value: 'left' },
    { label: '居中', value: 'center' },
    { label: '右对齐', value: 'right' }
] as const

type HsvColor = {
    h: number
    s: number
    v: number
}

const defaultTextColor = '#111111'
const colorOptions = [
    { label: '黑', value: '#111111' },
    { label: '深灰', value: '#2B2925' },
    { label: '正文灰', value: '#5F5A50' },
    { label: '香槟金', value: '#D9BE82' },
    { label: '暖金', value: '#B8954A' },
    { label: '陶土', value: '#9A6B35' },
    { label: '酒红', value: '#7A2F2A' },
    { label: '玫瑰', value: '#B46A75' },
    { label: '橄榄', value: '#77724A' },
    { label: '松绿', value: '#4D6049' },
    { label: '湖蓝', value: '#3C6E71' },
    { label: '雾蓝', value: '#617C8C' },
    { label: '紫灰', value: '#6A5F7D' },
    { label: '米白', value: '#FFFDF8' },
    { label: '奶油', value: '#FAF6EE' },
    { label: '浅金', value: '#F1E5C8' },
    { label: '浅粉', value: '#F2DDD5' },
    { label: '浅绿', value: '#E8EFE6' }
] as const

const imageUploading = ref(false)
const blocks = ref<LongDetailBlock[]>(parseLongDetailDraftContent(props.modelValue))
const instance = getCurrentInstance()
const colorPickerVisible = ref(false)
const colorPickerBlockIndex = ref(-1)
const pickerHsv = ref<HsvColor>({ h: 0, s: 0, v: 0.07 })
const pickerHexDraft = ref(defaultTextColor)
const svPanelRect = ref({ left: 0, top: 0, width: 300, height: 150 })
const hueSliderRect = ref({ left: 0, top: 0, width: 300, height: 22 })

const clamp = (value: number, min: number, max: number) => Math.min(max, Math.max(min, value))

const normalizeHexColor = (value?: string) => {
    const raw = String(value || '').trim()
    const hex = raw.startsWith('#') ? raw.slice(1) : raw
    if (/^[0-9A-Fa-f]{6}$/.test(hex)) {
        return `#${hex.toUpperCase()}`
    }
    return defaultTextColor
}

const hexToRgb = (value?: string) => {
    const normalized = normalizeHexColor(value)
    const hex = normalized.slice(1)
    return {
        r: parseInt(hex.slice(0, 2), 16),
        g: parseInt(hex.slice(2, 4), 16),
        b: parseInt(hex.slice(4, 6), 16)
    }
}

const componentToHex = (value: number) => {
    const hex = clamp(Math.round(value), 0, 255).toString(16).toUpperCase()
    return hex.length === 1 ? `0${hex}` : hex
}

const rgbToHex = (r: number, g: number, b: number) =>
    `#${componentToHex(r)}${componentToHex(g)}${componentToHex(b)}`

const rgbToHsv = (r: number, g: number, b: number): HsvColor => {
    const red = r / 255
    const green = g / 255
    const blue = b / 255
    const max = Math.max(red, green, blue)
    const min = Math.min(red, green, blue)
    const delta = max - min
    let h = 0

    if (delta !== 0) {
        if (max === red) {
            h = ((green - blue) / delta) % 6
        } else if (max === green) {
            h = (blue - red) / delta + 2
        } else {
            h = (red - green) / delta + 4
        }
        h = Math.round(h * 60)
        if (h < 0) h += 360
    }

    return {
        h,
        s: max === 0 ? 0 : delta / max,
        v: max
    }
}

const hsvToRgb = ({ h, s, v }: HsvColor) => {
    const normalizedH = ((h % 360) + 360) % 360
    const c = v * s
    const x = c * (1 - Math.abs(((normalizedH / 60) % 2) - 1))
    const m = v - c
    let red = 0
    let green = 0
    let blue = 0

    if (normalizedH < 60) {
        red = c
        green = x
    } else if (normalizedH < 120) {
        red = x
        green = c
    } else if (normalizedH < 180) {
        green = c
        blue = x
    } else if (normalizedH < 240) {
        green = x
        blue = c
    } else if (normalizedH < 300) {
        red = x
        blue = c
    } else {
        red = c
        blue = x
    }

    return {
        r: (red + m) * 255,
        g: (green + m) * 255,
        b: (blue + m) * 255
    }
}

const hsvToHex = (value: HsvColor) => {
    const rgb = hsvToRgb(value)
    return rgbToHex(rgb.r, rgb.g, rgb.b)
}

const hexToHsv = (value?: string) => {
    const rgb = hexToRgb(value)
    return rgbToHsv(rgb.r, rgb.g, rgb.b)
}

const pickerPreviewHex = computed(() => hsvToHex(pickerHsv.value))
const pickerHueHex = computed(() => hsvToHex({ h: pickerHsv.value.h, s: 1, v: 1 }))
const svCursorStyle = computed(() => ({
    left: `${pickerHsv.value.s * 100}%`,
    top: `${(1 - pickerHsv.value.v) * 100}%`
}))
const hueCursorStyle = computed(() => ({
    left: `${(pickerHsv.value.h / 360) * 100}%`
}))

const syncPickerFromHex = (hex: string) => {
    const normalized = normalizeHexColor(hex)
    pickerHsv.value = hexToHsv(normalized)
    pickerHexDraft.value = normalized
}

const updatePickerHexDraft = () => {
    pickerHexDraft.value = pickerPreviewHex.value
}

const getTouchPoint = (event: any) => {
    const touch = event?.touches?.[0] || event?.changedTouches?.[0] || event?.detail || event
    return {
        x: Number(touch?.clientX ?? touch?.x ?? 0),
        y: Number(touch?.clientY ?? touch?.y ?? 0)
    }
}

const queryRect = (selector: string) =>
    new Promise<UniApp.NodeInfo | null>((resolve) => {
        const query = uni.createSelectorQuery()
        if (instance?.proxy) {
            query.in(instance.proxy)
        }
        query
            .select(selector)
            .boundingClientRect((rect) => {
                resolve(rect as UniApp.NodeInfo | null)
            })
            .exec()
    })

const refreshPickerRects = async () => {
    await nextTick()
    const [svRect, hueRect] = await Promise.all([
        queryRect('.staff-long-detail-editor__sv-panel'),
        queryRect('.staff-long-detail-editor__hue-slider')
    ])

    if (svRect?.width && svRect?.height) {
        svPanelRect.value = {
            left: Number(svRect.left || 0),
            top: Number(svRect.top || 0),
            width: Number(svRect.width || 1),
            height: Number(svRect.height || 1)
        }
    }

    if (hueRect?.width) {
        hueSliderRect.value = {
            left: Number(hueRect.left || 0),
            top: Number(hueRect.top || 0),
            width: Number(hueRect.width || 1),
            height: Number(hueRect.height || 1)
        }
    }
}

watch(
    () => props.modelValue,
    (value) => {
        const next = parseLongDetailDraftContent(value)
        if (JSON.stringify(next) !== JSON.stringify(blocks.value)) {
            blocks.value = next
        }
    }
)

watch(
    blocks,
    (value) => {
        emit('update:modelValue', stringifyLongDetailDraftContent(value))
    },
    { deep: true }
)

const addText = () => {
    blocks.value.push(createTextBlock())
}

const addImage = () => {
    if (imageUploading.value) {
        uni.showToast({ title: '请等待当前图片上传完成', icon: 'none' })
        return
    }

    const block = createImageBlock()
    blocks.value.push(block)

    nextTick(() => {
        const index = blocks.value.findIndex((item) => item.id === block.id)
        if (index >= 0) {
            selectImages(index, true)
        }
    })
}

const remove = (index: number) => {
    blocks.value.splice(index, 1)
}

const move = (index: number, direction: -1 | 1) => {
    const targetIndex = index + direction
    if (targetIndex < 0 || targetIndex >= blocks.value.length) {
        return
    }
    const next = [...blocks.value]
    ;[next[index], next[targetIndex]] = [next[targetIndex], next[index]]
    blocks.value = next
}

const previewImages = (images: string[], index: number) => {
    const urls = images.map((item) => String(item || '').trim()).filter(Boolean)
    if (!urls.length) {
        return
    }
    uni.previewImage({
        urls,
        current: urls[index] || urls[0]
    })
}

const removeImage = (blockIndex: number, imageIndex: number) => {
    const target = blocks.value[blockIndex]
    if (!target || target.type !== 'image') {
        return
    }
    target.images.splice(imageIndex, 1)
}

const openColorPicker = async (blockIndex: number) => {
    const target = blocks.value[blockIndex]
    if (!target || target.type !== 'text') {
        return
    }

    colorPickerBlockIndex.value = blockIndex
    syncPickerFromHex(target.style.color)
    colorPickerVisible.value = true

    await refreshPickerRects()
}

const closeColorPicker = () => {
    colorPickerVisible.value = false
    colorPickerBlockIndex.value = -1
}

const handleHexInput = (event: any) => {
    const raw = String(event?.detail?.value || '').trim().toUpperCase()
    const withHash = raw.startsWith('#') ? raw : `#${raw}`
    pickerHexDraft.value = withHash

    if (/^#[0-9A-F]{6}$/.test(withHash)) {
        pickerHsv.value = hexToHsv(withHash)
    }
}

const handleSvTouch = async (event: any) => {
    await refreshPickerRects()

    const point = getTouchPoint(event)
    const rect = svPanelRect.value
    const s = clamp((point.x - rect.left) / rect.width, 0, 1)
    const v = 1 - clamp((point.y - rect.top) / rect.height, 0, 1)
    pickerHsv.value = { ...pickerHsv.value, s, v }
    updatePickerHexDraft()
}

const handleHueTouch = async (event: any) => {
    await refreshPickerRects()

    const point = getTouchPoint(event)
    const rect = hueSliderRect.value
    const h = Math.round(clamp((point.x - rect.left) / rect.width, 0, 1) * 360)
    pickerHsv.value = { ...pickerHsv.value, h: h >= 360 ? 0 : h }
    updatePickerHexDraft()
}

const applyPickerColor = () => {
    const draft = pickerHexDraft.value.trim().toUpperCase()
    const withHash = draft.startsWith('#') ? draft : `#${draft}`
    if (!/^#[0-9A-F]{6}$/.test(withHash)) {
        uni.showToast({ title: '请输入有效颜色', icon: 'none' })
        return
    }

    const target = blocks.value[colorPickerBlockIndex.value]
    if (target && target.type === 'text') {
        target.style.color = withHash
    }
    closeColorPicker()
}

const selectImages = (blockIndex: number, removeIfEmptyOnCancel = false) => {
    const target = blocks.value[blockIndex]
    if (!target || target.type !== 'image' || imageUploading.value) {
        return
    }

    const blockId = target.id

    uni.chooseImage({
        count: 9,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            const filePaths = Array.isArray(res.tempFilePaths) ? res.tempFilePaths : []
            if (!filePaths.length) {
                return
            }

            imageUploading.value = true
            emit('uploading-change', true)
            uni.showLoading({ title: '上传中...' })
            try {
                const uploaded: string[] = []
                let failedCount = 0
                for (const path of filePaths) {
                    try {
                        const result = await uploadImage(path)
                        const url = String(result?.uri || result?.url || '').trim()
                        if (url) {
                            uploaded.push(url)
                            continue
                        }
                    } catch (_error) {
                        failedCount += 1
                        continue
                    }
                    failedCount += 1
                }

                const currentBlock = blocks.value.find(
                    (block) => block.id === blockId && block.type === 'image'
                )

                if (currentBlock && uploaded.length) {
                    currentBlock.images.push(...uploaded)
                }

                if (failedCount > 0) {
                    uni.showToast({
                        title: uploaded.length ? '部分图片上传失败' : '上传失败',
                        icon: 'none'
                    })
                }

                if (currentBlock && !currentBlock.images.length) {
                    blocks.value = blocks.value.filter((block) => block.id !== blockId)
                }
            } catch (error: any) {
                uni.showToast({ title: error?.message || '上传失败', icon: 'none' })
            } finally {
                imageUploading.value = false
                emit('uploading-change', false)
                uni.hideLoading()
            }
        },
        fail: () => {
            if (!removeIfEmptyOnCancel) {
                return
            }

            const currentBlock = blocks.value.find(
                (block) => block.id === blockId && block.type === 'image'
            )
            if (currentBlock && !currentBlock.images.length) {
                blocks.value = blocks.value.filter((block) => block.id !== blockId)
            }
        }
    })
}
</script>

<style lang="scss" scoped>
.staff-long-detail-editor {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.staff-long-detail-editor__toolbar,
.staff-long-detail-editor__card {
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid #e7e2d6;
    border-radius: 28rpx;
}

.staff-long-detail-editor__toolbar {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
    padding: 20rpx;
}

.staff-long-detail-editor__toolbar-btn {
    min-height: 84rpx;
    border-radius: 24rpx;
    background: #ffffff;
    border: 1rpx solid rgba(11, 11, 11, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
}

.staff-long-detail-editor__toolbar-btn-text,
.staff-long-detail-editor__action,
.staff-long-detail-editor__subhead-text,
.staff-long-detail-editor__style-label,
.staff-long-detail-editor__image-adder-text,
.staff-long-detail-editor__card-type,
.staff-long-detail-editor__card-index {
    font-size: 24rpx;
    line-height: 1.4;
}

.staff-long-detail-editor__toolbar-btn-text,
.staff-long-detail-editor__card-index {
    font-weight: 700;
    color: var(--wm-color-primary, #0b0b0b);
}

.staff-long-detail-editor__empty {
    padding: 36rpx 24rpx;
    border-radius: 28rpx;
    border: 1rpx dashed #D8D3C7;
    background: rgba(255, 255, 255, 0.72);
    text-align: center;
    font-size: 24rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #5f5a50);
}

.staff-long-detail-editor__list {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.staff-long-detail-editor__card {
    padding: 22rpx;
}

.staff-long-detail-editor__card-head,
.staff-long-detail-editor__card-meta,
.staff-long-detail-editor__card-actions,
.staff-long-detail-editor__subhead,
.staff-long-detail-editor__style-row,
.staff-long-detail-editor__chip-group,
.staff-long-detail-editor__color-group {
    display: flex;
}

.staff-long-detail-editor__card-head,
.staff-long-detail-editor__subhead,
.staff-long-detail-editor__style-row {
    justify-content: space-between;
    align-items: center;
}

.staff-long-detail-editor__card-head {
    gap: 16rpx;
    margin-bottom: 20rpx;
}

.staff-long-detail-editor__card-meta,
.staff-long-detail-editor__card-actions,
.staff-long-detail-editor__chip-group,
.staff-long-detail-editor__color-group {
    gap: 12rpx;
    flex-wrap: wrap;
}

.staff-long-detail-editor__card-type,
.staff-long-detail-editor__action,
.staff-long-detail-editor__subhead-text,
.staff-long-detail-editor__style-label {
    font-weight: 600;
    color: var(--wm-text-primary, #111111);
}

.staff-long-detail-editor__action.disabled {
    opacity: 0.36;
}

.staff-long-detail-editor__action--danger {
    color: #5A4433;
}

.staff-long-detail-editor__subhead {
    margin-bottom: 14rpx;
}

.staff-long-detail-editor__subhead-tip {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.staff-long-detail-editor__image-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
}

.staff-long-detail-editor__image-item,
.staff-long-detail-editor__image-adder {
    position: relative;
    min-height: 180rpx;
    border-radius: 24rpx;
    overflow: hidden;
}

.staff-long-detail-editor__image-item {
    border: 1rpx solid #e7e2d6;
}

.staff-long-detail-editor__image-preview {
    width: 100%;
    height: 180rpx;
    display: block;
}

.staff-long-detail-editor__image-remove {
    position: absolute;
    top: 10rpx;
    right: 10rpx;
    width: 40rpx;
    height: 40rpx;
    border-radius: 999rpx;
    background: rgba(17, 17, 17, 0.58);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28rpx;
}

.staff-long-detail-editor__image-adder {
    border: 1rpx dashed #D8D3C7;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
}

.staff-long-detail-editor__image-adder-plus {
    font-size: 40rpx;
    line-height: 1;
    color: var(--wm-color-primary, #0b0b0b);
}

.staff-long-detail-editor__textarea {
    width: 100%;
    min-height: 220rpx;
    padding: 22rpx;
    border-radius: 24rpx;
    background: #ffffff;
    box-sizing: border-box;
    font-size: 28rpx;
    line-height: 1.8;
    color: var(--wm-text-primary, #111111);
}

.staff-long-detail-editor__style-group {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    margin-top: 18rpx;
}

.staff-long-detail-editor__style-row {
    align-items: flex-start;
    gap: 16rpx;
}

.staff-long-detail-editor__style-label {
    width: 88rpx;
    flex-shrink: 0;
    padding-top: 10rpx;
}

.staff-long-detail-editor__style-row--color {
    align-items: flex-start;
}

.staff-long-detail-editor__chip {
    min-height: 60rpx;
    padding: 0 24rpx;
    border-radius: 999rpx;
    border: 1rpx solid #E7E2D6;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.staff-long-detail-editor__chip.is-active {
    background: #f3f2ee;
    border-color: rgba(11, 11, 11, 0.22);
    color: var(--wm-color-primary, #0b0b0b);
    font-weight: 700;
}

.staff-long-detail-editor__color-panel {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.staff-long-detail-editor__color-current {
    min-height: 70rpx;
    padding: 0 16rpx;
    border-radius: 999rpx;
    border: 1rpx solid #E7E2D6;
    background: #ffffff;
    display: flex;
    align-items: center;
    gap: 12rpx;
    box-sizing: border-box;
}

.staff-long-detail-editor__color-current-dot {
    width: 38rpx;
    height: 38rpx;
    flex-shrink: 0;
    border-radius: 999rpx;
    border: 1rpx solid rgba(11, 11, 11, 0.14);
    box-shadow: inset 0 0 0 2rpx rgba(255, 255, 255, 0.72);
}

.staff-long-detail-editor__color-current-value {
    flex: 1;
    min-width: 0;
    font-size: 23rpx;
    font-weight: 800;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.staff-long-detail-editor__palette-button {
    min-width: 92rpx;
    min-height: 46rpx;
    padding: 0 18rpx;
    border-radius: 999rpx;
    background: var(--wm-color-primary, #0b0b0b);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22rpx;
    font-weight: 700;
    box-sizing: border-box;
}

.staff-long-detail-editor__color {
    position: relative;
    width: 44rpx;
    height: 44rpx;
    border-radius: 999rpx;
    border: 4rpx solid #ffffff;
    box-shadow: 0 0 0 1rpx rgba(11, 11, 11, 0.14), 0 8rpx 18rpx rgba(74, 43, 24, 0.08);
    box-sizing: border-box;
}

.staff-long-detail-editor__color.is-active {
    box-shadow: 0 0 0 4rpx #ffffff, 0 0 0 7rpx var(--wm-color-champagne, #d9be82),
        0 10rpx 22rpx rgba(184, 149, 74, 0.2);
}

.staff-long-detail-editor__color-inner {
    position: absolute;
    inset: 7rpx;
    border-radius: 999rpx;
    border: 1rpx solid rgba(255, 255, 255, 0.72);
}

.staff-long-detail-editor__picker-mask {
    position: fixed;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    z-index: 180;
    background: rgba(25, 23, 19, 0.42);
}

.staff-long-detail-editor__picker-drawer {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 181;
    padding: 18rpx 34rpx calc(34rpx + env(safe-area-inset-bottom));
    border-radius: 40rpx 40rpx 0 0;
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    background: var(--wm-color-bg-card, #fffdf8);
    box-shadow: 0 -20rpx 56rpx rgba(74, 43, 24, 0.18);
    box-sizing: border-box;
}

.staff-long-detail-editor__picker-handle {
    width: 72rpx;
    height: 8rpx;
    margin: 0 auto 24rpx;
    border-radius: 999rpx;
    background: rgba(102, 94, 82, 0.28);
}

.staff-long-detail-editor__picker-head,
.staff-long-detail-editor__hex-field,
.staff-long-detail-editor__picker-actions {
    display: flex;
    align-items: center;
}

.staff-long-detail-editor__picker-head {
    justify-content: space-between;
    gap: 18rpx;
    margin-bottom: 24rpx;
}

.staff-long-detail-editor__picker-title-group {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.staff-long-detail-editor__picker-title {
    font-size: 34rpx;
    font-weight: 800;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.staff-long-detail-editor__picker-hex {
    font-size: 23rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-secondary, #5f5a50);
}

.staff-long-detail-editor__picker-preview {
    width: 72rpx;
    height: 72rpx;
    flex-shrink: 0;
    border-radius: 26rpx;
    border: 4rpx solid #ffffff;
    box-shadow: 0 0 0 1rpx rgba(11, 11, 11, 0.14), 0 14rpx 28rpx rgba(74, 43, 24, 0.12);
    box-sizing: border-box;
}

.staff-long-detail-editor__sv-panel {
    position: relative;
    height: 300rpx;
    overflow: hidden;
    border-radius: 30rpx;
    border: 1rpx solid rgba(11, 11, 11, 0.12);
}

.staff-long-detail-editor__sv-white,
.staff-long-detail-editor__sv-black {
    position: absolute;
    inset: 0;
}

.staff-long-detail-editor__sv-white {
    background: linear-gradient(90deg, #ffffff 0%, rgba(255, 255, 255, 0) 100%);
}

.staff-long-detail-editor__sv-black {
    background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, #000000 100%);
}

.staff-long-detail-editor__sv-cursor {
    position: absolute;
    z-index: 2;
    width: 34rpx;
    height: 34rpx;
    border-radius: 999rpx;
    border: 4rpx solid #ffffff;
    box-shadow: 0 0 0 2rpx rgba(11, 11, 11, 0.42), 0 8rpx 18rpx rgba(11, 11, 11, 0.22);
    transform: translate(-50%, -50%);
    box-sizing: border-box;
}

.staff-long-detail-editor__hue-slider {
    position: relative;
    height: 44rpx;
    margin-top: 22rpx;
    border-radius: 999rpx;
    border: 1rpx solid rgba(11, 11, 11, 0.12);
    background: linear-gradient(
        90deg,
        #ff0000 0%,
        #ffff00 16.66%,
        #00ff00 33.33%,
        #00ffff 50%,
        #0000ff 66.66%,
        #ff00ff 83.33%,
        #ff0000 100%
    );
}

.staff-long-detail-editor__hue-cursor {
    position: absolute;
    top: 50%;
    width: 34rpx;
    height: 56rpx;
    border-radius: 999rpx;
    border: 4rpx solid #ffffff;
    background: rgba(17, 17, 17, 0.26);
    box-shadow: 0 0 0 2rpx rgba(11, 11, 11, 0.3), 0 8rpx 18rpx rgba(11, 11, 11, 0.18);
    transform: translate(-50%, -50%);
    box-sizing: border-box;
}

.staff-long-detail-editor__hex-field {
    gap: 16rpx;
    min-height: 82rpx;
    margin-top: 24rpx;
    padding: 0 22rpx;
    border-radius: 28rpx;
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    background: var(--wm-color-bg-soft, #faf6ee);
    box-sizing: border-box;
}

.staff-long-detail-editor__hex-label {
    flex-shrink: 0;
    font-size: 23rpx;
    font-weight: 800;
    color: var(--wm-text-secondary, #5f5a50);
}

.staff-long-detail-editor__hex-input {
    flex: 1;
    min-width: 0;
    height: 78rpx;
    font-size: 28rpx;
    font-weight: 800;
    color: var(--wm-text-primary, #111111);
}

.staff-long-detail-editor__picker-actions {
    gap: 18rpx;
    margin-top: 26rpx;
}

.staff-long-detail-editor__picker-action {
    flex: 1;
    min-height: 84rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 27rpx;
    font-weight: 800;
    box-sizing: border-box;
}

.staff-long-detail-editor__picker-action--light {
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    background: #ffffff;
    color: var(--wm-text-primary, #111111);
}

.staff-long-detail-editor__picker-action--dark {
    border: 1rpx solid var(--wm-color-champagne, #d9be82);
    background: var(--wm-color-primary, #0b0b0b);
    color: #ffffff;
}
</style>
