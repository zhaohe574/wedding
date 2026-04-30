<template>
    <navigator
        :url="`/packages/pages/news_detail/news_detail?id=${newsId}`"
        class="news-card-wrapper"
        :class="{ 'news-card-wrapper--editorial': isEditorial }"
    >
        <view
            class="news-card"
            :class="{
                'news-card--editorial': isEditorial,
                'news-card--editorial-no-cover': isEditorial && !item.image
            }"
        >
            <template v-if="isEditorial">
                <view v-if="item.image" class="news-card__editorial-cover-wrap">
                    <image
                        class="news-card__editorial-cover"
                        :src="item.image"
                        mode="aspectFill"
                        lazy-load
                    />
                    <view class="news-card__editorial-badge">资讯</view>
                </view>

                <view class="news-card__editorial-content">
                    <view class="news-card__editorial-head">
                        <text class="news-card__editorial-title">{{ item.title }}</text>
                        <view class="news-card__editorial-arrow">
                            <tn-icon name="right" size="22" :color="$theme.primaryColor" />
                        </view>
                    </view>
                    <text class="news-card__editorial-desc">{{ articleDesc }}</text>
                    <view class="news-card__editorial-meta">
                        <view class="news-card__editorial-meta-item">
                            <tn-icon name="clock" size="22" color="#8E887D" />
                            <text>{{ displayTime }}</text>
                        </view>
                        <view class="news-card__editorial-meta-item">
                            <tn-icon name="eye" size="22" color="#8E887D" />
                            <text>{{ displayClick }} 浏览</text>
                        </view>
                    </view>
                </view>
            </template>

            <template v-else>
                <view class="news-cover-wrap">
                    <image
                        v-if="item.image"
                        class="news-cover"
                        :src="item.image"
                        mode="aspectFill"
                        lazy-load
                    />
                    <view v-else class="news-cover news-cover--placeholder">
                        <text class="news-cover__placeholder-text">资讯封面</text>
                    </view>
                    <view class="news-cover-mask" />
                    <view class="news-cover-badge">灵感特辑</view>
                </view>
                <view class="news-content">
                    <view class="news-title-row">
                        <text class="news-title">{{ item.title }}</text>
                    </view>
                    <text class="news-desc">{{ articleDesc }}</text>
                    <view class="news-meta">
                        <view class="meta-item">
                            <tn-icon name="clock" size="24" :color="$theme.primaryColor" />
                            <text class="meta-text">{{ item.create_time }}</text>
                        </view>
                        <view class="meta-item">
                            <tn-icon name="eye" size="24" color="#9A9388" />
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
            </template>
        </view>
    </navigator>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'

interface NewsCardItem {
    id?: number | string
    title?: string
    desc?: string
    image?: string
    click?: number | string
    create_time?: string
}

const props = withDefaults(
    defineProps<{
        item: NewsCardItem
        newsId: number
        variant?: 'default' | 'editorial'
    }>(),
    {
        item: () => ({}),
        newsId: 0,
        variant: 'default'
    }
)

const $theme = useThemeStore()

const isEditorial = computed(() => props.variant === 'editorial')
const articleDesc = computed(
    () => props.item.desc || '查看案例拆解、流程攻略与婚礼趋势，快速获得更适合当前阶段的决策信息。'
)
const displayTime = computed(() => {
    const value = String(props.item.create_time || '').trim()
    return value.split(' ')[0] || '刚刚'
})
const displayClick = computed(() => {
    const value = Number(props.item.click || 0)
    if (Number.isNaN(value) || value < 10000) {
        return String(props.item.click ?? 0)
    }
    const formatted = (value / 10000).toFixed(value >= 100000 ? 0 : 1)
    return `${formatted.replace(/\.0$/, '')}万`
})
</script>

<style lang="scss" scoped>
.news-card-wrapper {
    display: block;
    margin: 0 var(--wm-space-page-x, 20rpx) var(--wm-space-card-padding-lg, 24rpx);
}

.news-card-wrapper--editorial {
    margin: 0;
}

.news-card {
    position: relative;
    display: flex;
    gap: var(--wm-space-section-gap-lg, 16rpx);
    padding: var(--wm-space-card-padding, 20rpx);
    border-radius: var(--wm-radius-card-lg, 28rpx);
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.96) 0%,
        rgba(248, 247, 242, 0.98) 100%
    );
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(17, 17, 17, 0.16));
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
        box-shadow: var(--wm-shadow-card, 0 18rpx 36rpx rgba(17, 17, 17, 0.2));
    }
}

