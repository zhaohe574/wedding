<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="staff" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="服务人员中心"
            title-align="center"
            variant="solid"
            bg-color="#181614"
            text-color="#FFFDF8"
        />

        <view class="staff-center-page">
            <view class="staff-center-page__content wm-page-content">
                <OaNoticeCard v-if="!isOaBound" />
                <view v-if="loadError" class="workspace-error" @click="loadPageData">{{ loadError }} · 点击重试</view>

                <!-- 1. 主理人工作室专属名片 Hero Card -->
                <view class="staff-hero">
                    <view class="staff-hero__ambient" />
                    <view class="staff-hero__header">
                        <view class="staff-hero__badges">
                            <view class="hero-tag hero-tag--role">
                                <BaseIcon name="honor" size="18" color="#D9BE82" />
                                <text>服务人员</text>
                            </view>
                            <StatusBadge
                                :tone="profileStatusBadge.tone"
                                size="sm"
                                class="staff-hero__badge"
                            >
                                {{ profileStatusBadge.text }}
                            </StatusBadge>
                            <StatusBadge
                                v-if="auditBadge.text"
                                :tone="auditBadge.tone"
                                size="sm"
                                class="staff-hero__badge"
                            >
                                {{ auditBadge.text }}
                            </StatusBadge>
                        </view>

                        <view
                            class="staff-hero__edit-btn"
                            @click="goPage('/packages/pages/staff_profile/staff_profile')"
                        >
                            <text class="staff-hero__edit-text">编辑资料</text>
                            <BaseIcon name="right" size="18" color="#D9BE82" />
                        </view>
                    </view>

                    <view class="staff-hero__profile-row">
                        <view class="staff-hero__avatar-box">
                            <image
                                class="staff-hero__avatar"
                                :src="displayProfile.avatar || defaultAvatar"
                                mode="aspectFill"
                            />
                            <view v-if="displayProfile.category_name" class="staff-hero__avatar-tag">
                                {{ displayProfile.category_name }}
                            </view>
                        </view>

                        <view class="staff-hero__copy">
                            <view class="staff-hero__name-row">
                                <text class="staff-hero__name">{{ profileName }}</text>
                                <view v-if="displayProfile.rating" class="rating-pill">
                                    <text class="rating-pill__star">★</text>
                                    <text class="rating-pill__num">{{ formatRating(displayProfile.rating) }}</text>
                                </view>
                            </view>
                            <text class="staff-hero__meta">{{ profileMetaLine }}</text>
                        </view>
                    </view>

                    <!-- 实时脉搏三大指标 -->
                    <view class="staff-hero__pulse-grid">
                        <view
                            v-for="item in focusHighlights"
                            :key="item.key"
                            :class="['pulse-tile', { 'pulse-tile--active': item.active }]"
                            @click="handlePulseClick(item.key)"
                        >
                            <text class="pulse-tile__label">{{ item.label }}</text>
                            <view class="pulse-tile__val-row">
                                <text class="pulse-tile__val">{{ item.value }}</text>
                                <text class="pulse-tile__unit">{{ item.unit }}</text>
                            </view>
                        </view>
                    </view>
                </view>

                <!-- 2. 待办提醒行动条 (有待确认订单或今日服务时高光突出) -->
                <view
                    v-if="toNumber(dashboard.todo.pending_confirm_orders) > 0"
                    class="urgent-task-card"
                    @click="primaryAction.action()"
                >
                    <view class="urgent-task-card__left">
                        <view class="urgent-task-card__icon-wrap">
                            <BaseIcon name="warning" size="26" color="#855B1B" />
                        </view>
                        <view class="urgent-task-card__text">
                            <text class="urgent-task-card__title">有 {{ dashboard.todo.pending_confirm_orders }} 笔订单待确认接单</text>
                            <text class="urgent-task-card__sub">及时确认可锁定档期并通知客户</text>
                        </view>
                    </view>
                    <view class="urgent-task-card__btn">
                        <text>去处理</text>
                        <BaseIcon name="right" size="20" color="#FFFFFF" />
                    </view>
                </view>

                <!-- 3. 高频快捷入口面板 -->
                <BaseCard variant="panel" scene="staff" class="quick-panel" padding="22rpx 20rpx">
                    <view class="quick-actions">
                        <view
                            v-for="item in quickActions"
                            :key="item.label"
                            class="quick-action"
                            :class="{ 'quick-action--primary': item.primary }"
                            @click="goPage(item.path)"
                        >
                            <view class="quick-action__icon-box">
                                <BaseIcon :name="item.icon" size="32" :color="item.primary ? '#B8954A' : '#4E483F'" />
                                <text v-if="item.primary" class="quick-action__tag">快速</text>
                            </view>
                            <text class="quick-action__label">{{ item.label }}</text>
                        </view>
                    </view>
                </BaseCard>

                <!-- 4. 业务数据概览看板 -->
                <BaseCard variant="panel" scene="staff" class="overview-panel" padding="26rpx">
                    <view class="section-head">
                        <view class="section-head__left">
                            <text class="section-head__title">业务概览</text>
                            <text class="section-head__desc">本月服务与经营指标</text>
                        </view>
                        <text class="section-head__meta">{{ resourceMetaText }}</text>
                    </view>

                    <view class="metric-grid">
                        <view
                            v-for="item in overviewMetrics"
                            :key="item.label"
                            :class="['metric-card', { 'metric-card--accent': item.accent, 'metric-card--clickable': !!item.action }]"
                            @click="item.action?.()"
                        >
                            <view class="metric-card__header">
                                <text class="metric-card__label">{{ item.label }}</text>
                                <text v-if="item.action" class="metric-card__arrow">›</text>
                            </view>
                            <view class="metric-card__value-row">
                                <text class="metric-card__value">{{ item.value }}</text>
                                <text class="metric-card__unit">{{ item.unit }}</text>
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <!-- 5. 近期订单流 -->
                <BaseCard variant="panel" scene="staff" class="order-panel" padding="26rpx">
                    <view class="section-head">
                        <view class="section-head__left">
                            <text class="section-head__title">近期订单</text>
                            <text class="section-head__desc">最新待服务及履约动态</text>
                        </view>
                        <view class="section-link" @click="goOrders()">
                            <text class="section-link__text">全部订单</text>
                            <BaseIcon name="right" size="18" color="#B8954A" />
                        </view>
                    </view>

                    <LoadingState
                        v-if="loading && !hasLoaded && !recentOrderCards.length"
                        text="同步订单中"
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
                                <text class="order-item__sn">单号 {{ item.orderSn }}</text>

                                <view class="order-item__meta">
                                    <StatusBadge
                                        v-if="item.pendingConfirmCount > 0"
                                        tone="warning"
                                        size="sm"
                                    >
                                        待确认 {{ item.pendingConfirmCount }}
                                    </StatusBadge>

                                    <text v-if="item.amountText" class="order-item__amount">
                                        {{ item.amountText }}
                                    </text>
                                    <BaseIcon name="right" size="16" color="#C4BCB0" />
                                </view>
                            </view>
                        </view>
                    </view>

                    <EmptyState v-else title="暂无待履约订单" />
                </BaseCard>

                <!-- 6. 主理人业务管理矩阵 -->
                <BaseCard variant="panel" scene="staff" class="resource-panel" padding="26rpx">
                    <view class="section-head">
                        <view class="section-head__left">
                            <text class="section-head__title">履约管理</text>
                            <text class="section-head__desc">排期排班、收益与合同</text>
                        </view>
                    </view>

                    <view class="resource-grid">
                        <view
                            v-for="item in businessTools"
                            :key="item.path"
                            class="resource-card"
                            @click="goPage(item.path)"
                        >
                            <view class="resource-card__top">
                                <view class="resource-card__icon">
                                    <BaseIcon :name="item.icon" size="26" color="#C8A45D" />
                                </view>
                                <view v-if="item.badge > 0" class="resource-card__badge">
                                    <text class="resource-card__badge-text">{{ formatBadge(item.badge) }}</text>
                                </view>
                            </view>
                            <text class="resource-card__title">{{ item.name }}</text>
                            <text class="resource-card__sub">{{ item.sub }}</text>
                        </view>
                    </view>
                </BaseCard>

                <!-- 7. 主理人品牌资产矩阵 -->
                <BaseCard variant="panel" scene="staff" class="resource-panel" padding="26rpx">
                    <view class="section-head">
                        <view class="section-head__left">
                            <text class="section-head__title">品牌资产</text>
                            <text class="section-head__desc">作品、套餐、附加项与资质</text>
                        </view>
                    </view>

                    <view class="resource-grid">
                        <view
                            v-for="item in assetTools"
                            :key="item.path"
                            class="resource-card"
                            @click="goPage(item.path)"
                        >
                            <view class="resource-card__top">
                                <view class="resource-card__icon resource-card__icon--brand">
                                    <BaseIcon :name="item.icon" size="26" color="#8A7758" />
                                </view>
                                <view v-if="item.badge > 0" class="resource-card__badge">
                                    <text class="resource-card__badge-text">{{ formatBadge(item.badge) }}</text>
                                </view>
                            </view>
                            <text class="resource-card__title">{{ item.name }}</text>
                            <text class="resource-card__sub">{{ item.sub }}</text>
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

