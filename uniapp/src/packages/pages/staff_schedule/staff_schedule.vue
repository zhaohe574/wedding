<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" hasSafeBottom>
        <BaseNavbar title="档期管理" />

        <view class="staff-schedule-page">
            <view class="staff-schedule-page__content wm-page-content">
                <view class="schedule-hero-card wm-panel-card">
                    <view class="schedule-hero-card__top">
                        <view class="hero-pill hero-pill--primary">
                            <text class="hero-pill__text">档期工作台</text>
                        </view>
                        <view :class="['hero-pill', `hero-pill--${monthContext.modifier}`]">
                            <text class="hero-pill__text">{{ monthContext.text }}</text>
                        </view>
                    </view>

                    <view class="schedule-hero-card__head">
                        <view class="schedule-hero-card__copy">
                            <text class="schedule-hero-card__title">
                                {{ year }} 年 {{ monthText }} 月档期
                            </text>
                            <text class="schedule-hero-card__desc">{{ monthHeadline }}</text>
                        </view>

                        <view class="schedule-nav">
                            <view
                                class="schedule-nav__btn"
                                :class="{ 'is-disabled': loadingMonth }"
                                @click="changeMonth(-1)"
                            >
                                <text class="schedule-nav__arrow">‹</text>
                            </view>
                            <view
                                class="schedule-nav__btn"
                                :class="{ 'is-disabled': loadingMonth }"
                                @click="changeMonth(1)"
                            >
                                <text class="schedule-nav__arrow">›</text>
                            </view>
                        </view>
                    </view>

                    <view class="hero-tip">
                        <view class="hero-tip__badge">
                            <tn-icon name="calendar" size="24" color="#0B0B0B" />
                        </view>
                        <view class="hero-tip__copy">
                            <text class="hero-tip__title">{{ monthTipTitle }}</text>
                            <text class="hero-tip__desc">{{ monthTipDesc }}</text>
                        </view>
                    </view>
                </view>

                <view class="staff-section-card wm-form-block">
                    <view class="section-head section-head--stack wm-section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title wm-section-title">月历视图</text>
                            <text class="section-head__desc wm-section-desc"> 先看待履约日期 </text>
                        </view>

                        <view class="legend-row">
                            <view v-for="item in legendItems" :key="item.label" class="legend-chip">
                                <view
                                    :class="['legend-chip__dot', `legend-chip__dot--${item.tone}`]"
                                />
                                <text class="legend-chip__text">{{ item.label }}</text>
                            </view>
                        </view>
                    </view>

                    <view class="calendar-shell">
                        <view class="week-header">
                            <text v-for="item in weekLabels" :key="item" class="week-header__cell">
                                {{ item }}
                            </text>
                        </view>

                        <view class="calendar-grid">
                            <view
                                v-for="cell in calendarCells"
                                :key="cell.dateStr"
                                class="day-cell"
                                :class="{
                                    'day-cell--selected': cell.dateStr === selectedDate,
                                    'day-cell--past': cell.isPast,
                                    'day-cell--other': !cell.currentMonth
                                }"
                                @click="selectDate(cell)"
                            >
                                <view
                                    class="day-cell__inner"
                                    :class="[
                                        `day-cell__inner--${getDayIndicator(cell.dateStr)}`,
                                        {
                                            'is-clickable': cell.currentMonth,
                                            'is-selected': cell.dateStr === selectedDate
                                        }
                                    ]"
                                >
                                    <view class="day-cell__head">
                                        <text class="day-cell__num">
                                            {{ cell.isToday ? '今' : cell.day }}
                                        </text>
                                        <text v-if="cell.isToday" class="day-cell__tag">今天</text>
                                    </view>

                                    <view class="day-cell__content">
                                        <text
                                            v-if="cell.currentMonth"
                                            :class="[
                                                'day-cell__status',
                                                `day-cell__status--${getDayIndicator(cell.dateStr)}`
                                            ]"
                                        >
                                            {{
                                                hasPendingOrder(cell.dateStr)
                                                    ? '已安排'
                                                    : getStatusLabel(
                                                          getDayStatusForView(cell.dateStr)
                                                      )
                                            }}
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <view v-if="selectedDate" class="staff-section-card wm-form-block">
                    <view class="section-head wm-section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title wm-section-title">{{
                                selectedDateLabel
                            }}</text>
                            <text class="section-head__desc wm-section-desc">{{
                                getWeekDay(selectedDate)
                            }}</text>
                        </view>

                        <view :class="['status-pill', `status-pill--${selectedDayView.modifier}`]">
                            <text class="status-pill__text">{{ selectedDayView.text }}</text>
                        </view>
                    </view>

                    <view class="selected-summary-card">
                        <view class="selected-summary-card__row">
                            <view class="selected-summary-card__copy">
                                <text class="selected-summary-card__eyebrow">当天安排</text>
                                <text class="selected-summary-card__title">
                                    {{ selectedDayView.title }}
                                </text>
                                <text class="selected-summary-card__desc">
                                    {{ selectedSummaryDesc }}
                                </text>
                            </view>
                            <view
                                :class="['focus-badge', `focus-badge--${selectedDayView.modifier}`]"
                            >
                                <text class="focus-badge__text">{{ selectedDayView.badge }}</text>
                            </view>
                        </view>
                    </view>

                    <view class="info-grid">
                        <view
                            v-for="item in infoCards"
                            :key="item.label"
                            :class="['info-card', { 'info-card--accent': item.accent }]"
                        >
                            <text class="info-card__label">{{ item.label }}</text>
                            <text class="info-card__value">{{ item.value }}</text>
                        </view>
                    </view>

                    <view v-if="selectedPendingOrders.length" class="day-order-list">
                        <view
                            v-for="item in selectedPendingOrders"
                            :key="`${item.service_date}-${item.order_id}`"
                            class="day-order-card"
                            @click="goOrderDetail(item.order_id)"
                        >
                            <view class="day-order-card__head">
                                <view class="day-order-card__copy">
                                    <text class="day-order-card__title">{{
                                        item.package_summary
                                    }}</text>
                                    <text class="day-order-card__meta">
                                        {{ item.contact_name || '未填写联系人' }}
                                        <text v-if="item.contact_mobile">
                                            ｜{{ item.contact_mobile }}
                                        </text>
                                    </text>
                                </view>
                                <view class="status-pill status-pill--warning">
                                    <text class="status-pill__text">待履约</text>
                                </view>
                            </view>

                            <view class="day-order-card__foot">
                                <text class="day-order-card__info">订单号 {{ item.order_sn }}</text>
                                <text class="day-order-card__info">
                                    服务项 {{ item.item_count }}
                                </text>
                            </view>

                            <text v-if="item.service_address" class="day-order-card__address">
                                {{ item.service_address }}
                            </text>
                        </view>
                    </view>

                    <view class="action-grid">
                        <view
                            class="schedule-action schedule-action--available"
                            :class="{
                                'is-disabled': isAvailableActionDisabled,
                                'is-active': displayDayStatus === 1 && !isSelectedPast
                            }"
                            @click="setStatus(1)"
                        >
                            <view class="schedule-action__icon schedule-action__icon--available">
                                <tn-icon name="check-circle" size="24" color="#4D4A42" />
                            </view>
                            <view class="schedule-action__copy">
                                <text class="schedule-action__title">设为可预约</text>
                                <text class="schedule-action__desc">开放当天新预约</text>
                            </view>
                        </view>

                        <view
                            class="schedule-action schedule-action--danger"
                            :class="{
                                'is-disabled': isUnavailableActionDisabled,
                                'is-active': displayDayStatus === 0 && !isSelectedPast
                            }"
                            @click="setStatus(0)"
                        >
                            <view class="schedule-action__icon schedule-action__icon--danger">
                                <tn-icon name="close-circle" size="24" color="#5A4433" />
                            </view>
                            <view class="schedule-action__copy">
                                <text class="schedule-action__title">设为不可用</text>
                                <text class="schedule-action__desc">关闭当天接单能力</text>
                            </view>
                        </view>
                    </view>

                    <view class="remark-card">
                        <view class="remark-card__head">
                            <text class="remark-card__title">档期备注</text>
                            <text class="remark-card__action" @click="openRemarkEditor">
                                编辑备注
                            </text>
                        </view>
                        <text class="remark-card__content">{{ selectedDayRemark }}</text>
                    </view>

                    <view class="focus-note">
                        <text class="focus-note__text">{{ selectedDayLimitText }}</text>
                    </view>
                </view>
            </view>
        </view>

        <BaseOverlayMask :show="showRemarkPopup" @close="closeRemarkEditor" />
        <tn-popup
            v-model="showRemarkPopup"
            open-direction="bottom"
            :overlay="false"
            :overlay-closeable="true"
            safe-area-inset-bottom
            :radius="24"
        >
            <view class="remark-popup wm-form-block">
                <view class="remark-popup__head">
                    <text class="remark-popup__action" @click="closeRemarkEditor">取消</text>
                    <text class="remark-popup__title">编辑档期备注</text>
                    <text
                        class="remark-popup__action remark-popup__action--primary"
                        @click="submitRemark"
                    >
                        保存
                    </text>
                </view>
                <view class="remark-popup__body">
                    <textarea
                        v-model="remarkDraft"
                        class="remark-popup__textarea"
                        maxlength="255"
                        placeholder="可填写当天安排说明、注意事项等"
                        :show-confirm-bar="false"
                        :auto-height="true"
                    />
                    <text class="remark-popup__count">{{ remarkDraft.length }}/255</text>
                </view>
            </view>
        </tn-popup>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import PageShell from '@/components/base/PageShell.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import { staffCenterScheduleMonth, staffCenterScheduleSetStatus } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

