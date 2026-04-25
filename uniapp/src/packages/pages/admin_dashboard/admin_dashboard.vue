<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="admin" tone="workspace" hasSafeBottom>
        <BaseNavbar title="经营驾驶舱" title-align="left" />

        <view class="dashboard-page wm-page-content">
            <view class="dashboard-page__stack">
                <view class="hero-card wm-panel-card" :style="heroCardStyle">
                    <view class="hero-top">
                        <view class="hero-main">
                            <text class="hero-eyebrow">经营驾驶舱</text>

                            <text class="hero-title">先看结果</text>

                            <text class="hero-period">{{ periodLabel }}</text>
                        </view>

                        <view class="hero-refresh" :style="heroRefreshStyle" @click="loadData">
                            <text class="hero-refresh-text">{{
                                loading ? '更新中' : '刷新数据'
                            }}</text>
                        </view>
                    </view>

                    <view class="hero-meta">
                        <text class="hero-meta-text">更新时间：{{ lastUpdated || '--' }}</text>

                        <text class="hero-meta-text">范围：{{ activeRangeLabel }}</text>
                    </view>

                    <view class="hero-chip-list">
                        <view
                            v-for="item in summaryChips"
                            :key="item.label"
                            class="hero-chip"
                            :style="heroChipStyle"
                        >
                            <text class="hero-chip-label">{{ item.label }}</text>

                            <text class="hero-chip-value">{{ item.value }}</text>
                        </view>
                    </view>
                </view>

                <view class="range-tabs wm-panel-card">
                    <view
                        v-for="tab in rangeTabs"
                        :key="tab.key"
                        class="range-tab-item"
                        :style="tab.key === rangeKey ? activeTabStyle : defaultTabStyle"
                        @click="changeRange(tab.key)"
                    >
                        {{ tab.label }}
                    </view>
                </view>

                <view class="section-card wm-panel-card" :style="panelStyle">
                    <view class="section-header">
                        <view>
                            <text class="section-title">经营核心</text>
                        </view>
                    </view>

                    <view class="metric-grid">
                        <view
                            v-for="item in coreMetrics"
                            :key="item.label"
                            class="metric-card"
                            :style="getMetricCardStyle(item.color)"
                        >
                            <text class="metric-label">{{ item.label }}</text>

                            <text class="metric-value">{{ item.value }}</text>

                            <text class="metric-hint">{{ item.hint }}</text>
                        </view>
                    </view>

                    <view class="snapshot-row">
                        <view
                            v-for="item in todaySnapshots"
                            :key="item.label"
                            class="snapshot-item"
                        >
                            <text class="snapshot-label">{{ item.label }}</text>

                            <text class="snapshot-value">{{ item.value }}</text>
                        </view>
                    </view>
                </view>

                <view class="section-card wm-panel-card" :style="panelStyle">
                    <view class="section-header">
                        <view>
                            <text class="section-title">团队总览</text>

                            <text class="section-subtitle">
                                本月档期占用 {{ formatPercent(capacityStats.booking_rate) }}，已占

                                {{ capacityStats.month_occupied_slots }} /

                                {{ capacityStats.month_total_slots }}
                            </text>
                        </view>
                    </view>

                    <view class="team-grid">
                        <view
                            v-for="item in teamStatsCards"
                            :key="item.label"
                            class="team-stat-item"
                            :style="teamStatStyle"
                        >
                            <text class="team-stat-label">{{ item.label }}</text>

                            <text class="team-stat-value">{{ item.value }}</text>
                        </view>
                    </view>
                </view>

                <view class="section-card wm-panel-card" :style="panelStyle">
                    <view class="section-header">
                        <view>
                            <text class="section-title">成员负载</text>
                        </view>
                    </view>

                    <view v-if="memberCards.length === 0" class="panel-empty"
                        >暂无成员负载数据</view
                    >

                    <scroll-view v-else class="member-scroll" scroll-x show-scrollbar="false">
                        <view class="member-list">
                            <view v-for="item in memberCards" :key="item.id" class="member-card">
                                <view class="member-top">
                                    <image
                                        class="member-avatar"
                                        :src="item.avatar"
                                        mode="aspectFill"
                                    />

                                    <view class="member-main">
                                        <view class="member-name-row">
                                            <text class="member-name">{{ item.name }}</text>

                                            <view
                                                v-if="item.isRecommend"
                                                class="member-recommend"
                                                :style="memberRecommendStyle"
                                            >
                                                推荐
                                            </view>
                                        </view>

                                        <text class="member-role">{{
                                            item.categoryName || '服务人员'
                                        }}</text>
                                    </view>

                                    <view
                                        class="member-load-tag"
                                        :style="getLoadTagStyle(item.loadLevel)"
                                    >
                                        {{ item.loadLevel }}
                                    </view>
                                </view>

                                <view class="member-data-grid">
                                    <view class="member-data-item">
                                        <text class="member-data-label">近30天订单</text>

                                        <text class="member-data-value">{{
                                            item.recentOrderCount
                                        }}</text>
                                    </view>

                                    <view class="member-data-item">
                                        <text class="member-data-label">未来30天占用</text>

                                        <text class="member-data-value">{{
                                            item.upcomingBookedSlots
                                        }}</text>
                                    </view>

                                    <view class="member-data-item">
                                        <text class="member-data-label">待跟进</text>

                                        <text class="member-data-value">{{
                                            item.followUpCount
                                        }}</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <view class="section-card wm-panel-card" :style="panelStyle">
                    <view class="section-header">
                        <view>
                            <text class="section-title">业务推进</text>

                            <text class="section-subtitle">
                                总订单 {{ totalOrders }} 单，支付推进率

                                {{ formatPercent(paidProgressRate) }}
                            </text>
                        </view>
                    </view>

                    <view class="todo-grid">
                        <view
                            v-for="item in todoCards"
                            :key="item.label"
                            class="todo-card"
                            :style="getTodoCardStyle(item.color)"
                        >
                            <text class="todo-label">{{ item.label }}</text>

                            <text class="todo-value">{{ item.value }}</text>

                            <text v-if="item.hint" class="todo-hint">{{ item.hint }}</text>
                        </view>
                    </view>

                    <view v-if="statusItems.length === 0" class="panel-empty">暂无订单统计</view>

                    <view v-else class="status-list">
                        <view v-for="item in statusItems" :key="item.status" class="status-item">
                            <view class="status-title">
                                <view class="status-label-wrap">
                                    <view
                                        class="status-dot"
                                        :style="{ backgroundColor: item.color }"
                                    />

                                    <text class="status-label">{{ item.label }}</text>
                                </view>

                                <text class="status-meta"
                                    >{{ item.count }} 单 · {{ formatPercent(item.percent) }}</text
                                >
                            </view>

                            <view class="status-track">
                                <view
                                    class="status-fill"
                                    :style="getStatusFillStyle(item.color, item.percent)"
                                />
                            </view>
                        </view>
                    </view>
                </view>

                <view class="section-card wm-panel-card" :style="panelStyle">
                    <view class="section-header">
                        <view>
                            <text class="section-title">收入趋势</text>

                            <text class="section-subtitle">
                                峰值 ¥{{ formatMoney(trendSummary.peak) }}，日均 ¥{{
                                    formatMoney(trendSummary.avg)
                                }}
                            </text>
                        </view>
                    </view>

                    <view v-if="trendList.length === 0" class="panel-empty">暂无趋势数据</view>

                    <view v-else class="trend-chart">
                        <view class="trend-bars">
                            <view v-for="item in trendList" :key="item.date" class="trend-column">
                                <view class="trend-track">
                                    <view
                                        class="trend-fill"
                                        :style="getTrendFillStyle(item.height)"
                                    />
                                </view>

                                <text class="trend-amount">{{ formatMoney(item.value) }}</text>

                                <text class="trend-label">{{ item.label }}</text>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="section-card wm-panel-card" :style="panelStyle">
                    <view class="section-header">
                        <view>
                            <text class="section-title">经营提醒</text>
                        </view>
                    </view>

                    <view class="insight-list">
                        <view
                            v-for="item in insights"
                            :key="item.text"
                            class="insight-item"
                            :style="item.style"
                        >
                            <view class="insight-tag" :style="item.tagStyle">{{
                                item.levelText
                            }}</view>

                            <text class="insight-text">{{ item.text }}</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

