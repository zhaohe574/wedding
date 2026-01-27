<template>
    <view class="order-quick-entry mx-[20rpx] mt-[20rpx]" v-if="content.enabled !== 0">
        <!-- 标题栏 -->
        <view class="flex items-center justify-between mb-[20rpx]" v-if="content.title">
            <view class="text-lg font-medium text-[#1E293B]">{{ content.title }}</view>
            <view
                class="flex items-center text-sm text-[#64748B] cursor-pointer"
                @click="navigateToOrderList"
            >
                <text>全部订单</text>
                <text class="ml-[8rpx]">></text>
            </view>
        </view>

        <!-- 订单状态入口 - 横向排列 -->
        <view
            class="bg-white rounded-[16rpx] p-[30rpx]"
            :style="{
                boxShadow:
                    content.show_shadow !== false ? '0 2rpx 12rpx rgba(0, 0, 0, 0.05)' : 'none'
            }"
        >
            <view class="flex items-center justify-around">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="flex flex-col items-center cursor-pointer"
                    @click="handleClick(item)"
                >
                    <!-- 图标 -->
                    <view class="relative mb-[16rpx]">
                        <image
                            v-if="item.icon"
                            :src="getImageUrl(item.icon)"
                            class="w-[56rpx] h-[56rpx]"
                            mode="aspectFit"
                        />
                        <view
                            v-else
                            class="w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center"
                            :style="{ backgroundColor: getStatusColor(item.status) + '15' }"
                        >
                            <text
                                class="text-[28rpx]"
                                :style="{ color: getStatusColor(item.status) }"
                                >{{ getStatusIcon(item.status) }}</text
                            >
                        </view>
                        <!-- 数量角标 -->
                        <view
                            v-if="item.count && item.count > 0"
                            class="absolute -top-[10rpx] -right-[10rpx] min-w-[36rpx] h-[36rpx] bg-[#F97316] rounded-full flex items-center justify-center px-[8rpx]"
                        >
                            <text class="text-white text-[22rpx] font-medium">
                                {{ item.count > 99 ? '99+' : item.count }}
                            </text>
                        </view>
                    </view>
                    <!-- 文字 -->
                    <text class="text-[26rpx] text-[#334155]">{{ item.name }}</text>
                </view>
            </view>
        </view>

        <!-- 最近订单预览 -->
        <view
            v-if="content.show_recent && recentOrders.length > 0"
            class="mt-[20rpx] bg-white rounded-[16rpx] overflow-hidden"
            :style="{
                boxShadow:
                    content.show_shadow !== false ? '0 2rpx 12rpx rgba(0, 0, 0, 0.05)' : 'none'
            }"
        >
            <view class="p-[24rpx] border-b border-[#F1F5F9]">
                <text class="text-[28rpx] font-medium text-[#1E293B]">最近订单</text>
            </view>
            <view
                v-for="(order, index) in recentOrders"
                :key="order.id || index"
                class="p-[24rpx] border-b border-[#F1F5F9] last:border-b-0 cursor-pointer active:bg-[#F8FAFC]"
                @click="navigateToOrder(order)"
            >
                <view class="flex items-center justify-between">
                    <view class="flex-1">
                        <view class="flex items-center mb-[8rpx]">
                            <text class="text-[28rpx] text-[#1E293B] font-medium">{{
                                order.service_name || '服务订单'
                            }}</text>
                            <view
                                class="ml-[16rpx] px-[12rpx] py-[4rpx] rounded-[6rpx] text-[22rpx]"
                                :style="{
                                    backgroundColor: getStatusColor(order.status) + '15',
                                    color: getStatusColor(order.status)
                                }"
                            >
                                {{ getStatusText(order.status) }}
                            </view>
                        </view>
                        <text class="text-[24rpx] text-[#64748B]">{{ order.order_no || '' }}</text>
                    </view>
                    <view class="text-right">
                        <text class="text-[32rpx] font-semibold text-[#F97316]">
                            {{ content.currency || '¥' }}{{ order.amount || '0.00' }}
                        </text>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/stores/app'
import { navigateTo } from '@/utils/util'

const appStore = useAppStore()
const getImageUrl = appStore.getImageUrl

interface EntryItem {
    is_show?: string
    icon?: string
    name: string
    status: string
    count?: number
    link?: {
        path: string
        name?: string
        type?: string
        query?: Record<string, any>
    }
}

interface OrderItem {
    id?: string | number
    order_no?: string
    service_name?: string
    status: string
    amount?: string | number
}

interface ContentType {
    enabled?: number
    title?: string
    show_shadow?: boolean
    show_recent?: boolean
    currency?: string
    data?: EntryItem[]
    recent_orders?: OrderItem[]
}

const props = withDefaults(
    defineProps<{
        content: ContentType
        styles?: Record<string, any>
    }>(),
    {
        content: () => ({
            enabled: 1,
            title: '我的订单',
            show_shadow: true,
            show_recent: false,
            currency: '¥',
            data: []
        }),
        styles: () => ({})
    }
)

// 过滤显示的入口列表
const showList = computed(() => {
    return props.content.data?.filter((item: EntryItem) => item.is_show !== '0') || []
})

// 最近订单列表
const recentOrders = computed(() => {
    return props.content.recent_orders?.slice(0, 3) || []
})

// 获取状态颜色
const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending_pay: '#F97316', // 待付款 - 橙色
        pending_confirm: '#3B82F6', // 待确认 - 蓝色
        processing: '#8B5CF6', // 进行中 - 紫色
        completed: '#22C55E', // 已完成 - 绿色
        cancelled: '#94A3B8', // 已取消 - 灰色
        refund: '#EF4444' // 退款 - 红色
    }
    return colors[status] || '#7C3AED'
}

// 获取状态图标
const getStatusIcon = (status: string) => {
    const icons: Record<string, string> = {
        pending_pay: '付',
        pending_confirm: '待',
        processing: '进',
        completed: '完',
        cancelled: '消',
        refund: '退'
    }
    return icons[status] || '单'
}

// 获取状态文字
const getStatusText = (status: string) => {
    const texts: Record<string, string> = {
        pending_pay: '待付款',
        pending_confirm: '待确认',
        processing: '进行中',
        completed: '已完成',
        cancelled: '已取消',
        refund: '退款中'
    }
    return texts[status] || '未知'
}

// 点击入口
const handleClick = (item: EntryItem) => {
    if (item.link?.path) {
        navigateTo(item.link)
    } else {
        // 默认跳转到订单列表，带上状态参数
        navigateTo({
            path: '/pages/order/list',
            query: { status: item.status }
        })
    }
}

// 跳转到全部订单
const navigateToOrderList = () => {
    navigateTo({
        path: '/pages/order/list'
    })
}

// 跳转到订单详情
const navigateToOrder = (order: OrderItem) => {
    if (order.id) {
        navigateTo({
            path: '/pages/order/detail',
            query: { id: order.id }
        })
    }
}
</script>

<style lang="scss" scoped>
.order-quick-entry {
    // 基础样式
}
</style>
