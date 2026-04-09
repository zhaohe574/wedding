<template>
    <BaseCard variant="hero" scene="consumer" class="aftersale-status-banner">
        <view class="aftersale-status-banner__top">
            <view class="aftersale-status-banner__copy">
                <text class="aftersale-status-banner__label">{{ label }}</text>
                <text class="aftersale-status-banner__title">{{ title }}</text>
                <text v-if="summary" class="aftersale-status-banner__summary">{{ summary }}</text>
            </view>
            <view v-if="badges.length" class="aftersale-status-banner__badges">
                <StatusBadge
                    v-for="badge in badges"
                    :key="badge.text"
                    :tone="badge.tone || 'neutral'"
                    size="sm"
                >
                    {{ badge.text }}
                </StatusBadge>
            </view>
        </view>

        <view v-if="metrics.length" class="aftersale-status-banner__metrics">
            <view v-for="item in metrics" :key="item.label" class="aftersale-status-banner__metric">
                <text class="aftersale-status-banner__metric-label">{{ item.label }}</text>
                <text class="aftersale-status-banner__metric-value">{{ item.value }}</text>
            </view>
        </view>
    </BaseCard>
</template>

<script setup lang="ts">
import BaseCard from '@/components/base/BaseCard.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import type { BadgeTone, StatusMetricItem } from '../shared'

interface BannerBadge {
    text: string
    tone?: BadgeTone
}

interface Props {
    label: string
    title: string
    summary?: string
    badges?: BannerBadge[]
    metrics?: StatusMetricItem[]
}

withDefaults(defineProps<Props>(), {
    summary: '',
    badges: () => [],
    metrics: () => []
})
</script>

<style lang="scss" scoped>
.aftersale-status-banner {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.aftersale-status-banner__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.aftersale-status-banner__copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.aftersale-status-banner__label {
    font-size: 22rpx;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: var(--wm-color-primary, #e85a4f);
}

.aftersale-status-banner__title {
    font-size: 40rpx;
    line-height: 1.25;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-status-banner__summary {
    font-size: 24rpx;
    line-height: 1.65;
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-status-banner__badges {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10rpx;
}

.aftersale-status-banner__metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14rpx;
}

.aftersale-status-banner__metric {
    padding: 18rpx 20rpx;
    border-radius: 28rpx;
    border: 1rpx solid rgba(239, 230, 225, 0.9);
    background: rgba(255, 255, 255, 0.76);
}

.aftersale-status-banner__metric-label {
    display: block;
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-status-banner__metric-value {
    display: block;
    margin-top: 8rpx;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-primary, #1e2432);
}
</style>
