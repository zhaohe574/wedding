<template>
    <div class="staff-center-booking">
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[200px]" label="订单编号">
                    <el-input v-model="queryParams.order_sn" placeholder="输入订单编号" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item class="w-[180px]" label="客户姓名">
                    <el-input
                        v-model="queryParams.customer_name"
                        placeholder="输入客户姓名"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[180px]" label="联系电话">
                    <el-input
                        v-model="queryParams.contact_mobile"
                        placeholder="输入联系电话"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="状态">
                    <el-select v-model="queryParams.status" placeholder="选择状态" clearable>
                        <el-option label="待确认" :value="0" />
                        <el-option label="已确认" :value="1" />
                        <el-option label="已完成" :value="2" />
                        <el-option label="已取消" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[320px]" label="服务日期">
                    <el-date-picker
                        v-model="dateRange"
                        type="daterange"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <div class="mt-4 grid grid-cols-5 gap-4">
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">总预约项</div>
                    <div class="text-2xl font-bold mt-2">{{ statistics.total || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">待确认</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ statistics.pending || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已确认</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ statistics.confirmed || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已完成</div>
                    <div class="text-2xl font-bold mt-2 text-blue-500">{{ statistics.completed || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已取消</div>
                    <div class="text-2xl font-bold mt-2 text-gray-500">{{ statistics.cancelled || 0 }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="预约项ID" prop="id" width="100" />
                <el-table-column label="订单编号" prop="order_sn" min-width="180" />
                <el-table-column label="客户" min-width="140">
                    <template #default="{ row }">
                        <div>{{ row.customer_name || '-' }}</div>
                        <div class="text-gray-400 text-xs">{{ row.customer_phone || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="服务信息" min-width="220">
                    <template #default="{ row }">
                        <div>{{ row.package_name || '-' }}</div>
                        <div class="text-gray-400 text-xs">
                            {{ row.service_date }}
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="金额" width="130">
                    <template #default="{ row }">
                        <span class="text-red-500 font-bold">¥{{ row.subtotal }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="确认状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.confirm_status === 1 ? 'success' : 'warning'">
                            {{ row.confirm_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="项状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getItemStatusType(row.item_status)">
                            {{ row.item_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="订单状态" width="100">
                    <template #default="{ row }">
                        <el-tag size="small">{{ row.order_status_desc }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="170" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button
                            v-if="row.confirm_status === 0 && row.item_status !== 3"
                            type="success"
                            link
                            @click="handleConfirm(row)"
                        >
                            确认
                        </el-button>
                        <el-button
                            v-if="row.confirm_status === 0 && row.item_status !== 3"
                            type="danger"
                            link
                            @click="handleCancel(row)"
                        >
                            取消
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="detailVisible" title="预约详情" width="760px">
            <div v-if="currentDetail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="预约项ID">{{ currentDetail.id }}</el-descriptions-item>
                    <el-descriptions-item label="订单编号">{{ currentDetail.order_sn }}</el-descriptions-item>
                    <el-descriptions-item label="客户姓名">{{ currentDetail.customer_name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="联系电话">{{ currentDetail.customer_phone || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="服务日期">{{ currentDetail.service_date }}</el-descriptions-item>
                    <el-descriptions-item label="套餐">{{ currentDetail.package_name || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="确认状态">{{ currentDetail.confirm_status_desc }}</el-descriptions-item>
                    <el-descriptions-item label="订单项状态">{{ currentDetail.item_status_desc }}</el-descriptions-item>
                    <el-descriptions-item label="订单状态">{{ currentDetail.order_status }}</el-descriptions-item>
                    <el-descriptions-item label="订单总额">¥{{ currentDetail.total_amount }}</el-descriptions-item>
                    <el-descriptions-item label="应付金额">¥{{ currentDetail.pay_amount }}</el-descriptions-item>
                    <el-descriptions-item label="优惠金额">¥{{ currentDetail.discount_amount }}</el-descriptions-item>
                    <el-descriptions-item label="备注" :span="2">{{ currentDetail.remark || '-' }}</el-descriptions-item>
                </el-descriptions>
            </div>
            <template #footer>
                <el-button @click="detailVisible = false">关闭</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts" name="staffCenterBooking">
import { onActivated, reactive, ref, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { usePaging } from '@/hooks/usePaging'
import {
    myBookingCancel,
    myBookingConfirm,
    myBookingDetail,
    myBookingStatistics,
    myBookings
} from '@/api/staff-center'

const queryParams = reactive({
    order_sn: '',
    customer_name: '',
    contact_mobile: '',
    status: '',
    start_date: '',
    end_date: ''
})

const dateRange = ref<string[]>([])
const statistics = ref<any>({})
const detailVisible = ref(false)
const currentDetail = ref<any>(null)

const {
    pager,
    getLists: fetchLists,
    resetPage,
    resetParams
} = usePaging({
    fetchFun: myBookings,
    params: queryParams
})

const getStatistics = async () => {
    statistics.value = (await myBookingStatistics()) || {}
}

const getLists = async () => {
    const res = await fetchLists()
    if (res?.extend) {
        statistics.value = res.extend
    }
}

const handleReset = () => {
    dateRange.value = []
    queryParams.start_date = ''
    queryParams.end_date = ''
    resetParams()
    getStatistics()
}

const handleDetail = async (row: any) => {
    currentDetail.value = await myBookingDetail({ id: row.id })
    detailVisible.value = true
}

const handleConfirm = async (row: any) => {
    await ElMessageBox.confirm('确认该预约项后将推进订单确认流程，是否继续？', '提示')
    await myBookingConfirm({ id: row.id })
    ElMessage.success('确认成功')
    getLists()
    getStatistics()
}

const handleCancel = async (row: any) => {
    const res = await ElMessageBox.prompt('请输入取消原因（选填）', '取消本人预约项', {
        confirmButtonText: '确认取消',
        cancelButtonText: '返回',
        inputType: 'textarea',
        inputPlaceholder: '可不填'
    })
    await myBookingCancel({ id: row.id, reason: res.value || '' })
    ElMessage.success('取消成功')
    getLists()
    getStatistics()
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

watch(
    dateRange,
    (value) => {
        queryParams.start_date = value?.[0] || ''
        queryParams.end_date = value?.[1] || ''
    },
    { deep: true }
)

onActivated(() => {
    getLists()
    getStatistics()
})

getLists()
getStatistics()
</script>
