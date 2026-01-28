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
    <view class="order-page">
        <!-- 状态筛选标签 -->
        <view class="status-tabs-wrapper">
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

        <!-- 订单列表 -->
        <view class="order-list-wrapper">
            <!-- 加载中 -->
            <view v-if="loading && orders.length === 0" class="loading-state">
                <view class="loading-content">
                    <tn-loading size="80" mode="flower" :color="$theme.primaryColor" />
                    <text class="loading-text">加载中...</text>
                </view>
            </view>
            
            <!-- 空状态 -->
            <view v-else-if="orders.length === 0" class="empty-state">
                <view class="empty-icon-wrapper">
                    <tn-icon name="file-text" size="200" color="#E5E5E5" />
                </view>
                <text class="empty-title">暂无订单</text>
                <text class="empty-subtitle">快去预约心仪的服务吧~</text>
                <view 
                    class="empty-action-btn"
                    :style="{ 
                        background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                        color: $theme.btnColor
                    }"
                    @click="goHome"
                >
                    <text class="empty-action-text" :style="{ color: $theme.btnColor }">去预约</text>
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

const $theme = useThemeStore()

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

const getStatusKey = (status: number) => {
    const statusMap: Record<number, string> = {
        0: 'unpaid',
        1: 'paid',
        2: 'in_service',
        3: 'completed',
        4: 'cancelled',
        5: 'refund'
    }
    return statusMap[status] || 'unpaid'
}

const getTimeSlotLabel = (timeSlot: any) => {
    const map: Record<number, string> = {
        0: '全天',
        1: '早礼',
        2: '午宴',
        3: '晚宴'
    }
    const slot = Number(timeSlot ?? -1)
    return Number.isFinite(slot) && slot >= 0 ? (map[slot] || '未知场次') : '未选择场次'
}

const buildActions = (status: number) => {
    if (status === 0) {
        return [
            { text: '取消', type: 'secondary', action: 'cancel' },
            { text: '支付', type: 'primary', action: 'pay' }
        ]
    }
    if (status === 2) {
        return [{ text: '确认完成', type: 'primary', action: 'confirm' }]
    }
    if ([3, 4, 5].includes(status)) {
        return [{ text: '删除', type: 'secondary', action: 'delete' }]
    }
    return []
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
            const discount = Number(order.discount_amount || 0) + Number(order.coupon_amount || 0)
            return {
                id: order.id,
                orderNo: order.order_sn,
                status: getStatusKey(order.order_status),
                createTime: order.create_time,
                location: order.service_address || '服务地址未填写',
                originalPrice: Number(order.total_amount || 0),
                discount,
                actualPrice: Number(order.pay_amount || 0),
                items: (order.items || []).map((item: any) => ({
                    id: item.id,
                    staffId: item.staff_id,
                    staffName: item.staff_name,
                    staffAvatar: item.staff?.avatar || '/static/images/default-avatar.png',
                    packageName: item.package_name,
                    serviceDate: item.service_date,
                    timeSlot: item.time_slot,
                    timeSlotDesc: getTimeSlotLabel(item.time_slot)
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
    uni.switchTab({ url: '/pages/index/index' })
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
    background: #F6F6F6;
    padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

/* 状态筛选标签 */
.status-tabs-wrapper {
    background: #FFFFFF;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    position: sticky;
    top: 0;
    z-index: 10;
    margin-bottom: 24rpx;
}

.tabs-main {
    width: 100%;
}

/* 订单列表容器 */
.order-list-wrapper {
    padding: 0 24rpx;
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
    color: #999999;
}

/* 空状态 */
.empty-state {
    min-height: 60vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48rpx;
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
    color: #333333;
    margin-bottom: 16rpx;
}

.empty-subtitle {
    font-size: 28rpx;
    color: #999999;
    margin-bottom: 48rpx;
}

.empty-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 28rpx 72rpx;
    border-radius: 56rpx;
    box-shadow: 0 12rpx 32rpx rgba(124, 58, 237, 0.4);
    transition: all 0.3s ease;
    
    &:active {
        transform: translateY(2rpx);
        box-shadow: 0 6rpx 16rpx rgba(124, 58, 237, 0.4);
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
    padding-bottom: 24rpx;
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
    color: #999999;
}

.load-more-clickable {
    font-weight: 600;
    padding: 16rpx 32rpx;
    border-radius: 48rpx;
    transition: all 0.2s ease;
    
    &:active {
        opacity: 0.7;
        transform: scale(0.98);
    }
}
</style>
