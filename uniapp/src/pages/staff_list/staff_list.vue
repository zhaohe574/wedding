<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="editorial" hasTabbar>
        <BaseNavbar title="星意主持工作室" />

        <view class="staff-list-page">
            <view class="staff-list-page__query-area">
                <view class="staff-list-page__query-bar">
                    <view class="staff-list-page__query-field" @tap="redirectToScheduleQuery">
                        <tn-icon name="calendar" size="24" color="#9A9388" />
                        <text class="staff-list-page__query-text">{{ selectedDateText }}</text>
                    </view>
                    <view
                        class="staff-list-page__query-field staff-list-page__query-field--category"
                        @tap="redirectToScheduleQuery"
                    >
                        <text class="staff-list-page__query-text">{{
                            currentCategoryName || '全部'
                        }}</text>
                        <tn-icon name="down" size="20" color="#9A9388" />
                    </view>
                    <view class="staff-list-page__query-submit" @tap="redirectToScheduleQuery">
                        <text class="staff-list-page__query-submit-text">立即查询</text>
                    </view>
                </view>
            </view>

            <view class="staff-list-page__filter-row">
                <view
                    v-for="tab in staffFilterTabs"
                    :key="tab.key"
                    class="staff-list-page__filter-item"
                    @tap="redirectToScheduleQuery"
                >
                    <text class="staff-list-page__filter-text">{{ tab.label }}</text>
                    <tn-icon name="down" size="18" color="#111111" />
                </view>
            </view>

            <z-paging
                ref="pagingRef"
                v-model="staffList"
                :auto="false"
                :default-page-size="STAFF_LIST_PAGE_SIZE"
                :fixed="false"
                :refresher-enabled="pagingRefresherEnabled"
                use-page-scroll
                @query="queryList"
            >
                <template #empty>
                    <EmptyState
                        title="当前筛选暂无可预约团队"
                        description="调整筛选条件后重试。"
                        action-text="返回重筛"
                        @action="handleEmptyAction"
                    />
                </template>

                <view v-if="staffViewMode === 'poster'" class="poster-list">
                    <view
                        v-for="item in staffList"
                        :key="item.id"
                        class="poster-card"
                        @tap="goToDetail(item.id)"
                    >
                        <view class="poster-card__media">
                            <image
                                class="poster-card__image"
                                :src="item.avatar || '/static/images/user/default_avatar.png'"
                                mode="aspectFill"
                                lazy-load
                            />
                            <text class="poster-card__badge">{{ formatPosterBadge(item) }}</text>
                        </view>

                        <view class="poster-card__content">
                            <view class="poster-card__name-row">
                                <text class="poster-card__name">{{ item.name || '未命名人员' }}</text>
                                <view v-if="getDisplayTags(item).length" class="poster-card__tags">
                                    <text
                                        v-for="tag in getDisplayTags(item)"
                                        :key="`${item.id}-${tag}`"
                                        class="poster-card__tag"
                                    >
                                        {{ tag }}
                                    </text>
                                </view>
                            </view>
                            <view v-if="getDisplayTags(item).length > 2" class="poster-card__tags poster-card__tags--wrap">
                                <text
                                    v-for="tag in getDisplayTags(item).slice(2)"
                                    :key="`${item.id}-more-${tag}`"
                                    class="poster-card__tag"
                                >
                                    {{ tag }}
                                </text>
                            </view>
                            <text class="poster-card__desc">
                                {{ buildStaffDescription(item) || formatRoleLine(item) }}
                            </text>

                            <view
                                class="poster-card__price"
                                :class="{ 'poster-card__price--negotiable': !hasStaffPrice(item) }"
                            >
                                <text class="poster-card__price-value">{{
                                    getStaffPriceValue(item)
                                }}</text>
                                <text v-if="getStaffPriceSuffix(item)" class="poster-card__price-unit">
                                    {{ getStaffPriceSuffix(item) }}
                                </text>
                            </view>
                        </view>
                    </view>
                </view>

                <view v-else class="line-list">
                    <view
                        v-for="item in staffList"
                        :key="item.id"
                        class="line-card"
                        @tap="goToDetail(item.id)"
                    >
                        <image
                            class="line-card__image"
                            :src="item.avatar || '/static/images/user/default_avatar.png'"
                            mode="aspectFill"
                            lazy-load
                        />

                        <view class="line-card__content">
                            <view class="line-card__head">
                                <view class="line-card__name-group">
                                    <text class="line-card__name">{{
                                        item.name || '未命名人员'
                                    }}</text>
                                    <text v-if="item.is_recommend" class="line-card__badge"
                                        >推荐</text
                                    >
                                </view>
                                <view
                                    class="line-card__favorite"
                                    @tap.stop="handleToggleFavorite(item)"
                                >
                                    <tn-icon
                                        :name="item.is_favorite ? 'like-fill' : 'like'"
                                        size="30"
                                        :color="item.is_favorite ? '#8A4B45' : '#D8D3C7'"
                                    />
                                </view>
                            </view>

                            <text class="line-card__role">{{ formatRoleLine(item) }}</text>

                            <view v-if="getDisplayTags(item, 3).length" class="line-card__tags">
                                <text
                                    v-for="tag in getDisplayTags(item, 3)"
                                    :key="`${item.id}-line-${tag}`"
                                    class="line-card__tag"
                                >
                                    {{ tag }}
                                </text>
                            </view>
                            <text v-else-if="buildStaffDescription(item)" class="line-card__desc">
                                {{ buildStaffDescription(item) }}
                            </text>

                            <view class="line-card__footer">
                                <view class="line-card__metrics">
                                    <view class="line-card__score">
                                        <tn-icon name="star-fill" size="20" color="#C8A45D" />
                                        <text class="line-card__score-text">{{
                                            formatRatingText(item)
                                        }}</text>
                                    </view>
                                    <text class="line-card__orders"
                                        >{{ item.order_count || 0 }}单</text
                                    >
                                </view>
                                <text class="line-card__price">{{ formatPriceText(item) }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </z-paging>

            <view
                class="view-switch-btn"
                :style="getSwitchButtonStyle()"
                @tap.stop="handleToggleViewMode"
            >
                <view v-if="staffViewMode === 'poster'" class="switch-icon-list">
                    <view v-for="row in 3" :key="row" class="switch-icon-list__row">
                        <view
                            class="switch-icon-list__dot"
                            :style="{ background: $theme.primaryColor }"
                        />
                        <view
                            class="switch-icon-list__line"
                            :style="{ background: $theme.primaryColor }"
                        />
                    </view>
                </view>
                <view v-else class="switch-icon-grid">
                    <view
                        v-for="cell in 4"
                        :key="cell"
                        class="switch-icon-grid__cell"
                        :style="{ borderColor: $theme.primaryColor }"
                    />
                </view>
            </view>

            <tabbar :badge-refresh-key="tabbarRefreshKey" />
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { onLoad, onReady, onShow } from '@dcloudio/uni-app'
import { getStaffList, toggleStaffFavorite } from '@/api/staff'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'
import {
    buildServiceRegionQuery,
    hasServiceRegion,
    loadServiceRegionSelection,
    normalizeServiceRegion,
    saveServiceRegionSelection,
    toServiceRegionParams
} from '@/utils/service-region'

type StaffViewMode = 'poster' | 'list'

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

const pagingRefresherEnabled = computed(() => import.meta.env.UNI_PLATFORM !== 'h5')
const hasValidQuery = computed(() =>
    Boolean(
        selectedDate.value && hasServiceRegion(selectedRegion.value) && currentCategoryId.value > 0
    )
)
const normalizeSelectedDateText = (value = '') => {
    const [year, month, day] = value.split('-').map((item) => Number(item))
    if (!year || !month || !day) return ''
    const date = new Date(year, month - 1, day)
    date.setHours(0, 0, 0, 0)
    if (Number.isNaN(date.getTime())) return ''
    return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
}

const selectedDateText = computed(
    () => normalizeSelectedDateText(selectedDate.value) || '未选择日期'
)
const staffFilterTabs = computed(() => [
    { key: 'sort', label: '排序' },
    { key: 'price', label: '价位' },
    { key: 'type', label: '类型' },
    { key: 'gender', label: '性别' }
])

const getSwitchButtonStyle = () => ({
    backgroundColor: 'rgba(255, 255, 255, 0.91)',
    borderColor: '#E7E2D6',
    boxShadow: `0 12rpx 28rpx ${alphaColor('#C8A45D', 0.12)}`
})

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
    uni.redirectTo({ url: buildScheduleQueryUrl() })
}

