<template>
    <div class="couple-questionnaire-task">
        <el-card class="!border-none" shadow="never">
            <div class="toolbar">
                <div>
                    <h2 class="page-title">问卷任务</h2>
                    <div class="page-desc">查看全部订单新人问卷任务，并在需要时手动发送提醒。</div>
                </div>
                <el-button @click="goList">问卷列表</el-button>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :inline="true" :model="queryParams" class="mb-4">
                <el-form-item label="服务人员">
                    <el-select v-model="queryParams.staff_id" clearable filterable placeholder="全部" style="width: 180px">
                        <el-option v-for="item in staffOptions" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="关键词">
                    <el-input
                        v-model="queryParams.keyword"
                        clearable
                        placeholder="任务编号、订单ID或问卷标题"
                        style="width: 240px"
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item label="填写状态">
                    <el-select v-model="queryParams.status" clearable placeholder="全部" style="width: 140px">
                        <el-option label="待填写" :value="0" />
                        <el-option label="已填写" :value="1" />
                        <el-option label="已取消" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item label="推送状态">
                    <el-select v-model="queryParams.send_status" clearable placeholder="全部" style="width: 140px">
                        <el-option label="待推送" :value="0" />
                        <el-option label="已推送" :value="1" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>

            <el-table v-loading="pager.loading" :data="pager.lists" border>
                <el-table-column prop="task_sn" label="任务编号" width="180" />
                <el-table-column label="服务人员" width="130">
                    <template #default="{ row }">{{ row.staff?.name || row.staff_name || '-' }}</template>
                </el-table-column>
                <el-table-column label="客户" width="140">
                    <template #default="{ row }">{{ row.user?.nickname || row.user?.mobile || '-' }}</template>
                </el-table-column>
                <el-table-column label="订单" width="180">
                    <template #default="{ row }">{{ row.order?.order_sn || row.order_id }}</template>
                </el-table-column>
                <el-table-column prop="title_snapshot" label="问卷" min-width="160" show-overflow-tooltip />
                <el-table-column label="版本" width="80">
                    <template #default="{ row }">v{{ row.version_no || '-' }}</template>
                </el-table-column>
                <el-table-column prop="status_desc" label="填写状态" width="100" />
                <el-table-column prop="send_status_desc" label="推送状态" width="100" />
                <el-table-column prop="send_count" label="推送次数" width="90" />
                <el-table-column prop="create_time" label="创建时间" width="170" />
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="viewTask(row)">详情</el-button>
                        <el-button v-if="row.status === 0" type="success" link @click="sendTask(row)">
                            发送问卷
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="taskDialog.visible" title="问卷任务详情" width="780px">
            <template v-if="taskDialog.detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="任务编号">{{ taskDialog.detail.task_sn }}</el-descriptions-item>
                    <el-descriptions-item label="订单">{{ taskDialog.detail.order?.order_sn || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="服务人员">{{ taskDialog.detail.staff?.name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="客户">{{ taskDialog.detail.user?.nickname || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="填写状态">{{ taskDialog.detail.status_desc }}</el-descriptions-item>
                    <el-descriptions-item label="推送状态">{{ taskDialog.detail.send_status_desc }}</el-descriptions-item>
                </el-descriptions>
                <div class="answer-list mt-4">
                    <div v-for="question in taskDialog.detail.questions || []" :key="question.id" class="answer-item">
                        <div class="answer-item__title">{{ question.title }}</div>
                        <div class="answer-item__value">
                            {{ getAnswerText(question, taskDialog.detail.answer?.answers || []) }}
                        </div>
                    </div>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
    coupleQuestionnaireSend,
    coupleQuestionnaireTaskDetail,
    coupleQuestionnaireTasks
} from '@/api/questionnaire'
import { staffAll } from '@/api/staff'
import { usePaging } from '@/hooks/usePaging'

const route = useRoute()
const router = useRouter()
const staffOptions = ref<any[]>([])
const queryParams = reactive({
    staff_id: route.query.staff_id ? Number(route.query.staff_id) : '',
    keyword: '',
    status: '',
    send_status: ''
})
const taskDialog = reactive({
    visible: false,
    detail: null as any
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: coupleQuestionnaireTasks,
    params: queryParams,
    firstLoading: true
})

const loadStaffOptions = async () => {
    try {
        staffOptions.value = await staffAll()
    } catch (error) {
        staffOptions.value = []
    }
}

const sendTask = async (row: any) => {
    await ElMessageBox.confirm('本次会通过站内消息提醒客户填写新人问卷。', '发送新人问卷', { type: 'info' })
    await coupleQuestionnaireSend({ id: row.id })
    ElMessage.success('已发送')
    await getLists()
}

const viewTask = async (row: any) => {
    const res = await coupleQuestionnaireTaskDetail({ id: row.id })
    taskDialog.detail = res?.data || res
    taskDialog.visible = true
}

const getAnswerText = (question: any, answers: any[]) => {
    const answer = answers.find((item) => String(item.key) === String(question.id))
    const value = answer?.value
    if (Array.isArray(value)) {
        return value.join('、') || '未填写'
    }
    return value || '未填写'
}

const goList = () => {
    router.push('/couple-questionnaire/lists')
}

onMounted(async () => {
    await loadStaffOptions()
    await getLists()
})
</script>

<style scoped lang="scss">
.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.page-title {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.page-desc {
    margin-top: 6px;
    color: var(--el-text-color-secondary);
}

.answer-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.answer-item {
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 8px;
    padding: 12px;
}

.answer-item__title {
    font-weight: 600;
    margin-bottom: 8px;
}

.answer-item__value {
    color: var(--el-text-color-regular);
    line-height: 1.7;
}
</style>
