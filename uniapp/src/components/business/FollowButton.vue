<template>
  <view 
    class="follow-button"
    :class="[
      isFollowed ? 'followed' : 'not-followed',
      sizeClass,
      { 'loading': loading }
    ]"
    @tap="handleClick"
  >
    <tn-icon 
      v-if="showIcon"
      :name="isFollowed ? 'check' : 'add'"
      :size="iconSize"
      :color="iconColor"
      class="follow-icon"
    />
    <text class="follow-text">{{ buttonText }}</text>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  /** 是否已关注 */
  isFollowed?: boolean
  /** 按钮尺寸：sm(56rpx) | md(72rpx) | lg(88rpx) */
  size?: 'sm' | 'md' | 'lg'
  /** 是否显示图标 */
  showIcon?: boolean
  /** 加载状态 */
  loading?: boolean
  /** 自定义文本 */
  followText?: string
  followedText?: string
}

const props = withDefaults(defineProps<Props>(), {
  isFollowed: false,
  size: 'sm',
  showIcon: true,
  loading: false,
  followText: '关注',
  followedText: '已关注'
})

const emit = defineEmits<{
  click: []
}>()

// 按钮文本
const buttonText = computed(() => {
  if (props.loading) return '处理中...'
  return props.isFollowed ? props.followedText : props.followText
})

// 尺寸类名
const sizeClass = computed(() => `size-${props.size}`)

// 图标尺寸
const iconSize = computed(() => {
  const sizeMap = {
    sm: '24',
    md: '28',
    lg: '32'
  }
  return sizeMap[props.size]
})

// 图标颜色
const iconColor = computed(() => {
  return props.isFollowed ? 'var(--tn-color-primary)' : '#FFFFFF'
})

// 点击处理
const handleClick = () => {
  if (props.loading) return
  emit('click')
}
</script>

<style lang="scss" scoped>
.follow-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  border-radius: 48rpx;
  font-weight: 500;
  transition: all 0.2s ease;
  user-select: none;
  
  // 未关注状态 - 主色背景
  &.not-followed {
    background: var(--tn-color-primary);
    color: #FFFFFF;
    
    &:active {
      transform: scale(0.98);
      opacity: 0.9;
    }
  }
  
  // 已关注状态 - 边框样式
  &.followed {
    background: var(--tn-color-primary-light-9, rgba(124, 58, 237, 0.08));
    color: var(--tn-color-primary);
    border: 2rpx solid var(--tn-color-primary-light-7, rgba(124, 58, 237, 0.2));
    
    &:active {
      transform: scale(0.98);
      background: var(--tn-color-primary-light-7, rgba(124, 58, 237, 0.12));
    }
  }
  
  // 加载状态
  &.loading {
    opacity: 0.6;
    pointer-events: none;
  }
  
  // 小尺寸 (56rpx)
  &.size-sm {
    height: 56rpx;
    padding: 0 24rpx;
    font-size: 24rpx;
    min-width: 120rpx;
  }
  
  // 中尺寸 (72rpx)
  &.size-md {
    height: 72rpx;
    padding: 0 32rpx;
    font-size: 26rpx;
    min-width: 140rpx;
  }
  
  // 大尺寸 (88rpx)
  &.size-lg {
    height: 88rpx;
    padding: 0 40rpx;
    font-size: 28rpx;
    min-width: 160rpx;
  }
}

.follow-icon {
  flex-shrink: 0;
}

.follow-text {
  white-space: nowrap;
}
</style>
