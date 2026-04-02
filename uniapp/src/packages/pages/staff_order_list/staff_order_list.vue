<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff">
        <BaseNavbar title="订单管理" />

        <view class="page-container">
            <z-paging
                ref="pagingRef"
                v-model="orderList"
                :auto="false"
                :hide-empty-view="true"
                :paging-style="pagingStyle"
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
                                    <text class="hero-card__title">订单管理</text>
                                </view>

                                <view class="hero-card__total">
                                    <text class="hero-card__total-label">总订单</text>
                                    <text class="hero-card__total-value">
                                        {{ Number(orderStats.all || 0) }}
                                    </text>
                                    <text class="hero-card__total-unit">单</text>
                                </view>
                            </view>

                            <view class="hero-metrics">
                                <view
                                    v-for="item in summaryCards"
                                    :key="item.key"
                                    :class="['hero-metric', { 'hero-metric--accent': item.accent }]"
                                    @click="switchStatusByValue(item.status)"
                                >
                                    <text class="hero-metric__label">{{ item.label }}</text>
                                    <view class="hero-metric__value-row">
                                        <text class="hero-metric__value">{{ item.value }}</text>
                                        <text class="hero-metric__unit">{{ item.unit }}</text>
                                    </view>
                                </view>
                            </view>

                            <scroll-view scroll-x class="hero-status-scroll" show-scrollbar="false">
                                <view class="hero-status-row">
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
                                    <text class="order-card__eyebrow">订单号</text>
                                    <text class="order-card__sn">{{ order.orderNo }}</text>
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

                            <view class="order-card__overview">
                                <view class="order-card__main">
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

                                    <view class="order-card__line">
                                        <tn-icon
                                            name="my"
                                            size="22"
                                            color="var(--wm-color-secondary, #C99B73)"
                                        />
                                        <text>{{ order.contactName || '未填写联系人' }}</text>
                                    </view>
                                    <view v-if="order.contactMobile" class="order-card__line">
                                        <tn-icon
                                            name="phone"
                                            size="22"
                                            color="var(--wm-color-secondary, #C99B73)"
                                        />
                                        <text>{{ order.contactMobile }}</text>
                                    </view>
                                    <view class="order-card__line">
                                        <tn-icon
                                            name="map-pin"
                                            size="22"
                                            color="var(--wm-color-secondary, #C99B73)"
                                        />
                                        <text>{{ order.location }}</text>
                                    </view>
                                </view>

                                <view class="order-card__amount-box">
                                    <text class="order-card__amount-label">实付金额</text>
                                    <text class="order-card__amount">¥{{ order.actualPrice }}</text>
                                    <text class="order-card__amount-sub">
                                        服务项 {{ order.serviceCount }}
                                    </text>
                                </view>
                            </view>

                            <view class="service-panel">
                                <view class="service-panel__head">
                                    <text class="service-panel__title">服务内容</text>
                                    <text class="service-panel__count">
                                        {{ order.serviceCount }} 项
                                    </text>
                                </view>

                                <view class="service-panel__tags">
                                    <text
                                        v-for="(tag, index) in order.packageNames"
                                        :key="`${order.id}-${index}`"
                                        class="service-tag"
                                    >
                                        {{ tag }}
                                    </text>
                                    <text
                                        v-if="order.packageNames.length === 0"
                                        class="service-tag service-tag--muted"
                                    >
                                        暂无服务项名称
                                    </text>
                                </view>
                            </view>

                            <view class="order-card__foot">
                                <view class="order-card__actions">
                                    <view
                                        class="action-btn action-btn--ghost"
                                        @click.stop="goDetail(order.id)"
                                    >
                                        查看详情
                                    </view>
                                    <view
                                        v-if="order.canConfirm"
                                        class="action-btn action-btn--primary"
                                        :style="primaryActionStyle"
                                        @click.stop="confirmOrder(order)"
                                    >
                                        确认订单
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
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import FilterChip from '@/components/base/FilterChip.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import {
    staffCenterOrderConfirm,
    staffCenterOrderLists,
    staffCenterOrderStats
} from '@/api/staffCenter'
import { useFixedNavbarPagingStyle } from '@/hooks/useFixedNavbarPagingStyle'
import { useThemeStore } from '@/stores/theme'
import { ensureStaffCenterAccess } from '@/utils/staff-center'

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
    canConfirm: boolean
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
    refunded: 0
} as const

