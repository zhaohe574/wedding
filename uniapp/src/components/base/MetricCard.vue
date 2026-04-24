<template>
    <view :class="metricClass">
        <text class="metric-card__label">{{ label }}</text>
        <text class="metric-card__value">{{ value }}</text>
        <text v-if="hint" class="metric-card__hint">{{ hint }}</text>
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    tone?: 'neutral' | 'primary' | 'success' | 'warning'
    label: string
    value: string | number
    hint?: string
}

const props = withDefaults(defineProps<Props>(), {
    tone: 'neutral',
    hint: ''
})

const metricClass = computed(() => ['metric-card', `metric-card--${props.tone}`])
</script>

<style lang="scss" scoped>
.metric-card {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    padding: 18rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    border-radius: var(--wm-radius-card, 24rpx);
    box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(17, 17, 17, 0.16));

    &__label {
        font-size: 22rpx;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__value {
        font-size: 40rpx;
        line-height: 1.1;
        font-weight: 700;
        color: var(--wm-text-primary, #111111);
    }

    &__hint {
        font-size: 22rpx;
        color: var(--wm-text-tertiary, #9a9388);
    }

    &--primary {
        background: var(--wm-color-primary-soft, #f3f2ee);
        border-color: var(--wm-color-border-strong, #d8c28a);
    }
}
</style>
