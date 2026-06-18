<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" tone="workspace">
        <BaseNavbar
            title="档期管理"
            title-align="center"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="staff-schedule-page">
            <view class="staff-schedule-page__content wm-page-content">
                <view class="schedule-card-wrap">
                    <BaseCard
                        variant="hero"
                        scene="staff"
                        class="schedule-hero"
                        background="linear-gradient(145deg, #2B261D 0%, #191713 62%, #3A2A16 100%)"
                        border="1rpx solid #D9BE82"
                        box-shadow="0 28rpx 68rpx rgba(74, 43, 24, 0.18)"
                    >
                        <view class="schedule-hero__head">
                            <view class="schedule-hero__copy">
                                <text class="schedule-hero__eyebrow">{{ heroEyebrow }}</text>
                                <text class="schedule-hero__title">
                                    {{ year }} 年 {{ activePanel === 'calendar' ? `${monthText} 月` : '全年锁档' }}
                                </text>
                            </view>

                            <view class="schedule-hero__actions">
                                <BaseButton
                                    :label="activePanel === 'calendar' ? '上月' : '上年'"
                                    variant="light"
                                    size="mini"
                                    height="54rpx"
                                    :disabled="isPeriodLoading"
                                    @click="changePeriod(-1)"
                                />
                                <BaseButton
                                    :label="activePanel === 'calendar' ? '下月' : '下年'"
                                    variant="light"
                                    size="mini"
                                    height="54rpx"
                                    :disabled="isPeriodLoading"
                                    @click="changePeriod(1)"
                                />
                            </view>
                        </view>

                        <view class="schedule-tabs">
                            <view
                                v-for="tab in panelTabs"
                                :key="tab.value"
                                :class="[
                                    'schedule-tab',
                                    { 'schedule-tab--active': activePanel === tab.value }
                                ]"
                                @click="switchPanel(tab.value)"
                            >
                                <text class="schedule-tab__text">{{ tab.label }}</text>
                            </view>
                        </view>

                        <view class="hero-metrics">
                            <view
                                v-for="item in heroMetrics"
                                :key="item.label"
                                :class="['hero-metric', { 'hero-metric--accent': item.accent }]"
                            >
                                <text class="hero-metric__label">{{ item.label }}</text>
                                <text class="hero-metric__value">{{ item.value }}</text>
                            </view>
                        </view>
                    </BaseCard>
                </view>

                <template v-if="activePanel === 'calendar'">
                    <view class="schedule-card-wrap schedule-card-wrap--spaced">
                        <BaseCard variant="panel" scene="staff" class="schedule-section schedule-calendar">
                            <view class="schedule-section__head">
                                <view class="schedule-section__copy">
                                    <text class="schedule-section__title">月历</text>
                                </view>
                            </view>

                            <BaseScheduleCalendar
                                v-model="selectedDate"
                                class="schedule-calendar__component"
                                :days="calendarDays"
                                @select="handleCalendarSelect"
                            />
                        </BaseCard>
                    </view>

                    <view v-if="selectedDate" class="schedule-card-wrap schedule-card-wrap--spaced">
                        <BaseCard
                            variant="panel"
                            scene="staff"
                            class="schedule-section selected-panel"
                            padding="24rpx 22rpx 20rpx"
                        >
                            <view class="schedule-section__head">
                                <view class="schedule-section__copy">
                                    <text class="schedule-section__title">{{ selectedDateLabel }}</text>
                                    <text class="schedule-section__meta">{{ getWeekDay(selectedDate) }}</text>
                                </view>

                                <StatusBadge :tone="selectedDayView.modifier as BadgeModifier" size="md">
                                    {{ selectedDayView.text }}
                                </StatusBadge>
                            </view>

                            <view class="selected-status selected-panel__section">
                                <view class="selected-status__copy">
                                    <text class="selected-status__label">当天安排</text>
                                    <text class="selected-status__title">{{ selectedDayView.title }}</text>
                                </view>
                                <view
                                    :class="[
                                        'focus-badge',
                                        `focus-badge--${selectedDayView.modifier}`
                                    ]"
                                >
                                    <text class="focus-badge__text">{{ selectedDayView.badge }}</text>
                                </view>
                            </view>

                            <view class="detail-list selected-panel__section">
                                <BaseInfoRow label="档期状态" :value="selectedScheduleText" />
                                <BaseInfoRow
                                    label="待履约"
                                    :value="`${selectedPendingOrders.length} 笔`"
                                    :tone="selectedPendingOrders.length ? 'warning' : 'default'"
                                />
                                <BaseInfoRow label="备注" :value="selectedDayRemark" multiline />
                            </view>

                            <view
                                v-if="selectedPendingOrders.length"
                                class="day-order-list selected-panel__section"
                            >
                                <view
                                    v-for="item in selectedPendingOrders"
                                    :key="`${item.service_date}-${item.order_id}`"
                                    class="day-order-card"
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
                                        <StatusBadge tone="warning" size="sm">待履约</StatusBadge>
                                    </view>

                                    <text v-if="item.service_address" class="day-order-card__address">
                                        {{ item.service_address }}
                                    </text>

                                    <view class="day-order-card__foot">
                                        <text class="day-order-card__info">订单号 {{ item.order_sn }}</text>
                                        <BaseButton
                                            label="详情"
                                            variant="light"
                                            size="mini"
                                            height="54rpx"
                                            icon="right"
                                            icon-position="right"
                                            @click="goOrderDetail(item.order_id)"
                                        />
                                    </view>
                                </view>
                            </view>

                            <view class="action-grid selected-panel__section">
                                <BaseButton
                                    label="设为可预约"
                                    variant="light"
                                    size="sm"
                                    height="68rpx"
                                    :disabled="isAvailableActionDisabled"
                                    :loading="submitting && !isUnavailableActionDisabled"
                                    @click="setStatus(1)"
                                />
                                <BaseButton
                                    label="设为不可用"
                                    variant="dark"
                                    size="sm"
                                    height="68rpx"
                                    :disabled="isUnavailableActionDisabled"
                                    :loading="submitting && !isAvailableActionDisabled"
                                    @click="setStatus(0)"
                                />
                            </view>

                            <view class="remark-card selected-panel__section">
                                <view class="remark-card__head">
                                    <text class="remark-card__title">备注</text>
                                    <BaseButton
                                        label="编辑"
                                        variant="light"
                                        size="mini"
                                        height="48rpx"
                                        @click="openRemarkEditor"
                                    />
                                </view>
                            </view>
                        </BaseCard>
                    </view>
                </template>

                <view v-else class="schedule-card-wrap schedule-card-wrap--spaced">
                    <BaseCard
                        variant="panel"
                        scene="staff"
                        class="schedule-section booked-year-panel"
                        padding="24rpx 22rpx 24rpx"
                    >
                        <view class="schedule-section__head">
                            <view class="schedule-section__copy">
                                <text class="schedule-section__title">全年锁档</text>
                                <text class="schedule-section__meta">
                                    {{ year }} 年正式锁档日期
                                </text>
                            </view>
                            <StatusBadge
                                :key="bookedYearBadgeKey"
                                tone="warning"
                                size="md"
                                :label="bookedYearMeta"
                            />
                        </view>

                        <scroll-view scroll-y class="booked-year-list">
                            <LoadingState
                                v-if="bookedYearLoading && !bookedYearLoaded"
                                text="锁档日期加载中"
                            />

                            <template v-else-if="bookedMonthGroups.length">
                                <view
                                    v-for="group in bookedMonthGroups"
                                    :key="group.key"
                                    class="booked-month-group"
                                >
                                    <text class="booked-month-group__title">{{ group.label }}</text>
                                    <view class="booked-date-grid">
                                        <view
                                            v-for="date in group.dates"
                                            :key="date.dateStr"
                                            :class="[
                                                'booked-date-chip',
                                                { 'booked-date-chip--active': selectedDate === date.dateStr }
                                            ]"
                                            @click="jumpToBookedDate(date.dateStr)"
                                        >
                                            <text class="booked-date-chip__day">{{ date.dayText }}</text>
                                            <text class="booked-date-chip__week">{{ date.weekText }}</text>
                                        </view>
                                    </view>
                                </view>
                            </template>

                            <EmptyState
                                v-else-if="bookedYearLoaded"
                                title="本年暂无锁档"
                                description="当前年份没有正式锁档日期"
                            />
                        </scroll-view>
                    </BaseCard>
                </view>
            </view>
        </view>

        <BaseOverlayMask
            :show="showRemarkPopup"
            background="rgba(25, 23, 19, 0.42)"
            @close="closeRemarkEditor"
        />
        <tn-popup
            v-model="showRemarkPopup"
            open-direction="bottom"
            :overlay="false"
            :overlay-closeable="true"
            safe-area-inset-bottom
            :radius="24"
        >
            <BaseCard
                variant="panel"
                scene="staff"
                class="remark-popup"
                padding="32rpx 30rpx calc(32rpx + env(safe-area-inset-bottom))"
                border-radius="40rpx 40rpx 0 0"
                background="#FFFDF8"
                border="1rpx solid #D8C9AD"
                box-shadow="0 -18rpx 44rpx rgba(74, 43, 24, 0.16)"
            >
                <view class="remark-popup__handle" />
                <view class="remark-popup__head">
                    <text class="remark-popup__title">编辑备注</text>
                    <text class="remark-popup__date">{{ selectedDateLabel }}</text>
                </view>
                <textarea
                    v-model="remarkDraft"
                    class="remark-popup__textarea"
                    maxlength="255"
                    placeholder="填写当天备注"
                    :show-confirm-bar="false"
                    :auto-height="true"
                />
                <view class="remark-popup__foot">
                    <text class="remark-popup__count">{{ remarkDraft.length }}/255</text>
                    <view class="remark-popup__actions">
                        <BaseButton
                            label="取消"
                            variant="light"
                            size="sm"
                            height="72rpx"
                            @click="closeRemarkEditor"
                        />
                        <BaseButton
                            label="保存"
                            variant="dark"
                            size="sm"
                            height="72rpx"
                            :loading="submitting"
                            @click="submitRemark"
                        />
                    </view>
                </view>
            </BaseCard>
        </tn-popup>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseInfoRow from '@/components/base/BaseInfoRow.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'
