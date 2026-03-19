<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            title="动态广场"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
        <!-- #endif -->
    </page-meta>

    <view class="dynamic-page page-with-tabbar-safe-bottom">
        <view class="filter-header">
            <view v-if="currentTag" class="tag-filter-banner" :style="tagBannerStyle">
                <view class="tag-filter-banner__icon">
                    <tn-icon name="fire" size="28" color="#FFFFFF" />
                </view>
                <view class="tag-filter-banner__content">
                    <text class="tag-filter-banner__label">当前话题</text>
                    <text class="tag-filter-banner__text">#{{ currentTag }}</text>
                </view>
                <view class="tag-filter-banner__clear" @click="clearTagFilter">
                    <tn-icon name="close" size="28" color="#FFFFFF" />
                </view>
            </view>

            <view class="type-scroll-wrapper">
                <scroll-view :scroll-x="true" class="type-scroll" :show-scrollbar="false">
                    <view class="type-scroll-content">
                        <view
                            v-for="(tab, index) in typeTabs"
                            :key="tab.label"
                            class="type-chip"
                            :class="{ active: currentTypeIndex === index }"
                            :style="currentTypeIndex === index ? getActiveTypeChipStyle() : {}"
                            @click="currentTypeIndex = index"
                        >
                            {{ tab.label }}
                        </view>
                    </view>
                </scroll-view>
                <view
                    class="filter-item filter-item--sort"
                    :style="sortIsActive ? getFilterItemActiveStyle() : {}"
                    @click="showSortPicker = true"
                >
                    <tn-icon
                        name="sort"
                        size="24"
                        :color="sortIsActive ? $theme.primaryColor : '#666666'"
                    />
                    <text
                        :class="{ active: sortIsActive }"
                        :style="sortIsActive ? { color: $theme.primaryColor } : {}"
                    >
                        {{ currentSortLabel }}
                    </text>
                    <tn-icon
                        name="arrow-down"
                        size="20"
                        :color="sortIsActive ? $theme.primaryColor : '#999999'"
                    />
                </view>
            </view>
        </view>

        <view class="dynamic-feed">
            <view v-if="loading && dynamics.length === 0" class="loading-state">
                <tn-loading size="60" mode="flower" />
                <text class="loading-text">动态加载中...</text>
            </view>

            <view v-else-if="dynamics.length === 0" class="empty-state">
                <view class="empty-icon-wrap">
                    <tn-icon name="inbox" size="150" color="#D1D5DB" />
                </view>
                <text class="empty-title">暂无符合条件的动态</text>
                <text class="empty-subtitle">调整筛选后重试</text>
                <view
                    v-if="showResetAction"
                    class="empty-action-btn"
                    :style="{
                        background: getPrimaryGradient(),
                        boxShadow: getPrimaryShadow(0.26)
                    }"
                    @click="handleResetFilters"
                >
                    <text class="empty-action-text" :style="{ color: $theme.btnColor }">
                        重置筛选
                    </text>
                </view>
            </view>

            <view v-else class="dynamic-list">
                <dynamic-card
                    v-for="item in dynamics"
                    :key="item.id"
                    :dynamic="item"
                    variant="plaza-unified"
                    :show-share="false"
                    @click="goDetail"
                    @like="handleLike"
                    @comment="goDetail"
                    @favorite="handleFavorite"
                />

                <view v-if="hasMore" class="load-more">
                    <text v-if="loading" class="load-more-text">加载中...</text>
                    <text v-else class="load-more-text load-more-clickable" @click="loadMore">
                        加载更多
                    </text>
                </view>
                <view v-else class="load-more">
                    <text class="load-more-text">没有更多了</text>
                </view>
            </view>
        </view>

        <TnPopup
            v-model="showSortPicker"
            open-direction="bottom"
            :radius="24"
            :safe-area-inset-bottom="true"
        >
            <view class="picker-container">
                <view class="picker-header">
                    <text class="picker-title">选择排序</text>
                    <view class="picker-close" @click="showSortPicker = false">
                        <tn-icon name="close" size="32" color="#666666" />
                    </view>
                </view>
                <view class="button-picker-content">
                    <view class="button-grid">
                        <view
                            v-for="item in sortOptions"
                            :key="item.value"
                            class="button-item"
                            :class="{ active: currentSort === item.value }"
                            :style="
                                currentSort === item.value
                                    ? {
                                          background: getPrimaryGradient(),
                                          color: '#FFFFFF',
                                          boxShadow: getPrimaryShadow(0.2)
                                      }
                                    : {}
                            "
                            @click="selectSort(item.value)"
                        >
                            {{ item.label }}
                        </view>
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
import { getDynamicList, likeDynamic } from '@/api/dynamic'
import { toggleStaffFavorite } from '@/api/staff'
import { DYNAMIC_LIST_REFRESH_KEY } from '@/enums/constantEnums'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import cache from '@/utils/cache'
import { alphaColor } from '@/utils/color'
import { mapDynamicItem } from '@/utils/dynamic'
import type { DynamicCardData } from '@/utils/dynamic'

