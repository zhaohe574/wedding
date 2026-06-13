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
    neutral: { bg: '#ECE4D6', border: '#D8C9AD', color: '#665E52', icon: '#665E52' },
    success: { bg: '#E8EFE6', border: '#71806F', color: '#4D6049', icon: '#71806F' },
    warning: { bg: '#F1E5C8', border: '#D9BE82', color: '#6F521B', icon: '#9A6B35' },
    danger: { bg: '#F2DDD5', border: '#9A6B35', color: '#7A3F1F', icon: '#9A6B35' },
    info: { bg: '#E8E6F0', border: '#8178B6', color: '#4F4A82', icon: '#8178B6' },
    primary: { bg: '#191713', border: '#D9BE82', color: '#FFFDF8', icon: '#D9BE82' },
    paid: { bg: '#E8EFE6', border: '#71806F', color: '#4D6049', icon: '#71806F' },
    running: { bg: '#E8E6F0', border: '#8178B6', color: '#4F4A82', icon: '#8178B6' },
    pending: { bg: '#F1E5C8', border: '#D9BE82', color: '#6F521B', icon: '#9A6B35' },
    risk: { bg: '#F2DDD5', border: '#9A6B35', color: '#7A3F1F', icon: '#9A6B35' }
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
