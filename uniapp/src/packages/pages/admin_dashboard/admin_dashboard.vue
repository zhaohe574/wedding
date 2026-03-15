<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            title="经营驾驶舱"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
        <!-- #endif -->
    </page-meta>

    <view class="dashboard-page min-h-screen pb-[40rpx]" :style="pageBgStyle">
        <view class="hero-card mx-[20rpx] mt-[20rpx]" :style="heroCardStyle">
            <view class="hero-top">
                <view class="hero-main">
                    <view class="hero-title-wrap">
                        <tn-icon name="star-fill" size="28" :color="$theme.btnColor" />
                        <text class="hero-title">经营总览</text>
                    </view>
                    <text class="hero-period">{{ periodLabel }}</text>
                </view>
                <view class="hero-refresh touch-target" :style="heroRefreshStyle" @click="loadData">
                    <tn-icon name="refresh" size="24" :color="$theme.btnColor" />
                    <text>{{ loading ? '更新中' : '刷新' }}</text>
                </view>
            </view>
            <view class="hero-footer">
                <view class="hero-meta-item">
                    <tn-icon name="clock" size="24" :color="$theme.btnColor" />
                    <text>更新时间：{{ lastUpdated || '--' }}</text>
                </view>
                <view class="hero-meta-item">
                    <tn-icon name="calendar" size="24" :color="$theme.btnColor" />
                    <text>数据范围：{{ activeRangeLabel }}</text>
                </view>
            </view>
        </view>

        <view class="range-tabs mx-[20rpx] mt-[16rpx]">
            <view
                v-for="tab in rangeTabs"
                :key="tab.key"
                class="range-tab-item touch-target"
                :style="tab.key === rangeKey ? activeTabStyle : defaultTabStyle"
                @click="changeRange(tab.key)"
            >
                {{ tab.label }}
            </view>
        </view>

        <view class="kpi-grid mx-[20rpx] mt-[16rpx]">
            <view
                v-for="item in kpiCards"
                :key="item.label"
                class="kpi-card"
                :style="getKpiCardStyle(item.color)"
            >
                <view class="kpi-top">
                    <text class="kpi-label">{{ item.label }}</text>
                    <view class="kpi-dot" :style="{ backgroundColor: item.color }"></view>
                </view>
                <text class="kpi-value">{{ item.value }}</text>
                <text class="kpi-hint">{{ item.hint }}</text>
            </view>
        </view>

        <view class="panel mx-[20rpx] mt-[16rpx]" :style="glassPanelStyle">
            <view class="panel-header">
                <view class="panel-title-wrap">
                    <tn-icon name="clock" size="30" :color="$theme.primaryColor" />
                    <text class="panel-title">收入趋势</text>
                </view>
                <text class="panel-subtitle">
                    峰值 ¥{{ formatMoney(trendSummary.peak) }} · 日均 ¥{{ formatMoney(trendSummary.avg) }}
                </text>
            </view>

            <view v-if="trendList.length === 0" class="panel-empty">暂无趋势数据</view>
            <view v-else class="trend-chart">
                <view class="trend-bars">
                    <view v-for="item in trendList" :key="item.date" class="trend-column">
                        <text class="trend-value">¥{{ formatMoney(item.value) }}</text>
                        <view class="trend-track">
                            <view class="trend-fill" :style="getTrendFillStyle(item.height)" />
                        </view>
                        <text class="trend-label">{{ item.label }}</text>
                    </view>
                </view>
            </view>
        </view>

        <view class="panel mx-[20rpx] mt-[16rpx]" :style="glassPanelStyle">
            <view class="panel-header">
                <view class="panel-title-wrap">
                    <tn-icon name="order" size="30" :color="$theme.primaryColor" />
                    <text class="panel-title">订单结构</text>
                </view>
                <text class="panel-subtitle">
                    总订单 {{ totalOrders }} · 支付推进率 {{ formatPercent(paidProgressRate) }}
                </text>
            </view>

            <view v-if="statusItems.length === 0" class="panel-empty">暂无订单统计</view>
            <view v-else class="status-list">
                <view v-for="item in statusItems" :key="item.status" class="status-item">
                    <view class="status-title">
                        <view class="status-label-wrap">
                            <view class="status-dot" :style="{ backgroundColor: item.color }" />
                            <text class="status-label">{{ item.label }}</text>
                        </view>
                        <text class="status-meta">{{ item.count }} 单 · {{ formatPercent(item.percent) }}</text>
                    </view>
                    <view class="status-track">
                        <view class="status-fill" :style="getStatusFillStyle(item.color, item.percent)" />
                    </view>
                </view>
            </view>
        </view>

        <view class="panel mx-[20rpx] mt-[16rpx]" :style="glassPanelStyle">
            <view class="panel-header">
                <view class="panel-title-wrap">
                    <tn-icon name="tip" size="30" :color="$theme.primaryColor" />
                    <text class="panel-title">经营洞察</text>
                </view>
                <text class="panel-subtitle">系统根据当前数据自动生成</text>
            </view>

            <view class="insight-list">
                <view v-for="item in insights" :key="item.text" class="insight-item" :style="item.style">
                    <view class="insight-level" :style="item.tagStyle">{{ item.levelText }}</view>
                    <text class="insight-text">{{ item.text }}</text>
                </view>
            </view>
        </view>

        <view class="panel mx-[20rpx] mt-[16rpx]" :style="glassPanelStyle">
            <view class="panel-header">
                <view class="panel-title-wrap">
                    <tn-icon name="time" size="30" :color="$theme.primaryColor" />
                    <text class="panel-title">今日经营快照</text>
                </view>
                <text class="panel-subtitle">重点关注当日订单与收款变化</text>
            </view>

            <view class="today-grid">
                <view
                    v-for="item in todayMetrics"
                    :key="item.label"
                    class="today-item"
                    :style="todayItemStyle"
                >
                    <text class="today-label">{{ item.label }}</text>
                    <text class="today-value">{{ item.value }}</text>
                </view>
            </view>
        </view>

        <view class="mx-[20rpx] mt-[20rpx]">
            <tn-button
                shape="round"
                size="lg"
                :plain="true"
                :custom-style="backBtnStyle"
                @click="handleBack"
            >
                返回
            </tn-button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import {
    adminDashboardIncomeTrend,
    adminDashboardOrderStats,
    adminDashboardOverview
} from '@/api/adminDashboard'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { useThemeStore } from '@/stores/theme'

