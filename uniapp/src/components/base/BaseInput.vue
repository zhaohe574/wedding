<template>
    <view class="base-input" :class="{ 'base-input--focused': isFocused }">
        <tn-input
            v-model="inputValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :type="type"
            :maxlength="maxlength"
            :height="88"
            :border-radius="16"
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
    (event: 'update:modelValue', value: string | number): void
    (event: 'focus', payload: Event): void
    (event: 'blur', payload: Event): void
    (event: 'input', value: string | number): void
    (event: 'change', value: string | number): void
}>()

const inputValue = ref(props.modelValue)
const isFocused = ref(false)

// 监听外部值变化
watch(
    () => props.modelValue,
    (newVal) => {
        inputValue.value = newVal
    }
)

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
    transition: all var(--cinema-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1);

    :deep(.tn-input) {
        height: 88rpx;
        padding: 0 24rpx;
        background: var(--cinema-surface-muted, #f1ebdf);
        border: 1rpx solid var(--cinema-border, rgba(198, 168, 106, 0.24));
        border-radius: var(--cinema-radius-sm, 18rpx);
        font-size: 28rpx;
        color: var(--cinema-text-primary, #151a23);
        box-shadow: inset 0 1rpx 0 rgba(255, 255, 255, 0.8);
        transition: all var(--cinema-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1);

        &::placeholder {
            color: var(--cinema-text-secondary, #5d6472);
        }
    }

    &--focused {
        :deep(.tn-input) {
            background: var(--cinema-surface-elevated, #fffdf8);
            border-color: var(--cinema-primary-border, rgba(124, 58, 237, 0.24));
            box-shadow:
                0 0 0 6rpx var(--cinema-primary-ring, rgba(124, 58, 237, 0.12)),
                var(--cinema-shadow-soft, 0 18rpx 44rpx rgba(8, 10, 16, 0.08));
        }
    }
}
</style>
