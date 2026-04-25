<template>
    <view :class="badgeClass">
        <text class="status-badge__text">
            <slot />
        </text>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'

interface Props {
    tone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info'
    size?: 'sm' | 'md'
}

const props = withDefaults(defineProps<Props>(), {
    tone: 'neutral',
    size: 'md'
})

const themeStore = useThemeStore()

const badgeClass = computed(() => [
    'status-badge',
    {
        'status-badge--staff': themeStore.scene === 'staff'
    },
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
        background: rgba(89, 106, 122, 0.08);
        border-color: rgba(89, 106, 122, 0.16);
        color: var(--wm-color-info, #596a7a);
    }

    &--success {
        background: rgba(79, 111, 90, 0.08);
        border-color: rgba(79, 111, 90, 0.16);
        color: var(--wm-color-success, #4f6f5a);
    }

    &--warning {
        background: rgba(159, 122, 46, 0.08);
        border-color: rgba(159, 122, 46, 0.16);
        color: var(--wm-color-warning, #9f7a2e);
    }

    &--danger {
        background: rgba(138, 75, 69, 0.08);
        border-color: rgba(138, 75, 69, 0.16);
        color: var(--wm-color-danger, #8a4b45);
    }

    &--info {
        background: rgba(11, 11, 11, 0.08);
        border-color: var(--wm-color-border-strong, #d8c28a);
        color: var(--wm-color-primary, #0b0b0b);
    }

    &--staff {
        backdrop-filter: blur(10rpx);
        -webkit-backdrop-filter: blur(10rpx);
        box-shadow: 0 6rpx 14rpx rgba(17, 17, 17, 0.05);
    }

    &--staff.status-badge--neutral {
        background: rgba(247, 246, 241, 0.88);
        border-color: rgba(216, 194, 138, 0.9);
        color: var(--wm-text-secondary, #5f5a50);
    }

    &--staff.status-badge--info {
        background: rgba(248, 242, 228, 0.9);
        border-color: rgba(216, 194, 138, 0.96);
    }
}

/* #ifdef MP-WEIXIN */
.status-badge--staff {
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
/* #endif */
</style>
