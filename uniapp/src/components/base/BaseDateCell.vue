<template>
    <view class="base-date-cell" :class="cellClass" @click="emit('click')">
        <text class="base-date-cell__day">{{ day }}</text>
        <text v-if="label" class="base-date-cell__label">{{ label }}</text>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    day: string | number
    label?: string
    state?: 'default' | 'selected' | 'booked' | 'busy' | 'disabled' | 'today'
    selected?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    label: '',
    state: 'default',
    selected: false
})

const emit = defineEmits<{
    (event: 'click'): void
}>()

const cellClass = computed(() => [
    `base-date-cell--${props.state}`,
    {
        'base-date-cell--selected': props.selected || props.state === 'selected'
    }
])
</script>

<script lang="ts">
export default {
    name: 'BaseDateCell',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-date-cell {
    width: 100%;
    max-width: 88rpx;
    height: 88rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4rpx;
    border-radius: 32rpx;
    border: 1rpx solid transparent;

    &__day {
        font-size: 26rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #191713);
    }

    &__label {
        font-size: 18rpx;
        font-weight: 800;
        color: var(--wm-text-tertiary, #8A806F);
    }

    &--today {
        background: var(--wm-color-bg-card, #FFFDF8);
        border-color: var(--wm-color-border, #D8C9AD);
    }

    &--busy {
        background: var(--wm-color-danger-soft, #F2DDD5);
        border-color: var(--wm-color-clay, #9A6B35);
    }

    &--booked {
        background: var(--wm-color-gold-soft, #F1E5C8);
        border-color: var(--wm-color-champagne, #D9BE82);
        box-shadow: 0 10rpx 24rpx rgba(184, 149, 74, 0.14);
    }

    &--booked &__day {
        color: #6F521B;
    }

    &--booked &__label {
        color: var(--wm-color-clay, #9A6B35);
    }

    &--selected {
        background: var(--wm-color-primary, #191713);
        border-color: var(--wm-color-champagne, #D9BE82);
        box-shadow: 0 12rpx 28rpx rgba(74, 43, 24, 0.16);
    }

    &--selected &__day,
    &--selected &__label {
        color: var(--wm-text-inverse, #FFFDF8);
    }

    &--booked.base-date-cell--selected {
        background: var(--wm-color-gold, #B8954A);
        border-color: var(--wm-color-clay, #9A6B35);
        box-shadow: 0 12rpx 28rpx rgba(184, 149, 74, 0.24);
    }

    &--booked.base-date-cell--selected &__day,
    &--booked.base-date-cell--selected &__label {
        color: #FFFDF8;
    }

    &--disabled {
        opacity: 0.36;
    }
}
</style>
