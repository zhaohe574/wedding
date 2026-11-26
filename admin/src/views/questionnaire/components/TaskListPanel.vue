<template>
    <div class="task-list-panel">
        <search-panel>
            <el-form class="mb-[-16px]" :inline="true" :model="queryParams">
                <el-form-item v-if="!hideStaffFilter" class="w-[180px]" label="服务人员">
                    <el-select
                        v-model="queryParams.staff_id"
                        clearable
                        filterable
                        placeholder="全部"
                    >
                        <el-option
                            v-for="item in staffOptions"
                            :key="item.id"
                            :label="item.name"
                            :value="Number(item.id)"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[250px]" label="关键词">
                    <el-input
                        v-model="queryParams.keyword"
                        clearable
                        placeholder="任务编号、订单号、订单 ID、客户姓名、手机号或服务人员"
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[140px]" label="填写状态">
                    <el-select v-model="queryParams.status" clearable placeholder="全部">
                        <el-option
                            v-for="item in TASK_STATUS_OPTIONS"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[140px]" label="推送状态">
                    <el-select v-model="queryParams.send_status" clearable placeholder="全部">
                        <el-option
                            v-for="item in SEND_STATUS_OPTIONS"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>

            <template v-if="queryParams.order_id !== ''" #extra>
                <el-tag closable type="info" @close="clearOrderFilter">
                    订单 ID：{{ queryParams.order_id }}
                </el-tag>
            </template>
        </search-panel>

        <el-alert
            v-if="listError"
            class="mt-4"
            type="error"
            :title="listError"
            :closable="false"
            show-icon
        >
            <template #default>
                <el-button type="primary" link @click="getLists()">重试</el-button>
            </template>
        </el-alert>

        <admin-table-toolbar class="mt-4">
            <template #left>
                <div class="quick-views">
                    <button
                        v-for="view in QUICK_VIEWS"
                        :key="view.value"
                        type="button"
                        class="quick-views__item"
                        :class="{ 'is-active': activeQuickView === view.value }"
                        @click="applyQuickView(view.value)"
                    >
                        {{ view.label }}
                    </button>
                </div>
            </template>
            <template #right>
                <el-button
                    v-perms="['growth.coupleQuestionnaire/send']"
                    type="primary"
                    @click="emit('open-send-by-order')"
                >
                    按订单补发
                </el-button>
                <el-button :loading="pager.loading" @click="getLists()">刷新</el-button>
            </template>
        </admin-table-toolbar>

        <el-table v-loading="pager.loading" :data="pager.lists" border>
            <el-table-column label="任务信息" min-width="180">
                <template #default="{ row }">
                    <div class="cell-main">{{ row.task_sn || '-' }}</div>
                    <div class="cell-sub">{{ row.create_time || '-' }}</div>
                </template>
            </el-table-column>
            <el-table-column label="订单与客户" min-width="190">
                <template #default="{ row }">
                    <div class="cell-main">{{ row.order?.order_sn || row.order_id || '-' }}</div>
                    <div class="cell-sub">{{ getCustomerText(row) }}</div>
                </template>
            </el-table-column>
            <el-table-column label="服务人员" width="110">
                <template #default="{ row }">
                    {{ row.staff?.name || row.staff_name || '-' }}
                </template>
            </el-table-column>
            <el-table-column label="问卷标题与版本" min-width="200">
                <template #default="{ row }">
                    <div class="cell-main">{{ row.title_snapshot || '新人问卷' }}</div>
                    <el-tag class="mt-1" size="small" effect="plain" type="info">
                        {{ Number(row.version_no || 0) > 0 ? `v${row.version_no}` : '无版本' }}
                    </el-tag>
                </template>
            </el-table-column>
            <el-table-column label="填写状态" width="170">
                <template #default="{ row }">
                    <el-tag :type="getTaskStatusMeta(row.status).tagType" effect="plain" size="small">
                        {{ row.status_desc || getTaskStatusMeta(row.status).label }}
                    </el-tag>
                    <div v-if="getStatusTimeHint(row)" class="cell-sub">
                        {{ getStatusTimeHint(row) }}
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="推送状态" width="180">
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
                        v-if="Number(row.send_status || 0) === 0 && row.planned_send_time"
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
                    <el-button
                        v-perms="['growth.coupleQuestionnaire/taskDetail']"
                        type="primary"
                        link
                        @click="openDetail(row)"
                    >
                        详情
                    </el-button>
                    <el-button
                        v-if="isTaskResendable(row)"
                        v-perms="['growth.coupleQuestionnaire/send']"
                        type="success"
                        link
                        :loading="sendingId === Number(row.id)"
                        @click="sendTask(row)"
                    >
                        {{ getSendActionLabel(row) }}
                    </el-button>
                    <el-button type="info" link @click="copyQuestionnairePath(row)">
                        复制入口
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <div class="flex justify-end mt-4">
            <pagination v-model="pager" @change="getLists" />
        </div>

        <TaskDetailDrawer
            v-model="detailVisible"
            :task-id="activeTaskId"
            @sent="handleDetailSent"
        />
    </div>