import BaseScheduleCalendar from '@/components/base/BaseScheduleCalendar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import {
    staffCenterScheduleBookedYear,
    staffCenterScheduleMonth,
    staffCenterScheduleSetStatus
} from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { useThemeStore } from '@/stores/theme'
import { showError, showSuccess } from '@/utils/feedback'

type ActivePanel = 'calendar' | 'booked'
type DayIndicator = 'available' | 'unavailable' | 'booked' | 'locked' | 'reserved'
type BadgeModifier = 'primary' | 'success' | 'warning' | 'danger' | 'neutral' | 'info'
interface CalendarCell {
    day: number
    dateStr: string
    currentMonth: boolean
    isPast: boolean
    isToday: boolean
}

interface CalendarDay {
    day: string | number
    value: string
    label?: string
    state?: 'default' | 'selected' | 'booked' | 'busy' | 'disabled' | 'today'
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

interface BookedYearItem extends PendingServiceOrderItem {}

interface BookedMonthGroup {
    key: string
    label: string
    dates: Array<{
        dateStr: string
        dayText: string
        weekText: string
    }>
}

const $theme = useThemeStore()

const today = new Date()
const todayStr = formatDateStr(today)
const activePanel = ref<ActivePanel>('calendar')
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
const bookedYearList = ref<BookedYearItem[]>([])
const bookedYearTotal = ref(0)
const bookedYearLoading = ref(false)
const bookedYearLoaded = ref(false)
const bookedYearLoadedFor = ref<number | null>(null)
const bookedYearPageSize = 400
let bookedYearRequestId = 0

const weekLabels = ['日', '一', '二', '三', '四', '五', '六']
const panelTabs: Array<{ label: string; value: ActivePanel }> = [
    { label: '月历', value: 'calendar' },
    { label: '全年锁档', value: 'booked' }
]

const monthText = computed(() => String(month.value).padStart(2, '0'))
const heroEyebrow = computed(() => (activePanel.value === 'calendar' ? '当前月份' : '当前年份'))
const isPeriodLoading = computed(
    () => loadingMonth.value || (activePanel.value === 'booked' && bookedYearLoading.value)
)
const bookedDateList = computed(() => {
    const dateMap = new Map<string, string>()
    const yearPrefix = `${year.value}-`

    bookedYearList.value.forEach((item) => {
        const dateStr = String(item.service_date || '').trim()
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr) && dateStr.startsWith(yearPrefix)) {
            dateMap.set(dateStr, dateStr)
        }
    })

