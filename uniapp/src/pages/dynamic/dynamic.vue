<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasTabbar>
        <view class="dynamic-page">
            <MpPageHeader title="动态广场" surface="glass" title-align="left" title-size="large" />

            <view class="dynamic-page__body">
                <view class="dynamic-page__filters-shell">
                    <scroll-view
                        :scroll-x="true"
                        class="dynamic-page__filter-scroll"
                        :show-scrollbar="false"
                    >
                        <view class="dynamic-page__filter-track">
                            <view
                                v-if="currentTag"
                                class="dynamic-page__topic-chip dynamic-page__topic-chip--active"
                                @click="clearTagFilter"
                            >
                                <text class="dynamic-page__topic-text">#{{ currentTag }}</text>
                                <tn-icon name="close" size="16" color="#FFFFFF" />
                            </view>

                            <view
                                v-for="(tab, index) in typeTabs"
                                :key="tab.label"
                                class="dynamic-page__type-chip"
                                :class="{ 'is-active': currentTypeIndex === index }"
                                @click="currentTypeIndex = index"
                            >
                                {{ tab.label }}
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
                            <tn-icon
                                name="sort"
                                size="20"
                                :color="sortIsActive ? '#E85A4F' : '#6A5B52'"
                            />
                            <text>{{ currentSortLabel }}</text>
                            <tn-icon
                                name="arrow-down"
                                size="16"
                                :color="sortIsActive ? '#E85A4F' : '#6A5B52'"
                            />
                        </view>
                    </view>
                </view>

                <view class="dynamic-page__content">
                    <view v-if="loading && dynamics.length === 0" class="dynamic-page__loading">
                        <tn-loading size="52" mode="flower" color="#E85A4F" />
                        <text class="dynamic-page__loading-text">加载中...</text>
                    </view>

                    <view v-else-if="dynamics.length === 0" class="dynamic-page__empty">
                        <view class="dynamic-page__empty-icon">
                            <tn-icon name="inbox" size="96" color="#D9CDC7" />
                        </view>
                        <text class="dynamic-page__empty-title">暂无内容</text>
                        <text class="dynamic-page__empty-desc">换个筛选试试</text>
                        <view
                            v-if="showResetAction"
                            class="dynamic-page__empty-action"
                            @click="handleResetFilters"
                        >
                            重置筛选
                        </view>
                    </view>

                    <view v-else class="dynamic-page__list">
                        <DynamicCard
                            v-for="item in dynamics"
                            :key="item.id"
                            :dynamic="item"
                            variant="editorial"
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
                :background="$theme.maskColor || 'rgba(8, 10, 16, 0.58)'"
                @close="showSortPicker = false"
            />

            <TnPopup
                v-model="showSortPicker"
                open-direction="bottom"
                :radius="44"
                :overlay="false"
                :safe-area-inset-bottom="true"
                :z-index="sortPopupZIndex"
            >
                <view class="dynamic-page__picker">
                    <view class="dynamic-page__picker-head">
                        <text class="dynamic-page__picker-title">排序方式</text>
                        <view class="dynamic-page__picker-close" @click="showSortPicker = false">
                            <tn-icon name="close" size="30" color="#978B83" />
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
                            {{ item.label }}
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
import MpPageHeader from '@/components/base/MpPageHeader.vue'
import DynamicCard from '@/components/business/DynamicCard.vue'
import PageShell from '@/components/base/PageShell.vue'
import { getDynamicList, likeDynamic } from '@/api/dynamic'
import { DYNAMIC_LIST_REFRESH_KEY } from '@/enums/constantEnums'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { mapDynamicItem } from '@/utils/dynamic'
import type { DynamicCardData } from '@/utils/dynamic'

const $theme = useThemeStore()
const userStore = useUserStore()
const sortPopupMaskZIndex = 20108
const sortPopupZIndex = 20110

const typeTabs = [
    { label: '全部', value: '' },
    { label: '图文', value: 1 },
    { label: '视频', value: 2 },
    { label: '案例', value: 3 },
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
        uni.showToast({ title: error?.message || error || '操作失败', icon: 'none' })
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
    showSortPicker.value = false
    fetchDynamics(true)
})

