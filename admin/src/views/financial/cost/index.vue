<template>
    <div class="cost-management">
        <!-- 搜索栏 -->
        <el-card class="!border-none mb-4" shadow="never">
            <el-form :model="queryParams" inline>
                <el-form-item label="成本类型">
                    <el-select v-model="queryParams.cost_type" placeholder="全部" clearable>
                        <el-option v-for="item in costTypeOptions" :key="item.value" :label="item.label" :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="queryParams.status" placeholder="全部" clearable>
                        <el-option label="待确认" :value="0" />
                        <el-option label="已确认" :value="1" />
                        <el-option label="已取消" :value="2" />
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
            <el-col :span="4">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">总成本</div>
                    <div class="stat-value">¥{{ formatMoney(statistics.total) }}</div>
                </el-card>
            </el-col>
            <el-col :span="4">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">人工成本</div>
                    <div class="stat-value">¥{{ formatMoney(statistics.labor) }}</div>
                </el-card>
            </el-col>
            <el-col :span="4">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">物料成本</div>
                    <div class="stat-value">¥{{ formatMoney(statistics.material) }}</div>
                </el-card>
            </el-col>
            <el-col :span="4">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">交通成本</div>
                    <div class="stat-value">¥{{ formatMoney(statistics.transport) }}</div>
                </el-card>
            </el-col>
            <el-col :span="4">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">设备成本</div>
                    <div class="stat-value">¥{{ formatMoney(statistics.equipment) }}</div>
                </el-card>
            </el-col>
            <el-col :span="4">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">其他成本</div>
                    <div class="stat-value">¥{{ formatMoney(statistics.other) }}</div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 列表 -->
        <el-card class="!border-none" shadow="never">
            <template #header>
                <div class="flex justify-between">
                    <span>成本记录</span>
                    <div>
                        <el-button type="primary" :disabled="!selectedIds.length" @click="handleBatchConfirm">
                            批量确认 ({{ selectedIds.length }})
                        </el-button>
                        <el-button type="primary" @click="showAdd">添加成本</el-button>
                    </div>
                </div>
            </template>

            <el-table :data="tableData" v-loading="loading" @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="50" :selectable="(row: any) => row.status === 0" />
                <el-table-column prop="cost_sn" label="成本编号" min-width="150" />
                <el-table-column prop="cost_type_text" label="成本类型" width="100">
                    <template #default="{ row }">
                        <el-tag>{{ row.cost_type_text }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="cost_name" label="成本名称" min-width="150" />
                <el-table-column label="订单" width="140">
                    <template #default="{ row }">{{ row.order?.order_sn || '-' }}</template>
                </el-table-column>
                <el-table-column label="服务人员" width="100">
                    <template #default="{ row }">{{ row.staff?.name || '-' }}</template>
                </el-table-column>
                <el-table-column prop="unit_price" label="单价" width="90" align="right">
                    <template #default="{ row }">¥{{ formatMoney(row.unit_price) }}</template>
                </el-table-column>
                <el-table-column prop="quantity" label="数量" width="70" align="center" />
                <el-table-column prop="cost_amount" label="金额" width="100" align="right">
                    <template #default="{ row }">
                        <span class="text-primary font-bold">¥{{ formatMoney(row.cost_amount) }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="service_date" label="服务日期" width="110" />
                <el-table-column prop="status" label="状态" width="80">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">{{ row.status_text }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button v-if="row.status === 0" type="primary" link @click="showEdit(row)">编辑</el-button>
                        <el-button v-if="row.status === 0" type="success" link @click="handleConfirm(row)">确认</el-button>
                        <el-button v-if="row.status === 0" type="danger" link @click="handleDelete(row)">删除</el-button>
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

        <!-- 编辑弹窗 -->
        <el-dialog v-model="dialogVisible" :title="formData.id ? '编辑成本' : '添加成本'" width="500px">
            <el-form :model="formData" label-width="100px">
                <el-form-item label="成本类型" required>
                    <el-select v-model="formData.cost_type" placeholder="请选择">
                        <el-option v-for="item in costTypeOptions" :key="item.value" :label="item.label" :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="成本名称" required>
                    <el-input v-model="formData.cost_name" placeholder="请输入成本名称" />
                </el-form-item>
                <el-form-item label="单价">
                    <el-input-number v-model="formData.unit_price" :min="0" :precision="2" />
                </el-form-item>
                <el-form-item label="数量">
                    <el-input-number v-model="formData.quantity" :min="0.01" :precision="2" />
                </el-form-item>
                <el-form-item label="成本金额" required>
                    <el-input-number v-model="formData.cost_amount" :min="0" :precision="2" />
                </el-form-item>
                <el-form-item label="服务日期">
                    <el-date-picker v-model="formData.service_date" type="date" value-format="YYYY-MM-DD" />
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="formData.remark" type="textarea" rows="2" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSave">保存</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
    getCostList, addCost, editCost, deleteCost, confirmCost, batchConfirmCost,
    getCostStatistics, getCostTypeOptions
} from '@/api/financial'

const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const dateRange = ref<string[]>([])
const selectedIds = ref<number[]>([])
const statistics = ref<any>({})
const costTypeOptions = ref<any[]>([])
const dialogVisible = ref(false)

const queryParams = reactive({
    page_no: 1,
    page_size: 15,
    cost_type: '' as any,
    status: '' as any,
    start_date: '',
    end_date: ''
})

const formData = reactive<any>({
    id: 0,
    cost_type: 1,
    cost_name: '',
    unit_price: 0,
    quantity: 1,
    cost_amount: 0,
    service_date: '',
    remark: ''
})

const formatMoney = (val: number | string) => {
    const num = Number(val) || 0
    return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const getStatusType = (status: number) => {
    const map: Record<number, string> = { 0: 'warning', 1: 'success', 2: 'info' }
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
    Object.assign(queryParams, { cost_type: '', status: '', start_date: '', end_date: '' })
    dateRange.value = []
    resetPage()
}

const fetchList = async () => {
    loading.value = true
    try {
        const res = await getCostList(queryParams)
        tableData.value = res.lists || []
        total.value = res.count || 0
    } finally {
        loading.value = false
    }
}

const fetchStats = async () => {
    const res = await getCostStatistics({
        start_date: queryParams.start_date,
        end_date: queryParams.end_date
    })
    statistics.value = res || {}
}

const fetchOptions = async () => {
    const res = await getCostTypeOptions()
    costTypeOptions.value = res || []
}

const showAdd = () => {
    Object.assign(formData, { id: 0, cost_type: 1, cost_name: '', unit_price: 0, quantity: 1, cost_amount: 0, service_date: '', remark: '' })
    dialogVisible.value = true
}

const showEdit = (row: any) => {
    Object.assign(formData, row)
    dialogVisible.value = true
}

const handleSave = async () => {
    if (!formData.cost_name || !formData.cost_amount) {
        ElMessage.warning('请填写完整信息')
        return
    }
    if (formData.id) {
        await editCost(formData)
    } else {
        await addCost(formData)
    }
    ElMessage.success('保存成功')
    dialogVisible.value = false
    fetchList()
    fetchStats()
}

const handleConfirm = async (row: any) => {
    await ElMessageBox.confirm('确定确认该成本记录？', '确认')
    await confirmCost({ id: row.id })
    ElMessage.success('确认成功')
    fetchList()
    fetchStats()
}

const handleBatchConfirm = async () => {
    await ElMessageBox.confirm(`确定批量确认选中的 ${selectedIds.value.length} 条记录？`, '批量确认')
    const res = await batchConfirmCost({ ids: selectedIds.value })
    ElMessage.success(`成功 ${res.success_count} 条，失败 ${res.fail_count} 条`)
    selectedIds.value = []
    fetchList()
    fetchStats()
}

const handleDelete = async (row: any) => {
    await ElMessageBox.confirm('确定删除该成本记录？', '删除确认')
    await deleteCost({ id: row.id })
    ElMessage.success('删除成功')
    fetchList()
    fetchStats()
}

onMounted(() => {
    fetchOptions()
    fetchList()
    fetchStats()
})
</script>

<style scoped>
.stat-card { text-align: center; }
.stat-label { font-size: 12px; color: #909399; margin-bottom: 4px; }
.stat-value { font-size: 18px; font-weight: bold; }
.text-primary { color: #409EFF; }
</style>
