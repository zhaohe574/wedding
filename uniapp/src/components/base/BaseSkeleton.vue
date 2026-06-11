<template>
    <view class="base-skeleton">
        <view
            v-for="index in rows"
            :key="index"
            class="base-skeleton__row"
            :class="{ 'base-skeleton__row--short': index === rows }"
        ></view>
    </view>
</template>

<script setup lang="ts">
interface Props {
    rows?: number
}

withDefaults(defineProps<Props>(), {
    rows: 3
})
</script>

<script lang="ts">
export default {
    name: 'BaseSkeleton',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-skeleton {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    padding: 34rpx;
    border-radius: var(--wm-radius-card, 44rpx);
    background: var(--wm-color-bg-card, #FFFDF8);
    border: 1rpx solid var(--wm-color-border, #E3D7C9);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));

    &__row {
        height: 28rpx;
        border-radius: 999rpx;
        background: linear-gradient(90deg, rgba(227, 215, 201, 0.54) 0%, rgba(255, 253, 248, 0.96) 50%, rgba(227, 215, 201, 0.54) 100%);
        background-size: 200% 100%;
        animation: base-skeleton-shimmer 1.4s ease-in-out infinite;
    }

    &__row--short {
        width: 62%;
    }
}

@keyframes base-skeleton-shimmer {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}
</style>