import { onShow } from '@dcloudio/uni-app'

import PageShell from '@/components/base/PageShell.vue'

import BaseNavbar from '@/components/base/BaseNavbar.vue'

import {
    adminDashboardIncomeTrend,
    adminDashboardOrderStats,
    adminDashboardOverview,
    adminDashboardTeamOverview
} from '@/packages/common/api/adminDashboard'

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

interface TeamOverviewData {
    team: {
        total_staff: number

        active_staff: number

        recommended_staff: number
    }

    capacity: {
        month_label: string

        month_total_slots: number

        month_booked_slots: number

        month_occupied_slots: number

        booking_rate: number
    }

    todo: {
        pending_confirm: number

        pending_pay: number

        in_service: number

        waitlist_total: number

        total: number
    }

    members: Array<{
        id: number

        name: string

        avatar: string

        category_name: string

        is_recommend: number

        recent_order_count: number

        upcoming_booked_slots: number

        follow_up_count: number

        load_level: string
    }>
}

interface InsightItem {
    levelText: string

    text: string

    style: Record<string, string>

    tagStyle: Record<string, string>
}

const appStore = useAppStore()

const userStore = useUserStore()

const $theme = useThemeStore()

const createEmptyTeamOverview = (): TeamOverviewData => ({
    team: {
        total_staff: 0,

        active_staff: 0,

        recommended_staff: 0
    },

    capacity: {
        month_label: '',

        month_total_slots: 0,

        month_booked_slots: 0,

        month_occupied_slots: 0,

        booking_rate: 0
    },

    todo: {
        pending_confirm: 0,

        pending_pay: 0,

        in_service: 0,

        waitlist_total: 0,

        total: 0
    },

    members: []
})