type RangeKey = '7d' | '30d' | 'month'
type InsightLevel = 'good' | 'warning' | 'risk'

interface TrendItem {
    date: string
    label: string
    value: number
    height: number
}

interface InsightItem {
    level: InsightLevel
    levelText: string
    text: string
    style: Record<string, string>
    tagStyle: Record<string, string>
}

const appStore = useAppStore()
const userStore = useUserStore()
const $theme = useThemeStore()

const overview = ref<any>({})
const orderStats = ref<any>({})
const trendList = ref<TrendItem[]>([])
const loading = ref(false)
const lastUpdated = ref('')
const rangeKey = ref<RangeKey>('7d')
const dateRange = ref({ startDate: '', endDate: '' })

const rangeTabs: Array<{ key: RangeKey; label: string }> = [
    { key: '7d', label: '近7天' },
    { key: '30d', label: '近30天' },
    { key: 'month', label: '本月' }
]

const hexToRgb = (hexColor: string) => {
    const hex = (hexColor || '').replace('#', '')
    if (!/^[0-9A-Fa-f]{6}$/.test(hex)) return null
    return {
        r: parseInt(hex.slice(0, 2), 16),
        g: parseInt(hex.slice(2, 4), 16),
        b: parseInt(hex.slice(4, 6), 16)
    }
}

