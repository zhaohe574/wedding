<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasTabbar>
        <BaseNavbar
            title="人员列表"
            variant="solid"
            bg-color="#181614"
            text-color="#FFFDF8"
        />

        <view class="staff-list-page">
            <!-- 顶部一体化黑金控制台 (纯粹黑金底色、香槟金分界、告别污浊渐变黑雾) -->
            <view class="filter-header">
                <!-- 第一行：轻奢筛选摘要胶囊 -->
                <view class="filter-header__summary" @click="redirectToScheduleQuery">
                    <view class="summary-info">
                        <!-- 地区 -->
                        <view class="summary-tag">
                            <BaseIcon name="location" size="20" color="#C6A15B" />
                            <text class="summary-tag__text">{{ displayDistrictText }}</text>
                        </view>
                        <view class="summary-divider" />
                        <!-- 日期 -->
                        <view class="summary-tag">
                            <BaseIcon name="calendar" size="20" color="#C6A15B" />
                            <text class="summary-tag__text">{{ displayDateText }}</text>
                        </view>
                        <!-- 分类 (若有) -->
                        <template v-if="currentCategoryName">
                            <view class="summary-divider" />
                            <view class="summary-tag summary-tag--category">
                                <text class="summary-tag__text">{{ currentCategoryName }}</text>
                            </view>
                        </template>
                    </view>

                    <!-- 右侧重筛入口 -->
                    <view class="summary-action">
                        <BaseIcon name="sort" size="18" color="#C6A15B" />
                        <text class="summary-action__text">重筛</text>
                        <BaseIcon name="right" size="14" color="#8E8880" />
                    </view>
                </view>

                <!-- 第二行：极简纯粹文字排序栏与黑金视图切换 -->
                <view class="filter-header__toolbar">
                    <view class="sort-tabs">
                        <!-- 综合 -->
                        <view
                            class="sort-tab-item"
                            :class="{ 'sort-tab-item--active': currentSort === 'default' }"
                            @click="handleSelectSort('default')"
                        >
                            <text class="sort-tab-item__text">综合</text>
                            <view v-if="currentSort === 'default'" class="sort-tab-item__line" />
                        </view>

                        <!-- 销量 -->
                        <view
                            class="sort-tab-item"
                            :class="{ 'sort-tab-item--active': currentSort === 'order_count' }"
                            @click="handleSelectSort('order_count')"
                        >
                            <text class="sort-tab-item__text">销量</text>
                            <view v-if="currentSort === 'order_count'" class="sort-tab-item__line" />
                        </view>

                        <!-- 评分 -->
                        <view
                            class="sort-tab-item"
                            :class="{ 'sort-tab-item--active': currentSort === 'rating' }"
                            @click="handleSelectSort('rating')"
                        >
                            <text class="sort-tab-item__text">评分</text>
                            <view v-if="currentSort === 'rating'" class="sort-tab-item__line" />
                        </view>

                        <!-- 价格 (双向升降箭头) -->
                        <view
                            class="sort-tab-item sort-tab-item--price"
                            :class="{ 'sort-tab-item--active': isPriceSortActive }"
                            @click="handleSelectSort('price')"
                        >
                            <text class="sort-tab-item__text">价格</text>
                            <view class="sort-tab-item__arrows">
                                <text
                                    class="price-arrow"
                                    :class="{ 'price-arrow--active': currentSort === 'price_asc' }"
                                >▲</text>
                                <text
                                    class="price-arrow"
                                    :class="{ 'price-arrow--active': currentSort === 'price_desc' }"
                                >▼</text>
                            </view>
                            <view v-if="isPriceSortActive" class="sort-tab-item__line" />
                        </view>
                    </view>

                    <!-- 右侧视图切换 (深色磨砂金标方纽) -->
                    <view
                        class="view-switch-box"
                        @click.stop="handleToggleViewMode"
                    >
                        <BaseIcon
                            :name="staffViewMode === 'poster' ? 'menu-list' : 'grid'"
                            size="28"
                            color="#D9BE82"
                        />
                    </view>
                </view>
            </view>

            <!-- 列表分页滚动容器 -->
            <z-paging
                ref="pagingRef"
                v-model="staffList"
                :auto="false"
                :default-page-size="STAFF_LIST_PAGE_SIZE"
                :fixed="false"
                lower-threshold="160rpx"
                :refresher-enabled="pagingRefresherEnabled"
                :to-bottom-loading-more-enabled="true"
                use-page-scroll
                @query="queryList"
            >
                <template #loading>
                    <view class="paging-state">
                        <BaseSkeleton :rows="4" />
                    </view>
                </template>

                <template #empty>
                    <view class="paging-state">
                        <EmptyState
                            title="当前筛选暂无可预约团队"
                            description="调整筛选条件后重试。"
                            action-text="返回重筛"
                            @action="handleEmptyAction"
                        />
                    </view>
                </template>

                <template #loadingMoreDefault>
                    <view class="paging-load-more paging-load-more--idle" />
                </template>

                <template #loadingMoreLoading>
                    <view class="paging-load-more">
                        <text class="paging-load-more__text">加载中...</text>
                    </view>
                </template>

                <template #loadingMoreNoMore>
                    <view class="paging-load-more">
                        <text class="paging-load-more__text">已展示全部人员</text>
                    </view>
                </template>

                <template #loadingMoreFail>
                    <view class="paging-load-more">
                        <text class="paging-load-more__text">加载失败，请上滑重试</text>
                    </view>
                </template>

                <!-- 视图模式 1：双列海报大片流 (Poster Grid) -->
                <view v-if="staffViewMode === 'poster'" class="poster-list">
                    <view
                        v-for="item in staffList"
                        :key="item.id"
                        class="poster-card"
                        @click="goToDetail(item.id)"
                    >
                        <!-- 肖像大片 (3:4 比例，360rpx 视觉冲击力) -->
                        <view class="poster-card__media">
                            <image
                                class="poster-card__image"
                                :src="getStaffAvatar(item)"
                                mode="aspectFill"
                                lazy-load
                            />
                            <view class="poster-card__shade" />

                            <!-- 推荐徽章 -->
                            <view v-if="item.is_recommend" class="poster-card__badge-wrap">
                                <text class="poster-card__badge-text">{{ getRecommendBadgeText(item) }}</text>
                            </view>

                            <!-- 收藏按钮 (磨砂玻璃浮钮) -->
                            <view
                                class="poster-card__favorite"
                                @click.stop="handleToggleFavorite(item)"
                            >
                                <view
                                    class="favorite-circle"
                                    :class="{ 'favorite-circle--active': item.is_favorite }"
                                >
                                    <BaseIcon
                                        :name="item.is_favorite ? 'like-fill' : 'like'"
                                        size="24"
                                        :color="item.is_favorite ? '#C6A15B' : '#FFFDF8'"
                                    />
                                </view>
                            </view>

                            <!-- 底部贴画：职业类别 -->
                            <view class="poster-card__media-bottom">
                                <text class="poster-card__category">{{ item.category_name || '主创' }}</text>
                                <text v-if="item.experience_years" class="poster-card__exp">{{ item.experience_years }}年经验</text>
                            </view>
                        </view>

                        <!-- 卡片文字信息区 -->
                        <view class="poster-card__content">
                            <!-- 姓名与评分 -->
                            <view class="poster-card__row-title">
                                <text class="poster-card__name">{{ item.name || '未命名人员' }}</text>
                                <view class="poster-card__score-box">
                                    <BaseIcon name="star-fill" size="18" color="#C8A45D" />
                                    <text class="poster-card__score-val">{{ formatRatingText(item) }}</text>
                                </view>
                            </view>

                            <!-- 高光标签 -->
                            <view v-if="getDisplayTags(item).length" class="poster-card__tags">
                                <text
                                    v-for="tag in getDisplayTags(item)"
                                    :key="`${item.id}-${tag}`"
                                    class="poster-card__tag-pill"
                                >
                                    {{ tag }}
                                </text>
                            </view>

                            <!-- 起价与已售单量 -->
                            <view class="poster-card__row-footer">
                                <view class="poster-card__price-group">
                                    <template v-if="hasStaffPrice(item)">
                                        <text class="poster-card__price-symbol">¥</text>
                                        <text class="poster-card__price-num">{{ getCleanPrice(item) }}</text>
                                        <text class="poster-card__price-unit">/次起</text>
                                    </template>
                                    <text v-else class="poster-card__price-negotiable">面议</text>
                                </view>
                                <text class="poster-card__order-count">已服务{{ item.order_count || 0 }}单</text>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 视图模式 2：单列优雅名片流 (Line List) -->
                <view v-else class="line-list">
                    <view
                        v-for="item in staffList"
                        :key="item.id"
                        class="line-card"
                        @click="goToDetail(item.id)"
                    >
                        <!-- 左侧肖像大头像 (136rpx) -->
                        <view class="line-card__avatar-wrap">
                            <image
                                class="line-card__avatar"
                                :src="getStaffAvatar(item)"
                                mode="aspectFill"
                                lazy-load
                            />
                            <view v-if="item.is_recommend" class="line-card__recommend-badge">
                                荐
                            </view>
                        </view>

                        <!-- 中间信息流 -->
                        <view class="line-card__info">
                            <view class="line-card__header">
                                <text class="line-card__name">{{ item.name || '未命名人员' }}</text>
                                <text class="line-card__role-badge">{{ item.category_name || '主创' }}</text>
                            </view>

                            <view class="line-card__tags" v-if="getDisplayTags(item).length">
                                <text
                                    v-for="tag in getDisplayTags(item)"
                                    :key="`${item.id}-${tag}`"
                                    class="line-card__tag"
                                >
                                    {{ tag }}
                                </text>
                            </view>
                            <text v-else class="line-card__desc">{{ getCompactMetaText(item) }}</text>

                            <view class="line-card__metrics">
                                <view class="line-card__score">
                                    <BaseIcon name="star-fill" size="18" color="#C8A45D" />
                                    <text class="line-card__score-num">{{ formatRatingText(item) }}</text>
                                </view>
                                <text class="line-card__dot">·</text>
                                <text class="line-card__orders">已服务 {{ item.order_count || 0 }} 单</text>
                            </view>
                        </view>

                        <!-- 右侧起价与快捷操作 -->
                        <view class="line-card__action">
                            <view class="line-card__price-box">
                                <template v-if="hasStaffPrice(item)">
                                    <text class="line-card__price-symbol">¥</text>
                                    <text class="line-card__price-val">{{ getCleanPrice(item) }}</text>
                                    <text class="line-card__price-unit">起</text>
                                </template>
                                <text v-else class="line-card__price-negotiable">面议</text>
                            </view>

                            <view
                                class="line-card__fav-btn"
                                @click.stop="handleToggleFavorite(item)"
                            >
                                <BaseIcon
                                    :name="item.is_favorite ? 'like-fill' : 'like'"
                                    size="30"
                                    :color="item.is_favorite ? '#C6A15B' : '#A89F91'"
                                />
                            </view>
                        </view>
                    </view>
                </view>
            </z-paging>

            <tabbar :badge-refresh-key="tabbarRefreshKey" />
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { onLoad, onPageScroll, onReady, onReachBottom, onShow } from '@dcloudio/uni-app'
import { getStaffList, toggleStaffFavorite } from '@/api/staff'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseIconButton from '@/components/base/BaseIconButton.vue'
import BaseSkeleton from '@/components/base/BaseSkeleton.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { showError, showSuccess } from '@/utils/feedback'
import {
    buildServiceRegionQuery,
    formatServiceRegionText,
    hasServiceRegion,
    loadServiceRegionSelection,
    normalizeServiceRegion,
    saveServiceRegionSelection,
    toServiceRegionParams
} from '@/utils/service-region'

