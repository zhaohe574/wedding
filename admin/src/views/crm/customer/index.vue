<template>
    <div class="customer-lists">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item class="w-[150px]" label="客户姓名">
                    <el-input
                        v-model="queryParams.customer_name"
                        placeholder="输入姓名"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[150px]" label="手机号">
                    <el-input
                        v-model="queryParams.customer_mobile"
                        placeholder="输入手机号"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item class="w-[120px]" label="意向等级">
                    <el-select v-model="queryParams.intention_level" placeholder="选择" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="A-高意向" value="A" />
                        <el-option label="B-中意向" value="B" />
                        <el-option label="C-低意向" value="C" />
                        <el-option label="D-待跟进" value="D" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[120px]" label="客户状态">
                    <el-select v-model="queryParams.customer_status" placeholder="选择" clearable>
                        <el-option label="全部" value="" />
                        <el-option label="新客户" :value="1" />
                        <el-option label="跟进中" :value="2" />
                        <el-option label="已签单" :value="3" />
                        <el-option label="已流失" :value="4" />
                        <el-option label="已完成" :value="5" />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[150px]" label="销售顾问">
                    <el-select v-model="queryParams.advisor_id" placeholder="选择顾问" clearable>
                        <el-option label="全部" value="" />
                        <el-option 
                            v-for="advisor in advisorList" 
                            :key="advisor.id" 
                            :label="advisor.advisor_name" 
                            :value="advisor.id" 
                        />
                    </el-select>
                </el-form-item>
                <el-form-item class="w-[280px]" label="婚期">
                    <el-date-picker
                        v-model="queryParams.wedding_date"
                        type="daterange"
                        start-placeholder="开始"
                        end-placeholder="结束"
                        value-format="YYYY-MM-DD"
                        clearable
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 统计卡片 -->
        <div class="mt-4 grid grid-cols-6 gap-4">
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">总客户</div>
                    <div class="text-2xl font-bold mt-2">{{ overview.total || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">新客户</div>
                    <div class="text-2xl font-bold mt-2 text-blue-500">{{ overview.new || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">跟进中</div>
                    <div class="text-2xl font-bold mt-2 text-orange-500">{{ overview.following || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已签单</div>
                    <div class="text-2xl font-bold mt-2 text-green-500">{{ overview.signed || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">已流失</div>
                    <div class="text-2xl font-bold mt-2 text-red-500">{{ overview.lost || 0 }}</div>
                </div>
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="text-center">
                    <div class="text-gray-500 text-sm">今日新增</div>
                    <div class="text-2xl font-bold mt-2 text-purple-500">{{ overview.today_new || 0 }}</div>
                </div>
            </el-card>
        </div>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex justify-between mb-4">
                <div>
                    <el-button type="primary" @click="handleAdd">
                        <el-icon class="mr-1"><Plus /></el-icon>添加客户
                    </el-button>
                    <el-button @click="handleBatchAssign" :disabled="selectedIds.length === 0">
                        批量分配
                    </el-button>
                </div>
            </div>

            <el-table 
                size="large" 
                v-loading="pager.loading" 
                :data="pager.lists"
                @selection-change="handleSelectionChange"
            >
                <el-table-column type="selection" width="50" />
                <el-table-column label="客户信息" min-width="180">
                    <template #default="{ row }">
                        <div class="flex items-center">
                            <div>
                                <div class="font-medium">{{ row.customer_name }}</div>
                                <div class="text-gray-400 text-xs">{{ row.customer_mobile }}</div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="意向等级" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getIntentionTagType(row.intention_level)" size="large">
                            {{ row.intention_level }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="客户状态" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getStatusTagType(row.customer_status)" size="small">
                            {{ row.customer_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="婚期" width="110">
                    <template #default="{ row }">
                        <div v-if="row.wedding_date">
                            <div>{{ row.wedding_date }}</div>
                            <div class="text-xs" :class="row.days_to_wedding < 30 ? 'text-red-500' : 'text-gray-400'">
                                {{ row.days_to_wedding > 0 ? `还有${row.days_to_wedding}天` : '已过期' }}
                            </div>
                        </div>
                        <span v-else class="text-gray-400">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="预算" width="100">
                    <template #default="{ row }">
                        <span v-if="row.wedding_budget">¥{{ row.wedding_budget }}</span>
                        <span v-else class="text-gray-400">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="销售顾问" width="100">
                    <template #default="{ row }">
                        <span v-if="row.advisor">{{ row.advisor.advisor_name }}</span>
                        <el-tag v-else type="danger" size="small">未分配</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="跟进信息" width="120">
                    <template #default="{ row }">
                        <div class="text-xs">
                            <div>跟进: {{ row.follow_count }}次</div>
                            <div :class="row.days_no_follow > 7 ? 'text-red-500' : 'text-gray-400'">
                                {{ row.days_no_follow }}天未跟进
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="来源" width="80">
                    <template #default="{ row }">
                        <span class="text-xs">{{ row.source_channel_desc }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" width="170" />
                <el-table-column label="操作" width="220" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button type="success" link @click="handleFollow(row)">跟进</el-button>
                        <el-button type="warning" link @click="handleAssign(row)">分配</el-button>
                        <el-button type="primary" link @click="handleEdit(row)">编辑</el-button>
                        <el-button 
                            v-if="row.customer_status <= 2" 
                            type="danger" 
                            link 
                            @click="handleMarkLoss(row)"
                        >流失</el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 添加/编辑客户弹窗 -->
        <el-dialog v-model="editVisible" :title="editForm.id ? '编辑客户' : '添加客户'" width="700px">
            <el-form :model="editForm" label-width="100px" :rules="editRules" ref="editFormRef">
                <el-row :gutter="16">
                    <el-col :span="12">
                        <el-form-item label="客户姓名" prop="customer_name">
                            <el-input v-model="editForm.customer_name" placeholder="输入姓名" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="手机号" prop="customer_mobile">
                            <el-input v-model="editForm.customer_mobile" placeholder="输入手机号" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="16">
                    <el-col :span="12">
                        <el-form-item label="微信号">
                            <el-input v-model="editForm.customer_wechat" placeholder="输入微信号" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="性别">
                            <el-radio-group v-model="editForm.gender">
                                <el-radio :label="0">未知</el-radio>
                                <el-radio :label="1">男</el-radio>
                                <el-radio :label="2">女</el-radio>
                            </el-radio-group>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="16">
                    <el-col :span="12">
                        <el-form-item label="意向等级" prop="intention_level">
                            <el-select v-model="editForm.intention_level" placeholder="选择意向等级">
                                <el-option label="A-高意向" value="A" />
                                <el-option label="B-中意向" value="B" />
                                <el-option label="C-低意向" value="C" />
                                <el-option label="D-待跟进" value="D" />
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="来源渠道">
                            <el-select v-model="editForm.source_channel" placeholder="选择来源">
                                <el-option label="小程序" :value="1" />
                                <el-option label="H5" :value="2" />
                                <el-option label="线下" :value="3" />
                                <el-option label="转介绍" :value="4" />
                                <el-option label="广告" :value="5" />
                                <el-option label="其他" :value="6" />
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="16">
                    <el-col :span="12">
                        <el-form-item label="计划婚期">
                            <el-date-picker
                                v-model="editForm.wedding_date"
                                type="date"
                                placeholder="选择婚期"
                                value-format="YYYY-MM-DD"
                            />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="预算金额">
                            <el-input-number v-model="editForm.wedding_budget" :min="0" :precision="2" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="16">
                    <el-col :span="12">
                        <el-form-item label="城市">
                            <el-input v-model="editForm.city" placeholder="输入城市" />
                        </el-form-item>
                    </el-col>
                    <el-col :span="12">
                        <el-form-item label="区域">
                            <el-input v-model="editForm.district" placeholder="输入区域" />
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-form-item label="婚礼场地">
                    <el-input v-model="editForm.wedding_venue" placeholder="输入婚礼场地" />
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="editForm.remark" type="textarea" :rows="2" placeholder="输入备注" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="editVisible = false">取消</el-button>
                <el-button type="primary" @click="submitEdit">确定</el-button>
            </template>
        </el-dialog>

        <!-- 分配顾问弹窗 -->
        <el-dialog v-model="assignVisible" title="分配销售顾问" width="500px">
            <el-form :model="assignForm" label-width="100px">
                <el-form-item label="选择顾问">
                    <el-select v-model="assignForm.advisor_id" placeholder="选择销售顾问" style="width: 100%">
                        <el-option 
                            v-for="advisor in availableAdvisors" 
                            :key="advisor.id" 
                            :label="`${advisor.advisor_name} (${advisor.current_customer_count}/${advisor.max_customer_count})`" 
                            :value="advisor.id"
                            :disabled="advisor.current_customer_count >= advisor.max_customer_count"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="分配原因">
                    <el-input v-model="assignForm.reason" type="textarea" :rows="2" placeholder="输入分配原因（可选）" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="assignVisible = false">取消</el-button>
                <el-button type="primary" @click="submitAssign">确定分配</el-button>
            </template>
        </el-dialog>

        <!-- 添加跟进弹窗 -->
        <el-dialog v-model="followVisible" title="添加跟进记录" width="600px">
            <el-form :model="followForm" label-width="100px" :rules="followRules" ref="followFormRef">
                <el-form-item label="跟进方式" prop="follow_type">
                    <el-select v-model="followForm.follow_type" placeholder="选择跟进方式">
                        <el-option label="电话" :value="1" />
                        <el-option label="微信" :value="2" />
                        <el-option label="到店" :value="3" />
                        <el-option label="试妆" :value="4" />
                        <el-option label="看样片" :value="5" />
                        <el-option label="上门" :value="6" />
                        <el-option label="其他" :value="7" />
                    </el-select>
                </el-form-item>
                <el-form-item label="跟进内容" prop="follow_content">
                    <el-input v-model="followForm.follow_content" type="textarea" :rows="4" placeholder="输入跟进内容" />
                </el-form-item>
                <el-form-item label="跟进结果" prop="follow_result">
                    <el-select v-model="followForm.follow_result" placeholder="选择跟进结果">
                        <el-option label="继续跟进" :value="1" />
                        <el-option label="意向提升" :value="2" />
                        <el-option label="意向下降" :value="3" />
                        <el-option label="已成交" :value="4" />
                        <el-option label="已流失" :value="5" />
                    </el-select>
                </el-form-item>
                <el-form-item label="跟进后意向">
                    <el-select v-model="followForm.intention_after" placeholder="选择意向等级">
                        <el-option label="A-高意向" value="A" />
                        <el-option label="B-中意向" value="B" />
                        <el-option label="C-低意向" value="C" />
                        <el-option label="D-待跟进" value="D" />
                    </el-select>
                </el-form-item>
                <el-form-item label="沟通时长">
                    <el-input-number v-model="followForm.duration" :min="0" />
                    <span class="ml-2 text-gray-400">分钟</span>
                </el-form-item>
                <el-form-item label="下次跟进">
                    <el-date-picker
                        v-model="followForm.next_follow_date"
                        type="datetime"
                        placeholder="选择下次跟进时间"
                        value-format="X"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="followVisible = false">取消</el-button>
                <el-button type="primary" @click="submitFollow">保存</el-button>
            </template>
        </el-dialog>

        <!-- 标记流失弹窗 -->
        <el-dialog v-model="lossVisible" title="标记客户流失" width="500px">
            <el-form :model="lossForm" label-width="100px">
                <el-form-item label="流失原因">
                    <el-input v-model="lossForm.loss_reason" type="textarea" :rows="3" placeholder="请输入流失原因" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="lossVisible = false">取消</el-button>
                <el-button type="danger" @click="submitMarkLoss">确认标记</el-button>
            </template>
        </el-dialog>

        <!-- 客户详情弹窗 -->
        <el-dialog v-model="detailVisible" title="客户详情" width="800px">
            <div v-if="currentCustomer">
                <el-descriptions :column="2" border>
                    <el-descriptions-item label="客户姓名">{{ currentCustomer.customer_name }}</el-descriptions-item>
                    <el-descriptions-item label="手机号">{{ currentCustomer.customer_mobile }}</el-descriptions-item>
                    <el-descriptions-item label="微信号">{{ currentCustomer.customer_wechat || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="性别">{{ currentCustomer.gender_desc }}</el-descriptions-item>
                    <el-descriptions-item label="意向等级">
                        <el-tag :type="getIntentionTagType(currentCustomer.intention_level)" size="large">
                            {{ currentCustomer.intention_level }} - {{ currentCustomer.intention_level_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="客户状态">
                        <el-tag :type="getStatusTagType(currentCustomer.customer_status)">
                            {{ currentCustomer.customer_status_desc }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="计划婚期">{{ currentCustomer.wedding_date || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="预算金额">
                        {{ currentCustomer.wedding_budget ? '¥' + currentCustomer.wedding_budget : '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="婚礼场地" :span="2">{{ currentCustomer.wedding_venue || '-' }}</el-descriptions-item>
                    <el-descriptions-item label="城市/区域">
                        {{ [currentCustomer.city, currentCustomer.district].filter(Boolean).join(' / ') || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="来源渠道">{{ currentCustomer.source_channel_desc }}</el-descriptions-item>
                    <el-descriptions-item label="销售顾问">
                        {{ currentCustomer.advisor?.advisor_name || '未分配' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="跟进次数">{{ currentCustomer.follow_count }}</el-descriptions-item>
                    <el-descriptions-item label="备注" :span="2">{{ currentCustomer.remark || '-' }}</el-descriptions-item>
                </el-descriptions>
            </div>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="customerLists">
import { Plus } from '@element-plus/icons-vue'
import { 
    customerLists, 
    customerDetail,
    customerAdd, 
    customerEdit, 
    customerAssign,
    customerBatchAssign,
    customerMarkLoss,
    customerOverview,
    customerAvailableAdvisors
} from '@/api/crm'
import { followRecordAdd } from '@/api/crm'
import { salesAdvisorSimpleList } from '@/api/crm'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const queryParams = reactive({
    customer_name: '',
    customer_mobile: '',
    intention_level: '',
    customer_status: '',
    advisor_id: '',
    wedding_date: []
})

const overview = ref<any>({})
const advisorList = ref<any[]>([])
const availableAdvisors = ref<any[]>([])
const selectedIds = ref<number[]>([])

// 编辑表单
const editVisible = ref(false)
const editFormRef = ref()
const editForm = reactive({
    id: 0,
    customer_name: '',
    customer_mobile: '',
    customer_wechat: '',
    gender: 0,
    intention_level: 'D',
    source_channel: 1,
    wedding_date: '',
    wedding_budget: 0,
    city: '',
    district: '',
    wedding_venue: '',
    remark: ''
})

const editRules = {
    customer_name: [{ required: true, message: '请输入客户姓名', trigger: 'blur' }]
}

// 分配表单
const assignVisible = ref(false)
const assignForm = reactive({
    id: 0,
    customer_ids: [] as number[],
    advisor_id: '',
    reason: ''
})

// 跟进表单
const followVisible = ref(false)
const followFormRef = ref()
const followForm = reactive({
    customer_id: 0,
    follow_type: 1,
    follow_content: '',
    follow_result: 1,
    intention_after: '',
    duration: 0,
    next_follow_date: ''
})

const followRules = {
    follow_type: [{ required: true, message: '请选择跟进方式', trigger: 'change' }],
    follow_content: [{ required: true, message: '请输入跟进内容', trigger: 'blur' }],
    follow_result: [{ required: true, message: '请选择跟进结果', trigger: 'change' }]
}

// 流失表单
const lossVisible = ref(false)
const lossForm = reactive({
    id: 0,
    loss_reason: ''
})

// 详情
const detailVisible = ref(false)
const currentCustomer = ref<any>(null)

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: customerLists,
    params: queryParams
})

const getIntentionTagType = (level: string) => {
    const types: Record<string, string> = {
        A: 'danger',
        B: 'warning',
        C: 'info',
        D: ''
    }
    return types[level] || ''
}

const getStatusTagType = (status: number) => {
    const types: Record<number, string> = {
        1: 'primary',
        2: 'warning',
        3: 'success',
        4: 'danger',
        5: 'info'
    }
    return types[status] || 'info'
}

const getOverview = async () => {
    overview.value = await customerOverview()
}

const getAdvisorList = async () => {
    advisorList.value = await salesAdvisorSimpleList()
}

const getAvailableAdvisors = async () => {
    availableAdvisors.value = await salesAdvisorSimpleList({ only_available: 1 })
}

const handleSelectionChange = (rows: any[]) => {
    selectedIds.value = rows.map(row => row.id)
}

const handleAdd = () => {
    Object.assign(editForm, {
        id: 0,
        customer_name: '',
        customer_mobile: '',
        customer_wechat: '',
        gender: 0,
        intention_level: 'D',
        source_channel: 1,
        wedding_date: '',
        wedding_budget: 0,
        city: '',
        district: '',
        wedding_venue: '',
        remark: ''
    })
    editVisible.value = true
}

const handleEdit = (row: any) => {
    Object.assign(editForm, {
        id: row.id,
        customer_name: row.customer_name,
        customer_mobile: row.customer_mobile,
        customer_wechat: row.customer_wechat,
        gender: row.gender,
        intention_level: row.intention_level,
        source_channel: row.source_channel,
        wedding_date: row.wedding_date,
        wedding_budget: row.wedding_budget,
        city: row.city,
        district: row.district,
        wedding_venue: row.wedding_venue,
        remark: row.remark
    })
    editVisible.value = true
}

const submitEdit = async () => {
    await editFormRef.value?.validate()
    if (editForm.id) {
        await customerEdit(editForm)
        feedback.msgSuccess('编辑成功')
    } else {
        await customerAdd(editForm)
        feedback.msgSuccess('添加成功')
    }
    editVisible.value = false
    getLists()
    getOverview()
}

const handleDetail = async (row: any) => {
    currentCustomer.value = await customerDetail({ id: row.id })
    detailVisible.value = true
}

const handleAssign = async (row: any) => {
    await getAvailableAdvisors()
    assignForm.id = row.id
    assignForm.customer_ids = []
    assignForm.advisor_id = ''
    assignForm.reason = ''
    assignVisible.value = true
}

const handleBatchAssign = async () => {
    await getAvailableAdvisors()
    assignForm.id = 0
    assignForm.customer_ids = selectedIds.value
    assignForm.advisor_id = ''
    assignForm.reason = ''
    assignVisible.value = true
}

const submitAssign = async () => {
    if (!assignForm.advisor_id) {
        feedback.msgError('请选择销售顾问')
        return
    }
    
    if (assignForm.id) {
        await customerAssign({
            id: assignForm.id,
            advisor_id: assignForm.advisor_id,
            reason: assignForm.reason
        })
        feedback.msgSuccess('分配成功')
    } else {
        const result = await customerBatchAssign({
            customer_ids: assignForm.customer_ids,
            advisor_id: assignForm.advisor_id,
            reason: assignForm.reason
        })
        feedback.msgSuccess(`成功分配 ${result.success} 个客户`)
    }
    assignVisible.value = false
    getLists()
}

const handleFollow = (row: any) => {
    Object.assign(followForm, {
        customer_id: row.id,
        follow_type: 1,
        follow_content: '',
        follow_result: 1,
        intention_after: row.intention_level,
        duration: 0,
        next_follow_date: ''
    })
    followVisible.value = true
}

const submitFollow = async () => {
    await followFormRef.value?.validate()
    await followRecordAdd({
        ...followForm,
        next_follow_time: followForm.next_follow_date ? parseInt(followForm.next_follow_date) : 0
    })
    feedback.msgSuccess('跟进记录添加成功')
    followVisible.value = false
    getLists()
}

const handleMarkLoss = (row: any) => {
    lossForm.id = row.id
    lossForm.loss_reason = ''
    lossVisible.value = true
}

const submitMarkLoss = async () => {
    await customerMarkLoss(lossForm)
    feedback.msgSuccess('已标记为流失')
    lossVisible.value = false
    getLists()
    getOverview()
}

onMounted(() => {
    getAdvisorList()
    getOverview()
})

getLists()
</script>

<style scoped>
</style>