const toRgba = (hexColor: string, alpha: number) => {
    const rgb = hexToRgb(hexColor)
    if (!rgb) return `rgba(124,58,237,${alpha})`
    return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`
}

const mixColor = (hexColor: string, mixHex: string, weight: number) => {
    const a = hexToRgb(hexColor)
    const b = hexToRgb(mixHex)
    if (!a || !b) return hexColor

    const w = Math.min(1, Math.max(0, weight))
    const r = Math.round(a.r * (1 - w) + b.r * w)
    const g = Math.round(a.g * (1 - w) + b.g * w)
    const bVal = Math.round(a.b * (1 - w) + b.b * w)

    return `#${[r, g, bVal]
        .map((value) => {
            const hex = value.toString(16)
            return hex.length === 1 ? `0${hex}` : hex
        })
        .join('')}`
}

const formatMoney = (value: any) => Number(value || 0).toFixed(2)
const formatPercent = (value: any) => `${Number(value || 0).toFixed(1)}%`

const padZero = (value: number) => String(value).padStart(2, '0')
const formatDate = (date: Date) =>
    `${date.getFullYear()}-${padZero(date.getMonth() + 1)}-${padZero(date.getDate())}`
const formatDateTime = (date: Date) =>
    `${formatDate(date)} ${padZero(date.getHours())}:${padZero(date.getMinutes())}`

const getDateRange = (key: RangeKey) => {
    const endDate = new Date()
    const startDate = new Date(endDate)

    if (key === 'month') {
        startDate.setDate(1)
    } else if (key === '30d') {
        startDate.setDate(startDate.getDate() - 29)
    } else {
        startDate.setDate(startDate.getDate() - 6)
    }

    return {
        startDate: formatDate(startDate),
        endDate: formatDate(endDate)
    }
}

const activeRangeLabel = computed(() => {
    const current = rangeTabs.find((item) => item.key === rangeKey.value)
    return current?.label || ''
})

const periodLabel = computed(() => {
    if (!dateRange.value.startDate || !dateRange.value.endDate) return '--'
    return `${dateRange.value.startDate} 至 ${dateRange.value.endDate}`
})

const pageBgStyle = computed(() => ({
    background: `linear-gradient(180deg, ${toRgba($theme.primaryColor, 0.1)} 0%, ${toRgba(
        $theme.secondaryColor,
        0.08
    )} 42%, #F6F6F6 100%)`
}))

const heroCardStyle = computed(() => ({
    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor} 100%)`,
    boxShadow: `0 12rpx 28rpx ${toRgba($theme.primaryColor, 0.28)}`
}))

const heroRefreshStyle = computed(() => ({
    backgroundColor: toRgba('#FFFFFF', 0.18),
    borderColor: toRgba('#FFFFFF', 0.35)
}))

const activeTabStyle = computed(() => ({
    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor} 100%)`,
    color: '#FFFFFF',
    boxShadow: `0 6rpx 16rpx ${toRgba($theme.primaryColor, 0.22)}`
}))

const defaultTabStyle = computed(() => ({
    backgroundColor: '#FFFFFF',
    color: '#333333',
    borderColor: toRgba($theme.primaryColor, 0.2)
}))

const glassPanelStyle = computed(() => ({
    borderColor: toRgba('#FFFFFF', 0.45),
    boxShadow: `0 8rpx 24rpx ${toRgba($theme.primaryColor, 0.12)}`
}))

const todayItemStyle = computed(() => ({
    backgroundColor: mixColor($theme.primaryColor, '#FFFFFF', 0.93),
    borderColor: toRgba($theme.primaryColor, 0.2)
}))

const backBtnStyle = computed(() => ({
    height: '72rpx',
    borderRadius: '32rpx',
    border: `2rpx solid ${$theme.primaryColor}`,
    color: $theme.primaryColor,
    background: 'transparent'
}))

const getKpiCardStyle = (color: string) => ({
    borderColor: toRgba(color, 0.24),
    boxShadow: `0 2rpx 10rpx ${toRgba(color, 0.12)}`
})

const getTrendFillStyle = (height: number) => ({
    height: `${height}rpx`,
    background: `linear-gradient(180deg, ${$theme.secondaryColor} 0%, ${$theme.primaryColor} 100%)`
})

const getStatusFillStyle = (color: string, percent: number) => ({
    width: `${percent}%`,
    backgroundColor: color
})

const totalOrders = computed(() => Number(orderStats.value?.total_orders || 0))

const paidProgressOrders = computed(() => {
    const paidLabels = ['已支付', '服务中', '已完成', '已评价']
    return (orderStats.value?.status_counts || []).reduce((total: number, item: any) => {
        return paidLabels.includes(item.label) ? total + Number(item.count || 0) : total
    }, 0)
})

const paidProgressRate = computed(() => {
    if (!totalOrders.value) return 0
    return (paidProgressOrders.value / totalOrders.value) * 100
})

const kpiPalette = computed(() => [
    $theme.primaryColor,
    $theme.secondaryColor,
    '#19BE6B',
    '#FF9900',
    $theme.ctaColor,
    $theme.accentColor
])

