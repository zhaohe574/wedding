<template>
    <view :class="cardClass" :style="cardStyle" @click="handleClick">
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    type?: 'standard' | 'glass'
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
    click: [event: Event]
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
    transition: all 0.3s ease;

    // 标准卡片样式
    &--standard {
        background: #ffffff;
        border-radius: 14rpx;
        padding: 20rpx;
        box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.08);
    }

    // 玻璃态卡片样式
    &--glass {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(20rpx);
        -webkit-backdrop-filter: blur(20rpx);
        border: 2rpx solid rgba(255, 255, 255, 0.3);
        border-radius: 24rpx;
        padding: 20rpx;
        box-shadow: 0 16rpx 48rpx rgba(124, 58, 237, 0.12), 0 6rpx 12rpx rgba(0, 0, 0, 0.04);

        // 确保文字对比度
        color: var(--color-main, #333333);
    }

    // 可悬停效果
    &--hoverable {
        cursor: pointer;

        &:active {
            transform: translateY(-3rpx);
        }

        &.base-card--standard:active {
            box-shadow: 0 6rpx 18rpx rgba(0, 0, 0, 0.12);
        }

        &.base-card--glass:active {
            box-shadow: 0 12rpx 36rpx rgba(124, 58, 237, 0.16), 0 8rpx 16rpx rgba(0, 0, 0, 0.06);
        }
    }
}
</style>
