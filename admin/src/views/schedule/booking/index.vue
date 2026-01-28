<template>
    <div class="schedule-booking">
        <!-- 搜索栏 -->
        <el-card class="!border-none mb-4" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="预约编号">
                    <el-input
                        v-model="queryParams.booking_no"
                        placeholder="请输入预约编号"
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
                <el-form-item label="预约状态">
                    <el-select v-model="queryParams.status" placeholder="请选择" clearable style="width: 150px">
                        <el-option label="待确认" :value="0" />
                        <el-option label="已确认" :value="1" />
                        <el-option label="已完成" :value="2" />
                        <el-option label="已取消" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="预约日期">
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
                    <el-button type="success" @click="handleExport">导出</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 统计卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card shadow="never">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                            <el-icon class="text-blue-500 text-xl"><Tickets /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">总预约</div>
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
                            <div class="text-gray-500 text-sm">待确认</div>
                            <div class="text-2xl font-bold text-orange-500">{{ statistics.pending || 0 }}</div>
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
                            <div class="text-gray-500 text-sm">已确认</div>
                            <div class="text-2xl font-bold text-green-500">{{ statistics.confirmed || 0 }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="never">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mr-4">
                            <el-icon class="text-gray-500 text-xl"><Close /></el-icon>
                        </div>
                        <div>
                            <div class="text-gray-500 text-sm">已取消</div>
                            <div class="text-2xl font-bold text-gray-500">{{ statistics.cancelled || 0 }}</div>
                        </div>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 列表 -->
        <el-card class="!border-none" shadow="never">
            <el-table :data="tableData" v-loading="loading" style="width: 100%">
                <el-table-column prop="booking_no" label="预约编号" width="160" />
                <el-table-column prop="customer_name" label="客户姓名" width="120" />
                <el-table-column prop="customer_phone" label="联系电话" width="130" />
                <el-table-column prop="booking_date" label="预约日期" width="120" />
                <el-table-column prop="time_slot" label="时间段" width="100">
                    <template #default="{ row }">
                        <el-tag v-if="row.time_slot === 0" size="small">全天</el-tag>
                        <el-tag v-else-if="row.time_slot === 1" type="success" size="small">早礼</el-tag>
                        <el-tag v-else-if="row.time_slot === 2" type="warning" size="small">午宴</el-tag>
                        <el-tag v-else-if="row.time_slot === 3" type="info" size="small">晚宴</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="service_name" label="预约服务" min-width="150" />
                <el-table-column prop="status" label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag v-if="row.status === 0" type="warning">待确认</el-tag>
                        <el-tag v-else-if="row.status === 1" type="success">已确认</el-tag>
                        <el-tag v-else-if="row.status === 2" type="info">已完成</el-tag>
                        <el-tag v-else type="danger">已取消</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
                <el-table-column prop="create_time" label="预约时间" width="160" />
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button link type="primary" size="small" @click="handleView(row)">查看</el-button>
                        <el-button v-if="row.status === 0" link type="success" size="small" @click="handleConfirm(row)">
                            确认
                        </el-button>
                        <el-button v-if="row.status === 0 || row.status === 1" link type="danger" size="small" @click="handleCancel(row)">
                            取消
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
        <el-dialog v-model="viewDialogVisible" title="预约详情" width="600px">
            <div v-if="currentRow" class="space-y-4">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="预约编号">{{ currentRow.booking_no }}</el-descriptions-item>
                    <el-descriptions-item label="预约状态">
                        <el-tag v-if="currentRow.status === 0" type="warning">待确认</el-tag>
                        <el-tag v-else-if="currentRow.status === 1" type="success">已确认</el-tag>
                        <el-tag v-else-if="currentRow.status === 2" type="info">已完成</el-tag>
                        <el-tag v-else type="danger">已取消</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="客户姓名">{{ currentRow.customer_name }}</el-descriptions-item>
                    <el-descriptions-item label="联系电话">{{ currentRow.customer_phone }}</el-descriptions-item>
                    <el-descriptions-item label="预约日期">{{ currentRow.booking_date }}</el-descriptions-item>
                    <el-descriptions-item label="时间段">
                        {{ currentRow.time_slot === 0 ? '全天' : currentRow.time_slot === 1 ? '早礼' : currentRow.time_slot === 2 ? '午宴' : '晚宴' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="预约服务" :span="2">{{ currentRow.service_name }}</el-descriptions-item>
                    <el-descriptions-item label="备注" :span="2">{{ currentRow.remark || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="预约时间" :span="2">{{ currentRow.create_time }}</el-descriptions-item>
                </el-descriptions>
            </div>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

// 查询参数
const queryParams = reactive({
    booking_no: '',
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
    pending: 0,
    confirmed: 0,
    cancelled: 0
})

// 表格数据
const tableData = ref([])
const loading = ref(false)

// 查看详情
const viewDialogVisible = ref(false)
const currentRow = ref<any>(null)

// 查询
const handleQuery = () => {
    loading.value = true
    // TODO: 调用API获取数据
    setTimeout(() => {
        loading.value = false
    }, 500)
}

// 重置
const handleReset = () => {
    Object.assign(queryParams, {
        booking_no: '',
        customer_name: '',
        status: '',
    })
    dateRange.value = []
    pager.page_no = 1
    handleQuery()
}

// 导出
const handleExport = () => {
    ElMessage.info('导出功能开发中...')
}

// 查看详情
const handleView = (row: any) => {
    currentRow.value = row
    viewDialogVisible.value = true
}

// 确认预约
const handleConfirm = async (row: any) => {
    try {
        await ElMessageBox.confirm('确认通过该预约吗？', '提示', {
            confirmButtonText: '确认',
            cancelButtonText: '取消',
            type: 'warning'
        })
        // TODO: 调用API确认预约
        ElMessage.success('确认成功')
        handleQuery()
    } catch (error) {
        // 用户取消
    }
}

// 取消预约
const handleCancel = async (row: any) => {
    try {
        await ElMessageBox.confirm('确认取消该预约吗？', '提示', {
            confirmButtonText: '确认',
            cancelButtonText: '取消',
            type: 'warning'
        })
        // TODO: 调用API取消预约
        ElMessage.success('取消成功')
        handleQuery()
    } catch (error) {
        // 用户取消
    }
}

// 初始化
handleQuery()
</script>

<style lang="scss" scoped>
.schedule-booking {
    // 自定义样式
}
</style>
