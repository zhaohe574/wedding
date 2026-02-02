<template>
    <view class="order-quick-entry mx-[24rpx] mt-[24rpx]" v-if="content.enabled !== 0">
        <!-- 标题栏 -->
        <view class="flex items-center justify-between mb-[20rpx] px-[8rpx]" v-if="content.title">
            <text class="text-[32rpx] font-semibold text-[#1E293B]">{{ content.title }}</text>
            <view
                class="flex items-center cursor-pointer"
                @click="navigateToOrderList"
            >
                <text class="text-[26rpx] text-[#64748B]">全部订单</text>
                <tn-icon name="right" size="28" color="#94A3B8" class="ml-[4rpx]" />
            </view>
        </view>

        <!-- 订单状态入口卡片 -->
        <view
            class="order-card bg-white rounded-[16rpx] p-[32rpx]"
            :style="{
                boxShadow: content.show_shadow !== false ? '0 2rpx 12rpx rgba(0, 0, 0, 0.08)' : 'none'
            }"
        >
            <view 
                class="grid gap-[24rpx]"
                :style="{ 
                    gridTemplateColumns: `repeat(${columns}, 1fr)` 
                }"
            >
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="order-entry flex flex-col items-center"
                    @click="handleClick(item)"
                >
                    <!-- 图标容器 -->
                    <view class="icon-container relative mb-[16rpx]">
                        <!-- 自定义图标 -->
                        <view
                            v-if="item.icon"
                            class="icon-bg w-[72rpx] h-[72rpx] rounded-full flex items-center justify-center"
                            :style="{ backgroundColor: getStatusColor(item.status) + '15' }"
                        >
                            <image
                                :src="getImageUrl(item.icon)"
                                class="w-[40rpx] h-[40rpx]"
                                mode="aspectFit"
                            />
                        </view>
                        <!-- 默认图标 -->
                        <view
                            v-else
                            class="icon-bg w-[72rpx] h-[72rpx] rounded-full flex items-center justify-center"
                            :style="{ backgroundColor: getStatusColor(item.status) + '15' }"
                        >
                            <tn-icon 
                                :name="getStatusIconName(item.status)" 
                                :size="36" 
                                :color="getStatusColor(item.status)" 
                            />
                        </view>
                        
                        <!-- 数量角标 -->
                        <view
                            v-if="item.count && item.count > 0"
                            class="count-badge absolute -top-[8rpx] -right-[8rpx] min-w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center px-[8rpx]"
                            :style="{ backgroundColor: $theme.ctaColor }"
                        >
                            <text class="text-white text-[20rpx] font-semibold">
                                {{ item.count > 99 ? '99+' : item.count }}
                            </text>
                        </view>
                    </view>
                    
                    <!-- 文字 -->
                    <text class="text-[24rpx] text-[#334155]">{{ item.name }}</text>
                </view>
            </view>
        </view>

        <!-- 最近订单预览 -->
        <view
            v-if="content.show_recent && recentOrders.length > 0"
            class="recent-orders mt-[24rpx] bg-white rounded-[16rpx] overflow-hidden"
            :style="{
                boxShadow: content.show_shadow !== false ? '0 2rpx 12rpx rgba(0, 0, 0, 0.08)' : 'none'
            }"
        >
            <!-- 标题 -->
            <view class="p-[32rpx] pb-[24rpx]">
                <text class="text-[28rpx] font-semibold text-[#1E293B]">最近订单</text>
            </view>
            
            <!-- 订单列表 -->
            <view
                v-for="(order, index) in recentOrders"
                :key="order.id || index"
                class="order-item px-[32rpx] py-[24rpx] border-t border-[#F1F5F9]"
                @click="navigateToOrder(order)"
            >
                <view class="flex items-start justify-between">
                    <view class="flex-1 mr-[24rpx]">
                        <!-- 服务名称和状态 -->
                        <view class="flex items-center mb-[12rpx]">
                            <text class="text-[28rpx] text-[#1E293B] font-medium flex-1">
                                {{ order.service_name || '服务订单' }}
                            </text>
                            <view
                                class="status-badge ml-[16rpx] px-[16rpx] py-[6rpx] rounded-[8rpx]"
                                :style="{
                                    backgroundColor: getStatusColor(order.status) + '15',
                                    color: getStatusColor(order.status)
                                }"
                            >
                                <text class="text-[22rpx] font-medium">{{ getStatusText(order.status) }}</text>
                            </view>
                        </view>
                        
                        <!-- 订单号 -->
                        <text class="text-[24rpx] text-[#94A3B8]">{{ order.order_no || '' }}</text>
                    </view>
                    
                    <!-- 价格 -->
                    <view class="price-section flex items-baseline">
                        <text class="text-[24rpx] font-semibold" :style="{ color: $theme.ctaColor }">
                            {{ content.currency || '¥' }}
                        </text>
                        <text class="text-[36rpx] font-bold ml-[4rpx]" :style="{ color: $theme.ctaColor }">
                            {{ order.amount || '0.00' }}
                        </text>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { navigateTo } from '@/utils/util'
import { storeToRefs } from 'pinia'
import request from '@/utils/request'

const appStore = useAppStore()
const $theme = useThemeStore()
const userStore = useUserStore()
const { isLogin } = storeToRefs(userStore)
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
    order_sn?: string
    order_no?: string
    service_name?: string
    order_status?: number
    status?: string | number | undefined
    pay_amount?: string | number
    amount?: string | number
}

