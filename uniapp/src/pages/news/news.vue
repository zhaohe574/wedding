<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <!-- #ifndef H5 -->
    <tn-sticky>
        <tn-navbar :back="false" :fixed="false" title="婚礼资讯"> </tn-navbar>
    </tn-sticky>
    <!-- #endif -->
    <view class="news-page cinema-page page-with-tabbar-safe-bottom">
        <view class="news-page__hero">
            <view class="news-page__hero-badge">Wedding Journal</view>
            <text class="news-page__hero-title">婚礼灵感、案例趋势与服务攻略</text>
            <text class="news-page__hero-desc">
                用电影海报式的信息编排整理灵感，让备婚浏览更沉浸，也更容易快速找到所需内容。
            </text>
            <view class="news-page__hero-stats glass-card">
                <view class="news-page__hero-stat">
                    <text class="news-page__hero-stat-value">{{ tabList.length || 1 }}</text>
                    <text class="news-page__hero-stat-label">栏目分类</text>
                </view>
                <view class="news-page__hero-divider" />
                <view class="news-page__hero-stat">
                    <text class="news-page__hero-stat-value">{{ activeTabName }}</text>
                    <text class="news-page__hero-stat-label">当前频道</text>
                </view>
            </view>
        </view>

        <view class="news-page__surface cinema-surface">
            <navigator class="news-page__search glass-card" url="/pages/search/search">
                <view class="news-page__search-head">
                    <view>
                        <text class="news-page__search-label">灵感检索</text>
                        <text class="news-page__search-title">搜索策划、案例、趋势关键词</text>
                    </view>
                    <text class="news-page__search-side">快速进入</text>
                </view>
                <tn-search-box placeholder="请输入关键词搜索" :disabled="true"></tn-search-box>
            </navigator>

            <view class="news-page__tabs cinema-panel">
                <view class="news-page__tabs-head">
                    <view>
                        <text class="news-page__section-title">精选栏目</text>
                        <text class="news-page__section-desc">保持海报感视觉，但优先保证长列表阅读效率。</text>
                    </view>
                </view>
                <tabs
                    :current="current"
                    @change="handleChange"
                    height="82"
                    bar-width="60"
                    :barStyle="{ bottom: '0' }"
                >
                    <tab v-for="(item, i) in tabList" :key="i" :name="item.name">
                        <view class="news-list">
                            <news-list :cid="item.id" :i="i" :index="current"></news-list>
                        </view>
                    </tab>
                </tabs>
            </view>
        </view>
        <tabbar />
    </view>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import NewsList from './component/news-list.vue'
import { getArticleCate } from '@/api/news'

const tabList = ref<any>([])
const current = ref<number>(0)

const activeTabName = computed(() => tabList.value[current.value]?.name || '全部')

const handleChange = (index: number) => {
    current.value = Number(index)
}

const getData = async () => {
    const data = await getArticleCate()
    tabList.value = [{ name: '全部', id: '' }].concat(data)
}

onLoad(() => {
    getData()
})
</script>

<style lang="scss">
.news-page {
    min-height: 100vh;

    &__hero {
        position: relative;
        padding: 24rpx 24rpx 196rpx;
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.12) 0, transparent 34%),
            linear-gradient(145deg, rgba(10, 13, 18, 0.98) 0%, rgba(22, 29, 41, 0.96) 56%, rgba(76, 58, 29, 0.92) 100%);
        overflow: hidden;
    }

    &__hero::after {
        content: '';
        position: absolute;
        right: -40rpx;
        top: 44rpx;
        width: 260rpx;
        height: 260rpx;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(232, 201, 142, 0.2) 0, transparent 72%);
        pointer-events: none;
    }

    &__hero-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10rpx 18rpx;
        border-radius: 999rpx;
        border: 1rpx solid rgba(255, 248, 236, 0.22);
        background: rgba(255, 248, 236, 0.08);
        font-size: 22rpx;
        font-weight: 600;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: rgba(255, 248, 236, 0.84);
    }

    &__hero-title {
        display: block;
        margin-top: 26rpx;
        font-size: 50rpx;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: 0.02em;
        color: var(--cinema-text-inverse, #fff8ea);
    }

    &__hero-desc {
        display: block;
        margin-top: 20rpx;
        max-width: 620rpx;
        font-size: 26rpx;
        line-height: 1.7;
        color: rgba(255, 248, 236, 0.72);
    }

    &__hero-stats {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 20rpx;
        margin-top: 32rpx;
        padding: 22rpx 24rpx;
        background: rgba(255, 248, 236, 0.1);
    }

    &__hero-stat {
        flex: 1;
        min-width: 0;
    }

    &__hero-stat-value {
        display: block;
        font-size: 32rpx;
        font-weight: 700;
        color: var(--cinema-text-inverse, #fff8ea);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__hero-stat-label {
        display: block;
        margin-top: 8rpx;
        font-size: 22rpx;
        color: rgba(255, 248, 236, 0.64);
    }

    &__hero-divider {
        width: 1rpx;
        height: 64rpx;
        background: rgba(255, 248, 236, 0.16);
    }

    &__surface {
        position: relative;
        margin-top: -148rpx;
        border-radius: 36rpx 36rpx 0 0;
        padding: 0 24rpx 28rpx;
        box-shadow: 0 -24rpx 48rpx rgba(8, 10, 16, 0.18);
    }

    &__search {
        padding: 24rpx;
    }

    &__search-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;
        margin-bottom: 18rpx;
    }

    &__search-label {
        display: block;
        font-size: 22rpx;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--cinema-text-secondary, #5d6472);
    }

    &__search-title {
        display: block;
        margin-top: 8rpx;
        font-size: 30rpx;
        font-weight: 600;
        line-height: 1.35;
        color: var(--cinema-text-primary, #151a23);
    }

    &__search-side {
        padding-top: 6rpx;
        font-size: 22rpx;
        color: var(--cinema-primary, #c6a86a);
    }

    &__tabs {
        margin-top: 22rpx;
        overflow: hidden;
    }

    &__tabs-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        padding: 26rpx 24rpx 10rpx;
    }

    &__section-title {
        display: block;
        font-size: 30rpx;
        font-weight: 700;
        color: var(--cinema-text-primary, #151a23);
    }

    &__section-desc {
        display: block;
        margin-top: 8rpx;
        font-size: 22rpx;
        line-height: 1.6;
        color: var(--cinema-text-secondary, #5d6472);
    }
}

.news-list {
    height: calc(100vh - 612rpx - env(safe-area-inset-bottom));
}

.news-page :deep(.tabs__nav),
.news-page :deep(.tabs__header),
.news-page :deep(.tabs__tab) {
    background: transparent !important;
}

.news-page :deep(.tabs__header) {
    padding: 0 18rpx;
}

.news-page :deep(.tabs__tab) {
    color: var(--cinema-text-secondary, #5d6472) !important;
    font-size: 26rpx !important;
    font-weight: 500 !important;
}

.news-page :deep(.tabs__tab.is-active) {
    color: var(--cinema-primary, #c6a86a) !important;
}

.news-page :deep(.tabs__line) {
    background: linear-gradient(135deg, var(--cinema-primary, #c6a86a) 0%, var(--cinema-accent, #e8c98e) 100%) !important;
    border-radius: 999rpx;
}
</style>
