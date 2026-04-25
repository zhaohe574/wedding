<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="staff" tone="workspace" hasSafeBottom>
        <BaseNavbar title="服务人员中心" title-align="left" />

        <view class="staff-center-page">
            <view class="staff-center-page__content wm-page-content">
                <BaseCard
                    variant="hero"
                    scene="staff"
                    class="workbench-hero"
                    :background="heroCardBackground"
                    :border="heroCardBorder"
                    :box-shadow="heroCardShadow"
                >
                    <view class="workbench-hero__top">
                        <view class="hero-eyebrow">
                            <text class="hero-eyebrow__text">服务工作台</text>
                        </view>

                        <view class="workbench-hero__badges">
                            <StatusBadge
                                :tone="profileStatusBadge.tone"
                                size="sm"
                                class="workbench-hero__badge"
                            >
                                {{ profileStatusBadge.text }}
                            </StatusBadge>

                            <StatusBadge
                                v-if="auditBadge.text"
                                :tone="auditBadge.tone"
                                size="sm"
                                class="workbench-hero__badge"
                            >
                                {{ auditBadge.text }}
                            </StatusBadge>
                        </view>
                    </view>

                    <view class="workbench-hero__head">
                        <view class="workbench-hero__profile">
                            <image
                                class="workbench-hero__avatar"
                                :src="displayProfile.avatar || defaultAvatar"
                                mode="aspectFill"
                            />

                            <view class="workbench-hero__copy">
                                <text class="workbench-hero__title">{{ profileName }}</text>
                                <text class="workbench-hero__subtitle">{{ profileMetaLine }}</text>
                            </view>
                        </view>

                        <view
                            class="profile-entry"
                            @click="goPage('/packages/pages/staff_profile/staff_profile')"
                        >
                            <text class="profile-entry__text">资料</text>
                            <tn-icon name="right" size="18" color="#F7F0DF" />
                        </view>
                    </view>

                    <text class="workbench-hero__headline">{{ heroHeadline }}</text>

                    <view class="workbench-focus-strip">
                        <view
                            v-for="item in focusHighlights"
                            :key="item.key"
                            :class="['focus-pill', { 'focus-pill--active': item.active }]"
                        >
                            <text class="focus-pill__label">{{ item.label }}</text>

                            <view class="focus-pill__value-row">
                                <text class="focus-pill__value">{{ item.value }}</text>
                                <text class="focus-pill__unit">{{ item.unit }}</text>
                            </view>

                            <text class="focus-pill__hint">{{ item.hint }}</text>
                        </view>
                    </view>

                    <view class="primary-action" @click="primaryAction.action()">
                        <view class="primary-action__main">
                            <view class="primary-action__icon">
                                <tn-icon :name="primaryAction.icon" size="26" color="#111111" />
                            </view>

                            <view class="primary-action__copy">
                                <text class="primary-action__label">{{ primaryAction.label }}</text>
                                <text class="primary-action__hint">{{ primaryAction.hint }}</text>
                            </view>
                        </view>

                        <view class="primary-action__value">
                            <text class="primary-action__number">{{ primaryAction.value }}</text>
                            <text class="primary-action__unit">{{ primaryAction.unit }}</text>
                        </view>
                    </view>

                    <view class="secondary-action-grid">
                        <view
                            v-for="item in secondaryActions"
                            :key="item.key"
                            :class="['secondary-action', `secondary-action--${item.tone}`]"
                            @click="item.action()"
                        >
                            <view class="secondary-action__top">
                                <view
                                    :class="[
                                        'secondary-action__icon',
                                        `secondary-action__icon--${item.tone}`
                                    ]"
                                >
                                    <tn-icon :name="item.icon" size="22" :color="item.iconColor" />
                                </view>

                                <tn-icon name="right" size="18" color="#9A9388" />
                            </view>

                            <text class="secondary-action__label">{{ item.label }}</text>

                            <view class="secondary-action__value-row">
                                <text class="secondary-action__value">{{ item.value }}</text>
                                <text class="secondary-action__unit">{{ item.unit }}</text>
                            </view>

                            <text class="secondary-action__hint">{{ item.hint }}</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="overview-panel">
                    <view class="section-head wm-section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title wm-section-title">今日概览</text>
                            <text class="section-head__desc wm-section-desc">先看履约节奏</text>
                        </view>

                        <text class="section-head__meta wm-helper-text">{{
                            overviewMetaText
                        }}</text>
                    </view>

                    <view class="metric-grid">
                        <view
                            v-for="item in overviewMetrics"
                            :key="item.label"
                            :class="['metric-card', { 'metric-card--accent': item.accent }]"
                        >
                            <text class="metric-card__label">{{ item.label }}</text>
                            <view class="metric-card__value-row">
                                <text class="metric-card__value">{{ item.value }}</text>
                                <text class="metric-card__unit">{{ item.unit }}</text>
                            </view>
                            <text class="metric-card__caption">{{ item.caption }}</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="order-panel">
                    <view class="section-head wm-section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title wm-section-title">订单动态</text>
                            <text class="section-head__desc wm-section-desc"
                                >最近需要跟进的内容</text
                            >
                        </view>

                        <view class="section-link" @click="goOrders()">
                            <text class="section-link__text">全部订单</text>
                            <tn-icon name="right" size="18" color="#9A9388" />
                        </view>
                    </view>

                    <LoadingState
                        v-if="loading && !hasLoaded && !recentOrderCards.length"
                        text="正在同步工作台..."
                    />

                    <view v-else-if="recentOrderCards.length" class="order-list">
                        <view
                            v-for="item in recentOrderCards"
                            :key="item.id"
                            class="order-item"
                            @click="goOrderDetail(item.id)"
                        >
                            <view class="order-item__top">
                                <view class="order-item__copy">
                                    <text class="order-item__title">{{ item.title }}</text>
                                    <text class="order-item__subtitle">{{ item.subtitle }}</text>
                                </view>

                                <StatusBadge :tone="item.statusTone" size="sm">
                                    {{ item.statusLabel }}
                                </StatusBadge>
                            </view>

                            <view class="order-item__bottom">
                                <text class="order-item__sn">订单号 {{ item.orderSn }}</text>

                                <view class="order-item__meta">
                                    <StatusBadge
                                        v-if="item.pendingConfirmCount > 0"
                                        tone="info"
                                        size="sm"
                                    >
                                        待确认 {{ item.pendingConfirmCount }}
                                    </StatusBadge>

                                    <text v-if="item.amountText" class="order-item__amount">
                                        {{ item.amountText }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <EmptyState
                        v-else
                        title="最近还没有订单动态"
                        description="订单更新会显示在这里"
                    />
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="resource-panel">
                    <view class="section-head wm-section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title wm-section-title">内容与资料</text>
                            <text class="section-head__desc wm-section-desc"
                                >维护服务展示与履约资料</text
                            >
                        </view>

                        <text class="section-head__meta wm-helper-text">{{
                            resourceMetaText
                        }}</text>
                    </view>

                    <view class="resource-grid">
                        <view
                            v-for="item in resourceMenus"
                            :key="item.path"
                            class="resource-card"
                            @click="goPage(item.path)"
                        >
                            <view class="resource-card__top">
                                <view
                                    :class="[
                                        'resource-card__icon',
                                        `resource-card__icon--${item.tone}`
                                    ]"
                                >
                                    <tn-icon :name="item.icon" size="22" :color="item.iconColor" />
                                </view>

                                <view v-if="item.badge > 0" class="resource-card__badge">
                                    <text class="resource-card__badge-text">{{
                                        formatBadge(item.badge)
                                    }}</text>
                                </view>
                            </view>

                            <view class="resource-card__copy">
                                <text class="resource-card__title">{{ item.name }}</text>
                                <text class="resource-card__desc">{{ item.description }}</text>
                            </view>
                        </view>
                    </view>
                </BaseCard>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

