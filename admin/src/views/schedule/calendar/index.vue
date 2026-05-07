<template>
    <admin-page-shell class="schedule-calendar" title="档期日历">
        <!-- 顶部筛选 -->
        <search-panel class="mb-4">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="工作人员">
                    <el-select
                        v-model="queryParams.staff_id"
                        placeholder="请选择"
                        clearable
                        style="width: 200px"
                        @change="fetchCalendar"
                    >
                        <el-option v-for="item in staffList" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="年月">
                    <el-date-picker
                        v-model="currentMonth"
                        type="month"
                        placeholder="选择月份"
                        format="YYYY年MM月"
                        value-format="YYYY-MM"
                        @change="handleMonthChange"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="fetchCalendar">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                    <el-button type="success" @click="handleBatchSet">批量设置</el-button>
                </el-form-item>
            </el-form>
        </search-panel>

        <!-- 统计卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card shadow="never">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                            <el-icon class="text-blue-500 text-xl"><Calendar /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">总档期</div>
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
                            <div class="text-gray-500 text-sm">已预约</div>
                            <div class="text-2xl font-bold text-orange-500">{{ statistics.booked || 0 }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="never">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
                            <el-icon class="text-red-500 text-xl"><Lock /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">已锁定/预留</div>
                            <div class="text-2xl font-bold text-red-500">{{ (statistics.locked || 0) + (statistics.reserved || 0) }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 日历视图 -->
        <el-card class="!border-none" shadow="never">
            <template #header>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold">档期日历</span>
                    <div class="flex items-center">
                        <el-button-group>
                            <el-button @click="prevMonth"><el-icon><ArrowLeft /></el-icon></el-button>
                            <el-button @click="toToday">今天</el-button>
                            <el-button @click="nextMonth"><el-icon><ArrowRight /></el-icon></el-button>
                        </el-button-group>
                    </div>
                </div>
            </template>

            <!-- 星期标题 -->
            <div class="grid grid-cols-7 gap-1 mb-2">
                <div v-for="week in weekDays" :key="week" class="text-center py-2 bg-gray-100 font-bold">
                    {{ week }}
                </div>
            </div>

            <!-- 日历格子 -->
            <div class="grid grid-cols-7 gap-1">
                <!-- 填充空白格子 -->
                <div v-for="n in firstDayOfMonth" :key="'empty-' + n" class="h-24 bg-gray-50"></div>
                
                <!-- 日期格子 -->
                <div
                    v-for="day in calendarDays"
                    :key="day.date"
                    class="h-24 border p-1 cursor-pointer hover:bg-blue-50 transition-colors"
                    :class="{
                        'bg-blue-50': day.date === today,
                        'bg-red-50': day.calendar?.is_lucky_day,
                        'bg-yellow-50': day.calendar?.is_holiday
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
                    
                    <!-- 档期状态 -->
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
                        
                        <!-- 标记 -->
                        <div class="flex gap-1 flex-wrap">
                            <el-tag v-if="day.calendar?.is_lucky_day" type="danger" size="small">吉</el-tag>
                            <el-tag v-if="day.calendar?.is_holiday" type="warning" size="small">假</el-tag>
                        </div>
                    </div>
                </div>
            </div>
        </el-card>

        <!-- 日期详情弹窗 -->
        <el-dialog v-model="dayDialogVisible" :title="selectedDay?.date + ' 档期详情'" width="600px">
            <div v-if="selectedDay">
                <!-- 黄历信息 -->
                <div v-if="selectedDay.calendar" class="mb-4 p-3 bg-gray-50 rounded">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex gap-4 flex-wrap">
                                <span v-if="selectedDay.calendar.lunar_date">农历: {{ selectedDay.calendar.lunar_date }}</span>
                                <el-tag v-if="selectedDay.calendar.is_lucky_day" type="danger">吉日</el-tag>
                                <el-tag v-if="selectedDay.calendar.is_holiday" type="warning">{{ selectedDay.calendar.holiday_name || '节假日' }}</el-tag>
                                <span v-if="selectedDay.calendar.congestion_level_text">
                                    拥堵度: {{ selectedDay.calendar.congestion_level_text }}
                                </span>
                            </div>
                            <div v-if="selectedDay.calendar.lucky_events" class="mt-2 text-sm">
                                <span class="text-green-600">宜:</span> {{ selectedDay.calendar.lucky_events }}
                            </div>
                            <div v-if="selectedDay.calendar.unlucky_events" class="mt-1 text-sm">
                                <span class="text-red-600">忌:</span> {{ selectedDay.calendar.unlucky_events }}
                            </div>
                            <div v-if="selectedDay.calendar.remark" class="mt-1 text-sm text-gray-500">
                                备注: {{ selectedDay.calendar.remark }}
                            </div>
                        </div>
                        <el-button type="primary" link @click="openCalendarEdit">编辑吉日</el-button>
                    </div>
                </div>

                <!-- 档期操作 -->
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

                <!-- 当前档期列表 -->
                <div v-if="selectedDay.schedules && selectedDay.schedules.length > 0" class="mt-4">
                    <div class="font-bold mb-2">当前档期:</div>
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
                <el-button type="primary" :disabled="!queryParams.staff_id" @click="handleSetDayStatus">确定设置</el-button>
            </template>
        </el-dialog>

        <!-- 吉日设置弹窗 -->
        <el-dialog v-model="calendarDialogVisible" title="编辑吉日设置" width="560px">
            <el-form :model="calendarForm" label-width="100px">
                <el-form-item label="日期">
                    <el-date-picker
                        v-model="calendarForm.event_date"
                        type="date"
                        value-format="YYYY-MM-DD"
                        style="width: 100%"
                        disabled
                    />
                </el-form-item>
                <el-form-item label="农历">
                    <el-input v-model="calendarForm.lunar_date" maxlength="20" placeholder="如：腊月初八" />
                </el-form-item>
                <el-form-item label="吉日">
                    <el-switch v-model="calendarForm.is_lucky_day" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="宜">
                    <el-input v-model="calendarForm.lucky_events" maxlength="255" placeholder="如：嫁娶,订盟,纳采" />
                </el-form-item>
                <el-form-item label="忌">
                    <el-input v-model="calendarForm.unlucky_events" maxlength="255" placeholder="如：开市,动土" />
                </el-form-item>
                <el-form-item label="节假日">
                    <div class="flex w-full gap-3">
                        <el-switch v-model="calendarForm.is_holiday" :active-value="1" :inactive-value="0" />
                        <el-input v-model="calendarForm.holiday_name" maxlength="50" placeholder="节假日名称" />
                    </div>
                </el-form-item>
                <el-form-item label="拥堵等级">
                    <el-select v-model="calendarForm.congestion_level" style="width: 100%">
                        <el-option
                            v-for="item in congestionOptions"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="calendarForm.remark" type="textarea" :rows="3" maxlength="255" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="calendarDialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="calendarSubmitLoading" @click="submitCalendarEdit">
                    保存
                </el-button>
            </template>
        </el-dialog>

        <!-- 批量设置弹窗 -->
        <el-dialog v-model="batchDialogVisible" title="批量设置档期" width="600px">
            <el-form :model="batchForm" label-width="100px">
                <el-form-item label="工作人员" required>
                    <el-select v-model="batchForm.staff_ids" multiple style="width: 100%">
                        <el-option v-for="item in staffList" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </el-form-item>
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
                        <el-checkbox :label="0">周日</el-checkbox>
                        <el-checkbox :label="1">周一</el-checkbox>
                        <el-checkbox :label="2">周二</el-checkbox>
                        <el-checkbox :label="3">周三</el-checkbox>
                        <el-checkbox :label="4">周四</el-checkbox>
                        <el-checkbox :label="5">周五</el-checkbox>
                        <el-checkbox :label="6">周六</el-checkbox>
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

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Calendar, CircleCheck, Ticket, Lock, ArrowLeft, ArrowRight } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
    calendarEventCongestionLevelOptions,
    calendarEventSave,
    scheduleBatchSet,
    scheduleMonthCalendar,
    scheduleSetStatus,
    scheduleStatistics,
    scheduleUnlock
} from '@/api/schedule'
import { staffAll } from '@/api/staff'

