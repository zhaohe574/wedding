<template>
    <div class="invoice-management">
        <!-- 搜索栏 -->
        <el-card class="!border-none mb-4" shadow="never">
            <el-form :model="queryParams" inline>
                <el-form-item label="发票编号">
                    <el-input v-model="queryParams.invoice_sn" placeholder="发票编号" clearable />
                </el-form-item>
                <el-form-item label="发票类型">
                    <el-select v-model="queryParams.invoice_type" placeholder="全部" clearable>
                        <el-option v-for="item in invoiceTypeOptions" :key="item.value" :label="item.label" :value="item.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select v-model="queryParams.status" placeholder="全部" clearable>
                        <el-option label="待开票" :value="0" />
                        <el-option label="开票中" :value="1" />
                        <el-option label="已开票" :value="2" />
                        <el-option label="开票失败" :value="3" />
                        <el-option label="已作废" :value="4" />
                    </el-select>
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
                    <div class="stat-label">总申请</div>
                    <div class="stat-value">{{ statistics.total_count }}</div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">待开票</div>
                    <div class="stat-value text-warning">{{ statistics.pending_count }}</div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">已开票</div>
                    <div class="stat-value text-success">{{ statistics.issued_count }}</div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card class="stat-card" shadow="never">
                    <div class="stat-label">开票金额</div>
                    <div class="stat-value text-primary">¥{{ formatMoney(statistics.total_amount) }}</div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 列表 -->
        <el-card class="!border-none" shadow="never">
            <el-table :data="tableData" v-loading="loading" stripe>
                <el-table-column prop="invoice_sn" label="发票编号" min-width="150" />
                <el-table-column prop="invoice_type_text" label="发票类型" width="100" />
                <el-table-column prop="invoice_title" label="发票抬头" min-width="150" show-overflow-tooltip />
                <el-table-column prop="title_type_text" label="抬头类型" width="80" />
                <el-table-column label="订单编号" width="140">
                    <template #default="{ row }">{{ row.order?.order_sn || '-' }}</template>
                </el-table-column>
                <el-table-column label="申请用户" width="100">
                    <template #default="{ row }">{{ row.user?.nickname || '-' }}</template>
                </el-table-column>
                <el-table-column prop="amount" label="金额" width="100" align="right">
                    <template #default="{ row }">¥{{ formatMoney(row.amount) }}</template>
                </el-table-column>
                <el-table-column prop="status" label="状态" width="90">
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">{{ row.status_text }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="create_time" label="申请时间" width="170" />
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="showDetail(row)">详情</el-button>
                        <el-button v-if="[0, 1].includes(row.status)" type="success" link @click="showIssue(row)">开票</el-button>
                        <el-button v-if="row.status === 2" type="danger" link @click="showVoid(row)">作废</el-button>
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

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="发票详情" width="650px">
            <el-descriptions :column="2" border>
                <el-descriptions-item label="发票编号">{{ currentRow.invoice_sn }}</el-descriptions-item>
                <el-descriptions-item label="发票号码">{{ currentRow.invoice_no || '-' }}</el-descriptions-item>
                <el-descriptions-item label="发票类型">{{ currentRow.invoice_type_text }}</el-descriptions-item>
                <el-descriptions-item label="抬头类型">{{ currentRow.title_type_text }}</el-descriptions-item>
                <el-descriptions-item label="发票抬头" :span="2">{{ currentRow.invoice_title }}</el-descriptions-item>
                <el-descriptions-item label="税号" :span="2">{{ currentRow.tax_no || '-' }}</el-descriptions-item>
                <el-descriptions-item label="开户行">{{ currentRow.bank_name || '-' }}</el-descriptions-item>
                <el-descriptions-item label="银行账号">{{ currentRow.bank_account || '-' }}</el-descriptions-item>
                <el-descriptions-item label="企业地址" :span="2">{{ currentRow.company_address || '-' }}</el-descriptions-item>
                <el-descriptions-item label="企业电话">{{ currentRow.company_phone || '-' }}</el-descriptions-item>
                <el-descriptions-item label="金额">¥{{ formatMoney(currentRow.amount) }}</el-descriptions-item>
                <el-descriptions-item label="接收邮箱" :span="2">{{ currentRow.email || '-' }}</el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag :type="getStatusType(currentRow.status)">{{ currentRow.status_text }}</el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="申请时间">{{ currentRow.create_time }}</el-descriptions-item>
                <el-descriptions-item label="开票时间" v-if="currentRow.issue_time">{{ currentRow.issue_time }}</el-descriptions-item>
                <el-descriptions-item label="发票地址" v-if="currentRow.invoice_url" :span="2">
                    <el-link :href="currentRow.invoice_url" target="_blank" type="primary">下载发票</el-link>
                </el-descriptions-item>
                <el-descriptions-item label="失败原因" v-if="currentRow.fail_reason" :span="2">
                    <span class="text-danger">{{ currentRow.fail_reason }}</span>
                </el-descriptions-item>
                <el-descriptions-item label="作废原因" v-if="currentRow.void_reason" :span="2">
                    <span class="text-danger">{{ currentRow.void_reason }}</span>
                </el-descriptions-item>
            </el-descriptions>
        </el-dialog>

        <!-- 开票弹窗 -->
        <el-dialog v-model="issueVisible" title="开票" width="450px">
            <el-form :model="issueForm" label-width="100px">
                <el-form-item label="发票号码" required>
                    <el-input v-model="issueForm.invoice_no" placeholder="请输入发票号码" />
                </el-form-item>
                <el-form-item label="电子发票URL">
                    <el-input v-model="issueForm.invoice_url" placeholder="电子发票下载地址" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="issueVisible = false">取消</el-button>
                <el-button type="danger" @click="handleFail">开票失败</el-button>
                <el-button type="primary" @click="handleIssue">确认开票</el-button>
            </template>
        </el-dialog>

        <!-- 作废弹窗 -->
        <el-dialog v-model="voidVisible" title="作废发票" width="450px">
            <el-form :model="voidForm" label-width="100px">
                <el-form-item label="作废原因" required>
                    <el-input v-model="voidForm.void_reason" type="textarea" rows="3" placeholder="请输入作废原因" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="voidVisible = false">取消</el-button>
                <el-button type="danger" @click="handleVoid">确认作废</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
    getInvoiceList, getInvoiceDetail, issueInvoice, failInvoice, voidInvoice,
    getInvoiceStatistics, getInvoiceTypeOptions
} from '@/api/financial'

