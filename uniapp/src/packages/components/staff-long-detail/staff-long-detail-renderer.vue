<template>
    <view v-if="blocks.length" class="staff-long-detail-renderer">
        <template v-for="block in blocks" :key="block.id">
            <view v-if="block.type === 'image'" class="staff-long-detail-renderer__image-group">
                <image
                    v-for="(image, imageIndex) in block.images"
                    :key="`${block.id}-${imageIndex}`"
                    v-show="!isHiddenImage(block.id, imageIndex)"
                    :src="image"
                    mode="widthFix"
                    class="staff-long-detail-renderer__image"
                    lazy-load
                    @click="previewImages(block.id, block.images, image)"
                    @error="handleImageError(block.id, imageIndex, image, $event)"
                />
            </view>

            <view v-else class="staff-long-detail-renderer__text" :style="getTextStyle(block)">
                {{ block.content }}
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import type { CSSProperties } from 'vue'
import { computed, ref } from 'vue'
import type { LongDetailBlock, LongDetailTextBlock } from './utils'
import { getLongDetailFontSize, parseLongDetailContent } from './utils'
import { isDevMode } from '@/utils/env'

const props = defineProps<{
    content?: string
}>()

const blocks = computed<LongDetailBlock[]>(() => parseLongDetailContent(props.content))
const hiddenImageMap = ref<Record<string, boolean>>({})

const getTextStyle = (block: LongDetailTextBlock): CSSProperties => ({
    textAlign: block.style.align,
    fontSize: getLongDetailFontSize(block.style.fontSize),
    color: block.style.color || '#1E2432',
    fontWeight: block.style.bold ? 700 : 400
})

const getHiddenImageKey = (blockId: string, index: number) => `${blockId}-${index}`

const isHiddenImage = (blockId: string, index: number) =>
    Boolean(hiddenImageMap.value[getHiddenImageKey(blockId, index)])

const handleImageError = (blockId: string, index: number, image: string, error: any) => {
    hiddenImageMap.value[getHiddenImageKey(blockId, index)] = true
    if (!isDevMode()) {
        return
    }

    console.warn('人员详情长图资源加载失败', {
        blockId,
        image,
        error: error?.detail || error || null
    })
}

const previewImages = (blockId: string, images: string[], currentImage: string) => {
    const urls = images
        .map((item, index) => ({
            url: String(item || '').trim(),
            hidden: isHiddenImage(blockId, index)
        }))
        .filter((item) => item.url && !item.hidden)
        .map((item) => item.url)
    if (!urls.length) {
        return
    }
    uni.previewImage({
        urls,
        current: currentImage && urls.includes(currentImage) ? currentImage : urls[0]
    })
}
</script>

<style lang="scss" scoped>
.staff-long-detail-renderer {
    width: 100%;
    background: #ffffff;
    overflow: hidden;
    border-radius: 36rpx;
}

.staff-long-detail-renderer__image-group {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.staff-long-detail-renderer__image {
    width: 100%;
    display: block;
    vertical-align: top;
}

.staff-long-detail-renderer__text {
    padding: 28rpx 30rpx;
    line-height: 1.8;
    white-space: pre-wrap;
    word-break: break-all;
    box-sizing: border-box;
}
</style>
