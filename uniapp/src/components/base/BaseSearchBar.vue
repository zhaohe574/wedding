<template>
    <view class="base-search-bar" :class="{ 'base-search-bar--focused': focused }">
        <BaseIcon name="search" size="30" color="var(--wm-text-primary, #191713)" />
        <input
            class="base-search-bar__input"
            :value="modelValue"
            :placeholder="placeholder"
            placeholder-class="base-search-bar__placeholder"
            confirm-type="search"
            @focus="handleFocus"
            @blur="handleBlur"
            @input="handleInput"
            @confirm="emit('search', modelValue)"
        />
        <BaseIcon
            v-if="modelValue"
            name="close"
            size="24"
            color="var(--wm-text-tertiary, #8A806F)"
            @click="handleClear"
        />
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import BaseIcon from './BaseIcon.vue'

interface Props {
    modelValue?: string
    placeholder?: string
}

withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: '搜索新人、手机号、订单号'
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void
    (event: 'search', value: string): void
    (event: 'clear'): void
}>()

const focused = ref(false)

const handleFocus = () => {
    focused.value = true
}

const handleBlur = () => {
    focused.value = false
}

const handleInput = (event: any) => {
    emit('update:modelValue', event?.detail?.value ?? '')
}

const handleClear = () => {
    emit('update:modelValue', '')
    emit('clear')
}
</script>

<script lang="ts">
export default {
    name: 'BaseSearchBar',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-search-bar {
    display: flex;
    align-items: center;
    gap: 18rpx;
    min-height: 96rpx;
    padding: 0 30rpx;
    border-radius: var(--wm-radius-control, 44rpx);
    border: 1rpx solid var(--wm-color-border, #D8C9AD);
    background: var(--wm-color-bg-card, #FFFDF8);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    transition: all var(--wm-motion-base, 220ms) ease;

    &--focused {
        border-color: var(--wm-color-champagne, #D9BE82);
        box-shadow: 0 0 0 6rpx rgba(217, 190, 130, 0.22),
            var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    }

    &__input {
        flex: 1;
        min-width: 0;
        height: 88rpx;
        font-size: 26rpx;
        font-weight: 800;
        color: var(--wm-text-primary, #191713);
    }
}

:global(.base-search-bar__placeholder) {
    color: var(--wm-text-secondary, #665E52);
    font-weight: 700;
}
</style>