</template>

<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus'
import { useRoute } from 'vue-router'

import { coupleQuestionnaireSend, coupleQuestionnaireTasks } from '@/api/questionnaire'
import { staffAll } from '@/api/staff'
import { usePaging } from '@/hooks/usePaging'

import type { QuestionnaireOverview, TaskPresetFilter } from '../shared'
import {
    copyQuestionnairePath,
    createEmptyOverview,
    extractErrorMessage,
    getSendActionLabel,
    getSendStatusMeta,
    getTaskStatusMeta,
    isTaskResendable,
    SEND_STATUS_OPTIONS,
    TASK_STATUS_OPTIONS
} from '../shared'
import TaskDetailDrawer from './TaskDetailDrawer.vue'

const props = defineProps<{
    presetFilter?: TaskPresetFilter
    hideStaffFilter?: boolean
}>()

const emit = defineEmits<{
    (e: 'overview', overview: QuestionnaireOverview): void
    (e: 'open-send-by-order'): void
}>()

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

const resolveQueryNumber = (value: unknown): number | '' => {
    if (value === undefined || value === null || value === '') {
        return ''
    }
    const num = Number(value)
    return Number.isFinite(num) ? num : ''
}

const queryParams = reactive({
    staff_id: resolveQueryNumber(route.query.staff_id),
    keyword: String(route.query.keyword || ''),
    status: resolveQueryNumber(route.query.status),
    send_status: resolveQueryNumber(route.query.send_status),
    order_id: resolveQueryNumber(route.query.order_id)
})

const staffOptions = ref<any[]>([])
const listError = ref('')
const sendingId = ref<number | null>(null)
const detailVisible = ref(false)
const activeTaskId = ref(0)

const activeQuickView = computed<QuickViewValue>(() => {
    if (queryParams.send_status === 2) {
        return 'send_failed'
    }
    if (queryParams.status === 0) {
        return 'pending'
    }
    if (queryParams.status === 3) {
        return 'viewed'
    }
    if (queryParams.status === 1) {
        return 'submitted'
    }
    return 'all'
})

const fetchTasks = async (params: Record<string, any>) => {
    listError.value = ''
    try {
        const res: any = await coupleQuestionnaireTasks(params)
        const payload: any =
            res && typeof res === 'object' && 'lists' in res ? res : (res?.data ?? res)
        const overview: QuestionnaireOverview = {
            ...createEmptyOverview(),
            ...(payload?.overview || {})
        }
        overview.unconfigured_count = Math.max(0, overview.staff_total - overview.configured_count)
        emit('overview', overview)
        return payload && Array.isArray(payload.lists) ? payload : { lists: [], count: 0 }
    } catch (error) {
        listError.value = extractErrorMessage(error, '问卷任务加载失败，请稍后重试')
        return { lists: [], count: 0 }
    }
}

const { pager, getLists, resetPage } = usePaging({
    fetchFun: fetchTasks,
    params: queryParams,
    firstLoading: true
})

