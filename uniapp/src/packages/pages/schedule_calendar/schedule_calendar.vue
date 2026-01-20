<template>
    <view class="schedule-calendar">
        <!-- 工作人员信息 -->
        <view class="staff-info" v-if="staffInfo">
            <image :src="staffInfo.avatar" class="avatar" mode="aspectFill" />
            <view class="info">
                <text class="name">{{ staffInfo.name }}</text>
                <text class="price">¥{{ staffInfo.price }}/天</text>
            </view>
        </view>

        <!-- 年月选择 -->
        <view class="month-selector">
            <view class="arrow" @click="prevMonth">
                <text class="iconfont icon-left"></text>
            </view>
            <view class="month-text">{{ currentYear }}年{{ currentMonth }}月</view>
            <view class="arrow" @click="nextMonth">
                <text class="iconfont icon-right"></text>
            </view>
        </view>

        <!-- 星期标题 -->
        <view class="week-header">
            <view class="week-item" v-for="week in weekDays" :key="week">{{ week }}</view>
        </view>

        <!-- 日历格子 -->
        <view class="calendar-grid">
            <!-- 填充空白 -->
            <view class="day-item empty" v-for="n in firstDayOfMonth" :key="'empty-' + n"></view>
            
            <!-- 日期 -->
            <view 
                class="day-item"
                v-for="day in calendarDays"
                :key="day.date"
                :class="{
                    'is-today': day.date === today,
                    'is-past': day.is_past,
                    'is-lucky': day.is_lucky_day,
                    'is-holiday': day.is_holiday,
                    'is-selected': selectedDate === day.date,
                    'is-disabled': !day.is_available || day.is_past
                }"
                @click="handleDayClick(day)"
            >
                <text class="day-num">{{ day.date.split('-')[2] }}</text>
                <text class="lunar" v-if="day.lunar_date">{{ day.lunar_date.slice(-2) }}</text>
                <view class="tags">
                    <text class="tag lucky" v-if="day.is_lucky_day">吉</text>
                    <text class="tag holiday" v-if="day.is_holiday">假</text>
                </view>
            </view>
        </view>

        <!-- 图例说明 -->
        <view class="legend">
            <view class="legend-item">
                <view class="dot lucky"></view>
                <text>吉日</text>
            </view>
            <view class="legend-item">
                <view class="dot holiday"></view>
                <text>节假日</text>
            </view>
            <view class="legend-item">
                <view class="dot disabled"></view>
                <text>不可预约</text>
            </view>
        </view>

        <!-- 时间段选择 -->
        <view class="time-slot-section" v-if="selectedDate">
            <view class="section-title">选择时间段</view>
            <view class="time-slots">
                <view 
                    class="slot-item"
                    v-for="slot in timeSlots"
                    :key="slot.value"
                    :class="{ 'is-selected': selectedTimeSlot === slot.value }"
                    @click="handleSlotClick(slot)"
                >
                    {{ slot.label }}
                </view>
            </view>
        </view>

        <!-- 选择结果 -->
        <view class="selection-result" v-if="selectedDate">
            <view class="result-info">
                <text>已选择: {{ selectedDate }} {{ selectedTimeSlotLabel }}</text>
                <text class="price">¥{{ currentPrice }}</text>
            </view>
        </view>

        <!-- 底部操作栏 -->
        <view class="bottom-bar">
            <view class="total">
                <text>合计: </text>
                <text class="price">¥{{ currentPrice }}</text>
            </view>
            <view class="actions">
                <button class="btn-waitlist" v-if="!isAvailable" @click="handleJoinWaitlist">加入候补</button>
                <button class="btn-cart" @click="handleAddToCart">加入购物车</button>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getStaffSchedule, joinWaitlist } from '@/api/schedule'
import { addToCart } from '@/api/cart'

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

const timeSlots = [
    { value: 0, label: '全天' },
    { value: 1, label: '上午' },
    { value: 2, label: '下午' },
    { value: 3, label: '晚上' }
]

// 计算月份第一天是星期几
const firstDayOfMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value - 1, 1).getDay()
})

// 计算日历天数
const calendarDays = computed(() => {
    const days = calendarData.value.days || {}
    return Object.values(days)
})

// 选中时间段文字
const selectedTimeSlotLabel = computed(() => {
    const slot = timeSlots.find(s => s.value === selectedTimeSlot.value)
    return slot ? slot.label : ''
})

// 当前价格
const currentPrice = computed(() => {
    return staffInfo.value?.price || 0
})

// 获取档期数据
const fetchSchedule = async () => {
    const res = await getStaffSchedule({
        staff_id: staffId.value,
        year: currentYear.value,
        month: currentMonth.value
    })
    calendarData.value = res
    staffInfo.value = res.staff
}

// 上个月
const prevMonth = () => {
    if (currentMonth.value === 1) {
        currentYear.value--
        currentMonth.value = 12
    } else {
        currentMonth.value--
    }
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
    fetchSchedule()
}

