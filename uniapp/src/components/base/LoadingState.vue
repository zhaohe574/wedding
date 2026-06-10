<template>
    <view class="loading-state-block" :class="stateClass">
        <view class="loading-state-block__mark">
            <tn-loading :size="compact ? 42 : 58" mode="flower" :color="color" />
        </view>
        <text class="loading-state-block__text">{{ text }}</text>
        <text v-if="description" class="loading-state-block__description">{{ description }}</text>
        <view class="loading-state-block__bars">
            <view class="loading-state-block__bar loading-state-block__bar--wide"></view>
            <view class="loading-state-block__bar"></view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    text?: string
    color?: string
    description?: string
    compact?: boolean
    tone?: 'neutral' | 'wedding' | 'workspace'
}

const props = withDefaults(defineProps<Props>(), {
    text: '加载中...',
    color: 'var(--wm-color-primary, #0B0B0B)',
    description: '',
    compact: false,
    tone: 'neutral'
})

const stateClass = computed(() => [
    `loading-state-block--${props.tone}`,
    {
        'loading-state-block--compact': props.compact
    }
])
</script>

<style lang="scss" scoped>
.loading-state-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 18rpx;
    min-height: 300rpx;
    padding: 64rpx 28rpx;
    box-sizing: border-box;

    &__mark {
        width: 96rpx;
        height: 96rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999rpx;
        background: var(--wm-color-secondary-soft, rgba(248, 241, 225, 0.72));
        border: 1rpx solid rgba(200, 164, 93, 0.18);
    }

    &__text {
        font-size: 24rpx;
        font-weight: 600;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__description {
        max-width: 520rpx;
        margin-top: -8rpx;
        font-size: 22rpx;
        line-height: 1.6;
        text-align: center;
        color: var(--wm-text-tertiary, #8a8a8a);
    }

    &__bars {
        width: 280rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12rpx;
    }

    &__bar {
        width: 180rpx;
        height: 12rpx;
        border-radius: 999rpx;
        background: linear-gradient(90deg, rgba(200, 164, 93, 0.08) 0%, rgba(200, 164, 93, 0.2) 50%, rgba(200, 164, 93, 0.08) 100%);
        animation: loading-state-shimmer 1.4s ease-in-out infinite;
    }

    &__bar--wide {
        width: 260rpx;
    }

    &--compact {
        min-height: 220rpx;
        padding: 42rpx 24rpx;
        gap: 14rpx;
    }

    &--compact &__mark {
        width: 76rpx;
        height: 76rpx;
    }

    &--workspace &__mark {
        background: var(--wm-color-bg-soft, #f7f7f7);
        border-color: var(--wm-color-border, #e5e5e5);
    }
}

@keyframes loading-state-shimmer {
    0% {
        opacity: 0.56;
    }

    50% {
        opacity: 1;
    }

    100% {
        opacity: 0.56;
    }
}
</style>