    return Array.from(dateMap.keys()).sort()
})

const isBookedYearReady = computed(
    () => bookedYearLoaded.value && bookedYearLoadedFor.value === year.value
)

const bookedYearMeta = computed(() => {
    if (bookedDateList.value.length > 0) {
        return `${bookedDateList.value.length} 天`
    }

    return bookedYearLoading.value && !isBookedYearReady.value ? '加载中' : '0 天'
})
const bookedYearBadgeKey = computed(() => `${year.value}-${bookedYearMeta.value}`)

const resolveScheduleError = (error: unknown, fallback = '操作失败') => {
    if (typeof error === 'string' && error.trim()) {
        return error
    }

    if (error && typeof error === 'object') {
        const value =
            (error as { msg?: unknown; message?: unknown }).msg ??
            (error as { message?: unknown }).message

        if (typeof value === 'string' && value.trim()) {
            return value
        }
    }

    return fallback
}

const bookedMonthGroups = computed<BookedMonthGroup[]>(() => {
    const groups = new Map<string, BookedMonthGroup>()
    bookedDateList.value.forEach((dateStr) => {
        const [, monthTextValue, dayTextValue] = dateStr.split('-')
        const monthKey = monthTextValue
        if (!groups.has(monthKey)) {
            groups.set(monthKey, {
                key: monthKey,
                label: `${Number(monthTextValue)} 月`,
                dates: []
            })
        }

        groups.get(monthKey)?.dates.push({
            dateStr,
            dayText: `${Number(dayTextValue)} 日`,
            weekText: getShortWeekDay(dateStr)
        })
    })

    return Array.from(groups.values())
})

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

