<template>
    <page-meta :page-style="$theme.pageStyle" />
    <BaseNavbar title="附加服务已下线" />

    <view class="offline-page">
        <view class="offline-card">
            <tn-icon name="warning-circle" size="112rpx" :color="$theme.primaryColor" />
            <text class="offline-title">附加服务变更已下线</text>
            <text class="offline-desc">旧版附加服务变更申请能力已关闭，当前页面仅保留占位，后续会按新版方案重新设计。</text>
            <view
                class="offline-btn"
                :style="{ backgroundColor: $theme.primaryColor, color: $theme.btnColor }"
                @click="goBack"
            >
                返回上一页
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const goBack = () => {
    uni.navigateBack()
}
</script>

<style lang="scss" scoped>
.offline-page {
    min-height: 100vh;
    padding: 32rpx 24rpx;
    background: #f6f7fb;
}

.offline-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24rpx;
    padding: 80rpx 40rpx;
    background: #ffffff;
    border-radius: 32rpx;
    box-shadow: 0 20rpx 48rpx rgba(15, 23, 42, 0.06);
    text-align: center;
}

.offline-title {
    font-size: 36rpx;
    font-weight: 600;
    color: #1f2937;
}

.offline-desc {
    font-size: 28rpx;
    line-height: 1.7;
    color: #6b7280;
}

.offline-btn {
    min-width: 280rpx;
    padding: 22rpx 40rpx;
    border-radius: 999rpx;
    font-size: 28rpx;
    font-weight: 600;
}
</style>
