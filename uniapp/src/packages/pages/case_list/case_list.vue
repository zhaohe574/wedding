<template>
    <view class="case-list-page">
        <!-- 1. 自定义顶部导航栏 -->
        <view
            class="custom-navbar"
            :class="{ 'custom-navbar--scrolled': isScrolled }"
            :style="{ height: `${navBarMetrics.navBarHeight}px`, paddingTop: `${navBarMetrics.statusBarHeight}px` }"
        >
            <view
                class="navbar-content"
                :style="{
                    height: `${navBarMetrics.contentHeight}px`,
                    paddingRight: `${navBarMetrics.capsuleWidth + navBarMetrics.capsuleMarginRight + 12}px`
                }"
            >
                <!-- 搜索框 -->
                <view class="search-bar" :class="{ 'search-bar--scrolled': isScrolled }" @tap="handleSearchTap">
                    <image
                        class="search-icon"
                        src="/packages/static/images/case/gen-search-v2.png"
                        mode="aspectFit"
                    />
                    <input
                        v-model="keyword"
                        class="search-input"
                        placeholder="搜索婚礼风格、场景、酒店等"
                        placeholder-class="search-placeholder"
                        confirm-type="search"
                        @confirm="handleSearchConfirm"
                    />
                    <view v-if="keyword" class="search-clear" @tap.stop="handleClearSearch">
                        <image
                            class="clear-icon"
                            src="/packages/static/images/case/close.png"
                            mode="aspectFit"
                        />
                    </view>
                </view>
            </view>
        </view>

        <!-- 顶部占位高度 -->
        <view :style="{ height: `${navBarMetrics.navBarHeight}px` }"></view>

        <!-- 2. 热门爆款轮播区域 -->
        <view v-if="hotCases.length > 0 && !hasActiveFilter" class="hot-section">
            <view class="hot-section-head">
                <view class="hot-title-box">
                    <text class="hot-title-text">热门爆款</text>
                    <view class="hot-title-underline"></view>
                </view>
                <view class="hot-more-link" @tap="handleFilterByHot">
                    <text class="hot-more-text">更多爆款</text>
                    <text class="hot-more-arrow">></text>
                </view>
            </view>

            <HotCardSlider
                :items="hotCases"
                @select="handleCaseDetail"
                @more="handleFilterByHot"
            />
        </view>

        <!-- 3. 吸顶筛选栏 -->
        <view
            class="sticky-filter-wrapper"
            :style="{ top: `${navBarMetrics.navBarHeight}px` }"
        >
            <!-- 4 个主筛选 Tab -->
            <view class="filter-tab-bar">
                <!-- 综合排序 -->
                <view
                    class="filter-tab-item"
                    :class="{ 'filter-tab-item--active': activeFilterMode === 'sort' || currentSortIndex > 0 }"
                    @tap="handleToggleFilter('sort')"
                >
                    <text class="tab-label">{{ currentSortLabel }}</text>
                    <image
                        class="tab-arrow"
                        :class="{ 'tab-arrow--up': activeFilterMode === 'sort' }"
                        src="/packages/static/images/case/fillter_down.png"
                        mode="aspectFit"
                    />
                </view>

                <!-- 地区 -->
                <view
                    class="filter-tab-item"
                    :class="{ 'filter-tab-item--active': activeFilterMode === 'city' || selectedCity || selectedProvince }"
                    @tap="handleToggleFilter('city')"
                >
                    <text class="tab-label">{{ currentCityLabel }}</text>
                    <image
                        class="tab-arrow"
                        :class="{ 'tab-arrow--up': activeFilterMode === 'city' }"
                        src="/packages/static/images/case/fillter_down.png"
                        mode="aspectFit"
                    />
                </view>

                <!-- 风格与色系 -->
                <view
                    class="filter-tab-item"
                    :class="{ 'filter-tab-item--active': activeFilterMode === 'style' || selectedStyles.length > 0 || selectedColors.length > 0 }"
                    @tap="handleToggleFilter('style')"
                >
                    <text class="tab-label">风格色系</text>
                    <view v-if="selectedStyles.length + selectedColors.length > 0" class="badge-count">
                        {{ selectedStyles.length + selectedColors.length }}
                    </view>
                    <image
                        class="tab-arrow"
                        :class="{ 'tab-arrow--up': activeFilterMode === 'style' }"
                        src="/packages/static/images/case/fillter_down.png"
                        mode="aspectFit"
                    />
                </view>

                <!-- 场景与预算 -->
                <view
                    class="filter-tab-item"
                    :class="{ 'filter-tab-item--active': activeFilterMode === 'scene' || selectedScenes.length > 0 || minPrice > -1 }"
                    @tap="handleToggleFilter('scene')"
                >
                    <text class="tab-label">场景预算</text>
                    <view v-if="selectedScenes.length > 0 || minPrice > -1" class="badge-count">
                        {{ selectedScenes.length + (minPrice > -1 ? 1 : 0) }}
                    </view>
                    <image
                        class="tab-arrow"
                        :class="{ 'tab-arrow--up': activeFilterMode === 'scene' }"
                        src="/packages/static/images/case/fillter_down.png"
                        mode="aspectFit"
                    />
                </view>
            </view>

            <!-- 快捷标签横滑栏 -->
            <scroll-view scroll-x class="quick-tags-scroll" :show-scrollbar="false">
                <view class="quick-tags-track">
                    <view
                        v-for="(tag, idx) in quickTags"
                        :key="idx"
                        class="quick-tag-chip"
                        :class="{ 'quick-tag-chip--active': tag.checked }"
                        @tap="handleToggleQuickTag(idx)"
                    >
                        {{ tag.name }}
                    </view>
                </view>
            </scroll-view>

            <!-- 已激活筛选 Chips 展示 -->
            <view v-if="activeFilterChips.length > 0" class="active-chips-bar">
                <view
                    v-for="(chip, index) in activeFilterChips"
                    :key="index"
                    class="active-chip"
                    @tap="handleRemoveChip(chip)"
                >
                    <text class="chip-text">{{ chip.label }}</text>
                    <image
                        class="chip-close-icon"
                        src="/packages/static/images/case/case_fillter_del.png"
                        mode="aspectFit"
                    />
                </view>
                <view class="clear-all-chips" @tap="handleResetAllFilters">清空全部</view>
            </view>
        </view>

        <!-- 4. 案例列表瀑布流 -->
        <view class="case-list-body">
            <!-- 结果计数 -->
            <view class="case-counter-row">
                <text class="counter-text">共找到 {{ caseList.length }} 套真实案例</text>
            </view>

            <!-- 案例卡片列表 -->
            <view class="case-cards-container">
                <view
                    v-for="(item, idx) in caseList"
                    :key="item.caseId || idx"
                    class="case-card"
                    @tap="handleCaseDetail(item)"
                >
                    <!-- 封面 -->
                    <view class="case-cover-box">
                        <image
                            class="case-cover-img"
                            :src="item.refCover"
                            mode="aspectFill"
                            lazy-load
                        />
                        <!-- 视频播放标记 -->
                        <image
                            v-if="item.hasVideo"
                            class="video-play-btn"
                            src="/packages/static/images/case/video-btn.png"
                            mode="aspectFit"
                        />
                        <!-- 左上角标签 -->
                        <view class="cover-badges-left">
                            <view v-if="item.shownew" class="badge-new">新品</view>
                            <view v-if="item.isHot" class="badge-hot">爆款</view>
                        </view>
                        <!-- 收藏爱心 -->
                        <view class="cover-fav-btn" @tap.stop="handleToggleFav(item)">
                            <image
                                class="fav-icon"
                                :src="likedCaseIds.includes(item.caseId) ? '/packages/static/images/case/case-liked.png' : '/packages/static/images/case/case-unlike.png'"
                                mode="aspectFit"
                            />
                        </view>
                    </view>

                    <!-- 信息主体 -->
                    <view class="case-details">
                        <!-- 第一行：标题与参考价 -->
                        <view class="details-row-1">
                            <text class="case-theme-title">{{ item.theme }}</text>
                            <view class="case-price-box">
                                <text class="case-price-symbol">¥</text>
                                <text class="case-price-val">{{ item.refPrice }}</text>
                            </view>
                        </view>

                        <!-- 第二行：酒店与风格标签 -->
                        <view class="details-row-2">
                            <view class="hotel-info">
                                <text class="hotel-name-text">{{ item.hotel }}</text>
                            </view>
                            <text class="style-tags-text">{{ item.refStyle }}</text>
                        </view>

                        <!-- 第三行：策划师与浏览数据 -->
                        <view class="details-row-3">
                            <view class="planner-info">
                                <image
                                    class="planner-avatar"
                                    :src="item.creatorAvator"
                                    mode="aspectFill"
                                />
                                <text class="planner-name">{{ item.planner }}</text>
                                <text class="planner-tag">策划师</text>
                            </view>
                            <view class="view-stats">
                                <image
                                    class="browse-icon"
                                    src="/packages/static/images/case/case-browse.png"
                                    mode="aspectFit"
                                />
                                <text class="touch-num">{{ item.touch }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 空状态 -->
            <view v-if="caseList.length === 0 && !isLoading" class="empty-case-box">
                <image
                    class="empty-img"
                    src="/packages/static/images/case/no-case-tip-icon.png"
                    mode="aspectFit"
                />
                <text class="empty-title">{{ listErrorCode ? '案例加载失败' : '暂无符合条件的案例' }}</text>
                <text class="empty-subtitle">
                    {{ listErrorCode ? '网络或服务异常，请稍后重试' : '可以尝试减少筛选条件查看更多作品' }}
                </text>
                <view class="empty-reset-btn" @tap="handleEmptyAction">
                    {{ listErrorCode ? '重新加载' : '重置筛选条件' }}
                </view>
            </view>

            <!-- 加载状态 -->
            <view v-if="isLoading" class="loading-more-row">
                <text class="loading-more-text">加载中...</text>
            </view>
            <view v-else-if="hadLoadAll && caseList.length > 0" class="no-more-row">
                <text class="no-more-text">—— 已经到底啦 ——</text>
            </view>
        </view>

        <!-- 5. 底部悬浮近期更新气泡 -->
        <view v-if="showRecentNotice && recentNewCount > 0" class="floating-recent-bubble">
            <image
                class="recent-flame-icon"
                src="/packages/static/images/case/case_nw.png"
                mode="aspectFit"
            />
            <text class="recent-notice-text">近7天已更新 {{ recentNewCount }} 套高定案例</text>
            <view class="recent-close-btn" @tap="showRecentNotice = false">✕</view>
        </view>

        <!-- 6. 筛选抽屉弹层 -->
        <FilterDrawer
            :visible="activeFilterMode !== null"
            :mode="activeFilterMode || 'sort'"
            :top-offset="filterDrawerTop"
            :sort-list="filterOptions.sortList"
            :current-sort-index="currentSortIndex"
            :styles="filterOptions.styles"
            :colors="filterOptions.colors"
            :scenes="filterOptions.scenes"
            :prices="filterOptions.prices"
            :provinces="filterOptions.provinces"
            :active-styles="selectedStyles"
            :active-colors="selectedColors"
            :active-scenes="selectedScenes"
            :active-city="selectedCity"
            :active-province="selectedProvince"
            @close="handleCloseDrawer"
            @update:sort="handleUpdateSort"
            @update:city="handleUpdateCity"
            @update:style="handleUpdateStyle"
            @update:scene="handleUpdateScene"
        />
    </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onPullDownRefresh, onReachBottom, onPageScroll } from '@dcloudio/uni-app'
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'
import HotCardSlider from './components/HotCardSlider.vue'
import FilterDrawer, { type FilterMode } from './components/FilterDrawer.vue'
import {
    getCaseList,
    getHotCaseList,
    getNewCaseCount,
    CASE_FILTER_OPTIONS,
    type CaseItem
} from '@/api/case'