function getShortWeekDay(dateStr: string): string {
    const date = new Date(dateStr.replace(/-/g, '/'))
    return ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][date.getDay()]
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

const getCalendarDayState = (
    cell: CalendarCell
): NonNullable<CalendarDay['state']> => {
    if (hasPendingOrder(cell.dateStr)) {
        return 'booked'
    }

    if (!cell.currentMonth || cell.isPast || getDayStatusForView(cell.dateStr) === 0) {
        return 'disabled'
    }

    const indicator = getDayIndicator(cell.dateStr)

    if (['booked', 'locked', 'reserved'].includes(indicator)) {
        return 'booked'
    }

    if (cell.isToday) {
        return 'today'
    }

    return 'default'
}

const getCalendarDayLabel = (cell: CalendarCell) => {
    if (!cell.currentMonth) return ''
    if (hasPendingOrder(cell.dateStr)) return '待履约'

    const status = getDayStatusForView(cell.dateStr)

    if (status === 1 && cell.isToday) return '今天'
    return getStatusLabel(status)
}

const calendarDays = computed<CalendarDay[]>(() =>
    calendarCells.value.map((cell) => ({
        day: cell.currentMonth ? (cell.isToday ? '今' : cell.day) : '',
        value: cell.dateStr,
        label: getCalendarDayLabel(cell),
        state: getCalendarDayState(cell)
    }))
)

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
        label: activePanel.value === 'calendar' ? '待履约' : '全年锁档',
        value:
            activePanel.value === 'calendar'
                ? monthSummary.value.pending_service_count
                : bookedDateList.value.length,
        accent: true
    },
    {
        label: activePanel.value === 'calendar' ? '已占用' : '当前月份',
        value: activePanel.value === 'calendar' ? monthSummary.value.occupied_days : monthText.value,
        accent: false
    },
    {
        label: activePanel.value === 'calendar' ? '可预约' : '年份',
        value: activePanel.value === 'calendar' ? monthSummary.value.available_days : year.value,
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

const selectedDayRemark = computed(() => {
    const remark = String(getScheduleDay(selectedDate.value)?.remark || '').trim()
    return remark || '暂无备注'
})

const isEditableDate = computed(() => !isSelectedPast.value && !isLockedStatus.value)
const isAvailableActionDisabled = computed(
    () => submitting.value || !isEditableDate.value || displayDayStatus.value === 1
)
const isUnavailableActionDisabled = computed(
    () => submitting.value || !isEditableDate.value || displayDayStatus.value === 0
)

function handleCalendarSelect(day: string | number) {
    const dateStr = String(day)
    const cell = calendarCells.value.find((item) => item.dateStr === dateStr)

    if (!cell?.currentMonth && !hasPendingOrder(dateStr)) return
    selectedDate.value = dateStr
}
function goOrderDetail(orderId: number) {
    uni.navigateTo({
        url: `/packages/pages/staff_order_detail/staff_order_detail?id=${orderId}`
    })
}

function resetBookedYearState() {
    bookedYearLoaded.value = false
    bookedYearLoadedFor.value = null
    bookedYearList.value = []
    bookedYearTotal.value = 0
}

async function reloadBookedYearList() {
    await loadBookedYearList(true)
}

function switchPanel(panel: ActivePanel) {
    if (activePanel.value === panel) return
    activePanel.value = panel

    if (panel === 'booked' && !isBookedYearReady.value) {
        reloadBookedYearList()
    }
}

async function changePeriod(delta: number) {
    if (activePanel.value === 'booked') {
        await changeYear(delta)
        return
    }

    await changeMonth(delta)
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
    const previousYear = year.value

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
    if (nextYear !== previousYear) {
        resetBookedYearState()
    }

    if (nextYear === today.getFullYear() && nextMonth === today.getMonth() + 1) {
        selectedDate.value = todayStr
    } else {
        selectedDate.value = `${nextYear}-${String(nextMonth).padStart(2, '0')}-01`
    }

    await fetchMonth()
    if (activePanel.value === 'booked') {
        reloadBookedYearList()
    }
}

async function changeYear(delta: number) {
    if (loadingMonth.value || bookedYearLoading.value) return

    const nextYear = year.value + delta
    year.value = nextYear
    resetBookedYearState()

    if (nextYear === today.getFullYear() && month.value === today.getMonth() + 1) {
        selectedDate.value = todayStr
    } else {
        selectedDate.value = `${nextYear}-${String(month.value).padStart(2, '0')}-01`
    }

    await fetchMonth()
    reloadBookedYearList()
}

async function jumpToBookedDate(dateStr: string) {
    const [targetYear, targetMonth] = dateStr.split('-')
    year.value = Number(targetYear)
    month.value = Number(targetMonth)
    selectedDate.value = dateStr
    activePanel.value = 'calendar'
    await fetchMonth()
}

async function setStatus(status: number, remark?: string): Promise<boolean> {
    if (submitting.value) return false
    if (status === 1 && isAvailableActionDisabled.value && remark === undefined) return false
    if (status === 0 && isUnavailableActionDisabled.value && remark === undefined) return false

    if (!isEditableDate.value) {
        showError('该日期不可调整')
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
        showSuccess('设置成功')
        return true
    } catch (error: any) {
        showError(resolveScheduleError(error, '设置失败'))
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
        showError(resolveScheduleError(error, '加载档期失败'))
    } finally {
        loadingMonth.value = false
    }
}

async function loadBookedYearList(reset = false) {
    if (bookedYearLoading.value) return

    const targetYear = year.value
    const requestId = ++bookedYearRequestId

    try {
        if (reset) {
            resetBookedYearState()
        }
        bookedYearLoading.value = true

        const rows: BookedYearItem[] = []
        let pageNo = 1
        let lastPage = 1
        let total = 0

        do {
            const response = await staffCenterScheduleBookedYear({
                year: targetYear,
                page_no: pageNo,
                page_size: bookedYearPageSize
            })
            if (requestId !== bookedYearRequestId || targetYear !== year.value) {
                return
            }

            const list = Array.isArray(response?.data) ? response.data : []
            const currentPage = Number(response?.current_page || pageNo)

            rows.push(...list)
            total = Number(response?.total || total)
            lastPage = Math.max(Number(response?.last_page || currentPage), currentPage)
            pageNo = currentPage + 1
        } while (pageNo <= lastPage)

        bookedYearTotal.value = total
        bookedYearList.value = rows
        bookedYearLoaded.value = true
        bookedYearLoadedFor.value = targetYear
    } catch (error: any) {
        if (requestId !== bookedYearRequestId || targetYear !== year.value) {
            return
        }

        const msg =
            resolveScheduleError(error, '加载全年锁档失败')
        showError(msg)
        bookedYearLoaded.value = true
        bookedYearLoadedFor.value = targetYear
    } finally {
        if (requestId === bookedYearRequestId) {
            bookedYearLoading.value = false
        }
    }
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    await fetchMonth()
    if (activePanel.value === 'booked') {
        reloadBookedYearList()
    }
})
</script>