onLoad((options: any) => {
    $theme.setScene('consumer')
    if (options?.tag) {
        currentTag.value = decodeURIComponent(options.tag)
    }
})

onShow(() => {
    $theme.setScene('consumer')
    tabbarRefreshKey.value += 1
    showSortPicker.value = false

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
    title: '婚庆服务动态广场',
    path: '/pages/dynamic/dynamic'
}))
</script>

<style lang="scss" scoped>
@import '../../styles/dynamic.scss';

.dynamic-page {
    --wm-space-page-x: 37rpx;
    --dynamic-page-body-bottom: 37rpx;
    --dynamic-page-section-gap: 26rpx;
    --dynamic-page-panel-radius: 44rpx;
    --dynamic-page-panel-border-width: 2rpx;
    --dynamic-page-shell-bg: rgba(255, 255, 255, 0.78);
    --dynamic-page-shell-shadow: 0 18rpx 38rpx rgba(214, 185, 167, 0.1);

    position: relative;
    min-height: 100%;
    background: radial-gradient(circle at top right, rgba(232, 90, 79, 0.12) 0, transparent 34%),
        linear-gradient(180deg, rgba(255, 248, 245, 0.92) 0%, rgba(252, 251, 249, 0) 30%),
        $dynamic-bg;

    &::before {
        content: '';
        position: absolute;
        top: 78rpx;
        right: -88rpx;
        width: 280rpx;
        height: 280rpx;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(232, 90, 79, 0.1) 0, transparent 72%);
        pointer-events: none;
        opacity: 0.88;
    }

    &__body {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 18rpx;
        padding: 6rpx var(--wm-space-page-x, 37rpx) var(--dynamic-page-body-bottom, 37rpx);
    }
    &__filters-shell,
    &__loading,
    &__empty {
        position: relative;
        overflow: hidden;
        border-radius: var(--dynamic-page-panel-radius, 44rpx);
        border: var(--dynamic-page-panel-border-width, 2rpx) solid rgba(239, 230, 225, 0.9);
        background: var(--dynamic-page-shell-bg, rgba(255, 255, 255, 0.78));
        box-shadow: var(--dynamic-page-shell-shadow, 0 18rpx 38rpx rgba(214, 185, 167, 0.1));
        backdrop-filter: blur(18rpx);
        -webkit-backdrop-filter: blur(18rpx);
    }

    &__filters-shell {
        display: flex;
        flex-direction: column;
        gap: 14rpx;
        padding: 18rpx 22rpx;
        background: rgba(255, 255, 255, 0.7);
    }

    &__filter-actions {
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        flex-shrink: 0;
    }

    &__reset-chip {
        @include dynamic-pill(rgba(255, 255, 255, 0.68), $dynamic-text-secondary);
        min-height: 64rpx;
        padding: 0 20rpx;
        background: rgba(255, 255, 255, 0.62);
        font-size: 24rpx;
        font-weight: 600;
    }

    &__filter-scroll {
        white-space: nowrap;

        &::-webkit-scrollbar {
            display: none;
        }
    }

    &__filter-track {
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        width: max-content;
        min-width: 100%;
        padding-right: 8rpx;
    }

    &__topic-chip,
    &__type-chip,
    &__sort-chip {
        @include dynamic-pill(rgba(255, 255, 255, 0.68), $dynamic-text-secondary);
        min-height: 68rpx;
        border-color: rgba(239, 230, 225, 0.92);
        background: rgba(255, 255, 255, 0.66);
        box-shadow: none;
        transition: all 0.2s ease;
    }

    &__topic-chip,
    &__type-chip {
        padding: 0 22rpx;
    }

    &__topic-chip {
        gap: 8rpx;
        flex-shrink: 0;

        &--active {
            background: $dynamic-accent;
            border-color: transparent;
            color: #ffffff;
            box-shadow: 0 12rpx 22rpx rgba(232, 90, 79, 0.16);
        }
    }

    &__topic-text {
        font-size: 24rpx;
        font-weight: 700;
        line-height: 1;
    }

    &__type-chip {
        min-width: 108rpx;
        font-size: 24rpx;
        font-weight: 600;
        flex-shrink: 0;

        &.is-active {
            color: $dynamic-accent;
            border-color: rgba(232, 90, 79, 0.12);
            background: rgba(255, 241, 238, 0.92);
        }
    }

    &__sort-chip {
        gap: 8rpx;
        padding: 0 20rpx;
        flex-shrink: 0;

        text {
            font-size: 24rpx;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
        }

        &.is-active {
            color: $dynamic-accent;
            border-color: rgba(232, 90, 79, 0.14);
            background: rgba(255, 241, 238, 0.92);
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
        background: rgba(255, 255, 255, 0.7);
        box-shadow: none;
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
        background: rgba(255, 241, 238, 0.96);
        border: 2rpx solid rgba(232, 90, 79, 0.12);
        color: $dynamic-accent;
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
        --dynamic-editorial-card-radius: 42rpx;
        --dynamic-editorial-card-bg: rgba(255, 255, 255, 0.9);
        --dynamic-editorial-card-shadow: 0 26rpx 52rpx rgba(214, 185, 167, 0.12);
        --dynamic-editorial-card-blur: 18rpx;
        --dynamic-editorial-head-padding: 24rpx 24rpx 0;
        --dynamic-editorial-author-gap: 14rpx;
        --dynamic-editorial-avatar-size: 54rpx;
        --dynamic-editorial-name-size: 26rpx;
        --dynamic-editorial-meta-margin-top: 8rpx;
        --dynamic-editorial-meta-size: 22rpx;
        --dynamic-editorial-cover-margin: 18rpx 24rpx 0;
        --dynamic-editorial-cover-radius: 30rpx;
        --dynamic-editorial-cover-height: 340rpx;
        --dynamic-editorial-video-badge-offset: 18rpx;
        --dynamic-editorial-video-badge-padding: 8rpx 14rpx;
        --dynamic-editorial-content-padding: 18rpx 24rpx 0;
        --dynamic-editorial-content-size: 31rpx;
        --dynamic-editorial-content-line-height: 1.58;
        --dynamic-editorial-content-clamp: 4;
        --dynamic-editorial-stats-gap: 20rpx;
        --dynamic-editorial-stats-padding: 14rpx 24rpx 24rpx;
        --dynamic-editorial-stat-size: 22rpx;
    }

    &__load-more {
        padding: 8rpx 0 10rpx;
        text-align: center;
    }

    &__load-more-text {
        font-size: 24rpx;
        color: $dynamic-text-muted;

        &--action {
            color: $dynamic-accent;
            font-weight: 600;
        }
    }

    &__picker {
        width: 100vw;
        max-width: 100vw;
        padding: 28rpx 28rpx 36rpx;
        background: rgba(252, 251, 249, 0.98);
        border-radius: 44rpx 44rpx 0 0;
        border-top: 1rpx solid rgba(232, 222, 216, 0.76);
        box-shadow: 0 -18rpx 36rpx rgba(214, 185, 167, 0.18);
        backdrop-filter: blur(16rpx);
        -webkit-backdrop-filter: blur(16rpx);
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
        background: rgba(255, 255, 255, 0.9);
        border: 1rpx solid rgba(232, 222, 216, 0.7);
    }

    &__picker-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16rpx;
    }

    &__picker-item {
        height: 88rpx;
        border-radius: 32rpx;
        border: 1rpx solid $dynamic-border;
        background: rgba(255, 255, 255, 0.88);
        display: flex;
        align-items: center;
        justify-content: center;
        color: $dynamic-text-secondary;
        font-size: 26rpx;
        font-weight: 600;

        &.is-active {
            color: #ffffff;
            border-color: transparent;
            background: $dynamic-accent;
            box-shadow: 0 14rpx 24rpx rgba(232, 90, 79, 0.18);
        }
    }
}

.dynamic-page :deep(.tn-popup) {
    pointer-events: none;
}

.dynamic-page :deep(.tn-popup__content) {
    pointer-events: auto;
}
</style>
