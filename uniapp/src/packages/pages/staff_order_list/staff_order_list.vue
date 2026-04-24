<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar title="订单管理" />

        <view class="page-container wm-page-content">
            <z-paging
                ref="pagingRef"
                v-model="orderList"
                :auto="false"
                :hide-empty-view="true"
                :paging-style="resolvedPagingStyle"
                @query="queryList"
            >
                <template #top>
                    <view class="page-section page-section--top">
                        <BaseCard
                            variant="hero"
                            scene="staff"
                            class="hero-card"
                            :style="heroCardStyle"
                        >
                            <view class="hero-card__head">
                                <view class="hero-card__copy">
                                    <text class="hero-card__eyebrow">履约工作台</text>
                                    <text class="hero-card__title">订单管理</text>
                                    <text class="hero-card__desc">{{ heroHeadline }}</text>
                                </view>

                                <view class="hero-card__total">
                                    <text class="hero-card__total-label">总订单</text>
                                    <text class="hero-card__total-value">
                                        {{ Number(orderStats.all || 0) }}
                                    </text>
                                    <text class="hero-card__total-unit">单</text>
                                </view>
                            </view>

                            <view class="hero-focus-strip">
                                <view
                                    v-for="item in focusCards"
                                    :key="item.key"
                                    :class="[
                                        'hero-focus-card',
                                        { 'hero-focus-card--accent': item.accent }
                                    ]"
                                    @click="switchStatusByValue(item.status)"
                                >
                                    <text class="hero-focus-card__label">{{ item.label }}</text>
                                    <view class="hero-focus-card__value-row">
                                        <text class="hero-focus-card__value">{{ item.value }}</text>
                                        <text class="hero-focus-card__unit">{{ item.unit }}</text>
                                    </view>
                                    <text class="hero-focus-card__hint">{{ item.hint }}</text>
                                </view>
                            </view>

                            <view class="hero-metrics">
                                <view
                                    v-for="item in summaryCards"
                                    :key="item.key"
                                    :class="[
                                        'summary-chip',
                                        { 'summary-chip--accent': item.accent }
                                    ]"
                                    @click="switchStatusByValue(item.status)"
                                >
                                    <text class="summary-chip__label">{{ item.label }}</text>
                                    <view class="summary-chip__value-row">
                                        <text class="summary-chip__value">{{ item.value }}</text>
                                        <text class="summary-chip__unit">{{ item.unit }}</text>
                                    </view>
                                </view>
                            </view>

                            <view class="summary-bar__scope">
                                <tn-icon name="tip" size="18" color="#C8A45D" />
                                <text class="summary-bar__scope-text">只显示履约相关订单</text>
                            </view>

                            <scroll-view scroll-x class="summary-tabs" show-scrollbar="false">
                                <view class="summary-tabs__row">
                                    <FilterChip
                                        v-for="tab in statusTabs"
                                        :key="String(tab.value)"
                                        scene="staff"
                                        :selected="currentStatus === tab.value"
                                        @click="switchStatusByValue(tab.value)"
                                    >
                                        {{ `${tab.label} ${tab.count}` }}
                                    </FilterChip>
                                </view>
                            </scroll-view>
                        </BaseCard>
                    </view>
                </template>

                <view class="page-section page-section--list">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">{{ listSectionTitle }}</text>
                            <text class="section-head__desc">{{ listSectionDesc }}</text>
                        </view>
                        <text class="section-head__meta">{{ listSectionMeta }}</text>
                    </view>

                    <LoadingState v-if="loading && !hasLoaded" text="正在同步订单工作台..." />

                    <template v-else-if="orderList.length">
                        <BaseCard
                            v-for="order in orderList"
                            :key="order.id"
                            variant="glass"
                            scene="staff"
                            class="order-card"
                            interactive
                            @click="goDetail(order.id)"
                        >
                            <view class="order-card__head">
                                <view class="order-card__copy">
                                    <view class="order-card__date-row">
                                        <text class="order-card__date">
                                            {{ order.serviceDate || '待安排服务日期' }}
                                        </text>
                                        <StatusBadge
                                            v-if="order.pendingConfirmCount > 0"
                                            tone="info"
                                            size="sm"
                                        >
                                            待确认 {{ order.pendingConfirmCount }}
                                        </StatusBadge>
                                    </view>
                                    <text class="order-card__sn">订单号 {{ order.orderNo }}</text>
                                    <text class="order-card__time">
                                        创建时间：{{ order.createTimeText || '暂无记录' }}
                                    </text>
                                </view>

                                <StatusBadge
                                    :tone="order.statusTone"
                                    size="md"
                                    class="order-card__status"
                                >
                                    {{ order.statusText }}
                                </StatusBadge>
                            </view>

                            <view
                                v-if="getOrderAlertText(order)"
                                :class="[
                                    'order-card__alert',
                                    `order-card__alert--${getOrderAlertTone(order)}`
                                ]"
                            >
                                <tn-icon
                                    name="clock"
                                    size="18"
                                    :color="getOrderAlertIconColor(order)"
                                />
                                <text class="order-card__alert-text">{{
                                    getOrderAlertText(order)
                                }}</text>
                            </view>

                            <view class="order-card__summary">
                                <view class="order-card__line order-card__line--primary">
                                    <tn-icon
                                        name="map-pin"
                                        size="22"
                                        color="var(--wm-color-secondary, #C8A45D)"
                                    />
                                    <text>{{ order.location }}</text>
                                </view>

                                <view class="order-card__line">
                                    <tn-icon
                                        name="my"
                                        size="22"
                                        color="var(--wm-color-secondary, #C8A45D)"
                                    />
                                    <text>
                                        {{ order.contactName || '未填写联系人' }}
                                        <text v-if="order.contactMobile"
                                            >｜{{ order.contactMobile }}</text
                                        >
                                    </text>
                                </view>
                            </view>

                            <view class="order-card__meta-chips">
                                <text class="service-tag">服务项 {{ order.serviceCount }}</text>
                                <text class="service-tag">实付 ¥{{ order.actualPrice }}</text>
                                <text v-if="order.packageNames[0]" class="service-tag">
                                    {{ order.packageNames[0] }}
                                </text>
                                <text
                                    v-if="order.packageNames.length > 1"
                                    class="service-tag service-tag--muted"
                                >
                                    另 {{ order.packageNames.length - 1 }} 项
                                </text>
                            </view>

                            <view class="order-card__foot">
                                <view class="order-card__actions">
                                    <view
                                        v-if="getPrimaryActionLabel(order)"
                                        class="action-btn action-btn--primary"
                                        :style="primaryActionStyle"
                                        @click.stop="handlePrimaryAction(order)"
                                    >
                                        {{ getPrimaryActionLabel(order) }}
                                    </view>
                                    <view class="action-link" @click.stop="goDetail(order.id)">
                                        查看详情
                                        <tn-icon name="right" size="16" color="#9A9388" />
                                    </view>
                                </view>
                            </view>
                        </BaseCard>
                    </template>

                    <EmptyState v-else-if="hasLoaded" title="当前筛选下暂无订单" />
                </view>
            </z-paging>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'
