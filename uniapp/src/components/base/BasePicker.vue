<template>
  <view class="base-picker" @click="handleClick">
    <view class="base-picker__display">
      <text v-if="displayText" class="base-picker__text">{{ displayText }}</text>
      <text v-else class="base-picker__placeholder">{{ placeholder }}</text>
      <tn-icon name="arrow-down" size="32" color="#999999" />
    </view>
    
    <tn-picker
      v-model="show"
      :columns="columns"
      :default-value="defaultValue"
      @confirm="handleConfirm"
      @cancel="handleCancel"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  modelValue?: any
  placeholder?: string
  columns?: any[]
  defaultValue?: any[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: '请选择',
  columns: () => [],
  defaultValue: () => []
})

const emit = defineEmits<{
  'update:modelValue': [value: any]
  confirm: [value: any]
  cancel: []
}>()

const show = ref(false)

// 计算显示文本
const displayText = computed(() => {
  if (props.modelValue) {
    // 如果是对象，尝试获取label或text属性
    if (typeof props.modelValue === 'object') {
      return props.modelValue.label || props.modelValue.text || props.modelValue.name || ''
    }
    return String(props.modelValue)
  }
  return ''
})

// 处理点击事件
const handleClick = () => {
  show.value = true
}

// 处理确认事件
const handleConfirm = (value: any) => {
  emit('update:modelValue', value)
  emit('confirm', value)
  show.value = false
}

// 处理取消事件
const handleCancel = () => {
  emit('cancel')
  show.value = false
}
</script>

<script lang="ts">
export default {
  name: 'BasePicker',
  options: {
    virtualHost: true
  }
}
</script>

<style lang="scss" scoped>
.base-picker {
  &__display {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 88rpx;
    padding: 0 24rpx;
    background: #F8F8F8;
    border-radius: 12rpx;
    border: 2rpx solid transparent;
    transition: all 0.2s ease;
    cursor: pointer;
    
    &:active {
      background: #FFFFFF;
      border-color: var(--color-primary, #7C3AED);
    }
  }
  
  &__text {
    font-size: 28rpx;
    color: var(--color-main, #333333);
  }
  
  &__placeholder {
    font-size: 28rpx;
    color: var(--color-muted, #999999);
  }
}
</style>
