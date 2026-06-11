<template>
    <view class="base-picker-field" :class="{ 'base-picker-field--disabled': disabled }" @click="handleClick">
        <view v-if="safeIcon" class="base-picker-field__icon">
            <BaseIcon :name="safeIcon" size="32" color="var(--wm-color-gold, #D4916E)" />
        </view>
        <view class="base-picker-field__copy">
            <view v-if="label || statusText" class="base-picker-field__meta">
                <text v-if="label" class="base-picker-field__label">{{ label }}</text>
                <text v-if="statusText" class="base-picker-field__status">{{ statusText }}</text>
            </view>
            <text class="base-picker-field__value" :class="{ 'base-picker-field__value--placeholder': !modelValue }">
                {{ modelValue || placeholder }}
            </text>
            <text v-if="hint" class="base-picker-field__hint">{{ hint }}</text>
        </view>
        <BaseIcon name="down" size="30" color="var(--wm-color-champagne, #E9C7A7)" />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from './BaseIcon.vue'

interface Props {
    label?: string
    modelValue?: string | number
    placeholder?: string
    icon?: string
    hint?: string
    statusText?: string
    disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    label: '',
    modelValue: '',
    placeholder: '请选择',
    icon: '',
    hint: '',
    statusText: '',
    disabled: false
})

const emit = defineEmits<{
    (event: 'click'): void
}>()

const safeIcon = computed(() => (typeof props.icon === 'string' ? props.icon.trim() : ''))

const handleClick = () => {
    if (props.disabled) return
    emit('click')
}
</script>

<script lang="ts">
export default {
    name: 'BasePickerField',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-picker-field {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
    min-height: 112rpx;
    padding: 0 30rpx;
    border-radius: var(--wm-radius-control, 44rpx);
    border: 1rpx solid var(--wm-color-border, #E3D7C9);
    background: var(--wm-color-bg-card, #FFFDF8);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));

    &:active {
        border-color: var(--wm-color-champagne, #E9C7A7);
    }

    &__icon {
        flex-shrink: 0;
        width: 64rpx;
        height: 64rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 24rpx;
        background: var(--wm-color-gold-soft, #F6E2D6);
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }

    &__meta {
        display: flex;
        align-items: center;
        gap: 12rpx;
        min-width: 0;
    }

    &__label {
        min-width: 0;
        font-size: 22rpx;
        font-weight: 800;
        color: var(--wm-text-secondary, #6B625A);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__status {
        flex-shrink: 0;
        padding: 0 12rpx;
        min-height: 30rpx;
        display: inline-flex;
        align-items: center;
        border-radius: 999rpx;
        background: var(--wm-color-gold-soft, #F6E2D6);
        color: var(--wm-color-secondary-strong, #7D4C35);
        font-size: 18rpx;
        font-weight: 900;
    }

    &__value {
        font-size: 28rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #1A1A1A);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__value--placeholder {
        color: var(--wm-text-tertiary, #B4A89C);
    }

    &__hint {
        font-size: 20rpx;
        line-height: 1.4;
        color: var(--wm-text-tertiary, #B4A89C);
    }

    &--disabled {
        opacity: 0.56;
    }
}
</style>
