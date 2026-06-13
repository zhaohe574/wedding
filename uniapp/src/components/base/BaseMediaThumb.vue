<template>
    <view class="base-media-thumb" :class="{ 'base-media-thumb--dark': dark }" @click="emit('click')">
        <image v-if="src" class="base-media-thumb__image" :src="src" mode="aspectFill" />
        <view v-else class="base-media-thumb__empty">
            <BaseIcon :name="safeIcon" size="34" color="var(--wm-color-champagne, #D9BE82)" />
        </view>
        <text v-if="label" class="base-media-thumb__label">{{ label }}</text>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from './BaseIcon.vue'

interface Props {
    src?: string
    label?: string
    icon?: string
    dark?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    src: '',
    label: '',
    icon: 'image',
    dark: true
})

const emit = defineEmits<{
    (event: 'click'): void
}>()

const safeIcon = computed(() => {
    const icon = typeof props.icon === 'string' ? props.icon.trim() : ''
    return icon || 'image'
})
</script>

<script lang="ts">
export default {
    name: 'BaseMediaThumb',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-media-thumb {
    width: 188rpx;
    min-height: 156rpx;
    padding: 12rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    border-radius: 36rpx;
    border: 1rpx solid var(--wm-color-border, #D8C9AD);
    background: var(--wm-color-bg-card, #FFFDF8);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));

    &--dark {
        background: var(--wm-color-primary, #191713);
        border-color: var(--wm-color-champagne, #D9BE82);
        box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
    }

    &__image,
    &__empty {
        width: 100%;
        height: 108rpx;
        border-radius: 28rpx;
        overflow: hidden;
    }

    &__empty {
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 253, 248, 0.08);
    }

    &__label {
        max-width: 100%;
        font-size: 22rpx;
        font-weight: 900;
        color: var(--wm-text-inverse, #FFFDF8);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &:not(.base-media-thumb--dark) &__label {
        color: var(--wm-text-primary, #191713);
    }
}
</style>
