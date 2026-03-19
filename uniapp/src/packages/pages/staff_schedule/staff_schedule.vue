<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="档期管理"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <view class="hero-card">
            <view
                class="hero-bg"
                :style="{
                    background: `linear-gradient(145deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 78%)`
                }"
            />

            <view class="hero-top">
                <view>
                    <text class="hero-title">{{ year }} 年 {{ monthText }} 月档期</text>
                    <text class="hero-desc">统一按全天管理，可快速切换可预约与不可用状态</text>
                </view>
                <view class="hero-nav">
                    <view class="hero-nav-btn" @click="changeMonth(-1)">
                        <text>‹</text>
                    </view>
                    <view class="hero-nav-btn" @click="changeMonth(1)">
                        <text>›</text>
                    </view>
                </view>
            </view>

            <view class="hero-stats">
                <view class="hero-stat">
                    <text class="hero-stat-label">可预约</text>
                    <text class="hero-stat-value">{{ stats.available }}</text>
                </view>
                <view class="hero-stat">
                    <text class="hero-stat-label">已安排</text>
                    <text class="hero-stat-value">{{ stats.booked }}</text>
                </view>
                <view class="hero-stat">
                    <text class="hero-stat-label">不可用</text>
                    <text class="hero-stat-value">{{ stats.unavailable }}</text>
                </view>
            </view>
        </view>

        <view class="section-card">
            <view class="section-head section-head--legend">
                <view>
                    <text class="section-title">月历视图</text>
                    <text class="section-subtitle">点选日期后，可在下方直接变更当日状态</text>
                </view>
                <view class="legend-row">
                    <view class="legend-item">
                        <view class="legend-dot legend-dot--available" />
                        <text>可预约</text>
                    </view>
                    <view class="legend-item">
                        <view class="legend-dot legend-dot--booked" />
                        <text>已安排</text>
                    </view>
                    <view class="legend-item">
                        <view class="legend-dot legend-dot--unavailable" />
                        <text>不可用</text>
                    </view>
                </view>
            </view>

            <view class="calendar-card">
                <view class="week-header">
                    <text v-for="w in weekLabels" :key="w" class="week-cell">{{ w }}</text>
                </view>
                <view class="calendar-grid">
                    <view
                        v-for="(cell, idx) in calendarCells"
                        :key="idx"
                        class="day-cell"
                        :class="{
                            'other-month': !cell.currentMonth,
                            'is-past': cell.isPast,
                            'is-selected': cell.dateStr === selectedDate
                        }"
                        :style="
                            cell.dateStr === selectedDate
                                ? { background: `${$theme.primaryColor}18`, borderColor: $theme.primaryColor }
                                : {}
                        "
                        @click="selectDate(cell)"
                    >
                        <text
                            class="day-num"
                            :style="cell.isToday ? { color: $theme.primaryColor, fontWeight: 700 } : {}"
                        >
                            {{ cell.isToday ? '今' : cell.day }}
                        </text>
                        <view v-if="cell.currentMonth && !cell.isPast" class="day-dot-wrap">
                            <view
                                v-if="getDayIndicator(cell.dateStr) === 'booked'"
                                class="day-dot day-dot--booked"
                            />
                            <view
                                v-else-if="getDayIndicator(cell.dateStr) === 'available'"
                                class="day-dot day-dot--available"
                            />
                            <view
                                v-else-if="getDayIndicator(cell.dateStr) === 'unavailable'"
                                class="day-dot day-dot--unavailable"
                            />
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view v-if="selectedDate" class="section-card">
            <view class="section-head">
                <view>
                    <text class="section-title">{{ selectedDateLabel }}</text>
                    <text class="section-subtitle">{{ getWeekDay(selectedDate) }}</text>
                </view>
                <view class="status-badge" :style="getStatusBadgeStyle(displayDayStatus)">
                    {{ getDisplayStatusText(displayDayStatus) }}
                </view>
            </view>

            <view class="focus-panel">
                <view class="focus-tips">
                    <view class="focus-tip">
                        <text class="focus-tip-label">当前说明</text>
                        <text class="focus-tip-value">{{ getDisplayStatusDesc(displayDayStatus) }}</text>
                    </view>
                    <view class="focus-tip">
                        <text class="focus-tip-label">编辑限制</text>
                        <text class="focus-tip-value">
                            {{ dayStatus >= 2 ? '已安排档期不可直接修改' : '当前日期支持直接调整' }}
                        </text>
                    </view>
                </view>

                <view class="action-row">
                    <view
                        class="action-btn action-btn--primary"
                        :style="{
                            background: `${$theme.primaryColor}14`,
                            borderColor: `${$theme.primaryColor}28`,
                            color: $theme.primaryColor,
                            opacity: dayStatus >= 2 || dayStatus === 1 ? 0.55 : 1
                        }"
                        @click="setStatus(1)"
                    >
                        <tn-icon name="check-circle" size="28" :color="$theme.primaryColor" />
                        <text>设为可预约</text>
                    </view>
                    <view
                        class="action-btn action-btn--danger"
                        :style="{ opacity: dayStatus >= 2 || dayStatus === 0 ? 0.55 : 1 }"
                        @click="setStatus(0)"
                    >
                        <tn-icon name="close-circle" size="28" color="#EF4444" />
                        <text>设为不可用</text>
                    </view>
                </view>

                <view class="focus-note">
                    已预约、已锁定、内部预留的日期由业务流程占用，不能在这里直接覆盖。
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterScheduleMonth, staffCenterScheduleSetStatus } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const today = new Date()
const year = ref(today.getFullYear())
const month = ref(today.getMonth() + 1)
const selectedDate = ref(formatDateStr(today))
const schedules = ref<Record<string, any>>({})

