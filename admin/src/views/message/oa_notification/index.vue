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
                <el-button type="primary" plain @click="openTest()">发送测试</el-button>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-tabs v-model="activeTab">
                <el-tab-pane label="模板配置" name="templates">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-sm text-secondary">
                            已启用 {{ templatePager.lists.filter((i: any) => i.status == 1).length }} / {{ templatePager.lists.length }} 个场景模板。支持手动配置或从微信服务号一键拉取。
                        </div>
                        <div class="flex gap-2">
                            <el-button type="success" :loading="syncLoading" @click="handleSyncWechat">从服务号一键同步绑定</el-button>
                            <el-button type="primary" plain @click="openWechatModal">查看服务号已有模板</el-button>
                        </div>
                    </div>
                    <el-table :data="templatePager.lists" v-loading="templatePager.loading" size="large">

                        <el-table-column label="场景" prop="scene" min-width="150" />
                        <el-table-column label="接收者" prop="audience" min-width="100">
                            <template #default="{ row }">{{ row.audience === 'staff' ? '工作人员' : '用户' }}</template>
                        </el-table-column>
                        <el-table-column label="模板ID" prop="template_id" min-width="220" />
                        <el-table-column label="说明/类目模板" prop="remark" min-width="200" show-overflow-tooltip />
                        <el-table-column label="状态" min-width="90">
                            <template #default="{ row }">
                                <el-tag :type="row.status == 1 ? 'success' : 'info'">{{ row.status == 1 ? '启用' : '停用' }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" fixed="right" width="130">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="openEdit(row.id)">编辑</el-button>
                                <el-button type="primary" link @click="openTest(row)">测试</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <pagination v-model="templatePager" @change="getTemplateLists" />
                </el-tab-pane>

                <el-tab-pane label="小程序订阅消息" name="mnp_templates">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-sm text-secondary">
                            已配置 {{ mnpTemplatePager.lists.filter((i: any) => i.status == 1 && i.template_id).length }} / {{ mnpTemplatePager.lists.length }} 个小程序订阅模板。需在微信小程序后台（功能 > 订阅消息）选用公共模板后填入模板ID。
                        </div>
                        <div>
                            <el-button type="primary" plain @click="openMnpTest()">发送小程序测试</el-button>
                        </div>
                    </div>

                    <el-table :data="mnpTemplatePager.lists" v-loading="mnpTemplatePager.loading" size="large">
                        <el-table-column label="场景代码" prop="scene" min-width="160">
                            <template #default="{ row }">
                                <span class="font-mono font-medium">{{ row.scene }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column label="通知名称" prop="title" min-width="160" />
                        <el-table-column label="小程序模板ID (priTmplId)" min-width="240">
                            <template #default="{ row }">
                                <el-tag v-if="row.template_id" type="success" class="font-mono">
                                    {{ row.template_id }}
                                </el-tag>
                                <el-tag v-else type="danger">未配置</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="跳转页面" prop="page_path" min-width="240" show-overflow-tooltip />
                        <el-table-column label="状态" min-width="90">
                            <template #default="{ row }">
                                <el-tag :type="row.status == 1 ? 'success' : 'info'">
                                    {{ row.status == 1 ? '启用' : '停用' }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="说明" prop="remark" min-width="220" show-overflow-tooltip />
                        <el-table-column label="操作" fixed="right" width="140">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="openMnpEdit(row.id)">配置</el-button>
                                <el-button type="primary" link @click="openMnpTest(row)">测试</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <pagination v-model="mnpTemplatePager" @change="getMnpTemplateLists" />
                </el-tab-pane>

                <el-tab-pane label="服务号日志" name="logs">
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

                <el-tab-pane label="小程序日志" name="mnp_logs">
                    <el-table :data="mnpLogPager.lists" v-loading="mnpLogPager.loading" size="large">
                        <el-table-column label="场景" prop="scene" min-width="150" />
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
                    <pagination v-model="mnpLogPager" @change="getMnpLogLists" />
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

        <el-dialog v-model="testVisible" title="发送公众号测试通知" width="560px">
            <el-form :model="testForm" label-width="100px">
                <el-form-item label="用户ID" required>
                    <el-input-number v-model="testForm.user_id" :min="1" class="!w-full" />
                </el-form-item>
                <el-form-item label="场景" required>
                    <el-select
                        v-model="testForm.scene"
                        placeholder="请选择测试场景"
                        class="!w-full"
                        filterable
                        @change="handleSceneChange"
                    >
                        <el-option
                            v-for="item in sceneOptions"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        >
                            <div class="flex items-center justify-between w-full">
                                <span>{{ item.name }}</span>
                                <div class="flex items-center gap-2">
                                    <el-tag size="small" :type="item.status === 1 ? 'success' : 'info'">
                                        {{ item.status === 1 ? '已启用' : '未启用' }}
                                    </el-tag>
                                    <span class="text-xs text-gray-400 font-mono">{{ item.value }}</span>
                                </div>
                            </div>
                        </el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="接收者">
                    <el-radio-group v-model="testForm.audience">
                        <el-radio value="user">用户</el-radio>
                        <el-radio value="staff">服务人员</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="业务ID">
                    <el-input-number v-model="testForm.business_id" :min="0" class="!w-full" />
                </el-form-item>
                <el-form-item label="测试数据" required>
                    <div class="w-full">
                        <div class="flex justify-between items-center mb-1 text-xs text-secondary">
                            <span>JSON 参数（根据场景已自动载入）：</span>
                            <el-button type="primary" link size="small" @click="resetTestData">重置默认</el-button>
                        </div>
                        <el-input v-model="testForm.dataText" type="textarea" :rows="8" />
                    </div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="testVisible = false">取消</el-button>
                <el-button type="primary" @click="sendTest">发送</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="wechatModalVisible" title="微信服务号已添加模板列表" width="800px">
            <div v-loading="wechatModalLoading">
                <el-alert
                    type="info"
                    title="以下为调用微信服务号官方接口 (get_all_private_template) 查询到的当前服务号下已添加并审核通过的模板。"
                    :closable="false"
                    class="mb-3"
                />
                <el-table :data="wechatTemplates" max-height="400" size="default" empty-text="未查询到模板，或请先在微信公众平台添加模板">
                    <el-table-column label="标题" prop="title" min-width="150" />
                    <el-table-column label="模板ID" prop="template_id" min-width="240" show-overflow-tooltip />
                    <el-table-column label="行业" min-width="120">
                        <template #default="{ row }">{{ row.primary_industry }} / {{ row.deputy_industry }}</template>
                    </el-table-column>
                    <el-table-column label="操作" width="100">
                        <template #default="{ row }">
                            <el-button type="primary" link @click="copyTemplateId(row.template_id)">复制ID</el-button>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
            <template #footer>
                <el-button @click="wechatModalVisible = false">关闭</el-button>
                <el-button type="success" :loading="syncLoading" @click="handleSyncWechat">自动匹配并绑定所有场景</el-button>
            </template>
        </el-dialog>

        <!-- 小程序订阅消息模板编辑 -->
        <popup ref="mnpPopupRef" title="配置小程序订阅模板" :async="true" width="620px" @confirm="handleMnpSubmit">
            <el-form :model="mnpFormData" label-width="110px">
                <el-form-item label="场景代码">
                    <span class="font-mono text-gray-600">{{ mnpFormData.scene }}</span>
                </el-form-item>
                <el-form-item label="通知名称" required>
                    <el-input v-model="mnpFormData.title" placeholder="如：新人问卷填写通知" />
                </el-form-item>
                <el-form-item label="小程序模板ID" required>
                    <el-input v-model="mnpFormData.template_id" placeholder="微信小程序后台申请的 priTmplId" />
                </el-form-item>
                <el-form-item label="字段映射" required>
                    <div class="w-full">
                        <el-input v-model="mnpFormData.mappingText" type="textarea" :rows="6" placeholder="例如：{&quot;thing1&quot;:&quot;title&quot;,&quot;time2&quot;:&quot;service_date&quot;}" />
                        <div class="text-xs text-gray-400 mt-1">
                            键名为小程序模板参数（如 thing1, time2, character_string3），值为业务字段名
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="跳转页面">
                    <el-input v-model="mnpFormData.page_path" placeholder="如：packages/pages/couple_questionnaire/detail" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-switch v-model="mnpFormData.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
                <el-form-item label="排序">
                    <el-input-number v-model="mnpFormData.sort" :min="0" />
                </el-form-item>
                <el-form-item label="备注说明">
                    <el-input v-model="mnpFormData.remark" type="textarea" :rows="2" />
                </el-form-item>
            </el-form>
        </popup>

        <!-- 小程序订阅测试发送 -->
        <el-dialog v-model="mnpTestVisible" title="发送小程序订阅消息测试" width="560px">
            <el-form :model="mnpTestForm" label-width="100px">
                <el-form-item label="目标用户ID" required>
                    <el-input-number v-model="mnpTestForm.user_id" :min="1" class="!w-full" />
                    <div class="text-xs text-gray-400 mt-1">
                        需为微信小程序已登录用户且在小程序端点击授权过该模板
                    </div>
                </el-form-item>
                <el-form-item label="场景" required>
                    <el-select
                        v-model="mnpTestForm.scene"
                        placeholder="请选择测试场景"
                        class="!w-full"
                        @change="handleMnpSceneChange"
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
                    <el-input-number v-model="mnpTestForm.business_id" :min="0" class="!w-full" />
                </el-form-item>
                <el-form-item label="测试数据" required>
                    <div class="w-full">
                        <div class="flex justify-between items-center mb-1 text-xs text-secondary">
                            <span>JSON 参数：</span>
                            <el-button type="primary" link size="small" @click="resetMnpTestData">重置默认</el-button>
                        </div>
                        <el-input v-model="mnpTestForm.dataText" type="textarea" :rows="8" />
                    </div>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="mnpTestVisible = false">取消</el-button>
                <el-button type="primary" @click="sendMnpTest">发送测试</el-button>
            </template>
        </el-dialog>
    </div>

</template>

<script lang="ts" setup name="oaNotification">
import eventRequest from '@/utils/request'
import { ElMessage } from 'element-plus'
import {
    retryOaNotification,
    oaNotificationConfig,
    oaNotificationFollowerLists,
    oaNotificationLogLists,
    oaNotificationTemplateDetail,
    oaNotificationTemplateLists,
    oaNotificationTestSend,
    saveOaNotificationConfig,
    setOaNotificationTemplate,
    getWechatPrivateTemplates,
    syncWechatTemplates,
    mnpNotificationTemplateLists,
    mnpNotificationTemplateDetail,
    setMnpNotificationTemplate,
    mnpNotificationLogLists,
    mnpNotificationTestSend
} from '@/api/message'
import { usePaging } from '@/hooks/usePaging'
import Popup from '@/components/popup/index.vue'

const activeTab = ref('templates')
const syncLoading = ref(false)
const wechatModalVisible = ref(false)
const wechatModalLoading = ref(false)
const wechatTemplates = ref<any[]>([])

const retryEvent = async (id: number) => {
    await eventRequest.post({ url: '/notification.oaNotification/eventRetry', params: { id } })
    await getEventLists()
}
const oaConfig = reactive({ enabled: 0, channel_mode: 'oa_only' })
const testVisible = ref(false)

const SCENE_PRESETS = [
    {
        scene: 'order_update',
        name: '订单生成成功通知',
        audience: 'user',
        data: {
            staff_name: '专属策划师',
            package_name: '浪漫法式婚礼套系',
            service_date: '2026-10-01 09:00',
            hotel_name: '喜来登大酒店',
            total_amount: '5888.00',
            order_sn: 'WED20261001001'
        }
    },
    {
        scene: 'staff_order',
        name: '接单成功通知 (服务人员)',
        audience: 'staff',
        data: {
            order_sn: 'WED20261001001',
            order_time: '2026-09-22 14:00',
            package_name: '婚礼主持服务',
            service_date: '2026-10-01 09:00'
        }
    },
    {
        scene: 'staff_refund',
        name: '拒单通知 (服务人员)',
        audience: 'staff',
        data: {
            service_date: '2026-10-01 09:00',
            hotel_name: '喜来登大酒店',
            staff_name: '张主持',
            reason: '档期冲突'
        }
    },
    {
        scene: 'ticket_update',
        name: '工单处理提醒 (用户)',
        audience: 'user',
        data: {
            ticket_sn: 'TK20260922001',
            package_name: '浪漫法式婚礼套系',
            service_date: '2026-10-01 09:00',
            hotel_name: '喜来登大酒店'
        }
    },
    {
        scene: 'staff_aftersale',
        name: '工单处理提醒 (服务人员)',
        audience: 'staff',
        data: {
            ticket_sn: 'TK20260922001',
            package_name: '浪漫法式婚礼套系',
            service_date: '2026-10-01 09:00',
            hotel_name: '喜来登大酒店'
        }
    },
    {
        scene: 'staff_schedule',
        name: '团队成员预约/档期锁定',
        audience: 'staff',
        data: {
            user_name: '李女士',
            staff_name: '金牌司仪',
            service_date: '2026-10-01 09:00',
            total_amount: '5888.00',
            hotel_name: '喜来登大酒店'
        }
    },
    {
        scene: 'staff_change',
        name: '顾客改期提醒 (服务人员)',
        audience: 'staff',
        data: {
            user_name: '李女士',
            order_sn: 'WED20261001001',
            category_name: '摄影',
            old_service_date: '2026-10-01 09:00',
            new_service_date: '2026-10-08 09:00'
        }
    },
    {
        scene: 'change_result',
        name: '订单申诉/改期结果 (用户)',
        audience: 'user',
        data: {
            order_sn: 'WED20261001001',
            apply_time: '2026-09-22 10:00',
            status_text: '审核通过'
        }
    },
    {
        scene: 'settlement_update',
        name: '收款成功/结算打款通知',
        audience: 'staff',
        data: {
            title: '婚庆服务结算打款',
            total_amount: '3200.00',
            pay_time: '2026-09-22 15:30',
            store_name: '格林社婚礼会所',
            service_date: '2026-10-01 09:00'
        }
    },
    {
        scene: 'waitlist_release',
        name: '候补释放预约通知',
        audience: 'user',
        data: {
            user_name: '张女士',
            staff_name: '金牌司仪',
            service_date: '2026-10-01 09:00',
            total_amount: '5888.00',
            hotel_name: '喜来登大酒店'
        }
    },
    {
        scene: 'waitlist_expired',
        name: '候补超时取消通知',
        audience: 'user',
        data: {
            order_sn: 'WED20261001001',
            user_name: '李女士',
            service_date: '2026-10-01 09:00',
            hotel_name: '喜来登大酒店',
            cancel_time: '2026-09-22 16:00'
        }
    },
    {
        scene: 'schedule_remind',
        name: '服务时间预约成功通知',
        audience: 'user',
        data: {
            service_date: '2026-10-01 09:00',
            staff_name: '专属策划师'
        }
    },
    {
        scene: 'questionnaire_update',
        name: '婚礼需求问卷/资料审核',
        audience: 'user',
        data: {
            contact_name: '王先生',
            package_name: '婚礼仪式定制需求',
            service_date: '2026-10-01 09:00',
            submit_time: '2026-09-22 12:00'
        }
    },
    {
        scene: 'activity_update',
        name: '活动预约/报名成功通知',
        audience: 'user',
        data: {
            activity_name: '秋季备婚品鉴沙龙',
            service_date: '2026-09-28 14:00',
            staff_name: '婚礼策划组',
            hotel_name: '旗舰体验中心'
        }
    },
    {
        scene: 'staff_pause',
        name: '订单申诉结果通知 (停单申诉)',
        audience: 'staff',
        data: {
            order_sn: 'WED20261001001',
            apply_time: '2026-09-22 10:00',
            status_text: '审核通过'
        }
    },
    {
        scene: 'staff_internal',
        name: '订阅模板/内部通知',
        audience: 'staff',
        data: {
            content: '您有一条新的婚礼执行协同事项，请及时前往后台查看。'
        }
    }
]

const testForm = reactive({
    user_id: 1,
    scene: 'order_update',
    audience: 'user',
    business_id: 0,
    dataText: JSON.stringify(SCENE_PRESETS[0].data, null, 2)
})

const sceneOptions = computed(() => {
    return SCENE_PRESETS.map((preset) => {
        const match = templatePager.lists.find((item: any) => item.scene === preset.scene && item.audience === preset.audience)
        const status = match ? Number(match.status) : 0
        return {
            value: preset.scene,
            name: preset.name,
            label: `${preset.name} (${preset.scene})`,
            audience: preset.audience,
            status
        }
    })
})

const handleSceneChange = (sceneVal: string) => {
    const preset = SCENE_PRESETS.find((p) => p.scene === sceneVal)
    if (preset) {
        testForm.audience = preset.audience
        testForm.dataText = JSON.stringify(preset.data, null, 2)
    }
}

const resetTestData = () => {
    handleSceneChange(testForm.scene)
    ElMessage.info('已重置为当前场景的默认测试数据')
}

const openTest = (row?: any) => {
    testVisible.value = true
    if (row && row.scene) {
        testForm.scene = row.scene
        testForm.audience = row.audience || 'user'
        handleSceneChange(row.scene)
    } else if (testForm.scene) {
        handleSceneChange(testForm.scene)
    } else {
        testForm.scene = 'order_update'
        handleSceneChange('order_update')
    }
}
const { pager: templatePager, getLists: getTemplateLists } = usePaging({ fetchFun: oaNotificationTemplateLists })
const { pager: logPager, getLists: getLogLists } = usePaging({ fetchFun: oaNotificationLogLists })
const { pager: eventPager, getLists: getEventLists } = usePaging({ fetchFun: (params: any) => eventRequest.get({ url: '/notification.oaNotification/eventList', params }) })
const { pager: followerPager, getLists: getFollowerLists } = usePaging({ fetchFun: oaNotificationFollowerLists })
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const formRef = shallowRef()
const formData = reactive<any>({ id: 0, scene: '', audience: 'user', template_id: '', mappingText: '{}', page_path: '', status: 0, remark: '' })

// 小程序订阅通知管理
const { pager: mnpTemplatePager, getLists: getMnpTemplateLists } = usePaging({ fetchFun: mnpNotificationTemplateLists })
const { pager: mnpLogPager, getLists: getMnpLogLists } = usePaging({ fetchFun: mnpNotificationLogLists })
const mnpPopupRef = shallowRef<InstanceType<typeof Popup>>()
const mnpFormData = reactive<any>({ id: 0, scene: '', title: '', template_id: '', mappingText: '{}', page_path: '', status: 1, sort: 0, remark: '' })
const mnpTestVisible = ref(false)
const mnpTestForm = reactive({ user_id: 1, scene: 'questionnaire_update', business_id: 0, dataText: '{}' })

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

const handleMnpSceneChange = (scene: string) => {
    const preset = MNP_SCENE_PRESETS.find((item) => item.scene === scene)
    if (preset) {
        mnpTestForm.dataText = JSON.stringify(preset.data, null, 2)
    }
}

const resetMnpTestData = () => {
    handleMnpSceneChange(mnpTestForm.scene)
    ElMessage.info('已重置为当前场景的默认测试数据')
}

const openMnpEdit = async (id: number) => {
    const data: any = await mnpNotificationTemplateDetail({ id })
    Object.assign(mnpFormData, data, {
        mappingText: JSON.stringify(data.data_mapping || {}, null, 2)
    })
    mnpPopupRef.value?.open()
}

const handleMnpSubmit = async () => {
    let mapping: Record<string, string>
    try {
        mapping = JSON.parse(mnpFormData.mappingText || '{}')
    } catch (_error) {
        ElMessage.error('字段映射不是合法JSON')
        return
    }
    await setMnpNotificationTemplate({ ...mnpFormData, data_mapping: mapping })
    ElMessage.success('小程序模板配置已保存')
    mnpPopupRef.value?.close()
    getMnpTemplateLists()
}

const openMnpTest = (row?: any) => {
    mnpTestVisible.value = true
    if (row && row.scene) {
        mnpTestForm.scene = row.scene
        handleMnpSceneChange(row.scene)
    } else {
        mnpTestForm.scene = 'questionnaire_update'
        handleMnpSceneChange('questionnaire_update')
    }
}

const sendMnpTest = async () => {
    let data: Record<string, string>
    try {
        data = JSON.parse(mnpTestForm.dataText || '{}')
    } catch (_error) {
        ElMessage.error('测试数据不是合法JSON')
        return
    }
    try {
        const res: any = await mnpNotificationTestSend({ ...mnpTestForm, data })
        ElMessage.success(res?.msg || '测试通知已提交')
        mnpTestVisible.value = false
        getMnpLogLists()
    } catch (e: any) {
        ElMessage.error(e?.message || '发送测试失败')
    }
}

watch(activeTab, (tab) => {
    if (tab === 'logs') getLogLists()
    if (tab === 'followers') getFollowerLists()
    if (tab === 'events') getEventLists()
    if (tab === 'mnp_templates') getMnpTemplateLists()
    if (tab === 'mnp_logs') getMnpLogLists()
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

const openWechatModal = async () => {
    wechatModalVisible.value = true
    wechatModalLoading.value = true
    try {
        const res: any = await getWechatPrivateTemplates()
        wechatTemplates.value = res.lists || []
    } catch (e: any) {
        ElMessage.error(e?.message || '获取微信服务号模板失败')
    } finally {
        wechatModalLoading.value = false
    }
}

const handleSyncWechat = async () => {
    syncLoading.value = true
    try {
        const res: any = await syncWechatTemplates()
        ElMessage.success(res.msg || '服务号模板同步成功')
        wechatModalVisible.value = false
        getTemplateLists()
    } catch (e: any) {
        ElMessage.error(e?.message || '同步微信模板失败')
    } finally {
        syncLoading.value = false
    }
}

const copyTemplateId = (id: string) => {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(id)
    }
    ElMessage.success('模板ID已复制到剪贴板')
}

oaNotificationConfig().then((data: any) => Object.assign(oaConfig, data))

getTemplateLists()
getMnpTemplateLists()
</script>