import { onShow } from '@dcloudio/uni-app'

import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { staffCenterDashboard, staffCenterProfile } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'
type ResourceTone = 'primary' | 'warning' | 'info' | 'neutral'

interface DashboardProfile {
    name: string
    avatar: string
    status: number
    status_desc: string
    audit_status: number
    audit_status_desc: string
    mobile: string
    price_text: string
    has_price: boolean
    category_name: string
}

interface DashboardOverview {
    order_count: number
    work_count: number
    package_count: number
    addon_count: number
    schedule_count: number
}

interface DashboardTodo {
    pending_confirm_orders: number
    today_service_count: number
    upcoming_7d_schedule_count: number
    unread_message_count: number
    total: number
}

interface DashboardRecentOrder {
    id: number
    order_sn: string
    service_date: string
    contact_name: string
    contact_mobile: string
    service_address: string
    order_status: number
    order_status_desc: string
    pay_amount: number
    item_count: number
    package_names: string[]
    pending_confirm_count: number
}

interface DashboardState {
    profile: DashboardProfile
    overview: DashboardOverview
    todo: DashboardTodo
    recent_orders: DashboardRecentOrder[]
}

interface StaffProfileDetail {
    name?: string
    avatar?: string
    category_name?: string
    mobile?: string
    mobile_full?: string
    status?: number
    status_desc?: string
    audit_status?: number
    audit_status_desc?: string
    rating?: number | string
    experience_years?: number | string
    orderCount?: number
    price_text?: string
    has_price?: boolean
}

