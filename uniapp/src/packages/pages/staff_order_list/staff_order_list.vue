<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" tone="workspace">
        <BaseNavbar
            title="订单管理"
            title-align="center"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

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
                    <view class="order-board">
                        <BaseCard variant="panel" scene="staff" class="filter-panel">
                            <StaffFilterBar
                                :items="statusTabs"
                                :model-value="currentStatus"
                                @select="handleStatusFilterSelect"
                            />
                        </BaseCard>
                    </view>
                </template>

                <view class="page-section page-section--list">
                    <StaffSectionHeader
                        :title="listSectionTitle"
                        :meta="listSectionMeta"
                    />

                    <LoadingState v-if="loading && !hasLoaded" text="订单加载中" />

                    <template v-else-if="orderList.length">
                        <view
                            v-for="order in orderList"
                            :key="order.id"
                            class="order-card-wrap"
                        >
                            <BaseCard
                                variant="glass"
                                scene="staff"
                                class="order-card"
                            >
                                <view class="order-card__main">
                                    <view class="order-card__head">
                                        <view class="order-card__copy">
                                            <text class="order-card__title">{{
                                                order.primaryPackageName || '订单服务'
                                            }}</text>
                                            <text class="order-card__sn">订单号 {{ order.orderNo }}</text>
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
                                        <BaseIcon
                                            name="clock"
                                            size="18"
                                            :color="getOrderAlertIconColor(order)"
                                        />
                                        <text class="order-card__alert-text">{{
                                            getOrderAlertText(order)
                                        }}</text>
                                    </view>

                                    <view class="order-card__info-panel">
                                        <BaseInfoRow
                                            label="服务日期"
                                            :value="order.serviceDate || '待安排'"
                                        />
                                        <BaseInfoRow label="服务地址" :value="order.location">
                                            <template #value>
                                                <text class="order-card__address-value">
                                                    {{ order.location }}
                                                </text>
                                            </template>
                                        </BaseInfoRow>
                                        <BaseInfoRow label="联系人" :value="order.contactText" />
                                        <BaseInfoRow
                                            label="应付金额"
                                            :value="`¥${order.payAmountText}`"
                                            tone="price"
                                        />
                                    </view>

                                    <view class="order-card__meta-chips">
                                        <text class="service-tag">服务项 {{ order.serviceCount }}</text>
                                        <text
                                            v-if="order.packageNames.length > 1"
                                            class="service-tag service-tag--muted"
                                        >
                                            另 {{ order.packageNames.length - 1 }} 项
                                        </text>
                                    </view>
                                </view>

                                <view class="order-card__foot">
                                    <text class="order-card__time">{{
                                        order.createTimeText || '暂无下单时间'
                                    }}</text>
                                    <view
                                        class="order-card__actions"
                                        @click.stop
                                        @tap.stop
                                    >
                                        <BaseButton
                                            v-if="getPrimaryActionLabel(order)"
                                            variant="dark"
                                            size="sm"
                                            height="66rpx"
                                            :label="getPrimaryActionLabel(order)"
                                            @click.stop="handlePrimaryAction(order)"
                                        />
                                        <BaseButton
                                            variant="light"
                                            size="sm"
                                            height="66rpx"
                                            label="详情"
                                            icon="right"
                                            icon-position="right"
                                            @click.stop="goDetail(order.id)"
                                        />
                                    </view>
                                </view>
                            </BaseCard>
                        </view>
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
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseInfoRow from '@/components/base/BaseInfoRow.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import StaffFilterBar from '@/packages/components/staff-workspace/staff-filter-bar.vue'
import StaffSectionHeader from '@/packages/components/staff-workspace/staff-section-header.vue'
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

