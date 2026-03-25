<template>
    <page-meta :page-style="$theme.pageStyle" />

    <view class="dynamic-page page-with-tabbar-safe-bottom">
        <view class="dynamic-page__header">
            <view
                class="dynamic-page__status"
                :style="{ height: `${navBarMetrics.statusBarHeight}px` }"
            ></view>
            <view class="dynamic-page__nav" :style="{ height: `${navBarMetrics.contentHeight}px` }">
                <text class="dynamic-page__title">动态</text>
            </view>

            <view class="dynamic-page__filters">
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
                            <tn-icon name="close" size="18" color="#E85A4F" />
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

                <view
                    class="dynamic-page__sort-chip"
                    :class="{ 'is-active': sortIsActive }"
                    @click="showSortPicker = true"
                >
                    <tn-icon
                        name="sort"
                        size="22"
                        :color="sortIsActive ? '#E85A4F' : '#978B83'"
                    />
                    <text>{{ currentSortLabel }}</text>
                    <tn-icon
                        name="arrow-down"
                        size="18"
                        :color="sortIsActive ? '#E85A4F' : '#978B83'"
                    />
                </view>
            </view>
        </view>

        <view class="dynamic-page__body">
            <view v-if="loading && dynamics.length === 0" class="dynamic-page__loading">
                <tn-loading size="56" mode="flower" color="#E85A4F" />
                <text class="dynamic-page__loading-text">动态加载中...</text>
            </view>

            <view v-else-if="dynamics.length === 0" class="dynamic-page__empty">
                <view class="dynamic-page__empty-icon">
                    <tn-icon name="inbox" size="112" color="#D9CDC7" />
                </view>
                <text class="dynamic-page__empty-title">暂无符合条件的动态</text>
                <text class="dynamic-page__empty-desc">调整筛选条件后再试一次</text>
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
                    <text v-if="loading" class="dynamic-page__load-more-text">加载中...</text>
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

        <view
            v-if="showSortPicker"
            class="dynamic-page__picker-mask"
            :style="{
                zIndex: sortPopupMaskZIndex,
                background: $theme.maskColor || 'rgba(8, 10, 16, 0.58)'
            }"
            @tap="showSortPicker = false"
            @touchmove.stop.prevent="() => {}"
        ></view>

        <TnPopup
            v-model="showSortPicker"
            open-direction="bottom"
            :radius="28"
            :overlay="false"
            :overlay-closeable="true"
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
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { onLoad, onReachBottom, onShareAppMessage, onShow } from '@dcloudio/uni-app'
import TnPopup from '@tuniao/tnui-vue3-uniapp/components/popup/src/popup.vue'
import DynamicCard from '@/components/business/DynamicCard.vue'
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'
import { getDynamicList, likeDynamic } from '@/api/dynamic'
import { DYNAMIC_LIST_REFRESH_KEY } from '@/enums/constantEnums'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { mapDynamicItem } from '@/utils/dynamic'
import type { DynamicCardData } from '@/utils/dynamic'

const $theme = useThemeStore()
const userStore = useUserStore()
const navBarMetrics = useNavBarMetrics()
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
    if (options?.tag) {
        currentTag.value = decodeURIComponent(options.tag)
    }
})

