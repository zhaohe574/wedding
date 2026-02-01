<template>
    <div class="order-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[180px]" label="订单编号">
                    <el-input
                        v-model="queryParams.order_sn"
                        placeholder="输入订单编号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="联系人">
                    <el-input
                        v-model="queryParams.contact_name"
                        placeholder="输入联系人"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="联系电话">
                    <el-input
                        v-model="queryParams.contact_mobile"
                        placeholder="输入联系电话"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="订单状态">
                    <el-select v-model="queryParams.order_status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="待确认" :value="0" />
                        <el-option label="待支付" :value="1" />
                        <el-option label="已支付" :value="2" />
                        <el-option label="服务中" :value="3" />
                        <el-option label="已完成" :value="4" />
                        <el-option label="已评价" :value="5" />
                        <el-option label="已取消" :value="6" />
                        <el-option label="已暂停" :value="7" />
                        <el-option label="已退款" :value="8" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[320px]" label="创建时间">
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
                    <div class="text-gray-500 text-sm">待确认</div>
                    <div class="text-2xl font-bold mt-2 text-yellow-500">{{ getStatusCount(0) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待支付</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ getStatusCount(1) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已支付</div>
                    <div class="text-2xl font-bold mt-2 text-blue-500">{{ getStatusCount(2) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">服务中</div>
                    <div class="text-2xl font-bold mt-2 text-purple-500">{{ getStatusCount(3) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已完成</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ getStatusCount(4) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已评价</div>
                    <div class="text-2xl font-bold mt-2 text-emerald-500">{{ getStatusCount(5) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已取消</div>
                    <div class="text-2xl font-bold mt-2 text-gray-500">{{ getStatusCount(6) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已暂停</div>
                    <div class="text-2xl font-bold mt-2 text-amber-500">{{ getStatusCount(7) }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已退款</div>
                    <div class="text-2xl font-bold mt-2 text-red-500">{{ getStatusCount(8) }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="订单编号" prop="order_sn" min-width="180" />
                <el-table-column label="用户信息" min-width="150">
                    <template #default="{ row }">
                        <div class="flex items-center" v-if="row.user">
                            <el-avatar :src="row.user.avatar" :size="32" />
                            <span class="ml-2">{{ row.user.nickname }}</span>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="联系信息" min-width="140">
                    <template #default="{ row }">
                        <div>{{ row.contact_name }}</div>
                        <div class="text-gray-400 text-xs">{{ row.contact_mobile }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="订单金额" width="120">
                    <template #default="{ row }">
                        <div class="text-red-500 font-bold">¥{{ row.pay_amount }}</div>
                        <div class="text-gray-400 text-xs" v-if="row.discount_amount > 0">
                            优惠: ¥{{ row.discount_amount }}
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="订单状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.order_status)">
                            {{ row.order_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="支付状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.pay_status === 1 ? 'success' : 'info'" size="small">
                            {{ row.pay_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="服务日期" prop="service_date" width="110" />
                <el-table-column label="来源" width="80">
                    <template #default="{ row }">
                        <span>{{ row.source_desc }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button 
                            v-if="row.order_status === 1 && row.pay_type === 4 && row.pay_voucher && row.pay_voucher_status === 0" 
                            type="warning" 
                            link 
                            @click="handleAuditVoucher(row)"
                        >审核凭证</el-button>
                        <el-button 
                            v-if="row.order_status === 2" 
                            type="warning" 
                            link 
                            @click="handleStartService(row)"
                        >开始服务</el-button>
                        <el-button 
                            v-if="row.order_status === 3" 
                            type="success" 
                            link 
                            @click="handleComplete(row)"
                        >完成</el-button>
                        <el-button 
                            v-if="row.order_status <= 1" 
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

        <!-- 订单详情弹窗 -->
        <el-dialog v-model="detailVisible" title="订单详情" width="800px">
            <div v-if="currentOrder" class="order-detail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="订单编号">{{ currentOrder.order_sn }}</el-descriptions-item>
                    <el-descriptions-item label="订单状态">
                        <el-tag :type="getStatusType(currentOrder.order_status)">
                            {{ currentOrder.order_status_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="联系人">{{ getDisplayContactName(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="联系电话">{{ getDisplayContactMobile(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="服务日期">{{ getDisplayServiceDate(currentOrder) }}</el-descriptions-item>
                    <el-descriptions-item label="婚礼日期">{{ currentOrder.wedding_date || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="服务地址" :span="2">{{ currentOrder.service_address || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="订单总额">¥{{ currentOrder.total_amount }}</el-descriptions-item>
                    <el-descriptions-item label="优惠金额">¥{{ currentOrder.discount_amount }}</el-descriptions-item>
                    <el-descriptions-item
                        v-if="Number(currentOrder.coupon_amount || 0) > 0"
                        label="优惠券金额"
                    >
                        ¥{{ currentOrder.coupon_amount }}
                    </el-descriptions-item>
                    <el-descriptions-item label="应付金额">¥{{ currentOrder.pay_amount }}</el-descriptions-item>
                    <el-descriptions-item label="已付金额">
                        <span class="text-red-500 font-bold">¥{{ getDisplayPaidAmount(currentOrder) }}</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="支付方式">{{ currentOrder.pay_type_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="支付状态">{{ currentOrder.pay_status_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="线下凭证" :span="2">
                        <el-image
                            v-if="currentOrder.pay_voucher"
                            :src="currentOrder.pay_voucher"
                            fit="contain"
                            style="width: 100%; max-height: 260px"
                        />
                        <span v-else>-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="凭证状态">
                        {{ currentOrder.pay_voucher_status_desc || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="审核备注">
                        {{ currentOrder.pay_voucher_audit_remark || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="用户备注" :span="2">{{ currentOrder.user_remark || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="管理备注" :span="2">{{ currentOrder.admin_remark || '-' }}</el-descriptions-item>
                </el-descriptions>

                <div class="mt-4" v-if="currentOrder.items && currentOrder.items.length > 0">
                    <h4 class="font-bold mb-2">服务项目</h4>
                    <el-table :data="currentOrder.items" border size="small">
                        <el-table-column label="工作人员" prop="staff_name" />
                        <el-table-column label="套餐" prop="package_name" />
                        <el-table-column label="服务日期" prop="service_date" />
                        <el-table-column label="单价" prop="price">
                            <template #default="{ row }">¥{{ row.price }}</template>
                        </el-table-column>
                        <el-table-column label="数量" prop="quantity" />
                        <el-table-column label="小计" prop="subtotal">
                            <template #default="{ row }">¥{{ row.subtotal }}</template>
                        </el-table-column>
                    </el-table>
                </div>

                <div class="mt-4" v-if="currentOrder.logs && currentOrder.logs.length > 0">
                    <h4 class="font-bold mb-2">操作日志</h4>
                    <el-timeline>
                        <el-timeline-item 
                            v-for="log in currentOrder.logs" 
                            :key="log.id"
                            :timestamp="log.create_time"
                            placement="top"
                        >
                            <span class="text-gray-500">[{{ log.operator_type_desc }}]</span>
                            {{ log.content }}
                        </el-timeline-item>
                    </el-timeline>
                </div>
            </div>
        </el-dialog>

        <!-- 线下凭证审核弹窗 -->
        <el-dialog v-model="auditVisible" title="线下凭证审核" width="520px">
            <el-form :model="auditForm" label-width="100px">
                <el-form-item label="订单编号">
                    <span>{{ auditForm.order_sn || '-' }}</span>
                </el-form-item>
                <el-form-item label="支付金额">
                    <span>¥{{ auditForm.pay_amount }}</span>
                </el-form-item>
                <el-form-item label="支付凭证">
                    <el-image
                        v-if="auditForm.voucher"
                        :src="auditForm.voucher"
                        fit="contain"
                        style="width: 100%; max-height: 260px"
                    />
                    <span v-else>未上传</span>
                </el-form-item>
                <el-form-item label="审核备注">
                    <el-input
                        v-model="auditForm.remark"
                        type="textarea"
                        :rows="3"
                        placeholder="可填写拒绝原因或备注"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="auditVisible = false">取消</el-button>
                <el-button type="danger" @click="submitAudit(0)">拒绝</el-button>
                <el-button type="primary" @click="submitAudit(1)">通过</el-button>
            </template>
        </el-dialog>

        <!-- 取消订单弹窗 -->
        <el-dialog v-model="cancelVisible" title="取消订单" width="500px">
            <el-form :model="cancelForm" label-width="100px">
                <el-form-item label="取消原因">
                    <el-input 
                        v-model="cancelForm.reason" 
                        type="textarea" 
                        :rows="3" 
                        placeholder="请输入取消原因"
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

<script lang="ts" setup name="orderLists">
import { 
    orderLists, 
    orderDetail, 
    orderStatistics, 
    orderCancel, 
    orderStartService, 
    orderComplete,
    orderAuditVoucher
} from '@/api/order'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    order_sn: '',
    contact_name: '',
    contact_mobile: '',
    order_status: '',
    create_time: []
})

const statistics = ref<any>({})
const detailVisible = ref(false)
const currentOrder = ref<any>(null)
const auditVisible = ref(false)
const auditForm = reactive({
    id: 0,
    order_sn: '',
    pay_amount: 0,
    voucher: '',
    remark: ''
})
const cancelVisible = ref(false)
const cancelForm = reactive({
    id: 0,
    reason: ''
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: orderLists,
    params: queryParams
})

const getStatistics = async () => {
    const res = await orderStatistics()
    statistics.value = res
}

const getStatusCount = (status: number) => {
    if (!statistics.value.status_counts) return 0
    const item = statistics.value.status_counts.find((s: any) => s.status === status)
    return item ? item.count : 0
}

const getStatusType = (
    status: number
): 'warning' | 'primary' | 'info' | 'success' | 'danger' => {
    const types: Record<number, 'warning' | 'primary' | 'info' | 'success' | 'danger'> = {
        0: 'warning',
        1: 'warning',
        2: 'primary',
        3: 'info',
        4: 'success',
        5: 'success',
        6: 'info',
        7: 'warning',
        8: 'danger'
    }
    return types[status] || 'info'
}

const getDisplayContactName = (order: any) => {
    return order?.contact_name || order?.user?.nickname || '-'
}

const getDisplayContactMobile = (order: any) => {
    return order?.contact_mobile || order?.user?.mobile || '-'
}

const getDisplayServiceDate = (order: any) => {
    if (order?.service_date) return order.service_date
    const dates = (order?.items || [])
        .map((item: any) => item.service_date || item.schedule_date)
        .filter(Boolean)
    if (!dates.length) return '-'
    return Array.from(new Set(dates)).join('、')
}

const getDisplayPaidAmount = (order: any) => {
    return Number(order?.paid_amount ?? 0).toFixed(2)
}

const handleDetail = async (row: any) => {
    const res = await orderDetail({ id: row.id })
    currentOrder.value = res
    detailVisible.value = true
}

const handleAuditVoucher = (row: any) => {
    auditForm.id = row.id
    auditForm.order_sn = row.order_sn || ''
    auditForm.pay_amount = row.pay_amount || 0
    auditForm.voucher = row.pay_voucher || ''
    auditForm.remark = ''
    auditVisible.value = true
}

const submitAudit = async (approved: number) => {
    await orderAuditVoucher({
        id: auditForm.id,
        approved,
        remark: auditForm.remark
    })
    feedback.msgSuccess('操作成功')
    auditVisible.value = false
    getLists()
    getStatistics()
}

const handleStartService = async (row: any) => {
    await feedback.confirm('确定要开始服务吗？')
    await orderStartService({ id: row.id })
    feedback.msgSuccess('操作成功')
    getLists()
    getStatistics()
}

const handleComplete = async (row: any) => {
    await feedback.confirm('确定要完成订单吗？')
    await orderComplete({ id: row.id })
    feedback.msgSuccess('操作成功')
    getLists()
    getStatistics()
}

const handleCancel = (row: any) => {
    cancelForm.id = row.id
    cancelForm.reason = ''
    cancelVisible.value = true
}

const submitCancel = async () => {
    await orderCancel(cancelForm)
    feedback.msgSuccess('订单已取消')
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
.order-detail :deep(.el-descriptions__label) {
    width: 100px;
}
</style>
