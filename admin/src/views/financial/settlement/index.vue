<template>
    <div class="settlement-management">
        <!-- 标签页 -->
        <el-tabs v-model="activeTab" class="mb-4">
            <el-tab-pane label="结算记录" name="records" />
            <el-tab-pane label="结算批次" name="batch" />
            <el-tab-pane label="结算配置" name="config" />
        </el-tabs>

        <!-- 结算记录 -->
        <template v-if="activeTab === 'records'">
            <el-card class="!border-none mb-4" shadow="never">
                <el-form :model="queryParams" inline>
                    <el-form-item label="服务人员">
                        <el-input v-model="queryParams.staff_name" placeholder="人员姓名" clearable />
                    </el-form-item>
                    <el-form-item label="订单编号">
                        <el-input v-model="queryParams.order_sn" placeholder="订单编号" clearable />
                    </el-form-item>
                    <el-form-item label="状态">
                        <el-select v-model="queryParams.status" placeholder="全部" clearable>
                            <el-option label="待结算" :value="0" />
                            <el-option label="已结算" :value="1" />
                            <el-option label="已取消" :value="2" />
                            <el-option label="结算失败" :value="3" />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="服务日期">
                        <el-date-picker
                            v-model="dateRange"
                            type="daterange"
                            range-separator="至"
                            start-placeholder="开始"
                            end-placeholder="结束"
                            value-format="YYYY-MM-DD"
                            @change="handleDateChange"
                        />
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button @click="resetSearch">重置</el-button>
                    </el-form-item>
                </el-form>
            </el-card>

            <!-- 统计卡片 -->
            <el-row :gutter="16" class="mb-4">
                <el-col :span="6">
                    <el-card class="stat-card" shadow="never">
                        <div class="stat-label">待结算笔数</div>
                        <div class="stat-value">{{ recordStats.pending_count }}</div>
                    </el-card>
                </el-col>
                <el-col :span="6">
                    <el-card class="stat-card" shadow="never">
                        <div class="stat-label">待结算金额</div>
                        <div class="stat-value text-warning">¥{{ formatMoney(recordStats.pending_amount) }}</div>
                    </el-card>
                </el-col>
                <el-col :span="6">
                    <el-card class="stat-card" shadow="never">
                        <div class="stat-label">已结算笔数</div>
                        <div class="stat-value">{{ recordStats.settled_count }}</div>
                    </el-card>
                </el-col>
                <el-col :span="6">
                    <el-card class="stat-card" shadow="never">
                        <div class="stat-label">已结算金额</div>
                        <div class="stat-value text-success">¥{{ formatMoney(recordStats.settled_amount) }}</div>
                    </el-card>
                </el-col>
            </el-row>

            <el-card class="!border-none" shadow="never">
                <template #header>
                    <div class="flex justify-between">
                        <span>结算记录</span>
                        <el-button type="primary" :disabled="!selectedIds.length" @click="handleBatchSettle">
                            批量结算 ({{ selectedIds.length }})
                        </el-button>
                    </div>
                </template>

                <el-table :data="tableData" v-loading="loading" @selection-change="handleSelectionChange">
                    <el-table-column type="selection" width="50" :selectable="(row: any) => row.status === 0" />
                    <el-table-column prop="settlement_sn" label="结算编号" min-width="150" />
                    <el-table-column label="服务人员" width="120">
                        <template #default="{ row }">
                            <div class="flex items-center" v-if="row.staff">
                                <el-avatar :src="row.staff.avatar" :size="28" class="mr-2" />
                                {{ row.staff.name }}
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="订单编号" width="140">
                        <template #default="{ row }">{{ row.order?.order_sn || '-' }}</template>
                    </el-table-column>
                    <el-table-column prop="service_date" label="服务日期" width="110" />
                    <el-table-column prop="order_amount" label="订单金额" width="100" align="right">
                        <template #default="{ row }">¥{{ formatMoney(row.order_amount) }}</template>
                    </el-table-column>
                    <el-table-column prop="settlement_rate" label="结算比例" width="90" align="center">
                        <template #default="{ row }">{{ row.settlement_rate }}%</template>
                    </el-table-column>
                    <el-table-column prop="actual_amount" label="结算金额" width="100" align="right">
                        <template #default="{ row }">
                            <span class="text-primary font-bold">¥{{ formatMoney(row.actual_amount) }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="status" label="状态" width="90">
                        <template #default="{ row }">
                            <el-tag :type="getStatusType(row.status)">{{ row.status_text }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="操作" width="120" fixed="right">
                        <template #default="{ row }">
                            <el-button type="primary" link @click="showDetail(row)">详情</el-button>
                            <el-button v-if="row.status === 0" type="success" link @click="handleSettle(row)">结算</el-button>
                        </template>
                    </el-table-column>
                </el-table>

                <div class="flex justify-end mt-4">
                    <el-pagination
                        v-model:current-page="queryParams.page_no"
                        v-model:page-size="queryParams.page_size"
                        :total="total"
                        :page-sizes="[15, 30, 50]"
                        layout="total, sizes, prev, pager, next"
                        @size-change="resetPage"
                        @current-change="fetchList"
                    />
                </div>
            </el-card>
        </template>

        <!-- 结算批次 -->
        <template v-if="activeTab === 'batch'">
            <el-card class="!border-none" shadow="never">
                <template #header>
                    <div class="flex justify-between">
                        <span>结算批次</span>
                        <el-button type="primary" @click="showCreateBatch">创建批次</el-button>
                    </div>
                </template>

                <el-table :data="batchList" v-loading="batchLoading">
                    <el-table-column prop="batch_sn" label="批次编号" width="160" />
                    <el-table-column prop="batch_name" label="批次名称" min-width="150" />
                    <el-table-column label="结算周期" width="200">
                        <template #default="{ row }">{{ row.settle_start_date }} ~ {{ row.settle_end_date }}</template>
                    </el-table-column>
                    <el-table-column prop="total_count" label="总笔数" width="80" align="center" />
                    <el-table-column prop="total_amount" label="总金额" width="120" align="right">
                        <template #default="{ row }">¥{{ formatMoney(row.total_amount) }}</template>
                    </el-table-column>
                    <el-table-column prop="success_count" label="成功/失败" width="100" align="center">
                        <template #default="{ row }">
                            <span class="text-success">{{ row.success_count }}</span> / 
                            <span class="text-danger">{{ row.fail_count }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="status" label="状态" width="90">
                        <template #default="{ row }">
                            <el-tag :type="getBatchStatusType(row.status)">{{ row.status_text }}</el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column prop="create_time" label="创建时间" width="170" />
                    <el-table-column label="操作" width="180" fixed="right">
                        <template #default="{ row }">
                            <el-button v-if="row.status === 0" type="success" link @click="handleAuditBatch(row, 1)">通过</el-button>
                            <el-button v-if="row.status === 0" type="danger" link @click="handleAuditBatch(row, 2)">拒绝</el-button>
                            <el-button v-if="row.status === 1" type="primary" link @click="handleExecuteBatch(row)">执行</el-button>
                            <el-button v-if="[0, 1].includes(row.status)" type="warning" link @click="handleCancelBatch(row)">取消</el-button>
                        </template>
                    </el-table-column>
                </el-table>
            </el-card>
        </template>

        <!-- 结算配置 -->
        <template v-if="activeTab === 'config'">
            <el-card class="!border-none" shadow="never">
                <template #header>
                    <div class="flex justify-between">
                        <span>结算配置</span>
                        <el-button type="primary" @click="showAddConfig">添加配置</el-button>
                    </div>
                </template>

                <el-table :data="configList" v-loading="configLoading">
                    <el-table-column label="适用人员" width="120">
                        <template #default="{ row }">
                            <span v-if="row.staff_id === 0">全部人员</span>
                            <span v-else>{{ row.staff?.name || '-' }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="适用分类" width="120">
                        <template #default="{ row }">
                            <span v-if="row.category_id === 0">全部分类</span>
                            <span v-else>{{ row.category?.name || '-' }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="settlement_rate" label="结算比例" width="100" align="center">
                        <template #default="{ row }">{{ row.settlement_rate }}%</template>
                    </el-table-column>
                    <el-table-column prop="min_amount" label="最低金额" width="100" align="right">
                        <template #default="{ row }">¥{{ formatMoney(row.min_amount) }}</template>
                    </el-table-column>
                    <el-table-column prop="cycle_text" label="结算周期" width="90" />
                    <el-table-column prop="settle_delay_days" label="延迟天数" width="90" align="center">
                        <template #default="{ row }">{{ row.settle_delay_days }} 天</template>
                    </el-table-column>
                    <el-table-column prop="is_default" label="默认配置" width="90" align="center">
                        <template #default="{ row }">
                            <el-tag v-if="row.is_default" type="success">是</el-tag>
                            <span v-else class="text-muted">否</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="status_text" label="状态" width="80" />
                    <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
                    <el-table-column label="操作" width="120" fixed="right">
                        <template #default="{ row }">
                            <el-button type="primary" link @click="showEditConfig(row)">编辑</el-button>
                            <el-button v-if="!row.is_default" type="danger" link @click="handleDeleteConfig(row)">删除</el-button>
                        </template>
                    </el-table-column>
                </el-table>
            </el-card>
        </template>

        <!-- 创建批次弹窗 -->
        <el-dialog v-model="batchDialogVisible" title="创建结算批次" width="500px">
            <el-form :model="batchForm" label-width="100px">
                <el-form-item label="批次名称">
                    <el-input v-model="batchForm.batch_name" placeholder="可选，默认自动生成" />
                </el-form-item>
                <el-form-item label="结算周期" required>
                    <el-date-picker
                        v-model="batchDateRange"
                        type="daterange"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        value-format="YYYY-MM-DD"
                    />
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="batchForm.remark" type="textarea" rows="3" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="batchDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleCreateBatch">确定</el-button>
            </template>
        </el-dialog>

        <!-- 配置编辑弹窗 -->
        <el-dialog v-model="configDialogVisible" :title="configForm.id ? '编辑配置' : '添加配置'" width="500px">
            <el-form :model="configForm" label-width="100px">
                <el-form-item label="结算比例" required>
                    <el-input-number v-model="configForm.settlement_rate" :min="0" :max="100" :precision="2" />
                    <span class="ml-2">%</span>
                </el-form-item>
                <el-form-item label="最低金额">
                    <el-input-number v-model="configForm.min_amount" :min="0" :precision="2" />
                </el-form-item>
                <el-form-item label="结算周期">
                    <el-select v-model="configForm.settle_cycle">
                        <el-option label="月结" :value="1" />
                        <el-option label="周结" :value="2" />
                        <el-option label="单笔结" :value="3" />
                    </el-select>
                </el-form-item>
                <el-form-item label="延迟天数">
                    <el-input-number v-model="configForm.settle_delay_days" :min="0" />
                    <span class="ml-2">天</span>
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="configForm.remark" type="textarea" rows="2" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="configDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSaveConfig">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
    getSettlementList, getSettlementDetail, doSettle, batchSettle, getSettlementStatistics,
    getBatchList, createBatch, auditBatch, executeBatch, cancelBatch,
    getSettlementConfigList, addSettlementConfig, editSettlementConfig, deleteSettlementConfig
} from '@/api/financial'

const activeTab = ref('records')
const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const dateRange = ref<string[]>([])
const selectedIds = ref<number[]>([])
const recordStats = ref<any>({})

const batchLoading = ref(false)
const batchList = ref<any[]>([])
const batchDialogVisible = ref(false)
const batchDateRange = ref<string[]>([])
const batchForm = reactive({ batch_name: '', remark: '' })

const configLoading = ref(false)
const configList = ref<any[]>([])
const configDialogVisible = ref(false)
const configForm = reactive<any>({
    id: 0,
    settlement_rate: 70,
    min_amount: 0,
    settle_cycle: 1,
    settle_delay_days: 7,
    remark: ''
})

const queryParams = reactive({
    page_no: 1,
    page_size: 15,
    staff_name: '',
    order_sn: '',
    status: '' as any,
    start_date: '',
    end_date: ''
})

const formatMoney = (val: number | string) => {
    const num = Number(val) || 0
    return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const getStatusType = (status: number) => {
    const map: Record<number, string> = { 0: 'warning', 1: 'success', 2: 'info', 3: 'danger' }
    return map[status] || ''
}

const getBatchStatusType = (status: number) => {
    const map: Record<number, string> = { 0: 'warning', 1: 'primary', 2: 'info', 3: 'success', 4: 'danger' }
    return map[status] || ''
}

const handleDateChange = (val: string[] | null) => {
    queryParams.start_date = val?.[0] || ''
    queryParams.end_date = val?.[1] || ''
}

const handleSelectionChange = (rows: any[]) => {
    selectedIds.value = rows.map(r => r.id)
}

const resetPage = () => {
    queryParams.page_no = 1
    fetchList()
    fetchStats()
}

const resetSearch = () => {
    Object.assign(queryParams, { staff_name: '', order_sn: '', status: '', start_date: '', end_date: '' })
    dateRange.value = []
    resetPage()
}

const fetchList = async () => {
    loading.value = true
    try {
        const res = await getSettlementList(queryParams)
        tableData.value = res.lists || []
        total.value = res.count || 0
    } finally {
        loading.value = false
    }
}

const fetchStats = async () => {
    const res = await getSettlementStatistics({
        start_date: queryParams.start_date,
        end_date: queryParams.end_date
    })
    recordStats.value = res || {}
}

const showDetail = async (row: any) => {
    const res = await getSettlementDetail({ id: row.id })
    ElMessageBox.alert(JSON.stringify(res, null, 2), '结算详情', { dangerouslyUseHTMLString: false })
}

const handleSettle = async (row: any) => {
    await ElMessageBox.confirm(`确定结算 ¥${formatMoney(row.actual_amount)} 给 ${row.staff?.name}？`, '确认结算')
    await doSettle({ id: row.id })
    ElMessage.success('结算成功')
    fetchList()
    fetchStats()
}

const handleBatchSettle = async () => {
    await ElMessageBox.confirm(`确定批量结算选中的 ${selectedIds.value.length} 条记录？`, '批量结算')
    const res = await batchSettle({ ids: selectedIds.value })
    ElMessage.success(`成功 ${res.success_count} 条，失败 ${res.fail_count} 条`)
    selectedIds.value = []
    fetchList()
    fetchStats()
}

// 批次相关
const fetchBatchList = async () => {
    batchLoading.value = true
    try {
        const res = await getBatchList({})
        batchList.value = res.lists || []
    } finally {
        batchLoading.value = false
    }
}

const showCreateBatch = () => {
    batchForm.batch_name = ''
    batchForm.remark = ''
    batchDateRange.value = []
    batchDialogVisible.value = true
}

const handleCreateBatch = async () => {
    if (!batchDateRange.value?.length) {
        ElMessage.warning('请选择结算周期')
        return
    }
    await createBatch({
        batch_name: batchForm.batch_name,
        settle_start_date: batchDateRange.value[0],
        settle_end_date: batchDateRange.value[1],
        remark: batchForm.remark
    })
    ElMessage.success('创建成功')
    batchDialogVisible.value = false
    fetchBatchList()
}

const handleAuditBatch = async (row: any, status: number) => {
    const action = status === 1 ? '通过' : '拒绝'
    await ElMessageBox.confirm(`确定${action}该批次？`, '审核确认')
    await auditBatch({ batch_id: row.id, status })
    ElMessage.success('操作成功')
    fetchBatchList()
}

const handleExecuteBatch = async (row: any) => {
    await ElMessageBox.confirm('确定执行该批次结算？', '执行确认')
    const res = await executeBatch({ id: row.id })
    ElMessage.success(`执行完成：成功 ${res.success_count} 条，失败 ${res.fail_count} 条`)
    fetchBatchList()
}

const handleCancelBatch = async (row: any) => {
    await ElMessageBox.confirm('确定取消该批次？', '取消确认')
    await cancelBatch({ id: row.id })
    ElMessage.success('取消成功')
    fetchBatchList()
}

// 配置相关
const fetchConfigList = async () => {
    configLoading.value = true
    try {
        const res = await getSettlementConfigList()
        configList.value = res || []
    } finally {
        configLoading.value = false
    }
}

const showAddConfig = () => {
    Object.assign(configForm, { id: 0, settlement_rate: 70, min_amount: 0, settle_cycle: 1, settle_delay_days: 7, remark: '' })
    configDialogVisible.value = true
}

const showEditConfig = (row: any) => {
    Object.assign(configForm, row)
    configDialogVisible.value = true
}

const handleSaveConfig = async () => {
    if (configForm.id) {
        await editSettlementConfig(configForm)
    } else {
        await addSettlementConfig(configForm)
    }
    ElMessage.success('保存成功')
    configDialogVisible.value = false
    fetchConfigList()
}

const handleDeleteConfig = async (row: any) => {
    await ElMessageBox.confirm('确定删除该配置？', '删除确认')
    await deleteSettlementConfig({ id: row.id })
    ElMessage.success('删除成功')
    fetchConfigList()
}

watch(activeTab, (val) => {
    if (val === 'records') fetchList()
    else if (val === 'batch') fetchBatchList()
    else if (val === 'config') fetchConfigList()
})

onMounted(() => {
    fetchList()
    fetchStats()
})
</script>

<style scoped>
.stat-card { text-align: center; }
.stat-label { font-size: 14px; color: #909399; margin-bottom: 8px; }
.stat-value { font-size: 24px; font-weight: bold; }
.text-primary { color: #409EFF; }
.text-success { color: #67C23A; }
.text-warning { color: #E6A23C; }
.text-danger { color: #F56C6C; }
.text-muted { color: #909399; }
</style>