onShow(() => {
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
    min-height: 100vh;
    background: $dynamic-bg;

    &__header {
        position: sticky;
        top: 0;
        z-index: 40;
        background: rgba(252, 251, 249, 0.96);
        backdrop-filter: blur(20rpx);
    }

    &__nav {
        display: flex;
        align-items: flex-end;
        padding: 0 40rpx 6rpx;
        box-sizing: border-box;
    }

    &__title {
        font-size: 50rpx;
        font-weight: 700;
        line-height: 1.05;
        color: $dynamic-text;
    }

    &__filters {
        display: flex;
        align-items: center;
        gap: 14rpx;
        padding: 20rpx 40rpx 0;
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
        gap: 14rpx;
        width: max-content;
        padding-right: 8rpx;
    }

    &__topic-chip {
        @include dynamic-pill($dynamic-surface-strong, $dynamic-text-secondary);
        gap: 8rpx;
        min-height: 70rpx;
        padding: 0 22rpx;
        flex-shrink: 0;

        &--active {
            background: $dynamic-accent-soft;
            border-color: rgba(232, 90, 79, 0.14);
            color: $dynamic-accent;
        }
    }

    &__topic-text {
        font-size: 24rpx;
        font-weight: 600;
        line-height: 1;
    }

    &__type-chip {
        @include dynamic-pill($dynamic-surface-strong, $dynamic-text-secondary);
        min-width: 104rpx;
        min-height: 70rpx;
        padding: 0 24rpx;
        flex-shrink: 0;
        transition: all 0.2s ease;

        &.is-active {
            color: #ffffff;
            border-color: $dynamic-accent;
            background: $dynamic-accent;
            box-shadow: $dynamic-shadow-accent;
        }
    }

    &__sort-chip {
        @include dynamic-pill($dynamic-surface-strong, $dynamic-text-muted);
        gap: 8rpx;
        min-width: 160rpx;
        min-height: 70rpx;
        padding: 0 22rpx;
        flex-shrink: 0;
        box-shadow: inset 0 1rpx 0 rgba(255, 255, 255, 0.6);

        text {
            font-size: 24rpx;
            line-height: 1;
            white-space: nowrap;
        }

        &.is-active {
            color: $dynamic-accent;
            border-color: rgba(232, 90, 79, 0.16);
            background: $dynamic-accent-soft;
        }
    }

    &__body {
        padding: 26rpx 40rpx 156rpx;
    }

    &__picker-mask {
        position: fixed;
        inset: 0;
        z-index: 20108;
    }

    &__loading,
    &__empty {
        min-height: 56vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    &__loading-text {
        margin-top: 22rpx;
        font-size: 26rpx;
        color: $dynamic-text-muted;
    }

    &__empty {
        padding: 0 40rpx;
    }

    &__empty-icon {
        margin-bottom: 24rpx;
    }

    &__empty-title {
        font-size: 32rpx;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__empty-desc {
        margin-top: 12rpx;
        font-size: 26rpx;
        color: $dynamic-text-muted;
    }

    &__empty-action {
        margin-top: 30rpx;
        min-width: 240rpx;
        height: 84rpx;
        padding: 0 36rpx;
        border-radius: $dynamic-radius-pill;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: $dynamic-accent;
        box-shadow: $dynamic-shadow-accent;
        color: #ffffff;
        font-size: 28rpx;
        font-weight: 600;
    }

    &__list {
        display: flex;
        flex-direction: column;
        gap: 22rpx;
    }

    &__load-more {
        padding: 24rpx 0 10rpx;
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
        padding: 20rpx 20rpx 28rpx;
        background: $dynamic-bg;
        border-radius: 28rpx 28rpx 0 0;
        box-shadow: 0 -18rpx 36rpx rgba(214, 185, 167, 0.22);
    }

    &__picker-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18rpx;
    }

    &__picker-title {
        font-size: 30rpx;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__picker-close {
        width: 56rpx;
        height: 56rpx;
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
        gap: 12rpx;
    }

    &__picker-item {
        height: 84rpx;
        border-radius: 20rpx;
        border: 1rpx solid $dynamic-border;
        background: rgba(255, 255, 255, 0.92);
        display: flex;
        align-items: center;
        justify-content: center;
        color: $dynamic-text-secondary;
        font-size: 26rpx;
        font-weight: 500;

        &.is-active {
            color: #ffffff;
            border-color: $dynamic-accent;
            background: $dynamic-accent;
            box-shadow: $dynamic-shadow-accent;
        }
    }
}

:deep(.tn-popup) {
    pointer-events: none;
}

:deep(.tn-popup__content) {
    pointer-events: auto;
}
</style>
