<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="档期管理"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <!-- 月份导航 -->
        <view class="month-nav-card">
            <view class="month-nav">
                <view class="nav-arrow" :style="{ color: $theme.primaryColor }" @click="changeMonth(-1)">
                    <text class="nav-arrow-text">‹</text>
                </view>
                <text class="month-title">{{ year }}年{{ monthText }}月</text>
                <view class="nav-arrow" :style="{ color: $theme.primaryColor }" @click="changeMonth(1)">
                    <text class="nav-arrow-text">›</text>
                </view>
            </view>
            <view class="month-stats">
                <view class="stat-item">
                    <view class="stat-dot dot-available" />
                    <text class="stat-text">可预约 {{ stats.available }}</text>
                </view>
                <view class="stat-item">
                    <view class="stat-dot dot-booked" />
                    <text class="stat-text">已预约 {{ stats.booked }}</text>
                </view>
                <view class="stat-item">
                    <view class="stat-dot dot-unavailable" />
                    <text class="stat-text">不可用 {{ stats.unavailable }}</text>
                </view>
            </view>
        </view>

        <!-- 日历网格 -->
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
                        'is-today': cell.isToday,
                        'is-selected': cell.dateStr === selectedDate
                    }"
                    :style="
                        cell.dateStr === selectedDate
                            ? { background: `${$theme.primaryColor}20`, borderColor: $theme.primaryColor }
                            : {}
                    "
                    @click="selectDate(cell)"
                >
                    <text
                        class="day-num"
                        :style="
                            cell.isToday
                                ? { color: $theme.primaryColor, fontWeight: 700 }
                                : cell.dateStr === selectedDate
                                  ? { color: $theme.primaryColor, fontWeight: 600 }
                                  : {}
                        "
                    >
                        {{ cell.isToday ? '今' : cell.day }}
                    </text>
                    <view v-if="cell.currentMonth && !cell.isPast" class="day-dots">
                        <view
                            v-if="getDayIndicator(cell.dateStr) === 'booked'"
                            class="indicator-dot dot-booked"
                        />
                        <view
                            v-else-if="getDayIndicator(cell.dateStr) === 'available'"
                            class="indicator-dot dot-available"
                        />
                        <view
                            v-else-if="getDayIndicator(cell.dateStr) === 'unavailable'"
                            class="indicator-dot dot-unavailable"
                        />
                    </view>
                </view>
            </view>
        </view>

        <!-- 日期详情 -->
        <view v-if="selectedDate" class="detail-card">
            <view class="detail-header">
                <view class="detail-date-info">
                    <text class="detail-date">{{ selectedDateLabel }}</text>
                    <text class="detail-week">{{ getWeekDay(selectedDate) }}</text>
                </view>
            </view>

            <!-- 快捷操作 -->
            <view class="quick-actions">
                <view
                    class="quick-btn"
                    :style="{
                        background: `${$theme.primaryColor}10`,
                        color: $theme.primaryColor,
                        borderColor: `${$theme.primaryColor}40`
                    }"
                    @click="setStatus(1)"
                >
                    <tn-icon name="check-circle" size="28" :color="$theme.primaryColor" />
                    <text>设为可预约</text>
                </view>
                <view class="quick-btn quick-btn-off" @click="setStatus(0)">
                    <tn-icon name="close-circle" size="28" color="#EF4444" />
                    <text>设为不可用</text>
                </view>
            </view>

            <view class="slot-list">
                <view class="slot-row">
                    <view class="slot-left">
                        <text class="slot-name">当日档期</text>
                        <text class="slot-time">当前系统已统一按全天管理</text>
                    </view>
                    <view class="slot-right">
                        <view
                            v-if="dayStatus >= 2"
                            class="status-tag"
                            :style="getStatusTagStyle(dayStatus)"
                        >
                            {{ getStatusLabel(dayStatus) }}
                        </view>
                        <view v-else class="toggle-group">
                            <view
                                class="toggle-btn"
                                :class="{ active: dayStatus === 1 }"
                                :style="
                                    dayStatus === 1
                                        ? { background: $theme.primaryColor, color: '#FFF' }
                                        : {}
                                "
                                @click="setStatus(1)"
                            >
                                可预约
                            </view>
                            <view
                                class="toggle-btn"
                                :class="{ active: dayStatus === 0 }"
                                :style="
                                    dayStatus === 0
                                        ? { background: '#EF4444', color: '#FFF' }
                                        : {}
                                "
                                @click="setStatus(0)"
                            >
                                不可用
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 提示 -->
        <view class="tip-row">
            <tn-icon name="info-circle" size="26" color="#9CA3AF" />
            <text class="tip-text">已预约/已锁定/内部预留的档期不可调整</text>
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

// 格式化日期为 YYYY-MM-DD
function formatDateStr(d: Date): string {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
}