const kpiCards = computed(() => [
    {
        label: '总收入',
        value: `¥${formatMoney(overview.value?.total_income)}`,
        hint: '本周期累计实收金额',
        color: kpiPalette.value[0]
    },
    {
        label: '净收入',
        value: `¥${formatMoney(overview.value?.net_income)}`,
        hint: '已扣除退款后的净额',
        color: kpiPalette.value[1]
    },
    {
        label: '总退款',
        value: `¥${formatMoney(overview.value?.total_refund)}`,
        hint: '本周期累计退款金额',
        color: kpiPalette.value[2]
    },
    {
        label: '订单总量',
        value: `${Number(overview.value?.order_count || 0)} 单`,
        hint: '本周期支付订单数',
        color: kpiPalette.value[3]
    },
    {
        label: '平均客单价',
        value: `¥${formatMoney(overview.value?.avg_order_amount)}`,
        hint: '每单平均收入水平',
        color: kpiPalette.value[4]
    },
    {
        label: '支付推进率',
        value: formatPercent(paidProgressRate.value),
        hint: '已支付、服务中、已完成与已评价占比',
        color: kpiPalette.value[5]
    }
])

const trendSummary = computed(() => {
    const values = trendList.value.map((item) => item.value)
    if (!values.length) return { peak: 0, avg: 0 }
    const total = values.reduce((sum, value) => sum + value, 0)
    return { peak: Math.max(...values), avg: total / values.length }
})

const statusColorMap: Record<string, string> = {
    待确认: '#909399',
    待支付: '#FF9900',
    已支付: '#19BE6B',
    服务中: '#3B82F6',
    已完成: '#64748B',
    已评价: '#14B8A6',
    已取消: '#FF2C3C',
    已暂停: '#F97316',
    已退款: '#FF2C3C'
}

const statusItems = computed(() => {
    const list = (orderStats.value?.status_counts || []).map((item: any) => {
        const count = Number(item.count || 0)
        const percent = totalOrders.value ? (count / totalOrders.value) * 100 : 0
        return {
            status: item.status,
            label: item.label,
            count,
            percent: Number(percent.toFixed(1)),
            color: statusColorMap[item.label] || '#909399'
        }
    })
    return list.sort((a: any, b: any) => b.count - a.count)
})

const insights = computed<InsightItem[]>(() => {
    const result: InsightItem[] = []

    const totalIncome = Number(overview.value?.total_income || 0)
    const netIncome = Number(overview.value?.net_income || 0)
    const totalRefund = Number(overview.value?.total_refund || 0)
    const refundRate = totalIncome > 0 ? (totalRefund / totalIncome) * 100 : 0
    const pendingPay = statusItems.value.find((item: any) => item.label === '待支付')?.percent || 0
    const cancelled = statusItems.value.find((item: any) => item.label === '已取消')?.percent || 0

    const buildInsight = (level: InsightLevel, text: string): InsightItem => {
        const config = {
            good: {
                levelText: '良好',
                tagColor: '#19BE6B',
                bgColor: toRgba('#19BE6B', 0.1)
            },
            warning: {
                levelText: '关注',
                tagColor: '#FF9900',
                bgColor: toRgba('#FF9900', 0.1)
            },
            risk: {
                levelText: '重点',
                tagColor: '#FF2C3C',
                bgColor: toRgba('#FF2C3C', 0.1)
            }
        }[level]

        return {
            level,
            levelText: config.levelText,
            text,
            style: {
                backgroundColor: config.bgColor,
                borderColor: toRgba(config.tagColor, 0.2)
            },
            tagStyle: {
                color: config.tagColor,
                backgroundColor: toRgba(config.tagColor, 0.14)
            }
        }
    }

    if (totalIncome > 0 && netIncome <= 0) {
        result.push(buildInsight('risk', '当前净收入已接近或低于 0，建议优先检查退款与支付回收情况。'))
    }
    if (refundRate > 5) {
        result.push(
            buildInsight('warning', `退款率达到 ${formatPercent(refundRate)}，建议排查履约质量与客户沟通环节。`)
        )
    }
    if (pendingPay > 30) {
        result.push(
            buildInsight('warning', `待支付订单占比 ${formatPercent(pendingPay)}，可增加催付与分期提醒策略。`)
        )
    }
    if (cancelled > 10) {
        result.push(
            buildInsight('risk', `取消订单占比 ${formatPercent(cancelled)}，建议跟进预约到履约的关键流失节点。`)
        )
    }
    if (!result.length) {
        result.push(buildInsight('good', '当前经营指标整体稳定，可持续关注订单转化效率与客户复购。'))
    }

    return result.slice(0, 3)
})