import { onHide, onLoad, onShow, onUnload } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import FilterChip from '@/components/base/FilterChip.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import {
    staffCenterOrderComplete,
    staffCenterOrderConfirm,
    staffCenterOrderLists,
    staffCenterOrderStartService,
    staffCenterOrderStats
} from '@/api/staffCenter'
import { useFixedNavbarPagingStyle } from '@/packages/common/hooks/useFixedNavbarPagingStyle'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'

type StatusValue = number | ''
type StatusTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

interface StatusTabConfigItem {
    label: string
    value: StatusValue
    key: keyof typeof DEFAULT_ORDER_STATS
}

interface SummaryCardItem {
    key: string
    label: string
    value: number
    unit: string
    status: StatusValue
    accent: boolean
}

interface FormattedOrder {
    id: number
    orderNo: string
    statusValue: number
    statusText: string
    statusTone: StatusTone
    createTimeText: string
    serviceDate: string
    location: string
    actualPrice: string
    packageNames: string[]
    serviceCount: number
    contactName: string
    contactMobile: string
    pendingConfirmCount: number
    confirmRemainSeconds: number
    confirmExpireAt: number
    confirmTimeoutActionDesc: string
    payRemainSeconds: number
    payExpireAt: number
    payTimeoutActionDesc: string
    canConfirm: boolean
    canStart: boolean
    canComplete: boolean
}

