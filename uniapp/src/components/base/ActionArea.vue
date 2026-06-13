<template>
    <view :class="actionClass">
        <slot />
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    sticky?: boolean
    safeBottom?: boolean
    layout?: 'single' | 'split' | 'stack'
    tone?: 'default' | 'solid' | 'dark' | 'transparent'
}

const props = withDefaults(defineProps<Props>(), {
    sticky: false,
    safeBottom: true,
    layout: 'single',
    tone: 'default'
})

const actionClass = computed(() => [
    'wm-action-area',
    `wm-action-area--${props.layout}`,
    `wm-action-area--tone-${props.tone}`,
    {
        'wm-action-area--sticky': props.sticky,
        'wm-action-area--safe': props.safeBottom
    }
])
</script>

<script lang="ts">
export default {
    name: 'ActionArea',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.wm-action-area {
    display: flex;
    align-items: center;
    gap: 20rpx;
    padding: var(--wm-space-action-top, 24rpx) var(--wm-space-action-x, 24rpx)
        var(--wm-space-action-bottom, 34rpx);
    background: linear-gradient(180deg, rgba(245, 241, 232, 0) 0%, rgba(245, 241, 232, 0.96) 28%, rgba(255, 253, 248, 0.98) 100%);
    border-top: 1rpx solid rgba(216, 201, 173, 0.86);
    box-shadow: 0 -14rpx 34rpx rgba(74, 43, 24, 0.08);

    &--safe {
        padding-bottom: calc(var(--wm-space-action-bottom, 34rpx) + env(safe-area-inset-bottom));
    }

    &--split {
        justify-content: space-between;
    }

    &--stack {
        flex-direction: column;
        align-items: stretch;
    }

    &--tone-solid {
        background: var(--wm-color-bg-card, #FFFDF8);
    }

    &--tone-dark {
        background: var(--wm-color-primary, #191713);
        border-top-color: var(--wm-color-champagne, #D9BE82);
    }

    &--tone-transparent {
        background: transparent;
        border-top-color: transparent;
        box-shadow: none;
    }

    &--sticky {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: var(--wm-z-action, 90);
    }
}
</style>
