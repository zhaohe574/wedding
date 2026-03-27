<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="人员详情" />

        <view class="staff-detail" v-if="staffInfo">
            <view class="staff-detail__content">
                <view class="hero-card">
                    <staff-banner
                        class="hero-card__banner"
                        :banner-list="bannerList"
                        :config="bannerConfig"
                        :default-image="staffInfo.avatar || '/static/images/user/default_avatar.png'"
                    />
                </view>

                <!-- 人员信息卡片 -->
                <view class="info-card">
                    <view class="info-card__header">
                        <view class="info-card__identity">
                            <text class="info-card__name">{{ staffInfo.name }}</text>
                            <text class="info-card__summary">{{ staffSummaryText }}</text>
                        </view>
                        <view class="info-card__favorite" @click="handleToggleFavorite">
                            <tn-icon
                                :name="staffInfo.is_favorite ? 'star-fill' : 'star'"
                                size="34"
                                :color="staffInfo.is_favorite ? '#E85A4F' : '#9E9A97'"
                            />
                            <text
                                class="info-card__favorite-text"
                                :class="{
                                    'info-card__favorite-text--active': staffInfo.is_favorite
                                }"
                            >
                                {{ staffInfo.is_favorite ? '已收藏' : '收藏' }}
                            </text>
                        </view>
                    </view>

                    <view v-if="statusBadgeList.length" class="info-card__badge-list">
                        <view
                            v-for="badge in statusBadgeList"
                            :key="badge"
                            class="info-card__badge"
                        >
                            <text class="info-card__badge-text">{{ badge }}</text>
                        </view>
                    </view>

                    <view class="info-card__metric-row">
                        <view class="info-card__metric">
                            <text class="info-card__metric-value">{{ staffInfo.rating ?? '0.0' }}</text>
                            <text class="info-card__metric-label">综合评分</text>
                        </view>
                        <view class="info-card__metric">
                            <text class="info-card__metric-value">{{ staffInfo.order_count || 0 }}</text>
                            <text class="info-card__metric-label">服务场次</text>
                        </view>
                        <view class="info-card__metric">
                            <text class="info-card__metric-value">{{ staffInfo.view_count || 0 }}</text>
                            <text class="info-card__metric-label">浏览次数</text>
                        </view>
                    </view>

                    <view class="info-card__price-row">
                        <text class="info-card__price-label">服务价格</text>
                        <view class="info-card__price-group">
                            <template v-if="staffPrice.hasPrice">
                                <text class="info-card__price-symbol">¥</text>
                                <text class="info-card__price-value">{{ staffPrice.value }}</text>
                                <text class="info-card__price-unit">/次起</text>
                            </template>
                            <text v-else class="info-card__price-negotiable">面议</text>
                        </view>
                    </view>
                </view>

                <view class="booking-brief-card">
                    <view class="booking-brief-card__header">
                        <view>
                            <text class="booking-brief-card__title">预约信息</text>
                            <text class="booking-brief-card__desc">先在详情页确认地区和日期，再进入设计稿的多步骤预约页。</text>
                        </view>
                    </view>
                    <view class="booking-brief-card__grid">
                        <view class="booking-brief-card__item" @click="handleInlineRegionEdit">
                            <text class="booking-brief-card__label">服务地区</text>
                            <text class="booking-brief-card__value">
                                {{ hasSelectedRegion ? selectedRegionText : '请选择服务区县' }}
                            </text>
                        </view>
                        <view class="booking-brief-card__item" @click="handleInlineDateEdit">
                            <text class="booking-brief-card__label">预约日期</text>
                            <text class="booking-brief-card__value">
                                {{ presetDate || '请选择预约日期' }}
                            </text>
                        </view>
                    </view>
                </view>

                <!-- 标签页切换 -->
                <view class="tabs-section">
                    <view class="tabs-wrapper">
                        <view
                            v-for="tab in tabs"
                            :key="tab.key"
                            class="tab-item"
                            :class="{ 'tab-item--active': currentTab === tab.key }"
                            @click="currentTab = tab.key"
                        >
                            <text
                                class="tab-text"
                                :class="{ 'tab-text--active': currentTab === tab.key }"
                            >
                                {{ tab.label }}
                            </text>
                        </view>
                    </view>
                </view>

                <!-- 标签页内容 -->
                <view class="tab-content">
                    <view v-if="currentTab === 'intro'" class="content-section content-section--stack">
                        <view class="soft-card">
                            <text class="soft-card__title">人员简介</text>
                            <text class="soft-card__content">{{ staffProfileText }}</text>
                        </view>

                        <view v-if="displayTagList.length" class="soft-card">
                            <text class="soft-card__title">擅长风格</text>
                            <view class="soft-tags">
                                <view v-for="tag in displayTagList" :key="tag" class="soft-tag">
                                    <text class="soft-tag__text">{{ tag }}</text>
                                </view>
                            </view>
                        </view>

                        <view v-if="staffServiceText" class="soft-card">
                            <text class="soft-card__title">服务说明</text>
                            <text class="soft-card__content">{{ staffServiceText }}</text>
                        </view>
                    </view>

                    <view v-else-if="currentTab === 'works'" class="content-section">
                        <view v-if="worksLoading" class="loading-state">
                            <tn-loading mode="circle" />
                        </view>

                        <view v-else-if="worksList.length" class="works-grid">
                            <view
                                v-for="work in worksList"
                                :key="work.id"
                                class="work-item"
                                @click="goWorkDetail(work)"
                            >
                                <image
                                    :src="work.cover || work.images?.[0]"
                                    mode="aspectFill"
                                    class="work-image"
                                    lazy-load
                                />
                                <view class="work-overlay">
                                    <text class="work-title">{{ work.title || '婚礼作品' }}</text>
                                </view>
                            </view>
                        </view>

                        <view v-else class="empty-card">
                            <text class="empty-card__text">暂无作品</text>
                        </view>
                    </view>

                    <view v-else class="content-section content-section--stack">
                        <view
                            v-if="staffInfo.certificates && staffInfo.certificates.length"
                            class="soft-card"
                        >
                            <text class="soft-card__title">资质证书</text>
                            <scroll-view scroll-x class="certs-scroll">
                                <view class="certs-wrapper">
                                    <view
                                        v-for="cert in staffInfo.certificates"
                                        :key="cert.id || cert.image"
                                        class="cert-item"
                                        @click="previewCert(cert.image)"
                                    >
                                        <image
                                            :src="cert.image"
                                            mode="aspectFill"
                                            class="cert-image"
                                        />
                                        <text class="cert-name">{{ cert.name }}</text>
                                    </view>
                                </view>
                            </scroll-view>
                        </view>

                        <view class="review-summary">
                            <view class="review-summary-card">
                                <text class="review-summary-value">
                                    {{ reviewStats.avg_score || '5.0' }}
                                </text>
                                <text class="review-summary-label">综合评分</text>
                            </view>
                            <view class="review-summary-card">
                                <text class="review-summary-value">
                                    {{ reviewStats.total_count || 0 }}
                                </text>
                                <text class="review-summary-label">全部评价</text>
                            </view>
                            <view class="review-summary-card">
                                <text class="review-summary-value">
                                    {{ reviewStats.good_rate || 0 }}%
                                </text>
                                <text class="review-summary-label">好评率</text>
                            </view>
                        </view>

                        <view class="review-filter-row">
                            <view class="review-filter-item">
                                好评 {{ reviewStats.good_count || 0 }}
                            </view>
                            <view class="review-filter-item">
                                中评 {{ reviewStats.medium_count || 0 }}
                            </view>
                            <view class="review-filter-item">
                                差评 {{ reviewStats.bad_count || 0 }}
                            </view>
                            <view class="review-filter-item">
                                有图 {{ reviewStats.image_count || 0 }}
                            </view>
                        </view>

                        <view v-if="reviewsLoading && !reviewsList.length" class="loading-state">
                            <tn-loading mode="circle" />
                        </view>

                        <view v-else-if="reviewsList.length" class="reviews-list">
                            <view
                                v-for="review in reviewsList"
                                :key="review.id"
                                class="review-card"
                                @click="goReviewDetail(review)"
                            >
                                <view class="review-card-header">
                                    <view class="review-user">
                                        <image
                                            class="review-user-avatar"
                                            :src="
                                                review.user?.avatar ||
                                                '/static/images/user/default_avatar.png'
                                            "
                                            mode="aspectFill"
                                        />
                                        <view class="review-user-info">
                                            <text class="review-user-name">
                                                {{ review.user?.nickname || '匿名用户' }}
                                            </text>
                                            <text class="review-time">
                                                {{
                                                    review.create_time_text ||
                                                    formatReviewTime(review.create_time)
                                                }}
                                            </text>
                                        </view>
                                    </view>
                                    <view class="review-score">
                                        <tn-icon
                                            v-for="star in 5"
                                            :key="`${review.id}-${star}`"
                                            :name="
                                                star <= Number(review.score || 0)
                                                    ? 'star-fill'
                                                    : 'star'
                                            "
                                            size="20"
                                            :color="
                                                star <= Number(review.score || 0)
                                                    ? '#F3A64A'
                                                    : '#D8D5D2'
                                            "
                                        />
                                    </view>
                                </view>

                                <text v-if="review.content" class="review-content">
                                    {{ review.content }}
                                </text>

                                <view v-if="review.tags?.length" class="review-tag-list">
                                    <view
                                        v-for="tag in review.tags"
                                        :key="tag.id || tag.name"
                                        class="review-tag"
                                    >
                                        {{ tag.name }}
                                    </view>
                                </view>

                                <view v-if="review.images?.length" class="review-image-list">
                                    <image
                                        v-for="(image, index) in review.images"
                                        :key="`${review.id}-${index}`"
                                        class="review-image"
                                        :src="image"
                                        mode="aspectFill"
                                        @click.stop="previewReviewImages(review.images, index)"
                                    />
                                </view>

                                <view v-if="review.replies?.length" class="review-reply-list">
                                    <view
                                        v-for="reply in review.replies"
                                        :key="reply.id"
                                        class="review-reply-item"
                                    >
                                        <text class="review-reply-type">
                                            {{
                                                Number(reply.reply_type) === 1
                                                    ? '用户追评'
                                                    : '商家回复'
                                            }}
                                        </text>
                                        <text class="review-reply-content">
                                            {{ reply.content }}
                                        </text>
                                    </view>
                                </view>
                            </view>

                            <view v-if="reviewsHasMore" class="review-load-more">
                                <text v-if="reviewsLoading" class="review-load-more-text">
                                    加载中...
                                </text>
                                <text
                                    v-else
                                    class="review-load-more-text review-load-more-text--action"
                                    @click="loadMoreReviews"
                                >
                                    加载更多评价
                                </text>
                            </view>
                            <view v-else class="review-load-more">
                                <text class="review-load-more-text">没有更多评价了</text>
                            </view>
                        </view>

                        <view v-else class="empty-card">
                            <text class="empty-card__text">暂无评价</text>
                        </view>
                    </view>
                </view>

                <!-- 底部操作栏 -->
                <ActionArea sticky safeBottom>
                    <view class="staff-detail__action-bar">
                        <view class="action-button share-action-item" @click="handleShareFallback">
                            <text class="action-button__text">分享</text>
                            <!-- #ifdef MP-WEIXIN -->
                            <button
                                class="share-action-trigger"
                                open-type="share"
                                hover-class="none"
                            ></button>
                            <!-- #endif -->
                        </view>
                        <view class="action-button" @click="handleContact">
                            <text class="action-button__text">咨询</text>
                        </view>
                        <view class="action-button action-button--primary" @click="handleBook">
                            <text class="action-button__text action-button__text--primary">
                                立即预约
                            </text>
                        </view>
                    </view>
                </ActionArea>
            </view>

        <u-popup
            v-model="showRegionPopup"
            mode="bottom"
            :mask="true"
            :mask-close-able="true"
            :safe-area-inset-bottom="true"
            :border-radius="24"
        >
            <view class="picker-container region-picker-container">
                <view class="picker-header">
                    <text class="picker-action" @click="closeRegionPicker">取消</text>
                    <text class="picker-title">选择服务地区</text>
                    <text class="picker-action picker-action-primary" @click="confirmRegionPicker">
                        确定
                    </text>
                </view>
                <view class="region-picker-content">
                    <view class="region-picker-col">
                        <view class="region-picker-col__title">省份</view>
                        <scroll-view scroll-y class="region-picker-scroll">
                            <view
                                v-for="province in regionProvinces"
                                :key="province.province_code"
                                class="region-picker-item"
                                :class="{ active: tempRegion.province_code === province.province_code }"
                                @click="handleProvinceSelect(province)"
                            >
                                {{ province.province_name }}
                            </view>
                        </scroll-view>
                    </view>
                    <view class="region-picker-col">
                        <view class="region-picker-col__title">城市</view>
                        <scroll-view scroll-y class="region-picker-scroll">
                            <view
                                v-for="city in regionCities"
                                :key="city.city_code"
                                class="region-picker-item"
                                :class="{ active: tempRegion.city_code === city.city_code }"
                                @click="handleCitySelect(city)"
                            >
                                {{ city.city_name }}
                            </view>
                        </scroll-view>
                    </view>
                    <view class="region-picker-col">
                        <view class="region-picker-col__title">区县</view>
                        <scroll-view scroll-y class="region-picker-scroll">
                            <view
                                v-for="district in regionDistricts"
                                :key="district.district_code"
                                class="region-picker-item"
                                :class="{ active: tempRegion.district_code === district.district_code }"
                                @click="handleDistrictSelect(district)"
                            >
                                {{ district.district_name }}
                            </view>
                        </scroll-view>
                    </view>
                </view>
                <view class="picker-footer">
                    <view class="picker-btn" @click="resetRegionSelection">清空</view>
                    <view
                        class="picker-btn picker-btn-primary"
                        :style="{ background: $theme.primaryColor }"
                        @click="confirmRegionPicker"
                    >
                        确定
                    </view>
                </view>
            </view>
        </u-popup>

        <u-popup
            v-model="showDatePopup"
            mode="bottom"
            :mask="true"
            :mask-close-able="true"
            :safe-area-inset-bottom="true"
            :border-radius="24"
        >
            <view class="picker-container">
                <view class="picker-header">
                    <text class="picker-action" @click="closeDatePicker">取消</text>
                    <text class="picker-title">选择预约日期</text>
                    <text class="picker-action picker-action-primary" @click="confirmDatePicker">
                        确定
                    </text>
                </view>
                <view class="date-picker-content">
                    <picker-view
                        class="date-picker-view"
                        :value="datePickerValue"
                        @change="handleDatePickerChange"
                    >
                        <picker-view-column>
                            <view
                                v-for="year in datePickerYears"
                                :key="`year-${year}`"
                                class="picker-item"
                            >
                                {{ year }}年
                            </view>
                        </picker-view-column>
                        <picker-view-column>
                            <view
                                v-for="month in datePickerMonths"
                                :key="`month-${month}`"
                                class="picker-item"
                            >
                                {{ month }}月
                            </view>
                        </picker-view-column>
                        <picker-view-column>
                            <view
                                v-for="day in datePickerDays"
                                :key="`day-${day}`"
                                class="picker-item"
                            >
                                {{ day }}日
                            </view>
                        </picker-view-column>
                    </picker-view>
                </view>
            </view>
        </u-popup>
        </view>

        <!-- 加载状态 -->
        <view v-else class="loading-container">
            <tn-loading mode="circle" />
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { onLoad, onShow, onShareAppMessage, onShareTimeline } from '@dcloudio/uni-app'
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import ActionArea from '@/components/base/ActionArea.vue'
import { getStaffDetail, toggleStaffFavorite, getStaffWorks } from '@/api/staff'
import { getStaffReviews, getStaffReviewStats } from '@/api/review'
import { getServiceRegionTree } from '@/api/service'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import StaffBanner from '@/packages/components/staff-banner/staff-banner.vue'
import {
    buildServiceRegionQuery,
    formatServiceRegionText,
    hasServiceRegion,
    loadServiceRegionSelection,
    normalizeServiceRegion,
    saveServiceRegionSelection,
    toServiceRegionParams
} from '@/utils/service-region'
import { getStaffBookingPageUrl } from '@/utils/staff-booking'

