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
                            <view class="staff-long-detail-editor__color-group">
                                <view
                                    v-for="color in colorOptions"
                                    :key="color"
                                    :class="[
                                        'staff-long-detail-editor__color',
                                        { 'is-active': block.style.color === color }
                                    ]"
                                    :style="{ backgroundColor: color }"
                                    @click="block.style.color = color"
                                ></view>
                            </view>
                        </view>
                    </view>
                </template>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'
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

const colorOptions = ['#111111', '#111111', '#5F5A50', '#0B0B0B', '#C8A45D', '#4D4A42']

const imageUploading = ref(false)
const blocks = ref<LongDetailBlock[]>(parseLongDetailDraftContent(props.modelValue))

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

.staff-long-detail-editor__color {
    width: 40rpx;
    height: 40rpx;
    border-radius: 999rpx;
    border: 4rpx solid transparent;
}

.staff-long-detail-editor__color.is-active {
    box-shadow: 0 0 0 4rpx #ffffff, 0 0 0 6rpx rgba(11, 11, 11, 0.72);
}
</style>