type StaffViewMode = 'poster' | 'list'

const DEFAULT_AVATAR = '/static/images/user/default_avatar.png'
const STAFF_LIST_PAGE_SIZE = 10
const sortOptions = [
    { label: '综合排序', value: 'default' },
    { label: '价格从低到高', value: 'price_asc' },
    { label: '价格从高到低', value: 'price_desc' },
    { label: '评分最高', value: 'rating' },
    { label: '销量最高', value: 'order_count' }
]

const $theme = useThemeStore()
const pagingRef = ref()
const tabbarRefreshKey = ref(0)
const queryReady = ref(false)
const keyword = ref('')
const staffList = ref<any[]>([])
const selectedDate = ref('')
const selectedRegion = ref(normalizeServiceRegion(loadServiceRegionSelection()))
const currentCategoryId = ref(0)
const currentCategoryName = ref('')
const selectedTagIds = ref<number[]>([])
const selectedTagNames = ref<string[]>([])
const currentSort = ref('default')
const staffViewMode = ref<StaffViewMode>('poster')

const isValidSortValue = (value: unknown): value is string =>
    sortOptions.some((item) => item.value === value)

const parseIdList = (value: unknown) =>
    Array.from(
        new Set(
            (Array.isArray(value) ? value : String(value || '').split(','))
                .map((item) => Number(item))
                .filter((item) => Number.isInteger(item) && item > 0)
        )
    )

