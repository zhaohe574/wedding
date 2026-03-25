<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            title="我的订单"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
        <!-- #endif -->
    </page-meta>
    <view class="order-page cinema-page page-with-tabbar-safe-bottom">
        <view class="order-page__hero">
            <view class="order-page__hero-copy">
                <text class="order-page__hero-label">Order Theatre</text>
                <text class="order-page__hero-title">订单进度、支付状态与履约信息一屏掌握</text>
                <text class="order-page__hero-desc">
                    以电影海报的氛围感承载关键状态，但列表区仍保持轻量和高可读性，方便持续跟单。
                </text>
            </view>
            <view class="order-page__hero-grid">
                <view
                    v-for="item in overviewCards"
                    :key="item.key"
                    class="order-page__hero-card glass-card"
                >
                    <text class="order-page__hero-card-value">{{ item.value }}</text>
                    <text class="order-page__hero-card-label">{{ item.label }}</text>
                </view>
            </view>
        </view>

        <view class="order-page__surface cinema-surface">
            <view class="status-tabs-wrapper cinema-panel">
                <view class="order-page__tabs-head">
                    <view>
                        <text class="order-page__section-title">状态筛选</text>
                        <text class="order-page__section-desc">
                            当前查看：{{ statusTabs[currentTabIndex]?.label || '全部订单' }}
                        </text>
                    </view>
                </view>
                <tn-tabs
                    v-model="currentTabIndex"
                    :scroll="true"
                    height="96rpx"
                    class="tabs-main"
                    :active-color="$theme.primaryColor"
                >
                    <tn-tabs-item
                        v-for="(tab, index) in statusTabs"
                        :key="index"
                        :title="tab.label"
                        :badge="statistics[tab.key] > 0 ? String(statistics[tab.key]) : ''"
                    />
                </tn-tabs>
            </view>

            <view class="order-list-wrapper">
                <view class="order-page__list-head">
                    <view>
                        <text class="order-page__section-title">订单列表</text>
                        <text class="order-page__section-desc">按订单状态快速查看支付、履约与售后进度。</text>
                    </view>
                </view>

                <!-- 订单列表 -->
                <view class="order-list-wrapper__body">
                    <!-- 加载中 -->
                    <view v-if="loading && orders.length === 0" class="loading-state">
                        <view class="loading-content">
                            <tn-loading size="80" mode="flower" :color="$theme.primaryColor" />
                            <text class="loading-text">加载中...</text>
                        </view>
                    </view>

                    <!-- 空状态 -->
                    <view v-else-if="orders.length === 0" class="empty-state cinema-panel">
                        <view class="empty-icon-wrapper">
                            <tn-icon name="file-text" size="200" color="#D1D5DB" />
                        </view>
                        <text class="empty-title">当前筛选下还没有订单</text>
                        <text class="empty-subtitle">回到服务人员页继续挑选，新的预约会第一时间同步到这里。</text>
                        <view
                            class="empty-action-btn"
                            :style="{
                                background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 100%)`,
                                color: $theme.btnColor
                            }"
                            @click="goHome"
                        >
                            <text class="empty-action-text" :style="{ color: $theme.btnColor }">
                                去预约
                            </text>
                        </view>
                    </view>

                    <!-- 订单列表 - 使用OrderCard组件 -->
                    <view v-else class="order-list">
                        <order-card
                            v-for="order in orders"
                            :key="order.id"
                            :order="order"
                            @click="goDetail(order.id)"
                            @action="handleCardAction"
                        />

                        <!-- 加载更多提示 -->
                        <view v-if="hasMore" class="load-more">
                            <view v-if="loading" class="load-more-loading">
                                <tn-loading size="40" mode="flower" :color="$theme.primaryColor" />
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
                        <view v-else-if="orders.length > 0" class="load-more">
                            <text class="load-more-text">已经到底了</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <tabbar />
    </view>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { onLoad, onShow, onReachBottom } from '@dcloudio/uni-app'
import { useThemeStore } from '@/stores/theme'
import {
    getOrderList,
    getOrderStatistics,
    cancelOrder,
    confirmOrder,
    deleteOrder
} from '@/api/order'
import OrderCard from '@/components/business/OrderCard.vue'

const $theme = useThemeStore()

const statusTabs = [
    { label: '全部', value: '', key: 'all' },
    { label: '待确认', value: 0, key: 'pending_confirm' },
    { label: '待支付', value: 1, key: 'pending_pay' },
    { label: '已支付', value: 2, key: 'paid' },
    { label: '服务中', value: 3, key: 'in_service' },
    { label: '已完成', value: 4, key: 'completed' },
    { label: '已评价', value: 5, key: 'reviewed' },
    { label: '已取消', value: 6, key: 'cancelled' },
    { label: '已暂停', value: 7, key: 'paused' },
    { label: '已退款', value: 8, key: 'refund' }
]

const currentTabIndex = ref(0)
const currentStatus = computed(() => statusTabs[currentTabIndex.value].value)
const orders = ref<any[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)
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
    refund: 0
})

const overviewCards = computed(() => [
    {
        key: 'all',
        label: '全部订单',
        value: statistics.all || 0
    },
    {
        key: 'pending_pay',
        label: '待支付',
        value: statistics.pending_pay || 0
    },
    {
        key: 'in_service',
        label: '服务中',
        value: statistics.in_service || 0
    },
    {
        key: 'completed',
        label: '已完成',
        value: statistics.completed || 0
    }
])

const getStatusKey = (status: number) => {
    const statusMap: Record<number, string> = {
        0: 'pending_confirm',
        1: 'pending_pay',
        2: 'paid',
        3: 'in_service',
        4: 'completed',
        5: 'reviewed',
        6: 'cancelled',
        7: 'paused',
        8: 'refunded'
    }
    return statusMap[status] || 'pending_pay'
}

const buildActions = (status: number) => {
    if (status === 0) {
        return [{ text: '取消', type: 'secondary', action: 'cancel' }]
    }
    if (status === 1) {
        return [
            { text: '取消', type: 'secondary', action: 'cancel' },
            { text: '支付', type: 'primary', action: 'pay' }
        ]
    }
    if (status === 3) {
        return [{ text: '确认完成', type: 'primary', action: 'confirm' }]
    }
    if ([4, 5, 6, 8].includes(status)) {
        return [{ text: '删除', type: 'secondary', action: 'delete' }]
    }
    return []
}

// 获取服务人员头像（优先使用关联的staff对象）
const getStaffAvatar = (item: any) => {
    if (item.staff && item.staff.avatar) {
        return item.staff.avatar
    }
    return '/static/images/user/default_avatar.png'
}

const fetchOrders = async (refresh = false) => {
    if (loading.value) return
    loading.value = true

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
            const discount = Number(order.discount_amount || 0)
            const locationText = [order.service_region_text, order.service_address]
                .map((item: any) => String(item || '').trim())
                .filter(Boolean)
                .join(' · ')
            return {
                id: order.id,
                orderNo: order.order_sn,
                status: getStatusKey(order.order_status),
                createTime: order.create_time,
                location: locationText || '服务地区未填写',
                originalPrice: Number(order.total_amount || 0),
                discount,
                actualPrice: Number(order.pay_amount || 0),
                items: (order.items || []).map((item: any) => ({
                    id: item.id,
                    staffId: item.staff_id,
                    staffName: item.staff_name,
                    staffAvatar: getStaffAvatar(item),
                    packageName: item.package_name,
                    serviceDate: item.service_date
                })),
                actions: buildActions(order.order_status)
            }
        })

        if (refresh) {
            orders.value = list
        } else {
            orders.value.push(...list)
        }

        const totalPage = Number(res?.last_page || 1)
        hasMore.value = page.value < totalPage
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const fetchStatistics = async () => {
    try {
        const res = await getOrderStatistics()
        Object.assign(statistics, res)
    } catch (e) {
        console.error(e)
    }
}

const loadMore = () => {
    if (hasMore.value && !loading.value) {
        page.value++
        fetchOrders()
    }
}

const goDetail = (orderId: number) => {
    uni.navigateTo({ url: `/pages/order_detail/order_detail?id=${orderId}` })
}

const goHome = () => {
    uni.navigateTo({ url: '/pages/staff_list/staff_list' })
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
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
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
            uni.showToast({ title: '订单已完成' })
            fetchOrders(true)
            fetchStatistics()
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

const handleRefund = (orderId: number) => {
    uni.navigateTo({ url: `/pages/order_detail/order_detail?id=${orderId}&action=refund` })
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
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

// 监听标签切换
watch(currentTabIndex, () => {
    fetchOrders(true)
})

onLoad((options: any) => {
    if (options.status !== undefined) {
        // 状态映射：支持字符串和数字两种格式
        const statusMap: Record<string, number> = {
            pending_confirm: 0,
            pending_pay: 1,
            paid: 2,
            in_service: 3,
            completed: 4,
            reviewed: 5,
            cancelled: 6,
            paused: 7,
            refund: 8
        }

        // 如果是字符串，先尝试映射，否则转换为数字
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
    fetchOrders(true)
    fetchStatistics()
})

onReachBottom(() => {
    loadMore()
})
</script>

<style lang="scss" scoped>
.order-page {
    min-height: 100vh;
    background: transparent;

    &__hero {
        padding: 24rpx 24rpx 184rpx;
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.1) 0, transparent 34%),
            linear-gradient(145deg, rgba(10, 13, 18, 0.98) 0%, rgba(19, 25, 36, 0.96) 52%, rgba(66, 52, 29, 0.92) 100%);
    }

    &__hero-copy {
        max-width: 640rpx;
    }

    &__hero-label {
        display: block;
        font-size: 22rpx;
        font-weight: 600;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: rgba(255, 248, 236, 0.72);
    }

    &__hero-title {
        display: block;
        margin-top: 22rpx;
        font-size: 48rpx;
        font-weight: 700;
        line-height: 1.22;
        color: var(--cinema-text-inverse, #fff8ea);
    }

    &__hero-desc {
        display: block;
        margin-top: 18rpx;
        font-size: 25rpx;
        line-height: 1.7;
        color: rgba(255, 248, 236, 0.7);
    }

    &__hero-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16rpx;
        margin-top: 28rpx;
    }

    &__hero-card {
        padding: 24rpx;
        background: rgba(255, 248, 236, 0.1);
    }

    &__hero-card-value {
        display: block;
        font-size: 36rpx;
        font-weight: 700;
        color: var(--cinema-text-inverse, #fff8ea);
    }

    &__hero-card-label {
        display: block;
        margin-top: 8rpx;
        font-size: 22rpx;
        color: rgba(255, 248, 236, 0.64);
    }

    &__surface {
        margin-top: -136rpx;
        border-radius: 36rpx 36rpx 0 0;
        padding: 0 24rpx 28rpx;
        box-shadow: 0 -24rpx 48rpx rgba(8, 10, 16, 0.18);
    }

    &__tabs-head,
    &__list-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__tabs-head {
        padding: 24rpx 24rpx 6rpx;
    }

    &__list-head {
        margin-bottom: 18rpx;
    }

    &__section-title {
        display: block;
        font-size: 30rpx;
        font-weight: 700;
        color: var(--cinema-text-primary, #151a23);
    }

    &__section-desc {
        display: block;
        margin-top: 8rpx;
        font-size: 22rpx;
        line-height: 1.6;
        color: var(--cinema-text-secondary, #5d6472);
    }
}

/* 状态筛选标签 */
.status-tabs-wrapper {
    position: sticky;
    top: 0;
    z-index: 10;
    margin-bottom: 24rpx;
    overflow: hidden;
}

.tabs-main {
    width: 100%;
}

/* 订单列表容器 */
.order-list-wrapper {
    padding: 0 0 24rpx;
}

.order-list-wrapper__body {
    min-height: 56vh;
}

/* 加载状态 */
.loading-state {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24rpx;
}

.loading-text {
    font-size: 28rpx;
    color: var(--cinema-text-secondary, #5d6472);
}

/* 空状态 */
.empty-state {
    min-height: 60vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48rpx;
    background: var(--cinema-surface-elevated, #fffdf8);
}

.empty-icon-wrapper {
    width: 280rpx;
    height: 280rpx;
    margin-bottom: 32rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-title {
    font-size: 34rpx;
    font-weight: 600;
    color: var(--cinema-text-primary, #151a23);
    margin-bottom: 16rpx;
    text-align: center;
}

.empty-subtitle {
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--cinema-text-secondary, #5d6472);
    margin-bottom: 48rpx;
    text-align: center;
}

.empty-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 28rpx 72rpx;
    border-radius: 56rpx;
    box-shadow: var(--cinema-shadow-medium, 0 20rpx 52rpx rgba(8, 10, 16, 0.12));
    transition: all 0.3s ease;

    &:active {
        transform: translateY(2rpx);
        box-shadow: var(--cinema-shadow-soft, 0 18rpx 44rpx rgba(8, 10, 16, 0.08));
    }
}

.empty-action-text {
    font-size: 32rpx;
    font-weight: 700;
}

/* 订单列表 */
.order-list {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    padding-bottom: 12rpx;
}

/* 加载更多 */
.load-more {
    padding: 40rpx 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.load-more-loading {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.load-more-text {
    font-size: 28rpx;
    color: var(--cinema-text-secondary, #5d6472);
}

.load-more-clickable {
    font-weight: 600;
    padding: 16rpx 32rpx;
    border-radius: 48rpx;
    background: rgba(255, 255, 255, 0.72);
    transition: all 0.2s ease;

    &:active {
        opacity: 0.7;
        transform: scale(0.98);
    }
}

.order-page :deep(.tn-tabs) {
    background: transparent;
}

.order-page :deep(.tn-tabs__bar) {
    background: linear-gradient(135deg, var(--cinema-primary, #c6a86a) 0%, var(--cinema-accent, #e8c98e) 100%);
}

.order-page :deep(.tn-tabs-item) {
    color: var(--cinema-text-secondary, #5d6472);
}
</style>
