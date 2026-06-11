<template>
    <view class="base-menu-row" :class="{ 'base-menu-row--dark': dark }" @click="emit('click')">
        <view v-if="safeIcon" class="base-menu-row__icon">
            <BaseIcon :name="safeIcon" size="30" color="var(--wm-color-champagne, #E9C7A7)" />
        </view>
        <text class="base-menu-row__label">{{ label }}</text>
        <text v-if="value" class="base-menu-row__value">{{ value }}</text>
        <BaseIcon name="right" size="26" :color="dark ? '#B8AA93' : 'var(--wm-text-tertiary, #B4A89C)'" />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from './BaseIcon.vue'

interface Props {
    label: string
    value?: string
    icon?: string
    dark?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    value: '',
    icon: '',
    dark: false
})

const emit = defineEmits<{
    (event: 'click'): void
}>()

const safeIcon = computed(() => (typeof props.icon === 'string' ? props.icon.trim() : ''))
</script>

<script lang="ts">
export default {
    name: 'BaseMenuRow',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-menu-row {
    display: flex;
    align-items: center;
    gap: 18rpx;
    min-height: 84rpx;

    &__icon {
        width: 48rpx;
        height: 48rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999rpx;
        background: rgba(233, 199, 167, 0.12);
    }

    &__label {
        flex: 1;
        min-width: 0;
        font-size: 26rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #1A1A1A);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__value {
        flex-shrink: 0;
        max-width: 220rpx;
        font-size: 24rpx;
        font-weight: 800;
        color: var(--wm-text-secondary, #6B625A);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &--dark &__label {
        color: var(--wm-text-inverse, #FFFDF8);
    }

    &--dark &__value {
        color: rgba(255, 253, 248, 0.68);
    }
}
</style>
