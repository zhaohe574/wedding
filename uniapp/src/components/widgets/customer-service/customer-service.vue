<template>
    <view v-if="content.enabled !== 0" class="consult-entry" :style="entryStyle" @click="goConsult">
        <view class="entry-head">
            <view class="entry-badge" :style="badgeStyle">顾问咨询</view>
            <view class="entry-arrow">立即联系</view>
        </view>
        <view class="entry-title">{{ content.title || '联系顾问' }}</view>
        <view v-if="content.subtitle" class="entry-subtitle">{{ content.subtitle }}</view>
        <view class="entry-footer">
            <view v-if="content.tips" class="entry-tips">{{ content.tips }}</view>
            <view class="entry-btn" :style="buttonStyle">{{
                content.buttonText || '联系顾问'
            }}</view>
        </view>
    </view>
</template>

<script lang="ts" setup>
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

const appendAlpha = (hexColor: string, alpha: string) => {
    if (/^#([0-9a-fA-F]{6})$/.test(hexColor)) {
        return `${hexColor}${alpha}`
    }
    return hexColor
}

const themeColor = computed(() => props.styles?.themeColor || '#C8A45D')
const cardRadius = computed(() => Number(props.styles?.cardRadius) || 20)
const cardGap = computed(() => Number(props.styles?.cardGap) || 16)

const entryStyle = computed<CSSProperties>(() => ({
    margin: `${cardGap.value * 2}rpx`,
    padding: '32rpx',
    borderRadius: `${cardRadius.value * 2}rpx`,
    background: `linear-gradient(135deg, ${appendAlpha(themeColor.value, '16')} 0%, #ffffff 100%)`,
    boxShadow: '0 18rpx 40rpx rgba(17, 17, 17, 0.08)'
}))

const badgeStyle = computed(() => ({
    color: themeColor.value,
    backgroundColor: appendAlpha(themeColor.value, '18')
}))

const buttonStyle = computed(() => ({
    backgroundColor: themeColor.value,
    color: '#ffffff'
}))

const goConsult = () => {
    uni.navigateTo({
        url: '/packages/pages/customer_service/customer_service?scene=home'
    })
}
</script>

<style lang="scss" scoped>
.consult-entry {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.entry-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.entry-badge {
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.entry-arrow {
    font-size: 24rpx;
    color: #6c665c;
}

.entry-title {
    font-size: 34rpx;
    font-weight: 700;
    color: #111111;
}

.entry-subtitle,
.entry-tips {
    font-size: 25rpx;
    line-height: 1.7;
    color: #6c665c;
}

.entry-footer {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.entry-btn {
    height: 84rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 27rpx;
    font-weight: 600;
}
</style>
