<template>
    <div class="order-pause-page">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[200px]" label="暂停单号">
                    <el-input
                        v-model="queryParams.pause_sn"
                        placeholder="输入暂停单号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[200px]" label="订单号">
                    <el-input
                        v-model="queryParams.order_sn"
                        placeholder="输入订单号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[160px]" label="状态">
                    <el-select v-model="queryParams.pause_status" placeholder="全部" clearable>
                        <el-option
                            v-for="o in statusOptions"
                            :key="o.value"
                            :label="o.label"
                            :value="o.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[180px]" label="类型">
                    <el-select v-model="queryParams.pause_type" placeholder="全部" clearable>
                        <el-option
                            v-for="o in typeOptions"
                            :key="o.value"
                            :label="o.label"
                            :value="o.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-4 text-tx-secondary text-sm">
                客户在小程序提交暂停后进入「待审核」，通过后订单转为「已暂停」；到期需人工恢复或延期，
                否则订单会一直停在暂停态。
            </div>

            <el-table v-loading="pager.loading" :data="pager.lists" size="large">
                <el-table-column label="暂停单号" prop="pause_sn" min-width="170" />
                <el-table-column label="订单号" prop="order_sn" min-width="170" />
                <el-table-column label="类型" width="110">
                    <template #default="{ row }">{{ typeText(row.pause_type) }}</template>
                </el-table-column>
                <el-table-column label="暂停区间" min-width="200">
                    <template #default="{ row }">
                        {{ row.pause_start_date || '—' }} ~ {{ row.pause_end_date || '—' }}
                    </template>
                </el-table-column>
                <el-table-column label="天数" prop="pause_days" width="80" />
                <el-table-column label="状态" width="110">
                    <template #default="{ row }">
                        <el-tag :type="statusTagType(row.pause_status)" size="small">
                            {{ row.pause_status_desc || statusText(row.pause_status) }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="申请时间" prop="create_time" width="180" />
                <el-table-column label="操作" width="220" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="openDetail(row)">详情</el-button>
                        <el-button
                            v-if="Number(row.pause_status) === 0"
                            v-perms="['order.orderPause/audit']"
                            type="primary"
                            link
                            @click="openAudit(row)"
                        >
                            审核
                        </el-button>
                        <el-button
                            v-if="Number(row.pause_status) === 1"
                            v-perms="['order.orderPause/resume']"
                            type="success"
                            link
                            @click="openResume(row)"
                        >
                            恢复
                        </el-button>
                        <el-button
                            v-if="Number(row.pause_status) === 1"
                            v-perms="['order.orderPause/extend']"
                            type="warning"
                            link
                            @click="openExtend(row)"
                        >
                            延期
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 详情 -->
        <el-drawer v-model="showDetail" title="暂停申请详情" size="520px">
            <el-descriptions :column="1" border>
                <el-descriptions-item label="暂停单号">{{ detail.pause_sn }}</el-descriptions-item>
                <el-descriptions-item label="订单号">{{ detail.order_sn }}</el-descriptions-item>
                <el-descriptions-item label="类型">{{ typeText(detail.pause_type) }}</el-descriptions-item>
                <el-descriptions-item label="状态">{{ detail.pause_status_desc || statusText(detail.pause_status) }}</el-descriptions-item>
                <el-descriptions-item label="暂停区间">
                    {{ detail.pause_start_date || '—' }} ~ {{ detail.pause_end_date || '—' }}
                </el-descriptions-item>
                <el-descriptions-item label="天数">{{ detail.pause_days }}</el-descriptions-item>
                <el-descriptions-item label="原服务日期">
                    {{ detail.original_service_date || '—' }}
                </el-descriptions-item>
                <el-descriptions-item label="申请原因">
                    {{ detail.pause_reason || '—' }}
                </el-descriptions-item>
                <el-descriptions-item v-if="detail.reject_reason" label="拒绝原因">
                    {{ detail.reject_reason }}
                </el-descriptions-item>
                <el-descriptions-item v-if="detail.audit_remark" label="审核备注">
                    {{ detail.audit_remark }}
                </el-descriptions-item>
                <el-descriptions-item label="申请时间">{{ detail.create_time }}</el-descriptions-item>
            </el-descriptions>
        </el-drawer>

        <!-- 审核 -->
        <el-dialog v-model="showAudit" title="审核暂停申请" width="520px">
            <el-form label-width="90px">
                <el-form-item label="审核结果">
                    <el-radio-group v-model="auditForm.approved">
                        <el-radio :value="1">通过</el-radio>
                        <el-radio :value="0">拒绝</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="auditForm.approved" label="审核备注">
                    <el-input v-model="auditForm.remark" type="textarea" :rows="3" />
                </el-form-item>
                <el-form-item v-else label="拒绝原因">
                    <el-input v-model="auditForm.reject_reason" type="textarea" :rows="3" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showAudit = false">取消</el-button>
                <el-button type="primary" :loading="submitting" @click="submitAudit">提交</el-button>
            </template>
        </el-dialog>

        <!-- 恢复 -->
        <el-dialog v-model="showResume" title="恢复订单" width="520px">
            <el-form label-width="110px">
                <el-form-item label="新服务日期">
                    <el-date-picker
                        v-model="resumeForm.new_service_date"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="不填则沿用原日期"
                        class="!w-full"
                    />
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="resumeForm.remark" type="textarea" :rows="3" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showResume = false">取消</el-button>
                <el-button type="primary" :loading="submitting" @click="submitResume">确认恢复</el-button>
            </template>
        </el-dialog>

        <!-- 延期 -->
        <el-dialog v-model="showExtend" title="延长暂停" width="520px">
            <el-form label-width="110px">
                <el-form-item label="新的结束日期" required>
                    <el-date-picker
                        v-model="extendForm.new_end_date"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="选择新的暂停结束日期"
                        class="!w-full"
                    />
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="extendForm.remark" type="textarea" :rows="3" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showExtend = false">取消</el-button>
                <el-button type="primary" :loading="submitting" @click="submitExtend">确认延期</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="orderPause">
/**
 * 订单暂停审核。
 *
 * 后端 OrderPauseController 早就齐了（lists/detail/audit/resume/extend/logs/expiring/statistics），
 * 但后台一直没有页面 —— 客户在小程序提交暂停后，订单卡在「已暂停(7)」没人处理。
 * 本页把这条闭环补上。
 */
import {
    orderPauseAudit,
    orderPauseDetail,
    orderPauseExtend,
    orderPauseLists,
    orderPauseResume,
    orderPauseStatusOptions,
    orderPauseTypeOptions
} from '@/api/order/pause'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    pause_sn: '',
    order_sn: '',
    pause_status: '',
    pause_type: ''
})

