<template>
<navigator url="/pages/oa_subscribe/oa_subscribe" class="oa-notice-entry" hover-class="oa-notice-entry--pressed">
                <view class="oa-notice-entry__icon"><BaseIcon name="notice" size="36" color="#8A6936" /></view>
                <view class="oa-notice-entry__body"><view class="oa-notice-entry__heading"><text class="oa-notice-entry__title">服务号通知</text><text class="oa-notice-entry__badge">微信提醒</text></view><text class="oa-notice-entry__description">订单、档期与工作提醒，在微信也能接收</text></view>
                <view class="oa-notice-entry__action"><text>去设置</text><BaseIcon name="right" size="22" color="#8A6936" /></view>
            </navigator>
</template>
<script setup lang="ts">
import BaseIcon from './BaseIcon.vue'
</script>
<style scoped>
.oa-notice-entry { display: flex; align-items: center; gap: 20rpx; padding: 26rpx 24rpx; border-radius: 28rpx; background: linear-gradient(120deg, #f4ead7, #fffdf8); border: 1rpx solid #decdac; box-shadow: 0 6rpx 18rpx rgba(74, 43, 24, .04); }
.oa-notice-entry--pressed { opacity: .7; }
.oa-notice-entry__icon { width: 72rpx; height: 72rpx; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: 22rpx; background: #eadbbd; }
.oa-notice-entry__body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 10rpx; }
.oa-notice-entry__heading { display: flex; align-items: center; gap: 12rpx; flex-wrap: wrap; }
.oa-notice-entry__title { font-size: 28rpx; font-weight: 600; color: #3b3227; }
.oa-notice-entry__badge { font-size: 19rpx; color: #8a6936; padding: 3rpx 9rpx; border: 1rpx solid #ddcba8; border-radius: 8rpx; }
.oa-notice-entry__description { font-size: 23rpx; line-height: 1.6; color: #8b7b64; }
.oa-notice-entry__action { display: flex; align-items: center; gap: 5rpx; flex-shrink: 0; font-size: 23rpx; color: #8a6936; }
</style>

