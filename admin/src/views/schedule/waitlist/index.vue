<template>
    <div class="schedule-waitlist">
        <!-- 搜索栏 -->
        <el-card class="!border-none mb-4" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="候补编号">
                    <el-input
                        v-model="queryParams.id"
                        placeholder="请输入候补编号"
                        clearable
                        style="width: 200px"
                    />
                </el-form-item>
                <el-form-item label="客户姓名">
                    <el-input
                        v-model="queryParams.customer_name"
                        placeholder="请输入客户姓名"
                        clearable
                        style="width: 200px"
                    />
                </el-form-item>
                <el-form-item label="候补状态">
                    <el-select v-model="queryParams.status" placeholder="请选择" clearable style="width: 150px">
                        <el-option label="等待中" :value="0" />
                        <el-option label="已通知" :value="1" />
                        <el-option label="已转正" :value="2" />
                        <el-option label="已失效" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="候补日期">
                    <el-date-picker
                        v-model="dateRange"
                        type="daterange"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                        style="width: 280px"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleQuery">查询</el-button>
                    <el-button @click="handleReset">重置</el-button>
                    <el-button type="success" @click="handleBatchNotify">批量通知</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 统计卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card shadow="never">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                            <el-icon class="text-blue-500 text-xl"><List /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">总候补</div>
                            <div class="text-2xl font-bold">{{ statistics.total || 0 }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="never">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center mr-4">
                            <el-icon class="text-orange-500 text-xl"><Clock /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">等待中</div>
                            <div class="text-2xl font-bold text-orange-500">{{ statistics.waiting || 0 }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="never">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                            <el-icon class="text-green-500 text-xl"><CircleCheck /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">已下单</div>
                            <div class="text-2xl font-bold text-green-500">{{ statistics.converted || 0 }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="never">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mr-4">
                            <el-icon class="text-gray-500 text-xl"><Warning /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">已过期</div>
                            <div class="text-2xl font-bold text-gray-500">{{ statistics.expired || 0 }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 列表 -->
        <el-card class="!border-none" shadow="never">
            <el-table :data="tableData" v-loading="loading" style="width: 100%" @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" />
                <el-table-column prop="id" label="候补编号" width="80" />
                <el-table-column prop="customer_name" label="客户姓名" width="120" />
                <el-table-column prop="customer_phone" label="联系电话" width="130" />
                <el-table-column prop="schedule_date" label="期望日期" width="120" />
                <el-table-column prop="time_slot_desc" label="时间段" width="100" />
                <el-table-column prop="service_name" label="候补服务" min-width="150" />
                <el-table-column prop="notify_status" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag v-if="row.notify_status === 0" type="warning">等待中</el-tag>
                        <el-tag v-else-if="row.notify_status === 1" type="primary">已通知</el-tag>
                        <el-tag v-else-if="row.notify_status === 2" type="success">已下单</el-tag>
                        <el-tag v-else type="info">已过期</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
                <el-table-column prop="create_time" label="申请时间" width="160" />
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button link type="primary" size="small" @click="handleView(row)">查看</el-button>
                        <el-button v-if="row.notify_status === 0" link type="success" size="small" @click="handleNotify(row)">
                            通知
                        </el-button>
                        <el-button v-if="row.notify_status === 0 || row.notify_status === 1" link type="warning" size="small" @click="handleConvert(row)">
                            转正
                        </el-button>
                        <el-button v-if="row.notify_status === 0" link type="danger" size="small" @click="handleInvalidate(row)">
                            失效
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <!-- 分页 -->
            <div class="flex justify-end mt-4">
                <el-pagination
                    v-model:current-page="pager.page_no"
                    v-model:page-size="pager.page_size"
                    :page-sizes="[10, 20, 50, 100]"
                    :total="pager.total"
                    background
                    layout="total, sizes, prev, pager, next, jumper"
                    @size-change="handleQuery"
                    @current-change="handleQuery"
                />
            </div>
        </el-card>

        <!-- 查看详情弹窗 -->
        <el-dialog v-model="viewDialogVisible" title="候补详情" width="600px">
            <div v-if="currentRow" class="space-y-4">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="候补编号">{{ currentRow.id }}</el-descriptions-item>
                    <el-descriptions-item label="候补状态">
                        <el-tag v-if="currentRow.notify_status === 0" type="warning">等待中</el-tag>
                        <el-tag v-else-if="currentRow.notify_status === 1" type="primary">已通知</el-tag>
                        <el-tag v-else-if="currentRow.notify_status === 2" type="success">已下单</el-tag>
                        <el-tag v-else type="info">已过期</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="客户姓名">{{ currentRow.customer_name }}</el-descriptions-item>
                    <el-descriptions-item label="联系电话">{{ currentRow.customer_phone }}</el-descriptions-item>
                    <el-descriptions-item label="期望日期">{{ currentRow.schedule_date }}</el-descriptions-item>
                    <el-descriptions-item label="时间段">{{ currentRow.time_slot_desc }}</el-descriptions-item>
                    <el-descriptions-item label="候补服务" :span="2">{{ currentRow.service_name }}</el-descriptions-item>
                    <el-descriptions-item label="备注" :span="2">{{ currentRow.remark || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="申请时间" :span="2">{{ currentRow.create_time }}</el-descriptions-item>
                </el-descriptions>
            </div>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { waitlistLists, waitlistBatchNotify, waitlistNotify, waitlistConvert, waitlistInvalidate, waitlistStatistics } from '@/api/waitlist'

// 查询参数
const queryParams = reactive({
    id: '',
    customer_name: '',
    status: '',
})

// 日期范围
const dateRange = ref<string[]>([])

// 分页器
const pager = reactive({
    page_no: 1,
    page_size: 20,
    total: 0
})

// 统计数据
const statistics = reactive({
    total: 0,
    waiting: 0,
    converted: 0,
    expired: 0
})

// 表格数据
const tableData = ref([])
const loading = ref(false)

// 选中项
const selectedRows = ref<any[]>([])

// 查看详情
const viewDialogVisible = ref(false)
const currentRow = ref<any>(null)

// 查询
const handleQuery = async () => {
    loading.value = true
    try {
        const params: any = {
            page_no: pager.page_no,
            page_size: pager.page_size,
            ...queryParams
        }

        if (dateRange.value && dateRange.value.length === 2) {
            params.start_date = dateRange.value[0]
            params.end_date = dateRange.value[1]
        }

        const res = await waitlistLists(params)
        tableData.value = res.lists || []
        pager.total = res.count || 0

        // 更新统计数据
        if (res.extend) {
            Object.assign(statistics, res.extend)
        }
    } catch (e: any) {
        ElMessage.error(e.message || '获取数据失败')
    } finally {
        loading.value = false
    }
}

// 重置
const handleReset = () => {
    Object.assign(queryParams, {
        id: '',
        customer_name: '',
        status: '',
    })
    dateRange.value = []
    pager.page_no = 1
    handleQuery()
}

// 选择变化
const handleSelectionChange = (selection: any[]) => {
    selectedRows.value = selection
}

// 批量通知
const handleBatchNotify = async () => {
    if (selectedRows.value.length === 0) {
        ElMessage.warning('请先选择需要通知的候补记录')
        return
    }
    try {
        await ElMessageBox.confirm(`确认通知选中的 ${selectedRows.value.length} 条候补记录吗？`, '提示', {
            confirmButtonText: '确认',
            cancelButtonText: '取消',
            type: 'warning'
        })
        await waitlistBatchNotify({
            ids: selectedRows.value.map((item: any) => item.id)
        })
        ElMessage.success('通知成功')
        handleQuery()
    } catch (error) {
        // 用户取消
    }
}

// 查看详情
const handleView = (row: any) => {
    currentRow.value = row
    viewDialogVisible.value = true
}

// 通知客户
const handleNotify = async (row: any) => {
    try {
        await ElMessageBox.confirm('确认通知该客户吗？', '提示', {
            confirmButtonText: '确认',
            cancelButtonText: '取消',
            type: 'warning'
        })
        await waitlistNotify({ id: row.id })
        ElMessage.success('通知成功')
        handleQuery()
    } catch (error) {
        // 用户取消
    }
}

// 转正预约
const handleConvert = async (row: any) => {
    try {
        await ElMessageBox.confirm('确认将该候补转为正式预约吗？', '提示', {
            confirmButtonText: '确认',
            cancelButtonText: '取消',
            type: 'warning'
        })
        await waitlistConvert({ id: row.id })
        ElMessage.success('转正成功')
        handleQuery()
    } catch (error) {
        // 用户取消
    }
}

// 标记失效
const handleInvalidate = async (row: any) => {
    try {
        await ElMessageBox.confirm('确认将该候补标记为失效吗？', '提示', {
            confirmButtonText: '确认',
            cancelButtonText: '取消',
            type: 'warning'
        })
        await waitlistInvalidate({ id: row.id })
        ElMessage.success('操作成功')
        handleQuery()
    } catch (error) {
        // 用户取消
    }
}

onMounted(() => {
    handleQuery()
})
</script>

<style lang="scss" scoped>
.schedule-waitlist {
    // 自定义样式
}
</style>