interface ScheduleRow {
    id: number
    status: number
    remark?: string
}

interface CalendarMeta {
    is_lucky_day?: boolean | number
    is_holiday?: boolean | number
    lunar_date?: string
    holiday_name?: string
    lucky_events?: string
    unlucky_events?: string
    congestion_level?: number
    congestion_level_text?: string
    remark?: string
}

interface CalendarDay {
    date: string
    schedules: ScheduleRow[]
    calendar?: CalendarMeta
}

interface CalendarData {
    days?: Record<string, CalendarDay>
}

const weekDays = ['日', '一', '二', '三', '四', '五', '六']
const today = formatLocalDate()

const queryParams = ref({
    staff_id: '' as any
})

const currentMonth = ref(formatLocalMonth())
const staffList = ref<any[]>([])
const calendarData = ref<CalendarData>({})
const statistics = ref<any>({})

const dayDialogVisible = ref(false)
const selectedDay = ref<CalendarDay | null>(null)
const dayForm = ref({
    status: 1,
    remark: ''
})

const batchDialogVisible = ref(false)
const batchLoading = ref(false)
const batchForm = ref({
    staff_ids: [] as number[],
    dateRange: [] as string[],
    status: 1,
    price: 0,
    skip_rest_days: [] as number[]
})

const calendarDialogVisible = ref(false)
const calendarSubmitLoading = ref(false)
const congestionOptions = ref<any[]>([])
const calendarForm = ref({
    event_date: '',
    lunar_date: '',
    is_lucky_day: 0,
    lucky_events: '',
    unlucky_events: '',
    is_holiday: 0,
    holiday_name: '',
    congestion_level: 0,
    remark: ''
})

