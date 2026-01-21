<template>
    <div class="notification-container">
        <!-- 统计卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card">
                    <div class="stat-content">
                        <div class="stat-label">总通知数</div>
                        <div class="stat-value">{{ statistics.total_count || 0 }}</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><Bell /></el-icon>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card success">
                    <div class="stat-content">
                        <div class="stat-label">已读</div>
                        <div class="stat-value">{{ statistics.read_count || 0 }}</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><Check /></el-icon>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card warning">
                    <div class="stat-content">
                        <div class="stat-label">未读</div>
                        <div class="stat-value">{{ statistics.unread_count || 0 }}</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><Message /></el-icon>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card info">
                    <div class="stat-content">
                        <div class="stat-label">阅读率</div>
                        <div class="stat-value">{{ statistics.read_rate || '0%' }}</div>
                    </div>
                    <el-icon class="stat-icon" :size="40"><TrendCharts /></el-icon>
                </el-card>
            </el-col>
        </el-row>

        <!-- 搜索栏 -->
        <el-card shadow="never" class="mb-4">
            <el-form :model="queryParams" inline>
                <el-form-item label="通知类型">
                    <el-select v-model="queryParams.notify_type" placeholder="请选择" clearable style="width: 140px">
                        <el-option label="系统通知" :value="1" />
                        <el-option label="订单通知" :value="2" />
                        <el-option label="互动通知" :value="3" />
                        <el-option label="活动通知" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item label="阅读状态">
                    <el-select v-model="queryParams.is_read" placeholder="请选择" clearable style="width: 120px">
                        <el-option label="未读" :value="0" />
                        <el-option label="已读" :value="1" />
                    </el-select>
                </el-form-item>
                <el-form-item label="用户">
                    <el-input v-model="queryParams.keyword" placeholder="昵称/手机号" clearable style="width: 150px" />
                </el-form-item>
                <el-form-item label="日期范围">
                    <el-date-picker
                        v-model="dateRange"
                        type="daterange"
                        value-format="YYYY-MM-DD"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        style="width: 240px"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSearch">搜索</el-button>
                    <el-button @click="handleReset">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- 操作栏 -->
        <el-card shadow="never" class="mb-4">
            <el-button type="primary" @click="handleSend">
                <el-icon><Position /></el-icon>
                发送通知
            </el-button>
            <el-button type="success" @click="handleSendAll">
                <el-icon><Promotion /></el-icon>
                全员通知
            </el-button>
            <el-button type="danger" :disabled="!selectedIds.length" @click="handleBatchDelete">
                <el-icon><Delete /></el-icon>
                批量删除
            </el-button>
        </el-card>

        <!-- 列表 -->
        <el-card shadow="never">
            <el-table 
                v-loading="loading" 
                :data="tableData" 
                @selection-change="handleSelectionChange"
                row-key="id"
            >
                <el-table-column type="selection" width="50" />
                <el-table-column label="接收用户" width="150">
                    <template #default="{ row }">
                        <div class="user-info" v-if="row.user">
                            <el-avatar :size="28" :src="row.user.avatar">
                                {{ row.user.nickname?.charAt(0) }}
                            </el-avatar>
                            <span class="ml-2">{{ row.user.nickname || '-' }}</span>
                        </div>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="通知类型" width="100">
                    <template #default="{ row }">
                        <el-tag :type="getTypeTag(row.notify_type)" size="small">
                            {{ row.notify_type_text }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="标题" min-width="150" prop="title" show-overflow-tooltip />
                <el-table-column label="内容" min-width="200" prop="content" show-overflow-tooltip />
                <el-table-column label="阅读状态" width="100" align="center">
                    <template #default="{ row }">
                        <el-tag :type="row.is_read ? 'success' : 'danger'" size="small">
                            {{ row.is_read_text }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="发送时间" width="160" prop="create_time_text" />
                <el-table-column label="阅读时间" width="160" prop="read_time_text" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button link type="primary" @click="handleDetail(row)">详情</el-button>
                        <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="pagination-container">
                <el-pagination
                    v-model:current-page="queryParams.page_no"
                    v-model:page-size="queryParams.page_size"
                    :page-sizes="[10, 20, 50, 100]"
                    :total="total"
                    layout="total, sizes, prev, pager, next, jumper"
                    @size-change="getList"
                    @current-change="getList"
                />
            </div>
        </el-card>

        <!-- 发送通知弹窗 -->
        <el-dialog 
            v-model="showSendDialog" 
            :title="sendForm.send_type === 'all' ? '全员通知' : '发送通知'"
            width="600px"
            destroy-on-close
        >
            <el-form ref="sendFormRef" :model="sendForm" :rules="sendRules" label-width="100px">
                <el-form-item v-if="sendForm.send_type !== 'all'" label="发送方式">
                    <el-radio-group v-model="sendForm.send_type">
                        <el-radio label="single">指定用户</el-radio>
                        <el-radio label="batch">批量发送</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="sendForm.send_type === 'single'" label="用户ID" prop="user_id">
                    <el-input v-model.number="sendForm.user_id" placeholder="请输入用户ID" />
                </el-form-item>
                <el-form-item v-if="sendForm.send_type === 'batch'" label="用户ID列表" prop="user_ids_text">
                    <el-input 
                        v-model="sendForm.user_ids_text" 
                        type="textarea" 
                        :rows="3"
                        placeholder="请输入用户ID，多个用英文逗号分隔"
                    />
                </el-form-item>
                <el-form-item label="通知类型" prop="notify_type">
                    <el-select v-model="sendForm.notify_type" placeholder="请选择" style="width: 100%">
                        <el-option label="系统通知" :value="1" />
                        <el-option label="订单通知" :value="2" />
                        <el-option label="互动通知" :value="3" />
                        <el-option label="活动通知" :value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item label="选择模板">
                    <el-select v-model="selectedTemplate" placeholder="选择模板快速填充" clearable style="width: 100%" @change="handleTemplateChange">
                        <el-option 
                            v-for="tpl in templates" 
                            :key="tpl.id" 
                            :label="tpl.name" 
                            :value="tpl.id"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="标题" prop="title">
                    <el-input v-model="sendForm.title" placeholder="请输入标题" maxlength="100" show-word-limit />
                </el-form-item>
                <el-form-item label="内容" prop="content">
                    <el-input 
                        v-model="sendForm.content" 
                        type="textarea" 
                        :rows="4"
                        placeholder="请输入内容"
                        maxlength="500"
                        show-word-limit
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showSendDialog = false">取消</el-button>
                <el-button type="primary" :loading="sendLoading" @click="handleSendSubmit">发送</el-button>
            </template>
        </el-dialog>

        <!-- 详情弹窗 -->
        <el-dialog v-model="showDetailDialog" title="通知详情" width="500px">
            <el-descriptions :column="1" border>
                <el-descriptions-item label="接收用户">
                    {{ detailData.user?.nickname || '-' }} ({{ detailData.user?.mobile || '-' }})
                </el-descriptions-item>
                <el-descriptions-item label="通知类型">
                    <el-tag :type="getTypeTag(detailData.notify_type)" size="small">
                        {{ detailData.notify_type_text }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="标题">{{ detailData.title }}</el-descriptions-item>
                <el-descriptions-item label="内容">{{ detailData.content }}</el-descriptions-item>
                <el-descriptions-item label="阅读状态">
                    <el-tag :type="detailData.is_read ? 'success' : 'danger'" size="small">
                        {{ detailData.is_read_text }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="发送时间">{{ detailData.create_time_text }}</el-descriptions-item>
                <el-descriptions-item label="阅读时间">{{ detailData.read_time_text }}</el-descriptions-item>
            </el-descriptions>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Bell, Check, Message, TrendCharts, Position, Promotion, Delete } from '@element-plus/icons-vue'
import {
    getNotificationList,
    getNotificationDetail,
    sendNotification,
    batchSendNotification,
    sendToAllNotification,
    deleteNotification,
    batchDeleteNotification,
    getNotificationStatistics,
    getNotificationTemplates
} from '@/api/notification'

// 列表数据
const loading = ref(false)
const tableData = ref<any[]>([])
const total = ref(0)
const statistics = ref<any>({})
const selectedIds = ref<number[]>([])
const dateRange = ref<string[]>([])

// 查询参数
const queryParams = reactive({
    page_no: 1,
    page_size: 10,
    notify_type: '',
    is_read: '',
    keyword: '',
    start_date: '',
    end_date: ''
})

// 监听日期范围变化
watch(dateRange, (val) => {
    if (val && val.length === 2) {
        queryParams.start_date = val[0]
        queryParams.end_date = val[1]
    } else {
        queryParams.start_date = ''
        queryParams.end_date = ''
    }
})

// 发送弹窗
const showSendDialog = ref(false)
const sendLoading = ref(false)
const sendFormRef = ref()
const sendForm = reactive({
    send_type: 'single',
    user_id: '',
    user_ids_text: '',
    notify_type: 1,
    title: '',
    content: ''
})

const sendRules = {
    notify_type: [{ required: true, message: '请选择通知类型', trigger: 'change' }],
    title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
    content: [{ required: true, message: '请输入内容', trigger: 'blur' }]
}

// 模板
const templates = ref<any[]>([])
const selectedTemplate = ref<number | null>(null)

// 详情弹窗
const showDetailDialog = ref(false)
const detailData = ref<any>({})

// 获取列表
const getList = async () => {
    loading.value = true
    try {
        const res = await getNotificationList(queryParams)
        tableData.value = res.lists || []
        total.value = res.count || 0
    } catch (error) {
        console.error(error)
    } finally {
        loading.value = false
    }
}

// 获取统计
const getStatistics = async () => {
    try {
        const res = await getNotificationStatistics()
        statistics.value = res || {}
    } catch (error) {
        console.error(error)
    }
}

// 获取模板
const getTemplates = async () => {
    try {
        const res = await getNotificationTemplates()
        templates.value = res || []
    } catch (error) {
        console.error(error)
    }
}

// 搜索
const handleSearch = () => {
    queryParams.page_no = 1
    getList()
}

// 重置
const handleReset = () => {
    queryParams.notify_type = ''
    queryParams.is_read = ''
    queryParams.keyword = ''
    dateRange.value = []
    handleSearch()
}

// 选择变化
const handleSelectionChange = (selection: any[]) => {
    selectedIds.value = selection.map(item => item.id)
}

// 发送通知
const handleSend = () => {
    sendForm.send_type = 'single'
    sendForm.user_id = ''
    sendForm.user_ids_text = ''
    sendForm.notify_type = 1
    sendForm.title = ''
    sendForm.content = ''
    selectedTemplate.value = null
    showSendDialog.value = true
}

// 全员通知
const handleSendAll = () => {
    sendForm.send_type = 'all'
    sendForm.notify_type = 1
    sendForm.title = ''
    sendForm.content = ''
    selectedTemplate.value = null
    showSendDialog.value = true
}

// 模板变化
const handleTemplateChange = (templateId: number) => {
    if (templateId) {
        const tpl = templates.value.find(t => t.id === templateId)
        if (tpl) {
            sendForm.notify_type = tpl.type
            sendForm.title = tpl.title
            sendForm.content = tpl.content
        }
    }
}

// 发送提交
const handleSendSubmit = async () => {
    if (!sendFormRef.value) return
    await sendFormRef.value.validate()

    sendLoading.value = true
    try {
        const params: any = {
            notify_type: sendForm.notify_type,
            title: sendForm.title,
            content: sendForm.content
        }

        if (sendForm.send_type === 'single') {
            if (!sendForm.user_id) {
                ElMessage.warning('请输入用户ID')
                return
            }
            params.user_id = sendForm.user_id
            await sendNotification(params)
        } else if (sendForm.send_type === 'batch') {
            if (!sendForm.user_ids_text) {
                ElMessage.warning('请输入用户ID列表')
                return
            }
            const userIds = sendForm.user_ids_text.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id))
            if (!userIds.length) {
                ElMessage.warning('请输入有效的用户ID')
                return
            }
            params.user_ids = userIds
            await batchSendNotification(params)
        } else if (sendForm.send_type === 'all') {
            await sendToAllNotification(params)
        }

        ElMessage.success('发送成功')
        showSendDialog.value = false
        getList()
        getStatistics()
    } catch (error) {
        console.error(error)
    } finally {
        sendLoading.value = false
    }
}

// 查看详情
const handleDetail = async (row: any) => {
    try {
        const res = await getNotificationDetail({ id: row.id })
        detailData.value = res
        showDetailDialog.value = true
    } catch (error) {
        console.error(error)
    }
}

// 删除
const handleDelete = (row: any) => {
    ElMessageBox.confirm('确定要删除该通知吗？', '提示', {
        type: 'warning'
    }).then(async () => {
        await deleteNotification({ id: row.id })
        ElMessage.success('删除成功')
        getList()
        getStatistics()
    }).catch(() => {})
}

// 批量删除
const handleBatchDelete = () => {
    ElMessageBox.confirm(`确定要删除选中的 ${selectedIds.value.length} 条通知吗？`, '提示', {
        type: 'warning'
    }).then(async () => {
        await batchDeleteNotification({ ids: selectedIds.value })
        ElMessage.success('批量删除成功')
        getList()
        getStatistics()
    }).catch(() => {})
}

// 获取类型标签
const getTypeTag = (type: number) => {
    const tags: Record<number, string> = { 1: '', 2: 'success', 3: 'warning', 4: 'danger' }
    return tags[type] || 'info'
}

onMounted(() => {
    getList()
    getStatistics()
    getTemplates()
})
</script>

<style scoped lang="scss">
.notification-container {
    padding: 16px;
}

.stat-card {
    position: relative;
    overflow: hidden;

    .stat-content {
        .stat-label {
            font-size: 14px;
            color: #909399;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #303133;
            margin-top: 8px;
        }
    }

    .stat-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #e6e6e6;
    }

    &.success .stat-value { color: #67c23a; }
    &.warning .stat-value { color: #e6a23c; }
    &.info .stat-value { color: #409eff; }
}

.user-info {
    display: flex;
    align-items: center;
}

.pagination-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
}

.mb-4 {
    margin-bottom: 16px;
}

.ml-2 {
    margin-left: 8px;
}
</style>
