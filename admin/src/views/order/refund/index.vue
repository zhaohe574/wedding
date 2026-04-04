<template>
    <admin-page-shell class="refund-lists" title="退款管理">
        <template #search>
            <search-panel>
                <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[180px]" label="退款编号">
                    <el-input
                        v-model="queryParams.refund_sn"
                        placeholder="输入退款编号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="退款状态">
                    <el-select v-model="queryParams.refund_status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="待审核" :value="0" />
                        <el-option label="审核通过" :value="1" />
                        <el-option label="退款中" :value="2" />
                        <el-option label="已退款" :value="3" />
                        <el-option label="已拒绝" :value="4" />
                        <el-option label="退款失败" :value="5" />
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
            <stat-panel :items="refundStatItems" :columns="6" />
        </template>

        <div class="admin-page-section">
            <el-card class="!border-none" shadow="never">
                <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="退款编号" prop="refund_sn" min-width="180" />
                <el-table-column label="订单编号" min-width="180">
                    <template #default="{ row }">
                        <span v-if="row.order">{{ row.order.order_sn }}</span>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="用户" min-width="120">
                    <template #default="{ row }">
                        <span v-if="row.order && row.order.user">{{ row.order.user.nickname }}</span>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="退款金额" width="120">
                    <template #default="{ row }">
                        <span class="text-red-500 font-bold">¥{{ row.refund_amount }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="退款类型" width="100">
                    <template #default="{ row }">
                        <el-tag size="small">{{ row.refund_type_desc }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="退款状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.refund_status)">
                            {{ row.refund_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="退款原因" prop="refund_reason" min-width="150" show-overflow-tooltip />
                <el-table-column label="申请时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="180" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button 
                            v-if="row.refund_status === 0" 
                            type="success" 
                            link 
                            @click="handleAudit(row, true)"
                        >通过</el-button>
                        <el-button 
                            v-if="row.refund_status === 0" 
                            type="danger" 
                            link 
                            @click="handleAudit(row, false)"
                        >拒绝</el-button>
                        <el-button 
                            v-if="row.can_confirm_offline && (row.refund_status === 1 || row.refund_status === 2)" 
                            type="warning" 
                            link 
                            @click="handleConfirm(row)"
                        >确认退款</el-button>
                    </template>
                </el-table-column>
                </el-table>
                <div class="flex justify-end mt-4">
                    <pagination v-model="pager" @change="getLists" />
                </div>
            </el-card>
        </div>

        <!-- 退款详情弹窗 -->
        <el-dialog v-model="detailVisible" title="退款详情" width="820px">
            <div v-if="currentRefund">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="退款编号">{{ currentRefund.refund_sn }}</el-descriptions-item>
                    <el-descriptions-item label="退款状态">
                        <el-tag :type="getStatusType(currentRefund.refund_status)">
                            {{ currentRefund.refund_status_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="订单编号">
                        {{ currentRefund.order?.order_sn || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="退款金额">
                        <span class="text-red-500 font-bold">¥{{ currentRefund.refund_amount }}</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="实际退款金额">
                        <span class="text-red-500 font-bold">¥{{ formatAmount(currentRefund.actual_refund_amount || 0) }}</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="退款方式">
                        {{ currentRefund.can_confirm_offline ? '线下人工确认' : '系统自动退款' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="退款类型">{{ currentRefund.refund_type_desc }}</el-descriptions-item>
                    <el-descriptions-item label="申请时间">{{ currentRefund.create_time }}</el-descriptions-item>
                    <el-descriptions-item label="退款原因" :span="2">{{ currentRefund.refund_reason }}</el-descriptions-item>
                    <el-descriptions-item label="审核时间">{{ currentRefund.audit_time || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="退款时间">{{ currentRefund.refund_time || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="退款流水号" :span="2">{{ currentRefund.refund_transaction_id || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="审核备注" :span="2">{{ currentRefund.audit_remark || '-' }}</el-descriptions-item>
                </el-descriptions>
                <div class="mt-4">
                    <div class="mb-3 text-base font-medium text-tx-primary">退款拆分明细</div>
                    <el-table :data="currentRefund.refund_items || []" size="small" border>
                        <el-table-column label="支付流水号" min-width="160">
                            <template #default="{ row }">
                                {{ row.payment?.payment_sn || row.payment?.order_sn || '-' }}
                            </template>
                        </el-table-column>
                        <el-table-column label="支付方式" min-width="100">
                            <template #default="{ row }">
                                {{ getPayWayText(Number(row.pay_way || 0)) }}
                            </template>
                        </el-table-column>
                        <el-table-column label="退款金额" min-width="100">
                            <template #default="{ row }">
                                ¥{{ formatAmount(row.refund_amount || 0) }}
                            </template>
                        </el-table-column>
                        <el-table-column label="子项状态" min-width="100">
                            <template #default="{ row }">
                                <el-tag :type="getRefundItemStatusType(Number(row.refund_status || 0))" size="small">
                                    {{ getRefundItemStatusText(Number(row.refund_status || 0)) }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="退款单号" prop="out_refund_no" min-width="180" />
                        <el-table-column label="第三方退款单号" min-width="180">
                            <template #default="{ row }">
                                {{ row.third_refund_no || '-' }}
                            </template>
                        </el-table-column>
                        <el-table-column label="处理说明" min-width="200" show-overflow-tooltip>
                            <template #default="{ row }">
                                {{ row.refund_msg || '-' }}
                            </template>
                        </el-table-column>
                    </el-table>
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
                        placeholder="请输入审核备注（可选）"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="auditVisible = false">取消</el-button>
                <el-button :type="auditForm.approved ? 'success' : 'danger'" @click="submitAudit">
                    {{ auditForm.approved ? '确认通过' : '确认拒绝' }}
                </el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script lang="ts" setup name="refundLists">
import { refundLists, refundDetail, refundStatistics, refundAudit, refundConfirm } from '@/api/order'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    refund_sn: '',
    refund_status: '',
    create_time: []
})

const statistics = ref<any>({})
const detailVisible = ref(false)
const currentRefund = ref<any>(null)
const auditVisible = ref(false)
const auditForm = reactive({
    id: 0,
    approved: true,
    remark: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: refundLists,
    params: queryParams
})

const getStatistics = async () => {
    const res = await refundStatistics()
    statistics.value = res
}

const getStatusCount = (status: number) => {
    if (!statistics.value.status_counts) return 0
    const item = statistics.value.status_counts.find((s: any) => s.status === status)
    return item ? item.count : 0
}

type StatAccent = 'primary' | 'success' | 'warning' | 'danger' | 'muted'
type StatusTagType = 'success' | 'warning' | 'info' | 'primary' | 'danger'

interface RefundStatItem {
    label: string
    value: number
    accent: StatAccent
}

const refundStatItems = computed<RefundStatItem[]>(() => [
    {
        label: '待审核',
        value: getStatusCount(0),
        accent: 'warning'
    },
    {
        label: '审核通过',
        value: getStatusCount(1),
        accent: 'primary'
    },
    {
        label: '退款中',
        value: getStatusCount(2),
        accent: 'muted'
    },
    {
        label: '已退款',
        value: getStatusCount(3),
        accent: 'success'
    },
    {
        label: '已拒绝',
        value: getStatusCount(4),
        accent: 'danger'
    },
    {
        label: '退款失败',
        value: getStatusCount(5),
        accent: 'danger'
    }
])

const getStatusType = (status: number): StatusTagType => {
    const types: Record<number, StatusTagType> = {
        0: 'warning',
        1: 'primary',
        2: 'info',
        3: 'success',
        4: 'danger',
        5: 'danger'
    }
    return types[status] || 'info'
}

const getRefundItemStatusType = (status: number): StatusTagType => {
    const types: Record<number, StatusTagType> = {
        0: 'warning',
        1: 'info',
        2: 'success',
        3: 'danger'
    }
    return types[status] || 'info'
}

const getRefundItemStatusText = (status: number) => {
    const texts: Record<number, string> = {
        0: '待执行',
        1: '处理中',
        2: '已完成',
        3: '失败'
    }
    return texts[status] || '未知'
}

const getPayWayText = (payWay: number) => {
    const texts: Record<number, string> = {
        1: '微信支付',
        2: '支付宝',
        3: '余额支付',
        4: '线下支付'
    }
    return texts[payWay] || '未知'
}

const formatAmount = (value: number | string) => Number(value || 0).toFixed(2)

const handleDetail = async (row: any) => {
    const res = await refundDetail({ id: row.id })
    currentRefund.value = res
    detailVisible.value = true
}

const handleAudit = (row: any, approved: boolean) => {
    auditForm.id = row.id
    auditForm.approved = approved
    auditForm.remark = ''
    auditVisible.value = true
}

const submitAudit = async () => {
    await refundAudit(auditForm)
    feedback.msgSuccess('审核成功')
    auditVisible.value = false
    getLists()
    getStatistics()
}

const handleConfirm = async (row: any) => {
    await feedback.confirm('确定该线下退款已经完成吗？')
    await refundConfirm({ id: row.id })
    feedback.msgSuccess('操作成功')
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
