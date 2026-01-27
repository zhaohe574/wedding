<template>
    <view v-if="content.enabled && showList.length" class="data-stats mx-[20rpx] mt-[20rpx]">
        <!-- 横向排列 -->
        <view v-if="content.style == 1" class="horizontal-layout bg-white rounded-lg p-[24rpx]">
            <view class="flex justify-around">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="flex flex-col items-center"
                    @click="handleClick(item)"
                >
                    <view class="stat-icon mb-[12rpx]">
                        <tn-icon :name="item.icon" size="48" color="#ff6b9d"></tn-icon>
                    </view>
                    <view class="text-2xl font-bold text-gray-900 mb-[8rpx]">
                        {{ getStatValue(item.value) }}
                        <text class="text-sm text-gray-500">{{ item.unit }}</text>
                    </view>
                    <view class="text-xs text-gray-500">{{ item.title }}</view>
                </view>
            </view>
        </view>

        <!-- 纵向排列 -->
        <view v-if="content.style == 2" class="vertical-layout">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="stat-card bg-white rounded-lg p-[24rpx] mb-[20rpx] flex items-center"
                @click="handleClick(item)"
            >
                <view class="stat-icon mr-[24rpx]">
                    <tn-icon :name="item.icon" size="48" color="#ff6b9d"></tn-icon>
                </view>
                <view class="flex-1">
                    <view class="text-xs text-gray-500 mb-[8rpx]">{{ item.title }}</view>
                    <view class="text-2xl font-bold text-gray-900">
                        {{ getStatValue(item.value) }}
                        <text class="text-sm text-gray-500">{{ item.unit }}</text>
                    </view>
                </view>
                <tn-icon name="right" size="32" color="#cccccc"></tn-icon>
            </view>
        </view>

        <!-- 网格布局 -->
        <view v-if="content.style == 3" class="grid-layout bg-white rounded-lg p-[24rpx]">
            <view class="grid grid-cols-2 gap-4">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="stat-grid-item p-[24rpx] bg-gradient-to-br from-pink-50 to-purple-50 rounded-lg"
                    @click="handleClick(item)"
                >
                    <view class="stat-icon mb-[12rpx]">
                        <tn-icon :name="item.icon" size="40" color="#ff6b9d"></tn-icon>
                    </view>
                    <view class="text-xl font-bold text-gray-900 mb-[8rpx]">
                        {{ getStatValue(item.value) }}
                        <text class="text-xs text-gray-500">{{ item.unit }}</text>
                    </view>
                    <view class="text-xs text-gray-500">{{ item.title }}</view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { getUserCenter } from '@/api/user'
import { getMyCouponStats } from '@/api/coupon'

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    }
})

const statsData = ref<any>({
    order_count: 0,
    coupon_count: 0,
    collect_count: 0,
    view_count: 0
})

// 过滤显示的统计项
const showList = computed(() => {
    return props.content.data?.filter((item: any) => item.is_show == '1') || []
})

// 获取统计数值
const getStatValue = (key: string) => {
    return statsData.value[key] || 0
}

// 处理点击事件
const handleClick = (item: any) => {
    // 根据不同的统计类型跳转到对应页面
    const routeMap: any = {
        order_count: '/pages/order/list',
        coupon_count: '/pages/coupon/list',
        collect_count: '/pages/collect/list',
        view_count: '/pages/history/list'
    }

    const url = routeMap[item.value]
    if (url) {
        uni.navigateTo({ url })
    }
}

// 加载统计数据
const loadStats = async () => {
    try {
        // 获取用户中心数据
        const centerRes = await getUserCenter()
        if (centerRes) {
            statsData.value.order_count = centerRes.order_count || 0
            statsData.value.collect_count = centerRes.collect_count || 0
            statsData.value.view_count = centerRes.view_count || 0
        }

        // 获取优惠券统计
        const couponRes = await getMyCouponStats()
        if (couponRes) {
            statsData.value.coupon_count = couponRes.total || 0
        }
    } catch (error) {
        console.error('加载统计数据失败:', error)
    }
}

onMounted(() => {
    loadStats()
})
</script>

<style scoped lang="scss">
.data-stats {
    .horizontal-layout,
    .vertical-layout .stat-card,
    .grid-layout {
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
    }

    .stat-card {
        transition: all 0.3s ease;

        &:active {
            transform: scale(0.98);
        }
    }

    .stat-grid-item {
        transition: all 0.3s ease;

        &:active {
            transform: scale(0.98);
        }
    }
}
</style>
