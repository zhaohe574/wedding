<template>
    <view :class="chipClass" @click="emit('click')">
        <BaseIcon
            v-if="safeIcon"
            class="filter-chip__icon"
            :name="safeIcon"
            size="26"
            :color="selected ? 'var(--wm-color-champagne, #E9C7A7)' : 'var(--wm-color-gold, #D4916E)'"
        />
        <text class="filter-chip__text">
            <slot>{{ label }}</slot>
        </text>
        <BaseIcon
            v-if="dropdown"
            name="down"
            size="24"
            :color="selected ? 'var(--wm-color-champagne, #E9C7A7)' : 'var(--wm-text-tertiary, #B4A89C)'"
        />
        <BaseIcon
            v-if="closable"
            name="close"
            size="22"
            class="filter-chip__close"
            :color="selected ? 'var(--wm-color-champagne, #E9C7A7)' : 'var(--wm-text-tertiary, #B4A89C)'"
            @click.stop="emit('close')"
        />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from './BaseIcon.vue'

interface Props {
    label?: string
    selected?: boolean
    closable?: boolean
    dropdown?: boolean
    icon?: string
    scene?: 'consumer' | 'staff' | 'admin'
}

const props = withDefaults(defineProps<Props>(), {
    label: '',
    selected: false,
    closable: false,
    dropdown: false,
    icon: '',
    scene: 'consumer'
})

const emit = defineEmits<{
    (event: 'click'): void
    (event: 'close'): void
}>()

const safeIcon = computed(() => (typeof props.icon === 'string' ? props.icon.trim() : ''))
const resolvedScene = computed(() =>
    ['consumer', 'staff', 'admin'].includes(props.scene || '') ? props.scene : 'consumer'
)
const chipClass = computed(() => [
    'filter-chip',
    `filter-chip--${resolvedScene.value}`,
    { 'filter-chip--selected': props.selected }
])
</script>

<style lang="scss" scoped>
.filter-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    min-height: 80rpx;
    padding: 0 28rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid var(--wm-color-border, #E3D7C9);
    background: rgba(255, 253, 248, 0.92);
    box-shadow: 0 10rpx 24rpx rgba(74, 43, 24, 0.06);
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(1rpx) scale(0.99);
    }

    &--selected {
        background: var(--wm-color-primary, #1A1A1A);
        border-color: var(--wm-color-champagne, #E9C7A7);
        box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
    }

    &__text {
        max-width: 240rpx;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 24rpx;
        font-weight: 900;
        color: var(--wm-text-secondary, #6B625A);
    }

    &--selected &__text {
        color: var(--wm-text-inverse, #FFFDF8);
    }
}
</style>