const navBarMetrics = useNavBarMetrics()

// 状态管理
const keyword = ref('')
const isScrolled = ref(false)
const isLoading = ref(false)
const hadLoadAll = ref(false)
const pageIndex = ref(1)

const hotCases = ref<CaseItem[]>([])
const caseList = ref<CaseItem[]>([])
// 案例列表接口错误标记（非空表示本次加载失败，展示空态）
const listErrorCode = ref('')
const filterOptions = ref(CASE_FILTER_OPTIONS)

// 筛选状态
const activeFilterMode = ref<FilterMode | null>(null)
const currentSortIndex = ref(0)
const selectedProvince = ref('')
const selectedCity = ref('')
const selectedStyles = ref<string[]>([])
const selectedColors = ref<string[]>([])
const selectedScenes = ref<string[]>([])
const minPrice = ref(-1)
const maxPrice = ref(-1)

const quickTags = ref([
    { name: '视频案例', checked: false },
    { name: '设计解说', checked: false },
    { name: '爆款案例', checked: false }
])

const likedCaseIds = ref<string[]>([])
const showRecentNotice = ref(true)
// 近7天新案例数：接口失败或为 0 时不展示角标
const recentNewCount = ref(0)

// 页面吸顶计算
const filterDrawerTop = computed(() => {
    return navBarMetrics.navBarHeight + 48
})

