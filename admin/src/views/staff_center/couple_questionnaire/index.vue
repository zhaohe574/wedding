<template>
    <div class="staff-center-questionnaire admin-edit-page">
        <el-card class="admin-edit-head !border-none" shadow="never">
            <div class="admin-edit-head__top">
                <div>
                    <h1 class="admin-edit-title">新人问卷</h1>
                    <div class="admin-edit-head__desc">
                        配置婚礼仪式资料问卷，订单进入待服务后会生成填写任务。
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <el-button @click="loadConfig">刷新配置</el-button>
                    <el-button type="primary" @click="publishConfig">发布新版</el-button>
                </div>
            </div>
        </el-card>

        <el-row :gutter="16" class="mt-4">
            <el-col :span="8">
                <el-card class="!border-none questionnaire-panel" shadow="never">
                    <template #header>
                        <div class="panel-head">
                            <span>问卷设置</span>
                            <el-tag v-if="config.latest_version" type="success">
                                v{{ config.latest_version.version_no }}
                            </el-tag>
                            <el-tag v-else type="info">未发布</el-tag>
                        </div>
                    </template>

                    <el-form label-width="88px">
                        <el-form-item label="标题">
                            <el-input v-model="form.title" maxlength="120" show-word-limit />
                        </el-form-item>
                        <el-form-item label="说明">
                            <el-input
                                v-model="form.description"
                                type="textarea"
                                :rows="4"
                                maxlength="500"
                                show-word-limit
                            />
                        </el-form-item>
                        <el-form-item label="推送方式">
                            <el-radio-group v-model="form.push_mode">
                                <el-radio-button :label="1">自动推送</el-radio-button>
                                <el-radio-button :label="2">手动触发</el-radio-button>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item label="启用状态">
                            <el-switch
                                v-model="form.status"
                                :active-value="1"
                                :inactive-value="0"
                                active-text="启用"
                                inactive-text="停用"
                            />
                        </el-form-item>
                    </el-form>

                    <el-alert
                        title="自动推送只控制站内消息发送，问卷任务仍会在订单进入待服务时生成。"
                        type="info"
                        :closable="false"
                        show-icon
                    />
                </el-card>
            </el-col>

            <el-col :span="16">
                <el-card class="!border-none questionnaire-panel" shadow="never">
                    <template #header>
                        <div class="panel-head">
                            <span>已选题目</span>
                            <div class="flex gap-2">
                                <el-button type="success" @click="addBlankQuestion">新增题目</el-button>
                                <el-button @click="saveConfig">保存草稿</el-button>
                                <el-button type="primary" @click="publishConfig">发布新版</el-button>
                            </div>
                        </div>
                    </template>

                    <el-empty v-if="!form.questions.length" description="可新增空白题，也可从下方题库样例复制" />
                    <div v-else class="question-list">
                        <div v-for="(item, index) in form.questions" :key="item.local_id" class="question-item">
                            <div class="question-item__top">
                                <el-tag>{{ getQuestionTypeLabel(item.type) }}</el-tag>
                                <span class="question-item__index">第 {{ index + 1 }} 题</span>
                                <div class="question-item__actions">
                                    <el-button link :disabled="index === 0" @click="moveQuestion(index, -1)">上移</el-button>
                                    <el-button link :disabled="index === form.questions.length - 1" @click="moveQuestion(index, 1)">下移</el-button>
                                    <el-button link type="danger" @click="removeQuestion(index)">移除</el-button>
                                </div>
                            </div>
                            <el-form label-width="70px" class="question-item__form">
                                <el-form-item label="分类">
                                    <el-select v-model="item.category" filterable allow-create default-first-option>
                                        <el-option
                                            v-for="category in categories"
                                            :key="category"
                                            :label="category"
                                            :value="category"
                                        />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="题型">
                                    <el-select v-model="item.type" @change="normalizeQuestionOptions(item)">
                                        <el-option
                                            v-for="type in questionTypes"
                                            :key="type.value"
                                            :label="type.label"
                                            :value="type.value"
                                        />
                                    </el-select>
                                </el-form-item>
                                <el-form-item label="标题">
                                    <el-input v-model="item.title" maxlength="120" show-word-limit />
                                </el-form-item>
                                <el-form-item label="提示">
                                    <el-input v-model="item.placeholder" maxlength="120" />
                                </el-form-item>
                                <el-form-item v-if="['single', 'multiple'].includes(item.type)" label="选项">
                                    <el-select
                                        v-model="item.options"
                                        multiple
                                        filterable
                                        allow-create
                                        default-first-option
                                        class="w-full"
                                        placeholder="输入选项后回车"
                                    />
                                </el-form-item>
                                <el-form-item label="必填">
                                    <el-switch v-model="item.required" :active-value="1" :inactive-value="0" />
                                </el-form-item>
                            </el-form>
                        </div>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <el-card class="!border-none mt-4" shadow="never">
            <template #header>
                <div class="panel-head">
                    <span>基础题库样例</span>
                    <el-input
                        v-model="bankKeyword"
                        placeholder="搜索题目"
                        clearable
                        style="width: 240px"
                    />
                </div>
            </template>
            <el-table :data="filteredBankQuestions" border>
                <el-table-column prop="category" label="分类" width="130" />
                <el-table-column prop="title" label="题目" min-width="240" />
                <el-table-column label="题型" width="90">
                    <template #default="{ row }">{{ getQuestionTypeLabel(row.type) }}</template>
                </el-table-column>
                <el-table-column label="必填" width="80">
                    <template #default="{ row }">{{ row.required ? '是' : '否' }}</template>
                </el-table-column>
                <el-table-column label="操作" width="100">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="addFromBank(row)">复制到问卷</el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <template #header>
                <div class="panel-head">
                    <span>问卷任务</span>
                    <el-button @click="loadTasks">刷新任务</el-button>
                </div>
            </template>
            <el-form :inline="true" :model="taskSearch" class="mb-4">
                <el-form-item label="填写状态">
                    <el-select v-model="taskSearch.status" clearable placeholder="全部" style="width: 140px">
                        <el-option label="待填写" :value="0" />
                        <el-option label="已填写" :value="1" />
                        <el-option label="已取消" :value="2" />
                    </el-select>
                </el-form-item>
                <el-form-item label="推送状态">
                    <el-select v-model="taskSearch.send_status" clearable placeholder="全部" style="width: 140px">
                        <el-option label="待推送" :value="0" />
                        <el-option label="已推送" :value="1" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetTaskPage">查询</el-button>
                    <el-button @click="resetTaskSearch">重置</el-button>
                </el-form-item>
            </el-form>
            <el-table v-loading="taskPager.loading" :data="taskPager.lists" border>
                <el-table-column prop="task_sn" label="任务编号" width="180" />
                <el-table-column label="客户" width="140">
                    <template #default="{ row }">{{ row.user?.nickname || row.user?.mobile || '-' }}</template>
                </el-table-column>
                <el-table-column label="订单" width="180">
                    <template #default="{ row }">{{ row.order?.order_sn || row.order_id }}</template>
                </el-table-column>
                <el-table-column prop="title_snapshot" label="问卷" min-width="160" />
                <el-table-column prop="status_desc" label="填写状态" width="100" />
                <el-table-column prop="send_status_desc" label="推送状态" width="100" />
                <el-table-column prop="send_count" label="推送次数" width="90" />
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
                <pagination v-model="taskPager" @change="loadTasks" />
            </div>
        </el-card>

        <el-dialog v-model="taskDialog.visible" title="问卷任务详情" width="760px">
            <template v-if="taskDialog.detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="任务编号">{{ taskDialog.detail.task_sn }}</el-descriptions-item>
                    <el-descriptions-item label="订单">{{ taskDialog.detail.order?.order_sn || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="客户">{{ taskDialog.detail.user?.nickname || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="状态">{{ taskDialog.detail.status_desc }}</el-descriptions-item>
                </el-descriptions>
                <div class="answer-list mt-4">
                    <div v-for="question in taskDialog.detail.questions || []" :key="question.id" class="answer-item">
                        <div class="answer-item__title">{{ question.title }}</div>
                        <div class="answer-item__value">{{ getAnswerText(question, taskDialog.detail.answer?.answers || []) }}</div>
                    </div>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
    myCoupleQuestionnaireConfig,
    myCoupleQuestionnairePublish,
    myCoupleQuestionnaireSave,
    myCoupleQuestionnaireSend,
    myCoupleQuestionnaireTaskDetail,
    myCoupleQuestionnaireTasks
} from '@/api/staff-center'

interface QuestionItem {
    id: number
    local_id: string
    bank_id: number
    category: string
    type: string
    title: string
    placeholder: string
    options: string[]
    required: number
    sort: number
}

const config = ref<any>({})
const categories = ref<string[]>([])
const questionTypes = ref<any[]>([])
const bankKeyword = ref('')
const bankQuestions = ref<any[]>([])
const form = reactive({
    title: '',
    description: '',
    push_mode: 1,
    status: 1,
    questions: [] as QuestionItem[]
})
const taskSearch = reactive({
    status: '',
    send_status: ''
})
const taskPager = reactive({
    page: 1,
    size: 10,
    count: 0,
    lists: [] as any[],
    loading: false
})
const taskDialog = reactive({
    visible: false,
    detail: null as any
})

const filteredBankQuestions = computed(() => {
    const keyword = bankKeyword.value.trim()
    if (!keyword) {
        return bankQuestions.value
    }
    return bankQuestions.value.filter((item) => `${item.category}${item.title}`.includes(keyword))
})

const getQuestionTypeLabel = (type: string) =>
    questionTypes.value.find((item) => item.value === type)?.label || type

const normalizeQuestion = (item: any, index = 0): QuestionItem => ({
    id: Number(item.id || 0),
    local_id: `${Date.now()}-${Math.random()}-${index}`,
    bank_id: Number(item.bank_id === undefined || item.bank_id === null ? item.id || 0 : item.bank_id),
    category: String(item.category || '补充说明'),
    type: String(item.type || 'textarea'),
    title: String(item.title || ''),
    placeholder: String(item.placeholder || ''),
    options: Array.isArray(item.options) ? item.options.map(String) : [],
    required: Number(item.required || 0) ? 1 : 0,
    sort: Number(item.sort || 0)
})

const normalizeQuestionOptions = (item: QuestionItem) => {
    if (!['single', 'multiple'].includes(item.type)) {
        item.options = []
        return
    }
    if (item.options.length < 2) {
        item.options = item.options.length ? item.options : ['选项一', '选项二']
    }
}

const serializeQuestions = () =>
    form.questions.map((item, index) => ({
        ...item,
        id: index + 1,
        local_id: undefined,
        sort: form.questions.length - index
    }))

const loadConfig = async () => {
    const res = await myCoupleQuestionnaireConfig()
    const data = res?.data || res || {}
    config.value = data
    categories.value = data.categories || []
    questionTypes.value = data.question_types || [
        { value: 'text', label: '短文本' },
        { value: 'textarea', label: '文本' },
        { value: 'single', label: '单选' },
        { value: 'multiple', label: '多选' },
        { value: 'rating', label: '评分' }
    ]
    bankQuestions.value = data.bank_questions || []
    form.title = data.title || '新人婚礼资料问卷'
    form.description = data.description || ''
    form.push_mode = Number(data.push_mode || 1)
    form.status = Number(data.status ?? 1)
    form.questions = (data.draft_questions || []).map(normalizeQuestion)
}

const saveConfig = async () => {
    await myCoupleQuestionnaireSave({
        title: form.title,
        description: form.description,
        push_mode: form.push_mode,
        status: form.status,
        questions: serializeQuestions()
    })
    ElMessage.success('草稿已保存')
    await loadConfig()
}

const publishConfig = async () => {
    await myCoupleQuestionnairePublish({
        title: form.title,
        description: form.description,
        push_mode: form.push_mode,
        status: form.status,
        questions: serializeQuestions()
    })
    ElMessage.success('新版已发布')
    await loadConfig()
    await loadTasks()
}

const addBlankQuestion = () => {
    form.questions.push(normalizeQuestion({
        id: 0,
        bank_id: 0,
        category: '补充说明',
        type: 'textarea',
        title: '',
        placeholder: '',
        options: [],
        required: 0,
        sort: 0
    }, form.questions.length))
}

const addFromBank = (row: any) => {
    form.questions.push(normalizeQuestion(row, form.questions.length))
}

const removeQuestion = (index: number) => {
    form.questions.splice(index, 1)
}

const moveQuestion = (index: number, delta: number) => {
    const target = index + delta
    if (target < 0 || target >= form.questions.length) {
        return
    }
    const [item] = form.questions.splice(index, 1)
    form.questions.splice(target, 0, item)
}

const loadTasks = async () => {
    taskPager.loading = true
    try {
        const res = await myCoupleQuestionnaireTasks({
            page: taskPager.page,
            limit: taskPager.size,
            status: taskSearch.status,
            send_status: taskSearch.send_status
        })
        const data = res?.data || res || {}
        taskPager.lists = data.lists || []
        taskPager.count = Number(data.total || 0)
    } finally {
        taskPager.loading = false
    }
}

const resetTaskPage = () => {
    taskPager.page = 1
    loadTasks()
}

const resetTaskSearch = () => {
    taskSearch.status = ''
    taskSearch.send_status = ''
    resetTaskPage()
}

const sendTask = async (row: any) => {
    await ElMessageBox.confirm('本次会通过站内消息提醒客户填写新人问卷。', '发送新人问卷', { type: 'info' })
    await myCoupleQuestionnaireSend({ id: row.id })
    ElMessage.success('已发送')
    await loadTasks()
}

const viewTask = async (row: any) => {
    const res = await myCoupleQuestionnaireTaskDetail({ id: row.id })
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

onMounted(async () => {
    await loadConfig()
    await loadTasks()
})
</script>

<style scoped lang="scss">
.questionnaire-panel {
    min-height: 100%;
}

.panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-weight: 600;
}

.question-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.question-item {
    border: 1px solid var(--el-border-color-light);
    border-radius: 8px;
    padding: 14px;
    background: var(--el-fill-color-blank);
}

.question-item__top {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.question-item__index {
    color: var(--el-text-color-secondary);
}

.question-item__actions {
    margin-left: auto;
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