const statusOptions = ref<any[]>([])
const typeOptions = ref<any[]>([])

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: orderPauseLists,
    params: queryParams
})

const showDetail = ref(false)
const detail = reactive<Record<string, any>>({})

const showAudit = ref(false)
const showResume = ref(false)
const showExtend = ref(false)
const submitting = ref(false)

const currentId = ref(0)
const auditForm = reactive({ approved: 1, remark: '', reject_reason: '' })
const resumeForm = reactive({ new_service_date: '', remark: '' })
const extendForm = reactive({ new_end_date: '', remark: '' })

function statusText(v: any) {
    const hit = statusOptions.value.find((o) => Number(o.value) === Number(v))
    return hit?.label || '—'
}

function typeText(v: any) {
    const hit = typeOptions.value.find((o) => Number(o.value) === Number(v))
    return hit?.label || '—'
}

function statusTagType(v: any) {
    const n = Number(v)
    if (n === 0) return 'warning'
    if (n === 1) return 'primary'
    if (n === 2) return 'success'
    if (n === 3) return 'danger'
    return 'info'
}

async function openDetail(row: any) {
    const data = await orderPauseDetail({ id: row.id })
    Object.keys(detail).forEach((k) => delete detail[k])
    Object.assign(detail, data || {})
    showDetail.value = true
}

function openAudit(row: any) {
    currentId.value = row.id
    Object.assign(auditForm, { approved: 1, remark: '', reject_reason: '' })
    showAudit.value = true
}

function openResume(row: any) {
    currentId.value = row.id
    Object.assign(resumeForm, { new_service_date: '', remark: '' })
    showResume.value = true
}

function openExtend(row: any) {
    currentId.value = row.id
    Object.assign(extendForm, { new_end_date: row.pause_end_date || '', remark: '' })
    showExtend.value = true
}

async function submitAudit() {
    if (!auditForm.approved && !auditForm.reject_reason.trim()) {
        feedback.msgError('请填写拒绝原因')
        return
    }
    submitting.value = true
    try {
        await orderPauseAudit({
            id: currentId.value,
            approved: auditForm.approved,
            remark: auditForm.remark,
            reject_reason: auditForm.reject_reason
        })
        feedback.msgSuccess('已提交')
        showAudit.value = false
        getLists()
    } finally {
        submitting.value = false
    }
}

async function submitResume() {
    submitting.value = true
    try {
        await orderPauseResume({
            id: currentId.value,
            new_service_date: resumeForm.new_service_date,
            remark: resumeForm.remark
        })
        feedback.msgSuccess('订单已恢复')
        showResume.value = false
        getLists()
    } finally {
        submitting.value = false
    }
}

async function submitExtend() {
    if (!extendForm.new_end_date) {
        feedback.msgError('请选择新的结束日期')
        return
    }
    submitting.value = true
    try {
        await orderPauseExtend({
            id: currentId.value,
            new_end_date: extendForm.new_end_date,
            remark: extendForm.remark
        })
        feedback.msgSuccess('延期成功')
        showExtend.value = false
        getLists()
    } finally {
        submitting.value = false
    }
}

async function loadOptions() {
    const [status, type] = await Promise.all([
        orderPauseStatusOptions().catch(() => []),
        orderPauseTypeOptions().catch(() => [])
    ])
    statusOptions.value = Array.isArray(status) ? status : (status?.lists ?? [])
    typeOptions.value = Array.isArray(type) ? type : (type?.lists ?? [])
}

onMounted(() => {
    loadOptions()
    getLists()
})
</script>