const currentSortLabel = computed(() => {
    return filterOptions.value.sortList[currentSortIndex.value] || '综合排序'
})

const currentCityLabel = computed(() => {
    if (selectedCity.value) return selectedCity.value
    if (selectedProvince.value) return selectedProvince.value
    return '全国地区'
})

const hasActiveFilter = computed(() => {
    return (
        keyword.value.trim().length > 0 ||
        selectedStyles.value.length > 0 ||
        selectedColors.value.length > 0 ||
        selectedScenes.value.length > 0 ||
        selectedCity.value !== '' ||
        selectedProvince.value !== '' ||
        minPrice.value > -1 ||
        quickTags.value.some(t => t.checked)
    )
})

interface ActiveChip {
    type: string
    label: string
    value: any
}

const activeFilterChips = computed<ActiveChip[]>(() => {
    const chips: ActiveChip[] = []
    if (selectedCity.value) {
        chips.push({ type: 'city', label: selectedCity.value, value: selectedCity.value })
    } else if (selectedProvince.value) {
        chips.push({ type: 'province', label: selectedProvince.value, value: selectedProvince.value })
    }
    selectedStyles.value.forEach(s => chips.push({ type: 'style', label: s, value: s }))
    selectedColors.value.forEach(c => chips.push({ type: 'color', label: c, value: c }))
    selectedScenes.value.forEach(sc => chips.push({ type: 'scene', label: sc, value: sc }))
    if (minPrice.value > -1) {
        const pLabel = maxPrice.value > 0 ? `${minPrice.value / 10000}-${maxPrice.value / 10000}万` : `${minPrice.value / 10000}万以上`
        chips.push({ type: 'price', label: pLabel, value: null })
    }
    return chips
})

