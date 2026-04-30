<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="admin" tone="workspace" hasSafeBottom>
        <BaseNavbar title="经营驾驶舱" title-align="left" />

        <view class="dashboard-page wm-page-content">
            <view class="dashboard-page__stack">
                <view class="decision-card wm-panel-card" :style="decisionCardStyle">
                    <view class="decision-card__top">
                        <view class="decision-card__copy">
                            <text class="decision-card__eyebrow">{{ decisionFocus.label }}</text>
                            <text class="decision-card__title">{{ decisionFocus.title }}</text>
                            <text class="decision-card__desc">{{ decisionFocus.description }}</text>
                        </view>

                        <view class="refresh-pill" @click="loadData">
                            <text class="refresh-pill__text">{{
                                loading ? '更新中' : '刷新'
                            }}</text>
                        </view>
                    </view>

                    <view class="decision-card__meta">
                        <text>{{ activeRangeLabel }}</text>
                        <text>{{ periodLabel }}</text>
                        <text>更新 {{ lastUpdated || '--' }}</text>
                    </view>

                    <view class="decision-metrics">
                        <view
                            v-for="item in decisionMetrics"
                            :key="item.label"
                            class="decision-metric"
                        >
                            <text class="decision-metric__label">{{ item.label }}</text>
                            <text class="decision-metric__value">{{ item.value }}</text>
                            <text class="decision-metric__hint">{{ item.hint }}</text>
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

                <view class="section-card wm-panel-card">
                    <view class="section-header">
                        <view>
                            <text class="section-title">关键指标</text>
                            <text class="section-subtitle">只保留收入、订单、转化与今日结果</text>
                        </view>
                    </view>

                    <view class="signal-grid">
                        <view
                            v-for="item in keyMetrics"
                            :key="item.label"
                            class="signal-card"
                            :class="'signal-card--' + item.tone"
                        >
                            <text class="signal-card__label">{{ item.label }}</text>
                            <text class="signal-card__value">{{ item.value }}</text>
                            <text class="signal-card__hint">{{ item.hint }}</text>
                        </view>
                    </view>
                </view>

                <view class="section-card wm-panel-card">
                    <view class="section-header">
                        <view>
                            <text class="section-title">今日优先处理</text>
                            <text class="section-subtitle">{{ prioritySummary }}</text>
                        </view>
                    </view>

                    <view class="priority-list">
                        <view
                            v-for="item in priorityCards"
                            :key="item.label"
                            class="priority-card"
                            :class="'priority-card--' + item.tone"
                        >
                            <view class="priority-card__head">
                                <text class="priority-card__label">{{ item.label }}</text>
                                <text class="priority-card__value">{{ item.value }}</text>
                            </view>
                            <text class="priority-card__action">{{ item.action }}</text>
                        </view>
                    </view>
                </view>

                <view class="section-card wm-panel-card">
                    <view class="section-header">
                        <view>
                            <text class="section-title">收入与产能</text>
                            <text class="section-subtitle"
                                >日均 {{ formatAmount(trendSummary.avg) }}，峰值
                                {{ formatAmount(trendSummary.peak) }}</text
                            >
                        </view>
                    </view>

                    <view class="trend-capacity">
                        <view class="trend-pane">
                            <view v-if="trendList.length === 0" class="panel-empty"
                                >暂无收入趋势</view
                            >

                            <view v-else class="trend-bars">
                                <view
                                    v-for="item in trendList"
                                    :key="item.date"
                                    class="trend-column"
                                >
                                    <view class="trend-track">
                                        <view
                                            class="trend-fill"
                                            :style="getTrendFillStyle(item.height)"
                                        />
                                    </view>
                                    <text class="trend-label">{{ item.label }}</text>
                                    <text class="trend-value">{{ formatAmount(item.value) }}</text>
                                </view>
                            </view>
                        </view>

                        <view class="capacity-pane">
                            <view class="capacity-pane__head">
                                <view>
                                    <text class="capacity-pane__label">本月档期占用</text>
                                    <text class="capacity-pane__value">{{
                                        formatPercent(capacityStats.booking_rate)
                                    }}</text>
                                </view>
                                <text class="capacity-pane__meta"
                                    >{{ capacityStats.month_occupied_slots }} /
                                    {{ capacityStats.month_total_slots }}</text
                                >
                            </view>

                            <view class="capacity-track">
                                <view class="capacity-fill" :style="capacityFillStyle" />
                            </view>

                            <view class="capacity-summary">
                                <text>在岗 {{ teamStats.active_staff || 0 }} 人</text>
                                <text>推荐 {{ teamStats.recommended_staff || 0 }} 人</text>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="section-card wm-panel-card">
                    <view class="section-header">
                        <view>
                            <text class="section-title">团队与风险</text>
                            <text class="section-subtitle">只展示需要经营者关注的成员与提醒</text>
                        </view>
                    </view>

                    <view v-if="focusMembers.length" class="member-focus-list">
                        <view v-for="item in focusMembers" :key="item.id" class="member-focus-item">
                            <image class="member-avatar" :src="item.avatar" mode="aspectFill" />
                            <view class="member-focus-main">
                                <text class="member-name">{{ item.name }}</text>
                                <text class="member-meta"
                                    >{{ item.categoryName || '服务人员' }} · 待跟进
                                    {{ item.followUpCount }}</text
                                >
                            </view>
                            <view class="member-load-tag" :class="'member-load-tag--' + item.tone">
                                {{ item.loadLevel }}
                            </view>
                        </view>
                    </view>

                    <view v-else class="panel-empty">暂无重点成员</view>

                    <view class="insight-list">
                        <view
                            v-for="item in insights"
                            :key="item.text"
                            class="insight-item"
                            :class="'insight-item--' + item.tone"
                        >
                            <view class="insight-tag">{{ item.levelText }}</view>
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