// 选中日期的显示文本
const selectedDateLabel = computed(() => {
    if (!selectedDate.value) return ''
    const parts = selectedDate.value.split('-')
    return `${Number(parts[1])}月${Number(parts[2])}日`
})

// 获取星期
function getWeekDay(dateStr: string): string {
    const d = new Date(dateStr.replace(/-/g, '/'))
    return ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'][d.getDay()]
}

// 生成日历格子
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

    // 上月填充
    const prevMonthLastDay = new Date(year.value, month.value - 1, 0).getDate()
    for (let i = startWeekDay - 1; i >= 0; i--) {
        const d = prevMonthLastDay - i
        const prevMonth = month.value - 1
        const prevYear = prevMonth <= 0 ? year.value - 1 : year.value
        const pm = prevMonth <= 0 ? 12 : prevMonth
        const dateStr = `${prevYear}-${String(pm).padStart(2, '0')}-${String(d).padStart(2, '0')}`
        cells.push({ day: d, dateStr, currentMonth: false, isPast: true, isToday: false })
    }

    // 当月
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year.value}-${String(month.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`
        const isPast = dateStr < todayStr
        const isToday = dateStr === todayStr
        cells.push({ day: d, dateStr, currentMonth: true, isPast, isToday })
    }

    // 下月填充（补满6行）
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

// 分析某日期的综合状态
// 返回 'booked' | 'available' | 'unavailable' | 'default'
// 规则：
//   - 无记录 → 默认可预约（'default'，日历显示绿点）
//   - 全天(slot=0)不可用 → 整天不可用，无论分场次状态
//   - 有已预约/已锁定/内部预留 → 'booked'
//   - 有可预约且全天未设为不可用 → 'available'
//   - 全部不可用 → 'unavailable'
function analyzeDayStatus(dateStr: string): string {
    const dayData = schedules.value[dateStr]
    if (!dayData || Object.keys(dayData).length === 0) return 'default'

    const status = Number(dayData[0]?.status ?? -1)
    if (status === 2 || status === 3 || status === 4) return 'booked'
    if (status === 0) return 'unavailable'
    if (status === 1) return 'available'
    return 'default'
}

// 月度统计
const stats = computed(() => {
    let available = 0
    let booked = 0
    let unavailable = 0
    const todayStr = formatDateStr(today)

    // 统计有记录的日期
    const counted = new Set<string>()
    for (const dateStr of Object.keys(schedules.value)) {
        if (dateStr < todayStr) continue
        counted.add(dateStr)
        const status = analyzeDayStatus(dateStr)
        if (status === 'booked') booked++
        else if (status === 'available' || status === 'default') available++
        else if (status === 'unavailable') unavailable++
    }

    // 无记录的当月未来日期也是默认可预约
    const lastDay = new Date(year.value, month.value, 0).getDate()
    for (let d = 1; d <= lastDay; d++) {
        const dateStr = `${year.value}-${String(month.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`
        if (dateStr < todayStr || dateStr === todayStr) continue
        if (counted.has(dateStr)) continue
        available++
    }

    return { available, booked, unavailable }
})

// 获取日期指示器类型
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

const dayStatus = computed(() => {
    return getDayStatus(selectedDate.value)
})

// 获取档期状态
function getSlotStatus(): number {
    const dayData = schedules.value[selectedDate.value]
    if (!dayData) return -1
    return Number(dayData[0]?.status ?? -1)
}

// 状态文本
function getStatusLabel(status: number): string {
    const map: Record<number, string> = {
        0: '不可用',
        1: '可预约',
        2: '已预约',
        3: '已锁定',
        4: '内部预留'
    }
    return map[status] ?? '按规则'
}

// 状态标签样式
function getStatusTagStyle(status: number): Record<string, string> {
    const styles: Record<number, Record<string, string>> = {
        2: { background: 'rgba(245, 158, 11, 0.1)', color: '#F59E0B' },
        3: { background: 'rgba(107, 114, 128, 0.1)', color: '#6B7280' },
        4: { background: 'rgba(59, 130, 246, 0.1)', color: '#3B82F6' }
    }
    return styles[status] || {}
}

// 选择日期
function selectDate(cell: { dateStr: string; currentMonth: boolean; isPast: boolean }) {
    if (!cell.currentMonth || cell.isPast) return
    selectedDate.value = cell.dateStr
}

// 切换月份
function changeMonth(delta: number) {
    let m = month.value + delta
    let y = year.value
    if (m > 12) { m = 1; y++ }
    if (m < 1) { m = 12; y-- }
    year.value = y
    month.value = m
    // 选中新月份的第一天（如果是当月则选今天）
    const todayStr = formatDateStr(today)
    const firstOfMonth = `${y}-${String(m).padStart(2, '0')}-01`
    if (y === today.getFullYear() && m === today.getMonth() + 1) {
        selectedDate.value = todayStr
    } else {
        selectedDate.value = firstOfMonth
    }
    fetchMonth()
}

// 设置单个时段状态
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

// 获取月份数据
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
    background: #F4F5F7;
    padding-bottom: 60rpx;
}

/* 月份导航卡片 */
.month-nav-card {
    margin: 20rpx 24rpx 0;
    padding: 24rpx 28rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.month-nav {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 40rpx;
}

.nav-arrow {
    width: 64rpx;
    height: 64rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #F3F4F6;
    cursor: pointer;

    &:active {
        opacity: 0.7;
    }
}

.nav-arrow-text {
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1;
}

.month-title {
    font-size: 34rpx;
    font-weight: 700;
    color: #1F2937;
    min-width: 200rpx;
    text-align: center;
}

.month-stats {
    display: flex;
    justify-content: center;
    gap: 32rpx;
    margin-top: 20rpx;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.stat-dot {
    width: 14rpx;
    height: 14rpx;
    border-radius: 50%;
}

.stat-text {
    font-size: 24rpx;
    color: #6B7280;
}

/* 通用色点 */
.dot-available {
    background: #10B981;
}

.dot-booked {
    background: #F59E0B;
}

.dot-unavailable {
    background: #EF4444;
}

/* 日历卡片 */
.calendar-card {
    margin: 16rpx 24rpx 0;
    padding: 20rpx 16rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.week-header {
    display: flex;
    margin-bottom: 8rpx;
}

.week-cell {
    flex: 1;
    text-align: center;
    font-size: 24rpx;
    font-weight: 600;
    color: #6B7280;
    padding: 16rpx 0;
}

.calendar-grid {
    display: flex;
    flex-wrap: wrap;
}

.day-cell {
    width: calc(100% / 7);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 96rpx;
    border-radius: 16rpx;
    border: 2rpx solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;

    &.other-month .day-num {
        color: #E5E7EB;
    }

    &.is-past .day-num {
        color: #9CA3AF;
        text-decoration: line-through;
    }

    &.is-selected {
        border-color: currentColor;
    }

    &:active:not(.other-month):not(.is-past) {
        opacity: 0.7;
    }
}

.day-num {
    font-size: 28rpx;
    font-weight: 500;
    color: #1F2937;
}

.day-dots {
    margin-top: 6rpx;
    height: 10rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.indicator-dot {
    width: 10rpx;
    height: 10rpx;
    border-radius: 50%;
}

/* 日期详情卡片 */
.detail-card {
    margin: 16rpx 24rpx 0;
    padding: 28rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
}

.detail-header {
    margin-bottom: 20rpx;
}

.detail-date-info {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.detail-date {
    font-size: 32rpx;
    font-weight: 700;
    color: #1F2937;
}

.detail-week {
    font-size: 24rpx;
    color: #9CA3AF;
    padding: 4rpx 14rpx;
    background: #F3F4F6;
    border-radius: 16rpx;
}

/* 快捷操作 */
.quick-actions {
    display: flex;
    gap: 16rpx;
    margin-bottom: 24rpx;
}

.quick-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    height: 68rpx;
    border-radius: 34rpx;
    font-size: 26rpx;
    font-weight: 500;
    border: 2rpx solid;
    cursor: pointer;

    &:active {
        opacity: 0.7;
    }
}

.quick-btn-off {
    background: rgba(239, 68, 68, 0.06);
    color: #EF4444;
    border-color: rgba(239, 68, 68, 0.3);
}

/* 时段列表 */
.slot-list {
    padding: 0;
}

.slot-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20rpx 0;
    border-bottom: 1rpx solid #F3F4F6;

    &:last-child {
        border-bottom: none;
    }
}

.slot-left {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.slot-name {
    font-size: 28rpx;
    font-weight: 600;
    color: #374151;
}

.slot-time {
    font-size: 22rpx;
    color: #9CA3AF;
}

.slot-right {
    display: flex;
    align-items: center;
}

/* 不可编辑状态标签 */
.status-tag {
    font-size: 24rpx;
    font-weight: 500;
    padding: 8rpx 20rpx;
    border-radius: 20rpx;
}

/* 切换按钮组 */
.toggle-group {
    display: flex;
    background: #F3F4F6;
    border-radius: 24rpx;
    overflow: hidden;
}

.toggle-btn {
    font-size: 24rpx;
    font-weight: 500;
    padding: 10rpx 24rpx;
    color: #9CA3AF;
    transition: all 0.2s ease;
    cursor: pointer;

    &.active {
        font-weight: 600;
    }

    &:active {
        opacity: 0.7;
    }
}

/* 提示 */
.tip-row {
    display: flex;
    align-items: center;
    gap: 8rpx;
    margin: 20rpx 24rpx 0;
    padding: 0 8rpx;
}

.tip-text {
    font-size: 22rpx;
    color: #9CA3AF;
}
</style>
