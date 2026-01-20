<template>
    <div class="timeline-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[180px]" label="订单ID">
                    <el-input
                        v-model="queryParams.order_id"
                        placeholder="输入订单ID"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[180px]" label="任务标题">
                    <el-input
                        v-model="queryParams.task_title"
                        placeholder="输入任务标题"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="任务类型">
                    <el-select v-model="queryParams.task_type" placeholder="选择类型" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="准备物料" :value="1" />
                        <el-option label="确认事项" :value="2" />
                        <el-option label="沟通联系" :value="3" />
                        <el-option label="现场安排" :value="4" />
                        <el-option label="其他" :value="5" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[150px]" label="任务状态">
                    <el-select v-model="queryParams.status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="已完成" value="completed" />
                        <el-option label="待完成" value="pending" />
                        <el-option label="已逾期" value="overdue" />
                        <el-option label="今日任务" value="today" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[320px]" label="触发日期">
                    <el-date-picker
                        v-model="queryParams.trigger_date"
                        type="daterange"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                        clearable
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 统计卡片 -->
        <div class="mt-4 grid grid-cols-4 gap-4">
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">今日任务</div>
                    <div class="text-2xl font-bold mt-2 text-blue-500">{{ stats.today || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待完成</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ stats.pending || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已逾期</div>
                    <div class="text-2xl font-bold mt-2 text-red-500">{{ stats.overdue || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已完成</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ stats.completed || 0 }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex justify-between mb-4">
                <div>
                    <el-button type="primary" @click="handleAdd">
                        <el-icon class="mr-1"><Plus /></el-icon>添加任务
                    </el-button>
                </div>
            </div>

            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="任务标题" prop="task_title" min-width="180">
                    <template #default="{ row }">
                        <div class="font-medium">{{ row.task_title }}</div>
                        <div class="text-gray-400 text-xs mt-1" v-if="row.task_desc">
                            {{ row.task_desc.substring(0, 50) }}{{ row.task_desc.length > 50 ? '...' : '' }}
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="订单信息" min-width="150">
                    <template #default="{ row }">
                        <div v-if="row.order">
                            <div>{{ row.order.order_sn }}</div>
                            <div class="text-gray-400 text-xs">{{ row.order.contact_name }}</div>
                        </div>
                        <span v-else class="text-gray-400">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="任务类型" width="100">
                    <template #default="{ row }">
                        <el-tag size="small" :type="getTaskTypeTagType(row.task_type)">
                            {{ row.task_type_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="触发日期" prop="trigger_date" width="110" />
                <el-table-column label="婚礼日期" prop="wedding_date" width="110" />
                <el-table-column label="任务状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusTagType(row.status)">
                            {{ row.status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="来源" width="80">
                    <template #default="{ row }">
                        <el-tag size="small" :type="row.is_system ? 'info' : ''">
                            {{ row.is_system ? '系统' : '手动' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button 
                            v-if="!row.is_completed" 
                            type="success" 
                            link 
                            @click="handleComplete(row)"
                        >完成</el-button>
                        <el-button 
                            v-if="row.is_completed" 
                            type="warning" 
                            link 
                            @click="handleUncomplete(row)"
                        >取消完成</el-button>
                        <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
                        <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 添加/编辑弹窗 -->
        <el-dialog v-model="editVisible" :title="editForm.id ? '编辑任务' : '添加任务'" width="600px">
            <el-form :model="editForm" label-width="100px" :rules="editRules" ref="editFormRef">
                <el-form-item label="订单" prop="order_id" v-if="!editForm.id">
                    <el-input v-model="editForm.order_id" placeholder="输入订单ID" />
                </el-form-item>
                <el-form-item label="任务标题" prop="task_title">
                    <el-input v-model="editForm.task_title" placeholder="输入任务标题" maxlength="100" />
                </el-form-item>
                <el-form-item label="任务描述" prop="task_desc">
                    <el-input 
                        v-model="editForm.task_desc" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="输入任务描述"
                        maxlength="500"
                    />
                </el-form-item>
                <el-form-item label="任务类型" prop="task_type">
                    <el-select v-model="editForm.task_type" placeholder="选择任务类型">
                        <el-option label="准备物料" :value="1" />
                        <el-option label="确认事项" :value="2" />
                        <el-option label="沟通联系" :value="3" />
                        <el-option label="现场安排" :value="4" />
                        <el-option label="其他" :value="5" />
                    </el-select>
                </el-form-item>
                <el-form-item label="触发日期" prop="trigger_date">
                    <el-date-picker
                        v-model="editForm.trigger_date"
                        type="date"
                        placeholder="选择触发日期"
                        value-format="YYYY-MM-DD"
                    />
                </el-form-item>
                <el-form-item label="提前天数" prop="days_before" v-if="!editForm.id">
                    <el-input-number v-model="editForm.days_before" :min="0" :max="365" />
                    <span class="ml-2 text-gray-400 text-sm">相对婚期提前的天数</span>
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="editForm.sort" :min="0" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="editVisible = false">取消</el-button>
                <el-button type="primary" @click="submitEdit">确定</el-button>
            </template>
        </el-dialog>

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="任务详情" width="600px">
            <div v-if="currentTask">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="任务标题" :span="2">{{ currentTask.task_title }}</el-descriptions-item>
                    <el-descriptions-item label="任务描述" :span="2">{{ currentTask.task_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="任务类型">{{ currentTask.task_type_desc }}</el-descriptions-item>
                    <el-descriptions-item label="任务状态">
                        <el-tag :type="getStatusTagType(currentTask.status)">{{ currentTask.status_desc }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="触发日期">{{ currentTask.trigger_date }}</el-descriptions-item>
                    <el-descriptions-item label="婚礼日期">{{ currentTask.wedding_date || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="订单编号">{{ currentTask.order?.order_sn || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="联系人">{{ currentTask.order?.contact_name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="完成状态">
                        <el-tag :type="currentTask.is_completed ? 'success' : 'warning'">
                            {{ currentTask.is_completed ? '已完成' : '未完成' }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="任务来源">
                        {{ currentTask.is_system ? '系统生成' : '手动添加' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="完成时间" v-if="currentTask.is_completed">
                        {{ currentTask.complete_time_format || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="完成备注" v-if="currentTask.is_completed">
                        {{ currentTask.complete_remark || '-' }}
                    </el-descriptions-item>
                </el-descriptions>
            </div>
        </el-dialog>

        <!-- 完成任务弹窗 -->
        <el-dialog v-model="completeVisible" title="完成任务" width="500px">
            <el-form :model="completeForm" label-width="100px">
                <el-form-item label="完成备注">
                    <el-input 
                        v-model="completeForm.complete_remark" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入完成备注（可选）"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="completeVisible = false">取消</el-button>
                <el-button type="primary" @click="submitComplete">确认完成</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="timelineLists">
import { Plus } from '@element-plus/icons-vue'
import { 
    timelineLists, 
    timelineDetail, 
    timelineAdd, 
    timelineEdit, 
    timelineDelete,
    timelineComplete,
    timelineUncomplete
} from '@/api/timeline'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    order_id: '',
    task_title: '',
    task_type: '',
    status: '',
    trigger_date: []
})

const stats = ref<any>({
    today: 0,
    pending: 0,
    overdue: 0,
    completed: 0
})

const editVisible = ref(false)
const editFormRef = ref()
const editForm = reactive({
    id: 0,
    order_id: '',
    task_title: '',
    task_desc: '',
    task_type: 1,
    trigger_date: '',
    days_before: 7,
    sort: 0
})

const editRules = {
    order_id: [{ required: true, message: '请输入订单ID', trigger: 'blur' }],
    task_title: [{ required: true, message: '请输入任务标题', trigger: 'blur' }],
    task_type: [{ required: true, message: '请选择任务类型', trigger: 'change' }],
    trigger_date: [{ required: true, message: '请选择触发日期', trigger: 'change' }]
}

const detailVisible = ref(false)
const currentTask = ref<any>(null)

const completeVisible = ref(false)
const completeForm = reactive({
    id: 0,
    complete_remark: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: timelineLists,
    params: queryParams
})

const getTaskTypeTagType = (type: number) => {
    const types: Record<number, string> = {
        1: 'primary',
        2: 'success',
        3: 'warning',
        4: 'danger',
        5: 'info'
    }
    return types[type] || 'info'
}

const getStatusTagType = (status: string) => {
    const types: Record<string, string> = {
        completed: 'success',
        pending: 'primary',
        overdue: 'danger',
        today: 'warning'
    }
    return types[status] || 'info'
}

const handleAdd = () => {
    editForm.id = 0
    editForm.order_id = ''
    editForm.task_title = ''
    editForm.task_desc = ''
    editForm.task_type = 1
    editForm.trigger_date = ''
    editForm.days_before = 7
    editForm.sort = 0
    editVisible.value = true
}

const handleEdit = (row: any) => {
    editForm.id = row.id
    editForm.order_id = row.order_id
    editForm.task_title = row.task_title
    editForm.task_desc = row.task_desc
    editForm.task_type = row.task_type
    editForm.trigger_date = row.trigger_date
    editForm.sort = row.sort || 0
    editVisible.value = true
}

const submitEdit = async () => {
    await editFormRef.value?.validate()
    if (editForm.id) {
        await timelineEdit(editForm)
        feedback.msgSuccess('编辑成功')
    } else {
        await timelineAdd(editForm)
        feedback.msgSuccess('添加成功')
    }
    editVisible.value = false
    getLists()
}

const handleDetail = async (row: any) => {
    const res = await timelineDetail({ id: row.id })
    currentTask.value = res
    detailVisible.value = true
}

const handleComplete = (row: any) => {
    completeForm.id = row.id
    completeForm.complete_remark = ''
    completeVisible.value = true
}

const submitComplete = async () => {
    await timelineComplete(completeForm)
    feedback.msgSuccess('任务已完成')
    completeVisible.value = false
    getLists()
}

const handleUncomplete = async (row: any) => {
    await feedback.confirm('确定要取消完成状态吗？')
    await timelineUncomplete({ id: row.id })
    feedback.msgSuccess('已取消完成状态')
    getLists()
}

const handleDelete = async (row: any) => {
    await feedback.confirm('确定要删除该任务吗？')
    await timelineDelete({ id: row.id })
    feedback.msgSuccess('删除成功')
    getLists()
}

// 计算统计数据
const calculateStats = () => {
    const lists = pager.lists || []
    const today = new Date().toISOString().split('T')[0]
    
    stats.value = {
        today: lists.filter((item: any) => item.trigger_date === today && !item.is_completed).length,
        pending: lists.filter((item: any) => !item.is_completed && item.trigger_date >= today).length,
        overdue: lists.filter((item: any) => !item.is_completed && item.trigger_date < today).length,
        completed: lists.filter((item: any) => item.is_completed).length
    }
}

watch(() => pager.lists, () => {
    calculateStats()
}, { deep: true })

getLists()
</script>

<style scoped>
</style>
