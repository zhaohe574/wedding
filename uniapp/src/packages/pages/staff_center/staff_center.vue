<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" hasSafeBottom>
        <BaseNavbar title="服务人员中心" />

        <view class="staff-center-page">
            <view class="staff-center-page__content">
                <view
                    class="staff-hero-card"
                    @click="goPage('/packages/pages/staff_profile/staff_profile')"
                >
                    <view class="staff-hero-card__top">
                        <view class="hero-pill hero-pill--primary">
                            <text class="hero-pill__text">今日工作台</text>
                        </view>
                        <view :class="['hero-pill', `hero-pill--${auditBadge.modifier}`]">
                            <text class="hero-pill__text">{{ auditBadge.text }}</text>
                        </view>
                    </view>

                    <view class="staff-hero-card__profile">
                        <image
                            class="staff-hero-card__avatar"
                            :src="displayProfile.avatar || defaultAvatar"
                            mode="aspectFill"
                        />
                        <view class="staff-hero-card__copy">
                            <text class="staff-hero-card__title">{{ profileTitle }}</text>
                            <text class="staff-hero-card__meta">{{ profileMetaText }}</text>
                        </view>
                    </view>
                </view>

                <view class="staff-summary-chips" aria-label="工作台摘要">
                    <view
                        v-for="item in heroMetrics"
                        :key="item.label"
                        :class="['hero-metric', { 'hero-metric--accent': item.accent }]"
                    >
                        <text class="hero-metric__label">{{ item.label }}</text>
                        <text class="hero-metric__value">{{ item.value }}</text>
                    </view>
                </view>

                <view class="staff-section-card">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">今日焦点</text>
                        </view>
                        <text class="section-head__meta">先处理</text>
                    </view>

                    <view
                        class="focus-card focus-card--primary"
                        @click="handleFocusCard(primaryFocusCard)"
                    >
                        <view class="focus-card__row">
                            <view class="focus-card__copy">
                                <text class="focus-card__title">{{ primaryFocusCard.label }}</text>
                            </view>
                            <view class="focus-badge focus-badge--primary">
                                <text class="focus-badge__text">{{
                                    primaryFocusCard.valueText
                                }}</text>
                            </view>
                        </view>
                        <view class="focus-card__arrow">
                            <tn-icon name="right" size="22" color="#E85A4F" />
                        </view>
                    </view>

                    <view class="focus-grid">
                        <view
                            v-for="item in secondaryFocusCards"
                            :key="item.key"
                            class="focus-card focus-card--secondary"
                            @click="handleFocusCard(item)"
                        >
                            <view class="focus-card__row">
                                <text class="focus-card__title">{{ item.label }}</text>
                                <view
                                    :class="['focus-badge', `focus-badge--${item.badgeModifier}`]"
                                >
                                    <text class="focus-badge__text">{{ item.valueText }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="staff-section-card">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">订单与档期</text>
                        </view>
                        <text class="section-head__meta">{{ recentOrderMetaText }}</text>
                    </view>

                    <view class="stats-grid">
                        <view
                            v-for="item in overviewStats"
                            :key="item.label"
                            :class="['stats-card', { 'stats-card--accent': item.accent }]"
                        >
                            <text class="stats-card__label">{{ item.label }}</text>
                            <view class="stats-card__value-row">
                                <text class="stats-card__value">{{ item.value }}</text>
                                <text class="stats-card__unit">{{ item.unit }}</text>
                            </view>
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
                            class="order-card"
                            @click="goOrderDetail(item.id)"
                        >
                            <view class="order-card__row order-card__row--start">
                                <view class="order-card__copy">
                                    <text class="order-card__title">{{ item.title }}</text>
                                    <text class="order-card__subtitle">{{ item.subtitle }}</text>
                                </view>
                                <view
                                    :class="['status-pill', `status-pill--${item.statusModifier}`]"
                                >
                                    <text class="status-pill__text">{{ item.statusLabel }}</text>
                                </view>
                            </view>

                            <view class="order-card__row order-card__row--center">
                                <text class="order-card__sn">订单号 {{ item.orderSn }}</text>
                                <view
                                    v-if="item.pendingConfirmCount > 0"
                                    class="status-pill status-pill--primary"
                                >
                                    <text class="status-pill__text">
                                        待确认 {{ item.pendingConfirmCount }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <EmptyState
                        v-else
                        title="最近还没有订单动态"
                        description="新订单与客户确认事项会优先显示在这里"
                    />
                </view>

                <view class="staff-section-card">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">经营管理</text>
                        </view>
                        <text class="section-head__meta">正式能力</text>
                    </view>

                    <view class="stats-grid stats-grid--compact">
                        <view v-for="item in businessStats" :key="item.label" class="stats-card">
                            <text class="stats-card__label">{{ item.label }}</text>
                            <view class="stats-card__value-row">
                                <text class="stats-card__value">{{ item.value }}</text>
                                <text class="stats-card__unit">{{ item.unit }}</text>
                            </view>
                        </view>
                    </view>

                    <view class="manage-grid">
                        <view
                            v-for="item in businessMenus"
                            :key="item.path"
                            class="manage-card"
                            @click="goPage(item.path)"
                        >
                            <text class="manage-card__title">{{ item.name }}</text>
                            <text class="manage-card__desc">{{ item.description }}</text>
                        </view>
                    </view>
                </view>

                <view class="staff-section-card">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">常用入口</text>
                        </view>
                        <text class="section-head__meta">快速直达</text>
                    </view>

                    <view class="quick-grid">
                        <view
                            v-for="item in quickMenus"
                            :key="item.path"
                            :class="[
                                'quick-card',
                                `quick-card--${item.tone}`,
                                {
                                    'quick-card--accent': item.accent,
                                    'quick-card--single': item.singleRow
                                }
                            ]"
                            @click="goPage(item.path)"
                        >
                            <view class="quick-card__main">
                                <view
                                    :class="['quick-card__icon', `quick-card__icon--${item.tone}`]"
                                >
                                    <tn-icon
                                        :name="item.icon"
                                        size="36rpx"
                                        :color="item.iconColor"
                                    />
                                </view>

                                <view class="quick-card__copy">
                                    <view class="quick-card__title-row">
                                        <text class="quick-card__title">{{ item.name }}</text>
                                        <view
                                            v-if="item.badge > 0"
                                            class="status-pill status-pill--ghost"
                                        >
                                            <text class="status-pill__text">
                                                {{ formatBadge(item.badge) }}
                                            </text>
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <tn-icon
                                v-if="item.singleRow"
                                name="right"
                                size="36rpx"
                                color="#B4ACA8"
                            />
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import { staffCenterDashboard, staffCenterProfile } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

type BadgeModifier = 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'ghost'
type QuickTone = 'primary' | 'warning' | 'info' | 'neutral'

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

interface HeroMetricItem {
    label: string
    value: string
    accent: boolean
}

interface FocusCardItem {
    key: string
    label: string
    valueText: string
    badgeModifier: BadgeModifier
    action: () => void
}

interface StatsCardItem {
    label: string
    value: number
    unit: string
    accent: boolean
}

interface RecentOrderCardItem {
    id: number
    title: string
    subtitle: string
    orderSn: string
    statusLabel: string
    statusModifier: BadgeModifier
    pendingConfirmCount: number
}

interface QuickMenuItem {
    name: string
    path: string
    badge: number
    accent: boolean
    icon: string
    iconColor: string
    tone: QuickTone
    singleRow: boolean
}

const $theme = useThemeStore()
const defaultAvatar = '/static/images/user/default_avatar.png'
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

const formatCompactDate = (value: string) => {
    const text = String(value || '').trim()
    return text ? text.replace(/-/g, '.') : '待安排服务日期'
}

const formatCountText = (value: number, unit: string) => `${toNumber(value)} ${unit}`

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

const getAuditBadgeFallback = (status: number) => {
    const map: Record<number, string> = {
        0: '待审核',
        1: '已通过',
        2: '已拒绝'
    }
    return map[status] || '审核中'
}

const getAuditBadgeModifier = (status: number): BadgeModifier => {
    if (status === 1) return 'success'
    if (status === 2) return 'danger'
    return 'warning'
}

const getOrderStatusModifier = (status: number): BadgeModifier => {
    const map: Record<number, BadgeModifier> = {
        0: 'warning',
        1: 'info',
        2: 'success',
        3: 'neutral',
        4: 'neutral',
        5: 'warning',
        6: 'danger',
        7: 'warning',
        10: 'warning',
        8: 'danger'
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

const profileTitle = computed(() => {
    const name = displayProfile.value.name || '未填写姓名'
    const category = displayProfile.value.category_name || '服务人员'
    return `${name}｜${category}`
})

const profileMetaText = computed(() => {
    const parts = []
    const rating = formatRating(displayProfile.value.rating)
    const orderCount = toNumber(displayProfile.value.order_count)
    const years = toNumber(displayProfile.value.experience_years)

    if (rating) {
        parts.push(`评分 ${rating}`)
    }

    parts.push(`已接单 ${orderCount} 笔`)

    if (years > 0) {
        parts.push(`${years} 年经验`)
    }

    return parts.join(' · ') || '完善资料后可提升客户信任感'
})

const visibleTodoTotal = computed(
    () =>
        toNumber(dashboard.value.todo.pending_confirm_orders) +
        toNumber(dashboard.value.todo.today_service_count) +
        toNumber(dashboard.value.todo.upcoming_7d_schedule_count)
)

const auditBadge = computed(() => ({
    text:
        displayProfile.value.audit_status_desc ||
        getAuditBadgeFallback(displayProfile.value.audit_status),
    modifier: getAuditBadgeModifier(displayProfile.value.audit_status)
}))

const heroMetrics = computed<HeroMetricItem[]>(() => [
    {
        label: '今日待办',
        value: formatCountText(visibleTodoTotal.value, '项'),
        accent: true
    },
    {
        label: '本周档期',
        value: formatCountText(toNumber(dashboard.value.todo.upcoming_7d_schedule_count), '场'),
        accent: false
    },
    {
        label: '待确认',
        value: formatCountText(toNumber(dashboard.value.todo.pending_confirm_orders), '笔'),
        accent: false
    }
])

const primaryFocusCard = computed<FocusCardItem>(() => ({
    key: 'pending_confirm_orders',
    label: '待确认订单',
    valueText: formatCountText(toNumber(dashboard.value.todo.pending_confirm_orders), '笔'),
    badgeModifier: 'primary',
    action: () => goOrders(0)
}))

const secondaryFocusCards = computed<FocusCardItem[]>(() => [
    {
        key: 'today_service_count',
        label: '今日服务',
        valueText: formatCountText(toNumber(dashboard.value.todo.today_service_count), '项'),
        badgeModifier: 'warning',
        action: () => goOrders()
    },
    {
        key: 'upcoming_7d_schedule_count',
        label: '7日安排',
        valueText: formatCountText(toNumber(dashboard.value.todo.upcoming_7d_schedule_count), '场'),
        badgeModifier: 'info',
        action: () => goPage('/packages/pages/staff_schedule/staff_schedule')
    }
])

const overviewStats = computed<StatsCardItem[]>(() => [
    {
        label: '总订单',
        value: toNumber(dashboard.value.overview.order_count),
        unit: '单',
        accent: true
    },
    {
        label: '档期条目',
        value: toNumber(dashboard.value.overview.schedule_count),
        unit: '条',
        accent: false
    }
])

const businessStats = computed<StatsCardItem[]>(() => [
    {
        label: '作品数量',
        value: toNumber(dashboard.value.overview.work_count),
        unit: '条',
        accent: false
    },
    {
        label: '服务套餐',
        value: toNumber(dashboard.value.overview.package_count),
        unit: '个',
        accent: false
    }
])

const recentOrderCards = computed<RecentOrderCardItem[]>(() =>
    dashboard.value.recent_orders.slice(0, 2).map((order) => ({
        id: order.id,
        title: buildOrderTitle(order),
        subtitle: buildOrderSubtitle(order),
        orderSn: order.order_sn,
        statusLabel: order.order_status_desc || '处理中',
        statusModifier: getOrderStatusModifier(toNumber(order.order_status)),
        pendingConfirmCount: toNumber(order.pending_confirm_count)
    }))
)

const recentOrderMetaText = computed(() =>
    recentOrderCards.value.length ? `近 ${recentOrderCards.value.length} 笔` : '最近更新'
)

const businessMenus = computed(() => [
    {
        name: '附加项管理',
        path: '/packages/pages/staff_addon_list/staff_addon_list',
        description: '维护附加服务配置'
    },
    {
        name: '动态管理',
        path: '/packages/pages/staff_dynamic_list/staff_dynamic_list',
        description: '查看审核状态与处理反馈'
    }
])

const quickMenus = computed<QuickMenuItem[]>(() => [
    {
        name: '档期管理',
        path: '/packages/pages/staff_schedule/staff_schedule',
        badge: 0,
        accent: false,
        icon: 'calendar',
        iconColor: '#E85A4F',
        tone: 'primary',
        singleRow: false
    },
    {
        name: '订单跟进',
        path: '/packages/pages/staff_order_list/staff_order_list',
        badge: toNumber(dashboard.value.todo.pending_confirm_orders),
        accent: true,
        icon: 'order',
        iconColor: '#E85A4F',
        tone: 'primary',
        singleRow: false
    },
    {
        name: '资料编辑',
        path: '/packages/pages/staff_profile/staff_profile',
        badge: 0,
        accent: false,
        icon: 'my',
        iconColor: '#C99B73',
        tone: 'warning',
        singleRow: false
    },
    {
        name: '作品管理',
        path: '/packages/pages/staff_work_list/staff_work_list',
        badge: 0,
        accent: false,
        icon: 'folder',
        iconColor: '#4D7AD9',
        tone: 'info',
        singleRow: false
    },
    {
        name: '套餐管理',
        path: '/packages/pages/staff_package_list/staff_package_list',
        badge: 0,
        accent: false,
        icon: 'set',
        iconColor: '#C99B73',
        tone: 'warning',
        singleRow: false
    }
])

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

const handleFocusCard = (item: FocusCardItem) => {
    item.action()
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
        gap: 15rpx;
        padding: 0 var(--wm-space-page-x, 37rpx);
    }
}

.staff-hero-card,
.staff-section-card {
    position: relative;
    overflow: hidden;
    box-sizing: border-box;
}

.staff-hero-card {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    padding: 30rpx;
    border-radius: 49rpx;
    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);
    background: linear-gradient(135deg, #fff6f2 0%, #fde8e1 100%);
    box-shadow: 0 20rpx 44rpx rgba(192, 130, 115, 0.16);

    &__top,
    &__profile {
        display: flex;
    }

    &__top {
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__profile {
        align-items: center;
        gap: 22rpx;
    }

    &__avatar {
        width: 108rpx;
        height: 108rpx;
        flex-shrink: 0;
        border-radius: 999rpx;
        background: #efcbc0;
        border: 2rpx solid rgba(255, 255, 255, 0.88);
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 7rpx;
    }

    &__title {
        font-size: 40rpx;
        font-weight: 700;
        line-height: 1.3;
        color: var(--wm-text-primary, #1e2432);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__meta {
        font-size: 24rpx;
        font-weight: 600;
        line-height: 1.5;
        color: var(--wm-text-secondary, #7f7b78);
    }
}

.staff-summary-chips {
    display: flex;
    gap: 12rpx;
}

.hero-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42rpx;
    padding: 11rpx 18rpx;
    border-radius: 999rpx;
    box-sizing: border-box;

    &__text {
        font-size: 22rpx;
        font-weight: 700;
        line-height: 1;
    }

    &--primary {
        background: #fff1ee;

        .hero-pill__text {
            color: var(--wm-color-primary, #e85a4f);
        }
    }

    &--success,
    &--warning,
    &--danger {
        border: 1rpx solid var(--wm-color-border, #efe6e1);
        background: rgba(255, 255, 255, 0.8);
    }

    &--success .hero-pill__text {
        color: #2f7d58;
    }

    &--warning .hero-pill__text {
        color: #c98524;
    }

    &--danger .hero-pill__text {
        color: #b44a3a;
    }
}

.hero-metric {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    padding: 15rpx 18rpx;
    border-radius: 30rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.78);
    box-sizing: border-box;

    &--accent {
        background: #fff1ee;
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }

    &__label {
        font-size: 21rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &--accent .hero-metric__label {
        color: var(--wm-color-primary, #e85a4f);
    }

    &__value {
        font-size: 38rpx;
        font-weight: 700;
        line-height: 1.2;
        color: var(--wm-text-primary, #1e2432);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

.staff-section-card {
    display: flex;
    flex-direction: column;
    gap: 15rpx;
    padding: 26rpx 30rpx;
    border-radius: 45rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.92);
    box-shadow: var(--wm-shadow-card, 0 18rpx 36rpx rgba(214, 185, 167, 0.2));
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
}

.section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: 32rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #1e2432);
    }

    &__desc {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.45;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__meta {
        flex-shrink: 0;
        padding-top: 4rpx;
        font-size: 22rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-secondary, #7f7b78);
    }
}

.focus-grid,
.stats-grid,
.manage-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
}

.focus-card,
.stats-card,
.manage-card,
.order-card {
    border-radius: 30rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: #fcfbf9;
    box-sizing: border-box;
}

.focus-card {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 19rpx 22rpx;

    &--primary {
        background: #fff1ee;
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }

    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    &__title {
        font-size: 28rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #1e2432);
    }

    &__desc,
    &__hint {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.45;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
    }

    &__arrow {
        display: flex;
        justify-content: flex-end;
    }

    &--primary .focus-card__hint {
        color: var(--wm-color-primary, #e85a4f);
    }
}

.focus-badge,
.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38rpx;
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    box-sizing: border-box;

    &__text {
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }

    &--primary {
        background: #fff1ee;

        .focus-badge__text,
        .status-pill__text {
            color: var(--wm-color-primary, #e85a4f);
        }
    }

    &--warning {
        background: #fff8ed;

        .focus-badge__text,
        .status-pill__text {
            color: #c99b73;
        }
    }

    &--info {
        background: #eef5ff;

        .focus-badge__text,
        .status-pill__text {
            color: #4d7ad9;
        }
    }

    &--success {
        background: rgba(47, 125, 88, 0.12);

        .status-pill__text {
            color: #2f7d58;
        }
    }

    &--danger {
        background: rgba(180, 74, 58, 0.12);

        .status-pill__text {
            color: #b44a3a;
        }
    }

    &--neutral {
        background: rgba(96, 112, 134, 0.12);

        .status-pill__text {
            color: #607086;
        }
    }

    &--ghost {
        background: rgba(255, 255, 255, 0.86);
        border: 1rpx solid var(--wm-color-border, #efe6e1);

        .status-pill__text {
            color: var(--wm-text-secondary, #7f7b78);
        }
    }
}

.stats-grid--compact .stats-card {
    min-height: 148rpx;
}

.stats-card {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    padding: 19rpx 22rpx;

    &--accent {
        background: #fff1ee;
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }

    &__label {
        font-size: 21rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &--accent .stats-card__label {
        color: var(--wm-color-primary, #e85a4f);
    }

    &__value-row {
        display: flex;
        align-items: flex-end;
        gap: 8rpx;
    }

    &__value {
        font-size: 46rpx;
        font-weight: 700;
        line-height: 1.1;
        color: var(--wm-text-primary, #1e2432);
    }

    &__unit,
    &__hint {
        font-size: 21rpx;
        font-weight: 600;
        line-height: 1.35;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__hint {
        line-height: 1.45;
    }
}

.order-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.order-card {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 19rpx 22rpx;

    &__row {
        display: flex;
        justify-content: space-between;
        gap: 12rpx;

        &--start {
            align-items: flex-start;
        }

        &--center {
            align-items: center;
        }
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 5rpx;
    }

    &__title {
        font-size: 28rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #1e2432);
    }

    &__subtitle,
    &__hint {
        font-size: 22rpx;
        font-weight: 600;
        line-height: 1.45;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__sn,
    &__pending-placeholder {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.35;
        color: var(--wm-text-tertiary, #b4aca8);
    }

    &__pending-placeholder {
        flex-shrink: 0;
    }
}

.manage-card {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    min-height: 128rpx;
    padding: 19rpx 22rpx;

    &__title {
        font-size: 28rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #1e2432);
    }

    &__desc {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.45;
        color: var(--wm-text-secondary, #7f7b78);
    }
}

.quick-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
}

.quick-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14rpx;
    min-height: 136rpx;
    padding: 19rpx 22rpx;
    border-radius: 30rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: #fcfbf9;
    box-sizing: border-box;

    &--accent {
        background: #fff1ee;
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }

    &--single {
        grid-column: 1 / -1;
        min-height: 108rpx;
    }

    &__main {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 14rpx;
    }

    &__icon {
        width: 68rpx;
        height: 68rpx;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 24rpx;

        &--primary {
            background: #fff1ee;
        }

        &--warning {
            background: #fff8ed;
        }

        &--info {
            background: #eef5ff;
        }

        &--neutral {
            background: #f3efea;
        }
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 5rpx;
    }

    &__title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10rpx;
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: 24rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #1e2432);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__desc {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.45;
        color: var(--wm-text-secondary, #7f7b78);
    }
}
</style>
