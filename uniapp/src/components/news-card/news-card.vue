<template>
    <navigator :url="`/packages/pages/news_detail/news_detail?id=${newsId}`" class="news-card-wrapper">
        <view class="news-card">
            <view class="news-cover-wrap">
                <image
                    v-if="item.image"
                    class="news-cover"
                    :src="item.image"
                    mode="aspectFill"
                    lazy-load
                />
                <view v-else class="news-cover news-cover--placeholder">
                    <text class="news-cover__placeholder-text">Wedding Story</text>
                </view>
                <view class="news-cover-mask" />
                <view class="news-cover-badge">灵感特辑</view>
            </view>
            <view class="news-content">
                <view class="news-title-row">
                    <text class="news-title">{{ item.title }}</text>
                </view>
                <text class="news-desc">{{ item.desc || '查看案例拆解、流程攻略与婚礼趋势，快速获得更适合当前阶段的决策信息。' }}</text>
                <view class="news-meta">
                    <view class="meta-item">
                        <tn-icon name="clock" size="24" :color="$theme.primaryColor" />
                        <text class="meta-text">{{ item.create_time }}</text>
                    </view>
                    <view class="meta-item">
                        <tn-icon name="eye" size="24" color="#B4ACA8" />
                        <text class="meta-text">{{ item.click }}</text>
                    </view>
                </view>
                <view class="news-foot">
                    <text class="news-foot__hint">点击查看详情</text>
                    <view class="news-foot__arrow">
                        <tn-icon name="right" size="24" :color="$theme.primaryColor" />
                    </view>
                </view>
            </view>
        </view>
    </navigator>
</template>

<script lang="ts" setup>
import { useThemeStore } from '@/stores/theme'

const props = withDefaults(
    defineProps<{
        item: any
        newsId: number
    }>(),
    {
        item: {},
        newsId: 0
    }
)

const $theme = useThemeStore()
</script>

<style lang="scss" scoped>
.news-card-wrapper {
    display: block;
    margin: 0 20rpx 24rpx;
}

.news-card {
    position: relative;
    display: flex;
    gap: 18rpx;
    padding: 18rpx;
    border-radius: 28rpx;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(255, 247, 244, 0.98) 100%);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(214, 185, 167, 0.16));
    overflow: hidden;
    transition: transform var(--wm-motion-base, 220ms) ease,
        box-shadow var(--wm-motion-base, 220ms) ease;

    &::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, rgba(255, 255, 255, 0.06), transparent 36%);
        pointer-events: none;
    }

    &:active {
        transform: translateY(-2rpx) scale(0.995);
        box-shadow: var(--wm-shadow-card, 0 18rpx 36rpx rgba(214, 185, 167, 0.2));
    }
}

.news-cover-wrap {
    position: relative;
    width: 250rpx;
    height: 206rpx;
    border-radius: 22rpx;
    flex-shrink: 0;
    overflow: hidden;
    background: linear-gradient(145deg, #f6d8d1 0%, #f0b7aa 52%, #dba58d 100%);
}

.news-cover {
    width: 100%;
    height: 100%;
    background: #f1ece2;
}

.news-cover--placeholder {
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
    padding: 22rpx;
}

.news-cover__placeholder-text {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.3;
    color: #ffffff;
}

.news-cover-mask {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.04) 0%, rgba(30, 36, 50, 0.38) 100%);
}

.news-cover-badge {
    position: absolute;
    left: 16rpx;
    top: 16rpx;
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.74);
    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);
    font-size: 20rpx;
    font-weight: 600;
    color: var(--wm-color-primary, #e85a4f);
    letter-spacing: 0.08em;
}

.news-content {
    position: relative;
    z-index: 1;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 0;
    padding: 4rpx 0;
}

.news-title-row {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
}

.news-title {
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-all;
}

.news-desc {
    margin-top: 12rpx;
    font-size: 24rpx;
    color: var(--wm-text-secondary, #7f7b78);
    line-height: 1.7;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-all;
}

.news-meta {
    margin-top: 18rpx;
    display: flex;
    align-items: center;
    gap: 20rpx;
    flex-wrap: wrap;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 8rpx 12rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.76);
}

.meta-text {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.news-foot {
    margin-top: 16rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14rpx;
}

.news-foot__hint {
    font-size: 22rpx;
    font-weight: 600;
    color: var(--wm-color-primary, #e85a4f);
    letter-spacing: 0.04em;
}

.news-foot__arrow {
    width: 44rpx;
    height: 44rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.78);
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