const staffId = ref<number>(0)
const staffInfo = ref<any>(null)
const currentTab = ref('intro')
const presetDate = ref('') // 预设日期
const showDatePopup = ref(false)
const showRegionPopup = ref(false)
const datePickerValue = ref([0, 0, 0])
const openDatePickerRequested = ref(false)
const openBookingPopupRequested = ref(false)
const pendingDatePickerAfterRegion = ref(false)
const selectedPackageId = ref<number>(0)
const regionTree = ref<any[]>([])
const selectedRegion = ref(normalizeServiceRegion(loadServiceRegionSelection()))
const tempRegion = ref(normalizeServiceRegion(selectedRegion.value))
const $theme = useThemeStore()
const userStore = useUserStore()

// 轮播图数据
const bannerList = ref<any[]>([])
const bannerConfig = ref({
    banner_mode: 1,
    banner_small_height: 400,
    banner_large_height: 600,
    banner_indicator_style: 1,
    banner_autoplay: 1,
    banner_interval: 3000
})

// 作品列表
const worksList = ref<any[]>([])
const worksLoading = ref(false)

// 评价列表
const reviewsList = ref<any[]>([])
const reviewsLoading = ref(false)
const reviewsPage = ref(1)
const reviewsHasMore = ref(true)
const reviewsInitialized = ref(false)
const reviewStatsLoaded = ref(false)
const reviewStats = ref({
    total_count: 0,
    good_count: 0,
    medium_count: 0,
    bad_count: 0,
    image_count: 0,
    video_count: 0,
    avg_score: '5.0',
    good_rate: 100
})

