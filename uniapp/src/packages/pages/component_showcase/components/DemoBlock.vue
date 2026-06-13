<template>
    <view class="demo-block">
        <view class="demo-block__head">
            <text class="demo-block__name">{{ title }}</text>
            <text class="demo-block__tag">{{ tag }}</text>
        </view>

        <view class="demo-block__body">
            <slot />
        </view>
    </view>
</template>

<script setup lang="ts">
interface Props {
    title: string
    tag?: string
}

withDefaults(defineProps<Props>(), {
    tag: 'Pencil'
})
</script>

<script lang="ts">
export default {
    name: 'DemoBlock',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.demo-block {
    position: relative;
    overflow: hidden;
    border-radius: var(--wm-radius-card, 44rpx);
    background: rgba(255, 253, 248, 0.72);
    border: 1rpx solid rgba(216, 201, 173, 0.86);
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
}

.demo-block__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    padding: 24rpx 28rpx 0;
}

.demo-block__name {
    min-width: 0;
    font-size: 24rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.demo-block__tag {
    flex-shrink: 0;
    min-height: 34rpx;
    padding: 0 14rpx;
    display: inline-flex;
    align-items: center;
    border-radius: 999rpx;
    background: var(--wm-color-gold-soft, #F1E5C8);
    color: var(--wm-color-clay, #9A6B35);
    font-size: 18rpx;
    font-weight: 900;
}

.demo-block__body {
    padding: 24rpx 28rpx 28rpx;
}
</style>