.news-card--editorial {
    display: block;
    gap: 0;
    padding: 0;
    border-radius: var(--news-editorial-card-radius, 16rpx);
    border-color: var(--wm-color-border, #e5e5e5);
    background: var(--news-editorial-card-bg, #ffffff);
    box-shadow: var(--news-editorial-card-shadow, 0 8rpx 18rpx rgba(17, 17, 17, 0.04));
    backdrop-filter: none;
    -webkit-backdrop-filter: none;

    &::after {
        display: none;
    }

    &:active {
        transform: translateY(1rpx);
        opacity: 0.96;
        box-shadow: var(--news-editorial-card-shadow, 0 8rpx 18rpx rgba(17, 17, 17, 0.04));
    }
}

.news-card--editorial-no-cover {
    &::before {
        content: '';
        position: absolute;
        left: 0;
        top: 24rpx;
        bottom: 24rpx;
        width: 6rpx;
        border-radius: 999rpx;
        background: var(--wm-color-secondary, #c8a45d);
    }

    .news-card__editorial-content {
        padding-top: 28rpx;
    }

    .news-card__editorial-title {
        -webkit-line-clamp: 3;
    }
}

.news-card__editorial-cover-wrap {
    position: relative;
    width: 100%;
    height: var(--news-editorial-cover-height, 360rpx);
    overflow: hidden;
    background: var(--wm-color-bg-soft, #f7f7f7);
}

.news-card__editorial-cover {
    width: 100%;
    height: 100%;
    display: block;
}

.news-card__editorial-badge {
    position: absolute;
    left: 18rpx;
    top: 18rpx;
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.84);
    border: 1rpx solid rgba(255, 255, 255, 0.72);
    color: var(--wm-color-primary, #0b0b0b);
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1;
}

.news-card__editorial-content {
    padding: 18rpx 22rpx 20rpx;
}

.news-card__editorial-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.news-card__editorial-title {
    flex: 1;
    min-width: 0;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.58;
    color: var(--wm-text-primary, #111111);
    word-break: break-all;
    overflow: hidden;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.news-card__editorial-arrow {
    width: 44rpx;
    height: 44rpx;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--wm-color-bg-soft, #f7f7f7);
    border: 1rpx solid var(--wm-color-border, #e5e5e5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.news-card__editorial-desc {
    display: block;
    margin-top: 12rpx;
    font-size: 25rpx;
    line-height: 1.62;
    color: var(--wm-text-secondary, #4a4a4a);
    word-break: break-all;
    overflow: hidden;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.news-card__editorial-meta {
    display: flex;
    align-items: center;
    gap: 20rpx;
    flex-wrap: wrap;
    margin-top: 16rpx;
}

.news-card__editorial-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    color: var(--wm-text-tertiary, #8e887d);

    text {
        font-size: 22rpx;
        font-weight: 600;
        line-height: 1;
    }
}

.news-cover-wrap {
    position: relative;
    width: 250rpx;
    height: 206rpx;
    border-radius: var(--wm-radius-card-soft, 20rpx);
    flex-shrink: 0;
    overflow: hidden;
    background: linear-gradient(145deg, #f7f0df 0%, #d8c28a 52%, #c8a45d 100%);
}

.news-cover {
    width: 100%;
    height: 100%;
    background: #f7f0df;
}

.news-cover--placeholder {
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
    padding: var(--wm-space-card-padding, 20rpx);
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
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.04) 0%, rgba(11, 11, 11, 0.38) 100%);
}

.news-cover-badge {
    position: absolute;
    left: 16rpx;
    top: 16rpx;
    padding: 8rpx 16rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(255, 255, 255, 0.74);
    border: 1rpx solid var(--wm-color-border-strong, #d8c28a);
    font-size: 20rpx;
    font-weight: 600;
    color: var(--wm-color-primary, #0b0b0b);
    letter-spacing: 0;
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
    color: var(--wm-text-primary, #111111);
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
    color: var(--wm-text-secondary, #5f5a50);
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
    gap: var(--wm-space-section-gap-lg, 16rpx);
    flex-wrap: wrap;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 8rpx 14rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(255, 255, 255, 0.76);
}

.meta-text {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #5f5a50);
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
    color: var(--wm-color-primary, #0b0b0b);
    letter-spacing: 0;
}

.news-foot__arrow {
    width: 44rpx;
    height: 44rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(255, 255, 255, 0.78);
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
