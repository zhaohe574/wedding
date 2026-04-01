<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="详情" />
        <view class="news-detail-page cinema-page">
            <view class="news-detail-page__shell">
            <view class="news-detail-page__header cinema-panel">
                <text class="news-detail-page__title">{{ newsData.title }}</text>
                <view class="news-detail-page__meta">
                    <view class="news-detail-page__meta-item" v-if="newsData.author">
                        作者：{{ newsData.author }}
                    </view>
                    <view class="news-detail-page__meta-item news-detail-page__meta-item--time">
                        {{ newsData.create_time }}
                    </view>
                    <view class="news-detail-page__meta-item news-detail-page__meta-item--views">
                        <image
                            src="/static/images/icon/icon_visit.png"
                            class="news-detail-page__meta-icon"
                        ></image>
                        <text>{{ newsData.click }}</text>
                    </view>
                </view>
            </view>

            <view class="news-detail-page__content wm-panel">
                <view
                    class="news-detail-page__summary"
                    v-if="newsData.abstract"
                >
                    <text class="news-detail-page__summary-label">摘要：</text>
                    <text class="news-detail-page__summary-text">{{ newsData.abstract }}</text>
                </view>

                <view class="news-detail-page__article">
                    <u-parse :html="newsData.content"></u-parse>
                </view>
            </view>
            </view>

            <view class="news-detail-page__action" @click="handleAddCollect(newsData.id)">
                <tn-icon
                    :name="newsData.collect ? 'star-fill' : 'star'"
                    size="34"
                    :color="newsData.collect ? '#E85A4F' : '#7F7B78'"
                ></tn-icon>
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
let newsId = ''
const $theme = useThemeStore()

const getData = async (id: number | string) => {
    newsData.value = await getArticleDetail({ id: Number(id) })
}

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
        color: var(--wm-text-primary, #1e2432);
    }

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 20rpx;
        margin-top: 20rpx;
        font-size: 22rpx;
        color: var(--wm-text-secondary, #7f7b78);
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
        background: var(--wm-color-bg-soft, #fff7f4);
        border: 1rpx solid var(--wm-color-border, #efe6e1);
        color: var(--wm-text-secondary, #7f7b78);
        font-size: 26rpx;
        line-height: 1.75;
    }

    &__summary-label {
        font-weight: 700;
        color: var(--wm-text-primary, #1e2432);
    }

    &__article {
        margin-top: 24rpx;
        color: var(--wm-text-primary, #1e2432);
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
        color: var(--wm-text-primary, #1e2432);
        line-height: 1.85;
    }

    &__article :deep(a) {
        color: var(--wm-color-primary, #e85a4f);
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
        border: 1rpx solid var(--wm-color-border, #efe6e1);
        box-shadow: var(--wm-shadow-card, 0 18rpx 36rpx rgba(214, 185, 167, 0.2));
    }

    &__action-text {
        font-size: 26rpx;
        font-weight: 600;
        color: var(--wm-text-primary, #1e2432);
    }
}
</style>
