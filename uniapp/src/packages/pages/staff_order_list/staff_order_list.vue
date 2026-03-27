<template>
    <page-meta :page-style="$theme.pageStyle" />
    <BaseNavbar title="订单管理" />

    <view class="page-container">
        <z-paging ref="pagingRef" v-model="orderList" @query="queryList" :auto="false" :hide-empty-view="true">
            <template #top>
                <view class="top-panel">
                    <view
                        class="summary-card"
                        :style="{
                            background: `linear-gradient(145deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 78%)`
                        }"
                    >
                        <view class="summary-head">
                            <view>
                                <text class="summary-title">订单工作台</text>
                                <text class="summary-desc">快速确认、跟进服务进度、查看历史沉淀</text>
                            </view>
                            <view class="summary-pill">
                                共 {{ orderStats.all || 0 }} 单
                            </view>
                        </view>

                        <view class="summary-grid">
                            <view
                                v-for="item in summaryCards"
                                :key="item.key"
                                class="summary-item"
                                @click="switchStatusByValue(item.status)"
                            >
                                <text class="summary-item-label">{{ item.label }}</text>
                                <text class="summary-item-value">{{ item.value }}</text>
                            </view>
                        </view>
                    </view>

                    <scroll-view scroll-x class="status-scroll">
                        <view class="status-row">
                            <view
                                v-for="tab in statusTabs"
                                :key="tab.value"
                                class="status-chip"
                                :class="{ active: currentStatus === tab.value }"
                                :style="currentStatus === tab.value ? getActiveChipStyle() : {}"
                                @click="switchStatusByValue(tab.value)"
                            >
                                <text class="status-chip-label">{{ tab.label }}</text>
                                <text
                                    class="status-chip-count"
                                    :style="currentStatus === tab.value ? { color: $theme.btnColor } : {}"
                                >
                                    {{ tab.count }}
                                </text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </template>

            <view class="order-list">
                <view
                    v-for="order in orderList"
                    :key="order.id"
                    class="order-card"
                    @click="goDetail(order.id)"
                >
                    <view class="order-head">
                        <view class="order-sn-wrap">
                            <text class="order-sn-label">订单号</text>
                            <text class="order-sn">{{ order.orderNo }}</text>
                        </view>
                        <view class="order-status" :style="getStatusStyle(order.statusValue)">
                            {{ order.statusText }}
                        </view>
                    </view>

                    <view class="order-main">
                        <view class="order-main-left">
                            <view class="order-highlight">
                                <text class="order-date">{{ order.serviceDate || '待安排服务日期' }}</text>
                                <text v-if="order.pendingConfirmCount > 0" class="pending-chip">
                                    待确认 {{ order.pendingConfirmCount }}
                                </text>
                            </view>

                            <view class="order-line">
                                <tn-icon name="my" size="22" color="#64748B" />
                                <text>{{ order.contactName || '未填写联系人' }}</text>
                            </view>
                            <view v-if="order.contactMobile" class="order-line">
                                <tn-icon name="phone" size="22" color="#64748B" />
                                <text>{{ order.contactMobile }}</text>
                            </view>
                            <view class="order-line">
                                <tn-icon name="map-pin" size="22" color="#64748B" />
                                <text>{{ order.location }}</text>
                            </view>
                        </view>

                        <view class="order-amount-box">
                            <text class="order-amount-label">实付金额</text>
                            <text class="order-amount">¥{{ order.actualPrice }}</text>
                            <text class="order-amount-sub">服务项 {{ order.serviceCount }}</text>
                        </view>
                    </view>

                    <view class="service-block">
                        <view class="service-block-head">
                            <text class="service-block-title">服务内容</text>
                            <text class="service-block-count">{{ order.serviceCount }} 项</text>
                        </view>
                        <view class="service-tags">
                            <text v-for="(tag, index) in order.packageNames" :key="index" class="service-tag">
                                {{ tag }}
                            </text>
                            <text v-if="order.packageNames.length === 0" class="service-tag service-tag--muted">
                                暂无服务项名称
                            </text>
                        </view>
                    </view>

                    <view class="order-foot">
                        <text class="order-time">创建时间：{{ order.createTimeText }}</text>
                        <view class="order-actions">
                            <view class="action-btn action-btn--ghost" @click.stop="goDetail(order.id)">
                                查看详情
                            </view>
                            <view
                                v-if="order.canConfirm"
                                class="action-btn action-btn--primary"
                                :style="{
                                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 100%)`,
                                    color: $theme.btnColor
                                }"
                                @click.stop="confirmOrder(order)"
                            >
                                确认订单
                            </view>
                        </view>
                    </view>
                </view>

                <view v-if="orderList.length === 0" class="empty-state">
                    <tn-icon name="order" size="120" color="#D1D5DB" />
                    <text class="empty-title">当前筛选下暂无订单</text>
                    <text class="empty-desc">切换状态或等待新的订单进入</text>
                </view>
            </view>
        </z-paging>
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    staffCenterOrderLists,
    staffCenterOrderConfirm,
    staffCenterOrderStats
} from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const pagingRef = ref<any>(null)
const orderList = ref<any[]>([])
const orderStats = ref<Record<string, number>>({
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
})

const currentStatus = ref<number | ''>('')

const statusConfig = [
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

const statusTabs = computed(() =>
    statusConfig.map((item) => ({
        ...item,
        count: Number(orderStats.value[item.key] || 0)
    }))
)

const summaryCards = computed(() => [
    {
        key: 'pending_confirm',
        label: '待确认',
        value: Number(orderStats.value.pending_confirm || 0),
        status: 0
    },
    {
        key: 'in_service',
        label: '服务中',
        value: Number(orderStats.value.in_service || 0),
        status: 3
    },
    {
        key: 'completed',
        label: '已完成',
        value: Number(orderStats.value.completed || 0),
        status: 4
    }
])

const getActiveChipStyle = () => ({
    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 100%)`,
    color: $theme.btnColor,
    borderColor: 'transparent'
})