interface DisplayProfile {
    name: string
    avatar: string
    category_name: string
    mobile: string
    status: number
    status_desc: string
    audit_status: number
    audit_status_desc: string
    rating: number | string
    experience_years: number | string
    order_count: number
}

interface WorkbenchAction {
    key: string
    label: string
    value: number
    unit: string
    hint: string
    icon: string
    tone: BadgeTone
    iconColor: string
    action: () => void
}

interface OverviewMetric {
    label: string
    value: number
    unit: string
    caption: string
    accent: boolean
}

interface RecentOrderCardItem {
    id: number
    title: string
    subtitle: string
    orderSn: string
    statusLabel: string
    statusTone: BadgeTone
    pendingConfirmCount: number
    amountText: string
}

interface ResourceMenuItem {
    name: string
    description: string
    path: string
    badge: number
    icon: string
    iconColor: string
    tone: ResourceTone
}

const $theme = useThemeStore()

const defaultAvatar = '/static/images/user/default_avatar.png'
const heroCardBackground = 'linear-gradient(145deg, #111111 0%, #0B0B0B 58%, #2F2924 100%)'
const heroCardBorder = '1rpx solid rgba(255, 255, 255, 0.08)'
const heroCardShadow = '0 18rpx 42rpx rgba(11, 11, 11, 0.18)'

const loading = ref(false)
const hasLoaded = ref(false)
const profileDetail = ref<StaffProfileDetail>({})

const createEmptyDashboard = (): DashboardState => ({
    profile: {
        name: '',
        avatar: '',
        status: 0,
        status_desc: '',
        audit_status: 0,
        audit_status_desc: '',
        mobile: '',
        price_text: '',
        has_price: false,
        category_name: ''
    },
    overview: {
        order_count: 0,
        work_count: 0,
        package_count: 0,
        addon_count: 0,
        schedule_count: 0
    },
    todo: {
        pending_confirm_orders: 0,
        today_service_count: 0,
        upcoming_7d_schedule_count: 0,
        unread_message_count: 0,
        total: 0
    },
    recent_orders: []
})

const dashboard = ref<DashboardState>(createEmptyDashboard())

const toNumber = (value: unknown) => {
    const result = Number(value ?? 0)

    return Number.isFinite(result) ? result : 0
}

const resolveErrorMessage = (error: unknown) => {
    if (typeof error === 'string') return error

    if (error && typeof error === 'object') {
        const target = error as { msg?: string; message?: string }

        return target.msg || target.message || '加载失败'
    }

    return '加载失败'
}

const formatRating = (value: unknown) => {
    const rating = toNumber(value)

    if (rating <= 0) return ''

    return rating.toFixed(1)
}

const formatAmount = (value: unknown) => {
    const amount = toNumber(value)

    if (amount <= 0) return ''

    return `¥${amount.toFixed(2).replace(/\.00$/, '')}`
}

const formatCompactDate = (value: string) => {
    const text = String(value || '').trim()

    return text ? text.replace(/-/g, '.') : '待安排服务日期'
}

const buildOrderTitle = (order: DashboardRecentOrder) => {
    const packageNames = Array.isArray(order.package_names)
        ? order.package_names.filter(Boolean)
        : []

    if (packageNames.length > 1) {
        return `${packageNames[0]} 等${packageNames.length}项`
    }

    if (packageNames.length === 1) {
        return packageNames[0]
    }

    return `共 ${toNumber(order.item_count)} 个服务项`
}

