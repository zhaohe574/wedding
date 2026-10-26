<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-alert
                type="info"
                title="微信小程序订阅消息必须由用户在小程序前端产生手势交互（点击按钮）时主动授权后，方可由服务端推送至微信聊天列表「服务通知」。未授权或额度耗尽时，业务消息始终安全保留在小程序站内通知中心。"
                :closable="false"
                show-icon
            />
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-tabs v-model="activeTab">
                <el-tab-pane label="小程序订阅模板" name="templates">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-sm text-secondary">
                            已配置 {{ templatePager.lists.filter((i: any) => i.status == 1 && i.template_id).length }} / {{ templatePager.lists.length }} 个小程序订阅模板。需在微信小程序后台（功能 > 订阅消息）选用公共模板后填入模板ID。
                        </div>
                        <div>
                            <el-button type="primary" plain @click="openTest()">发送小程序测试</el-button>
                        </div>
                    </div>

                    <el-table :data="templatePager.lists" v-loading="templatePager.loading" size="large">
                        <el-table-column label="场景代码" prop="scene" min-width="150">
                            <template #default="{ row }">
                                <span class="font-mono font-medium">{{ row.scene }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column label="通知名称" prop="title" min-width="150" />
                        <el-table-column label="小程序模板ID (priTmplId)" min-width="220">
                            <template #default="{ row }">
                                <el-tag v-if="row.template_id" type="success" class="font-mono">
                                    {{ row.template_id }}
                                </el-tag>
                                <el-tag v-else type="danger">未配置</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="小程序跳转页面" prop="page_path" min-width="220" show-overflow-tooltip />
                        <el-table-column label="状态" min-width="90">
                            <template #default="{ row }">
                                <el-tag :type="row.status == 1 ? 'success' : 'info'">
                                    {{ row.status == 1 ? '启用' : '停用' }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="说明" prop="remark" min-width="200" show-overflow-tooltip />
                        <el-table-column label="操作" fixed="right" width="140">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="openEdit(row.id)">配置</el-button>
                                <el-button type="primary" link @click="openTest(row)">测试</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <pagination v-model="templatePager" @change="getTemplateLists" />
                </el-tab-pane>

                <el-tab-pane label="发送日志" name="logs">
                    <el-table :data="logPager.lists" v-loading="logPager.loading" size="large">
                        <el-table-column label="场景" prop="scene" min-width="140" />
                        <el-table-column label="用户ID" prop="user_id" width="90" />
                        <el-table-column label="小程序OpenID" prop="openid" min-width="200" show-overflow-tooltip />
                        <el-table-column label="模板ID" prop="template_id" min-width="180" show-overflow-tooltip />
                        <el-table-column label="状态" min-width="90">
                            <template #default="{ row }">
                                <el-tag :type="row.send_status == 1 ? 'success' : 'danger'">
                                    {{ row.send_status == 1 ? '成功' : (row.send_status == 0 ? '待发送' : '失败') }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="返回码" prop="last_error_code" width="100">
                            <template #default="{ row }">
                                <el-tag :type="row.last_error_code === '0' ? 'success' : (row.last_error_code === '43101' ? 'warning' : 'danger')" size="small">
                                    {{ row.last_error_code || '-' }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="说明/错误信息" prop="error_msg" min-width="220" show-overflow-tooltip />
                        <el-table-column label="创建时间" prop="create_time" min-width="160" />
                    </el-table>
                    <pagination v-model="logPager" @change="getLogLists" />
                </el-tab-pane>
            </el-tabs>
        </el-card>

        <!-- 编辑弹窗 -->
        <popup ref="popupRef" title="配置小程序订阅模板" :async="true" width="620px" @confirm="handleSubmit">
            <el-form ref="formRef" :model="formData" label-width="110px">
                <el-form-item label="场景代码">
                    <span class="font-mono text-gray-600">{{ formData.scene }}</span>
                </el-form-item>
                <el-form-item label="通知名称" required>
                    <el-input v-model="formData.title" placeholder="如：新人问卷填写通知" />
                </el-form-item>
                <el-form-item label="小程序模板ID" required>
                    <el-input v-model="formData.template_id" placeholder="微信小程序后台申请的 priTmplId" />
                </el-form-item>
                <el-form-item label="字段映射" required>
                    <div class="w-full">
                        <el-input v-model="formData.mappingText" type="textarea" :rows="6" placeholder="例如：{&quot;thing1&quot;:&quot;title&quot;,&quot;time2&quot;:&quot;service_date&quot;}" />
                        <div class="text-xs text-gray-400 mt-1">
                            键名为小程序模板参数（如 thing1, time2, character_string3），值为业务字段名
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="跳转页面">
                    <el-input v-model="formData.page_path" placeholder="如：packages/pages/couple_questionnaire/detail" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="排序">
                    <el-input-number v-model="formData.sort" :min="0" />
                </el-form-item>
                <el-form-item label="备注说明">
                    <el-input v-model="formData.remark" type="textarea" :rows="2" />
                </el-form-item>
            </el-form>
        </popup>

        <!-- 测试弹窗 -->
        <el-dialog v-model="testVisible" title="发送小程序订阅消息测试" width="560px">
            <el-form :model="testForm" label-width="100px">
                <el-form-item label="目标用户ID" required>
                    <el-input-number v-model="testForm.user_id" :min="1" class="!w-full" />
                    <div class="text-xs text-gray-400 mt-1">
                        需为微信小程序已登录用户且在小程序端点击授权过该模板
                    </div>
                </el-form-item>
                <el-form-item label="场景" required>
                    <el-select
                        v-model="testForm.scene"
                        placeholder="请选择测试场景"
                        class="!w-full"
                        @change="handleSceneChange"
                    >
                        <el-option
                            v-for="item in MNP_SCENE_PRESETS"
                            :key="item.scene"
                            :label="item.name"
                            :value="item.scene"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="业务ID">
                    <el-input-number v-model="testForm.business_id" :min="0" class="!w-full" />
                </el-form-item>
                <el-form-item label="测试数据" required>
                    <div class="w-full">
                        <div class="flex justify-between items-center mb-1 text-xs text-secondary">
                            <span>JSON 参数：</span>
                            <el-button type="primary" link size="small" @click="resetTestData">重置默认</el-button>
                        </div>
                        <el-input v-model="testForm.dataText" type="textarea" :rows="8" />
                    </div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="testVisible = false">取消</el-button>
                <el-button type="primary" @click="sendTest">发送测试</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="mnpNotification">
import { ref, reactive, shallowRef, watch } from 'vue'
import { ElMessage } from 'element-plus'
import {
    mnpNotificationTemplateLists,
    mnpNotificationTemplateDetail,
    setMnpNotificationTemplate,
    mnpNotificationLogLists,
    mnpNotificationTestSend
} from '@/api/message'
import { usePaging } from '@/hooks/usePaging'
import Popup from '@/components/popup/index.vue'

const activeTab = ref('templates')
const { pager: templatePager, getLists: getTemplateLists } = usePaging({ fetchFun: mnpNotificationTemplateLists })
const { pager: logPager, getLists: getLogLists } = usePaging({ fetchFun: mnpNotificationLogLists })

const popupRef = shallowRef<InstanceType<typeof Popup>>()
const formRef = shallowRef()
const formData = reactive<any>({
    id: 0,
    scene: '',
    title: '',
    template_id: '',
    mappingText: '{}',
    page_path: '',
    status: 1,
    sort: 0,
    remark: ''
})

const testVisible = ref(false)
const testForm = reactive({
    user_id: 1,
    scene: 'questionnaire_update',
    business_id: 0,
    dataText: '{}'
})

const MNP_SCENE_PRESETS = [
    {
        scene: 'questionnaire_update',
        name: '新人问卷填写通知',
        data: {
            title: '婚礼仪式资料问卷',
            staff_name: '专属策划师',
            service_date: '2026-10-01 09:00',
            remark_text: '请新人补充仪式策划信息'
        }
    },
    {
        scene: 'activity_update',
        name: '活动报名成功通知',
        data: {
            title: '婚礼品鉴沙龙活动',
            ticket_name: 'VIP免费入场券',
            service_date: '2026-10-05 14:00',
            remark_text: '凭核销码到店入场'
        }
    },
    {
        scene: 'ticket_update',
        name: '工单处理进度通知',
        data: {
            ticket_sn: 'TK20260922001',
            title: '售后工单进度流转',
            service_date: '2026-09-22 15:00',
            remark_text: '客服人员已处理完毕'
        }
    },
    {
        scene: 'order_update',
        name: '订单状态更新通知',
        data: {
            order_sn: 'ORD20260922001',
            package_name: '浪漫法式婚礼套系',
            service_date: '2026-10-01 09:00',
            total_amount: '5888.00'
        }
    }
]

const handleSceneChange = (scene: string) => {
    const preset = MNP_SCENE_PRESETS.find((item) => item.scene === scene)
    if (preset) {
        testForm.dataText = JSON.stringify(preset.data, null, 2)
    }
}

const resetTestData = () => {
    handleSceneChange(testForm.scene)
}

const openEdit = async (id: number) => {
    const data: any = await mnpNotificationTemplateDetail({ id })
    Object.assign(formData, data, {
        mappingText: JSON.stringify(data.data_mapping || {}, null, 2)
    })
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
    await setMnpNotificationTemplate({ ...formData, data_mapping: mapping })
    ElMessage.success('小程序模板配置已保存')
    popupRef.value?.close()
    getTemplateLists()
}

const openTest = (row?: any) => {
    testVisible.value = true
    if (row && row.scene) {
        testForm.scene = row.scene
        handleSceneChange(row.scene)
    } else {
        testForm.scene = 'questionnaire_update'
        handleSceneChange('questionnaire_update')
    }
}

const sendTest = async () => {
    let data: Record<string, string>
    try {
        data = JSON.parse(testForm.dataText || '{}')
    } catch (_error) {
        ElMessage.error('测试数据不是合法JSON')
        return
    }
    try {
        const res: any = await mnpNotificationTestSend({ ...testForm, data })
        ElMessage.success(res?.msg || '测试通知已提交')
        testVisible.value = false
        getLogLists()
    } catch (e: any) {
        ElMessage.error(e?.message || '发送测试失败')
    }
}

watch(activeTab, (tab) => {
    if (tab === 'templates') getTemplateLists()
    if (tab === 'logs') getLogLists()
})

getTemplateLists()
</script>