<style lang="scss" scoped>
.staff-schedule-page {
    min-height: auto;
    padding: 18rpx 0 18rpx;
    background: linear-gradient(180deg, #fffdf8 0%, #f8f3e7 100%);

    &__content {
        display: block;
        padding-bottom: calc(28rpx + env(safe-area-inset-bottom));
    }
}

.schedule-card-wrap {
    display: block;
    width: 100%;
}

.schedule-card-wrap--spaced {
    margin-top: 22rpx;
}

.schedule-hero,
.schedule-section,
.selected-panel {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    border-radius: 36rpx;
}

.selected-panel {
    gap: 0;
}

.selected-panel__section {
    display: block;
    margin-top: 18rpx;
}

.schedule-hero {
    padding: 34rpx 30rpx;
}

.schedule-hero::before {
    opacity: 0.34;
}

.schedule-hero__head,
.schedule-section__head,
.selected-status,
.day-order-card__head,
.day-order-card__foot,
.remark-card__head,
.remark-popup__head,
.remark-popup__foot,
.remark-popup__actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
}

.schedule-hero__copy,
.schedule-section__copy,
.selected-status__copy,
.day-order-card__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.schedule-hero__eyebrow {
    font-size: 22rpx;
    font-weight: 900;
    line-height: 1.2;
    color: rgba(255, 253, 248, 0.62);
}