const todayMetrics = computed(() => {
    const today = orderStats.value?.today || {}
    return [
        { label: '今日新增订单', value: `${Number(today.orders || 0)} 单` },
        { label: '今日已支付订单', value: `${Number(today.paid_orders || 0)} 单` },
        { label: '今日收款金额', value: `¥${formatMoney(today.amount)}` }
    ]
})

const buildTrend = (data: Record<string, number>) => {
    const entries = Object.entries(data || {})
    if (!entries.length) {
        trendList.value = []
        return
    }

    const maxPoints = 10
    let sampled = entries
    if (entries.length > maxPoints) {
        const step = Math.ceil(entries.length / maxPoints)
        sampled = entries.filter((_, index) => index % step === 0 || index === entries.length - 1)
    }

    const values = sampled.map(([, value]) => Number(value || 0))
    const maxValue = Math.max(...values, 1)

    trendList.value = sampled.map(([date, value]) => {
        const amount = Number(value || 0)
        return {
            date,
            label: date.slice(5),
            value: amount,
            height: Math.max(20, Math.round((amount / maxValue) * 150))
        }
    })
}

const getAllowedUserIds = () => {
    const rawUserIds = String(appStore.config?.feature_switch?.admin_dashboard_user_ids || '')
    if (!rawUserIds.trim()) return []

    const idSet = new Set<number>()
    rawUserIds
        .split(/[\s,，]+/)
        .map((item) => Number(item))
        .forEach((id) => {
            if (Number.isInteger(id) && id > 0) idSet.add(id)
        })

    return Array.from(idSet)
}

const loadData = async () => {
    if (loading.value) return
    loading.value = true
    try {
        dateRange.value = getDateRange(rangeKey.value)
        const params = {
            start_date: dateRange.value.startDate,
            end_date: dateRange.value.endDate
        }
        const [overviewRes, trendRes, orderRes] = await Promise.all([
            adminDashboardOverview(params),
            adminDashboardIncomeTrend({ type: 'daily', ...params }),
            adminDashboardOrderStats(params)
        ])
        overview.value = overviewRes || {}
        orderStats.value = orderRes || {}
        buildTrend(trendRes?.data || {})
        lastUpdated.value = formatDateTime(new Date())
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        loading.value = false
    }
}

const changeRange = (nextRange: RangeKey) => {
    if (rangeKey.value === nextRange) return
    rangeKey.value = nextRange
    loadData()
}

const ensureAccess = async () => {
    if (!appStore.config?.feature_switch) await appStore.getConfig()

    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return false
    }
    if (!userStore.userInfo?.id) await userStore.getUser()

    if (appStore.config?.feature_switch?.admin_dashboard !== 1) {
        uni.showToast({ title: '管理员看板已关闭', icon: 'none' })
        setTimeout(() => uni.navigateBack(), 1200)
        return false
    }

    const currentUserId = Number(userStore.userInfo?.id || userStore.userInfo?.user_id || 0)
    const allowedUserIds = getAllowedUserIds()
    if (currentUserId <= 0 || !allowedUserIds.includes(currentUserId)) {
        uni.showToast({ title: '暂无权限访问管理员看板', icon: 'none' })
        setTimeout(() => uni.navigateBack(), 1200)
        return false
    }

    return true
}

const handleBack = () => {
    uni.navigateBack()
}

onShow(async () => {
    if (!(await ensureAccess())) return
    await loadData()
})
</script>

<style lang="scss" scoped>
.dashboard-page {
    padding-bottom: 28rpx;
}

.hero-card {
    border-radius: 24rpx;
    padding: 24rpx;
    color: #ffffff;
    transition: all 0.2s ease;
}

.hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12rpx;
}

.hero-main {
    flex: 1;
    min-width: 0;
}

