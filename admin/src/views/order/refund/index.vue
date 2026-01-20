<template>
    <div class="refund-lists">
        <el-card class="!border-none" shadow="never">
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
        </el-card>

        <!-- 统计卡片 -->
        <div class="mt-4 grid grid-cols-5 gap-4">
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待审核</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ getStatusCount(0) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">审核通过</div>
                    <div class="text-2xl font-bold mt-2 text-blue-500">{{ getStatusCount(1) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">退款中</div>
                    <div class="text-2xl font-bold mt-2 text-purple-500">{{ getStatusCount(2) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已退款</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ getStatusCount(3) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已拒绝</div>
                    <div class="text-2xl font-bold mt-2 text-red-500">{{ getStatusCount(4) }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
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
                            v-if="row.refund_status === 1 || row.refund_status === 2" 
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

        <!-- 退款详情弹窗 -->
        <el-dialog v-model="detailVisible" title="退款详情" width="600px">
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
                    <el-descriptions-item label="退款类型">{{ currentRefund.refund_type_desc }}</el-descriptions-item>
                    <el-descriptions-item label="申请时间">{{ currentRefund.create_time }}</el-descriptions-item>
                    <el-descriptions-item label="退款原因" :span="2">{{ currentRefund.refund_reason }}</el-descriptions-item>
                    <el-descriptions-item label="审核时间">{{ currentRefund.audit_time || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="退款时间">{{ currentRefund.refund_time || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="审核备注" :span="2">{{ currentRefund.audit_remark || '-' }}</el-descriptions-item>
                </el-descriptions>
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
    </div>
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

const getStatusType = (status: number) => {
    const types: Record<number, string> = {
        0: 'warning',
        1: 'primary',
        2: 'info',
        3: 'success',
        4: 'danger'
    }
    return types[status] || 'info'
}

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
    await feedback.confirm('确定退款已完成吗？')
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