interface FormattedOrder {
    id: number
    orderNo: string
    statusValue: number
    statusText: string
    statusTone: StatusTone
    createTimeText: string
    serviceDate: string
    location: string
    payAmountText: string
    packageNames: string[]
    primaryPackageName: string
    serviceCount: number
    contactName: string
    contactMobile: string
    contactText: string
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

const statusTabs = computed(() =>
    STATUS_TAB_CONFIG.map((item) => ({
        ...item,
        count: Number(orderStats.value[item.key] || 0)
    }))
)

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
        payAmountText: formatMoney(order.pay_amount || 0),
        packageNames,
        primaryPackageName: packageNames[0] || '',
        serviceCount,
        contactName: order.contact_name || '',
        contactMobile: order.contact_mobile || '',
        contactText:
            order.contact_name || order.contact_mobile
                ? `${order.contact_name || '未填写联系人'}${
                      order.contact_mobile ? `｜${order.contact_mobile}` : ''
                  }`
                : '未填写联系人',
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

const handleStatusFilterSelect = (value: string | number) => {
    switchStatusByValue(value === '' ? '' : Number(value))
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
    return amount.toFixed(2)
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

.order-board {
    display: flex;
    flex-direction: column;
    gap: 0;
    padding-top: 14rpx;
}

.filter-panel {
    padding: 20rpx 22rpx;
    border-radius: 34rpx;
    background: rgba(255, 253, 248, 0.96);
    border-color: #d8c9ad;
    box-shadow: 0 16rpx 34rpx rgba(74, 43, 24, 0.08);
}

.page-section--list {
    gap: 0;
    padding-top: 18rpx;
    padding-bottom: calc(56rpx + env(safe-area-inset-bottom));
}

.page-section--list :deep(.staff-section-header) {
    margin-bottom: 18rpx;
}

.order-card-wrap {
    display: block;
    margin-bottom: 30rpx;
}

.order-card-wrap:last-child {
    margin-bottom: 0;
}

.order-card {
    display: flex;
    flex-direction: column;
    gap: 22rpx;
    padding: 30rpx 28rpx 28rpx;
    border-radius: 36rpx;
    background: #fffdf8;
    border: 1rpx solid #d8c9ad;
    box-shadow: 0 18rpx 40rpx rgba(74, 43, 24, 0.08);
}

.order-card::before {
    opacity: 0.42;
}

.order-card__main {
    display: flex;
    flex-direction: column;
    gap: 22rpx;
}

.order-card__head {
    display: flex;
    justify-content: space-between;
    gap: 20rpx;
    align-items: flex-start;
}

.order-card__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.order-card__title {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 31rpx;
    font-weight: 900;
    line-height: 1.3;
    color: #191713;
}

.order-card__sn {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 21rpx;
    font-weight: 800;
    line-height: 1.35;
    color: #8c806d;
}

.order-card__status {
    flex-shrink: 0;
    max-width: 180rpx;
}

.order-card__alert {
    display: flex;
    align-items: center;
    gap: 10rpx;
    margin-top: 0;
    padding: 16rpx 18rpx;
    border-radius: 24rpx;
    font-size: 22rpx;
    font-weight: 800;
}

.order-card__alert-text {
    flex: 1;
    min-width: 0;
}

.order-card__info-panel {
    display: flex;
    flex-direction: column;
    padding: 8rpx 22rpx;
    border-radius: 28rpx;
    background: rgba(250, 246, 238, 0.78);
    border: 1rpx solid rgba(216, 201, 173, 0.78);
}

.order-card__info-panel :deep(.base-info-row) {
    min-height: 78rpx;
    gap: 20rpx;
}

.order-card__info-panel :deep(.base-info-row + .base-info-row) {
    border-top: 1rpx solid rgba(216, 201, 173, 0.58);
}

.order-card__info-panel :deep(.base-info-row__label) {
    font-size: 23rpx;
    font-weight: 800;
    color: #756b5c;
}

.order-card__info-panel :deep(.base-info-row__value) {
    font-size: 25rpx;
    font-weight: 900;
    color: #191713;
}

.order-card__info-panel :deep(.base-info-row__value--price) {
    font-size: 27rpx;
    color: #b8954a;
}

.order-card__address-value {
    flex: 1;
    min-width: 0;
    max-width: 430rpx;
    text-align: right;
    font-size: 25rpx;
    font-weight: 900;
    line-height: 1.45;
    color: #191713;
    word-break: break-word;
}

.order-card__meta-chips {
    display: flex;
    flex-wrap: wrap;
    margin-top: 0;
    gap: 12rpx;
}

.service-tag {
    min-height: 48rpx;
    padding: 0 18rpx;
    border-radius: 999rpx;
    background: #fff9ec;
    border-color: rgba(216, 201, 173, 0.92);
    font-size: 22rpx;
    font-weight: 800;
    color: #6b5833;
}

.service-tag--muted {
    background: #fffdf8;
    color: #8c806d;
}

.order-card__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    margin-top: 0;
    padding-top: 22rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.7);
}

.order-card__time {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 22rpx;
    font-weight: 800;
    line-height: 1.4;
    color: #8c806d;
}

.order-card__actions {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    justify-content: flex-end;
    gap: 12rpx;
    margin-left: auto;
}

.order-card__actions :deep(.base-button) {
    min-width: 132rpx;
}

@media screen and (max-width: 375px) {
    .order-card-wrap {
        margin-bottom: 28rpx;
    }

    .order-card-wrap:last-child {
        margin-bottom: 0;
    }

    .order-card {
        padding: 26rpx 24rpx 24rpx;
        border-radius: 32rpx;
    }

    .order-card__address-value {
        max-width: 360rpx;
    }

    .order-card__foot {
        flex-direction: row;
        align-items: center;
    }

    .order-card__actions {
        width: auto;
        margin-left: auto;
    }

    .order-card__actions :deep(.base-button) {
        flex: none;
        min-width: 120rpx;
    }
}
</style>
