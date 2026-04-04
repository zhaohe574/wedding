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
    layout?: 'single' | 'split'
}

const props = withDefaults(defineProps<Props>(), {
    sticky: false,
    safeBottom: true,
    layout: 'single'
})

const actionClass = computed(() => [
    'wm-action-area',
    `wm-action-area--${props.layout}`,
    {
        'wm-action-area--sticky': props.sticky,
        'wm-action-area--safe': props.safeBottom
    }
])
</script>

<style lang="scss" scoped>
.wm-action-area {
    display: flex;
    align-items: center;
    gap: var(--wm-space-section-gap-sm, 22rpx);
    padding: var(--wm-space-action-top, 22rpx) var(--wm-space-action-x, 37rpx)
        var(--wm-space-action-bottom, 39rpx);
    background: linear-gradient(
        180deg,
        rgba(252, 251, 249, 0) 0%,
        rgba(252, 251, 249, 0.92) 24%,
        rgba(252, 251, 249, 0.98) 100%
    );

    &--safe {
        padding-bottom: calc(var(--wm-space-action-bottom, 39rpx) + env(safe-area-inset-bottom));
    }

    &--sticky {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 90;
        backdrop-filter: blur(12rpx);
        -webkit-backdrop-filter: blur(12rpx);
    }
}
</style>
