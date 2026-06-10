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
    tone?: 'default' | 'solid'
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

<style lang="scss" scoped>
.wm-action-area {
    display: flex;
    align-items: center;
    gap: var(--wm-space-section-gap-sm, 22rpx);
    padding: var(--wm-space-action-top, 20rpx) var(--wm-space-action-x, 32rpx)
        var(--wm-space-action-bottom, 34rpx);
    background: linear-gradient(
        180deg,
        rgba(251, 250, 247, 0) 0%,
        rgba(251, 250, 247, 0.96) 28%,
        rgba(255, 255, 255, 0.98) 100%
    );
    border-top: 1rpx solid rgba(232, 224, 210, 0.72);

    &--safe {
        padding-bottom: calc(var(--wm-space-action-bottom, 34rpx) + env(safe-area-inset-bottom));
    }

    &--split {
        justify-content: space-between;
    }

    &--split > :deep(*) {
        min-width: 0;
    }

    &--single > :deep(*) {
        flex: 1;
    }

    &--tone-solid {
        background: #ffffff;
    }

    &--sticky {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: var(--wm-z-action, 90);
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }
}

/* #ifdef MP-WEIXIN */
.wm-action-area--sticky {
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
/* #endif */
</style>
