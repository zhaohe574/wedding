<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasTabbar>
        <BaseNavbar
            title="我的订单"
            variant="solid"
            bg-color="#181614"
            text-color="#FFFDF8"
        />

        <view class="order-page">
            <!-- 顶部高定风格胶囊分类切换 -->
            <view class="order-page__top">
                <scroll-view scroll-x class="order-filter-scroll" :show-scrollbar="false" enhanced :bounces="true">
                    <view class="order-filter-track">
                        <view
                            v-for="(tab, index) in statusTabs"
                            :key="tab.key"
                            class="order-filter-pill"
                            :class="{ 'order-filter-pill--active': currentTabIndex === index }"
                            @click="currentTabIndex = index"
                        >
                            <text class="order-filter-pill__label">{{ tab.label }}</text>
                            <text
                                v-if="statistics[tab.key] > 0"
                                class="order-filter-pill__badge"
                            >
                                {{ statistics[tab.key] > 99 ? '99+' : statistics[tab.key] }}
                            </text>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <!-- 订单列表主体内容 -->
            <view class="order-page__body wm-page-content">
                <view v-if="loading && orders.length === 0" class="loading-state">
                    <LoadingState text="订单加载中..." />
                </view>

                <view v-else-if="orders.length === 0" class="empty-state">
                    <EmptyState
                        :title="emptyTitle"
                        :description="emptyDesc"
                        action-text="去选人预约"
                        @action="goHome"
                    />
                </view>

                <view v-else class="order-list">
                    <view
                        v-for="order in orders"
                        :key="order.id"
                        class="order-card"
                        @click="goDetail(order.id)"
                    >
                        <!-- 卡片头部：服务人员名片 + 订单状态 -->
                        <view class="order-card__head">
                            <view class="order-card__staff">
                                <view class="order-card__avatar-ring">
                                    <image
                                        class="order-card__avatar"
                                        :src="order.items[0]?.staffAvatar || '/static/images/user/default_avatar.png'"
                                        mode="aspectFill"
                                    />
                                </view>
                                <view class="order-card__staff-info">
                                    <view class="order-card__title-row">
                                        <text class="order-card__title">{{ order.serviceTitle }}</text>
                                    </view>
                                    <view class="order-card__meta-chips">
                                        <view v-if="order.serviceDateText" class="order-card__chip">
                                            <BaseIcon name="calendar" size="20" color="#C6A15B" />
                                            <text class="order-card__chip-text">{{ order.serviceDateText }}</text>
                                        </view>
                                        <view v-if="order.serviceMeta" class="order-card__chip">
                                            <BaseIcon name="location" size="20" color="#C6A15B" />
                                            <text class="order-card__chip-text">{{ order.serviceMeta }}</text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <view class="order-card__status-badge">
                                <StatusBadge
                                    :tone="getStatusTone(order.statusValue)"
                                    size="sm"
                                    dot
                                >
                                    {{ order.statusText }}
                                </StatusBadge>
                            </view>
                        </view>

                        <!-- 服务方案小结条 -->
                        <view class="order-card__summary-bar">
                            <text class="order-card__summary-text">{{ order.displaySummary }}</text>
                            <text class="order-card__items-count">共 {{ order.items.length }} 项服务</text>
                        </view>

                        <!-- 倒计时警示胶囊条（待确认） -->
                        <view
                            v-if="shouldShowConfirmSection(order)"
                            class="order-card__countdown-banner order-card__countdown-banner--confirm"
                        >
                            <BaseIcon name="clock" size="24" color="#9A6B35" />
                            <view class="order-card__countdown-content">
                                <text v-if="getConfirmRemainText(order)" class="order-card__countdown-time">
                                    剩余确认时间 {{ getConfirmRemainText(order) }}
                                </text>
                                <text v-if="order.confirmTimeoutActionDesc" class="order-card__countdown-desc">
                                    ({{ order.confirmTimeoutActionDesc }})
                                </text>
                            </view>
                        </view>

                        <!-- 倒计时警示胶囊条（待支付） -->
                        <view
                            v-if="shouldShowPaySection(order)"
                            class="order-card__countdown-banner order-card__countdown-banner--pay"
                        >
                            <BaseIcon name="clock" size="24" color="#B84A39" />
                            <view class="order-card__countdown-content">
                                <text v-if="getPayRemainText(order)" class="order-card__countdown-time">
                                    剩余支付时间 {{ getPayRemainText(order) }}
                                </text>
                                <text v-if="order.payTimeoutActionDesc" class="order-card__countdown-desc">
                                    ({{ order.payTimeoutActionDesc }})
                                </text>
                            </view>
                        </view>

                        <!-- 卡片底栏：单号、金额排版与操作按钮组 -->
                        <view class="order-card__foot">
                            <view class="order-card__info-row">
                                <view class="order-card__sn-box" @click.stop="copyOrderNo(order.orderNo)">
                                    <text class="order-card__sn-text">{{ formatOrderNo(order.orderNo) }}</text>
                                    <BaseIcon name="copy" size="22" color="#8C8273" />
                                </view>
                                <view class="order-card__price-wrap">
                                    <text class="order-card__price-label">{{ isBalancePendingPayment(order) ? '待付尾款' : '实付' }}</text>
                                    <view class="order-card__price-figure">
                                        <text class="order-card__price-symbol">¥</text>
                                        <text class="order-card__price-int">{{ formatPriceParts(isBalancePendingPayment(order) ? (order.totalPrice - order.actualPrice) : (order.actualPrice || order.totalPrice)).integer }}</text>
                                        <text class="order-card__price-dec">{{ formatPriceParts(isBalancePendingPayment(order) ? (order.totalPrice - order.actualPrice) : (order.actualPrice || order.totalPrice)).decimal }}</text>
                                    </view>
                                    <text
                                        v-if="order.totalPrice > 0 && order.actualPrice > 0 && order.actualPrice !== order.totalPrice && !isBalancePendingPayment(order)"
                                        class="order-card__total-price"
                                    >
                                        (应付 ¥{{ formatAmount(order.totalPrice) }})
                                    </text>
                                </view>
                            </view>

                            <view class="order-card__actions" @click.stop>
                                <view
                                    v-for="(action, index) in order.actions"
                                    :key="`${order.id}-${index}`"
                                    class="order-card__action-btn"
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
                                <view class="order-card__action-btn" @click.stop="goDetail(order.id)">
                                    <BaseButton
                                        label="查看详情"
                                        variant="light"
                                        size="sm"
                                        icon="right"
                                        icon-position="right"
                                        height="64rpx"
                                        font-size="23rpx"
                                    />
                                </view>
                            </view>
                        </view>
                    </view>

                    <!-- 分页触底状态 -->
                    <view v-if="hasMore" class="load-more">
                        <view v-if="loading" class="load-more-loading">
                            <tn-loading size="36" mode="flower" color="#C6A15B" />
                            <text class="load-more-text">加载中...</text>
                        </view>
                        <text v-else class="load-more-clickable" @click="loadMore">
                            点击加载更多
                        </text>
                    </view>

                    <view v-else-if="orders.length > 0" class="load-more-done">
                        <view class="load-more-line"></view>
                        <text class="load-more-done-text">✦ 愿每一场婚礼皆得圆满 ✦</text>
                        <view class="load-more-line"></view>
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
import BaseIcon from '@/components/base/BaseIcon.vue'
import EmptyState from '@/components/base/EmptyState.vue'
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
        return `${packageName} · ${staffName}`
    }

    return packageName || staffName || '婚礼定制服务'
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
            .join(' · ') || '待安排婚礼服务档期'
    )
}

