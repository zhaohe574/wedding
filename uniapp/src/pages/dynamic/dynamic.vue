<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasTabbar>
        <view class="dynamic-page">
            <MpPageHeader title="动态广场" title-align="left" title-size="large" fixed />

            <view class="dynamic-page__body">
                <BaseCard class="dynamic-page__filters-shell" variant="list" padding="10rpx">
                    <view class="dynamic-page__filter-toolbar">
                        <scroll-view
                            scroll-x
                            class="dynamic-page__filter-scroll"
                            :show-scrollbar="false"
                        >
                            <view class="dynamic-page__filter-track">
                                <view
                                    v-for="(tab, index) in typeTabs"
                                    :key="tab.label"
                                    class="dynamic-page__type-chip"
                                    :class="{ 'is-active': currentTypeIndex === index }"
                                    @click="currentTypeIndex = index"
                                >
                                    <text>{{ tab.label }}</text>
                                </view>
                            </view>
                        </scroll-view>

                        <view class="dynamic-page__filter-actions">
                            <view
                                v-if="showResetAction"
                                class="dynamic-page__reset-chip"
                                @click="handleResetFilters"
                            >
                                重置
                            </view>
                            <view
                                class="dynamic-page__sort-chip"
                                :class="{ 'is-active': sortIsActive }"
                                @click="showSortPicker = true"
                            >
                                <BaseIcon
                                    name="sort"
                                    size="20"
                                    :color="sortIsActive ? '#D9BE82' : '#9A6B35'"
                                />
                                <text>{{ currentSortLabel }}</text>
                                <BaseIcon
                                    name="down"
                                    size="18"
                                    :color="sortIsActive ? '#D9BE82' : '#9A6B35'"
                                />
                            </view>
                        </view>
                    </view>

                    <view v-if="currentTag" class="dynamic-page__tag-state">
                        <view class="dynamic-page__tag-chip" @click="clearTagFilter">
                            <text>#{{ currentTag }}</text>
                            <BaseIcon name="close" size="20" color="#9A6B35" />
                        </view>
                    </view>
                </BaseCard>

                <view class="dynamic-page__content">
                    <BaseCard
                        v-if="loading && dynamics.length === 0"
                        class="dynamic-page__state-card"
                        variant="panel"
                    >
                        <view class="dynamic-page__state-inner dynamic-page__state-inner--loading">
                            <LoadingState text="正在同步动态广场..." />
                        </view>
                    </BaseCard>

                    <BaseCard
                        v-else-if="dynamics.length === 0"
                        class="dynamic-page__state-card"
                        variant="panel"
                    >
                        <view class="dynamic-page__state-inner">
                            <EmptyState
                                title="暂无动态内容"
                                description="换个筛选条件，或稍后查看新的作品动态。"
                                :action-text="showResetAction ? '重置筛选' : ''"
                                compact
                                @action="handleResetFilters"
                            />
                        </view>
                    </BaseCard>

                    <view v-else class="dynamic-page__list">
                        <DynamicCard
                            v-for="item in dynamics"
                            :key="item.id"
                            :dynamic="item"
                            variant="plaza-v2"
                            :show-share="false"
                            @click="goDetail"
                            @like="handleLike"
                            @comment="goDetail"
                        />

                        <view class="dynamic-page__load-more">
                            <text v-if="loading" class="dynamic-page__load-more-text"
                                >加载中...</text
                            >
                            <text
                                v-else-if="hasMore"
                                class="dynamic-page__load-more-text dynamic-page__load-more-text--action"
                                @click="loadMore"
                            >
                                加载更多
                            </text>
                            <text v-else class="dynamic-page__load-more-text">没有更多了</text>
                        </view>
                    </view>
                </view>
            </view>

            <BaseOverlayMask
                :show="showSortPicker"
                :z-index="sortPopupMaskZIndex"
                :background="$theme.maskColor || 'rgba(25, 23, 19, 0.42)'"
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
                <view class="dynamic-page__picker">
                    <view class="dynamic-page__picker-head">
                        <text class="dynamic-page__picker-title">排序方式</text>
                        <view class="dynamic-page__picker-close" @click="showSortPicker = false">
                            <BaseIcon name="close" size="30" color="var(--wm-text-primary, #191713)" />
                        </view>
                    </view>

                    <view class="dynamic-page__picker-grid">
                        <view
                            v-for="item in sortOptions"
                            :key="item.value"
                            class="dynamic-page__picker-item"
                            :class="{ 'is-active': currentSort === item.value }"
                            @click="selectSort(item.value)"
                        >
                            <view class="dynamic-page__picker-item-mark"></view>
                            <text>{{ item.label }}</text>
                        </view>
                    </view>
                </view>
            </TnPopup>

            <tabbar :badge-refresh-key="tabbarRefreshKey" />
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { onLoad, onReachBottom, onShareAppMessage, onShow } from '@dcloudio/uni-app'
import TnPopup from '@tuniao/tnui-vue3-uniapp/components/popup/src/popup.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import MpPageHeader from '@/components/base/MpPageHeader.vue'
import DynamicCard from '@/components/business/DynamicCard.vue'
import PageShell from '@/components/base/PageShell.vue'
import { getDynamicList, likeDynamic } from '@/api/dynamic'
import { DYNAMIC_LIST_NAV_QUERY_KEY, DYNAMIC_LIST_REFRESH_KEY } from '@/enums/constantEnums'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { mapDynamicItem } from '@/utils/dynamic'
import { showError } from '@/utils/feedback'
import type { DynamicCardData } from '@/utils/dynamic'

