<template>
    <view :class="cardClass" :style="cardStyle" @click="handleClick">
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    variant?: 'surface' | 'glass' | 'hero' | 'panel'
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
    border-radius: var(--wm-radius-card, 42rpx);
    transition: transform var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1),
        box-shadow var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1),
        border-color var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1),
        background var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1);

    &--surface,
    &--panel {
        background: var(--wm-color-bg-card, rgba(255, 255, 255, 0.94));
        border: 1rpx solid var(--wm-color-border, #e7e2d6);
        box-shadow: var(--wm-shadow-soft, 0 16rpx 34rpx rgba(17, 17, 17, 0.06));
    }

    &--surface {
        padding: var(--wm-space-card-padding, 28rpx);
    }

    &--panel {
        padding: var(--wm-space-card-padding, 28rpx) 32rpx;
    }

    &--glass {
        padding: var(--wm-space-card-padding, 28rpx);
        border-radius: var(--wm-radius-card-glass, 46rpx);
        background: var(--wm-color-bg-card, rgba(255, 255, 255, 0.9));
        border: 1rpx solid var(--wm-color-border, #e7e2d6);
        box-shadow: var(--wm-shadow-card, 0 18rpx 38rpx rgba(17, 17, 17, 0.09));
        backdrop-filter: blur(20rpx);
        -webkit-backdrop-filter: blur(20rpx);
    }

    &--hero {
        padding: var(--wm-space-card-padding-lg, 32rpx);
        border-radius: var(--wm-radius-card-lg, 48rpx);
        background: var(
            --wm-hero-gradient,
            linear-gradient(180deg, #ffffff 0%, #ffffff 62%, #f7f0df 100%)
        );
        border: 1rpx solid var(--wm-color-border-strong, #d8c28a);
        box-shadow: var(--wm-shadow-hero, 0 22rpx 48rpx rgba(17, 17, 17, 0.12));
    }

    &--admin.base-card--panel {
        border-radius: 37rpx;
    }

    &--staff {
        &.base-card--surface,
        &.base-card--panel {
            background: linear-gradient(
                180deg,
                rgba(255, 255, 255, 0.98) 0%,
                rgba(248, 247, 242, 0.96) 100%
            );
            border-color: rgba(216, 194, 138, 0.7);
            box-shadow: var(--wm-shadow-soft, 0 18rpx 38rpx rgba(17, 17, 17, 0.06));
        }

        &.base-card--glass {
            background: linear-gradient(
                180deg,
                rgba(255, 255, 255, 0.94) 0%,
                rgba(248, 247, 242, 0.92) 100%
            );
            border-color: rgba(216, 194, 138, 0.88);
            box-shadow: var(--wm-shadow-card, 0 22rpx 48rpx rgba(17, 17, 17, 0.09));
        }

        &.base-card--hero {
            background: radial-gradient(
                    circle at top right,
                    rgba(200, 164, 93, 0.18) 0,
                    transparent 34%
                ),
                linear-gradient(180deg, #ffffff 0%, #ffffff 62%, #f8f7f2 100%);
            border-color: rgba(216, 194, 138, 0.96);
            box-shadow: var(--wm-shadow-hero, 0 28rpx 60rpx rgba(17, 17, 17, 0.12));
        }
    }

    &--interactive {
        cursor: pointer;

        &:active {
            transform: translateY(-1rpx);
        }
    }
}
</style>
