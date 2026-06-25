<template>
    <div class="material-cleanup">
        <el-card class="!border-none" shadow="never">
            <div class="cleanup-header">
                <div>
                    <div class="cleanup-title">素材清理</div>
                    <div class="cleanup-desc">
                        扫描本地 uploads 与素材记录，只展示超过 {{ summary.retention_days || 30 }} 天且未被当前业务或凭证引用的素材。
                    </div>
                </div>
                <div class="cleanup-actions">
                    <el-button :loading="loading" @click="refresh">
                        <icon name="el-icon-Refresh" />
                        重新扫描
                    </el-button>
                    <el-button
                        type="danger"
                        :disabled="!selectedRows.length"
                        :loading="deleting"
                        @click="handleDelete"
                    >
                        <icon name="el-icon-Delete" />
                        清理选中
                    </el-button>
                </div>
            </div>

            <el-alert
                class="cleanup-alert"
                type="warning"
                :closable="false"
                show-icon
                title="删除前会再次复核引用状态和保护期；保护期内素材不会进入可清理列表。"
            />

            <div class="stat-grid">
                <div class="stat-item">
                    <span class="stat-label">可清理素材</span>
                    <strong>{{ summary.candidate_count || 0 }}</strong>
                    <small>条候选</small>
                </div>
                <div class="stat-item">
                    <span class="stat-label">预计释放</span>
                    <strong>{{ summary.total_size_desc || '0 B' }}</strong>
                    <small>本地硬盘</small>
                </div>
                <div class="stat-item">
                    <span class="stat-label">素材记录</span>
                    <strong>{{ summary.registered_count || 0 }}</strong>
                    <small>未引用</small>
                </div>
                <div class="stat-item">
                    <span class="stat-label">孤儿文件</span>
                    <strong>{{ summary.orphan_count || 0 }}</strong>
                    <small>无记录</small>
                </div>
                <div class="stat-item">
                    <span class="stat-label">保护期跳过</span>
                    <strong>{{ summary.protected_count || 0 }}</strong>
                    <small>{{ summary.protected_size_desc || '0 B' }}</small>
                </div>
            </div>

            <div class="filter-bar">
                <el-select v-model="query.source" class="filter-source" @change="resetPage">
                    <el-option label="全部来源" value="all" />
                    <el-option label="素材记录" value="registered" />
                    <el-option label="孤儿文件" value="orphan" />
                </el-select>
                <el-select v-model="query.type" class="filter-type" @change="resetPage">
                    <el-option label="全部类型" :value="0" />
                    <el-option label="图片" :value="10" />
                    <el-option label="视频" :value="20" />
                    <el-option label="文件" :value="30" />
                </el-select>
                <el-select v-model="query.reference_state" class="filter-reference" @change="resetPage">
                    <el-option label="全部引用状态" value="all" />
                    <el-option label="未发现引用" value="none" />
                    <el-option label="仅历史引用" value="stale" />
                </el-select>
                <el-input
                    v-model="query.keyword"
                    class="filter-keyword"
                    clearable
                    placeholder="搜索文件名或路径"
                    @keyup.enter="resetPage"
                    @clear="resetPage"
                />
                <el-button type="primary" :loading="loading" @click="resetPage">
                    <icon name="el-icon-Search" />
                    查询
                </el-button>
            </div>

            <el-table
                v-loading="loading"
                :data="lists"
                row-key="id"
                class="cleanup-table"
                @selection-change="handleSelectionChange"
            >
                <el-table-column type="selection" width="48" />
                <el-table-column label="预览" width="92">
                    <template #default="{ row }">
                        <div class="preview-box">
                            <el-image
                                v-if="row.type === 10 && row.exists"
                                :src="row.url"
                                fit="cover"
                                :preview-src-list="[row.url]"
                                preview-teleported
                            />
                            <video v-else-if="row.type === 20 && row.exists" :src="row.url" muted />
                            <icon v-else name="el-icon-Document" size="24" />
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="素材信息" min-width="320">
                    <template #default="{ row }">
                        <div class="file-name">{{ row.name || '-' }}</div>
                        <div class="file-uri">{{ row.uri }}</div>
                        <div class="file-reason">{{ row.reason }}</div>
                        <div v-if="row.reference_sources?.length" class="reference-sources">
                            来源：{{ row.reference_sources.join('、') }}
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="类型" width="90">
                    <template #default="{ row }">
                        <el-tag effect="plain" size="small">{{ row.type_desc }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="来源" width="110">
                    <template #default="{ row }">
                        <el-tag :type="row.source_type === 'orphan' ? 'warning' : 'info'" effect="light">
                            {{ row.source_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="引用风险" width="132">
                    <template #default="{ row }">
                        <div class="risk-cell">
                            <el-tag
                                :type="row.reference_state === 'stale' ? 'warning' : 'success'"
                                effect="light"
                                size="small"
                            >
                                {{ row.reference_state_desc }}
                            </el-tag>
                            <span :class="['risk-level', row.risk_level === 'medium' ? 'is-medium' : '']">
                                {{ row.risk_level_desc }}
                            </span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column prop="size_desc" label="大小" width="110" />
                <el-table-column label="时间" width="180">
                    <template #default="{ row }">
                        <div class="time-main">{{ row.mtime_desc }}</div>
                        <div class="time-sub">创建：{{ row.create_time_desc }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="96" fixed="right">
                    <template #default="{ row }">
                        <el-button link type="primary" @click="openPreview(row)">查看</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="table-footer">
                <div class="selection-tip">
                    已选 {{ selectedRows.length }} 项
                </div>
                <pagination :model-value="pager" @change="loadLists" />
            </div>
        </el-card>

        <el-dialog v-model="resultVisible" title="清理结果" width="620px">
            <div class="result-summary">
                已清理 {{ deleteResult.deleted_count || 0 }} 项，释放 {{ deleteResult.free_size_desc || '0 B' }}。
                失败 {{ deleteResult.failed_count || 0 }} 项。
            </div>
            <el-table
                v-if="deleteResult.failed_items?.length"
                :data="deleteResult.failed_items"
                max-height="320"
            >
                <el-table-column prop="uri" label="路径" min-width="260" show-overflow-tooltip />
                <el-table-column prop="reason" label="失败原因" min-width="220" show-overflow-tooltip />
            </el-table>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="materialCleanup">
import { onMounted, reactive, ref } from 'vue'

import {
    materialCleanupDelete,
    materialCleanupLists,
    materialCleanupSummary
} from '@/api/file'
import feedback from '@/utils/feedback'

const query = reactive({
    source: 'all',
    type: 0,
    reference_state: 'all',
    keyword: ''
})

const pager = reactive({
    page: 1,
    size: 20,
    count: 0
})

const loading = ref(false)
const deleting = ref(false)
const lists = ref<any[]>([])
const selectedRows = ref<any[]>([])
const summary = ref<Record<string, any>>({})
const resultVisible = ref(false)
const deleteResult = ref<Record<string, any>>({})

const loadSummary = async () => {
    summary.value = await materialCleanupSummary({
        source: query.source,
        type: query.type,
        reference_state: query.reference_state,
        keyword: query.keyword
    })
}

const loadLists = async () => {
    loading.value = true
    try {
        const res = await materialCleanupLists({
            page_no: pager.page,
            page_size: pager.size,
            source: query.source,
            type: query.type,
            reference_state: query.reference_state,
            keyword: query.keyword
        })
        lists.value = res?.lists || []
        pager.count = res?.count || 0
        selectedRows.value = []
    } finally {
        loading.value = false
    }
}

const refresh = async () => {
    loading.value = true
    try {
        await loadSummary()
        const res = await materialCleanupLists({
            page_no: pager.page,
            page_size: pager.size,
            source: query.source,
            type: query.type,
            reference_state: query.reference_state,
            keyword: query.keyword
        })
        lists.value = res?.lists || []
        pager.count = res?.count || 0
        selectedRows.value = []
    } finally {
        loading.value = false
    }
}

const resetPage = async () => {
    pager.page = 1
    await refresh()
}

const handleSelectionChange = (rows: any[]) => {
    selectedRows.value = rows
}

const openPreview = (row: any) => {
    if (!row?.url) {
        feedback.msgWarning('当前素材没有可预览地址')
        return
    }
    window.open(row.url, '_blank')
}

const handleDelete = async () => {
    if (!selectedRows.value.length) {
        feedback.msgWarning('请先选择要清理的素材')
        return
    }

    const staleCount = selectedRows.value.filter((item) => item.reference_state === 'stale').length
    const message = staleCount > 0
        ? `确认清理选中的 ${selectedRows.value.length} 个素材？其中 ${staleCount} 个仅存在历史引用，曾被下架、禁用或旧模板引用，当前不在启用内容中。删除后本地文件将不可恢复，系统会在删除前再次复核引用状态。`
        : `确认清理选中的 ${selectedRows.value.length} 个素材？删除后本地文件将不可恢复，系统会在删除前再次复核引用状态。`

    await feedback.confirm(message)

    deleting.value = true
    try {
        deleteResult.value = await materialCleanupDelete({
            ids: selectedRows.value.map((item) => item.id)
        })
        resultVisible.value = true
        await refresh()
    } finally {
        deleting.value = false
    }
}

onMounted(() => {
    refresh()
})
</script>

<style lang="scss" scoped>
.material-cleanup {
    min-width: 920px;

    .cleanup-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .cleanup-title {
        color: #101828;
        font-size: 20px;
        font-weight: 600;
        line-height: 28px;
    }

    .cleanup-desc {
        margin-top: 6px;
        color: #667085;
        font-size: 13px;
        line-height: 20px;
    }

    .cleanup-actions {
        display: flex;
        flex-shrink: 0;
        gap: 10px;
    }

    .cleanup-alert {
        margin-top: 18px;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .stat-item {
        min-height: 92px;
        padding: 16px;
        border: 1px solid #eaecf0;
        border-radius: 8px;
        background: #fcfcfd;
        display: flex;
        flex-direction: column;
        justify-content: space-between;

        .stat-label,
        small {
            color: #667085;
            font-size: 12px;
            line-height: 18px;
        }

        strong {
            color: #101828;
            font-size: 24px;
            font-weight: 650;
            line-height: 30px;
        }
    }

    .filter-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 18px;
        padding: 14px;
        border: 1px solid #eaecf0;
        border-radius: 8px;
        background: #ffffff;
    }

    .filter-source {
        width: 132px;
    }

    .filter-type {
        width: 120px;
    }

    .filter-reference {
        width: 148px;
    }

    .filter-keyword {
        width: 320px;
    }

    .cleanup-table {
        margin-top: 16px;
    }

    .preview-box {
        width: 54px;
        height: 54px;
        border: 1px solid #eaecf0;
        border-radius: 8px;
        background: #f9fafb;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;

        .el-image,
        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    }

    .file-name {
        color: #101828;
        font-weight: 600;
        line-height: 22px;
    }

    .file-uri {
        margin-top: 4px;
        color: #667085;
        font-size: 12px;
        line-height: 18px;
        word-break: break-all;
    }

    .file-reason {
        margin-top: 4px;
        color: #b54708;
        font-size: 12px;
        line-height: 18px;
    }

    .reference-sources {
        margin-top: 4px;
        color: #6941c6;
        font-size: 12px;
        line-height: 18px;
        word-break: break-all;
    }

    .risk-cell {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }

    .risk-level {
        color: #039855;
        font-size: 12px;
        line-height: 18px;

        &.is-medium {
            color: #b54708;
        }
    }

    .time-main {
        color: #344054;
        line-height: 20px;
    }

    .time-sub {
        color: #98a2b3;
        font-size: 12px;
        line-height: 18px;
    }

    .table-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 16px;
    }

    .selection-tip {
        color: #667085;
        font-size: 13px;
    }

    .result-summary {
        margin-bottom: 14px;
        color: #344054;
        line-height: 22px;
    }
}
</style>
