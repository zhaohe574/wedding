<template>
    <div class="financial-flow">
        <!-- 搜索栏 -->
        <el-card class="!border-none mb-4" shadow="never">
            <el-form :model="queryParams" inline>
                <el-form-item label="流水编号">
                    <el-input v-model="queryParams.flow_sn" placeholder="请输入流水编号" clearable />
                </el-form-item>
                <el-form-item label="流水类型">
                    <el-select v-model="queryParams.flow_type" placeholder="全部" clearable>
                        <el-option v-for="item in flowTypeOptions" :key="item.value" :label="item.label" :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="业务类型">
                    <el-select v-model="queryParams.biz_type" placeholder="全部" clearable>
                        <el-option v-for="item in bizTypeOptions" :key="item.value" :label="item.label" :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="方向">
                    <el-select v-model="queryParams.direction" placeholder="全部" clearable>
                        <el-option label="收入" :value="1" />
                        <el-option label="支出" :value="-1" />
                    </el-select>
                </el-form-item>
                <el-form-item label="时间范围">
                    <el-date-picker
                        v-model="dateRange"
                        type="daterange"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
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
                    <div class="stat-label">流水笔数</div>
                    <div class="stat-value">{{ statistics.total_count }}</div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">总收入</div>
                    <div class="stat-value text-success">¥{{ formatMoney(statistics.total_income) }}</div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">总支出</div>
                    <div class="stat-value text-danger">¥{{ formatMoney(statistics.total_expense) }}</div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">净额</div>
                    <div class="stat-value" :class="statistics.net_amount >= 0 ? 'text-success' : 'text-danger'">
                        ¥{{ formatMoney(statistics.net_amount) }}
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 列表 -->
        <el-card class="!border-none" shadow="never">
            <el-table :data="tableData" v-loading="loading" stripe>
                <el-table-column prop="flow_sn" label="流水编号" min-width="160" />
                <el-table-column prop="flow_type_text" label="流水类型" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getFlowTypeTag(row.flow_type)">{{ row.flow_type_text }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="biz_type_text" label="业务类型" width="100" />
                <el-table-column prop="biz_sn" label="业务编号" min-width="140" />
                <el-table-column prop="amount" label="金额" width="120" align="right">
                    <template #default="{ row }">
                        <span :class="row.direction == 1 ? 'text-success' : 'text-danger'">
                            {{ row.direction == 1 ? '+' : '-' }}¥{{ formatMoney(row.amount) }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column prop="pay_way_text" label="支付方式" width="100" />
                <el-table-column label="关联用户" width="120">
                    <template #default="{ row }">
                        <span v-if="row.user">{{ row.user.nickname }}</span>
                        <span v-else class="text-muted">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="关联人员" width="100">
                    <template #default="{ row }">
                        <span v-if="row.staff">{{ row.staff.name }}</span>
                        <span v-else class="text-muted">-</span>
                    </template>
                </el-table-column>
                <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
                <el-table-column prop="create_time" label="创建时间" width="170" />
                <el-table-column label="操作" width="80" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="showDetail(row)">详情</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="flex justify-end mt-4">
                <el-pagination
                    v-model:current-page="queryParams.page_no"
                    v-model:page-size="queryParams.page_size"
                    :total="total"
                    :page-sizes="[15, 30, 50, 100]"
                    layout="total, sizes, prev, pager, next, jumper"
                    @size-change="resetPage"
                    @current-change="fetchList"
                />
            </div>
        </el-card>

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="流水详情" width="600px">
            <el-descriptions :column="2" border>
                <el-descriptions-item label="流水编号">{{ currentRow.flow_sn }}</el-descriptions-item>
                <el-descriptions-item label="流水类型">{{ currentRow.flow_type_text }}</el-descriptions-item>
                <el-descriptions-item label="业务类型">{{ currentRow.biz_type_text }}</el-descriptions-item>
                <el-descriptions-item label="业务编号">{{ currentRow.biz_sn || '-' }}</el-descriptions-item>
                <el-descriptions-item label="金额">
                    <span :class="currentRow.direction == 1 ? 'text-success' : 'text-danger'">
                        {{ currentRow.direction == 1 ? '+' : '-' }}¥{{ formatMoney(currentRow.amount) }}
                    </span>
                </el-descriptions-item>
                <el-descriptions-item label="支付方式">{{ currentRow.pay_way_text }}</el-descriptions-item>
                <el-descriptions-item label="变动前余额">¥{{ formatMoney(currentRow.balance_before) }}</el-descriptions-item>
                <el-descriptions-item label="变动后余额">¥{{ formatMoney(currentRow.balance_after) }}</el-descriptions-item>
                <el-descriptions-item label="第三方交易号" :span="2">{{ currentRow.transaction_id || '-' }}</el-descriptions-item>
                <el-descriptions-item label="备注" :span="2">{{ currentRow.remark || '-' }}</el-descriptions-item>
                <el-descriptions-item label="创建时间" :span="2">{{ currentRow.create_time }}</el-descriptions-item>
            </el-descriptions>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { getFlowList, getFlowDetail, getFlowStatistics, getFlowTypeOptions, getBizTypeOptions } from '@/api/financial'

const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const dateRange = ref<string[]>([])
const detailVisible = ref(false)
const currentRow = ref<any>({})
const statistics = ref<any>({})
const flowTypeOptions = ref<any[]>([])
const bizTypeOptions = ref<any[]>([])

const queryParams = reactive({
    page_no: 1,
    page_size: 15,
    flow_sn: '',
    flow_type: '' as any,
    biz_type: '' as any,
    direction: '' as any,
    start_date: '',
    end_date: ''
})

const formatMoney = (val: number | string) => {
    const num = Number(val) || 0
    return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const getFlowTypeTag = (type: number) => {
    const map: Record<number, string> = {
        1: 'success',
        2: 'danger',
        3: 'warning',
        4: 'info',
        5: ''
    }
    return map[type] || ''
}

const handleDateChange = (val: string[] | null) => {
    if (val) {
        queryParams.start_date = val[0]
        queryParams.end_date = val[1]
    } else {
        queryParams.start_date = ''
        queryParams.end_date = ''
    }
}

const resetPage = () => {
    queryParams.page_no = 1
    fetchList()
    fetchStatistics()
}

const resetSearch = () => {
    queryParams.flow_sn = ''
    queryParams.flow_type = ''
    queryParams.biz_type = ''
    queryParams.direction = ''
    queryParams.start_date = ''
    queryParams.end_date = ''
    dateRange.value = []
    resetPage()
}

const fetchList = async () => {
    loading.value = true
    try {
        const res = await getFlowList(queryParams)
        tableData.value = res.lists || []
        total.value = res.count || 0
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const fetchStatistics = async () => {
    try {
        const params: any = {}
        if (queryParams.start_date && queryParams.end_date) {
            params.start_date = queryParams.start_date
            params.end_date = queryParams.end_date
        }
        const res = await getFlowStatistics(params)
        statistics.value = res || {}
    } catch (e) {
        console.error(e)
    }
}

const fetchOptions = async () => {
    try {
        const [flowRes, bizRes] = await Promise.all([
            getFlowTypeOptions(),
            getBizTypeOptions()
        ])
        flowTypeOptions.value = flowRes || []
        bizTypeOptions.value = bizRes || []
    } catch (e) {
        console.error(e)
    }
}

const showDetail = async (row: any) => {
    try {
        const res = await getFlowDetail({ id: row.id })
        currentRow.value = res || row
        detailVisible.value = true
    } catch (e) {
        currentRow.value = row
        detailVisible.value = true
    }
}

onMounted(() => {
    fetchOptions()
    fetchList()
    fetchStatistics()
})
</script>

<style scoped>
.stat-card {
    text-align: center;
}
.stat-label {
    font-size: 14px;
    color: #909399;
    margin-bottom: 8px;
}
.stat-value {
    font-size: 24px;
    font-weight: bold;
}
.text-success { color: #67C23A; }
.text-danger { color: #F56C6C; }
.text-muted { color: #909399; }
</style>
