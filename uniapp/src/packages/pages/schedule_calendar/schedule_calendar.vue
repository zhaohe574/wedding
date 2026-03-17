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
                    :src="staffInfo.avatar || '/static/images/user/default_avatar.png'"
                    class="staff-avatar"
                    mode="aspectFill"
                />
                <view class="staff-info">
                    <view class="info-row">
                        <text class="staff-name">{{ staffInfo.name }}</text>
                        <view class="staff-rating" v-if="staffInfo.rating">
                            <tn-icon name="star-fill" size="24" color="#FFD700" />
                            <text class="rating-text">{{ staffInfo.rating }}</text>
                        </view>
                    </view>
                    <view class="info-row">
                        <text class="staff-category">{{ staffInfo.category?.name }}</text>
                        <text class="staff-experience" v-if="staffInfo.experience_years"
                            >{{ staffInfo.experience_years }}年经验</text
                        >
                    </view>
                    <view class="info-row" v-if="staffInfo.order_count">
                        <text class="staff-orders">已服务{{ staffInfo.order_count }}单</text>
                    </view>
                </view>
            </view>
        </view>
        <!-- 日历选择 -->
        <view class="calendar-section">
            <!-- 月份选择器 -->
            <view class="month-selector">
                <view class="selector-header">
                    <view class="nav-btn" @click="prevMonth">
                        <tn-icon name="left" size="32" :color="$theme.primaryColor" />
                    </view>
                    <picker
                        mode="date"
                        :value="pickerValue"
                        fields="month"
                        @change="handleDateChange"
                    >
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
                        v-for="(week, index) in weekDays"
                        :key="week"
                        class="week-item"
                        :class="{ 'is-weekend': index === 0 || index === 6 }"
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
                        <view v-if="getStatusClass(day)" class="status-badge">
                            <view class="badge-dot" :class="getStatusClass(day)"></view>
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
        </view>

        <!-- 推荐人员 -->
        <view v-if="showRecommendSection" class="recommend-section">
            <view class="section-header">
                <text class="section-title">同类型推荐</text>
                <text class="section-hint">该日期暂无档期</text>
            </view>
            <view v-if="recommendLoading" class="recommend-loading">
                <text>推荐加载中...</text>
            </view>
            <view v-else-if="recommendStaffList.length" class="recommend-list">
                <view
                    v-for="item in recommendStaffList"
                    :key="item.id"
                    class="recommend-card"
                    @click="handleRecommendStaffClick(item)"
                >
                    <image
                        class="recommend-avatar"
                        :src="item.avatar || '/static/images/user/default_avatar.png'"
                        mode="aspectFill"
                    />
                    <view class="recommend-info">
                        <view class="recommend-name-row">
                            <text class="recommend-name">{{ item.name }}</text>
                            <view v-if="item.rating" class="recommend-rating">
                                <tn-icon name="star-fill" size="20" color="#FFD700" />
                                <text class="rating-text">{{ item.rating }}</text>
                            </view>
                        </view>
                        <view class="recommend-meta">
                            <text class="recommend-category">{{
                                item.category_name || recommendCategoryName
                            }}</text>
                            <text v-if="item.experience_years" class="recommend-exp"
                                >{{ item.experience_years }}年经验</text
                            >
                        </view>
                        <view v-if="item.tags && item.tags.length" class="recommend-tags">
                            <text
                                v-for="(tag, index) in item.tags.slice(0, 3)"
                                :key="`${item.id}-${index}`"
                                class="recommend-tag"
                            >
                                {{ tag }}
                            </text>
                        </view>
                    </view>
                    <view class="recommend-action">
                        <text class="action-text">去查看</text>
                        <tn-icon name="right" size="18" color="#999999" />
                    </view>
                </view>
            </view>
            <view v-else class="recommend-empty">
                <text>暂无{{ recommendCategoryName }}可推荐人员</text>
            </view>
        </view>

        <!-- 套餐选择 -->
        <view class="package-section" v-if="selectedDate && staffPackages.length > 0">
            <view class="section-header">
                <text class="section-title">选择套餐</text>
                <text class="section-hint" v-if="selectedPackageName"
                    >当前：{{ selectedPackageName }}</text
                >
            </view>
            <view class="package-list">
                <view
                    v-for="pkg in staffPackages"
                    :key="getPackageId(pkg)"
                    class="package-card"
                    :class="{ active: selectedPackageId === getPackageId(pkg) }"
                    @click="selectPackage(pkg)"
                >
                    <view class="package-main">
                        <view class="package-info">
                            <text class="package-name">{{ getPackageName(pkg) }}</text>
                            <text class="package-type">
                                {{ pkg.description || pkg.package?.description || '全天预约，价格固定' }}
                            </text>
                        </view>
                        <view class="package-price-wrapper">
                            <view v-if="getPackageOriginalPrice(pkg)" class="original-price">
                                <text class="original-price-text"
                                    >¥{{ getPackageOriginalPrice(pkg) }}</text
                                >
                            </view>
                            <text class="package-price">¥{{ getPackageBasePrice(pkg) }}</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>

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
                <view v-if="showWaitlistButton" class="btn-waitlist" @click="handleJoinWaitlist">
                    <tn-icon name="clock" size="32" color="#FFFFFF" />
                    <text>候补</text>
                </view>
                <view
                    class="btn-cart"
                    :class="{ disabled: !canSubmit || !isAvailable }"
                    :style="{
                        background:
                            canSubmit && isAvailable
                                ? `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                                : '#CCCCCC'
                    }"
                    @click="handleDirectOrder"
                >
                    <tn-icon name="cart" size="32" color="#FFFFFF" />
                    <text>立即下单</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getStaffSchedule, joinWaitlist } from '@/api/schedule'
import { getStaffDetail, getStaffList } from '@/api/staff'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { requestSubscribeByScene } from '@/utils/subscribe'

const $theme = useThemeStore()
const weekDays = ['日', '一', '二', '三', '四', '五', '六']

const formatDate = (date: Date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

const parseDate = (value: string) => {
    const [year, month, day] = value.split('-').map((item) => Number(item))
    if (!year || !month || !day) {
        return new Date(NaN)
    }
    return new Date(year, month - 1, day)
}

const today = formatDate(new Date())
const isPastDate = (value: string) => {
    const target = parseDate(value)
    const todayDate = parseDate(today)
    return target < todayDate
}
const isTodayOrPast = (value: string) => {
    const target = parseDate(value)
    const todayDate = parseDate(today)
    return target <= todayDate
}

const staffId = ref(0)
const staffInfo = ref<any>(null)
const staffPackages = ref<any[]>([])
const selectedPackageId = ref<number | null>(null)
const currentYear = ref(new Date().getFullYear())
const currentMonth = ref(new Date().getMonth() + 1)
const calendarData = ref<any>({})
const selectedDate = ref('')
const recommendStaffList = ref<any[]>([])
const recommendLoading = ref(false)
const getPackageId = (pkg: any) => Number(pkg?.package_id || pkg?.id || 0)
const getPackageName = (pkg: any) => pkg?.name || pkg?.package?.name || '未命名套餐'

const selectedPackage = computed(() => {
    return staffPackages.value.find((pkg) => getPackageId(pkg) === selectedPackageId.value) || null
})

const selectedPackageName = computed(() => {
    return selectedPackage.value ? getPackageName(selectedPackage.value) : ''
})

const staffCategoryId = computed(() => {
    return Number(staffInfo.value?.category?.id || staffInfo.value?.category_id || 0)
})

const recommendCategoryName = computed(() => {
    return staffInfo.value?.category?.name || '同类型'
})

const selectedDay = computed(() => {
    if (!selectedDate.value) {
        return null
    }
    return calendarData.value.days?.[selectedDate.value] || null
})

const isAvailable = computed(() => {
    if (!selectedDay.value) {
        return false
    }
    return !isTodayOrPast(selectedDate.value) && !!selectedDay.value.is_available
})

const showRecommendSection = computed(() => {
    return !!selectedDate.value && !isAvailable.value
})

const showWaitlistButton = computed(() => {
    return !!selectedDate.value && !!selectedPackage.value && !isAvailable.value && !isTodayOrPast(selectedDate.value)
})

const canSubmit = computed(() => {
    return !!selectedPackage.value && !!selectedDate.value && !isTodayOrPast(selectedDate.value)
})

const currentPrice = computed(() => {
    if (!selectedPackage.value) {
        return '0.00'
    }
    return Number(getPackageBasePrice(selectedPackage.value) || 0).toFixed(2)
})

const getPackageBasePrice = (pkg: any) => {
    if (pkg?.price !== null && pkg?.price !== undefined && pkg?.price !== '') {
        return Number(pkg.price)
    }
    return Number(pkg?.package?.price || 0)
}

const getPackageOriginalPrice = (pkg: any) => {
    if (
        pkg?.original_price !== null &&
        pkg?.original_price !== undefined &&
        pkg?.original_price !== ''
    ) {
        return Number(pkg.original_price)
    }
    if (
        pkg?.package?.original_price !== null &&
        pkg?.package?.original_price !== undefined &&
        pkg?.package?.original_price !== ''
    ) {
        return Number(pkg.package.original_price)
    }
    return null
}

const pickerValue = computed(() => {
    return `${String(currentYear.value)}-${String(currentMonth.value).padStart(2, '0')}`
})

const firstDayOfMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value - 1, 1).getDay()
})

const calendarDays = computed(() => {
    const days = calendarData.value.days || {}
    return Object.values(days).map((day: any) => ({
        ...day,
        is_past: isTodayOrPast(day.date),
        schedules: day.schedules ? Object.values(day.schedules) : []
    }))
})

const buildScheduleParams = () => {
    return {
        staff_id: staffId.value,
        year: currentYear.value,
        month: currentMonth.value
    }
}

const fetchSchedule = async () => {
    if (!staffId.value) {
        return
    }
    try {
        const res = await getStaffSchedule(buildScheduleParams())
        calendarData.value = res
        if (!staffInfo.value && res.staff) {
            staffInfo.value = res.staff
        }
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '获取档期失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

const handleRecommendStaffClick = (item: any) => {
    if (!item?.id) {
        return
    }
    let url = `/packages/pages/schedule_calendar/schedule_calendar?staff_id=${item.id}`
    if (selectedDate.value) {
        url += `&date=${selectedDate.value}`
    }
    uni.navigateTo({ url })
}

const fetchRecommendStaff = async () => {
    if (!selectedDate.value || isAvailable.value || !staffCategoryId.value) {
        recommendStaffList.value = []
        return
    }
    try {
        recommendLoading.value = true
        const res = await getStaffList({
            page_no: 1,
            page_size: 6,
            sort: 'rating',
            category_id: staffCategoryId.value,
            date: selectedDate.value
        })
        const lists = Array.isArray(res?.lists) ? res.lists : []
        recommendStaffList.value = lists.filter(
            (item: any) => Number(item.id) !== Number(staffId.value)
        )
    } catch (e: any) {
        recommendStaffList.value = []
        console.error('获取推荐人员失败', e)
    } finally {
        recommendLoading.value = false
    }
}

const fetchStaffDetail = async () => {
    try {
        const res = await getStaffDetail({ id: staffId.value })
        staffInfo.value = res
        staffPackages.value = Array.isArray(res.packages) ? res.packages : []
        if (!selectedPackageId.value && staffPackages.value.length > 0) {
            const defaultPackage = staffPackages.value.find(
                (pkg: any) => Number(pkg.is_default) === 1
            )
            selectedPackageId.value = getPackageId(defaultPackage || staffPackages.value[0])
        }
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '获取人员信息失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

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

const toToday = () => {
    const now = new Date()
    currentYear.value = now.getFullYear()
    currentMonth.value = now.getMonth() + 1
    selectedDate.value = ''
    fetchSchedule()
}

const handleDateChange = (e: any) => {
    const date = new Date(e.detail.value)
    currentYear.value = date.getFullYear()
    currentMonth.value = date.getMonth() + 1
    selectedDate.value = ''
    fetchSchedule()
}

const handleDayClick = (day: any) => {
    if (day.is_past) {
        uni.showToast({
            title: isPastDate(day.date) ? '不能选择过去日期' : '当天不支持预约',
            icon: 'none'
        })
        return
    }

    selectedDate.value = day.date
    if (!day.is_available) {
        uni.showToast({ title: '该日期不可预约，可加入候补', icon: 'none' })
    }
}

const getStatusClass = (day: any) => {
    const schedules = Array.isArray(day?.schedules) ? day.schedules : []
    const statuses = schedules
        .map((item: any) => Number(item?.status))
        .filter((status: number) => Number.isFinite(status))

    // 存在明确状态时，按优先级显示
    if (statuses.length > 0) {
        if (statuses.includes(2)) return 'booked'
        if (statuses.includes(3)) return 'locked'
        if (statuses.includes(4)) return 'reserved'
        if (statuses.includes(1)) return 'available'
        if (statuses.includes(0)) return 'unavailable'
    }

    // 无档期记录时，以接口返回的可预约结果作为兜底展示
    if (!day?.is_past && day?.is_available) {
        return 'available'
    }
    return ''
}

const ensureReady = () => {
    const userStore = useUserStore()
    if (!userStore.isLogin) {
        uni.showToast({ title: '请先登录', icon: 'none' })
        setTimeout(() => {
            uni.navigateTo({ url: '/pages/login/login' })
        }, 1500)
        return false
    }

    if (!selectedDate.value) {
        uni.showToast({ title: '请选择日期', icon: 'none' })
        return false
    }

    if (!selectedPackage.value) {
        uni.showToast({ title: '请选择套餐', icon: 'none' })
        return false
    }

    return true
}

const handleJoinWaitlist = async () => {
    if (!ensureReady()) {
        return
    }
    if (selectedDate.value && isTodayOrPast(selectedDate.value)) {
        uni.showToast({ title: '当天不支持候补', icon: 'none' })
        return
    }

    try {
        try {
            await requestSubscribeByScene('waitlist_release')
        } catch (e) {
            // 订阅失败不影响候补
        }
        const payload: any = {
            staff_id: staffId.value,
            date: selectedDate.value,
            package_id: selectedPackageId.value
        }
        await joinWaitlist(payload)
        uni.showToast({ title: '已加入候补', icon: 'success' })
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加入候补失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

const handleDirectOrder = async () => {
    if (!ensureReady()) {
        return
    }
    if (selectedDate.value && isTodayOrPast(selectedDate.value)) {
        uni.showToast({ title: '当天不支持预约', icon: 'none' })
        return
    }

    if (!isAvailable.value) {
        uni.showToast({ title: '该日期不可预约', icon: 'none' })
        return
    }

    const orderUrl =
        `/packages/pages/order_confirm/order_confirm?staff_id=${staffId.value}` +
        `&package_id=${selectedPackageId.value}&date=${selectedDate.value}`
    uni.navigateTo({ url: orderUrl })
}

const selectPackage = (pkg: any) => {
    selectedPackageId.value = getPackageId(pkg)
}

watch([selectedDate, isAvailable, staffCategoryId], () => {
    if (!selectedDate.value || isAvailable.value) {
        recommendStaffList.value = []
        return
    }
    fetchRecommendStaff()
})

onLoad(async (options: any) => {
    staffId.value = parseInt(options.staff_id || '0')
    if (!staffId.value) {
        uni.showToast({ title: '工作人员不存在', icon: 'none' })
        setTimeout(() => {
            uni.navigateBack()
        }, 1500)
        return
    }

    if (options.date) {
        selectedDate.value = options.date
        const presetDate = new Date(options.date)
        if (!isNaN(presetDate.getTime())) {
            currentYear.value = presetDate.getFullYear()
            currentMonth.value = presetDate.getMonth() + 1
        }
    }

    if (options.package_id) {
        const presetPackageId = parseInt(options.package_id || '0')
        if (presetPackageId > 0) {
            selectedPackageId.value = presetPackageId
        }
    }

    await fetchStaffDetail()
    await fetchSchedule()
})
</script>

<style lang="scss" scoped>
.schedule-calendar {
    min-height: 100vh;
    background: linear-gradient(180deg, var(--color-primary-light-9) 0%, #f5f5f5 100%);
    padding-bottom: env(safe-area-inset-bottom);
}

/* 工作人员信息卡片 */
.staff-card {
    margin: 20rpx 24rpx;
    background: #ffffff;
    border-radius: 16rpx;
    padding: 20rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.staff-header {
    display: flex;
    align-items: flex-start;
    gap: 16rpx;
}

.staff-avatar {
    width: 80rpx;
    height: 80rpx;
    border-radius: 12rpx;
    flex-shrink: 0;
}

.staff-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6rpx;

    .info-row {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    .staff-name {
        font-size: 28rpx;
        font-weight: 700;
        color: var(--color-main);
    }

    .staff-rating {
        display: flex;
        align-items: center;
        gap: 4rpx;

        .rating-text {
            font-size: 24rpx;
            font-weight: 600;
            color: #ffd700;
        }
    }

    .staff-category {
        font-size: 24rpx;
        color: var(--color-muted);
    }

    .staff-experience {
        font-size: 22rpx;
        color: var(--color-primary);
        padding: 2rpx 8rpx;
        background: var(--color-primary-light-9);
        border-radius: 4rpx;
    }

    .staff-orders {
        font-size: 22rpx;
        color: var(--color-content);
    }
}

/* 日历选择区域 */
.calendar-section {
    margin: 0 24rpx 24rpx;
}

/* 月份选择器 */
.month-selector {
    background: #ffffff;
    border-radius: 16rpx 16rpx 0 0;
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
    background: #f9fafb;
    border-radius: 32rpx;
    transition: all 0.2s ease;
    flex-shrink: 0;

    &:active {
        transform: scale(0.9);
        background: #f0f0f0;
    }
}

.month-display {
    display: flex;
    align-items: center;
    gap: 12rpx;
    padding: 12rpx 32rpx;
    background: #f9fafb;
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
    background: #ffffff;
    padding: 0 24rpx 24rpx;
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
            color: #ffffff;
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
        background: #fff1f0;
    }

    &.is-holiday {
        background: #fffbe6;
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
        background: #52c41a;
    }

    &.booked {
        background: #faad14;
    }

    &.locked {
        background: #ff4d4f;
    }

    &.reserved {
        background: #722ed1;
    }

    &.unavailable {
        background: #bfbfbf;
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
    color: #ffffff;
}

.tag-lucky {
    background: #ff4d4f;
}

.tag-holiday {
    background: #faad14;
}

/* 图例说明 */
.legend-section {
    background: #ffffff;
    border-radius: 0 0 16rpx 16rpx;
    padding: 16rpx 24rpx;
    border-top: 1rpx solid #f0f0f0;
}

.legend-title {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--color-main);
    margin-bottom: 12rpx;
}

.legend-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12rpx;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.legend-dot {
    width: 16rpx;
    height: 16rpx;
    border-radius: 8rpx;
    flex-shrink: 0;

    &.available {
        background: #52c41a;
    }

    &.booked {
        background: #faad14;
    }

    &.locked {
        background: #ff4d4f;
    }

    &.reserved {
        background: #722ed1;
    }
}

.legend-text {
    font-size: 22rpx;
    color: var(--color-content);
}

/* 场次选择 */
/* 套餐选择 */
.package-section {
    margin: 0 24rpx 24rpx;
    background: #ffffff;
    border-radius: 16rpx;
    padding: 32rpx;
}

.package-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.package-card {
    background: #f9fafb;
    border: 2rpx solid #f0f0f0;
    border-radius: 16rpx;
    padding: 24rpx;
    transition: all 0.2s ease;
}

.package-card.active {
    border-color: var(--color-primary);
    background: var(--color-primary-light-9);
}

.package-main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.package-info {
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    flex: 1;
}

.package-name {
    font-size: 28rpx;
    font-weight: 600;
    color: var(--color-main);
}

.package-type {
    font-size: 22rpx;
    color: var(--color-muted);
}

.package-price-wrapper {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4rpx;
}

.original-price {
    .original-price-text {
        font-size: 22rpx;
        color: var(--color-muted);
        text-decoration: line-through;
    }
}

.package-price {
    font-size: 28rpx;
    font-weight: 700;
    color: var(--color-primary);
}

.package-slots {
    margin-top: 12rpx;
    display: flex;
    flex-wrap: wrap;
    gap: 8rpx;
}

.slot-tag {
    padding: 4rpx 12rpx;
    border-radius: 8rpx;
    background: #ffffff;
    border: 1rpx solid #eaeaea;
    font-size: 20rpx;
    color: var(--color-content);
}

.time-slot-section {
    margin: 0 24rpx 24rpx;
    background: #ffffff;
    border-radius: 16rpx;
    padding: 32rpx;
}

/* 推荐人员 */
.recommend-section {
    margin: 0 24rpx 24rpx;
    background: #ffffff;
    border-radius: 16rpx;
    padding: 32rpx;
}

.recommend-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.recommend-card {
    display: flex;
    align-items: center;
    gap: 16rpx;
    padding: 20rpx;
    background: #f9fafb;
    border: 2rpx solid #f0f0f0;
    border-radius: 16rpx;
    transition: all 0.2s ease;
}

.recommend-card:active {
    transform: scale(0.98);
}

.recommend-avatar {
    width: 96rpx;
    height: 96rpx;
    border-radius: 12rpx;
    flex-shrink: 0;
}

.recommend-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.recommend-name-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.recommend-name {
    font-size: 28rpx;
    font-weight: 600;
    color: var(--color-main);
}

.recommend-rating {
    display: flex;
    align-items: center;
    gap: 4rpx;
}

.recommend-rating .rating-text {
    font-size: 22rpx;
    font-weight: 600;
    color: #ffd700;
}

.recommend-meta {
    display: flex;
    align-items: center;
    gap: 12rpx;
    font-size: 22rpx;
    color: var(--color-muted);
}

.recommend-exp {
    font-size: 20rpx;
    color: var(--color-primary);
    background: var(--color-primary-light-9);
    padding: 2rpx 8rpx;
    border-radius: 6rpx;
}

.recommend-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8rpx;
}

.recommend-tag {
    font-size: 20rpx;
    color: var(--color-content);
    padding: 2rpx 8rpx;
    border-radius: 8rpx;
    background: #ffffff;
    border: 1rpx solid #eaeaea;
}

.recommend-action {
    display: flex;
    align-items: center;
    gap: 4rpx;
    font-size: 22rpx;
    color: var(--color-muted);
}

.recommend-loading,
.recommend-empty {
    text-align: center;
    padding: 24rpx 0;
    font-size: 24rpx;
    color: var(--color-muted);
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
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.slot-item.is-unavailable {
    opacity: 0.5;
    border-style: dashed;
}

.slot-price {
    font-size: 22rpx;
    margin-left: auto;
}

.slot-item.is-selected .slot-price {
    color: #ffffff;
}

.slot-item {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 16rpx;
    padding: 20rpx 24rpx;
    background: #f9fafb;
    border: 2rpx solid #f0f0f0;
    border-radius: 16rpx;
    transition: all 0.2s ease;

    &.is-selected {
        color: #ffffff;
    }

    &:active {
        transform: scale(0.95);
    }

    .slot-label {
        font-size: 26rpx;
        font-weight: 500;
    }
}

.slot-item.is-selected .slot-label {
    color: #ffffff;
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
    background: #ffffff;
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
        color: #ffffff;
    }
}

.btn-waitlist {
    background: linear-gradient(135deg, #ff9500 0%, #ff9500 100%);
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