const buildOrderSubtitle = (order: DashboardRecentOrder) => {
    const parts = [formatCompactDate(order.service_date)]

    const detailText =
        String(order.service_address || '').trim() ||
        String(order.contact_name || '').trim() ||
        buildOrderTitle(order)

    if (detailText) {
        parts.push(detailText)
    }

    return parts.join(' · ')
}

const getAuditTone = (status: number): BadgeTone => {
    if (status === 1) return 'success'
    if (status === 2) return 'danger'

    return 'warning'
}

const getProfileStatusTone = (status: number): BadgeTone => {
    if (status === 1) return 'success'
    if (status === 0) return 'warning'

    return 'neutral'
}

const getOrderStatusTone = (status: number): BadgeTone => {
    const map: Record<number, BadgeTone> = {
        0: 'warning',
        1: 'info',
        2: 'success',
        3: 'neutral',
        4: 'neutral',
        5: 'warning',
        6: 'danger',
        7: 'warning',
        8: 'danger',
        10: 'warning'
    }

    return map[status] || 'neutral'
}

const displayProfile = computed<DisplayProfile>(() => {
    const summary = dashboard.value.profile
    const detail = profileDetail.value

    return {
        name: detail.name || summary.name || '',
        avatar: detail.avatar || summary.avatar || '',
        category_name: detail.category_name || summary.category_name || '',
        mobile: String(detail.mobile_full || detail.mobile || summary.mobile || ''),
        status: detail.status ?? summary.status ?? 0,
        status_desc: detail.status_desc || summary.status_desc || '',
        audit_status: detail.audit_status ?? summary.audit_status ?? 0,
        audit_status_desc: detail.audit_status_desc || summary.audit_status_desc || '',
        rating: detail.rating ?? 0,
        experience_years: detail.experience_years ?? 0,
        order_count: detail.orderCount ?? dashboard.value.overview.order_count
    }
})

const profileName = computed(() => displayProfile.value.name || '未填写姓名')

const profileMetaText = computed(() => {
    const parts = []
    const rating = formatRating(displayProfile.value.rating)
    const orderCount = toNumber(displayProfile.value.order_count)
    const years = toNumber(displayProfile.value.experience_years)

    if (displayProfile.value.category_name) {
        parts.push(displayProfile.value.category_name)
    }

    if (rating) {
        parts.push(`评分 ${rating}`)
    }

    parts.push(`已接单 ${orderCount} 笔`)

    if (years > 0) {
        parts.push(`${years} 年经验`)
    }

    return parts.join(' · ') || '完善资料'
})

const profileMetaLine = computed(() => profileMetaText.value)

const auditBadge = computed(() => ({
    text: String(displayProfile.value.audit_status_desc || '').trim(),
    tone: getAuditTone(displayProfile.value.audit_status)
}))

const profileStatusBadge = computed(() => ({
    text: displayProfile.value.status_desc || '服务状态',
    tone: getProfileStatusTone(displayProfile.value.status)
}))

const heroHeadline = computed(() => {
    const pending = toNumber(dashboard.value.todo.pending_confirm_orders)
    const today = toNumber(dashboard.value.todo.today_service_count)
    const upcoming = toNumber(dashboard.value.todo.upcoming_7d_schedule_count)

    if (pending > 0) {
        return `优先处理 ${pending} 笔待确认订单`
    }

    if (today > 0) {
        return `今日有 ${today} 项服务待履约`
    }

    if (upcoming > 0) {
        return `未来 7 日已有 ${upcoming} 场安排`
    }

    return '先看订单，再确认档期安排'
})

const focusHighlights = computed(() => {
    const todayServiceCount = toNumber(dashboard.value.todo.today_service_count)
    const upcomingScheduleCount = toNumber(dashboard.value.todo.upcoming_7d_schedule_count)
    const unreadMessageCount = toNumber(dashboard.value.todo.unread_message_count)

    return [
        {
            key: 'today-service',
            label: '今日服务',
            value: todayServiceCount,
            unit: '项',
            hint: todayServiceCount > 0 ? '优先履约' : '暂无排期',
            active: todayServiceCount > 0
        },
        {
            key: 'upcoming-schedule',
            label: '7日安排',
            value: upcomingScheduleCount,
            unit: '场',
            hint: upcomingScheduleCount > 0 ? '提前确认' : '排期宽松',
            active: upcomingScheduleCount > 0
        },
        {
            key: 'unread-message',
            label: '待回消息',
            value: unreadMessageCount,
            unit: '条',
            hint: unreadMessageCount > 0 ? '尽快回复' : '沟通顺畅',
            active: unreadMessageCount > 0
        }
    ]
})

