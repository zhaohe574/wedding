<template>
    <view :class="rowClass" @click="handleClick">
        <view v-if="safeIcon" class="base-menu-row__icon">
            <BaseIcon :name="safeIcon" size="30" color="var(--wm-menu-row-icon, var(--wm-color-clay, #9A6B35))" />
        </view>
        <text class="base-menu-row__label">{{ label }}</text>
        <text v-if="value" class="base-menu-row__value">{{ value }}</text>
        <BaseIcon name="right" size="26" :color="dark ? 'var(--wm-text-tertiary, #8A806F)' : 'var(--wm-text-tertiary, #8A806F)'" />
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
    divided?: boolean
    disabled?: boolean
    density?: 'compact' | 'default' | 'comfortable'
}

const props = withDefaults(defineProps<Props>(), {
    value: '',
    icon: '',
    dark: false,
    divided: false,
    disabled: false,
    density: 'default'
})

const emit = defineEmits<{
    (event: 'click'): void
}>()

const safeIcon = computed(() => (typeof props.icon === 'string' ? props.icon.trim() : ''))
const resolvedDensity = computed(() =>
    ['compact', 'default', 'comfortable'].includes(props.density) ? props.density : 'default'
)
const rowClass = computed(() => [
    'base-menu-row',
    `base-menu-row--${resolvedDensity.value}`,
    {
        'base-menu-row--dark': props.dark,
        'base-menu-row--divided': props.divided,
        'base-menu-row--disabled': props.disabled,
        'base-menu-row--no-icon': !safeIcon.value
    }
])

const handleClick = () => {
    if (props.disabled) return
    emit('click')
}
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
    position: relative;
    display: flex;
    align-items: center;
    gap: 18rpx;
    min-height: 84rpx;
    padding: 0;
    box-sizing: border-box;

    &::after {
        content: '';
        position: absolute;
        left: calc(48rpx + 18rpx);
        right: 0;
        bottom: 0;
        height: 1rpx;
        background: var(--wm-menu-row-divider, var(--wm-color-border, #D8C9AD));
        opacity: 0;
    }

    &--compact {
        min-height: 72rpx;
    }

    &--comfortable {
        min-height: 96rpx;
    }

    &--divided::after {
        opacity: 1;
    }

    &--no-icon::after {
        left: 0;
    }

    &__icon {
        width: 48rpx;
        height: 48rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999rpx;
        background: var(--wm-menu-row-icon-bg, var(--wm-color-gold-soft, #F1E5C8));
    }

    &__label {
        flex: 1;
        min-width: 0;
        font-size: 26rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #191713);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__value {
        flex-shrink: 0;
        max-width: 220rpx;
        font-size: 24rpx;
        font-weight: 800;
        color: var(--wm-text-secondary, #665E52);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &--compact &__label {
        font-size: 24rpx;
    }

    &--compact &__value {
        font-size: 22rpx;
    }

    &--comfortable &__label {
        font-size: 28rpx;
    }

    &--comfortable &__value {
        font-size: 24rpx;
    }

    &--dark &__label {
        color: var(--wm-text-primary, #191713);
    }

    &--dark &__value {
        color: var(--wm-text-secondary, #665E52);
    }

    &--dark::after {
        background: var(--wm-menu-row-divider, var(--wm-color-border, #D8C9AD));
    }

    &--disabled {
        opacity: 0.52;
    }
}
</style>
