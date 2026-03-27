<template>
    <page-meta :page-style="$theme.pageStyle" />
    <BaseNavbar title="人员列表" />

    <view class="staff-list-page page-with-tabbar-safe-bottom">
        <view class="result-summary">
            <view class="result-summary__head">
                <text class="result-summary__eyebrow">当前筛选</text>
                <text class="result-summary__hint">结果已按所选档期与服务条件匹配</text>
            </view>

            <view class="result-summary__grid">
                <view class="result-summary__item result-summary__item--date">
                    <text class="result-summary__label">预约日期</text>
                    <text class="result-summary__value">{{ selectedDate }}</text>
                </view>
                <view class="result-summary__item">
                    <text class="result-summary__label">服务地区</text>
                    <text class="result-summary__value">{{ selectedRegionText }}</text>
                </view>
                <view class="result-summary__item">
                    <text class="result-summary__label">服务分类</text>
                    <text class="result-summary__value">{{ displayCategoryName }}</text>
                </view>
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
                <view class="empty-state">
                    <view class="empty-state__icon">
                        <tn-icon name="inbox" size="152" color="#D1D5DB" />
                    </view>
                    <text class="empty-state__title">当前筛选暂无可预约团队</text>
                    <text class="empty-state__subtitle">
                        返回档期查询页调整条件后，再重新筛选一批更合适的人员。
                    </text>
                    <view
                        class="empty-state__btn"
                        :style="getPrimaryButtonStyle(0.22)"
                        @tap="handleEmptyAction"
                    >
                        <text class="empty-state__btn-text" :style="{ color: $theme.btnColor }">
                            返回重筛
                        </text>
                    </view>
                </view>
            </template>

            <view v-if="staffViewMode === 'poster'" class="poster-list">
                <view
                    v-for="item in staffList"
                    :key="item.id"
                    class="poster-card"
                    @tap="goToDetail(item.id)"
                >
                    <image
                        class="poster-card__image"
                        :src="item.avatar || '/static/images/user/default_avatar.png'"
                        mode="aspectFill"
                    />

                    <view class="poster-card__body">
                        <view class="poster-card__head">
                            <view class="poster-card__name-group">
                                <text class="poster-card__name">{{ item.name }}</text>
                                <text v-if="item.is_recommend" class="poster-card__badge">推荐</text>
                            </view>
                            <view class="poster-card__favorite" @tap.stop="handleToggleFavorite(item)">
                                <tn-icon
                                    :name="item.is_favorite ? 'like-fill' : 'like'"
                                    size="34"
                                    :color="item.is_favorite ? '#FF4D5A' : '#C7B9AF'"
                                />
                            </view>
                        </view>

                        <text class="poster-card__role">
                            {{ item.category_name || '服务人员' }}
                            <text v-if="item.experience_years"> · {{ item.experience_years }}年经验</text>
                        </text>

                        <text class="poster-card__desc">{{ buildStaffDescription(item) }}</text>

                        <view class="poster-card__meta">
                            <text>评分 {{ item.rating || '0.0' }}</text>
                            <text>{{ item.order_count || 0 }} 单</text>
                            <text>{{ formatPriceText(item) }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <view v-else class="compact-list">
                <view
                    v-for="item in staffList"
                    :key="item.id"
                    class="compact-item"
                    @tap="goToDetail(item.id)"
                >
                    <image
                        class="compact-item__image"
                        :src="item.avatar || '/static/images/user/default_avatar.png'"
                        mode="aspectFill"
                    />

                    <view class="compact-item__body">
                        <view class="compact-item__head">
                            <view class="compact-item__name-group">
                                <text class="compact-item__name">{{ item.name }}</text>
                                <text v-if="item.is_recommend" class="compact-item__badge">推荐</text>
                            </view>
                            <view class="compact-item__favorite" @tap.stop="handleToggleFavorite(item)">
                                <tn-icon
                                    :name="item.is_favorite ? 'like-fill' : 'like'"
                                    size="30"
                                    :color="item.is_favorite ? '#FF4D5A' : '#D5CCC5'"
                                />
                            </view>
                        </view>

                        <text class="compact-item__line">
                            {{ item.category_name || '服务人员' }}
                            <text v-if="item.experience_years"> · {{ item.experience_years }}年经验</text>
                        </text>

                        <text class="compact-item__desc">{{ buildStaffDescription(item) }}</text>

                        <view class="compact-item__meta">
                            <text>评分 {{ item.rating || '0.0' }}</text>
                            <text>{{ item.order_count || 0 }} 单</text>
                            <text>{{ formatPriceText(item) }}</text>
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
                    <view class="switch-icon-list__dot" :style="{ background: $theme.primaryColor }" />
                    <view class="switch-icon-list__line" :style="{ background: $theme.primaryColor }" />
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
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { onLoad, onReady, onShow } from '@dcloudio/uni-app'
import { getStaffList, toggleStaffFavorite } from '@/api/staff'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'
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

const STAFF_VIEW_MODE_STORAGE_KEY = 'staff_list_view_mode'
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

const getInitialStaffViewMode = (): StaffViewMode => {
    try {
        const cachedMode = uni.getStorageSync(STAFF_VIEW_MODE_STORAGE_KEY)
        return cachedMode === 'list' ? 'list' : 'poster'
    } catch (error) {
        console.error(error)
        return 'poster'
    }
}

const staffViewMode = ref<StaffViewMode>(getInitialStaffViewMode())

const pagingRefresherEnabled = computed(() => import.meta.env.UNI_PLATFORM !== 'h5')
const hasValidQuery = computed(
    () => Boolean(selectedDate.value && hasServiceRegion(selectedRegion.value) && currentCategoryId.value > 0)
)
const selectedRegionText = computed(() => {
    const cityName = selectedRegion.value.city_name || selectedRegion.value.province_name
    const districtName = selectedRegion.value.district_name
    if (cityName && districtName) {
        return `${cityName} · ${districtName}`
    }
    return formatServiceRegionText(selectedRegion.value, ' / ') || '未选择'
})
const displayCategoryName = computed(() => currentCategoryName.value || '已选分类')

const normalizeSelectedDateText = (value = '') => {
    const [year, month, day] = value.split('-').map((item) => Number(item))
    if (!year || !month || !day) return ''
    const date = new Date(year, month - 1, day)
    date.setHours(0, 0, 0, 0)
    if (Number.isNaN(date.getTime())) return ''
    return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
}

const getPrimaryGradient = () =>
    `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || '#C99B73'} 100%)`

const getPrimaryButtonStyle = (alpha = 0.2) => ({
    backgroundColor: $theme.primaryColor,
    backgroundImage: getPrimaryGradient(),
    boxShadow: `0 12rpx 28rpx ${alphaColor($theme.primaryColor, alpha)}`
})

const getSwitchButtonStyle = () => ({
    backgroundColor: 'rgba(255,255,255,0.94)',
    borderColor: alphaColor($theme.primaryColor, 0.18),
    boxShadow: `0 14rpx 30rpx ${alphaColor('#D4B09A', 0.18)}`
})

const buildScheduleQueryUrl = () => {
    const queryParts: string[] = []
    if (selectedDate.value) queryParts.push(`date=${encodeURIComponent(selectedDate.value)}`)
    if (currentCategoryId.value > 0) queryParts.push(`category_id=${currentCategoryId.value}`)
    if (currentCategoryName.value) queryParts.push(`category_name=${encodeURIComponent(currentCategoryName.value)}`)
    if (keyword.value) queryParts.push(`keyword=${encodeURIComponent(keyword.value)}`)
    if (selectedTagIds.value.length) queryParts.push(`tag_ids=${selectedTagIds.value.join(',')}`)
    if (selectedTagNames.value.length) {
        queryParts.push(`tag_names=${encodeURIComponent(selectedTagNames.value.join('、'))}`)
    }
    if (currentSort.value !== 'default') queryParts.push(`sort=${encodeURIComponent(currentSort.value)}`)
    const regionQuery = buildServiceRegionQuery(selectedRegion.value)
    if (regionQuery) queryParts.push(regionQuery)
    return queryParts.length
        ? `/pages/schedule_query/schedule_query?${queryParts.join('&')}`
        : '/pages/schedule_query/schedule_query'
}

const redirectToScheduleQuery = () => {
    uni.redirectTo({ url: buildScheduleQueryUrl() })
}

const buildStaffDescription = (item: any) => {
    const profile = String(item?.profile || '').trim()
    if (profile) return profile
    const tags = Array.isArray(item?.tags) ? item.tags.filter(Boolean).slice(0, 3) : []
    if (tags.length) return tags.join(' · ')
    return '点击查看服务档期与团队详情'
}

const formatPriceText = (item: any) => {
    if (item?.has_price === false || item?.price === null || item?.price === undefined) {
        return '面议'
    }
    return `¥${item.price_text || item.price}/次`
}

const handleEmptyAction = () => {
    redirectToScheduleQuery()
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

const handleToggleViewMode = () => {
    staffViewMode.value = staffViewMode.value === 'poster' ? 'list' : 'poster'
    try {
        uni.setStorageSync(STAFF_VIEW_MODE_STORAGE_KEY, staffViewMode.value)
    } catch (error) {
        console.error(error)
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
    if (typeof options?.date === 'string') selectedDate.value = normalizeSelectedDateText(options.date)
    if (options?.category_id) {
        const categoryId = Number(options.category_id)
        if (!Number.isNaN(categoryId) && categoryId > 0) currentCategoryId.value = categoryId
    }
    if (typeof options?.category_name === 'string') currentCategoryName.value = options.category_name.trim()
    if (options?.tag_ids) selectedTagIds.value = parseIdList(options.tag_ids)
    if (typeof options?.tag_names === 'string') selectedTagNames.value = parseTextList(options.tag_names)
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
    min-height: 100vh;
    background:
        radial-gradient(circle at top right, rgba(232, 90, 79, 0.08) 0, transparent 34%),
        linear-gradient(180deg, #fcfbf9 0%, #fff7f4 100%);
}

.result-summary {
    margin: 34rpx 20rpx 18rpx;
}

.result-summary__head {
    padding: 0 8rpx;
}

.result-summary__eyebrow {
    display: block;
    font-size: 22rpx;
    font-weight: 700;
    letter-spacing: 4rpx;
    color: rgba(232, 90, 79, 0.88);
}

.result-summary__hint {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.5;
    color: #8d837d;
}

.result-summary__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
    margin-top: 18rpx;
}

.result-summary__item {
    min-height: 128rpx;
    padding: 20rpx 18rpx 18rpx;
    border-radius: 28rpx;
    border: 1rpx solid rgba(239, 230, 225, 0.92);
    background: rgba(255, 255, 255, 0.94);
    box-shadow: 0 10rpx 22rpx rgba(214, 185, 167, 0.08);
}

.result-summary__item--date {
    border-color: rgba(232, 90, 79, 0.18);
    background: linear-gradient(180deg, rgba(232, 90, 79, 0.08) 0%, rgba(255, 255, 255, 0.96) 100%);
}

.result-summary__label {
    display: block;
    font-size: 20rpx;
    font-weight: 600;
    color: #8d837d;
}

.result-summary__value {
    display: -webkit-box;
    margin-top: 12rpx;
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1.45;
    color: #1e2432;
    overflow: hidden;
    text-overflow: ellipsis;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    word-break: break-all;
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
    color: #1e2432;
}

.empty-state__subtitle {
    margin-top: 12rpx;
    font-size: 26rpx;
    line-height: 1.6;
    color: #7f7b78;
}

.empty-state__btn {
    margin-top: 34rpx;
    min-width: 240rpx;
    height: 88rpx;
    padding: 0 40rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.empty-state__btn-text {
    font-size: 28rpx;
    font-weight: 600;
}

.poster-list,
.compact-list {
    padding: 0 20rpx calc(196rpx + env(safe-area-inset-bottom));
}

.poster-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.poster-card {
    display: flex;
    gap: 18rpx;
    padding: 16rpx;
    border-radius: 28rpx;
    border: 1rpx solid rgba(239, 230, 225, 0.92);
    background: rgba(255, 255, 255, 0.88);
    box-shadow: 0 16rpx 32rpx rgba(214, 185, 167, 0.12);
}

.poster-card__image {
    width: 168rpx;
    height: 192rpx;
    flex-shrink: 0;
    border-radius: 20rpx;
    background: linear-gradient(135deg, #fce7e1 0%, #ddb4a6 100%);
}

.poster-card__body,
.compact-item__body {
    flex: 1;
    min-width: 0;
}

.poster-card__head,
.compact-item__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12rpx;
}

.poster-card__name-group,
.compact-item__name-group {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 10rpx;
}

.poster-card__name,
.compact-item__name {
    min-width: 0;
    font-size: 30rpx;
    font-weight: 700;
    color: #1e2432;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.poster-card__badge,
.compact-item__badge {
    flex-shrink: 0;
    padding: 4rpx 12rpx;
    border-radius: 999rpx;
    font-size: 20rpx;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #e85a4f 0%, #c99b73 100%);
}

.poster-card__role,
.compact-item__line {
    display: block;
    margin-top: 12rpx;
    font-size: 24rpx;
    line-height: 1.5;
    color: #8d837d;
}

.poster-card__desc,
.compact-item__desc {
    display: -webkit-box;
    margin-top: 8rpx;
    font-size: 26rpx;
    line-height: 1.6;
    color: #1e2432;
    overflow: hidden;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.poster-card__meta,
.compact-item__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
    margin-top: 14rpx;
    font-size: 22rpx;
    color: #8d837d;
}

.compact-list {
    display: flex;
    flex-direction: column;
}

.compact-item {
    display: flex;
    gap: 16rpx;
    padding: 18rpx 0;
    border-bottom: 1rpx solid rgba(239, 230, 225, 0.92);
}

.compact-item__image {
    width: 96rpx;
    height: 96rpx;
    flex-shrink: 0;
    border-radius: 20rpx;
    background: linear-gradient(135deg, #fce7e1 0%, #ddb4a6 100%);
}

.view-switch-btn {
    position: fixed;
    right: 28rpx;
    bottom: calc(164rpx + env(safe-area-inset-bottom));
    z-index: 30;
    width: 88rpx;
    height: 88rpx;
    border-radius: 50%;
    border: 1rpx solid transparent;
    display: flex;
    align-items: center;
    justify-content: center;
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
</style>
