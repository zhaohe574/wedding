<template>
    <div class="customer-service-widget" :style="widgetStyle">
        <div class="entry-head">
            <div class="entry-badge" :style="badgeStyle">顾问咨询</div>
            <div class="entry-arrow">立即联系</div>
        </div>
        <div class="entry-title">{{ content.title || '联系专属顾问' }}</div>
        <div v-if="content.subtitle" class="entry-subtitle">{{ content.subtitle }}</div>
        <div class="entry-footer">
            <div v-if="content.tips" class="entry-tips">{{ content.tips }}</div>
            <div class="entry-btn" :style="buttonStyle">
                {{ content.buttonText || '联系专属顾问' }}
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import type { PropType } from 'vue'

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
const cardRadius = computed(() => Number(props.styles?.cardRadius) || 20)
const cardGap = computed(() => Number(props.styles?.cardGap) || 16)

const widgetStyle = computed(() => ({
    margin: `${cardGap.value}px`,
    padding: '18px 16px',
    borderRadius: `${cardRadius.value}px`,
    background: `linear-gradient(135deg, ${appendAlpha(themeColor.value, '16')} 0%, #ffffff 100%)`,
    boxShadow: '0 12px 28px rgba(15, 23, 42, 0.08)'
}))

const badgeStyle = computed(() => ({
    color: themeColor.value,
    backgroundColor: appendAlpha(themeColor.value, '18')
}))

const buttonStyle = computed(() => ({
    backgroundColor: themeColor.value,
    color: '#ffffff'
}))
</script>

<style lang="scss" scoped>
.customer-service-widget {
    min-height: 220px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    box-sizing: border-box;
}

.entry-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.entry-badge {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

.entry-arrow {
    font-size: 12px;
    color: #6b7280;
}

.entry-title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
}

.entry-subtitle,
.entry-tips {
    font-size: 13px;
    line-height: 1.7;
    color: #6b7280;
}

.entry-footer {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.entry-btn {
    height: 42px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
}
</style>
