<template>
    <div class="advisor-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[150px]" label="顾问姓名">
                    <el-input
                        v-model="queryParams.advisor_name"
                        placeholder="输入姓名"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="手机号">
                    <el-input
                        v-model="queryParams.mobile"
                        placeholder="输入手机号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[120px]" label="状态">
                    <el-select v-model="queryParams.status" placeholder="选择状态" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="正常" :value="1" />
                        <el-option label="休假" :value="2" />
                        <el-option label="离职" :value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex justify-between mb-4">
                <el-button type="primary" @click="handleAdd">
                    <el-icon class="mr-1"><Plus /></el-icon>添加顾问
                </el-button>
            </div>

            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="顾问信息" min-width="180">
                    <template #default="{ row }">
                        <div class="flex items-center">
                            <el-avatar :src="row.avatar" :size="40">
                                {{ row.advisor_name?.charAt(0) }}
                            </el-avatar>
                            <div class="ml-3">
                                <div class="font-medium">{{ row.advisor_name }}</div>
                                <div class="text-gray-400 text-xs">{{ row.mobile }}</div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="企业微信" prop="wechat" width="120" />
                <el-table-column label="客户数" width="120">
                    <template #default="{ row }">
                        <div>
                            <span :class="row.current_customer_count >= row.max_customer_count ? 'text-red-500' : ''">
                                {{ row.current_customer_count }}
                            </span>
                            / {{ row.max_customer_count }}
                        </div>
                        <el-progress 
                            :percentage="Math.min(100, (row.current_customer_count / row.max_customer_count) * 100)" 
                            :stroke-width="4"
                            :show-text="false"
                            :status="row.current_customer_count >= row.max_customer_count ? 'exception' : ''"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="成交统计" width="140">
                    <template #default="{ row }">
                        <div class="text-xs">
                            <div>订单: {{ row.total_order_count }}</div>
                            <div>金额: ¥{{ row.total_order_amount }}</div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="转化率" width="100">
                    <template #default="{ row }">
                        <span class="text-green-500 font-bold">{{ row.conversion_rate }}%</span>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusTagType(row.status)">
                            {{ row.status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="200" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
                        <el-button 
                            v-if="row.status === 1" 
                            type="warning" 
                            link 
                            @click="handleUpdateStatus(row, 2)"
                        >设为休假</el-button>
                        <el-button 
                            v-if="row.status === 2" 
                            type="success" 
                            link 
                            @click="handleUpdateStatus(row, 1)"
                        >恢复正常</el-button>
                        <el-button 
                            v-if="row.current_customer_count > 0" 
                            type="warning" 
                            link 
                            @click="handleTransfer(row)"
                        >转移客户</el-button>
                        <el-button 
                            v-if="row.current_customer_count === 0" 
                            type="danger" 
                            link 
                            @click="handleDelete(row)"
                        >删除</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 添加/编辑弹窗 -->
        <el-dialog v-model="editVisible" :title="editForm.id ? '编辑顾问' : '添加顾问'" width="600px">
            <el-form :model="editForm" label-width="100px" :rules="editRules" ref="editFormRef">
                <el-form-item label="顾问姓名" prop="advisor_name">
                    <el-input v-model="editForm.advisor_name" placeholder="输入姓名" />
                </el-form-item>
                <el-form-item label="手机号" prop="mobile">
                    <el-input v-model="editForm.mobile" placeholder="输入手机号" />
                </el-form-item>
                <el-form-item label="企业微信">
                    <el-input v-model="editForm.wechat" placeholder="输入企业微信号" />
                </el-form-item>
                <el-form-item label="邮箱">
                    <el-input v-model="editForm.email" placeholder="输入邮箱" />
                </el-form-item>
                <el-form-item label="最大客户数">
                    <el-input-number v-model="editForm.max_customer_count" :min="1" :max="1000" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-radio-group v-model="editForm.status">
                        <el-radio :label="1">正常</el-radio>
                        <el-radio :label="2">休假</el-radio>
                        <el-radio :label="0">离职</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="排序">
                    <el-input-number v-model="editForm.sort" :min="0" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="editVisible = false">取消</el-button>
                <el-button type="primary" @click="submitEdit">确定</el-button>
            </template>
        </el-dialog>

        <!-- 转移客户弹窗 -->
        <el-dialog v-model="transferVisible" title="转移客户" width="500px">
            <el-alert type="warning" :closable="false" class="mb-4">
                将 {{ currentAdvisor?.advisor_name }} 的所有客户转移给其他顾问
            </el-alert>
            <el-form :model="transferForm" label-width="100px">
                <el-form-item label="目标顾问">
                    <el-select v-model="transferForm.to_advisor_id" placeholder="选择接收顾问" style="width: 100%">
                        <el-option 
                            v-for="advisor in availableAdvisors" 
                            :key="advisor.id" 
                            :label="`${advisor.advisor_name} (${advisor.current_customer_count}/${advisor.max_customer_count})`" 
                            :value="advisor.id"
                            :disabled="advisor.id === currentAdvisor?.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="转移原因">
                    <el-input v-model="transferForm.reason" type="textarea" :rows="2" placeholder="输入转移原因" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="transferVisible = false">取消</el-button>
                <el-button type="primary" @click="submitTransfer">确定转移</el-button>
            </template>
        </el-dialog>

        <!-- 详情弹窗 -->
        <el-dialog v-model="detailVisible" title="顾问详情" width="600px">
            <div v-if="currentAdvisor">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="顾问姓名">{{ currentAdvisor.advisor_name }}</el-descriptions-item>
                    <el-descriptions-item label="手机号">{{ currentAdvisor.mobile }}</el-descriptions-item>
                    <el-descriptions-item label="企业微信">{{ currentAdvisor.wechat || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="邮箱">{{ currentAdvisor.email || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="状态">
                        <el-tag :type="getStatusTagType(currentAdvisor.status)">
                            {{ currentAdvisor.status_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="客户数">
                        {{ currentAdvisor.current_customer_count }} / {{ currentAdvisor.max_customer_count }}
                    </el-descriptions-item>
                    <el-descriptions-item label="成交订单">{{ currentAdvisor.total_order_count }}</el-descriptions-item>
                    <el-descriptions-item label="成交金额">¥{{ currentAdvisor.total_order_amount }}</el-descriptions-item>
                    <el-descriptions-item label="转化率">{{ currentAdvisor.conversion_rate }}%</el-descriptions-item>
                    <el-descriptions-item label="创建时间">{{ currentAdvisor.create_time }}</el-descriptions-item>
                </el-descriptions>
            </div>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="advisorLists">
import { Plus } from '@element-plus/icons-vue'
import { 
    salesAdvisorLists, 
    salesAdvisorDetail,
    salesAdvisorAdd, 
    salesAdvisorEdit, 
    salesAdvisorDelete,
    salesAdvisorUpdateStatus,
    salesAdvisorTransferCustomers,
    salesAdvisorSimpleList
} from '@/api/crm'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    advisor_name: '',
    mobile: '',
    status: ''
})

const editVisible = ref(false)
const editFormRef = ref()
const editForm = reactive({
    id: 0,
    advisor_name: '',
    mobile: '',
    wechat: '',
    email: '',
    max_customer_count: 100,
    status: 1,
    sort: 0
})

const editRules = {
    advisor_name: [{ required: true, message: '请输入顾问姓名', trigger: 'blur' }]
}

const transferVisible = ref(false)
const transferForm = reactive({
    from_advisor_id: 0,
    to_advisor_id: '',
    reason: ''
})

const detailVisible = ref(false)
const currentAdvisor = ref<any>(null)
const availableAdvisors = ref<any[]>([])

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: salesAdvisorLists,
    params: queryParams
})

const getStatusTagType = (status: number) => {
    const types: Record<number, string> = {
        0: 'danger',
        1: 'success',
        2: 'warning'
    }
    return types[status] || 'info'
}

const getAvailableAdvisors = async () => {
    availableAdvisors.value = await salesAdvisorSimpleList()
}

const handleAdd = () => {
    Object.assign(editForm, {
        id: 0,
        advisor_name: '',
        mobile: '',
        wechat: '',
        email: '',
        max_customer_count: 100,
        status: 1,
        sort: 0
    })
    editVisible.value = true
}

const handleEdit = (row: any) => {
    Object.assign(editForm, {
        id: row.id,
        advisor_name: row.advisor_name,
        mobile: row.mobile,
        wechat: row.wechat,
        email: row.email,
        max_customer_count: row.max_customer_count,
        status: row.status,
        sort: row.sort || 0
    })
    editVisible.value = true
}

const submitEdit = async () => {
    await editFormRef.value?.validate()
    if (editForm.id) {
        await salesAdvisorEdit(editForm)
        feedback.msgSuccess('编辑成功')
    } else {
        await salesAdvisorAdd(editForm)
        feedback.msgSuccess('添加成功')
    }
    editVisible.value = false
    getLists()
}

const handleDetail = async (row: any) => {
    currentAdvisor.value = await salesAdvisorDetail({ id: row.id })
    detailVisible.value = true
}

const handleUpdateStatus = async (row: any, status: number) => {
    const statusText = status === 1 ? '恢复正常' : '设为休假'
    await feedback.confirm(`确定要${statusText}吗？`)
    await salesAdvisorUpdateStatus({ id: row.id, status })
    feedback.msgSuccess('操作成功')
    getLists()
}

const handleTransfer = async (row: any) => {
    await getAvailableAdvisors()
    currentAdvisor.value = row
    transferForm.from_advisor_id = row.id
    transferForm.to_advisor_id = ''
    transferForm.reason = ''
    transferVisible.value = true
}

const submitTransfer = async () => {
    if (!transferForm.to_advisor_id) {
        feedback.msgError('请选择目标顾问')
        return
    }
    const result = await salesAdvisorTransferCustomers(transferForm)
    feedback.msgSuccess(`成功转移 ${result.success} 个客户`)
    transferVisible.value = false
    getLists()
}

const handleDelete = async (row: any) => {
    await feedback.confirm('确定要删除该顾问吗？')
    await salesAdvisorDelete({ id: row.id })
    feedback.msgSuccess('删除成功')
    getLists()
}

getLists()
</script>

<style scoped>
</style>