import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import OaNoticeCard from '@/components/base/OaNoticeCard.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { staffCenterDashboard } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { useThemeStore } from '@/stores/theme'
import { showError } from '@/utils/feedback'
import { useOaBound } from '@/utils/oa-status'

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

const { isOaBound, checkOaBoundStatus } = useOaBound()

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
    icon: string
    tone: BadgeTone
    iconColor: string
    action: () => void
}

interface OverviewMetric {
    label: string
    value: number
    unit: string
    accent: boolean
    action?: () => void
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
    sub?: string
    path: string
    badge: number
    icon: string
    iconColor: string
    tone: ResourceTone
}

const $theme = useThemeStore()

const defaultAvatar = '/static/images/user/default_avatar.png'
const heroCardBackground = 'linear-gradient(145deg, #1A1815 0%, #100F0D 60%, #26211B 100%)'
const heroCardBorder = '1rpx solid rgba(217, 190, 130, 0.35)'
const heroCardShadow = '0 20rpx 50rpx rgba(18, 16, 14, 0.16)'

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

    parts.push(`接单 ${orderCount} 笔`)

    if (years > 0) {
        parts.push(`${years} 年经验`)
    }

    return parts.join(' · ') || '完善主理人资料'
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

const focusHighlights = computed(() => {
    const todayServiceCount = toNumber(dashboard.value.todo.today_service_count)
    const upcomingScheduleCount = toNumber(dashboard.value.todo.upcoming_7d_schedule_count)
    const pendingOrdersCount = toNumber(dashboard.value.todo.pending_confirm_orders)

    return [
        {
            key: 'today-service',
            label: '今日服务',
            value: todayServiceCount,
            unit: '项',
            active: todayServiceCount > 0
        },
        {
            key: 'pending-confirm',
            label: '待确认单',
            value: pendingOrdersCount,
            unit: '笔',
            active: pendingOrdersCount > 0
        },
        {
            key: 'upcoming-schedule',
            label: '7日安排',
            value: upcomingScheduleCount,
            unit: '场',
            active: upcomingScheduleCount > 0
        }
    ]
})

