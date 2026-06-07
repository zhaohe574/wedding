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
                    <el-button type="primary" :loading="pager.loading" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
                <el-alert
                    v-if="listError"
                    class="mb-4"
                    type="error"
                    :title="listError"
                    show-icon
                    :closable="false"
                >
                    <template #default>
                        <el-button type="primary" link @click="getLists">重试</el-button>
                    </template>
                </el-alert>
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
                <el-table-column label="填写状态" width="110">
                    <template #default="{ row }">
                        <el-tag :type="getTaskStatusTag(row.status)" effect="plain">
                            {{ row.status_desc || getTaskStatusText(row.status) }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="推送状态" width="110">
                    <template #default="{ row }">
                        <el-tag :type="getSendStatusTag(row.send_status)" effect="plain">
                            {{ row.send_status_desc || getSendStatusText(row.send_status) }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="send_count" label="推送次数" width="90" />
                <el-table-column label="最近推送" width="170">
                    <template #default="{ row }">{{ row.last_send_time || row.send_time || '-' }}</template>
                </el-table-column>
                <el-table-column prop="create_time" label="创建时间" width="170" />
                <el-table-column label="操作" width="240" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="viewTask(row)">详情</el-button>
                        <el-button v-if="row.status === 0" type="success" link :loading="sendingId === row.id" @click="sendTask(row)">
                            {{ Number(row.send_status || 0) === 1 ? '再次发送' : '发送问卷' }}
                        </el-button>
                        <el-button type="info" link @click="copyUserPath(row)">复制用户入口</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="taskDialog.visible" title="问卷任务详情" width="860px">
            <div v-loading="taskDialog.loading">
                <el-alert
                    class="mb-4"
                    title="问卷答案包含婚礼流程偏好、联系方式等隐私信息，仅用于本订单服务沟通。"
                    type="info"
                    :closable="false"
                    show-icon
                />
                <template v-if="taskDialog.detail">
                    <el-descriptions :column="2" border>
                        <el-descriptions-item label="任务编号">{{ taskDialog.detail.task_sn || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="订单">{{ taskDialog.detail.order?.order_sn || taskDialog.detail.order_id || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="服务人员">{{ taskDialog.detail.staff?.name || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="客户">{{ taskDialog.detail.user?.nickname || taskDialog.detail.user?.mobile || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="问卷版本">v{{ taskDialog.detail.version_no || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="题目数">{{ (taskDialog.detail.questions || []).length }}</el-descriptions-item>
                        <el-descriptions-item label="填写状态">
                            <el-tag :type="getTaskStatusTag(taskDialog.detail.status)" effect="plain">
                                {{ taskDialog.detail.status_desc || getTaskStatusText(taskDialog.detail.status) }}
                            </el-tag>
                        </el-descriptions-item>
                        <el-descriptions-item label="推送状态">
                            <el-tag :type="getSendStatusTag(taskDialog.detail.send_status)" effect="plain">
                                {{ taskDialog.detail.send_status_desc || getSendStatusText(taskDialog.detail.send_status) }}
                            </el-tag>
                        </el-descriptions-item>
                        <el-descriptions-item label="推送次数">{{ taskDialog.detail.send_count || 0 }}</el-descriptions-item>
                        <el-descriptions-item label="最近推送">{{ taskDialog.detail.last_send_time || taskDialog.detail.send_time || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="提交时间">{{ taskDialog.detail.submitted_time || taskDialog.detail.submit_time || '-' }}</el-descriptions-item>
                        <el-descriptions-item label="用户入口">
                            <el-button type="primary" link @click="copyUserPath(taskDialog.detail)">复制小程序路径</el-button>
                        </el-descriptions-item>
                    </el-descriptions>
                    <div v-if="(taskDialog.detail.questions || []).length" class="answer-list mt-4">
                        <div v-for="question in taskDialog.detail.questions || []" :key="question.id" class="answer-item">
                            <div class="answer-item__title">
                                {{ question.title }}
                                <el-tag v-if="Number(question.required || 0) === 1" size="small" type="danger" effect="plain">必填</el-tag>
                            </div>
                            <div class="answer-item__value">
                                {{ getAnswerText(question, taskDialog.detail.answer?.answers || []) }}
                            </div>
                        </div>
                    </div>
                    <el-empty v-else description="当前任务暂无可展示题目，请检查服务人员是否已发布问卷版本。" />
                </template>
                <el-empty v-else description="未查询到问卷任务详情，可能已删除或无权限查看。" />
            </div>
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
const listError = ref('')
const sendingId = ref<number | null>(null)
const queryParams = reactive({
    staff_id: route.query.staff_id ? Number(route.query.staff_id) : '',
    keyword: String(route.query.keyword || route.query.order_id || ''),
    status: route.query.status !== undefined ? Number(route.query.status) : '',
    send_status: route.query.send_status !== undefined ? Number(route.query.send_status) : ''
})
const taskDialog = reactive({
    visible: false,
    loading: false,
    detail: null as any
})

const fetchTaskLists = async (params: any) => {
    listError.value = ''
    try {
        return await coupleQuestionnaireTasks(params)
    } catch (error: any) {
        listError.value = error?.message || error || '问卷任务加载失败，请稍后重试'
        throw error
    }
}

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: fetchTaskLists,
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
    try {
        await ElMessageBox.confirm(
            `本次会通过站内消息提醒客户填写「${row.title_snapshot || '新人问卷'}」。如已推送，将再次提醒客户。`,
            '发送新人问卷',
            { type: 'info' }
        )
    } catch (error) {
        return
    }
    sendingId.value = Number(row.id || 0)
    try {
        await coupleQuestionnaireSend({ id: row.id })
        ElMessage.success('问卷已发送给客户')
        await getLists()
    } catch (error: any) {
        ElMessage.error(error?.message || error || '问卷发送失败，请稍后重试')
    } finally {
        sendingId.value = null
    }
}

const viewTask = async (row: any) => {
    taskDialog.visible = true
    taskDialog.loading = true
    taskDialog.detail = null
    try {
        const res = await coupleQuestionnaireTaskDetail({ id: row.id })
        taskDialog.detail = res?.data || res || null
    } catch (error: any) {
        ElMessage.error(error?.message || error || '获取问卷详情失败')
    } finally {
        taskDialog.loading = false
    }
}

const getTaskStatusText = (status: number | string) => {
    const value = Number(status || 0)
    if (value === 1) return '已填写'
    if (value === 2) return '已取消'
    return '待填写'
}

const getTaskStatusTag = (status: number | string): 'success' | 'warning' | 'info' => {
    const value = Number(status || 0)
    if (value === 1) return 'success'
    if (value === 2) return 'info'
    return 'warning'
}

const getSendStatusText = (status: number | string) => (Number(status || 0) === 1 ? '已推送' : '待推送')
const getSendStatusTag = (status: number | string): 'success' | 'warning' =>
    Number(status || 0) === 1 ? 'success' : 'warning'

const buildUserPath = (row: any) => `/packages/pages/couple_questionnaire/detail?id=${Number(row?.id || 0)}`

const copyUserPath = async (row: any) => {
    const path = buildUserPath(row)
    try {
        await navigator.clipboard?.writeText(path)
        ElMessage.success('已复制小程序问卷路径')
    } catch (error) {
        ElMessage.info(path)
    }
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
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    margin-bottom: 8px;
}

.answer-item__value {
    color: var(--el-text-color-regular);
    line-height: 1.7;
}
</style>
