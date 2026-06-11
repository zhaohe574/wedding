<template>
    <view :class="controlClass">
        <view
            v-for="item in options"
            :key="String(item.value)"
            class="base-segmented-control__item"
            :class="{ 'base-segmented-control__item--active': item.value === modelValue }"
            @click="selectItem(item.value)"
        >
            <text class="base-segmented-control__text">{{ item.label }}</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface SegmentOption {
    label: string
    value: string | number
}

interface Props {
    modelValue: string | number
    options: SegmentOption[]
    tone?: 'default' | 'dark'
}

const props = withDefaults(defineProps<Props>(), {
    tone: 'default'
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: string | number): void
    (event: 'change', value: string | number): void
}>()

const selectItem = (value: string | number) => {
    emit('update:modelValue', value)
    emit('change', value)
}

const controlClass = computed(() => [
    'base-segmented-control',
    `base-segmented-control--${props.tone === 'dark' ? 'dark' : 'default'}`
])
</script>

<script lang="ts">
export default {
    name: 'BaseSegmentedControl',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-segmented-control {
    display: flex;
    align-items: center;
    gap: 8rpx;
    min-height: 96rpx;
    padding: 10rpx;
    border-radius: 36rpx;
    background: var(--wm-color-mist, #EDE6DD);

    &__item {
        flex: 1;
        min-width: 0;
        min-height: 76rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 30rpx;
        transition: all var(--wm-motion-base, 220ms) ease;
    }

    &__item--active {
        background: var(--wm-color-primary, #1A1A1A);
        box-shadow: 0 12rpx 26rpx rgba(74, 43, 24, 0.16);
    }

    &__text {
        font-size: 24rpx;
        font-weight: 900;
        color: var(--wm-text-secondary, #6B625A);
    }

    &__item--active &__text {
        color: var(--wm-text-inverse, #FFFDF8);
    }

    &--dark {
        background: rgba(255, 253, 248, 0.12);
    }

    &--dark &__text {
        color: rgba(255, 253, 248, 0.72);
    }

    &--dark &__item--active {
        background: var(--wm-color-gold-soft, #F6E2D6);
    }

    &--dark &__item--active &__text {
        color: var(--wm-text-primary, #1A1A1A);
    }
}
</style>
