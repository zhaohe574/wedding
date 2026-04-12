<template>
    <admin-page-shell class="staff-center-waitlist" title="我的候补">
        <search-panel>
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[180px]" label="候补编号">
                    <el-input v-model="queryParams.id" placeholder="输入候补编号" clearable @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item class="w-[180px]" label="客户姓名">
                    <el-input
                        v-model="queryParams.customer_name"
                        placeholder="输入客户姓名"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="候补状态">
                    <el-select v-model="queryParams.status" placeholder="选择状态" clearable>
                        <el-option label="等待中" :value="0" />
                        <el-option label="已通知" :value="1" />
                        <el-option label="已下单" :value="2" />
                        <el-option label="已过期" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[320px]" label="候补日期">
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
                    <el-button type="success" @click="handleBatchNotify">批量通知</el-button>
                </el-form-item>
            </el-form>
        </search-panel>

        <div class="mt-4 grid grid-cols-4 gap-4">
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">总候补</div>
                    <div class="text-2xl font-bold mt-2">{{ statistics.total || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">等待中</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ statistics.waiting || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已下单</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ statistics.converted || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已过期</div>
                    <div class="text-2xl font-bold mt-2 text-gray-500">{{ statistics.expired || 0 }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists" @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" />
                <el-table-column label="候补编号" prop="id" width="100" />
                <el-table-column label="客户" min-width="140">
                    <template #default="{ row }">
                        <div>{{ row.customer_name || '-' }}</div>
                        <div class="text-gray-400 text-xs">{{ row.customer_phone || '-' }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="期望日期" prop="schedule_date" width="120" />
                <el-table-column label="候补服务" prop="service_name" min-width="160" />
                <el-table-column label="状态" width="110">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.notify_status)">
                            {{ row.notify_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="备注" prop="remark" min-width="180" show-overflow-tooltip />
                <el-table-column label="申请时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleView(row)">详情</el-button>
                        <el-button
                            v-if="row.notify_status === 0"
                            type="success"
                            link
                            @click="handleNotify(row)"
                        >
                            通知
                        </el-button>
                        <el-button
                            v-if="row.notify_status === 0 || row.notify_status === 1"
                            type="warning"
                            link
                            @click="handleConvert(row)"
                        >
                            转正
                        </el-button>
                        <el-button
                            v-if="row.notify_status === 0"
                            type="danger"
                            link
                            @click="handleInvalidate(row)"
                        >
                            失效
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="detailVisible" title="候补详情" width="680px">
            <el-descriptions v-if="currentDetail" :column="2" border>
                <el-descriptions-item label="候补编号">{{ currentDetail.id }}</el-descriptions-item>
                <el-descriptions-item label="状态">{{ currentDetail.notify_status_desc }}</el-descriptions-item>
                <el-descriptions-item label="客户姓名">{{ currentDetail.customer_name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="联系电话">{{ currentDetail.customer_phone || '-' }}</el-descriptions-item>
                <el-descriptions-item label="期望日期">{{ currentDetail.schedule_date }}</el-descriptions-item>
                <el-descriptions-item label="候补服务" :span="2">{{ currentDetail.service_name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="备注" :span="2">{{ currentDetail.remark || '-' }}</el-descriptions-item>
            </el-descriptions>
            <template #footer>
                <el-button @click="detailVisible = false">关闭</el-button>
            </template>
        </el-dialog>
    </admin-page-shell>
</template>

<script setup lang="ts" name="staffCenterWaitlist">
import { onActivated, reactive, ref, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { usePaging } from '@/hooks/usePaging'
import {
    myWaitlist,
    myWaitlistBatchNotify,
    myWaitlistConvert,
    myWaitlistDetail,
    myWaitlistInvalidate,
    myWaitlistNotify,
    myWaitlistStatistics
} from '@/api/staff-center'

const queryParams = reactive({
    id: '',
    customer_name: '',
    status: '',
    start_date: '',
    end_date: ''
})

const dateRange = ref<string[]>([])
const selectedRows = ref<any[]>([])
const statistics = ref<any>({})
const detailVisible = ref(false)
const currentDetail = ref<any>(null)

const {
    pager,
    getLists: fetchLists,
    resetPage,
    resetParams
} = usePaging({
    fetchFun: myWaitlist,
    params: queryParams
})

const getStatistics = async () => {
    statistics.value = (await myWaitlistStatistics()) || {}
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

const handleSelectionChange = (selection: any[]) => {
    selectedRows.value = selection
}

const handleBatchNotify = async () => {
    if (!selectedRows.value.length) {
        ElMessage.warning('请先选择候补记录')
        return
    }
    await ElMessageBox.confirm(`确定通知 ${selectedRows.value.length} 条候补记录吗？`, '提示')
    await myWaitlistBatchNotify({ ids: selectedRows.value.map((item) => item.id) })
    ElMessage.success('通知成功')
    getLists()
    getStatistics()
}

const handleView = async (row: any) => {
    currentDetail.value = await myWaitlistDetail({ id: row.id })
    detailVisible.value = true
}

const handleNotify = async (row: any) => {
    await ElMessageBox.confirm('确定通知该候补客户吗？', '提示')
    await myWaitlistNotify({ id: row.id })
    ElMessage.success('通知成功')
    getLists()
    getStatistics()
}

const handleConvert = async (row: any) => {
    await ElMessageBox.confirm('确定将该候补转为正式预约吗？', '提示')
    await myWaitlistConvert({ id: row.id })
    ElMessage.success('转正成功')
    getLists()
    getStatistics()
}

const handleInvalidate = async (row: any) => {
    await ElMessageBox.confirm('确定标记该候补为失效吗？', '提示')
    await myWaitlistInvalidate({ id: row.id })
    ElMessage.success('操作成功')
    getLists()
    getStatistics()
}

const getStatusType = (status: number): 'warning' | 'primary' | 'success' | 'info' | 'danger' => {
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