// 数据获取
const fetchInitialData = async () => {
    isLoading.value = true
    try {
        const [hotRes, listRes, newNum] = await Promise.all([
            getHotCaseList(),
            queryFilteredCases(),
            getNewCaseCount()
        ])
        hotCases.value = hotRes
        caseList.value = listRes.list
        recentNewCount.value = newNum
        // 接口不可用时记录错误标记，页面按空态渲染（不使用任何内嵌案例数据）
        listErrorCode.value = listRes.error_code || ''
    } finally {
        isLoading.value = false
    }
}

const queryFilteredCases = async () => {
    const checkedTags = quickTags.value.filter(t => t.checked).map(t => t.name)
    let orderBy: 'ORDER_BY_CTIME' | 'ORDER_BY_PRICE' | 'ORDER_BY_TOUCH' = 'ORDER_BY_CTIME'
    let order: 'DESC' | 'ASC' = 'DESC'

    if (currentSortIndex.value === 1) {
        orderBy = 'ORDER_BY_TOUCH'
    } else if (currentSortIndex.value === 2) {
        orderBy = 'ORDER_BY_PRICE'
        order = 'DESC'
    } else if (currentSortIndex.value === 3) {
        orderBy = 'ORDER_BY_PRICE'
        order = 'ASC'
    }

    return await getCaseList({
        page: pageIndex.value,
        keyword: keyword.value,
        orderBy,
        order,
        province: selectedProvince.value,
        city: selectedCity.value,
        styles: selectedStyles.value.join(','),
        colors: selectedColors.value.join(','),
        scenes: selectedScenes.value.join(','),
        minPrice: minPrice.value,
        maxPrice: maxPrice.value,
        tags: checkedTags
    })
}

