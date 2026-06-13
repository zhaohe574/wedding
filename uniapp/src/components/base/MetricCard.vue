<template>
    <view :class="metricClass">
        <view class="metric-card__top">
            <text class="metric-card__label">{{ label }}</text>
            <BaseIcon v-if="safeIcon" :name="safeIcon" size="32" :color="iconColor" />
        </view>
        <text class="metric-card__value">{{ value }}</text>
        <view v-if="trend || hint" class="metric-card__bottom">
            <text v-if="trend" class="metric-card__trend">{{ trend }}</text>
            <text v-if="hint" class="metric-card__hint">{{ hint }}</text>
        </view>
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from './BaseIcon.vue'

interface Props {
    tone?: 'neutral' | 'primary' | 'success' | 'warning' | 'dark'
    label: string
    value: string | number
    hint?: string
    trend?: string
    icon?: string
}

const props = withDefaults(defineProps<Props>(), {
    tone: 'dark',
    hint: '',
    trend: '',
    icon: ''
})

const safeIcon = computed(() => (typeof props.icon === 'string' ? props.icon.trim() : ''))
const resolvedTone = computed(() =>
    ['neutral', 'primary', 'success', 'warning', 'dark'].includes(props.tone || '') ? props.tone : 'dark'
)
const metricClass = computed(() => ['metric-card', `metric-card--${resolvedTone.value}`])
const iconColor = computed(() =>
    resolvedTone.value === 'dark' ? 'var(--wm-color-champagne, #D9BE82)' : 'var(--wm-color-gold, #B8954A)'
)
</script>

<style lang="scss" scoped>
.metric-card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    min-height: 216rpx;
    padding: 32rpx;
    border: 1rpx solid var(--wm-color-border, #D8C9AD);
    border-radius: var(--wm-radius-card-glass, 48rpx);
    background: var(--wm-color-bg-card, #FFFDF8);
    box-shadow: var(--wm-shadow-card, 0 20rpx 48rpx rgba(74, 43, 24, 0.10));
    overflow: hidden;

    &::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 18% 0%, rgba(217, 190, 130, 0.18) 0, transparent 42%);
        pointer-events: none;
    }

    &--dark,
    &--primary {
        background: linear-gradient(145deg, #2B261D 0%, #191713 62%, #3A2A16 100%);
        border-color: var(--wm-color-champagne, #D9BE82);
        color: var(--wm-text-inverse, #FFFDF8);
        box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
    }

    &--success {
        background: var(--wm-color-success-soft, #E8EFE6);
        border-color: #71806F;
    }

    &--warning {
        background: var(--wm-color-warning-soft, #F1E5C8);
        border-color: var(--wm-color-champagne, #D9BE82);
    }

    &__top,
    &__bottom,
    &__label,
    &__value {
        position: relative;
        z-index: 1;
    }

    &__top,
    &__bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__label {
        font-size: 24rpx;
        font-weight: 800;
        color: var(--wm-text-secondary, #665E52);
    }

    &--dark &__label,
    &--primary &__label,
    &--dark &__hint,
    &--primary &__hint {
        color: rgba(255, 253, 248, 0.68);
    }

    &__value {
        font-family: var(--wm-font-family-display, Georgia, serif);
        font-size: 56rpx;
        line-height: 1.05;
        font-weight: 900;
        color: inherit;
    }

    &__trend {
        font-size: 24rpx;
        font-weight: 900;
        color: var(--wm-color-gold, #B8954A);
    }

    &__hint {
        font-size: 24rpx;
        font-weight: 700;
        color: var(--wm-text-tertiary, #8A806F);
    }
}
</style>