import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import {
    adminDashboardIncomeTrend,
    adminDashboardOrderStats,
    adminDashboardOverview,
    adminDashboardTeamOverview
} from '@/packages/common/api/adminDashboard'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'

type RangeKey = '7d' | '30d' | 'month'
type Tone = 'good' | 'warning' | 'risk' | 'neutral'

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

interface MetricItem {
    label: string
    value: string
    hint: string
    tone: Tone
}

interface PriorityItem {
    label: string
    value: string
    action: string
    tone: Tone
}

interface InsightItem {
    levelText: string
    text: string
    tone: Tone
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

const overview = ref<Record<string, any>>({})
const orderStats = ref<Record<string, any>>({})
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

const toNumber = (value: any) => Number(value || 0)
const formatInteger = (value: any) => String(Math.round(toNumber(value)))
const formatPercent = (value: any) => `${toNumber(value).toFixed(1)}%`

const formatAmount = (value: any) => {
    const amount = toNumber(value)

    if (Math.abs(amount) >= 10000) {
        return `¥${(amount / 10000).toFixed(1)}万`
    }

    return `¥${Math.round(amount)}`
}

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

const decisionCardStyle = computed(() => ({
    background: `linear-gradient(145deg, #111111 0%, #1d1b18 58%, ${toRgba(
        $theme.primaryColor,
        0.42
    )} 100%)`,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    boxShadow: '0 18rpx 42rpx rgba(11, 11, 11, 0.18)'
}))

const activeTabStyle = computed(() => ({
    background: $theme.primaryColor,
    color: '#FFFFFF',
    borderColor: $theme.primaryColor,
    boxShadow: `0 8rpx 18rpx ${toRgba($theme.primaryColor, 0.14)}`
}))

const defaultTabStyle = computed(() => ({
    backgroundColor: 'rgba(255, 255, 255, 0.94)',
    color: 'var(--wm-text-secondary, #5F5A50)',
    borderColor: 'var(--wm-color-border, #E2DED5)'
}))

const totalOrders = computed(() => toNumber(orderStats.value?.total_orders))

const paidProgressOrders = computed(() => {
    const paidLabels = ['待服务', '服务中', '已完成', '已评价']

    return (orderStats.value?.status_counts || []).reduce((total: number, item: any) => {
        return paidLabels.includes(item.label) ? total + toNumber(item.count) : total
    }, 0)
})

const paidProgressRate = computed(() => {
    if (!totalOrders.value) return 0

    return (paidProgressOrders.value / totalOrders.value) * 100
})

const statusItems = computed(() =>
    (orderStats.value?.status_counts || []).map((item: any) => {
        const count = toNumber(item.count)
        const percent = totalOrders.value ? (count / totalOrders.value) * 100 : 0

        return {
            status: item.status,
            label: item.label,
            count,
            percent: Number(percent.toFixed(1))
        }
    })
)

const capacityStats = computed(
    () => teamOverview.value.capacity || createEmptyTeamOverview().capacity
)
const teamStats = computed(() => teamOverview.value.team || createEmptyTeamOverview().team)
const todoStats = computed(() => teamOverview.value.todo || createEmptyTeamOverview().todo)

const pendingConfirmCount = computed(() => toNumber(todoStats.value.pending_confirm))
const pendingPayCount = computed(() => toNumber(todoStats.value.pending_pay))
const waitlistCount = computed(() => toNumber(todoStats.value.waitlist_total))
const todoTotal = computed(
    () => pendingConfirmCount.value + pendingPayCount.value + waitlistCount.value
)

const getStatusPercent = (label: string) => {
    return statusItems.value.find((item: any) => item.label === label)?.percent || 0
}

const incomeGrowthText = computed(() => {
    const growth = toNumber(overview.value?.income_growth)

    if (growth > 0) return `较上期 +${growth.toFixed(1)}%`
    if (growth < 0) return `较上期 ${growth.toFixed(1)}%`
    return '较上期持平'
})

const decisionFocus = computed(() => {
    const bookingRate = toNumber(capacityStats.value.booking_rate)
    const incomeGrowth = toNumber(overview.value?.income_growth)

    if (pendingConfirmCount.value > 0) {
        return {
            label: '优先决策',
            title: '先确认档期',
            description: `${pendingConfirmCount.value} 单待确认，优先锁定人员与档期，避免转化流失。`
        }
    }

    if (pendingPayCount.value > 0) {
        return {
            label: '优先决策',
            title: '催付待支付订单',
            description: `${pendingPayCount.value} 单待支付，先推动收款，让收入更确定。`
        }
    }

    if (waitlistCount.value > 0 && bookingRate < 85) {
        return {
            label: '增长机会',
            title: '转化候补需求',
            description: `${waitlistCount.value} 条候补可跟进，结合可用档期做快速分配。`
        }
    }

    if (bookingRate >= 85) {
        return {
            label: '产能提醒',
            title: '档期接近满载',
            description: `本月占用 ${formatPercent(bookingRate)}，优先保障高价值订单与交付质量。`
        }
    }

    if (incomeGrowth < 0) {
        return {
            label: '经营提醒',
            title: '收入较上期下降',
            description: `收入${incomeGrowth.toFixed(1)}%，重点看新增订单与待支付转化。`
        }
    }

    return {
        label: '经营状态',
        title: '经营平稳',
        description: '暂无高优先级风险，继续关注新增订单、待支付转化与档期占用。'
    }
})

const decisionMetrics = computed<MetricItem[]>(() => [
    {
        label: '本期收入',
        value: formatAmount(overview.value?.total_income),
        hint: incomeGrowthText.value,
        tone: 'neutral'
    },
    {
        label: '待处理',
        value: `${todoTotal.value} 项`,
        hint: `确认 ${pendingConfirmCount.value} · 支付 ${pendingPayCount.value}`,
        tone: todoTotal.value > 0 ? 'warning' : 'good'
    },
    {
        label: '档期占用',
        value: formatPercent(capacityStats.value.booking_rate),
        hint: `在岗 ${teamStats.value.active_staff || 0} 人`,
        tone: toNumber(capacityStats.value.booking_rate) >= 85 ? 'warning' : 'good'
    }
])

const keyMetrics = computed<MetricItem[]>(() => [
    {
        label: '净收入',
        value: formatAmount(overview.value?.net_income),
        hint: `退款 ${formatAmount(overview.value?.total_refund)}`,
        tone: 'neutral'
    },
    {
        label: '今日收款',
        value: formatAmount(orderStats.value?.today?.amount),
        hint: `今日新增 ${formatInteger(orderStats.value?.today?.orders)} 单`,
        tone: 'good'
    },
    {
        label: '总订单',
        value: `${formatInteger(totalOrders.value)} 单`,
        hint: `支付订单 ${formatInteger(paidProgressOrders.value)} 单`,
        tone: 'neutral'
    },
    {
        label: '支付推进',
        value: formatPercent(paidProgressRate.value),
        hint: `客单价 ${formatAmount(overview.value?.avg_order_amount)}`,
        tone: paidProgressRate.value >= 70 ? 'good' : 'warning'
    }
])

const prioritySummary = computed(() => {
    if (todoTotal.value === 0) return '暂无紧急待办，保持日常巡检。'

    return `共 ${todoTotal.value} 项待推进，优先确认档期，其次推动收款。`
})

const priorityCards = computed<PriorityItem[]>(() => [
    {
        label: '待确认',
        value: `${pendingConfirmCount.value}`,
        action: pendingConfirmCount.value > 0 ? '先确认人员与档期' : '无待确认订单',
        tone: pendingConfirmCount.value > 0 ? 'risk' : 'good'
    },
    {
        label: '待支付',
        value: `${pendingPayCount.value}`,
        action: pendingPayCount.value > 0 ? '跟进付款与尾款' : '待支付稳定',
        tone: pendingPayCount.value > 0 ? 'warning' : 'good'
    },
    {
        label: '候补',
        value: `${waitlistCount.value}`,
        action: waitlistCount.value > 0 ? '匹配空档并回访' : '暂无候补压力',
        tone: waitlistCount.value > 0 ? 'warning' : 'good'
    }
])

const trendSummary = computed(() => {
    const values = trendList.value.map((item) => item.value)

    if (!values.length) return { peak: 0, avg: 0 }

    const total = values.reduce((sum, value) => sum + value, 0)

    return {
        peak: Math.max(...values),
        avg: total / values.length
    }
})

const capacityFillStyle = computed(() => ({
    width: `${Math.min(100, Math.max(0, toNumber(capacityStats.value.booking_rate)))}%`,
    background: `linear-gradient(90deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor} 100%)`
}))

const memberCards = computed(() =>
    (teamOverview.value.members || []).map((item) => {
        const loadLevel = item.load_level || '可分配'
        const tone: Tone =
            loadLevel === '高负载' ? 'risk' : loadLevel === '平稳' ? 'warning' : 'good'

        return {
            id: item.id,
            name: item.name || '未命名成员',
            avatar: item.avatar || '/static/images/user/default_avatar.png',
            categoryName: item.category_name,
            upcomingBookedSlots: item.upcoming_booked_slots || 0,
            followUpCount: item.follow_up_count || 0,
            loadLevel,
            tone
        }
    })
)

const focusMembers = computed(() =>
    memberCards.value
        .filter(
            (item) => item.tone !== 'good' || item.followUpCount > 0 || item.upcomingBookedSlots > 0
        )
        .slice(0, 3)
)

const insights = computed<InsightItem[]>(() => {
    const result: InsightItem[] = []
    const totalIncome = toNumber(overview.value?.total_income)
    const totalRefund = toNumber(overview.value?.total_refund)
    const refundRate = totalIncome > 0 ? (totalRefund / totalIncome) * 100 : 0
    const pendingPayRate = getStatusPercent('待支付')
    const cancelledRate = getStatusPercent('已取消')
    const bookingRate = toNumber(capacityStats.value.booking_rate)
    const activeStaff = toNumber(teamStats.value.active_staff)

    const pushInsight = (tone: Tone, levelText: string, text: string) => {
        result.push({ tone, levelText, text })
    }

    if (activeStaff <= 0) {
        pushInsight('risk', '重点', '暂无可排班成员，需要先恢复团队可用状态。')
    }

    if (bookingRate >= 85) {
        pushInsight(
            'warning',
            '关注',
            `本月档期占用 ${formatPercent(bookingRate)}，后续接单需注意交付压力。`
        )
    }

    if (waitlistCount.value >= Math.max(3, activeStaff * 2) && activeStaff > 0) {
        pushInsight('risk', '重点', `候补 ${waitlistCount.value} 条偏高，需要尽快分配可用档期。`)
    }

    if (refundRate > 5) {
        pushInsight('warning', '关注', `退款率 ${formatPercent(refundRate)}，建议复盘退款原因。`)
    }

    if (cancelledRate > 10) {
        pushInsight(
            'risk',
            '重点',
            `取消订单占比 ${formatPercent(cancelledRate)}，需检查线索质量。`
        )
    }

    if (pendingPayRate > 20) {
        pushInsight(
            'warning',
            '关注',
            `待支付占比 ${formatPercent(pendingPayRate)}，建议集中催付。`
        )
    }

    if (!result.length) {
        pushInsight('good', '稳定', '暂无明显经营风险，保持当前节奏。')
    }

    return result.slice(0, 3)
})

const getTrendFillStyle = (height: number) => ({
    height: `${height}rpx`,
    background: `linear-gradient(180deg, ${toRgba($theme.secondaryColor, 0.92)} 0%, ${
        $theme.primaryColor
    } 100%)`
})

const buildTrend = (data: Record<string, number>) => {
    const entries = Object.entries(data || {})

    if (!entries.length) {
        trendList.value = []
        return
    }

    const maxPoints = 7
    let sampled = entries

    if (entries.length > maxPoints) {
        const step = Math.ceil(entries.length / maxPoints)
        sampled = entries.filter((_, index) => index % step === 0 || index === entries.length - 1)
    }

    const values = sampled.map(([, value]) => toNumber(value))
    const maxValue = Math.max(...values, 1)

    trendList.value = sampled.map(([date, value]) => {
        const amount = toNumber(value)

        return {
            date,
            label: date.slice(5),
            value: amount,
            height: Math.max(24, Math.round((amount / maxValue) * 150))
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

    if (Number(appStore.config?.feature_switch?.admin_dashboard ?? 1) !== 1) {
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

.decision-card,
.section-card {
    border-radius: var(--wm-radius-card, 16rpx);
    border-width: 1rpx;
    border-style: solid;
}

.decision-card {
    padding: 30rpx;
}

.decision-card__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.decision-card__copy {
    flex: 1;
    min-width: 0;
}

.decision-card__eyebrow,
.decision-card__title,
.decision-card__desc {
    display: block;
}

.decision-card__eyebrow {
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-color-secondary, #c8a45d);
}

.decision-card__title {
    margin-top: 10rpx;
    font-size: 42rpx;
    line-height: 1.28;
    color: #ffffff;
    font-weight: 800;
}

.decision-card__desc {
    margin-top: 14rpx;
    font-size: 25rpx;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.76);
}

.refresh-pill {
    flex-shrink: 0;
    padding: 16rpx 24rpx;
    border-radius: 999rpx;
    border: 1rpx solid rgba(255, 255, 255, 0.14);
    background: rgba(255, 255, 255, 0.1);
}

.refresh-pill__text {
    font-size: 24rpx;
    line-height: 1;
    color: #ffffff;
}

.decision-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx 22rpx;
    margin-top: 22rpx;

    text {
        font-size: 22rpx;
        line-height: 1.45;
        color: rgba(255, 255, 255, 0.62);
    }
}

.decision-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
    margin-top: 24rpx;
}

.decision-metric {
    min-width: 0;
    padding: 18rpx 16rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    border: 1rpx solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.09);
}

.decision-metric__label,
.decision-metric__value,
.decision-metric__hint {
    display: block;
}

.decision-metric__label {
    font-size: 21rpx;
    line-height: 1.35;
    color: rgba(255, 255, 255, 0.62);
}

.decision-metric__value {
    margin-top: 8rpx;
    font-size: 30rpx;
    line-height: 1.25;
    color: #ffffff;
    font-weight: 800;
}

.decision-metric__hint {
    margin-top: 8rpx;
    font-size: 20rpx;
    line-height: 1.35;
    color: rgba(255, 255, 255, 0.56);
}

.range-tabs {
    display: flex;
    gap: 16rpx;
    padding: 12rpx;
    border: 1rpx solid var(--wm-color-border, #e2ded5);
    border-radius: var(--wm-radius-card, 16rpx);
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
    border-color: var(--wm-color-border, #e2ded5);
    background: #ffffff;
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
    font-size: 31rpx;
    line-height: 1.35;
    color: var(--wm-text-primary, #111111);
    font-weight: 800;
}

.section-subtitle {
    margin-top: 8rpx;
    font-size: 23rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.signal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
    margin-top: 24rpx;
}

.signal-card {
    min-width: 0;
    padding: 24rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    border: 1rpx solid var(--wm-color-border, #e2ded5);
    background: linear-gradient(180deg, #ffffff 0%, #fbfaf7 100%);
}

.signal-card--good {
    background: #fbfaf4;
}

.signal-card--warning {
    border-color: rgba(200, 164, 93, 0.32);
    background: rgba(200, 164, 93, 0.08);
}

.signal-card--risk {
    border-color: rgba(138, 75, 69, 0.28);
    background: rgba(138, 75, 69, 0.08);
}

.signal-card__label,
.signal-card__value,
.signal-card__hint {
    display: block;
}

.signal-card__label {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.signal-card__value {
    margin-top: 10rpx;
    font-size: 35rpx;
    line-height: 1.28;
    color: var(--wm-text-primary, #111111);
    font-weight: 800;
}

.signal-card__hint {
    margin-top: 10rpx;
    font-size: 22rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #5f5a50);
}

.priority-list {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    margin-top: 24rpx;
}

.priority-card {
    padding: 22rpx 24rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    border: 1rpx solid var(--wm-color-border, #e2ded5);
    background: #ffffff;
}

.priority-card--good {
    background: #fbfaf7;
}

.priority-card--warning {
    border-color: rgba(200, 164, 93, 0.32);
    background: rgba(200, 164, 93, 0.08);
}

.priority-card--risk {
    border-color: rgba(138, 75, 69, 0.28);
    background: rgba(138, 75, 69, 0.08);
}

.priority-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.priority-card__label {
    font-size: 26rpx;
    line-height: 1.4;
    color: var(--wm-text-primary, #111111);
    font-weight: 700;
}

.priority-card__value {
    font-size: 36rpx;
    line-height: 1;
    color: var(--wm-text-primary, #111111);
    font-weight: 800;
}

.priority-card__action {
    display: block;
    margin-top: 8rpx;
    font-size: 23rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #5f5a50);
}

.trend-capacity {
    margin-top: 24rpx;
}

.trend-pane {
    padding: 22rpx 18rpx 18rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    background: #fbfaf7;
}

.trend-bars {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 10rpx;
}

.trend-column {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.trend-track {
    width: 100%;
    height: 160rpx;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 0 5rpx;
    border-radius: 12rpx;
    background: rgba(255, 255, 255, 0.92);
    box-sizing: border-box;
}

.trend-fill {
    width: 100%;
    border-radius: 10rpx 10rpx 0 0;
}

.trend-label,
.trend-value {
    display: block;
    max-width: 86rpx;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.trend-label {
    margin-top: 10rpx;
    font-size: 20rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.trend-value {
    margin-top: 4rpx;
    font-size: 20rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.capacity-pane {
    margin-top: 16rpx;
    padding: 24rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    border: 1rpx solid var(--wm-color-border, #e2ded5);
    background: #ffffff;
}

.capacity-pane__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.capacity-pane__label,
.capacity-pane__value,
.capacity-pane__meta {
    display: block;
}

.capacity-pane__label {
    font-size: 23rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.capacity-pane__value {
    margin-top: 8rpx;
    font-size: 36rpx;
    line-height: 1.2;
    color: var(--wm-text-primary, #111111);
    font-weight: 800;
}

.capacity-pane__meta {
    font-size: 23rpx;
    line-height: 1.45;
    color: var(--wm-text-secondary, #5f5a50);
}

.capacity-track {
    width: 100%;
    height: 16rpx;
    margin-top: 18rpx;
    overflow: hidden;
    border-radius: 999rpx;
    background: var(--wm-color-bg-soft, #f7f4ef);
}

.capacity-fill {
    height: 100%;
    border-radius: 999rpx;
}

.capacity-summary {
    display: flex;
    justify-content: space-between;
    gap: 16rpx;
    margin-top: 16rpx;

    text {
        font-size: 22rpx;
        line-height: 1.45;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.member-focus-list {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    margin-top: 24rpx;
}

.member-focus-item {
    display: flex;
    align-items: center;
    gap: 16rpx;
    padding: 20rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    border: 1rpx solid var(--wm-color-border, #e2ded5);
    background: #fbfaf7;
}

.member-avatar {
    width: 74rpx;
    height: 74rpx;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--wm-color-bg-soft, #f7f4ef);
}

.member-focus-main {
    flex: 1;
    min-width: 0;
}

.member-name,
.member-meta {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.member-name {
    font-size: 27rpx;
    line-height: 1.4;
    color: var(--wm-text-primary, #111111);
    font-weight: 700;
}

.member-meta {
    margin-top: 6rpx;
    font-size: 22rpx;
    line-height: 1.4;
    color: var(--wm-text-secondary, #5f5a50);
}

.member-load-tag {
    flex-shrink: 0;
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    line-height: 1.2;
}

.member-load-tag--good {
    color: #4d4a42;
    background: rgba(77, 74, 66, 0.1);
}

.member-load-tag--warning {
    color: #9f7a2e;
    background: rgba(200, 164, 93, 0.14);
}

.member-load-tag--risk {
    color: #8a4b45;
    background: rgba(138, 75, 69, 0.12);
}

.insight-list {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    margin-top: 20rpx;
}

.insight-item {
    display: flex;
    align-items: flex-start;
    gap: 14rpx;
    padding: 20rpx;
    border-radius: var(--wm-radius-card-soft, 14rpx);
    border: 1rpx solid var(--wm-color-border, #e2ded5);
    background: #ffffff;
}

.insight-item--good {
    background: #fbfaf7;
}

.insight-item--warning {
    border-color: rgba(200, 164, 93, 0.28);
    background: rgba(200, 164, 93, 0.08);
}

.insight-item--risk {
    border-color: rgba(138, 75, 69, 0.24);
    background: rgba(138, 75, 69, 0.08);
}

.insight-tag {
    flex-shrink: 0;
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    background: rgba(11, 11, 11, 0.06);
    font-size: 22rpx;
    line-height: 1.2;
    color: var(--wm-text-primary, #111111);
}

.insight-text {
    flex: 1;
    font-size: 24rpx;
    line-height: 1.55;
    color: var(--wm-text-primary, #111111);
}

.panel-empty {
    padding: 34rpx 0 12rpx;
    font-size: 24rpx;
    line-height: 1.5;
    color: var(--wm-text-tertiary, #9a9388);
    text-align: center;
}
</style>