const STATUS_TAB_CONFIG: StatusTabConfigItem[] = [
    { label: '全部', value: '', key: 'all' },
    { label: '待确认', value: 0, key: 'pending_confirm' },
    { label: '待支付', value: 1, key: 'pending_pay' },
    { label: '已支付', value: 2, key: 'paid' },
    { label: '服务中', value: 3, key: 'in_service' },
    { label: '已完成', value: 4, key: 'completed' },
    { label: '已评价', value: 5, key: 'reviewed' },
    { label: '已取消', value: 6, key: 'cancelled' },
    { label: '已暂停', value: 7, key: 'paused' },
    { label: '已退款', value: 8, key: 'refunded' }
]

const STATUS_TEXT_MAP: Record<number, string> = {
    0: '待确认',
    1: '待支付',
    2: '已支付',
    3: '服务中',
    4: '已完成',
    5: '已评价',
    6: '已取消',
    7: '已暂停',
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
    8: 'danger',
    9: 'danger'
}

const DISPLAY_ITEM_TYPES = [1, 3]

const $theme = useThemeStore()
const pagingStyle = useFixedNavbarPagingStyle()
const pagingRef = ref<any>(null)
const orderList = ref<FormattedOrder[]>([])
const orderStats = ref<Record<string, number>>({ ...DEFAULT_ORDER_STATS })
const currentStatus = ref<StatusValue>('')
const loading = ref(false)
const hasLoaded = ref(false)

const heroCardStyle = computed(() => ({
    '--wm-hero-gradient': `linear-gradient(135deg, ${
        $theme.primaryColor || '#E85A4F'
    }12 0%, var(--wm-color-bg-page, #FCFBF9) 52%, ${$theme.secondaryColor || '#C99B73'}14 100%)`,
    borderColor: 'var(--wm-color-border-strong, #F4C7BF)'
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
        accent: true
    },
    {
        key: 'in_service',
        label: '服务中',
        value: Number(orderStats.value.in_service || 0),
        unit: '单',
        status: 3,
        accent: false
    },
    {
        key: 'completed',
        label: '已完成',
        value: Number(orderStats.value.completed || 0),
        unit: '单',
        status: 4,
        accent: false
    }
])

const resetOrderStats = () => {
    orderStats.value = { ...DEFAULT_ORDER_STATS }
}

const getStatusText = (status: number) => STATUS_TEXT_MAP[status] || '未知'

const getStatusTone = (status: number): StatusTone => STATUS_TONE_MAP[status] || 'neutral'

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
        canConfirm: orderStatus === 0 && pendingConfirmCount > 0
    }
}