// 计算月份第一天是星期几
const firstDayOfMonth = computed(() => {
    const [year, month] = currentMonth.value.split('-').map(Number)
    return new Date(year, month - 1, 1).getDay()
})

// 计算日历天数
const calendarDays = computed(() => {
    const days = calendarData.value.days
    return days ? Object.values(days) : []
})

// 获取工作人员列表
const fetchStaffList = async () => {
    const res = await staffAll()
    staffList.value = res || []
}

// 获取日历数据
const fetchCalendar = async () => {
    const [year, month] = currentMonth.value.split('-').map(Number)
    const res = await scheduleMonthCalendar({
        staff_id: queryParams.value.staff_id,
        year,
        month
    })
    calendarData.value = res

    // 获取统计
    const statsRes = await scheduleStatistics({
        staff_id: queryParams.value.staff_id,
        year,
        month
    })
    statistics.value = statsRes
}

// 月份切换
const handleMonthChange = () => {
    fetchCalendar()
}

const prevMonth = () => {
    const [year, month] = currentMonth.value.split('-').map(Number)
    if (month === 1) {
        currentMonth.value = `${year - 1}-12`
    } else {
        currentMonth.value = `${year}-${String(month - 1).padStart(2, '0')}`
    }
    fetchCalendar()
}

const nextMonth = () => {
    const [year, month] = currentMonth.value.split('-').map(Number)
    if (month === 12) {
        currentMonth.value = `${year + 1}-01`
    } else {
        currentMonth.value = `${year}-${String(month + 1).padStart(2, '0')}`
    }
    fetchCalendar()
}

const toToday = () => {
    currentMonth.value = formatLocalMonth()
    fetchCalendar()
}

const handleReset = () => {
    queryParams.value.staff_id = ''
    currentMonth.value = formatLocalMonth()
    fetchCalendar()
}

// 点击日期
const handleDayClick = (day: CalendarDay) => {
    const currentSchedule = getPrimarySchedule(day)
    selectedDay.value = day
    dayForm.value = {
        status: currentSchedule?.status ?? 1,
        remark: currentSchedule?.remark ?? ''
    }
    dayDialogVisible.value = true
}

