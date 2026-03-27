<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" hasSafeBottom>
        <BaseNavbar title="服务人员中心" />

        <PageSection variant="hero">
            <BaseCard
                variant="hero"
                scene="staff"
                interactive
                borderRadius="24rpx"
                padding="24rpx"
                @click="goPage('/packages/pages/staff_profile/staff_profile')"
            >
                <view class="info-card">
                    <view class="info-card__top">
                        <text class="info-card__eyebrow">今日工作台</text>
                        <StatusBadge :tone="auditBadgeTone" size="sm">
                            {{ auditBadgeText }}
                        </StatusBadge>
                    </view>

                    <view class="info-card__profile">
                        <image
                            class="info-card__avatar"
                            :src="displayProfile.avatar || defaultAvatar"
                            mode="aspectFill"
                        />
                        <view class="info-card__profile-main">
                            <view class="info-card__title-row">
                                <text class="info-card__title">{{ profileTitle }}</text>
                                <text class="info-card__link">查看资料</text>
                            </view>
                            <text class="info-card__meta">{{ profileMetaText }}</text>
                        </view>
                    </view>

                    <text class="info-card__summary">{{ headerSummaryText }}</text>

                    <view class="info-card__chips">
                        <view
                            v-for="item in headerChips"
                            :key="item.label"
                            class="info-chip"
                        >
                            <text class="info-chip__label">{{ item.label }}</text>
                            <text class="info-chip__value">{{ item.value }}</text>
                        </view>
                    </view>
                </view>
            </BaseCard>
        </PageSection>

        <PageSection variant="dashboard">
            <BaseCard
                variant="surface"
                scene="staff"
                borderRadius="24rpx"
                padding="20rpx 24rpx"
                :hoverable="false"
            >
                <view class="section-head">
                    <view>
                        <text class="section-head__title">待办看板</text>
                        <text class="section-head__subtitle">优先处理时效最高的事项</text>
                    </view>
                    <text class="section-head__meta">今日优先</text>
                </view>

                <view class="todo-list">
                    <view
                        v-for="item in todoRows"
                        :key="item.key"
                        class="todo-row"
                        @click="handleTodo(item)"
                    >
                        <view class="todo-row__main">
                            <text class="todo-row__title">{{ item.label }}</text>
                            <text class="todo-row__desc">{{ item.desc }}</text>
                        </view>
                        <view class="todo-row__side">
                            <text class="todo-row__value">{{ item.value }}</text>
                            <tn-icon name="right" size="20" color="#B4ACA8" />
                        </view>
                    </view>
                </view>
            </BaseCard>
        </PageSection>

        <PageSection variant="dashboard">
            <BaseCard
                variant="surface"
                scene="staff"
                borderRadius="24rpx"
                padding="20rpx 24rpx"
                :hoverable="false"
            >
                <view class="section-head">
                    <view>
                        <text class="section-head__title">经营概览</text>
                        <text class="section-head__subtitle">围绕订单、档期与内容更新管理</text>
                    </view>
                    <text class="section-head__meta">排期与订单</text>
                </view>

                <view class="overview-grid">
                    <view
                        v-for="item in overviewCards"
                        :key="item.label"
                        :class="['overview-card', { 'overview-card--accent': item.highlight }]"
                    >
                        <text class="overview-card__label">{{ item.label }}</text>
                        <view class="overview-card__value-row">
                            <text class="overview-card__value">{{ item.value }}</text>
                            <text class="overview-card__unit">{{ item.unit }}</text>
                        </view>
                        <text class="overview-card__hint">{{ item.hint }}</text>
                    </view>
                </view>
            </BaseCard>
        </PageSection>

        <PageSection variant="dashboard">
            <BaseCard
                variant="surface"
                scene="staff"
                borderRadius="24rpx"
                padding="20rpx 24rpx"
                :hoverable="false"
            >
                <view class="section-head">
                    <view>
                        <text class="section-head__title">最近订单</text>
                        <text class="section-head__subtitle">首页直接浏览最新进入的订单摘要</text>
                    </view>
                    <text class="section-head__meta">{{ recentOrderCountText }}</text>
                </view>

                <LoadingState
                    v-if="loading && !recentOrderRows.length"
                    text="正在同步工作台..."
                />

                <view v-else-if="recentOrderRows.length" class="recent-order-list">
                    <view
                        v-for="item in recentOrderRows"
                        :key="item.id"
                        class="recent-order"
                        @click="goOrderDetail(item.id)"
                    >
                        <view class="recent-order__head">
                            <view class="recent-order__copy">
                                <text class="recent-order__title">{{ item.title }}</text>
                                <text class="recent-order__subtitle">{{ item.subtitle }}</text>
                            </view>
                            <StatusBadge :tone="getOrderStatusTone(item.orderStatus)" size="sm">
                                {{ item.statusLabel }}
                            </StatusBadge>
                        </view>

                        <view class="recent-order__meta">
                            <text class="recent-order__sn">{{ item.orderSn }}</text>
                            <text
                                v-if="item.pendingConfirmCount > 0"
                                class="recent-order__pending"
                            >
                                待确认 {{ item.pendingConfirmCount }}
                            </text>
                        </view>
                    </view>
                </view>

                <EmptyState
                    v-else
                    title="最近还没有订单动态"
                    description="新订单与客户确认事项会优先显示在这里"
                />
            </BaseCard>
        </PageSection>

        <PageSection variant="dashboard">
            <BaseCard
                variant="surface"
                scene="staff"
                borderRadius="24rpx"
                padding="20rpx 24rpx"
                :hoverable="false"
            >
                <view class="section-head">
                    <view>
                        <text class="section-head__title">快捷操作</text>
                        <text class="section-head__subtitle">常用模块直达，减少反复切页</text>
                    </view>
                    <text class="section-head__meta">常用入口</text>
                </view>

                <view class="quick-grid">
                    <view
                        v-for="item in quickMenus"
                        :key="item.path"
                        :class="['quick-card', { 'quick-card--accent': item.highlight }]"
                        @click="goPage(item.path)"
                    >
                        <view v-if="item.badge > 0" class="quick-card__badge">
                            {{ formatBadge(item.badge) }}
                        </view>
                        <text class="quick-card__name">{{ item.name }}</text>
                        <text class="quick-card__desc">{{ item.desc }}</text>
                    </view>
                </view>
            </BaseCard>
        </PageSection>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import PageShell from '@/components/base/PageShell.vue'
