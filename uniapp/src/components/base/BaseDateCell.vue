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
        color: var(--wm-text-primary, #1A1A1A);
    }

    &__label {
        font-size: 18rpx;
        font-weight: 800;
        color: var(--wm-text-tertiary, #B4A89C);
    }

    &--today {
        background: var(--wm-color-bg-card, #FFFDF8);
        border-color: var(--wm-color-border, #E3D7C9);
    }

    &--busy {
        background: var(--wm-color-danger-soft, #F2DDD5);
        border-color: var(--wm-color-clay, #C97957);
    }

    &--booked {
        background: #F7E4D2;
        border-color: #D4916E;
        box-shadow: 0 10rpx 24rpx rgba(212, 145, 110, 0.14);
    }

    &--booked &__day {
        color: #7D4C35;
    }

    &--booked &__label {
        color: #A86243;
    }

    &--selected {
        background: var(--wm-color-primary, #1A1A1A);
        border-color: var(--wm-color-champagne, #E9C7A7);
        box-shadow: 0 12rpx 28rpx rgba(74, 43, 24, 0.16);
    }

    &--selected &__day,
    &--selected &__label {
        color: var(--wm-text-inverse, #FFFDF8);
    }

    &--booked.base-date-cell--selected {
        background: #D4916E;
        border-color: #7D4C35;
        box-shadow: 0 12rpx 28rpx rgba(212, 145, 110, 0.24);
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
