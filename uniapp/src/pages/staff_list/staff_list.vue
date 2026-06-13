<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasTabbar>
        <BaseNavbar
            title="人员列表"
            variant="solid"
            bg-color="#000000"
            text-color="#FFFDF8"
        />

        <view class="staff-list-page">
            <view class="filter-summary">
                <BaseCard
                    variant="list"
                    scene="consumer"
                    class="filter-summary__panel"
                    padding="8rpx 10rpx"
                >
                    <view class="filter-summary__content">
                        <view
                            v-for="chip in summaryChips"
                            :key="chip.key"
                            class="filter-summary__item"
                            :class="{ 'filter-summary__item--active': chip.selected }"
                            @click="redirectToScheduleQuery"
                        >
                            <BaseIcon
                                class="filter-summary__item-icon"
                                :name="chip.icon"
                                size="20"
                                :color="chip.selected ? '#D9BE82' : '#9A6B35'"
                            />
                            <text class="filter-summary__item-text">{{ chip.label }}</text>
                        </view>
                        <view
                            class="filter-summary__edit"
                            @click="redirectToScheduleQuery"
                        >
                            <BaseIcon name="sort" size="20" color="#D9BE82" />
                            <text class="filter-summary__edit-text">重筛</text>
                        </view>
                    </view>
                </BaseCard>
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
                <view v-if="staffViewMode === 'poster'" class="poster-list">
                    <BaseCard
                        v-for="item in staffList"
                        :key="item.id"
                        class="poster-card"
                        variant="media"
                        scene="consumer"
                        interactive
                        @click="goToDetail(item.id)"
                    >
                        <view class="poster-card__media">
                            <image
                                class="poster-card__image"
                                :src="getStaffAvatar(item)"
                                mode="aspectFill"
                                lazy-load
                            />
                            <view class="poster-card__shade" />
                            <StatusBadge
                                v-if="item.is_recommend"
                                class="poster-card__badge"
                                tone="primary"
                                size="xs"
                                dot
                            >
                                {{ getRecommendBadgeText(item) }}
                            </StatusBadge>
                            <view
                                class="poster-card__favorite"
                                @click.stop="handleToggleFavorite(item)"
                            >
                                <BaseIconButton
                                    :icon="item.is_favorite ? 'like-fill' : 'like'"
                                    :variant="item.is_favorite ? 'dark' : 'light'"
                                    size="sm"
                                    width="62rpx"
                                    height="62rpx"
                                    icon-size="30"
                                />
                            </view>
                        </view>

                        <view class="poster-card__content">
                            <view class="poster-card__head">
                                <text class="poster-card__name">{{
                                    item.name || '未命名人员'
                                }}</text>
                                <view
                                    class="poster-card__price"
                                    :class="{
                                        'poster-card__price--negotiable': !hasStaffPrice(item)
                                    }"
                                >
                                    <text class="poster-card__price-value">{{
                                        getStaffPriceValue(item)
                                    }}</text>
                                    <text
                                        v-if="getStaffPriceSuffix(item)"
                                        class="poster-card__price-unit"
                                    >
                                        {{ getStaffPriceSuffix(item) }}
                                    </text>
                                </view>
                            </view>
                            <text class="poster-card__role">{{ formatRoleLine(item) }}</text>

                            <view v-if="getDisplayTags(item).length" class="poster-card__tags">
                                <StatusBadge
                                    v-for="tag in getDisplayTags(item)"
                                    :key="`${item.id}-${tag}`"
                                    tone="warning"
                                    size="xs"
                                >
                                    {{ tag }}
                                </StatusBadge>
                            </view>
                            <text v-else-if="buildStaffDescription(item)" class="poster-card__desc">
                                {{ buildStaffDescription(item) }}
                            </text>

                            <view class="poster-card__footer">
                                <view class="poster-card__score">
                                    <BaseIcon name="star-fill" size="20" color="#C8A45D" />
                                    <text class="poster-card__score-text">{{
                                        formatRatingText(item)
                                    }}</text>
                                </view>
                                <text class="poster-card__orders"
                                    >已服务{{ item.order_count || 0 }}单</text
                                >
                            </view>
                        </view>
                    </BaseCard>
                </view>

                <view v-else class="line-list">
                    <BaseCard
                        v-for="item in staffList"
                        :key="item.id"
                        class="line-card"
                        :variant="getStaffCardTone(item)"
                        scene="consumer"
                        padding="14rpx 16rpx"
                        interactive
                        @click="goToDetail(item.id)"
                    >
                        <image
                            class="line-card__image"
                            :src="getStaffAvatar(item)"
                            mode="aspectFill"
                            lazy-load
                        />

                        <view class="line-card__content">
                            <view class="line-card__head">
                                <view class="line-card__name-group">
                                    <text class="line-card__name">{{
                                        item.name || '未命名人员'
                                    }}</text>
                                    <StatusBadge
                                        v-if="item.is_recommend"
                                        tone="primary"
                                        size="xs"
                                    >
                                        {{ getRecommendBadgeText(item) }}
                                    </StatusBadge>
                                </view>
                                <view
                                    class="line-card__favorite"
                                    @click.stop="handleToggleFavorite(item)"
                                >
                                    <BaseIconButton
                                        :icon="item.is_favorite ? 'like-fill' : 'like'"
                                        variant="ghost"
                                        size="sm"
                                        width="52rpx"
                                        height="52rpx"
                                        icon-size="26"
                                    />
                                </view>
                            </view>

                            <text class="line-card__role">{{ formatRoleLine(item) }}</text>

                            <view v-if="getDisplayTags(item, 3).length" class="line-card__tags">
                                <StatusBadge
                                    v-for="tag in getDisplayTags(item, 3)"
                                    :key="`${item.id}-line-${tag}`"
                                    tone="warning"
                                    size="xs"
                                >
                                    {{ tag }}
                                </StatusBadge>
                            </view>
                            <text v-else-if="buildStaffDescription(item)" class="line-card__desc">
                                {{ buildStaffDescription(item) }}
                            </text>

                            <view class="line-card__footer">
                                <view class="line-card__metrics">
                                    <view class="line-card__score">
                                        <BaseIcon name="star-fill" size="20" color="#C8A45D" />
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
                    </BaseCard>
                </view>
            </z-paging>

            <view
                class="view-switch-btn"
                @click.stop="handleToggleViewMode"
            >
                <BaseIconButton
                    :icon="staffViewMode === 'poster' ? 'menu-list' : 'grid'"
                    variant="light"
                    size="sm"
                    width="88rpx"
                    height="88rpx"
                    icon-size="34"
                />
            </view>

            <tabbar :badge-refresh-key="tabbarRefreshKey" />
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { onLoad, onReady, onShow } from '@dcloudio/uni-app'
import { getStaffList, toggleStaffFavorite } from '@/api/staff'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseIconButton from '@/components/base/BaseIconButton.vue'
import BaseSkeleton from '@/components/base/BaseSkeleton.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
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

