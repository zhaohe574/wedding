<template>
    <view :class="chipClass" @click="emit('click')">
        <BaseIcon
            v-if="safeIcon"
            class="filter-chip__icon"
            :name="safeIcon"
            size="26"
            :color="selected ? 'var(--wm-color-champagne, #D9BE82)' : 'var(--wm-color-gold, #B8954A)'"
        />
        <text class="filter-chip__text">
            <slot>{{ label }}</slot>
        </text>
        <view v-if="hasBadge" class="filter-chip__badge">
            <text class="filter-chip__badge-text">{{ badgeText }}</text>
        </view>
        <BaseIcon
            v-if="dropdown"
            name="down"
            size="24"
            :color="selected ? 'var(--wm-color-champagne, #D9BE82)' : 'var(--wm-text-tertiary, #8A806F)'"
        />
        <BaseIcon
            v-if="closable"
            name="close"
            size="22"
            class="filter-chip__close"
            :color="selected ? 'var(--wm-color-champagne, #D9BE82)' : 'var(--wm-text-tertiary, #8A806F)'"
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
    badge?: string | number
    scene?: 'consumer' | 'staff' | 'admin'
}

const props = withDefaults(defineProps<Props>(), {
    label: '',
    selected: false,
    closable: false,
    dropdown: false,
    icon: '',
    badge: '',
    scene: 'consumer'
})

const emit = defineEmits<{
    (event: 'click'): void
    (event: 'close'): void
}>()

const safeIcon = computed(() => (typeof props.icon === 'string' ? props.icon.trim() : ''))
const badgeText = computed(() => String(props.badge ?? '').trim())
const hasBadge = computed(() => Boolean(badgeText.value) && badgeText.value !== '0')
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
    border: 1rpx solid var(--wm-color-border, #D8C9AD);
    background: rgba(255, 253, 248, 0.92);
    box-shadow: 0 10rpx 24rpx rgba(74, 43, 24, 0.06);
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(1rpx) scale(0.99);
    }

    &--selected {
        background: var(--wm-color-primary, #191713);
        border-color: var(--wm-color-champagne, #D9BE82);
        box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
    }

    &__text {
        max-width: 240rpx;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 24rpx;
        font-weight: 900;
        color: var(--wm-text-secondary, #665E52);
    }

    &--selected &__text {
        color: var(--wm-text-inverse, #FFFDF8);
    }

    &__badge {
        min-width: 36rpx;
        height: 36rpx;
        padding: 0 10rpx;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--wm-radius-pill, 999rpx);
        background: rgba(25, 23, 19, 0.08);
        color: currentColor;
        box-sizing: border-box;
    }

    &--selected &__badge {
        background: rgba(255, 253, 248, 0.18);
    }

    &__badge-text {
        font-size: 20rpx;
        font-weight: 900;
        line-height: 1;
        color: inherit;
    }
}
</style>
