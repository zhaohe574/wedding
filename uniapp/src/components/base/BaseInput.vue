<template>
  <view class="base-input" :class="{ 'base-input--focused': isFocused }">
    <tn-input
      v-model="inputValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :type="type"
      :maxlength="maxlength"
      :height="88"
      :border-radius="12"
      @focus="handleFocus"
      @blur="handleBlur"
      @input="handleInput"
      @change="handleChange"
    >
      <template v-if="$slots.prefix" #prefix>
        <slot name="prefix" />
      </template>
      <template v-if="$slots.suffix" #suffix>
        <slot name="suffix" />
      </template>
    </tn-input>
  </view>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  modelValue?: string | number
  placeholder?: string
  disabled?: boolean
  type?: 'text' | 'number' | 'idcard' | 'digit' | 'tel' | 'password'
  maxlength?: number
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: '请输入',
  disabled: false,
  type: 'text',
  maxlength: -1
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
  focus: [event: Event]
  blur: [event: Event]
  input: [value: string | number]
  change: [value: string | number]
}>()

const inputValue = ref(props.modelValue)
const isFocused = ref(false)

// 监听外部值变化
watch(() => props.modelValue, (newVal) => {
  inputValue.value = newVal
})

// 处理焦点事件
const handleFocus = (event: Event) => {
  isFocused.value = true
  emit('focus', event)
}

// 处理失焦事件
const handleBlur = (event: Event) => {
  isFocused.value = false
  emit('blur', event)
}

// 处理输入事件
const handleInput = (value: string | number) => {
  emit('update:modelValue', value)
  emit('input', value)
}

// 处理变化事件
const handleChange = (value: string | number) => {
  emit('change', value)
}
</script>

<script lang="ts">
export default {
  name: 'BaseInput',
  options: {
    virtualHost: true
  }
}
</script>

<style lang="scss" scoped>
.base-input {
  transition: all 0.2s ease;
  
  :deep(.tn-input) {
    height: 88rpx;
    padding: 0 24rpx;
    background: #F9FAFB;
    border: 2rpx solid #E5E7EB;
    border-radius: 16rpx;
    font-size: 28rpx;
    color: var(--color-main, #333333);
    transition: all 0.2s ease;
    
    &::placeholder {
      color: #9CA3AF;
    }
  }
  
  &--focused {
    :deep(.tn-input) {
      background: #FFFFFF;
      border-color: var(--color-primary, #7C3AED);
      box-shadow: 0 0 0 6rpx rgba(124, 58, 237, 0.1);
    }
  }
}
</style>