// 打开吉日编辑
const openCalendarEdit = () => {
    if (!selectedDay.value) return
    const calendar = selectedDay.value.calendar || {}
    calendarForm.value = {
        event_date: selectedDay.value.date,
        lunar_date: calendar.lunar_date || '',
        is_lucky_day: calendar.is_lucky_day ? 1 : 0,
        lucky_events: calendar.lucky_events || '',
        unlucky_events: calendar.unlucky_events || '',
        is_holiday: calendar.is_holiday ? 1 : 0,
        holiday_name: calendar.holiday_name || '',
        congestion_level: Number(calendar.congestion_level || 0),
        remark: calendar.remark || ''
    }
    calendarDialogVisible.value = true
}

// 保存吉日设置
const submitCalendarEdit = async () => {
    calendarSubmitLoading.value = true
    try {
        await calendarEventSave(calendarForm.value)
        ElMessage.success('保存成功')
        calendarDialogVisible.value = false
        await fetchCalendar()
        const latestDay = calendarData.value.days?.[calendarForm.value.event_date]
        if (latestDay) {
            selectedDay.value = latestDay
        }
    } finally {
        calendarSubmitLoading.value = false
    }
}

// 设置档期状态
const handleSetDayStatus = async () => {
    if (!selectedDay.value) return
    if (!queryParams.value.staff_id) {
        ElMessage.warning('请先选择工作人员')
        return
    }
    
    await scheduleSetStatus({
        staff_id: queryParams.value.staff_id,
        date: selectedDay.value.date,
        ...dayForm.value
    })
    ElMessage.success('设置成功')
    dayDialogVisible.value = false
    fetchCalendar()
}

// 释放锁定
const handleUnlock = async (row: any) => {
    await ElMessageBox.confirm('确定要释放该档期的锁定吗?', '提示')
    await scheduleUnlock({ id: row.id })
    ElMessage.success('释放成功')
    fetchCalendar()
}

// 批量设置
const handleBatchSet = () => {
    batchForm.value = {
        staff_ids: [],
        dateRange: [],
        status: 1,
        price: 0,
        skip_rest_days: []
    }
    batchDialogVisible.value = true
}

const handleBatchSubmit = async () => {
    if (batchForm.value.staff_ids.length === 0) {
        ElMessage.warning('请选择工作人员')
        return
    }
    if (!batchForm.value.dateRange || batchForm.value.dateRange.length !== 2) {
        ElMessage.warning('请选择日期范围')
        return
    }

    batchLoading.value = true
    try {
        await scheduleBatchSet({
            staff_ids: batchForm.value.staff_ids,
            start_date: batchForm.value.dateRange[0],
            end_date: batchForm.value.dateRange[1],
            status: batchForm.value.status,
            price: batchForm.value.price,
            skip_rest_days: batchForm.value.skip_rest_days
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

type StatusTagType = 'success' | 'warning' | 'info' | 'primary' | 'danger'

const getStatusType = (status: number): StatusTagType => {
    const map: Record<number, StatusTagType> = {
        0: 'info',
        1: 'success',
        2: 'warning',
        3: 'danger',
        4: 'danger'
    }
    return map[status] || 'info'
}

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'bg-gray-200 text-gray-600',
        1: 'bg-green-100 text-green-600',
        2: 'bg-orange-100 text-orange-600',
        3: 'bg-red-100 text-red-600',
        4: 'bg-purple-100 text-purple-600'
    }
    return map[status] || ''
}

const getPrimarySchedule = (day: CalendarDay) => {
    return Array.isArray(day.schedules) && day.schedules.length > 0 ? day.schedules[0] as any : null
}

function formatLocalDate(date = new Date()) {
    const year = date.getFullYear()
    const month = `${date.getMonth() + 1}`.padStart(2, '0')
    const day = `${date.getDate()}`.padStart(2, '0')
    return `${year}-${month}-${day}`
}

function formatLocalMonth(date = new Date()) {
    const year = date.getFullYear()
    const month = `${date.getMonth() + 1}`.padStart(2, '0')
    return `${year}-${month}`
}

onMounted(() => {
    fetchStaffList()
    fetchCalendar()
    calendarEventCongestionLevelOptions().then((res) => {
        congestionOptions.value = res || []
    })
})
</script>

<style scoped>
.schedule-calendar {
    padding: 4px 0;
}
</style>
