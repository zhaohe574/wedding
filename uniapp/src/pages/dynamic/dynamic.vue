<template>
    <page-meta :page-style="$theme.pageStyle" :scroll-enabled="false" />
    <PageShell
        scene="consumer"
        hasTabbar
        :shell-style="{ height: '100vh', overflow: 'hidden', boxSizing: 'border-box' }"
    >
        <view class="dynamic-page">
            <!-- 沉浸式导航头 -->
            <MpPageHeader
                title="动态广场"
                title-align="left"
                title-size="large"
                surface="dark"
                fixed
            />

            <view class="dynamic-page__body">
                <!-- 顶部筛选与分类综合面板 -->
                <view class="dynamic-page__control-shell">
                    <!-- 一级大类选项卡 (全部、图文、视频、活动) -->
                    <view class="dynamic-page__category-bar">
                        <view
                            v-for="(tab, index) in typeTabs"
                            :key="tab.label"
                            class="dynamic-page__category-tab"
                            :class="{ 'is-active': currentTypeIndex === index }"
                            @click="currentTypeIndex = index"
                        >
                            <text class="dynamic-page__category-label">{{ tab.label }}</text>
                            <view v-if="currentTypeIndex === index" class="dynamic-page__category-dot"></view>
                        </view>
                    </view>

                    <!-- 次级微筛选条 (已选标签、重置、排序方式) -->
                    <view class="dynamic-page__sub-filter-bar">
                        <view class="dynamic-page__sub-filter-left">
                            <view v-if="currentTag" class="dynamic-page__tag-chip" @click="clearTagFilter">
                                <text class="dynamic-page__tag-hash">#</text>
                                <text class="dynamic-page__tag-name">{{ currentTag }}</text>
                                <BaseIcon name="close" size="18" color="#9A6B35" />
                            </view>
                            <text v-else class="dynamic-page__sub-filter-hint">精选婚礼灵感与动态</text>
                        </view>

                        <view class="dynamic-page__sub-filter-right">
                            <view
                                v-if="showResetAction"
                                class="dynamic-page__reset-btn"
                                @click="handleResetFilters"
                            >
                                <BaseIcon name="refresh" size="20" color="#8C8273" />
                                <text>重置</text>
                            </view>
                            <view
                                class="dynamic-page__sort-btn"
                                :class="{ 'is-active': sortIsActive }"
                                @click="showSortPicker = true"
                            >
                                <BaseIcon
                                    name="sort"
                                    size="20"
                                    :color="sortIsActive ? '#C6A15B' : '#5E564B'"
                                />
                                <text class="dynamic-page__sort-text">{{ currentSortLabel }}</text>
                                <BaseIcon
                                    name="down"
                                    size="16"
                                    :color="sortIsActive ? '#C6A15B' : '#8C8273'"
                                />
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 滚动动态内容流 -->
                <scroll-view
                    class="dynamic-page__content-scroll"
                    scroll-y
                    :show-scrollbar="false"
                    refresher-enabled
                    :refresher-threshold="REFRESH_THRESHOLD"
                    refresher-default-style="none"
                    :refresher-triggered="refresherTriggered"
                    @refresherpulling="handleRefresherPulling"
                    @refresherrefresh="handleContentRefresh"
                    @refresherrestore="handleRefresherReset"
                    @refresherabort="handleRefresherReset"
                    @scrolltolower="loadMore"
                >
                    <view class="dynamic-page__content">
                        <!-- 加载骨架状态 -->
                        <view
                            v-if="loading && dynamics.length === 0"
                            class="dynamic-page__skeleton-list"
                        >
                            <view v-for="n in 3" :key="n" class="dynamic-page__skeleton-card">
                                <view class="dynamic-page__skeleton-header">
                                    <view class="dynamic-page__skeleton-avatar"></view>
                                    <view class="dynamic-page__skeleton-meta">
                                        <view class="dynamic-page__skeleton-line dynamic-page__skeleton-line--name"></view>
                                        <view class="dynamic-page__skeleton-line dynamic-page__skeleton-line--time"></view>
                                    </view>
                                </view>
                                <view class="dynamic-page__skeleton-body">
                                    <view class="dynamic-page__skeleton-line dynamic-page__skeleton-line--full"></view>
                                    <view class="dynamic-page__skeleton-line dynamic-page__skeleton-line--two-thirds"></view>
                                </view>
                                <view class="dynamic-page__skeleton-media"></view>
                            </view>
                        </view>

                        <!-- 空状态 -->
                        <BaseCard
                            v-else-if="dynamics.length === 0"
                            class="dynamic-page__empty-card"
                            variant="panel"
                        >
                            <view class="dynamic-page__empty-inner">
                                <EmptyState
                                    title="暂无相关动态"
                                    description="换个分类或筛选条件看看，或者稍后再来探索。"
                                    :action-text="showResetAction ? '恢复全部' : ''"
                                    compact
                                    @action="handleResetFilters"
                                />
                            </view>
                        </BaseCard>

                        <!-- 动态卡片列表 -->
                        <view v-else class="dynamic-page__list">
                            <DynamicCard
                                v-for="item in dynamics"
                                :key="item.id"
                                :dynamic="item"
                                variant="plaza-v2"
                                :show-share="true"
                                :show-comment="showDynamicComment"
                                @click="goDetail"
                                @like="handleLike"
                                @comment="goDetailWithComment"
                                @share="handleShareCard"
                                @topic-click="handleTopicFilter"
                            />

                            <!-- 底部加载更多 / 结束提示 -->
                            <view class="dynamic-page__load-more">
                                <view v-if="loading" class="dynamic-page__loading-pill">
                                    <view class="dynamic-page__spinner"></view>
                                    <text>加载精彩内容...</text>
                                </view>
                                <view
                                    v-else-if="hasMore"
                                    class="dynamic-page__load-more-btn"
                                    @click="loadMore"
                                >
                                    <text>点击加载更多</text>
                                    <BaseIcon name="down" size="18" color="#8C8273" />
                                </view>
                                <view v-else class="dynamic-page__end-ornament">
                                    <view class="dynamic-page__end-line"></view>
                                    <text class="dynamic-page__end-text">✦ 已为您呈现全部动态 ✦</text>
                                    <view class="dynamic-page__end-line"></view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <!-- 自定义下拉刷新反馈 -->
                    <view
                        slot="refresher"
                        class="dynamic-page__refresh-hint"
                        :class="{
                            'is-ready': isPullReady,
                            'is-refreshing': refresherTriggered
                        }"
                    >
                        <view class="dynamic-page__refresh-flower">
                            <BaseIcon
                                name="refresh"
                                size="24"
                                :color="isPullReady ? '#C6A15B' : '#8C8273'"
                            />
                        </view>
                        <text class="dynamic-page__refresh-text">{{ refreshHintText }}</text>
                    </view>
                </scroll-view>
            </view>

            <!-- 遮罩与排序选择器底部抽屉 -->
            <BaseOverlayMask
                :show="showSortPicker"
                :z-index="sortPopupMaskZIndex"
                :background="$theme.maskColor || 'rgba(24, 22, 20, 0.52)'"
                @close="showSortPicker = false"
            />

            <TnPopup
                v-model="showSortPicker"
                open-direction="bottom"
                :radius="32"
                :overlay="false"
                :safe-area-inset-bottom="true"
                :z-index="sortPopupZIndex"
            >
                <view class="dynamic-page__sort-drawer">
                    <view class="dynamic-page__sort-drawer-header">
                        <view class="dynamic-page__sort-drawer-title-box">
                            <text class="dynamic-page__sort-drawer-title">动态排序</text>
                            <text class="dynamic-page__sort-drawer-subtitle">选择您偏好的浏览顺序</text>
                        </view>
                        <view class="dynamic-page__sort-drawer-close" @click="showSortPicker = false">
                            <BaseIcon name="close" size="24" color="#8C8273" />
                        </view>
                    </view>

                    <view class="dynamic-page__sort-options">
                        <view
                            v-for="item in sortOptions"
                            :key="item.value"
                            class="dynamic-page__sort-option-card"
                            :class="{ 'is-selected': currentSort === item.value }"
                            @click="selectSort(item.value)"
                        >
                            <view class="dynamic-page__sort-option-main">
                                <text class="dynamic-page__sort-option-title">{{ item.label }}</text>
                                <text class="dynamic-page__sort-option-desc">{{ item.desc }}</text>
                            </view>
                            <view class="dynamic-page__sort-option-check">
                                <BaseIcon
                                    v-if="currentSort === item.value"
                                    name="check"
                                    size="24"
                                    color="#C6A15B"
                                />
                                <view v-else class="dynamic-page__sort-option-circle"></view>
                            </view>
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
import { onLoad, onShareAppMessage, onShow } from '@dcloudio/uni-app'
import TnPopup from '@tuniao/tnui-vue3-uniapp/components/popup/src/popup.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'
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
import {
    ensureMiniProgramReviewModeConfig,
    isMiniProgramReviewMode
} from '@/utils/miniProgramReviewMode'
import type { DynamicCardData } from '@/utils/dynamic'

