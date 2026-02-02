<template>
    <view v-if="content.enabled && showList.length" class="data-stats mx-[24rpx] mt-[24rpx]">
        <!-- 横向排列 -->
        <view v-if="content.style == 1" class="horizontal-layout bg-white rounded-[16rpx] p-[32rpx]">
            <view class="flex justify-around">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="stat-item flex flex-col items-center"
                    @click="handleClick(item)"
                >
                    <!-- 图标 -->
                    <view 
                        class="icon-wrapper mb-[16rpx] w-[80rpx] h-[80rpx] rounded-full flex items-center justify-center"
                        :style="{ backgroundColor: getIconBg(index) }"
                    >
                        <tn-icon :name="item.icon" :size="40" :color="getIconColor(index)"></tn-icon>
                    </view>
                    <!-- 数值 -->
                    <view class="flex items-baseline mb-[8rpx]">
                        <text class="text-[40rpx] font-bold text-[#1E293B]">
                            {{ getStatValue(item.value) }}
                        </text>
                        <text v-if="item.unit" class="text-[24rpx] text-[#64748B] ml-[4rpx]">{{ item.unit }}</text>
                    </view>
                    <!-- 标题 -->
                    <text class="text-[24rpx] text-[#64748B]">{{ item.title }}</text>
                </view>
            </view>
        </view>

        <!-- 纵向排列 -->
        <view v-if="content.style == 2" class="vertical-layout">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="stat-card bg-white rounded-[16rpx] p-[32rpx] mb-[24rpx] flex items-center"
                @click="handleClick(item)"
            >
                <!-- 图标 -->
                <view 
                    class="icon-wrapper w-[80rpx] h-[80rpx] rounded-[16rpx] flex items-center justify-center mr-[24rpx]"
                    :style="{ backgroundColor: getIconBg(index) }"
                >
                    <tn-icon :name="item.icon" :size="40" :color="getIconColor(index)"></tn-icon>
                </view>
                <!-- 内容 -->
                <view class="flex-1">
                    <text class="text-[24rpx] text-[#64748B] block mb-[8rpx]">{{ item.title }}</text>
                    <view class="flex items-baseline">
                        <text class="text-[40rpx] font-bold text-[#1E293B]">
                            {{ getStatValue(item.value) }}
                        </text>
                        <text v-if="item.unit" class="text-[24rpx] text-[#64748B] ml-[4rpx]">{{ item.unit }}</text>
                    </view>
                </view>
                <!-- 箭头 -->
                <tn-icon name="right" size="32" color="#CBD5E1"></tn-icon>
            </view>
        </view>

        <!-- 网格布局 -->
        <view v-if="content.style == 3" class="grid-layout bg-white rounded-[16rpx] p-[24rpx]">
            <view class="grid grid-cols-2 gap-[24rpx]">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="stat-grid-item p-[28rpx] rounded-[16rpx]"
                    :style="{ backgroundColor: getIconBg(index) }"
                    @click="handleClick(item)"
                >
                    <!-- 图标 -->
                    <view class="mb-[16rpx]">
                        <tn-icon :name="item.icon" :size="36" :color="getIconColor(index)"></tn-icon>
                    </view>
                    <!-- 数值 -->
                    <view class="flex items-baseline mb-[8rpx]">
                        <text class="text-[36rpx] font-bold text-[#1E293B]">
                            {{ getStatValue(item.value) }}
                        </text>
                        <text v-if="item.unit" class="text-[22rpx] text-[#64748B] ml-[4rpx]">{{ item.unit }}</text>
                    </view>
                    <!-- 标题 -->
                    <text class="text-[24rpx] text-[#64748B]">{{ item.title }}</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
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

const $theme = useThemeStore()

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

// 获取图标背景色（循环使用主题色浅色变体）
const getIconBg = (index: number) => {
    const colors = [
        $theme.primaryColor + '15',
        $theme.secondaryColor + '15',
        $theme.ctaColor + '15',
        $theme.accentColor + '15'
    ]
    return colors[index % colors.length]
}

// 获取图标颜色
const getIconColor = (index: number) => {
    const colors = [
        $theme.primaryColor,
        $theme.secondaryColor,
        $theme.ctaColor,
        $theme.accentColor
    ]
    return colors[index % colors.length]
}

// 处理点击事件
const handleClick = (item: any) => {
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
        // 优化：直接使用 userStore 中的数据，避免重复请求
        const { userInfo } = useUserStore()
        if (userInfo) {
            statsData.value.order_count = userInfo.order_count || 0
            statsData.value.collect_count = userInfo.collect_count || 0
            statsData.value.view_count = userInfo.view_count || 0
        }

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
    .stat-card,
    .grid-layout {
        box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
        transition: all 0.2s ease;
    }

    .stat-item,
    .stat-card,
    .stat-grid-item {
        cursor: pointer;
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.98);
            opacity: 0.9;
        }
    }
    
    .icon-wrapper {
        transition: all 0.2s ease;
    }
}
</style>
