<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="consumer" tone="editorial" hasSafeBottom>
        <BaseNavbar
            title="人员详情"
            :back="!isShareEntry"
            variant="solid"
            bg-color="#181614"
            text-color="#FFFDF8"
        />

        <view class="staff-detail" v-if="staffInfo">
            <view class="staff-detail__content">
                <!-- 顶部横幅全景大卡 (无外边距，纯直角全宽大片，无圆角阴影) -->
                <view class="hero-card">
                    <staff-banner
                        class="hero-card__banner"
                        :banner-list="bannerList"
                        :config="bannerConfig"
                        :default-image="
                            staffInfo.avatar || '/static/images/user/default_avatar.png'
                        "
                    />
                </view>

                <!-- 页面主体内容流 (统一下沉边距，杜绝多层嵌套挤压) -->
                <view class="staff-detail__main">
                    <!-- 主创大师名片 (Master Artisan Card 原生卡片容器，杜绝自定义组件外边距折叠与样式隔离) -->
                    <view class="info-card">
                    <view class="info-card__inner">
                        <!-- 主创身份栏 -->
                        <view class="info-card__header">
                            <view class="info-card__avatar-box">
                                <image
                                    class="info-card__avatar"
                                    :src="staffInfo.avatar || '/static/images/user/default_avatar.png'"
                                    mode="aspectFill"
                                />
                                <view class="info-card__verify-badge">
                                    <BaseIcon name="trusty" size="18" color="#FFFDF8" />
                                </view>
                            </view>

                            <view class="info-card__identity">
                                <view class="info-card__name-row">
                                    <text class="info-card__name">{{ staffInfo.name }}</text>
                                    <StatusBadge tone="warning" size="xs">
                                        {{ staffInfo.category_name || '主创团队' }}
                                    </StatusBadge>
                                </view>
                                <text class="info-card__summary">{{ primaryMetaText }}</text>
                            </view>

                            <view class="info-card__favorite" @click.stop="handleToggleFavorite">
                                <BaseIconButton
                                    :icon="staffInfo.is_favorite ? 'like-fill' : 'like'"
                                    :variant="staffInfo.is_favorite ? 'dark' : 'light'"
                                    size="sm"
                                    width="76rpx"
                                    height="76rpx"
                                    icon-size="30"
                                />
                            </view>
                        </view>

                        <!-- 标签徽章组 -->
                        <view v-if="statusBadgeList.length" class="info-card__badge-list">
                            <StatusBadge
                                v-for="badge in statusBadgeList"
                                :key="badge"
                                tone="warning"
                                size="xs"
                            >
                                {{ badge }}
                            </StatusBadge>
                        </view>

                        <!-- 口碑指标网格 (4列) -->
                        <view class="info-card__metric-row">
                            <view
                                v-for="metric in compactMetricList"
                                :key="metric.label"
                                class="info-card__metric"
                            >
                                <text class="info-card__metric-value">{{ metric.value }}</text>
                                <text class="info-card__metric-label">{{ metric.label }}</text>
                            </view>
                        </view>

                        <!-- 服务起价栏 -->
                        <view class="info-card__price-row">
                            <text class="info-card__price-label">服务起价</text>
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
                </view>

                <!-- 服务档期与地区筛选栏 (原生视图结构，规避小程序组件样式隔离穿透失效) -->
                <view class="booking-brief-card">
                    <view class="booking-brief-header">
                        <view class="booking-brief-heading">
                            <view class="booking-brief-icon-box">
                                <BaseIcon name="calendar" size="22" color="#7A5B20" />
                            </view>
                            <text class="booking-brief-title">服务档期与地区</text>
                        </view>
                        <view class="booking-brief-status-tag">
                            <view class="booking-brief-status-dot" />
                            <text class="booking-brief-status-text">实时可约</text>
                        </view>
                    </view>

                    <view class="booking-brief-grid">
                        <!-- 服务地区 (截取核心区县显示) -->
                        <view class="booking-picker-item" @click="handleInlineRegionEdit">
                            <view class="booking-picker-item__icon-wrap">
                                <BaseIcon name="location" size="26" color="#B8954A" />
                            </view>
                            <view class="booking-picker-item__content">
                                <text class="booking-picker-item__label">服务地区</text>
                                <text
                                    class="booking-picker-item__value"
                                    :class="{ 'booking-picker-item__value--placeholder': !hasSelectedRegion }"
                                >
                                    {{ selectedDistrictText || '选择地区' }}
                                </text>
                            </view>
                            <BaseIcon name="down" size="22" color="#C6A15B" />
                        </view>

                        <!-- 预约日期 (截取M月D日显示) -->
                        <view class="booking-picker-item" @click="handleInlineDateEdit">
                            <view class="booking-picker-item__icon-wrap">
                                <BaseIcon name="calendar" size="26" color="#B8954A" />
                            </view>
                            <view class="booking-picker-item__content">
                                <text class="booking-picker-item__label">预约日期</text>
                                <text
                                    class="booking-picker-item__value"
                                    :class="{ 'booking-picker-item__value--placeholder': !presetDate }"
                                >
                                    {{ selectedDateDisplay || '选择日期' }}
                                </text>
                            </view>
                            <BaseIcon name="down" size="22" color="#C6A15B" />
                        </view>
                    </view>
                </view>

                <!-- 内容 Tabs 切换 -->
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
                            <view v-if="currentTab === tab.key" class="tab-indicator" />
                        </view>
                    </view>
                </view>

                <!-- Tab 内容容器 -->
                <view class="tab-content">
                    <!-- Tab 1: 主创介绍与资质 -->
                    <view
                        v-if="currentTab === 'intro'"
                        class="content-section content-section--stack"
                    >
                        <view v-if="hasLongDetail" class="detail-stream-shell">
                            <staff-long-detail-renderer :content="staffInfo.long_detail" />
                        </view>

                        <view v-if="displayTagList.length" class="soft-card wm-soft-card">
                            <view class="soft-card__header">
                                <view class="soft-card__dot" />
                                <text class="soft-card__title">擅长风格</text>
                            </view>

                            <view class="soft-tags">
                                <view v-for="tag in displayTagList" :key="tag" class="soft-tag">
                                    <text class="soft-tag__text">{{ tag }}</text>
                                </view>
                            </view>
                        </view>

                        <view v-if="displayCertificates.length" class="soft-card wm-soft-card">
                            <view class="soft-card__header">
                                <view class="soft-card__dot" />
                                <text class="soft-card__title">官方资质认证</text>
                                <text class="soft-card__meta">已通过专业资质实名核验</text>
                            </view>

                            <scroll-view scroll-x class="certs-scroll" :show-scrollbar="false">
                                <view class="certs-wrapper">
                                    <view
                                        v-for="cert in displayCertificates"
                                        :key="cert.id || cert.image"
                                        class="cert-item"
                                        @click="openCertificatePopup(cert)"
                                    >
                                        <view class="cert-image-wrap">
                                            <image
                                                :src="
                                                    resolveDetailImageSrc(
                                                        'certificate',
                                                        cert.image,
                                                        cert.id || cert.image
                                                    )
                                                "
                                                mode="aspectFill"
                                                class="cert-image"
                                                @error="
                                                    handleDetailImageError(
                                                        'certificate',
                                                        cert.image,
                                                        cert.id || cert.image,
                                                        $event
                                                    )
                                                "
                                            />
                                            <view class="cert-view-badge">
                                                <text>查看证书</text>
                                            </view>
                                        </view>
                                        <text class="cert-name">{{ cert.name }}</text>
                                    </view>
                                </view>
                            </scroll-view>
                        </view>

                        <!-- 服务保障承诺卡 -->
                        <view class="soft-card wm-soft-card guarantee-card">
                            <view class="soft-card__header">
                                <view class="soft-card__dot" />
                                <text class="soft-card__title">品质服务承诺</text>
                            </view>
                            <view class="guarantee-grid">
                                <view class="guarantee-item">
                                    <BaseIcon name="trusty" size="32" color="#C6A15B" />
                                    <text class="guarantee-title">官方严选认证</text>
                                    <text class="guarantee-desc">实名认证与作品核验</text>
                                </view>
                                <view class="guarantee-item">
                                    <BaseIcon name="calendar" size="32" color="#C6A15B" />
                                    <text class="guarantee-title">档期准时履约</text>
                                    <text class="guarantee-desc">专人专档无惧冲突</text>
                                </view>
                                <view class="guarantee-item">
                                    <BaseIcon name="funds" size="32" color="#C6A15B" />
                                    <text class="guarantee-title">全程价格透明</text>
                                    <text class="guarantee-desc">无任何隐形消费</text>
                                </view>
                                <view class="guarantee-item">
                                    <BaseIcon name="like-fill" size="32" color="#C6A15B" />
                                    <text class="guarantee-title">专属管家跟进</text>
                                    <text class="guarantee-desc">全程售后安心护航</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <!-- Tab 2: 精选代表作 -->
                    <StaffWorksTab
                        v-else-if="currentTab === 'works'"
                        :works-list="worksList"
                        :loading="worksLoading"
                        @select-work="goWorkDetail"
                    />

                    <!-- Tab 3: 新人口碑评价 -->
                    <StaffReviewsTab
                        v-else
                        :reviews-list="reviewsList"
                        :review-stats="reviewStats"
                        :loading="reviewsLoading"
                        :has-more="reviewsHasMore"
                        @load-more="loadMoreReviews"
                        @select-review="goReviewDetail"
                    />
                </view>

                </view>

                <!-- 吸底常驻操作栏 (全宽独立吸底，杜绝错位变形) -->
                <StaffActionBar
                    @contact="handleContact"
                    @book="handleBook"
                    @share-click="handleShareFallback"
                />
            </view>

            <!-- 服务地区选择器 -->
            <BaseServiceRegionPicker
                v-model="selectedRegion"
                v-model:open="showRegionPopup"
                :data="regionTree"
                @confirm="handleServiceRegionConfirm"
                @cancel="closeRegionPicker"
            />

            <!-- 日期选择器 -->
            <BaseDateTimePicker
                v-model="datePickerModel"
                v-model:open="showDatePopup"
                mode="date"
                format="YYYY-MM-DD"
                :min-time="datePickerMinText"
                :max-time="datePickerMaxText"
                @confirm="handleDatePickerConfirm"
                @cancel="closeDatePicker"
                @close="closeDatePicker"
            />

            <!-- 替代人员推荐弹窗 -->
            <StaffAlternativePopup
                v-model="showAlternativeStaffPopup"
                :loading="alternativeStaffLoading"
                :querying="alternativeStaffQuerying"
                :reason="alternativeStaffReason"
                :list="alternativeStaffList"
                :theme-color="$theme.primaryColor"
                @select="handleAlternativeStaffSelect"
                @pick-date="handleAlternativePickDate"
                @join-waitlist="handleAlternativeJoinWaitlist"
            />

            <!-- 资质证书弹窗 -->
            <StaffCertificatePopup
                v-model="showCertificatePopup"
                :certificate="activeCertificate"
                :theme-color="$theme.primaryColor"
            />
        </view>

        <view v-else-if="detailLoading" class="loading-container">
            <LoadingState text="人员详情加载中..." />
        </view>

        <view v-else class="detail-state-shell wm-page-content">
            <EmptyState
                :title="detailError?.title || '人员信息暂不可用'"
                :description="detailError?.message || '该人员可能已下架，或当前网络不可用。'"
                :action-text="detailError?.actionText || '重新加载'"
                @action="handleDetailRecoveryAction"
            />

            <view class="detail-state-shell__actions">
                <view class="detail-state-shell__link" @click="goHome">
                    <text>返回首页</text>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { remindBeforeOaAction } from '@/utils/oa-reminder'
