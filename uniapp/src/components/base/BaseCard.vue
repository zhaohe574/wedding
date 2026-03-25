<template>
    <view :class="cardClass" :style="cardStyle" @click="handleClick">
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    type?: 'standard' | 'glass' | 'dark'
    hoverable?: boolean
    padding?: string
    borderRadius?: string
}

const props = withDefaults(defineProps<Props>(), {
    type: 'standard',
    hoverable: true,
    padding: '20rpx',
    borderRadius: ''
})

const emit = defineEmits<{
    (event: 'click', payload: Event): void
}>()

// 计算卡片类名
const cardClass = computed(() => {
    const classes = ['base-card', `base-card--${props.type}`]
    if (props.hoverable) {
        classes.push('base-card--hoverable')
    }
    return classes.join(' ')
})

// 计算卡片样式
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

// 处理点击事件
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
    transition: all var(--cinema-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1);

    &--standard {
        background: var(--cinema-surface-elevated, #fffdf8);
        border: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
        border-radius: var(--cinema-radius-md, 24rpx);
        padding: 20rpx;
        box-shadow: var(--cinema-shadow-soft, 0 18rpx 44rpx rgba(8, 10, 16, 0.08));
    }

    &--glass {
        background: var(--cinema-surface-overlay, rgba(255, 248, 236, 0.86));
        backdrop-filter: blur(24rpx);
        -webkit-backdrop-filter: blur(24rpx);
        border: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
        border-radius: var(--cinema-radius-lg, 32rpx);
        padding: 20rpx;
        box-shadow: var(--cinema-shadow-medium, 0 20rpx 52rpx rgba(8, 10, 16, 0.12));
        color: var(--cinema-text-primary, #151a23);
    }

    &--dark {
        background: var(
            --cinema-hero-gradient,
            linear-gradient(145deg, rgba(10, 13, 18, 0.98) 0%, rgba(25, 32, 45, 0.96) 52%, rgba(76, 58, 29, 0.94) 100%)
        );
        border: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
        border-radius: var(--cinema-radius-lg, 32rpx);
        padding: 24rpx;
        box-shadow: var(--cinema-shadow-strong, 0 24rpx 60rpx rgba(8, 10, 16, 0.18));
        color: var(--cinema-text-inverse, #fff8ea);
    }

    &--hoverable {
        cursor: pointer;

        &:active {
            transform: translateY(-2rpx) scale(0.995);
        }

        &.base-card--standard:active {
            box-shadow: var(--cinema-shadow-medium, 0 20rpx 52rpx rgba(8, 10, 16, 0.12));
        }

        &.base-card--glass:active {
            box-shadow: var(--cinema-shadow-strong, 0 24rpx 60rpx rgba(8, 10, 16, 0.18));
        }

        &.base-card--dark:active {
            box-shadow: var(--cinema-shadow-strong, 0 24rpx 60rpx rgba(8, 10, 16, 0.18));
        }
    }
}
</style>
