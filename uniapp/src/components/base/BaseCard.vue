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
}

const props = withDefaults(defineProps<Props>(), {
    variant: undefined,
    type: 'standard',
    scene: 'consumer',
    interactive: false,
    hoverable: true,
    padding: '',
    borderRadius: ''
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
    border-radius: var(--wm-radius-card, 45rpx);
    transition: transform var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1),
        box-shadow var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1),
        border-color var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1),
        background var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1);

    &--surface,
    &--panel {
        background: #ffffff;
        border: 1rpx solid var(--wm-color-border, #efe6e1);
        box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(214, 185, 167, 0.16));
    }

    &--surface {
        padding: var(--wm-space-card-padding, 30rpx);
    }

    &--panel {
        padding: var(--wm-space-card-padding, 30rpx) 34rpx;
    }

    &--glass {
        padding: var(--wm-space-card-padding, 30rpx);
        border-radius: var(--wm-radius-card-glass, 49rpx);
        background: var(--wm-color-bg-card, rgba(255, 255, 255, 0.88));
        border: 1rpx solid var(--wm-color-border, #efe6e1);
        box-shadow: var(--wm-shadow-card, 0 18rpx 36rpx rgba(214, 185, 167, 0.2));
        backdrop-filter: blur(24rpx);
        -webkit-backdrop-filter: blur(24rpx);
    }

    &--hero {
        padding: var(--wm-space-card-padding-lg, 34rpx);
        border-radius: var(--wm-radius-card-lg, 52rpx);
        background: var(
            --wm-hero-gradient,
            linear-gradient(180deg, #fff5f1 0%, #fcfbf9 68%, #f7f1ed 100%)
        );
        border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);
        box-shadow: var(--wm-shadow-hero, 0 24rpx 56rpx rgba(177, 108, 95, 0.18));
    }

    &--admin.base-card--panel {
        border-radius: 37rpx;
    }

    &--interactive {
        cursor: pointer;

        &:active {
            transform: translateY(-2rpx);
        }
    }
}
</style>