const pagingRefresherEnabled = computed(() => import.meta.env.UNI_PLATFORM !== 'h5')
const hasValidQuery = computed(() =>
    Boolean(
        selectedDate.value && hasServiceRegion(selectedRegion.value) && currentCategoryId.value > 0
    )
)
const selectedRegionText = computed(() => {
    const cityName = selectedRegion.value.city_name || selectedRegion.value.province_name
    const districtName = selectedRegion.value.district_name
    if (cityName && districtName) {
        return `${cityName} · ${districtName}`
    }
    return formatServiceRegionText(selectedRegion.value, ' / ') || '未选择'
})

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
const currentSortName = computed(
    () => sortOptions.find((item) => item.value === currentSort.value)?.label || '综合排序'
)
const summaryChips = computed(() => [
    { key: 'region', label: selectedRegionText.value, icon: 'location', selected: true },
    { key: 'date', label: selectedDateText.value, icon: 'calendar', selected: false },
    { key: 'sort', label: currentSortName.value, icon: 'sort', selected: false }
])

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

const getStaffAvatar = (item: any) => item?.avatar || DEFAULT_AVATAR

const getRecommendBadgeText = (item: any) => String(item?.recommend_text || '推荐').trim() || '推荐'

const getStaffCardTone = (item: any): 'dark' | 'list' => (item?.is_recommend ? 'dark' : 'list')

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
}

.filter-summary {
    margin: 16rpx 24rpx 18rpx;
}

.filter-summary__panel {
    --wm-radius-card: 30rpx;
    --wm-radius-list-panel: 30rpx;
}

.filter-summary__content {
    display: flex;
    align-items: center;
    gap: 6rpx;
    min-height: 64rpx;
}

.filter-summary__item {
    flex: 1;
    min-width: 0;
    height: 56rpx;
    padding: 0 8rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4rpx;
    background: rgba(255, 253, 248, 0.72);
    border: 1rpx solid rgba(217, 190, 130, 0.22);
}