const loadOrderStats = async () => {
    try {
        const data = await staffCenterOrderStats()
        orderStats.value = {
            ...orderStats.value,
            ...(data || {})
        }
    } catch {
        orderStats.value = {
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
        }
    }
}

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const params: any = { page: pageNo, page_size: pageSize }
        if (currentStatus.value !== '') {
            params.status = currentStatus.value
        }
        const res: any = await staffCenterOrderLists(params)
        const list = Array.isArray(res?.data) ? res.data : []
        pagingRef.value.complete(list.map(formatOrder))
    } catch {
        pagingRef.value.complete(false)
    }
}

const switchStatusByValue = (value: number | '') => {
    if (currentStatus.value === value) return
    currentStatus.value = value
    pagingRef.value?.reload()
}

const formatOrder = (order: any) => {
    const items = Array.isArray(order.items) ? order.items : []
    const orderStatus = Number(order.order_status ?? -1)
    const packageNames = items
        .map((item: any) => item.package_name)
        .filter((name: string) => Boolean(name))
    const serviceDateList = items
        .map((item: any) => item.service_date)
        .filter((date: string) => Boolean(date))
        .sort()
    const pendingConfirmCount = items.filter(
        (item: any) => Number(item.confirm_status ?? 0) === 0 && Number(item.item_status ?? 0) !== 3
    ).length

    return {
        id: Number(order.id || 0),
        orderNo: order.order_sn || '',
        statusValue: orderStatus,
        statusText: order.order_status_desc || getStatusText(orderStatus),
        createTimeText: formatDateTime(order.create_time),
        serviceDate: serviceDateList[0] || '',
        location: order.service_address || '服务地址未填写',
        actualPrice: formatMoney(order.pay_amount || 0),
        packageNames,
        serviceCount: items.length,
        contactName: order.contact_name || '',
        contactMobile: order.contact_mobile || '',
        pendingConfirmCount,
        canConfirm: orderStatus === 0 && pendingConfirmCount > 0
    }
}

const goDetail = (id: number) => {
    uni.navigateTo({ url: `/packages/pages/staff_order_detail/staff_order_detail?id=${id}` })
}

const confirmOrder = (order: any) => {
    uni.showModal({
        title: '确认订单',
        content: '确认后客户可进行支付，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterOrderConfirm({ id: order.id })
                uni.showToast({ title: '确认成功', icon: 'success' })
                await loadOrderStats()
                pagingRef.value?.reload()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '确认失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

const getStatusText = (status: number) => {
    const texts: Record<number, string> = {
        0: '待确认',
        1: '待支付',
        2: '已支付',
        3: '服务中',
        4: '已完成',
        5: '已评价',
        6: '已取消',
        7: '已暂停',
        8: '已退款'
    }
    return texts[status] || '未知'
}

const getStatusStyle = (status: number) => {
    const styles: Record<number, Record<string, string>> = {
        0: { background: 'rgba(249,115,22,0.12)', color: '#EA580C' },
        1: { background: 'rgba(201,155,115,0.12)', color: '#C99B73' },
        2: { background: 'rgba(16,185,129,0.12)', color: '#059669' },
        3: { background: 'rgba(14,165,233,0.12)', color: '#0284C7' },
        4: { background: 'rgba(100,116,139,0.12)', color: '#475569' },
        5: { background: 'rgba(232,90,79,0.12)', color: '#E85A4F' },
        6: { background: 'rgba(239,68,68,0.12)', color: '#DC2626' },
        7: { background: 'rgba(245,158,11,0.12)', color: '#D97706' },
        8: { background: 'rgba(244,63,94,0.12)', color: '#E11D48' }
    }
    return styles[status] || styles[0]
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
    const existed = statusConfig.some((item) => item.value === parsed)
    currentStatus.value = existed ? parsed : ''
}

onLoad((options) => {
    initStatusByQuery(options?.status)
})

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    await loadOrderStats()
    pagingRef.value?.reload()
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(191, 219, 254, 0.72) 0, rgba(247, 248, 251, 0) 36%),
        linear-gradient(180deg, #F6F8FC 0%, #F4F6FB 100%);
}

.top-panel {
    padding: 24rpx 24rpx 0;
}

.summary-card {
    padding: 28rpx;
    border-radius: 30rpx;
    box-shadow: 0 18rpx 36rpx rgba(37, 99, 235, 0.2);
}

.summary-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.summary-title {
    display: block;
    font-size: 34rpx;
    font-weight: 700;
    color: #FFFFFF;
}

.summary-desc {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.8);
}

.summary-pill {
    flex-shrink: 0;
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.16);
    font-size: 22rpx;
    font-weight: 600;
    color: #FFFFFF;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
    margin-top: 26rpx;
}