const loadMoreCases = async () => {
    if (isLoading.value || hadLoadAll.value) return
    isLoading.value = true
    pageIndex.value += 1
    try {
        const res = await queryFilteredCases()
        if (res.list.length === 0) {
            hadLoadAll.value = true
        } else {
            caseList.value = [...caseList.value, ...res.list]
        }
    } finally {
        isLoading.value = false
    }
}

// 交互操作
const handleToggleFilter = (mode: FilterMode) => {
    if (activeFilterMode.value === mode) {
        activeFilterMode.value = null
    } else {
        activeFilterMode.value = mode
    }
}

const handleCloseDrawer = () => {
    activeFilterMode.value = null
}

const handleUpdateSort = async (idx: number) => {
    currentSortIndex.value = idx
    pageIndex.value = 1
    hadLoadAll.value = false
    const res = await queryFilteredCases()
    caseList.value = res.list
}

const handleUpdateCity = async (data: { province: string; city: string }) => {
    selectedProvince.value = data.province
    selectedCity.value = data.city
    pageIndex.value = 1
    hadLoadAll.value = false
    const res = await queryFilteredCases()
    caseList.value = res.list
}

const handleUpdateStyle = async (data: { styles: string[]; colors: string[] }) => {
    selectedStyles.value = data.styles
    selectedColors.value = data.colors
    pageIndex.value = 1
    hadLoadAll.value = false
    const res = await queryFilteredCases()
    caseList.value = res.list
}

const handleUpdateScene = async (data: { scenes: string[]; minPrice: number; maxPrice: number }) => {
    selectedScenes.value = data.scenes
    minPrice.value = data.minPrice
    maxPrice.value = data.maxPrice
    pageIndex.value = 1
    hadLoadAll.value = false
    const res = await queryFilteredCases()
    caseList.value = res.list
}

const handleToggleQuickTag = async (idx: number) => {
    quickTags.value[idx].checked = !quickTags.value[idx].checked
    pageIndex.value = 1
    hadLoadAll.value = false
    const res = await queryFilteredCases()
    caseList.value = res.list
}

const handleRemoveChip = async (chip: ActiveChip) => {
    if (chip.type === 'city') selectedCity.value = ''
    else if (chip.type === 'province') selectedProvince.value = ''
    else if (chip.type === 'style') {
        selectedStyles.value = selectedStyles.value.filter(s => s !== chip.value)
    } else if (chip.type === 'color') {
        selectedColors.value = selectedColors.value.filter(c => c !== chip.value)
    } else if (chip.type === 'scene') {
        selectedScenes.value = selectedScenes.value.filter(sc => sc !== chip.value)
    } else if (chip.type === 'price') {
        minPrice.value = -1
        maxPrice.value = -1
    }
    pageIndex.value = 1
    const res = await queryFilteredCases()
    caseList.value = res.list
}

const handleResetAllFilters = async () => {
    selectedProvince.value = ''
    selectedCity.value = ''
    selectedStyles.value = []
    selectedColors.value = []
    selectedScenes.value = []
    minPrice.value = -1
    maxPrice.value = -1
    quickTags.value.forEach(t => (t.checked = false))
    keyword.value = ''
    currentSortIndex.value = 0
    pageIndex.value = 1
    hadLoadAll.value = false
    const res = await queryFilteredCases()
    caseList.value = res.list
}