const $theme = useThemeStore()
const userStore = useUserStore()
const sortPopupMaskZIndex = 20108
const sortPopupZIndex = 20110

const typeTabs = [
    { label: '全部', value: '' },
    { label: '图文', value: 1 },
    { label: '视频', value: 2 },
    { label: '活动', value: 4 }
]

const sortOptions = [
    { label: '最新发布', value: 'latest', orderBy: 'create_time', orderDir: 'desc' },
    { label: '最多点赞', value: 'like', orderBy: 'like_count', orderDir: 'desc' },
    { label: '最多评论', value: 'comment', orderBy: 'comment_count', orderDir: 'desc' },
    { label: '最多浏览', value: 'view', orderBy: 'view_count', orderDir: 'desc' }
]

const currentTypeIndex = ref(0)
const currentTag = ref('')
const currentSort = ref('latest')
const showSortPicker = ref(false)
const dynamics = ref<DynamicCardData[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)
const hasInitialized = ref(false)
const tabbarRefreshKey = ref(0)
let skipNextTypeWatch = false

const currentType = computed(() => typeTabs[currentTypeIndex.value]?.value ?? '')
const sortIsActive = computed(() => currentSort.value !== 'latest')
const showResetAction = computed(
    () => Boolean(currentTag.value) || Boolean(currentType.value) || sortIsActive.value
)
const currentSortOption = computed(
    () => sortOptions.find((item) => item.value === currentSort.value) || sortOptions[0]
)
const currentSortLabel = computed(() => currentSortOption.value.label)

const buildQueryParams = () => {
    const params: Record<string, any> = {
        page: page.value,
        page_size: 10,
        order_by: currentSortOption.value.orderBy,
        order_dir: currentSortOption.value.orderDir
    }

    if (currentType.value !== '') {
        params.dynamic_type = currentType.value
    }
    if (currentTag.value) {
        params.tag = currentTag.value
    }

    return params
}

const shouldRefreshOnShow = () => Boolean(cache.get(DYNAMIC_LIST_REFRESH_KEY))

const consumeRefreshFlag = () => {
    cache.remove(DYNAMIC_LIST_REFRESH_KEY)
}

const consumeNavigationQuery = () => {
    const query = cache.get(DYNAMIC_LIST_NAV_QUERY_KEY)
    if (query) {
        cache.remove(DYNAMIC_LIST_NAV_QUERY_KEY)
    }
    return query && typeof query === 'object' && !Array.isArray(query) ? query : null
}

