<template>
    <view
        class="base-input"
        :class="[
            `base-input--${variant}`,
            `base-input--${state}`,
            { 'base-input--focused': isFocused }
        ]"
    >
        <tn-input
            v-model="inputValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :type="type"
            :maxlength="maxlength"
            :height="88"
            :border-radius="18"
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
    variant?: 'filled' | 'outlined'
    state?: 'default' | 'error' | 'disabled'
    inputmode?: string
    disabled?: boolean
    type?: 'text' | 'number' | 'idcard' | 'digit' | 'tel' | 'password'
    maxlength?: number
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: '请输入',
    variant: 'filled',
    state: 'default',
    inputmode: 'text',
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

watch(
    () => props.modelValue,
    (newVal) => {
        inputValue.value = newVal
    }
)

const handleFocus = (event: Event) => {
    isFocused.value = true
    emit('focus', event)
}

const handleBlur = (event: Event) => {
    isFocused.value = false
    emit('blur', event)
}

const handleInput = (value: string | number) => {
    emit('update:modelValue', value)
    emit('input', value)
}

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
    transition: all var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1);

    :deep(.tn-input) {
        min-height: 88rpx;
        padding: 0 24rpx;
        border-radius: var(--wm-radius-input, 18rpx);
        font-size: 28rpx;
        color: var(--wm-text-primary, #1e2432);
        border: 1rpx solid var(--wm-color-border, #efe6e1);
        transition: all var(--wm-motion-base, 220ms) cubic-bezier(0.4, 0, 0.2, 1);

        &::placeholder {
            color: var(--wm-text-tertiary, #b4aca8);
        }
    }

    &--filled {
        :deep(.tn-input) {
            background: var(--wm-color-bg-soft, #fff7f4);
        }
    }

    &--outlined {
        :deep(.tn-input) {
            background: #ffffff;
        }
    }

    &--focused {
        :deep(.tn-input) {
            background: #ffffff;
            border-color: var(--wm-color-border-strong, #f4c7bf);
            box-shadow: 0 0 0 6rpx rgba(232, 90, 79, 0.12);
        }
    }

    &--error {
        :deep(.tn-input) {
            border-color: rgba(180, 74, 58, 0.3);
            box-shadow: 0 0 0 4rpx rgba(180, 74, 58, 0.08);
        }
    }

    &--disabled {
        opacity: 0.64;
    }
}
</style>
