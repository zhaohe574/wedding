<template>
    <view v-if="content.enabled !== 0" class="customer-service-widget" :style="widgetStyle">
        <view class="hero-card" :style="heroCardStyle">
            <view class="hero-badge" :style="badgeStyle">在线客服</view>
            <view class="hero-title">{{ content.title || '婚礼管家客服' }}</view>
            <view v-if="content.subtitle" class="hero-subtitle">{{ content.subtitle }}</view>
        </view>

        <view class="contact-card" :style="contactCardStyle">
            <view class="qr-wrapper">
                <!-- 这样渲染可以在小程序中长按识别二维码 -->
                <u-parse :html="richTxt"></u-parse>
            </view>
            <view class="qr-title">{{ content.qrTitle || '微信扫码咨询' }}</view>
            <view v-if="content.remark" class="qr-remark">{{ content.remark }}</view>

            <template v-if="content.mobile">
                <!-- #ifdef H5 -->
                <a class="phone-action" :style="phoneActionStyle" :href="'tel:' + content.mobile">
                    <text>{{ content.phoneText || '一键拨打客服' }}</text>
                    <text class="phone-number">{{ content.mobile }}</text>
                </a>
                <!-- #endif -->
                <!-- #ifndef H5 -->
                <view class="phone-action" :style="phoneActionStyle" @click="handleCall">
                    <text>{{ content.phoneText || '一键拨打客服' }}</text>
                    <text class="phone-number">{{ content.mobile }}</text>
                </view>
                <!-- #endif -->
            </template>

            <view v-if="content.time" class="service-time">{{ content.time }}</view>
        </view>

        <view v-if="content.tips" class="tips-card" :style="tipsCardStyle">
            <view class="tips-dot" :style="{ backgroundColor: themeColor }"></view>
            <view>{{ content.tips }}</view>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { computed } from 'vue'
import type { CSSProperties } from 'vue'

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    }
})

const { getImageUrl } = useAppStore()
const themeStore = useThemeStore()

const appendAlpha = (hexColor: string, alpha: string) => {
    if (/^#([0-9a-fA-F]{6})$/.test(hexColor)) {
        return `${hexColor}${alpha}`
    }
    return hexColor
}

const themeColor = computed(() => props.styles?.themeColor || themeStore.primaryColor || '#E56B6F')
const pageBgColor = computed(() => props.styles?.pageBgColor || '#F7F8FC')
const cardRadius = computed(() => Number(props.styles?.cardRadius) || 20)
const qrSize = computed(() => Number(props.styles?.qrSize) || 120)
const cardGap = computed(() => Number(props.styles?.cardGap) || 16)
const cardRadiusRpx = computed(() => `${cardRadius.value * 2}rpx`)

const widgetStyle = computed<CSSProperties>(() => ({
    padding: `${cardGap.value * 2}rpx`,
    background: `linear-gradient(180deg, ${appendAlpha(themeColor.value, '1A')} 0%, ${pageBgColor.value} 320rpx, ${pageBgColor.value} 100%)`,
    minHeight: '100vh',
    boxSizing: 'border-box'
}))

const heroCardStyle = computed(() => ({
    borderRadius: cardRadiusRpx.value,
    background: `linear-gradient(135deg, ${appendAlpha(themeColor.value, '18')} 0%, #ffffff 100%)`
}))

const badgeStyle = computed(() => ({
    color: themeColor.value,
    backgroundColor: appendAlpha(themeColor.value, '1A')
}))

const contactCardStyle = computed(() => ({
    borderRadius: cardRadiusRpx.value
}))

const phoneActionStyle = computed(() => ({
    color: themeColor.value,
    backgroundColor: appendAlpha(themeColor.value, '14')
}))

const tipsCardStyle = computed(() => ({
    borderRadius: `${Math.max(10, cardRadius.value - 6) * 2}rpx`,
    backgroundColor: appendAlpha(themeColor.value, '12')
}))

const richTxt = computed(() => {
    const size = qrSize.value
    const radius = Math.max(8, Math.floor(cardRadius.value / 2))
    if (!props.content?.qrcode) {
        return `<div style="width:${size}px;height:${size}px;border-radius:${radius}px;border:1px dashed #d1d5db;color:#9ca3af;font-size:12px;display:flex;align-items:center;justify-content:center;">请上传二维码</div>`
    }

    const src = getImageUrl(props.content.qrcode)
    return `<img src="${src}" style="width:${size}px;height:${size}px;border-radius:${radius}px;display:block;" />`
})

const handleCall = () => {
    if (!props.content?.mobile) return
    uni.makePhoneCall({
        phoneNumber: String(props.content.mobile)
    })
}
</script>

<style lang="scss" scoped>
.hero-card {
    padding: 36rpx 32rpx;
    box-shadow: 0 16rpx 40rpx rgba(15, 23, 42, 0.08);
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    height: 48rpx;
    padding: 0 20rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.hero-title {
    margin-top: 18rpx;
    color: #111827;
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1.4;
}

.hero-subtitle {
    margin-top: 12rpx;
    color: #6b7280;
    font-size: 26rpx;
    line-height: 1.6;
}

.contact-card {
    margin-top: 24rpx;
    padding: 36rpx 32rpx;
    background: #ffffff;
    box-shadow: 0 16rpx 40rpx rgba(15, 23, 42, 0.06);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.qr-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
}

.qr-title {
    margin-top: 24rpx;
    color: #111827;
    font-size: 32rpx;
    font-weight: 600;
}

.qr-remark {
    margin-top: 14rpx;
    color: #6b7280;
    font-size: 26rpx;
    text-align: center;
    line-height: 1.6;
}

.phone-action {
    width: 100%;
    margin-top: 24rpx;
    padding: 20rpx 24rpx;
    border-radius: 24rpx;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 24rpx;
    font-weight: 600;
    text-decoration: none;
}

.phone-number {
    font-size: 30rpx;
}

.service-time {
    margin-top: 16rpx;
    color: #6b7280;
    font-size: 24rpx;
    line-height: 1.5;
}

.tips-card {
    margin-top: 20rpx;
    padding: 16rpx 20rpx;
    color: #374151;
    font-size: 24rpx;
    line-height: 1.5;
    display: flex;
    align-items: center;
}

.tips-dot {
    width: 14rpx;
    height: 14rpx;
    border-radius: 999rpx;
    margin-right: 12rpx;
    flex-shrink: 0;
}
</style>
