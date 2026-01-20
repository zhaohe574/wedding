<template>
    <div class="order-transfer-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[180px]" label="转让单号">
                    <el-input
                        v-model="queryParams.transfer_sn"
                        placeholder="输入转让单号"
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
                <el-form-item class="w-[150px]" label="用户关键词">
                    <el-input
                        v-model="queryParams.user_keyword"
                        placeholder="姓名/手机号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[120px]" label="转让状态">
                    <el-select v-model="queryParams.transfer_status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="待审核" :value="0" />
                        <el-option label="待接收" :value="1" />
                        <el-option label="接收确认" :value="2" />
                        <el-option label="转让完成" :value="3" />
                        <el-option label="已拒绝" :value="4" />
                        <el-option label="已取消" :value="5" />
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
        <div class="mt-4 grid grid-cols-6 gap-4">
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待审核</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ getStatusCount(0) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待接收</div>
                    <div class="text-2xl font-bold mt-2 text-blue-500">{{ getStatusCount(1) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">接收确认</div>
                    <div class="text-2xl font-bold mt-2 text-purple-500">{{ getStatusCount(2) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">转让完成</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ getStatusCount(3) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已拒绝</div>
                    <div class="text-2xl font-bold mt-2 text-red-500">{{ getStatusCount(4) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已取消</div>
                    <div class="text-2xl font-bold mt-2 text-gray-500">{{ getStatusCount(5) }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="转让单号" prop="transfer_sn" min-width="160" />
                <el-table-column label="订单编号" min-width="160">
                    <template #default="{ row }">
                        <span class="text-primary cursor-pointer" @click="viewOrder(row.order_id)">
                            {{ row.order_sn }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="转让方" min-width="140">
                    <template #default="{ row }">
                        <div>{{ row.from_user_name }}</div>
                        <div class="text-gray-400 text-xs">{{ row.from_user_mobile }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="接收方" min-width="140">
                    <template #default="{ row }">
                        <div>{{ row.to_user_name }}</div>
                        <div class="text-gray-400 text-xs">{{ row.to_user_mobile }}</div>
                        <el-tag v-if="row.to_user_verified" type="success" size="small" class="mt-1">已验证</el-tag>
                        <el-tag v-else type="info" size="small" class="mt-1">未验证</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="订单金额" width="110">
                    <template #default="{ row }">
                        <span class="text-red-500 font-bold" v-if="row.order">¥{{ row.order.pay_amount }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusTagType(row.transfer_status)" size="small">
                            {{ row.transfer_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="手续费" width="90">
                    <template #default="{ row }">
                        <span v-if="row.transfer_fee > 0" class="text-orange-500">¥{{ row.transfer_fee }}</span>
                        <span v-else class="text-gray-400">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="申请时间" prop="create_time" width="160" />
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button 
                            v-if="row.transfer_status === 0" 
                            type="success" 
                            link 
                            @click="handleAudit(row, true)"
                        >通过</el-button>
                        <el-button 
                            v-if="row.transfer_status === 0" 
                            type="danger" 
                            link 
                            @click="handleAudit(row, false)"
                        >拒绝</el-button>
                        <el-button 
                            v-if="row.transfer_status === 1" 
                            type="warning" 
                            link 
                            @click="handleResendCode(row)"
                        >重发验证码</el-button>
                        <el-button 
                            v-if="row.transfer_status === 1 || row.transfer_status === 2" 
                            type="success" 
                            link 
                            @click="handleComplete(row)"
                        >完成转让</el-button>
                        <el-button 
                            v-if="row.transfer_status <= 2" 
                            type="danger" 
                            link 
                            @click="handleCancel(row)"
                        >取消</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="转让详情" width="700px">
            <div v-if="currentTransfer" class="transfer-detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="转让单号">{{ currentTransfer.transfer_sn }}</el-descriptions-item>
                    <el-descriptions-item label="转让状态">
                        <el-tag :type="getStatusTagType(currentTransfer.transfer_status)" size="small">
                            {{ currentTransfer.transfer_status_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="订单编号">{{ currentTransfer.order_sn }}</el-descriptions-item>
                    <el-descriptions-item label="订单金额" v-if="currentTransfer.order">
                        <span class="text-red-500 font-bold">¥{{ currentTransfer.order.pay_amount }}</span>
                    </el-descriptions-item>
                </el-descriptions>

                <el-divider content-position="left">转让方信息</el-divider>
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="姓名">{{ currentTransfer.from_user_name }}</el-descriptions-item>
                    <el-descriptions-item label="手机号">{{ currentTransfer.from_user_mobile }}</el-descriptions-item>
                </el-descriptions>

                <el-divider content-position="left">接收方信息</el-divider>
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="姓名">{{ currentTransfer.to_user_name }}</el-descriptions-item>
                    <el-descriptions-item label="手机号">{{ currentTransfer.to_user_mobile }}</el-descriptions-item>
                    <el-descriptions-item label="验证状态">
                        <el-tag v-if="currentTransfer.to_user_verified" type="success" size="small">已验证</el-tag>
                        <el-tag v-else type="info" size="small">未验证</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="验证码发送次数">
                        {{ currentTransfer.accept_code_send_count || 0 }} 次
                    </el-descriptions-item>
                </el-descriptions>

                <el-divider content-position="left">时间信息</el-divider>
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="申请时间">{{ currentTransfer.create_time }}</el-descriptions-item>
                    <el-descriptions-item label="审核时间">{{ currentTransfer.audit_time || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="接收时间">{{ currentTransfer.accept_time || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="完成时间">{{ currentTransfer.complete_time || '-' }}</el-descriptions-item>
                </el-descriptions>

                <el-descriptions :column="1" border class="mt-4">
                    <el-descriptions-item label="转让原因">
                        {{ currentTransfer.transfer_reason || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="审核备注" v-if="currentTransfer.audit_remark">
                        {{ currentTransfer.audit_remark }}
                    </el-descriptions-item>
                    <el-descriptions-item label="拒绝原因" v-if="currentTransfer.reject_reason">
                        <span class="text-red-500">{{ currentTransfer.reject_reason }}</span>
                    </el-descriptions-item>
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

        <!-- 取消弹窗 -->
        <el-dialog v-model="cancelVisible" title="取消转让" width="500px">
            <el-form :model="cancelForm" label-width="100px">
                <el-form-item label="取消原因">
                    <el-input 
                        v-model="cancelForm.reason" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入取消原因（选填）"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="cancelVisible = false">取消</el-button>
                <el-button type="danger" @click="submitCancel">确认取消</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="orderTransfer">
import { 
    orderTransferLists, 
    orderTransferDetail, 
    orderTransferStatistics,
    orderTransferAudit,
    orderTransferComplete,
    orderTransferCancel,
    orderTransferResendCode
} from '@/api/order/transfer'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    transfer_sn: '',
    order_sn: '',
    user_keyword: '',
    transfer_status: '',
    create_time: []
})

const statistics = ref<any>({})
const detailVisible = ref(false)
const currentTransfer = ref<any>(null)
const auditVisible = ref(false)
const auditForm = reactive({
    id: 0,
    approved: true,
    remark: '',
    reject_reason: ''
})
const cancelVisible = ref(false)
const cancelForm = reactive({
    id: 0,
    reason: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: orderTransferLists,
    params: queryParams
})

const getStatistics = async () => {
    const res = await orderTransferStatistics({})
    statistics.value = res
}

const getStatusCount = (status: number) => {
    if (!statistics.value.status_counts) return 0
    const item = statistics.value.status_counts.find((s: any) => s.status === status)
    return item ? item.count : 0
}

const getStatusTagType = (status: number) => {
    const types: Record<number, string> = {
        0: 'warning',
        1: 'primary',
        2: 'info',
        3: 'success',
        4: 'danger',
        5: 'info'
    }
    return types[status] || 'info'
}

const viewOrder = (orderId: number) => {
    console.log('View order:', orderId)
}

const handleDetail = async (row: any) => {
    const res = await orderTransferDetail({ id: row.id })
    currentTransfer.value = res
    detailVisible.value = true
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
    await orderTransferAudit({
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

const handleResendCode = async (row: any) => {
    await feedback.confirm('确定要重新发送验证码吗？')
    await orderTransferResendCode({ id: row.id })
    feedback.msgSuccess('验证码已发送')
    getLists()
}

const handleComplete = async (row: any) => {
    await feedback.confirm('确定要手动完成此转让吗？')
    await orderTransferComplete({ id: row.id })
    feedback.msgSuccess('转让完成')
    getLists()
    getStatistics()
}

const handleCancel = (row: any) => {
    cancelForm.id = row.id
    cancelForm.reason = ''
    cancelVisible.value = true
}

const submitCancel = async () => {
    await orderTransferCancel(cancelForm)
    feedback.msgSuccess('已取消')
    cancelVisible.value = false
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
.transfer-detail :deep(.el-descriptions__label) {
    width: 120px;
}
</style>