const overview = ref<any>({})

const orderStats = ref<any>({})

const trendList = ref<TrendItem[]>([])

const teamOverview = ref<TeamOverviewData>(createEmptyTeamOverview())

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

    if (!rgb) return `rgba(11, 11, 11, ${alpha})`

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

    return current?.label || '--'
})

const periodLabel = computed(() => {
    if (!dateRange.value.startDate || !dateRange.value.endDate) return '--'

    return `${dateRange.value.startDate} 至 ${dateRange.value.endDate}`
})

const heroCardStyle = computed(() => ({
    background: 'linear-gradient(145deg, #111111 0%, #000000 58%, #2f2924 100%)',

    borderColor: 'rgba(255, 255, 255, 0.08)',

    boxShadow: '0 18rpx 42rpx rgba(11, 11, 11, 0.18)'
}))

const heroRefreshStyle = computed(() => ({
    backgroundColor: 'rgba(255, 255, 255, 0.1)',

    borderColor: 'rgba(255, 255, 255, 0.14)'
}))

const heroChipStyle = computed(() => ({
    backgroundColor: 'rgba(255, 255, 255, 0.09)',

    borderColor: 'rgba(255, 255, 255, 0.12)'
}))

const panelStyle = computed(() => ({
    backgroundColor: '#FFFFFF',

    borderColor: 'var(--wm-color-border, #E2DED5)',

    boxShadow: 'none'
}))

const activeTabStyle = computed(() => ({
    background: $theme.primaryColor,

    color: '#FFFFFF',

    borderColor: $theme.primaryColor,

    boxShadow: `0 8rpx 18rpx ${toRgba($theme.primaryColor, 0.14)}`
}))

const defaultTabStyle = computed(() => ({
    backgroundColor: 'rgba(255, 255, 255, 0.92)',

    color: 'var(--wm-text-secondary, #5F5A50)',

    borderColor: 'var(--wm-color-border, #E2DED5)'
}))

const teamStatStyle = computed(() => ({
    backgroundColor: 'var(--wm-color-bg-soft, #FFFFFF)',

    borderColor: 'var(--wm-color-border, #E2DED5)'
}))

const memberRecommendStyle = computed(() => ({
    color: $theme.primaryColor,

    backgroundColor: 'var(--wm-color-primary-soft, #F3F2EE)'
}))

