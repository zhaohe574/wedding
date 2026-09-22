<template>
    <view class="staff-workspace-hero">
        <view class="staff-workspace-hero__main">
            <view class="staff-workspace-hero__copy">
                <text class="staff-workspace-hero__title">{{ title }}</text>
            </view>

            <view v-if="$slots.badges" class="staff-workspace-hero__badges">
                <slot name="badges" />
            </view>

            <view v-if="actionText" class="staff-workspace-hero__action" @click="emit('action')">
                <text class="staff-workspace-hero__action-plus">+</text>
                <text class="staff-workspace-hero__action-text">{{ actionText }}</text>
            </view>
        </view>

        <view v-if="$slots.default" class="staff-workspace-hero__footer">
            <slot />
        </view>
    </view>
</template>

<script setup lang="ts">
interface Props {
    title: string
    actionText?: string
}

withDefaults(defineProps<Props>(), {
    actionText: ''
})

const emit = defineEmits<{
    (event: 'action'): void
}>()
</script>

<style lang="scss" scoped>
.staff-workspace-hero {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    padding: 32rpx;
    border-radius: 28rpx;
    background: radial-gradient(circle at 92% 10%, rgba(200, 164, 93, 0.16) 0, transparent 65%),
        linear-gradient(145deg, #1D1B17 0%, #12110F 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.32);
    box-shadow: 0 16rpx 40rpx rgba(18, 17, 15, 0.14);
    overflow: hidden;

    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2rpx;
        background: linear-gradient(90deg, transparent, rgba(217, 190, 130, 0.6), transparent);
    }
}

.staff-workspace-hero__main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
}

.staff-workspace-hero__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.staff-workspace-hero__title {
    font-size: 38rpx;
    font-weight: 800;
    line-height: 1.25;
    color: #FFFFFF;
    letter-spacing: 0.5rpx;
}

.staff-workspace-hero__badges {
    display: flex;
    align-items: center;
    gap: 10rpx;
    flex-wrap: wrap;
}

.staff-workspace-hero__action {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    min-height: 60rpx;
    padding: 0 26rpx;
    border-radius: 999rpx;
    background: linear-gradient(135deg, #F3E5C8 0%, #C8A45D 100%);
    box-shadow: 0 6rpx 18rpx rgba(200, 164, 93, 0.28);
    transition: opacity 0.2s ease, transform 0.2s ease;

    &:active {
        opacity: 0.88;
        transform: scale(0.96);
    }
}

.staff-workspace-hero__action-plus {
    font-size: 26rpx;
    font-weight: 700;
    line-height: 1;
    color: #1A1713;
}

.staff-workspace-hero__action-text {
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1;
    color: #1A1713;
}

.staff-workspace-hero__footer {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    margin-top: 6rpx;
}
</style>
