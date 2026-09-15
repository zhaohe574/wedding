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
    neutral: { bg: '#F5F1EB', border: '#E8DFD1', color: '#686259', icon: '#686259' },
    success: { bg: '#EEF4F0', border: 'rgba(93, 122, 104, 0.35)', color: '#42594B', icon: '#5D7A68' },
    warning: { bg: '#FAF4EB', border: 'rgba(197, 164, 109, 0.35)', color: '#8A6932', icon: '#C5A46D' },
    danger: { bg: '#FAECE9', border: 'rgba(180, 83, 71, 0.35)', color: '#873B32', icon: '#B45347' },
    info: { bg: '#EFF3F8', border: 'rgba(109, 126, 153, 0.35)', color: '#4A586E', icon: '#6D7E99' },
    primary: { bg: '#1A1816', border: 'rgba(197, 164, 109, 0.45)', color: '#FCFAF7', icon: '#C5A46D' },
    paid: { bg: '#EEF4F0', border: 'rgba(93, 122, 104, 0.35)', color: '#42594B', icon: '#5D7A68' },
    running: { bg: '#EFF3F8', border: 'rgba(109, 126, 153, 0.35)', color: '#4A586E', icon: '#6D7E99' },
    pending: { bg: '#FAF4EB', border: 'rgba(197, 164, 109, 0.35)', color: '#8A6932', icon: '#C5A46D' },
    risk: { bg: '#FAECE9', border: 'rgba(180, 83, 71, 0.35)', color: '#873B32', icon: '#B45347' }
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
        letter-spacing: 0.5rpx;
        white-space: nowrap;
    }

    &--xs &__text {
        font-size: 19rpx;
    }

    &--xs &__dot {
        width: 9rpx;
        height: 9rpx;
    }
}
</style>