const totalOrders = computed(() => Number(orderStats.value?.total_orders || 0))

const paidProgressOrders = computed(() => {
    const paidLabels = ['待服务', '服务中', '已完成', '已评价']

    return (orderStats.value?.status_counts || []).reduce((total: number, item: any) => {
        return paidLabels.includes(item.label) ? total + Number(item.count || 0) : total
    }, 0)
})

const paidProgressRate = computed(() => {
    if (!totalOrders.value) return 0

    return (paidProgressOrders.value / totalOrders.value) * 100
})

const capacityStats = computed(
    () => teamOverview.value.capacity || createEmptyTeamOverview().capacity
)

const teamStats = computed(() => teamOverview.value.team || createEmptyTeamOverview().team)

const todoStats = computed(() => teamOverview.value.todo || createEmptyTeamOverview().todo)

const summaryChips = computed(() => [
    {
        label: '团队人数',

        value: `${teamStats.value.total_staff || 0} 人`
    },

    {
        label: '当月档期占用',

        value: formatPercent(capacityStats.value.booking_rate)
    },

    {
        label: '待处理事项',

        value: `${todoStats.value.total || 0} 项`
    }
])

const formatGrowthText = (value: any, prefix: string) => {
    const amount = Number(value || 0)

    if (amount > 0) return `${prefix} +${amount.toFixed(1)}%`

    if (amount < 0) return `${prefix} ${amount.toFixed(1)}%`

    return `${prefix} 持平`
}

const coreMetrics = computed(() => [
    {
        label: '总收入',

        value: `¥${formatMoney(overview.value?.total_income)}`,

        hint: formatGrowthText(overview.value?.income_growth, '较上期'),

        color: $theme.primaryColor
    },

    {
        label: '净收入',

        value: `¥${formatMoney(overview.value?.net_income)}`,

        hint: `退款 ¥${formatMoney(overview.value?.total_refund)}`,

        color: $theme.secondaryColor
    },

    {
        label: '支付订单',

        value: `${Number(overview.value?.order_count || 0)} 单`,

        hint: `支付推进率 ${formatPercent(paidProgressRate.value)}`,

        color: '#4D4A42'
    },

    {
        label: '客单价',

        value: `¥${formatMoney(overview.value?.avg_order_amount)}`,

        hint: `今日收款 ¥${formatMoney(orderStats.value?.today?.amount)}`,

        color: '#9F7A2E'
    }
])

const todaySnapshots = computed(() => [
    {
        label: '今日新增订单',

        value: `${Number(orderStats.value?.today?.orders || 0)} 单`
    },

    {
        label: '今日已支付',

        value: `${Number(orderStats.value?.today?.paid_orders || 0)} 单`
    },

    {
        label: '候补跟进',

        value: `${todoStats.value.waitlist_total || 0} 条`
    }
])

const teamStatsCards = computed(() => [
    {
        label: '团队人数',

        value: `${teamStats.value.total_staff || 0} 人`
    },

    {
        label: '在岗可排班',

        value: `${teamStats.value.active_staff || 0} 人`
    },

    {
        label: '推荐成员',

        value: `${teamStats.value.recommended_staff || 0} 人`
    },

    {
        label: '候补跟进',

        value: `${todoStats.value.waitlist_total || 0} 条`
    },

    {
        label: '待确认订单',

        value: `${todoStats.value.pending_confirm || 0} 单`
    },

    {
        label: '服务中订单',

        value: `${todoStats.value.in_service || 0} 单`
    }
])

const memberCards = computed(() =>
    (teamOverview.value.members || []).map((item) => ({
        id: item.id,

        name: item.name,

        avatar: item.avatar || '/static/images/user/default_avatar.png',

        categoryName: item.category_name,

        isRecommend: item.is_recommend === 1,

        recentOrderCount: item.recent_order_count || 0,

        upcomingBookedSlots: item.upcoming_booked_slots || 0,

        followUpCount: item.follow_up_count || 0,

        loadLevel: item.load_level || '可分配'
    }))
)