.schedule-hero__title {
    font-size: 42rpx;
    font-weight: 900;
    line-height: 1.22;
    color: #fffdf8;
}

.schedule-hero__actions {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.schedule-tabs {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10rpx;
    margin: 10rpx 0 12rpx;
    padding: 8rpx;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.12);
    border: 1rpx solid rgba(255, 253, 248, 0.16);
    position: relative;
    z-index: 2;
}

.schedule-tab {
    min-width: 0;
    min-height: 58rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: transparent;
}

.schedule-tab--active {
    background: #fffdf8;
    box-shadow: 0 12rpx 28rpx rgba(0, 0, 0, 0.18);
}

.schedule-tab__text {
    font-size: 23rpx;
    font-weight: 900;
    line-height: 1;
    color: rgba(255, 253, 248, 0.76);
}

.schedule-tab--active .schedule-tab__text {
    color: #191713;
}

.hero-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
}

.hero-metric {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    padding: 18rpx;
    border-radius: 26rpx;
    background: rgba(255, 253, 248, 0.12);
    border: 1rpx solid rgba(255, 253, 248, 0.18);
    box-sizing: border-box;
}

.hero-metric--accent {
    background: rgba(217, 190, 130, 0.2);
    border-color: rgba(217, 190, 130, 0.44);
}

.hero-metric__label {
    font-size: 22rpx;
    font-weight: 800;
    color: rgba(255, 253, 248, 0.64);
}

.hero-metric__value {
    font-size: 36rpx;
    font-weight: 900;
    line-height: 1.2;
    color: #fffdf8;
}

.schedule-section {
    padding: 28rpx 26rpx;
    background: rgba(255, 253, 248, 0.96);
    border-color: #d8c9ad;
    box-shadow: 0 18rpx 40rpx rgba(74, 43, 24, 0.08);
}

.schedule-section__title {
    font-size: 31rpx;
    font-weight: 900;
    line-height: 1.3;
    color: #191713;
}

.schedule-section__meta {
    font-size: 23rpx;
    font-weight: 800;
    line-height: 1.35;
    color: #8c806d;
}

