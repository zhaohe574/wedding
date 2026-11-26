<template>
    <admin-page-shell class="staff-questionnaire" title="新人问卷">
        <template #extra>
            <el-button type="primary" @click="sendDialogVisible = true">按订单补发问卷</el-button>
            <el-button :loading="refreshLoading" @click="refreshActive">刷新</el-button>
        </template>

        <template #stats>
            <div class="staff-questionnaire__stats">
                <div class="staff-questionnaire__stat" @click="switchTab('config')">
                    <span class="staff-questionnaire__stat-label">当前发布版本</span>
                    <strong class="staff-questionnaire__stat-value">
                        {{ latestVersionNo > 0 ? `v${latestVersionNo}` : '未发布' }}
                    </strong>
                    <span class="staff-questionnaire__stat-hint">
                        {{ Number(form.status) === 1 ? '启用中' : '已停用' }} ·
                        {{ pushSchedulePreview }}
                    </span>
                </div>
                <div class="staff-questionnaire__stat" @click="switchTab('config')">
                    <span class="staff-questionnaire__stat-label">草稿题目</span>
                    <strong class="staff-questionnaire__stat-value">
                        {{ form.questions.length }} 题
                    </strong>
                    <span class="staff-questionnaire__stat-hint">
                        必填 {{ requiredQuestionCount }} 题 · 选填 {{ optionalQuestionCount }} 题
                    </span>
                </div>
                <div class="staff-questionnaire__stat" @click="applyPendingStat">
                    <span class="staff-questionnaire__stat-label">待回收任务</span>
                    <strong class="staff-questionnaire__stat-value">{{ pendingTaskCount }}</strong>
                    <span class="staff-questionnaire__stat-hint">待填写与已查看，点击查看</span>
                </div>
                <div class="staff-questionnaire__stat" @click="applyAllStat">
                    <span class="staff-questionnaire__stat-label">累计问卷任务</span>
                    <strong class="staff-questionnaire__stat-value">
                        {{ allTaskTotal ?? taskPager.count }}
                    </strong>
                    <span class="staff-questionnaire__stat-hint">
                        {{
                            allTaskTotal !== null ? '点击查看全部任务' : '点击同步全部任务'
                        }}
                    </span>
                </div>
            </div>
        </template>

        <el-card shadow="never" class="!border-none admin-hub-card">
            <el-tabs
                v-model="activeTab"
                class="staff-questionnaire__tabs"
                @tab-change="handleTabChange"
            >
                <el-tab-pane label="问卷配置" name="config">
                    <el-alert
                        v-if="configError"
                        class="staff-questionnaire__alert"
                        type="error"
                        :title="configError"
                        :closable="false"
                        show-icon
                    >
                        <template #default>
                            <el-button type="primary" link @click="loadConfig">重试</el-button>
                        </template>
                    </el-alert>

                    <StaffSelfConfigPanel
                        ref="configPanelRef"
                        :form="form"
                        :latest-version-no="latestVersionNo"
                        :categories="categories"
                        :question-types="questionTypes"
                        :bank-questions="bankQuestions"
                        :loading="configLoading"
                        :saving="saving"
                        :publishing="publishing"
                        :invalid-index="invalidIndex"
                        @refresh="loadConfig"
                        @save="saveConfig"
                        @publish="publishConfig"
                        @clear-invalid="invalidIndex = -1"
                    />
                </el-tab-pane>

                <el-tab-pane name="tasks" lazy>
                    <template #label>
                        <span class="staff-questionnaire__tab-label">
                            问卷任务
                            <span
                                v-if="pendingTaskCount > 0"
                                class="staff-questionnaire__tab-badge"
                            >
                                {{ pendingTaskCount }}
                            </span>
                        </span>
                    </template>

                    <search-panel>
                        <el-form class="mb-[-16px]" :inline="true" :model="taskSearch">
                            <el-form-item class="w-[260px]" label="关键词">
                                <el-input
                                    v-model="taskSearch.keyword"
                                    clearable
                                    placeholder="任务编号、订单号、订单 ID、客户姓名或手机号"
                                    @keyup.enter="resetTaskPage"
                                />
                            </el-form-item>
                            <el-form-item class="w-[140px]" label="填写状态">
                                <el-select v-model="taskSearch.status" clearable placeholder="全部">
                                    <el-option
                                        v-for="item in TASK_STATUS_OPTIONS"
                                        :key="item.value"
                                        :label="item.label"
                                        :value="item.value"
                                    />
                                </el-select>
                            </el-form-item>
                            <el-form-item class="w-[140px]" label="推送状态">
                                <el-select
                                    v-model="taskSearch.send_status"
                                    clearable
                                    placeholder="全部"
                                >
                                    <el-option
                                        v-for="item in SEND_STATUS_OPTIONS"
                                        :key="item.value"
                                        :label="item.label"
                                        :value="item.value"
                                    />
                                </el-select>
                            </el-form-item>
                            <el-form-item>
                                <el-button type="primary" @click="resetTaskPage">查询</el-button>
                                <el-button @click="resetTaskSearch">重置</el-button>
                            </el-form-item>
                        </el-form>
                    </search-panel>

                    <admin-table-toolbar class="mt-4">
                        <template #left>
                            <div class="staff-questionnaire__quick-views">
                                <button
                                    v-for="view in QUICK_VIEWS"
                                    :key="view.value"
                                    type="button"
                                    class="staff-questionnaire__quick-view"
                                    :class="{ 'is-active': activeQuickView === view.value }"
                                    @click="applyQuickView(view.value)"
                                >
                                    {{ view.label }}
                                </button>
                            </div>
                        </template>
                        <template #right>
                            <el-button type="primary" @click="sendDialogVisible = true">
                                按订单补发
                            </el-button>
                            <el-button :loading="taskPager.loading" @click="loadTasks">
                                刷新
                            </el-button>
                        </template>
                    </admin-table-toolbar>

                    <el-alert
                        v-if="taskError"
                        class="staff-questionnaire__alert"
                        type="error"
                        :title="taskError"
                        :closable="false"
                        show-icon
                    >
                        <template #default>
                            <el-button type="primary" link @click="loadTasks">重试</el-button>
                        </template>
                    </el-alert>

                    <el-table v-loading="taskPager.loading" :data="taskPager.lists" border>
                        <el-table-column label="任务信息" min-width="180">
                            <template #default="{ row }">
                                <div class="cell-main">{{ row.task_sn || '-' }}</div>
                                <div class="cell-sub">{{ row.create_time || '-' }}</div>
                            </template>
                        </el-table-column>
                        <el-table-column label="订单与客户" min-width="190">
                            <template #default="{ row }">
                                <div class="cell-main">
                                    {{ row.order?.order_sn || row.order_id || '-' }}
                                </div>
                                <div class="cell-sub">{{ getCustomerText(row) }}</div>
                            </template>
                        </el-table-column>
                        <el-table-column label="问卷标题与版本" min-width="200">
                            <template #default="{ row }">
                                <div class="cell-main">{{ row.title_snapshot || '新人问卷' }}</div>
                                <el-tag class="mt-1" size="small" effect="plain" type="info">
                                    {{
                                        Number(row.version_no || 0) > 0
                                            ? `v${row.version_no}`
                                            : '无版本'
                                    }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="填写状态" width="170">
                            <template #default="{ row }">
                                <el-tag
                                    :type="getTaskStatusMeta(row.status).tagType"
                                    effect="plain"
                                    size="small"
                                >
                                    {{ row.status_desc || getTaskStatusMeta(row.status).label }}
                                </el-tag>
                                <div v-if="getStatusTimeHint(row)" class="cell-sub">
                                    {{ getStatusTimeHint(row) }}
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="推送状态" width="190">
                            <template #default="{ row }">
                                <el-tag
                                    :type="getSendStatusMeta(row.send_status).tagType"
                                    effect="plain"
                                    size="small"
                                >
                                    {{ row.send_status_desc || getSendStatusMeta(row.send_status).label }}
                                </el-tag>
                                <div class="cell-sub">推送 {{ Number(row.send_count || 0) }} 次</div>
                                <div
                                    v-if="
                                        Number(row.send_status || 0) === 0 && row.planned_send_time
                                    "
                                    class="cell-planned"
                                >
                                    <el-tooltip
                                        :content="row.push_schedule_desc || '到点由系统自动推送'"
                                        placement="top"
                                    >
                                        <span>计划：{{ row.planned_send_time }}</span>
                                    </el-tooltip>
                                </div>
                                <div
                                    v-if="Number(row.send_status) === 2 && row.send_error"
                                    class="cell-error"
                                >
                                    <el-tooltip :content="row.send_error" placement="top">
                                        <span>{{ row.send_error }}</span>
                                    </el-tooltip>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="最近推送" width="165">
                            <template #default="{ row }">
                                {{ row.last_send_time || row.send_time || '-' }}
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="220" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="openTaskDetail(row)">
                                    详情
                                </el-button>
                                <el-button
                                    v-if="isTaskResendable(row)"
                                    type="success"
                                    link
                                    :loading="sendingId === Number(row.id)"
                                    @click="sendTask(row)"
                                >
                                    {{ getTaskSendActionLabel(row) }}
                                </el-button>
                                <el-button type="info" link @click="copyQuestionnairePath(row)">
                                    复制入口
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <div class="flex justify-end mt-4">
                        <pagination v-model="taskPager" @change="loadTasks" />
                    </div>
                </el-tab-pane>
            </el-tabs>
        </el-card>

        <TaskDetailDrawer
            v-model="detailVisible"
            :task-id="activeTaskId"
            :fetch-detail="myCoupleQuestionnaireTaskDetail"
            :send-task-api="myCoupleQuestionnaireSend"
            skip-perms
            hide-staff-info
            @sent="handleTaskSent"
        />

        <SendByOrderDialog
            v-model="sendDialogVisible"
            :fetch-orders="myOrders"
            :send-by-order="myCoupleQuestionnaireSend"
            alert-title="仅可选择你名下待服务 / 服务中 / 已完成 / 已评价的订单，且需先发布问卷版本"
            @success="handleSendByOrderSuccess"
        />
    </admin-page-shell>
</template>

<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus'
import { useRoute, useRouter } from 'vue-router'

import {
    myCoupleQuestionnaireConfig,
    myCoupleQuestionnairePublish,
    myCoupleQuestionnaireSave,
    myCoupleQuestionnaireSend,
    myCoupleQuestionnaireTaskDetail,
    myCoupleQuestionnaireTasks,
    myOrders
} from '@/api/staff-center'

import SendByOrderDialog from '../../questionnaire/components/SendByOrderDialog.vue'
import TaskDetailDrawer from '../../questionnaire/components/TaskDetailDrawer.vue'
import type { EditableQuestionItem } from '../../questionnaire/shared'
import {
    copyQuestionnairePath,
    extractErrorMessage,
    formatPushScheduleSummary,
    getSendActionLabel,
    getSendStatusMeta,
    getTaskStatusMeta,
    isTaskResendable,
    normalizeEditableQuestion,
    QUESTION_TYPES,
    SEND_STATUS_OPTIONS,
    serializeEditableQuestions,
    TASK_STATUS_OPTIONS,
    validateAutoPushConfig,
    validateQuestionnaireForm
} from '../../questionnaire/shared'
import StaffSelfConfigPanel from './components/StaffSelfConfigPanel.vue'

type TabName = 'config' | 'tasks'
type QuickViewValue = 'all' | 'pending' | 'viewed' | 'submitted' | 'send_failed'

/**
 * 任务列表接口的 status 是单值筛选，无法一次同时匹配「待填写 0 / 已查看 3」，
 * 因此拆成独立快捷视图，避免出现点击后列表与文案不符的情况。
 */
const QUICK_VIEWS: Array<{ value: QuickViewValue; label: string }> = [
    { value: 'all', label: '全部任务' },
    { value: 'pending', label: '待填写' },
    { value: 'viewed', label: '已查看' },
    { value: 'submitted', label: '已填写' },
    { value: 'send_failed', label: '推送失败' }
]

const route = useRoute()
const router = useRouter()

const resolveQueryNumber = (value: unknown): number | '' => {
    if (value === undefined || value === null || value === '') {
        return ''
    }
    const num = Number(value)
    return Number.isFinite(num) ? num : ''
}

const hasTaskQuery = (query: Record<string, any>) =>
    String(query.keyword ?? '') !== '' ||
    String(query.status ?? '') !== '' ||
    String(query.send_status ?? '') !== ''

const resolveTab = (value: unknown, fallbackToTasks: boolean): TabName => {
    const target = String(value ?? '')
    if (target === 'config' || target === 'tasks') {
        return target
    }
    return fallbackToTasks ? 'tasks' : 'config'
}

const activeTab = ref<TabName>(resolveTab(route.query.tab, hasTaskQuery(route.query)))

const config = ref<any>({})
const configLoading = ref(false)
const configError = ref('')
const saving = ref(false)
const publishing = ref(false)
const invalidIndex = ref(-1)
const categories = ref<string[]>([])
const questionTypes = ref<Array<{ value: string; label: string }>>([...QUESTION_TYPES])
const bankQuestions = ref<any[]>([])
const configPanelRef = ref<any>(null)

const form = reactive({
    title: '',
    description: '',
    push_mode: 1,
    status: 1,
    auto_push_trigger: 1,
    auto_push_days: 15,
    auto_push_time: '10:00',
    questions: [] as EditableQuestionItem[]
})

const taskSearch = reactive({
    keyword: String(route.query.keyword || ''),
    status: resolveQueryNumber(route.query.status),
    send_status: resolveQueryNumber(route.query.send_status)
})
const taskPager = reactive({
    page: 1,
    size: 15,
    count: 0,
    lists: [] as any[],
    loading: false
})
const taskError = ref('')
const taskLoaded = ref(false)
const allTaskTotal = ref<number | null>(null)
const sendingId = ref<number | null>(null)
const detailVisible = ref(false)
const activeTaskId = ref(0)
const sendDialogVisible = ref(false)

const latestVersionNo = computed(() =>
    Number(config.value?.latest_version?.version_no || config.value?.published_version_no || 0)
)

const pendingTaskCount = computed(() => Number(config.value?.pending_task_count || 0))

const requiredQuestionCount = computed(
    () => form.questions.filter((item) => Number(item.required) === 1).length
)

const optionalQuestionCount = computed(
    () => form.questions.length - requiredQuestionCount.value
)

const pushSchedulePreview = computed(() =>
    formatPushScheduleSummary(
        form.push_mode,
        form.auto_push_trigger,
        form.auto_push_days,
        form.auto_push_time
    )
)

const refreshLoading = computed(() =>
    activeTab.value === 'tasks' ? taskPager.loading : configLoading.value
)

const activeQuickView = computed<QuickViewValue>(() => {
    if (taskSearch.send_status === 2) {
        return 'send_failed'
    }
    if (taskSearch.status === 0) {
        return 'pending'
    }
    if (taskSearch.status === 3) {
        return 'viewed'
    }
    if (taskSearch.status === 1) {
        return 'submitted'
    }
    return 'all'
})

const normalizeConfigPayload = (res: any): any => {
    if (
        res &&
        typeof res === 'object' &&
        !Array.isArray(res) &&
        ('draft_questions' in res || 'push_mode' in res)
    ) {
        return res
    }
    return res?.data ?? res ?? {}
}

const applyConfig = (data: any) => {
    config.value = data || {}
    categories.value = Array.isArray(data?.categories)
        ? data.categories.map((item: any) => String(item || '')).filter(Boolean)
        : []
    const detailTypes = Array.isArray(data?.question_types)
        ? data.question_types
              .map((item: any) => ({
                  value: String(item?.value || ''),
                  label: String(item?.label || '')
              }))
              .filter((item: any) => item.value)
        : []
    questionTypes.value = detailTypes.length ? detailTypes : [...QUESTION_TYPES]
    bankQuestions.value = Array.isArray(data?.bank_questions) ? data.bank_questions : []
    form.title = String(data?.title || '新人婚礼资料问卷')
    form.description = String(data?.description || '')
    form.push_mode = Number(data?.push_mode ?? 1) === 2 ? 2 : 1
    form.status = Number(data?.status ?? 1) === 0 ? 0 : 1
    const trigger = [1, 2, 3].includes(Number(data?.auto_push_trigger))
        ? Number(data.auto_push_trigger)
        : 1
    form.auto_push_trigger = trigger
    const rawDays = Number(data?.auto_push_days || 0)
    form.auto_push_days = rawDays > 0 ? rawDays : trigger === 3 ? 3 : 15
    form.auto_push_time = String(data?.auto_push_time || '10:00')
    form.questions = Array.isArray(data?.draft_questions)
        ? data.draft_questions.map((item: any, index: number) =>
              normalizeEditableQuestion(item, index)
          )
        : []
    invalidIndex.value = -1
}

const loadConfig = async () => {
    configLoading.value = true
    configError.value = ''
    try {
        const res: any = await myCoupleQuestionnaireConfig()
        applyConfig(normalizeConfigPayload(res))
    } catch (error) {
        configError.value = extractErrorMessage(error, '问卷配置加载失败，请稍后重试')
    } finally {
        configLoading.value = false
    }
}

/** 静默刷新待回收计数，避免 loadConfig 覆盖 Tab 1 中尚未保存的草稿。 */
const syncConfigCounters = async () => {
    try {
        const res: any = await myCoupleQuestionnaireConfig()
        const data = normalizeConfigPayload(res)
        if (config.value && typeof config.value === 'object') {
            config.value.pending_task_count = Number(data?.pending_task_count || 0)
        } else {
            config.value = { pending_task_count: Number(data?.pending_task_count || 0) }
        }
    } catch {
        // 仅用于刷新计数，失败保持静默
    }
}

const buildConfigPayload = () => ({
    title: form.title.trim(),
    description: form.description.trim(),
    push_mode: form.push_mode,
    status: form.status,
    auto_push_trigger: form.auto_push_trigger,
    auto_push_days: Number(form.auto_push_trigger) === 1 ? 0 : form.auto_push_days,
    auto_push_time: Number(form.auto_push_trigger) === 1 ? '10:00' : form.auto_push_time,
    questions: serializeEditableQuestions(form.questions)
})

const validateConfigForm = () => {
    const result = validateQuestionnaireForm({
        title: form.title,
        questions: form.questions
    })
    if (!result.valid) {
        invalidIndex.value = result.invalidIndex ?? -1
        ElMessage.warning(result.message || '请先完善问卷配置')
        configPanelRef.value?.focusQuestion(invalidIndex.value)
        return false
    }
    invalidIndex.value = -1
    const pushCheck = validateAutoPushConfig({
        push_mode: form.push_mode,
        auto_push_trigger: form.auto_push_trigger,
        auto_push_days: form.auto_push_days,
        auto_push_time: form.auto_push_time
    })
    if (!pushCheck.valid) {
        ElMessage.warning(pushCheck.message || '请先完善自动推送时间设置')
        return false
    }
    return true
}

const saveConfig = async () => {
    if (saving.value || !validateConfigForm()) {
        return
    }
    saving.value = true
    try {
        await myCoupleQuestionnaireSave(buildConfigPayload())
        ElMessage.success('草稿已保存')
        await loadConfig()
    } catch {
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    } finally {
        saving.value = false
    }
}

const publishConfig = async () => {
    if (publishing.value || !validateConfigForm()) {
        return
    }
    try {
        await ElMessageBox.confirm(
            '发布后将生成新的问卷版本。仅对后续新生成的订单问卷生效；已创建或已发送的任务保留下发时的版本与题目快照。',
            '发布新版问卷',
            { type: 'warning', confirmButtonText: '确认发布', cancelButtonText: '再检查一下' }
        )
    } catch {
        return
    }
    publishing.value = true
    try {
        await myCoupleQuestionnairePublish(buildConfigPayload())
        ElMessage.success('新版问卷已发布')
        await loadConfig()
    } catch {
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    } finally {
        publishing.value = false
    }
}

const syncQuery = () => {
    const query: Record<string, any> = { ...route.query, tab: activeTab.value }
    if (taskSearch.keyword) {
        query.keyword = taskSearch.keyword
    } else {
        delete query.keyword
    }
    if (taskSearch.status !== '') {
        query.status = String(taskSearch.status)
    } else {
        delete query.status
    }
    if (taskSearch.send_status !== '') {
        query.send_status = String(taskSearch.send_status)
    } else {
        delete query.send_status
    }
    router.replace({ query })
}

const switchTab = (tab: TabName) => {
    activeTab.value = tab
    syncQuery()
}

const handleTabChange = (name: string | number) => {
    const target = String(name)
    if (target !== 'config' && target !== 'tasks') {
        return
    }
    syncQuery()
    if (target === 'tasks' && !taskLoaded.value) {
        void loadTasks()
    }
}

const loadTasks = async () => {
    taskPager.loading = true
    taskError.value = ''
    try {
        const res: any = await myCoupleQuestionnaireTasks({
            page: taskPager.page,
            limit: taskPager.size,
            keyword: taskSearch.keyword.trim(),
            status: taskSearch.status,
            send_status: taskSearch.send_status
        })
        const data: any =
            res && typeof res === 'object' && 'lists' in res ? res : (res?.data ?? res) ?? {}
        taskPager.lists = Array.isArray(data.lists) ? data.lists : []
        taskPager.count = Number(data.total ?? data.count ?? 0)
        if (
            taskSearch.keyword === '' &&
            taskSearch.status === '' &&
            taskSearch.send_status === ''
        ) {
            allTaskTotal.value = Number(data.total || 0)
        }
    } catch (error) {
        taskPager.lists = []
        taskPager.count = 0
        taskError.value = extractErrorMessage(error, '问卷任务加载失败，请稍后重试')
    } finally {
        taskPager.loading = false
        taskLoaded.value = true
    }
}

const resetTaskPage = () => {
    taskPager.page = 1
    syncQuery()
    void loadTasks()
}

const resetTaskSearch = () => {
    taskSearch.keyword = ''
    taskSearch.status = ''
    taskSearch.send_status = ''
    resetTaskPage()
}

const applyQuickView = (view: QuickViewValue) => {
    if (view === 'all') {
        taskSearch.status = ''
        taskSearch.send_status = ''
    } else if (view === 'pending') {
        taskSearch.status = 0
        taskSearch.send_status = ''
    } else if (view === 'viewed') {
        taskSearch.status = 3
        taskSearch.send_status = ''
    } else if (view === 'submitted') {
        taskSearch.status = 1
        taskSearch.send_status = ''
    } else {
        taskSearch.status = ''
        taskSearch.send_status = 2
    }
    resetTaskPage()
}

const applyPendingStat = () => {
    taskSearch.keyword = ''
    taskSearch.send_status = ''
    taskSearch.status = 0
    taskPager.page = 1
    activeTab.value = 'tasks'
    syncQuery()
    void loadTasks()
}

const applyAllStat = () => {
    activeTab.value = 'tasks'
    syncQuery()
    resetTaskSearch()
}

const refreshActive = () => {
    if (activeTab.value === 'tasks') {
        void loadTasks()
        return
    }
    void loadConfig()
}

const getCustomerText = (row: any) => {
    const user = row?.user || {}
    const order = row?.order || {}
    const text = [user.nickname || order.contact_name, user.mobile || order.contact_mobile]
        .filter(Boolean)
        .join(' / ')
    return text || '-'
}

const getStatusTimeHint = (row: any) => {
    if (Number(row?.status ?? 0) === 1) {
        return row?.submitted_time || row?.submit_time || ''
    }
    return row?.viewed_time || row?.expire_time || ''
}

const getTaskSendActionLabel = (row: any) => getSendActionLabel(row) || '发送问卷'

const openTaskDetail = (row: any) => {
    activeTaskId.value = Number(row?.id || 0)
    detailVisible.value = true
}

const sendTask = async (row: any) => {
    const id = Number(row?.id || 0)
    if (!id || sendingId.value === id) {
        return
    }
    const isAheadOfSchedule =
        Number(row?.send_status || 0) === 0 && Boolean(row?.planned_send_time)
    const confirmText = isAheadOfSchedule
        ? `该任务原计划于 ${row.planned_send_time} 自动发送，确定现在提前发送吗？本次会通过站内消息提醒客户填写「${row?.title_snapshot || '新人问卷'}」。`
        : `确定${getTaskSendActionLabel(row)}吗？本次会通过站内消息提醒客户填写「${row?.title_snapshot || '新人问卷'}」。`
    try {
        await ElMessageBox.confirm(confirmText, '发送问卷', { type: 'info' })
    } catch {
        return
    }
    sendingId.value = id
    try {
        await myCoupleQuestionnaireSend({ id })
        ElMessage.success('问卷已发送')
        await loadTasks()
        await syncConfigCounters()
    } catch {
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    } finally {
        sendingId.value = null
    }
}

const handleTaskSent = async () => {
    await loadTasks()
    await syncConfigCounters()
}

const handleSendByOrderSuccess = async () => {
    await loadTasks()
    await syncConfigCounters()
}

watch(
    () => route.query,
    (query) => {
        const nextTab = resolveTab(query.tab, hasTaskQuery(query))
        const nextKeyword = String(query.keyword || '')
        const nextStatus = resolveQueryNumber(query.status)
        const nextSendStatus = resolveQueryNumber(query.send_status)
        const filtersChanged =
            taskSearch.keyword !== nextKeyword ||
            taskSearch.status !== nextStatus ||
            taskSearch.send_status !== nextSendStatus
        if (nextTab === activeTab.value && !filtersChanged) {
            return
        }
        activeTab.value = nextTab
        taskSearch.keyword = nextKeyword
        taskSearch.status = nextStatus
        taskSearch.send_status = nextSendStatus
        taskPager.page = 1
        if (nextTab === 'tasks') {
            void loadTasks()
        }
    },
    { deep: true }
)

onMounted(async () => {
    await loadConfig()
    if (activeTab.value === 'tasks') {
        await loadTasks()
    }
})
</script>

<style scoped lang="scss">
.staff-questionnaire {
    &__stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    &__stat {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 12px 14px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;
        background: var(--el-bg-color);
        cursor: pointer;
        transition:
            border-color 0.2s,
            background-color 0.2s;

        &:hover,
        &.is-active {
            border-color: var(--el-color-primary);
            background: var(--el-color-primary-light-9);
        }
    }

    &__stat-label {
        color: var(--el-text-color-secondary);
        font-size: 12px;
    }

    &__stat-value {
        color: var(--el-text-color-primary);
        font-size: 20px;
        font-weight: 600;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;
    }

    &__stat-hint {
        color: var(--el-text-color-secondary);
        font-size: 12px;
        line-height: 1.4;
    }

    &__tabs {
        :deep(.el-tabs__content) {
            padding-top: 8px;
        }
    }

    &__tab-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    &__tab-badge {
        min-width: 18px;
        padding: 0 5px;
        border-radius: 9px;
        background: var(--el-fill-color);
        color: var(--el-text-color-secondary);
        font-size: 11px;
        line-height: 16px;
        text-align: center;
    }

    &__alert {
        margin-bottom: 12px;
        border-radius: var(--admin-radius-sm);
    }

    &__quick-views {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    &__quick-view {
        padding: 5px 12px;
        border: 1px solid var(--admin-color-border);
        border-radius: 999px;
        background: var(--admin-surface-card);
        color: var(--admin-color-muted);
        font-size: 12px;
        line-height: 1.4;
        cursor: pointer;
        transition:
            color 0.2s ease,
            border-color 0.2s ease,
            background 0.2s ease;

        &:hover {
            color: var(--el-color-primary);
            border-color: var(--el-color-primary-light-5);
        }

        &.is-active {
            color: var(--el-color-primary);
            border-color: var(--el-color-primary-light-5);
            background: var(--el-color-primary-light-9);
            font-weight: 500;
        }
    }
}

.admin-hub-card {
    :deep(.el-card__body) {
        padding-top: 8px;
    }
}

.cell-main {
    font-size: 13px;
    font-weight: 500;
    line-height: 1.5;
    color: var(--admin-color-title);
}

.cell-sub {
    margin-top: 2px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--admin-color-muted);
}

.cell-planned {
    margin-top: 2px;
    max-width: 160px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--el-color-primary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: help;
}

.cell-error {
    margin-top: 4px;
    max-width: 170px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--el-color-danger);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: help;
}

@media (max-width: 1200px) {
    .staff-questionnaire__stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .staff-questionnaire__stats {
        grid-template-columns: 1fr;
    }
}
</style>
