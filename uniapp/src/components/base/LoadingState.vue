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
    tone?: 'neutral' | 'wedding' | 'workspace' | 'dark'
}

const props = withDefaults(defineProps<Props>(), {
    text: '加载中...',
    color: 'var(--wm-color-gold, #D4916E)',
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

<script lang="ts">
export default {
    name: 'LoadingState',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.loading-state-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 18rpx;
    min-height: 320rpx;
    padding: 56rpx 28rpx;

    &__mark {
        width: 104rpx;
        height: 104rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999rpx;
        background: var(--wm-color-primary, #1A1A1A);
        border: 1rpx solid var(--wm-color-champagne, #E9C7A7);
        box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
    }

    &__text {
        font-size: 24rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #1A1A1A);
    }

    &__description {
        max-width: 520rpx;
        margin-top: -8rpx;
        font-size: 22rpx;
        line-height: 1.6;
        text-align: center;
        color: var(--wm-text-secondary, #6B625A);
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
        height: 14rpx;
        border-radius: 999rpx;
        background: linear-gradient(90deg, rgba(227, 215, 201, 0.5) 0%, rgba(255, 253, 248, 0.96) 50%, rgba(227, 215, 201, 0.5) 100%);
        background-size: 200% 100%;
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

    &--dark &__text {
        color: var(--wm-text-inverse, #FFFDF8);
    }
}

@keyframes loading-state-shimmer {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}
</style>
