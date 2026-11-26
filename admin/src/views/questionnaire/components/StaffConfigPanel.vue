<template>
    <div class="staff-config-panel">
        <search-panel>
            <el-form class="mb-[-16px]" :inline="true" :model="queryParams">
                <el-form-item class="w-[260px]" label="关键词">
                    <el-input
                        v-model="queryParams.keyword"
                        clearable
                        placeholder="姓名、工号、手机号或问卷标题"
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="发布状态">
                    <el-select v-model="queryParams.published" clearable placeholder="全部">
                        <el-option
                            v-for="item in PUBLISH_STATUS_OPTIONS"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[150px]" label="推送方式">
                    <el-select v-model="queryParams.push_mode" clearable placeholder="全部">
                        <el-option
                            v-for="item in PUSH_MODE_OPTIONS"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[150px]" label="启用状态">
                    <el-select v-model="queryParams.status" clearable placeholder="全部">
                        <el-option
                            v-for="item in ENABLE_STATUS_OPTIONS"
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

        <admin-table-toolbar class="mt-4" :hint="toolbarHint">
            <template #right>
                <el-button :loading="pager.loading" @click="getLists()">刷新</el-button>
            </template>
        </admin-table-toolbar>

        <el-table v-loading="pager.loading" :data="pager.lists" border>
            <el-table-column label="服务人员" min-width="160">
                <template #default="{ row }">
                    <div class="cell-main">{{ row.staff?.name || row.staff_name || '-' }}</div>
                    <div class="cell-sub">
                        {{ row.staff?.sn || row.staff_sn || row.staff?.mobile || row.staff_mobile || '-' }}
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="问卷标题与说明" min-width="220">
                <template #default="{ row }">
                    <div class="cell-main">{{ row.title || '未配置问卷标题' }}</div>
                    <div class="cell-sub cell-sub--ellipsis">
                        {{ row.description || '暂无问卷说明' }}
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="题目与配置状态" width="150">
                <template #default="{ row }">
                    <div class="cell-main">{{ Number(row.draft_question_count || 0) }} 题</div>
                    <el-tag
                        class="mt-1"
                        size="small"
                        effect="plain"
                        :type="row.has_config ? 'success' : 'info'"
                    >
                        {{ row.has_config ? '已配置' : '未初始化' }}
                    </el-tag>
                </template>
            </el-table-column>
            <el-table-column label="发布版本" width="150">
                <template #default="{ row }">
                    <el-tag v-if="row.published" type="success" effect="plain">
                        v{{ row.published_version_no }}
                    </el-tag>
                    <el-tag v-else type="info" effect="plain">未发布</el-tag>
                    <div v-if="row.published_time" class="cell-sub mt-1">
                        {{ row.published_time }}
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="推送方式 & 启用状态" width="200">
                <template #default="{ row }">
                    <div class="tag-row">
                        <el-tag
                            size="small"
                            effect="plain"
                            :type="Number(row.push_mode) === 1 ? 'success' : 'warning'"
                        >
                            {{ row.push_mode_desc || '自动推送' }}
                        </el-tag>
                        <el-tag
                            size="small"
                            effect="plain"
                            :type="Number(row.status) === 1 ? 'success' : 'info'"
                        >
                            {{ row.status_desc || '启用' }}
                        </el-tag>
                    </div>
                    <div v-if="Number(row.push_mode ?? 1) === 1" class="cell-sub">
                        {{
                            row.push_schedule_desc ||
                            formatPushScheduleSummary(
                                row.push_mode,
                                row.auto_push_trigger,
                                row.auto_push_days,
                                row.auto_push_time
                            )
                        }}
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="任务概览" width="150">
                <template #default="{ row }">
                    <div class="cell-main">
                        待填写 {{ Number(row.pending_task_count || 0) }}
                    </div>
                    <div class="cell-sub">
                        已回收 {{ Number(row.submitted_task_count || 0) }}
                    </div>
                </template>
            </el-table-column>
            <el-table-column prop="update_time" label="更新时间" width="165">
                <template #default="{ row }">{{ row.update_time || '-' }}</template>
            </el-table-column>
            <el-table-column label="操作" width="170" fixed="right">
                <template #default="{ row }">
                    <el-button
                        v-perms="['growth.coupleQuestionnaire/detail']"
                        type="primary"
                        link
                        @click="emit('open-config', row)"
                    >
                        配置问卷
                    </el-button>
                    <el-button
                        v-perms="['growth.coupleQuestionnaire/tasks']"
                        type="success"
                        link
                        @click="emit('view-tasks', row)"
                    >
                        查看任务
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <div class="flex justify-end mt-4">
            <pagination v-model="pager" @change="getLists" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { coupleQuestionnaireLists } from '@/api/questionnaire'