.schedule-calendar__component {
    overflow: hidden;
    border-radius: 28rpx;
}

.schedule-calendar__component :deep(.base-card) {
    padding: 18rpx;
    border-radius: 28rpx;
    background: rgba(250, 246, 238, 0.78);
    border-color: rgba(216, 201, 173, 0.78);
    box-shadow: none;
}

.schedule-calendar__component :deep(.base-card__header) {
    display: none;
}

.schedule-calendar__component :deep(.base-schedule-calendar__week),
.schedule-calendar__component :deep(.base-schedule-calendar__grid) {
    gap: 8rpx;
}

.schedule-calendar__component :deep(.base-date-cell) {
    max-width: none;
    min-height: 92rpx;
    height: 92rpx;
    border-radius: 20rpx;
}

.schedule-calendar__component :deep(.base-date-cell__day) {
    font-size: 25rpx;
}

.schedule-calendar__component :deep(.base-date-cell__label) {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 17rpx;
}

.selected-status {
    padding: 14rpx 16rpx;
    border-radius: 24rpx;
    background: rgba(250, 246, 238, 0.78);
    border: 1rpx solid rgba(216, 201, 173, 0.78);
}

.selected-status__label {
    font-size: 21rpx;
    font-weight: 800;
    color: #8c806d;
}

.selected-status__title {
    font-size: 28rpx;
    font-weight: 900;
    line-height: 1.35;
    color: #191713;
}

.focus-badge {
    flex-shrink: 0;
    min-height: 38rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 14rpx;
    border-radius: 999rpx;
    border: 1rpx solid #d8c9ad;
    background: #fffdf8;
}

.focus-badge__text {
    font-size: 20rpx;
    font-weight: 900;
    line-height: 1;
    color: #665e52;
    white-space: nowrap;
}

.focus-badge--success {
    background: #e8efe6;
    border-color: #71806f;
}

.focus-badge--warning {
    background: #f1e5c8;
    border-color: #d9be82;
}

.focus-badge--danger {
    background: #f2ddd5;
    border-color: #9a6b35;
}

.focus-badge--primary {
    background: #191713;
    border-color: #d9be82;
}

.focus-badge--primary .focus-badge__text {
    color: #fffdf8;
}

.detail-list {
    display: flex;
    flex-direction: column;
    padding: 0 16rpx;
    border-radius: 22rpx;
    background: rgba(250, 246, 238, 0.78);
    border: 1rpx solid rgba(216, 201, 173, 0.78);
}

.detail-list :deep(.base-info-row) {
    min-height: 58rpx;
    gap: 14rpx;
}

.detail-list :deep(.base-info-row + .base-info-row) {
    border-top: 1rpx solid rgba(216, 201, 173, 0.58);
}

.detail-list :deep(.base-info-row__label) {
    font-size: 22rpx;
    font-weight: 800;
    color: #756b5c;
}

.detail-list :deep(.base-info-row__value) {
    font-size: 24rpx;
    font-weight: 900;
    color: #191713;
}

.day-order-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.day-order-card + .day-order-card {
    margin-top: 12rpx;
}

.day-order-card {
    display: flex;
    flex-direction: column;
    gap: 0;
    padding: 14rpx 16rpx;
    border-radius: 22rpx;
    border: 1rpx solid rgba(216, 201, 173, 0.78);
    background: rgba(255, 253, 248, 0.86);
    box-sizing: border-box;
}

.day-order-card__head {
    align-items: flex-start;
}

.day-order-card__title {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 27rpx;
    font-weight: 900;
    line-height: 1.35;
    color: #191713;
}

.day-order-card__meta,
.day-order-card__info,
.day-order-card__address {
    font-size: 21rpx;
    font-weight: 800;
    line-height: 1.5;
    color: #756b5c;
}

.day-order-card__meta {
    margin-top: 4rpx;
}

.day-order-card__address {
    margin-top: 8rpx;
    word-break: break-word;
}

.day-order-card__foot {
    margin-top: 8rpx;
    padding-top: 10rpx;
    border-top: 1rpx solid rgba(216, 201, 173, 0.58);
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10rpx;
}

