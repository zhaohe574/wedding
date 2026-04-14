<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasTabbar>
        <BaseNavbar title="我的订单" />
        <view class="order-page">
            <view class="order-page__summary">
                <text class="order-page__summary-title">按订单状态快速查看服务进度</text>
                <text class="order-page__summary-desc"
                    >待确认、待支付、待服务等关键节点会自动同步到这里。</text
                >
            </view>
            <scroll-view scroll-x class="order-page__filter-scroll" :show-scrollbar="false">
                <view class="order-page__filter-row">
                    <view
                        v-for="(tab, index) in statusTabs"
                        :key="tab.key"
                        class="order-page__filter-chip"
                        :class="{ 'order-page__filter-chip--active': currentTabIndex === index }"
                        @click="currentTabIndex = index"
                    >
                        <text class="order-page__filter-chip-text">{{ tab.label }}</text>
                        <view v-if="statistics[tab.key] > 0" class="order-page__filter-chip-count">
                            <text class="order-page__filter-chip-count-text">
                                {{ statistics[tab.key] }}
                            </text>
                        </view>
                    </view>
                </view>
            </scroll-view>

            <view class="order-page__content">
                <view v-if="loading && orders.length === 0" class="loading-state">
                    <LoadingState text="订单加载中..." />
                </view>

                <view v-else-if="orders.length === 0" class="empty-state">
                    <EmptyState
                        title="当前筛选下还没有订单"
                        description="继续去挑选服务人员，新的预约会第一时间同步到这里。"
                        action-text="去预约"
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
                                    <text class="order-card__title">{{ order.serviceTitle }}</text>
                                    <text class="order-card__summary">{{
                                        order.displaySummary
                                    }}</text>
                                </view>
                            </view>
                            <view class="order-card__status-row">
                                <view
                                    class="order-card__status"
                                    :style="getStatusStyle(order.statusValue)"
                                >
                                    <text class="order-card__status-text">
                                        {{ order.statusText }}
                                    </text>
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
                                <text class="order-card__order-no">订单号 {{ order.orderNo }}</text>
                                <view class="order-card__amount-row">
                                    <text class="order-card__amount-label">实付</text>
                                    <text class="order-card__amount">¥{{ order.actualPrice }}</text>
                                </view>
                            </view>

                            <view
                                v-if="order.actions.length"
                                class="order-card__actions"
                                @click.stop
                            >
                                <view
                                    v-for="(action, index) in order.actions"
                                    :key="`${order.id}-${index}`"
                                    class="order-card__action"
                                    :class="{
                                        'order-card__action--primary': action.type === 'primary'
                                    }"
                                    @click="handleCardAction(action, order)"
                                >
                                    <text
                                        class="order-card__action-text"
                                        :class="{
                                            'order-card__action-text--primary':
                                                action.type === 'primary'
                                        }"
                                    >
                                        {{ action.text }}
                                    </text>
                                </view>
                            </view>

                            <view v-else class="order-card__link" @click.stop="goDetail(order.id)">
                                <text class="order-card__link-text">查看详情</text>
                            </view>
                        </view>
                    </view>

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
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import { useThemeStore } from '@/stores/theme'
import {
    cancelOrder,
    confirmOrder,
    deleteOrder,
    getOrderList,
    getOrderStatistics
} from '@/api/order'

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
]

