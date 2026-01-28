<template>
    <div class="schedule-rule">
        <el-card class="!border-none" shadow="never">
            <template #header>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-bold">档期规则管理</span>
                    <el-button type="primary" @click="handleAdd">
                        <el-icon class="mr-1"><Plus /></el-icon>添加规则
                    </el-button>
                </div>
            </template>

            <el-table :data="ruleList" v-loading="loading">
                <el-table-column prop="id" label="ID" width="80" />
                <el-table-column label="类型" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.staff_id === 0 ? 'danger' : 'primary'">
                            {{ row.type_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="工作人员" min-width="120">
                    <template #default="{ row }">
                        {{ row.staff_id === 0 ? '全局默认' : (row.staff?.name || '-') }}
                    </template>
                </el-table-column>
                <el-table-column label="预约限制" width="140">
                    <template #default>
                        当日不可预约
                    </template>
                </el-table-column>
                <el-table-column prop="max_orders_per_day" label="单日最大接单" width="120" />
                <el-table-column label="工作时间" width="140">
                    <template #default="{ row }">
                        {{ row.work_start_time }} - {{ row.work_end_time }}
                    </template>
                </el-table-column>
                <el-table-column prop="rest_days_desc" label="休息日" min-width="150" />
                <el-table-column label="状态" width="80">
                    <template #default="{ row }">
                        <el-switch 
                            v-model="row.is_enabled" 
                            :active-value="1" 
                            :inactive-value="0"
                            @change="handleStatusChange(row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" size="small" link @click="handleEdit(row)">编辑</el-button>
                        <el-button 
                            v-if="row.staff_id !== 0"
                            type="danger" 
                            size="small" 
                            link 
                            @click="handleDelete(row)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <!-- 添加/编辑弹窗 -->
        <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑规则' : '添加规则'" width="600px">
            <el-form :model="formData" :rules="formRules" ref="formRef" label-width="120px">
                <el-form-item label="适用对象" prop="staff_id">
                    <el-select v-model="formData.staff_id" :disabled="isEdit" style="width: 100%">
                        <el-option label="全局默认" :value="0" />
                        <el-option 
                            v-for="item in staffList" 
                            :key="item.id" 
                            :label="item.name" 
                            :value="item.id" 
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="预约限制">
                    <el-input-number v-model="formData.advance_days" :min="1" :max="1" disabled style="width: 100%" />
                    <div class="text-gray-400 text-xs mt-1">仅限制当天不可预约</div>
                </el-form-item>
                <el-form-item label="单日最大接单" prop="max_orders_per_day">
                    <el-input-number v-model="formData.max_orders_per_day" :min="1" :max="10" style="width: 100%" />
                </el-form-item>
                <el-form-item label="订单间隔时间" prop="interval_hours">
                    <el-input-number v-model="formData.interval_hours" :min="0" :max="24" style="width: 100%" />
                    <div class="text-gray-400 text-xs mt-1">两个订单之间的最小间隔(小时)</div>
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
                <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { 
    scheduleRuleLists, 
    scheduleRuleAdd, 
    scheduleRuleEdit, 
    scheduleRuleDelete, 
    scheduleRuleChangeStatus 
} from '@/api/schedule'
import { staffAll } from '@/api/staff'

const loading = ref(false)
const ruleList = ref<any[]>([])
const staffList = ref<any[]>([])

const dialogVisible = ref(false)
const isEdit = ref(false)
const submitLoading = ref(false)
const formRef = ref<FormInstance>()

const formData = ref({
    id: 0,
    staff_id: 0,
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

// 获取规则列表
const fetchRuleList = async () => {
    loading.value = true
    try {
        const res = await scheduleRuleLists()
        ruleList.value = res.lists || []
    } finally {
        loading.value = false
    }
}

// 获取工作人员列表
const fetchStaffList = async () => {
    const res = await staffAll()
    staffList.value = res || []
}

// 添加
const handleAdd = () => {
    isEdit.value = false
    formData.value = {
        id: 0,
        staff_id: 0,
        advance_days: 1,
        max_orders_per_day: 1,
        interval_hours: 0,
        work_start_time: '09:00',
        work_end_time: '18:00',
        rest_days: [],
        is_enabled: 1
    }
    dialogVisible.value = true
}

// 编辑
const handleEdit = (row: any) => {
    isEdit.value = true
    formData.value = {
        id: row.id,
        staff_id: row.staff_id,
        advance_days: 1,
        max_orders_per_day: row.max_orders_per_day,
        interval_hours: row.interval_hours,
        work_start_time: row.work_start_time,
        work_end_time: row.work_end_time,
        rest_days: row.rest_days_arr || [],
        is_enabled: row.is_enabled
    }
    dialogVisible.value = true
}

// 删除
const handleDelete = async (row: any) => {
    await ElMessageBox.confirm('确定要删除该规则吗?', '提示')
    await scheduleRuleDelete({ id: row.id })
    ElMessage.success('删除成功')
    fetchRuleList()
}

// 状态切换
const handleStatusChange = async (row: any) => {
    await scheduleRuleChangeStatus({ id: row.id })
    ElMessage.success('操作成功')
}

// 提交
const handleSubmit = async () => {
    await formRef.value?.validate()
    
    submitLoading.value = true
    try {
        if (isEdit.value) {
            await scheduleRuleEdit(formData.value)
        } else {
            await scheduleRuleAdd(formData.value)
        }
        ElMessage.success(isEdit.value ? '编辑成功' : '添加成功')
        dialogVisible.value = false
        fetchRuleList()
    } finally {
        submitLoading.value = false
    }
}

onMounted(() => {
    fetchRuleList()
    fetchStaffList()
})
</script>

<style scoped>
.schedule-rule {
    padding: 16px;
}
</style>