const primaryAction = computed<WorkbenchAction>(() => {
    const pending = toNumber(dashboard.value.todo.pending_confirm_orders)

    return {
        key: 'pending-confirm',
        label: '待确认订单',
        value: pending,
        unit: '笔',
        hint: pending > 0 ? '优先处理' : '当前无积压',
        icon: 'warning',
        tone: 'info',
        iconColor: '#111111',
        action: () => goOrders(0)
    }
})

const secondaryActions = computed<WorkbenchAction[]>(() => {
    const totalOrders = toNumber(dashboard.value.overview.order_count)
    const todayServiceCount = toNumber(dashboard.value.todo.today_service_count)
    const upcomingScheduleCount = toNumber(dashboard.value.todo.upcoming_7d_schedule_count)

    return [
        {
            key: 'order-list',
            label: '订单跟进',
            value: totalOrders,
            unit: '单',
            hint: totalOrders > 0 ? '查看全部订单' : '开始跟进',
            icon: 'order',
            tone: 'neutral',
            iconColor: '#9A9388',
            action: () => goOrders()
        },
        {
            key: 'schedule',
            label: '档期管理',
            value: upcomingScheduleCount,
            unit: '场',
            hint: todayServiceCount > 0 ? `今日服务 ${todayServiceCount} 项` : '查看未来安排',
            icon: 'calendar',
            tone: 'warning',
            iconColor: '#C8A45D',
            action: () => goPage('/packages/pages/staff_schedule/staff_schedule')
        }
    ]
})

const overviewMetrics = computed<OverviewMetric[]>(() => [
    {
        label: '今日服务',
        value: toNumber(dashboard.value.todo.today_service_count),
        unit: '项',
        caption: '当日履约',
        accent: toNumber(dashboard.value.todo.today_service_count) > 0
    },
    {
        label: '7日安排',
        value: toNumber(dashboard.value.todo.upcoming_7d_schedule_count),
        unit: '场',
        caption: '提前确认',
        accent: false
    },
    {
        label: '总订单',
        value: toNumber(dashboard.value.overview.order_count),
        unit: '单',
        caption: '累计跟进',
        accent: false
    },
    {
        label: '档期条目',
        value: toNumber(dashboard.value.overview.schedule_count),
        unit: '条',
        caption: '工作日历',
        accent: false
    }
])

const overviewMetaText = computed(() => {
    const total =
        toNumber(dashboard.value.todo.pending_confirm_orders) +
        toNumber(dashboard.value.todo.today_service_count)

    return total > 0 ? `当前 ${total} 项重点` : '今天较为平稳'
})

const recentOrderCards = computed<RecentOrderCardItem[]>(() =>
    dashboard.value.recent_orders.slice(0, 3).map((order) => ({
        id: order.id,
        title: buildOrderTitle(order),
        subtitle: buildOrderSubtitle(order),
        orderSn: order.order_sn,
        statusLabel: order.order_status_desc || '处理中',
        statusTone: getOrderStatusTone(toNumber(order.order_status)),
        pendingConfirmCount: toNumber(order.pending_confirm_count),
        amountText: formatAmount(order.pay_amount)
    }))
)

