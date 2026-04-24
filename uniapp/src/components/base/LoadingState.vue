<template>
    <view class="loading-state-block">
        <tn-loading size="64" mode="flower" :color="color" />
        <text class="loading-state-block__text">{{ text }}</text>
    </view>
</template>

<script setup lang="ts">
interface Props {
    text?: string
    color?: string
}

withDefaults(defineProps<Props>(), {
    text: '加载中...',
    color: 'var(--wm-color-primary, #0B0B0B)'
})
</script>

<style lang="scss" scoped>
.loading-state-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16rpx;
    padding: 48rpx 24rpx;

    &__text {
        font-size: 24rpx;
        color: var(--wm-text-secondary, #5f5a50);
    }
}
</style>
