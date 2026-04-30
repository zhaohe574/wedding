<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasTabbar>
        <view class="news-page">
            <MpPageHeader title="婚礼资讯" surface="glass" title-align="left" title-size="large" />

            <view class="news-page__body">
                <view class="news-page__filters-shell">
                    <scroll-view
                        :scroll-x="true"
                        class="news-page__filter-scroll"
                        :show-scrollbar="false"
                    >
                        <view class="news-page__filter-track">
                            <view
                                v-for="(tab, index) in categoryTabs"
                                :key="`${tab.id}-${tab.name}`"
                                class="news-page__type-chip"
                                :class="{ 'is-active': currentCategoryIndex === index }"
                                @click="currentCategoryIndex = index"
                            >
                                {{ tab.name }}
                            </view>
                        </view>
                    </scroll-view>

                    <view class="news-page__filter-actions">
                        <view
                            v-if="showResetAction"
                            class="news-page__reset-chip"
                            @click="handleResetFilters"
                        >
                            重置
                        </view>
                        <view class="news-page__search-chip" @click="goSearch">
                            <tn-icon name="search" size="22" color="#4A4A4A" />
                        </view>
                        <view
                            class="news-page__sort-chip"
                            :class="{ 'is-active': sortIsActive }"
                            @click="showSortPicker = true"
                        >
                            <tn-icon
                                name="sort"
                                size="20"
                                :color="sortIsActive ? '#FFFFFF' : '#4A4A4A'"
                            />
                            <text>{{ currentSortLabel }}</text>
                            <tn-icon
                                name="arrow-down"
                                size="16"
                                :color="sortIsActive ? '#FFFFFF' : '#4A4A4A'"
                            />
                        </view>
                    </view>
                </view>

                <view class="news-page__content">
                    <view v-if="loading && articles.length === 0" class="news-page__loading">
                        <tn-loading size="52" mode="flower" color="#0B0B0B" />
                        <text class="news-page__loading-text">加载中...</text>
                    </view>

                    <view v-else-if="articles.length === 0" class="news-page__empty">
                        <view class="news-page__empty-icon">
                            <tn-icon name="inbox" size="96" color="#C8A45D" />
                        </view>
                        <text class="news-page__empty-title">暂无资讯</text>
                        <text class="news-page__empty-desc">换个分类或排序试试</text>
                        <view
                            v-if="showResetAction"
                            class="news-page__empty-action"
                            @click="handleResetFilters"
                        >
                            重置筛选
                        </view>
                    </view>

                    <view v-else class="news-page__list">
                        <NewsCard
                            v-for="item in articles"
                            :key="item.id"
                            :item="item"
                            :news-id="Number(item.id || 0)"
                            variant="editorial"
                        />

                        <view class="news-page__load-more">
                            <text v-if="loading" class="news-page__load-more-text">加载中...</text>
                            <text
                                v-else-if="hasMore"
                                class="news-page__load-more-text news-page__load-more-text--action"
                                @click="loadMore"
                            >
                                加载更多
                            </text>
                            <text v-else class="news-page__load-more-text">没有更多了</text>
                        </view>
                    </view>
                </view>
            </view>

            <BaseOverlayMask
                :show="showSortPicker"
                :z-index="sortPopupMaskZIndex"
                :background="$theme.maskColor || 'rgba(11, 11, 11, 0.58)'"
                @close="showSortPicker = false"
            />

            <TnPopup
                v-model="showSortPicker"
                open-direction="bottom"
                :radius="24"
                :overlay="false"
                :safe-area-inset-bottom="true"
                :z-index="sortPopupZIndex"
            >
                <view class="news-page__picker">
                    <view class="news-page__picker-head">
                        <text class="news-page__picker-title">排序方式</text>
                        <view class="news-page__picker-close" @click="showSortPicker = false">
                            <tn-icon name="close" size="30" color="#111111" />
                        </view>
                    </view>

                    <view class="news-page__picker-grid">
                        <view
                            v-for="item in sortOptions"
                            :key="item.value"
                            class="news-page__picker-item"
                            :class="{ 'is-active': currentSort === item.value }"
                            @click="selectSort(item.value)"
                        >
                            <view class="news-page__picker-item-mark"></view>
                            <text>{{ item.label }}</text>
                        </view>
                    </view>
                </view>
            </TnPopup>

            <tabbar :badge-refresh-key="tabbarRefreshKey" />
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { onLoad, onReachBottom, onShareAppMessage, onShow } from '@dcloudio/uni-app'
import TnPopup from '@tuniao/tnui-vue3-uniapp/components/popup/src/popup.vue'
import NewsCard from '@/components/news-card/news-card.vue'
import MpPageHeader from '@/components/base/MpPageHeader.vue'
import PageShell from '@/components/base/PageShell.vue'
import { getArticleCate, getArticleList } from '@/api/news'
import { useThemeStore } from '@/stores/theme'