const todoCards = computed(() => [
    {
        label: '待确认',

        value: `${todoStats.value.pending_confirm || 0}`,

        hint: '',

        color: '#C8A45D'
    },

    {
        label: '待支付',

        value: `${todoStats.value.pending_pay || 0}`,

        hint: '',

        color: '#C8A45D'
    },

    {
        label: '服务中',

        value: `${todoStats.value.in_service || 0}`,

        hint: '',

        color: '#6C665C'
    },

    {
        label: '候补跟进',

        value: `${todoStats.value.waitlist_total || 0}`,

        hint: '',

        color: '#6C665C'
    }
])

const statusColorMap: Record<string, string> = {
    待确认: '#C8A45D',

    待支付: '#C8A45D',

    待服务: '#4D4A42',

    服务中: '#6C665C',

    已完成: '#5F5A50',

    已评价: '#C8A45D',

    已取消: '#8A4B45',

    已暂停: '#9F7A2E',

    已退款: '#8A4B45',

    用户已删除: '#C8A45D'
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

            color: statusColorMap[item.label] || '#9A9388'
        }
    })

    return list.sort((a: any, b: any) => b.count - a.count)
})

const trendSummary = computed(() => {
    const values = trendList.value.map((item) => item.value)

    if (!values.length) return { peak: 0, avg: 0 }

    const total = values.reduce((sum, value) => sum + value, 0)

    return {
        peak: Math.max(...values),

        avg: total / values.length
    }
})

const insights = computed<InsightItem[]>(() => {
    const result: InsightItem[] = []

    const totalIncome = Number(overview.value?.total_income || 0)

    const totalRefund = Number(overview.value?.total_refund || 0)

    const refundRate = totalIncome > 0 ? (totalRefund / totalIncome) * 100 : 0

    const pendingPayRate = statusItems.value.find((item) => item.label === '待支付')?.percent || 0

    const cancelledRate = statusItems.value.find((item) => item.label === '已取消')?.percent || 0

    const waitlistTotal = Number(todoStats.value.waitlist_total || 0)

    const bookingRate = Number(capacityStats.value.booking_rate || 0)

    const activeStaff = Number(teamStats.value.active_staff || 0)

    const buildInsight = (level: InsightLevel, text: string): InsightItem => {
        const config = {
            good: {
                levelText: '稳定',

                color: '#4D4A42'
            },

            warning: {
                levelText: '关注',

                color: '#C8A45D'
            },

            risk: {
                levelText: '重点',

                color: '#8A4B45'
            }
        }[level]

        return {
            levelText: config.levelText,

            text,

            style: {
                backgroundColor: toRgba(config.color, 0.08),

                borderColor: toRgba(config.color, 0.14)
            },

            tagStyle: {
                color: config.color,

                backgroundColor: toRgba(config.color, 0.12)
            }
        }
    }

    if (activeStaff <= 0) {
        result.push(buildInsight('risk', '暂无可排班成员'))
    }

    if (bookingRate >= 85) {
        result.push(buildInsight('warning', `本月档期占用 ${formatPercent(bookingRate)}`))
    }

    if (waitlistTotal >= Math.max(3, activeStaff * 2) && activeStaff > 0) {
        result.push(buildInsight('risk', `候补跟进 ${waitlistTotal} 条`))
    }

    if (refundRate > 5) {
        result.push(buildInsight('warning', `退款率 ${formatPercent(refundRate)}`))
    }

    if (cancelledRate > 10) {
        result.push(buildInsight('risk', `取消订单占比 ${formatPercent(cancelledRate)}`))
    }

    if (pendingPayRate > 20) {
        result.push(buildInsight('warning', `待支付占比 ${formatPercent(pendingPayRate)}`))
    }

    if (!result.length) {
        result.push(buildInsight('good', '经营状态平稳'))
    }

    return result.slice(0, 3)
})

const getMetricCardStyle = (color: string) => ({
    backgroundColor: mixColor(color, '#FFFFFF', 0.95),

    borderColor: toRgba(color, 0.14)
})

const getTodoCardStyle = (color: string) => ({
    backgroundColor: mixColor(color, '#FFFFFF', 0.95),

    borderColor: toRgba(color, 0.14)
})