const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const statistics = ref<any>({})
const invoiceTypeOptions = ref<any[]>([])
const detailVisible = ref(false)
const issueVisible = ref(false)
const voidVisible = ref(false)
const currentRow = ref<any>({})

const queryParams = reactive({
    page_no: 1,
    page_size: 15,
    invoice_sn: '',
    invoice_type: '' as any,
    status: '' as any
})

const issueForm = reactive({
    id: 0,
    invoice_no: '',
    invoice_url: ''
})

const voidForm = reactive({
    id: 0,
    void_reason: ''
})

const formatMoney = (val: number | string) => {
    const num = Number(val) || 0
    return num.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const getStatusType = (status: number) => {
    const map: Record<number, string> = { 0: 'warning', 1: 'info', 2: 'success', 3: 'danger', 4: '' }
    return map[status] || ''
}

const resetPage = () => {
    queryParams.page_no = 1
    fetchList()
    fetchStats()
}

const resetSearch = () => {
    Object.assign(queryParams, { invoice_sn: '', invoice_type: '', status: '' })
    resetPage()
}

const fetchList = async () => {
    loading.value = true
    try {
        const res = await getInvoiceList(queryParams)
        tableData.value = res.lists || []
        total.value = res.count || 0
    } finally {
        loading.value = false
    }
}

const fetchStats = async () => {
    const res = await getInvoiceStatistics({})
    statistics.value = res || {}
}

const fetchOptions = async () => {
    const res = await getInvoiceTypeOptions()
    invoiceTypeOptions.value = res || []
}

const showDetail = async (row: any) => {
    try {
        const res = await getInvoiceDetail({ id: row.id })
        currentRow.value = res || row
    } catch {
        currentRow.value = row
    }
    detailVisible.value = true
}

const showIssue = (row: any) => {
    issueForm.id = row.id
    issueForm.invoice_no = ''
    issueForm.invoice_url = ''
    issueVisible.value = true
}

const handleIssue = async () => {
    if (!issueForm.invoice_no) {
        ElMessage.warning('请输入发票号码')
        return
    }
    await issueInvoice(issueForm)
    ElMessage.success('开票成功')
    issueVisible.value = false
    fetchList()
    fetchStats()
}

const handleFail = async () => {
    const { value } = await ElMessageBox.prompt('请输入开票失败原因', '开票失败', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        inputPattern: /.+/,
        inputErrorMessage: '原因不能为空'
    })
    await failInvoice({ id: issueForm.id, fail_reason: value })
    ElMessage.success('操作成功')
    issueVisible.value = false
    fetchList()
}

const showVoid = (row: any) => {
    voidForm.id = row.id
    voidForm.void_reason = ''
    voidVisible.value = true
}

const handleVoid = async () => {
    if (!voidForm.void_reason) {
        ElMessage.warning('请输入作废原因')
        return
    }
    await voidInvoice(voidForm)
    ElMessage.success('作废成功')
    voidVisible.value = false
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
.stat-label { font-size: 14px; color: #909399; margin-bottom: 8px; }
.stat-value { font-size: 24px; font-weight: bold; }
.text-primary { color: #409EFF; }
.text-success { color: #67C23A; }
.text-warning { color: #E6A23C; }
.text-danger { color: #F56C6C; }
</style>
