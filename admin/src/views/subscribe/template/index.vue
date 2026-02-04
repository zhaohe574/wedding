<template>
    <div class="subscribe-container">
        <!-- 统计卡片 -->
        <el-row :gutter="16" class="mb-4">
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card">
                    <div class="stat-item">
                        <div class="stat-value">{{ statistics.total || 0 }}</div>
                        <div class="stat-label">消息总数</div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card success">
                    <div class="stat-item">
                        <div class="stat-value">{{ statistics.success || 0 }}</div>
                        <div class="stat-label">发送成功</div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card danger">
                    <div class="stat-item">
                        <div class="stat-value">{{ statistics.failed || 0 }}</div>
                        <div class="stat-label">发送失败</div>
                    </div>
                </el-card>
            </el-col>
            <el-col :span="6">
                <el-card shadow="hover" class="stat-card info">
                    <div class="stat-item">
                        <div class="stat-value">{{ statistics.success_rate || 0 }}%</div>
                        <div class="stat-label">成功率</div>
                    </div>
                </el-card>
            </el-col>
        </el-row>

        <!-- 标签页 -->
        <el-card>
            <el-tabs v-model="activeTab" @tab-change="handleTabChange">
                <!-- 消息模板 -->
                <el-tab-pane label="消息模板" name="template">
                    <div class="toolbar">
                        <el-button type="primary" @click="handleAddTemplate">
                            <el-icon><Plus /></el-icon>
                            添加模板
                        </el-button>
                        <el-input
                            v-model="templateSearch.name"
                            placeholder="搜索模板名称"
                            style="width: 200px; margin-left: 16px"
                            clearable
                            @clear="fetchTemplateList"
                            @keyup.enter="fetchTemplateList"
                        />
                        <el-select
                            v-model="templateSearch.scene"
                            placeholder="选择场景"
                            style="width: 160px; margin-left: 8px"
                            clearable
                            @change="fetchTemplateList"
                        >
                            <el-option
                                v-for="item in sceneOptions"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </div>

                    <el-table :data="templateList" v-loading="templateLoading" border>
                        <el-table-column prop="name" label="模板名称" min-width="150" />
                        <el-table-column prop="template_id" label="模板ID" min-width="200" show-overflow-tooltip />
                        <el-table-column prop="scene_desc" label="使用场景" width="140" />
                        <el-table-column prop="status" label="状态" width="100" align="center">
                            <template #default="{ row }">
                                <el-switch
                                    :model-value="row.status === 1"
                                    @change="handleToggleTemplateStatus(row)"
                                />
                            </template>
                        </el-table-column>
                        <el-table-column prop="sort" label="排序" width="80" align="center" />
                        <el-table-column prop="create_time" label="创建时间" width="170" />
                        <el-table-column label="操作" width="150" fixed="right">
                            <template #default="{ row }">
                                <el-button link type="primary" @click="handleEditTemplate(row)">编辑</el-button>
                                <el-button link type="danger" @click="handleDeleteTemplate(row)">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>

                    <div class="pagination">
                        <el-pagination
                            v-model:current-page="templatePager.page"
                            v-model:page-size="templatePager.size"
                            :total="templatePager.total"
                            :page-sizes="[10, 20, 50, 100]"
                            layout="total, sizes, prev, pager, next"
                            @size-change="fetchTemplateList"
                            @current-change="fetchTemplateList"
                        />
                    </div>
                </el-tab-pane>

                <!-- 场景配置 -->
                <el-tab-pane label="场景配置" name="scene">
                    <el-table :data="sceneList" v-loading="sceneLoading" border>
                        <el-table-column prop="name" label="场景名称" min-width="140" />
                        <el-table-column prop="scene" label="场景标识" width="150" />
                        <el-table-column prop="template_name" label="关联模板" width="150" />
                        <el-table-column prop="trigger_event" label="触发事件" width="140" />
                        <el-table-column prop="is_auto_desc" label="发送方式" width="100" align="center" />
                        <el-table-column prop="status" label="状态" width="100" align="center">
                            <template #default="{ row }">
                                <el-switch
                                    :model-value="row.status === 1"
                                    @change="handleToggleSceneStatus(row)"
                                />
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="150" fixed="right">
                            <template #default="{ row }">
                                <el-button link type="primary" @click="handleEditScene(row)">配置</el-button>
                                <el-button link type="success" @click="handleBindTemplate(row)">绑定模板</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-tab-pane>

                <!-- 发送记录 -->
                <el-tab-pane label="发送记录" name="log">
                    <div class="toolbar">
                        <el-select
                            v-model="logSearch.send_status"
                            placeholder="发送状态"
                            style="width: 120px"
                            clearable
                            @change="fetchLogList"
                        >
                            <el-option label="待发送" :value="0" />
                            <el-option label="发送成功" :value="1" />
                            <el-option label="发送失败" :value="2" />
                        </el-select>
                        <el-select
                            v-model="logSearch.scene"
                            placeholder="选择场景"
                            style="width: 160px; margin-left: 8px"
                            clearable
                            @change="fetchLogList"
                        >
                            <el-option
                                v-for="item in sceneOptions"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                        <el-date-picker
                            v-model="logSearch.dateRange"
                            type="daterange"
                            start-placeholder="开始日期"
                            end-placeholder="结束日期"
                            value-format="YYYY-MM-DD"
                            style="margin-left: 8px"
                            @change="fetchLogList"
                        />
                    </div>

                    <el-table :data="logList" v-loading="logLoading" border>
                        <el-table-column prop="id" label="ID" width="80" />
                        <el-table-column label="用户" width="140">
                            <template #default="{ row }">
                                {{ row.user_info?.nickname || '未知用户' }}
                            </template>
                        </el-table-column>
                        <el-table-column prop="scene_desc" label="场景" width="140" />
                        <el-table-column prop="business_type" label="业务类型" width="100" />
                        <el-table-column prop="send_status_desc" label="发送状态" width="100" align="center">
                            <template #default="{ row }">
                                <el-tag :type="getSendStatusType(row.send_status)">
                                    {{ row.send_status_desc }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="error_msg" label="错误信息" min-width="150" show-overflow-tooltip />
                        <el-table-column prop="create_time" label="创建时间" width="170" />
                        <el-table-column label="操作" width="120" fixed="right">
                            <template #default="{ row }">
                                <el-button link type="primary" @click="handleViewLog(row)">详情</el-button>
                                <el-button
                                    v-if="row.send_status === 2"
                                    link
                                    type="warning"
                                    @click="handleRetryLog(row)"
                                >重试</el-button>
                            </template>
                        </el-table-column>
                    </el-table>

                    <div class="pagination">
                        <el-pagination
                            v-model:current-page="logPager.page"
                            v-model:page-size="logPager.size"
                            :total="logPager.total"
                            :page-sizes="[10, 20, 50, 100]"
                            layout="total, sizes, prev, pager, next"
                            @size-change="fetchLogList"
                            @current-change="fetchLogList"
                        />
                    </div>
                </el-tab-pane>
            </el-tabs>
        </el-card>

        <!-- 模板编辑弹窗 -->
        <el-dialog
            v-model="templateDialogVisible"
            :title="templateForm.id ? '编辑模板' : '添加模板'"
            width="600px"
        >
            <el-form
                ref="templateFormRef"
                :model="templateForm"
                :rules="templateRules"
                label-width="100px"
            >
                <el-form-item label="模板ID" prop="template_id">
                    <el-input v-model="templateForm.template_id" placeholder="微信小程序模板ID" />
                </el-form-item>
                <el-form-item label="模板名称" prop="name">
                    <el-input v-model="templateForm.name" placeholder="模板名称" />
                </el-form-item>
                <el-form-item label="模板标题" prop="title">
                    <el-input v-model="templateForm.title" placeholder="模板标题（可选）" />
                </el-form-item>
                <el-form-item label="使用场景" prop="scene">
                    <el-select v-model="templateForm.scene" placeholder="选择使用场景" style="width: 100%">
                        <el-option
                            v-for="item in sceneOptions"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="关键词" prop="keywords">
                    <el-input v-model="templateForm.keywords" placeholder="关键词，逗号分隔" />
                </el-form-item>
                <el-form-item label="状态" prop="status">
                    <el-switch v-model="templateForm.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <el-input-number v-model="templateForm.sort" :min="0" :max="9999" />
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input v-model="templateForm.remark" type="textarea" :rows="3" placeholder="备注说明" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="templateDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitTemplate" :loading="templateSubmitting">确定</el-button>
            </template>
        </el-dialog>

        <!-- 场景配置弹窗 -->
        <el-dialog v-model="sceneDialogVisible" title="场景配置" width="600px">
            <el-form
                ref="sceneFormRef"
                :model="sceneForm"
                label-width="100px"
            >
                <el-form-item label="场景标识">
                    <el-input v-model="sceneForm.scene" disabled />
                </el-form-item>
                <el-form-item label="场景名称" prop="name">
                    <el-input v-model="sceneForm.name" placeholder="场景名称" />
                </el-form-item>
                <el-form-item label="场景描述" prop="description">
                    <el-input v-model="sceneForm.description" type="textarea" :rows="2" placeholder="场景描述" />
                </el-form-item>
                <el-form-item label="触发事件" prop="trigger_event">
                    <el-input v-model="sceneForm.trigger_event" placeholder="触发事件名称" />
                </el-form-item>
                <el-form-item label="数据映射">
                    <el-input
                        v-model="sceneForm.data_mapping_text"
                        type="textarea"
                        :rows="6"
                        placeholder='请输入JSON对象，例如 {"thing1":"staff_name"}'
                    />
                    <div class="form-tip">
                        示例：{"thing1":"staff_name","time2":"schedule_date","thing3":"time_slot_desc"}
                    </div>
                </el-form-item>
                <el-form-item label="跳转页面" prop="page_path">
                    <el-input v-model="sceneForm.page_path" placeholder="小程序跳转页面路径" />
                </el-form-item>
                <el-form-item label="发送方式" prop="is_auto">
                    <el-radio-group v-model="sceneForm.is_auto">
                        <el-radio :label="1">自动发送</el-radio>
                        <el-radio :label="0">手动触发</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="延迟发送" prop="delay_seconds">
                    <el-input-number v-model="sceneForm.delay_seconds" :min="0" :max="86400" />
                    <span class="ml-2">秒</span>
                </el-form-item>
                <el-form-item label="状态" prop="status">
                    <el-switch v-model="sceneForm.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="sceneDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitScene" :loading="sceneSubmitting">确定</el-button>
            </template>
        </el-dialog>

        <!-- 绑定模板弹窗 -->
        <el-dialog v-model="bindDialogVisible" title="绑定模板" width="500px">
            <el-form label-width="100px">
                <el-form-item label="场景名称">
                    <span>{{ currentScene?.name }}</span>
                </el-form-item>
                <el-form-item label="选择模板">
                    <el-select v-model="bindTemplateId" placeholder="选择模板" style="width: 100%">
                        <el-option
                            v-for="item in templateList"
                            :key="item.template_id"
                            :label="item.name"
                            :value="item.template_id"
                        />
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="bindDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="handleSubmitBind" :loading="bindSubmitting">确定</el-button>
            </template>
        </el-dialog>

        <!-- 日志详情弹窗 -->
        <el-dialog v-model="logDetailVisible" title="发送记录详情" width="600px">
            <el-descriptions :column="2" border v-if="currentLog">
                <el-descriptions-item label="记录ID">{{ currentLog.id }}</el-descriptions-item>
                <el-descriptions-item label="用户ID">{{ currentLog.user_id }}</el-descriptions-item>
                <el-descriptions-item label="OpenID">{{ currentLog.openid }}</el-descriptions-item>
                <el-descriptions-item label="模板ID">{{ currentLog.template_id }}</el-descriptions-item>
                <el-descriptions-item label="场景">{{ currentLog.scene_desc }}</el-descriptions-item>
                <el-descriptions-item label="业务类型">{{ currentLog.business_type }}</el-descriptions-item>
                <el-descriptions-item label="业务ID">{{ currentLog.business_id }}</el-descriptions-item>
                <el-descriptions-item label="发送状态">
                    <el-tag :type="getSendStatusType(currentLog.send_status)">
                        {{ currentLog.send_status_desc }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="跳转页面" :span="2">{{ currentLog.page }}</el-descriptions-item>
                <el-descriptions-item label="错误码">{{ currentLog.error_code || '-' }}</el-descriptions-item>
                <el-descriptions-item label="错误信息">{{ currentLog.error_msg || '-' }}</el-descriptions-item>
                <el-descriptions-item label="创建时间">{{ currentLog.create_time }}</el-descriptions-item>
                <el-descriptions-item label="发送时间">{{ currentLog.send_time || '-' }}</el-descriptions-item>
                <el-descriptions-item label="消息内容" :span="2">
                    <pre class="content-pre">{{ JSON.stringify(currentLog.content, null, 2) }}</pre>
                </el-descriptions-item>
            </el-descriptions>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
    getTemplateList,
    addTemplate,
    editTemplate,
    deleteTemplate,
    toggleTemplateStatus,
    getSceneOptions,
    getSceneList,
    getSceneDetail,
    editScene,
    toggleSceneStatus,
    bindTemplate,
    getMessageLogList,
    getMessageLogDetail,
    retryMessageLog,
    getStatistics
} from '@/api/subscribe'

// 标签页
const activeTab = ref('template')

// 统计数据
const statistics = ref<any>({})

// 场景选项
const sceneOptions = ref<any[]>([])

// 模板列表
const templateList = ref<any[]>([])
const templateLoading = ref(false)
const templateSearch = reactive({ name: '', scene: '' })
const templatePager = reactive({ page: 1, size: 20, total: 0 })

// 场景列表
const sceneList = ref<any[]>([])
const sceneLoading = ref(false)

// 日志列表
const logList = ref<any[]>([])
const logLoading = ref(false)
const logSearch = reactive<any>({ send_status: '', scene: '', dateRange: null })
const logPager = reactive({ page: 1, size: 20, total: 0 })

// 模板表单
const templateDialogVisible = ref(false)
const templateFormRef = ref()
const templateSubmitting = ref(false)
const templateForm = reactive<any>({
    id: null,
    template_id: '',
    name: '',
    title: '',
    scene: '',
    keywords: '',
    status: 1,
    sort: 0,
    remark: ''
})
const templateRules = {
    template_id: [{ required: true, message: '请输入模板ID', trigger: 'blur' }],
    name: [{ required: true, message: '请输入模板名称', trigger: 'blur' }],
    scene: [{ required: true, message: '请选择使用场景', trigger: 'change' }]
}

// 场景表单
const sceneDialogVisible = ref(false)
const sceneFormRef = ref()
const sceneSubmitting = ref(false)
const sceneForm = reactive<any>({
    id: null,
    scene: '',
    name: '',
    description: '',
    trigger_event: '',
    data_mapping_text: '',
    page_path: '',
    is_auto: 1,
    delay_seconds: 0,
    status: 1,
    sort: 0
})

// 绑定模板
const bindDialogVisible = ref(false)
const bindSubmitting = ref(false)
const bindTemplateId = ref('')
const currentScene = ref<any>(null)

// 日志详情
const logDetailVisible = ref(false)
const currentLog = ref<any>(null)

// 初始化
onMounted(() => {
    fetchSceneOptions()
    fetchStatistics()
    fetchTemplateList()
})

// 获取场景选项
const fetchSceneOptions = async () => {
    const res = await getSceneOptions()
    sceneOptions.value = res || []
}

// 获取统计数据
const fetchStatistics = async () => {
    const res = await getStatistics()
    statistics.value = res || {}
}

// 获取模板列表
const fetchTemplateList = async () => {
    templateLoading.value = true
    try {
        const res = await getTemplateList({
            page_no: templatePager.page,
            page_size: templatePager.size,
            ...templateSearch
        })
        templateList.value = res.lists || []
        templatePager.total = res.count || 0
    } finally {
        templateLoading.value = false
    }
}

// 获取场景列表
const fetchSceneList = async () => {
    sceneLoading.value = true
    try {
        const res = await getSceneList()
        sceneList.value = res.lists || []
    } finally {
        sceneLoading.value = false
    }
}

// 获取日志列表
const fetchLogList = async () => {
    logLoading.value = true
    try {
        const params: any = {
            page_no: logPager.page,
            page_size: logPager.size
        }
        if (logSearch.send_status !== '') params.send_status = logSearch.send_status
        if (logSearch.scene) params.scene = logSearch.scene
        if (logSearch.dateRange) {
            params.start_date = logSearch.dateRange[0]
            params.end_date = logSearch.dateRange[1]
        }
        const res = await getMessageLogList(params)
        logList.value = res.lists || []
        logPager.total = res.count || 0
    } finally {
        logLoading.value = false
    }
}

// 标签页切换
const handleTabChange = (name: string) => {
    if (name === 'template') {
        fetchTemplateList()
    } else if (name === 'scene') {
        fetchSceneList()
    } else if (name === 'log') {
        fetchLogList()
    }
}

// 添加模板
const handleAddTemplate = () => {
    Object.assign(templateForm, {
        id: null,
        template_id: '',
        name: '',
        title: '',
        scene: '',
        keywords: '',
        status: 1,
        sort: 0,
        remark: ''
    })
    templateDialogVisible.value = true
}

// 编辑模板
const handleEditTemplate = (row: any) => {
    Object.assign(templateForm, row)
    templateDialogVisible.value = true
}

// 删除模板
const handleDeleteTemplate = async (row: any) => {
    await ElMessageBox.confirm('确定要删除该模板吗？', '提示', { type: 'warning' })
    await deleteTemplate(row.id)
    ElMessage.success('删除成功')
    fetchTemplateList()
}

// 切换模板状态
const handleToggleTemplateStatus = async (row: any) => {
    await toggleTemplateStatus(row.id)
    ElMessage.success('操作成功')
    fetchTemplateList()
}

// 提交模板
const handleSubmitTemplate = async () => {
    await templateFormRef.value?.validate()
    templateSubmitting.value = true
    try {
        if (templateForm.id) {
            await editTemplate(templateForm)
        } else {
            await addTemplate(templateForm)
        }
        ElMessage.success(templateForm.id ? '编辑成功' : '添加成功')
        templateDialogVisible.value = false
        fetchTemplateList()
    } finally {
        templateSubmitting.value = false
    }
}

// 编辑场景
const formatDataMapping = (mapping: any) => {
    if (!mapping || typeof mapping !== 'object' || Object.keys(mapping).length === 0) {
        return ''
    }
    try {
        return JSON.stringify(mapping, null, 2)
    } catch (e) {
        return ''
    }
}

const parseDataMapping = (mappingText: string) => {
    if (!mappingText || !mappingText.trim()) {
        return {}
    }
    try {
        const parsed = JSON.parse(mappingText)
        if (!parsed || typeof parsed !== 'object') {
            return null
        }
        return parsed
    } catch (e) {
        return null
    }
}

const handleEditScene = async (row: any) => {
    let sceneData = row
    try {
        const detail = await getSceneDetail(row.id)
        if (detail && detail.id) {
            sceneData = detail
        }
    } catch (e) {
        // 获取详情失败时使用列表数据
    }
    Object.assign(sceneForm, sceneData, {
        data_mapping_text: formatDataMapping(sceneData.data_mapping)
    })
    sceneDialogVisible.value = true
}

// 切换场景状态
const handleToggleSceneStatus = async (row: any) => {
    await toggleSceneStatus(row.id)
    ElMessage.success('操作成功')
    fetchSceneList()
}

// 提交场景配置
const handleSubmitScene = async () => {
    const dataMapping = parseDataMapping(sceneForm.data_mapping_text)
    if (dataMapping === null) {
        ElMessage.warning('数据映射JSON格式不正确')
        return
    }
    sceneSubmitting.value = true
    try {
        const payload = { ...sceneForm, data_mapping: dataMapping }
        delete payload.data_mapping_text
        await editScene(payload)
        ElMessage.success('配置成功')
        sceneDialogVisible.value = false
        fetchSceneList()
    } finally {
        sceneSubmitting.value = false
    }
}

// 绑定模板
const handleBindTemplate = (row: any) => {
    currentScene.value = row
    bindTemplateId.value = row.template_id || ''
    bindDialogVisible.value = true
}

// 提交绑定
const handleSubmitBind = async () => {
    if (!bindTemplateId.value) {
        ElMessage.warning('请选择模板')
        return
    }
    bindSubmitting.value = true
    try {
        await bindTemplate({
            scene_id: currentScene.value.id,
            template_id: bindTemplateId.value
        })
        ElMessage.success('绑定成功')
        bindDialogVisible.value = false
        fetchSceneList()
    } finally {
        bindSubmitting.value = false
    }
}

// 查看日志详情
const handleViewLog = async (row: any) => {
    const res = await getMessageLogDetail(row.id)
    currentLog.value = res
    logDetailVisible.value = true
}

// 重试发送
const handleRetryLog = async (row: any) => {
    await ElMessageBox.confirm('确定要重试发送该消息吗？', '提示', { type: 'warning' })
    await retryMessageLog(row.id)
    ElMessage.success('重试成功')
    fetchLogList()
}

// 获取发送状态样式
const getSendStatusType = (status: number) => {
    const map: Record<number, string> = {
        0: 'info',
        1: 'success',
        2: 'danger'
    }
    return map[status] || 'info'
}
</script>

<style scoped lang="scss">
.subscribe-container {
    padding: 16px;
}

.stat-card {
    .stat-item {
        text-align: center;
        padding: 10px 0;
    }
    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #409eff;
    }
    .stat-label {
        font-size: 14px;
        color: #909399;
        margin-top: 8px;
    }
    &.success .stat-value {
        color: #67c23a;
    }
    &.danger .stat-value {
        color: #f56c6c;
    }
    &.info .stat-value {
        color: #909399;
    }
}

.toolbar {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
}

.pagination {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
}

.content-pre {
    margin: 0;
    padding: 8px;
    background: #f5f7fa;
    border-radius: 4px;
    font-size: 12px;
    max-height: 200px;
    overflow: auto;
}

.form-tip {
    margin-top: 6px;
    font-size: 12px;
    color: #909399;
    line-height: 1.4;
}

.mb-4 {
    margin-bottom: 16px;
}

.ml-2 {
    margin-left: 8px;
}
</style>
