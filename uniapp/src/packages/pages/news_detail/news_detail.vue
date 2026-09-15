<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="详情" />
        <view v-if="loading || loadError" class="news-detail-page wm-page-content">
            <text>{{ loading ? '正在加载资讯…' : loadError }}</text>
            <button v-if="!loading" @click="getData(newsId)">重新加载</button>
            <button @click="backToNews">返回资讯列表</button>
        </view>
        <view v-else class="news-detail-page cinema-page wm-page-content">
            <view class="news-detail-page__shell">
                <view class="news-detail-page__header cinema-panel wm-panel-card">
                    <text class="news-detail-page__title">{{ newsData.title }}</text>
                    <view class="news-detail-page__meta">
                        <view class="news-detail-page__meta-item" v-if="newsData.author">
                            作者：{{ newsData.author }}
                        </view>
                        <view class="news-detail-page__meta-item news-detail-page__meta-item--time">
                            {{ newsData.create_time }}
                        </view>
                        <view
                            class="news-detail-page__meta-item news-detail-page__meta-item--views"
                        >
                            <image
                                src="/static/images/icon/icon_visit.png"
                                class="news-detail-page__meta-icon"
                            ></image>
                            <text>{{ newsData.click }}</text>
                        </view>
                    </view>
                </view>

                <view class="news-detail-page__content wm-panel wm-panel-card">
                    <view class="news-detail-page__summary" v-if="newsData.abstract">
                        <text class="news-detail-page__summary-label">摘要：</text>
                        <text class="news-detail-page__summary-text">{{ newsData.abstract }}</text>
                    </view>

                    <view class="news-detail-page__article">
                        <u-parse :html="newsData.content"></u-parse>
                    </view>
                </view>
            </view>

            <view class="news-detail-page__action" @click="handleAddCollect(newsData.id)">
                <BaseIcon
                    :name="newsData.collect ? 'star-fill' : 'star'"
                    size="34"
                    :color="newsData.collect ? '#0B0B0B' : '#5F5A50'"
                ></BaseIcon>
                <text class="news-detail-page__action-text">
                    {{ newsData.collect ? '已收藏' : '收藏' }}
                </text>
            </view>
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getArticleDetail, addCollect, cancelCollect } from '@/api/news'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'

const newsData = ref<any>({})
const loading = ref(true)
const loadError = ref('')
let newsId = ''
const $theme = useThemeStore()

const getData = async (id: number | string) => {
    loading.value = true
    loadError.value = ''
    try {
        if (!Number.isInteger(Number(id)) || Number(id) <= 0) throw new Error('资讯链接无效')
        const data = await getArticleDetail({ id: Number(id) })
        if (!data?.id) throw new Error('资讯不存在或已下架')
        newsData.value = data
    } catch {
        loadError.value = '资讯暂时无法加载，可能已下架或网络异常。'
    } finally {
        loading.value = false
    }
}

const backToNews = () => uni.reLaunch({ url: '/pages/news/news' })

const handleAddCollect = async (id: number) => {
    try {
        if (newsData.value.collect) {
            await cancelCollect({ id })
            uni.$u.toast('已取消收藏')
        } else {
            await addCollect({ id })
            uni.$u.toast('收藏成功')
        }
        getData(newsId)
    } catch (e) {
        //TODO handle the exception
    }
}

onLoad((options: any) => {
    $theme.setScene('consumer')
    newsId = options.id
    getData(newsId)
})
</script>

<style lang="scss" scoped>
.news-detail-page {
    padding: 20rpx 20rpx calc(140rpx + env(safe-area-inset-bottom));

    &__shell {
        display: flex;
        flex-direction: column;
        gap: 20rpx;
    }

    &__header,
    &__content {
        padding: 24rpx;
    }

    &__title {
        display: block;
        font-size: 38rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #111111);
    }

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 20rpx;
        margin-top: 20rpx;
        font-size: 22rpx;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__meta-item {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        min-width: 0;
    }

    &__meta-item--time {
        flex: 1;
    }

    &__meta-icon {
        width: 28rpx;
        height: 28rpx;
        flex-shrink: 0;
    }

    &__summary {
        padding: 20rpx;
        border-radius: var(--wm-radius-card-soft, 20rpx);
        background: var(--wm-color-bg-soft, #ffffff);
        border: 1rpx solid var(--wm-color-border, #e7e2d6);
        color: var(--wm-text-secondary, #5f5a50);
        font-size: 26rpx;
        line-height: 1.75;
    }

    &__summary-label {
        font-weight: 700;
        color: var(--wm-text-primary, #111111);
    }

    &__article {
        margin-top: 24rpx;
        color: var(--wm-text-primary, #111111);
        font-size: 28rpx;
        line-height: 1.85;
    }

    &__article :deep(img) {
        max-width: 100% !important;
        border-radius: var(--wm-radius-card-soft, 20rpx);
        overflow: hidden;
    }

    &__article :deep(p) {
        margin-bottom: 20rpx;
        color: var(--wm-text-primary, #111111);
        line-height: 1.85;
    }

    &__article :deep(a) {
        color: var(--wm-color-primary, #0b0b0b);
    }

    &__action {
        position: fixed;
        right: 24rpx;
        bottom: calc(32rpx + env(safe-area-inset-bottom));
        z-index: 20;
        display: inline-flex;
        align-items: center;
        gap: 10rpx;
        height: 84rpx;
        padding: 0 30rpx;
        border-radius: var(--wm-radius-pill, 999rpx);
        background: rgba(255, 255, 255, 0.92);
        border: 1rpx solid var(--wm-color-border, #e7e2d6);
        box-shadow: var(--wm-shadow-card, 0 18rpx 36rpx rgba(17, 17, 17, 0.2));
    }

    &__action-text {
        font-size: 26rpx;
        font-weight: 600;
        color: var(--wm-text-primary, #111111);
    }
}
</style>
