<template>
    <view class="bottom-action-bar">
        <view class="bottom-action-bar__inner">
            <!-- 左侧辅助操作组 -->
            <view class="bottom-action-tools">
                <!-- 微信好友分享 (原生 open-type) -->
                <button
                    class="action-tool-item action-tool-item--share"
                    open-type="share"
                    hover-class="none"
                    aria-label="分享主创给好友"
                    @click="emit('share-click')"
                >
                    <BaseIcon name="share" size="38" color="#181614" />
                    <text class="action-tool-label">分享</text>
                </button>

                <!-- 在线咨询 / 客服 -->
                <view class="action-tool-item" @click="emit('contact')">
                    <BaseIcon name="customer-service" size="38" color="#181614" />
                    <text class="action-tool-label">咨询</text>
                </view>
            </view>

            <!-- 右侧核心转化：立即预约 -->
            <view class="bottom-action-main">
                <button class="primary-book-btn" @click="emit('book')">
                    <text class="primary-book-btn__title">立即预约主创</text>
                    <text class="primary-book-btn__sub">专属档期优先锁定</text>
                </button>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import BaseIcon from '@/components/base/BaseIcon.vue'

const emit = defineEmits<{
    (e: 'contact'): void
    (e: 'book'): void
    (e: 'share-click'): void
}>()
</script>

<style lang="scss" scoped>
.bottom-action-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: rgba(255, 255, 255, 0.98);
    border-top: 1rpx solid rgba(216, 201, 173, 0.6);
    backdrop-filter: blur(28rpx);
    -webkit-backdrop-filter: blur(28rpx);
    padding: 16rpx 28rpx calc(16rpx + env(safe-area-inset-bottom));
    box-shadow: 0 -8rpx 32rpx rgba(24, 22, 20, 0.06);
    box-sizing: border-box;
}

.bottom-action-bar__inner {
    display: flex;
    align-items: center;
    gap: 24rpx;
    height: 96rpx;
    width: 100%;
    box-sizing: border-box;
}

.bottom-action-tools {
    display: flex;
    align-items: center;
    gap: 28rpx;
    flex-shrink: 0;
}

.action-tool-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    min-width: 72rpx;
    cursor: pointer;
    background: transparent;
    border: none;
    padding: 0;
    margin: 0;
    line-height: normal;

    &::after {
        display: none;
    }
}

.action-tool-item--share {
    outline: none;
}

.action-tool-label {
    font-size: 20rpx;
    font-weight: 600;
    color: #5E564B;
    line-height: 1;
}

.bottom-action-main {
    flex: 1;
    min-width: 0;
}

.primary-book-btn {
    width: 100%;
    height: 88rpx;
    border-radius: 999rpx;
    background: linear-gradient(135deg, #181614 0%, #2A2622 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    box-shadow: 0 10rpx 24rpx rgba(24, 22, 20, 0.25);
    outline: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0 20rpx;
    line-height: 1.15;
    box-sizing: border-box;

    &::after {
        display: none;
    }

    &:active {
        opacity: 0.92;
        transform: scale(0.985);
    }
}

.primary-book-btn__title {
    font-size: 28rpx;
    font-weight: 700;
    color: #FAF8F2;
    letter-spacing: 1rpx;
}

.primary-book-btn__sub {
    font-size: 18rpx;
    color: #D9BE82;
    margin-top: 2rpx;
    letter-spacing: 0.5rpx;
}
</style>