const getTomorrowDate = () => {
    const tomorrow = new Date()
    tomorrow.setHours(0, 0, 0, 0)
    tomorrow.setDate(tomorrow.getDate() + 1)
    return tomorrow
}

const getMaxDateForPicker = () => {
    const maxDate = getTomorrowDate()
    maxDate.setFullYear(maxDate.getFullYear() + 5)
    return maxDate
}

const formatDateText = (date: Date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

const parseDateText = (value = '') => {
    const [year, month, day] = value.split('-').map((item) => Number(item))
    if (!year || !month || !day) {
        return null
    }
    const date = new Date(year, month - 1, day)
    date.setHours(0, 0, 0, 0)
    if (Number.isNaN(date.getTime())) {
        return null
    }
    return date
}

const isSelectableDate = (value = '') => {
    const parsedDate = parseDateText(value)
    if (!parsedDate) {
        return false
    }
    const minDate = getTomorrowDate()
    const maxDate = getMaxDateForPicker()
    return parsedDate >= minDate && parsedDate <= maxDate
}

const normalizeSelectedDateText = (value = '') => {
    if (!isSelectableDate(value)) {
        return ''
    }
    return formatDateText(parseDateText(value) as Date)
}

const getEffectiveSelectableDate = (value = '') => {
    const parsedDate = parseDateText(value)
    const minDate = getTomorrowDate()
    const maxDate = getMaxDateForPicker()
    if (!parsedDate || parsedDate < minDate) {
        return minDate
    }
    if (parsedDate > maxDate) {
        return maxDate
    }
    return parsedDate
}

const datePickerYears = computed(() => {
    const minDate = getTomorrowDate()
    const maxDate = getMaxDateForPicker()
    const totalYears = maxDate.getFullYear() - minDate.getFullYear() + 1
    return Array.from({ length: totalYears }, (_, index) => minDate.getFullYear() + index)
})

const getDatePickerMonthsByYear = (year: number) => {
    const minDate = getTomorrowDate()
    const maxDate = getMaxDateForPicker()
    const startMonth = year === minDate.getFullYear() ? minDate.getMonth() + 1 : 1
    const endMonth = year === maxDate.getFullYear() ? maxDate.getMonth() + 1 : 12
    return Array.from({ length: endMonth - startMonth + 1 }, (_, index) => startMonth + index)
}

const getDatePickerDaysByYearMonth = (year: number, month: number) => {
    const minDate = getTomorrowDate()
    const maxDate = getMaxDateForPicker()
    const isMinMonth = year === minDate.getFullYear() && month === minDate.getMonth() + 1
    const isMaxMonth = year === maxDate.getFullYear() && month === maxDate.getMonth() + 1
    const startDay = isMinMonth ? minDate.getDate() : 1
    const endDay = isMaxMonth ? maxDate.getDate() : new Date(year, month, 0).getDate()
    return Array.from({ length: endDay - startDay + 1 }, (_, index) => startDay + index)
}

const normalizeDatePickerValue = (value: number[]) => {
    const yearIndex = Math.min(Math.max(value[0] ?? 0, 0), datePickerYears.value.length - 1)
    const year = datePickerYears.value[yearIndex]
    const months = getDatePickerMonthsByYear(year)
    const monthIndex = Math.min(Math.max(value[1] ?? 0, 0), months.length - 1)
    const month = months[monthIndex]
    const days = getDatePickerDaysByYearMonth(year, month)
    const dayIndex = Math.min(Math.max(value[2] ?? 0, 0), days.length - 1)
    return [yearIndex, monthIndex, dayIndex]
}

const datePickerMonths = computed(() => {
    const yearIndex = Math.min(
        Math.max(datePickerValue.value[0] ?? 0, 0),
        datePickerYears.value.length - 1
    )
    const year = datePickerYears.value[yearIndex]
    return getDatePickerMonthsByYear(year)
})

const datePickerDays = computed(() => {
    const yearIndex = Math.min(
        Math.max(datePickerValue.value[0] ?? 0, 0),
        datePickerYears.value.length - 1
    )
    const year = datePickerYears.value[yearIndex]
    const monthIndex = Math.min(
        Math.max(datePickerValue.value[1] ?? 0, 0),
        Math.max(datePickerMonths.value.length - 1, 0)
    )
    const month = datePickerMonths.value[monthIndex]
    return getDatePickerDaysByYearMonth(year, month)
})

const hasSelectedRegion = computed(() => hasServiceRegion(selectedRegion.value))
const selectedRegionText = computed(() => {
    if (!hasSelectedRegion.value) {
        return '请选择服务区县'
    }
    return formatServiceRegionText(selectedRegion.value)
})
const regionProvinces = computed(() => regionTree.value || [])
const regionCities = computed(() => {
    return (
        regionTree.value.find((item: any) => item.province_code === tempRegion.value.province_code)?.cities || []
    )
})
const regionDistricts = computed(() => {
    return (
        regionCities.value.find((item: any) => item.city_code === tempRegion.value.city_code)?.districts || []
    )
})
const displayTagList = computed(() => {
    const tags = Array.isArray(staffInfo.value?.tags) ? staffInfo.value.tags : []
    return tags
        .map((item: any) => String(item || '').trim())
        .filter((item: string) => item)
})
const statusBadgeList = computed(() => {
    const badges: string[] = []
    if (staffInfo.value?.is_verified) {
        badges.push('已认证')
    }
    if (staffInfo.value?.is_vip) {
        badges.push('VIP')
    }
    if (staffInfo.value?.is_recommend) {
        badges.push('推荐')
    }
    return badges
})
const staffSummaryText = computed(() => {
    const categoryName = String(
        staffInfo.value?.category?.name || staffInfo.value?.category_name || ''
    ).trim()
    const parts: string[] = []
    if (categoryName) {
        parts.push(categoryName)
    }
    const summaryTags = displayTagList.value.slice(0, 2)
    if (summaryTags.length) {
        parts.push(summaryTags.join('｜'))
    }
    const orderCount = Number(staffInfo.value?.order_count || 0)
    if (orderCount > 0) {
        parts.push(`服务 ${orderCount} 场`)
    }
    return parts.join('｜') || '资料正在完善中'
})
const staffProfileText = computed(() => {
    const profile = String(staffInfo.value?.profile || '').trim()
    const serviceDesc = String(staffInfo.value?.service_desc || '').trim()
    return profile || serviceDesc || '暂无简介'
})
const staffServiceText = computed(() => {
    const profile = String(staffInfo.value?.profile || '').trim()
    const serviceDesc = String(staffInfo.value?.service_desc || '').trim()
    if (!serviceDesc || serviceDesc === profile) {
        return ''
    }
    return serviceDesc
})
const staffPrice = computed(() => {
    const hasPrice =
        staffInfo.value?.has_price !== false &&
        staffInfo.value?.price !== null &&
        staffInfo.value?.price !== undefined &&
        staffInfo.value?.price !== ''
    return {
        hasPrice,
        value: String(staffInfo.value?.price_text || staffInfo.value?.price || '')
    }
})

const getPackageId = (pkg: any) => Number(pkg?.package_id || pkg?.id || 0)
const isRecommendedPackage = (pkg: any) =>
    Number(pkg?.is_recommend ?? pkg?.package?.is_recommend ?? 0) === 1

const syncSelectedPackage = () => {
    const packages = Array.isArray(staffInfo.value?.packages) ? staffInfo.value.packages : []
    if (!packages.length) {
        selectedPackageId.value = 0
        return
    }

    const hasSelected = packages.some((pkg: any) => getPackageId(pkg) === selectedPackageId.value)
    if (hasSelected) {
        return
    }

    const recommendedPackage = packages.find((pkg: any) => isRecommendedPackage(pkg))
    selectedPackageId.value = getPackageId(recommendedPackage || packages[0])
}

const handleInlineRegionEdit = () => {
    pendingDatePickerAfterRegion.value = false
    openRegionPicker()
}

const handleInlineDateEdit = () => {
    if (!hasSelectedRegion.value) {
        uni.showToast({ title: '请先选择服务地区', icon: 'none' })
        pendingDatePickerAfterRegion.value = true
        openRegionPicker()
        return
    }
    pendingDatePickerAfterRegion.value = false
    openDatePicker()
}

// 标签页配置
const tabs = [
    { key: 'intro', label: '人员简介' },
    { key: 'works', label: '人员作品' },
    { key: 'reviews', label: '人员评价' }
]

// 监听标签页切换
watch(currentTab, (newTab) => {
    if (newTab === 'works' && worksList.value.length === 0) {
        loadWorks()
    } else if (newTab === 'reviews') {
        if (!reviewStatsLoaded.value) {
            loadReviewStats()
        }
        if (!reviewsInitialized.value) {
            loadReviews(true)
        }
    }
})

// 获取详情
const getDetail = async () => {
    try {
        const params: Record<string, any> & { id: number } = { id: staffId.value }
        if (presetDate.value) {
            params.date = presetDate.value
        }
        Object.assign(params, toServiceRegionParams(selectedRegion.value))

        const data = await getStaffDetail(params)
        staffInfo.value = data
        syncSelectedPackage()

        // 加载轮播图配置
        if (data.banner_mode !== undefined) {
            bannerConfig.value = {
                banner_mode: data.banner_mode || 1,
                banner_small_height: data.banner_small_height || 400,
                banner_large_height: data.banner_large_height || 600,
                banner_indicator_style:
                    data.banner_indicator_style !== undefined ? data.banner_indicator_style : 1,
                banner_autoplay: data.banner_autoplay !== undefined ? data.banner_autoplay : 1,
                banner_interval: data.banner_interval || 3000
            }
        }

        // 加载轮播图列表
        if (data.banners && Array.isArray(data.banners)) {
            bannerList.value = data.banners
        }

        if (currentTab.value === 'reviews') {
            if (!reviewStatsLoaded.value) {
                loadReviewStats()
            }
            if (!reviewsInitialized.value) {
                loadReviews(true)
            }
        }
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '获取详情失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

const getRegionTree = async () => {
    try {
        const data = await getServiceRegionTree()
        regionTree.value = Array.isArray(data) ? data : []
        syncTempRegion(selectedRegion.value)
    } catch (error) {
        console.error(error)
    }
}

// 加载作品列表
const loadWorks = async () => {
    if (worksLoading.value) return

    worksLoading.value = true
    try {
        const data = await getStaffWorks({ staff_id: staffId.value })
        worksList.value = data || []
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载作品失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        worksLoading.value = false
    }
}

const loadReviewStats = async () => {
    if (!staffId.value || reviewStatsLoaded.value) return

    try {
        const data = await getStaffReviewStats({ staff_id: staffId.value })
        reviewStats.value = {
            total_count: Number(data?.total_count || 0),
            good_count: Number(data?.good_count || 0),
            medium_count: Number(data?.medium_count || 0),
            bad_count: Number(data?.bad_count || 0),
            image_count: Number(data?.image_count || 0),
            video_count: Number(data?.video_count || 0),
            avg_score: Number(data?.avg_score || 5).toFixed(1),
            good_rate: Number(data?.good_rate || 0)
        }
        reviewStatsLoaded.value = true
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载评价统计失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

const loadReviews = async (refresh = false) => {
    if (reviewsLoading.value || (!refresh && !reviewsHasMore.value)) return

    if (refresh) {
        reviewsPage.value = 1
        reviewsHasMore.value = true
    }

    reviewsLoading.value = true
    try {
        const data = await getStaffReviews({
            staff_id: staffId.value,
            page: reviewsPage.value,
            limit: 10
        })
        const list = data?.lists || []

        reviewsList.value = refresh ? list : [...reviewsList.value, ...list]
        reviewsHasMore.value = Boolean(data?.has_more)
        reviewsInitialized.value = true
        reviewsPage.value += 1
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载评价失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        reviewsLoading.value = false
    }
}

// 收藏/取消收藏
const handleToggleFavorite = async () => {
    // 检查登录状态
    if (!userStore.isLogin) {
        uni.showToast({ title: '请先登录', icon: 'none' })
        setTimeout(() => {
            uni.navigateTo({ url: '/pages/login/login' })
        }, 1500)
        return
    }

    try {
        await toggleStaffFavorite({ id: staffId.value })
        staffInfo.value.is_favorite = !staffInfo.value.is_favorite
        uni.showToast({
            title: staffInfo.value.is_favorite ? '收藏成功' : '已取消收藏',
            icon: 'success'
        })
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '操作失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

// 联系咨询
const handleContact = () => {
    uni.navigateTo({
        url: `/packages/pages/customer_service/customer_service?scene=staff_detail&staff_id=${staffId.value}`
    })
}

const handleShareFallback = () => {
    const payload = buildSharePayload()
    let shareContent = `${payload.title} ${payload.path}`
    let toastTitle = '已复制分享信息'

    // #ifdef H5
    if (typeof window !== 'undefined' && window.location?.href) {
        shareContent = window.location.href
        toastTitle = '已复制分享链接'
    }
    // #endif

    uni.setClipboardData({
        data: shareContent,
        success: () => {
            uni.showToast({ title: toastTitle, icon: 'none' })
        }
    })
}

const syncTempRegion = (value?: Record<string, any>) => {
    const region = normalizeServiceRegion(value || selectedRegion.value)
    tempRegion.value = region
    if (!tempRegion.value.province_code && regionTree.value.length) {
        handleProvinceSelect(regionTree.value[0])
        return
    }

    if (!tempRegion.value.city_code && regionCities.value.length) {
        handleCitySelect(regionCities.value[0])
    }
}

const openRegionPicker = () => {
    if (showRegionPopup.value) {
        return
    }
    syncTempRegion()
    showRegionPopup.value = true
}

const hideRegionPicker = () => {
    showRegionPopup.value = false
}

const closeRegionPicker = () => {
    hideRegionPicker()
    pendingDatePickerAfterRegion.value = false
}

const handleProvinceSelect = (province: any) => {
    tempRegion.value = normalizeServiceRegion({
        province_code: province?.province_code || '',
        province_name: province?.province_name || '',
        city_code: '',
        city_name: '',
        district_code: '',
        district_name: ''
    })

    const firstCity = (province?.cities || [])[0]
    if (firstCity) {
        handleCitySelect(firstCity)
    }
}

const handleCitySelect = (city: any) => {
    tempRegion.value = normalizeServiceRegion({
        province_code: city?.province_code || tempRegion.value.province_code,
        province_name: city?.province_name || tempRegion.value.province_name,
        city_code: city?.city_code || '',
        city_name: city?.city_name || '',
        district_code: '',
        district_name: ''
    })
}

const handleDistrictSelect = (district: any) => {
    tempRegion.value = normalizeServiceRegion({
        ...tempRegion.value,
        province_code: district?.province_code || tempRegion.value.province_code,
        province_name: district?.province_name || tempRegion.value.province_name,
        city_code: district?.city_code || tempRegion.value.city_code,
        city_name: district?.city_name || tempRegion.value.city_name,
        district_code: district?.district_code || '',
        district_name: district?.district_name || ''
    })
}

const resetRegionSelection = () => {
    tempRegion.value = normalizeServiceRegion({})
}

const confirmRegionPicker = async () => {
    if (!hasServiceRegion(tempRegion.value)) {
        uni.showToast({ title: '请选择到区县', icon: 'none' })
        return
    }

    selectedRegion.value = normalizeServiceRegion(tempRegion.value)
    saveServiceRegionSelection(selectedRegion.value)
    hideRegionPicker()

    await getDetail()

    if (pendingDatePickerAfterRegion.value) {
        pendingDatePickerAfterRegion.value = false
        setTimeout(() => openDatePicker(), 0)
        return
    }
}

const syncDatePickerValue = (value = '') => {
    const targetDate = getEffectiveSelectableDate(value)
    const yearIndex = datePickerYears.value.indexOf(targetDate.getFullYear())
    const safeYearIndex = yearIndex >= 0 ? yearIndex : 0
    const months = getDatePickerMonthsByYear(datePickerYears.value[safeYearIndex])
    const monthIndex = Math.max(months.indexOf(targetDate.getMonth() + 1), 0)
    const days = getDatePickerDaysByYearMonth(
        datePickerYears.value[safeYearIndex],
        months[monthIndex]
    )
    const dayIndex = Math.max(days.indexOf(targetDate.getDate()), 0)
    datePickerValue.value = [safeYearIndex, monthIndex, dayIndex]
}

const openDatePicker = () => {
    if (!hasSelectedRegion.value) {
        openRegionPicker()
        return
    }
    syncDatePickerValue(presetDate.value)
    showDatePopup.value = true
}

const hideDatePicker = () => {
    showDatePopup.value = false
}

const closeDatePicker = () => {
    hideDatePicker()
    pendingDatePickerAfterRegion.value = false
}

const handleDatePickerChange = (event: any) => {
    datePickerValue.value = normalizeDatePickerValue(event.detail.value || [])
}

const confirmDatePicker = async () => {
    const year = datePickerYears.value[datePickerValue.value[0]]
    const month = String(datePickerMonths.value[datePickerValue.value[1]]).padStart(2, '0')
    const day = String(datePickerDays.value[datePickerValue.value[2]]).padStart(2, '0')
    const nextDate = `${year}-${month}-${day}`

    presetDate.value = nextDate
    hideDatePicker()
    await getDetail()
    pendingDatePickerAfterRegion.value = false
}

const buildStaffDetailQuery = (extra: Record<string, any> = {}) => {
    const params = [`id=${staffId.value}`]
    const regionQuery = buildServiceRegionQuery(selectedRegion.value)
    if (regionQuery) {
        params.push(regionQuery)
    }
    if (presetDate.value) {
        params.push(`date=${encodeURIComponent(presetDate.value)}`)
    }
    if (selectedPackageId.value) {
        params.push(`package_id=${selectedPackageId.value}`)
    }

    Object.entries(extra).forEach(([key, value]) => {
        if (value === '' || value === undefined || value === null) {
            return
        }
        params.push(`${key}=${encodeURIComponent(String(value))}`)
    })

    return params.join('&')
}

// 立即预约
const handleBook = () => {
    if (!staffId.value || staffId.value === 0) {
        uni.showToast({ title: '服务人员信息错误', icon: 'none' })
        return
    }

    if (!hasSelectedRegion.value || !presetDate.value) {
        uni.showToast({ title: '请先选择服务地区与预约日期', icon: 'none' })
        if (!hasSelectedRegion.value) {
            handleInlineRegionEdit()
            return
        }
        handleInlineDateEdit()
        return
    }

    uni.navigateTo({
        url: getStaffBookingPageUrl({
            staff_id: staffId.value,
            package_id: selectedPackageId.value,
            date: presetDate.value,
            ...selectedRegion.value
        })
    })
}

// 预览作品图片
const goWorkDetail = (work: any) => {
    if (!work?.id) {
        uni.showToast({ title: '作品信息错误', icon: 'none' })
        return
    }
    uni.navigateTo({
        url: `/packages/pages/staff_work_detail/staff_work_detail?id=${work.id}`
    })
}

// 预览证书
const previewCert = (url: string) => {
    uni.previewImage({
        urls: [url]
    })
}

const previewReviewImages = (
    images: Array<string | number>,
    index: number | string = 0
) => {
    const urls = (images || []).map((item) => String(item)).filter(Boolean)
    if (!urls.length) return
    const currentIndex = Number(index || 0)
    uni.previewImage({
        urls,
        current: urls[currentIndex] || urls[0]
    })
}

const loadMoreReviews = () => {
    loadReviews()
}

const goReviewDetail = (review: any) => {
    if (!review?.id) {
        return
    }
    uni.navigateTo({
        url: `/packages/pages/review/detail?id=${review.id}`
    })
}

const formatReviewTime = (timestamp: number) => {
    if (!timestamp) {
        return '-'
    }
    return new Date(timestamp * 1000).toLocaleDateString()
}

const getShareTitle = () => {
    const staffName = String(staffInfo.value?.name || '').trim()
    const categoryName = String(staffInfo.value?.category?.name || '').trim()

    if (staffName && categoryName) {
        return `${staffName}｜${categoryName}`
    }

    if (staffName) {
        return `${staffName}｜服务人员详情`
    }

    return '服务人员详情'
}

const buildSharePayload = () => {
    const payload: {
        title: string
        path: string
        imageUrl?: string
    } = {
        title: getShareTitle(),
        path: `/packages/pages/staff_detail/staff_detail?${buildStaffDetailQuery()}`
    }

    const avatar = String(staffInfo.value?.avatar || '').trim()
    if (avatar) {
        payload.imageUrl = avatar
    }

    return payload
}

onLoad((options) => {
    $theme.setScene('consumer')
    if (options?.id) {
        staffId.value = Number(options.id)
    }
    selectedRegion.value = normalizeServiceRegion({
        ...loadServiceRegionSelection(),
        ...options
    })
    tempRegion.value = normalizeServiceRegion(selectedRegion.value)
    if (hasServiceRegion(selectedRegion.value)) {
        saveServiceRegionSelection(selectedRegion.value)
    }
    if (options?.date) {
        presetDate.value = normalizeSelectedDateText(options.date)
    }
    if (options?.package_id) {
        selectedPackageId.value = Number(options.package_id)
    }
    if (options?.open_date_picker === '1') {
        openDatePickerRequested.value = true
    }
    if (options?.open_booking_popup === '1') {
        openBookingPopupRequested.value = true
    }
    if (options?.tab && ['intro', 'works', 'reviews'].includes(options.tab)) {
        currentTab.value = options.tab
    }
})

onShow(async () => {
    $theme.setScene('consumer')
    // 微信分享直达时隐藏原生“返回首页”按钮
    // #ifdef MP-WEIXIN
    try {
        uni.hideHomeButton()
    } catch (error) {
        console.warn('隐藏首页按钮失败：', error)
    }
    // #endif

        await getRegionTree()
    if (staffId.value) {
        await getDetail()
        if (openBookingPopupRequested.value || openDatePickerRequested.value) {
            const shouldOpenDateEditor = openDatePickerRequested.value
            openBookingPopupRequested.value = false
            openDatePickerRequested.value = false
            if (shouldOpenDateEditor) {
                setTimeout(() => handleInlineDateEdit(), 0)
            } else {
                setTimeout(() => handleBook(), 0)
            }
        }
    }
})

onShareAppMessage(() => {
    return buildSharePayload()
})

// #ifdef MP-WEIXIN
onShareTimeline(() => {
    const sharePayload = buildSharePayload()
    const timelinePayload: {
        title: string
        query: string
        imageUrl?: string
    } = {
        title: sharePayload.title,
        query: buildStaffDetailQuery()
    }

    if (sharePayload.imageUrl) {
        timelinePayload.imageUrl = sharePayload.imageUrl
    }

    return timelinePayload
})
// #endif
</script>

<style lang="scss" scoped>
/* 加载状态 */
.loading-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
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

    .picker-action {
        min-width: 96rpx;
        font-size: 28rpx;
        color: #667085;
        text-align: center;

        &:active {
            opacity: 0.72;
        }
    }

    .picker-action-primary {
        color: var(--color-primary);
        font-weight: 600;
    }
}

.date-picker-content {
    padding: 12rpx 16rpx 24rpx;
}

.date-picker-view {
    height: 420rpx;
}

.picker-item {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    font-size: 30rpx;
    color: #1E2432;
}

.picker-footer {
    display: flex;
    gap: 16rpx;
    padding: 16rpx 24rpx 24rpx;
    border-top: 1rpx solid #eef1f5;
}

.picker-btn {
    flex: 1;
    height: 82rpx;
    border-radius: 16rpx;
    background: #f3f4f6;
    color: #475467;
    font-size: 28rpx;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;

    &:active {
        opacity: 0.85;
        transform: scale(0.98);
    }
}

.picker-btn-primary {
    color: #ffffff;
    font-weight: 600;
}

.region-picker-content {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
    padding: 18rpx 18rpx 8rpx;
}

.region-picker-col {
    min-width: 0;
    border: 1rpx solid #eef1f5;
    border-radius: 18rpx;
    overflow: hidden;
    background: #f9fafc;
}

.region-picker-col__title {
    padding: 18rpx 20rpx 14rpx;
    font-size: 24rpx;
    font-weight: 600;
    color: #374151;
    border-bottom: 1rpx solid #eef1f5;
    background: #ffffff;
}

.region-picker-scroll {
    height: 480rpx;
}

.region-picker-item {
    padding: 20rpx;
    font-size: 24rpx;
    color: #4b5563;
    border-bottom: 1rpx solid rgba(229, 231, 235, 0.72);
    transition: all 0.2s ease;

    &:last-child {
        border-bottom: none;
    }

    &:active {
        opacity: 0.82;
    }

    &.active {
        font-weight: 600;
        color: var(--color-primary);
        background: var(--color-primary-light-9);
    }
}


</style>

<style lang="scss" scoped>
.staff-detail {
    min-height: 100vh;
    background: linear-gradient(180deg, #fcfbf9 0%, #f8f3ef 34%, #f5f1ee 100%);
}

.staff-detail__content {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    padding: 12rpx 20rpx 180rpx;
}

.hero-card {
    position: relative;
    overflow: hidden;
    border-radius: 28rpx;
    background: linear-gradient(135deg, #efcbc0 0%, #c99688 100%);
    box-shadow: 0 18rpx 40rpx rgba(180, 138, 123, 0.18);
}

.hero-card__banner {
    display: block;
}

.hero-card__banner :deep(.banner-container),
.hero-card__banner :deep(.banner-swiper),
.hero-card__banner :deep(.media-container),
.hero-card__banner :deep(.banner-media),
.hero-card__banner :deep(.banner-video) {
    border-radius: 28rpx;
}

.info-card {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    padding: 24rpx 22rpx;
    border-radius: 26rpx;
    background: rgba(255, 255, 255, 0.84);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    backdrop-filter: blur(20rpx);
    -webkit-backdrop-filter: blur(20rpx);
    box-shadow: 0 16rpx 34rpx rgba(155, 132, 121, 0.1);
}

.info-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.info-card__identity {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.info-card__name {
    font-size: 40rpx;
    line-height: 1.1;
    font-weight: 700;
    color: #1e2432;
}

.info-card__summary {
    font-size: 24rpx;
    line-height: 1.6;
    color: #7f7b78;
}

.info-card__favorite {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    min-height: 68rpx;
    padding: 0 18rpx;
    border-radius: 999rpx;
    background: #fff6f1;
    border: 1rpx solid rgba(232, 90, 79, 0.12);
}

.info-card__favorite-text {
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 600;
    color: #9e9a97;
}

.info-card__favorite-text--active {
    color: #e85a4f;
}

.info-card__badge-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
}

.info-card__badge {
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    background: #fff4ef;
    border: 1rpx solid rgba(232, 90, 79, 0.12);
}

.info-card__badge-text {
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 600;
    color: #c66d5d;
}

.info-card__metric-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
}

.info-card__metric {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    padding: 18rpx 14rpx;
    border-radius: 22rpx;
    background: linear-gradient(180deg, #fff8f4 0%, #fff2eb 100%);
}

.info-card__metric-value {
    font-size: 32rpx;
    line-height: 1.1;
    font-weight: 700;
    color: #1e2432;
}

.info-card__metric-label {
    font-size: 22rpx;
    line-height: 1.3;
    color: #938d89;
}

.info-card__price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    padding-top: 4rpx;
}

.info-card__price-label {
    font-size: 24rpx;
    line-height: 1.3;
    color: #7f7b78;
}

.info-card__price-group {
    display: flex;
    align-items: baseline;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 4rpx;
    min-width: 0;
}

.info-card__price-symbol,
.info-card__price-value {
    color: #e85a4f;
    font-weight: 700;
}

.info-card__price-symbol {
    font-size: 24rpx;
}

.info-card__price-value {
    font-size: 42rpx;
    line-height: 1;
}

.info-card__price-unit,
.info-card__price-negotiable {
    font-size: 22rpx;
    line-height: 1.3;
    color: #7f7b78;
}

.info-card__price-negotiable {
    font-size: 28rpx;
    font-weight: 700;
    color: #1e2432;
}

.booking-brief-card {
    margin-top: 22rpx;
    padding: 26rpx;
    border-radius: 28rpx;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(255, 247, 244, 0.96));
    border: 1rpx solid #efe6e1;
    box-shadow: 0 18rpx 34rpx rgba(214, 185, 167, 0.12);
}

.booking-brief-card__title {
    display: block;
    font-size: 30rpx;
    font-weight: 700;
    color: #1e2432;
}

.booking-brief-card__desc {
    display: block;
    margin-top: 10rpx;
    font-size: 23rpx;
    line-height: 1.6;
    color: #7f7b78;
}

.booking-brief-card__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
    margin-top: 20rpx;
}

.booking-brief-card__item {
    padding: 20rpx;
    border-radius: 22rpx;
    background: #ffffff;
    border: 1rpx solid #efe6e1;
}

.booking-brief-card__label {
    display: block;
    font-size: 22rpx;
    color: #8e8985;
}

.booking-brief-card__value {
    display: block;
    margin-top: 10rpx;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 1.5;
    color: #1e2432;
}

.tabs-section {
    padding: 4rpx;
    border-radius: 22rpx;
    background: rgba(255, 255, 255, 0.84);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    backdrop-filter: blur(18rpx);
    -webkit-backdrop-filter: blur(18rpx);
}

.tabs-wrapper {
    display: flex;
    align-items: center;
    gap: 6rpx;
}

.tab-item {
    flex: 1;
    min-width: 0;
    height: 72rpx;
    padding: 0 12rpx;
    border-radius: 18rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tab-item--active {
    background: #e85a4f;
    box-shadow: 0 10rpx 22rpx rgba(232, 90, 79, 0.18);
}

.tab-text {
    font-size: 24rpx;
    line-height: 1.2;
    font-weight: 600;
    color: #8a8581;
}

.tab-text--active {
    color: #ffffff;
}

.tab-content {
    margin: 0;
}

.content-section {
    padding: 0;
    background: transparent;
    border-radius: 0;
}

.content-section--stack {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.soft-card {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    padding: 24rpx 22rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.84);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    backdrop-filter: blur(18rpx);
    -webkit-backdrop-filter: blur(18rpx);
}

.soft-card__title {
    font-size: 28rpx;
    line-height: 1.2;
    font-weight: 700;
    color: #1e2432;
}

.soft-card__content {
    font-size: 26rpx;
    line-height: 1.8;
    color: #4a4541;
}

.soft-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.soft-tag {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    background: #fff4ef;
    border: 1rpx solid rgba(232, 90, 79, 0.14);
}

.soft-tag__text {
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 600;
    color: #c66d5d;
}

.works-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
}

.work-item {
    position: relative;
    overflow: hidden;
    border-radius: 22rpx;
    background: linear-gradient(135deg, #f8e3db 0%, #ead7cf 100%);
    box-shadow: 0 14rpx 30rpx rgba(185, 158, 147, 0.12);
}

.work-image {
    width: 100%;
    height: 224rpx;
}

.work-overlay {
    position: absolute;
    inset: auto 0 0 0;
    padding: 18rpx 16rpx;
    background: linear-gradient(
        180deg,
        rgba(30, 36, 50, 0) 0%,
        rgba(30, 36, 50, 0.6) 100%
    );
}

.work-title {
    display: block;
    font-size: 24rpx;
    line-height: 1.35;
    font-weight: 600;
    color: #ffffff;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.review-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
}

.review-summary-card {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    padding: 20rpx 16rpx;
    border-radius: 22rpx;
    background: rgba(255, 255, 255, 0.84);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
}

.review-summary-value {
    font-size: 32rpx;
    line-height: 1.1;
    font-weight: 700;
    color: #1e2432;
}

.review-summary-label {
    font-size: 22rpx;
    line-height: 1.3;
    color: #938d89;
}

.review-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
}

.review-filter-item {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    background: #fff4ef;
    border: 1rpx solid rgba(232, 90, 79, 0.12);
    font-size: 22rpx;
    line-height: 1.2;
    color: #b46657;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.review-card {
    padding: 24rpx 22rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.84);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
}

.review-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.review-user {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 14rpx;
}

.review-user-avatar {
    width: 72rpx;
    height: 72rpx;
    border-radius: 50%;
    background: #f4efec;
    flex-shrink: 0;
}

.review-user-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.review-user-name {
    font-size: 26rpx;
    line-height: 1.2;
    font-weight: 600;
    color: #1e2432;
}

.review-time {
    font-size: 22rpx;
    line-height: 1.2;
    color: #938d89;
}

.review-score {
    display: inline-flex;
    align-items: center;
    gap: 4rpx;
}

.review-content {
    display: block;
    margin-top: 18rpx;
    font-size: 25rpx;
    line-height: 1.75;
    color: #4a4541;
}

.review-tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
    margin-top: 16rpx;
}

.review-tag {
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    background: #fff4ef;
    border: 1rpx solid rgba(232, 90, 79, 0.12);
    font-size: 22rpx;
    line-height: 1.2;
    color: #b46657;
}

.review-image-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 18rpx;
}

.review-image {
    width: calc((100% - 24rpx) / 3);
    height: 184rpx;
    border-radius: 18rpx;
    background: #f4efec;
}

.review-reply-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-top: 18rpx;
}

.review-reply-item {
    padding: 18rpx 16rpx;
    border-radius: 18rpx;
    background: #fff7f3;
    border: 1rpx solid rgba(232, 90, 79, 0.08);
}

.review-reply-type {
    display: block;
    margin-bottom: 8rpx;
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 600;
    color: #c66d5d;
}

.review-reply-content {
    display: block;
    font-size: 24rpx;
    line-height: 1.7;
    color: #5a5451;
}

.review-load-more {
    padding-top: 4rpx;
    text-align: center;
}

.review-load-more-text {
    font-size: 24rpx;
    line-height: 1.3;
    color: #938d89;
}

.review-load-more-text--action {
    color: #e85a4f;
    font-weight: 600;
}

.certs-scroll {
    white-space: nowrap;
}

.certs-wrapper {
    display: inline-flex;
    gap: 14rpx;
}

.cert-item {
    display: inline-flex;
    flex-direction: column;
    gap: 10rpx;
    width: 216rpx;
}

.cert-image {
    width: 216rpx;
    height: 144rpx;
    border-radius: 18rpx;
    background: #f4efec;
}

.cert-name {
    font-size: 22rpx;
    line-height: 1.4;
    color: #5a5451;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.empty-card {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 220rpx;
    padding: 24rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.84);
    border: 1rpx dashed rgba(201, 179, 168, 0.8);
}