// 空态主按钮：接口失败时重新拉取，否则重置筛选条件
const handleEmptyAction = async () => {
    if (listErrorCode.value) {
        await fetchInitialData()
        return
    }
    await handleResetAllFilters()
}

// 搜索框整体可点击聚焦，无需额外处理
const handleSearchTap = () => {
    /* 保持输入框可聚焦，无需额外动作 */
}

const handleSearchConfirm = async () => {
    pageIndex.value = 1
    hadLoadAll.value = false
    const res = await queryFilteredCases()
    caseList.value = res.list
}

const handleClearSearch = async () => {
    keyword.value = ''
    pageIndex.value = 1
    const res = await queryFilteredCases()
    caseList.value = res.list
}

const handleFilterByHot = () => {
    const hotTag = quickTags.value.find(t => t.name === '爆款案例')
    if (hotTag) {
        hotTag.checked = true
        handleSearchConfirm()
    }
}

const handleToggleFav = (item: CaseItem) => {
    const idx = likedCaseIds.value.indexOf(item.caseId)
    if (idx > -1) {
        likedCaseIds.value.splice(idx, 1)
        uni.showToast({ title: '已取消收藏', icon: 'none' })
    } else {
        likedCaseIds.value.push(item.caseId)
        uni.showToast({ title: '已收藏案例', icon: 'success' })
    }
}

const handleCaseDetail = (item: CaseItem) => {
    uni.navigateTo({
        url: `/packages/pages/staff_work_detail/staff_work_detail?id=${item.caseId}&title=${encodeURIComponent(item.theme)}`
    })
}

// 页面生命周期
onPageScroll(e => {
    isScrolled.value = e.scrollTop > 60
})

onPullDownRefresh(async () => {
    pageIndex.value = 1
    hadLoadAll.value = false
    await fetchInitialData()
    uni.stopPullDownRefresh()
})

onReachBottom(() => {
    loadMoreCases()
})

onMounted(() => {
    fetchInitialData()
})
</script>

<style scoped lang="scss">
.case-list-page {
    min-height: 100vh;
    background-color: #f8f9fa;
    position: relative;
    padding-bottom: 80rpx;
}

/* 自定义顶部导航 */
.custom-navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 400;
    /* 使用项目内渐变替代第三方 CDN 背景图，避免热链与品牌风险 */
    background: linear-gradient(180deg, #fff5f7 0%, #f8f9fa 100%);
    transition: background-color 0.25s ease, box-shadow 0.25s ease;

    &.custom-navbar--scrolled {
        background-image: none;
        background-color: #ffffff;
        box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
    }
}

.navbar-content {
    display: flex;
    align-items: center;
    padding-left: 32rpx;
    box-sizing: border-box;
}

.search-bar {
    flex: 1;
    height: 72rpx;
    background: #ffffff;
    border-radius: 36rpx;
    box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    padding: 0 24rpx;
    box-sizing: border-box;
    transition: background-color 0.2s ease;

    &.search-bar--scrolled {
        background: #f4f5f7;
        box-shadow: none;
    }
}

.search-icon {
    width: 32rpx;
    height: 32rpx;
    margin-right: 14rpx;
    flex-shrink: 0;
}

.search-input {
    flex: 1;
    font-size: 26rpx;
    color: #323233;
}

.search-placeholder {
    color: #9fa0a5;
    font-size: 26rpx;
}

.search-clear {
    width: 40rpx;
    height: 40rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.clear-icon {
    width: 24rpx;
    height: 24rpx;
}

/* 热门爆款区域 */
.hot-section {
    position: relative;
    padding-top: 24rpx;
    padding-bottom: 12rpx;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.7) 0%, #fafafa 100%);
}

.hot-section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 36rpx 24rpx;
}

.hot-title-box {
    position: relative;
}

.hot-title-text {
    font-size: 38rpx;
    font-weight: 700;
    color: #2b2b2b;
    position: relative;
    z-index: 1;
    letter-spacing: 1rpx;
}