interface ArticleCateItem {
    id: number | string
    name: string
}

interface ArticleItem {
    id: number | string
    title?: string
    desc?: string
    image?: string
    click?: number | string
    create_time?: string
    collect?: boolean
}

type ArticleSort = 'default' | 'new' | 'hot'

const $theme = useThemeStore()
const sortPopupMaskZIndex = 20108
const sortPopupZIndex = 20110
const pageSize = 10
const defaultCategory: ArticleCateItem = { name: '全部', id: '' }

const categoryTabs = ref<ArticleCateItem[]>([defaultCategory])
const currentCategoryIndex = ref(0)
const currentSort = ref<ArticleSort>('default')
const showSortPicker = ref(false)
const articles = ref<ArticleItem[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)
const hasInitialized = ref(false)
const tabbarRefreshKey = ref(0)

const sortOptions: { label: string; value: ArticleSort }[] = [
    { label: '综合推荐', value: 'default' },
    { label: '最新发布', value: 'new' },
    { label: '热门浏览', value: 'hot' }
]

const currentCategory = computed(
    () => categoryTabs.value[currentCategoryIndex.value] || defaultCategory
)
const sortIsActive = computed(() => currentSort.value !== 'default')
const showResetAction = computed(() => Boolean(currentCategory.value.id) || sortIsActive.value)
const currentSortOption = computed(
    () => sortOptions.find((item) => item.value === currentSort.value) || sortOptions[0]
)
const currentSortLabel = computed(() => currentSortOption.value.label)

const buildQueryParams = () => {
    const params: Record<string, any> = {
        page_no: page.value,
        page_size: pageSize,
        sort: currentSort.value
    }

    if (currentCategory.value.id !== '') {
        params.cid = currentCategory.value.id
    }

    return params
}

const loadCategories = async () => {
    try {
        const data = await getArticleCate()
        const list = Array.isArray(data) ? data : []
        categoryTabs.value = [defaultCategory].concat(list)

        if (currentCategoryIndex.value >= categoryTabs.value.length) {
            currentCategoryIndex.value = 0
        }
    } catch (error) {
        console.error(error)
        categoryTabs.value = [defaultCategory]
    }
}

const fetchArticles = async (refresh = false) => {
    if (loading.value) {
        return
    }

    loading.value = true
    try {
        if (refresh) {
            page.value = 1
            articles.value = []
        }

        const { lists = [] } = await getArticleList(buildQueryParams())
        const list = Array.isArray(lists) ? lists : []
        hasInitialized.value = true

        if (refresh) {
            articles.value = list
        } else {
            articles.value.push(...list)
        }

        hasMore.value = list.length === pageSize
    } catch (error) {
        console.error(error)
        if (refresh) {
            articles.value = []
        }
        hasMore.value = false
        hasInitialized.value = true
    } finally {
        loading.value = false
    }
}

const loadMore = () => {
    if (!hasMore.value || loading.value) {
        return
    }

    page.value += 1
    fetchArticles()
}

