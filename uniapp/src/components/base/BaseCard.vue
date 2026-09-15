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

type CardVariant =
    | 'surface'
    | 'dark'
    | 'gold'
    | 'soft'
    | 'glass'
    | 'hero'
    | 'panel'
    | 'list'
    | 'listDark'
    | 'media'
    | 'quiet'
    | 'bare'

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
    border-radius: var(--wm-radius-card, 24rpx);
    box-sizing: border-box;
    transition: transform var(--wm-motion-base, 220ms) cubic-bezier(0.25, 1, 0.5, 1),
        box-shadow var(--wm-motion-base, 220ms) ease,
        border-color var(--wm-motion-base, 220ms) ease,
        opacity var(--wm-motion-fast, 150ms) ease;

    &::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(125deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.05) 40%, transparent 70%);
        pointer-events: none;
        opacity: 0.9;
    }

    &--surface,
    &--panel,
    &--soft,
    &--quiet {
        padding: var(--wm-space-card-padding, 28rpx);
        background: var(--wm-color-bg-card, #FFFFFF);
        border: 1rpx solid var(--wm-color-border, #E8DFD1);
        box-shadow: var(--wm-shadow-soft, 0 8rpx 24rpx rgba(28, 24, 20, 0.05));
    }

    &--panel {
        padding: var(--wm-space-card-padding-lg, 36rpx);
    }

    &--soft {
        background: var(--wm-color-bg-soft, #F5F1EB);
        border: 1rpx solid var(--wm-color-border, #E8DFD1);
    }

    &--quiet {
        box-shadow: none;
    }

    &--bare {
        padding: 0;
        border: none;
        background: transparent;
        box-shadow: none;

        &::before {
            display: none;
        }
    }

    &--media {
        padding: 0;
        background: var(--wm-color-bg-card, #FFFFFF);
        border: 1rpx solid var(--wm-color-border, #E8DFD1);
        box-shadow: var(--wm-shadow-card, 0 12rpx 32rpx rgba(28, 24, 20, 0.08));
    }

    &--glass {
        padding: var(--wm-space-card-padding, 28rpx);
        background: rgba(255, 255, 255, 0.92);
        border: 1rpx solid rgba(197, 164, 109, 0.25);
        box-shadow: var(--wm-shadow-soft, 0 8rpx 24rpx rgba(28, 24, 20, 0.05));
    }

    &--hero,
    &--gold {
        padding: var(--wm-space-card-padding-lg, 36rpx);
        border-color: var(--wm-color-champagne, #C5A46D);
        box-shadow: var(--wm-shadow-hero, 0 24rpx 56rpx rgba(28, 24, 20, 0.14));
    }

    &--hero {
        background: radial-gradient(circle at 14% 0%, rgba(197, 164, 109, 0.22) 0, transparent 46%),
            linear-gradient(145deg, #25201C 0%, #1A1816 60%, #302619 100%);
        color: var(--wm-text-inverse, #FCFAF7);
        border: 1rpx solid rgba(197, 164, 109, 0.45);
    }

    &--gold {
        background: linear-gradient(180deg, var(--wm-color-gold-soft, #F7EFE3) 0%, #FFFFFF 100%);
        border: 1rpx solid rgba(197, 164, 109, 0.35);
    }

    &--dark {
        padding: var(--wm-space-card-padding-lg, 36rpx);
        background: linear-gradient(145deg, #24201C 0%, #1A1816 60%, #2A2218 100%);
        border: 1rpx solid rgba(197, 164, 109, 0.4);
        box-shadow: var(--wm-shadow-action, 0 16rpx 36rpx rgba(28, 24, 20, 0.22));
        color: var(--wm-text-inverse, #FCFAF7);
    }

    &--staff {
        background: var(--wm-color-bg-card, #1F1D1A);
        border: 1rpx solid var(--wm-color-border, #38332C);
        box-shadow: var(--wm-shadow-soft, 0 10rpx 30rpx rgba(0, 0, 0, 0.35));
        color: var(--wm-text-primary, #FAF8F5);

        &::before {
            background: linear-gradient(125deg, rgba(197, 164, 109, 0.08) 0%, transparent 60%);
        }
    }

    &--list,
    &--listDark {
        padding: var(--wm-space-list-panel-y, 16rpx) var(--wm-space-list-panel-x, 28rpx);
        border-radius: var(--wm-radius-list-panel, 20rpx);
    }

    &--list {
        background: var(--wm-color-bg-card, #FFFFFF);
        border: 1rpx solid var(--wm-color-border, #E8DFD1);
        box-shadow: var(--wm-shadow-soft, 0 8rpx 24rpx rgba(28, 24, 20, 0.05));
    }

    &--listDark {
        background: linear-gradient(135deg, #24201C 0%, #1A1816 70%, #2E2519 100%);
        border: 1rpx solid rgba(197, 164, 109, 0.3);
        box-shadow: var(--wm-shadow-soft, 0 10rpx 28rpx rgba(0, 0, 0, 0.3));
        color: #FAF8F5;
    }

    &--list::before,
    &--listDark::before {
        opacity: 0;
    }

    &--interactive {
        cursor: pointer;
        will-change: transform, box-shadow;

        &:active {
            transform: scale(0.985) translateY(2rpx);
            box-shadow: 0 4rpx 14rpx rgba(28, 24, 20, 0.04);
            opacity: 0.94;
        }
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
        letter-spacing: 1rpx;
        color: var(--wm-color-gold, #B8954A);
    }

    &__title {
        font-size: 32rpx;
        font-weight: 900;
        line-height: 1.3;
        color: inherit;
    }

    &__description {
        font-size: 24rpx;
        line-height: 1.6;
        color: var(--wm-text-secondary, #665E52);
    }

    &--hero &__description,
    &--dark &__description {
        color: rgba(255, 253, 248, 0.72);
    }

    &__footer {
        margin-top: 24rpx;
    }
}
</style>