const $theme = useThemeStore()
const userStore = useUserStore()
const sortPopupMaskZIndex = 20108
const sortPopupZIndex = 20110
const REFRESH_THRESHOLD = 80

const typeTabs = [
    { label: '全部', value: '' },
    { label: '图文', value: 1 },
    { label: '视频', value: 2 },
    { label: '活动', value: 4 }
]

const sortOptions = [
    { label: '最新发布', value: 'latest', orderBy: 'create_time', orderDir: 'desc', desc: '按发布时间倒序排列' },
    { label: '最多点赞', value: 'like', orderBy: 'like_count', orderDir: 'desc', desc: '精选高赞与人气内容' },
    { label: '最多评论', value: 'comment', orderBy: 'comment_count', orderDir: 'desc', desc: '热烈讨论与互动话题' },
    { label: '最多浏览', value: 'view', orderBy: 'view_count', orderDir: 'desc', desc: '大家都在看的热门动态' }
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
const refresherTriggered = ref(false)
const pullDistance = ref(0)
let skipNextTypeWatch = false

const currentType = computed(() => typeTabs[currentTypeIndex.value]?.value ?? '')
const sortIsActive = computed(() => currentSort.value !== 'latest')
const showResetAction = computed(
    () => Boolean(currentTag.value) || Boolean(currentType.value) || sortIsActive.value
)
const miniProgramReviewMode = computed(() => isMiniProgramReviewMode())
const showDynamicComment = computed(() => !miniProgramReviewMode.value)
const currentSortOption = computed(
    () => sortOptions.find((item) => item.value === currentSort.value) || sortOptions[0]
)
const currentSortLabel = computed(() => currentSortOption.value.label)
const isPullReady = computed(() => pullDistance.value >= REFRESH_THRESHOLD)
const refreshHintText = computed(() => {
    if (refresherTriggered.value) {
        return '正在同步最新灵感...'
    }
    return isPullReady.value ? '松开即刻刷新' : '下拉探索最新动态'
})

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

const handleRefresherPulling = (event: any) => {
    const distance = Number(event?.detail?.dy || 0)
    pullDistance.value = Math.max(0, distance)
}

const handleRefresherReset = () => {
    pullDistance.value = 0
}

const handleContentRefresh = async () => {
    if (refresherTriggered.value) {
        return
    }

    refresherTriggered.value = true
    pullDistance.value = REFRESH_THRESHOLD
    showSortPicker.value = false
    try {
        await fetchDynamics(true)
    } finally {
        refresherTriggered.value = false
        handleRefresherReset()
    }
}

const goDetail = (dynamic: DynamicCardData | number) => {
    const id = typeof dynamic === 'number' ? dynamic : dynamic?.id
    if (!id) {
        return
    }
    uni.navigateTo({ url: `/packages/pages/dynamic_detail/dynamic_detail?id=${id}` })
}

const goDetailWithComment = (dynamic: DynamicCardData | number) => {
    const id = typeof dynamic === 'number' ? dynamic : dynamic?.id
    if (!id) {
        return
    }
    uni.navigateTo({ url: `/packages/pages/dynamic_detail/dynamic_detail?id=${id}&action=comment` })
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

const handleShareCard = (dynamic: DynamicCardData) => {
    goDetail(dynamic)
}

const handleTopicFilter = (topic: { id: number; name: string }) => {
    if (topic?.name) {
        currentTag.value = topic.name
        fetchDynamics(true)
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
    ensureMiniProgramReviewModeConfig()
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

onShareAppMessage(() => ({
    title: '动态广场 - 精选婚礼灵感与真实故事',
    path: '/pages/dynamic/dynamic'
}))
</script>

<style lang="scss" scoped>
@import '../../styles/dynamic.scss';

.dynamic-page {
    --wm-space-page-x: 28rpx;
    --dynamic-page-body-bottom: 24rpx;

    position: relative;
    height: 100vh;
    min-height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: #F8F6F0;

    &__body {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 16rpx;
        flex: 1;
        min-height: 0;
        box-sizing: border-box;
        padding: 18rpx var(--wm-space-page-x, 28rpx) 0;
    }

    /* 顶部控制面板 */
    &__control-shell {
        border-radius: 28rpx;
        border: 1rpx solid rgba(231, 224, 211, 0.85);
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 12rpx 30rpx rgba(74, 43, 24, 0.05);
        padding: 12rpx 16rpx;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        gap: 12rpx;
    }

    &__category-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #FAF7F2;
        border-radius: 999rpx;
        padding: 6rpx;
        box-sizing: border-box;
        border: 1rpx solid rgba(231, 224, 211, 0.7);
    }

    &__category-tab {
        position: relative;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 64rpx;
        border-radius: 999rpx;
        transition: all 0.22s ease;

        &.is-active {
            background: #181614;
            box-shadow: 0 6rpx 16rpx rgba(24, 22, 20, 0.16);

            .dynamic-page__category-label {
                color: #FFFDF8;
                font-weight: 700;
            }
        }
    }

    &__category-label {
        font-size: 26rpx;
        font-weight: 500;
        color: #5E564B;
        line-height: 1;
    }

    &__category-dot {
        position: absolute;
        bottom: 8rpx;
        width: 8rpx;
        height: 8rpx;
        border-radius: 50%;
        background: #D9BE82;
    }

    &__sub-filter-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
        padding: 4rpx 6rpx 2rpx;
    }

    &__sub-filter-left {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
    }

    &__sub-filter-hint {
        font-size: 22rpx;
        color: #8C8273;
        line-height: 1;
    }

    &__sub-filter-right {
        display: inline-flex;
        align-items: center;
        gap: 10rpx;
        flex-shrink: 0;
    }

    &__tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        height: 48rpx;
        padding: 0 16rpx;
        border-radius: 999rpx;
        background: #FDF8ED;
        border: 1rpx solid rgba(217, 190, 130, 0.7);
        color: #9A6B35;
        font-size: 22rpx;
        line-height: 1;
        box-sizing: border-box;

        &:active {
            opacity: 0.85;
        }
    }

    &__tag-hash {
        color: #C6A15B;
        font-weight: 700;
    }

    &__tag-name {
        max-width: 220rpx;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 600;
    }

    &__reset-btn {
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        height: 48rpx;
        padding: 0 16rpx;
        border-radius: 999rpx;
        background: #FAF6EE;
        border: 1rpx solid rgba(231, 224, 211, 0.9);
        color: #5E564B;
        font-size: 22rpx;
        line-height: 1;

        &:active {
            background: #F2ECE1;
        }
    }

    &__sort-btn {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        height: 52rpx;
        padding: 0 18rpx;
        border-radius: 999rpx;
        background: #FFFFFF;
        border: 1rpx solid rgba(231, 224, 211, 0.9);
        color: #5E564B;
        font-size: 22rpx;
        font-weight: 600;
        line-height: 1;
        box-shadow: 0 4rpx 10rpx rgba(74, 43, 24, 0.04);
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.96);
        }

        &.is-active {
            border-color: rgba(217, 190, 130, 0.8);
            background: #FDF9F2;
            color: #C6A15B;
        }
    }

    /* 列表滚动容器 */
    &__content-scroll {
        flex: 1;
        min-height: 0;
        width: 100%;
        margin-top: 4rpx;
    }

    &__content {
        padding-bottom: 220rpx;
    }

    &__list {
        display: flex;
        flex-direction: column;
        gap: 24rpx;
    }

    /* 骨架屏 */
    &__skeleton-list {
        display: flex;
        flex-direction: column;
        gap: 24rpx;
    }

    &__skeleton-card {
        border-radius: 32rpx;
        background: #FFFFFF;
        border: 1rpx solid rgba(231, 224, 211, 0.6);
        padding: 28rpx;
        display: flex;
        flex-direction: column;
        gap: 20rpx;
    }

    &__skeleton-header {
        display: flex;
        align-items: center;
        gap: 16rpx;
    }

    &__skeleton-avatar {
        width: 76rpx;
        height: 76rpx;
        border-radius: 50%;
        background: linear-gradient(90deg, #FAF6EE 25%, #F2ECE1 50%, #FAF6EE 75%);
        background-size: 200% 100%;
        animation: skeletonShimmer 1.5s infinite;
    }

    &__skeleton-meta {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 10rpx;
    }

    &__skeleton-line {
        height: 22rpx;
        border-radius: 8rpx;
        background: linear-gradient(90deg, #FAF6EE 25%, #F2ECE1 50%, #FAF6EE 75%);
        background-size: 200% 100%;
        animation: skeletonShimmer 1.5s infinite;

        &--name {
            width: 180rpx;
            height: 26rpx;
        }

        &--time {
            width: 120rpx;
            height: 18rpx;
        }

        &--full {
            width: 100%;
        }

        &--two-thirds {
            width: 65%;
        }
    }

    &__skeleton-body {
        display: flex;
        flex-direction: column;
        gap: 12rpx;
    }

    &__skeleton-media {
        width: 100%;
        height: 320rpx;
        border-radius: 20rpx;
        background: linear-gradient(90deg, #FAF6EE 25%, #F2ECE1 50%, #FAF6EE 75%);
        background-size: 200% 100%;
        animation: skeletonShimmer 1.5s infinite;
    }

    @keyframes skeletonShimmer {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }

    /* 空状态 */
    &__empty-card {
        border-radius: 32rpx;
        border: 1rpx solid rgba(231, 224, 211, 0.85);
        background: #FFFFFF;
        padding: 48rpx 32rpx;
        box-shadow: 0 12rpx 30rpx rgba(74, 43, 24, 0.05);
    }

    /* 加载更多 */
    &__load-more {
        padding: 32rpx 0 16rpx;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    &__loading-pill {
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        padding: 12rpx 28rpx;
        border-radius: 999rpx;
        background: #FFFFFF;
        border: 1rpx solid rgba(231, 224, 211, 0.8);
        font-size: 22rpx;
        color: #8C8273;
        box-shadow: 0 6rpx 16rpx rgba(74, 43, 24, 0.04);
    }

    &__spinner {
        width: 24rpx;
        height: 24rpx;
        border: 3rpx solid rgba(217, 190, 130, 0.3);
        border-top-color: #C6A15B;
        border-radius: 50%;
        animation: spinnerRotate 0.8s linear infinite;
    }

    @keyframes spinnerRotate {
        to {
            transform: rotate(360deg);
        }
    }

    &__load-more-btn {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        padding: 12rpx 32rpx;
        border-radius: 999rpx;
        background: #FFFFFF;
        border: 1rpx solid rgba(231, 224, 211, 0.9);
        font-size: 23rpx;
        font-weight: 500;
        color: #5E564B;
        box-shadow: 0 4rpx 14rpx rgba(74, 43, 24, 0.04);
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.96);
            background: #FAF6EE;
        }
    }

    &__end-ornament {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20rpx;
        width: 100%;
        padding: 16rpx 0;
    }

    &__end-line {
        flex: 1;
        max-width: 120rpx;
        height: 1rpx;
        background: linear-gradient(90deg, transparent, rgba(217, 190, 130, 0.6), transparent);
    }

    &__end-text {
        font-size: 22rpx;
        color: #8C8273;
        letter-spacing: 2rpx;
    }

    /* 下拉刷新提示 */
    &__refresh-hint {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
        height: 80rpx;
        color: #8C8273;
        font-size: 23rpx;
    }

    &__refresh-flower {
        transition: transform 0.25s ease;

        .dynamic-page__refresh-hint.is-refreshing & {
            animation: spinnerRotate 0.9s linear infinite;
        }
    }

    /* 排序抽屉弹窗 */
    &__sort-drawer {
        background: #FFFFFF;
        border-radius: 32rpx 32rpx 0 0;
        padding: 36rpx 32rpx calc(24rpx + env(safe-area-inset-bottom));
        box-sizing: border-box;

        &-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 28rpx;
        }

        &-title {
            font-size: 32rpx;
            font-weight: 700;
            color: #181614;
            display: block;
        }

        &-subtitle {
            font-size: 22rpx;
            color: #8C8273;
            margin-top: 6rpx;
            display: block;
        }

        &-close {
            width: 60rpx;
            height: 60rpx;
            border-radius: 50%;
            background: #FAF6EE;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;

            &:active {
                background: #F2ECE1;
            }
        }
    }

    &__sort-options {
        display: flex;
        flex-direction: column;
        gap: 16rpx;
    }

    &__sort-option-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 24rpx 28rpx;
        border-radius: 22rpx;
        background: #FAF8F5;
        border: 1rpx solid rgba(231, 224, 211, 0.8);
        transition: all 0.2s ease;

        &:active {
            background: #F6EDE0;
        }

        &.is-selected {
            background: #FDF9F2;
            border-color: #D9BE82;
            box-shadow: 0 8rpx 20rpx rgba(217, 190, 130, 0.12);

            .dynamic-page__sort-option-title {
                color: #C6A15B;
                font-weight: 700;
            }
        }
    }

    &__sort-option-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__sort-option-title {
        font-size: 28rpx;
        font-weight: 600;
        color: #181614;
    }

    &__sort-option-desc {
        font-size: 22rpx;
        color: #8C8273;
    }

    &__sort-option-circle {
        width: 36rpx;
        height: 36rpx;
        border-radius: 50%;
        border: 2rpx solid #D8C9AD;
    }
}
</style>