const currentTabIndex = ref(0)
const currentStatus = computed(() => statusTabs[currentTabIndex.value].value)
const orders = ref<any[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)
const orderCountdownNowTs = ref(Date.now())
let orderCountdownTimer: ReturnType<typeof setInterval> | null = null
let orderCountdownRefreshing = false
const statistics = reactive<any>({
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

const resolvePaymentChannel = (order: any) => {
    const paymentChannel = Number(order?.payment_channel || 0)
    if ([1, 2].includes(paymentChannel)) {
        return paymentChannel
    }
    return Number(order?.pay_type) === 4 || !!order?.pay_voucher ? 2 : 1
}

const buildActions = (status: number, order: any) => {
    if (status === 0) {
        return [{ text: '取消', type: 'secondary', action: 'cancel' }]
    }
    if (status === 1) {
        const paymentChannel = resolvePaymentChannel(order)
        if (paymentChannel === 2) {
            return [
                { text: '取消', type: 'secondary', action: 'cancel' },
                {
                    text: Number(order?.pay_voucher_status) === 0 ? '凭证审核中' : '上传凭证',
                    type: 'primary',
                    action: 'voucher'
                }
            ]
        }
        const payLabel = order?.need_pay_label || '支付'
        return [
            { text: '取消', type: 'secondary', action: 'cancel' },
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

const getStaffAvatar = (item: any) => {
    if (item.staff && item.staff.avatar) {
        return item.staff.avatar
    }
    return '/static/images/user/default_avatar.png'
}

const getOrderPrimaryTitle = (items: Array<any>) => {
    const primaryItem = items[0] || {}
    const packageName = String(primaryItem.packageName || '').trim()
    const staffName = String(primaryItem.staffName || '').trim()

    if (packageName && staffName) {
        return `${packageName}｜${staffName}`
    }

    return packageName || staffName || '婚礼服务订单'
}

const getOrderMetaText = (locationText: string, items: Array<any>) => {
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

const formatCountdown = (seconds: number | string | undefined) => {
    const total = Math.max(Number(seconds || 0), 0)
    if (total <= 0) return '已超时，等待系统处理'
    const hours = Math.floor(total / 3600)
    const minutes = Math.floor((total % 3600) / 60)
    const remainSeconds = total % 60

    return [hours, minutes, remainSeconds].map((item) => String(item).padStart(2, '0')).join(':')
}

const shouldShowConfirmCountdown = (order: any) =>
    Number(order?.statusValue ?? order?.order_status ?? -1) === 0 &&
    Number(order?.confirmDeadlineTime ?? order?.confirm_deadline_time ?? 0) > 0

const buildExpireAt = (
    deadlineTime: number | string | undefined,
    remainSeconds: number | string | undefined
) => {
    if (Number(deadlineTime || 0) <= 0) return 0
    return Date.now() + Math.max(Number(remainSeconds || 0), 0) * 1000
}

const getLiveRemainSeconds = (
    order: any,
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

const getConfirmRemainText = (order: any) => {
    if (!shouldShowConfirmCountdown(order)) return ''
    return formatCountdown(getLiveRemainSeconds(order, 'confirmDeadlineTime', 'confirmExpireAt'))
}

const shouldShowConfirmSection = (order: any) =>
    !!getConfirmRemainText(order) ||
    (Number(order?.statusValue ?? order?.order_status ?? -1) === 0 &&
        !!String(
            order?.confirmTimeoutActionDesc || order?.confirm_timeout_action_desc || ''
        ).trim())

const shouldShowPayCountdown = (order: any) =>
    Number(order?.statusValue ?? order?.order_status ?? -1) === 1 &&
    Number(order?.payDeadlineTime ?? order?.pay_deadline_time ?? 0) > 0

const getPayRemainText = (order: any) => {
    if (!shouldShowPayCountdown(order)) return ''
    return formatCountdown(getLiveRemainSeconds(order, 'payDeadlineTime', 'payExpireAt'))
}

const shouldShowPaySection = (order: any) =>
    !!getPayRemainText(order) ||
    (Number(order?.statusValue ?? order?.order_status ?? -1) === 1 &&
        !!String(order?.payTimeoutActionDesc || order?.pay_timeout_action_desc || '').trim())

const clearOrderCountdown = () => {
    if (orderCountdownTimer) {
        clearInterval(orderCountdownTimer)
        orderCountdownTimer = null
    }
}

const hasActiveOrderCountdown = (order: any) =>
    getLiveRemainSeconds(order, 'confirmDeadlineTime', 'confirmExpireAt') > 0 ||
    getLiveRemainSeconds(order, 'payDeadlineTime', 'payExpireAt') > 0

const hasExpiredOrderCountdown = (order: any) =>
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

        const params: any = { page: page.value, page_size: 10 }
        if (currentStatus.value !== '') {
            params.status = currentStatus.value
        }

        const res = await getOrderList(params)
        const dataList = Array.isArray(res?.data) ? res.data : []
        const list = dataList.map((order: any) => {
            const locationText = [order.service_region_text, order.service_address]
                .map((item: any) => String(item || '').trim())
                .filter(Boolean)
                .join(' · ')
            const items = (order.items || []).map((item: any) => ({
                id: item.id,
                staffId: item.staff_id,
                staffName: item.staff_name,
                staffAvatar: getStaffAvatar(item),
                packageName: item.package_name,
                serviceDate: item.service_date
            }))
            const serviceDateList = items
                .map((item: any) => String(item.serviceDate || '').trim())
                .filter(Boolean)
                .sort()

            return {
                id: order.id,
                orderNo: order.order_sn,
                statusValue: Number(order.order_status || 0),
                statusText:
                    order.order_status_desc || getStatusText(Number(order.order_status || 0)),
                actualPrice: Number(order.need_pay_amount || order.pay_amount || 0),
                totalPrice: Number(order.pay_amount || 0),
                paymentChannel: resolvePaymentChannel(order),
                paymentChannelDesc:
                    order.payment_channel_desc ||
                    (resolvePaymentChannel(order) === 2 ? '线下支付' : '线上支付'),
                payVoucherStatus: Number(order.pay_voucher_status ?? -1),
                payVoucher: order.pay_voucher || '',
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
                displaySummary: buildDisplaySummary(
                    serviceDateList[0] || '待安排服务日期',
                    [
                        order.payment_channel_desc ||
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

const handleCardAction = (action: { action: string }, order: any) => {
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

const handleCancel = async (orderId: number) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要取消该订单吗？'
    })
    if (res.confirm) {
        try {
            await cancelOrder({ id: orderId, reason: '用户取消' })
            uni.showToast({ title: '订单已取消' })
            fetchOrders(true)
            fetchStatistics()
        } catch (error: any) {
            uni.showToast({ title: error.message || '操作失败', icon: 'none' })
        }
    }
}

const handleConfirm = async (orderId: number) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定服务已完成吗？'
    })
    if (res.confirm) {
        try {
            await confirmOrder({ id: orderId })
            uni.showToast({ title: '操作成功' })
            fetchOrders(true)
            fetchStatistics()
        } catch (error: any) {
            uni.showToast({ title: error.message || '操作失败', icon: 'none' })
        }
    }
}

const handleDelete = async (orderId: number) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要删除该订单吗？'
    })
    if (res.confirm) {
        try {
            await deleteOrder({ id: orderId })
            uni.showToast({ title: '删除成功' })
            fetchOrders(true)
            fetchStatistics()
        } catch (error: any) {
            uni.showToast({ title: error.message || '操作失败', icon: 'none' })
        }
    }
}

const getStatusStyle = (status: number) => {
    const styles: Record<number, Record<string, string>> = {
        0: {
            color: '#E85A4F',
            background: 'rgba(232, 90, 79, 0.12)',
            border: '1rpx solid rgba(232, 90, 79, 0.14)'
        },
        1: {
            color: '#C98524',
            background: 'rgba(201, 133, 36, 0.12)',
            border: '1rpx solid rgba(201, 133, 36, 0.14)'
        },
        2: {
            color: '#2F7D58',
            background: 'rgba(47, 125, 88, 0.12)',
            border: '1rpx solid rgba(47, 125, 88, 0.14)'
        },
        3: {
            color: '#6A92E6',
            background: 'rgba(106, 146, 230, 0.12)',
            border: '1rpx solid rgba(106, 146, 230, 0.16)'
        },
        4: {
            color: '#7F7B78',
            background: 'rgba(127, 123, 120, 0.12)',
            border: '1rpx solid rgba(127, 123, 120, 0.14)'
        },
        5: {
            color: '#2F7D58',
            background: 'rgba(47, 125, 88, 0.12)',
            border: '1rpx solid rgba(47, 125, 88, 0.14)'
        },
        6: {
            color: '#B4ACA8',
            background: 'rgba(180, 172, 168, 0.14)',
            border: '1rpx solid rgba(180, 172, 168, 0.16)'
        },
        7: {
            color: '#C98524',
            background: 'rgba(201, 133, 36, 0.12)',
            border: '1rpx solid rgba(201, 133, 36, 0.14)'
        },
        10: {
            color: '#0F766E',
            background: 'rgba(15, 118, 110, 0.12)',
            border: '1rpx solid rgba(15, 118, 110, 0.14)'
        },
        8: {
            color: '#B44A3A',
            background: 'rgba(180, 74, 58, 0.12)',
            border: '1rpx solid rgba(180, 74, 58, 0.14)'
        }
    }

    return styles[status] || styles[0]
}

watch(currentTabIndex, () => {
    fetchOrders(true)
})

onLoad((options: any) => {
    $theme.setScene('consumer')
    if (options.status !== undefined) {
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
    background: var(--wm-color-page, #fcfbf9);

    &__summary {
        display: flex;
        flex-direction: column;
        gap: 10rpx;
        padding: 18rpx 37rpx 6rpx;
    }

    &__summary-title {
        font-size: 30rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #1e2432);
    }

    &__summary-desc {
        font-size: 24rpx;
        line-height: 1.6;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__filter-scroll {
        margin-top: 22rpx;
        padding: 0 37rpx;
        white-space: nowrap;
    }

    &__filter-row {
        display: inline-flex;
        gap: 22rpx;
        padding-bottom: 15rpx;
    }

    &__filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        min-height: 82rpx;
        padding: 0 30rpx;
        border-radius: 999rpx;
        border: 1rpx solid var(--wm-color-border, #efe6e1);
        background: rgba(255, 255, 255, 0.84);
        color: var(--wm-text-secondary, #7f7b78);
        box-sizing: border-box;

        &--active {
            border-color: var(--wm-color-primary, #e85a4f);
            background: var(--wm-color-primary, #e85a4f);
            color: #ffffff;
            box-shadow: 0 8rpx 18rpx rgba(232, 90, 79, 0.14);
        }
    }

    &__filter-chip-text {
        font-size: 28rpx;
        font-weight: 600;
        line-height: 1;
    }

    &__filter-chip-count {
        min-width: 36rpx;
        height: 36rpx;
        padding: 0 10rpx;
        border-radius: 999rpx;
        background: rgba(232, 90, 79, 0.08);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
    }

    &__filter-chip-count-text {
        font-size: 20rpx;
        line-height: 1;
        color: currentColor;
    }

    &__filter-chip--active &__filter-chip-count {
        background: rgba(255, 255, 255, 0.18);
    }

    &__content {
        padding: 22rpx 37rpx 45rpx;
    }
}

.loading-state {
    min-height: 56vh;
}

.loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16rpx;
}

.loading-text {
    font-size: 26rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.empty-state {
    min-height: 56vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-icon-wrapper {
    width: 220rpx;
    height: 220rpx;
    margin-bottom: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-title {
    font-size: 34rpx;
    font-weight: 600;
    color: var(--wm-text-primary, #1e2432);
    margin-bottom: 16rpx;
    text-align: center;
}

.empty-subtitle {
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
    margin-bottom: 40rpx;
    text-align: center;
}

.empty-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 280rpx;
    min-height: 88rpx;
    padding: 0 36rpx;
    border-radius: 999rpx;
    box-shadow: 0 12rpx 24rpx rgba(232, 90, 79, 0.16);

    &:active {
        transform: translateY(1rpx);
    }
}

.empty-action-text {
    font-size: 30rpx;
    font-weight: 700;
}

.order-list {
    display: flex;
    flex-direction: column;
    gap: 30rpx;
}

.order-card {
    padding: 34rpx 37rpx;
    border-radius: 45rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.88);
    box-shadow: 0 8rpx 18rpx rgba(214, 185, 167, 0.08);

    &:active {
        transform: translateY(-2rpx);
    }
}

.order-card__body {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.order-card__hero {
    display: flex;
    align-items: center;
    gap: 18rpx;
}

.order-card__avatar {
    width: 92rpx;
    height: 92rpx;
    border-radius: 50%;
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.86);
    border: 2rpx solid rgba(255, 255, 255, 0.92);
    box-shadow: 0 10rpx 20rpx rgba(214, 185, 167, 0.16);
}

.order-card__hero-main {
    min-width: 0;
    flex: 1;
}

.order-card__title {
    display: block;
    font-size: 30rpx;
    font-weight: 600;
    line-height: 1.6;
    color: var(--wm-text-primary, #1e2432);
}

.order-card__summary {
    display: block;
    font-size: 28rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.order-card__status-row {
    display: flex;
    align-items: center;
}

.order-card__confirm {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 18rpx 22rpx;
    border-radius: 28rpx;
    background: rgba(255, 241, 238, 0.72);
    border: 1rpx solid rgba(244, 199, 191, 0.88);
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
    color: var(--wm-text-secondary, #7f7b78);
}

.order-card__confirm-value {
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1.5;
    text-align: right;
    color: var(--wm-color-primary, #e85a4f);
}

.order-card__status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48rpx;
    padding: 0 24rpx;
    border-radius: 999rpx;
    box-sizing: border-box;
}

.order-card__status-text {
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1;
}

.order-card__foot {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24rpx;
    margin-top: 28rpx;
}

.order-card__amount-wrap {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    min-width: 0;
}

.order-card__order-no {
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-tertiary, #b4aca8);
}

.order-card__amount-row {
    display: flex;
    align-items: baseline;
    gap: 10rpx;
    flex-wrap: wrap;
}

.order-card__amount-label {
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
    line-height: 1;
}

.order-card__amount {
    font-size: 34rpx;
    font-weight: 600;
    line-height: 1;
    color: var(--wm-color-primary, #e85a4f);
}

.order-card__actions {
    display: inline-flex;
    align-items: center;
    gap: 16rpx;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.order-card__action {
    min-width: 128rpx;
    height: 82rpx;
    padding: 0 30rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.82);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}

.order-card__action--primary {
    border-color: var(--wm-color-primary, #e85a4f);
    background: var(--wm-color-primary, #e85a4f);
}

.order-card__action-text {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-primary, #1e2432);
    line-height: 1;
}

.order-card__link {
    min-width: 128rpx;
    height: 82rpx;
    padding: 0 30rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.82);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    box-sizing: border-box;
}

.order-card__action-text--primary {
    color: #ffffff;
}

.order-card__link-text {
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1;
    color: var(--wm-text-primary, #1e2432);
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
    color: var(--wm-text-secondary, #7f7b78);
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
</style>
