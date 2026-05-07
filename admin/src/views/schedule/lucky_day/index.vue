<template>
    <admin-page-shell class="schedule-lucky-day" title="吉日设置">
        <search-panel>
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="日期">
                    <el-date-picker
                        v-model="queryParams.event_date"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="选择日期"
                    />
                </el-form-item>
                <el-form-item label="日期范围">
                    <el-date-picker
                        v-model="dateRange"
                        type="daterange"
                        value-format="YYYY-MM-DD"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                    />
                </el-form-item>
                <el-form-item label="吉日">
                    <el-select v-model="queryParams.is_lucky_day" clearable placeholder="全部" style="width: 120px">
                        <el-option label="是" :value="1" />
                        <el-option label="否" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="节假日">
                    <el-select v-model="queryParams.is_holiday" clearable placeholder="全部" style="width: 120px">
                        <el-option label="是" :value="1" />
                        <el-option label="否" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="拥堵等级">
                    <el-select v-model="queryParams.congestion_level" clearable placeholder="全部" style="width: 130px">
                        <el-option
                            v-for="item in congestionOptions"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="节假日名">
                    <el-input
                        v-model="queryParams.holiday_name"
                        clearable
                        placeholder="输入名称"
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetSearch">重置</el-button>
                    <el-button type="primary" plain @click="openEdit()">新增吉日</el-button>
                    <el-button type="success" plain @click="openBatch">批量设置</el-button>
                </el-form-item>
            </el-form>
        </search-panel>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table v-loading="pager.loading" :data="pager.lists" size="large">
                <el-table-column label="日期" prop="event_date" width="130" />
                <el-table-column label="农历" prop="lunar_date" width="120" />
                <el-table-column label="标记" width="130">
                    <template #default="{ row }">
                        <div class="flex gap-1">
                            <el-tag v-if="row.is_lucky_day" type="danger">吉</el-tag>
                            <el-tag v-if="row.is_holiday" type="warning">假</el-tag>
                            <span v-if="!row.is_lucky_day && !row.is_holiday">-</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="宜" prop="lucky_events" min-width="180" show-overflow-tooltip />
                <el-table-column label="忌" prop="unlucky_events" min-width="180" show-overflow-tooltip />
                <el-table-column label="节假日" min-width="140">
                    <template #default="{ row }">
                        {{ row.holiday_name || '-' }}
                    </template>
                </el-table-column>
                <el-table-column label="拥堵等级" width="120">
                    <template #default="{ row }">
                        {{ row.congestion_level_text || row.congestion_level_desc || '未知' }}
                    </template>
                </el-table-column>
                <el-table-column label="备注" prop="remark" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openEdit(row)">编辑</el-button>
                        <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="mt-4 flex justify-end">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="editVisible" :title="editTitle" width="560px">
            <el-form :model="editForm" label-width="100px">
                <el-form-item label="日期" required>
                    <el-date-picker
                        v-model="editForm.event_date"
                        type="date"
                        value-format="YYYY-MM-DD"
                        style="width: 100%"
                    />
                </el-form-item>
                <el-form-item label="农历">
                    <el-input v-model="editForm.lunar_date" maxlength="20" placeholder="如：腊月初八" />
                </el-form-item>
                <el-form-item label="吉日">
                    <el-switch v-model="editForm.is_lucky_day" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="宜">
                    <el-input v-model="editForm.lucky_events" maxlength="255" placeholder="如：嫁娶,订盟,纳采" />
                </el-form-item>
                <el-form-item label="忌">
                    <el-input v-model="editForm.unlucky_events" maxlength="255" placeholder="如：开市,动土" />
                </el-form-item>
                <el-form-item label="节假日">
                    <div class="flex w-full gap-3">
                        <el-switch v-model="editForm.is_holiday" :active-value="1" :inactive-value="0" />
                        <el-input v-model="editForm.holiday_name" maxlength="50" placeholder="节假日名称" />
                    </div>
                </el-form-item>
                <el-form-item label="拥堵等级">
                    <el-select v-model="editForm.congestion_level" style="width: 100%">
                        <el-option
                            v-for="item in congestionOptions"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="editForm.remark" type="textarea" :rows="3" maxlength="255" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="editVisible = false">取消</el-button>
                <el-button type="primary" :loading="submitLoading" @click="submitEdit">保存</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="batchVisible" title="批量设置吉日" width="560px">
            <el-form :model="batchForm" label-width="100px">
                <el-form-item label="日期范围" required>
                    <el-date-picker
                        v-model="batchForm.dateRange"
                        type="daterange"
                        value-format="YYYY-MM-DD"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        style="width: 100%"
                    />
                </el-form-item>
                <el-form-item label="农历">
                    <el-input v-model="batchForm.lunar_date" maxlength="20" />
                </el-form-item>
                <el-form-item label="吉日">
                    <el-switch v-model="batchForm.is_lucky_day" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="宜">
                    <el-input v-model="batchForm.lucky_events" maxlength="255" />
                </el-form-item>
                <el-form-item label="忌">
                    <el-input v-model="batchForm.unlucky_events" maxlength="255" />
                </el-form-item>
                <el-form-item label="节假日">
                    <div class="flex w-full gap-3">
                        <el-switch v-model="batchForm.is_holiday" :active-value="1" :inactive-value="0" />
                        <el-input v-model="batchForm.holiday_name" maxlength="50" placeholder="节假日名称" />
                    </div>
                </el-form-item>
                <el-form-item label="拥堵等级">
                    <el-select v-model="batchForm.congestion_level" style="width: 100%">
                        <el-option
                            v-for="item in congestionOptions"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="batchForm.remark" type="textarea" :rows="3" maxlength="255" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="batchVisible = false">取消</el-button>
                <el-button type="primary" :loading="submitLoading" @click="submitBatch">批量保存</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import {
    calendarEventBatchSave,
    calendarEventCongestionLevelOptions,
    calendarEventDelete,
    calendarEventLists,
    calendarEventSave
} from '@/api/schedule'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    event_date: '',
    start_date: '',
    end_date: '',
    is_lucky_day: '' as any,
    is_holiday: '' as any,
    congestion_level: '' as any,
    holiday_name: ''
})
const dateRange = ref<string[]>([])
const congestionOptions = ref<any[]>([])
const editVisible = ref(false)
const batchVisible = ref(false)
const submitLoading = ref(false)
const editTitle = computed(() => (editForm.id ? '编辑吉日' : '新增吉日'))