const getLoadTagStyle = (level: string) => {
    const toneMap: Record<string, { color: string }> = {
        高负载: { color: '#8A4B45' },

        平稳: { color: '#C8A45D' },

        可分配: { color: '#4D4A42' }
    }

    const tone = toneMap[level] || toneMap['可分配']

    return {
        color: tone.color,

        backgroundColor: toRgba(tone.color, 0.1)
    }
}

const getTrendFillStyle = (height: number) => ({
    height: `${height}rpx`,

    background: `linear-gradient(180deg, ${toRgba($theme.secondaryColor, 0.9)} 0%, ${
        $theme.primaryColor
    } 100%)`
})

const getStatusFillStyle = (color: string, percent: number) => ({
    width: `${Math.max(percent, 4)}%`,

    backgroundColor: color
})

const buildTrend = (data: Record<string, number>) => {
    const entries = Object.entries(data || {})

    if (!entries.length) {
        trendList.value = []

        return
    }

    const maxPoints = 8

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

            height: Math.max(26, Math.round((amount / maxValue) * 160))
        }
    })
}

const normalizeTeamOverview = (data: any): TeamOverviewData => ({
    team: {
        ...createEmptyTeamOverview().team,

        ...(data?.team || {})
    },

    capacity: {
        ...createEmptyTeamOverview().capacity,

        ...(data?.capacity || {})
    },

    todo: {
        ...createEmptyTeamOverview().todo,

        ...(data?.todo || {})
    },

    members: Array.isArray(data?.members) ? data.members : []
})

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

        const [overviewRes, trendRes, orderRes, teamRes] = await Promise.all([
            adminDashboardOverview(params),

            adminDashboardIncomeTrend({ type: 'daily', ...params }),

            adminDashboardOrderStats(params),

            adminDashboardTeamOverview(params)
        ])

        overview.value = overviewRes || {}

        orderStats.value = orderRes || {}

        teamOverview.value = normalizeTeamOverview(teamRes)

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
    if (!appStore.config?.feature_switch) {
        await appStore.getConfig()
    }

    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })

        return false
    }

    if (!userStore.userInfo?.id) {
        await userStore.getUser()
    }

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

onShow(async () => {
    $theme.setScene('admin')

    if (!(await ensureAccess())) return

    await loadData()
})
</script>

<style lang="scss" scoped>
.dashboard-page {
    padding-bottom: 32rpx;
}

.dashboard-page__stack {
    display: flex;

    flex-direction: column;

    gap: 20rpx;
}

.hero-card,
.section-card {
    border-radius: var(--wm-radius-card, 16rpx);

    border-width: 1rpx;

    border-style: solid;
}

.hero-card {
    padding: 28rpx;
}

.hero-top {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 20rpx;
}

.hero-main {
    flex: 1;

    min-width: 0;
}

