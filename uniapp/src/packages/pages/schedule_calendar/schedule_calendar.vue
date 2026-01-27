<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar 
            title="选择档期"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
    </page-meta>
    
    <view class="schedule-calendar">
        <!-- 工作人员信息卡片 -->
        <view class="staff-card" v-if="staffInfo">
            <view class="staff-header">
                <image 
                    :src="staffInfo.avatar || '/static/images/default-avatar.png'" 
                    class="staff-avatar" 
                    mode="aspectFill" 
                />
                <view class="staff-info">
                    <text class="staff-name">{{ staffInfo.name }}</text>
                    <text class="staff-category">{{ staffInfo.category?.name }}</text>
                </view>
            </view>
            <view class="price-info">
                <text class="price-label">服务价格</text>
                <view class="price-amount">
                    <text class="price-symbol">¥</text>
                    <text class="price-value">{{ staffInfo.price }}</text>
                    <text class="price-unit">/天</text>
                </view>
            </view>
        </view>

        <!-- 月份选择器 -->
        <view class="month-selector">
            <view class="selector-header">
                <view class="nav-btn" @click="prevMonth">
                    <tn-icon name="left" size="32" :color="$theme.primaryColor" />
                </view>
                <picker mode="date" :value="pickerValue" fields="month" @change="handleDateChange">
                    <view class="month-display">
                        <text class="month-text">{{ currentYear }}年{{ currentMonth }}月</text>
                        <tn-icon name="down" size="24" color="#999999" />
                    </view>
                </picker>
                <view class="nav-btn" @click="nextMonth">
                    <tn-icon name="right" size="32" :color="$theme.primaryColor" />
                </view>
                <view 
                    class="today-btn" 
                    :style="{ 
                        background: $theme.primaryColor,
                        color: '#FFFFFF'
                    }"
                    @click="toToday"
                >
                    <text>今天</text>
                </view>
            </view>
        </view>

        <!-- 日历主体 -->
        <view class="calendar-container">
            <!-- 星期标题 -->
            <view class="week-header">
                <view 
                    v-for="week in weekDays" 
                    :key="week" 
                    class="week-item"
                    :class="{ 'is-weekend': week === '日' || week === '六' }"
                >
                    <text>{{ week }}</text>
                </view>
            </view>

            <!-- 日历网格 -->
            <view class="calendar-grid">
                <!-- 填充空白 -->
                <view 
                    v-for="n in firstDayOfMonth" 
                    :key="'empty-' + n" 
                    class="day-cell empty"
                ></view>

                <!-- 日期单元格 -->
                <view
                    v-for="day in calendarDays"
                    :key="day.date"
                    class="day-cell"
                    :class="{
                        'is-today': day.date === today,
                        'is-past': day.is_past,
                        'is-selected': selectedDate === day.date,
                        'is-disabled': !day.is_available || day.is_past,
                        'is-lucky': day.is_lucky_day,
                        'is-holiday': day.is_holiday
                    }"
                    @click="handleDayClick(day)"
                >
                    <view class="day-content">
                        <text class="day-number">{{ day.date.split('-')[2] }}</text>
                        <text v-if="day.lunar_date" class="lunar-text">
                            {{ day.lunar_date.slice(-2) }}
                        </text>
                    </view>
                    
                    <!-- 状态标识 -->
                    <view v-if="day.schedules && day.schedules.length > 0" class="status-badge">
                        <view 
                            class="badge-dot"
                            :class="getStatusClass(day)"
                        ></view>
                    </view>
                    
                    <!-- 特殊标记 -->
                    <view class="day-tags">
                        <view v-if="day.is_lucky_day" class="tag-lucky">吉</view>
                        <view v-if="day.is_holiday" class="tag-holiday">假</view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 图例说明 -->
        <view class="legend-section">
            <view class="legend-title">图例说明</view>
            <view class="legend-grid">
                <view class="legend-item">
                    <view class="legend-dot available"></view>
                    <text class="legend-text">可预约</text>
                </view>
                <view class="legend-item">
                    <view class="legend-dot booked"></view>
                    <text class="legend-text">已预约</text>
                </view>
                <view class="legend-item">
                    <view class="legend-dot locked"></view>
                    <text class="legend-text">已锁定</text>
                </view>
                <view class="legend-item">
                    <view class="legend-dot reserved"></view>
                    <text class="legend-text">预留</text>
                </view>
            </view>
        </view>

        <!-- 时间段选择 -->
        <view v-if="selectedDate" class="time-slot-section">
            <view class="section-header">
                <text class="section-title">选择时间段</text>
                <text class="section-hint">{{ selectedDate }}</text>
            </view>
            <view class="time-slots">
                <view
                    v-for="slot in timeSlots"
                    :key="slot.value"
                    class="slot-item"
                    :class="{ 'is-selected': selectedTimeSlot === slot.value }"
                    :style="selectedTimeSlot === slot.value ? {
                        background: $theme.primaryColor,
                        borderColor: $theme.primaryColor
                    } : {}"
                    @click="handleSlotClick(slot)"
                >
                    <tn-icon 
                        :name="slot.icon" 
                        size="40" 
                        :color="selectedTimeSlot === slot.value ? '#FFFFFF' : $theme.primaryColor" 
                    />
                    <text class="slot-label">{{ slot.label }}</text>
                </view>
            </view>
        </view>

        <!-- 底部占位 -->
        <view class="bottom-placeholder"></view>

        <!-- 底部操作栏 -->
        <view class="bottom-bar">
            <view class="price-summary">
                <text class="summary-label">合计</text>
                <view class="summary-price">
                    <text class="price-symbol">¥</text>
                    <text class="price-value">{{ currentPrice }}</text>
                </view>
            </view>
            <view class="action-buttons">
                <view 
                    v-if="!isAvailable" 
                    class="btn-waitlist"
                    @click="handleJoinWaitlist"
                >
                    <tn-icon name="clock" size="32" color="#FFFFFF" />
                    <text>候补</text>
                </view>
                <view 
                    class="btn-cart"
                    :class="{ disabled: !isAvailable || !selectedDate }"
                    :style="{ 
                        background: isAvailable && selectedDate ? 
                            `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)` : 
                            '#CCCCCC'
                    }"
                    @click="handleAddToCart"
                >
                    <tn-icon name="cart" size="32" color="#FFFFFF" />
                    <text>加入购物车</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getStaffSchedule, joinWaitlist } from '@/api/schedule'
