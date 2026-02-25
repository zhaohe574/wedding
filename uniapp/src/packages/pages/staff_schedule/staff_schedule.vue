<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="档期管理"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <!-- 日期选择卡片 -->
        <view class="date-card">
            <view class="card-header">
                <tn-icon name="calendar" size="32" :color="$theme.primaryColor" />
                <text class="card-title">选择日期</text>
            </view>

            <picker mode="date" :value="selectedDate" @change="handleDateChange">
                <view class="date-picker">
                    <view class="date-display">
                        <text class="date-text">{{ selectedDate }}</text>
                        <view class="date-week">{{ getWeekDay(selectedDate) }}</view>
                    </view>
                    <tn-icon name="right" size="28" color="#C8C9CC" />
                </view>
            </picker>

            <view class="month-info">
                <tn-icon name="info" size="24" color="#999999" />
                <text>当前月份：{{ year }}-{{ monthText }}</text>
            </view>
        </view>

        <!-- 时段列表 -->
        <view class="slot-list">
            <view v-for="slot in timeSlots" :key="slot.value" class="slot-card">
                <!-- 时段头部 -->
                <view class="slot-header">
                    <view class="slot-info">
                        <tn-icon :name="slot.icon" size="36" :color="$theme.primaryColor" />
                        <view class="slot-text">
                            <text class="slot-label">{{ slot.label }}</text>
                            <text class="slot-time">{{ slot.time }}</text>
                        </view>
                    </view>
                    <view class="slot-status" :style="getStatusStyle(getSlotStatus(slot.value))">
                        <tn-icon
                            :name="getStatusIcon(getSlotStatus(slot.value))"
                            size="24"
                            color="inherit"
                        />
                        <text>{{ getSlotStatusLabel(slot.value) }}</text>
                    </view>
                </view>

                <!-- 操作按钮 -->
                <view class="slot-actions">
                    <view
                        class="action-btn available-btn"
                        :class="{ disabled: !canEdit(slot.value) }"
                        :style="
                            canEdit(slot.value)
                                ? {
                                      background: `linear-gradient(135deg, ${$theme.primaryColor}15 0%, ${$theme.primaryColor}30 100%)`,
                                      color: $theme.primaryColor,
                                      borderColor: $theme.primaryColor
                                  }
                                : {}
                        "
                        @click="setStatus(slot.value, 1)"
                    >
                        <tn-icon
                            name="check-circle"
                            size="28"
                            :color="canEdit(slot.value) ? $theme.primaryColor : '#C8C9CC'"
                        />
                        <text>设为可预约</text>
                    </view>
                    <view
                        class="action-btn unavailable-btn"
                        :class="{ disabled: !canEdit(slot.value) }"
                        @click="setStatus(slot.value, 0)"
                    >
                        <tn-icon
                            name="close-circle"
                            size="28"
                            :color="canEdit(slot.value) ? '#FF2C3C' : '#C8C9CC'"
                        />
                        <text>设为不可用</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 提示信息 -->
        <view class="tip-card">
            <tn-icon name="info" size="28" color="#FF9900" />
            <text class="tip-text">已预约/锁定/预留的档期不可调整</text>
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

