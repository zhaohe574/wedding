<template>
    <admin-page-shell class="staff-center-calendar" title="我的档期">
        <search-panel>
            <el-form class="mb-[-16px]" :inline="true">
                <el-form-item label="年月">
                    <el-date-picker
                        v-model="currentMonth"
                        type="month"
                        placeholder="选择月份"
                        format="YYYY年MM月"
                        value-format="YYYY-MM"
                        @change="fetchCalendar"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="fetchCalendar">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                    <el-button type="success" @click="handleBatchSet">批量设置</el-button>
                    <el-button plain @click="goToBooking">待确认预约项</el-button>
                    <el-button plain @click="goToWaitlist">候补列表</el-button>
                </el-form-item>
            </el-form>
        </search-panel>

        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card shadow="never">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                            <el-icon class="text-blue-500 text-xl"><Calendar /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">本月档期</div>
                            <div class="text-2xl font-bold">{{ statistics.total || 0 }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="never">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                            <el-icon class="text-green-500 text-xl"><CircleCheck /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">可预约</div>
                            <div class="text-2xl font-bold text-green-500">{{ statistics.available || 0 }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="never">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center mr-4">
                            <el-icon class="text-orange-500 text-xl"><Ticket /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">已占用</div>
                            <div class="text-2xl font-bold text-orange-500">{{ statistics.booked || 0 }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="never" class="cursor-pointer" @click="goToWaitlist">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center mr-4">
                            <el-icon class="text-purple-500 text-xl"><Bell /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">待跟进候补</div>
                            <div class="text-2xl font-bold text-purple-500">{{ waitlistPendingCount }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <el-card class="!border-none" shadow="never">
            <template #header>
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-lg font-bold">我的档期</span>
                        <div class="text-sm text-gray-400 mt-1">点击具体日期维护可预约、内部预留与锁定释放。</div>
                    </div>
                    <el-button-group>
                        <el-button @click="prevMonth"><el-icon><ArrowLeft /></el-icon></el-button>
                        <el-button @click="toToday">今天</el-button>
                        <el-button @click="nextMonth"><el-icon><ArrowRight /></el-icon></el-button>
                    </el-button-group>
                </div>
            </template>

            <div class="grid grid-cols-7 gap-1 mb-2">
                <div v-for="week in weekDays" :key="week" class="text-center py-2 bg-gray-100 font-bold">
                    {{ week }}
                </div>
            </div>

            <div class="grid grid-cols-7 gap-1">
                <div v-for="n in firstDayOfMonth" :key="`empty-${n}`" class="h-24 bg-gray-50"></div>

                <div
                    v-for="day in calendarDays"
                    :key="day.date"
                    class="h-24 border p-1 cursor-pointer hover:bg-blue-50 transition-colors"
                    :class="{
                        'bg-blue-50': day.date === today,
                        'bg-red-50': day.calendar?.is_lucky_day,
                        'bg-yellow-50': day.calendar?.is_holiday,
                    }"
                    @click="handleDayClick(day)"
                >
                    <div class="flex justify-between items-start">
                        <span class="font-bold" :class="{ 'text-red-500': day.calendar?.is_lucky_day }">
                            {{ day.date.split('-')[2] }}
                        </span>
                        <span v-if="day.calendar?.lunar_date" class="text-xs text-gray-400">
                            {{ day.calendar.lunar_date }}
                        </span>
                    </div>

                    <div class="mt-1 space-y-1">
                        <template v-if="day.schedules && day.schedules.length > 0">
                            <div
                                v-for="schedule in day.schedules.slice(0, 2)"
                                :key="schedule.id"
                                class="text-xs px-1 py-0.5 rounded truncate"
                                :class="getStatusClass(schedule.status)"
                            >
                                {{ getStatusText(schedule.status) }}
                            </div>
                            <div v-if="day.schedules.length > 2" class="text-xs text-gray-400">
                                +{{ day.schedules.length - 2 }} 更多
                            </div>
                        </template>

                        <div class="flex gap-1 flex-wrap">
                            <el-tag v-if="day.calendar?.is_lucky_day" type="danger" size="small">吉</el-tag>
                            <el-tag v-if="day.calendar?.is_holiday" type="warning" size="small">假</el-tag>
                        </div>
                    </div>
                </div>
            </div>
        </el-card>

        <el-dialog v-model="dayDialogVisible" :title="`${selectedDay?.date || ''} 档期设置`" width="600px">
            <div v-if="selectedDay">
                <div v-if="selectedDay.calendar" class="mb-4 p-3 bg-gray-50 rounded">
                    <div class="flex gap-4">
                        <span v-if="selectedDay.calendar.lunar_date">农历：{{ selectedDay.calendar.lunar_date }}</span>
                        <el-tag v-if="selectedDay.calendar.is_lucky_day" type="danger">吉日</el-tag>
                        <el-tag v-if="selectedDay.calendar.is_holiday" type="warning">{{ selectedDay.calendar.holiday_name }}</el-tag>
                    </div>
                    <div v-if="selectedDay.calendar.lucky_events" class="mt-2 text-sm">
                        <span class="text-green-600">宜：</span>{{ selectedDay.calendar.lucky_events }}
                    </div>
                    <div v-if="selectedDay.calendar.unlucky_events" class="mt-1 text-sm">
                        <span class="text-red-600">忌：</span>{{ selectedDay.calendar.unlucky_events }}
                    </div>
                </div>

                <el-form :model="dayForm" label-width="80px">
                    <el-form-item label="状态">
                        <el-select v-model="dayForm.status" style="width: 100%">
                            <el-option label="不可用" :value="0" />
                            <el-option label="可预约" :value="1" />
                            <el-option label="内部预留" :value="4" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="备注">
                        <el-input v-model="dayForm.remark" type="textarea" :rows="2" />
                    </el-form-item>
                </el-form>

                <div v-if="selectedDay.schedules && selectedDay.schedules.length > 0" class="mt-4">
                    <div class="font-bold mb-2">当前档期：</div>
                    <el-table :data="selectedDay.schedules" size="small">
                        <el-table-column prop="status" label="状态">
                            <template #default="{ row }">
                                <el-tag :type="getStatusType(row.status)" size="small">
                                    {{ getStatusText(row.status) }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="120">
                            <template #default="{ row }">
                                <el-button
                                    v-if="row.status === 3 || row.status === 4"
                                    type="primary"
                                    size="small"
                                    link
                                    @click="handleUnlock(row)"
                                >
                                    释放
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
            </div>
            <template #footer>
                <el-button @click="dayDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSetDayStatus">确定设置</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="batchDialogVisible" title="批量设置档期" width="600px">
            <el-form :model="batchForm" label-width="100px">
                <el-form-item label="日期范围" required>
                    <el-date-picker
                        v-model="batchForm.dateRange"
                        type="daterange"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                        style="width: 100%"
                    />
                </el-form-item>
                <el-form-item label="设置状态" required>
                    <el-select v-model="batchForm.status" style="width: 100%">
                        <el-option label="不可用" :value="0" />
                        <el-option label="可预约" :value="1" />
                        <el-option label="内部预留" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item label="特价(可选)">
                    <el-input-number v-model="batchForm.price" :min="0" :precision="2" style="width: 100%" />
                </el-form-item>
                <el-form-item label="跳过休息日">
                    <el-checkbox-group v-model="batchForm.skip_rest_days">
                        <el-checkbox :value="0">周日</el-checkbox>
                        <el-checkbox :value="1">周一</el-checkbox>
                        <el-checkbox :value="2">周二</el-checkbox>
                        <el-checkbox :value="3">周三</el-checkbox>
                        <el-checkbox :value="4">周四</el-checkbox>
                        <el-checkbox :value="5">周五</el-checkbox>
                        <el-checkbox :value="6">周六</el-checkbox>
                    </el-checkbox-group>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="batchDialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="batchLoading" @click="handleBatchSubmit">确定</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffCenterCalendar">
import { computed, onMounted, ref } from 'vue'
import { ArrowLeft, ArrowRight, Bell, Calendar, CircleCheck, Ticket } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useRouter } from 'vue-router'
import { getRoutePath } from '@/router'
import {
    myCalendar,
    myCalendarBatchSet,
    myCalendarSetStatus,
    myCalendarStatistics,
    myCalendarUnlock,
    myWaitlistStatistics,
} from '@/api/staff-center'

interface CalendarDay {
    date: string
    calendar?: Record<string, any>
    schedules?: Array<Record<string, any>>
}

const router = useRouter()
const weekDays = ['日', '一', '二', '三', '四', '五', '六']
const today = new Date().toISOString().split('T')[0]

const bookingPath = computed(() => getRoutePath('ops.booking/myBookings') || '/staff-center/booking')
const waitlistPath = computed(() => getRoutePath('ops.waitlist/myWaitlist') || '/staff-center/waitlist')

const currentMonth = ref(new Date().toISOString().slice(0, 7))
const calendarData = ref<Record<string, unknown>>({})
const statistics = ref<Record<string, unknown>>({})
const waitlistStatistics = ref<Record<string, unknown>>({})

const dayDialogVisible = ref(false)
const selectedDay = ref<CalendarDay | null>(null)
const dayForm = ref({
    status: 1,
    remark: '',
})

const batchDialogVisible = ref(false)
const batchLoading = ref(false)
const batchForm = ref({
    dateRange: [] as string[],
    status: 1,
    price: 0,
    skip_rest_days: [] as number[],
})

const waitlistPendingCount = computed(() => Number(waitlistStatistics.value.waiting || 0))

const firstDayOfMonth = computed(() => {
    const [year, month] = currentMonth.value.split('-').map(Number)
    return new Date(year, month - 1, 1).getDay()
})

const calendarDays = computed(() => {
    const days = calendarData.value.days
    return Array.isArray(days) ? (days as CalendarDay[]) : (Object.values(days || {}) as CalendarDay[])
})

const fetchCalendar = async () => {
    const [year, month] = currentMonth.value.split('-').map(Number)
    const [calendarRes, statsRes, waitlistRes] = await Promise.all([
        myCalendar({ year, month }),
        myCalendarStatistics({ year, month }),
        myWaitlistStatistics(),
    ])
    calendarData.value = (calendarRes || {}) as Record<string, unknown>
    statistics.value = (statsRes || {}) as Record<string, unknown>
    waitlistStatistics.value = (waitlistRes || {}) as Record<string, unknown>
}

const prevMonth = () => {
    const [year, month] = currentMonth.value.split('-').map(Number)
    currentMonth.value = month === 1 ? `${year - 1}-12` : `${year}-${String(month - 1).padStart(2, '0')}`
    fetchCalendar()
}

const nextMonth = () => {
    const [year, month] = currentMonth.value.split('-').map(Number)
    currentMonth.value = month === 12 ? `${year + 1}-01` : `${year}-${String(month + 1).padStart(2, '0')}`
    fetchCalendar()
}

const toToday = () => {
    currentMonth.value = new Date().toISOString().slice(0, 7)
    fetchCalendar()
}

const handleReset = () => {
    currentMonth.value = new Date().toISOString().slice(0, 7)
    fetchCalendar()
}

const handleDayClick = (day: CalendarDay) => {
    selectedDay.value = day
    dayForm.value = {
        status: 1,
        remark: '',
    }
    dayDialogVisible.value = true
}

const handleSetDayStatus = async () => {
    if (!selectedDay.value) return
    await myCalendarSetStatus({
        date: selectedDay.value.date,
        ...dayForm.value,
    })
    ElMessage.success('设置成功')
    dayDialogVisible.value = false
    fetchCalendar()
}

const handleUnlock = async (row: Record<string, unknown>) => {
    await ElMessageBox.confirm('确定要释放该档期的锁定吗？', '提示')
    await myCalendarUnlock({ id: row.id })
    ElMessage.success('释放成功')
    fetchCalendar()
}

const handleBatchSet = () => {
    batchForm.value = {
        dateRange: [],
        status: 1,
        price: 0,
        skip_rest_days: [],
    }
    batchDialogVisible.value = true
}

const handleBatchSubmit = async () => {
    if (!batchForm.value.dateRange || batchForm.value.dateRange.length !== 2) {
        ElMessage.warning('请选择日期范围')
        return
    }
    batchLoading.value = true
    try {
        await myCalendarBatchSet({
            start_date: batchForm.value.dateRange[0],
            end_date: batchForm.value.dateRange[1],
            status: batchForm.value.status,
            price: batchForm.value.price,
            skip_rest_days: batchForm.value.skip_rest_days,
        })
        ElMessage.success('批量设置成功')
        batchDialogVisible.value = false
        fetchCalendar()
    } finally {
        batchLoading.value = false
    }
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = { 0: '不可用', 1: '可预约', 2: '已预约', 3: '已锁定', 4: '内部预留' }
    return map[status] || '未知'
}

const getStatusType = (status: number): 'info' | 'success' | 'warning' | 'danger' | 'primary' => {
    const map: Record<number, 'info' | 'success' | 'warning' | 'danger' | 'primary'> = {
        0: 'info',
        1: 'success',
        2: 'warning',
        3: 'danger',
        4: 'danger',
    }
    return map[status] || 'info'
}

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'bg-gray-200 text-gray-600',
        1: 'bg-green-100 text-green-600',
        2: 'bg-orange-100 text-orange-600',
        3: 'bg-red-100 text-red-600',
        4: 'bg-yellow-100 text-yellow-700',
    }
    return map[status] || ''
}

const goToBooking = () => {
    router.push(bookingPath.value)
}

const goToWaitlist = () => {
    router.push(waitlistPath.value)
}

onMounted(() => {
    fetchCalendar()
})
</script>