.hot-title-underline {
    position: absolute;
    bottom: 2rpx;
    left: 0;
    width: 140rpx;
    height: 14rpx;
    background: linear-gradient(90deg, #e3c49e 0%, rgba(227, 196, 158, 0.1) 100%);
    border-radius: 6rpx;
    z-index: 0;
}

.hot-more-link {
    display: flex;
    align-items: center;
}

.hot-more-text {
    font-size: 26rpx;
    color: #969799;
    font-weight: 500;
}

.hot-more-arrow {
    font-size: 24rpx;
    color: #c0c4cc;
    margin-left: 6rpx;
}

/* 吸顶筛选栏 */
.sticky-filter-wrapper {
    position: sticky;
    z-index: 300;
    background: #ffffff;
    border-bottom: 1rpx solid #f2f3f5;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.02);
}

.filter-tab-bar {
    display: flex;
    height: 88rpx;
    align-items: center;
}

.filter-tab-item {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    height: 100%;

    &.filter-tab-item--active {
        .tab-label {
            color: #c29b6b;
            font-weight: 600;
        }
    }
}

.tab-label {
    font-size: 26rpx;
    color: #646566;
    max-width: 130rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tab-arrow {
    width: 18rpx;
    height: 18rpx;
    margin-left: 8rpx;
    transition: transform 0.2s ease;

    &.tab-arrow--up {
        transform: rotate(180deg);
    }
}

.badge-count {
    background: #c29b6b;
    color: #ffffff;
    font-size: 20rpx;
    border-radius: 20rpx;
    padding: 0 8rpx;
    height: 28rpx;
    line-height: 28rpx;
    margin-left: 4rpx;
}

/* 快捷标签 */
.quick-tags-scroll {
    white-space: nowrap;
    padding: 6rpx 0 20rpx;
}

.quick-tags-track {
    display: flex;
    padding: 0 32rpx;
    gap: 16rpx;
}

.quick-tag-chip {
    padding: 10rpx 28rpx;
    background: #f5f5f9;
    border-radius: 8rpx;
    font-size: 24rpx;
    color: #646566;
    flex-shrink: 0;
    transition: all 0.2s ease;

    &.quick-tag-chip--active {
        background: #fff9f3;
        color: #8a6e57;
        font-weight: 600;
        border: 1rpx solid #e2cbba;
    }
}

/* 激活的筛选项条 */
.active-chips-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12rpx;
    padding: 0 32rpx 18rpx;
}

.active-chip {
    display: flex;
    align-items: center;
    background: #f0f1f9;
    border-radius: 8rpx;
    padding: 6rpx 16rpx;
}

.chip-text {
    font-size: 24rpx;
    color: #646566;
    margin-right: 8rpx;
}

.chip-close-icon {
    width: 20rpx;
    height: 20rpx;
    opacity: 0.7;
}

.clear-all-chips {
    font-size: 24rpx;
    color: #969799;
    margin-left: 12rpx;
    text-decoration: underline;
}

/* 案例流列表 */
.case-list-body {
    padding: 24rpx 32rpx 0;
}

.case-counter-row {
    margin-bottom: 24rpx;
}

.counter-text {
    font-size: 24rpx;
    color: #969799;
}

.case-cards-container {
    display: flex;
    flex-direction: column;
    gap: 48rpx;
}

.case-card {
    background: #ffffff;
    border-radius: 20rpx;
    overflow: hidden;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.04);
}

.case-cover-box {
    position: relative;
    width: 100%;
    height: 448rpx;
    background-color: #f0f1f3;
}

.case-cover-img {
    width: 100%;
    height: 100%;
    display: block;
}

.video-play-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 88rpx;
    height: 88rpx;
}

.cover-badges-left {
    position: absolute;
    top: 20rpx;
    left: 20rpx;
    display: flex;
    gap: 10rpx;
}

.badge-new {
    background: #c29b6b;
    color: #ffffff;
    font-size: 20rpx;
    font-weight: 600;
    padding: 4rpx 14rpx;
    border-radius: 6rpx;
}

