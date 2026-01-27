<template>
  <view class="base-date-picker" @click="handleClick">
    <view class="base-date-picker__display">
      <text v-if="displayText" class="base-date-picker__text">{{ displayText }}</text>
      <text v-else class="base-date-picker__placeholder">{{ placeholder }}</text>
      <tn-icon name="calendar" size="32" color="#999999" />
    </view>
    
    <tn-date-time-picker
      v-model="show"
      :mode="mode"
      :default-value="defaultValue"
      :min-date="minDate"
      :max-date="maxDate"
      @confirm="handleConfirm"
      @cancel="handleCancel"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  modelValue?: string | number
  placeholder?: string
  mode?: 'date' | 'time' | 'datetime'
  format?: string
  defaultValue?: string | number
  minDate?: string | number
  maxDate?: string | number
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: '请选择日期',
  mode: 'date',
  format: 'YYYY-MM-DD',
  defaultValue: '',
  minDate: '',
  maxDate: ''
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
  confirm: [value: string | number]
  cancel: []
}>()

const show = ref(false)

// 计算显示文本
const displayText = computed(() => {
  if (props.modelValue) {
    // 格式化日期显示
    return formatDate(props.modelValue)
  }
  return ''
})

// 格式化日期
const formatDate = (value: string | number): string => {
  if (!value) return ''
  
  const date = new Date(value)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  
  let formatted = props.format
  formatted = formatted.replace('YYYY', String(year))
  formatted = formatted.replace('MM', month)
  formatted = formatted.replace('DD', day)
  formatted = formatted.replace('HH', hours)
  formatted = formatted.replace('mm', minutes)
  
  return formatted
}

// 处理点击事件
const handleClick = () => {
  show.value = true
}

// 处理确认事件
const handleConfirm = (value: string | number) => {
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
  name: 'BaseDatePicker',
  options: {
    virtualHost: true
  }
}
</script>

<style lang="scss" scoped>
.base-date-picker {
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
