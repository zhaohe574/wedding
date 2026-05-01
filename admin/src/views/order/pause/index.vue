<template>
    <admin-page-shell class="order-pause-lists" title="订单暂停">
        <template #search>
            <search-panel>
                <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[180px]" label="暂停单号">
                    <el-input
                        v-model="queryParams.pause_sn"
                        placeholder="输入暂停单号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[180px]" label="订单编号">
                    <el-input
                        v-model="queryParams.order_sn"
                        placeholder="输入订单编号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[180px]" label="暂停类型">
                    <el-select v-model="queryParams.pause_type" placeholder="选择类型" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="疫情" :value="1" />
                        <el-option label="突发事件" :value="2" />
                        <el-option label="个人原因" :value="3" />
                        <el-option label="其他" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[180px]" label="暂停状态">
                    <el-select v-model="queryParams.pause_status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="待审核" :value="0" />
                        <el-option label="暂停中" :value="1" />
                        <el-option label="已恢复" :value="2" />
                        <el-option label="已拒绝" :value="3" />
                        <el-option label="已取消" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[320px]" label="申请时间">
                    <el-date-picker
                        v-model="queryParams.create_time"
                        type="daterange"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                        clearable
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
                </el-form>
            </search-panel>
        </template>

        <template #stats>
            <stat-panel :items="pauseStatItems" :columns="5" />
        </template>

        <div class="admin-page-section">
            <el-card class="!border-none" shadow="never">
                <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="暂停单号" prop="pause_sn" min-width="160" />
                <el-table-column label="订单编号" min-width="160">
                    <template #default="{ row }">
                        <span class="text-primary cursor-pointer" @click="viewOrder(row.order_id)">
                            {{ row.order_sn }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="用户信息" min-width="140">
                    <template #default="{ row }">
                        <div class="flex items-center" v-if="row.user">
                            <el-avatar :src="row.user.avatar" :size="28" />
                            <span class="ml-2">{{ row.user.nickname }}</span>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="暂停类型" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getTypeTagType(row.pause_type)" size="small">
                            {{ row.pause_type_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="暂停时间" min-width="180">
                    <template #default="{ row }">
                        <div>{{ row.pause_start_date }} ~ {{ row.pause_end_date }}</div>
                        <div class="text-gray-400 text-xs">
                            计划{{ row.pause_days }}天
                            <template v-if="row.pause_status === 1 && row.remain_days !== null">
                                ，剩余<span :class="row.remain_days <= 3 ? 'text-red-500' : ''">{{ row.remain_days }}</span>天
                            </template>
                            <template v-if="row.pause_status === 2">
                                ，实际{{ row.actual_pause_days }}天
                            </template>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusTagType(row.pause_status)" size="small">
                            {{ row.pause_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="暂停原因" prop="pause_reason" min-width="120" show-overflow-tooltip />
                <el-table-column label="申请时间" prop="create_time" width="160" />
                <el-table-column label="操作" width="180" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button 
                            v-if="row.pause_status === 0" 
                            type="success" 
                            link 
                            @click="handleAudit(row, true)"
                        >通过</el-button>
                        <el-button 
                            v-if="row.pause_status === 0" 
                            type="danger" 
                            link 
                            @click="handleAudit(row, false)"
                        >拒绝</el-button>
                        <el-button 
                            v-if="row.pause_status === 1" 
                            type="warning" 
                            link 
                            @click="handleResume(row)"
                        >恢复</el-button>
                        <el-button 
                            v-if="row.pause_status === 1" 
                            type="info" 
                            link 
                            @click="handleExtend(row)"
                        >延期</el-button>
                    </template>
                </el-table-column>
                </el-table>
                <div class="flex justify-end mt-4">
                    <pagination v-model="pager" @change="getLists" />
                </div>
            </el-card>
        </div>

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="暂停详情" width="700px">
            <div v-if="currentPause" class="pause-detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="暂停单号">{{ currentPause.pause_sn }}</el-descriptions-item>
                    <el-descriptions-item label="暂停状态">
                        <el-tag :type="getStatusTagType(currentPause.pause_status)" size="small">
                            {{ currentPause.pause_status_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="订单编号">{{ currentPause.order_sn }}</el-descriptions-item>
                    <el-descriptions-item label="暂停类型">
                        <el-tag :type="getTypeTagType(currentPause.pause_type)" size="small">
                            {{ currentPause.pause_type_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="申请用户" v-if="currentPause.user">
                        {{ currentPause.user.nickname }} ({{ currentPause.user.mobile }})
                    </el-descriptions-item>
                    <el-descriptions-item label="申请时间">{{ currentPause.create_time }}</el-descriptions-item>
                    <el-descriptions-item label="暂停开始">{{ currentPause.pause_start_date }}</el-descriptions-item>
                    <el-descriptions-item label="暂停结束">{{ currentPause.pause_end_date }}</el-descriptions-item>
                    <el-descriptions-item label="计划天数">{{ currentPause.pause_days }} 天</el-descriptions-item>
                    <el-descriptions-item label="实际天数" v-if="currentPause.pause_status === 2">
                        {{ currentPause.actual_pause_days }} 天
                    </el-descriptions-item>
                    <el-descriptions-item label="原服务日期">{{ currentPause.original_service_date || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="新服务日期" v-if="currentPause.new_service_date">
                        {{ currentPause.new_service_date }}
                    </el-descriptions-item>
                    <el-descriptions-item label="暂停原因" :span="2">
                        {{ currentPause.pause_reason }}
                    </el-descriptions-item>
                    <el-descriptions-item label="审核备注" :span="2" v-if="currentPause.audit_remark">
                        {{ currentPause.audit_remark }}
                    </el-descriptions-item>
                    <el-descriptions-item label="拒绝原因" :span="2" v-if="currentPause.reject_reason">
                        <span class="text-red-500">{{ currentPause.reject_reason }}</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="恢复备注" :span="2" v-if="currentPause.resume_remark">
                        {{ currentPause.resume_remark }}
                    </el-descriptions-item>
                </el-descriptions>

                <!-- 证明材料 -->
                <div class="mt-4" v-if="currentPause.proof_images && currentPause.proof_images.length > 0">
                    <h4 class="font-bold mb-2">证明材料</h4>
                    <el-image
                        v-for="(img, index) in currentPause.proof_images"
                        :key="index"
                        :src="img"
                        :preview-src-list="currentPause.proof_images"
                        :initial-index="index"
                        fit="cover"
                        class="w-20 h-20 mr-2 rounded"
                    />
                </div>

                <div class="mt-4">
                    <h4 class="font-bold mb-2">操作日志</h4>
                    <el-empty v-if="pauseLogs.length === 0" description="暂无操作日志" :image-size="80" />
                    <el-timeline v-else>
                        <el-timeline-item
                            v-for="log in pauseLogs"
                            :key="log.id || `${log.action}-${log.create_time}`"
                            :timestamp="formatLogTime(log.create_time)"
                            placement="top"
                        >
                            <div class="operation-log">
                                <div class="operation-log__meta">
                                    <span>{{ getLogOperator(log) }}</span>
                                    <span v-if="log.action_desc || log.action"> / {{ log.action_desc || log.action }}</span>
                                </div>
                                <div class="operation-log__content">{{ log.content || '-' }}</div>
                            </div>
                        </el-timeline-item>
                    </el-timeline>
                </div>
            </div>
        </el-dialog>

        <!-- 审核弹窗 -->
        <el-dialog v-model="auditVisible" :title="auditForm.approved ? '审核通过' : '审核拒绝'" width="500px">
            <el-form :model="auditForm" label-width="100px">
                <el-form-item label="审核备注">
                    <el-input 
                        v-model="auditForm.remark" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入审核备注（选填）"
                    />
                </el-form-item>
                <el-form-item label="拒绝原因" v-if="!auditForm.approved" required>
                    <el-input 
                        v-model="auditForm.reject_reason" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入拒绝原因"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="auditVisible = false">取消</el-button>
                <el-button 
                    :type="auditForm.approved ? 'success' : 'danger'" 
                    @click="submitAudit"
                >确认</el-button>
            </template>
        </el-dialog>

        <!-- 恢复弹窗 -->
        <el-dialog v-model="resumeVisible" title="恢复订单" width="500px">
            <el-form :model="resumeForm" label-width="100px">
                <el-form-item label="新服务日期">
                    <el-date-picker
                        v-model="resumeForm.new_service_date"
                        type="date"
                        placeholder="选择新服务日期（可选）"
                        value-format="YYYY-MM-DD"
                    />
                </el-form-item>
                <el-form-item label="恢复备注">
                    <el-input 
                        v-model="resumeForm.remark" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入恢复备注（选填）"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="resumeVisible = false">取消</el-button>
                <el-button type="primary" @click="submitResume">确认恢复</el-button>
            </template>
        </el-dialog>

        <!-- 延期弹窗 -->
        <el-dialog v-model="extendVisible" title="延长暂停时间" width="500px">
            <el-form :model="extendForm" label-width="120px">
                <el-form-item label="当前结束日期">
                    <span>{{ extendForm.current_end_date }}</span>
                </el-form-item>
                <el-form-item label="新结束日期" required>
                    <el-date-picker
                        v-model="extendForm.new_end_date"
                        type="date"
                        placeholder="选择新结束日期"
                        value-format="YYYY-MM-DD"
                        :disabled-date="(date: Date) => date <= new Date(extendForm.current_end_date)"
                    />
                </el-form-item>
                <el-form-item label="延期说明">
                    <el-input 
                        v-model="extendForm.remark" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入延期说明（选填）"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="extendVisible = false">取消</el-button>
                <el-button type="primary" @click="submitExtend">确认延期</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script lang="ts" setup name="orderPause">
import { 
    orderPauseLists, 
    orderPauseDetail, 
    orderPauseStatistics,
    orderPauseAudit,
    orderPauseResume,
    orderPauseExtend,
    orderPauseLogs
} from '@/api/order/pause'
import { usePaging } from '@/hooks/usePaging'
import { timeFormat } from '@/utils/util'
import feedback from '@/utils/feedback'
import { useRouter } from 'vue-router'

const router = useRouter()

const queryParams = reactive({
    pause_sn: '',
    order_sn: '',
    pause_type: '',
    pause_status: '',
    create_time: []
})

const statistics = ref<any>({})
const detailVisible = ref(false)
const currentPause = ref<any>(null)
const pauseLogs = ref<any[]>([])
const auditVisible = ref(false)
const auditForm = reactive({
    id: 0,
    approved: true,
    remark: '',
    reject_reason: ''
})
const resumeVisible = ref(false)
const resumeForm = reactive({
    id: 0,
    new_service_date: '',
    remark: ''
})
const extendVisible = ref(false)
const extendForm = reactive({
    id: 0,
    current_end_date: '',
    new_end_date: '',
    remark: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: orderPauseLists,
    params: queryParams
})

const getStatistics = async () => {
    const res = await orderPauseStatistics({})
    statistics.value = res
}

const getStatusCount = (status: number) => {
    if (!statistics.value.status_counts) return 0
    const item = statistics.value.status_counts.find((s: any) => s.status === status)
    return item ? item.count : 0
}

type StatAccent = 'primary' | 'success' | 'warning' | 'danger' | 'muted'
type StatusTagType = 'success' | 'warning' | 'info' | 'primary' | 'danger'

interface PauseStatItem {
    label: string
    value: number
    suffix?: string
    accent: StatAccent
}

const pauseStatItems = computed<PauseStatItem[]>(() => [
    {
        label: '待审核',
        value: getStatusCount(0),
        accent: 'warning'
    },
    {
        label: '暂停中',
        value: getStatusCount(1),
        accent: 'primary'
    },
    {
        label: '即将到期',
        value: Number(statistics.value.expiring_count || 0),
        accent: 'danger'
    },
    {
        label: '已恢复',
        value: getStatusCount(2),
        accent: 'success'
    },
    {
        label: '平均暂停天数',
        value: Number(statistics.value.avg_pause_days || 0),
        suffix: '天',
        accent: 'muted'
    }
])

const getTypeTagType = (type: number): StatusTagType => {
    const types: Record<number, StatusTagType> = {
        1: 'danger',
        2: 'warning',
        3: 'info',
        4: 'info'
    }
    return types[type] ?? 'info'
}

const getStatusTagType = (status: number): StatusTagType => {
    const types: Record<number, StatusTagType> = {
        0: 'warning',
        1: 'primary',
        2: 'success',
        3: 'danger',
        4: 'info'
    }
    return types[status] ?? 'info'
}

const viewOrder = (orderId: number) => {
    if (!orderId) {
        return
    }
    router.push({
        path: '/order/lists',
        query: {
            detail_id: String(orderId)
        }
    })
}

const handleDetail = async (row: any) => {
    const res = await orderPauseDetail({ id: row.id })
    currentPause.value = res
    pauseLogs.value = await orderPauseLogs({ id: row.id })
    detailVisible.value = true
}

const formatLogTime = (value: number | string) => {
    if (!value) {
        return '-'
    }
    const numericValue = Number(value)
    if (!Number.isNaN(numericValue)) {
        return timeFormat(numericValue, 'yyyy-mm-dd hh:MM:ss')
    }
    return String(value)
}

const getLogOperator = (log: any) => {
    return log.operator_name || log.operator_type_desc || '系统'
}

const handleAudit = (row: any, approved: boolean) => {
    auditForm.id = row.id
    auditForm.approved = approved
    auditForm.remark = ''
    auditForm.reject_reason = ''
    auditVisible.value = true
}

const submitAudit = async () => {
    if (!auditForm.approved && !auditForm.reject_reason) {
        feedback.msgError('请填写拒绝原因')
        return
    }
    await orderPauseAudit({
        id: auditForm.id,
        approved: auditForm.approved ? 1 : 0,
        remark: auditForm.remark,
        reject_reason: auditForm.reject_reason
    })
    feedback.msgSuccess('操作成功')
    auditVisible.value = false
    getLists()
    getStatistics()
}

const handleResume = (row: any) => {
    resumeForm.id = row.id
    resumeForm.new_service_date = ''
    resumeForm.remark = ''
    resumeVisible.value = true
}

const submitResume = async () => {
    await orderPauseResume(resumeForm)
    feedback.msgSuccess('订单已恢复')
    resumeVisible.value = false
    getLists()
    getStatistics()
}

const handleExtend = (row: any) => {
    extendForm.id = row.id
    extendForm.current_end_date = row.pause_end_date
    extendForm.new_end_date = ''
    extendForm.remark = ''
    extendVisible.value = true
}

const submitExtend = async () => {
    if (!extendForm.new_end_date) {
        feedback.msgError('请选择新结束日期')
        return
    }
    await orderPauseExtend(extendForm)
    feedback.msgSuccess('延期成功')
    extendVisible.value = false
    getLists()
    getStatistics()
}

onActivated(() => {
    getLists()
    getStatistics()
})

getLists()
getStatistics()
</script>

<style scoped>
.pause-detail :deep(.el-descriptions__label) {
    width: 100px;
}

.operation-log {
    line-height: 1.6;
}

.operation-log__meta {
    margin-bottom: 4px;
    font-size: 12px;
    color: var(--admin-color-muted);
}

.operation-log__content {
    color: var(--admin-color-title);
}
</style>