interface ContentType {
    enabled?: number
    title?: string
    show_shadow?: boolean
    show_recent?: boolean
    currency?: string
    columns?: number
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

// 最近订单数据（动态加载）
const recentOrdersData = ref<OrderItem[]>([])

// 过滤显示的入口列表
const showList = computed(() => {
    return props.content.data?.filter((item: EntryItem) => item.is_show !== '0') || []
})

// 每行显示的列数
const columns = computed(() => {
    return props.content.columns || 5 // 默认5列
})

// 最近订单列表（优先使用动态加载的数据）
const recentOrders = computed(() => {
    if (recentOrdersData.value.length > 0) {
        return recentOrdersData.value.slice(0, 3)
    }
    return props.content.recent_orders?.slice(0, 3) || []
})

// 获取状态颜色
const getStatusColor = (status: string | number | undefined) => {
    if (status === undefined) return $theme.primaryColor
    // 兼容字符串和数字类型的状态
    const statusNum = typeof status === 'string' ? parseInt(status) : status
    
    const colors: Record<number, string> = {
        0: $theme.primaryColor,          // 待确认 - 主色
        1: $theme.ctaColor,              // 待付款 - CTA色
        2: $theme.primaryColor,          // 已支付 - 主色
        3: $theme.secondaryColor,        // 服务中 - 辅助色
        4: '#22C55E',                    // 已完成 - 绿色
        5: '#22C55E',                    // 已评价 - 绿色
        6: '#94A3B8',                    // 已取消 - 灰色
        7: '#EF4444',                    // 已暂停 - 红色
        8: '#EF4444'                     // 已退款 - 红色
    }
    return colors[statusNum] || $theme.primaryColor
}

// 获取状态图标名称
const getStatusIconName = (status: string | number | undefined) => {
    if (status === undefined) return 'order'
    // 兼容字符串和数字类型的状态
    const statusNum = typeof status === 'string' ? parseInt(status) : status
    
    const icons: Record<number, string> = {
        0: 'time',                       // 待确认
        1: 'ticket',                     // 待付款
        2: 'money',               // 已支付
        3: 'loading',                    // 服务中
        4: 'success-circle',               // 已完成
        5: 'keyboard-circle',                       // 已评价
        6: 'close-circle',               // 已取消
        7: 'reduce-circle',               // 已暂停
        8: 'brand'                     // 已退款
    }
    return icons[statusNum] || 'order'
}

// 获取状态文字
const getStatusText = (status: string | number | undefined) => {
    if (status === undefined) return '未知'
    // 兼容字符串和数字类型的状态
    const statusNum = typeof status === 'string' ? parseInt(status) : status
    
    const texts: Record<number, string> = {
        0: '待确认',
        1: '待付款',
        2: '已支付',
        3: '服务中',
        4: '已完成',
        5: '已评价',
        6: '已取消',
        7: '已暂停',
        8: '已退款'
    }
    return texts[statusNum] || '未知'
}

// 加载最近订单
const loadRecentOrders = async () => {
    // 只在登录且开启显示最近订单时加载
    if (!isLogin.value || !props.content.show_recent) {
        return
    }
    
    try {
        const res = await request.get({ 
            url: '/order/lists',
            data: {
                page: 1,
                page_size: 3
            }
        }, { isAuth: true })
        
        if (res && res.data && Array.isArray(res.data)) {
            // 处理订单数据，提取第一个服务项的名称作为服务名称
            recentOrdersData.value = res.data.map((order: any) => ({
                id: order.id,
                order_no: order.order_sn,
                service_name: order.items?.[0]?.package_name || '服务订单',
                status: order.order_status,
                amount: order.pay_amount
            }))
        }
    } catch (error) {
        console.error('加载最近订单失败:', error)
    }
}

// 点击入口
const handleClick = (item: EntryItem) => {
    if (item.link?.path) {
        // 如果 link 存在但 query 不存在，使用 status 构造 query
        const link = {
            ...item.link,
            query: item.link.query || { status: item.status }
        }
        navigateTo(link)
    } else {
        navigateTo({
            path: '/pages/order/order',
            query: { status: item.status }
        })
    }
}

// 跳转到全部订单
const navigateToOrderList = () => {
    navigateTo({
        path: '/pages/order/order'
    })
}

// 跳转到订单详情
const navigateToOrder = (order: OrderItem) => {
    if (order.id) {
        navigateTo({
            path: '/pages/order_detail/order_detail',
            query: { id: order.id }
        })
    }
}

// 组件挂载时加载最近订单
onMounted(() => {
    loadRecentOrders()
})
</script>

<style lang="scss" scoped>
.order-quick-entry {
    .order-card,
    .recent-orders {
        transition: all 0.2s ease;
    }
    
    .order-entry {
        transition: all 0.2s ease;
        cursor: pointer;
        
        &:active {
            transform: scale(0.95);
            opacity: 0.8;
        }
        
        .icon-container {
            .icon-bg {
                transition: all 0.2s ease;
            }
            
            .count-badge {
                box-shadow: 0 4rpx 12rpx rgba(249, 115, 22, 0.4);
            }
        }
    }
    
    .order-item {
        transition: all 0.2s ease;
        cursor: pointer;
        
        &:active {
            background-color: #F8FAFC;
        }
        
        .status-badge {
            transition: all 0.2s ease;
        }
    }
}
</style>