const formatAmount = (amount: number | string) => {
    return Number(amount || 0).toFixed(2)
}

const formatPriceParts = (amount: number | string) => {
    const num = Math.max(Number(amount || 0), 0)
    const fixed = num.toFixed(2)
    const [integer, decimal] = fixed.split('.')
    return { symbol: '¥', integer: integer || '0', decimal: `.${decimal || '00'}` }
}

const formatOrderNo = (orderNo: string) => {
    const value = String(orderNo || '').trim()
    return value ? `订单号: ${value}` : '订单号 --'
}

const copyOrderNo = (orderNo: string) => {
    const value = String(orderNo || '').trim()
    if (!value) return
    uni.setClipboardData({
        data: value,
        success: () => showSuccess('已复制订单号')
    })
}

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
    uni.navigateTo({ url: `/packages/pages/order_detail/order_detail?id=${orderId}` })
}

const emptyTitle = computed(() => {
    if (!currentTab.value || currentTab.value.value === '') return '您还没有预约过婚礼服务'
    return `暂无${currentTab.value.label}的订单`
})

const emptyDesc = computed(() => {
    if (!currentTab.value || currentTab.value.value === '') return '挑选心仪的服务团队，开启专属婚礼方案定制。'
    return '如有新的婚礼需求，可随时查询档期并预约团队。'
})

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
    uni.navigateTo({ url: `/packages/pages/order_detail/order_detail?id=${orderId}&action=pay` })
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
    min-height: 100vh;
    background:
        radial-gradient(ellipse at 50% 0%, rgba(217, 190, 130, 0.12) 0%, rgba(248, 246, 240, 0) 65%),
        var(--wm-color-bg-page, #F8F6F0);
    box-sizing: border-box;

    &__top {
        position: sticky;
        top: 0;
        z-index: 20;
        background: rgba(248, 246, 240, 0.94);
        backdrop-filter: blur(20px);
        border-bottom: 1rpx solid rgba(217, 190, 130, 0.32);
        box-shadow: 0 8rpx 24rpx rgba(24, 22, 20, 0.03);
    }

    &__body {
        padding: 24rpx var(--wm-space-page-x, 28rpx) calc(44rpx + env(safe-area-inset-bottom));
        display: flex;
        flex-direction: column;
        gap: 24rpx;
        box-sizing: border-box;
    }
}

