<template>
    <view class="base-stepper">
        <view class="base-stepper__button" :class="{ 'base-stepper__button--disabled': modelValue <= min }" @click="decrease">
            <BaseIcon name="minus" size="24" color="var(--wm-color-champagne, #D9BE82)" />
        </view>
        <text class="base-stepper__value">{{ modelValue }}</text>
        <view class="base-stepper__button" :class="{ 'base-stepper__button--disabled': modelValue >= max }" @click="increase">
            <BaseIcon name="add" size="24" color="var(--wm-color-champagne, #D9BE82)" />
        </view>
    </view>
</template>

<script setup lang="ts">
import BaseIcon from './BaseIcon.vue'

interface Props {
    modelValue: number
    min?: number
    max?: number
    step?: number
}

const props = withDefaults(defineProps<Props>(), {
    min: 0,
    max: 99,
    step: 1
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: number): void
    (event: 'change', value: number): void
}>()

const updateValue = (value: number) => {
    const nextValue = Math.max(props.min, Math.min(props.max, value))
    emit('update:modelValue', nextValue)
    emit('change', nextValue)
}

const decrease = () => {
    updateValue(props.modelValue - props.step)
}

const increase = () => {
    updateValue(props.modelValue + props.step)
}
</script>

<script lang="ts">
export default {
    name: 'BaseStepper',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-stepper {
    display: inline-flex;
    align-items: center;
    gap: 18rpx;
    min-height: 96rpx;
    padding: 0 22rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid var(--wm-color-champagne, #D9BE82);
    background: var(--wm-color-primary, #191713);
    box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));

    &__button {
        width: 56rpx;
        height: 56rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999rpx;
        background: rgba(255, 253, 248, 0.08);
    }

    &__button--disabled {
        opacity: 0.36;
    }

    &__value {
        min-width: 48rpx;
        text-align: center;
        font-size: 28rpx;
        font-weight: 900;
        color: var(--wm-text-inverse, #FFFDF8);
    }
}
</style>
