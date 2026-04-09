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
    box-sizing: border-box;

    &--sm {
        min-height: 38rpx;
        padding: 0 12rpx;
    }

    &--md {
        min-height: 44rpx;
        padding: 0 14rpx;
    }

    &__text {
        font-size: 21rpx;
        font-weight: 600;
        line-height: 1;
    }

    &--neutral {
        background: rgba(96, 112, 134, 0.08);
        border-color: rgba(96, 112, 134, 0.16);
        color: var(--wm-color-info, #607086);
    }

    &--success {
        background: rgba(47, 125, 88, 0.08);
        border-color: rgba(47, 125, 88, 0.16);
        color: var(--wm-color-success, #2f7d58);
    }

    &--warning {
        background: rgba(201, 133, 36, 0.08);
        border-color: rgba(201, 133, 36, 0.16);
        color: var(--wm-color-warning, #c98524);
    }

    &--danger {
        background: rgba(201, 75, 73, 0.08);
        border-color: rgba(201, 75, 73, 0.16);
        color: var(--wm-color-danger, #c94b49);
    }

    &--info {
        background: rgba(232, 90, 79, 0.08);
        border-color: var(--wm-color-border-strong, #f4c7bf);
        color: var(--wm-color-primary, #e85a4f);
    }
}
</style>