const handleResetFilters = () => {
    currentSort.value = 'default'
    showSortPicker.value = false

    if (currentCategoryIndex.value !== 0) {
        currentCategoryIndex.value = 0
        return
    }

    fetchArticles(true)
}

const selectSort = (sort: ArticleSort) => {
    currentSort.value = sort
    showSortPicker.value = false
    fetchArticles(true)
}

const goSearch = () => {
    uni.navigateTo({ url: '/pages/search/search?type=article' })
}

watch(currentCategoryIndex, () => {
    showSortPicker.value = false
    fetchArticles(true)
})

onLoad(() => {
    $theme.setScene('consumer')
    loadCategories()
})

onShow(() => {
    $theme.setScene('consumer')
    tabbarRefreshKey.value += 1
    showSortPicker.value = false

    if (!hasInitialized.value) {
        fetchArticles(true)
    }
})

onReachBottom(() => {
    loadMore()
})

onShareAppMessage(() => ({
    title: '婚礼资讯',
    path: '/pages/news/news'
}))
</script>

<style lang="scss" scoped>
@import '../../styles/dynamic.scss';

.news-page {
    --wm-space-page-x: 32rpx;
    --news-page-body-bottom: 32rpx;
    --news-page-panel-radius: 16rpx;
    --news-page-panel-border-width: 1rpx;
    --news-page-shell-bg: #ffffff;
    --news-page-shell-shadow: 0 8rpx 18rpx rgba(17, 17, 17, 0.04);

    position: relative;
    min-height: 100%;
    background: #ffffff;

    &::before {
        display: none;
    }

    &__body {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 24rpx;
        padding: 24rpx var(--wm-space-page-x, 32rpx) var(--news-page-body-bottom, 32rpx);
    }

    &__filters-shell,
    &__loading,
    &__empty {
        position: relative;
        overflow: hidden;
        border-radius: var(--news-page-panel-radius, 16rpx);
        border: var(--news-page-panel-border-width, 1rpx) solid var(--wm-color-border, #e5e5e5);
        background: var(--news-page-shell-bg, #ffffff);
        box-shadow: var(--news-page-shell-shadow, 0 8rpx 18rpx rgba(17, 17, 17, 0.04));
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    &__filters-shell {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 12rpx;
        padding: 12rpx 12rpx 12rpx 18rpx;
        border-radius: 999rpx;
        background: #ffffff;
        box-shadow: none;
    }

    &__filter-scroll {
        flex: 1;
        min-width: 0;
        white-space: nowrap;

        &::-webkit-scrollbar {
            display: none;
        }
    }

    &__filter-track {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        width: max-content;
        min-width: 0;
    }

    &__filter-actions {
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        flex-shrink: 0;
    }

    &__reset-chip,
    &__type-chip,
    &__sort-chip,
    &__search-chip {
        @include dynamic-pill(#ffffff, var(--wm-text-secondary, #4a4a4a));
        min-height: 52rpx;
        border-color: transparent;
        background: #ffffff;
        box-shadow: none;
        transition: all 0.2s ease;
    }

    &__reset-chip {
        padding: 0 16rpx;
        color: var(--wm-color-secondary, #c8a45d);
        font-size: 22rpx;
        font-weight: 600;
    }

    &__type-chip {
        min-width: auto;
        padding: 0 22rpx;
        font-size: 23rpx;
        font-weight: 600;
        flex-shrink: 0;

        &.is-active {
            color: #ffffff;
            border-color: $dynamic-accent;
            background: $dynamic-accent;
            box-shadow: none;
        }
    }

    &__search-chip {
        width: 52rpx;
        min-width: 52rpx;
        padding: 0;
        border-color: var(--wm-color-border, #e5e5e5);
        background: var(--wm-color-bg-soft, #f7f7f7);
    }

    &__sort-chip {
        gap: 8rpx;
        padding: 0 18rpx;
        flex-shrink: 0;
        border-color: var(--wm-color-border, #e5e5e5);
        background: var(--wm-color-bg-soft, #f7f7f7);

        text {
            font-size: 23rpx;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
        }

        &.is-active {
            color: #ffffff;
            border-color: $dynamic-accent;
            background: $dynamic-accent;
            box-shadow: none;
        }
    }

    &__content {
        position: relative;
        padding: 0;
        background: transparent;
        border: none;
        box-shadow: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        overflow: visible;
    }

    &__loading,
    &__empty {
        min-height: 42vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 96rpx 32rpx;
        background: #ffffff;
    }

    &__loading-text {
        margin-top: 20rpx;
        font-size: 25rpx;
        font-weight: 600;
        color: $dynamic-text-muted;
    }

    &__empty {
        text-align: center;
    }

    &__empty-icon {
        margin-bottom: 24rpx;
    }

    &__empty-title {
        font-size: 34rpx;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__empty-desc {
        margin-top: 10rpx;
        max-width: 420rpx;
        font-size: 24rpx;
        line-height: 1.6;
        color: $dynamic-text-muted;
    }

    &__empty-action {
        margin-top: 32rpx;
        min-width: 224rpx;
        height: 82rpx;
        padding: 0 34rpx;
        border-radius: $dynamic-radius-pill;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: $dynamic-accent;
        border: 1rpx solid $dynamic-accent;
        color: #ffffff;
        font-size: 26rpx;
        font-weight: 700;
        transition: all 0.2s ease;

        &:active {
            transform: translateY(1rpx);
            opacity: 0.92;
        }
    }

    &__list {
        display: flex;
        flex-direction: column;
        gap: 24rpx;
        --news-editorial-card-radius: 16rpx;
        --news-editorial-card-bg: #ffffff;
        --news-editorial-card-shadow: 0 8rpx 18rpx rgba(17, 17, 17, 0.04);
        --news-editorial-cover-height: 360rpx;
    }

    &__load-more {
        padding: 8rpx 0 10rpx;
        text-align: center;
    }

    &__load-more-text {
        font-size: 24rpx;
        color: $dynamic-text-muted;

        &--action {
            color: var(--wm-text-primary, #111111);
            font-weight: 600;
        }
    }

    &__picker {
        width: 100vw;
        max-width: 100vw;
        padding: 28rpx 28rpx 36rpx;
        background: #ffffff;
        border-radius: 24rpx 24rpx 0 0;
        border-top: 1rpx solid var(--wm-color-border, #e5e5e5);
        box-shadow: 0 -10rpx 24rpx rgba(17, 17, 17, 0.1);
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    &__picker-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18rpx;
    }

    &__picker-title {
        font-size: 32rpx;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__picker-close {
        width: 64rpx;
        height: 64rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: var(--wm-color-bg-soft, #f7f7f7);
        border: 1rpx solid var(--wm-color-border, #e5e5e5);
    }

    &__picker-grid {
        display: flex;
        flex-direction: column;
        gap: 0;
        border-top: 1rpx solid var(--wm-color-border, #e5e5e5);
    }

    &__picker-item {
        height: 96rpx;
        border-radius: 0;
        border: 0;
        border-bottom: 1rpx solid var(--wm-color-border, #e5e5e5);
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 18rpx;
        padding: 0 4rpx;
        color: $dynamic-text-secondary;
        font-size: 26rpx;
        font-weight: 600;

        &.is-active {
            color: $dynamic-text;
            background: #ffffff;
            box-shadow: none;
            font-weight: 700;
        }
    }

    &__picker-item-mark {
        width: 6rpx;
        height: 30rpx;
        border-radius: 999rpx;
        background: var(--wm-color-secondary, #c8a45d);
        opacity: 0;
    }

    &__picker-item.is-active &__picker-item-mark {
        opacity: 1;
    }
}

.news-page :deep(.tn-popup) {
    pointer-events: none;
}

.news-page :deep(.tn-popup__content) {
    pointer-events: auto;
}
</style>
