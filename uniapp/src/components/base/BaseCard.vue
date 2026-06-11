<template>
    <view :class="cardClass" :style="cardStyle" @click="handleClick">
        <view v-if="eyebrow || title || description || $slots.header" class="base-card__header">
            <slot name="header">
                <view class="base-card__title-group">
                    <text v-if="eyebrow" class="base-card__eyebrow">{{ eyebrow }}</text>
                    <text v-if="title" class="base-card__title">{{ title }}</text>
                    <text v-if="description" class="base-card__description">{{ description }}</text>
                </view>
            </slot>
        </view>
        <slot />
        <view v-if="$slots.footer" class="base-card__footer">
            <slot name="footer" />
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type CardVariant = 'surface' | 'dark' | 'gold' | 'soft' | 'glass' | 'hero' | 'panel' | 'list' | 'media' | 'quiet' | 'bare'

interface Props {
    variant?: CardVariant
    type?: 'standard' | 'glass' | 'dark'
    scene?: 'consumer' | 'staff' | 'admin'
    title?: string
    eyebrow?: string
    description?: string
    interactive?: boolean
    hoverable?: boolean
    padding?: string
    borderRadius?: string
    background?: string
    border?: string
    boxShadow?: string
}

const props = withDefaults(defineProps<Props>(), {
    variant: undefined,
    type: 'standard',
    scene: 'consumer',
    title: '',
    eyebrow: '',
    description: '',
    interactive: false,
    hoverable: false,
    padding: '',
    borderRadius: '',
    background: '',
    border: '',
    boxShadow: ''
})

const emit = defineEmits<{
    (event: 'click', payload: Event): void
}>()

const resolvedVariant = computed<CardVariant>(() => {
    if (props.variant) return props.variant
    if (props.type === 'glass') return 'glass'
    if (props.type === 'dark') return 'dark'
    return 'surface'
})

const cardClass = computed(() => [
    'base-card',
    `base-card--${resolvedVariant.value}`,
    `base-card--${props.scene}`,
    {
        'base-card--interactive': props.interactive || props.hoverable
    }
])

const cardStyle = computed(() => ({
    ...(props.padding ? { padding: props.padding } : {}),
    ...(props.borderRadius ? { borderRadius: props.borderRadius } : {}),
    ...(props.background ? { background: props.background } : {}),
    ...(props.border ? { border: props.border } : {}),
    ...(props.boxShadow ? { boxShadow: props.boxShadow } : {})
}))

const handleClick = (event: Event) => {
    emit('click', event)
}
</script>

<script lang="ts">
export default {
    name: 'BaseCard',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-card {
    position: relative;
    width: 100%;
    overflow: hidden;
    border-radius: var(--wm-radius-card, 44rpx);
    box-sizing: border-box;
    transition: transform var(--wm-motion-base, 220ms) ease,
        box-shadow var(--wm-motion-base, 220ms) ease,
        border-color var(--wm-motion-base, 220ms) ease;

    &::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, rgba(255, 253, 248, 0.16), transparent 34%);
        pointer-events: none;
        opacity: 0.9;
    }

    &--surface,
    &--panel,
    &--list,
    &--soft,
    &--quiet {
        padding: var(--wm-space-card-padding, 28rpx);
        background: linear-gradient(180deg, rgba(255, 253, 248, 0.98) 0%, var(--wm-color-bg-card, #FFFDF8) 100%);
        border: 1rpx solid var(--wm-color-border, #E3D7C9);
        box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    }

    &--panel {
        padding: var(--wm-space-card-padding-lg, 36rpx);
    }

    &--list {
        padding: 28rpx;
    }

    &--soft {
        background: var(--wm-color-bg-soft, #F8F1E7);
    }

    &--quiet {
        box-shadow: none;
    }

    &--bare {
        padding: 0;
        border: none;
        background: transparent;
        box-shadow: none;
    }

    &--media {
        padding: 0;
        background: var(--wm-color-bg-card, #FFFDF8);
        border: 1rpx solid var(--wm-color-border, #E3D7C9);
        box-shadow: var(--wm-shadow-card, 0 20rpx 48rpx rgba(74, 43, 24, 0.10));
    }

    &--glass {
        padding: var(--wm-space-card-padding, 28rpx);
        background: rgba(255, 253, 248, 0.94);
        border: 1rpx solid rgba(227, 215, 201, 0.9);
        box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    }

    &--hero,
    &--gold {
        padding: var(--wm-space-card-padding-lg, 36rpx);
        border-color: var(--wm-color-champagne, #E9C7A7);
        box-shadow: var(--wm-shadow-hero, 0 28rpx 68rpx rgba(74, 43, 24, 0.18));
    }

    &--hero {
        background: radial-gradient(circle at 12% 0%, rgba(233, 199, 167, 0.22) 0, transparent 42%),
            linear-gradient(145deg, #1A1A1A 0%, #0B0B0B 62%, #2D211A 100%);
        color: var(--wm-text-inverse, #FFFDF8);
        border: 1rpx solid var(--wm-color-champagne, #E9C7A7);
    }

    &--gold {
        background: linear-gradient(180deg, var(--wm-color-gold-soft, #F6E2D6) 0%, #FFF7EC 100%);
        border: 1rpx solid var(--wm-color-champagne, #E9C7A7);
    }

    &--dark {
        padding: var(--wm-space-card-padding-lg, 36rpx);
        background: linear-gradient(145deg, #1A1A1A 0%, #0B0B0B 62%, #2D211A 100%);
        border: 1rpx solid var(--wm-color-champagne, #E9C7A7);
        box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
        color: var(--wm-text-inverse, #FFFDF8);
    }

    &--interactive:active {
        transform: translateY(2rpx) scale(0.996);
        box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    }

    &__header,
    &__footer,
    &__title-group {
        position: relative;
        z-index: 1;
    }

    &__header {
        margin-bottom: 20rpx;
    }

    &__title-group {
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }

    &__eyebrow {
        font-size: 22rpx;
        font-weight: 900;
        color: var(--wm-color-gold, #D4916E);
    }

    &__title {
        font-size: 32rpx;
        font-weight: 900;
        line-height: 1.25;
        color: inherit;
    }

    &__description {
        font-size: 24rpx;
        line-height: 1.55;
        color: var(--wm-text-secondary, #6B625A);
    }

    &--hero &__description,
    &--dark &__description {
        color: rgba(255, 253, 248, 0.68);
    }

    &__footer {
        margin-top: 24rpx;
    }
}
</style>
