<template>
    <admin-page-shell class="questionnaire-hub" title="新人问卷">
        <template #extra>
            <el-button
                v-perms="['growth.coupleQuestionnaire/send']"
                type="primary"
                @click="sendDialogVisible = true"
            >
                按订单补发问卷
            </el-button>
            <el-button @click="refreshActivePanel">刷新</el-button>
        </template>

        <template #stats>
            <div class="questionnaire-hub__stats">
                <div
                    class="questionnaire-hub__stat"
                    :class="{ 'is-active': activeStatKey === 'published' }"
                    @click="applyStat('published')"
                >
                    <span class="questionnaire-hub__stat-label">已发布模板</span>
                    <strong class="questionnaire-hub__stat-value">
                        {{ overview.published_count }} / {{ overview.staff_total }}
                    </strong>
                    <span class="questionnaire-hub__stat-hint">
                        已配置 {{ overview.configured_count }} 人
                    </span>
                </div>
                <div
                    class="questionnaire-hub__stat"
                    :class="{ 'is-active': activeStatKey === 'unpublished' }"
                    @click="applyStat('unpublished')"
                >
                    <span class="questionnaire-hub__stat-label">待发布人员</span>
                    <strong class="questionnaire-hub__stat-value">
                        {{ overview.unpublished_count }}
                    </strong>
                    <span class="questionnaire-hub__stat-hint">
                        未初始化 {{ overview.unconfigured_count }} 人
                    </span>
                </div>
                <div
                    class="questionnaire-hub__stat"
                    :class="{ 'is-active': activeStatKey === 'pending' }"
                    @click="applyStat('pending')"
                >
                    <span class="questionnaire-hub__stat-label">待回收任务</span>
                    <strong class="questionnaire-hub__stat-value">
                        {{ overview.active_task_count }}
                    </strong>
                    <span class="questionnaire-hub__stat-hint">
                        待填写 {{ overview.pending_count }} · 已查看 {{ overview.viewed_count }}
                    </span>
                </div>
                <div
                    class="questionnaire-hub__stat"
                    :class="{ 'is-active': activeStatKey === 'submitted' }"
                    @click="applyStat('submitted')"
                >
                    <span class="questionnaire-hub__stat-label">已提交 / 回收率</span>
                    <strong class="questionnaire-hub__stat-value">
                        {{ overview.submitted_count }}
                    </strong>
                    <span class="questionnaire-hub__stat-hint">回收率 {{ overview.submit_rate }}%</span>
                </div>
                <div
                    class="questionnaire-hub__stat"
                    :class="{ 'is-active': activeStatKey === 'failed' }"
                    @click="applyStat('failed')"
                >
                    <span class="questionnaire-hub__stat-label">推送异常</span>
                    <strong class="questionnaire-hub__stat-value">
                        {{ overview.send_failed_count }}
                    </strong>
                    <span
                        class="questionnaire-hub__stat-hint"
                        :class="{ 'is-danger': overview.send_failed_count > 0 }"
                    >
                        {{
                            overview.send_failed_count > 0
                                ? '点击查看失败明细'
                                : '发送通道正常'
                        }}
                    </span>
                </div>
                <div
                    class="questionnaire-hub__stat"
                    :class="{ 'is-active': activeStatKey === 'bank' }"
                    @click="applyStat('bank')"
                >
                    <span class="questionnaire-hub__stat-label">基础题库</span>
                    <strong class="questionnaire-hub__stat-value">
                        {{ overview.bank_enabled_count }} / {{ overview.bank_total_count }}
                    </strong>
                    <span class="questionnaire-hub__stat-hint">启用中 / 总题数</span>
                </div>
            </div>
        </template>

        <el-card shadow="never" class="!border-none admin-hub-card">
            <el-empty
                v-if="!visibleTabs.length"
                description="当前账号暂无可访问的子页面权限"
            />
            <el-tabs
                v-else
                v-model="activeTab"
                class="questionnaire-hub__tabs"
                @tab-change="handleTabChange"
            >
                <el-tab-pane v-if="canAccess('lists')" label="问卷配置" name="lists" lazy>
                    <StaffConfigPanel
                        ref="configPanelRef"
                        :preset-filter="configPresetFilter"
                        @overview="handleOverview"
                        @open-config="openConfigDrawer"
                        @view-tasks="goStaffTasks"
                    />
                </el-tab-pane>
                <el-tab-pane v-if="canAccess('tasks')" name="tasks" lazy>
                    <template #label>
                        <span class="questionnaire-hub__tab-label">
                            问卷任务
                            <span
                                v-if="overview.send_failed_count > 0"
                                class="questionnaire-hub__tab-badge questionnaire-hub__tab-badge--danger"
                            >
                                {{ overview.send_failed_count }}
                            </span>
                            <span
                                v-else-if="overview.active_task_count > 0"
                                class="questionnaire-hub__tab-badge"
                            >
                                {{ overview.active_task_count }}
                            </span>
                        </span>
                    </template>
                    <TaskListPanel
                        ref="taskPanelRef"
                        :preset-filter="taskPresetFilter"
                        @overview="handleOverview"
                        @open-send-by-order="sendDialogVisible = true"
                    />
                </el-tab-pane>
                <el-tab-pane v-if="canAccess('bank')" label="基础题库" name="bank" lazy>
                    <QuestionBankPanel ref="bankPanelRef" @changed="handleBankChanged" />
                </el-tab-pane>
            </el-tabs>
        </el-card>

        <StaffConfigDrawer
            v-model="configDrawer.visible"
            :staff-id="configDrawer.staffId"
            :staff-name="configDrawer.staffName"
            @saved="handleConfigUpdated"
            @published="handleConfigUpdated"
        />
        <SendByOrderDialog v-model="sendDialogVisible" @success="handleSendByOrderSuccess" />
    </admin-page-shell>
