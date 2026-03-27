<template>
    <view :class="badgeClass">
        <text class="status-badge__text">
            <slot />
        </text>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    tone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info'
    size?: 'sm' | 'md'
}

const props = withDefaults(defineProps<Props>(), {
    tone: 'neutral',
    size: 'md'
})

const badgeClass = computed(() => [
    'status-badge',
    `status-badge--${props.tone}`,
    `status-badge--${props.size}`
])
</script>

<style lang="scss" scoped>
.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--wm-radius-pill, 999rpx);
    border: 1rpx solid transparent;

    &--sm {
        min-height: 40rpx;
        padding: 0 12rpx;
    }

    &--md {
        min-height: 48rpx;
        padding: 0 16rpx;
    }

    &__text {
        font-size: 22rpx;
        font-weight: 600;
        line-height: 1;
    }

    &--neutral {
        background: rgba(96, 112, 134, 0.1);
        border-color: rgba(96, 112, 134, 0.16);
        color: var(--wm-color-info, #607086);
    }

    &--success {
        background: rgba(47, 125, 88, 0.1);
        border-color: rgba(47, 125, 88, 0.16);
        color: var(--wm-color-success, #2f7d58);
    }

    &--warning {
        background: rgba(201, 133, 36, 0.1);
        border-color: rgba(201, 133, 36, 0.16);
        color: var(--wm-color-warning, #c98524);
    }

    &--danger {
        background: rgba(180, 74, 58, 0.1);
        border-color: rgba(180, 74, 58, 0.16);
        color: var(--wm-color-danger, #b44a3a);
    }

    &--info {
        background: var(--wm-color-primary-soft, #fff1ee);
        border-color: var(--wm-color-border-strong, #f4c7bf);
        color: var(--wm-color-primary, #e85a4f);
    }
}
</style>