const resourceMenus = computed<ResourceMenuItem[]>(() => [
    {
        name: '资料编辑',
        description: '维护个人资料',
        path: '/packages/pages/staff_profile/staff_profile',
        badge: 0,
        icon: 'edit',
        iconColor: '#0B0B0B',
        tone: 'primary'
    },
    {
        name: '作品管理',
        description: '更新案例内容',
        path: '/packages/pages/staff_work_list/staff_work_list',
        badge: toNumber(dashboard.value.overview.work_count),
        icon: 'image',
        iconColor: '#6C665C',
        tone: 'info'
    },
    {
        name: '套餐管理',
        description: '维护服务套餐',
        path: '/packages/pages/staff_package_list/staff_package_list',
        badge: toNumber(dashboard.value.overview.package_count),
        icon: 'service',
        iconColor: '#C8A45D',
        tone: 'warning'
    },
    {
        name: '附加项管理',
        description: '管理附加服务',
        path: '/packages/pages/staff_addon_list/staff_addon_list',
        badge: toNumber(dashboard.value.overview.addon_count),
        icon: 'plus',
        iconColor: '#C8A45D',
        tone: 'warning'
    },
    {
        name: '动态管理',
        description: '查看审核反馈',
        path: '/packages/pages/staff_dynamic_list/staff_dynamic_list',
        badge: 0,
        icon: 'topic',
        iconColor: '#9A9388',
        tone: 'neutral'
    },
    {
        name: '证书管理',
        description: '维护资质证书',
        path: '/packages/pages/staff_certificate_list/staff_certificate_list',
        badge: 0,
        icon: 'honor',
        iconColor: '#9A9388',
        tone: 'neutral'
    }
])

const resourceMetaText = computed(() => {
    const parts = []
    const workCount = toNumber(dashboard.value.overview.work_count)
    const packageCount = toNumber(dashboard.value.overview.package_count)
    const addonCount = toNumber(dashboard.value.overview.addon_count)

    parts.push(`作品 ${workCount}`)
    parts.push(`套餐 ${packageCount}`)
    parts.push(`附加项 ${addonCount}`)

    return parts.join(' · ')
})

const loadPageData = async () => {
    loading.value = true

    const [dashboardResult, profileResult] = await Promise.all([
        staffCenterDashboard()
            .then((data) => ({ data, error: '' }))
            .catch((error) => ({ data: null, error: resolveErrorMessage(error) })),
        staffCenterProfile()
            .then((data) => ({ data, error: '' }))
            .catch((error) => ({ data: null, error: resolveErrorMessage(error) }))
    ])

    if (dashboardResult.data) {
        const data = dashboardResult.data
        const emptyState = createEmptyDashboard()

        dashboard.value = {
            profile: {
                ...emptyState.profile,
                ...(data?.profile || {})
            },
            overview: {
                ...emptyState.overview,
                ...(data?.overview || {})
            },
            todo: {
                ...emptyState.todo,
                ...(data?.todo || {})
            },
            recent_orders: Array.isArray(data?.recent_orders) ? data.recent_orders : []
        }
    }

    if (profileResult.data && typeof profileResult.data === 'object') {
        profileDetail.value = profileResult.data as StaffProfileDetail
    }

    const errorMessage = dashboardResult.error || profileResult.error

    if (errorMessage) {
        uni.showToast({ title: errorMessage, icon: 'none' })
    }

    hasLoaded.value = true
    loading.value = false
}

const goPage = (path: string) => {
    uni.navigateTo({ url: path })
}

const goOrders = (status?: number) => {
    const query = status === undefined ? '' : `?status=${status}`

    uni.navigateTo({ url: `/packages/pages/staff_order_list/staff_order_list${query}` })
}

const goOrderDetail = (id: number) => {
    uni.navigateTo({ url: `/packages/pages/staff_order_detail/staff_order_detail?id=${id}` })
}

const formatBadge = (value: number) => {
    if (value > 99) return '99+'

    return String(value)
}

onShow(async () => {
    $theme.setScene('staff')

    if (!(await ensureStaffCenterAccess())) return

    await loadPageData()
})
</script>