const parseTextList = (value: unknown) =>
    String(value || '')
        .split(/[、,]/)
        .map((item) => item.trim())
        .filter(Boolean)

const pagingRefresherEnabled = computed(() => true)
const hasValidQuery = computed(() =>
    Boolean(
        selectedDate.value && hasServiceRegion(selectedRegion.value) && currentCategoryId.value > 0
    )
)

// 地区核心区县优先，杜绝折行截断
const displayDistrictText = computed(() => {
    const reg = selectedRegion.value
    if (reg?.district_name) return reg.district_name
    if (reg?.city_name) return reg.city_name
    if (reg?.province_name) return reg.province_name
    return '选择地区'
})

const normalizeSelectedDateText = (value = '') => {
    const [year, month, day] = value.split('-').map((item) => Number(item))
    if (!year || !month || !day) return ''
    const date = new Date(year, month - 1, day)
    date.setHours(0, 0, 0, 0)
    if (Number.isNaN(date.getTime())) return ''
    return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
}

// 日期精简为 M月D日，杜绝截断
const displayDateText = computed(() => {
    if (!selectedDate.value) return '选择日期'
    const parts = selectedDate.value.split('-')
    if (parts.length === 3) {
        const m = parseInt(parts[1], 10)
        const d = parseInt(parts[2], 10)
        return `${m}月${d}日`
    }
    return selectedDate.value
})