import { usePaging } from '@/hooks/usePaging'

import type { QuestionnaireOverview, StaffConfigPresetFilter } from '../shared'
import {
    createEmptyOverview,
    ENABLE_STATUS_OPTIONS,
    extractErrorMessage,
    formatPushScheduleSummary,
    PUBLISH_STATUS_OPTIONS,
    PUSH_MODE_OPTIONS
} from '../shared'

const props = defineProps<{
    presetFilter?: StaffConfigPresetFilter
}>()

const emit = defineEmits<{
    (e: 'overview', overview: QuestionnaireOverview): void
    (e: 'open-config', row: any): void
    (e: 'view-tasks', row: any): void
}>()

const queryParams = reactive<{
    keyword: string
    published: number | ''
    push_mode: number | ''
    status: number | ''
}>({
    keyword: '',
    published: '',
    push_mode: '',
    status: ''
})

const listError = ref('')
const overviewState = ref<QuestionnaireOverview>(createEmptyOverview())

const fetchConfigs = async (params: Record<string, any>) => {
    listError.value = ''
    try {
        const res: any = await coupleQuestionnaireLists(params)
        const payload: any =
            res && typeof res === 'object' && 'lists' in res ? res : (res?.data ?? res)
        const overview: QuestionnaireOverview = {
            ...createEmptyOverview(),
            ...(payload?.overview || {})
        }
        overview.unconfigured_count = Math.max(0, overview.staff_total - overview.configured_count)
        overviewState.value = overview
        emit('overview', overview)
        return payload && Array.isArray(payload.lists) ? payload : { lists: [], count: 0 }
    } catch (error) {
        listError.value = extractErrorMessage(error, '问卷配置加载失败，请稍后重试')
        return { lists: [], count: 0 }
    }
}

const { pager, getLists, resetPage } = usePaging({
    fetchFun: fetchConfigs,
    params: queryParams,
    firstLoading: true
})

const toolbarHint = computed(
    () =>
        `共 ${pager.count || 0} 位服务人员 · 已发布 ${overviewState.value.published_count} · 待发布 ${overviewState.value.unpublished_count}`
)

const handleReset = () => {
    queryParams.keyword = ''
    queryParams.published = ''
    queryParams.push_mode = ''
    queryParams.status = ''
    resetPage()
}

const applyPresetFilter = (filter?: StaffConfigPresetFilter, shouldReset = true) => {
    if (!filter) {
        return
    }
    let changed = false
    if (filter.published !== undefined && queryParams.published !== filter.published) {
        queryParams.published = filter.published
        changed = true
    }
    if (filter.push_mode !== undefined && queryParams.push_mode !== filter.push_mode) {
        queryParams.push_mode = filter.push_mode
        changed = true
    }
    if (filter.status !== undefined && queryParams.status !== filter.status) {
        queryParams.status = filter.status
        changed = true
    }
    if (filter.keyword !== undefined && queryParams.keyword !== filter.keyword) {
        queryParams.keyword = filter.keyword
        changed = true
    }
    if (filter._ts !== undefined) {
        changed = true
    }
    if (changed && shouldReset) {
        resetPage()
    }
}

applyPresetFilter(props.presetFilter, false)

watch(
    () => props.presetFilter,
    (filter) => {
        applyPresetFilter(filter, true)
    },
    { deep: true }
)

onMounted(() => {
    getLists()
})

defineExpose({ reload: getLists, resetPage })
</script>

<style scoped lang="scss">
.staff-config-panel {
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

    &--ellipsis {
        display: -webkit-box;
        overflow: hidden;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
}

.tag-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
</style>