const $theme = useThemeStore()
const userStore = useUserStore()

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

const getPrimaryGradient = () =>
    `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`

const getPrimaryShadow = (alpha = 0.2) => `0 8rpx 24rpx ${alphaColor($theme.primaryColor, alpha)}`

const getTypeChipActiveShadow = () => `0 2rpx 8rpx ${alphaColor($theme.primaryColor, 0.14)}`

const getFilterItemActiveStyle = () => ({
    background: alphaColor($theme.primaryColor, 0.1),
    borderColor: alphaColor($theme.primaryColor, 0.32),
    boxShadow: `0 6rpx 14rpx ${alphaColor($theme.primaryColor, 0.12)}`
})

const getActiveTypeChipStyle = () => ({
    background: getPrimaryGradient(),
    borderColor: $theme.primaryColor,
    color: '#FFFFFF',
    boxShadow: getTypeChipActiveShadow()
})

const tagBannerStyle = computed(() => ({
    background: getPrimaryGradient(),
    boxShadow: getPrimaryShadow(0.2)
}))

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
    } catch (e) {
        console.error(e)
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
    } catch (e: any) {
        uni.showToast({ title: e?.message || e || '操作失败', icon: 'none' })
    }
}

const handleFavorite = async (staffId: number) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }

    const target = dynamics.value.find((item) => item.user.staffId === staffId)
    if (!target?.user.canFavorite) {
        return
    }

    try {
        const isFavorite = !target.user.isFavorite
        await toggleStaffFavorite({ id: staffId })

        dynamics.value.forEach((item) => {
            if (item.user.staffId === staffId) {
                item.user.isFavorite = isFavorite
            }
        })

        uni.showToast({
            title: isFavorite ? '收藏成功' : '已取消收藏',
            icon: 'none'
        })
    } catch (e: any) {
        uni.showToast({ title: e?.message || e || '操作失败', icon: 'none' })
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
.dynamic-page {
    min-height: 100vh;
    background: linear-gradient(180deg, #fcf8ff 0%, #f8f6fb 42%, #f5f5f5 100%);
}

.filter-header {
    position: sticky;
    top: 0;
    z-index: 100;
    padding: 12rpx 0 8rpx;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(14rpx);
    border-bottom: 1rpx solid rgba(229, 231, 235, 0.8);
    box-shadow: 0 8rpx 24rpx rgba(15, 23, 42, 0.06);
}

.tag-filter-banner {
    margin: 0 20rpx 12rpx;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    gap: 16rpx;

    &__icon {
        width: 56rpx;
        height: 56rpx;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.18);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    &__content {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__label {
        font-size: 22rpx;
        color: rgba(255, 255, 255, 0.78);
    }

    &__text {
        font-size: 28rpx;
        font-weight: 700;
        color: #ffffff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__clear {
        width: 56rpx;
        height: 56rpx;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;

        &:active {
            opacity: 0.85;
            transform: scale(0.98);
        }
    }
}

.type-scroll-wrapper {
    display: flex;
    align-items: center;
    gap: 12rpx;
    padding: 8rpx 20rpx 4rpx;
}

.type-scroll {
    flex: 1;
    min-width: 0;
    white-space: nowrap;

    &::-webkit-scrollbar {
        display: none;
    }
}

.type-scroll-content {
    display: inline-flex;
    align-items: center;
    padding: 6rpx 0 10rpx;
    white-space: nowrap;
    width: max-content;
}

.type-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    min-width: 126rpx;
    height: 68rpx;
    margin-right: 12rpx;
    padding: 0 26rpx;
    border-radius: 999rpx;
    border: 1rpx solid #e7eaf0;
    background: #f7f8fb;
    font-size: 26rpx;
    color: #4b5563;
    font-weight: 500;
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.98);
        opacity: 0.88;
    }

    &.active {
        font-weight: 600;
    }

    &:last-child {
        margin-right: 8rpx;
    }
}