const isPriceSortActive = computed(() =>
    currentSort.value === 'price_asc' || currentSort.value === 'price_desc'
)

const handleSelectSort = (sortKey: string) => {
    if (sortKey === 'price') {
        if (currentSort.value === 'price_asc') {
            currentSort.value = 'price_desc'
        } else {
            currentSort.value = 'price_asc'
        }
    } else {
        if (currentSort.value === sortKey) return
        currentSort.value = sortKey
    }
    pagingRef.value?.reload()
}

const resolveStaffListError = (error: unknown, fallback = '操作失败') => {
    if (typeof error === 'string' && error.trim()) {
        return error
    }

    if (error && typeof error === 'object') {
        const value =
            (error as { msg?: unknown; message?: unknown }).msg ??
            (error as { message?: unknown }).message

        if (typeof value === 'string' && value.trim()) {
            return value
        }
    }

    return fallback
}

const buildScheduleQueryUrl = () => {
    const queryParts: string[] = []
    if (selectedDate.value) queryParts.push(`date=${encodeURIComponent(selectedDate.value)}`)
    if (currentCategoryId.value > 0) queryParts.push(`category_id=${currentCategoryId.value}`)
    if (currentCategoryName.value)
        queryParts.push(`category_name=${encodeURIComponent(currentCategoryName.value)}`)
    if (keyword.value) queryParts.push(`keyword=${encodeURIComponent(keyword.value)}`)
    if (selectedTagIds.value.length) queryParts.push(`tag_ids=${selectedTagIds.value.join(',')}`)
    if (selectedTagNames.value.length) {
        queryParts.push(`tag_names=${encodeURIComponent(selectedTagNames.value.join('、'))}`)
    }
    if (currentSort.value !== 'default')
        queryParts.push(`sort=${encodeURIComponent(currentSort.value)}`)
    const regionQuery = buildServiceRegionQuery(selectedRegion.value)
    if (regionQuery) queryParts.push(regionQuery)
    queryParts.push('source=staff_list')
    return queryParts.length
        ? `/pages/schedule_query/schedule_query?${queryParts.join('&')}`
        : '/pages/schedule_query/schedule_query'
}