.hero-title-wrap {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.hero-title {
    font-size: 34rpx;
    font-weight: 600;
    line-height: 1.4;
}

.hero-period {
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.5;
    opacity: 0.92;
}

.hero-refresh {
    min-width: 116rpx;
    height: 64rpx;
    padding: 0 16rpx;
    border-radius: 32rpx;
    border-width: 2rpx;
    border-style: solid;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    font-size: 24rpx;
}

.hero-footer {
    margin-top: 18rpx;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.hero-meta-item {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 24rpx;
    line-height: 1.5;
    opacity: 0.95;
}

.touch-target {
    min-height: 88rpx;
}

.range-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12rpx;
}

.range-tab-item {
    border-radius: 14rpx;
    border-width: 2rpx;
    border-style: solid;
    font-size: 28rpx;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
}

.kpi-card {
    background: #ffffff;
    border-radius: 14rpx;
    border: 2rpx solid transparent;
    padding: 20rpx;
    transition: all 0.2s ease;
}

.kpi-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8rpx;
}

.kpi-dot {
    width: 14rpx;
    height: 14rpx;
    border-radius: 50%;
    flex-shrink: 0;
}

.kpi-label {
    font-size: 24rpx;
    color: #666666;
    line-height: 1.5;
}

.kpi-value {
    margin-top: 8rpx;
    font-size: 34rpx;
    font-weight: 600;
    color: #333333;
    line-height: 1.4;
}

.kpi-hint {
    margin-top: 6rpx;
    font-size: 22rpx;
    color: #999999;
    line-height: 1.5;
}

.panel {
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(20rpx);
    border: 2rpx solid rgba(255, 255, 255, 0.38);
    border-radius: 24rpx;
    padding: 20rpx;
    transition: all 0.2s ease;
}

.panel-header {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.panel-title-wrap {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.panel-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
    line-height: 1.4;
}

.panel-subtitle {
    font-size: 24rpx;
    color: #666666;
    line-height: 1.5;
}

.panel-empty {
    padding: 40rpx 0;
    text-align: center;
    font-size: 24rpx;
    color: #999999;
}

.trend-chart {
    margin-top: 20rpx;
}

.trend-bars {
    min-height: 240rpx;
    display: flex;
    align-items: flex-end;
    gap: 10rpx;
}

.trend-column {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.trend-value {
    font-size: 20rpx;
    color: #999999;
    margin-bottom: 8rpx;
    transform: scale(0.92);
    transform-origin: bottom center;
    white-space: nowrap;
}

.trend-track {
    width: 24rpx;
    height: 156rpx;
    border-radius: 999rpx;
    background: #e5e7eb;
    overflow: hidden;
    display: flex;
    align-items: flex-end;
}

.trend-fill {
    width: 100%;
    border-radius: 999rpx;
}

.trend-label {
    margin-top: 8rpx;
    font-size: 22rpx;
    color: #666666;
    line-height: 1.5;
}

.status-list {
    margin-top: 16rpx;
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}

.status-item {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.status-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8rpx;
}

.status-label-wrap {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.status-dot {
    width: 12rpx;
    height: 12rpx;
    border-radius: 50%;
}

.status-label {
    font-size: 26rpx;
    color: #333333;
    line-height: 1.5;
}

.status-meta {
    font-size: 24rpx;
    color: #666666;
    line-height: 1.5;
}

.status-track {
    height: 12rpx;
    border-radius: 999rpx;
    background: #e5e7eb;
    overflow: hidden;
}

.status-fill {
    height: 100%;
    border-radius: 999rpx;
}

.insight-list {
    margin-top: 16rpx;
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.insight-item {
    border: 2rpx solid transparent;
    border-radius: 14rpx;
    padding: 16rpx;
    display: flex;
    align-items: flex-start;
    gap: 10rpx;
}

.insight-level {
    min-width: 72rpx;
    height: 38rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22rpx;
    font-weight: 500;
    flex-shrink: 0;
}

.insight-text {
    flex: 1;
    min-width: 0;
    font-size: 24rpx;
    color: #333333;
    line-height: 1.6;
}

.today-grid {
    margin-top: 16rpx;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10rpx;
}

.today-item {
    border-radius: 14rpx;
    border: 2rpx solid transparent;
    padding: 16rpx 12rpx;
    text-align: center;
}

.today-label {
    font-size: 22rpx;
    color: #666666;
    line-height: 1.5;
}

.today-value {
    margin-top: 8rpx;
    font-size: 30rpx;
    font-weight: 600;
    color: #333333;
    line-height: 1.4;
}

@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