import { computed, nextTick, ref, watch } from 'vue'

import {
    onLoad,
    onPageScroll,
    onShow,
    onShareAppMessage,
    onShareTimeline
} from '@dcloudio/uni-app'

import PageShell from '@/components/base/PageShell.vue'

import BaseNavbar from '@/components/base/BaseNavbar.vue'

import BaseIcon from '@/components/base/BaseIcon.vue'

import StaffAlternativePopup from './components/StaffAlternativePopup.vue'
import StaffCertificatePopup from './components/StaffCertificatePopup.vue'
import StaffWorksTab from './components/StaffWorksTab.vue'
import StaffReviewsTab from './components/StaffReviewsTab.vue'
import StaffActionBar from './components/StaffActionBar.vue'

import BaseCard from '@/components/base/BaseCard.vue'

import BaseDateTimePicker from '@/components/base/BaseDateTimePicker.vue'

import BaseIconButton from '@/components/base/BaseIconButton.vue'

import BaseServiceRegionPicker from '@/components/base/BaseServiceRegionPicker.vue'

import EmptyState from '@/components/base/EmptyState.vue'

import LoadingState from '@/components/base/LoadingState.vue'

import StatusBadge from '@/components/base/StatusBadge.vue'

import { getStaffDetail, getStaffList, toggleStaffFavorite, getStaffWorks } from '@/api/staff'

import { getStaffReviews, getStaffReviewStats } from '@/packages/common/api/review'

import { checkScheduleAvailable, joinWaitlist } from '@/packages/common/api/schedule'

import { getServiceRegionTree } from '@/api/service'

import { ClientEnum } from '@/enums/appEnums'

import StaffLongDetailRenderer from '@/packages/components/staff-long-detail/staff-long-detail-renderer.vue'

import { hasLongDetailContent } from '@/packages/components/staff-long-detail/utils'

import { BACK_URL } from '@/enums/constantEnums'

import { useThemeStore } from '@/stores/theme'

import { useUserStore } from '@/stores/user'

import StaffBanner from '@/packages/components/staff-banner/staff-banner.vue'

import cache from '@/utils/cache'

import { client } from '@/utils/client'

import { isDevMode } from '@/utils/env'

import { goHome, goLoginWithBack, normalizePageRecoveryError } from '@/packages/common/utils/page-recovery'

import { confirmModal, showError, showSuccess } from '@/utils/feedback'

import {
    buildServiceRegionQuery,
    hasServiceRegion,
    loadServiceRegionSelection,
    normalizeServiceRegion,
    saveServiceRegionSelection,
    toServiceRegionParams
} from '@/utils/service-region'