/* 顶部高定分段胶囊选择器 */
.order-filter-scroll {
    width: 100%;
    white-space: nowrap;
}

.order-filter-track {
    display: inline-flex;
    align-items: center;
    gap: 14rpx;
    padding: 16rpx var(--wm-space-page-x, 28rpx);
    box-sizing: border-box;
}

.order-filter-pill {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    height: 64rpx;
    padding: 0 28rpx;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.88);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    box-shadow: 0 4rpx 12rpx rgba(24, 22, 20, 0.03);
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    box-sizing: border-box;

    &__label {
        font-size: 24rpx;
        font-weight: 600;
        color: var(--wm-color-text-secondary, #5E564B);
        transition: color 0.2s ease;
    }

    &__badge {
        font-size: 18rpx;
        font-weight: 700;
        line-height: 1;
        padding: 4rpx 10rpx;
        border-radius: 999rpx;
        background: rgba(198, 161, 91, 0.2);
        color: var(--wm-color-gold, #C6A15B);
    }

    &:active {
        transform: scale(0.96);
    }

    &--active {
        background: var(--wm-color-primary, #181614);
        border-color: var(--wm-color-primary, #181614);
        box-shadow: 0 8rpx 20rpx rgba(24, 22, 20, 0.2);

        .order-filter-pill__label {
            color: var(--wm-text-inverse, #FFFDF8);
            font-weight: 700;
        }

        .order-filter-pill__badge {
            background: var(--wm-color-gold, #C6A15B);
            color: #181614;
        }
    }
}

/* 状态容器 */
.loading-state,
.empty-state {
    min-height: 58vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 订单卡片列表 */
.order-list {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

.order-card {
    position: relative;
    padding: 30rpx;
    border-radius: 32rpx;
    background: linear-gradient(180deg, #FFFFFF 0%, #FAF8F5 100%);
    border: 1rpx solid rgba(217, 190, 130, 0.4);
    box-shadow: 0 12rpx 32rpx rgba(24, 22, 20, 0.05);
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    box-sizing: border-box;
    transition: transform 0.18s ease, box-shadow 0.18s ease;

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 6rpx 16rpx rgba(24, 22, 20, 0.07);
    }

    /* 头部：人员名片 + 状态徽章 */
    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__staff {
        display: flex;
        align-items: center;
        gap: 18rpx;
        min-width: 0;
        flex: 1;
    }

    &__avatar-ring {
        position: relative;
        width: 88rpx;
        height: 88rpx;
        border-radius: 50%;
        padding: 3rpx;
        box-sizing: border-box;
        background: linear-gradient(135deg, #D9BE82 0%, #FAF6EE 100%);
        box-shadow: 0 6rpx 14rpx rgba(24, 22, 20, 0.08);
        flex-shrink: 0;
    }

    &__avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #FFFFFF;
        display: block;
    }

    &__staff-info {
        display: flex;
        flex-direction: column;
        gap: 8rpx;
        min-width: 0;
        flex: 1;
    }

    &__title-row {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    &__title {
        font-size: 30rpx;
        font-weight: 800;
        line-height: 1.35;
        color: var(--wm-color-primary, #181614);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__meta-chips {
        display: flex;
        align-items: center;
        gap: 12rpx;
        flex-wrap: wrap;
    }

    &__chip {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        padding: 4rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(242, 236, 225, 0.7);
        border: 1rpx solid rgba(217, 190, 130, 0.3);
    }

    &__chip-text {
        font-size: 20rpx;
        font-weight: 600;
        color: var(--wm-color-text-secondary, #5E564B);
        max-width: 260rpx;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__status-badge {
        flex-shrink: 0;
    }

    /* 方案小结摘要条 */
    &__summary-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        padding: 14rpx 20rpx;
        border-radius: 18rpx;
        background: rgba(250, 246, 238, 0.85);
        border: 1rpx solid rgba(231, 224, 211, 0.9);
        box-sizing: border-box;
    }

    &__summary-text {
        font-size: 23rpx;
        color: var(--wm-color-text-secondary, #5E564B);
        line-height: 1.4;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }

    &__items-count {
        font-size: 21rpx;
        font-weight: 700;
        color: var(--wm-color-gold, #C6A15B);
        flex-shrink: 0;
    }

    /* 倒计时警示胶囊条 */
    &__countdown-banner {
        display: flex;
        align-items: center;
        gap: 12rpx;
        padding: 14rpx 20rpx;
        border-radius: 18rpx;
        box-sizing: border-box;

        &--confirm {
            background: rgba(217, 190, 130, 0.16);
            border: 1rpx solid rgba(216, 194, 138, 0.75);
        }

        &--pay {
            background: rgba(184, 74, 57, 0.08);
            border: 1rpx solid rgba(184, 74, 57, 0.3);
        }
    }

    &__countdown-content {
        display: flex;
        align-items: center;
        gap: 8rpx;
        flex-wrap: wrap;
        flex: 1;
    }

    &__countdown-time {
        font-size: 23rpx;
        font-weight: 700;
        line-height: 1.4;
        color: var(--wm-color-primary, #181614);
    }

    &__countdown-desc {
        font-size: 21rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
    }

    /* 卡片底栏 */
    &__foot {
        padding-top: 18rpx;
        border-top: 1rpx solid rgba(231, 224, 211, 0.7);
        display: flex;
        flex-direction: column;
        gap: 18rpx;
    }

    &__info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__sn-box {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        padding: 6rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(242, 236, 225, 0.5);
        border: 1rpx solid rgba(217, 190, 130, 0.25);
        max-width: 320rpx;

        &:active {
            opacity: 0.7;
        }
    }

    &__sn-text {
        font-size: 20rpx;
        font-family: monospace;
        color: var(--wm-color-text-tertiary, #8C8273);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__price-wrap {
        display: inline-flex;
        align-items: baseline;
        gap: 6rpx;
    }

    &__price-label {
        font-size: 22rpx;
        font-weight: 600;
        color: var(--wm-color-text-secondary, #5E564B);
    }

    &__price-figure {
        display: inline-flex;
        align-items: baseline;
    }

    &__price-symbol {
        font-size: 22rpx;
        font-weight: 800;
        color: var(--wm-color-primary, #181614);
    }

    &__price-int {
        font-size: 36rpx;
        font-weight: 900;
        color: var(--wm-color-primary, #181614);
        letter-spacing: -0.5rpx;
    }

    &__price-dec {
        font-size: 24rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #181614);
    }

    &__total-price {
        font-size: 21rpx;
        color: var(--wm-color-text-tertiary, #8C8273);
        margin-left: 6rpx;
    }

    &__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 14rpx;
        flex-wrap: wrap;
    }

    &__action-btn {
        flex-shrink: 0;
    }
}

/* 分页状态 */
.load-more {
    padding: 36rpx 0 20rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    &-loading {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    &-text {
        font-size: 24rpx;
        color: var(--wm-color-text-secondary, #5E564B);
    }

    &-clickable {
        font-size: 24rpx;
        font-weight: 600;
        padding: 12rpx 28rpx;
        border-radius: 999rpx;
        background: rgba(255, 255, 255, 0.9);
        border: 1rpx solid rgba(217, 190, 130, 0.5);
        color: var(--wm-color-gold, #C6A15B);

        &:active {
            opacity: 0.7;
            transform: translateY(1rpx);
        }
    }
}

.load-more-done {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20rpx;
    padding: 40rpx 20rpx 24rpx;
}

.load-more-line {
    flex: 1;
    max-width: 120rpx;
    height: 1rpx;
    background: linear-gradient(90deg, transparent, rgba(217, 190, 130, 0.6), transparent);
}

.load-more-done-text {
    font-size: 22rpx;
    font-weight: 500;
    color: var(--wm-color-text-tertiary, #8C8273);
    letter-spacing: 2rpx;
}

@media screen and (max-width: 360px) {
    .order-card {
        padding: 24rpx;

        &__head {
            align-items: flex-start;
        }

        &__avatar-ring {
            width: 76rpx;
            height: 76rpx;
        }

        &__title {
            font-size: 28rpx;
        }

        &__info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 12rpx;
        }

        &__actions {
            width: 100%;
            justify-content: flex-end;
        }
    }
}
</style>