import { addToCart } from '@/api/cart'
import { useUserStore } from '@/stores/user'

const weekDays = ['日', '一', '二', '三', '四', '五', '六']
const today = new Date().toISOString().split('T')[0]

const staffId = ref(0)
const staffInfo = ref<any>(null)
const currentYear = ref(new Date().getFullYear())
const currentMonth = ref(new Date().getMonth() + 1)
const calendarData = ref<any>({})
const selectedDate = ref('')
const selectedTimeSlot = ref(0)
const isAvailable = ref(true)

// 时间段配置
const timeSlots = [
    { value: 0, label: '全天', icon: 'sun' },
    { value: 1, label: '上午', icon: 'sunrise' },
    { value: 2, label: '下午', icon: 'sun' },
    { value: 3, label: '晚上', icon: 'moon' }
]

// 日期选择器值
const pickerValue = computed(() => {
    return `${String(currentYear.value)}-${String(currentMonth.value).padStart(2, '0')}`
})

// 计算月份第一天是星期几
const firstDayOfMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value - 1, 1).getDay()
})

// 计算日历天数
const calendarDays = computed(() => {
    const days = calendarData.value.days || {}
    return Object.values(days).map((day: any) => ({
        ...day,
        is_past: new Date(day.date) < new Date(today),
        schedules: day.schedules ? Object.values(day.schedules) : []
    }))
})

// 当前价格
const currentPrice = computed(() => {
    return staffInfo.value?.price || 0
})