type DayIndicator = 'available' | 'unavailable' | 'booked' | 'locked' | 'reserved'
type BadgeModifier = 'primary' | 'success' | 'warning' | 'danger' | 'neutral'

interface CalendarCell {
    day: number
    dateStr: string
    currentMonth: boolean
    isPast: boolean
    isToday: boolean
}

interface DayViewModel {
    title: string
    text: string
    badge: string
    modifier: BadgeModifier
}

interface ScheduleDayItem {
    status?: number
    remark?: string
}

interface MonthSummary {
    available_days: number
    occupied_days: number
    unavailable_days: number
    pending_service_count: number
}

interface PendingServiceOrderItem {
    order_id: number
    order_sn: string
    service_date: string
    contact_name: string
    contact_mobile: string
    service_address: string
    package_summary: string
    item_count: number
    order_status: number
    order_status_desc: string
    can_staff_start: number
}

const $theme = useThemeStore()

const today = new Date()
const todayStr = formatDateStr(today)
const year = ref(today.getFullYear())
const month = ref(today.getMonth() + 1)
const selectedDate = ref(formatDateStr(today))
const schedules = ref<Record<string, Record<number, ScheduleDayItem>>>({})
const monthSummary = ref<MonthSummary>({
    available_days: 0,
    occupied_days: 0,
    unavailable_days: 0,
    pending_service_count: 0
})
const pendingServiceOrders = ref<PendingServiceOrderItem[]>([])
const loadingMonth = ref(false)
const submitting = ref(false)
const showRemarkPopup = ref(false)
const remarkDraft = ref('')