.summary-item {
    padding: 20rpx;
    border-radius: 22rpx;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(12rpx);
}

.summary-item-label {
    display: block;
    font-size: 22rpx;
    color: rgba(255, 255, 255, 0.75);
}

.summary-item-value {
    display: block;
    margin-top: 14rpx;
    font-size: 38rpx;
    font-weight: 800;
    color: #FFFFFF;
}

.status-scroll {
    margin-top: 18rpx;
    white-space: nowrap;
}

.status-row {
    display: inline-flex;
    gap: 14rpx;
    padding-bottom: 6rpx;
}

.status-chip {
    min-width: 148rpx;
    padding: 18rpx 22rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.9);
    border: 2rpx solid #E2E8F0;
    box-shadow: 0 8rpx 20rpx rgba(15, 23, 42, 0.04);
}

.status-chip.active {
    box-shadow: 0 14rpx 28rpx rgba(37, 99, 235, 0.12);
}

.status-chip-label {
    display: block;
    font-size: 24rpx;
    font-weight: 600;
    color: currentColor;
}

.status-chip-count {
    display: block;
    margin-top: 10rpx;
    font-size: 34rpx;
    font-weight: 800;
    color: #0F172A;
}

.order-list {
    padding: 18rpx 24rpx 48rpx;
}

.order-card + .order-card {
    margin-top: 18rpx;
}

.order-card {
    padding: 28rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.94);
    border: 1rpx solid rgba(255, 255, 255, 0.72);
    box-shadow: 0 18rpx 30rpx rgba(15, 23, 42, 0.05);
}

.order-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
}

.order-sn-wrap {
    min-width: 0;
}

.order-sn-label {
    display: block;
    font-size: 22rpx;
    color: #94A3B8;
}

.order-sn {
    display: block;
    margin-top: 8rpx;
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.35;
    color: #0F172A;
}

.order-status {
    flex-shrink: 0;
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.order-main {
    display: flex;
    gap: 20rpx;
    margin-top: 24rpx;
}

.order-main-left {
    flex: 1;
    min-width: 0;
}

.order-highlight {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 14rpx;
}

.order-date {
    font-size: 30rpx;
    font-weight: 700;
    color: #0F172A;
}

.pending-chip {
    padding: 6rpx 14rpx;
    border-radius: 999rpx;
    background: rgba(249, 115, 22, 0.12);
    font-size: 22rpx;
    color: #EA580C;
}

.order-line {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 24rpx;
    line-height: 1.55;
    color: #475569;
}

.order-line + .order-line {
    margin-top: 10rpx;
}

.order-line text:last-child {
    flex: 1;
    min-width: 0;
}

.order-amount-box {
    flex-shrink: 0;
    min-width: 168rpx;
    padding: 20rpx;
    border-radius: 24rpx;
    background: linear-gradient(180deg, #F8FAFC 0%, #EFF6FF 100%);
}

.order-amount-label {
    display: block;
    font-size: 20rpx;
    color: #94A3B8;
}

.order-amount {
    display: block;
    margin-top: 12rpx;
    font-size: 36rpx;
    font-weight: 800;
    color: #0F172A;
}

.order-amount-sub {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    color: #64748B;
}

.service-block {
    margin-top: 22rpx;
    padding: 22rpx;
    border-radius: 24rpx;
    background: #F8FAFC;
}

.service-block-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.service-block-title {
    font-size: 24rpx;
    font-weight: 600;
    color: #0F172A;
}

.service-block-count {
    font-size: 22rpx;
    color: #94A3B8;
}

.service-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 16rpx;
}

.service-tag {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    background: #FFFFFF;
    font-size: 22rpx;
    color: #334155;
}

.service-tag--muted {
    color: #94A3B8;
}

.order-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    margin-top: 22rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid #E2E8F0;
}

.order-time {
    flex: 1;
    min-width: 0;
    font-size: 22rpx;
    color: #94A3B8;
}

.order-actions {
    display: flex;
    gap: 12rpx;
}

.action-btn {
    min-width: 124rpx;
    height: 64rpx;
    padding: 0 24rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    font-weight: 600;
}

.action-btn--ghost {
    color: #475569;
    background: #F8FAFC;
    border: 2rpx solid #E2E8F0;
}

.action-btn--primary {
    box-shadow: 0 12rpx 22rpx rgba(37, 99, 235, 0.16);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 140rpx 0;
}

.empty-title {
    margin-top: 24rpx;
    font-size: 30rpx;
    font-weight: 600;
    color: #475569;
}

.empty-desc {
    margin-top: 10rpx;
    font-size: 22rpx;
    color: #94A3B8;
}
</style>
