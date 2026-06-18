<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasTabbar>
        <BaseNavbar
            title="我的订单"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />
        <view class="order-page">
            <view class="order-page__top wm-page-content">
                <view class="order-filter-shell">
                    <scroll-view scroll-x class="order-page__filter-scroll" :show-scrollbar="false">
                        <view class="order-page__filter-row">
                            <view
                                v-for="(tab, index) in statusTabs"
                                :key="tab.key"
                                class="order-page__filter-item"
                                @click="currentTabIndex = index"
                            >
                                <FilterChip
                                    :label="tab.label"
                                    :selected="currentTabIndex === index"
                                    :badge="statistics[tab.key] > 0 ? statistics[tab.key] : ''"
                                />
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view class="order-page__content wm-page-content">
                <view v-if="loading && orders.length === 0" class="loading-state">
                    <LoadingState text="订单加载中..." />
                </view>

                <view v-else-if="orders.length === 0" class="empty-state">
                    <EmptyState
                        title="当前筛选下还没有订单"
                        action-text="去预约"
                        @action="goHome"
                    />
                </view>

                <view v-else class="order-list">
                    <BaseCard
                        v-for="order in orders"
                        :key="order.id"
                        class="order-card"
                        variant="list"
                        border-radius="34rpx"
                        background="linear-gradient(180deg, rgba(255, 253, 248, 0.99) 0%, #FFFDF8 100%)"
                        border="1rpx solid rgba(216, 201, 173, 0.9)"
                        box-shadow="0 18rpx 44rpx rgba(74, 43, 24, 0.08)"
                    >
                        <view class="order-card__body">
                            <view class="order-card__hero">
                                <image
                                    class="order-card__avatar"
                                    :src="
                                        order.items[0]?.staffAvatar ||
                                        '/static/images/user/default_avatar.png'
                                    "
                                    mode="aspectFill"
                                />
                                <view class="order-card__hero-main">
                                    <view class="order-card__title-row">
                                        <text class="order-card__title">{{ order.serviceTitle }}</text>
                                        <StatusBadge
                                            :tone="getStatusTone(order.statusValue)"
                                            size="sm"
                                            dot
                                        >
                                            {{ order.statusText }}
                                        </StatusBadge>
                                    </view>
                                    <text class="order-card__summary">{{
                                        order.displaySummary
                                    }}</text>
                                </view>
                            </view>
                            <view class="order-card__details">
                                <view
                                    v-for="detail in getOrderDetails(order)"
                                    :key="detail.label"
                                    class="order-card__detail-item"
                                    :class="{ 'order-card__detail-item--wide': detail.wide }"
                                >
                                    <view class="order-card__detail-icon">
                                        <BaseIcon
                                            :name="detail.icon"
                                            size="22"
                                            color="var(--wm-color-gold, #B8954A)"
                                        />
                                    </view>
                                    <view class="order-card__detail-main">
                                        <text class="order-card__detail-label">{{ detail.label }}</text>
                                        <text class="order-card__detail-value">{{ detail.value }}</text>
                                    </view>
                                </view>
                            </view>
                            <view
                                v-if="shouldShowConfirmSection(order)"
                                class="order-card__confirm"
                            >
                                <view
                                    v-if="getConfirmRemainText(order)"
                                    class="order-card__confirm-item"
                                >
                                    <text class="order-card__confirm-label">剩余确认时间</text>
                                    <text class="order-card__confirm-value">
                                        {{ getConfirmRemainText(order) }}
                                    </text>
                                </view>
                                <view
                                    v-if="order.confirmTimeoutActionDesc"
                                    class="order-card__confirm-item"
                                >
                                    <text class="order-card__confirm-label">超时处理</text>
                                    <text class="order-card__confirm-value">
                                        {{ order.confirmTimeoutActionDesc }}
                                    </text>
                                </view>
                            </view>
                            <view v-if="shouldShowPaySection(order)" class="order-card__confirm">
                                <view
                                    v-if="getPayRemainText(order)"
                                    class="order-card__confirm-item"
                                >
                                    <text class="order-card__confirm-label">剩余支付时间</text>
                                    <text class="order-card__confirm-value">
                                        {{ getPayRemainText(order) }}
                                    </text>
                                </view>
                                <view
                                    v-if="order.payTimeoutActionDesc"
                                    class="order-card__confirm-item"
                                >
                                    <text class="order-card__confirm-label">支付超时处理</text>
                                    <text class="order-card__confirm-value">
                                        {{ order.payTimeoutActionDesc }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view class="order-card__foot">
                            <view class="order-card__amount-wrap">
                                <text class="order-card__order-no">{{ formatOrderNo(order.orderNo) }}</text>
                                <view class="order-card__amount-row">
                                    <text class="order-card__amount-label">实付</text>
                                    <text class="order-card__amount">{{ formatAmount(order.actualPrice) }}</text>
                                </view>
                                <view class="order-card__amount-row order-card__amount-row--sub">
                                    <text class="order-card__amount-label">应付</text>
                                    <text class="order-card__amount-sub">{{ formatAmount(order.totalPrice) }}</text>
                                </view>
                            </view>

                            <view class="order-card__actions">
                                <view
                                    v-for="(action, index) in order.actions"
                                    :key="`${order.id}-${index}`"
                                    class="order-card__action-item"
                                    @click.stop="handleCardAction(action, order)"
                                >
                                    <BaseButton
                                        :label="action.text"
                                        :variant="action.type === 'primary' ? 'dark' : 'light'"
                                        size="sm"
                                        height="64rpx"
                                        font-size="23rpx"
                                    />
                                </view>

                                <view class="order-card__action-item order-card__action-item--detail">
                                    <BaseButton
                                        label="查看详情"
                                        variant="light"
                                        size="sm"
                                        icon="right"
                                        icon-position="right"
                                        height="64rpx"
                                        font-size="23rpx"
                                        @click.stop="goDetail(order.id)"
                                    />
                                </view>
                            </view>
                        </view>
                    </BaseCard>

                    <view v-if="hasMore" class="load-more">
                        <view v-if="loading" class="load-more-loading">
                            <tn-loading size="36" mode="flower" :color="$theme.primaryColor" />
                            <text class="load-more-text">加载中...</text>
                        </view>
                        <text
                            v-else
                            class="load-more-text load-more-clickable"
                            :style="{ color: $theme.primaryColor }"
                            @click="loadMore"
                        >
                            加载更多
                        </text>
                    </view>

                    <view v-else class="load-more">
                        <text class="load-more-text">没有更多了</text>
                    </view>
                </view>
            </view>
            <tabbar />
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { onHide, onLoad, onReachBottom, onShow, onUnload } from '@dcloudio/uni-app'
import PageShell from '@/components/base/PageShell.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import FilterChip from '@/components/base/FilterChip.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import {
    cancelOrder,
    confirmOrder,
    deleteOrder,
    getOrderList,
    getOrderStatistics
} from '@/api/order'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'
import { resolvePaymentChannel, shouldUseOfflineCollection } from '@/utils/paymentChannel'
import type {
    OrderApiItem,
    OrderApiItemLine,
    OrderCardAction,
    OrderListParams,
    OrderListViewItem,
    OrderStatistics
} from '@/types/order'

const $theme = useThemeStore()

const statusTabs = [
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
    { label: '已退款', value: 8, key: 'refund' }
] as const

const currentTabIndex = ref(0)
const currentTab = computed(() => statusTabs[currentTabIndex.value] || statusTabs[0])
const currentStatus = computed(() => currentTab.value.value)
const orders = ref<OrderListViewItem[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)
const orderCountdownNowTs = ref(Date.now())
let orderCountdownTimer: ReturnType<typeof setInterval> | null = null
let orderCountdownRefreshing = false
const statistics = reactive<OrderStatistics>({
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
    refund: 0
})

const isApiBalancePendingPayment = (order?: OrderApiItem) => {
    return (
        Number(order?.order_status || -1) === 1 &&
        (order?.need_pay === 'balance' ||
            (Number(order?.deposit_amount || 0) > 0 &&
                Number(order?.deposit_paid || 0) === 1 &&
                Number(order?.balance_paid || 0) === 0 &&
                Number(order?.balance_amount || 0) > 0))
    )
}

const isBalancePendingPayment = (order?: OrderListViewItem) => {
    return Boolean(order?.isBalancePendingPayment)
}

const canUseOfflineCollection = (order: OrderApiItem) => {
    return (
        shouldUseOfflineCollection(order) &&
        Number(order?.order_status || -1) === 1 &&
        Number(order?.need_pay_amount || 0) > 0
    )
}

const buildActions = (status: number, order: OrderApiItem): OrderCardAction[] => {
    if (status === 0) {
        return [{ text: '取消', type: 'secondary', action: 'cancel' }]
    }
    if (status === 1) {
        const cancelAction: OrderCardAction[] = isApiBalancePendingPayment(order)
            ? []
            : [{ text: '取消', type: 'secondary', action: 'cancel' }]
        if (canUseOfflineCollection(order)) {
            return [
                ...cancelAction,
                {
                    text: '联系顾问',
                    type: 'primary',
                    action: 'contact'
                }
            ]
        }
        const payLabel = order?.need_pay_label || '支付'
        return [
            ...cancelAction,
            { text: payLabel, type: 'primary', action: 'pay' }
        ]
    }
    if (status === 3 && Number(order?.can_user_complete || 0) === 1) {
        return [{ text: '确认完成', type: 'primary', action: 'confirm' }]
    }
    if ([4, 5, 6, 8].includes(status)) {
        return [{ text: '删除', type: 'secondary', action: 'delete' }]
    }
    return []
}

const getStaffAvatar = (item: OrderApiItemLine) => {
    if (item.staff && item.staff.avatar) {
        return item.staff.avatar
    }
    return '/static/images/user/default_avatar.png'
}

const getOrderPrimaryTitle = (items: OrderListViewItem['items']) => {
    const primaryItem = items[0] || {}
    const packageName = String(primaryItem.packageName || '').trim()
    const staffName = String(primaryItem.staffName || '').trim()

    if (packageName && staffName) {
        return `${packageName}｜${staffName}`
    }

    return packageName || staffName || '服务订单'
}

const getOrderMetaText = (locationText: string, items: OrderListViewItem['items']) => {
    const primaryItem = items[0] || {}
    const packageName = String(primaryItem.packageName || '').trim()
    return locationText || packageName || ''
}

const getStatusText = (status: number) => {
    const texts: Record<number, string> = {
        0: '待确认',
        1: '待支付',
        2: '待服务',
        3: '服务中',
        4: '已完成',
        5: '已评价',
        6: '已取消',
        7: '已暂停',
        10: '退款中',
        8: '已退款'
    }

    return texts[status] || '订单状态'
}

const buildDisplaySummary = (serviceDateText: string, serviceMeta: string) => {
    return (
        [serviceDateText, serviceMeta]
            .map((item) => String(item || '').trim())
            .filter(Boolean)
            .join(' · ') || '待安排服务信息'
    )
}

const formatAmount = (amount: number | string) => {
    return `¥${Number(amount || 0).toFixed(2)}`
}

const formatOrderNo = (orderNo: string) => {
    const value = String(orderNo || '').trim()
    return value ? `订单号 ${value}` : '订单号 --'
}

const formatServiceCount = (count: number) => {
    return `${Math.max(Number(count || 0), 0)}项`
}

const buildPaymentText = (order: OrderListViewItem) =>
    [order.paymentChannelDesc, order.paymentModeDesc]
        .map((item) => String(item || '').trim())
        .filter(Boolean)
        .join(' · ') || '待确认'

const getOrderDetails = (order: OrderListViewItem) => [
    {
        label: '服务时间',
        value: order.serviceDateText || '待安排',
        icon: 'calendar'
    },
    {
        label: '服务数量',
        value: formatServiceCount(order.items.length),
        icon: 'team'
    },
    {
        label: '支付安排',
        value: buildPaymentText(order),
        icon: 'wallet',
        wide: true
    },
    {
        label: '服务信息',
        value: order.serviceMeta || '待完善',
        icon: 'location',
        wide: true
    }
]

const formatCountdown = (seconds: number | string | undefined) => {
    const total = Math.max(Number(seconds || 0), 0)
    if (total <= 0) return '已超时，等待系统处理'
    const hours = Math.floor(total / 3600)
    const minutes = Math.floor((total % 3600) / 60)
    const remainSeconds = total % 60

    return [hours, minutes, remainSeconds].map((item) => String(item).padStart(2, '0')).join(':')
}

const shouldShowConfirmCountdown = (order: OrderListViewItem) =>
    Number(order.statusValue) === 0 && Number(order.confirmDeadlineTime) > 0

const buildExpireAt = (
    deadlineTime: number | string | undefined,
    remainSeconds: number | string | undefined
) => {
    if (Number(deadlineTime || 0) <= 0) return 0
    return Date.now() + Math.max(Number(remainSeconds || 0), 0) * 1000
}

const getLiveRemainSeconds = (
    order: OrderListViewItem,
    deadlineField: 'confirmDeadlineTime' | 'payDeadlineTime',
    expireField: 'confirmExpireAt' | 'payExpireAt'
) => {
    orderCountdownNowTs.value
    if (!order) return 0
    const deadlineTime = Number(order?.[deadlineField] || 0)
    if (deadlineTime <= 0) return 0
    const expireAt = Number(order?.[expireField] || 0)
    if (expireAt <= 0) return 0
    return Math.max(Math.ceil((expireAt - orderCountdownNowTs.value) / 1000), 0)
}

const getConfirmRemainText = (order: OrderListViewItem) => {
    if (!shouldShowConfirmCountdown(order)) return ''
    return formatCountdown(getLiveRemainSeconds(order, 'confirmDeadlineTime', 'confirmExpireAt'))
}

const shouldShowConfirmSection = (order: OrderListViewItem) =>
    !!getConfirmRemainText(order) ||
    (Number(order.statusValue) === 0 && !!String(order.confirmTimeoutActionDesc || '').trim())

const shouldShowPayCountdown = (order: OrderListViewItem) =>
    Number(order.statusValue) === 1 && Number(order.payDeadlineTime) > 0

const getPayRemainText = (order: OrderListViewItem) => {
    if (!shouldShowPayCountdown(order)) return ''
    return formatCountdown(getLiveRemainSeconds(order, 'payDeadlineTime', 'payExpireAt'))
}

const shouldShowPaySection = (order: OrderListViewItem) =>
    !!getPayRemainText(order) ||
    (Number(order.statusValue) === 1 && !!String(order.payTimeoutActionDesc || '').trim())

const clearOrderCountdown = () => {
    if (orderCountdownTimer) {
        clearInterval(orderCountdownTimer)
        orderCountdownTimer = null
    }
}

const hasActiveOrderCountdown = (order: OrderListViewItem) =>
    getLiveRemainSeconds(order, 'confirmDeadlineTime', 'confirmExpireAt') > 0 ||
    getLiveRemainSeconds(order, 'payDeadlineTime', 'payExpireAt') > 0

const hasExpiredOrderCountdown = (order: OrderListViewItem) =>
    (shouldShowConfirmCountdown(order) &&
        getLiveRemainSeconds(order, 'confirmDeadlineTime', 'confirmExpireAt') <= 0) ||
    (shouldShowPayCountdown(order) &&
        getLiveRemainSeconds(order, 'payDeadlineTime', 'payExpireAt') <= 0)

const refreshOrderCountdownData = async () => {
    if (orderCountdownRefreshing) return
    orderCountdownRefreshing = true
    try {
        await fetchOrders(true)
        await fetchStatistics()
    } finally {
        orderCountdownRefreshing = false
    }
}

const startOrderCountdown = () => {
    clearOrderCountdown()
    orderCountdownNowTs.value = Date.now()

    if (orders.value.some(hasExpiredOrderCountdown)) {
        refreshOrderCountdownData()
        return
    }

    if (!orders.value.some(hasActiveOrderCountdown)) {
        return
    }

    orderCountdownTimer = setInterval(() => {
        orderCountdownNowTs.value = Date.now()

        if (orders.value.some(hasExpiredOrderCountdown)) {
            clearOrderCountdown()
            refreshOrderCountdownData()
            return
        }

        if (!orders.value.some(hasActiveOrderCountdown)) {
            clearOrderCountdown()
        }
    }, 1000)
}

const fetchOrders = async (refresh = false) => {
    if (loading.value) return
    loading.value = true
    clearOrderCountdown()

    try {
        if (refresh) {
            page.value = 1
            orders.value = []
        }

        const params: OrderListParams = { page: page.value, page_size: 10 }
        if (currentStatus.value !== '') {
            params.status = currentStatus.value
        }

        const res = await getOrderList(params)
        const dataList = Array.isArray(res?.data) ? res.data : []
        const list: OrderListViewItem[] = dataList.map((order: OrderApiItem) => {
            const locationText = [order.service_region_text, order.service_address]
                .map((item) => String(item || '').trim())
                .filter(Boolean)
                .join(' · ')
            const items: OrderListViewItem['items'] = (order.items || []).map((item) => ({
                id: item.id,
                staffId: item.staff_id,
                staffName: item.staff_name,
                staffAvatar: getStaffAvatar(item),
                packageName: item.package_name,
                serviceDate: item.service_date
            }))
            const serviceDateList = items
                .map((item) => String(item.serviceDate || '').trim())
                .filter(Boolean)
                .sort()

            return {
                id: Number(order.id || 0),
                orderNo: String(order.order_sn || ''),
                statusValue: Number(order.order_status || 0),
                statusText:
                    order.order_status_desc || getStatusText(Number(order.order_status || 0)),
                actualPrice: Number(order.paid_amount || 0),
                totalPrice: Number(order.pay_amount || 0),
                paymentChannel: resolvePaymentChannel(order),
                paymentChannelDesc: shouldUseOfflineCollection(order)
                    ? '线下支付'
                    : order.payment_channel_desc ||
                      (resolvePaymentChannel(order) === 2 ? '线下支付' : '线上支付'),
                payVoucherStatus: Number(order.pay_voucher_status ?? -1),
                payVoucher: order.pay_voucher || '',
                offlineCollectionEnabled: Number(
                    order.offline_collection_available ?? order.offline_collection_enabled ?? 0
                ),
                paymentModeDesc: order.payment_mode_desc || '全款支付',
                serviceTitle: getOrderPrimaryTitle(items),
                serviceMeta: getOrderMetaText(locationText, items),
                serviceDateText: serviceDateList[0] || '待安排服务日期',
                confirmDeadlineTime: Number(order.confirm_deadline_time || 0),
                confirmRemainSeconds: Number(order.confirm_remain_seconds || 0),
                confirmExpireAt: buildExpireAt(
                    Number(order.confirm_deadline_time || 0),
                    Number(order.confirm_remain_seconds || 0)
                ),
                confirmTimeoutActionDesc: String(order.confirm_timeout_action_desc || '').trim(),
                payDeadlineTime: Number(order.pay_deadline_time || 0),
                payRemainSeconds: Number(order.pay_remain_seconds || 0),
                payExpireAt: buildExpireAt(
                    Number(order.pay_deadline_time || 0),
                    Number(order.pay_remain_seconds || 0)
                ),
                payTimeoutActionDesc: String(order.pay_timeout_action_desc || '').trim(),
                isBalancePendingPayment: isApiBalancePendingPayment(order),
                displaySummary: buildDisplaySummary(
                    serviceDateList[0] || '待安排服务日期',
                    [
                        shouldUseOfflineCollection(order)
                            ? '线下支付'
                            : order.payment_channel_desc ||
                              (resolvePaymentChannel(order) === 2 ? '线下支付' : '线上支付'),
                        order.payment_mode_desc || '',
                        getOrderMetaText(locationText, items)
                    ]
                        .filter(Boolean)
                        .join(' · ')
                ),
                items,
                actions: buildActions(Number(order.order_status || 0), order)
            }
        })

        if (refresh) {
            orders.value = list
        } else {
            orders.value.push(...list)
        }

        const totalPage = Number(res?.last_page || 1)
        hasMore.value = page.value < totalPage
    } catch (error) {
        console.error(error)
    } finally {
        loading.value = false
        startOrderCountdown()
    }
}

const fetchStatistics = async () => {
    try {
        const res = await getOrderStatistics()
        Object.assign(statistics, res)
    } catch (error) {
        console.error(error)
    }
}

const loadMore = () => {
    if (hasMore.value && !loading.value) {
        page.value += 1
        fetchOrders()
    }
}

const goDetail = (orderId: number) => {
    uni.navigateTo({ url: `/pages/order_detail/order_detail?id=${orderId}` })
}

const goHome = () => {
    uni.navigateTo({ url: '/pages/schedule_query/schedule_query' })
}

const handleCardAction = (action: OrderCardAction, order: OrderListViewItem) => {
    switch (action.action) {
        case 'pay':
            handlePay(order.id)
            break
        case 'cancel':
            handleCancel(order.id)
            break
        case 'confirm':
            handleConfirm(order.id)
            break
        case 'voucher':
            goDetail(order.id)
            break
        case 'contact':
            handleContact(order.id)
            break
        case 'detail':
            goDetail(order.id)
            break
        case 'delete':
            handleDelete(order.id)
            break
        default:
            goDetail(order.id)
            break
    }
}

const handlePay = (orderId: number) => {
    uni.navigateTo({ url: `/pages/order_detail/order_detail?id=${orderId}&action=pay` })
}

const handleContact = (orderId: number) => {
    uni.navigateTo({
        url: `/packages/pages/customer_service/customer_service?scene=order_detail&order_id=${orderId}`
    })
}

const handleCancel = async (orderId: number) => {
    const targetOrder = orders.value.find((item) => Number(item.id) === Number(orderId))
    if (isBalancePendingPayment(targetOrder)) {
        showError('服务已完成，待支付尾款，订单不可取消')
        return
    }

    const confirmed = await confirmModal({
        title: '提示',
        content: '确定要取消该订单吗？'
    })
    if (confirmed) {
        try {
            await cancelOrder({ id: orderId, reason: '用户取消' })
            showSuccess('订单已取消')
            fetchOrders(true)
            fetchStatistics()
        } catch (error: unknown) {
            showError(error)
        }
    }
}

const handleConfirm = async (orderId: number) => {
    const confirmed = await confirmModal({
        title: '提示',
        content: '确定服务已完成吗？'
    })
    if (confirmed) {
        try {
            await confirmOrder({ id: orderId })
            showSuccess('操作成功')
            fetchOrders(true)
            fetchStatistics()
        } catch (error: unknown) {
            showError(error)
        }
    }
}

const handleDelete = async (orderId: number) => {
    const targetOrder = orders.value.find((item) => Number(item.id) === Number(orderId))
    if (isBalancePendingPayment(targetOrder)) {
        showError('服务已完成，待支付尾款，订单不可删除')
        return
    }

    const confirmed = await confirmModal({
        title: '提示',
        content: '确定要删除该订单吗？'
    })
    if (confirmed) {
        try {
            await deleteOrder({ id: orderId })
            showSuccess('删除成功')
            fetchOrders(true)
            fetchStatistics()
        } catch (error: unknown) {
            showError(error)
        }
    }
}

const getStatusTone = (status: number) => {
    const toneMap: Record<number, 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'paid' | 'running' | 'pending'> = {
        0: 'pending',
        1: 'warning',
        2: 'paid',
        3: 'running',
        4: 'success',
        5: 'success',
        6: 'neutral',
        7: 'warning',
        10: 'info',
        8: 'danger'
    }

    return toneMap[status] || 'neutral'
}

watch(currentTabIndex, () => {
    fetchOrders(true)
})

onLoad((options?: { status?: string | number }) => {
    $theme.setScene('consumer')
    if (options?.status !== undefined) {
        const statusMap: Record<string, number> = {
            pending_confirm: 0,
            pending_pay: 1,
            paid: 2,
            in_service: 3,
            completed: 4,
            reviewed: 5,
            cancelled: 6,
            paused: 7,
            refunding: 10,
            refund: 8
        }

        let statusValue: number | string = options.status
        if (typeof statusValue === 'string' && statusMap[statusValue] !== undefined) {
            statusValue = statusMap[statusValue]
        } else {
            statusValue = Number(statusValue)
        }

        const index = statusTabs.findIndex((tab) => tab.value === statusValue)
        if (index !== -1) {
            currentTabIndex.value = index
        }
    }
})

onShow(() => {
    $theme.setScene('consumer')
    fetchOrders(true)
    fetchStatistics()
})

onHide(() => {
    clearOrderCountdown()
})

onUnload(() => {
    clearOrderCountdown()
})

onReachBottom(() => {
    loadMore()
})
</script>

<style lang="scss" scoped>
.order-page {
    min-height: 100%;
    background:
        linear-gradient(180deg, rgba(25, 23, 19, 0.1) 0, rgba(255, 253, 248, 0) 320rpx),
        var(--wm-color-page, #f8f5ef);

    &__top {
        display: flex;
        flex-direction: column;
        gap: 18rpx;
        padding-top: 20rpx;
        padding-bottom: 0;
    }

    &__filter-scroll {
        white-space: nowrap;
    }

    &__filter-row {
        display: inline-flex;
        gap: 14rpx;
        padding: 10rpx 2rpx;
    }

    &__filter-item {
        display: inline-flex;
    }

    &__content {
        padding-top: 18rpx;
        padding-bottom: calc(45rpx + env(safe-area-inset-bottom));
    }
}

.order-filter-shell {
    padding: 4rpx 12rpx;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.88);
    border: 1rpx solid rgba(216, 201, 173, 0.72);
    box-shadow: 0 16rpx 36rpx rgba(74, 43, 24, 0.08);
}

.loading-state {
    min-height: 56vh;
}

.empty-state {
    min-height: 56vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.order-list {
    display: flex;
    flex-direction: column;
    gap: 22rpx;
}

.order-card {
    display: block;
    overflow: visible;
}

.order-card__body {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.order-card__hero {
    display: flex;
    align-items: center;
    gap: 18rpx;
}

.order-card__avatar {
    width: 96rpx;
    height: 96rpx;
    border-radius: 50%;
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.86);
    border: 4rpx solid rgba(255, 253, 248, 0.96);
    box-shadow: 0 10rpx 22rpx rgba(74, 43, 24, 0.12);
}

.order-card__hero-main {
    min-width: 0;
    flex: 1;
}

.order-card__title-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
    min-width: 0;
}

.order-card__title {
    flex: 1;
    min-width: 0;
    font-size: 30rpx;
    font-weight: 800;
    line-height: 1.35;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.order-card__summary {
    display: block;
    margin-top: 10rpx;
    font-size: 28rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #5f5a50);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.order-card__details {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
    padding: 16rpx;
    border-radius: 24rpx;
    background: linear-gradient(180deg, rgba(248, 242, 228, 0.68) 0%, rgba(255, 253, 248, 0.76) 100%);
    border: 1rpx solid rgba(216, 201, 173, 0.74);
}

.order-card__detail-item {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 12rpx;
    padding: 12rpx 14rpx;
    border-radius: 18rpx;
    background: rgba(255, 253, 248, 0.74);
    box-sizing: border-box;
}

.order-card__detail-item--wide {
    grid-column: 1 / -1;
}

.order-card__detail-icon {
    width: 44rpx;
    height: 44rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(217, 190, 130, 0.18);
}

.order-card__detail-main {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.order-card__detail-label {
    font-size: 20rpx;
    line-height: 1.2;
    color: var(--wm-text-tertiary, #9a9388);
}

.order-card__detail-value {
    max-width: 100%;
    font-size: 24rpx;
    line-height: 1.35;
    font-weight: 700;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.order-card__confirm {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 18rpx 22rpx;
    border-radius: 22rpx;
    background: rgba(217, 190, 130, 0.14);
    border: 1rpx solid rgba(216, 194, 138, 0.78);
}

.order-card__confirm-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.order-card__confirm-label {
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.order-card__confirm-value {
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1.5;
    text-align: right;
    color: var(--wm-color-primary, #191713);
}

.order-card__foot {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24rpx;
    margin-top: 24rpx;
    padding-top: 24rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.66);
}

.order-card__amount-wrap {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    min-width: 0;
    flex: 1;
}

.order-card__order-no {
    max-width: 330rpx;
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-tertiary, #9a9388);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.order-card__amount-row {
    display: flex;
    align-items: baseline;
    gap: 10rpx;
    flex-wrap: wrap;
}

.order-card__amount-row--sub {
    margin-top: 4rpx;
}

.order-card__amount-label {
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #9a9388);
    line-height: 1;
}

.order-card__amount {
    font-size: 34rpx;
    font-weight: 900;
    line-height: 1;
    color: var(--wm-color-price, var(--wm-color-primary, #191713));
}

.order-card__amount-sub {
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-secondary, #5f5a50);
}

.order-card__actions {
    display: flex;
    align-items: center;
    gap: 12rpx;
    flex-wrap: wrap;
    justify-content: flex-end;
    max-width: 360rpx;
    box-sizing: border-box;
}

.order-card__action-item {
    flex-shrink: 0;
}

.load-more {
    padding: 45rpx 0 30rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.load-more-loading {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.load-more-text {
    font-size: 26rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.load-more-clickable {
    font-weight: 600;
    padding: 14rpx 28rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.84);

    &:active {
        opacity: 0.7;
        transform: translateY(1rpx);
    }
}

@media screen and (max-width: 360px) {
    .order-card__hero {
        align-items: flex-start;
    }

    .order-card__avatar {
        width: 78rpx;
        height: 78rpx;
    }

    .order-card__title-row {
        flex-direction: column;
        gap: 10rpx;
    }

    .order-card__title {
        max-width: 100%;
    }

    .order-card__details {
        grid-template-columns: 1fr;
        padding: 14rpx;
    }

    .order-card__foot {
        align-items: flex-start;
        flex-direction: column;
        gap: 18rpx;
    }

    .order-card__order-no {
        max-width: 100%;
    }

    .order-card__actions {
        width: 100%;
        max-width: none;
        justify-content: flex-end;
    }

}
</style>
