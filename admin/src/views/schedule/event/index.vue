<template>
    <div class="calendar-event">
        <el-card class="!border-none mb-4" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="日期范围">
                    <el-date-picker
                        v-model="queryParams.dateRange"
                        type="daterange"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                        style="width: 240px"
                    />
                </el-form-item>
                <el-form-item label="吉日">
                    <el-select v-model="queryParams.is_lucky_day" clearable style="width: 100px">
                        <el-option label="是" :value="1" />
                        <el-option label="否" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="节假日">
                    <el-select v-model="queryParams.is_holiday" clearable style="width: 100px">
                        <el-option label="是" :value="1" />
                        <el-option label="否" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="fetchList">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                    <el-button type="success" @click="handleAdd">添加事件</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none" shadow="never">
            <el-table :data="eventList" v-loading="loading">
                <el-table-column prop="event_date" label="日期" width="120" />
                <el-table-column prop="lunar_date" label="农历" width="100" />
                <el-table-column label="吉日" width="80">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_lucky_day" type="danger">吉</el-tag>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column prop="lucky_events" label="宜" min-width="150" show-overflow-tooltip />
                <el-table-column prop="unlucky_events" label="忌" min-width="150" show-overflow-tooltip />
                <el-table-column label="节假日" width="120">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_holiday" type="warning">{{ row.holiday_name }}</el-tag>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column prop="congestion_level_desc" label="拥堵等级" width="100">
                    <template #default="{ row }">
                        <el-tag 
                            :type="getCongestionType(row.congestion_level)"
                            size="small"
                        >
                            {{ row.congestion_level_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" link @click="handleEdit(row)">编辑</el-button>
                        <el-button type="danger" size="small" link @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <el-pagination
                    v-model:current-page="pager.page_no"
                    v-model:page-size="pager.page_size"
                    :total="pager.total"
                    :page-sizes="[15, 30, 50, 100]"
                    layout="total, sizes, prev, pager, next, jumper"
                    @size-change="fetchList"
                    @current-change="fetchList"
                />
            </div>
        </el-card>

        <!-- 添加/编辑弹窗 -->
        <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑事件' : '添加事件'" width="600px">
            <el-form :model="formData" :rules="formRules" ref="formRef" label-width="100px">
                <el-form-item label="日期" prop="event_date">
                    <el-date-picker
                        v-model="formData.event_date"
                        type="date"
                        placeholder="选择日期"
                        value-format="YYYY-MM-DD"
                        style="width: 100%"
                    />
                </el-form-item>
                <el-form-item label="农历日期">
                    <el-input v-model="formData.lunar_date" placeholder="如: 正月初一" />
                </el-form-item>
                <el-form-item label="是否吉日">
                    <el-switch v-model="formData.is_lucky_day" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="宜">
                    <el-input v-model="formData.lucky_events" placeholder="逗号分隔，如: 嫁娶,订盟,纳采" />
                </el-form-item>
                <el-form-item label="忌">
                    <el-input v-model="formData.unlucky_events" placeholder="逗号分隔，如: 安葬,破土" />
                </el-form-item>
                <el-form-item label="是否节假日">
                    <el-switch v-model="formData.is_holiday" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item v-if="formData.is_holiday" label="节假日名称">
                    <el-input v-model="formData.holiday_name" placeholder="如: 春节" />
                </el-form-item>
                <el-form-item label="拥堵等级">
                    <el-select v-model="formData.congestion_level" style="width: 100%">
                        <el-option label="未知" :value="0" />
                        <el-option label="低" :value="1" />
                        <el-option label="中" :value="2" />
                        <el-option label="高" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="formData.remark" type="textarea" rows="2" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { calendarEventLists, calendarEventAdd, calendarEventEdit, calendarEventDelete } from '@/api/schedule'

const loading = ref(false)
const eventList = ref<any[]>([])

const queryParams = ref({
    dateRange: [] as string[],
    is_lucky_day: '' as any,
    is_holiday: '' as any
})

const pager = ref({
    page_no: 1,
    page_size: 15,
    total: 0
})

const dialogVisible = ref(false)
const isEdit = ref(false)
const submitLoading = ref(false)
const formRef = ref<FormInstance>()

const formData = ref({
    id: 0,
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

const formRules: FormRules = {
    event_date: [{ required: true, message: '请选择日期', trigger: 'change' }]
}

// 获取列表
const fetchList = async () => {
    loading.value = true
    try {
        const params: any = {
            page_no: pager.value.page_no,
            page_size: pager.value.page_size
        }
        if (queryParams.value.dateRange && queryParams.value.dateRange.length === 2) {
            params.event_date_start = queryParams.value.dateRange[0]
            params.event_date_end = queryParams.value.dateRange[1]
        }
        if (queryParams.value.is_lucky_day !== '') {
            params.is_lucky_day = queryParams.value.is_lucky_day
        }
        if (queryParams.value.is_holiday !== '') {
            params.is_holiday = queryParams.value.is_holiday
        }
        
        const res = await calendarEventLists(params)
        eventList.value = res.lists || []
        pager.value.total = res.count || 0
    } finally {
        loading.value = false
    }
}

// 重置
const handleReset = () => {
    queryParams.value = {
        dateRange: [],
        is_lucky_day: '',
        is_holiday: ''
    }
    pager.value.page_no = 1
    fetchList()
}

// 添加
const handleAdd = () => {
    isEdit.value = false
    formData.value = {
        id: 0,
        event_date: '',
        lunar_date: '',
        is_lucky_day: 0,
        lucky_events: '',
        unlucky_events: '',
        is_holiday: 0,
        holiday_name: '',
        congestion_level: 0,
        remark: ''
    }
    dialogVisible.value = true
}

// 编辑
const handleEdit = (row: any) => {
    isEdit.value = true
    formData.value = {
        id: row.id,
        event_date: row.event_date,
        lunar_date: row.lunar_date,
        is_lucky_day: row.is_lucky_day,
        lucky_events: row.lucky_events,
        unlucky_events: row.unlucky_events,
        is_holiday: row.is_holiday,
        holiday_name: row.holiday_name,
        congestion_level: row.congestion_level,
        remark: row.remark
    }
    dialogVisible.value = true
}

// 删除
const handleDelete = async (row: any) => {
    await ElMessageBox.confirm('确定要删除该事件吗?', '提示')
    await calendarEventDelete({ id: row.id })
    ElMessage.success('删除成功')
    fetchList()
}

// 提交
const handleSubmit = async () => {
    await formRef.value?.validate()
    
    submitLoading.value = true
    try {
        if (isEdit.value) {
            await calendarEventEdit(formData.value)
        } else {
            await calendarEventAdd(formData.value)
        }
        ElMessage.success(isEdit.value ? '编辑成功' : '添加成功')
        dialogVisible.value = false
        fetchList()
    } finally {
        submitLoading.value = false
    }
}

// 获取拥堵等级类型
const getCongestionType = (level: number) => {
    const map: Record<number, string> = { 0: 'info', 1: 'success', 2: 'warning', 3: 'danger' }
    return map[level] || 'info'
}

onMounted(() => {
    fetchList()
})
</script>

<style scoped>
.calendar-event {
    padding: 16px;
}
</style>