import {
    BOOKING_RETURN_MODE_DETAIL_BACK,
    consumeStaffDetailRestoreSnapshot,
    consumeStaffDetailReturnState,
    getStaffBookingPageUrl,
    saveStaffDetailRestoreSnapshot,
    type StaffDetailRestoreSnapshot
} from '@/packages/common/utils/staff-booking'

type AlternativeStaffItem = {
    id: number
    name?: string
    avatar?: string
    category_name?: string
    experience_years?: number
    rating?: number | string
    order_count?: number
    is_recommend?: number
    price?: number | string | null
    price_text?: number | string
    has_price?: boolean
    profile?: string
    tags?: string[]
    [key: string]: any
}
type StaffCertificateItem = {
    id?: number | string
    name?: string
    type?: string
    sn?: string
    certificate_no?: string
    issue_org?: string
    issue_date?: string
    expire_date?: string
    image?: string
    verify_status_desc?: string
    audit_status_desc?: string
    is_expired?: number | boolean
    [key: string]: any
}
type StaffDetailPageOptions = Record<string, any>
type WechatEntryOptions = {
    path?: string
    scene?: number | string
    query?: StaffDetailPageOptions
}

const STAFF_DETAIL_ROUTE = 'packages/pages/staff_detail/staff_detail'

const WECHAT_SHARE_ENTRY_SCENES = new Set([1007, 1008, 1044, 1154])

const normalizeEntryPath = (path?: string) => String(path || '').replace(/^\/+/, '')

const normalizeStaffId = (value: unknown) => {
    const staffId = Number(value || 0)

    return Number.isFinite(staffId) ? staffId : 0
}

const normalizePositiveConfigNumber = (value: unknown, fallback: number) => {
    const parsedValue = Number(value)

    return Number.isFinite(parsedValue) && parsedValue > 0 ? parsedValue : fallback
}

const normalizeBannerMode = (value: unknown, fallback: number) => {
    const parsedValue = Number(value)

    return [1, 2].includes(parsedValue) ? parsedValue : fallback
}

const normalizeBannerIndicatorStyle = (value: unknown, fallback: number) => {
    const parsedValue = Number(value)

    return [0, 1, 2, 3, 4].includes(parsedValue) ? parsedValue : fallback
}

const normalizeBannerSwitch = (value: unknown, fallback: number) => {
    if (value === 0 || value === '0') {
        return 0
    }

    if (value === 1 || value === '1') {
        return 1
    }

    return fallback
}

const isShareEntryFlag = (value: unknown) => {
    if (value === true || value === 1) {
        return true
    }

    const normalized = String(value ?? '').trim().toLowerCase()

    return normalized === '1' || normalized === 'true'
}

const hasShareEntryFlag = (options?: StaffDetailPageOptions) => {
    return isShareEntryFlag(options?.from_share)
}

const isStaffDetailEntryPath = (path?: string) => normalizeEntryPath(path) === STAFF_DETAIL_ROUTE

const isDirectEntryPage = () => {
    try {
        return getCurrentPages().length <= 1
    } catch {
        return false
    }
}

const isWechatShareScene = (scene: unknown) => {
    const sceneCode = Number(scene)

    return Number.isFinite(sceneCode) && WECHAT_SHARE_ENTRY_SCENES.has(sceneCode)
}

const getWechatEntryOptions = () => {
    const optionList: WechatEntryOptions[] = []

    const uniRuntime = uni as unknown as {
        getEnterOptionsSync?: () => WechatEntryOptions
        getLaunchOptionsSync?: () => WechatEntryOptions
    }

    try {
        const enterOptions = uniRuntime.getEnterOptionsSync?.()

        if (enterOptions) {
            optionList.push(enterOptions)
        }

        const launchOptions = uniRuntime.getLaunchOptionsSync?.()

        if (launchOptions) {
            optionList.push(launchOptions)
        }
    } catch (error) {
        console.warn('读取微信入口参数失败：', error)
    }

    return optionList
}

const getStaffDetailWechatEntryQuery = () => {
    const staffDetailEntry = getWechatEntryOptions().find((entryOptions) =>
        isStaffDetailEntryPath(entryOptions.path)
    )

    return staffDetailEntry?.query || {}
}

const resolveStaffDetailPageOptions = (options?: StaffDetailPageOptions) => ({
    ...getStaffDetailWechatEntryQuery(),
    ...(options || {})
})

const resolveStaffIdFromOptions = (options?: StaffDetailPageOptions) => {
    return normalizeStaffId(options?.id ?? options?.staff_id ?? options?.staffId)
}

const getCurrentPageRuntimeOptions = () => {
    try {
        const pages = getCurrentPages()

        const currentPage = pages[pages.length - 1] as { options?: StaffDetailPageOptions }

        return currentPage?.options || {}
    } catch {
        return {}
    }
}

const isWechatShareDirectEntry = () => {
    if (!isDirectEntryPage()) {
        return false
    }

    return getWechatEntryOptions().some((entryOptions) => {
        if (!isStaffDetailEntryPath(entryOptions.path)) {
            return false
        }

        return hasShareEntryFlag(entryOptions.query) || isWechatShareScene(entryOptions.scene)
    })
}

const resolveShareEntry = (options?: StaffDetailPageOptions) => {
    return hasShareEntryFlag(options) || isWechatShareDirectEntry()
}

const staffId = ref<number>(0)

const staffInfo = ref<any>(null)

const detailLoading = ref(true)

const detailError = ref<ReturnType<typeof normalizePageRecoveryError> | null>(null)

const isShareEntry = ref(resolveShareEntry())

const getCurrentStaffIdForQuery = () => {
    return (
        normalizeStaffId(staffInfo.value?.id) ||
        normalizeStaffId(staffId.value) ||
        resolveStaffIdFromOptions(getCurrentPageRuntimeOptions())
    )
}

const hideWechatHomeButtonForShareEntry = () => {
    if (!isShareEntry.value) {
        return
    }

    const hideHomeButtonTask = uni.hideHomeButton() as unknown

    if (
        hideHomeButtonTask &&
        typeof (hideHomeButtonTask as Promise<unknown>).catch === 'function'
    ) {
        ;(hideHomeButtonTask as Promise<unknown>).catch((error: unknown) => {
            console.warn('隐藏首页按钮失败：', error)
        })
    }

}

const currentTab = ref('intro')

const presetDate = ref('') // 预设日期

const showDatePopup = ref(false)

const showRegionPopup = ref(false)

const datePickerModel = ref('')

const openDatePickerRequested = ref(false)

const openBookingPopupRequested = ref(false)

const pendingDatePickerAfterRegion = ref(false)

const selectedPackageId = ref<number>(0)

const waitlistId = ref<number>(0)

const showAlternativeStaffPopup = ref(false)

const showCertificatePopup = ref(false)

const alternativeStaffLoading = ref(false)

const alternativeStaffReason = ref('')

const alternativeStaffList = ref<AlternativeStaffItem[]>([])

const alternativeStaffQuerying = ref(false)

const activeCertificate = ref<StaffCertificateItem | null>(null)

const regionTree = ref<any[]>([])

const regionTreeLoading = ref(false)

let regionTreeLoadTask: Promise<void> | null = null

const detailScrollTop = ref(0)

let pendingRestoreScrollTop: number | null = null

const selectedRegion = ref(normalizeServiceRegion(loadServiceRegionSelection()))

const $theme = useThemeStore()