const weekLabels = ['日', '一', '二', '三', '四', '五', '六']
const legendItems = [
    { label: '待履约', tone: 'pending' },
    { label: '可预约', tone: 'available' },
    { label: '已安排', tone: 'booked' },
    { label: '已锁定', tone: 'locked' },
    { label: '内部预留', tone: 'reserved' },
    { label: '不可用', tone: 'unavailable' }
]

const monthText = computed(() => String(month.value).padStart(2, '0'))

const pendingOrdersByDate = computed<Record<string, PendingServiceOrderItem[]>>(() => {
    return pendingServiceOrders.value.reduce((acc, item) => {
        if (!acc[item.service_date]) {
            acc[item.service_date] = []
        }
        acc[item.service_date].push(item)
        return acc
    }, {} as Record<string, PendingServiceOrderItem[]>)
})

const selectedPendingOrders = computed(() => pendingOrdersByDate.value[selectedDate.value] || [])

const monthContext = computed(() => {
    const currentYear = today.getFullYear()
    const currentMonth = today.getMonth() + 1

    if (year.value === currentYear && month.value === currentMonth) {
        return {
            text: '本月排班',
            modifier: 'success' as const
        }
    }

    if (year.value > currentYear || (year.value === currentYear && month.value > currentMonth)) {
        return {
            text: '提前维护',
            modifier: 'warning' as const
        }
    }

    return {
        text: '历史回看',
        modifier: 'danger' as const
    }
})

const monthHeadline = computed(() => {
    if (monthSummary.value.pending_service_count > 0) {
        return `本月有 ${monthSummary.value.pending_service_count} 笔待履约订单，优先处理服务日期更近的安排`
    }
    if (monthSummary.value.occupied_days > 0) {
        return `本月已有 ${monthSummary.value.occupied_days} 天被订单或锁定状态占用`
    }
    return '当前月份暂无紧急履约压力，可提前维护可预约档期'
})

const monthTipTitle = computed(() => {
    if (monthSummary.value.pending_service_count > 0) return '先处理待履约日期'
    if (monthSummary.value.unavailable_days > 0) return '继续校准可预约区间'
    return '本月节奏平稳'
})

const monthTipDesc = computed(() => {
    return `可预约 ${monthSummary.value.available_days} 天，已占用 ${monthSummary.value.occupied_days} 天，不可用 ${monthSummary.value.unavailable_days} 天`
})

function formatDateStr(date: Date): string {
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
}

function getWeekDay(dateStr: string): string {
    const date = new Date(dateStr.replace(/-/g, '/'))
    return ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'][date.getDay()]
}

function getScheduleDay(dateStr: string): ScheduleDayItem | undefined {
    return schedules.value[dateStr]?.[0]
}

function hasPendingOrder(dateStr: string): boolean {
    return (pendingOrdersByDate.value[dateStr] || []).length > 0
}

const selectedDateLabel = computed(() => {
    if (!selectedDate.value) return ''
    const [selectedYear, selectedMonth, selectedDay] = selectedDate.value.split('-')
    return `${selectedYear} 年 ${Number(selectedMonth)} 月 ${Number(selectedDay)} 日`
})