const DEFAULT_ORDER_STATS = {
    all: 0,
    pending_confirm: 0,
    pending_pay: 0,
    paid: 0,
    in_service: 0,
    completed: 0,
    reviewed: 0,
    cancelled: 0,
    paused: 0,
    refunding: 0,
    refunded: 0
} as const

const STATUS_TAB_CONFIG: StatusTabConfigItem[] = [
    { label: '全部', value: '', key: 'all' },
    { label: '待确认', value: 0, key: 'pending_confirm' },
    { label: '待支付', value: 1, key: 'pending_pay' },
    { label: '待服务', value: 2, key: 'paid' },
    { label: '服务中', value: 3, key: 'in_service' },
    { label: '已完成', value: 4, key: 'completed' },
    { label: '已评价', value: 5, key: 'reviewed' },
    { label: '已取消', value: 6, key: 'cancelled' },
    { label: '已暂停', value: 7, key: 'paused' },
    { label: '退款中', value: 10, key: 'refunding' },
    { label: '已退款', value: 8, key: 'refunded' }
]

const STATUS_TEXT_MAP: Record<number, string> = {
    0: '待确认',
    1: '待支付',
    2: '待服务',
    3: '服务中',
    4: '已完成',
    5: '已评价',
    6: '已取消',
    7: '已暂停',
    10: '退款中',
    8: '已退款',
    9: '用户已删除'
}

const STATUS_TONE_MAP: Record<number, StatusTone> = {
    0: 'warning',
    1: 'info',
    2: 'success',
    3: 'neutral',
    4: 'success',
    5: 'info',
    6: 'danger',
    7: 'warning',
    10: 'warning',
    8: 'danger',
    9: 'danger'
}

const DISPLAY_ITEM_TYPES = [1, 3]

const $theme = useThemeStore()
const pagingStyle = useFixedNavbarPagingStyle()
const resolvedPagingStyle = computed(() => ({
    ...pagingStyle.value,
    paddingLeft: 'var(--wm-space-page-x, 37rpx)',
    paddingRight: 'var(--wm-space-page-x, 37rpx)',
    boxSizing: 'border-box'
}))
const pagingRef = ref<any>(null)
const orderList = ref<FormattedOrder[]>([])
const orderStats = ref<Record<string, number>>({ ...DEFAULT_ORDER_STATS })
const currentStatus = ref<StatusValue>('')
const loading = ref(false)
const hasLoaded = ref(false)
const orderCountdownNowTs = ref(Date.now())
let orderCountdownTimer: ReturnType<typeof setInterval> | null = null
let orderCountdownRefreshing = false

const heroCardStyle = computed(() => ({
    '--wm-hero-gradient': `linear-gradient(135deg, ${
        $theme.primaryColor || '#0B0B0B'
    }12 0%, var(--wm-color-bg-page, #FFFFFF) 52%, ${$theme.secondaryColor || '#C8A45D'}14 100%)`,
    borderColor: 'var(--wm-color-border-strong, #D8C28A)'
}))