const redirectToScheduleQuery = () => {
    const pages = getCurrentPages()
    // 智能检测：若上一页本身就是档期查询页，直接退回上一页，避免在栈中重复堆积两个相同的档期查询页
    if (pages && pages.length > 1) {
        const prevPage = pages[pages.length - 2]
        const prevRoute = (prevPage as any)?.route || (prevPage as any)?.__route__ || ''
        if (prevRoute.includes('schedule_query')) {
            uni.navigateBack({ delta: 1 })
            return
        }
    }
    // 上一页不是档期查询页时（如直接从首页、分享或Tab进入），使用 navigateTo 正常压栈
    // 保证用户在档期查询页点击返回时能正常返回当前列表页，杜绝 redirectTo 导致返回死循环
    uni.navigateTo({ url: buildScheduleQueryUrl() })
}

const getStaffAvatar = (item: any) => item?.avatar || DEFAULT_AVATAR

const getRecommendBadgeText = (item: any) => String(item?.recommend_text || '精选推荐').trim() || '精选推荐'

const normalizeTagList = (tags: unknown) => {
    if (Array.isArray(tags)) {
        return tags.map((tag: any) => String(tag || '').trim()).filter(Boolean)
    }
    if (typeof tags === 'string') {
        return parseTextList(tags)
    }
    return []
}

const getDisplayTags = (item: any, limit = 2) => {
    const originTags = normalizeTagList(item?.tags_arr).length
        ? normalizeTagList(item?.tags_arr)
        : normalizeTagList(item?.tags)
    return originTags
        .map((tag: any) => String(tag || '').trim())
        .filter(Boolean)
        .slice(0, limit)
}

const getCompactMetaText = (item: any) => {
    const parts = [item?.category_name || '主创人员']
    if (item?.experience_years) {
        parts.push(`${item.experience_years}年经验`)
    }
    return parts.join(' · ')
}

const formatRatingText = (item: any) => {
    const rating = Number(item?.rating || 0)
    return Number.isFinite(rating) && rating > 0 ? rating.toFixed(1) : '5.0'
}

const hasStaffPrice = (item: any) =>
    !(item?.has_price === false || item?.price === null || item?.price === undefined)

const getCleanPrice = (item: any) => {
    const raw = String(item.price_text || item.price || '')
    return raw.replace(/^[¥￥]/, '')
}

const handleEmptyAction = () => {
    redirectToScheduleQuery()
}

const handleToggleViewMode = () => {
    staffViewMode.value = staffViewMode.value === 'poster' ? 'list' : 'poster'
}

const queryList = async (pageNo: number, _pageSize: number) => {
    if (!queryReady.value || !hasValidQuery.value) {
        pagingRef.value?.complete([])
        return
    }

    try {
        const params: Record<string, any> = {
            page_no: pageNo,
            page_size: STAFF_LIST_PAGE_SIZE,
            category_id: currentCategoryId.value,
            date: selectedDate.value,
            sort: currentSort.value
        }
        if (keyword.value) params.keyword = keyword.value
        if (selectedTagIds.value.length) params.tag_ids = selectedTagIds.value.join(',')
        Object.assign(params, toServiceRegionParams(selectedRegion.value))
        const res = await getStaffList(params)
        pagingRef.value?.complete(res.lists)
    } catch (error) {
        pagingRef.value?.complete(false)
    }
}

const handleToggleFavorite = async (item: any) => {
    try {
        await toggleStaffFavorite({ id: item.id })
        item.is_favorite = !item.is_favorite
        showSuccess(item.is_favorite ? '收藏成功' : '已取消收藏')
    } catch (error: any) {
        showError(resolveStaffListError(error, '操作失败'))
    }
}

const goToDetail = (id: number) => {
    let url = `/packages/pages/staff_detail/staff_detail?id=${id}`
    const regionQuery = buildServiceRegionQuery(selectedRegion.value)
    if (regionQuery) url += `&${regionQuery}`
    if (selectedDate.value) url += `&date=${selectedDate.value}`
    uni.navigateTo({ url })
}