import PageSection from '@/components/base/PageSection.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import { staffCenterDashboard, staffCenterProfile } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

interface DashboardProfile {
    name: string
    avatar: string
    status: number
    audit_status: number
    mobile: string
    price_text: string
    has_price: boolean
    category_name: string
}

interface DashboardOverview {
    order_count: number
    work_count: number
    package_count: number
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
    audit_status?: number
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
    audit_status: number
    rating: number | string
    experience_years: number | string
    order_count: number
}

interface HeaderChipItem {
    label: string
    value: number
}

interface TodoRowItem {
    key: string
    label: string
    desc: string
    value: number
    action: () => void
}

interface OverviewCardItem {
    label: string
    value: number
    unit: string
    hint: string
    highlight: boolean
}

interface RecentOrderRowItem {
    id: number
    title: string
    subtitle: string
    orderSn: string
    statusLabel: string
    orderStatus: number
    pendingConfirmCount: number
}

interface QuickMenuItem {
    name: string
    desc: string
    path: string
    badge: number
    highlight: boolean
}

const $theme = useThemeStore()
const defaultAvatar = '/static/images/user/default_avatar.png'
const loading = ref(false)
const profileDetail = ref<StaffProfileDetail>({})

const createEmptyDashboard = (): DashboardState => ({
    profile: {
        name: '',
        avatar: '',
        status: 0,
        audit_status: 0,
        mobile: '',
        price_text: '',
        has_price: false,
        category_name: ''
    },
    overview: {
        order_count: 0,
        work_count: 0,
        package_count: 0,
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

const resolveAuditTagText = (status: unknown, auditStatus: unknown) => {
    const normalizedStatus = toNumber(status)
    const normalizedAuditStatus = toNumber(auditStatus)

    if (normalizedStatus !== 1 && normalizedAuditStatus === 1) {
        return '已停用'
    }

    const map: Record<number, string> = {
        0: '待审核',
        1: '已认证',
        2: '已拒绝'
    }

    return map[normalizedAuditStatus] || '待完善'
}

const resolveAuditTone = (status: unknown, auditStatus: unknown): BadgeTone => {
    const normalizedStatus = toNumber(status)
    const normalizedAuditStatus = toNumber(auditStatus)

    if (normalizedStatus !== 1 && normalizedAuditStatus === 1) {
        return 'neutral'
    }

    const map: Record<number, BadgeTone> = {
        0: 'warning',
        1: 'success',
        2: 'danger'
    }

    return map[normalizedAuditStatus] || 'info'
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
        audit_status: detail.audit_status ?? summary.audit_status ?? 0,
        rating: detail.rating ?? 0,
        experience_years: detail.experience_years ?? 0,
        order_count: detail.orderCount ?? dashboard.value.overview.order_count
    }
})

const auditBadgeText = computed(() =>
    resolveAuditTagText(displayProfile.value.status, displayProfile.value.audit_status)
)

const auditBadgeTone = computed<BadgeTone>(() =>
    resolveAuditTone(displayProfile.value.status, displayProfile.value.audit_status)
)

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

const headerSummaryText = computed(
    () =>
        `今日待办 ${toNumber(dashboard.value.todo.total)} 项，待确认订单 ${toNumber(
            dashboard.value.todo.pending_confirm_orders
        )} 笔。`
)

const headerChips = computed<HeaderChipItem[]>(() => [
    {
        label: '今日待办',
        value: toNumber(dashboard.value.todo.total)
    },
    {
        label: '本周档期',
        value: toNumber(dashboard.value.todo.upcoming_7d_schedule_count)
    },
    {
        label: '待确认',
        value: toNumber(dashboard.value.todo.pending_confirm_orders)
    }
])

const todoRows = computed<TodoRowItem[]>(() => [
    {
        key: 'pending_confirm_orders',
        label: '待确认订单',
        desc: '优先处理客户新下单与确认动作',
        value: toNumber(dashboard.value.todo.pending_confirm_orders),
        action: () => goOrders(0)
    },
    {
        key: 'today_service_count',
        label: '今日服务',
        desc: '确认当日行程与服务准备',
        value: toNumber(dashboard.value.todo.today_service_count),
        action: () => goOrders()
    },
    {
        key: 'upcoming_7d_schedule_count',
        label: '未来 7 日安排',
        desc: '提前检查档期冲突与服务准备',
        value: toNumber(dashboard.value.todo.upcoming_7d_schedule_count),
        action: () => goPage('/packages/pages/staff_schedule/staff_schedule')
    },
    {
        key: 'unread_message_count',
        label: '未读消息',
        desc: '查看通知、系统提醒与客户留言',
        value: toNumber(dashboard.value.todo.unread_message_count),
        action: () => goPage('/packages/pages/notification/index')
    }
])

const overviewCards = computed<OverviewCardItem[]>(() => [
    {
        label: '总订单',
        value: toNumber(dashboard.value.overview.order_count),
        unit: '单',
        hint: '持续跟进客户订单',
        highlight: true
    },
    {
        label: '档期条目',
        value: toNumber(dashboard.value.overview.schedule_count),
        unit: '条',
        hint: '维护未来服务安排',
        highlight: false
    },
    {
        label: '作品数量',
        value: toNumber(dashboard.value.overview.work_count),
        unit: '条',
        hint: '展示案例与内容更新',
        highlight: false
    },
    {
        label: '服务套餐',
        value: toNumber(dashboard.value.overview.package_count),
        unit: '个',
        hint: '统一管理价格与组合',
        highlight: false
    }
])

const recentOrderRows = computed<RecentOrderRowItem[]>(() =>
    dashboard.value.recent_orders.slice(0, 2).map((order) => ({
        id: order.id,
        title: buildOrderTitle(order),
        subtitle: buildOrderSubtitle(order),
        orderSn: order.order_sn,
        statusLabel: order.order_status_desc,
        orderStatus: toNumber(order.order_status),
        pendingConfirmCount: toNumber(order.pending_confirm_count)
    }))
)

const recentOrderCountText = computed(() => `近 ${recentOrderRows.value.length} 笔`)

const quickMenus = computed<QuickMenuItem[]>(() => [
    {
        name: '档期管理',
        desc: '查看日程',
        path: '/packages/pages/staff_schedule/staff_schedule',
        badge: 0,
        highlight: true
    },
    {
        name: '订单跟进',
        desc: '处理确认',
        path: '/packages/pages/staff_order_list/staff_order_list',
        badge: toNumber(dashboard.value.todo.pending_confirm_orders),
        highlight: false
    },
    {
        name: '客户消息',
        desc: '查看消息',
        path: '/packages/pages/notification/index',
        badge: toNumber(dashboard.value.todo.unread_message_count),
        highlight: false
    },
    {
        name: '资料编辑',
        desc: '更新信息',
        path: '/packages/pages/staff_profile/staff_profile',
        badge: 0,
        highlight: false
    },
    {
        name: '作品管理',
        desc: '展示案例',
        path: '/packages/pages/staff_work_list/staff_work_list',
        badge: 0,
        highlight: false
    },
    {
        name: '套餐管理',
        desc: '维护价格',
        path: '/packages/pages/staff_package_list/staff_package_list',
        badge: 0,
        highlight: false
    },
    {
        name: '动态管理',
        desc: '发布动态',
        path: '/packages/pages/staff_dynamic_list/staff_dynamic_list',
        badge: 0,
        highlight: false
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

const handleTodo = (item: TodoRowItem) => {
    item.action()
}

const getOrderStatusTone = (status: number): BadgeTone => {
    const styles: Record<number, BadgeTone> = {
        0: 'warning',
        1: 'info',
        2: 'success',
        3: 'neutral',
        4: 'neutral',
        5: 'warning',
        6: 'danger',
        7: 'warning',
        8: 'danger'
    }

    return styles[status] || 'neutral'
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
.info-card {
    display: flex;
    flex-direction: column;
    gap: 16rpx;

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__eyebrow {
        display: inline-flex;
        align-self: flex-start;
        padding: 8rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(255, 255, 255, 0.72);
        color: var(--wm-color-primary, #e85a4f);
        font-size: 20rpx;
        font-weight: 700;
        letter-spacing: 0.08em;
    }

    &__profile {
        display: flex;
        align-items: center;
        gap: 16rpx;
    }

    &__avatar {
        width: 76rpx;
        height: 76rpx;
        flex-shrink: 0;
        border-radius: 24rpx;
        border: 2rpx solid rgba(255, 255, 255, 0.88);
        background: rgba(255, 255, 255, 0.74);
    }

    &__profile-main {
        flex: 1;
        min-width: 0;
    }

    &__title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: 32rpx;
        font-weight: 700;
        line-height: 1.3;
        color: var(--wm-text-primary, #1e2432);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__link {
        flex-shrink: 0;
        font-size: 22rpx;
        color: var(--wm-color-primary, #e85a4f);
    }

    &__meta {
        display: block;
        margin-top: 6rpx;
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.45;
        color: var(--wm-text-secondary, #7f7b78);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__summary {
        font-size: 22rpx;
        line-height: 1.55;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 10rpx;
    }
}

.info-chip {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 8rpx 12rpx;
    border-radius: 999rpx;
    background: rgba(252, 251, 249, 0.82);
    border: 1rpx solid rgba(239, 230, 225, 0.92);

    &__label {
        font-size: 18rpx;
        font-weight: 600;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__value {
        font-size: 18rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #1e2432);
    }
}

.section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;

    &__title {
        display: block;
        font-size: 30rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #1e2432);
    }

    &__subtitle {
        display: block;
        margin-top: 6rpx;
        font-size: 22rpx;
        line-height: 1.55;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__meta {
        flex-shrink: 0;
        padding-top: 4rpx;
        font-size: 20rpx;
        font-weight: 600;
        color: var(--wm-text-tertiary, #b4aca8);
        white-space: nowrap;
    }
}

.todo-list,
.recent-order-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-top: 18rpx;
}

.todo-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding: 18rpx 16rpx;
    border-radius: 20rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);

    &__main {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: 24rpx;
        font-weight: 600;
        color: var(--wm-text-primary, #1e2432);
    }

    &__desc {
        display: block;
        margin-top: 6rpx;
        font-size: 20rpx;
        line-height: 1.45;
        color: var(--wm-text-secondary, #7f7b78);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__side {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        flex-shrink: 0;
    }

    &__value {
        font-size: 30rpx;
        font-weight: 700;
        line-height: 1;
        color: var(--wm-text-primary, #1e2432);
    }
}

.overview-grid,
.quick-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14rpx;
    margin-top: 18rpx;
}

.overview-card {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    padding: 18rpx 16rpx;
    border-radius: 20rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);

    &--accent {
        background: var(--wm-color-primary-soft, #fff1ee);
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }

    &__label {
        font-size: 20rpx;
        font-weight: 600;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__value-row {
        display: flex;
        align-items: flex-end;
        gap: 8rpx;
    }

    &__value {
        font-size: 40rpx;
        font-weight: 700;
        line-height: 1;
        color: var(--wm-text-primary, #1e2432);
    }

    &__unit {
        padding-bottom: 4rpx;
        font-size: 20rpx;
        font-weight: 600;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__hint {
        font-size: 20rpx;
        line-height: 1.45;
        color: var(--wm-text-tertiary, #b4aca8);
    }
}

.recent-order {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    padding: 18rpx 16rpx;
    border-radius: 20rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14rpx;
    }

    &__copy {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: 24rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #1e2432);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__subtitle {
        display: block;
        margin-top: 6rpx;
        font-size: 20rpx;
        line-height: 1.45;
        color: var(--wm-text-secondary, #7f7b78);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
    }

    &__sn {
        flex: 1;
        min-width: 0;
        font-size: 18rpx;
        color: var(--wm-text-tertiary, #b4aca8);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__pending {
        flex-shrink: 0;
        font-size: 18rpx;
        font-weight: 700;
        color: var(--wm-color-warning, #c98524);
    }
}

.quick-card {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 8rpx;
    min-height: 126rpx;
    padding: 18rpx 16rpx;
    border-radius: 20rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);

    &--accent {
        background: var(--wm-color-primary-soft, #fff1ee);
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }

    &__badge {
        position: absolute;
        top: 14rpx;
        right: 14rpx;
        min-width: 34rpx;
        height: 34rpx;
        padding: 0 8rpx;
        border-radius: 999rpx;
        background: var(--wm-color-primary, #e85a4f);
        color: #ffffff;
        font-size: 20rpx;
        font-weight: 700;
        line-height: 34rpx;
        text-align: center;
    }

    &__name {
        display: block;
        padding-right: 38rpx;
        font-size: 24rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #1e2432);
    }

    &__desc {
        display: block;
        font-size: 20rpx;
        line-height: 1.45;
        color: var(--wm-text-secondary, #7f7b78);
    }
}
</style>