</template>

<script lang="ts" setup name="questionnaireHub">
/**
 * 新人问卷 Hub：把「问卷配置 / 问卷任务 / 基础题库」收口成一个运营工作台。
 * Tab 按 perms 过滤，KPI 卡点击后携带预设筛选切到对应面板；
 * 配置抽屉与按订单补发都留在 Hub 内完成，不再跳旧路由页。
 */
import type { QuestionnaireOverview } from '@/api/questionnaire'
import { coupleQuestionnaireLists } from '@/api/questionnaire'
import { hasPermission } from '@/utils/perm'

import QuestionBankPanel from '../components/QuestionBankPanel.vue'
import SendByOrderDialog from '../components/SendByOrderDialog.vue'
import StaffConfigDrawer from '../components/StaffConfigDrawer.vue'
import StaffConfigPanel from '../components/StaffConfigPanel.vue'
import TaskListPanel from '../components/TaskListPanel.vue'
import type { StaffConfigPresetFilter, TaskPresetFilter } from '../shared'
import { createEmptyOverview } from '../shared'

type TabName = 'lists' | 'tasks' | 'bank'

interface HubTab {
    name: TabName
    label: string
    perms: string[]
}

const HUB_TABS: HubTab[] = [
    { name: 'lists', label: '问卷配置', perms: ['growth.coupleQuestionnaire/lists'] },
    { name: 'tasks', label: '问卷任务', perms: ['growth.coupleQuestionnaire/tasks'] },
    { name: 'bank', label: '基础题库', perms: ['growth.coupleQuestionBank/lists'] }
]

const STAT_TAB: Record<string, TabName> = {
    published: 'lists',
    unpublished: 'lists',
    pending: 'tasks',
    submitted: 'tasks',
    failed: 'tasks',
    bank: 'bank'
}

const route = useRoute()
const router = useRouter()

const visibleTabs = computed(() => HUB_TABS.filter((tab) => hasPermission(tab.perms)))
const canAccess = (name: TabName) => visibleTabs.value.some((tab) => tab.name === name)

const resolveTab = (value: unknown): TabName | '' => {
    const target = String(value ?? '')
    if (target && visibleTabs.value.some((tab) => tab.name === target)) {
        return target as TabName
    }
    return (visibleTabs.value[0]?.name as TabName) || ''
}

const activeTab = ref<TabName | ''>(resolveTab(route.query.tab))
const activeStatKey = ref('')
const overview = reactive<QuestionnaireOverview>(createEmptyOverview())

const configPresetFilter = ref<StaffConfigPresetFilter>()
const taskPresetFilter = ref<TaskPresetFilter>()
const configPanelRef = ref<any>()
const taskPanelRef = ref<any>()
const bankPanelRef = ref<any>()
const sendDialogVisible = ref(false)
const configDrawer = reactive({
    visible: false,
    staffId: 0,
    staffName: ''
})

const handleOverview = (next: QuestionnaireOverview) => {
    Object.assign(overview, createEmptyOverview(), next)
    overview.unconfigured_count = Math.max(
        0,
        Number(overview.staff_total || 0) - Number(overview.configured_count || 0)
    )
}

/**
 * 默认落在基础题库等无 overview 来源的 Tab 时，借问卷列表接口预热一次 KPI。
 * 接口失败不打断页面，进入配置/任务面板后会再次拉取并由面板提示错误。
 */
const refreshOverviewSample = async () => {
    if (!hasPermission(['growth.coupleQuestionnaire/lists', 'growth.coupleQuestionnaire/tasks'])) {
        return
    }
    try {
        const res: any = await coupleQuestionnaireLists({ page_no: 1, page_size: 1 })
        const payload: any = res && typeof res === 'object' && 'lists' in res ? res : (res?.data ?? res)
        if (payload?.overview) {
            handleOverview({ ...createEmptyOverview(), ...payload.overview })
        }
    } catch {
        // 预热失败保持静默，避免首屏多一条与当前面板无关的错误提示
    }
}

