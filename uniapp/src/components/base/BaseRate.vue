<template>
    <view class="base-rate" :class="{ 'base-rate--readonly': readonly }">
        <view
            v-for="index in count"
            :key="index"
            class="base-rate__item"
            :style="{ marginRight: gap }"
            @tap="handleSelect(index)"
        >
            <BaseIcon
                :name="index <= currentValue ? 'star-fill' : 'star'"
                :size="size"
                :color="index <= currentValue ? activeColor : inactiveColor"
            />
        </view>
        <text v-if="showText" class="base-rate__text">{{ currentValue }} 分</text>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from './BaseIcon.vue'

interface Props {
    modelValue?: number
    count?: number
    minCount?: number
    size?: string | number
    gap?: string
    activeColor?: string
    inactiveColor?: string
    readonly?: boolean
    showText?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: 0,
    count: 5,
    minCount: 0,
    size: '40rpx',
    gap: '10rpx',
    activeColor: 'var(--wm-color-champagne, #D9BE82)',
    inactiveColor: 'var(--wm-color-border, #E7E0D3)',
    readonly: false,
    showText: false
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: number): void
    (event: 'change', value: number): void
}>()

const currentValue = computed(() => {
    const value = Number(props.modelValue) || 0
    return Math.min(Math.max(value, props.minCount || 0), props.count)
})

const handleSelect = (index: number) => {
    if (props.readonly) return
    const next = Math.max(index, props.minCount || 0)
    if (next === currentValue.value) return
    emit('update:modelValue', next)
    emit('change', next)
}
</script>

<style lang="scss" scoped>
.base-rate {
    display: inline-flex;
    align-items: center;

    &__item {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4rpx;
    }

    &__text {
        margin-left: 12rpx;
        font-size: var(--wm-font-meta, 24rpx);
        color: var(--wm-text-secondary, #5E564B);
    }

    &--readonly &__item {
        padding: 0;
    }
}
</style>