const formatDate = (date: Date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

const selectedDate = ref(formatDate(new Date()))
const year = ref(Number(selectedDate.value.split('-')[0]))
const month = ref(Number(selectedDate.value.split('-')[1]))
const schedules = ref<Record<string, any>>({})

const monthText = computed(() => String(month.value).padStart(2, '0'))

// 时段配置
const timeSlots = [
    { value: 0, label: '全天', time: '全天服务', icon: 'sun' },
    { value: 1, label: '早礼', time: '08:00-12:00', icon: 'sunrise' },
    { value: 2, label: '午宴', time: '12:00-18:00', icon: 'sun' },
    { value: 3, label: '晚宴', time: '18:00-22:00', icon: 'moon' }
]

// 状态映射
const statusMap: Record<number, string> = {
    0: '不可用',
    1: '可预约',
    2: '已预约',
    3: '已锁定',
    4: '内部预留'
}

// 获取星期
const getWeekDay = (dateStr: string) => {
    const date = new Date(dateStr)
    const weekDays = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六']
    return weekDays[date.getDay()]
}

// 获取时段状态
const getSlotStatus = (slotValue: number) => {
    const day = schedules.value[selectedDate.value] || {}
    return day[slotValue]?.status ?? null
}

// 获取状态文本
const getSlotStatusLabel = (slotValue: number) => {
    const status = getSlotStatus(slotValue)
    if (status === null || status === undefined) return '按规则'
    return statusMap[status] || '未知'
}

// 获取状态样式
const getStatusStyle = (status: number | null) => {
    const styles: Record<string, any> = {
        1: { background: 'rgba(25, 190, 107, 0.1)', color: '#19BE6B' },
        0: { background: 'rgba(255, 44, 60, 0.1)', color: '#FF2C3C' },
        2: { background: 'rgba(255, 153, 0, 0.1)', color: '#FF9900' },
        3: { background: `${$theme.primaryColor}15`, color: $theme.primaryColor },
        4: { background: 'rgba(64, 158, 255, 0.1)', color: '#409EFF' },
        default: { background: 'rgba(153, 153, 153, 0.1)', color: '#999999' }
    }
    return styles[status as any] || styles.default
}

// 获取状态图标
const getStatusIcon = (status: number | null) => {
    const icons: Record<string, string> = {
        1: 'check-circle',
        0: 'close-circle',
        2: 'clock',
        3: 'lock',
        4: 'star',
        default: 'info'
    }
    return icons[status as any] || icons.default
}

// 是否可编辑
const canEdit = (slotValue: number) => {
    const status = getSlotStatus(slotValue)
    return status === null || status === 0 || status === 1
}

// 获取月份数据
const fetchMonth = async () => {
    const res = await staffCenterScheduleMonth({ year: year.value, month: month.value })
    schedules.value = res?.schedules || {}
}

// 设置状态
const setStatus = async (slotValue: number, status: number) => {
    if (!canEdit(slotValue)) {
        uni.showToast({ title: '该时段不可调整', icon: 'none' })
        return
    }

    try {
        await staffCenterScheduleSetStatus({
            date: selectedDate.value,
            time_slot: slotValue,
            status
        })
        if (!schedules.value[selectedDate.value]) {
            schedules.value[selectedDate.value] = {}
        }
        schedules.value[selectedDate.value][slotValue] = {
            status
        }
        uni.showToast({ title: '设置成功', icon: 'success' })
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '设置失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

// 日期变更
const handleDateChange = (e: any) => {
    selectedDate.value = e.detail.value
    const [y, m] = selectedDate.value.split('-')
    const newYear = Number(y)
    const newMonth = Number(m)
    if (newYear !== year.value || newMonth !== month.value) {
        year.value = newYear
        month.value = newMonth
        fetchMonth()
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
    background: linear-gradient(180deg, rgba(124, 58, 237, 0.05) 0%, #f6f6f6 100%);
    padding-bottom: 40rpx;
}

/* 日期选择卡片 */
.date-card {
    margin: 24rpx;
    padding: 32rpx 24rpx;
    background: #ffffff;
    border-radius: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 24rpx;
}

.card-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
}

.date-picker {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx;
    background: rgba(124, 58, 237, 0.05);
    border-radius: 16rpx;
    border: 2rpx solid rgba(124, 58, 237, 0.1);
}

.date-display {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.date-text {
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
}

.date-week {
    padding: 4rpx 12rpx;
    background: rgba(124, 58, 237, 0.1);
    border-radius: 12rpx;
    font-size: 24rpx;
    color: #7c3aed;
}

.month-info {
    display: flex;
    align-items: center;
    gap: 8rpx;
    margin-top: 16rpx;
    font-size: 24rpx;
    color: #999999;
}

/* 时段列表 */
.slot-list {
    padding: 0 24rpx;
}

.slot-card {
    margin-bottom: 24rpx;
    padding: 24rpx;
    background: #ffffff;
    border-radius: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

/* 时段头部 */
.slot-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20rpx;
    border-bottom: 1rpx solid #f5f5f5;
    margin-bottom: 20rpx;
}

.slot-info {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.slot-text {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.slot-label {
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
}

.slot-time {
    font-size: 24rpx;
    color: #999999;
}

.slot-status {
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 8rpx 16rpx;
    border-radius: 24rpx;
    font-size: 24rpx;
    font-weight: 500;
}

/* 操作按钮 */
.slot-actions {
    display: flex;
    gap: 16rpx;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    height: 72rpx;
    border-radius: 48rpx;
    font-size: 28rpx;
    font-weight: 500;
    border: 2rpx solid;
    transition: all 0.2s ease;

    &:active:not(.disabled) {
        opacity: 0.8;
    }

    &.disabled {
        opacity: 0.4;
        pointer-events: none;
    }
}

.available-btn {
    background: transparent;
}

.unavailable-btn {
    background: rgba(255, 44, 60, 0.05);
    color: #ff2c3c;
    border-color: #ff2c3c;

    &.disabled {
        background: #f5f5f5;
        color: #c8c9cc;
        border-color: #e5e5e5;
    }
}

/* 提示卡片 */
.tip-card {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin: 0 24rpx;
    padding: 20rpx 24rpx;
    background: rgba(255, 153, 0, 0.1);
    border-radius: 16rpx;
    border: 1rpx solid rgba(255, 153, 0, 0.2);
}

.tip-text {
    flex: 1;
    font-size: 24rpx;
    color: #ff9900;
    line-height: 1.5;
}
</style>