.action-grid :deep(.base-button) {
    width: 100%;
}

.remark-card {
    padding: 10rpx 14rpx;
    border-radius: 22rpx;
    background: rgba(250, 246, 238, 0.78);
    border: 1rpx solid rgba(216, 201, 173, 0.78);
}

.remark-card__title {
    font-size: 24rpx;
    font-weight: 900;
    color: #191713;
}

.booked-year-panel {
    min-height: 620rpx;
}

.booked-year-list {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    max-height: 860rpx;
    overflow: hidden;
    margin-top: 18rpx;
}

.booked-month-group {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 18rpx;
    border-radius: 26rpx;
    border: 1rpx solid rgba(216, 201, 173, 0.78);
    background: rgba(255, 253, 248, 0.88);
    box-sizing: border-box;
}

.booked-month-group__title {
    font-size: 28rpx;
    font-weight: 900;
    line-height: 1.25;
    color: #191713;
}

.booked-date-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12rpx;
}

.booked-date-chip {
    min-width: 0;
    min-height: 92rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    padding: 12rpx 8rpx;
    border-radius: 22rpx;
    border: 1rpx solid rgba(216, 201, 173, 0.86);
    background: #fffdf8;
    box-sizing: border-box;
}

.booked-date-chip--active {
    border-color: #d9be82;
    background: #f1e5c8;
    box-shadow: 0 10rpx 24rpx rgba(154, 107, 53, 0.12);
}

.booked-date-chip__day {
    font-size: 26rpx;
    font-weight: 900;
    line-height: 1.15;
    color: #191713;
}

.booked-date-chip__week {
    font-size: 20rpx;
    font-weight: 800;
    line-height: 1;
    color: #8c806d;
}

.remark-popup {
    width: 100vw;
    box-sizing: border-box;
}

.remark-popup__handle {
    width: 72rpx;
    height: 8rpx;
    margin: 0 auto 24rpx;
    border-radius: 999rpx;
    background: rgba(140, 128, 109, 0.35);
}

.remark-popup__head {
    align-items: flex-end;
    margin-bottom: 22rpx;
}

.remark-popup__title {
    font-size: 32rpx;
    font-weight: 900;
    line-height: 1.3;
    color: #191713;
}

.remark-popup__date,
.remark-popup__count {
    font-size: 22rpx;
    font-weight: 800;
    line-height: 1.3;
    color: #8c806d;
}

.remark-popup__textarea {
    width: 100%;
    min-height: 220rpx;
    padding: 22rpx;
    border-radius: 26rpx;
    border: 1rpx solid #d8c9ad;
    background: #fffdf8;
    box-sizing: border-box;
    font-size: 26rpx;
    line-height: 1.6;
    color: #191713;
}

.remark-popup__foot {
    align-items: flex-end;
    margin-top: 18rpx;
}

.remark-popup__actions {
    flex-shrink: 0;
}

.remark-popup__actions :deep(.base-button) {
    min-width: 150rpx;
}

@media screen and (max-width: 375px) {
    .schedule-hero,
    .schedule-section {
        padding-left: 22rpx;
        padding-right: 22rpx;
    }

    .schedule-hero__head,
    .schedule-section__head,
    .selected-status,
    .day-order-card__foot,
    .remark-popup__foot {
        align-items: stretch;
        flex-direction: column;
    }

    .schedule-hero__actions,
    .remark-popup__actions {
        width: 100%;
    }

    .schedule-hero__actions :deep(.base-button),
    .remark-popup__actions :deep(.base-button) {
        flex: 1;
    }

    .hero-metrics,
    .action-grid {
        grid-template-columns: 1fr;
    }

    .booked-year-list {
        max-height: 760rpx;
    }

    .booked-date-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .schedule-calendar__component :deep(.base-card) {
        padding: 12rpx;
    }

    .schedule-calendar__component :deep(.base-date-cell) {
        min-height: 78rpx;
        height: 78rpx;
        border-radius: 18rpx;
    }

    .schedule-calendar__component :deep(.base-date-cell__day) {
        font-size: 23rpx;
    }

    .schedule-calendar__component :deep(.base-date-cell__label) {
        font-size: 15rpx;
    }
}
</style>