const calendarCells = computed<CalendarCell[]>(() => {
    const cells: CalendarCell[] = []
    const firstDay = new Date(year.value, month.value - 1, 1)
    const lastDay = new Date(year.value, month.value, 0)
    const startWeekDay = firstDay.getDay()
    const daysInMonth = lastDay.getDate()

    const prevMonthLastDay = new Date(year.value, month.value - 1, 0).getDate()
    for (let i = startWeekDay - 1; i >= 0; i--) {
        const day = prevMonthLastDay - i
        const prevMonth = month.value - 1
        const prevYear = prevMonth <= 0 ? year.value - 1 : year.value
        const displayMonth = prevMonth <= 0 ? 12 : prevMonth
        const dateStr = `${prevYear}-${String(displayMonth).padStart(2, '0')}-${String(
            day
        ).padStart(2, '0')}`
        cells.push({ day, dateStr, currentMonth: false, isPast: true, isToday: false })
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year.value}-${String(month.value).padStart(2, '0')}-${String(
            day
        ).padStart(2, '0')}`
        cells.push({
            day,
            dateStr,
            currentMonth: true,
            isPast: dateStr < todayStr,
            isToday: dateStr === todayStr
        })
    }

    const remaining = 42 - cells.length
    for (let day = 1; day <= remaining; day++) {
        const nextMonth = month.value + 1
        const nextYear = nextMonth > 12 ? year.value + 1 : year.value
        const displayMonth = nextMonth > 12 ? 1 : nextMonth
        const dateStr = `${nextYear}-${String(displayMonth).padStart(2, '0')}-${String(
            day
        ).padStart(2, '0')}`
        cells.push({ day, dateStr, currentMonth: false, isPast: false, isToday: false })
    }

    return cells
})

function getDayStatus(dateStr: string): number {
    return Number(getScheduleDay(dateStr)?.status ?? -1)
}

function getDayStatusForView(dateStr: string): number {
    const status = getDayStatus(dateStr)
    return status === -1 ? 1 : status
}

function getDayIndicator(dateStr: string): DayIndicator {
    const status = getDayStatusForView(dateStr)
    if (status === 0) return 'unavailable'
    if (status === 2) return 'booked'
    if (status === 3) return 'locked'
    if (status === 4) return 'reserved'
    return 'available'
}

function getStatusLabel(status: number): string {
    const map: Record<number, string> = {
        0: '不可用',
        1: '可预约',
        2: '已安排',
        3: '已锁定',
        4: '预留'
    }
    return map[status] || '可预约'
}

function buildMonthSummary(
    targetYear: number,
    targetMonth: number,
    monthSchedules: Record<string, Record<number, ScheduleDayItem>>,
    monthOrders: PendingServiceOrderItem[]
): MonthSummary {
    const startDate = `${targetYear}-${String(targetMonth).padStart(2, '0')}-01`
    const endDate = formatDateStr(new Date(targetYear, targetMonth, 0))
    const pendingDateMap = new Set(monthOrders.map((item) => item.service_date))
    let availableDays = 0
    let occupiedDays = 0
    let unavailableDays = 0

    let cursor = startDate
    while (cursor <= endDate) {
        if (cursor >= todayStr) {
            const status = Number(monthSchedules[cursor]?.[0]?.status ?? 1)
            if (pendingDateMap.has(cursor)) {
                occupiedDays++
            } else if (status === 0) {
                unavailableDays++
            } else if (status === 2 || status === 3 || status === 4) {
                occupiedDays++
            } else {
                availableDays++
            }
        }

        const date = new Date(cursor.replace(/-/g, '/'))
        date.setDate(date.getDate() + 1)
        cursor = formatDateStr(date)
    }

    return {
        available_days: availableDays,
        occupied_days: occupiedDays,
        unavailable_days: unavailableDays,
        pending_service_count: monthOrders.length
    }
}

const heroMetrics = computed(() => [
    {
        label: '待履约',
        value: monthSummary.value.pending_service_count,
        accent: true
    },
    {
        label: '已占用',
        value: monthSummary.value.occupied_days,
        accent: false
    },
    {
        label: '可预约',
        value: monthSummary.value.available_days,
        accent: false
    }
])

const dayStatus = computed(() => getDayStatus(selectedDate.value))
const displayDayStatus = computed(() => getDayStatusForView(selectedDate.value))
const isSelectedPast = computed(() => selectedDate.value < todayStr)
const isLockedStatus = computed(
    () => dayStatus.value >= 2 || selectedPendingOrders.value.length > 0
)

function getStatusView(status: number): DayViewModel {
    const views: Record<number, DayViewModel> = {
        0: {
            title: '当天已关闭预约',
            text: '当前不可用',
            badge: '休息中',
            modifier: 'danger'
        },
        1: {
            title: '当天可正常接单',
            text: '当前可预约',
            badge: '可接单',
            modifier: 'success'
        },
        2: {
            title: '当天已有订单占用',
            text: '已安排',
            badge: '已占用',
            modifier: 'warning'
        },
        3: {
            title: '当天处于锁定状态',
            text: '已锁定',
            badge: '待释放',
            modifier: 'neutral'
        },
        4: {
            title: '当天已做内部预留',
            text: '内部预留',
            badge: '内部占位',
            modifier: 'primary'
        }
    }

    return views[status] || views[1]
}

const selectedDayView = computed(() => {
    if (selectedPendingOrders.value.length > 0) {
        return {
            title: '当天存在待履约订单',
            text: '待履约',
            badge: `${selectedPendingOrders.value.length} 笔待办`,
            modifier: 'warning' as const
        }
    }

    return getStatusView(displayDayStatus.value)
})

const selectedScheduleText = computed(() => getStatusLabel(displayDayStatus.value))

const selectedSummaryDesc = computed(() => {
    if (selectedPendingOrders.value.length > 0) {
        return '请先进入订单详情开始履约或跟进服务完成情况，当前日期不建议直接覆盖档期。'
    }
    if (displayDayStatus.value === 0) {
        return '当天不会对外开放预约，适合休息、请假或手动关闭接单。'
    }
    if (displayDayStatus.value === 1) {
        return '当天无订单占用，可继续保持开放状态或按实际情况关闭。'
    }
    return '当前日期已被业务状态占用，如需调整，请先处理对应订单或释放锁定。'
})

const selectedDayRemark = computed(() => {
    const remark = String(getScheduleDay(selectedDate.value)?.remark || '').trim()
    return remark || '暂无备注'
})

const selectedDayLimitText = computed(() => {
    if (isSelectedPast.value) return '历史日期不可调整'
    if (selectedPendingOrders.value.length > 0) return '当天存在待履约订单，不可直接覆盖档期'
    if (dayStatus.value >= 2) return '当前日期已被业务占用，不可直接修改'
    return '仅支持切换为可预约或不可用，并可补充备注'
})

const infoCards = computed(() => [
    {
        label: '档期状态',
        value: selectedScheduleText.value,
        accent: displayDayStatus.value === 1 && !isSelectedPast.value
    },
    {
        label: '待履约',
        value: `${selectedPendingOrders.value.length} 笔`,
        accent: selectedPendingOrders.value.length > 0
    },
    {
        label: '备注',
        value: selectedDayRemark.value,
        accent: false
    }
])

const isEditableDate = computed(() => !isSelectedPast.value && !isLockedStatus.value)
const isAvailableActionDisabled = computed(
    () => submitting.value || !isEditableDate.value || displayDayStatus.value === 1
)
const isUnavailableActionDisabled = computed(
    () => submitting.value || !isEditableDate.value || displayDayStatus.value === 0
)

function selectDate(cell: CalendarCell) {
    if (!cell.currentMonth) return
    selectedDate.value = cell.dateStr
}

function goOrderDetail(orderId: number) {
    uni.navigateTo({
        url: `/packages/pages/staff_order_detail/staff_order_detail?id=${orderId}`
    })
}

function openRemarkEditor() {
    if (!selectedDate.value) return
    remarkDraft.value = String(getScheduleDay(selectedDate.value)?.remark || '').trim()
    showRemarkPopup.value = true
}

function closeRemarkEditor() {
    showRemarkPopup.value = false
}

async function changeMonth(delta: number) {
    if (loadingMonth.value) return

    let nextMonth = month.value + delta
    let nextYear = year.value

    if (nextMonth > 12) {
        nextMonth = 1
        nextYear++
    }
    if (nextMonth < 1) {
        nextMonth = 12
        nextYear--
    }

    year.value = nextYear
    month.value = nextMonth

    if (nextYear === today.getFullYear() && nextMonth === today.getMonth() + 1) {
        selectedDate.value = todayStr
    } else {
        selectedDate.value = `${nextYear}-${String(nextMonth).padStart(2, '0')}-01`
    }

    await fetchMonth()
}

async function setStatus(status: number, remark?: string): Promise<boolean> {
    if (submitting.value) return false
    if (status === 1 && isAvailableActionDisabled.value && remark === undefined) return false
    if (status === 0 && isUnavailableActionDisabled.value && remark === undefined) return false

    if (!isEditableDate.value) {
        uni.showToast({ title: '该日期不可调整', icon: 'none' })
        return false
    }

    try {
        submitting.value = true
        const nextRemark =
            remark !== undefined
                ? remark.trim()
                : String(getScheduleDay(selectedDate.value)?.remark || '')
        await staffCenterScheduleSetStatus({
            date: selectedDate.value,
            status,
            remark: nextRemark
        })
        await fetchMonth()
        uni.showToast({ title: '设置成功', icon: 'success' })
        return true
    } catch (error: any) {
        const msg = typeof error === 'string' ? error : error?.msg || error?.message || '设置失败'
        uni.showToast({ title: msg, icon: 'none' })
        return false
    } finally {
        submitting.value = false
    }
}

async function submitRemark() {
    if (!selectedDate.value) return
    const success = await setStatus(displayDayStatus.value, remarkDraft.value)
    if (success) {
        showRemarkPopup.value = false
    }
}

async function fetchMonth() {
    if (loadingMonth.value) return

    try {
        loadingMonth.value = true
        const response = await staffCenterScheduleMonth({ year: year.value, month: month.value })
        schedules.value = response?.schedules || {}
        pendingServiceOrders.value = response?.pending_service_orders || []
        monthSummary.value =
            response?.month_summary ||
            buildMonthSummary(year.value, month.value, schedules.value, pendingServiceOrders.value)
    } catch (error: any) {
        schedules.value = {}
        pendingServiceOrders.value = []
        monthSummary.value = buildMonthSummary(year.value, month.value, {}, [])
        const msg =
            typeof error === 'string' ? error : error?.msg || error?.message || '加载档期失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        loadingMonth.value = false
    }
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    await fetchMonth()
})
</script>

<style lang="scss" scoped>
.staff-schedule-page {
    min-height: 100vh;
    padding: 20rpx 0 40rpx;
    background: radial-gradient(
            circle at top left,
            rgba(247, 240, 223, 0.92) 0,
            rgba(247, 240, 223, 0) 42%
        ),
        radial-gradient(
            circle at top right,
            rgba(247, 240, 223, 0.72) 0,
            rgba(247, 240, 223, 0) 34%
        ),
        linear-gradient(180deg, #ffffff 0%, #F8F7F2 100%);

    &__content {
        display: flex;
        flex-direction: column;
        gap: 18rpx;
    }
}

.schedule-hero-card {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 24rpx 24rpx 26rpx;
    border-radius: 34rpx;
    border: 1rpx solid var(--wm-color-border-strong, #d8c28a);
    background: linear-gradient(135deg, #ffffff 0%, #f7f0df 100%);
    box-shadow: 0 20rpx 44rpx rgba(17, 17, 17, 0.16);

    &__top,
    &__head {
        display: flex;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__top {
        align-items: center;
    }

    &__head {
        align-items: flex-start;
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: 36rpx;
        font-weight: 700;
        line-height: 1.3;
        color: var(--wm-text-primary, #111111);
    }
}

.hero-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38rpx;
    padding: 9rpx 14rpx;
    border-radius: 999rpx;
    box-sizing: border-box;

    &__text {
        font-size: 22rpx;
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1;
    }

    &--primary {
        background: #f3f2ee;

        .hero-pill__text {
            color: var(--wm-color-primary, #0b0b0b);
        }
    }

    &--success,
    &--warning,
    &--danger {
        border: 1rpx solid var(--wm-color-border, #e7e2d6);
        background: rgba(255, 255, 255, 0.84);
    }

    &--success .hero-pill__text {
        color: #4d4a42;
    }

    &--warning .hero-pill__text {
        color: #c8a45d;
    }

    &--danger .hero-pill__text {
        color: #5a4433;
    }
}

.schedule-nav {
    display: flex;
    gap: 10rpx;

    &__btn {
        width: 58rpx;
        height: 58rpx;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999rpx;
        border: 1rpx solid rgba(216, 194, 138, 0.92);
        background: rgba(255, 255, 255, 0.84);
        box-sizing: border-box;

        &.is-disabled {
            opacity: 0.45;
        }
    }

    &__arrow {
        font-size: 30rpx;
        font-weight: 700;
        line-height: 1;
        color: var(--wm-color-primary, #0b0b0b);
    }
}

.hero-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10rpx;
}

.hero-metric {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    padding: 12rpx 14rpx;
    border-radius: 22rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: rgba(255, 255, 255, 0.78);
    box-sizing: border-box;

    &--accent {
        background: #f3f2ee;
        border-color: var(--wm-color-border-strong, #d8c28a);
    }

    &__label {
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &--accent .hero-metric__label {
        color: var(--wm-color-primary, #0b0b0b);
    }

    &__value {
        font-size: 34rpx;
        font-weight: 700;
        line-height: 1.2;
        color: var(--wm-text-primary, #111111);
    }
}

.staff-section-card {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 22rpx 24rpx;
    border-radius: 30rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: rgba(255, 255, 255, 0.92);
    box-shadow: 0 18rpx 36rpx rgba(17, 17, 17, 0.2);
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
}

.section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;

    &--stack {
        flex-direction: column;
        align-items: stretch;
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__meta {
        font-size: 22rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.legend-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
}

.legend-chip {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 7rpx 12rpx;
    border-radius: 999rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);

    &__dot {
        width: 10rpx;
        height: 10rpx;
        border-radius: 999rpx;

        &--available {
            background: #4d4a42;
        }

        &--booked {
            background: #9F7A2E;
        }

        &--locked {
            background: #6C665C;
        }

        &--reserved {
            background: var(--wm-color-primary, #0b0b0b);
        }

        &--unavailable {
            background: #5a4433;
        }
    }

    &__text {
        font-size: 18rpx;
        font-weight: 700;
        line-height: 1;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.calendar-shell {
    padding: 14rpx;
    border-radius: 24rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.week-header,
.calendar-grid {
    display: flex;
    flex-wrap: wrap;
}

.week-header {
    margin-bottom: 8rpx;

    &__cell {
        width: calc(100% / 7);
        padding: 10rpx 0 14rpx;
        text-align: center;
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1.2;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.day-cell {
    width: calc(100% / 7);
    padding: 4rpx;
    box-sizing: border-box;

    &__inner {
        min-height: 96rpx;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 8rpx;
        padding: 10rpx 10rpx 10rpx;
        border-radius: 18rpx;
        border: 1rpx solid transparent;
        background: transparent;
        box-sizing: border-box;

        &.is-clickable {
            background: rgba(255, 255, 255, 0.9);
        }

        &.is-selected {
            border-color: rgba(11, 11, 11, 0.4);
            background: #f3f2ee;
            box-shadow: 0 10rpx 22rpx rgba(11, 11, 11, 0.1);
        }

        &--booked {
            background: #f7f0df;
        }

        &--locked {
            background: rgba(108, 102, 92, 0.08);
        }

        &--reserved {
            background: rgba(11, 11, 11, 0.08);
        }

        &--unavailable {
            background: rgba(90, 68, 51, 0.08);
        }
    }

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8rpx;
    }

    &__num {
        font-size: 26rpx;
        font-weight: 700;
        line-height: 1.2;
        color: var(--wm-text-primary, #111111);
    }

    &__tag {
        flex-shrink: 0;
        padding: 4rpx 8rpx;
        border-radius: 999rpx;
        background: #f3f2ee;
        font-size: 16rpx;
        font-weight: 700;
        line-height: 1;
        color: var(--wm-color-primary, #0b0b0b);
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        min-height: 20rpx;
    }

    &__dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: 999rpx;

        &--available {
            background: #4d4a42;
        }
    }

    &__status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30rpx;
        height: 30rpx;
        padding: 0 8rpx;
        border-radius: 999rpx;
        font-size: 16rpx;
        font-weight: 700;
        line-height: 1;
        box-sizing: border-box;

        &--booked {
            background: #F7F0DF;
            color: #c8a45d;
        }

        &--locked {
            background: rgba(108, 102, 92, 0.14);
            color: #6C665C;
        }

        &--reserved {
            background: #f3f2ee;
            color: var(--wm-color-primary, #0b0b0b);
        }

        &--unavailable {
            background: rgba(90, 68, 51, 0.14);
            color: #5a4433;
        }
    }

    &--other {
        .day-cell__inner {
            background: transparent;
        }

        .day-cell__num {
            color: #D8D3C7;
        }
    }

    &--past {
        .day-cell__num {
            color: #9a9388;
        }

        .day-cell__inner {
            opacity: 0.78;
        }
    }

    &--today .day-cell__num {
        color: var(--wm-color-primary, #0b0b0b);
    }
}

.selected-day-card {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    border: 1rpx solid var(--wm-color-border-strong, #d8c28a);
    background: linear-gradient(135deg, #ffffff 0%, #f3f2ee 100%);

    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__eyebrow {
        font-size: 18rpx;
        font-weight: 700;
        line-height: 1;
        color: var(--wm-color-primary, #0b0b0b);
    }

    &__title {
        font-size: 28rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #111111);
    }
}

.focus-badge,
.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34rpx;
    padding: 7rpx 12rpx;
    border-radius: 999rpx;
    box-sizing: border-box;

    &__text {
        font-size: 18rpx;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }

    &--primary {
        background: #f3f2ee;

        .focus-badge__text,
        .status-pill__text {
            color: var(--wm-color-primary, #0b0b0b);
        }
    }

    &--success {
        background: rgba(77, 74, 66, 0.12);

        .focus-badge__text,
        .status-pill__text {
            color: #4d4a42;
        }
    }

    &--warning {
        background: #f7f0df;

        .focus-badge__text,
        .status-pill__text {
            color: #c8a45d;
        }
    }

    &--danger {
        background: rgba(90, 68, 51, 0.12);

        .focus-badge__text,
        .status-pill__text {
            color: #5a4433;
        }
    }

    &--neutral {
        background: rgba(108, 102, 92, 0.12);

        .focus-badge__text,
        .status-pill__text {
            color: #6C665C;
        }
    }
}

.info-grid,
.action-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10rpx;
}

.info-card {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    min-height: 112rpx;
    padding: 16rpx 18rpx;
    border-radius: 22rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: #ffffff;
    box-sizing: border-box;

    &--accent {
        background: #f3f2ee;
        border-color: var(--wm-color-border-strong, #d8c28a);
    }

    &__label {
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1.2;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__value {
        font-size: 24rpx;
        font-weight: 700;
        line-height: 1.4;
        color: var(--wm-text-primary, #111111);
    }
}

.schedule-action {
    display: flex;
    align-items: center;
    gap: 12rpx;
    min-height: 104rpx;
    padding: 16rpx 18rpx;
    border-radius: 22rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: #ffffff;
    box-sizing: border-box;

    &__icon {
        width: 54rpx;
        height: 54rpx;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999rpx;

        &--available {
            background: rgba(77, 74, 66, 0.12);
        }

        &--danger {
            background: rgba(90, 68, 51, 0.12);
        }
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: 26rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #111111);
    }

    &--available.is-active {
        background: rgba(77, 74, 66, 0.1);
        border-color: rgba(77, 74, 66, 0.2);
    }

    &--danger.is-active {
        background: rgba(90, 68, 51, 0.1);
        border-color: rgba(90, 68, 51, 0.2);
    }

    &.is-disabled {
        opacity: 0.48;
    }
}

.focus-note {
    padding: 18rpx 20rpx;
    border-radius: 22rpx;
    background: #ffffff;
    border: 1rpx solid rgba(216, 194, 138, 0.78);

    &__text {
        font-size: 22rpx;
        font-weight: 600;
        line-height: 1.6;
        color: #5A4433;
    }
}

.schedule-hero-card {
    &__copy {
        gap: 8rpx;
    }

    &__desc {
        font-size: 24rpx;
        line-height: 1.6;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.hero-tip {
    display: flex;
    align-items: center;
    gap: 16rpx;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    border: 1rpx solid rgba(216, 194, 138, 0.92);
    background: rgba(255, 255, 255, 0.72);

    &__badge {
        width: 56rpx;
        height: 56rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 18rpx;
        background: #f3f2ee;
        flex-shrink: 0;
    }

    &__copy {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: 26rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #111111);
    }

    &__desc {
        font-size: 22rpx;
        line-height: 1.5;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.day-order-list {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}

.day-order-card,
.selected-summary-card {
    border-radius: 24rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: linear-gradient(180deg, #FFFFFF 0%, #FFFFFF 100%);
}

.day-order-card {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 20rpx;

    &__head,
    &__info-row,
    &__foot {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__title {
        font-size: 28rpx;
        font-weight: 700;
        line-height: 1.4;
        color: var(--wm-text-primary, #111111);
    }

    &__meta,
    &__info,
    &__address {
        font-size: 22rpx;
        line-height: 1.5;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.mini-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8rpx 12rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: #fff;

    &__text {
        font-size: 20rpx;
        line-height: 1;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &--warning {
        background: rgba(159, 122, 46, 0.08);
        border-color: rgba(159, 122, 46, 0.18);
    }

    &--warning .mini-chip__text {
        color: #9f7a2e;
    }
}

.legend-chip__dot--pending {
    background: var(--wm-color-primary, #0b0b0b);
}

.day-cell {
    &__inner {
        min-height: 116rpx;
        gap: 10rpx;
    }

    &__content {
        display: flex;
        flex-direction: column;
        gap: 8rpx;
        align-items: flex-start;
    }

    &__status {
        min-width: 0;
        height: auto;
        padding: 0;
        border-radius: 0;
        background: transparent;
        font-size: 18rpx;
        line-height: 1.2;
    }

    &__status--available {
        color: #4d4a42;
    }

    &__status--booked {
        color: #9f7a2e;
    }

    &__status--locked {
        color: #6C665C;
    }

    &__status--reserved {
        color: #c8a45d;
    }

    &__status--unavailable {
        color: #5a4433;
    }
}

.selected-summary-card {
    display: flex;
    padding: 18rpx 20rpx;

    &__row {
        width: 100%;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__eyebrow {
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1;
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__title {
        font-size: 30rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #111111);
    }

    &__desc {
        font-size: 22rpx;
        line-height: 1.6;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.info-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.schedule-action__desc {
    font-size: 22rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.remark-card {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 20rpx;
    border-radius: 24rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: #ffffff;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
    }

    &__title,
    &__action {
        font-size: 24rpx;
        font-weight: 700;
        line-height: 1.2;
    }

    &__title {
        color: var(--wm-text-primary, #111111);
    }

    &__action {
        color: var(--wm-color-primary, #0b0b0b);
    }

    &__content {
        font-size: 24rpx;
        line-height: 1.7;
        color: var(--wm-text-secondary, #5f5a50);
    }
}

.remark-popup {
    padding: 20rpx 24rpx calc(24rpx + env(safe-area-inset-bottom));
    background: #FFFFFF;
    border-top-left-radius: 32rpx;
    border-top-right-radius: 32rpx;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
        padding-bottom: 20rpx;
    }

    &__title,
    &__action {
        font-size: 26rpx;
        line-height: 1.3;
    }

    &__title {
        font-weight: 700;
        color: var(--wm-text-primary, #111111);
    }

    &__action {
        color: var(--wm-text-secondary, #5f5a50);
    }

    &__action--primary {
        font-weight: 700;
        color: var(--wm-color-primary, #0b0b0b);
    }

    &__body {
        display: flex;
        flex-direction: column;
        gap: 12rpx;
    }

    &__textarea {
        width: 100%;
        min-height: 220rpx;
        padding: 22rpx;
        border-radius: 24rpx;
        border: 1rpx solid var(--wm-color-border, #e7e2d6);
        background: #fff;
        box-sizing: border-box;
        font-size: 26rpx;
        line-height: 1.6;
        color: var(--wm-text-primary, #111111);
    }

    &__count {
        align-self: flex-end;
        font-size: 20rpx;
        line-height: 1;
        color: #9a9388;
    }
}

@media screen and (max-width: 375px) {
    .staff-section-card,
    .schedule-hero-card {
        padding-left: 20rpx;
        padding-right: 20rpx;
    }

    .hero-metrics,
    .info-grid,
    .action-grid {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .day-cell__inner {
        min-height: 88rpx;
        padding-left: 8rpx;
        padding-right: 8rpx;
    }

    .day-cell__num {
        font-size: 26rpx;
    }
}
</style>