const handlePulseClick = (key: string) => {
    if (key === 'today-service' || key === 'upcoming-schedule') {
        goPage('/packages/pages/staff_schedule/staff_schedule')
    } else if (key === 'pending-confirm') {
        goOrders(0)
    }
}

const primaryAction = computed<WorkbenchAction>(() => {
    const pending = toNumber(dashboard.value.todo.pending_confirm_orders)

    return {
        key: 'pending-confirm',
        label: '待确认订单',
        value: pending,
        unit: '笔',
        icon: 'warning',
        tone: 'info',
        iconColor: '#111111',
        action: () => goOrders(0)
    }
})

const secondaryActions = computed<WorkbenchAction[]>(() => {
    const totalOrders = toNumber(dashboard.value.overview.order_count)
    const upcomingScheduleCount = toNumber(dashboard.value.todo.upcoming_7d_schedule_count)

    return [
        {
            key: 'order-list',
            label: '订单跟进',
            value: totalOrders,
            unit: '单',
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
        accent: toNumber(dashboard.value.todo.today_service_count) > 0,
        action: () => goPage('/packages/pages/staff_schedule/staff_schedule')
    },
    {
        label: '7日安排',
        value: toNumber(dashboard.value.todo.upcoming_7d_schedule_count),
        unit: '场',
        accent: false,
        action: () => goPage('/packages/pages/staff_schedule/staff_schedule')
    },
    {
        label: '累计总单',
        value: toNumber(dashboard.value.overview.order_count),
        unit: '单',
        accent: false,
        action: () => goOrders()
    },
    {
        label: '档期条目',
        value: toNumber(dashboard.value.overview.schedule_count),
        unit: '条',
        accent: false,
        action: () => goPage('/packages/pages/staff_schedule/staff_schedule')
    }
])

const overviewMetaText = computed(() => {
    const total =
        toNumber(dashboard.value.todo.pending_confirm_orders) +
        toNumber(dashboard.value.todo.today_service_count)

    return total > 0 ? `当前 ${total} 项重点` : '暂无加急事项'
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

// 业务履约功能矩阵
const businessTools = computed<ResourceMenuItem[]>(() => [
    {
        name: '订单管理',
        sub: '跟进服务订单',
        path: '/packages/pages/staff_order_list/staff_order_list',
        badge: toNumber(dashboard.value.overview.order_count),
        icon: 'order',
        iconColor: '#C8A45D',
        tone: 'warning'
    },
    {
        name: '档期日历',
        sub: '锁档与排期',
        path: '/packages/pages/staff_schedule/staff_schedule',
        badge: toNumber(dashboard.value.overview.schedule_count),
        icon: 'calendar',
        iconColor: '#C8A45D',
        tone: 'warning'
    },
    {
        name: '我的结算',
        sub: '收益与分账',
        path: '/packages/pages/staff_settlement/staff_settlement',
        badge: 0,
        icon: 'wallet',
        iconColor: '#C8A45D',
        tone: 'warning'
    },
    {
        name: '手动录单',
        sub: '线下快速建单',
        path: '/packages/pages/staff_order_create/staff_order_create',
        badge: 0,
        icon: 'edit',
        iconColor: '#C8A45D',
        tone: 'primary'
    },
    {
        name: '档期确认函',
        sub: '定制档期海报',
        path: '/packages/pages/staff_schedule_confirm_letter/staff_schedule_confirm_letter',
        badge: 0,
        icon: 'service',
        iconColor: '#C8A45D',
        tone: 'warning'
    }
])

// 品牌资产功能矩阵
const assetTools = computed<ResourceMenuItem[]>(() => [
    {
        name: '作品管理',
        sub: '展示主理人案例',
        path: '/packages/pages/staff_work_list/staff_work_list',
        badge: toNumber(dashboard.value.overview.work_count),
        icon: 'image',
        iconColor: '#8A7758',
        tone: 'info'
    },
    {
        name: '套餐管理',
        sub: '服务内容与定价',
        path: '/packages/pages/staff_package_list/staff_package_list',
        badge: toNumber(dashboard.value.overview.package_count),
        icon: 'service',
        iconColor: '#8A7758',
        tone: 'warning'
    },
    {
        name: '附加项管理',
        sub: '增值增项配置',
        path: '/packages/pages/staff_addon_list/staff_addon_list',
        badge: toNumber(dashboard.value.overview.addon_count),
        icon: 'plus',
        iconColor: '#8A7758',
        tone: 'warning'
    },
    {
        name: '资质证书',
        sub: '荣誉背书与资质',
        path: '/packages/pages/staff_certificate_list/staff_certificate_list',
        badge: 0,
        icon: 'honor',
        iconColor: '#8A7758',
        tone: 'neutral'
    },
    {
        name: '主理人动态',
        sub: '发布社交日常',
        path: '/packages/pages/staff_dynamic_list/staff_dynamic_list',
        badge: 0,
        icon: 'topic',
        iconColor: '#8A7758',
        tone: 'neutral'
    },
    {
        name: '个人资料',
        sub: '基本信息设置',
        path: '/packages/pages/staff_profile/staff_profile',
        badge: 0,
        icon: 'edit',
        iconColor: '#8A7758',
        tone: 'primary'
    }
])

// 兼容老调用
const resourceMenus = computed<ResourceMenuItem[]>(() => [
    ...businessTools.value,
    ...assetTools.value
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

const loadError = ref('')
const quickActions = [
    { label: '手动录单', icon: 'edit', primary: true, path: '/packages/pages/staff_order_create/staff_order_create' },
    { label: '订单管理', icon: 'order', primary: false, path: '/packages/pages/staff_order_list/staff_order_list' },
    { label: '档期日历', icon: 'calendar', primary: false, path: '/packages/pages/staff_schedule/staff_schedule' },
    { label: '我的结算', icon: 'money', primary: false, path: '/packages/pages/staff_settlement/staff_settlement' }
]

const loadPageData = async () => {
    if (loading.value) return
    loadError.value = ''
    loading.value = true

    const dashboardResult = await staffCenterDashboard()
        .then((data) => ({ data, error: '' }))
        .catch((error) => ({ data: null, error: resolveErrorMessage(error) }))

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

    if (dashboardResult.data?.profile && typeof dashboardResult.data.profile === 'object') {
        profileDetail.value = dashboardResult.data.profile as StaffProfileDetail
    }

    const errorMessage = dashboardResult.error

    if (errorMessage) {
        loadError.value = errorMessage
        showError(errorMessage)
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

    void checkOaBoundStatus()

    if (!(await ensureStaffCenterAccess())) return

    await loadPageData()
})
</script>

<style lang="scss" scoped>
.staff-center-page {
    width: 100%;
    min-height: 100%;
    padding: 16rpx 0 calc(36rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    background:
        radial-gradient(circle at 86% 0%, rgba(217, 190, 130, 0.12) 0, transparent 340rpx),
        linear-gradient(180deg, rgba(25, 23, 19, 0.03) 0, transparent 200rpx),
        var(--wm-color-bg-page, #f8f6f1);

    &__content {
        display: flex;
        flex-direction: column;
        gap: 20rpx;
    }
}

.workspace-error {
    padding: 24rpx;
    border-radius: 20rpx;
    background: #fff3e6;
    color: #915c38;
    font-size: 25rpx;
    text-align: center;
}

/* 1. 主理人专属名片 Hero Card */
.staff-hero {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    padding: 32rpx;
    border-radius: 32rpx;
    background: linear-gradient(145deg, #1C1A17 0%, #11100E 60%, #26211B 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.35);
    box-shadow: 0 20rpx 50rpx rgba(18, 16, 14, 0.16);
    overflow: hidden;

    &__ambient {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(200, 164, 93, 0.22), transparent 45%);
        pointer-events: none;
    }

    &__header {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__badges {
        display: flex;
        align-items: center;
        gap: 10rpx;
        flex-wrap: wrap;
    }

    &__edit-btn {
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        padding: 8rpx 18rpx;
        border-radius: 999rpx;
        background: rgba(217, 190, 130, 0.15);
        border: 1rpx solid rgba(217, 190, 130, 0.3);
        transition: opacity 0.2s ease;

        &:active {
            opacity: 0.8;
        }
    }

    &__edit-text {
        font-size: 22rpx;
        font-weight: 600;
        color: #D9BE82;
        line-height: 1;
    }

    &__profile-row {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 24rpx;
    }

    &__avatar-box {
        position: relative;
        flex-shrink: 0;
    }

    &__avatar {
        width: 120rpx;
        height: 120rpx;
        border-radius: 999rpx;
        border: 3rpx solid #D9BE82;
        box-shadow: 0 10rpx 24rpx rgba(0, 0, 0, 0.3);
        display: block;
    }

    &__avatar-tag {
        position: absolute;
        bottom: -6rpx;
        left: 50%;
        transform: translateX(-50%);
        padding: 2rpx 12rpx;
        border-radius: 999rpx;
        background: linear-gradient(135deg, #F3E5C8 0%, #C8A45D 100%);
        color: #1A1713;
        font-size: 18rpx;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
        box-shadow: 0 4rpx 10rpx rgba(0, 0, 0, 0.25);
    }

    &__copy {
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
    }

    &__name {
        font-size: 40rpx;
        font-weight: 800;
        line-height: 1.2;
        color: #FFFFFF;
        letter-spacing: 0.5rpx;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__meta {
        font-size: 23rpx;
        font-weight: 500;
        line-height: 1.4;
        color: rgba(255, 255, 255, 0.72);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__pulse-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12rpx;
        margin-top: 6rpx;
    }
}

.hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    padding: 6rpx 14rpx;
    border-radius: 999rpx;
    font-size: 21rpx;
    font-weight: 600;
    line-height: 1;

    &--role {
        background: rgba(217, 190, 130, 0.2);
        color: #D9BE82;
        border: 1rpx solid rgba(217, 190, 130, 0.35);
    }
}

.rating-pill {
    display: inline-flex;
    align-items: center;
    gap: 4rpx;
    padding: 4rpx 12rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.12);
    border: 1rpx solid rgba(255, 255, 255, 0.16);

    &__star {
        font-size: 20rpx;
        color: #F1D495;
        line-height: 1;
    }

    &__num {
        font-size: 21rpx;
        font-weight: 700;
        color: #FFFFFF;
        line-height: 1;
    }
}

.pulse-tile {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    padding: 16rpx 14rpx;
    border-radius: 20rpx;
    background: rgba(255, 253, 248, 0.08);
    border: 1rpx solid rgba(255, 253, 248, 0.08);
    transition: all 0.2s ease;

    &--active {
        background: rgba(200, 164, 93, 0.18);
        border-color: rgba(217, 190, 130, 0.38);
    }

    &:active {
        transform: scale(0.97);
    }

    &__label {
        font-size: 21rpx;
        font-weight: 600;
        color: rgba(255, 253, 248, 0.7);
        line-height: 1;
    }

    &__val-row {
        display: flex;
        align-items: baseline;
        gap: 6rpx;
    }

    &__val {
        font-size: 38rpx;
        font-weight: 800;
        color: #FFFFFF;
        line-height: 1;
    }

    &__unit {
        font-size: 20rpx;
        font-weight: 500;
        color: rgba(255, 253, 248, 0.6);
    }
}

/* 2. 待办提醒卡片 */
.urgent-task-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding: 24rpx 28rpx;
    border-radius: 28rpx;
    background: linear-gradient(135deg, #FAF3E5 0%, #EBD8B0 100%);
    border: 1rpx solid rgba(200, 164, 93, 0.45);
    box-shadow: 0 10rpx 24rpx rgba(200, 164, 93, 0.16);
    transition: opacity 0.2s ease;

    &:active {
        opacity: 0.88;
    }

    &__left {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 18rpx;
    }

    &__icon-wrap {
        width: 60rpx;
        height: 60rpx;
        border-radius: 18rpx;
        background: rgba(200, 164, 93, 0.22);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    &__text {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: 27rpx;
        font-weight: 700;
        color: #4A3311;
        line-height: 1.35;
    }

    &__sub {
        font-size: 21rpx;
        color: #8C6A37;
        line-height: 1.25;
    }

    &__btn {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        padding: 10rpx 22rpx;
        border-radius: 999rpx;
        background: #181614;
        color: #FFFFFF;
        font-size: 23rpx;
        font-weight: 700;
        box-shadow: 0 6rpx 14rpx rgba(24, 22, 20, 0.25);
    }
}

/* 3. 快捷操作栏 */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14rpx;
}

.quick-action {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    min-height: 140rpx;
    border-radius: 24rpx;
    background: #FAF8F5;
    border: 1rpx solid #EBE6DC;
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.96);
    }

    &--primary {
        background: #FAF3E5;
        border-color: #D9BE82;
    }

    &__icon-box {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__tag {
        position: absolute;
        top: -12rpx;
        right: -32rpx;
        padding: 2rpx 8rpx;
        border-radius: 999rpx;
        background: #B8954A;
        color: #FFFFFF;
        font-size: 16rpx;
        font-weight: 700;
        line-height: 1.1;
    }

    &__label {
        font-size: 25rpx;
        font-weight: 700;
        color: #191713;
        line-height: 1;
    }
}

/* Section Head 通用表头 */
.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 20rpx;

    &__left {
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: 32rpx;
        font-weight: 800;
        color: #181614;
        line-height: 1.25;
    }

    &__desc {
        font-size: 22rpx;
        color: #8C857B;
        line-height: 1.35;
    }

    &__meta {
        font-size: 22rpx;
        font-weight: 600;
        color: #B8954A;
    }
}

.section-link {
    display: inline-flex;
    align-items: center;
    gap: 4rpx;

    &__text {
        font-size: 23rpx;
        font-weight: 600;
        color: #B8954A;
    }
}

/* 4. 业务数据看板 */
.metric-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14rpx;
}

.metric-card {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 140rpx;
    padding: 22rpx 24rpx;
    border-radius: 24rpx;
    background: #FAF8F5;
    border: 1rpx solid #EAE5DB;
    transition: all 0.2s ease;

    &--accent {
        background: #F8F3EA;
        border-color: #D9BE82;
    }

    &--clickable:active {
        transform: scale(0.97);
    }

    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__label {
        font-size: 23rpx;
        font-weight: 600;
        color: #7A7267;
    }

    &__arrow {
        font-size: 26rpx;
        color: #B8954A;
        line-height: 1;
    }

    &__value-row {
        display: flex;
        align-items: baseline;
        gap: 6rpx;
        margin-top: 12rpx;
    }

    &__value {
        font-size: 46rpx;
        font-weight: 800;
        color: #181614;
        line-height: 1;
    }

    &__unit {
        font-size: 22rpx;
        font-weight: 500;
        color: #7A7267;
    }
}

/* 5. 近期订单流 */
.order-list {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}

.order-item {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 22rpx 24rpx;
    border-radius: 24rpx;
    background: #FAF8F5;
    border: 1rpx solid #EAE5DB;
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.98);
        border-color: #D9BE82;
    }

    &__top,
    &__bottom {
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
        gap: 6rpx;
    }

    &__title {
        font-size: 28rpx;
        font-weight: 700;
        color: #181614;
        line-height: 1.35;
    }

    &__subtitle {
        font-size: 22rpx;
        color: #6B6458;
        line-height: 1.4;
    }

    &__sn {
        font-size: 21rpx;
        color: #9A9285;
    }

    &__meta {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    &__amount {
        font-size: 28rpx;
        font-weight: 800;
        color: #181614;
    }
}

/* 6 & 7. 矩阵卡片 Resource Grid */
.resource-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14rpx;
}

.resource-card {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    padding: 22rpx 20rpx;
    border-radius: 24rpx;
    background: #FAF8F5;
    border: 1rpx solid #EAE5DB;
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.97);
        border-color: #D9BE82;
    }

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8rpx;
    }

    &__icon {
        width: 64rpx;
        height: 64rpx;
        border-radius: 18rpx;
        background: #F4EEDF;
        display: flex;
        align-items: center;
        justify-content: center;

        &--brand {
            background: #EDEAE1;
        }
    }

    &__badge {
        padding: 2rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(200, 164, 93, 0.16);
        border: 1rpx solid rgba(200, 164, 93, 0.3);
    }

    &__badge-text {
        font-size: 20rpx;
        font-weight: 700;
        color: #B8954A;
        line-height: 1.2;
    }

    &__title {
        font-size: 27rpx;
        font-weight: 700;
        color: #181614;
        line-height: 1.3;
    }

    &__sub {
        font-size: 21rpx;
        color: #8C857B;
        line-height: 1.2;
    }
}

@media (max-width: 360px) {
    .staff-hero {
        padding: 24rpx;
    }

    .staff-hero__avatar {
        width: 100rpx;
        height: 100rpx;
    }

    .staff-hero__name {
        font-size: 34rpx;
    }

    .pulse-tile__val {
        font-size: 32rpx;
    }
}
</style>