onLoad((options) => {
    $theme.setScene('consumer')
    selectedRegion.value = normalizeServiceRegion({
        ...loadServiceRegionSelection(),
        ...options
    })
    if (hasServiceRegion(selectedRegion.value)) {
        saveServiceRegionSelection(selectedRegion.value)
    }

    if (typeof options?.keyword === 'string') keyword.value = options.keyword.trim()
    if (typeof options?.date === 'string')
        selectedDate.value = normalizeSelectedDateText(options.date)
    if (options?.category_id) {
        const categoryId = Number(options.category_id)
        if (!Number.isNaN(categoryId) && categoryId > 0) currentCategoryId.value = categoryId
    }
    if (typeof options?.category_name === 'string')
        currentCategoryName.value = options.category_name.trim()
    if (options?.tag_ids) selectedTagIds.value = parseIdList(options.tag_ids)
    if (typeof options?.tag_names === 'string')
        selectedTagNames.value = parseTextList(options.tag_names)
    if (isValidSortValue(options?.sort)) currentSort.value = String(options?.sort)

    if (!hasValidQuery.value) {
        redirectToScheduleQuery()
        return
    }

    queryReady.value = true
})

onReady(() => {
    if (queryReady.value) pagingRef.value?.reload()
})

onPageScroll((event) => {
    pagingRef.value?.updatePageScrollTop(event.scrollTop)
})

onReachBottom(() => {
    pagingRef.value?.pageReachBottom()
})

onShow(() => {
    $theme.setScene('consumer')
    tabbarRefreshKey.value += 1
})
</script>

<style lang="scss" scoped>
/* ==========================================================================
   Haute Wedding Couture - Staff List Page Design System
   ========================================================================== */
.staff-list-page {
    min-height: 100vh;
    background-color: var(--wm-color-bg-page, #FAF8F2);
    box-sizing: border-box;
}

/* 顶部纯粹黑金控制台 (一体沉浸，杜绝发灰黑雾渐变) */
.filter-header {
    background-color: #181614;
    padding: 12rpx 24rpx 16rpx;
    border-bottom: 1rpx solid rgba(217, 190, 130, 0.22);
    box-shadow: 0 10rpx 28rpx rgba(0, 0, 0, 0.22);
    position: sticky;
    top: 0;
    z-index: 20;
    box-sizing: border-box;

    &__summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        padding: 12rpx 20rpx;
        background: rgba(255, 253, 248, 0.06);
        border: 1rpx solid rgba(217, 190, 130, 0.22);
        border-radius: 999rpx;
        box-sizing: border-box;
        transition: background 0.15s ease;

        &:active {
            background: rgba(255, 253, 248, 0.1);
        }
    }

    &__toolbar {
        margin-top: 14rpx;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        padding: 0 8rpx;
    }
}

/* 筛选摘要内部 */
.summary-info {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 12rpx;
    overflow-x: auto;

    &::-webkit-scrollbar {
        display: none;
    }
}

.summary-tag {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 6rpx;

    &__text {
        font-size: 23rpx;
        font-weight: 700;
        color: #D9BE82;
        line-height: 1;
        white-space: nowrap;
    }

    &--category .summary-tag__text {
        color: #FFFDF8;
    }
}

.summary-divider {
    flex-shrink: 0;
    width: 1rpx;
    height: 18rpx;
    background: rgba(217, 190, 130, 0.25);
}

.summary-action {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 4rpx;
    padding-left: 10rpx;

    &__text {
        font-size: 22rpx;
        font-weight: 800;
        color: #C6A15B;
        line-height: 1;
    }
}

/* 极简文字排版排序项 */
.sort-tabs {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 36rpx;
}

.sort-tab-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 6rpx 0 8rpx;

    &__text {
        font-size: 26rpx;
        font-weight: 600;
        color: #9E9689;
        line-height: 1.2;
        transition: color 0.15s ease;
    }

    &--active {
        .sort-tab-item__text {
            color: #FFFDF8;
            font-weight: 800;
        }
    }

    &__line {
        position: absolute;
        bottom: 0;
        width: 30rpx;
        height: 4rpx;
        border-radius: 999rpx;
        background: linear-gradient(90deg, #D9BE82 0%, #C6A15B 100%);
        box-shadow: 0 2rpx 8rpx rgba(198, 161, 91, 0.4);
    }

    &--price {
        flex-direction: row;
        align-items: center;
        gap: 4rpx;

        .sort-tab-item__line {
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
        }
    }

    &__arrows {
        display: flex;
        flex-direction: column;
        line-height: 1;
        font-size: 13rpx;
        transform: scale(0.72);
        margin-left: 2rpx;
    }
}

