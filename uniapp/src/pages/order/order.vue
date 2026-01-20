<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="我的订单"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="order-list">
        <!-- 状态筛选 -->
        <view class="status-tabs bg-white sticky top-0 z-10">
            <scroll-view scroll-x class="whitespace-nowrap">
                <view 
                    v-for="tab in statusTabs" 
                    :key="tab.value"
                    class="inline-block px-4 py-3 text-sm"
                    :class="currentStatus === tab.value ? 'text-primary border-b-2 border-primary font-medium' : 'text-gray-500'"
                    @click="changeStatus(tab.value)"
                >
                    {{ tab.label }}
                    <text v-if="statistics[tab.key] > 0" class="ml-1 text-xs">({{ statistics[tab.key] }})</text>
                </view>
            </scroll-view>
        </view>

        <!-- 订单列表 -->
        <view class="p-3">
            <view v-if="loading && orders.length === 0" class="py-20 text-center text-gray-400">
                加载中...
            </view>
            <view v-else-if="orders.length === 0" class="py-20 text-center text-gray-400">
                <image src="/static/images/empty.png" class="w-32 h-32 mx-auto mb-4" mode="aspectFit" />
                <text>暂无订单</text>
            </view>
            <view v-else>
                <view 
                    v-for="order in orders" 
                    :key="order.id"
                    class="bg-white rounded-lg mb-3 overflow-hidden"
                    @click="goDetail(order.id)"
                >
                    <!-- 订单头部 -->
                    <view class="flex justify-between items-center px-4 py-3 border-b border-gray-100">
                        <text class="text-sm text-gray-500">订单号: {{ order.order_sn }}</text>
                        <text class="text-sm" :class="getStatusColor(order.order_status)">
                            {{ order.order_status_desc }}
                        </text>
                    </view>

                    <!-- 订单项 -->
                    <view class="px-4 py-3">
                        <view 
                            v-for="item in order.items" 
                            :key="item.id"
                            class="flex items-center py-2"
                        >
                            <image 
                                :src="item.staff?.avatar || '/static/images/default-avatar.png'" 
                                class="w-16 h-16 rounded-lg mr-3"
                                mode="aspectFill"
                            />
                            <view class="flex-1">
                                <view class="text-sm font-medium">{{ item.staff_name }}</view>
                                <view class="text-xs text-gray-400 mt-1">{{ item.package_name }}</view>
                                <view class="text-xs text-gray-400">{{ item.service_date }}</view>
                            </view>
                            <view class="text-right">
                                <view class="text-primary font-medium">¥{{ item.price }}</view>
                            </view>
                        </view>
                    </view>

                    <!-- 订单金额 -->
                    <view class="px-4 py-3 border-t border-gray-100 flex justify-between items-center">
                        <text class="text-xs text-gray-400">{{ order.create_time }}</text>
                        <view>
                            <text class="text-sm">实付: </text>
                            <text class="text-primary font-bold text-lg">¥{{ order.pay_amount }}</text>
                        </view>
                    </view>

                    <!-- 操作按钮 -->
                    <view class="px-4 py-3 border-t border-gray-100 flex justify-end gap-3">
                        <button 
                            v-if="order.order_status === 0"
                            class="btn-outline"
                            @click.stop="handleCancel(order)"
                        >取消订单</button>
                        <button 
                            v-if="order.order_status === 0"
                            class="btn-primary"
                            @click.stop="handlePay(order)"
                        >立即支付</button>
                        <button 
                            v-if="order.order_status === 2"
                            class="btn-primary"
                            @click.stop="handleConfirm(order)"
                        >确认完成</button>
                        <button 
                            v-if="order.order_status === 1"
                            class="btn-outline"
                            @click.stop="handleRefund(order)"
                        >申请退款</button>
                        <button 
                            v-if="[3, 4, 5].includes(order.order_status)"
                            class="btn-outline"
                            @click.stop="handleDelete(order)"
                        >删除订单</button>
                    </view>
                </view>

                <!-- 加载更多 -->
                <view v-if="hasMore" class="py-4 text-center text-gray-400 text-sm">
                    <text v-if="loading">加载中...</text>
                    <text v-else @click="loadMore">加载更多</text>
                </view>
                <view v-else-if="orders.length > 0" class="py-4 text-center text-gray-400 text-sm">
                    没有更多了
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { onLoad, onShow, onReachBottom } from '@dcloudio/uni-app'
import { getOrderList, getOrderStatistics, cancelOrder, confirmOrder, deleteOrder } from '@/api/order'

const statusTabs = [
    { label: '全部', value: '', key: 'all' },
    { label: '待支付', value: 0, key: 'pending' },
    { label: '已支付', value: 1, key: 'paid' },
    { label: '服务中', value: 2, key: 'in_service' },
    { label: '已完成', value: 3, key: 'completed' },
    { label: '退款', value: 5, key: 'refund' }
]

const currentStatus = ref<number | string>('')
const orders = ref<any[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)
const statistics = reactive<any>({})

const getStatusColor = (status: number) => {
    const colors: Record<number, string> = {
        0: 'text-orange-500',
        1: 'text-blue-500',
        2: 'text-purple-500',
        3: 'text-green-500',
        4: 'text-gray-500',
        5: 'text-red-500'
    }
    return colors[status] || 'text-gray-500'
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
        const list = res.data || []
        
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

const changeStatus = (status: number | string) => {
    currentStatus.value = status
    fetchOrders(true)
}

const loadMore = () => {
    if (hasMore.value && !loading.value) {
        page.value++
        fetchOrders()
    }
}

const goDetail = (id: number) => {
    uni.navigateTo({ url: `/pages/order_detail/order_detail?id=${id}` })
}

const handlePay = (order: any) => {
    uni.navigateTo({ url: `/pages/order_detail/order_detail?id=${order.id}&action=pay` })
}

const handleCancel = async (order: any) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要取消该订单吗？'
    })
    if (res.confirm) {
        try {
            await cancelOrder({ id: order.id, reason: '用户取消' })
            uni.showToast({ title: '订单已取消' })
            fetchOrders(true)
            fetchStatistics()
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

const handleConfirm = async (order: any) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定服务已完成吗？'
    })
    if (res.confirm) {
        try {
            await confirmOrder({ id: order.id })
            uni.showToast({ title: '订单已完成' })
            fetchOrders(true)
            fetchStatistics()
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

const handleRefund = (order: any) => {
    uni.navigateTo({ url: `/pages/order_detail/order_detail?id=${order.id}&action=refund` })
}

const handleDelete = async (order: any) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要删除该订单吗？'
    })
    if (res.confirm) {
        try {
            await deleteOrder({ id: order.id })
            uni.showToast({ title: '删除成功' })
            fetchOrders(true)
            fetchStatistics()
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

onLoad((options: any) => {
    if (options.status) {
        currentStatus.value = Number(options.status)
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
.order-list {
    min-height: 100vh;
    background-color: #f5f5f5;
}

.btn-outline {
    padding: 12rpx 24rpx;
    font-size: 24rpx;
    border: 1rpx solid #ddd;
    border-radius: 8rpx;
    background: #fff;
    color: #666;
}

.btn-primary {
    padding: 12rpx 24rpx;
    font-size: 24rpx;
    border: none;
    border-radius: 8rpx;
    background: var(--primary-color, #ff6b35);
    color: #fff;
}
</style>
