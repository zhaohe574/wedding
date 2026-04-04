<template>
    <div class="staff-center-rule">
        <el-card class="!border-none mb-4" shadow="never">
            <template #header>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold">档期规则</span>
                    <el-button type="primary" @click="handleAdd">
                        <el-icon class="mr-1"><Plus /></el-icon>新增个人规则
                    </el-button>
                </div>
            </template>

            <div class="text-sm text-gray-500">全局模板只读</div>
        </el-card>

        <el-card class="!border-none mb-4" shadow="never">
            <template #header>
                <span class="font-bold">全局模板（只读）</span>
            </template>
            <el-descriptions :column="3" border>
                <el-descriptions-item label="提前预约天数">{{ globalTemplate.advance_days ?? '-' }}</el-descriptions-item>
                <el-descriptions-item label="单日最大接单">{{ globalTemplate.max_orders_per_day ?? '-' }}</el-descriptions-item>
                <el-descriptions-item label="间隔小时">{{ globalTemplate.interval_hours ?? '-' }}</el-descriptions-item>
                <el-descriptions-item label="工作时间">
                    {{ globalTemplate.work_start_time || '-' }} - {{ globalTemplate.work_end_time || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="休息日" :span="2">
                    {{ formatRestDays(globalTemplate.rest_days_arr || []) || '无' }}
                </el-descriptions-item>
            </el-descriptions>
        </el-card>

        <el-card class="!border-none" shadow="never">
            <el-table :data="ruleList" v-loading="loading">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column label="类型" width="120">
                    <template #default="{ row }">
                        <el-tag :type="row.staff_id === 0 ? 'info' : 'primary'">
                            {{ row.staff_id === 0 ? '全局规则' : '个人规则' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="max_orders_per_day" label="单日最大接单" width="140" />
                <el-table-column label="工作时间" width="170">
                    <template #default="{ row }">
                        {{ row.work_start_time }} - {{ row.work_end_time }}
                    </template>
                </el-table-column>
                <el-table-column prop="rest_days_desc" label="休息日" min-width="180" />
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-switch
                            v-model="row.is_enabled"
                            :active-value="1"
                            :inactive-value="0"
                            :disabled="row.staff_id === 0"
                            @change="handleStatusChange(row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-if="row.staff_id !== 0"
                            type="primary"
                            size="small"
                            link
                            @click="handleEdit(row)"
                        >
                            编辑
                        </el-button>
                        <el-button
                            v-if="row.staff_id !== 0"
                            type="danger"
                            size="small"
                            link
                            @click="handleDelete(row)"
                        >
                            删除
                        </el-button>
                        <span v-else class="text-gray-400 text-xs">只读</span>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑规则' : '新增规则'" width="620px">
            <el-form ref="formRef" :model="formData" :rules="formRules" label-width="120px">
                <el-form-item label="提前预约天数">
                    <el-input-number v-model="formData.advance_days" :min="0" :max="365" style="width: 100%" />
                </el-form-item>
                <el-form-item label="单日最大接单" prop="max_orders_per_day">
                    <el-input-number v-model="formData.max_orders_per_day" :min="1" :max="10" style="width: 100%" />
                </el-form-item>
                <el-form-item label="订单间隔(小时)">
                    <el-input-number v-model="formData.interval_hours" :min="0" :max="24" style="width: 100%" />
                </el-form-item>
                <el-form-item label="工作时间">
                    <div class="flex items-center gap-2 w-full">
                        <el-time-select
                            v-model="formData.work_start_time"
                            start="06:00"
                            end="23:00"
                            step="00:30"
                            placeholder="开始时间"
                            style="width: 45%"
                        />
                        <span>至</span>
                        <el-time-select
                            v-model="formData.work_end_time"
                            start="06:00"
                            end="23:00"
                            step="00:30"
                            placeholder="结束时间"
                            style="width: 45%"
                        />
                    </div>
                </el-form-item>
                <el-form-item label="休息日">
                    <el-checkbox-group v-model="formData.rest_days">
                        <el-checkbox :label="0">周日</el-checkbox>
                        <el-checkbox :label="1">周一</el-checkbox>
                        <el-checkbox :label="2">周二</el-checkbox>
                        <el-checkbox :label="3">周三</el-checkbox>
                        <el-checkbox :label="4">周四</el-checkbox>
                        <el-checkbox :label="5">周五</el-checkbox>
                        <el-checkbox :label="6">周六</el-checkbox>
                    </el-checkbox-group>
                </el-form-item>
                <el-form-item label="是否启用">
                    <el-switch v-model="formData.is_enabled" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="submitLoading" @click="handleSubmit">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts" name="staffCenterRule">
import { onMounted, reactive, ref } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import {
    myRuleChangeStatus,
    myRuleDelete,
    myRuleSave,
    myRuleTemplate,
    myRules
} from '@/api/staff-center'

const loading = ref(false)
const ruleList = ref<any[]>([])
const globalTemplate = ref<any>({})

const dialogVisible = ref(false)
const isEdit = ref(false)
const submitLoading = ref(false)
const formRef = ref<FormInstance>()

const formData = reactive({
    id: 0,
    advance_days: 1,
    max_orders_per_day: 1,
    interval_hours: 0,
    work_start_time: '09:00',
    work_end_time: '18:00',
    rest_days: [] as number[],
    is_enabled: 1
})

const formRules: FormRules = {
    max_orders_per_day: [{ required: true, message: '请输入单日最大接单数', trigger: 'blur' }]
}

const formatRestDays = (days: number[]) => {
    const map: Record<number, string> = {
        0: '周日',
        1: '周一',
        2: '周二',
        3: '周三',
        4: '周四',
        5: '周五',
        6: '周六'
    }
    return days.map((item) => map[item]).filter(Boolean).join('、')
}

const fetchRuleList = async () => {
    loading.value = true
    try {
        const res = await myRules({ page_no: 1, page_size: 200 })
        ruleList.value = res?.lists || []
    } finally {
        loading.value = false
    }
}

const fetchTemplate = async () => {
    globalTemplate.value = (await myRuleTemplate()) || {}
}

const resetFormData = () => {
    Object.assign(formData, {
        id: 0,
        advance_days: 1,
        max_orders_per_day: 1,
        interval_hours: 0,
        work_start_time: '09:00',
        work_end_time: '18:00',
        rest_days: [],
        is_enabled: 1
    })
}

const handleAdd = () => {
    isEdit.value = false
    resetFormData()
    dialogVisible.value = true
}

const handleEdit = (row: any) => {
    if (row.staff_id === 0) {
        ElMessage.warning('全局模板不可编辑')
        return
    }
    isEdit.value = true
    Object.assign(formData, {
        id: row.id,
        advance_days: row.advance_days ?? 1,
        max_orders_per_day: row.max_orders_per_day ?? 1,
        interval_hours: row.interval_hours ?? 0,
        work_start_time: row.work_start_time || '09:00',
        work_end_time: row.work_end_time || '18:00',
        rest_days: row.rest_days_arr || [],
        is_enabled: row.is_enabled ?? 1
    })
    dialogVisible.value = true
}

const handleDelete = async (row: any) => {
    await ElMessageBox.confirm('确定删除该个人规则吗？', '提示')
    await myRuleDelete({ id: row.id })
    ElMessage.success('删除成功')
    fetchRuleList()
}

const handleStatusChange = async (row: any) => {
    if (row.staff_id === 0) {
        return
    }
    await myRuleChangeStatus({ id: row.id })
    ElMessage.success('操作成功')
    fetchRuleList()
}

const handleSubmit = async () => {
    await formRef.value?.validate()
    submitLoading.value = true
    try {
        await myRuleSave({ ...formData })
        ElMessage.success('保存成功')
        dialogVisible.value = false
        fetchRuleList()
    } finally {
        submitLoading.value = false
    }
}

onMounted(() => {
    fetchTemplate()
    fetchRuleList()
})
</script>

<style scoped>
.staff-center-rule {
    padding: 16px;
}
</style>