.price-arrow {
    color: rgba(255, 253, 248, 0.25);
    height: 11rpx;
    line-height: 11rpx;

    &--active {
        color: #C6A15B;
        font-weight: 900;
    }
}

/* 视图切换按钮：磨砂黑金小方纽 */
.view-switch-box {
    flex-shrink: 0;
    width: 58rpx;
    height: 58rpx;
    border-radius: 16rpx;
    background: rgba(255, 253, 248, 0.08);
    border: 1rpx solid rgba(217, 190, 130, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s ease;

    &:active {
        background: rgba(255, 253, 248, 0.16);
    }
}

/* 列表容器 */
.paging-state {
    padding: 24rpx 28rpx 220rpx;
}

.paging-load-more {
    min-height: 72rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12rpx 0 24rpx;

    &--idle {
        min-height: 0;
        padding: 0;
    }

    &__text {
        font-size: 22rpx;
        font-weight: 600;
        color: #A89F91;
    }
}

/* ==========================================================================
   视图 1：双列海报卡片 (Poster Grid)
   ========================================================================== */
.poster-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20rpx;
    padding: 14rpx 24rpx calc(190rpx + env(safe-area-inset-bottom));
}

.poster-card {
    background: #FFFDF8;
    border-radius: 24rpx;
    overflow: hidden;
    border: 1rpx solid rgba(217, 190, 130, 0.26);
    box-shadow: 0 12rpx 32rpx rgba(40, 32, 20, 0.06);
    display: flex;
    flex-direction: column;
    transition: transform 0.15s ease, box-shadow 0.15s ease;

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 6rpx 16rpx rgba(40, 32, 20, 0.08);
    }

    &__media {
        position: relative;
        width: 100%;
        height: 360rpx; /* 黄金 3:4 画幅，尽显大师神采 */
        background: #242220;
        overflow: hidden;
    }

    &__image {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__shade {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(24, 22, 20, 0.12) 0%, rgba(24, 22, 20, 0.05) 50%, rgba(24, 22, 20, 0.72) 100%);
        pointer-events: none;
    }

    &__badge-wrap {
        position: absolute;
        top: 14rpx;
        left: 14rpx;
        z-index: 2;
        padding: 6rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(24, 22, 20, 0.85);
        border: 1rpx solid rgba(217, 190, 130, 0.45);
        backdrop-filter: blur(8px);
    }

    &__badge-text {
        font-size: 18rpx;
        font-weight: 800;
        color: #D9BE82;
        line-height: 1;
    }

    &__favorite {
        position: absolute;
        top: 12rpx;
        right: 12rpx;
        z-index: 2;
    }

    &__media-bottom {
        position: absolute;
        left: 16rpx;
        bottom: 14rpx;
        right: 16rpx;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    &__category {
        font-size: 20rpx;
        font-weight: 800;
        color: #FFFDF8;
        padding: 4rpx 12rpx;
        border-radius: 8rpx;
        background: rgba(198, 161, 91, 0.9);
        line-height: 1.2;
    }

    &__exp {
        font-size: 19rpx;
        font-weight: 600;
        color: rgba(255, 253, 248, 0.88);
        line-height: 1.2;
    }

    &__content {
        padding: 18rpx 18rpx 20rpx;
        display: flex;
        flex-direction: column;
        gap: 12rpx;
    }

    &__row-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8rpx;
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: 28rpx;
        font-weight: 900;
        color: #191713;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
    }

    &__score-box {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        padding: 4rpx 10rpx;
        border-radius: 999rpx;
        background: #F8F2E4;
    }

    &__score-val {
        font-size: 20rpx;
        font-weight: 800;
        color: #9A6B35;
        line-height: 1;
    }

    &__tags {
        display: flex;
        align-items: center;
        gap: 8rpx;
        flex-wrap: wrap;
    }

    &__tag-pill {
        font-size: 19rpx;
        font-weight: 600;
        color: #7A5B20;
        background: rgba(217, 190, 130, 0.16);
        padding: 4rpx 10rpx;
        border-radius: 6rpx;
        line-height: 1.2;
    }

    &__row-footer {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 8rpx;
        padding-top: 2rpx;
        border-top: 1rpx solid rgba(217, 190, 130, 0.14);
    }

    &__price-group {
        display: flex;
        align-items: baseline;
        gap: 2rpx;
    }

    &__price-symbol {
        font-size: 22rpx;
        font-weight: 900;
        color: #C6A15B;
        font-family: Georgia, serif;
    }

    &__price-num {
        font-size: 32rpx;
        font-weight: 900;
        color: #191713;
        line-height: 1;
        letter-spacing: -0.5rpx;
    }

    &__price-unit {
        font-size: 19rpx;
        font-weight: 600;
        color: #8E8880;
    }

    &__price-negotiable {
        font-size: 26rpx;
        font-weight: 800;
        color: #8E8880;
    }

    &__order-count {
        font-size: 19rpx;
        font-weight: 600;
        color: #8E8880;
        white-space: nowrap;
    }
}

