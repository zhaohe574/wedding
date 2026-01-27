<template>
  <tn-button
    :class="buttonClass"
    :size="computedSize"
    :shape="'round'"
    :disabled="disabled"
    :loading="loading"
    :bg-color="bgColor"
    :text-color="textColor"
    :border-color="borderColor"
    @click="handleClick"
  >
    <slot />
  </tn-button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'

interface Props {
  type?: 'primary' | 'secondary' | 'cta'
  size?: 'lg' | 'md' | 'sm'
  disabled?: boolean
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'primary',
  size: 'md',
  disabled: false,
  loading: false
})

const emit = defineEmits<{
  click: [event: Event]
}>()

const themeStore = useThemeStore()

// 计算按钮类名
const buttonClass = computed(() => {
  const classes = ['base-button', `base-button--${props.type}`, `base-button--${props.size}`]
  return classes.join(' ')
})

// 计算图鸟UI的尺寸映射
const computedSize = computed(() => {
  const sizeMap = {
    lg: 'lg',
    md: 'md',
    sm: 'sm'
  }
  return sizeMap[props.size]
})

// 计算背景色
const bgColor = computed(() => {
  if (props.type === 'primary') {
    return themeStore.primaryColor
  } else if (props.type === 'cta') {
    return themeStore.ctaColor
  } else if (props.type === 'secondary') {
    return 'transparent'
  }
  return themeStore.primaryColor
})

// 计算文字颜色
const textColor = computed(() => {
  if (props.type === 'secondary') {
    return themeStore.primaryColor
  }
  return themeStore.btnColor
})

// 计算边框颜色
const borderColor = computed(() => {
  if (props.type === 'secondary') {
    return themeStore.primaryColor
  }
  return 'transparent'
})

// 处理点击事件
const handleClick = (event: Event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>

<script lang="ts">
export default {
  name: 'BaseButton',
  options: {
    virtualHost: true
  }
}
</script>

<style lang="scss" scoped>
.base-button {
  transition: all 0.2s ease;
  border-radius: 48rpx;
  
  &:active {
    transform: translateY(2rpx);
  }
  
  // 主要按钮样式增强
  &--primary {
    :deep(.tn-button) {
      box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);
      font-weight: 600;
      letter-spacing: 1rpx;
      
      &:active {
        box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
      }
    }
  }
  
  // 次要按钮样式增强
  &--secondary {
    :deep(.tn-button) {
      font-weight: 500;
      border-width: 2rpx;
      
      &:active {
        opacity: 0.8;
      }
    }
  }
  
  // CTA按钮样式增强
  &--cta {
    :deep(.tn-button) {
      box-shadow: 0 8rpx 24rpx rgba(249, 115, 22, 0.3);
      font-weight: 600;
      letter-spacing: 1rpx;
      
      &:active {
        box-shadow: 0 4rpx 12rpx rgba(249, 115, 22, 0.3);
      }
    }
  }
  
  // 尺寸样式
  &--lg {
    :deep(.tn-button) {
      height: 88rpx;
      font-size: 32rpx;
    }
  }
  
  &--md {
    :deep(.tn-button) {
      height: 72rpx;
      font-size: 28rpx;
    }
  }
  
  &--sm {
    :deep(.tn-button) {
      height: 56rpx;
      font-size: 26rpx;
      font-weight: 500;
      letter-spacing: 0.5rpx;
    }
  }
}
</style>
