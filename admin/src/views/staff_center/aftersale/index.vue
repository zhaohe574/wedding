<template>
    <admin-page-shell class="staff-center-aftersale" title="我的售后">
        <el-alert
            class="mb-4"
            title="这里只展示与当前服务人员身份相关的售后记录：分配给我的工单、投诉我的记录、我的回访任务。"
            type="info"
            :closable="false"
            show-icon
        />

        <el-card class="!border-none" shadow="never">
            <el-tabs v-model="activeTab" @tab-change="handleTabChange">
                <el-tab-pane label="我的工单" name="ticket">
                    <el-form :model="ticketSearch" :inline="true" class="mb-4">
                        <el-form-item label="工单编号">
                            <el-input v-model="ticketSearch.ticket_sn" clearable placeholder="输入工单编号" />
                        </el-form-item>
                        <el-form-item label="标题">
                            <el-input v-model="ticketSearch.title" clearable placeholder="输入标题" />
                        </el-form-item>
                        <el-form-item label="状态">
                            <el-select v-model="ticketSearch.status" clearable placeholder="全部" style="width: 130px">
                                <el-option label="待分配" :value="0" />
                                <el-option label="处理中" :value="1" />
                                <el-option label="待确认" :value="2" />
                                <el-option label="已完成" :value="3" />
                                <el-option label="已关闭" :value="4" />
                                <el-option label="已取消" :value="5" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="优先级">
                            <el-select v-model="ticketSearch.priority" clearable placeholder="全部" style="width: 130px">
                                <el-option label="低" :value="1" />
                                <el-option label="中" :value="2" />
                                <el-option label="高" :value="3" />
                                <el-option label="紧急" :value="4" />
                            </el-select>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="resetTicketPage">查询</el-button>
                            <el-button @click="resetTicketSearch">重置</el-button>
                        </el-form-item>
                    </el-form>

                    <el-table :data="ticketPager.lists" v-loading="ticketPager.loading" size="large">
                        <el-table-column prop="ticket_sn" label="工单编号" width="180" />
                        <el-table-column prop="title" label="标题" min-width="180" show-overflow-tooltip />
                        <el-table-column label="客户" width="140">
                            <template #default="{ row }">
                                {{ row.user?.nickname || row.contact_name || '-' }}
                            </template>
                        </el-table-column>
                        <el-table-column label="关联订单" min-width="190">
                            <template #default="{ row }">
                                <div>{{ row.order_info?.order_sn || '-' }}</div>
                                <div class="text-xs text-gray-400">{{ row.order_info?.service_date || '' }}</div>
                            </template>
                        </el-table-column>
                        <el-table-column prop="priority_desc" label="优先级" width="90">
                            <template #default="{ row }">
                                <el-tag :type="getPriorityType(row.priority)">{{ row.priority_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="status_desc" label="状态" width="100">
                            <template #default="{ row }">
                                <el-tag :type="getTicketStatusType(row.status)">{{ row.status_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="create_time" label="创建时间" width="170" />
                        <el-table-column label="操作" width="140" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="viewTicket(row)">详情</el-button>
                                <el-button v-if="row.status === 1" type="success" link @click="openHandleTicket(row)">
                                    处理
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <div class="flex justify-end mt-4">
                        <pagination v-model="ticketPager" @change="getTicketLists" />
                    </div>
                </el-tab-pane>

                <el-tab-pane label="我的投诉" name="complaint">
                    <el-form :model="complaintSearch" :inline="true" class="mb-4">
                        <el-form-item label="投诉编号">
                            <el-input v-model="complaintSearch.complaint_sn" clearable placeholder="输入投诉编号" />
                        </el-form-item>
                        <el-form-item label="标题">
                            <el-input v-model="complaintSearch.title" clearable placeholder="输入标题" />
                        </el-form-item>
                        <el-form-item label="状态">
                            <el-select v-model="complaintSearch.status" clearable placeholder="全部" style="width: 130px">
                                <el-option label="待处理" :value="0" />
                                <el-option label="处理中" :value="1" />
                                <el-option label="已处理" :value="2" />
                                <el-option label="已申诉" :value="3" />
                                <el-option label="已关闭" :value="4" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="等级">
                            <el-select v-model="complaintSearch.level" clearable placeholder="全部" style="width: 130px">
                                <el-option label="一般" :value="1" />
                                <el-option label="严重" :value="2" />
                                <el-option label="紧急" :value="3" />
                            </el-select>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="resetComplaintPage">查询</el-button>
                            <el-button @click="resetComplaintSearch">重置</el-button>
                        </el-form-item>
                    </el-form>

                    <el-table :data="complaintPager.lists" v-loading="complaintPager.loading" size="large">
                        <el-table-column prop="complaint_sn" label="投诉编号" width="180" />
                        <el-table-column prop="title" label="标题" min-width="180" show-overflow-tooltip />
                        <el-table-column label="客户" width="140">
                            <template #default="{ row }">{{ row.user?.nickname || row.contact_name || '-' }}</template>
                        </el-table-column>
                        <el-table-column prop="type_desc" label="类型" width="110" />
                        <el-table-column prop="level_desc" label="等级" width="90">
                            <template #default="{ row }">
                                <el-tag :type="getComplaintLevelType(row.level)">{{ row.level_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="status_desc" label="状态" width="100">
                            <template #default="{ row }">
                                <el-tag :type="getComplaintStatusType(row.status)">{{ row.status_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="create_time" label="创建时间" width="170" />
                        <el-table-column label="操作" width="90" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="viewComplaint(row)">详情</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <div class="flex justify-end mt-4">
                        <pagination v-model="complaintPager" @change="getComplaintLists" />
                    </div>
                </el-tab-pane>

                <el-tab-pane label="我的回访" name="callback">
                    <el-form :model="callbackSearch" :inline="true" class="mb-4">
                        <el-form-item label="回访编号">
                            <el-input v-model="callbackSearch.callback_sn" clearable placeholder="输入回访编号" />
                        </el-form-item>
                        <el-form-item label="状态">
                            <el-select v-model="callbackSearch.status" clearable placeholder="全部" style="width: 130px">
                                <el-option label="待回访" :value="0" />
                                <el-option label="已回访" :value="1" />
                                <el-option label="无法联系" :value="2" />
                                <el-option label="已取消" :value="3" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="类型">
                            <el-select v-model="callbackSearch.type" clearable placeholder="全部" style="width: 130px">
                                <el-option label="服务前" :value="1" />
                                <el-option label="服务中" :value="2" />
                                <el-option label="服务后" :value="3" />
                            </el-select>
                        </el-form-item>
                        <el-form-item label="是否有问题">
                            <el-select v-model="callbackSearch.has_problem" clearable placeholder="全部" style="width: 130px">
                                <el-option label="有问题" :value="1" />
                                <el-option label="无问题" :value="0" />
                            </el-select>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="resetCallbackPage">查询</el-button>
                            <el-button @click="resetCallbackSearch">重置</el-button>
                        </el-form-item>
                    </el-form>

                    <el-table :data="callbackPager.lists" v-loading="callbackPager.loading" size="large">
                        <el-table-column prop="callback_sn" label="回访编号" width="180" />
                        <el-table-column label="客户" width="140">
                            <template #default="{ row }">{{ row.user?.nickname || '-' }}</template>
                        </el-table-column>
                        <el-table-column label="订单" min-width="170">
                            <template #default="{ row }">{{ row.order?.order_sn || '-' }}</template>
                        </el-table-column>
                        <el-table-column prop="type_desc" label="类型" width="90" />
                        <el-table-column prop="method_desc" label="方式" width="110" />
                        <el-table-column prop="status_desc" label="状态" width="100">
                            <template #default="{ row }">
                                <el-tag :type="getCallbackStatusType(row.status)">{{ row.status_desc }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="评分" width="120">
                            <template #default="{ row }">
                                <el-rate v-if="row.score > 0" v-model="row.score" disabled size="small" />
                                <span v-else>-</span>
                            </template>
                        </el-table-column>
                        <el-table-column label="问题" width="90">
                            <template #default="{ row }">
                                <el-tag v-if="row.has_problem" type="danger">有</el-tag>
                                <el-tag v-else type="success">无</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="plan_time" label="计划时间" width="170" />
                        <el-table-column label="操作" width="190" fixed="right">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="viewCallback(row)">详情</el-button>
                                <el-button v-if="row.status === 0" type="success" link @click="openCompleteCallback(row)">
                                    完成回访
                                </el-button>
                                <el-button v-if="row.status === 0" type="warning" link @click="markUnreachable(row)">
                                    无法联系
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <div class="flex justify-end mt-4">
                        <pagination v-model="callbackPager" @change="getCallbackLists" />
                    </div>
                </el-tab-pane>
            </el-tabs>
        </el-card>

        <el-dialog v-model="handleVisible" title="处理工单" width="600px">
            <el-form :model="handleForm" label-width="90px">
                <el-form-item label="处理结果" required>
                    <el-input v-model="handleForm.result" type="textarea" :rows="4" placeholder="请输入处理结果" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="handleVisible = false">取消</el-button>
                <el-button type="primary" :loading="submitLoading" @click="submitHandleTicket">确定</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="callbackVisible" title="完成回访" width="600px">
            <el-form :model="callbackForm" label-width="110px">
                <el-form-item label="整体满意度">
                    <el-rate v-model="callbackForm.score" />
                </el-form-item>
                <el-form-item label="服务态度">
                    <el-rate v-model="callbackForm.score_service" />
                </el-form-item>
                <el-form-item label="专业水平">
                    <el-rate v-model="callbackForm.score_professional" />
                </el-form-item>
                <el-form-item label="时间守约">
                    <el-rate v-model="callbackForm.score_punctual" />
                </el-form-item>
                <el-form-item label="回访时长(秒)">
                    <el-input-number v-model="callbackForm.duration" :min="0" />
                </el-form-item>
                <el-form-item label="回访内容">
                    <el-input v-model="callbackForm.content" type="textarea" :rows="3" maxlength="1000" />
                </el-form-item>
                <el-form-item label="回访摘要">
                    <el-input v-model="callbackForm.summary" type="textarea" :rows="2" maxlength="1000" />
                </el-form-item>
                <el-form-item label="是否有问题">
                    <el-switch v-model="callbackForm.has_problem" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <template v-if="callbackForm.has_problem === 1">
                    <el-form-item label="问题类型">
                        <el-input v-model="callbackForm.problem_type" maxlength="100" />
                    </el-form-item>
                    <el-form-item label="问题描述">
                        <el-input v-model="callbackForm.problem_desc" type="textarea" :rows="2" maxlength="1000" />
                    </el-form-item>
                </template>
            </el-form>
            <template #footer>
                <el-button @click="callbackVisible = false">取消</el-button>
                <el-button type="primary" :loading="submitLoading" @click="submitCompleteCallback">确定</el-button>
            </template>
        </el-dialog>

        <el-drawer v-model="detailVisible" :title="detailTitle" size="620px">
            <template v-if="currentDetail">
                <el-descriptions :column="2" border>
                    <el-descriptions-item
                        v-for="field in detailFields"
                        :key="field.key"
                        :label="field.label"
                        :span="field.span || 1"
                    >
                        {{ getDetailValue(field.key) }}
                    </el-descriptions-item>
                </el-descriptions>
                <template v-if="currentDetail.logs && currentDetail.logs.length > 0">
                    <el-divider>工单日志</el-divider>
                    <el-timeline>
                        <el-timeline-item
                            v-for="log in currentDetail.logs"
                            :key="log.id"
                            :timestamp="log.create_time || ''"
                        >
                            {{ log.content }}
                        </el-timeline-item>
                    </el-timeline>
                </template>
            </template>
        </el-drawer>
    </admin-page-shell>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import type { TabPaneName } from 'element-plus'
import {
    myAfterSaleCallbackComplete,
    myAfterSaleCallbackDetail,
    myAfterSaleCallbackLists,
    myAfterSaleCallbackMarkUnreachable,
    myAfterSaleComplaintDetail,
    myAfterSaleComplaintLists,
    myAfterSaleTicketDetail,
    myAfterSaleTicketHandle,
    myAfterSaleTicketLists
} from '@/api/staff-center'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

const activeTab = ref('ticket')
const submitLoading = ref(false)
const detailVisible = ref(false)
const detailTitle = ref('')
const currentDetail = ref<any>(null)
const detailFields = ref<any[]>([])
const handleVisible = ref(false)
const callbackVisible = ref(false)

const ticketSearch = reactive({
    ticket_sn: '',
    title: '',
    status: '' as any,
    priority: '' as any
})
const complaintSearch = reactive({
    complaint_sn: '',
    title: '',
    status: '' as any,
    level: '' as any
})
const callbackSearch = reactive({
    callback_sn: '',
    status: '' as any,
    type: '' as any,
    has_problem: '' as any
})

const handleForm = reactive({
    id: 0,
    result: ''
})

const callbackForm = reactive({
    id: 0,
    score: 5,
    score_service: 5,
    score_professional: 5,
    score_punctual: 5,
    duration: 0,
    content: '',
    summary: '',
    has_problem: 0,
    problem_type: '',
    problem_desc: ''
})

const {
    pager: ticketPager,
    getLists: getTicketLists,
    resetPage: resetTicketPage,
    resetParams: resetTicketParams
} = usePaging({
    fetchFun: myAfterSaleTicketLists,
    params: ticketSearch,
    size: 10
})

const {
    pager: complaintPager,
    getLists: getComplaintLists,
    resetPage: resetComplaintPage,
    resetParams: resetComplaintParams
} = usePaging({
    fetchFun: myAfterSaleComplaintLists,
    params: complaintSearch,
    size: 10
})

const {
    pager: callbackPager,
    getLists: getCallbackLists,
    resetPage: resetCallbackPage,
    resetParams: resetCallbackParams
} = usePaging({
    fetchFun: myAfterSaleCallbackLists,
    params: callbackSearch,
    size: 10
})

const resetTicketSearch = () => resetTicketParams()
const resetComplaintSearch = () => resetComplaintParams()
const resetCallbackSearch = () => resetCallbackParams()

const handleTabChange = (name: TabPaneName) => {
    if (name === 'ticket') getTicketLists()
    if (name === 'complaint') getComplaintLists()
    if (name === 'callback') getCallbackLists()
}

const viewTicket = async (row: any) => {
    currentDetail.value = await myAfterSaleTicketDetail({ id: row.id })
    detailTitle.value = '工单详情'
    detailFields.value = [
        { key: 'ticket_sn', label: '工单编号' },
        { key: 'title', label: '标题' },
        { key: 'type_desc', label: '类型' },
        { key: 'priority_desc', label: '优先级' },
        { key: 'status_desc', label: '状态' },
        { key: 'order_info.order_sn', label: '关联订单' },
        { key: 'order_info.service_date', label: '服务日期' },
        { key: 'order_info.package_name', label: '主套餐' },
        { key: 'order_info.staff_name', label: '主套餐服务人员' },
        { key: 'order_info.service_address', label: '服务地址', span: 2 },
        { key: 'content', label: '内容', span: 2 },
        { key: 'handle_result', label: '处理结果', span: 2 },
        { key: 'create_time', label: '创建时间' }
    ]
    detailVisible.value = true
}

const viewComplaint = async (row: any) => {
    currentDetail.value = await myAfterSaleComplaintDetail({ id: row.id })
    detailTitle.value = '投诉详情'
    detailFields.value = [
        { key: 'complaint_sn', label: '投诉编号' },
        { key: 'title', label: '标题' },
        { key: 'type_desc', label: '类型' },
        { key: 'level_desc', label: '等级' },
        { key: 'status_desc', label: '状态' },
        { key: 'contact_name', label: '联系人' },
        { key: 'contact_mobile', label: '联系电话' },
        { key: 'content', label: '投诉内容', span: 2 },
        { key: 'expect_result', label: '期望结果', span: 2 },
        { key: 'handle_result', label: '处理结果', span: 2 },
        { key: 'create_time', label: '创建时间' }
    ]
    detailVisible.value = true
}

const viewCallback = async (row: any) => {
    currentDetail.value = await myAfterSaleCallbackDetail({ id: row.id })
    detailTitle.value = '回访详情'
    detailFields.value = [
        { key: 'callback_sn', label: '回访编号' },
        { key: 'type_desc', label: '类型' },
        { key: 'method_desc', label: '方式' },
        { key: 'status_desc', label: '状态' },
        { key: 'score', label: '满意度' },
        { key: 'plan_time', label: '计划时间' },
        { key: 'actual_time', label: '实际时间' },
        { key: 'content', label: '回访内容', span: 2 },
        { key: 'summary', label: '回访摘要', span: 2 },
        { key: 'problem_desc', label: '问题描述', span: 2 }
    ]
    detailVisible.value = true
}

const openHandleTicket = (row: any) => {
    handleForm.id = row.id
    handleForm.result = ''
    handleVisible.value = true
}

const submitHandleTicket = async () => {
    if (!handleForm.result.trim()) {
        feedback.msgError('请输入处理结果')
        return
    }
    submitLoading.value = true
    try {
        await myAfterSaleTicketHandle({ id: handleForm.id, result: handleForm.result })
        feedback.msgSuccess('处理成功')
        handleVisible.value = false
        getTicketLists()
    } finally {
        submitLoading.value = false
    }
}

const openCompleteCallback = (row: any) => {
    Object.assign(callbackForm, {
        id: row.id,
        score: 5,
        score_service: 5,
        score_professional: 5,
        score_punctual: 5,
        duration: 0,
        content: '',
        summary: '',
        has_problem: 0,
        problem_type: '',
        problem_desc: ''
    })
    callbackVisible.value = true
}

const submitCompleteCallback = async () => {
    submitLoading.value = true
    try {
        await myAfterSaleCallbackComplete(callbackForm)
        feedback.msgSuccess('回访完成')
        callbackVisible.value = false
        getCallbackLists()
    } finally {
        submitLoading.value = false
    }
}

const markUnreachable = async (row: any) => {
    await feedback.confirm('确定标记为无法联系吗？')
    await myAfterSaleCallbackMarkUnreachable({ id: row.id })
    feedback.msgSuccess('标记成功')
    getCallbackLists()
}

const getPriorityType = (priority: number) => {
    const map: Record<number, any> = { 1: 'info', 2: 'primary', 3: 'warning', 4: 'danger' }
    return map[priority] || 'info'
}

const getTicketStatusType = (status: number) => {
    const map: Record<number, any> = { 0: 'info', 1: 'warning', 2: 'primary', 3: 'success', 4: 'danger', 5: 'info' }
    return map[status] || 'info'
}

const getComplaintLevelType = (level: number) => {
    const map: Record<number, any> = { 1: 'info', 2: 'warning', 3: 'danger' }
    return map[level] || 'info'
}

const getComplaintStatusType = (status: number) => {
    const map: Record<number, any> = { 0: 'info', 1: 'warning', 2: 'success', 3: 'primary', 4: 'info' }
    return map[status] || 'info'
}

const getCallbackStatusType = (status: number) => {
    const map: Record<number, any> = { 0: 'warning', 1: 'success', 2: 'info', 3: 'info' }
    return map[status] || 'info'
}

const getDetailValue = (path: string) => {
    const value = path.split('.').reduce((data: any, key: string) => data?.[key], currentDetail.value)
    return value === undefined || value === null || value === '' ? '-' : value
}

onMounted(() => {
    getTicketLists()
})
</script>

<style scoped>
.staff-center-aftersale {
    padding: 4px 0;
}
</style>