// 点击日期
const handleDayClick = (day: any) => {
    if (day.is_past || !day.is_available) {
        isAvailable.value = false
        selectedDate.value = day.date
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

// 加入候补
const handleJoinWaitlist = async () => {
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
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

// 加入购物车
const handleAddToCart = async () => {
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
        
        // 跳转到购物车
        setTimeout(() => {
            uni.navigateTo({ url: '/packages/pages/cart/cart' })
        }, 1500)
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

onLoad((options: any) => {
    staffId.value = parseInt(options.staff_id || '0')
    if (staffId.value > 0) {
        fetchSchedule()
    }
})
</script>

<style lang="scss" scoped>
.schedule-calendar {
    min-height: 100vh;
    background: #f5f5f5;
    padding-bottom: 120rpx;
}

.staff-info {
    display: flex;
    align-items: center;
    padding: 30rpx;
    background: #fff;
    margin-bottom: 20rpx;
    
    .avatar {
        width: 100rpx;
        height: 100rpx;
        border-radius: 50%;
        margin-right: 20rpx;
    }
    
    .info {
        .name {
            font-size: 32rpx;
            font-weight: bold;
            display: block;
        }
        .price {
            font-size: 28rpx;
            color: #ff6b6b;
            margin-top: 8rpx;
        }
    }
}

.month-selector {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20rpx;
    background: #fff;
    
    .arrow {
        padding: 20rpx;
    }
    
    .month-text {
        font-size: 32rpx;
        font-weight: bold;
        margin: 0 40rpx;
    }
}

.week-header {
    display: flex;
    background: #fff;
    padding: 20rpx 0;
    
    .week-item {
        flex: 1;
        text-align: center;
        font-size: 26rpx;
        color: #666;
    }
}

.calendar-grid {
    display: flex;
    flex-wrap: wrap;
    background: #fff;
    padding-bottom: 20rpx;
    
    .day-item {
        width: calc(100% / 7);
        height: 120rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        
        &.empty {
            background: transparent;
        }
        
        &.is-today {
            .day-num {
                background: #1890ff;
                color: #fff;
                border-radius: 50%;
                width: 50rpx;
                height: 50rpx;
                line-height: 50rpx;
                text-align: center;
            }
        }
        
        &.is-past {
            opacity: 0.4;
        }
        
        &.is-lucky {
            background: #fff1f0;
        }
        
        &.is-holiday {
            background: #fffbe6;
        }
        
        &.is-selected {
            background: #e6f7ff;
            border: 2rpx solid #1890ff;
            border-radius: 8rpx;
        }
        
        &.is-disabled {
            .day-num {
                color: #ccc;
            }
        }
        
        .day-num {
            font-size: 28rpx;
        }
        
        .lunar {
            font-size: 20rpx;
            color: #999;
            margin-top: 4rpx;
        }
        
        .tags {
            position: absolute;
            top: 4rpx;
            right: 4rpx;
            display: flex;
            gap: 4rpx;
            
            .tag {
                font-size: 16rpx;
                padding: 2rpx 6rpx;
                border-radius: 4rpx;
                
                &.lucky {
                    background: #ff4d4f;
                    color: #fff;
                }
                
                &.holiday {
                    background: #faad14;
                    color: #fff;
                }
            }
        }
    }
}

.legend {
    display: flex;
    justify-content: center;
    padding: 20rpx;
    background: #fff;
    margin-top: 20rpx;
    gap: 40rpx;
    
    .legend-item {
        display: flex;
        align-items: center;
        font-size: 24rpx;
        color: #666;
        
        .dot {
            width: 20rpx;
            height: 20rpx;
            border-radius: 4rpx;
            margin-right: 10rpx;
            
            &.lucky { background: #fff1f0; border: 1rpx solid #ff4d4f; }
            &.holiday { background: #fffbe6; border: 1rpx solid #faad14; }
            &.disabled { background: #f5f5f5; border: 1rpx solid #ccc; }
        }
    }
}

.time-slot-section {
    background: #fff;
    margin-top: 20rpx;
    padding: 30rpx;
    
    .section-title {
        font-size: 28rpx;
        font-weight: bold;
        margin-bottom: 20rpx;
    }
    
    .time-slots {
        display: flex;
        gap: 20rpx;
        
        .slot-item {
            flex: 1;
            text-align: center;
            padding: 20rpx;
            background: #f5f5f5;
            border-radius: 8rpx;
            font-size: 26rpx;
            
            &.is-selected {
                background: #1890ff;
                color: #fff;
            }
        }
    }
}

.selection-result {
    background: #fff;
    margin-top: 20rpx;
    padding: 30rpx;
    
    .result-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 28rpx;
        
        .price {
            font-size: 36rpx;
            color: #ff6b6b;
            font-weight: bold;
        }
    }
}

.bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    padding: 20rpx 30rpx;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.1);
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    
    .total {
        font-size: 28rpx;
        
        .price {
            font-size: 36rpx;
            color: #ff6b6b;
            font-weight: bold;
        }
    }
    
    .actions {
        display: flex;
        gap: 20rpx;
        
        button {
            padding: 16rpx 40rpx;
            border-radius: 40rpx;
            font-size: 28rpx;
            
            &.btn-waitlist {
                background: #ff9500;
                color: #fff;
            }
            
            &.btn-cart {
                background: #ff6b6b;
                color: #fff;
            }
        }
    }
}
</style>