<style lang="scss" scoped>
.staff-center-page {
    width: 100%;
    padding: 12rpx 0 calc(32rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;

    &__content {
        display: flex;
        flex-direction: column;
        gap: 20rpx;
    }
}

.workbench-hero,
.overview-panel,
.order-panel,
.resource-panel {
    position: relative;
    overflow: hidden;
}

.workbench-hero {
    background: linear-gradient(145deg, #111111 0%, #0B0B0B 58%, #2F2924 100%);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 18rpx 42rpx rgba(11, 11, 11, 0.18);

    &::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(
            circle at top right,
            rgba(200, 164, 93, 0.2),
            transparent 42%
        );
        pointer-events: none;
    }

    &__top,
    &__head,
    &__profile,
    &__badges {
        display: flex;
        align-items: center;
    }

    &__top {
        justify-content: space-between;
        gap: 16rpx;
    }

    &__badges {
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10rpx;
    }

    &__badge {
        flex-shrink: 0;
    }

    &__head {
        justify-content: space-between;
        gap: 20rpx;
        margin-top: 20rpx;
    }

    &__profile {
        flex: 1;
        min-width: 0;
        gap: 20rpx;
    }

    &__avatar {
        width: 104rpx;
        height: 104rpx;
        flex-shrink: 0;
        border-radius: 999rpx;
        border: 2rpx solid rgba(255, 255, 255, 0.24);
        background: rgba(255, 255, 255, 0.1);
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__title {
        font-size: 40rpx;
        font-weight: 700;
        line-height: 1.2;
        color: #FFFFFF;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__subtitle {
        font-size: 22rpx;
        font-weight: 600;
        line-height: 1.45;
        color: rgba(255, 255, 255, 0.76);
    }

    &__headline {
        margin-top: 18rpx;
        font-size: 28rpx;
        font-weight: 700;
        line-height: 1.4;
        color: #FFFFFF;
    }
}

.workbench-focus-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
    margin-top: 18rpx;
}

.focus-pill {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    min-height: 126rpx;
    padding: 18rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    background: rgba(255, 255, 255, 0.08);
    border: 1rpx solid rgba(255, 255, 255, 0.08);

    &--active {
        background: rgba(247, 240, 223, 0.14);
        border-color: rgba(216, 194, 138, 0.22);
    }

    &__label {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.35;
        color: rgba(255, 255, 255, 0.72);
    }

    &__value-row {
        display: flex;
        align-items: flex-end;
        gap: 6rpx;
    }

    &__value {
        font-size: 34rpx;
        font-weight: 700;
        line-height: 1;
        color: #FFFFFF;
    }

    &__unit {
        padding-bottom: 4rpx;
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1;
        color: rgba(255, 255, 255, 0.72);
    }

    &__hint {
        font-size: 18rpx;
        font-weight: 600;
        line-height: 1.45;
        color: rgba(255, 255, 255, 0.62);
    }
}

.hero-eyebrow,
.profile-entry,
.section-link,
.primary-action,
.secondary-action,
.metric-card,
.order-item,
.resource-card {
    box-sizing: border-box;
}

.hero-eyebrow {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42rpx;
    padding: 0 18rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.12);
    border: 1rpx solid rgba(255, 255, 255, 0.12);

    &__text {
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1;
        color: #FFFFFF;
        letter-spacing: 0;
    }
}

.profile-entry {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    min-height: 56rpx;
    padding: 0 18rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.08);
    border: 1rpx solid rgba(255, 255, 255, 0.12);

    &__text {
        font-size: 22rpx;
        font-weight: 600;
        color: #FFFFFF;
        line-height: 1;
    }
}

.primary-action {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-top: 22rpx;
    padding: 24rpx 26rpx;
    border-radius: var(--wm-radius-card, 16rpx);
    background: linear-gradient(135deg, #F8F2E4 0%, #D8C28A 100%);
    border: 1rpx solid rgba(200, 164, 93, 0.42);
    box-shadow: 0 10rpx 22rpx rgba(11, 11, 11, 0.1);

    &__main {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 16rpx;
    }

    &__icon {
        width: 64rpx;
        height: 64rpx;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--wm-radius-card-soft, 14rpx);
        background: rgba(17, 17, 17, 0.12);
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__label {
        font-size: 28rpx;
        font-weight: 700;
        line-height: 1.35;
        color: #111111;
    }

    &__hint {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.45;
        color: rgba(17, 17, 17, 0.68);
    }

    &__value {
        flex-shrink: 0;
        display: flex;
        align-items: flex-end;
        gap: 8rpx;
    }

    &__number {
        font-size: 48rpx;
        font-weight: 700;
        line-height: 1;
        color: #111111;
    }

    &__unit {
        padding-bottom: 5rpx;
        font-size: 22rpx;
        font-weight: 700;
        line-height: 1;
        color: rgba(17, 17, 17, 0.7);
    }
}

.secondary-action-grid,
.metric-grid,
.resource-grid {
    display: grid;
    gap: 12rpx;
}

.secondary-action-grid,
.metric-grid,
.resource-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.secondary-action-grid {
    margin-top: 12rpx;
}

.secondary-action {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    min-height: 168rpx;
    padding: 22rpx;
    border-radius: var(--wm-radius-card, 16rpx);
    border: 1rpx solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.96);

    &--warning {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, #FFFFFF 100%);
    }

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10rpx;
    }

    &__icon {
        width: 52rpx;
        height: 52rpx;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 18rpx;
        background: #F8F7F2;

        &--neutral {
            background: #F8F7F2;
        }

        &--warning {
            background: #F7F0DF;
        }
    }

    &__label {
        font-size: 26rpx;
        font-weight: 700;
        line-height: 1.35;
        color: #0B0B0B;
    }

    &__value-row {
        display: flex;
        align-items: flex-end;
        gap: 6rpx;
    }

    &__value {
        font-size: 42rpx;
        font-weight: 700;
        line-height: 1;
        color: #0B0B0B;
    }

    &__unit {
        padding-bottom: 5rpx;
        font-size: 21rpx;
        font-weight: 700;
        color: #5f5a50;
        line-height: 1;
    }

    &__hint {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.45;
        color: #5f5a50;
    }
}