const safeDecode = (value: unknown) => {
    const text = String(value ?? '')
    try {
        return decodeURIComponent(text)
    } catch (error) {
        return text
    }
}

const getTypeIndexByValue = (value: unknown) => {
    const matchedIndex = typeTabs.findIndex((item) => String(item.value) === String(value ?? ''))
    return matchedIndex >= 0 ? matchedIndex : 0
}

const applyNavigationQuery = (query: Record<string, any> | null | undefined) => {
    if (!query || typeof query !== 'object') {
        return false
    }

    const hasDynamicType =
        Object.prototype.hasOwnProperty.call(query, 'dynamic_type') ||
        Object.prototype.hasOwnProperty.call(query, 'type')
    const hasTag = Object.prototype.hasOwnProperty.call(query, 'tag')
    if (!hasDynamicType && !hasTag) {
        return false
    }

    if (hasDynamicType) {
        const nextTypeIndex = getTypeIndexByValue(query.dynamic_type ?? query.type)
        if (currentTypeIndex.value !== nextTypeIndex) {
            skipNextTypeWatch = true
            currentTypeIndex.value = nextTypeIndex
        }
    }
    if (hasTag) {
        currentTag.value = safeDecode(query.tag)
    }

    return true
}

const fetchDynamics = async (refresh = false) => {
    if (loading.value) {
        return
    }

    loading.value = true
    try {
        if (refresh) {
            page.value = 1
            dynamics.value = []
        }

        const res = await getDynamicList(buildQueryParams())
        const list = (res.data || []).map(mapDynamicItem)
        hasInitialized.value = true

        if (refresh) {
            dynamics.value = list
        } else {
            dynamics.value.push(...list)
        }

        hasMore.value = list.length === 10
    } catch (error) {
        console.error(error)
    } finally {
        loading.value = false
    }
}

const loadMore = () => {
    if (!hasMore.value || loading.value) {
        return
    }
    page.value += 1
    fetchDynamics()
}

const goDetail = (dynamic: DynamicCardData | number) => {
    const id = typeof dynamic === 'number' ? dynamic : dynamic?.id
    if (!id) {
        return
    }
    uni.navigateTo({ url: `/pages/dynamic_detail/dynamic_detail?id=${id}` })
}

const handleLike = async (dynamic: DynamicCardData) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }

    try {
        await likeDynamic({ id: dynamic.id })
        dynamic.isLiked = !dynamic.isLiked
        dynamic.likeCount += dynamic.isLiked ? 1 : -1
    } catch (error: any) {
        showError(error, '操作失败')
    }
}

const clearTagFilter = () => {
    currentTag.value = ''
    fetchDynamics(true)
}

const handleResetFilters = () => {
    currentTag.value = ''
    currentSort.value = 'latest'
    showSortPicker.value = false

    if (currentTypeIndex.value !== 0) {
        currentTypeIndex.value = 0
        return
    }

    fetchDynamics(true)
}

const selectSort = (sort: string) => {
    currentSort.value = sort
    showSortPicker.value = false
    fetchDynamics(true)
}

watch(currentTypeIndex, () => {
    if (skipNextTypeWatch) {
        skipNextTypeWatch = false
        return
    }
    showSortPicker.value = false
    fetchDynamics(true)
})

onLoad((options: any) => {
    $theme.setScene('consumer')
    applyNavigationQuery(options)
})

onShow(() => {
    $theme.setScene('consumer')
    tabbarRefreshKey.value += 1
    showSortPicker.value = false

    const navigationQuery = consumeNavigationQuery()
    if (applyNavigationQuery(navigationQuery)) {
        if (shouldRefreshOnShow()) {
            consumeRefreshFlag()
        }
        fetchDynamics(true)
        return
    }

    if (shouldRefreshOnShow()) {
        consumeRefreshFlag()
        fetchDynamics(true)
        return
    }

    if (!hasInitialized.value) {
        fetchDynamics(true)
    }
})

