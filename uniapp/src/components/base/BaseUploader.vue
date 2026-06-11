<template>
    <view class="base-uploader">
        <view class="base-uploader__header">
            <text class="base-uploader__title">{{ title }}</text>
            <text class="base-uploader__count">{{ items.length }}/{{ max }}</text>
        </view>
        <view class="base-uploader__grid">
            <BaseMediaThumb
                v-for="item in items"
                :key="item.id"
                :src="item.url"
                :label="item.label"
                :dark="false"
                @click="emit('preview', item)"
            />
            <view v-if="items.length < max" class="base-uploader__add" @click="emit('add')">
                <BaseIcon name="add" size="42" color="var(--wm-color-champagne, #E9C7A7)" />
                <text class="base-uploader__add-text">上传凭证</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import BaseIcon from './BaseIcon.vue'
import BaseMediaThumb from './BaseMediaThumb.vue'

interface UploadItem {
    id: string | number
    url?: string
    label?: string
}

interface Props {
    title?: string
    items?: UploadItem[]
    max?: number
}

withDefaults(defineProps<Props>(), {
    title: '上传凭证',
    items: () => [],
    max: 6
})

const emit = defineEmits<{
    (event: 'add'): void
    (event: 'preview', item: UploadItem): void
}>()
</script>

<script lang="ts">
export default {
    name: 'BaseUploader',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-uploader {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    padding: 34rpx;
    border-radius: var(--wm-radius-card, 44rpx);
    background: var(--wm-color-bg-card, #FFFDF8);
    border: 1rpx solid var(--wm-color-border, #E3D7C9);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));

    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20rpx;
    }

    &__title {
        font-size: 30rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #1A1A1A);
    }

    &__count {
        font-size: 22rpx;
        font-weight: 900;
        color: var(--wm-color-gold, #D4916E);
    }

    &__grid {
        display: flex;
        flex-wrap: wrap;
        gap: 18rpx;
    }

    &__add {
        width: 188rpx;
        height: 156rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10rpx;
        border-radius: 36rpx;
        border: 1rpx dashed var(--wm-color-champagne, #E9C7A7);
        background: var(--wm-color-gold-soft, #F6E2D6);
    }

    &__add-text {
        font-size: 22rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #1A1A1A);
    }
}
</style>
