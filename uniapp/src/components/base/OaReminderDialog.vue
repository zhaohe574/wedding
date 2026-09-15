<template>
    <view v-if="oaReminderDialog.host === hostId" class="oa-reminder" @touchmove.stop.prevent @click.self="chooseOaReminder('once')">
        <view class="oa-reminder__card" @click.stop>
            <view class="oa-reminder__close" @click="chooseOaReminder('once')"><BaseIcon name="close" size="30" color="#968675" /></view>
            <view class="oa-reminder__icon"><BaseIcon name="notice" size="44" color="#A08044" /></view>
            <text class="oa-reminder__title">{{ oaReminderDialog.title }}</text>
            <text class="oa-reminder__content">{{ oaReminderDialog.content }}</text>
            <view class="oa-reminder__link" @click="chooseOaReminder('settings')">查看绑定方法 <BaseIcon name="right" size="22" color="#99763D" /></view>
            <view class="oa-reminder__actions">
                <view class="oa-reminder__button" hover-class="oa-reminder__pressed" @click="chooseOaReminder('once')">本次跳过</view>
                <view class="oa-reminder__button oa-reminder__button--primary" hover-class="oa-reminder__pressed" @click="chooseOaReminder('today')">今天不再提醒</view>
            </view>
            <text class="oa-reminder__footer">两种选择都会继续当前操作</text>
        </view>
    </view>
</template>
<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { onShow, onHide, onUnload, onBackPress } from '@dcloudio/uni-app'
import BaseIcon from './BaseIcon.vue'
import { oaReminderDialog, registerOaReminderHost, unregisterOaReminderHost, chooseOaReminder } from '@/utils/oa-reminder'
const hostId = `oa_${Math.random().toString(36).slice(2)}`
const register = () => registerOaReminderHost(hostId)
const unregister = () => unregisterOaReminderHost(hostId)
onMounted(register)
onShow(register)
onHide(unregister)
onUnload(unregister)
onUnmounted(unregister)
onBackPress(() => {
    if (oaReminderDialog.host !== hostId) return false
    chooseOaReminder('once')
    return true
})
</script>
<style scoped>
.oa-reminder { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 40rpx; background: rgba(25, 23, 19, .5); }
.oa-reminder__card { position: relative; width: 100%; max-width: 640rpx; padding: 44rpx 32rpx 30rpx; border-radius: 32rpx; background: #fffdf8; display: flex; flex-direction: column; align-items: center; box-sizing: border-box; }
.oa-reminder__close { position: absolute; right: 14rpx; top: 14rpx; padding: 16rpx; }
.oa-reminder__icon { display: flex; justify-content: center; align-items: center; width: 92rpx; height: 92rpx; background: #f1e5ce; border-radius: 28rpx; margin-bottom: 26rpx; }
.oa-reminder__title { font-size: 34rpx; font-weight: 600; color: #382e23; text-align: center; }
.oa-reminder__content { margin-top: 22rpx; color: #817464; font-size: 27rpx; line-height: 1.8; }
.oa-reminder__link { padding: 28rpx 16rpx; color: #99763d; font-size: 26rpx; display: flex; align-items: center; gap: 8rpx; }
.oa-reminder__actions { display: flex; gap: 16rpx; width: 100%; }
.oa-reminder__button { flex: 1; padding: 24rpx 8rpx; border-radius: 20rpx; background: #f2ece2; color: #786550; font-size: 26rpx; text-align: center; }
.oa-reminder__button--primary { background: #30291f; color: #eddbb7; }
.oa-reminder__pressed { opacity: .7; }
.oa-reminder__footer { margin-top: 22rpx; color: #a59989; font-size: 22rpx; }
</style>