const buildStaffDescription = (item: any) => {
    return String(item?.profile || '').trim()
}

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

const formatRoleLine = (item: any) => {
    const parts = [item?.category_name || '服务人员']
    if (item?.experience_years) {
        parts.push(`${item.experience_years}年经验`)
    }
    return parts.join(' · ')
}

const formatPosterBadge = (item: any) => {
    if (item?.is_recommend) {
        return '联合创始人'
    }

    const tags = getDisplayTags(item, 1)
    return String(item?.category_name || tags[0] || currentCategoryName.value || '资深主持').trim()
}

const formatRatingText = (item: any) => {
    const rating = Number(item?.rating || 0)
    return Number.isFinite(rating) ? rating.toFixed(1) : '0.0'
}

const hasStaffPrice = (item: any) =>
    !(item?.has_price === false || item?.price === null || item?.price === undefined)

const getStaffPriceValue = (item: any) => {
    if (!hasStaffPrice(item)) {
        return '面议'
    }
    return `¥${item.price_text || item.price}`
}

const getStaffPriceSuffix = (item: any) => {
    if (!hasStaffPrice(item)) {
        return ''
    }
    return '/次'
}

const formatPriceText = (item: any) => {
    if (!hasStaffPrice(item)) {
        return '面议'
    }
    return `¥${item.price_text || item.price}/次`
}