.badge-hot {
    background: #e53e3e;
    color: #ffffff;
    font-size: 20rpx;
    font-weight: 600;
    padding: 4rpx 14rpx;
    border-radius: 6rpx;
}

.cover-fav-btn {
    position: absolute;
    top: 20rpx;
    right: 20rpx;
    width: 64rpx;
    height: 64rpx;
    background: rgba(0, 0, 0, 0.35);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4rpx);
}

.fav-icon {
    width: 36rpx;
    height: 36rpx;
}

.case-details {
    padding: 24rpx 28rpx 28rpx;
}

.details-row-1 {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
}

.case-theme-title {
    font-size: 34rpx;
    font-weight: 600;
    color: #2b2b2b;
    max-width: 420rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.case-price-box {
    display: flex;
    align-items: baseline;
    color: #c29b6b;
}

.case-price-symbol {
    font-size: 24rpx;
    font-weight: 600;
    margin-right: 4rpx;
}

.case-price-val {
    font-size: 38rpx;
    font-weight: 700;
    font-family: 'DIN Alternate', PingFangSC-Medium, sans-serif;
}

.details-row-2 {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 14rpx;
}

.hotel-info {
    max-width: 360rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hotel-name-text {
    font-size: 26rpx;
    color: #9fa0a5;
}

.style-tags-text {
    font-size: 24rpx;
    color: #8a6e57;
    font-weight: 500;
}

.details-row-3 {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid #f6f7f9;
}

.planner-info {
    display: flex;
    align-items: center;
}

.planner-avatar {
    width: 44rpx;
    height: 44rpx;
    border-radius: 50%;
    margin-right: 12rpx;
    background-color: #eee;
}

.planner-name {
    font-size: 24rpx;
    color: #4a4b4d;
    font-weight: 500;
    margin-right: 8rpx;
}

.planner-tag {
    font-size: 20rpx;
    color: #9fa0a5;
    background: #f5f6f8;
    padding: 2rpx 8rpx;
    border-radius: 4rpx;
}

.view-stats {
    display: flex;
    align-items: center;
}

.browse-icon {
    width: 28rpx;
    height: 28rpx;
    margin-right: 6rpx;
    opacity: 0.6;
}

.touch-num {
    font-size: 24rpx;
    color: #9fa0a5;
}

/* 空状态 */
.empty-case-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 120rpx 40rpx;
}

.empty-img {
    width: 160rpx;
    height: 160rpx;
    margin-bottom: 24rpx;
}

.empty-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #323233;
    margin-bottom: 8rpx;
}

.empty-subtitle {
    font-size: 26rpx;
    color: #969799;
    margin-bottom: 32rpx;
}

.empty-reset-btn {
    padding: 16rpx 48rpx;
    background: #c29b6b;
    color: #ffffff;
    font-size: 28rpx;
    border-radius: 36rpx;
}

.loading-more-row,
.no-more-row {
    padding: 40rpx 0 20rpx;
    text-align: center;
}

.loading-more-text,
.no-more-text {
    font-size: 24rpx;
    color: #c0c4cc;
}

/* 底部浮动更新提示气泡 */
.floating-recent-bubble {
    position: fixed;
    bottom: 60rpx;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(194, 155, 107, 0.92);
    backdrop-filter: blur(12rpx);
    border-radius: 40rpx;
    padding: 14rpx 28rpx;
    display: flex;
    align-items: center;
    box-shadow: 0 8rpx 24rpx rgba(194, 155, 107, 0.35);
    z-index: 350;
    animation: bounceIn 0.4s ease-out;
}

@keyframes bounceIn {
    from {
        transform: translate(-50%, 40rpx);
        opacity: 0;
    }
    to {
        transform: translate(-50%, 0);
        opacity: 1;
    }
}

.recent-flame-icon {
    width: 32rpx;
    height: 32rpx;
    margin-right: 12rpx;
}

.recent-notice-text {
    font-size: 26rpx;
    color: #ffffff;
    font-weight: 500;
    letter-spacing: 1rpx;
}

.recent-close-btn {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.7);
    margin-left: 18rpx;
    padding: 4rpx;
}
</style>