onReachBottom(() => {
    loadMore()
})

onShareAppMessage(() => ({
    title: '动态广场',
    path: '/pages/dynamic/dynamic'
}))
</script>

<style lang="scss" scoped>
@import '../../styles/dynamic.scss';

.dynamic-page {
    --wm-space-page-x: 32rpx;
    --dynamic-page-body-bottom: 32rpx;
    --dynamic-page-section-gap: 24rpx;
    --dynamic-page-panel-radius: 28rpx;
    --dynamic-page-panel-border-width: 1rpx;
    --dynamic-page-shell-bg: rgba(255, 253, 248, 0.96);
    --dynamic-page-shell-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));

    position: relative;
    min-height: 100%;
    background: var(--wm-color-bg-page, #FFFDF8);

    &::before {
        display: none;
    }

    &__body {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 24rpx;
        padding: 24rpx var(--wm-space-page-x, 32rpx) var(--dynamic-page-body-bottom, 32rpx);
    }
    &__filters-shell,
    &__state-card {
        position: relative;
        overflow: hidden;
        border-radius: var(--dynamic-page-panel-radius, 16rpx);
        border: var(--dynamic-page-panel-border-width, 1rpx) solid var(--wm-color-border, #D8C9AD);
        background: var(--dynamic-page-shell-bg, rgba(255, 253, 248, 0.96));
        box-shadow: var(--dynamic-page-shell-shadow, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    &__filters-shell {
        --wm-space-list-panel-y: 10rpx;
        --wm-space-list-panel-x: 10rpx;

        display: block;
        border-radius: 999rpx;
        background: rgba(255, 253, 248, 0.98);
        box-shadow: var(--wm-shadow-soft, 0 12rpx 30rpx rgba(74, 43, 24, 0.06));
    }

    &__filter-toolbar {
        display: flex;
        align-items: center;
        gap: 12rpx;
        width: 100%;
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
        gap: 10rpx;
        flex-shrink: 0;
    }

    &__type-chip,
    &__reset-chip,
    &__sort-chip,
    &__tag-chip {
        min-height: 56rpx;
        border-radius: var(--wm-radius-pill, 999rpx);
        border: 1rpx solid rgba(216, 201, 173, 0.86);
        background: rgba(255, 253, 248, 0.92);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--wm-text-secondary, #665E52);
        box-sizing: border-box;
    }

    &__type-chip {
        flex-shrink: 0;
        padding: 0 22rpx;

        text {
            font-size: 23rpx;
            line-height: 1;
            font-weight: 900;
            white-space: nowrap;
        }

        &.is-active {
            color: var(--wm-text-inverse, #FFFDF8);
            border-color: var(--wm-color-champagne, #D9BE82);
            background: var(--wm-color-primary, #191713);
            box-shadow: var(--wm-shadow-action, 0 16rpx 36rpx rgba(74, 43, 24, 0.14));
        }
    }

    &__reset-chip {
        padding: 0 18rpx;
        color: #9A6B35;
        font-size: 22rpx;
        font-weight: 900;
        white-space: nowrap;
        background: rgba(250, 246, 238, 0.92);
    }

    &__sort-chip {
        gap: 8rpx;
        padding: 0 18rpx;
        flex-shrink: 0;
        background: rgba(250, 246, 238, 0.94);

        text {
            max-width: 132rpx;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 22rpx;
            line-height: 1;
            font-weight: 900;
        }

        &.is-active {
            color: var(--wm-color-champagne, #D9BE82);
            border-color: var(--wm-color-champagne, #D9BE82);
            background: var(--wm-color-primary, #191713);
            box-shadow: var(--wm-shadow-action, 0 16rpx 36rpx rgba(74, 43, 24, 0.14));
        }
    }

    &__tag-state {
        display: flex;
        align-items: center;
        margin-top: 10rpx;
        padding: 0 4rpx 2rpx;
    }

    &__tag-chip {
        gap: 8rpx;
        min-height: 48rpx;
        padding: 0 16rpx;
        color: #9A6B35;
        background: rgba(247, 240, 223, 0.84);

        text {
            max-width: 420rpx;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 22rpx;
            line-height: 1;
            font-weight: 900;
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

    &__state-card {
        --wm-space-card-padding-lg: 18rpx;

        min-height: 520rpx;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: center;
        border-radius: 44rpx;
        border-color: rgba(216, 201, 173, 0.9);
        background: linear-gradient(180deg, rgba(255, 253, 248, 0.98) 0%, rgba(250, 246, 238, 0.9) 100%);
        box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    }

    &__state-inner {
        width: 100%;
        border-radius: 34rpx;
    }

    &__state-inner :deep(.empty-state-block) {
        min-height: 300rpx;
        border-radius: 34rpx;
        background: rgba(255, 253, 248, 0.78);
        box-shadow: none;
    }

    &__state-inner--loading {
        min-height: 300rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 34rpx;
        border: 1rpx solid var(--wm-color-border, #D8C9AD);
        background: rgba(255, 253, 248, 0.78);
    }

    &__list {
        display: flex;
        flex-direction: column;
        gap: 24rpx;
        --dynamic-plaza-card-radius: 36rpx;
        --dynamic-plaza-card-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    }

    &__load-more {
        padding: 8rpx 0 10rpx;
        text-align: center;
    }

    &__load-more-text {
        font-size: 24rpx;
        color: $dynamic-text-muted;

        &--action {
            color: var(--wm-text-primary, #191713);
            font-weight: 600;
        }
    }

    &__picker {
        width: 100vw;
        max-width: 100vw;
        padding: 34rpx 32rpx 42rpx;
        background: var(--wm-color-bg-card, #FFFDF8);
        border-radius: var(--wm-radius-popup, 44rpx) var(--wm-radius-popup, 44rpx) 0 0;
        border-top: 1rpx solid var(--wm-color-border, #D8C9AD);
        box-shadow: var(--wm-shadow-floating, 0 24rpx 56rpx rgba(74, 43, 24, 0.16));
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
        font-size: 34rpx;
        font-weight: 900;
        color: var(--wm-text-primary, #191713);
    }

    &__picker-close {
        width: 64rpx;
        height: 64rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: var(--wm-color-bg-soft, #FAF6EE);
        border: 1rpx solid var(--wm-color-border, #D8C9AD);
    }

    &__picker-grid {
        display: flex;
        flex-direction: column;
        gap: 0;
        padding: 0 4rpx;
        border-radius: 28rpx;
        border-top: 1rpx solid var(--wm-color-border, #D8C9AD);
        background: linear-gradient(180deg, rgba(255, 253, 248, 0.98) 0%, rgba(250, 246, 238, 0.88) 100%);
    }

    &__picker-item {
        height: 96rpx;
        border-radius: 0;
        border: 0;
        border-bottom: 1rpx solid var(--wm-color-border, #D8C9AD);
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 18rpx;
        padding: 0 4rpx;
        color: var(--wm-text-secondary, #665E52);
        font-size: 26rpx;
        font-weight: 800;

        &.is-active {
            color: var(--wm-text-primary, #191713);
            background: var(--wm-color-bg-soft, #FAF6EE);
            box-shadow: none;
            font-weight: 900;
        }
    }

    &__picker-item:first-child {
        border-top-left-radius: 28rpx;
        border-top-right-radius: 28rpx;
    }

    &__picker-item:last-child {
        border-bottom: 0;
        border-bottom-left-radius: 28rpx;
        border-bottom-right-radius: 28rpx;
    }

    &__picker-item-mark {
        width: 6rpx;
        height: 30rpx;
        border-radius: 999rpx;
        background: var(--wm-color-gold, #B8954A);
        opacity: 0;
    }

    &__picker-item.is-active &__picker-item-mark {
        opacity: 1;
    }
}

.dynamic-page :deep(.tn-popup) {
    pointer-events: none;
}

.dynamic-page :deep(.tn-popup__content) {
    pointer-events: auto;
}
</style>
