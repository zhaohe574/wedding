<template>
    <admin-page-shell
        class="crm-loss-warning-lists"
        title="流失预警"
        description="跟踪长期未跟进或意向下降客户，一键跟进自动消警、转派顾问或推送提醒。"
    >
        <template #stats>
            <div class="crm-loss-warning-lists__stats">
                <div
                    class="crm-loss-warning-lists__stat"
                    :class="{ 'is-active': queryParams.warning_status === 0 && queryParams.warning_level === '' }"
                    @click="applyStatFilter({ warning_status: 0, warning_level: '' })"
                >
                    <span>待处理预警</span>
                    <strong>{{ stats.total_pending }}</strong>
                </div>
                <div
                    class="crm-loss-warning-lists__stat"
                    :class="{ 'is-active': queryParams.warning_status === 0 && queryParams.warning_level === 3 }"
                    @click="applyStatFilter({ warning_status: 0, warning_level: 3 })"
                >
                    <span>高风险待处理</span>
                    <strong class="text-error">{{ stats.high_level }}</strong>
                </div>
                <div
                    class="crm-loss-warning-lists__stat"
                    :class="{ 'is-active': queryParams.warning_status === 0 && queryParams.warning_level === 2 }"
                    @click="applyStatFilter({ warning_status: 0, warning_level: 2 })"
                >
                    <span>中风险待处理</span>
                    <strong class="text-warning">{{ stats.medium_level }}</strong>
                </div>
                <div
                    class="crm-loss-warning-lists__stat"
                    :class="{ 'is-active': queryParams.warning_status === 1 }"
                    @click="applyStatFilter({ warning_status: 1, warning_level: '' })"
                >
                    <span>今日已处理</span>
                    <strong class="text-success">{{ stats.today_handled }}</strong>
                </div>
            </div>
        </template>

        <template #search>
            <search-panel>
                <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                    <el-form-item class="w-[240px]" label="关键词">
                        <el-input
                            v-model="queryParams.keyword"
                            placeholder="客户/手机号/原因/备注"
                            clearable
                            @keyup.enter="resetPage"
                        />
                    </el-form-item>
                    <el-form-item class="w-[170px]" label="顾问">
                        <el-select v-model="queryParams.advisor_id" placeholder="全部顾问" clearable filterable>
                            <el-option
                                v-for="item in allAdvisorOptions"
                                :key="item.id"
                                :label="item.advisor_name"
                                :value="item.id"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[155px]" label="类型">
                        <el-select v-model="queryParams.warning_type" placeholder="全部类型" clearable>
                            <el-option
                                v-for="item in optionData.warning_type_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[140px]" label="等级">
                        <el-select v-model="queryParams.warning_level" placeholder="全部等级" clearable>
                            <el-option
                                v-for="item in optionData.warning_level_options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item class="w-[140px]" label="状态">
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
                            扫描预警
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
                            <el-button
                                type="primary"
                                link
                                class="!p-0 !h-auto !justify-start font-medium"
                                @click="openCustomerDrawer(row.customer_id)"
                            >
                                {{ row.customer?.customer_name || '-' }}
                            </el-button>
                            <span class="text-xs text-tx-secondary">手机：{{ row.customer?.customer_mobile || '-' }}</span>
                            <span class="text-xs text-tx-secondary">顾问：{{ row.advisor?.advisor_name || '未分配' }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="预警内容" min-width="230">
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
                <el-table-column label="未跟进" width="100" align="center">
                    <template #default="{ row }">{{ Number(row.days_no_follow || 0) }} 天</template>
                </el-table-column>
                <el-table-column label="状态" width="100" align="center">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.warning_status)">
                            {{ row.warning_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="推送状态" min-width="175">
                    <template #default="{ row }">
                        <div class="crm-loss-warning-lists__stack">
                            <el-tag size="small" :type="getPushStatusType(row.push_status)" effect="plain" class="self-start">
                                {{ row.push_status_desc || '未推送' }}
                            </el-tag>
                            <span v-if="row.push_time_text" class="text-xs text-tx-secondary">
                                {{ row.push_time_text }}
                            </span>
                            <span
                                v-if="row.push_error_msg"
                                class="text-xs text-warning truncate max-w-[165px]"
                                :title="row.push_error_msg"
                            >
                                {{ row.push_error_msg }}
                            </span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="处理备注" min-width="180" prop="handle_remark" show-overflow-tooltip />
                <el-table-column label="创建时间" prop="create_time_text" min-width="165" />
                <el-table-column label="更新时间" prop="update_time_text" min-width="165" />
                <el-table-column label="操作" width="260" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-if="Number(row.warning_status) === 0 && row.advisor_id"
                            v-perms="['crm.followRecord/add']"
                            type="primary"
                            link
                            @click="openFollowForWarning(row)"
                        >
                            立即跟进
                        </el-button>
                        <el-button
                            v-if="Number(row.warning_status) === 0"
                            v-perms="['crm.customer/transferAdvisor']"
                            type="warning"
                            link
                            @click="openTransferForWarning(row)"
                        >
                            转派
                        </el-button>
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
                            type="info"
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

        <!-- 快速转派顾问弹窗 -->
        <el-dialog v-model="showTransferDialog" title="转派跟进顾问" width="500px" destroy-on-close>
            <el-form label-width="95px">
                <el-form-item label="客户">
                    <span>{{ transferForm.customer_name }}（当前：{{ transferForm.current_advisor_name || '未分配' }}）</span>
                </el-form-item>
                <el-form-item label="目标顾问" required>
                    <el-select v-model="transferForm.advisor_id" placeholder="请选择在岗未满载顾问" filterable class="w-full">
                        <el-option
                            v-for="item in assignableAdvisorOptions"
                            :key="item.id"
                            :disabled="item.id === transferForm.current_advisor_id"
                            :label="`${item.advisor_name}（${item.load_text}）`"
                            :value="item.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="转派原因">
                    <el-input v-model="transferForm.reason" placeholder="如：流失预警转派激活" maxlength="200" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showTransferDialog = false">取消</el-button>
                <el-button type="primary" :loading="transferSubmitting" @click="handleSubmitTransfer">确认转派</el-button>
            </template>
        </el-dialog>

        <QuickFollowDialog
            v-model="showFollowDialog"
            :customer="followTargetCustomer"
            @submitted="handleAfterMutation"
        />

        <CustomerDetailDrawer
            v-model="showDetailDrawer"
            :customer-id="drawerCustomerId"
            @updated="handleAfterMutation"
        />
    </admin-page-shell>
</template>

<script lang="ts" setup name="crmLossWarningLists">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage, ElNotification } from 'element-plus'

import { customerAdvisorOptions, customerTransferAdvisor } from '@/api/crm/customer'
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

import CustomerDetailDrawer from '../components/CustomerDetailDrawer.vue'
import QuickFollowDialog from '../components/QuickFollowDialog.vue'

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
    advisor_id: '' as '' | number,
    warning_type: '' as '' | number,
    warning_level: '' as '' | number,
    warning_status: '' as '' | number
})