.empty-card__text {
    font-size: 26rpx;
    line-height: 1.4;
    color: #938d89;
}

.loading-container,
.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-state {
    min-height: 220rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.72);
}

.staff-detail__action-bar {
    display: flex;
    gap: 12rpx;
    width: 100%;
}

.action-button {
    position: relative;
    flex: 1;
    height: 96rpx;
    border-radius: 20rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.9);
    border: 1rpx solid rgba(239, 230, 225, 0.96);
    box-shadow: 0 10rpx 24rpx rgba(155, 132, 121, 0.08);
    overflow: hidden;
}

.action-button--primary {
    background: #e85a4f;
    border-color: #e85a4f;
    box-shadow: 0 14rpx 28rpx rgba(232, 90, 79, 0.22);
}

.action-button__text {
    font-size: 26rpx;
    line-height: 1.2;
    font-weight: 700;
    color: #1e2432;
}

.action-button__text--primary {
    color: #ffffff;
}

.share-action-item {
    position: relative;
}

.share-action-trigger {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    padding: 0;
    margin: 0;
    background: transparent;
    border: none;
    box-shadow: none;
    opacity: 0;
    appearance: none;
    -webkit-appearance: none;
    -webkit-tap-highlight-color: transparent;
}

.share-action-trigger::after {
    display: none;
}

@media (max-width: 360px) {
    .tab-text {
        font-size: 22rpx;
    }

    .action-button__text {
        font-size: 24rpx;
    }
}
</style>
