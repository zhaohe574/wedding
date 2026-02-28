<template>
    <div class="customer-service-widget" :style="widgetStyle">
        <div class="hero-card" :style="heroCardStyle">
            <div class="hero-badge" :style="badgeStyle">在线客服</div>
            <div class="hero-title">{{ content.title || '婚礼管家客服' }}</div>
            <div v-if="content.subtitle" class="hero-subtitle">{{ content.subtitle }}</div>
        </div>

        <div class="contact-card" :style="contactCardStyle">
            <div class="qr-wrapper">
                <decoration-img
                    :width="`${qrSize}px`"
                    :height="`${qrSize}px`"
                    :src="content.qrcode"
                    alt=""
                />
            </div>
            <div class="qr-title">{{ content.qrTitle || '微信扫码咨询' }}</div>
            <div v-if="content.remark" class="qr-remark">{{ content.remark }}</div>

            <div v-if="content.mobile" class="phone-action" :style="phoneActionStyle">
                <span>{{ content.phoneText || '一键拨打客服' }}</span>
                <span class="phone-number">{{ content.mobile }}</span>
            </div>
            <div v-if="content.time" class="service-time">{{ content.time }}</div>
        </div>

        <div v-if="content.tips" class="tips-card" :style="tipsCardStyle">
            <span class="tips-dot" :style="{ backgroundColor: themeColor }"></span>
            <span>{{ content.tips }}</span>
        </div>
    </div>
</template>
<script lang="ts" setup>
import type { PropType } from 'vue'

import DecorationImg from '../../decoration-img.vue'
import type options from './options'

type OptionsType = ReturnType<typeof options>
const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    }
})

const appendAlpha = (hexColor: string, alpha: string) => {
    if (/^#([0-9a-fA-F]{6})$/.test(hexColor)) {
        return `${hexColor}${alpha}`
    }
    return hexColor
}

const themeColor = computed(() => props.styles?.themeColor || '#E56B6F')
const pageBgColor = computed(() => props.styles?.pageBgColor || '#F7F8FC')
const cardRadius = computed(() => Number(props.styles?.cardRadius) || 20)
const qrSize = computed(() => Number(props.styles?.qrSize) || 120)
const cardGap = computed(() => Number(props.styles?.cardGap) || 16)

const widgetStyle = computed(() => ({
    padding: `${cardGap.value}px`,
    background: `linear-gradient(180deg, ${appendAlpha(themeColor.value, '1A')} 0%, ${pageBgColor.value} 180px, ${pageBgColor.value} 100%)`
}))

const heroCardStyle = computed(() => ({
    borderRadius: `${cardRadius.value}px`,
    background: `linear-gradient(135deg, ${appendAlpha(themeColor.value, '18')} 0%, #ffffff 100%)`
}))

const badgeStyle = computed(() => ({
    color: themeColor.value,
    backgroundColor: appendAlpha(themeColor.value, '1A')
}))

const contactCardStyle = computed(() => ({
    borderRadius: `${cardRadius.value}px`
}))

const phoneActionStyle = computed(() => ({
    color: themeColor.value,
    backgroundColor: appendAlpha(themeColor.value, '14')
}))

const tipsCardStyle = computed(() => ({
    borderRadius: `${Math.max(10, cardRadius.value - 6)}px`,
    backgroundColor: appendAlpha(themeColor.value, '12')
}))
</script>

<style lang="scss" scoped>
.customer-service-widget {
    min-height: 560px;
    box-sizing: border-box;
}

.hero-card {
    padding: 18px 16px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    height: 24px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

.hero-title {
    margin-top: 10px;
    color: #111827;
    font-size: 20px;
    font-weight: 700;
    line-height: 1.4;
}

.hero-subtitle {
    margin-top: 6px;
    color: #6b7280;
    font-size: 13px;
    line-height: 1.6;
}

.contact-card {
    margin-top: 14px;
    padding: 18px 16px;
    background: #ffffff;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
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
    margin-top: 12px;
    font-size: 16px;
    font-weight: 600;
    color: #111827;
}

.qr-remark {
    margin-top: 8px;
    font-size: 13px;
    color: #6b7280;
    text-align: center;
    line-height: 1.6;
}

.phone-action {
    width: 100%;
    margin-top: 14px;
    padding: 10px 12px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
    box-sizing: border-box;
}

.phone-number {
    font-size: 15px;
}

.service-time {
    margin-top: 10px;
    color: #6b7280;
    font-size: 13px;
}

.tips-card {
    margin-top: 12px;
    padding: 10px 12px;
    color: #374151;
    font-size: 12px;
    display: flex;
    align-items: center;
    line-height: 1.5;
}

.tips-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    margin-right: 8px;
    flex-shrink: 0;
}
</style>
