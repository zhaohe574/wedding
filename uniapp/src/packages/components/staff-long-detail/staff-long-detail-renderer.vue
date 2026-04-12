<template>
    <view v-if="blocks.length" class="staff-long-detail-renderer">
        <template v-for="block in blocks" :key="block.id">
            <view v-if="block.type === 'image'" class="staff-long-detail-renderer__image-group">
                <image
                    v-for="(image, imageIndex) in block.images"
                    :key="`${block.id}-${imageIndex}`"
                    :src="image"
                    mode="widthFix"
                    class="staff-long-detail-renderer__image"
                    lazy-load
                    @click="previewImages(block.images, imageIndex)"
                />
            </view>

            <view
                v-else
                class="staff-long-detail-renderer__text"
                :style="getTextStyle(block)"
            >
                {{ block.content }}
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import type { CSSProperties } from 'vue'
import { computed } from 'vue'
import type { LongDetailBlock, LongDetailTextBlock } from './utils'
import { getLongDetailFontSize, parseLongDetailContent } from './utils'

const props = defineProps<{
    content?: string
}>()

const blocks = computed<LongDetailBlock[]>(() => parseLongDetailContent(props.content))

const getTextStyle = (block: LongDetailTextBlock): CSSProperties => ({
    textAlign: block.style.align,
    fontSize: getLongDetailFontSize(block.style.fontSize),
    color: block.style.color || '#1E2432',
    fontWeight: block.style.bold ? 700 : 400
})

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
