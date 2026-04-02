<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" hasSafeBottom>
        <BaseNavbar title="档期管理" />

        <view class="staff-schedule-page">
            <view class="staff-schedule-page__content">
                <view class="schedule-hero-card">
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
                </view>

                <view class="staff-section-card">
                    <view class="section-head section-head--stack">
                        <view class="section-head__copy">
                            <text class="section-head__title">月历视图</text>
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
                                    'day-cell--today': cell.isToday,
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
                                            'is-clickable': cell.currentMonth && !cell.isPast,
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

                                    <view
                                        v-if="cell.currentMonth && !cell.isPast"
                                        class="day-cell__foot"
                                    >
                                        <view
                                            v-if="getDayIndicator(cell.dateStr) === 'available'"
                                            class="day-cell__dot day-cell__dot--available"
                                        />
                                        <text
                                            v-else
                                            :class="[
                                                'day-cell__status',
                                                `day-cell__status--${getDayIndicator(cell.dateStr)}`
                                            ]"
                                        >
                                            {{ getIndicatorLabel(getDayIndicator(cell.dateStr)) }}
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <view v-if="selectedDate" class="staff-section-card">
                    <view class="section-head">
                        <view class="section-head__copy">
                            <text class="section-head__title">{{ selectedDateLabel }}</text>
                            <text class="section-head__meta">{{ getWeekDay(selectedDate) }}</text>
                        </view>

                        <view :class="['status-pill', `status-pill--${selectedDayView.modifier}`]">
                            <text class="status-pill__text">{{ selectedDayView.text }}</text>
                        </view>
                    </view>

                    <view class="selected-day-card">
                        <view class="selected-day-card__row">
                            <view class="selected-day-card__copy">
                                <text class="selected-day-card__eyebrow">当前安排</text>
                                <text class="selected-day-card__title">
                                    {{ selectedDayView.title }}
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
                                <tn-icon name="check-circle" size="24" color="#2F7D58" />
                            </view>
                            <view class="schedule-action__copy">
                                <text class="schedule-action__title">设为可预约</text>
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
                                <tn-icon name="close-circle" size="24" color="#B44A3A" />
                            </view>
                            <view class="schedule-action__copy">
                                <text class="schedule-action__title">设为不可用</text>
                            </view>
                        </view>
                    </view>

                    <view class="remark-card">
                        <view class="remark-card__head">
                            <text class="remark-card__title">档期备注</text>
                            <text class="remark-card__action" @click="openRemarkEditor"
                                >编辑备注</text
                            >
                        </view>
                        <text class="remark-card__content">{{ selectedDayRemark }}</text>
                    </view>

                    <view class="focus-note">
                        <text class="focus-note__text">{{ selectedDayLimitText }}</text>
                    </view>
                </view>
            </view>
        </view>

        <tn-popup
            v-model="showRemarkPopup"
            open-direction="bottom"
            :overlay-closeable="true"
            safe-area-inset-bottom
            :radius="24"
        >
            <view class="remark-popup">
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
import { ensureStaffCenterAccess } from '@/utils/staff-center'
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

const $theme = useThemeStore()

const today = new Date()
const todayStr = formatDateStr(today)
const year = ref(today.getFullYear())
const month = ref(today.getMonth() + 1)
const selectedDate = ref(formatDateStr(today))
const schedules = ref<Record<string, any>>({})
const loadingMonth = ref(false)
const submitting = ref(false)
const showRemarkPopup = ref(false)
const remarkDraft = ref('')

const weekLabels = ['日', '一', '二', '三', '四', '五', '六']
const legendItems = [
    { label: '可预约', tone: 'available' },
    { label: '已安排', tone: 'booked' },
    { label: '已锁定', tone: 'locked' },
    { label: '内部预留', tone: 'reserved' },
    { label: '不可用', tone: 'unavailable' }
]

const monthText = computed(() => String(month.value).padStart(2, '0'))