.filter-item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    height: 68rpx;
    min-width: 180rpx;
    max-width: 210rpx;
    padding: 0 20rpx;
    background: #ffffff;
    border-radius: 999rpx;
    border: 1rpx solid #e8ebf0;
    font-size: 24rpx;
    color: #5b6473;
    transition: all 0.2s ease;
    flex-shrink: 0;

    &:active {
        transform: scale(0.98);
        background: #f7f8fb;
    }

    text {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;

        &.active {
            font-weight: 600;
        }
    }

    &--sort {
        box-shadow: 0 4rpx 14rpx rgba(15, 23, 42, 0.04);
    }
}

.dynamic-feed {
    padding: 20rpx;
}

.loading-state {
    min-height: 58vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.loading-text {
    margin-top: 28rpx;
    font-size: 28rpx;
    color: #98a2b3;
}

.empty-state {
    min-height: 58vh;
    padding: 180rpx 48rpx 220rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.empty-icon-wrap {
    margin-bottom: 26rpx;
}

.empty-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 12rpx;
}

.empty-subtitle {
    font-size: 26rpx;
    color: #9aa3af;
    text-align: center;
    line-height: 1.5;
}

.empty-action-btn {
    margin-top: 34rpx;
    min-width: 240rpx;
    height: 88rpx;
    padding: 0 40rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;

    &:active {
        transform: translateY(2rpx) scale(0.98);
    }
}

.empty-action-text {
    font-size: 28rpx;
    font-weight: 600;
}

.dynamic-list {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.load-more {
    padding: 24rpx 0 12rpx;
    text-align: center;
}

.load-more-text {
    font-size: 26rpx;
    color: #98a2b3;
}

.load-more-clickable {
    color: var(--color-primary, #7c3aed);
    font-weight: 600;
}

.picker-container {
    background: #ffffff;
    width: 100vw;
    max-width: 100vw;
    margin: 0;
    border-radius: 24rpx 24rpx 0 0;
    box-shadow: 0 -12rpx 36rpx rgba(15, 23, 42, 0.1);
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.picker-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22rpx 24rpx;
    border-bottom: 1rpx solid #eef1f5;
}

.picker-title {
    font-size: 30rpx;
    font-weight: 700;
    color: #1f2937;
}

.picker-close {
    width: 56rpx;
    height: 56rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;

    &:active {
        background: #f4f5f7;
    }
}

.button-picker-content {
    padding: 24rpx;
    max-height: 60vh;
    overflow-y: auto;
}

.button-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16rpx;
}

.button-item {
    padding: 24rpx 16rpx;
    background: #f8f9fb;
    border: 1rpx solid #e7ebf1;
    border-radius: 16rpx;
    text-align: center;
    font-size: 26rpx;
    color: #3f4a5a;
    font-weight: 500;
    transition: all 0.2s ease;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;

    &:active {
        transform: scale(0.98);
        opacity: 0.9;
    }

    &.active {
        font-weight: 600;
        border-color: transparent;
    }
}
</style>
