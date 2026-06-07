<template>
    <view :class="cardClass" :style="cardStyle" @click="handleClick">
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    variant?: 'surface' | 'glass' | 'hero' | 'panel' | 'list' | 'media' | 'quiet' | 'bare'
    type?: 'standard' | 'glass' | 'dark'
    scene?: 'consumer' | 'staff' | 'admin'
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
    interactive: false,
    hoverable: true,
    padding: '',
    borderRadius: '',
    background: '',
    border: '',
    boxShadow: ''
})

const emit = defineEmits<{
    (event: 'click', payload: Event): void
}>()

const resolvedVariant = computed(() => {
    if (props.variant) {
        return props.variant
    }

    if (props.type === 'glass') {
        return 'glass'
    }

    if (props.type === 'dark') {
        return 'hero'
    }

    return 'surface'
})

const isInteractive = computed(() => props.interactive || props.hoverable)

const cardClass = computed(() => {
    const classes = [
        'base-card',
        `base-card--${resolvedVariant.value}`,
        `base-card--${props.scene}`
    ]

    if (isInteractive.value) {
        classes.push('base-card--interactive')
    }

    return classes.join(' ')
})

const cardStyle = computed(() => {
    const styles: Record<string, string> = {}

    if (props.padding) {
        styles.padding = props.padding
    }

    if (props.borderRadius) {
        styles.borderRadius = props.borderRadius
    }

    if (props.background) {
        styles.background = props.background
    }

    if (props.border) {
        styles.border = props.border
    }

    if (props.boxShadow) {
        styles.boxShadow = props.boxShadow
    }

    return styles
})

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
    width: 100%;
    border-radius: var(--wm-radius-card, 16rpx);
    transition: transform var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1),
        box-shadow var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1),
        border-color var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1),
        background var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1);

    &--surface,
    &--panel,
    &--list,
    &--quiet {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, #ffffff 100%);
        border: 1rpx solid var(--wm-color-border, #e8e0d2);
        box-shadow: var(--wm-shadow-soft, 0 10rpx 28rpx rgba(17, 17, 17, 0.05));
    }

    &--surface {
        padding: var(--wm-space-card-padding, 28rpx);
    }

    &--panel {
        padding: var(--wm-space-card-padding, 28rpx) 32rpx;
    }

    &--list {
        padding: 24rpx;
    }

    &--media {
        padding: 0;
        overflow: hidden;
        border-radius: var(--wm-radius-card-lg, 32rpx);
        background: #ffffff;
        border: 1rpx solid var(--wm-color-border, #e8e0d2);
        box-shadow: var(--wm-shadow-card, 0 18rpx 42rpx rgba(17, 17, 17, 0.08));
    }

    &--quiet,
    &--bare {
        box-shadow: none;
    }

    &--bare {
        padding: 0;
        border: none;
        background: transparent;
    }

    &--glass {
        padding: var(--wm-space-card-padding, 28rpx);
        border-radius: var(--wm-radius-card-glass, 26rpx);
        background: rgba(255, 255, 255, 0.94);
        border: 1rpx solid rgba(232, 224, 210, 0.9);
        box-shadow: var(--wm-shadow-soft, 0 10rpx 28rpx rgba(17, 17, 17, 0.05));
        backdrop-filter: blur(18rpx);
        -webkit-backdrop-filter: blur(18rpx);
    }

    &--hero {
        padding: var(--wm-space-card-padding-lg, 32rpx);
        border-radius: var(--wm-radius-card-lg, 32rpx);
        background: var(
            --wm-hero-gradient,
            radial-gradient(circle at 12% 0%, rgba(200, 164, 93, 0.18) 0, transparent 34%), linear-gradient(180deg, #ffffff 0%, #fbfaf7 62%, #f8f1e1 100%)
        );
        border: 1rpx solid rgba(200, 164, 93, 0.58);
        box-shadow: var(--wm-shadow-hero, 0 26rpx 60rpx rgba(17, 17, 17, 0.14));
    }

    &--admin.base-card--panel {
        border-radius: var(--wm-radius-card, 16rpx);
    }

    &--staff {
        &.base-card--surface,
        &.base-card--panel {
            background: #ffffff;
            border-color: var(--wm-color-border, #e5e5e5);
            box-shadow: none;
        }

        &.base-card--glass {
            background: #ffffff;
            border-color: var(--wm-color-border, #e5e5e5);
            box-shadow: none;
        }

        &.base-card--hero {
            background: radial-gradient(
                    circle at top right,
                    rgba(200, 164, 93, 0.12) 0,
                    transparent 34%
                ),
                linear-gradient(180deg, #ffffff 0%, #ffffff 62%, #f8f3e7 100%);
            border-color: rgba(200, 164, 93, 0.72);
            box-shadow: var(--wm-shadow-hero, 0 16rpx 36rpx rgba(17, 17, 17, 0.1));
        }
    }

    &--interactive {
        cursor: pointer;

        &:active {
            transform: translateY(2rpx) scale(0.996);
            box-shadow: var(--wm-shadow-soft, 0 10rpx 28rpx rgba(17, 17, 17, 0.05));
        }
    }
}

/* #ifdef MP-WEIXIN */
.base-card--glass {
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
/* #endif */
</style>