const primaryActionStyle = computed(() => ({
    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${
        $theme.secondaryColor || $theme.primaryColor
    } 100%)`,
    color: $theme.btnColor
}))

const statusTabs = computed(() =>
    STATUS_TAB_CONFIG.map((item) => ({
        ...item,
        count: Number(orderStats.value[item.key] || 0)
    }))
)

const summaryCards = computed<SummaryCardItem[]>(() => [
    {
        key: 'pending_confirm',
        label: '待确认',
        value: Number(orderStats.value.pending_confirm || 0),
        unit: '笔',
        status: 0,
        accent: currentStatus.value === 0 || Number(orderStats.value.pending_confirm || 0) > 0
    },
    {
        key: 'paid',
        label: '待服务',
        value: Number(orderStats.value.paid || 0),
        unit: '单',
        status: 2,
        accent: currentStatus.value === 2
    },
    {
        key: 'in_service',
        label: '服务中',
        value: Number(orderStats.value.in_service || 0),
        unit: '单',
        status: 3,
        accent: currentStatus.value === 3
    },
    {
        key: 'completed',
        label: '已完成',
        value: Number(orderStats.value.completed || 0),
        unit: '单',
        status: 4,
        accent: currentStatus.value === 4
    }
])

const heroHeadline = computed(() => {
    const pendingConfirm = Number(orderStats.value.pending_confirm || 0)
    const pendingPay = Number(orderStats.value.pending_pay || 0)
    const pendingService = Number(orderStats.value.paid || 0)
    const inService = Number(orderStats.value.in_service || 0)

    if (currentStatus.value === 0) {
        return pendingConfirm > 0
            ? `当前有 ${pendingConfirm} 笔订单等待你确认`
            : '当前筛选下暂无待确认订单'
    }

    if (currentStatus.value === 3) {
        return inService > 0
            ? `正在跟进 ${inService} 笔服务中的订单`
            : '当前没有服务中的订单，可优先处理新排期'
    }

    if (pendingConfirm > 0) {
        return `先确认 ${pendingConfirm} 笔新单，再跟进 ${pendingService} 笔待服务安排`
    }

    if (pendingPay > 0) {
        return `有 ${pendingPay} 笔订单等待支付，留意付款进度与服务节奏`
    }

    return '订单按服务节奏分层展示，优先处理最临近的履约任务'
})

const focusCards = computed(() => [
    {
        key: 'pending-confirm',
        label: '待确认',
        value: Number(orderStats.value.pending_confirm || 0),
        unit: '笔',
        hint: Number(orderStats.value.pending_confirm || 0) > 0 ? '优先确认' : '暂时平稳',
        status: 0 as StatusValue,
        accent: currentStatus.value === 0 || Number(orderStats.value.pending_confirm || 0) > 0
    },
    {
        key: 'pending-pay',
        label: '待支付',
        value: Number(orderStats.value.pending_pay || 0),
        unit: '笔',
        hint: Number(orderStats.value.pending_pay || 0) > 0 ? '关注支付' : '支付顺畅',
        status: 1 as StatusValue,
        accent: currentStatus.value === 1
    },
    {
        key: 'pending-service',
        label: '待服务',
        value: Number(orderStats.value.paid || 0),
        unit: '笔',
        hint: Number(orderStats.value.paid || 0) > 0 ? '准备履约' : '档期充足',
        status: 2 as StatusValue,
        accent: currentStatus.value === 2
    }
])

const resetOrderStats = () => {
    orderStats.value = { ...DEFAULT_ORDER_STATS }
}

const getStatusText = (status: number) => STATUS_TEXT_MAP[status] || '未知'

const getStatusTone = (status: number): StatusTone => STATUS_TONE_MAP[status] || 'neutral'

const formatCountdown = (seconds: number | string | undefined) => {
    const total = Math.max(Number(seconds || 0), 0)
    if (total <= 0) return '已超时，等待系统处理'
    const hours = Math.floor(total / 3600)
    const minutes = Math.floor((total % 3600) / 60)
    const remainSeconds = total % 60

    return [hours, minutes, remainSeconds].map((item) => String(item).padStart(2, '0')).join(':')
}

const buildExpireAt = (
    deadlineTime: number | string | undefined,
    remainSeconds: number | string | undefined
) => {
    if (Number(deadlineTime || 0) <= 0) return 0
    return Date.now() + Math.max(Number(remainSeconds || 0), 0) * 1000
}

const getLiveRemainSeconds = (
    order: FormattedOrder | null | undefined,
    deadlineField: 'confirmExpireAt' | 'payExpireAt',
    status: number
) => {
    orderCountdownNowTs.value
    if (!order) return 0
    if (order.statusValue !== status) return 0
    const expireAt = Number(order?.[deadlineField] || 0)
    if (expireAt <= 0) return 0
    return Math.max(Math.ceil((expireAt - orderCountdownNowTs.value) / 1000), 0)
}

const getConfirmRemainText = (order: FormattedOrder | null | undefined) => {
    if (!order || order.statusValue !== 0 || Number(order.confirmExpireAt || 0) <= 0) return ''
    return formatCountdown(getLiveRemainSeconds(order, 'confirmExpireAt', 0))
}

const getPayRemainText = (order: FormattedOrder | null | undefined) => {
    if (!order || order.statusValue !== 1 || Number(order.payExpireAt || 0) <= 0) return ''
    return formatCountdown(getLiveRemainSeconds(order, 'payExpireAt', 1))
}

const clearOrderCountdown = () => {
    if (orderCountdownTimer) {
        clearInterval(orderCountdownTimer)
        orderCountdownTimer = null
    }
}

const hasActiveOrderCountdown = (order: FormattedOrder) =>
    getLiveRemainSeconds(order, 'confirmExpireAt', 0) > 0 ||
    getLiveRemainSeconds(order, 'payExpireAt', 1) > 0

const hasExpiredOrderCountdown = (order: FormattedOrder) =>
    (order.statusValue === 0 &&
        Number(order.confirmExpireAt || 0) > 0 &&
        getLiveRemainSeconds(order, 'confirmExpireAt', 0) <= 0) ||
    (order.statusValue === 1 &&
        Number(order.payExpireAt || 0) > 0 &&
        getLiveRemainSeconds(order, 'payExpireAt', 1) <= 0)

const refreshOrderCountdownData = async () => {
    if (orderCountdownRefreshing) return
    orderCountdownRefreshing = true
    try {
        await loadOrderStats()
        hasLoaded.value = false
        pagingRef.value?.reload()
    } finally {
        orderCountdownRefreshing = false
    }
}

const startOrderCountdown = () => {
    clearOrderCountdown()
    orderCountdownNowTs.value = Date.now()

    if (orderList.value.some(hasExpiredOrderCountdown)) {
        refreshOrderCountdownData()
        return
    }

    if (!orderList.value.some(hasActiveOrderCountdown)) {
        return
    }

    orderCountdownTimer = setInterval(() => {
        orderCountdownNowTs.value = Date.now()

        if (orderList.value.some(hasExpiredOrderCountdown)) {
            clearOrderCountdown()
            refreshOrderCountdownData()
            return
        }

        if (!orderList.value.some(hasActiveOrderCountdown)) {
            clearOrderCountdown()
        }
    }, 1000)
}

const loadOrderStats = async () => {
    try {
        const data = await staffCenterOrderStats()
        orderStats.value = {
            ...DEFAULT_ORDER_STATS,
            ...(data || {})
        }
    } catch (error: any) {
        resetOrderStats()
        const msg =
            typeof error === 'string' ? error : error?.msg || error?.message || '加载订单统计失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

const formatOrder = (order: any): FormattedOrder => {
    const items = Array.isArray(order.items) ? order.items : []
    const displayItems = items.filter((item: any) =>
        DISPLAY_ITEM_TYPES.includes(Number(item?.item_type ?? 1))
    )
    const orderStatus = Number(order.order_status ?? -1)
    const packageNames = displayItems
        .map((item: any) => item.package_name)
        .filter((name: string) => Boolean(name))
    const serviceDateList = displayItems
        .map((item: any) => item.service_date)
        .filter((date: string) => Boolean(date))
        .sort()
    const pendingConfirmCount = displayItems.filter(
        (item: any) => Number(item.confirm_status ?? 0) === 0 && Number(item.item_status ?? 0) !== 3
    ).length
    const serviceCount = displayItems.length
    return {
        id: Number(order.id || 0),
        orderNo: order.order_sn || '',
        statusValue: orderStatus,
        statusText: order.order_status_desc || getStatusText(orderStatus),
        statusTone: getStatusTone(orderStatus),
        createTimeText: formatDateTime(order.create_time),
        serviceDate: serviceDateList[0] || '',
        location: order.service_address || '服务地址未填写',
        actualPrice: formatMoney(order.pay_amount || 0),
        packageNames,
        serviceCount,
        contactName: order.contact_name || '',
        contactMobile: order.contact_mobile || '',
        pendingConfirmCount,
        confirmRemainSeconds: Number(order.confirm_remain_seconds || 0),
        confirmExpireAt: buildExpireAt(
            Number(order.confirm_deadline_time || 0),
            Number(order.confirm_remain_seconds || 0)
        ),
        confirmTimeoutActionDesc:
            orderStatus === 0 ? String(order.confirm_timeout_action_desc || '').trim() : '',
        payRemainSeconds: Number(order.pay_remain_seconds || 0),
        payExpireAt: buildExpireAt(
            Number(order.pay_deadline_time || 0),
            Number(order.pay_remain_seconds || 0)
        ),
        payTimeoutActionDesc:
            orderStatus === 1 ? String(order.pay_timeout_action_desc || '').trim() : '',
        canConfirm: orderStatus === 0 && pendingConfirmCount > 0,
        canStart: orderStatus === 2 && Number(order?.can_staff_start || 0) === 1,
        canComplete: orderStatus === 3 && Number(order?.can_staff_complete || 0) === 1
    }
}

const getPrimaryActionLabel = (order: FormattedOrder) => {
    if (order.canConfirm) return '确认订单'
    if (order.canStart) return '开始履约'
    if (order.canComplete) return '完成服务'
    return ''
}

const handlePrimaryAction = (order: FormattedOrder) => {
    if (order.canConfirm) return confirmOrder(order)
    if (order.canStart) return startService(order)
    if (order.canComplete) return completeOrder(order)
}

const getOrderAlertText = (order: FormattedOrder) => {
    if (order.statusValue === 0) {
        if (getConfirmRemainText(order)) return `剩余确认时间：${getConfirmRemainText(order)}`
        if (order.confirmTimeoutActionDesc) return `超时处理：${order.confirmTimeoutActionDesc}`
    }

    if (order.statusValue === 1) {
        if (getPayRemainText(order)) return `剩余支付时间：${getPayRemainText(order)}`
        if (order.payTimeoutActionDesc) return `支付超时处理：${order.payTimeoutActionDesc}`
    }

    return ''
}

const getOrderAlertTone = (order: FormattedOrder): StatusTone => {
    if (order.statusValue === 0) return order.confirmTimeoutActionDesc ? 'danger' : 'warning'
    if (order.statusValue === 1) return order.payTimeoutActionDesc ? 'danger' : 'info'
    return 'neutral'
}

const getOrderAlertIconColor = (order: FormattedOrder) => {
    if (order.statusValue === 0) return order.confirmTimeoutActionDesc ? '#5A4433' : '#9F7A2E'
    if (order.statusValue === 1) return order.payTimeoutActionDesc ? '#5A4433' : '#0B0B0B'
    return '#6C665C'
}

const listSectionTitle = computed(() => {
    const currentTab = STATUS_TAB_CONFIG.find((item) => item.value === currentStatus.value)

    if (currentTab && currentTab.value !== '') {
        return `${currentTab.label}订单`
    }

    return '订单列表'
})

const listSectionDesc = computed(() => {
    if (currentStatus.value === 0) return '优先处理确认时效最紧的订单'
    if (currentStatus.value === 1) return '优先跟进支付与确认节点'
    if (currentStatus.value === 2) return '准备即将履约的订单'
    if (currentStatus.value === 3) return '先关注正在履约中的订单'

    return '按履约优先级查看当前订单'
})

const listSectionMeta = computed(() => `${orderList.value.length} 笔`)

const queryList = async (pageNo: number, pageSize: number) => {
    loading.value = true
    clearOrderCountdown()

    try {
        const params: any = { page_size: pageSize }
        if (pageNo > 1) {
            params.page_no = pageNo
        }
        if (currentStatus.value !== '') {
            params.status = currentStatus.value
        }

        const res: any = await staffCenterOrderLists(params)
        const list = Array.isArray(res?.data) ? res.data : []
        pagingRef.value.complete(list.map(formatOrder))
        await nextTick()
        startOrderCountdown()
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
        pagingRef.value.complete(false)
        clearOrderCountdown()
    } finally {
        loading.value = false
        hasLoaded.value = true
    }
}

const switchStatusByValue = (value: StatusValue) => {
    if (currentStatus.value === value) return
    currentStatus.value = value
    hasLoaded.value = false
    pagingRef.value?.reload()
}

const goDetail = (id: number) => {
    uni.navigateTo({ url: `/packages/pages/staff_order_detail/staff_order_detail?id=${id}` })
}

const confirmOrder = (order: FormattedOrder) => {
    uni.showModal({
        title: '确认订单',
        content: '确认后客户可进行支付，是否继续？',
        success: async (res) => {
            if (!res.confirm) return

            try {
                await staffCenterOrderConfirm({ id: order.id })
                uni.showToast({ title: '确认成功', icon: 'success' })
                await loadOrderStats()
                hasLoaded.value = false
                pagingRef.value?.reload()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '确认失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

const completeOrder = (order: FormattedOrder) => {
    uni.showModal({
        title: '完成服务',
        content: '确认本单服务已完成吗？',
        success: async (res) => {
            if (!res.confirm) return

            try {
                await staffCenterOrderComplete({ id: order.id })
                uni.showToast({ title: '操作成功', icon: 'success' })
                await loadOrderStats()
                hasLoaded.value = false
                pagingRef.value?.reload()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '操作失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

const startService = (order: FormattedOrder) => {
    uni.showModal({
        title: '开始履约',
        content: '确认本单已开始履约吗？',
        success: async (res) => {
            if (!res.confirm) return

            try {
                await staffCenterOrderStartService({ id: order.id })
                uni.showToast({ title: '开始履约成功', icon: 'success' })
                await loadOrderStats()
                hasLoaded.value = false
                pagingRef.value?.reload()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '操作失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

const formatMoney = (value: number | string) => {
    const amount = Number(value || 0)
    return Number.isInteger(amount) ? String(amount) : amount.toFixed(2)
}

const formatDateTime = (value: any) => {
    if (!value) return ''

    let date: Date

    if (typeof value === 'number') {
        date = new Date(value < 1e12 ? value * 1000 : value)
    } else {
        date = new Date(String(value).replace(/-/g, '/'))
    }

    if (Number.isNaN(date.getTime())) return String(value)

    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hour = String(date.getHours()).padStart(2, '0')
    const minute = String(date.getMinutes()).padStart(2, '0')

    return `${year}-${month}-${day} ${hour}:${minute}`
}

const initStatusByQuery = (status: string | number | undefined) => {
    if (status === undefined || status === null || status === '') {
        currentStatus.value = ''
        return
    }

    const parsed = Number(status)
    const existed = STATUS_TAB_CONFIG.some((item) => item.value === parsed)
    currentStatus.value = existed ? parsed : ''
}

onLoad((options) => {
    initStatusByQuery(options?.status)
})

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    await loadOrderStats()
    hasLoaded.value = false
    pagingRef.value?.reload()
})

onHide(() => {
    clearOrderCountdown()
})

onUnload(() => {
    clearOrderCountdown()
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    padding-top: 20rpx;
    box-sizing: border-box;
    background: radial-gradient(
            circle at top left,
            rgba(11, 11, 11, 0.1) 0,
            rgba(248, 247, 242, 0) 36%
        ),
        linear-gradient(180deg, var(--wm-color-bg-page, #ffffff) 0%, #f8f7f2 100%);
}

.page-section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    box-sizing: border-box;

    &--top {
        padding-top: 20rpx;
    }

    &--list {
        padding-top: 18rpx;
        padding-bottom: calc(48rpx + env(safe-area-inset-bottom));
    }
}

.hero-card,
.order-card {
    background: rgba(255, 255, 255, 0.98);
    border: 1rpx solid rgba(231, 226, 214, 0.98);
    box-shadow: 0 20rpx 42rpx rgba(17, 17, 17, 0.12);
}

.hero-card {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    overflow: hidden;
}

.hero-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.hero-card__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.hero-card__eyebrow {
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-color-primary, #0b0b0b);
}

.hero-card__title {
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1.28;
    color: var(--wm-text-primary, #111111);
}

.hero-card__desc {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.hero-card__total {
    flex-shrink: 0;
    min-width: 132rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4rpx;
    padding: 18rpx 20rpx;
    border-radius: 30rpx;
    text-align: center;
    background: rgba(255, 255, 255, 0.74);
    border: 1rpx solid rgba(255, 255, 255, 0.8);
}

.hero-card__total-label {
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-secondary, #5f5a50);
}

.hero-card__total-value {
    font-size: 42rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #111111);
}

.hero-card__total-unit {
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-tertiary, #9a9388);
}

.hero-focus-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
    margin-top: 18rpx;
}

.hero-focus-card {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    min-width: 0;
    padding: 18rpx 20rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.7);
    border: 1rpx solid rgba(255, 255, 255, 0.82);
    box-sizing: border-box;

    &--accent {
        background: rgba(247, 240, 223, 0.92);
        border-color: var(--wm-color-border-strong, #d8c28a);
    }

    &__label {
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1.3;
        color: var(--wm-text-secondary, #5f5a50);
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
        color: var(--wm-text-primary, #111111);
    }

    &__unit {
        padding-bottom: 4rpx;
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__hint {
        font-size: 18rpx;
        font-weight: 600;
        line-height: 1.45;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.hero-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10rpx;
}

.summary-chip {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    min-width: 0;
    padding: 14rpx 16rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.72);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);

    &--accent {
        background: var(--wm-color-primary-soft, #f3f2ee);
        border-color: var(--wm-color-border-strong, #d8c28a);
    }
}

.summary-chip__label {
    font-size: 19rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-secondary, #5f5a50);
}

.summary-chip__value-row {
    display: flex;
    align-items: flex-end;
    gap: 4rpx;
}

.summary-chip__value {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #111111);
}

.summary-chip__unit {
    font-size: 18rpx;
    font-weight: 600;
    line-height: 1.2;
    color: var(--wm-text-secondary, #5f5a50);
}

.summary-bar__scope {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 0 4rpx;
}

.summary-bar__scope-text {
    font-size: 20rpx;
    font-weight: 600;
    line-height: 1.4;
    color: var(--wm-text-secondary, #5f5a50);
}

.summary-tabs {
    white-space: nowrap;
}

.summary-tabs__row {
    display: inline-flex;
    gap: 12rpx;
    padding-bottom: 2rpx;
}

.section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
    padding: 0 10rpx;
}

.section-head__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.section-head__title {
    font-size: 32rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
}

.section-head__desc {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.section-head__meta {
    flex-shrink: 0;
    font-size: 22rpx;
    font-weight: 700;
    line-height: 1.4;
    color: var(--wm-color-primary, #0b0b0b);
}

.order-card + .order-card {
    margin-top: 18rpx;
}

.order-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.order-card__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.order-card__sn {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
    word-break: break-all;
}

.order-card__time {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.45;
    color: var(--wm-text-secondary, #5f5a50);
}

.order-card__status {
    flex-shrink: 0;
}

.order-card__date-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 14rpx;
}

.order-card__date {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
}

.order-card__line {
    display: flex;
    align-items: flex-start;
    gap: 10rpx;
    font-size: 23rpx;
    font-weight: 600;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);

    &--primary {
        color: var(--wm-text-primary, #111111);
    }
}

.order-card__line + .order-card__line {
    margin-top: 10rpx;
}

.order-card__line text:last-child {
    flex: 1;
    min-width: 0;
}

.service-tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48rpx;
    padding: 0 16rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
    color: var(--wm-text-secondary, #5f5a50);

    &--muted {
        color: var(--wm-text-tertiary, #9a9388);
    }
}

.order-card__alert {
    display: flex;
    align-items: center;
    gap: 8rpx;
    margin-top: 16rpx;
    padding: 14rpx 16rpx;
    border-radius: 24rpx;
    font-size: 21rpx;
    font-weight: 600;
    line-height: 1.45;

    &--warning {
        background: rgba(247, 240, 223, 0.92);
        color: var(--wm-color-warning, #9f7a2e);
    }

    &--info {
        background: rgba(247, 240, 223, 0.9);
        color: var(--wm-color-primary, #0b0b0b);
    }

    &--danger {
        background: rgba(90, 68, 51, 0.08);
        color: var(--wm-color-danger, #5a4433);
    }
}

.order-card__alert-text {
    flex: 1;
    min-width: 0;
}

.order-card__meta-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 18rpx;
}

.order-card__foot {
    display: flex;
    justify-content: flex-end;
    margin-top: 22rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.order-card__actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.action-btn {
    min-width: 156rpx;
    height: 72rpx;
    padding: 0 28rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1;
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.92;
    }

    &--primary {
        margin-left: auto;
        border: none;
        box-shadow: 0 18rpx 36rpx rgba(11, 11, 11, 0.18);
    }
}

.action-link {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    min-height: 56rpx;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
    color: var(--wm-text-secondary, #5f5a50);
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.92;
    }
}
</style>