const allAdvisorOptions = ref<any[]>([])
const assignableAdvisorOptions = ref<any[]>([])

const createDefaultActionForm = () => ({
    id: 0,
    customer_name: '',
    action: 'handle' as 'handle' | 'ignore',
    remark: ''
})

const showActionDialog = ref(false)
const showFollowDialog = ref(false)
const showDetailDrawer = ref(false)
const showTransferDialog = ref(false)
const drawerCustomerId = ref(0)
const followTargetCustomer = ref<any>(null)
const submitting = ref(false)
const transferSubmitting = ref(false)
const actionFormRef = shallowRef<FormInstance>()
const actionForm = reactive(createDefaultActionForm())

const transferForm = reactive({
    customer_id: 0,
    customer_name: '',
    current_advisor_id: 0,
    current_advisor_name: '',
    advisor_id: undefined as number | undefined,
    reason: '流失预警转派激活'
})

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

const getPushStatusType = (status: string): 'success' | 'warning' | 'primary' | 'danger' | 'info' => {
    const map: Record<string, 'success' | 'warning' | 'primary' | 'danger' | 'info'> = {
        oa_sent: 'success',
        station_only: 'primary',
        queued: 'warning',
        failed: 'danger',
        none: 'info'
    }
    return map[String(status || 'none')] || 'info'
}

const applyStatFilter = (patch: Partial<typeof queryParams>) => {
    Object.assign(queryParams, patch)
    resetPage()
}

const loadOptions = async () => {
    const data = await lossWarningOptions()
    optionData.warning_type_options = data?.warning_type_options || []
    optionData.warning_level_options = data?.warning_level_options || []
    optionData.warning_status_options = data?.warning_status_options || []
}

