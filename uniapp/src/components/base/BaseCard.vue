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
  padding: '24rpx',
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
    background: #FFFFFF;
    border-radius: 16rpx;
    padding: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
  }
  
  // 玻璃态卡片样式
  &--glass {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20rpx;
    padding: 24rpx;
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.1);
    
    // 确保文字对比度
    color: var(--color-main, #333333);
  }
  
  // 可悬停效果
  &--hoverable {
    cursor: pointer;
    
    &:active {
      transform: translateY(-4rpx);
    }
    
    &.base-card--standard:active {
      box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
    }
    
    &.base-card--glass:active {
      box-shadow: 0 12rpx 40rpx rgba(0, 0, 0, 0.15);
    }
  }
}
</style>