const userStore = useUserStore()

const DETAIL_TAB_KEYS = ['intro', 'works', 'reviews'] as const

// 轮播图数据

const bannerList = ref<any[]>([])

const bannerConfig = ref({
    banner_mode: 1,

    banner_small_height: 360,

    banner_large_height: 520,

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

    avg_score: '0.0',

    good_rate: 100
})

const detailImageFallbackMap = ref<Record<string, string>>({})

const detailImageFallback = '/static/images/user/default_avatar.png'

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

const getDetailResourceKey = (section: string, identifier: unknown) =>
    `${section}:${String(identifier ?? '')}`

const resolveDetailImageSrc = (section: string, src: unknown, identifier?: unknown) => {
    const resourceKey = getDetailResourceKey(section, identifier ?? src)

    const text = String(src || '').trim()

    return detailImageFallbackMap.value[resourceKey] || text || detailImageFallback
}

const logDetailResourceError = (section: string, src: unknown, error: any) => {
    if (!isDevMode()) {
        return
    }

    console.warn('人员详情资源加载失败', {
        section,

        staffId: staffId.value,

        src: String(src || ''),

        error: error?.detail || error || null
    })
}

const handleDetailImageError = (section: string, src: unknown, identifier: unknown, error: any) => {
    logDetailResourceError(section, src, error)

    const source = String(src || '').trim()

    const resourceKey = getDetailResourceKey(section, identifier ?? source)

    if (!source || source === detailImageFallback || detailImageFallbackMap.value[resourceKey]) {
        return
    }

    detailImageFallbackMap.value[resourceKey] = detailImageFallback
}

const resetDetailImageFallbacks = () => {
    detailImageFallbackMap.value = {}
}