const handleEmptyAction = () => {
    redirectToScheduleQuery()
}

const handleToggleViewMode = () => {
    staffViewMode.value = staffViewMode.value === 'poster' ? 'list' : 'poster'
}

const queryList = async (pageNo: number, _pageSize: number) => {
    if (!queryReady.value || !hasValidQuery.value) {
        pagingRef.value.complete([])
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
        pagingRef.value.complete(res.lists)
    } catch (error) {
        pagingRef.value.complete(false)
    }
}

const handleToggleFavorite = async (item: any) => {
    try {
        await toggleStaffFavorite({ id: item.id })
        item.is_favorite = !item.is_favorite
        uni.showToast({
            title: item.is_favorite ? '收藏成功' : '已取消收藏',
            icon: 'none'
        })
    } catch (error: any) {
        uni.showToast({ title: error?.msg || '操作失败', icon: 'none' })
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

onShow(() => {
    $theme.setScene('consumer')
    tabbarRefreshKey.value += 1
})
</script>

<style lang="scss" scoped>
.staff-list-page {
    min-height: 100%;
    background: #ffffff;
}

.staff-list-page__query-area {
    padding: 16rpx 22rpx 18rpx;
    background: #000000;
}

.staff-list-page__query-bar {
    display: flex;
    align-items: center;
    min-height: 58rpx;
    overflow: hidden;
    border-radius: 12rpx;
    background: #ffffff;
}

.staff-list-page__query-field {
    flex: 1;
    min-width: 0;
    height: 58rpx;
    padding: 0 20rpx;
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.staff-list-page__query-field--category {
    max-width: 170rpx;
    justify-content: center;
    border-left: 1rpx solid rgba(11, 11, 11, 0.08);
}

.staff-list-page__query-text {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 25rpx;
    line-height: 1;
    color: #111111;
}

.staff-list-page__query-submit {
    width: 202rpx;
    height: 58rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #d0021b;
}

.staff-list-page__query-submit-text {
    font-size: 24rpx;
    line-height: 1;
    font-weight: 700;
    color: #ffffff;
}

.staff-list-page__filter-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    padding: 22rpx 22rpx 18rpx;
    background: #ffffff;
    border-bottom: 1rpx solid rgba(11, 11, 11, 0.06);
}

.staff-list-page__filter-item {
    min-height: 48rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5rpx;
}

.staff-list-page__filter-text {
    display: block;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-align: center;
    font-size: 25rpx;
    font-weight: 500;
    line-height: 1;
    color: #111111;
}

.empty-state {
    min-height: 58vh;
    padding: 120rpx 48rpx 220rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.empty-state__title {
    margin-top: 24rpx;
    font-size: 32rpx;
    font-weight: 600;
    color: #111111;
}

.empty-state__subtitle {
    margin-top: 12rpx;
    font-size: 26rpx;
    line-height: 1.6;
    color: #5f5a50;
}

.empty-state__btn {
    margin-top: 34rpx;
    min-width: 240rpx;
    height: 88rpx;
    padding: 0 32rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.empty-state__btn-text {
    font-size: 28rpx;
    font-weight: 600;
}

.poster-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    padding: 0 22rpx calc(132rpx + env(safe-area-inset-bottom));
    background: #ffffff;
}

.poster-card {
    width: calc(50% - 7rpx);
    margin-bottom: 26rpx;
    overflow: visible;
    border-radius: 0;
    border: none;
    background: transparent;
    box-shadow: none;

    &:active {
        transform: translateY(1rpx) scale(0.995);
    }
}

.poster-card__media {
    position: relative;
    height: 374rpx;
    overflow: hidden;
    border-radius: 5rpx;
    background: #f0efeb;
}

.poster-card__image {
    width: 100%;
    height: 100%;
    display: block;
}

.poster-card__badge {
    position: absolute;
    top: 0;
    left: 0;
    max-width: 150rpx;
    padding: 7rpx 12rpx;
    border-radius: 0;
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1;
    color: #ffffff;
    background: #b74c4c;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.poster-card__content {
    padding: 14rpx 2rpx 0;
}

.poster-card__name-row {
    display: flex;
    align-items: center;
    gap: 8rpx;
    min-width: 0;
}

.poster-card__name {
    flex-shrink: 0;
    min-width: 0;
    display: block;
    max-width: 142rpx;
    font-size: 29rpx;
    font-weight: 700;
    line-height: 1.25;
    color: #111111;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.poster-card__price {
    margin-top: 14rpx;
    display: inline-flex;
    align-items: baseline;
    gap: 4rpx;
}

.poster-card__price--negotiable .poster-card__price-value,
.poster-card__price--negotiable .poster-card__price-unit {
    color: #9a9388;
}

.poster-card__price-value {
    min-width: 0;
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.2;
    color: #ff1b1b;
}

.poster-card__price-unit {
    font-size: 18rpx;
    font-weight: 600;
    line-height: 1.2;
    color: #ff1b1b;
}

.poster-card__tags {
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 5rpx;
    min-width: 0;
}

.poster-card__tags--wrap {
    margin-top: 7rpx;
    flex-wrap: wrap;
}

.poster-card__tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28rpx;
    padding: 3rpx 7rpx;
    border-radius: 3rpx;
    font-size: 18rpx;
    line-height: 1.2;
    color: #5f5a50;
    background: #f3f3f3;
    border: none;
}

.poster-card__desc {
    display: -webkit-box;
    margin-top: 10rpx;
    min-height: 34rpx;
    font-size: 23rpx;
    line-height: 1.45;
    color: #253044;
    overflow: hidden;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
}

.line-list {
    padding: 20rpx 22rpx calc(132rpx + env(safe-area-inset-bottom));
    background: #ffffff;
}

.line-card {
    display: flex;
    gap: 16rpx;
    padding: 16rpx;
    min-height: 196rpx;
    border-radius: var(--wm-radius-card, 16rpx);
    border: 1rpx solid var(--wm-color-border, #e2ded5);
    background: rgba(255, 255, 255, 0.96);
    box-shadow: var(--wm-shadow-soft, 0 8rpx 20rpx rgba(17, 17, 17, 0.05));

    &:active {
        transform: translateY(1rpx) scale(0.995);
    }
}

.line-card + .line-card {
    margin-top: 16rpx;
}

.line-card__image {
    width: 164rpx;
    height: 164rpx;
    flex-shrink: 0;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    background: linear-gradient(135deg, #f7f0df 0%, #d8c28a 100%);
}

.line-card__content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.line-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12rpx;
}

.line-card__name-group {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.line-card__name {
    min-width: 0;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.35;
    color: #111111;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.line-card__badge {
    flex-shrink: 0;
    padding: 4rpx 12rpx;
    border-radius: 999rpx;
    font-size: 20rpx;
    font-weight: 600;
    color: #ffffff;
    background: linear-gradient(135deg, #0b0b0b 0%, #c8a45d 100%);
}

.line-card__favorite {
    width: 48rpx;
    height: 48rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.line-card__role {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    line-height: 1.45;
    color: #5f5a50;
}

.line-card__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8rpx;
    margin-top: 10rpx;
}

.line-card__tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 36rpx;
    padding: 6rpx 12rpx;
    border-radius: 999rpx;
    font-size: 20rpx;
    line-height: 1.2;
    color: var(--wm-color-primary, #0b0b0b);
    background: rgba(11, 11, 11, 0.08);
    border: 1rpx solid rgba(11, 11, 11, 0.16);
}

.line-card__desc {
    display: -webkit-box;
    margin-top: 10rpx;
    font-size: 22rpx;
    line-height: 1.5;
    color: #5f5a50;
    overflow: hidden;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.line-card__footer {
    margin-top: auto;
    padding-top: 14rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12rpx;
}

.line-card__metrics {
    display: flex;
    align-items: center;
    gap: 12rpx;
    min-width: 0;
}

.line-card__score {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
}

.line-card__score-text {
    font-size: 22rpx;
    font-weight: 700;
    line-height: 1.2;
    color: #9F7A2E;
}

.line-card__orders {
    font-size: 20rpx;
    line-height: 1.2;
    color: #5f5a50;
}

.line-card__price {
    flex-shrink: 0;
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-color-primary, #0b0b0b);
}

.view-switch-btn {
    position: fixed;
    right: 28rpx;
    bottom: calc(150rpx + env(safe-area-inset-bottom));
    z-index: 30;
    width: 88rpx;
    height: 88rpx;
    border-radius: 44rpx;
    border: 1rpx solid var(--wm-color-border, #e2ded5);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(16rpx);
    -webkit-backdrop-filter: blur(16rpx);
}

.switch-icon-list {
    width: 34rpx;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.switch-icon-list__row {
    display: flex;
    align-items: center;
    gap: 6rpx;
}

.switch-icon-list__dot {
    width: 6rpx;
    height: 6rpx;
    border-radius: 2rpx;
    flex-shrink: 0;
}

.switch-icon-list__line {
    flex: 1;
    height: 4rpx;
    border-radius: 999rpx;
}

.switch-icon-grid {
    width: 32rpx;
    height: 32rpx;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 4rpx;
}

.switch-icon-grid__cell {
    border: 2rpx solid transparent;
    border-radius: 6rpx;
    background: rgba(255, 255, 255, 0.45);
}

/* #ifdef MP-WEIXIN */
.view-switch-btn {
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
/* #endif */
</style>