const refreshActivePanel = () => {
    if (activeTab.value === 'lists') {
        configPanelRef.value?.reload()
        return
    }
    if (activeTab.value === 'tasks') {
        taskPanelRef.value?.reload()
        return
    }
    if (activeTab.value === 'bank') {
        bankPanelRef.value?.reload()
    }
}

/**
 * KPI 点击会把面板筛选重置为固定条件，同步清掉 URL 上冲突的旧筛选，
 * 否则刷新或再次进入时会用旧 keyword/staff_id 覆盖 KPI 预设。
 */
const syncStatQuery = (target: TabName) => {
    const nextQuery: Record<string, any> = { ...route.query, tab: target }
    if (target === 'lists') {
        delete nextQuery.keyword
    } else if (target === 'tasks') {
        delete nextQuery.keyword
        delete nextQuery.order_id
        delete nextQuery.staff_id
        delete nextQuery.status
        delete nextQuery.send_status
    }
    router.replace({ query: nextQuery })
}

const applyStat = (key: string) => {
    const target = STAT_TAB[key]
    if (!target || !canAccess(target)) {
        return
    }
    syncStatQuery(target)
    if (key === 'published') {
        configPresetFilter.value = {
            published: 1,
            push_mode: '',
            status: '',
            keyword: '',
            _ts: Date.now()
        }
    } else if (key === 'unpublished') {
        configPresetFilter.value = {
            published: 0,
            push_mode: '',
            status: '',
            keyword: '',
            _ts: Date.now()
        }
    } else if (key === 'pending') {
        taskPresetFilter.value = {
            staff_id: '',
            keyword: '',
            status: 0,
            send_status: '',
            _ts: Date.now()
        }
    } else if (key === 'submitted') {
        taskPresetFilter.value = {
            staff_id: '',
            keyword: '',
            status: 1,
            send_status: '',
            _ts: Date.now()
        }
    } else if (key === 'failed') {
        taskPresetFilter.value = {
            staff_id: '',
            keyword: '',
            status: '',
            send_status: 2,
            _ts: Date.now()
        }
    }
    activeStatKey.value = key
    activeTab.value = target
}

const openConfigDrawer = (row: any) => {
    configDrawer.staffId = Number(row?.staff_id || row?.staff?.id || 0)
    configDrawer.staffName = String(row?.staff_name || row?.staff?.name || '')
    configDrawer.visible = true
}

const handleConfigUpdated = () => {
    configPanelRef.value?.reload()
}

const goStaffTasks = (row: any) => {
    if (!canAccess('tasks')) {
        return
    }
    const staffId = Number(row?.staff_id || row?.staff?.id || 0)
    taskPresetFilter.value = {
        staff_id: staffId || '',
        keyword: '',
        status: '',
        send_status: '',
        _ts: Date.now()
    }
    const nextQuery: Record<string, any> = {
        ...route.query,
        tab: 'tasks'
    }
    if (staffId) {
        nextQuery.staff_id = String(staffId)
    } else {
        delete nextQuery.staff_id
    }
    delete nextQuery.keyword
    delete nextQuery.order_id
    delete nextQuery.status
    delete nextQuery.send_status
    router.replace({ query: nextQuery })
    activeTab.value = 'tasks'
}

const handleSendByOrderSuccess = async () => {
    if (canAccess('tasks')) {
        activeTab.value = 'tasks'
        if (String(route.query.tab ?? '') !== 'tasks') {
            router.replace({ query: { ...route.query, tab: 'tasks' } })
        }
    }
    await nextTick()
    taskPanelRef.value?.reload()
    configPanelRef.value?.reload()
}

const handleBankChanged = () => {
    void refreshOverviewSample()
}

const handleTabChange = (name: string | number) => {
    const next = String(name) as TabName
    if (!next) {
        return
    }
    if (activeStatKey.value && STAT_TAB[activeStatKey.value] !== next) {
        activeStatKey.value = ''
    }
    if (String(route.query.tab ?? '') !== next) {
        router.replace({ query: { ...route.query, tab: next } })
    }
}

watch(
    () => route.query.tab,
    (value) => {
        const next = resolveTab(value)
        if (next && next !== activeTab.value) {
            activeTab.value = next
        }
    }
)

watch(visibleTabs, () => {
    const next = resolveTab(route.query.tab) || resolveTab(activeTab.value)
    if (next && next !== activeTab.value) {
        activeTab.value = next
    }
})

onMounted(() => {
    if (overview.staff_total === 0 && !['lists', 'tasks'].includes(String(activeTab.value))) {
        void refreshOverviewSample()
    }
})
</script>

<style scoped lang="scss">
.questionnaire-hub {
    &__stats {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
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

        &.is-danger {
            color: var(--el-color-danger);
        }
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

        &--danger {
            background: var(--el-color-danger-light-9);
            color: var(--el-color-danger);
        }
    }
}

.admin-hub-card {
    :deep(.el-card__body) {
        padding-top: 8px;
    }
}

@media (max-width: 1200px) {
    .questionnaire-hub__stats {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .questionnaire-hub__stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
