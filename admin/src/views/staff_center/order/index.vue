<template>
    <div class="staff-center-order">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[180px]" label="订单编号">
                    <el-input v-model="queryParams.order_sn" placeholder="输入订单编号" clearable @keyup.enter="resetPage" />
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
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

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
                    <div class="text-gray-500 text-sm">已取消/退款</div>
                    <div class="text-2xl font-bold mt-2 text-gray-500">
                        {{ getStatusCount(6) + getStatusCount(8) }}
                    </div>
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
                        <div>{{ row.contact_name || row.user?.nickname || '-' }}</div>
                        <div class="text-gray-400 text-xs">{{ row.contact_mobile || row.user?.mobile || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="订单金额" width="120">
                    <template #default="{ row }">
                        <div class="text-red-500 font-bold">¥{{ row.pay_amount }}</div>
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
                <el-table-column label="服务日期" prop="service_date" width="120" />
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button
                            v-if="row.order_status === 0 && row.pending_confirm_count > 0"
                            type="success"
                            link
                            @click="handleConfirm(row)"
                        >
                            确认
                        </el-button>
                        <el-button v-if="row.order_status === 2" type="warning" link @click="handleStartService(row)">
                            开始服务
                        </el-button>
                        <el-button v-if="row.order_status === 3" type="success" link @click="handleComplete(row)">
                            完成
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="detailVisible" title="订单详情" width="820px">
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
                    <el-descriptions-item label="订单总额">¥{{ currentOrder.total_amount }}</el-descriptions-item>
                    <el-descriptions-item label="优惠金额">¥{{ currentOrder.discount_amount }}</el-descriptions-item>
                    <el-descriptions-item label="优惠券金额">¥{{ currentOrder.coupon_amount || 0 }}</el-descriptions-item>
                    <el-descriptions-item label="应付金额">¥{{ currentOrder.pay_amount }}</el-descriptions-item>
                    <el-descriptions-item label="已付金额">
                        <span class="text-red-500 font-bold">¥{{ getDisplayPaidAmount(currentOrder) }}</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="支付方式">{{ currentOrder.pay_type_desc || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="支付状态">{{ currentOrder.pay_status_desc || '-' }}</el-descriptions-item>
                </el-descriptions>

                <div class="mt-4" v-if="currentOrder.items?.length">
                    <h4 class="font-bold mb-2">服务项目</h4>
                    <el-table :data="currentOrder.items" border size="small">
                        <el-table-column label="工作人员" prop="staff_name" />
                        <el-table-column label="套餐" prop="package_name" />
                        <el-table-column label="服务日期" prop="service_date" />
                        <el-table-column label="状态" prop="item_status">
                            <template #default="{ row }">
                                <el-tag :type="getItemStatusType(row.item_status)">{{ getItemStatusText(row.item_status) }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="单价" prop="price" />
                        <el-table-column label="数量" prop="quantity" />
                        <el-table-column label="小计" prop="subtotal" />
                    </el-table>
                    <div class="text-xs text-gray-400 mt-2">
                        注：非本人订单项已按权限脱敏，仅保留服务人员、日期和状态。
                    </div>
                </div>
            </div>
        </el-dialog>
    </div>
</template>

<script setup lang="ts" name="staffCenterOrder">
import { onActivated, reactive, ref } from 'vue'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import {
    myOrderConfirm,
    myOrderComplete,
    myOrderDetail,
    myOrders,
    myOrderStartService,
    myOrderStatistics
} from '@/api/staff-center'

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

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: myOrders,
    params: queryParams
})

const getStatistics = async () => {
    statistics.value = (await myOrderStatistics()) || {}
}

const getStatusCount = (status: number) => {
    const item = statistics.value?.status_counts?.find((s: any) => s.status === status)
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
    return dates.length ? Array.from(new Set(dates)).join('、') : '-'
}

const getDisplayPaidAmount = (order: any) => {
    return Number(order?.paid_amount ?? 0).toFixed(2)
}

const getItemStatusText = (status: number) => {
    const map: Record<number, string> = { 0: '待服务', 1: '服务中', 2: '已完成', 3: '已取消' }
    return map[status] || '-'
}

const getItemStatusType = (status: number): 'warning' | 'primary' | 'success' | 'info' | 'danger' => {
    const map: Record<number, 'warning' | 'primary' | 'success' | 'info' | 'danger'> = {
        0: 'warning',
        1: 'primary',
        2: 'success',
        3: 'info'
    }
    return map[status] || 'info'
}

const handleDetail = async (row: any) => {
    currentOrder.value = await myOrderDetail({ id: row.id })
    detailVisible.value = true
}

const handleConfirm = async (row: any) => {
    await feedback.confirm('确认该订单后，将确认当前服务人员名下的全部待确认项目，是否继续？')
    await myOrderConfirm({ id: row.id })
    feedback.msgSuccess('确认成功')
    getLists()
    getStatistics()
}

const handleStartService = async (row: any) => {
    await feedback.confirm('确定要开始服务吗？')
    await myOrderStartService({ id: row.id })
    feedback.msgSuccess('操作成功')
    getLists()
    getStatistics()
}

const handleComplete = async (row: any) => {
    await feedback.confirm('确定要完成订单吗？')
    await myOrderComplete({ id: row.id })
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

<style scoped>
.order-detail :deep(.el-descriptions__label) {
    width: 100px;
}
</style>