const emptyForm = () => ({
    id: 0,
    event_date: '',
    lunar_date: '',
    is_lucky_day: 1,
    lucky_events: '',
    unlucky_events: '',
    is_holiday: 0,
    holiday_name: '',
    congestion_level: 0,
    remark: ''
})

const editForm = reactive(emptyForm())
const batchForm = reactive({
    dateRange: [] as string[],
    lunar_date: '',
    is_lucky_day: 1,
    lucky_events: '',
    unlucky_events: '',
    is_holiday: 0,
    holiday_name: '',
    congestion_level: 0,
    remark: ''
})

const fetchLuckyDayLists = (params: any) => {
    const range = dateRange.value || []
    return calendarEventLists({
        ...params,
        ...queryParams,
        start_date: range[0] || '',
        end_date: range[1] || ''
    })
}

const { pager, getLists, resetPage } = usePaging({
    fetchFun: fetchLuckyDayLists,
    params: {}
})

const resetSearch = () => {
    Object.assign(queryParams, {
        event_date: '',
        start_date: '',
        end_date: '',
        is_lucky_day: '',
        is_holiday: '',
        congestion_level: '',
        holiday_name: ''
    })
    dateRange.value = []
    resetPage()
}

const openEdit = (row?: any) => {
    Object.assign(editForm, emptyForm(), row || {})
    if (row) {
        editForm.is_lucky_day = row.is_lucky_day ? 1 : 0
        editForm.is_holiday = row.is_holiday ? 1 : 0
        editForm.congestion_level = Number(row.congestion_level || 0)
    }
    editVisible.value = true
}

const submitEdit = async () => {
    submitLoading.value = true
    try {
        await calendarEventSave(editForm)
        feedback.msgSuccess('保存成功')
        editVisible.value = false
        getLists()
    } finally {
        submitLoading.value = false
    }
}

const handleDelete = async (row: any) => {
    await feedback.confirm(`确定删除 ${row.event_date} 的吉日设置吗？`)
    await calendarEventDelete({ id: row.id })
    feedback.msgSuccess('删除成功')
    getLists()
}

const openBatch = () => {
    Object.assign(batchForm, {
        dateRange: [],
        lunar_date: '',
        is_lucky_day: 1,
        lucky_events: '',
        unlucky_events: '',
        is_holiday: 0,
        holiday_name: '',
        congestion_level: 0,
        remark: ''
    })
    batchVisible.value = true
}

const submitBatch = async () => {
    if (!batchForm.dateRange || batchForm.dateRange.length !== 2) {
        feedback.msgError('请选择日期范围')
        return
    }

    submitLoading.value = true
    try {
        await calendarEventBatchSave({
            start_date: batchForm.dateRange[0],
            end_date: batchForm.dateRange[1],
            lunar_date: batchForm.lunar_date,
            is_lucky_day: batchForm.is_lucky_day,
            lucky_events: batchForm.lucky_events,
            unlucky_events: batchForm.unlucky_events,
            is_holiday: batchForm.is_holiday,
            holiday_name: batchForm.holiday_name,
            congestion_level: batchForm.congestion_level,
            remark: batchForm.remark
        })
        feedback.msgSuccess('批量设置成功')
        batchVisible.value = false
        getLists()
    } finally {
        submitLoading.value = false
    }
}

onMounted(async () => {
    congestionOptions.value = (await calendarEventCongestionLevelOptions()) || []
    getLists()
})
</script>

<style scoped>
.schedule-lucky-day {
    padding: 4px 0;
}
</style>