const monthContext = computed(() => {
    const currentYear = today.getFullYear()
    const currentMonth = today.getMonth() + 1

    if (year.value === currentYear && month.value === currentMonth) {
        return {
            text: '本月工作台',
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
    const dayData = schedules.value[dateStr]
    if (!dayData) return -1
    return Number(dayData[0]?.status ?? -1)
}

function getDayIndicator(dateStr: string): DayIndicator {
    const status = getDayStatus(dateStr)
    if (status === 0) return 'unavailable'
    if (status === 2) return 'booked'
    if (status === 3) return 'locked'
    if (status === 4) return 'reserved'
    return 'available'
}

function getIndicatorLabel(indicator: DayIndicator): string {
    const map: Record<DayIndicator, string> = {
        available: '',
        unavailable: '休',
        booked: '约',
        locked: '锁',
        reserved: '留'
    }
    return map[indicator]
}

const stats = computed(() => {
    let available = 0
    let booked = 0
    let unavailable = 0

    const counted = new Set<string>()
    for (const dateStr of Object.keys(schedules.value)) {
        if (dateStr < todayStr) continue
        counted.add(dateStr)

        const status = getDayStatus(dateStr)
        if (status === 0) {
            unavailable++
            continue
        }

        if (status === 2 || status === 3 || status === 4) {
            booked++
            continue
        }

        available++
    }

    const daysInMonth = new Date(year.value, month.value, 0).getDate()
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year.value}-${String(month.value).padStart(2, '0')}-${String(
            day
        ).padStart(2, '0')}`
        if (dateStr < todayStr || dateStr === todayStr) continue
        if (counted.has(dateStr)) continue
        available++
    }

    return { available, booked, unavailable }
})

const heroMetrics = computed(() => [
    { label: '可预约', value: stats.value.available, accent: true },
    { label: '已安排', value: stats.value.booked, accent: false },
    { label: '不可用', value: stats.value.unavailable, accent: false }
])

const dayStatus = computed(() => getDayStatus(selectedDate.value))
const displayDayStatus = computed(() => (dayStatus.value === -1 ? 1 : dayStatus.value))
const isSelectedPast = computed(() => selectedDate.value < todayStr)
const isLockedStatus = computed(() => dayStatus.value >= 2)

function getStatusView(status: number): DayViewModel {
    const normalizedStatus = status === -1 ? 1 : status

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
            text: '已预约',
            badge: '已排满',
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

    return views[normalizedStatus] || views[1]
}

const selectedDayView = computed(() => getStatusView(displayDayStatus.value))

const selectedDayRemark = computed(() => {
    const remark = String(schedules.value[selectedDate.value]?.[0]?.remark || '').trim()
    return remark || '暂无备注'
})

const selectedDayLimitText = computed(() => {
    if (isSelectedPast.value) return '历史日期不可调整'
    if (isLockedStatus.value) return '业务占用，不可覆盖'
    return '仅修改当天状态'
})

const infoCards = computed(() => [
    {
        label: '当前状态',
        value: selectedDayView.value.badge,
        accent: displayDayStatus.value === 1 && !isSelectedPast.value
    },
    {
        label: '修改权限',
        value: selectedDayLimitText.value,
        accent: false
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
    if (!cell.currentMonth || cell.isPast) return
    selectedDate.value = cell.dateStr
}

function openRemarkEditor() {
    if (!selectedDate.value) return
    remarkDraft.value = String(schedules.value[selectedDate.value]?.[0]?.remark || '').trim()
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

async function setStatus(status: number, remark?: string) {
    if (submitting.value) return
    if (status === 1 && isAvailableActionDisabled.value && remark === undefined) return
    if (status === 0 && isUnavailableActionDisabled.value && remark === undefined) return

    if (!isEditableDate.value) {
        uni.showToast({ title: '该日期不可调整', icon: 'none' })
        return
    }

    try {
        submitting.value = true
        const nextRemark =
            remark !== undefined
                ? remark.trim()
                : String(schedules.value[selectedDate.value]?.[0]?.remark || '')
        await staffCenterScheduleSetStatus({
            date: selectedDate.value,
            status,
            remark: nextRemark
        })

        if (!schedules.value[selectedDate.value]) {
            schedules.value[selectedDate.value] = {}
        }
        schedules.value[selectedDate.value][0] = {
            ...schedules.value[selectedDate.value][0],
            status,
            remark: nextRemark
        }
        uni.showToast({ title: '设置成功', icon: 'success' })
    } catch (error: any) {
        const msg = typeof error === 'string' ? error : error?.msg || error?.message || '设置失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        submitting.value = false
    }
}

async function submitRemark() {
    if (!selectedDate.value) return
    await setStatus(displayDayStatus.value, remarkDraft.value)
    if (!submitting.value) {
        showRemarkPopup.value = false
    }
}

async function fetchMonth() {
    if (loadingMonth.value) return

    try {
        loadingMonth.value = true
        const response = await staffCenterScheduleMonth({ year: year.value, month: month.value })
        schedules.value = response?.schedules || {}
    } catch (error: any) {
        schedules.value = {}
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
    padding: 20rpx 20rpx 40rpx;
    background: radial-gradient(
            circle at top left,
            rgba(255, 241, 238, 0.92) 0,
            rgba(255, 241, 238, 0) 42%
        ),
        radial-gradient(
            circle at top right,
            rgba(253, 232, 225, 0.72) 0,
            rgba(253, 232, 225, 0) 34%
        ),
        linear-gradient(180deg, #fcfbf9 0%, #f9f4f0 100%);

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
    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);
    background: linear-gradient(135deg, #fff6f2 0%, #fde8e1 100%);
    box-shadow: 0 20rpx 44rpx rgba(192, 130, 115, 0.16);

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
        color: var(--wm-text-primary, #1e2432);
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
        background: #fff1ee;

        .hero-pill__text {
            color: var(--wm-color-primary, #e85a4f);
        }
    }

    &--success,
    &--warning,
    &--danger {
        border: 1rpx solid var(--wm-color-border, #efe6e1);
        background: rgba(255, 255, 255, 0.84);
    }

    &--success .hero-pill__text {
        color: #2f7d58;
    }

    &--warning .hero-pill__text {
        color: #c99b73;
    }

    &--danger .hero-pill__text {
        color: #b44a3a;
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
        border: 1rpx solid rgba(244, 199, 191, 0.92);
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
        color: var(--wm-color-primary, #e85a4f);
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
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.78);
    box-sizing: border-box;

    &--accent {
        background: #fff1ee;
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }

    &__label {
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &--accent .hero-metric__label {
        color: var(--wm-color-primary, #e85a4f);
    }

    &__value {
        font-size: 34rpx;
        font-weight: 700;
        line-height: 1.2;
        color: var(--wm-text-primary, #1e2432);
    }
}

.staff-section-card {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 22rpx 24rpx;
    border-radius: 30rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.92);
    box-shadow: 0 18rpx 36rpx rgba(214, 185, 167, 0.2);
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

    &__title {
        font-size: 32rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #1e2432);
    }

    &__desc {
        font-size: 22rpx;
        font-weight: 600;
        line-height: 1.5;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__meta {
        font-size: 22rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-secondary, #7f7b78);
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
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);

    &__dot {
        width: 10rpx;
        height: 10rpx;
        border-radius: 999rpx;

        &--available {
            background: #2f7d58;
        }

        &--booked {
            background: #d19339;
        }

        &--locked {
            background: #607086;
        }

        &--reserved {
            background: var(--wm-color-primary, #e85a4f);
        }

        &--unavailable {
            background: #b44a3a;
        }
    }

    &__text {
        font-size: 18rpx;
        font-weight: 700;
        line-height: 1;
        color: var(--wm-text-secondary, #7f7b78);
    }
}

.calendar-shell {
    padding: 14rpx;
    border-radius: 24rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
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
        color: var(--wm-text-secondary, #7f7b78);
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
            border-color: rgba(232, 90, 79, 0.4);
            background: #fff1ee;
            box-shadow: 0 10rpx 22rpx rgba(232, 90, 79, 0.1);
        }

        &--booked {
            background: #fff8ed;
        }

        &--locked {
            background: rgba(96, 112, 134, 0.08);
        }

        &--reserved {
            background: rgba(232, 90, 79, 0.08);
        }

        &--unavailable {
            background: rgba(180, 74, 58, 0.08);
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
        color: var(--wm-text-primary, #1e2432);
    }

    &__tag {
        flex-shrink: 0;
        padding: 4rpx 8rpx;
        border-radius: 999rpx;
        background: #fff1ee;
        font-size: 16rpx;
        font-weight: 700;
        line-height: 1;
        color: var(--wm-color-primary, #e85a4f);
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
            background: #2f7d58;
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
            background: #fff2dd;
            color: #c99b73;
        }

        &--locked {
            background: rgba(96, 112, 134, 0.14);
            color: #607086;
        }

        &--reserved {
            background: #fff1ee;
            color: var(--wm-color-primary, #e85a4f);
        }

        &--unavailable {
            background: rgba(180, 74, 58, 0.14);
            color: #b44a3a;
        }
    }

    &--other {
        .day-cell__inner {
            background: transparent;
        }

        .day-cell__num {
            color: #cfc5be;
        }
    }

    &--past {
        .day-cell__num {
            color: #b4aca8;
        }

        .day-cell__inner {
            opacity: 0.78;
        }
    }

    &--today .day-cell__num {
        color: var(--wm-color-primary, #e85a4f);
    }
}

.selected-day-card {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    border: 1rpx solid var(--wm-color-border-strong, #f4c7bf);
    background: linear-gradient(135deg, #fff6f2 0%, #fff1ee 100%);

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
        color: var(--wm-color-primary, #e85a4f);
    }

    &__title {
        font-size: 28rpx;
        font-weight: 700;
        line-height: 1.35;
        color: var(--wm-text-primary, #1e2432);
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
        background: #fff1ee;

        .focus-badge__text,
        .status-pill__text {
            color: var(--wm-color-primary, #e85a4f);
        }
    }

    &--success {
        background: rgba(47, 125, 88, 0.12);

        .focus-badge__text,
        .status-pill__text {
            color: #2f7d58;
        }
    }

    &--warning {
        background: #fff8ed;

        .focus-badge__text,
        .status-pill__text {
            color: #c99b73;
        }
    }

    &--danger {
        background: rgba(180, 74, 58, 0.12);

        .focus-badge__text,
        .status-pill__text {
            color: #b44a3a;
        }
    }

    &--neutral {
        background: rgba(96, 112, 134, 0.12);

        .focus-badge__text,
        .status-pill__text {
            color: #607086;
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
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: #fcfbf9;
    box-sizing: border-box;

    &--accent {
        background: #fff1ee;
        border-color: var(--wm-color-border-strong, #f4c7bf);
    }

    &__label {
        font-size: 20rpx;
        font-weight: 700;
        line-height: 1.2;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__value {
        font-size: 24rpx;
        font-weight: 700;
        line-height: 1.4;
        color: var(--wm-text-primary, #1e2432);
    }
}

.schedule-action {
    display: flex;
    align-items: center;
    gap: 12rpx;
    min-height: 104rpx;
    padding: 16rpx 18rpx;
    border-radius: 22rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: #fcfbf9;
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
            background: rgba(47, 125, 88, 0.12);
        }

        &--danger {
            background: rgba(180, 74, 58, 0.12);
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
        color: var(--wm-text-primary, #1e2432);
    }

    &--available.is-active {
        background: rgba(47, 125, 88, 0.1);
        border-color: rgba(47, 125, 88, 0.2);
    }

    &--danger.is-active {
        background: rgba(180, 74, 58, 0.1);
        border-color: rgba(180, 74, 58, 0.2);
    }

    &.is-disabled {
        opacity: 0.48;
    }
}

.focus-note {
    padding: 14rpx 16rpx;
    border-radius: 20rpx;
    background: #fcfbf9;
    border: 1rpx solid var(--wm-color-border, #efe6e1);

    &__text {
        font-size: 20rpx;
        font-weight: 600;
        line-height: 1.5;
        color: var(--wm-text-secondary, #7f7b78);
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
