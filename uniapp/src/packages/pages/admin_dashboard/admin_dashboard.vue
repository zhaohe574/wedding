<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar title="管理员看板" :front-color="$theme.navColor" :background-color="$theme.navBgColor" />
    </page-meta>

    <view class="min-h-screen bg-[#f6f6f6] pb-[40rpx]">
        <view class="bg-white mx-[24rpx] mt-[24rpx] rounded-lg p-[24rpx]">
            <view class="flex items-center justify-between">
                <view class="text-base font-semibold">概览</view>
                <view class="text-xs text-gray-400" @click="loadData">刷新</view>
            </view>
            <view class="grid grid-cols-2 gap-[16rpx] mt-[20rpx]">
                <view class="bg-[#f8fafc] rounded-lg p-[20rpx]">
                    <view class="text-xs text-gray-400">总收入</view>
                    <view class="text-lg font-semibold mt-[6rpx]">¥{{ formatMoney(overview.total_income) }}</view>
                </view>
                <view class="bg-[#f8fafc] rounded-lg p-[20rpx]">
                    <view class="text-xs text-gray-400">净收入</view>
                    <view class="text-lg font-semibold mt-[6rpx]">¥{{ formatMoney(overview.net_income) }}</view>
                </view>
                <view class="bg-[#f8fafc] rounded-lg p-[20rpx]">
                    <view class="text-xs text-gray-400">订单数</view>
                    <view class="text-lg font-semibold mt-[6rpx]">{{ overview.order_count || 0 }}</view>
                </view>
                <view class="bg-[#f8fafc] rounded-lg p-[20rpx]">
                    <view class="text-xs text-gray-400">利润率</view>
                    <view class="text-lg font-semibold mt-[6rpx]">{{ overview.profit_rate || 0 }}%</view>
                </view>
            </view>
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg p-[24rpx]">
            <view class="text-base font-semibold">收入趋势</view>
            <view v-if="trendList.length === 0" class="text-center text-gray-400 py-[40rpx]">暂无数据</view>
            <view v-else class="flex items-end gap-[12rpx] mt-[24rpx]" style="height: 180rpx;">
                <view v-for="item in trendList" :key="item.label" class="flex-1 flex flex-col items-center justify-end">
                    <view
                        class="w-[18rpx] rounded-t"
                        :style="{ height: item.height + 'rpx', background: $theme.primaryColor }"
                    ></view>
                    <view class="text-[20rpx] text-gray-400 mt-[8rpx]">{{ item.label }}</view>
                </view>
            </view>
        </view>

        <view class="bg-white mx-[24rpx] mt-[20rpx] rounded-lg p-[24rpx]">
            <view class="text-base font-semibold">订单统计</view>
            <view class="text-xs text-gray-400 mt-[8rpx]">总订单 {{ orderStats.total_orders || 0 }} · 已收款 ¥{{ formatMoney(orderStats.paid_amount) }}</view>

            <view class="mt-[20rpx]">
                <view v-for="item in orderStats.status_counts || []" :key="item.status" class="mb-[14rpx]">
                    <view class="flex items-center justify-between text-xs text-gray-500">
                        <text>{{ item.label }}</text>
                        <text>{{ item.count }}</text>
                    </view>
                    <view class="h-[10rpx] bg-gray-100 rounded-full mt-[6rpx] overflow-hidden">
                        <view
                            class="h-full rounded-full"
                            :style="{ width: getPercent(item.count) + '%', background: $theme.primaryColor }"
                        ></view>
                    </view>
                </view>
            </view>
        </view>

        <view class="mx-[24rpx] mt-[24rpx]">
            <tn-button type="danger" shape="round" size="lg" :plain="true" @click="handleLogout">
                退出登录
            </tn-button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { adminIncomeTrend, adminOrderStats, adminOverview } from '@/api/admin'
import { useAdminStore } from '@/stores/admin'
import { useAppStore } from '@/stores/app'

const adminStore = useAdminStore()
const appStore = useAppStore()

const overview = ref<any>({})
const orderStats = ref<any>({})
const trendList = ref<Array<{ label: string; value: number; height: number }>>([])
const loading = ref(false)

const formatMoney = (value: any) => {
    const num = Number(value || 0)
    return num.toFixed(2)
}

const buildTrend = (data: Record<string, number>) => {
    const entries = Object.entries(data || {})
    const recent = entries.slice(-7)
    const values = recent.map(([, val]) => Number(val || 0))
    const max = Math.max(...values, 1)
    trendList.value = recent.map(([date, val]) => {
        const value = Number(val || 0)
        return {
            label: date.slice(5),
            value,
            height: Math.max(12, Math.round((value / max) * 140))
        }
    })
}

const getPercent = (count: number) => {
    const total = Number(orderStats.value?.total_orders || 0)
    if (!total) return 0
    return Math.min(100, Math.round((count / total) * 100))
}

const loadData = async () => {
    if (loading.value) return
    loading.value = true
    try {
        const [overviewRes, trendRes, orderRes] = await Promise.all([
            adminOverview(),
            adminIncomeTrend({ type: 'daily' }),
            adminOrderStats()
        ])
        overview.value = overviewRes || {}
        orderStats.value = orderRes || {}
        buildTrend(trendRes?.data || {})
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        loading.value = false
    }
}

const ensureAccess = async () => {
    if (!appStore.config?.feature_switch) {
        await appStore.getConfig()
    }
    if (appStore.config?.feature_switch?.admin_dashboard !== 1) {
        uni.showToast({ title: '管理员看板已关闭', icon: 'none' })
        setTimeout(() => uni.navigateBack(), 1200)
        return false
    }
    if (!adminStore.isLogin) {
        uni.navigateTo({ url: '/packages/pages/admin_login/admin_login' })
        return false
    }
    return true
}

const handleLogout = async () => {
    await adminStore.logout()
    uni.navigateTo({ url: '/packages/pages/admin_login/admin_login' })
}

onShow(async () => {
    if (!(await ensureAccess())) return
    loadData()
})
</script>

<style lang="scss" scoped></style>