/* 磨砂心形圆钮 */
.favorite-circle {
    width: 58rpx;
    height: 58rpx;
    border-radius: 50%;
    background: rgba(24, 22, 20, 0.6);
    backdrop-filter: blur(8px);
    border: 1rpx solid rgba(255, 253, 248, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;

    &--active {
        background: rgba(24, 22, 20, 0.85);
        border-color: rgba(217, 190, 130, 0.6);
    }
}

/* ==========================================================================
   视图 2：单列优雅名片 (Line List)
   ========================================================================== */
.line-list {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    padding: 14rpx 24rpx calc(190rpx + env(safe-area-inset-bottom));
}

.line-card {
    background: #FFFDF8;
    border-radius: 24rpx;
    padding: 22rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.26);
    box-shadow: 0 12rpx 32rpx rgba(40, 32, 20, 0.05);
    display: flex;
    align-items: center;
    gap: 20rpx;
    transition: transform 0.15s ease;

    &:active {
        transform: translateY(2rpx);
    }

    &__avatar-wrap {
        position: relative;
        width: 136rpx;
        height: 136rpx;
        flex-shrink: 0;
        border-radius: 20rpx;
        overflow: hidden;
        border: 2rpx solid rgba(217, 190, 130, 0.35);
        background: #242220;
    }

    &__avatar {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__recommend-badge {
        position: absolute;
        top: 0;
        left: 0;
        padding: 2rpx 10rpx;
        border-bottom-right-radius: 12rpx;
        background: linear-gradient(135deg, #C6A15B 0%, #A88243 100%);
        font-size: 18rpx;
        font-weight: 800;
        color: #FFFDF8;
        line-height: 1.2;
    }

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }

    &__header {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    &__name {
        font-size: 30rpx;
        font-weight: 900;
        color: #191713;
        line-height: 1.2;
    }

    &__role-badge {
        font-size: 19rpx;
        font-weight: 700;
        color: #7A5B20;
        background: rgba(217, 190, 130, 0.2);
        padding: 2rpx 10rpx;
        border-radius: 6rpx;
        line-height: 1.2;
    }

    &__tags {
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    &__tag {
        font-size: 20rpx;
        color: #665E52;
        background: #F4EFE6;
        padding: 2rpx 10rpx;
        border-radius: 6rpx;
        line-height: 1.2;
    }

    &__desc {
        font-size: 22rpx;
        color: #8E8880;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__metrics {
        display: flex;
        align-items: center;
        gap: 8rpx;
        font-size: 21rpx;
        color: #8E8880;
    }

    &__score {
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
    }

    &__score-num {
        font-weight: 800;
        color: #9A6B35;
    }

    &__dot {
        color: #C8A45D;
    }

    &__orders {
        font-weight: 600;
    }

    &__action {
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: space-between;
        min-height: 120rpx;
    }

    &__price-box {
        display: flex;
        align-items: baseline;
        gap: 2rpx;
    }

    &__price-symbol {
        font-size: 22rpx;
        font-weight: 900;
        color: #C6A15B;
        font-family: Georgia, serif;
    }

    &__price-val {
        font-size: 34rpx;
        font-weight: 900;
        color: #191713;
        line-height: 1;
    }

    &__price-unit {
        font-size: 20rpx;
        font-weight: 600;
        color: #8E8880;
    }

    &__price-negotiable {
        font-size: 28rpx;
        font-weight: 800;
        color: #8E8880;
    }

    &__fav-btn {
        width: 60rpx;
        height: 60rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
</style>
