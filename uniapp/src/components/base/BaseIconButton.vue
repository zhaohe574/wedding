<template>
    <view :class="buttonClass" :style="buttonStyle" @click="emit('click')">
        <BaseIcon v-if="resolvedIcon" :name="resolvedIcon" :size="resolvedIconSize" :color="iconColor" />
        <text v-if="label" class="base-icon-button__label">{{ label }}</text>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from './BaseIcon.vue'

interface Props {
    icon?: string
    label?: string
    variant?: 'dark' | 'light' | 'ghost'
    size?: 'md' | 'sm'
    width?: string
    height?: string
    iconSize?: string
}

const props = withDefaults(defineProps<Props>(), {
    icon: '',
    label: '',
    variant: 'dark',
    size: 'md',
    width: '',
    height: '',
    iconSize: ''
})

const emit = defineEmits<{
    (event: 'click'): void
}>()

const normalizeString = (value?: string) => (typeof value === 'string' ? value.trim() : '')
const resolvedIcon = computed(() => normalizeString(props.icon) || 'more')
const resolvedVariant = computed(() => (['dark', 'light', 'ghost'].includes(props.variant || '') ? props.variant : 'dark'))
const resolvedSize = computed(() => (props.size === 'sm' ? 'sm' : 'md'))
const buttonClass = computed(() => ['base-icon-button', `base-icon-button--${resolvedVariant.value}`, `base-icon-button--${resolvedSize.value}`])
const resolvedIconSize = computed(() => normalizeString(props.iconSize) || (resolvedSize.value === 'sm' ? '30' : '38'))
const iconColor = computed(() =>
    resolvedVariant.value === 'dark' ? 'var(--wm-color-champagne, #E9C7A7)' : 'var(--wm-text-primary, #1A1A1A)'
)
const buttonStyle = computed(() => ({
    ...(props.width ? { width: props.width } : {}),
    ...(props.height ? { height: props.height } : {})
}))
</script>

<script lang="ts">
export default {
    name: 'BaseIconButton',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-icon-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-border, #E3D7C9);
    box-sizing: border-box;

    &--md {
        width: 104rpx;
        height: 104rpx;
    }

    &--sm {
        width: 80rpx;
        height: 80rpx;
    }

    &--dark {
        background: var(--wm-color-primary, #1A1A1A);
        border-color: var(--wm-color-champagne, #E9C7A7);
        box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
    }

    &--light {
        background: var(--wm-color-bg-card, #FFFDF8);
        box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    }

    &--ghost {
        background: transparent;
    }

    &__label {
        font-size: 22rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #1A1A1A);
    }
}
</style>