const resolveStaffDetailError = (error: unknown, fallback = '操作失败') => {
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

const datePickerMinText = computed(() => formatDateText(getTomorrowDate()))

const datePickerMaxText = computed(() => formatDateText(getMaxDateForPicker()))

const hasSelectedRegion = computed(() => hasServiceRegion(selectedRegion.value))

const selectedRegionText = computed(() => {
    if (!hasSelectedRegion.value) {
        return '请选择服务区县'
    }

    const cityName = String(selectedRegion.value.city_name || '').trim()

    const districtName = String(selectedRegion.value.district_name || '').trim()

    return [cityName, districtName].filter(Boolean).join(' / ') || districtName || '请选择服务区县'
})

const selectedDistrictText = computed(() => {
    if (!hasSelectedRegion.value) {
        return ''
    }

    const districtName = String(selectedRegion.value.district_name || '').trim()
    const cityName = String(selectedRegion.value.city_name || '').trim()
    const provinceName = String(selectedRegion.value.province_name || '').trim()

    // 截取核心服务位置：区县优先（如“桃城区”），无区县时显示城市（如“衡水市”）
    return districtName || cityName || provinceName
})

const selectedDateDisplay = computed(() => {
    if (!presetDate.value) {
        return ''
    }

    const parts = presetDate.value.split('-')
    if (parts.length === 3) {
        const year = parseInt(parts[0], 10)
        const month = parseInt(parts[1], 10)
        const day = parseInt(parts[2], 10)
        const currentYear = new Date().getFullYear()

        // 截取核心日期：同年截取显示“M月D日”（如“9月21日”），跨年带上两位短年份（如“27年9月21日”）
        if (year && year !== currentYear) {
            return `${String(year).slice(-2)}年${month}月${day}日`
        }
        return `${month}月${day}日`
    }

    return presetDate.value
})

const displayTagList = computed(() => {
    const tags = Array.isArray(staffInfo.value?.tags) ? staffInfo.value.tags : []

    return tags.map((item: any) => String(item || '').trim()).filter((item: string) => item)
})

const displayCertificates = computed(() => {
    const certificates = Array.isArray(staffInfo.value?.certificates)
        ? (staffInfo.value.certificates as StaffCertificateItem[])
        : []

    return certificates.filter((item: any) => String(item?.image || '').trim())
})

const hasLongDetail = computed(() => hasLongDetailContent(staffInfo.value?.long_detail))

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

const primaryMetaText = computed(() => {
    const parts: string[] = []

    const categoryName = String(
        staffInfo.value?.category?.name || staffInfo.value?.category_name || ''
    ).trim()

    if (categoryName) {
        parts.push(categoryName)
    }

    const experienceYears = Number(staffInfo.value?.experience_years || 0)

    if (experienceYears > 0) {
        parts.push(`${experienceYears}年经验`)
    }

    if (hasSelectedRegion.value) {
        parts.push(selectedRegionText.value)
    }

    return parts.join(' · ') || staffSummaryText.value
})

const compactMetricList = computed(() => [
    {
        label: '综合评分',
        value: staffInfo.value?.rating ? `${Number(staffInfo.value.rating).toFixed(1)}` : '5.0'
    },
    {
        label: '服务新人',
        value: `${staffInfo.value?.order_count || 0}对`
    },
    {
        label: '好评率',
        value: `${reviewStats.value?.good_rate ?? 100}%`
    },
    {
        label: '人气热度',
        value: `${staffInfo.value?.view_count || 0}`
    }
])

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

const currentCategoryId = computed(() =>
    Number(staffInfo.value?.category_id || staffInfo.value?.category?.id || 0)
)

const getPackageId = (pkg: any) => Number(pkg?.package_id || pkg?.id || 0)

const isRecommendedPackage = (pkg: any) =>
    Number(pkg?.is_recommend ?? pkg?.package?.is_recommend ?? 0) === 1

const resetAlternativeStaffState = () => {
    showAlternativeStaffPopup.value = false

    alternativeStaffLoading.value = false

    alternativeStaffReason.value = ''

    alternativeStaffList.value = []
}

const cloneSerializable = <T>(value: T): T | null => {
    try {
        return JSON.parse(JSON.stringify(value ?? null)) as T | null
    } catch (error) {
        console.warn('详情快照序列化失败：', error)
        return null
    }
}

const applyStaffBannerData = (data: any) => {
    bannerList.value = Array.isArray(data?.banners) ? data.banners : []

    const currentConfig = bannerConfig.value

    bannerConfig.value = {
        banner_mode: normalizeBannerMode(data?.banner_mode, currentConfig.banner_mode),

        banner_small_height: normalizePositiveConfigNumber(
            data?.banner_small_height,
            currentConfig.banner_small_height
        ),

        banner_large_height: normalizePositiveConfigNumber(
            data?.banner_large_height,
            currentConfig.banner_large_height
        ),

        banner_indicator_style: normalizeBannerIndicatorStyle(
            data?.banner_indicator_style,
            currentConfig.banner_indicator_style
        ),

        banner_autoplay: normalizeBannerSwitch(data?.banner_autoplay, currentConfig.banner_autoplay),

        banner_interval: normalizePositiveConfigNumber(
            data?.banner_interval,
            currentConfig.banner_interval
        )
    }
}

const applyStaffDetailDisplayData = (data: any) => {
    resetDetailImageFallbacks()

    staffInfo.value = data

    applyStaffBannerData(data)
}

const scheduleRestoreDetailScroll = () => {
    if (pendingRestoreScrollTop === null) {
        return
    }

    const scrollTop = pendingRestoreScrollTop

    nextTick(() => {
        setTimeout(() => {
            uni.pageScrollTo({
                scrollTop,
                duration: 0
            })
        }, 0)
    })
}

const applyDetailRestoreSnapshot = () => {
    const snapshot = consumeStaffDetailRestoreSnapshot()

    if (!snapshot || snapshot.staff_id !== staffId.value || !snapshot.staff_info) {
        return null
    }

    const normalizedRegion = normalizeServiceRegion(snapshot.selected_region)

    applyStaffDetailDisplayData(snapshot.staff_info)

    selectedPackageId.value = Number(snapshot.package_id || 0)

    syncSelectedPackage()

    selectedRegion.value = normalizedRegion

    if (hasServiceRegion(normalizedRegion)) {
        saveServiceRegionSelection(normalizedRegion)
    }

    if (snapshot.preset_date) {
        presetDate.value = normalizeSelectedDateText(snapshot.preset_date)
    }

    if (DETAIL_TAB_KEYS.includes(snapshot.current_tab as (typeof DETAIL_TAB_KEYS)[number])) {
        currentTab.value = snapshot.current_tab
    }

    pendingRestoreScrollTop = snapshot.scroll_top > 0 ? snapshot.scroll_top : 0

    return snapshot
}

const saveCurrentDetailRestoreSnapshot = () => {
    const snapshot: StaffDetailRestoreSnapshot = {
        staff_id: staffId.value,
        package_id: selectedPackageId.value,
        staff_info: cloneSerializable(staffInfo.value),
        selected_region: cloneSerializable(selectedRegion.value) || normalizeServiceRegion(null),
        preset_date: presetDate.value,
        current_tab: currentTab.value,
        scroll_top: detailScrollTop.value,
        saved_at: Date.now()
    }

    saveStaffDetailRestoreSnapshot(snapshot)
}

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

const applyPendingDetailReturnState = () => {
    const state = consumeStaffDetailReturnState()

    if (!state || state.staff_id !== staffId.value) {
        return
    }

    selectedPackageId.value = state.package_id

    if (staffInfo.value) {
        syncSelectedPackage()
    }
}

const handleInlineRegionEdit = () => {
    pendingDatePickerAfterRegion.value = false

    openRegionPicker()
}

const handleInlineDateEdit = () => {
    if (!hasSelectedRegion.value) {
        showError('请先选择服务地区')

        pendingDatePickerAfterRegion.value = true

        openRegionPicker()

        return
    }

    pendingDatePickerAfterRegion.value = false

    openDatePicker()
}

const handleDetailRecoveryAction = () => {
    if (detailError.value?.kind === 'auth') {
        goLoginWithBack(buildStaffDetailQuery())
        return
    }

    void getDetail()
}

const handleAlternativePickDate = () => {
    if (alternativeStaffQuerying.value) {
        return
    }

    showAlternativeStaffPopup.value = false

    setTimeout(() => handleInlineDateEdit(), 0)
}

// 标签页配置

const tabs = [
    { key: 'intro', label: '主创介绍' },
    { key: 'works', label: '精选作品' },
    { key: 'reviews', label: '新人评价' }
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

watch(showCertificatePopup, (visible) => {
    if (!visible) {
        activeCertificate.value = null
    }
})

// 获取详情

const getDetail = async () => {
    if (!staffId.value) {
        staffInfo.value = null
        detailLoading.value = false
        detailError.value = normalizePageRecoveryError('缺少人员信息，请从列表重新进入', '缺少人员信息，请从列表重新进入')
        return
    }

    detailLoading.value = true
    detailError.value = null

    try {
        const params: Record<string, any> & { id: number } = { id: staffId.value }

        if (presetDate.value) {
            params.date = presetDate.value
        }

        Object.assign(params, toServiceRegionParams(selectedRegion.value))

        const data = await getStaffDetail(params)

        if (!data?.id) {
            throw new Error('人员不存在或已下架，请返回列表重新选择')
        }

        applyStaffDetailDisplayData(data)

        syncSelectedPackage()

        if (currentTab.value === 'reviews') {
            if (!reviewStatsLoaded.value) {
                loadReviewStats()
            }

            if (!reviewsInitialized.value) {
                loadReviews(true)
            }
        }

        scheduleRestoreDetailScroll()

        pendingRestoreScrollTop = null
    } catch (e: any) {
        staffInfo.value = null
        detailError.value = normalizePageRecoveryError(e, '获取人员详情失败，请稍后重试')
    } finally {
        detailLoading.value = false
    }
}

const getRegionTree = async (force = false) => {
    if (!force && regionTree.value.length) {
        return Promise.resolve()
    }

    if (regionTreeLoading.value && regionTreeLoadTask) {
        return regionTreeLoadTask
    }

    regionTreeLoading.value = true

    regionTreeLoadTask = (async () => {
        try {
            const data = await getServiceRegionTree()

            regionTree.value = Array.isArray(data) ? data : []
        } catch (error: any) {
            const errorMsg =
                typeof error === 'string'
                    ? error
                    : error?.msg || error?.message || '加载服务地区失败'

            showError(errorMsg)
        } finally {
            regionTreeLoading.value = false
            regionTreeLoadTask = null
        }
    })()

    return regionTreeLoadTask
}

// 加载作品列表

const loadWorks = async () => {
    if (worksLoading.value) return

    worksLoading.value = true

    try {
        const data = await getStaffWorks({ staff_id: staffId.value })

        worksList.value = data || []
    } catch (e: any) {
        showError(resolveStaffDetailError(e, '加载作品失败'))
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

            avg_score: Number(data?.avg_score ?? 0).toFixed(1),

            good_rate: Number(data?.good_rate || 0)
        }

        reviewStatsLoaded.value = true
    } catch (e: any) {
        showError(resolveStaffDetailError(e, '加载评价统计失败'))
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
        showError(resolveStaffDetailError(e, '加载评价失败'))
    } finally {
        reviewsLoading.value = false
    }
}

// 收藏/取消收藏

const handleToggleFavorite = async () => {
    // 检查登录状态

    if (!userStore.isLogin) {
        showError('请先登录')

        setTimeout(() => {
            uni.navigateTo({ url: '/pages/login/login' })
        }, 1500)

        return
    }

    try {
        await toggleStaffFavorite({ id: staffId.value })

        staffInfo.value.is_favorite = !staffInfo.value.is_favorite

        showSuccess(staffInfo.value.is_favorite ? '收藏成功' : '已取消收藏')
    } catch (e: any) {
        showError(resolveStaffDetailError(e, '操作失败'))
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

    uni.setClipboardData({
        data: shareContent,

        success: () => {
            showSuccess(toastTitle)
        }
    })
}

const openRegionPicker = () => {
    if (showRegionPopup.value) {
        return
    }

    showRegionPopup.value = true
}

const hideRegionPicker = () => {
    showRegionPopup.value = false
}

const closeRegionPicker = () => {
    hideRegionPicker()

    pendingDatePickerAfterRegion.value = false
}

const handleServiceRegionConfirm = async (value: Record<string, any>) => {
    const nextRegion = normalizeServiceRegion(value)

    if (!hasServiceRegion(nextRegion)) {
        showError('请选择到区县')

        return
    }

    selectedRegion.value = nextRegion

    saveServiceRegionSelection(selectedRegion.value)

    hideRegionPicker()

    resetAlternativeStaffState()

    await getDetail()

    if (pendingDatePickerAfterRegion.value) {
        pendingDatePickerAfterRegion.value = false

        setTimeout(() => openDatePicker(), 0)

        return
    }
}

const openDatePicker = () => {
    if (!hasSelectedRegion.value) {
        pendingDatePickerAfterRegion.value = true

        openRegionPicker()

        return
    }

    datePickerModel.value = formatDateText(getEffectiveSelectableDate(presetDate.value))

    showDatePopup.value = true
}

const hideDatePicker = () => {
    showDatePopup.value = false
}

const closeDatePicker = () => {
    hideDatePicker()

    pendingDatePickerAfterRegion.value = false
}

const handleDatePickerConfirm = async (value: unknown) => {
    const pickerValue =
        typeof value === 'string'
            ? value
            : String((value as Record<string, any>)?.value || datePickerModel.value || '')

    const nextDate = normalizeSelectedDateText(pickerValue) || formatDateText(getTomorrowDate())

    presetDate.value = nextDate

    hideDatePicker()

    resetAlternativeStaffState()

    await getDetail()

    pendingDatePickerAfterRegion.value = false
}

const buildStaffDetailQuery = (extra: Record<string, any> = {}) => {
    const queryStaffId = getCurrentStaffIdForQuery()

    const params = [`id=${queryStaffId}`]

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

const buildTargetStaffDetailQuery = (targetStaffId: number, extra: Record<string, any> = {}) => {
    const params = [`id=${targetStaffId}`]

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

const getBookingPageUrl = () =>
    getStaffBookingPageUrl({
        staff_id: staffId.value,

        package_id: selectedPackageId.value,

        waitlist_id: waitlistId.value,

        date: presetDate.value,

        return_mode: BOOKING_RETURN_MODE_DETAIL_BACK,

        ...selectedRegion.value
    })

const navigateToBookingPage = () => {
    saveCurrentDetailRestoreSnapshot()

    uni.navigateTo({
        url: getBookingPageUrl()
    })
}

const ensureBookingLogin = (message = '请先登录后预约') => {
    if (userStore.isLogin) {
        return true
    }

    cache.set(BACK_URL, getBookingPageUrl())

    showError(message)

    setTimeout(() => {
        uni.navigateTo({ url: '/pages/login/login' })
    }, 300)

    return false
}

const fetchAlternativeStaffList = async () => {
    if (!currentCategoryId.value || !presetDate.value || !hasSelectedRegion.value) {
        alternativeStaffList.value = []

        return
    }

    const result = await getStaffList({
        page_no: 1,

        page_size: 6,

        category_id: currentCategoryId.value,

        date: presetDate.value,

        sort: 'default',

        ...toServiceRegionParams(selectedRegion.value)
    })

    const list = Array.isArray(result?.lists) ? result.lists : []

    alternativeStaffList.value = list

        .filter((item: AlternativeStaffItem) => Number(item?.id || 0) !== staffId.value)

        .slice(0, 6)
}

const openAlternativeStaffPopup = async (reason = '') => {
    alternativeStaffReason.value = reason || '当前档期暂不可预约'

    alternativeStaffLoading.value = true

    alternativeStaffList.value = []

    showAlternativeStaffPopup.value = true

    try {
        await fetchAlternativeStaffList()
    } catch (error: any) {
        showError(resolveStaffDetailError(error, '加载同类服务人员失败'))

        alternativeStaffList.value = []
    } finally {
        alternativeStaffLoading.value = false
    }
}

const handleAlternativeStaffSelect = (item: AlternativeStaffItem) => {
    if (alternativeStaffQuerying.value) {
        return
    }

    const targetStaffId = Number(item?.id || 0)

    if (!targetStaffId) {
        showError('服务人员信息错误')

        return
    }

    showAlternativeStaffPopup.value = false

    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?${buildTargetStaffDetailQuery(
            targetStaffId,

            {
                open_booking_popup: 1
            }
        )}`
    })
}

const handleAlternativeJoinWaitlist = async () => {
    if (alternativeStaffQuerying.value) {
        return
    }

    if (selectedPackageId.value <= 0) {
        showError('当前人员暂无可候补套餐，请重新选择日期')

        return
    }

    alternativeStaffQuerying.value = true

    try {

        if (!await remindBeforeOaAction()) return
        await joinWaitlist({
            staff_id: staffId.value,

            date: presetDate.value,

            package_id: selectedPackageId.value
        })

        showAlternativeStaffPopup.value = false

        showSuccess('已加入候补')
    } catch (error: any) {
        showError(resolveStaffDetailError(error, '加入候补失败'))
    } finally {
        alternativeStaffQuerying.value = false
    }
}

// 立即预约

const handleBook = async () => {
    if (!staffId.value || staffId.value === 0) {
        showError('服务人员信息错误')

        return
    }

    if (!hasSelectedRegion.value || !presetDate.value) {
        showError('请先选择服务地区与预约日期')

        if (!hasSelectedRegion.value) {
            handleInlineRegionEdit()

            return
        }

        handleInlineDateEdit()

        return
    }

    if (!ensureBookingLogin()) {
        return
    }

    if (alternativeStaffQuerying.value) {
        return
    }

    alternativeStaffQuerying.value = true

    try {
        const result = await checkScheduleAvailable({
            staff_id: staffId.value,

            date: presetDate.value,

            ...toServiceRegionParams(selectedRegion.value)
        })

        if (result?.is_available !== false) {
            navigateToBookingPage()

            return
        }

        await openAlternativeStaffPopup(
            String(result?.message || result?.status_desc || '').trim() || '当前档期暂不可预约'
        )
    } catch (error: any) {
        showError(resolveStaffDetailError(error, '预约校验失败'))
    } finally {
        alternativeStaffQuerying.value = false
    }
}

// 预览作品图片

const goWorkDetail = (work: any) => {
    if (!work?.id) {
        showError('作品信息错误')

        return
    }

    uni.navigateTo({
        url: `/packages/pages/staff_work_detail/staff_work_detail?id=${work.id}`
    })
}

const openCertificatePopup = (certificate: StaffCertificateItem) => {
    if (!certificate) {
        return
    }

    activeCertificate.value = certificate

    showCertificatePopup.value = true
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

        path: `/packages/pages/staff_detail/staff_detail?${buildStaffDetailQuery({
            from_share: 1
        })}`
    }

    const avatar = String(staffInfo.value?.avatar || '').trim()

    if (avatar) {
        payload.imageUrl = avatar
    }

    return payload
}

onLoad((options) => {
    $theme.setScene('consumer')

    const pageOptions = resolveStaffDetailPageOptions(options)

    isShareEntry.value = resolveShareEntry(pageOptions)

    hideWechatHomeButtonForShareEntry()

    const pageStaffId = resolveStaffIdFromOptions(pageOptions)

    if (pageStaffId) {
        staffId.value = pageStaffId
    }

    selectedRegion.value = normalizeServiceRegion({
        ...loadServiceRegionSelection(),

        ...pageOptions
    })

    if (hasServiceRegion(selectedRegion.value)) {
        saveServiceRegionSelection(selectedRegion.value)
    }

    if (pageOptions?.date) {
        presetDate.value = normalizeSelectedDateText(pageOptions.date)
    }

    if (pageOptions?.package_id) {
        selectedPackageId.value = Number(pageOptions.package_id)
    }

    if (pageOptions?.waitlist_id) {
        waitlistId.value = Number(pageOptions.waitlist_id)
    }

    if (pageOptions?.open_date_picker === '1') {
        openDatePickerRequested.value = true
    }

    if (pageOptions?.open_booking_popup === '1') {
        openBookingPopupRequested.value = true
    }

    if (pageOptions?.tab && ['intro', 'works', 'reviews'].includes(pageOptions.tab)) {
        currentTab.value = pageOptions.tab
    }

    applyDetailRestoreSnapshot()
})

onShow(async () => {
    $theme.setScene('consumer')

    isShareEntry.value = isShareEntry.value || resolveShareEntry()

    hideWechatHomeButtonForShareEntry()

    applyPendingDetailReturnState()

    scheduleRestoreDetailScroll()

    void getRegionTree().catch(() => null)

    await getDetail()

    if (staffInfo.value && (openBookingPopupRequested.value || openDatePickerRequested.value)) {
        const shouldOpenDateEditor = openDatePickerRequested.value

        openBookingPopupRequested.value = false

        openDatePickerRequested.value = false

        if (shouldOpenDateEditor) {
            setTimeout(() => handleInlineDateEdit(), 0)
        } else {
            setTimeout(() => handleBook(), 0)
        }
    }
})

onPageScroll((event) => {
    detailScrollTop.value = Number(event.scrollTop || 0)
})

onShareAppMessage(() => {
    return buildSharePayload()
})

onShareTimeline(() => {
    const sharePayload = buildSharePayload()

    const timelinePayload: {
        title: string

        query: string

        imageUrl?: string
    } = {
        title: sharePayload.title,

        query: buildStaffDetailQuery({ from_share: 1 })
    }

    if (sharePayload.imageUrl) {
        timelinePayload.imageUrl = sharePayload.imageUrl
    }

    return timelinePayload
})

</script>

<style lang="scss" scoped>
/* ==========================================================================
   Haute Wedding Couture Design System - Staff Detail Page
   ========================================================================== */

.staff-detail {
    min-height: 100vh;
    background-color: var(--wm-color-bg-page, #FAF8F2);
    box-sizing: border-box;
}

.staff-detail__content {
    display: flex;
    flex-direction: column;
    gap: 0;
    padding-bottom: calc(180rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
}

/* 页面内容流：统一20rpx边距，杜绝层层嵌套导致内部被压缩变小 */
.staff-detail__main {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    padding: 0 20rpx;
    box-sizing: border-box;
    width: 100%;
}

/* ==========================================================================
   1. 顶部横幅 Hero Card (全宽大片视觉，下弧收边)
   ========================================================================== */
.hero-card {
    width: 100%;
    margin-bottom: 24rpx !important;
    overflow: hidden;
    border-radius: 0 !important;
    box-shadow: none !important;

    &__banner {
        width: 100%;
        display: block;
        border-radius: 0 !important;
    }

    :deep(.banner-container),
    :deep(.banner-swiper),
    :deep(.media-container),
    :deep(.banner-media),
    :deep(.banner-video) {
        border-radius: 0 !important;
    }
}

/* ==========================================================================
   2. 主创大师名片 (Master Artisan Card)
   ========================================================================== */
.info-card {
    position: relative;
    z-index: 10;
    margin: 0;
    width: 100%;
    border-radius: 28rpx;
    overflow: hidden;
    background: #181614 !important;
    border: 1rpx solid rgba(217, 190, 130, 0.35) !important;
    box-shadow: 0 16rpx 40rpx rgba(24, 22, 20, 0.16) !important;
    box-sizing: border-box;

    &__inner {
        padding: 28rpx 24rpx;
        display: flex;
        flex-direction: column;
        gap: 20rpx;
    }

    &__header {
        display: flex;
        align-items: center;
        gap: 20rpx;
    }

    &__avatar-box {
        position: relative;
        width: 120rpx;
        height: 120rpx;
        flex-shrink: 0;
    }

    &__avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 4rpx solid rgba(217, 190, 130, 0.5);
        box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.3);
        background: #242220;
    }

    &__verify-badge {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 36rpx;
        height: 36rpx;
        border-radius: 50%;
        background: linear-gradient(135deg, #D9BE82 0%, #C6A15B 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3rpx solid #181614;
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.2);
    }

    &__identity {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: 14rpx;
        flex-wrap: wrap;
    }

    &__name {
        font-family: 'Playfair Display', -apple-system, 'Songti SC', serif;
        font-size: 38rpx;
        font-weight: 700;
        color: #FAF8F2;
        letter-spacing: 1rpx;
        line-height: 1.2;
    }

    &__summary {
        font-size: 24rpx;
        color: #B8B3AA;
        line-height: 1.4;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__favorite {
        flex-shrink: 0;
    }

    &__badge-list {
        display: flex;
        flex-wrap: wrap;
        gap: 12rpx;
    }

    /* 4列口碑指标行 */
    &__metric-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        background: rgba(255, 255, 255, 0.05);
        border: 1rpx solid rgba(217, 190, 130, 0.2);
        border-radius: 18rpx;
        padding: 18rpx 0;
    }

    &__metric {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;

        &:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 20%;
            height: 60%;
            width: 1rpx;
            background: rgba(255, 255, 255, 0.08);
        }
    }

    &__metric-value {
        font-size: 32rpx;
        font-weight: 700;
        color: #FAF8F2;
        line-height: 1.2;
    }

    &__metric-label {
        font-size: 21rpx;
        color: #A8A39D;
        margin-top: 6rpx;
    }

    /* 价格栏 */
    &__price-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1rpx solid rgba(255, 255, 255, 0.08);
        padding-top: 18rpx;
    }

    &__price-label {
        font-size: 24rpx;
        color: #A8A39D;
    }

    &__price-group {
        display: flex;
        align-items: baseline;
    }

    &__price-symbol {
        font-size: 26rpx;
        color: #D9BE82;
        font-weight: 600;
    }

    &__price-value {
        font-size: 40rpx;
        color: #D9BE82;
        font-weight: 800;
        letter-spacing: -0.5rpx;
        margin: 0 4rpx;
    }

    &__price-unit {
        font-size: 22rpx;
        color: #8E8880;
    }

    &__price-negotiable {
        font-size: 30rpx;
        color: #D9BE82;
        font-weight: 600;
    }
}

/* ==========================================================================
   3. 服务档期与地区筛选栏 (Concierge Booking Strip)
   ========================================================================== */
.booking-brief-card {
    margin: 0;
    width: 100%;
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 22rpx 24rpx;
    box-shadow: 0 6rpx 24rpx rgba(24, 22, 20, 0.04);
    border: 1rpx solid rgba(217, 190, 130, 0.3);
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    box-sizing: border-box;
}

.booking-brief-header {
    display: flex !important;
    flex-direction: row !important;
    justify-content: space-between !important;
    align-items: center !important;
    width: 100%;
    box-sizing: border-box;
}

.booking-brief-heading {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 12rpx;
    flex: 1;
    min-width: 0;
}

.booking-brief-icon-box {
    width: 44rpx;
    height: 44rpx;
    border-radius: 12rpx;
    background: linear-gradient(135deg, #F5E8C7 0%, #D9BE82 100%);
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0;
}

.booking-brief-title {
    font-size: 28rpx;
    font-weight: 700;
    color: #181614;
    letter-spacing: 0.5rpx;
    line-height: 1.2;
}

.booking-brief-status-tag {
    display: inline-flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 8rpx;
    padding: 6rpx 16rpx;
    border-radius: 999rpx;
    background: #FAF6EE;
    border: 1rpx solid rgba(217, 190, 130, 0.35);
    flex-shrink: 0;
}

.booking-brief-status-dot {
    width: 10rpx;
    height: 10rpx;
    border-radius: 50%;
    background: #52C41A;
    box-shadow: 0 0 6rpx rgba(82, 196, 26, 0.5);
    flex-shrink: 0;
}

.booking-brief-status-text {
    font-size: 20rpx;
    color: #7A5B20;
    font-weight: 600;
    line-height: 1;
}

.booking-brief-grid {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 14rpx;
    width: 100%;
    box-sizing: border-box;
}

.booking-picker-item {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 10rpx;
    min-height: 96rpx;
    padding: 0 16rpx;
    border-radius: 18rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.35);
    background: #FFFDF8;
    box-sizing: border-box;
    transition: all 0.2s ease;

    &:active {
        border-color: #D9BE82;
        background: #FDFBF5;
    }

    &__icon-wrap {
        width: 48rpx;
        height: 48rpx;
        border-radius: 14rpx;
        background: #F1E5C8;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0;
    }

    &__content {
        flex: 1;
        min-width: 0;
        display: flex !important;
        flex-direction: column !important;
        gap: 4rpx;
    }

    &__label {
        font-size: 20rpx;
        line-height: 1.2;
        color: #8C8273;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__value {
        font-size: 26rpx;
        line-height: 1.3;
        font-weight: 700;
        color: #181614;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;

        &--placeholder {
            color: #9E9890;
            font-weight: 400;
            font-size: 24rpx;
        }
    }
}

/* ==========================================================================
   4. 内容 Tabs 切换
   ========================================================================== */
.tabs-section {
    margin: 0;
    position: sticky;
    top: 0;
    z-index: 20;
    background: var(--wm-color-bg-page, #FAF8F2);
    padding: 10rpx 0;
    box-sizing: border-box;
    width: 100%;
}

.tabs-wrapper {
    display: flex;
    justify-content: space-around;
    background: #FFFFFF;
    border-radius: 20rpx;
    padding: 6rpx;
    box-shadow: 0 4rpx 16rpx rgba(24, 22, 20, 0.04);
    border: 1rpx solid rgba(217, 190, 130, 0.2);
    width: 100%;
    box-sizing: border-box;
}

.tab-item {
    flex: 1;
    text-align: center;
    padding: 18rpx 0;
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;

    &:active {
        opacity: 0.8;
    }
}

.tab-text {
    font-size: 28rpx;
    color: #7E7870;
    font-weight: 500;
    transition: all 0.2s ease;

    &--active {
        color: #181614;
        font-weight: 700;
        font-size: 30rpx;
    }
}

.tab-indicator {
    width: 36rpx;
    height: 6rpx;
    background: linear-gradient(90deg, #D9BE82, #C6A15B);
    border-radius: 3rpx;
    position: absolute;
    bottom: 8rpx;
    left: 50%;
    transform: translateX(-50%);
}

/* ==========================================================================
   5. Tab 1 内容：主创介绍与资质 (详情零额外内嵌边距，视觉放大充实)
   ========================================================================== */
.tab-content {
    margin: 0;
    width: 100%;
    box-sizing: border-box;
}

.content-section {
    display: flex;
    flex-direction: column;

    &--stack {
        gap: 20rpx;
    }
}

/* 详情流卡片：padding 为 0，让作品大图/长图全幅舒展展示，杜绝内部被挤小 */
.detail-stream-shell {
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 4rpx 20rpx rgba(24, 22, 20, 0.03);
    border: 1rpx solid rgba(217, 190, 130, 0.15);
    width: 100%;
    box-sizing: border-box;
}

.soft-card {
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 24rpx;
    box-shadow: 0 4rpx 20rpx rgba(24, 22, 20, 0.03);
    border: 1rpx solid rgba(217, 190, 130, 0.2);
    width: 100%;
    box-sizing: border-box;

    &__header {
        display: flex;
        align-items: center;
        gap: 12rpx;
        margin-bottom: 18rpx;
    }

    &__dot {
        width: 8rpx;
        height: 24rpx;
        background: linear-gradient(180deg, #D9BE82 0%, #C6A15B 100%);
        border-radius: 4rpx;
    }

    &__title {
        font-size: 27rpx;
        font-weight: 700;
        color: #181614;
    }

    &__meta {
        font-size: 21rpx;
        color: #9E9890;
        margin-left: auto;
    }
}

.soft-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.soft-tag {
    background: #FAF6EE;
    border: 1rpx solid rgba(217, 190, 130, 0.35);
    border-radius: 12rpx;
    padding: 10rpx 22rpx;

    &__text {
        font-size: 24rpx;
        color: #75561E;
        font-weight: 500;
    }
}

/* 资质证书横滑栏 */
.certs-scroll {
    width: 100%;
    white-space: nowrap;
}

.certs-wrapper {
    display: inline-flex;
    gap: 18rpx;
    padding-bottom: 6rpx;
}

.cert-item {
    width: 220rpx;
    display: inline-flex;
    flex-direction: column;
    gap: 12rpx;
}

.cert-image-wrap {
    width: 220rpx;
    height: 150rpx;
    border-radius: 16rpx;
    overflow: hidden;
    position: relative;
    background: #F2EFE9;
    border: 1rpx solid rgba(217, 190, 130, 0.25);
}

.cert-image {
    width: 100%;
    height: 100%;
}

.cert-view-badge {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(24, 22, 20, 0.7);
    backdrop-filter: blur(4px);
    padding: 6rpx 0;
    text-align: center;
    font-size: 20rpx;
    color: #FAF8F2;
    font-weight: 500;
}

.cert-name {
    font-size: 22rpx;
    color: #4A4540;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* 服务保障网格 */
.guarantee-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14rpx;
    width: 100%;
    box-sizing: border-box;
}

.guarantee-item {
    background: #FAF8F4;
    border-radius: 18rpx;
    padding: 20rpx 16rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    border: 1rpx solid rgba(217, 190, 130, 0.18);
}

.guarantee-title {
    font-size: 24rpx;
    font-weight: 700;
    color: #181614;
    margin-top: 10rpx;
}

.guarantee-desc {
    font-size: 20rpx;
    color: #8E8880;
    margin-top: 4rpx;
}

/* ==========================================================================
   6. 加载与错误态 (Loading & Empty States)
   ========================================================================== */
.loading-container,
.detail-state-shell {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background: var(--wm-color-bg-page, #FAF8F2);
}

.detail-state-shell {
    flex-direction: column;
    gap: 18rpx;
    box-sizing: border-box;

    &__actions {
        margin-top: 24rpx;
    }

    &__link {
        font-size: 26rpx;
        color: #C6A15B;
        text-decoration: underline;
        cursor: pointer;
    }
}
</style>
