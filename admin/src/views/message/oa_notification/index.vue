<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-alert
                type="info"
                title="服务号未关注或未绑定时不发送推送，业务消息保留在站内。"
                :closable="false"
                show-icon
            />
            <div class="mt-4 flex items-center gap-4">
                <span>公众号主通道</span>
                <el-switch v-model="oaConfig.enabled" :active-value="1" :inactive-value="0" @change="saveConfig" />
                <span class="text-secondary">关闭时仅保留站内消息</span>
                <el-button type="primary" plain @click="testVisible = true">发送测试</el-button>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-tabs v-model="activeTab">
                <el-tab-pane label="模板配置" name="templates">
                    <el-table :data="templatePager.lists" v-loading="templatePager.loading" size="large">
                        <el-table-column label="场景" prop="scene" min-width="150" />
                        <el-table-column label="接收者" prop="audience" min-width="100">
                            <template #default="{ row }">{{ row.audience === 'staff' ? '工作人员' : '用户' }}</template>
                        </el-table-column>
                        <el-table-column label="模板ID" prop="template_id" min-width="220" />
                        <el-table-column label="状态" min-width="90">
                            <template #default="{ row }">
                                <el-tag :type="row.status == 1 ? 'success' : 'info'">{{ row.status == 1 ? '启用' : '停用' }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" fixed="right" width="100">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="openEdit(row.id)">编辑</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <pagination v-model="templatePager" @change="getTemplateLists" />
                </el-tab-pane>

                <el-tab-pane label="发送日志" name="logs">
                    <el-table :data="logPager.lists" v-loading="logPager.loading" size="large">
                        <el-table-column label="场景" prop="scene" min-width="140" />
                        <el-table-column label="接收者" prop="audience" min-width="100">
                            <template #default="{ row }">{{ row.audience === 'staff' ? '工作人员' : '用户' }}</template>
                        </el-table-column>
                        <el-table-column label="业务" min-width="150">
                            <template #default="{ row }">{{ row.business_type }} #{{ row.business_id }}</template>
                        </el-table-column>
                        <el-table-column label="状态" prop="send_status" min-width="90">
                            <template #default="{ row }">{{ sendStatusText(row.send_status) }}</template>
                        </el-table-column>
                        <el-table-column label="操作" width="80">
                            <template #default="{ row }">
                                <el-button v-if="Number(row.send_status) === 2" type="primary" link
                                    v-perms="['notification.oaNotification/retry']" @click="retryLog(row.id)">重试</el-button>
                            </template>
                        </el-table-column>
                        <el-table-column label="错误信息" prop="error_msg" min-width="220" show-overflow-tooltip />
                        <el-table-column label="创建时间" prop="create_time" min-width="160" />
                    </el-table>
                    <pagination v-model="logPager" @change="getLogLists" />
                </el-tab-pane>

                <el-tab-pane label="业务事件" name="events">
                    <p class="mb-3">查看待处理、已处理和跳过的业务通知，包括未关联接收账号的事项。服务号实际送达结果见发送日志。</p>
                    <el-button class="mb-3" @click="getEventLists">刷新</el-button>
                    <el-table :data="eventPager.lists" v-loading="eventPager.loading">
                        <el-table-column label="事件" prop="event" min-width="220" />
                        <el-table-column label="内容" prop="title" min-width="180" />
                        <el-table-column label="用户" prop="user_id" />
                        <el-table-column label="接收身份" prop="audience" />
                        <el-table-column label="状态"><template #default="{ row }">{{ ['待处理', '已处理', '已跳过'][Number(row.status)] || '失败' }}</template></el-table-column>
                        <el-table-column label="原因" prop="error" min-width="240" />
                        <el-table-column label="操作"><template #default="{ row }"><el-button v-if="Number(row.status) === 3" v-perms="['notification.oaNotification/retry']" link @click="retryEvent(row.id)">重试</el-button></template></el-table-column>
                    </el-table>
                    <pagination v-model="eventPager" @change="getEventLists" />
                </el-tab-pane>

                <el-tab-pane label="绑定状态" name="followers">
                    <el-table :data="followerPager.lists" v-loading="followerPager.loading" size="large">
                        <el-table-column label="用户ID" prop="user_id" width="100" />
                        <el-table-column label="平台用户" prop="nickname" min-width="130" />
                        <el-table-column label="后台账号" min-width="160"><template #default="{ row }">{{ row.admin_account || '普通客户' }}{{ row.admin_disabled ? '（已停用）' : '' }}</template></el-table-column>
                        <el-table-column label="绑定状态" width="100"><template #default="{ row }">{{ row.bound ? '已绑定' : '未绑定' }}</template></el-table-column>
                        <el-table-column label="公众号OpenID" prop="openid" min-width="240" />
                        <el-table-column label="关注状态" prop="follow_status" width="100">
                            <template #default="{ row }">{{ row.follow_status == 1 ? '已关注' : '已取消' }}</template>
                        </el-table-column>
                        <el-table-column label="最近事件" prop="last_event_time" min-width="160" />
                    </el-table>
                    <pagination v-model="followerPager" @change="getFollowerLists" />
                </el-tab-pane>
            </el-tabs>
        </el-card>

        <popup ref="popupRef" title="编辑公众号模板" :async="true" width="620px" @confirm="handleSubmit">
            <el-form ref="formRef" :model="formData" label-width="110px">
                <el-form-item label="场景"><span>{{ formData.scene }}</span></el-form-item>
                <el-form-item label="接收者"><span>{{ formData.audience === 'staff' ? '工作人员' : '用户' }}</span></el-form-item>
                <el-form-item label="模板ID" required><el-input v-model="formData.template_id" /></el-form-item>
                <el-form-item label="字段映射" required>
                    <el-input v-model="formData.mappingText" type="textarea" :rows="6" placeholder="例如：{&quot;keyword1&quot;:&quot;order_sn&quot;}" />
                </el-form-item>
                <el-form-item label="跳转路径"><el-input v-model="formData.page_path" /></el-form-item>
                <el-form-item label="状态"><el-switch v-model="formData.status" :active-value="1" :inactive-value="0" /></el-form-item>
                <el-form-item label="备注"><el-input v-model="formData.remark" type="textarea" :rows="2" /></el-form-item>
            </el-form>
        </popup>

        <el-dialog v-model="testVisible" title="发送公众号测试通知" width="520px">
            <el-form :model="testForm" label-width="100px">
                <el-form-item label="用户ID" required><el-input-number v-model="testForm.user_id" :min="1" class="!w-full" /></el-form-item>
                <el-form-item label="场景" required><el-input v-model="testForm.scene" placeholder="例如 order_confirm" /></el-form-item>
                <el-form-item label="接收者">
                    <el-radio-group v-model="testForm.audience"><el-radio value="user">用户</el-radio><el-radio value="staff">服务人员</el-radio></el-radio-group>
                </el-form-item>
                <el-form-item label="业务ID"><el-input-number v-model="testForm.business_id" :min="0" class="!w-full" /></el-form-item>
                <el-form-item label="测试数据" required><el-input v-model="testForm.dataText" type="textarea" :rows="5" /></el-form-item>
            </el-form>
            <template #footer><el-button @click="testVisible = false">取消</el-button><el-button type="primary" @click="sendTest">发送</el-button></template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="oaNotification">
import eventRequest from '@/utils/request'
import { ElMessage } from 'element-plus'
import { retryOaNotification, oaNotificationConfig, oaNotificationFollowerLists, oaNotificationLogLists, oaNotificationTemplateDetail, oaNotificationTemplateLists, oaNotificationTestSend, saveOaNotificationConfig, setOaNotificationTemplate } from '@/api/message'
import { usePaging } from '@/hooks/usePaging'
import Popup from '@/components/popup/index.vue'

const activeTab = ref('templates')
const retryEvent = async (id: number) => {
    await eventRequest.post({ url: '/notification.oaNotification/eventRetry', params: { id } })
    await getEventLists()
}
const oaConfig = reactive({ enabled: 0, channel_mode: 'oa_only' })
const testVisible = ref(false)
const testForm = reactive({ user_id: 0, scene: 'order_confirm', audience: 'user', business_id: 0, dataText: '{\n  "order_sn": "TEST",\n  "status_text": "测试通知"\n}' })
const { pager: templatePager, getLists: getTemplateLists } = usePaging({ fetchFun: oaNotificationTemplateLists })
const { pager: logPager, getLists: getLogLists } = usePaging({ fetchFun: oaNotificationLogLists })
const { pager: eventPager, getLists: getEventLists } = usePaging({ fetchFun: (params: any) => eventRequest.get({ url: '/notification.oaNotification/eventList', params }) })
const { pager: followerPager, getLists: getFollowerLists } = usePaging({ fetchFun: oaNotificationFollowerLists })
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const formRef = shallowRef()
const formData = reactive<any>({ id: 0, scene: '', audience: 'user', template_id: '', mappingText: '{}', page_path: '', status: 0, remark: '' })

watch(activeTab, (tab) => {
    if (tab === 'logs') getLogLists()
    if (tab === 'followers') getFollowerLists()
    if (tab === 'events') getEventLists()
})

const retryLog = async (id: number) => {
    await retryOaNotification({ id })
    getLogLists()
}

const sendStatusText = (status: number) => ({ 0: '待发送', 1: '已发送', 2: '失败', 3: '发送中', 4: '已跳过' }[status] || '未知')

const openEdit = async (id: number) => {
    const data: any = await oaNotificationTemplateDetail({ id })
    Object.assign(formData, data, { mappingText: JSON.stringify(data.data_mapping || {}, null, 2) })
    popupRef.value?.open()
}

const handleSubmit = async () => {
    let mapping: Record<string, string>
    try {
        mapping = JSON.parse(formData.mappingText || '{}')
    } catch (_error) {
        ElMessage.error('字段映射不是合法JSON')
        return
    }
    await setOaNotificationTemplate({ ...formData, data_mapping: mapping })
    popupRef.value?.close()
    getTemplateLists()
}

const saveConfig = async () => {
    await saveOaNotificationConfig(oaConfig)
    ElMessage.success('公众号通知开关已保存')
}

const sendTest = async () => {
    let data: Record<string, string>
    try {
        data = JSON.parse(testForm.dataText || '{}')
    } catch (_error) {
        ElMessage.error('测试数据不是合法JSON')
        return
    }
    await oaNotificationTestSend({ ...testForm, data })
    ElMessage.success('测试通知已提交')
    testVisible.value = false
    getLogLists()
}

oaNotificationConfig().then((data: any) => Object.assign(oaConfig, data))
getTemplateLists()
</script>
