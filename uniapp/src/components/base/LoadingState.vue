<template>
    <view class="loading-state-block">
        <view class="loading-state-block__mark">
            <tn-loading size="58" mode="flower" :color="color" />
        </view>
        <text class="loading-state-block__text">{{ text }}</text>
        <view class="loading-state-block__bars">
            <view class="loading-state-block__bar loading-state-block__bar--wide"></view>
            <view class="loading-state-block__bar"></view>
        </view>
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
    gap: 18rpx;
    min-height: 300rpx;
    padding: 64rpx 28rpx;
    box-sizing: border-box;

    &__mark {
        width: 96rpx;
        height: 96rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999rpx;
        background: rgba(248, 241, 225, 0.72);
        border: 1rpx solid rgba(200, 164, 93, 0.18);
    }

    &__text {
        font-size: 24rpx;
        font-weight: 600;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__bars {
        width: 280rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12rpx;
    }

    &__bar {
        width: 180rpx;
        height: 12rpx;
        border-radius: 999rpx;
        background: linear-gradient(90deg, rgba(200, 164, 93, 0.08) 0%, rgba(200, 164, 93, 0.2) 50%, rgba(200, 164, 93, 0.08) 100%);
    }

    &__bar--wide {
        width: 260rpx;
    }
}
</style>