const queryList = async (pageNo: number, pageSize: number) => {
    loading.value = true

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
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
        pagingRef.value.complete(false)
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
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    padding-top: 20rpx;
    box-sizing: border-box;
    background: radial-gradient(
            circle at top left,
            rgba(232, 90, 79, 0.1) 0,
            rgba(252, 251, 249, 0) 36%
        ),
        linear-gradient(180deg, var(--wm-color-bg-page, #fcfbf9) 0%, #f7f1ed 100%);
}

.page-section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 0 var(--wm-space-page-x, 37rpx);
    box-sizing: border-box;

    &--top {
        padding-top: 20rpx;
    }

    &--list {
        padding-top: 18rpx;
        padding-bottom: calc(48rpx + env(safe-area-inset-bottom));
    }
}

.hero-card {
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
}

.hero-card__title {
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1.28;
    color: var(--wm-text-primary, #1e2432);
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
    color: var(--wm-text-secondary, #7f7b78);
}

.hero-card__total-value {
    font-size: 42rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #1e2432);
}

.hero-card__total-unit {
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-tertiary, #b4aca8);
}

.hero-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
    margin-top: 20rpx;
}

.hero-status-scroll {
    margin-top: 18rpx;
    white-space: nowrap;
}

.hero-status-row {
    display: inline-flex;
    gap: 12rpx;
    padding-bottom: 2rpx;
}

.hero-metric {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    min-width: 0;
    padding: 18rpx 20rpx;
    border-radius: 30rpx;
    background: rgba(255, 255, 255, 0.76);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    box-sizing: border-box;

    &--accent {
        background: var(--wm-color-primary-soft, #fff1ee);
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }
}

.hero-metric__label {
    font-size: 21rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-secondary, #7f7b78);
}

.hero-metric--accent .hero-metric__label {
    color: var(--wm-color-primary, #e85a4f);
}

.hero-metric__value-row {
    display: flex;
    align-items: flex-end;
    gap: 6rpx;
}

.hero-metric__value {
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #1e2432);
}

.hero-metric__unit {
    font-size: 20rpx;
    font-weight: 600;
    line-height: 1.35;
    color: var(--wm-text-secondary, #7f7b78);
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

.order-card__eyebrow {
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-tertiary, #b4aca8);
}

.order-card__sn {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #1e2432);
    word-break: break-all;
}

.order-card__time {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.45;
    color: var(--wm-text-secondary, #7f7b78);
}

.order-card__status {
    flex-shrink: 0;
}

.order-card__overview {
    display: flex;
    gap: 18rpx;
    margin-top: 22rpx;
}

.order-card__main {
    flex: 1;
    min-width: 0;
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
    color: var(--wm-text-primary, #1e2432);
}

.order-card__line {
    display: flex;
    align-items: flex-start;
    gap: 10rpx;
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1.55;
    color: var(--wm-text-secondary, #7f7b78);
}

.order-card__line + .order-card__line {
    margin-top: 10rpx;
}

.order-card__line text:last-child {
    flex: 1;
    min-width: 0;
}

.order-card__amount-box {
    flex-shrink: 0;
    min-width: 176rpx;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    padding: 20rpx;
    border-radius: 28rpx;
    background: linear-gradient(
        180deg,
        rgba(255, 241, 238, 0.95) 0%,
        rgba(255, 255, 255, 0.9) 100%
    );
    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);
    box-sizing: border-box;
}

.order-card__amount-label {
    font-size: 20rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-secondary, #7f7b78);
}

.order-card__amount {
    font-size: 38rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #1e2432);
}

.order-card__amount-sub {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.35;
    color: var(--wm-color-primary, #e85a4f);
}

.service-panel {
    margin-top: 22rpx;
    padding: 20rpx 22rpx;
    border-radius: 30rpx;
    background: rgba(252, 251, 249, 0.9);
    border: 1rpx solid var(--wm-color-border, #efe6e1);
}

.service-panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.service-panel__title {
    font-size: 24rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #1e2432);
}

.service-panel__count {
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1.35;
    color: var(--wm-text-secondary, #7f7b78);
}

.service-panel__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 16rpx;
}

.service-tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48rpx;
    padding: 0 16rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
    color: var(--wm-text-secondary, #7f7b78);

    &--muted {
        color: var(--wm-text-tertiary, #b4aca8);
    }
}

.order-card__foot {
    display: flex;
    justify-content: flex-end;
    margin-top: 22rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid var(--wm-color-border, #efe6e1);
}

.order-card__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 12rpx;
}

.action-btn {
    min-width: 124rpx;
    height: 68rpx;
    padding: 0 24rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1;

    &--ghost {
        color: var(--wm-text-primary, #1e2432);
        background: rgba(255, 255, 255, 0.86);
        border: 2rpx solid var(--wm-color-border, #efe6e1);
    }

    &--primary {
        border: none;
        box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(214, 185, 167, 0.16));
    }
}
</style>
