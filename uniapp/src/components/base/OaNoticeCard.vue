<template>
    <navigator
        v-if="!isOaBound"
        url="/pages/oa_subscribe/oa_subscribe"
        class="oa-notice-entry"
        hover-class="oa-notice-entry--pressed"
    >
        <view class="oa-notice-entry__icon">
            <BaseIcon name="notice" size="36" color="#8A6936" />
        </view>
        <view class="oa-notice-entry__body">
            <view class="oa-notice-entry__heading">
                <text class="oa-notice-entry__title">服务号通知</text>
                <text class="oa-notice-entry__badge">微信提醒</text>
            </view>
            <text class="oa-notice-entry__description">订单、档期与工作提醒，在微信也能接收</text>
        </view>
        <view class="oa-notice-entry__action">
            <text>去设置</text>
            <BaseIcon name="right" size="22" color="#8A6936" />
        </view>
    </navigator>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import BaseIcon from './BaseIcon.vue'
import { useOaBound } from '@/utils/oa-status'

const { isOaBound, checkOaBoundStatus } = useOaBound()

onMounted(() => {
    void checkOaBoundStatus()
})
</script>

<style scoped>
.oa-notice-entry {
    display: flex;
    align-items: center;
    gap: 20rpx;
    padding: 24rpx;
    border-radius: 24rpx;
    background: linear-gradient(135deg, #fbf7ee 0%, #ffffff 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.35);
    box-shadow: 0 4rpx 16rpx rgba(184, 154, 102, 0.08);
}
.oa-notice-entry--pressed {
    opacity: 0.75;
}
.oa-notice-entry__icon {
    width: 72rpx;
    height: 72rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20rpx;
    background: #f4ebd9;
}
.oa-notice-entry__body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}
.oa-notice-entry__heading {
    display: flex;
    align-items: center;
    gap: 12rpx;
    flex-wrap: wrap;
}
.oa-notice-entry__title {
    font-size: 28rpx;
    font-weight: 600;
    color: #2b2620;
}
.oa-notice-entry__badge {
    font-size: 19rpx;
    color: #8a6936;
    padding: 2rpx 8rpx;
    border: 1rpx solid #ddcba8;
    border-radius: 6rpx;
    background: rgba(221, 203, 168, 0.15);
}
.oa-notice-entry__description {
    font-size: 22rpx;
    line-height: 1.5;
    color: #8b7b64;
}
.oa-notice-entry__action {
    display: flex;
    align-items: center;
    gap: 4rpx;
    flex-shrink: 0;
    font-size: 24rpx;
    font-weight: 500;
    color: #8a6936;
}
</style>


