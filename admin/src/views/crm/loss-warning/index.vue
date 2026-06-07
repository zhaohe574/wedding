<template>
    <admin-page-shell
        class="crm-loss-warning-lists"
        title="流失预警"
        description="跟踪长期未跟进客户，处理预警并推送企业微信提醒。"
    >
        <template #stats>
            <div class="crm-loss-warning-lists__stats">
                <div class="crm-loss-warning-lists__stat">
                    <span>待处理</span>
                    <strong>{{ stats.total_pending }}</strong>
                </div>
                <div class="crm-loss-warning-lists__stat">
                    <span>高风险</span>
                    <strong class="text-error">{{ stats.high_level }}</strong>
                </div>
                <div class="crm-loss-warning-lists__stat">
                    <span>中风险</span>
                    <strong class="text-warning">{{ stats.medium_level }}</strong>
                </div>
                <div class="crm-loss-warning-lists__stat">
                    <span>今日处理</span>
                    <strong>{{ stats.today_handled }}</strong>
                </div>
            </div>
        </template>

        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[260px]" label="关键词">
                        <el-input
                            v-model="queryParams.keyword"
                            placeholder="客户/手机号/原因/备注"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="类型">
                        <el-select v-model="queryParams.warning_type" placeholder="全部类型" clearable>
                            <el-option
                                v-for="item in optionData.warning_type_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="等级">
                        <el-select v-model="queryParams.warning_level" placeholder="全部等级" clearable>
                            <el-option
                                v-for="item in optionData.warning_level_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[160px]" label="状态">
                        <el-select v-model="queryParams.warning_status" placeholder="全部状态" clearable>
                            <el-option
                                v-for="item in optionData.warning_status_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="resetParams">重置</el-button>
                        <el-button v-perms="['crm.lossWarning/generate']" type="primary" @click="handleGenerate">
                            生成预警
                        </el-button>
                        <el-button v-perms="['crm.lossWarning/push']" @click="handlePush()">
                            推送待处理
                        </el-button>
                    </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <div class="admin-page-section">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="客户" min-width="220" fixed="left">
                    <template #default="{ row }">
                        <div class="crm-loss-warning-lists__stack">
                            <span class="font-medium">{{ row.customer?.customer_name || '-' }}</span>
                            <span class="text-xs text-tx-secondary">手机号：{{ row.customer?.customer_mobile || '-' }}</span>
                            <span class="text-xs text-tx-secondary">顾问：{{ row.advisor?.advisor_name || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="预警" min-width="230">
                    <template #default="{ row }">
                        <div class="crm-loss-warning-lists__stack">
                            <div class="crm-loss-warning-lists__line">
                                <el-tag effect="plain">{{ row.warning_type_desc }}</el-tag>
                                <el-tag :type="getLevelType(row.warning_level)">
                                    {{ row.warning_level_desc }}
                                </el-tag>
                            </div>
                            <span>{{ row.warning_reason || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="未跟进" width="110" align="center">
                    <template #default="{ row }">{{ Number(row.days_no_follow || 0) }} 天</template>
                </el-table-column>
                <el-table-column label="状态" width="110" align="center">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.warning_status)">
                            {{ row.warning_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="处理备注" min-width="190" prop="handle_remark" />
                <el-table-column label="创建时间" prop="create_time_text" min-width="170" />
                <el-table-column label="更新时间" prop="update_time_text" min-width="170" />
                <el-table-column label="操作" width="190" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['crm.lossWarning/push']"
                            type="primary"
                            link
                            :disabled="Number(row.warning_status) !== 0"
                            @click="handlePush(row)"
                        >
                            推送
                        </el-button>
                        <el-button
                            v-perms="['crm.lossWarning/handle']"
                            type="primary"
                            link
                            :disabled="Number(row.warning_status) !== 0"
                            @click="openActionDialog(row, 'handle')"
                        >
                            处理
                        </el-button>
                        <el-button
                            v-perms="['crm.lossWarning/ignore']"
                            type="primary"
                            link
                            :disabled="Number(row.warning_status) !== 0"
                            @click="openActionDialog(row, 'ignore')"
                        >
                            忽略
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </div>

        <el-dialog
            v-model="showActionDialog"
            :title="actionForm.action === 'handle' ? '处理预警' : '忽略预警'"
            width="520px"
            destroy-on-close
            @closed="handleActionDialogClosed"
        >
            <el-form ref="actionFormRef" :model="actionForm" :rules="actionRules" label-width="90px">
                <el-form-item label="客户">
                    <el-input v-model="actionForm.customer_name" disabled />
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input
                        v-model="actionForm.remark"
                        type="textarea"
                        :rows="4"
                        maxlength="255"
                        show-word-limit
                        placeholder="请输入处理备注"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showActionDialog = false">取消</el-button>
                <el-button type="primary" :loading="submitting" @click="handleSubmitAction">
                    确认
                </el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script lang="ts" setup name="crmLossWarningLists">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'

import {
    lossWarningGenerate,
    lossWarningHandle,
    lossWarningIgnore,
    lossWarningLists,
    lossWarningOptions,
    lossWarningPush,
    lossWarningStats
} from '@/api/crm/lossWarning'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

interface OptionItem {
    value: number | string
    label: string
}

const optionData = reactive({
    warning_type_options: [] as OptionItem[],
    warning_level_options: [] as OptionItem[],
    warning_status_options: [] as OptionItem[]
})

const stats = reactive({
    total_pending: 0,
    high_level: 0,
    medium_level: 0,
    low_level: 0,
    today_handled: 0
})

const queryParams = reactive({
    keyword: '',
    warning_type: '' as '' | number,
    warning_level: '' as '' | number,
    warning_status: '' as '' | number
})

const createDefaultActionForm = () => ({
    id: 0,
    customer_name: '',
    action: 'handle' as 'handle' | 'ignore',
    remark: ''
})

const showActionDialog = ref(false)
const submitting = ref(false)
const actionFormRef = shallowRef<FormInstance>()
const actionForm = reactive(createDefaultActionForm())

const actionRules = reactive<FormRules>({
    remark: [{ max: 255, message: '备注最多255个字符', trigger: 'blur' }]
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: lossWarningLists,
    params: queryParams
})

const getLevelType = (level: number): 'danger' | 'warning' | 'info' => {
    const map: Record<number, 'danger' | 'warning' | 'info'> = {
        1: 'info',
        2: 'warning',
        3: 'danger'
    }
    return map[Number(level)] || 'info'
}

const getStatusType = (status: number): 'warning' | 'success' | 'info' => {
    const map: Record<number, 'warning' | 'success' | 'info'> = {
        0: 'warning',
        1: 'success',
        2: 'info'
    }
    return map[Number(status)] || 'info'
}

const loadOptions = async () => {
    const data = await lossWarningOptions()
    optionData.warning_type_options = data?.warning_type_options || []
    optionData.warning_level_options = data?.warning_level_options || []
    optionData.warning_status_options = data?.warning_status_options || []
}

const loadStats = async () => {
    const data = await lossWarningStats()
    Object.assign(stats, {
        total_pending: Number(data?.total_pending || 0),
        high_level: Number(data?.high_level || 0),
        medium_level: Number(data?.medium_level || 0),
        low_level: Number(data?.low_level || 0),
        today_handled: Number(data?.today_handled || 0)
    })
}

const handleGenerate = async () => {
    await feedback.confirm('确定立即扫描长期未跟进客户并生成流失预警？')
    const data = await lossWarningGenerate()
    ElMessage.success(`已生成或更新 ${Number(data?.count || 0)} 条预警`)
    await loadStats()
    getLists()
}

const handlePush = async (row?: any) => {
    const message = row?.id ? `确定推送客户「${row.customer?.customer_name || ''}」的预警？` : '确定推送当前待处理预警？'
    await feedback.confirm(message)
    const data = await lossWarningPush(row?.id ? { id: row.id } : {})
    const failed = Number(data?.failed || 0)
    const success = Number(data?.success || 0)
    if (failed > 0) {
        ElMessage.warning(`推送完成，成功 ${success} 条，失败 ${failed} 条`)
    } else {
        ElMessage.success(`推送完成，成功 ${success} 条`)
    }
}

const openActionDialog = (row: any, action: 'handle' | 'ignore') => {
    Object.assign(actionForm, {
        id: Number(row.id || 0),
        customer_name: String(row.customer?.customer_name || ''),
        action,
        remark: ''
    })
    showActionDialog.value = true
}

const handleSubmitAction = async () => {
    await actionFormRef.value?.validate()
    submitting.value = true
    try {
        const payload = {
            id: Number(actionForm.id || 0),
            remark: actionForm.remark.trim()
        }
        if (actionForm.action === 'handle') {
            await lossWarningHandle(payload)
            ElMessage.success('预警已处理')
        } else {
            await lossWarningIgnore(payload)
            ElMessage.success('预警已忽略')
        }
        showActionDialog.value = false
        await loadStats()
        getLists()
    } finally {
        submitting.value = false
    }
}

const handleActionDialogClosed = () => {
    actionFormRef.value?.clearValidate()
}

onActivated(() => {
    loadOptions()
    loadStats()
    getLists()
})

loadOptions()
loadStats()
getLists()
</script>

<style lang="scss" scoped>
.crm-loss-warning-lists {
    &__stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    &__stat {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 14px 16px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 6px;

        span {
            color: var(--el-text-color-secondary);
            font-size: 13px;
        }

        strong {
            color: var(--el-text-color-primary);
            font-size: 24px;
            line-height: 1;
        }
    }

    &__stack {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;
        line-height: 1.45;
    }

    &__line {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
}

@media (max-width: 900px) {
    .crm-loss-warning-lists {
        &__stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
}
</style>