const loadStaffOptions = async () => {
    try {
        const res: any = await staffAll()
        staffOptions.value = Array.isArray(res) ? res : Array.isArray(res?.data) ? res.data : []
    } catch {
        staffOptions.value = []
    }
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

const applyQuickView = (view: QuickViewValue) => {
    if (view === 'all') {
        queryParams.status = ''
        queryParams.send_status = ''
    } else if (view === 'pending') {
        queryParams.status = 0
        queryParams.send_status = ''
    } else if (view === 'viewed') {
        queryParams.status = 3
        queryParams.send_status = ''
    } else if (view === 'submitted') {
        queryParams.status = 1
        queryParams.send_status = ''
    } else {
        queryParams.status = ''
        queryParams.send_status = 2
    }
    resetPage()
}

const handleReset = () => {
    queryParams.staff_id = ''
    queryParams.keyword = ''
    queryParams.status = ''
    queryParams.send_status = ''
    queryParams.order_id = ''
    resetPage()
}

const clearOrderFilter = () => {
    queryParams.order_id = ''
    resetPage()
}

const readRouteQuery = (query: Record<string, any>) => ({
    staff_id: resolveQueryNumber(query.staff_id),
    keyword: String(query.keyword || ''),
    status: resolveQueryNumber(query.status),
    send_status: resolveQueryNumber(query.send_status),
    order_id: resolveQueryNumber(query.order_id)
})

const applyFilter = (filter: Partial<TaskPresetFilter>, shouldReset = true) => {
    let changed = false
    if (filter.staff_id !== undefined && queryParams.staff_id !== filter.staff_id) {
        queryParams.staff_id = filter.staff_id
        changed = true
    }
    if (filter.keyword !== undefined && queryParams.keyword !== filter.keyword) {
        queryParams.keyword = filter.keyword
        changed = true
    }
    if (filter.status !== undefined && queryParams.status !== filter.status) {
        queryParams.status = filter.status
        changed = true
    }
    if (filter.send_status !== undefined && queryParams.send_status !== filter.send_status) {
        queryParams.send_status = filter.send_status
        changed = true
    }
    if (filter._ts !== undefined) {
        changed = true
    }
    if (changed && shouldReset) {
        resetPage()
    }
}

if (props.presetFilter) {
    applyFilter(props.presetFilter, false)
}

watch(
    () => route.query,
    (query) => {
        const next = readRouteQuery(query)
        let changed = false
        if (queryParams.staff_id !== next.staff_id) {
            queryParams.staff_id = next.staff_id
            changed = true
        }
        if (queryParams.keyword !== next.keyword) {
            queryParams.keyword = next.keyword
            changed = true
        }
        if (queryParams.status !== next.status) {
            queryParams.status = next.status
            changed = true
        }
        if (queryParams.send_status !== next.send_status) {
            queryParams.send_status = next.send_status
            changed = true
        }
        if (queryParams.order_id !== next.order_id) {
            queryParams.order_id = next.order_id
            changed = true
        }
        if (changed) {
            resetPage()
        }
    },
    { deep: true }
)

watch(
    () => props.presetFilter,
    (filter) => {
        if (filter) {
            applyFilter(filter, true)
        }
    },
    { deep: true }
)

const openDetail = (row: any) => {
    activeTaskId.value = Number(row?.id || 0)
    detailVisible.value = true
}

const handleDetailSent = () => {
    getLists()
}

const sendTask = async (row: any) => {
    const id = Number(row?.id || 0)
    if (!id || sendingId.value === id) {
        return
    }
    const label = getSendActionLabel(row)
    const isAheadOfSchedule =
        Number(row?.send_status || 0) === 0 && Boolean(row?.planned_send_time)
    const confirmText = isAheadOfSchedule
        ? `该任务原计划于 ${row.planned_send_time} 自动发送，确定现在提前发送吗？本次会通过站内消息提醒客户填写「${row?.title_snapshot || '新人问卷'}」。`
        : `确定${label}吗？本次会通过站内消息提醒客户填写「${row?.title_snapshot || '新人问卷'}」。`
    try {
        await ElMessageBox.confirm(confirmText, '发送新人问卷', { type: 'info' })
    } catch {
        return
    }
    sendingId.value = id
    try {
        await coupleQuestionnaireSend({ id })
        ElMessage.success('问卷已发送')
        await getLists()
    } catch {
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    } finally {
        sendingId.value = null
    }
}

onMounted(() => {
    loadStaffOptions()
    getLists()
})

defineExpose({ reload: getLists, resetPage, applyFilter })
</script>

<style scoped lang="scss">
.task-list-panel {
    min-width: 0;
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

.quick-views {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;

    &__item {
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
</style>