// 获取档期数据
const fetchSchedule = async () => {
    try {
        const res = await getStaffSchedule({
            staff_id: staffId.value,
            year: currentYear.value,
            month: currentMonth.value
        })
        calendarData.value = res
        staffInfo.value = res.staff
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

// 上个月
const prevMonth = () => {
    if (currentMonth.value === 1) {
        currentYear.value--
        currentMonth.value = 12
    } else {
        currentMonth.value--
    }
    selectedDate.value = ''
    fetchSchedule()
}

// 下个月
const nextMonth = () => {
    if (currentMonth.value === 12) {
        currentYear.value++
        currentMonth.value = 1
    } else {
        currentMonth.value++
    }
    selectedDate.value = ''
    fetchSchedule()
}

// 回到今天
const toToday = () => {
    const now = new Date()
    currentYear.value = now.getFullYear()
    currentMonth.value = now.getMonth() + 1
    selectedDate.value = ''
    fetchSchedule()
}

// 日期选择器变化
const handleDateChange = (e: any) => {
    const date = new Date(e.detail.value)
    currentYear.value = date.getFullYear()
    currentMonth.value = date.getMonth() + 1
    selectedDate.value = ''
    fetchSchedule()
}

// 点击日期
const handleDayClick = (day: any) => {
    if (day.is_past) {
        uni.showToast({ title: '该日期已过期', icon: 'none' })
        return
    }
    
    if (!day.is_available) {
        isAvailable.value = false
        selectedDate.value = day.date
        selectedTimeSlot.value = 0
        uni.showToast({ title: '该日期不可预约，可加入候补', icon: 'none' })
        return
    }
    
    isAvailable.value = true
    selectedDate.value = day.date
    selectedTimeSlot.value = 0
}

// 点击时间段
const handleSlotClick = (slot: any) => {
    selectedTimeSlot.value = slot.value
}

// 获取状态样式类
const getStatusClass = (day: any) => {
    if (!day.schedules || day.schedules.length === 0) {
        return ''
    }

    const schedule = day.schedules[0]
    const classMap: Record<number, string> = {
        0: 'unavailable',
        1: 'available',
        2: 'booked',
        3: 'locked',
        4: 'reserved'
    }

    return classMap[schedule.status] || ''
}

// 加入候补
const handleJoinWaitlist = async () => {
    const userStore = useUserStore()
    if (!userStore.isLogin) {
        uni.showToast({ title: '请先登录', icon: 'none' })
        setTimeout(() => {
            uni.navigateTo({ url: '/pages/login/login' })
        }, 1500)
        return
    }

    if (!selectedDate.value) {
        uni.showToast({ title: '请选择日期', icon: 'none' })
        return
    }

    try {
        await joinWaitlist({
            staff_id: staffId.value,
            date: selectedDate.value,
            time_slot: selectedTimeSlot.value
        })
        uni.showToast({ title: '已加入候补', icon: 'success' })
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '操作失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

// 加入购物车
const handleAddToCart = async () => {
    const userStore = useUserStore()
    if (!userStore.isLogin) {
        uni.showToast({ title: '请先登录', icon: 'none' })
        setTimeout(() => {
            uni.navigateTo({ url: '/pages/login/login' })
        }, 1500)
        return
    }

    if (!selectedDate.value) {
        uni.showToast({ title: '请选择日期', icon: 'none' })
        return
    }

    if (!isAvailable.value) {
        uni.showToast({ title: '该日期不可预约', icon: 'none' })
        return
    }

    try {
        await addToCart({
            staff_id: staffId.value,
            date: selectedDate.value,
            time_slot: selectedTimeSlot.value
        })
        uni.showToast({ title: '已加入购物车', icon: 'success' })

        setTimeout(() => {
            uni.navigateTo({ url: '/packages/pages/cart/cart' })
        }, 1500)
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '操作失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

onLoad((options: any) => {
    staffId.value = parseInt(options.staff_id || '0')
    if (staffId.value > 0) {
        fetchSchedule()
    } else {
        uni.showToast({ title: '请选择工作人员', icon: 'none' })
        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
    }
})
</script>

<style lang="scss" scoped>
.schedule-calendar {
    min-height: 100vh;
    background: linear-gradient(180deg, var(--color-primary-light-9) 0%, #F5F5F5 100%);
    padding-bottom: env(safe-area-inset-bottom);
}

/* 工作人员信息卡片 */
.staff-card {
    margin: 24rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 32rpx;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
}

.staff-header {
    display: flex;
    align-items: center;
    margin-bottom: 24rpx;
    padding-bottom: 24rpx;
    border-bottom: 1rpx solid #F0F0F0;
}

.staff-avatar {
    width: 96rpx;
    height: 96rpx;
    border-radius: 16rpx;
    margin-right: 20rpx;
}

.staff-info {
    flex: 1;
    
    .staff-name {
        display: block;
        font-size: 32rpx;
        font-weight: 700;
        color: var(--color-main);
        margin-bottom: 8rpx;
    }
    
    .staff-category {
        font-size: 26rpx;
        color: var(--color-muted);
    }
}

.price-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    
    .price-label {
        font-size: 26rpx;
        color: var(--color-content);
    }
}

.price-amount {
    display: flex;
    align-items: baseline;
    
    .price-symbol {
        font-size: 24rpx;
        font-weight: 600;
        color: var(--color-primary);
        margin-right: 4rpx;
    }
    
    .price-value {
        font-size: 40rpx;
        font-weight: 700;
        color: var(--color-primary);
    }
    
    .price-unit {
        font-size: 24rpx;
        color: var(--color-muted);
        margin-left: 4rpx;
    }
}

/* 月份选择器 */
.month-selector {
    margin: 0 24rpx 24rpx;
    background: #FFFFFF;
    border-radius: 16rpx;
    padding: 24rpx;
}

.selector-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.nav-btn {
    width: 64rpx;
    height: 64rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #F9FAFB;
    border-radius: 32rpx;
    transition: all 0.2s ease;
    flex-shrink: 0;
    
    &:active {
        transform: scale(0.9);
        background: #F0F0F0;
    }
}

.month-display {
    display: flex;
    align-items: center;
    gap: 12rpx;
    padding: 12rpx 32rpx;
    background: #F9FAFB;
    border-radius: 32rpx;
    flex: 1;
    justify-content: center;
    
    .month-text {
        font-size: 32rpx;
        font-weight: 700;
        color: var(--color-main);
    }
}

.today-btn {
    padding: 12rpx 24rpx;
    border-radius: 32rpx;
    font-size: 26rpx;
    font-weight: 500;
    transition: all 0.2s ease;
    flex-shrink: 0;
    
    &:active {
        transform: scale(0.95);
    }
    
    text {
        color: inherit;
    }
}

/* 日历容器 */
.calendar-container {
    margin: 0 24rpx 24rpx;
    background: #FFFFFF;
    border-radius: 16rpx;
    padding: 24rpx;
}

/* 星期标题 */
.week-header {
    display: flex;
    margin-bottom: 16rpx;
}

.week-item {
    flex: 1;
    text-align: center;
    font-size: 26rpx;
    font-weight: 600;
    color: var(--color-content);
    padding: 12rpx 0;
    
    &.is-weekend {
        color: var(--color-secondary);
    }
}

/* 日历网格 */
.calendar-grid {
    display: flex;
    flex-wrap: wrap;
}

.day-cell {
    width: calc(100% / 7);
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    border-radius: 12rpx;
    margin-bottom: 8rpx;
    transition: all 0.2s ease;
    
    &.empty {
        background: transparent;
    }
    
    &.is-today {
        .day-number {
            background: var(--color-primary);
            color: #FFFFFF;
            width: 56rpx;
            height: 56rpx;
            line-height: 56rpx;
            border-radius: 28rpx;
            text-align: center;
        }
    }
    
    &.is-past {
        opacity: 0.3;
        pointer-events: none;
    }
    
    &.is-selected {
        background: var(--color-primary-light-9);
        border: 2rpx solid var(--color-primary);
    }
    
    &.is-disabled {
        .day-number {
            color: var(--color-disabled);
        }
    }
    
    &.is-lucky {
        background: #FFF1F0;
    }
    
    &.is-holiday {
        background: #FFFBE6;
    }
    
    &:active:not(.is-past):not(.is-disabled) {
        transform: scale(0.95);
    }
}

.day-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4rpx;
}

.day-number {
    font-size: 28rpx;
    font-weight: 600;
    color: var(--color-main);
}

.lunar-text {
    font-size: 20rpx;
    color: var(--color-muted);
}

/* 状态标识 */
.status-badge {
    position: absolute;
    bottom: 8rpx;
    left: 50%;
    transform: translateX(-50%);
}

.badge-dot {
    width: 12rpx;
    height: 12rpx;
    border-radius: 6rpx;
    
    &.available {
        background: #52C41A;
    }
    
    &.booked {
        background: #FAAD14;
    }
    
    &.locked {
        background: #FF4D4F;
    }
    
    &.reserved {
        background: #722ED1;
    }
    
    &.unavailable {
        background: #BFBFBF;
    }
}

/* 特殊标记 */
.day-tags {
    position: absolute;
    top: 4rpx;
    left: 4rpx;
    display: flex;
    gap: 4rpx;
}

.tag-lucky,
.tag-holiday {
    width: 24rpx;
    height: 24rpx;
    border-radius: 12rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16rpx;
    font-weight: 700;
    color: #FFFFFF;
}

.tag-lucky {
    background: #FF4D4F;
}

.tag-holiday {
    background: #FAAD14;
}

/* 图例说明 */
.legend-section {
    margin: 0 24rpx 24rpx;
    background: #FFFFFF;
    border-radius: 16rpx;
    padding: 24rpx;
}

.legend-title {
    font-size: 28rpx;
    font-weight: 700;
    color: var(--color-main);
    margin-bottom: 16rpx;
}

.legend-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16rpx;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.legend-dot {
    width: 24rpx;
    height: 24rpx;
    border-radius: 12rpx;
    
    &.available {
        background: #52C41A;
    }
    
    &.booked {
        background: #FAAD14;
    }
    
    &.locked {
        background: #FF4D4F;
    }
    
    &.reserved {
        background: #722ED1;
    }
}

.legend-text {
    font-size: 24rpx;
    color: var(--color-content);
}

/* 时间段选择 */
.time-slot-section {
    margin: 0 24rpx 24rpx;
    background: #FFFFFF;
    border-radius: 16rpx;
    padding: 32rpx;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24rpx;
}

.section-title {
    font-size: 30rpx;
    font-weight: 700;
    color: var(--color-main);
}

.section-hint {
    font-size: 24rpx;
    color: var(--color-muted);
}

.time-slots {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16rpx;
}

.slot-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12rpx;
    padding: 24rpx;
    background: #F9FAFB;
    border: 2rpx solid #F0F0F0;
    border-radius: 16rpx;
    transition: all 0.2s ease;
    
    &.is-selected {
        color: #FFFFFF;
    }
    
    &:active {
        transform: scale(0.95);
    }
    
    .slot-label {
        font-size: 26rpx;
        font-weight: 500;
    }
}

/* 底部占位 */
.bottom-placeholder {
    height: 180rpx;
}

/* 底部操作栏 */
.bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx;
    padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
    background: #FFFFFF;
    box-shadow: 0 -4rpx 16rpx rgba(0, 0, 0, 0.08);
    z-index: 100;
}

.price-summary {
    flex: 1;
    
    .summary-label {
        display: block;
        font-size: 24rpx;
        color: var(--color-muted);
        margin-bottom: 8rpx;
    }
}

.summary-price {
    display: flex;
    align-items: baseline;
    
    .price-symbol {
        font-size: 28rpx;
        font-weight: 600;
        color: var(--color-primary);
        margin-right: 4rpx;
    }
    
    .price-value {
        font-size: 48rpx;
        font-weight: 700;
        color: var(--color-primary);
    }
}

.action-buttons {
    display: flex;
    gap: 16rpx;
}

.btn-waitlist,
.btn-cart {
    display: flex;
    align-items: center;
    gap: 12rpx;
    padding: 20rpx 32rpx;
    border-radius: 48rpx;
    font-size: 28rpx;
    font-weight: 600;
    transition: all 0.2s ease;
    
    &:active {
        transform: scale(0.95);
    }
    
    text {
        color: #FFFFFF;
    }
}

.btn-waitlist {
    background: linear-gradient(135deg, #FF9500 0%, #FF9500 100%);
    box-shadow: 0 4rpx 12rpx rgba(255, 149, 0, 0.3);
}

.btn-cart {
    box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.3);
    
    &.disabled {
        opacity: 0.5;
        box-shadow: none;
    }
}
</style>