const weekLabels = ['日', '一', '二', '三', '四', '五', '六']
const monthText = computed(() => String(month.value).padStart(2, '0'))

function formatDateStr(d: Date): string {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
}

const selectedDateLabel = computed(() => {
    if (!selectedDate.value) return ''
    const parts = selectedDate.value.split('-')
    return `${Number(parts[1])} 月 ${Number(parts[2])} 日`
})

function getWeekDay(dateStr: string): string {
    const d = new Date(dateStr.replace(/-/g, '/'))
    return ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'][d.getDay()]
}

const calendarCells = computed(() => {
    const cells: Array<{
        day: number
        dateStr: string
        currentMonth: boolean
        isPast: boolean
        isToday: boolean
    }> = []

    const firstDay = new Date(year.value, month.value - 1, 1)
    const lastDay = new Date(year.value, month.value, 0)
    const startWeekDay = firstDay.getDay()
    const daysInMonth = lastDay.getDate()

    const todayStr = formatDateStr(today)

    const prevMonthLastDay = new Date(year.value, month.value - 1, 0).getDate()
    for (let i = startWeekDay - 1; i >= 0; i--) {
        const d = prevMonthLastDay - i
        const prevMonth = month.value - 1
        const prevYear = prevMonth <= 0 ? year.value - 1 : year.value
        const pm = prevMonth <= 0 ? 12 : prevMonth
        const dateStr = `${prevYear}-${String(pm).padStart(2, '0')}-${String(d).padStart(2, '0')}`
        cells.push({ day: d, dateStr, currentMonth: false, isPast: true, isToday: false })
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year.value}-${String(month.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`
        const isPast = dateStr < todayStr
        const isToday = dateStr === todayStr
        cells.push({ day: d, dateStr, currentMonth: true, isPast, isToday })
    }

    const remaining = 42 - cells.length
    for (let d = 1; d <= remaining; d++) {
        const nextMonth = month.value + 1
        const nextYear = nextMonth > 12 ? year.value + 1 : year.value
        const nm = nextMonth > 12 ? 1 : nextMonth
        const dateStr = `${nextYear}-${String(nm).padStart(2, '0')}-${String(d).padStart(2, '0')}`
        cells.push({ day: d, dateStr, currentMonth: false, isPast: false, isToday: false })
    }

    return cells
})

function analyzeDayStatus(dateStr: string): string {
    const dayData = schedules.value[dateStr]
    if (!dayData || Object.keys(dayData).length === 0) return 'default'

    const status = Number(dayData[0]?.status ?? -1)
    if (status === 2 || status === 3 || status === 4) return 'booked'
    if (status === 0) return 'unavailable'
    if (status === 1) return 'available'
    return 'default'
}

const stats = computed(() => {
    let available = 0
    let booked = 0
    let unavailable = 0
    const todayStr = formatDateStr(today)

    const counted = new Set<string>()
    for (const dateStr of Object.keys(schedules.value)) {
        if (dateStr < todayStr) continue
        counted.add(dateStr)
        const status = analyzeDayStatus(dateStr)
        if (status === 'booked') booked++
        else if (status === 'available' || status === 'default') available++
        else if (status === 'unavailable') unavailable++
    }

    const lastDay = new Date(year.value, month.value, 0).getDate()
    for (let d = 1; d <= lastDay; d++) {
        const dateStr = `${year.value}-${String(month.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`
        if (dateStr < todayStr || dateStr === todayStr) continue
        if (counted.has(dateStr)) continue
        available++
    }

    return { available, booked, unavailable }
})

function getDayIndicator(dateStr: string): string {
    const status = analyzeDayStatus(dateStr)
    if (status === 'booked') return 'booked'
    if (status === 'available' || status === 'default') return 'available'
    if (status === 'unavailable') return 'unavailable'
    return ''
}

function getDayStatus(dateStr: string): number {
    const dayData = schedules.value[dateStr]
    if (!dayData) return -1
    return Number(dayData[0]?.status ?? -1)
}

const dayStatus = computed(() => getDayStatus(selectedDate.value))

const displayDayStatus = computed(() => {
    if (dayStatus.value === -1) return 1
    return dayStatus.value
})

function getSlotStatus(): number {
    const dayData = schedules.value[selectedDate.value]
    if (!dayData) return -1
    return Number(dayData[0]?.status ?? -1)
}

function getDisplayStatusText(status: number): string {
    const map: Record<number, string> = {
        0: '当前不可用',
        1: '当前可预约',
        2: '已预约',
        3: '已锁定',
        4: '内部预留'
    }
    return map[status] || '默认可预约'
}

function getDisplayStatusDesc(status: number): string {
    const map: Record<number, string> = {
        0: '当天已主动关闭预约，可在有空时重新开放。',
        1: '当天处于开放状态，客户可正常预约。',
        2: '当天已有订单占用，请按订单流程处理。',
        3: '当天存在锁定记录，需等待业务流程释放。',
        4: '当天已被内部预留，不对外开放。'
    }
    return map[status] || '若没有单独记录，系统默认按可预约处理。'
}

function getStatusBadgeStyle(status: number): Record<string, string> {
    const styles: Record<number, Record<string, string>> = {
        0: { background: 'rgba(239,68,68,0.12)', color: '#DC2626' },
        1: { background: 'rgba(16,185,129,0.12)', color: '#059669' },
        2: { background: 'rgba(245,158,11,0.12)', color: '#D97706' },
        3: { background: 'rgba(59,130,246,0.12)', color: '#2563EB' },
        4: { background: 'rgba(124,58,237,0.12)', color: '#7C3AED' }
    }
    return styles[status] || styles[1]
}

function selectDate(cell: { dateStr: string; currentMonth: boolean; isPast: boolean }) {
    if (!cell.currentMonth || cell.isPast) return
    selectedDate.value = cell.dateStr
}

function changeMonth(delta: number) {
    let m = month.value + delta
    let y = year.value
    if (m > 12) {
        m = 1
        y++
    }
    if (m < 1) {
        m = 12
        y--
    }
    year.value = y
    month.value = m

    const todayStr = formatDateStr(today)
    const firstOfMonth = `${y}-${String(m).padStart(2, '0')}-01`
    if (y === today.getFullYear() && m === today.getMonth() + 1) {
        selectedDate.value = todayStr
    } else {
        selectedDate.value = firstOfMonth
    }
    fetchMonth()
}

async function setStatus(status: number) {
    const current = getSlotStatus()
    if (current >= 2) {
        uni.showToast({ title: '该日期不可调整', icon: 'none' })
        return
    }
    if (current === status) return

    try {
        await staffCenterScheduleSetStatus({
            date: selectedDate.value,
            status
        })
        if (!schedules.value[selectedDate.value]) {
            schedules.value[selectedDate.value] = {}
        }
        schedules.value[selectedDate.value][0] = { status }
        uni.showToast({ title: '设置成功', icon: 'success' })
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '设置失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

async function fetchMonth() {
    try {
        const res = await staffCenterScheduleMonth({ year: year.value, month: month.value })
        schedules.value = res?.schedules || {}
    } catch {
        schedules.value = {}
    }
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    await fetchMonth()
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    padding: 24rpx 24rpx 56rpx;
    background:
        radial-gradient(circle at top left, rgba(191, 219, 254, 0.72) 0, rgba(246, 248, 252, 0) 36%),
        linear-gradient(180deg, #F6F8FC 0%, #F4F6FB 100%);
}

.hero-card {
    position: relative;
    padding: 28rpx;
    border-radius: 30rpx;
    overflow: hidden;
    box-shadow: 0 18rpx 36rpx rgba(37, 99, 235, 0.18);
}

.hero-bg {
    position: absolute;
    inset: 0;
}

.hero-top,
.hero-stats {
    position: relative;
    z-index: 1;
}

.hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.hero-title {
    display: block;
    font-size: 36rpx;
    font-weight: 700;
    color: #FFFFFF;
}

.hero-desc {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.8);
}

.hero-nav {
    display: flex;
    gap: 10rpx;
}

.hero-nav-btn {
    width: 68rpx;
    height: 68rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40rpx;
    color: #FFFFFF;
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
    margin-top: 26rpx;
}

.hero-stat {
    padding: 20rpx;
    border-radius: 22rpx;
    background: rgba(255, 255, 255, 0.14);
    backdrop-filter: blur(12rpx);
}

.hero-stat-label {
    display: block;
    font-size: 22rpx;
    color: rgba(255, 255, 255, 0.75);
}

.hero-stat-value {
    display: block;
    margin-top: 12rpx;
    font-size: 38rpx;
    font-weight: 800;
    color: #FFFFFF;
}

.section-card {
    margin-top: 22rpx;
    padding: 28rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid rgba(255, 255, 255, 0.72);
    box-shadow: 0 18rpx 30rpx rgba(15, 23, 42, 0.05);
}

.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
}

.section-head--legend {
    align-items: flex-start;
}

.section-title {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    color: #0F172A;
}

.section-subtitle {
    display: block;
    margin-top: 8rpx;
    font-size: 22rpx;
    color: #94A3B8;
}

.legend-row {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 18rpx;
}

.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    font-size: 22rpx;
    color: #64748B;
}

.legend-dot {
    width: 14rpx;
    height: 14rpx;
    border-radius: 50%;
}

.legend-dot--available,
.day-dot--available {
    background: #10B981;
}

.legend-dot--booked,
.day-dot--booked {
    background: #F59E0B;
}

.legend-dot--unavailable,
.day-dot--unavailable {
    background: #EF4444;
}

.calendar-card {
    margin-top: 22rpx;
    padding: 20rpx;
    border-radius: 26rpx;
    background: linear-gradient(180deg, #F8FAFC 0%, #FFFFFF 100%);
    border: 2rpx solid #E2E8F0;
}

.week-header,
.calendar-grid {
    display: flex;
    flex-wrap: wrap;
}

.week-cell,
.day-cell {
    width: calc(100% / 7);
}

.week-cell {
    padding: 14rpx 0 20rpx;
    text-align: center;
    font-size: 24rpx;
    font-weight: 600;
    color: #64748B;
}

.day-cell {
    min-height: 112rpx;
    padding: 16rpx 0 12rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    border-radius: 20rpx;
    border: 2rpx solid transparent;
}

.day-cell.other-month .day-num {
    color: #CBD5E1;
}

.day-cell.is-past .day-num {
    color: #94A3B8;
    text-decoration: line-through;
}

.day-num {
    font-size: 28rpx;
    color: #0F172A;
}

.day-dot-wrap {
    margin-top: 8rpx;
    min-height: 12rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.day-dot {
    width: 12rpx;
    height: 12rpx;
    border-radius: 50%;
}

.status-badge {
    flex-shrink: 0;
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.focus-panel {
    margin-top: 22rpx;
}

.focus-tips {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
}

.focus-tip {
    padding: 20rpx;
    border-radius: 22rpx;
    background: linear-gradient(180deg, #F8FAFC 0%, #FFFFFF 100%);
    border: 2rpx solid #E2E8F0;
}

.focus-tip-label {
    display: block;
    font-size: 22rpx;
    color: #94A3B8;
}

.focus-tip-value {
    display: block;
    margin-top: 12rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: #0F172A;
}

.action-row {
    display: flex;
    gap: 16rpx;
    margin-top: 20rpx;
}

.action-btn {
    flex: 1;
    height: 76rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    font-size: 26rpx;
    font-weight: 600;
    border: 2rpx solid;
}

.action-btn--primary {
    background: rgba(37, 99, 235, 0.08);
}

.action-btn--danger {
    background: rgba(239, 68, 68, 0.06);
    border-color: rgba(239, 68, 68, 0.22);
    color: #EF4444;
}

.focus-note {
    margin-top: 18rpx;
    font-size: 22rpx;
    line-height: 1.7;
    color: #64748B;
}
</style>
