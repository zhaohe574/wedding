<template>
    <view :class="badgeClass">
        <view v-if="dot" class="status-badge__dot"></view>
        <BaseIcon
            v-if="safeIcon"
            class="status-badge__icon"
            :name="safeIcon"
            :size="iconSize"
            :color="currentTone.icon"
        />
        <text class="status-badge__text">
            <slot>{{ label }}</slot>
        </text>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from './BaseIcon.vue'

type Tone = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'primary' | 'paid' | 'running' | 'pending' | 'risk'

interface Props {
    label?: string
    tone?: Tone
    size?: 'xs' | 'sm' | 'md'
    strong?: boolean
    dot?: boolean
    icon?: string
}

const props = withDefaults(defineProps<Props>(), {
    label: '',
    tone: 'neutral',
    size: 'md',
    strong: false,
    dot: false,
    icon: ''
})

const toneMap: Record<Tone, { bg: string; border: string; color: string; icon: string }> = {
    neutral: { bg: '#F2ECE1', border: '#E7E0D3', color: '#5E564B', icon: '#5E564B' },
    success: { bg: '#EDF3ED', border: '#607361', color: '#3F5240', icon: '#607361' },
    warning: { bg: '#FDF5EA', border: '#D48D3B', color: '#7E4D15', icon: '#D48D3B' },
    danger: { bg: '#FBEFEF', border: '#B84A39', color: '#7E2C20', icon: '#B84A39' },
    info: { bg: '#F0EFF7', border: '#7B73A8', color: '#453E6F', icon: '#7B73A8' },
    primary: { bg: '#181614', border: '#C6A15B', color: '#FFFDF8', icon: '#C6A15B' },
    paid: { bg: '#EDF3ED', border: '#607361', color: '#3F5240', icon: '#607361' },
    running: { bg: '#F0EFF7', border: '#7B73A8', color: '#453E6F', icon: '#7B73A8' },
    pending: { bg: '#FDF5EA', border: '#D48D3B', color: '#7E4D15', icon: '#D48D3B' },
    risk: { bg: '#FBEFEF', border: '#B84A39', color: '#7E2C20', icon: '#B84A39' }
}

const resolvedTone = computed<Tone>(() => (props.tone in toneMap ? props.tone : 'neutral'))
const currentTone = computed(() => toneMap[resolvedTone.value])
const safeIcon = computed(() => (typeof props.icon === 'string' ? props.icon.trim() : ''))
const iconSize = computed(() => (props.size === 'xs' ? '20' : props.size === 'sm' ? '22' : '24'))
const badgeClass = computed(() => [
    'status-badge',
    `status-badge--${props.size}`,
    {
        'status-badge--strong': props.strong
    }
])
</script>

<style lang="scss" scoped>
.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    border: 1rpx solid v-bind('currentTone.border');
    border-radius: var(--wm-radius-pill, 999rpx);
    background: v-bind('currentTone.bg');
    color: v-bind('currentTone.color');
    box-sizing: border-box;

    &--xs {
        min-height: 32rpx;
        padding: 0 12rpx;
    }

    &--sm {
        min-height: 38rpx;
        padding: 0 14rpx;
    }

    &--md {
        min-height: 44rpx;
        padding: 0 18rpx;
    }

    &--strong {
        min-height: 52rpx;
        padding: 0 22rpx;
        box-shadow: 0 10rpx 22rpx rgba(74, 43, 24, 0.08);
    }

    &__dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: 999rpx;
        background: currentColor;
        opacity: 0.72;
    }

    &__text {
        font-size: 22rpx;
        font-weight: 900;
        line-height: 1;
        white-space: nowrap;
    }

    &--xs &__text {
        font-size: 19rpx;
    }
}
</style>