.overview-panel,
.order-panel,
.resource-panel {
    background: rgba(255, 255, 255, 0.94);
}

.section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__meta {
        flex-shrink: 0;
        padding-top: 4rpx;
    }
}

.metric-grid {
    margin-top: 16rpx;
}

.metric-card {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    min-height: 156rpx;
    padding: 22rpx;
    border-radius: var(--wm-radius-card, 16rpx);
        border: 1rpx solid var(--wm-color-border, #e2ded5);
    background: #ffffff;

    &--accent {
        background: var(--wm-color-bg-soft, #f6f5f2);
        border-color: #d8c28a;
    }

    &__label {
        font-size: 22rpx;
        font-weight: 700;
        line-height: 1.35;
        color: #9A9388;
    }

    &__value-row {
        display: flex;
        align-items: flex-end;
        gap: 8rpx;
    }

    &__value {
        font-size: 44rpx;
        font-weight: 700;
        line-height: 1;
        color: #111111;
    }

    &__unit {
        padding-bottom: 5rpx;
        font-size: 21rpx;
        font-weight: 700;
        line-height: 1;
        color: #5f5a50;
    }

    &__caption {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.45;
        color: #5f5a50;
    }
}

.section-link {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    min-height: 42rpx;

    &__text {
        font-size: 22rpx;
        font-weight: 600;
        line-height: 1;
        color: #5f5a50;
    }
}

.order-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-top: 16rpx;
}

.order-item {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 22rpx;
    border-radius: var(--wm-radius-card, 16rpx);
    border: 1rpx solid var(--wm-color-border, #e2ded5);
    background: #ffffff;

    &__top,
    &__bottom,
    &__meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
    }

    &__top {
        align-items: flex-start;
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: 27rpx;
        font-weight: 700;
        line-height: 1.35;
        color: #111111;
    }

    &__subtitle {
        font-size: 21rpx;
        font-weight: 600;
        line-height: 1.45;
        color: #5f5a50;
    }

    &__sn,
    &__amount {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.35;
    }

    &__sn {
        color: #9a9388;
    }

    &__amount {
        color: #0b0b0b;
    }
}

.resource-grid {
    margin-top: 16rpx;
}

.resource-card {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    min-height: 182rpx;
    padding: 22rpx;
    border-radius: var(--wm-radius-card, 16rpx);
    border: 1rpx solid var(--wm-color-border, #e2ded5);
    background: #ffffff;

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10rpx;
    }

    &__icon {
        width: 58rpx;
        height: 58rpx;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--wm-radius-card-soft, 14rpx);

        &--primary {
            background: #f3f2ee;
        }

        &--warning {
            background: var(--wm-color-secondary-soft, #f8f2e4);
        }

        &--info {
            background: var(--wm-color-primary-soft, #f2f1ec);
        }

        &--neutral {
            background: var(--wm-color-bg-soft, #f6f5f2);
        }
    }

    &__badge {
        min-width: 42rpx;
        height: 42rpx;
        padding: 0 12rpx;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999rpx;
        background: rgba(11, 11, 11, 0.08);
        border: 1rpx solid rgba(11, 11, 11, 0.14);
    }

    &__badge-text {
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1;
        color: #0b0b0b;
    }

    &__copy {
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: 26rpx;
        font-weight: 700;
        line-height: 1.35;
        color: #111111;
    }

    &__desc {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.45;
        color: #5f5a50;
    }
}
</style>
