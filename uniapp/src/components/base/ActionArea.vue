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
    gap: 16rpx;
    padding: 12rpx 20rpx;
    background: rgba(255, 255, 255, 0.92);
    border-top: 1rpx solid var(--wm-color-border, #efe6e1);
    backdrop-filter: blur(18rpx);
    -webkit-backdrop-filter: blur(18rpx);

    &--safe {
        padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    }

    &--sticky {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 90;
    }
}
</style>