.filter-summary__item--active {
    background: var(--wm-color-primary, #191713);
    border-color: var(--wm-color-primary, #191713);
}

.filter-summary__item-icon {
    flex-shrink: 0;
}

.filter-summary__item-text {
    min-width: 0;
    font-size: 21rpx;
    font-weight: 800;
    line-height: 1.2;
    color: var(--wm-color-clay, #9A6B35);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.filter-summary__item--active .filter-summary__item-text {
    color: var(--wm-text-inverse, #FFFDF8);
}

.filter-summary__edit {
    width: 98rpx;
    flex-shrink: 0;
    min-width: 98rpx;
    height: 56rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4rpx;
    background: var(--wm-color-primary, #191713);
    box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
}

.filter-summary__edit-text {
    font-size: 21rpx;
    font-weight: 900;
    line-height: 1;
    color: var(--wm-text-inverse, #FFFDF8);
}

.paging-state {
    padding: 12rpx var(--wm-space-page-x, 32rpx) 220rpx;
}

.poster-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
    padding: 0 24rpx calc(176rpx + env(safe-area-inset-bottom));
}

.poster-list :deep(.poster-card.base-card) {
    width: auto;
    min-width: 0;
    --wm-radius-card: 28rpx;
}

.poster-card__media {
    position: relative;
    height: 224rpx;
    background: linear-gradient(135deg, #f7f0df 0%, #d8c28a 100%);
}

.poster-card__image {
    width: 100%;
    height: 100%;
    display: block;
}

.poster-card__shade {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(25, 23, 19, 0.04) 0%, rgba(25, 23, 19, 0.38) 100%);
    pointer-events: none;
}

.poster-card__badge {
    position: absolute;
    top: 12rpx;
    left: 12rpx;
    z-index: 2;
}

.poster-card__favorite {
    position: absolute;
    top: 10rpx;
    right: 10rpx;
    z-index: 2;
}

.poster-card__content {
    padding: 14rpx 14rpx 16rpx;
}

.poster-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8rpx;
}

.poster-card__name {
    flex: 1;
    min-width: 0;
    display: block;
    font-size: 26rpx;
    font-weight: 900;
    line-height: 1.35;
    color: var(--wm-text-primary, #191713);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.poster-card__price {
    flex-shrink: 0;
    display: inline-flex;
    align-items: baseline;
    gap: 4rpx;
    padding-top: 2rpx;
}

.poster-card__price--negotiable .poster-card__price-value,
.poster-card__price--negotiable .poster-card__price-unit {
    color: var(--wm-text-tertiary, #8A806F);
}

.poster-card__price-value {
    min-width: 0;
    font-size: 25rpx;
    font-weight: 900;
    line-height: 1.2;
    color: var(--wm-color-primary, #191713);
}

.poster-card__price-unit {
    font-size: 18rpx;
    font-weight: 900;
    line-height: 1.2;
    color: var(--wm-color-gold, #B8954A);
}

.poster-card__role {
    display: block;
    margin-top: 6rpx;
    font-size: 20rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #665E52);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.poster-card__tags {
    display: none;
}

.poster-card__desc {
    display: none;
}

.poster-card__footer {
    margin-top: 10rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8rpx;
}

.poster-card__score {
    padding: 6rpx 10rpx;
    border-radius: 999rpx;
    background: var(--wm-color-gold-soft, #F1E5C8);
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
}

.poster-card__score-text {
    font-size: 19rpx;
    font-weight: 900;
    line-height: 1.2;
    color: var(--wm-color-clay, #9A6B35);
}

.poster-card__orders {
    font-size: 18rpx;
    line-height: 1.2;
    color: var(--wm-text-secondary, #665E52);
    white-space: nowrap;
}

.line-list {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 0 24rpx calc(176rpx + env(safe-area-inset-bottom));
}

.line-card {
    display: flex;
    align-items: center;
    gap: 14rpx;
    min-height: 128rpx;
    padding: 14rpx 16rpx;
}

.line-card__image {
    width: 96rpx;
    height: 96rpx;
    flex-shrink: 0;
    border-radius: 26rpx;
    border: 1rpx solid var(--wm-color-champagne, #D9BE82);
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
    align-items: center;
    justify-content: space-between;
    gap: 12rpx;
}

.line-card__name-group {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 8rpx;
    flex: 1;
}

.line-card__name {
    min-width: 0;
    font-size: 28rpx;
    font-weight: 900;
    line-height: 1.35;
    color: var(--wm-text-primary, #191713);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.line-card__favorite {
    flex-shrink: 0;
}

.line-card__role {
    display: block;
    margin-top: 4rpx;
    font-size: 21rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #665E52);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.line-card__tags {
    display: none;
}

.line-card__desc {
    display: none;
}

.line-card__footer {
    margin-top: auto;
    padding-top: 8rpx;
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
    font-size: 20rpx;
    font-weight: 900;
    line-height: 1.2;
    color: var(--wm-color-gold, #B8954A);
}

.line-card__orders {
    font-size: 19rpx;
    line-height: 1.2;
    color: var(--wm-text-secondary, #665E52);
    white-space: nowrap;
}

.line-card__price {
    flex-shrink: 0;
    font-size: 24rpx;
    font-weight: 900;
    line-height: 1.2;
    color: var(--wm-color-primary, #191713);
}

.view-switch-btn {
    position: fixed;
    right: 28rpx;
    bottom: calc(176rpx + env(safe-area-inset-bottom));
    z-index: 30;
}

.line-card.base-card--dark .line-card__name,
.line-card.base-card--dark .line-card__price,
.line-card.base-card--dark .line-card__role,
.line-card.base-card--dark .line-card__orders,
.line-card.base-card--dark .line-card__desc {
    color: var(--wm-text-inverse, #FFFDF8);
}

.line-card.base-card--dark .line-card__role,
.line-card.base-card--dark .line-card__orders,
.line-card.base-card--dark .line-card__desc {
    opacity: 0.72;
}
</style>
