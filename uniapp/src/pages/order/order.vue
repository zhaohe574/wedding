<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
        <!-- #endif -->
    </page-meta>
    <view class="order-page">
        <!-- 顶部导航栏 -->
        <!-- #ifndef H5 -->
        <base-navbar title="我的订单" :back-icon="false" />
        <!-- #endif -->
        
        <!-- 状态筛选标签 -->
        <view class="status-tabs-wrapper">
            <tn-tabs v-model="currentTabIndex" :scroll="true" height="88rpx" class="tabs-main">
                <tn-tabs-item
                    v-for="(tab, index) in statusTabs"
                    :key="index"
                    :title="tab.label"
                    :badge="statistics[tab.key] > 0 ? String(statistics[tab.key]) : ''"
                />
            </tn-tabs>
        </view>

        <!-- 订单列表 -->
        <view class="order-list-wrapper">
            <!-- 加载中 -->
            <view v-if="loading && orders.length === 0" class="loading-state">
                <tn-loading size="60" mode="flower" />
                <text class="loading-text">加载中...</text>
            </view>
            
            <!-- 空状态 -->
            <view v-else-if="orders.length === 0" class="empty-state">
                <view class="empty-icon-wrapper">
                    <tn-icon name="file-text" size="120" color="#d1d5db" />
                </view>
                <text class="empty-title">暂无订单</text>
                <text class="empty-subtitle">快去预约服务吧~</text>
            </view>
            
            <!-- 订单列表 - 使用OrderCard组件 -->
            <view v-else class="order-list">
                <order-card
                    v-for="order in orders"
                    :key="order.id"
                    :order="order"
                    @click="goDetail"
                    @pay="handlePay"
                    @cancel="handleCancel"
                    @confirm="handleConfirm"
                    @refund="handleRefund"
                    @delete="handleDelete"
                />

                <!-- 加载更多提示 -->
                <view v-if="hasMore" class="load-more">
                    <text v-if="loading" class="load-more-text">加载中...</text>
                    <text v-else class="load-more-text load-more-clickable" @click="loadMore">加载更多</text>
                </view>
                <view v-else-if="orders.length > 0" class="load-more">
                    <text class="load-more-text">没有更多了</text>
                </view>
            </view>
        </view>

        <tabbar />
    </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
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

const themeStore = useThemeStore()

const statusTabs = [
    { label: '全部', value: '', key: 'all' },
    { label: '待支付', value: 0, key: 'pending' },
    { label: '已支付', value: 1, key: 'paid' },
    { label: '服务中', value: 2, key: 'in_service' },
    { label: '已完成', value: 3, key: 'completed' },
    { label: '退款', value: 5, key: 'refund' }
]

const currentTabIndex = ref(0)
const currentStatus = computed(() => statusTabs[currentTabIndex.value].value)
const orders = ref<any[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)
const statistics = reactive<any>({
    all: 0,
    pending: 0,
    paid: 0,
    in_service: 0,
    completed: 0,
    refund: 0
})

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
        const list = (res.data || []).map((order: any) => ({
            id: order.id,
            orderSn: order.order_sn,
            status: order.order_status,
            statusText: order.order_status_desc,
            createTime: order.create_time,
            items: (order.items || []).map((item: any) => ({
                id: item.id,
                staffId: item.staff_id,
                staffName: item.staff_name,
                staffAvatar: item.staff?.avatar || '/static/images/default-avatar.png',
                packageName: item.package_name,
                serviceDate: item.service_date,
                price: item.price
            })),
            totalAmount: order.total_amount,
            discountAmount: order.discount_amount || 0,
            payAmount: order.pay_amount
        }))

        if (refresh) {
            orders.value = list
        } else {
            orders.value.push(...list)
        }

        hasMore.value = list.length === 10
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
        const statusValue = Number(options.status)
        const index = statusTabs.findIndex(tab => tab.value === statusValue)
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
    background: linear-gradient(180deg, var(--color-primary-light-9, #FAF5FF) 0%, #F5F5F5 100%);
    padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

/* 状态筛选标签 */
.status-tabs-wrapper {
    background: #ffffff;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    position: sticky;
    top: 0;
    z-index: 10;
    margin-bottom: 16rpx; // 使用sm间距
}

.tabs-main {
    width: 100%;
}

/* 订单列表容器 */
.order-list-wrapper {
    padding: 0 24rpx; // 使用md间距
}

/* 加载状态 */
.loading-state {
    padding: 160rpx 0;
    text-align: center;
}

.loading-text {
    display: block;
    margin-top: 32rpx; // 使用lg间距
    font-size: 28rpx;
    color: var(--color-muted);
}

/* 空状态 */
.empty-state {
    padding: 160rpx 0;
    text-align: center;
}

.empty-icon-wrapper {
    width: 256rpx;
    height: 256rpx;
    margin: 0 auto 32rpx; // 使用lg间距
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-title {
    display: block;
    font-size: 32rpx;
    color: var(--color-muted);
}

.empty-subtitle {
    display: block;
    margin-top: 16rpx; // 使用sm间距
    font-size: 24rpx;
    color: var(--color-disabled);
}

/* 订单列表 */
.order-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx; // 使用sm间距
}

/* 加载更多 */
.load-more {
    padding: 32rpx 0; // 使用lg间距
    text-align: center;
}

.load-more-text {
    font-size: 28rpx;
    color: var(--color-muted);
}

.load-more-clickable {
    color: var(--color-primary);
    font-weight: 500;
}
</style>