const loadAdvisorOptions = async () => {
    const [allData, assignableData] = await Promise.all([
        customerAdvisorOptions({ mode: 'all' }),
        customerAdvisorOptions({ mode: 'assignable' })
    ])
    allAdvisorOptions.value = Array.isArray(allData) ? allData : []
    assignableAdvisorOptions.value = Array.isArray(assignableData) ? assignableData : []
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

const openCustomerDrawer = (customerId: number) => {
    if (!customerId) return
    drawerCustomerId.value = Number(customerId)
    showDetailDrawer.value = true
}

const openFollowForWarning = (row: any) => {
    followTargetCustomer.value = {
        id: Number(row.customer_id),
        customer_name: row.customer?.customer_name || '',
        customer_mobile: row.customer?.customer_mobile || '',
        intention_level: row.customer?.intention_level || 'D'
    }
    showFollowDialog.value = true
}

const openTransferForWarning = async (row: any) => {
    await loadAdvisorOptions()
    Object.assign(transferForm, {
        customer_id: Number(row.customer_id || 0),
        customer_name: row.customer?.customer_name || '',
        current_advisor_id: Number(row.advisor_id || 0),
        current_advisor_name: row.advisor?.advisor_name || '',
        advisor_id: undefined,
        reason: '流失预警转派激活'
    })
    showTransferDialog.value = true
}

const handleSubmitTransfer = async () => {
    if (!transferForm.advisor_id) {
        ElMessage.warning('请选择目标顾问')
        return
    }
    transferSubmitting.value = true
    try {
        await customerTransferAdvisor({
            customer_id: transferForm.customer_id,
            advisor_id: Number(transferForm.advisor_id),
            reason: transferForm.reason.trim()
        })
        ElMessage.success('顾问已转派，待处理预警同步更新')
        showTransferDialog.value = false
        await handleAfterMutation()
    } finally {
        transferSubmitting.value = false
    }
}

const handleGenerate = async () => {
    await feedback.confirm('确定立即扫描长期未跟进客户并生成流失预警？')
    const data = await lossWarningGenerate()
    ElMessage.success(`已生成或更新 ${Number(data?.count || 0)} 条预警`)
    await handleAfterMutation()
}

const handlePush = async (row?: any) => {
    const message = row?.id ? `确定推送客户「${row.customer?.customer_name || ''}」的预警？` : '确定推送当前待处理预警？'
    await feedback.confirm(message)
    const data = await lossWarningPush(row?.id ? { id: row.id } : {})
    const failed = Number(data?.failed || 0)
    const skipped = Number(data?.skipped || 0)
    const success = Number(data?.success || 0)
    const oaSent = Number(data?.oa_sent || 0)
    const stationOnly = Number(data?.station_only || 0)
    const errorReasons = Array.isArray(data?.errors)
        ? data.errors.slice(0, 3).map((e: any) => e.message).join('；')
        : ''

    if (failed > 0 || (success === 0 && skipped > 0)) {
        ElNotification({
            title: `推送结果：成功 ${success} 条，跳过 ${skipped} 条，失败 ${failed} 条`,
            message: errorReasons || '部分预警未发送，请检查顾问关联账号与服务号配置',
            type: 'warning',
            duration: 6000
        })
    } else if (stationOnly > 0 && oaSent === 0) {
        ElNotification({
            title: `站内信已送达 ${success} 条（服务号未送达）`,
            message: errorReasons || '未开启服务号模板通知或顾问尚未关注公众号',
            type: 'info',
            duration: 5000
        })
    } else if (oaSent > 0) {
        ElMessage.success(`推送成功 ${success} 条（服务号已送达 ${oaSent} 条）`)
    } else {
        ElMessage.success(`推送完成，成功 ${success} 条`)
    }
    await getLists()
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
        await handleAfterMutation()
    } finally {
        submitting.value = false
    }
}

const handleAfterMutation = async () => {
    await Promise.all([loadStats(), getLists()])
}

const handleActionDialogClosed = () => {
    actionFormRef.value?.clearValidate()
}

onActivated(() => {
    void loadOptions()
    void loadAdvisorOptions()
    void loadStats()
    void getLists()
})

void loadOptions()
void loadAdvisorOptions()
void loadStats()
void getLists()
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
        cursor: pointer;
        transition: border-color 0.2s, background-color 0.2s;

        span {
            color: var(--el-text-color-secondary);
            font-size: 13px;
        }

        strong {
            color: var(--el-text-color-primary);
            font-size: 24px;
            line-height: 1;
        }

        &:hover,
        &.is-active {
            border-color: var(--el-color-primary);
            background: var(--el-color-primary-light-9);
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