.hero-eyebrow {
    display: block;

    font-size: 22rpx;

    line-height: 1.4;

    color: var(--wm-color-secondary, #c8a45d);
}

.hero-title {
    display: block;

    margin-top: 8rpx;

    font-size: 38rpx;

    line-height: 1.35;

    color: #ffffff;

    font-weight: 700;
}

.hero-period {
    display: block;

    margin-top: 12rpx;

    font-size: 24rpx;

    line-height: 1.5;

    color: rgba(255, 255, 255, 0.72);
}

.hero-refresh {
    flex-shrink: 0;

    min-width: 148rpx;

    padding: 18rpx 24rpx;

    border-radius: 999rpx;

    border-width: 1rpx;

    border-style: solid;

    text-align: center;
}

.hero-refresh-text {
    font-size: 24rpx;

    color: #ffffff;

    line-height: 1;
}

.hero-meta {
    display: flex;

    flex-wrap: wrap;

    gap: 12rpx 24rpx;

    margin-top: 24rpx;
}

.hero-meta-text {
    font-size: 22rpx;

    color: rgba(255, 255, 255, 0.66);

    line-height: 1.5;
}

.hero-chip-list {
    display: flex;

    flex-wrap: wrap;

    gap: 16rpx;

    margin-top: 24rpx;
}

.hero-chip {
    min-width: 180rpx;

    padding: 18rpx 20rpx;

    border-radius: var(--wm-radius-card-soft, 14rpx);

    border-width: 1rpx;

    border-style: solid;
}

.hero-chip-label,
.hero-chip-value {
    display: block;
}

.hero-chip-label {
    font-size: 22rpx;

    color: rgba(255, 255, 255, 0.64);
}

.hero-chip-value {
    margin-top: 8rpx;

    font-size: 30rpx;

    line-height: 1.3;

    color: #ffffff;

    font-weight: 700;
}

.range-tabs {
    display: flex;

    gap: 16rpx;

    padding: 12rpx;
}

.range-tab-item {
    flex: 1;

    padding: 20rpx 0;

    border-radius: var(--wm-radius-card-soft, 14rpx);

    border-width: 1rpx;

    border-style: solid;

    font-size: 26rpx;

    line-height: 1;

    text-align: center;
}

.section-card {
    padding: 28rpx;
}

.section-header {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 16rpx;
}

.section-title,
.section-subtitle {
    display: block;
}

.section-title {
    font-size: 30rpx;

    line-height: 1.35;

    color: var(--wm-text-primary, #111111);

    font-weight: 700;
}

.section-subtitle {
    margin-top: 8rpx;

    font-size: 22rpx;

    line-height: 1.5;

    color: var(--wm-text-secondary, #5f5a50);
}

.metric-grid,
.team-grid,
.todo-grid {
    display: grid;

    gap: 16rpx;

    margin-top: 24rpx;

    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.metric-card,
.todo-card,
.team-stat-item {
    border-radius: var(--wm-radius-card-soft, 14rpx);

    border-width: 1rpx;

    border-style: solid;
}

.metric-card,
.todo-card {
    padding: 24rpx;
}

.team-stat-item {
    padding: 22rpx;
}

.metric-label,
.metric-value,
.metric-hint,
.todo-label,
.todo-value,
.todo-hint,
.team-stat-label,
.team-stat-value {
    display: block;
}

.metric-label,
.todo-label,
.team-stat-label {
    font-size: 22rpx;

    color: var(--wm-text-secondary, #5f5a50);
}

.metric-value,
.todo-value,
.team-stat-value {
    margin-top: 10rpx;

    font-size: 34rpx;

    line-height: 1.3;

    color: var(--wm-text-primary, #111111);

    font-weight: 700;
}

.metric-hint,
.todo-hint {
    margin-top: 10rpx;

    font-size: 22rpx;

    line-height: 1.5;

    color: var(--wm-text-secondary, #5f5a50);
}

.snapshot-row {
    display: flex;

    gap: 16rpx;

    margin-top: 20rpx;
}

.snapshot-item {
    flex: 1;

    padding-top: 20rpx;

    border-top: 1rpx solid var(--wm-color-border, #e2ded5);
}

.snapshot-label,
.snapshot-value {
    display: block;
}

.snapshot-label {
    font-size: 22rpx;

    color: var(--wm-text-secondary, #5f5a50);
}

.snapshot-value {
    margin-top: 8rpx;

    font-size: 28rpx;

    color: var(--wm-text-primary, #111111);

    font-weight: 700;
}

.member-scroll {
    margin-top: 24rpx;

    white-space: nowrap;
}

.member-list {
    display: inline-flex;

    gap: 16rpx;

    padding-right: 8rpx;
}

.member-card {
    width: 420rpx;

    padding: 24rpx;

    border-radius: var(--wm-radius-card, 16rpx);

    background: linear-gradient(180deg, #ffffff 0%, #ffffff 100%);

    border: 1rpx solid var(--wm-color-border, #e2ded5);

    box-sizing: border-box;
}

.member-top {
    display: flex;

    align-items: center;

    gap: 16rpx;
}

.member-avatar {
    width: 84rpx;

    height: 84rpx;

    border-radius: 50%;

    flex-shrink: 0;

    background: var(--wm-color-bg-soft, #ffffff);
}

.member-main {
    flex: 1;

    min-width: 0;
}

.member-name-row {
    display: flex;

    align-items: center;

    gap: 10rpx;
}

.member-name {
    max-width: 160rpx;

    font-size: 28rpx;

    line-height: 1.35;

    color: var(--wm-text-primary, #111111);

    font-weight: 700;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.member-recommend,
.member-load-tag {
    padding: 6rpx 12rpx;

    border-radius: 999rpx;

    font-size: 20rpx;

    line-height: 1.2;
}

.member-role {
    display: block;

    margin-top: 8rpx;

    font-size: 22rpx;

    color: var(--wm-text-secondary, #5f5a50);

    line-height: 1.4;
}

.member-data-grid {
    display: grid;

    grid-template-columns: repeat(3, minmax(0, 1fr));

    gap: 12rpx;

    margin-top: 24rpx;
}

.member-data-item {
    padding: 16rpx 12rpx;

    border-radius: var(--wm-radius-card-soft, 14rpx);

    background: rgba(255, 255, 255, 0.92);
}

.member-data-label,
.member-data-value {
    display: block;

    text-align: center;
}

.member-data-label {
    font-size: 20rpx;

    color: var(--wm-text-secondary, #5f5a50);

    line-height: 1.4;
}

.member-data-value {
    margin-top: 8rpx;

    font-size: 28rpx;

    color: var(--wm-text-primary, #111111);

    font-weight: 700;
}

.status-list {
    margin-top: 24rpx;
}

.status-item + .status-item {
    margin-top: 20rpx;
}

.status-title {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16rpx;
}

.status-label-wrap {
    display: flex;

    align-items: center;

    gap: 10rpx;
}

.status-dot {
    width: 14rpx;

    height: 14rpx;

    border-radius: 50%;

    flex-shrink: 0;
}

.status-label,
.status-meta {
    font-size: 24rpx;

    line-height: 1.5;
}

.status-label {
    color: var(--wm-text-primary, #111111);
}

.status-meta {
    color: var(--wm-text-secondary, #5f5a50);
}

.status-track {
    width: 100%;

    height: 14rpx;

    margin-top: 10rpx;

    background: var(--wm-color-bg-soft, #ffffff);

    border-radius: 999rpx;

    overflow: hidden;
}

.status-fill {
    height: 100%;

    border-radius: 999rpx;
}

.trend-chart {
    margin-top: 24rpx;
}

.trend-bars {
    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 12rpx;
}

.trend-column {
    flex: 1;

    display: flex;

    flex-direction: column;

    align-items: center;
}

.trend-track {
    width: 100%;

    height: 180rpx;

    display: flex;

    align-items: flex-end;

    justify-content: center;

    padding: 0 6rpx;

    background: linear-gradient(180deg, #ffffff 0%, #ffffff 100%);

    border-radius: var(--wm-radius-card-soft, 14rpx);
}

.trend-fill {
    width: 100%;

    border-radius: 10rpx 10rpx 0 0;
}

.trend-amount,
.trend-label {
    display: block;

    text-align: center;
}

.trend-amount {
    margin-top: 10rpx;

    font-size: 20rpx;

    color: var(--wm-text-secondary, #5f5a50);

    line-height: 1.4;
}

.trend-label {
    margin-top: 6rpx;

    font-size: 20rpx;

    color: var(--wm-text-tertiary, #9a9388);

    line-height: 1.4;
}

.insight-list {
    margin-top: 24rpx;
}

.insight-item {
    display: flex;

    align-items: flex-start;

    gap: 16rpx;

    padding: 22rpx 20rpx;

    border-radius: var(--wm-radius-card, 16rpx);

    border-width: 1rpx;

    border-style: solid;
}

.insight-item + .insight-item {
    margin-top: 16rpx;
}

.insight-tag {
    flex-shrink: 0;

    padding: 8rpx 16rpx;

    border-radius: 999rpx;

    font-size: 22rpx;

    line-height: 1.2;
}

.insight-text {
    flex: 1;

    font-size: 24rpx;

    line-height: 1.6;

    color: var(--wm-text-primary, #111111);
}

.panel-empty {
    padding: 36rpx 0 12rpx;

    font-size: 24rpx;

    line-height: 1.5;

    color: var(--wm-text-tertiary, #9a9388);

    text-align: center;
}
</style>
