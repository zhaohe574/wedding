<template>
    <view :class="inputClass">
        <text v-if="label" class="base-input__label">{{ label }}</text>
        <view class="base-input__control">
            <BaseIcon
                v-if="safeIcon"
                class="base-input__icon"
                :name="safeIcon"
                size="30"
                color="var(--wm-color-champagne, #D9BE82)"
            />
            <slot name="prefix" />
            <input
                class="base-input__native"
                :value="inputValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :type="nativeType"
                :maxlength="maxlength"
                placeholder-class="base-input__placeholder"
                @focus="handleFocus"
                @blur="handleBlur"
                @input="handleNativeInput"
                @confirm="handleConfirm"
            />
            <slot name="suffix" />
            <BaseIcon
                v-if="clearable && inputValue && !disabled"
                name="close"
                size="26"
                color="var(--wm-text-tertiary, #8A806F)"
                @click="handleClear"
            />
        </view>
        <text v-if="helper || errorText" class="base-input__helper">
            {{ errorText || helper }}
        </text>
    </view>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import BaseIcon from './BaseIcon.vue'

interface Props {
    modelValue?: string | number
    label?: string
    placeholder?: string
    icon?: string
    variant?: 'filled' | 'outlined' | 'dark'
    state?: 'default' | 'error' | 'disabled'
    inputmode?: string
    disabled?: boolean
    clearable?: boolean
    helper?: string
    errorText?: string
    type?: 'text' | 'number' | 'idcard' | 'digit' | 'tel' | 'password'
    maxlength?: number
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    label: '',
    placeholder: '请输入',
    icon: '',
    variant: 'outlined',
    state: 'default',
    inputmode: 'text',
    disabled: false,
    clearable: false,
    helper: '',
    errorText: '',
    type: 'text',
    maxlength: -1
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: string | number): void
    (event: 'focus', payload: Event): void
    (event: 'blur', payload: Event): void
    (event: 'input', value: string | number): void
    (event: 'change', value: string | number): void
    (event: 'confirm', value: string | number): void
}>()

const inputValue = ref(props.modelValue)
const isFocused = ref(false)

watch(
    () => props.modelValue,
    (newVal) => {
        inputValue.value = newVal
    }
)

const nativeType = computed(() => (props.type === 'password' ? 'password' : props.type === 'tel' ? 'number' : props.type))
const safeIcon = computed(() => (typeof props.icon === 'string' ? props.icon.trim() : ''))
const inputClass = computed(() => [
    'base-input',
    `base-input--${props.variant}`,
    `base-input--${props.errorText ? 'error' : props.disabled ? 'disabled' : props.state}`,
    {
        'base-input--focused': isFocused.value,
        'base-input--has-label': Boolean(props.label)
    }
])

const syncValue = (value: string | number) => {
    inputValue.value = value
    emit('update:modelValue', value)
    emit('input', value)
    emit('change', value)
}

const handleFocus = (event: Event) => {
    isFocused.value = true
    emit('focus', event)
}

const handleBlur = (event: Event) => {
    isFocused.value = false
    emit('blur', event)
}

const handleNativeInput = (event: any) => {
    syncValue(event?.detail?.value ?? '')
}

const handleConfirm = () => {
    emit('confirm', inputValue.value)
}

const handleClear = () => {
    syncValue('')
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
    display: flex;
    flex-direction: column;
    gap: 12rpx;

    &__label {
        font-size: 24rpx;
        font-weight: 900;
        color: var(--wm-text-secondary, #665E52);
    }

    &__control {
        display: flex;
        align-items: center;
        gap: 14rpx;
        min-height: 96rpx;
        padding: 0 28rpx;
        border: 1rpx solid var(--wm-color-border, #D8C9AD);
        border-radius: var(--wm-radius-input, 44rpx);
        background: var(--wm-color-bg-card, #FFFDF8);
        box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
        transition: all var(--wm-motion-base, 220ms) ease;
    }

    &__native {
        flex: 1;
        min-width: 0;
        height: 88rpx;
        font-size: 28rpx;
        font-weight: 800;
        line-height: 88rpx;
        color: var(--wm-text-primary, #191713);
    }

    &__helper {
        font-size: 22rpx;
        line-height: 1.45;
        color: var(--wm-text-tertiary, #8A806F);
    }

    &--filled &__control {
        background: var(--wm-color-bg-soft, #FAF6EE);
    }

    &--dark &__control {
        background: var(--wm-color-primary, #191713);
        border-color: var(--wm-color-champagne, #D9BE82);
    }

    &--dark &__native {
        color: var(--wm-text-inverse, #FFFDF8);
    }

    &--focused &__control {
        border-color: var(--wm-color-champagne, #D9BE82);
        box-shadow: 0 0 0 6rpx rgba(217, 190, 130, 0.22),
            var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    }

    &--error &__control {
        border-color: var(--wm-color-clay, #9A6B35);
        box-shadow: 0 0 0 6rpx rgba(154, 107, 53, 0.12);
    }

    &--error &__helper {
        color: var(--wm-color-clay, #9A6B35);
    }

    &--disabled {
        opacity: 0.58;
    }
}

:global(.base-input__placeholder) {
    color: var(--wm-text-tertiary, #8A806F);
    font-weight: 700;
}
</style>
