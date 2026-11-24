<template>
    <el-drawer
        :model-value="modelValue"
        title="客户 360° 档案"
        size="780px"
        destroy-on-close
        @update:model-value="emit('update:modelValue', $event)"
    >
        <div v-loading="loading" class="customer-drawer">
            <template v-if="detail">
                <!-- 顶部摘要名片 -->
                <div class="customer-drawer__hero">
                    <div class="customer-drawer__hero-main">
                        <div class="customer-drawer__title-row">
                            <h3 class="customer-drawer__name">{{ detail.customer_name || '-' }}</h3>
                            <el-tag :type="getStatusType(detail.customer_status)">
                                {{ detail.customer_status_desc || '-' }}
                            </el-tag>
                            <el-tag :type="getIntentionType(detail.intention_level)" effect="plain">
                                {{ detail.intention_level || 'D' }}级 · {{ detail.intention_level_desc || '-' }}
                            </el-tag>
                            <el-tag v-if="detail.days_to_wedding !== null && detail.days_to_wedding !== undefined" type="warning" effect="plain">
                                {{ detail.days_to_wedding >= 0 ? `距婚期 ${detail.days_to_wedding} 天` : `婚期已过 ${Math.abs(detail.days_to_wedding)} 天` }}
                            </el-tag>
                        </div>

                        <div class="customer-drawer__contacts">
                            <span>
                                手机号：{{ detail.customer_mobile || '-' }}
                                <el-button
                                    v-if="detail.customer_mobile"
                                    type="primary"
                                    link
                                    size="small"
                                    @click="copyText(detail.customer_mobile, '手机号')"
                                >
                                    复制
                                </el-button>
                            </span>
                            <span>
                                微信号：{{ detail.customer_wechat || '-' }}
                                <el-button
                                    v-if="detail.customer_wechat"
                                    type="primary"
                                    link
                                    size="small"
                                    @click="copyText(detail.customer_wechat, '微信号')"
                                >
                                    复制
                                </el-button>
                            </span>
                            <span>
                                专属顾问：<strong>{{ detail.advisor?.advisor_name || '未分配' }}</strong>
                            </span>
                        </div>

                        <div class="customer-drawer__metrics">
                            <span>跟进 {{ Number(detail.follow_count || 0) }} 次</span>
                            <span>未跟进 {{ Number(detail.days_no_follow || 0) }} 天</span>
                            <span>下次跟进：{{ detail.next_follow_time_text || '未设置' }}</span>
                            <span>真实订单：{{ Number(detail.real_order_count || 0) }} 笔 / ¥{{ Number(detail.real_total_amount || 0).toFixed(2) }}</span>
                        </div>
                    </div>

                    <div class="customer-drawer__hero-actions">
                        <el-button
                            v-perms="['crm.customer/edit']"
                            size="small"
                            @click="emit('edit', detail)"
                        >
                            编辑资料
                        </el-button>
                        <el-button
                            v-if="canTransfer"
                            v-perms="['crm.customer/transferAdvisor']"
                            size="small"
                            @click="emit('transfer', detail)"
                        >
                            转移顾问
                        </el-button>
                    </div>
                </div>

                <!-- 主体标签页 -->
                <el-tabs v-model="activeTab" class="customer-drawer__tabs">
                    <el-tab-pane label="跟进时间轴" name="timeline">
                        <!-- 内嵌快速跟进条 -->
                        <div v-if="detail.advisor_id" v-perms="['crm.followRecord/add']" class="customer-drawer__quick-box">
                            <div class="customer-drawer__quick-row">
                                <el-select v-model="quickForm.follow_type" size="small" class="w-[105px]">
                                    <el-option
                                        v-for="item in followOptions.follow_type_options"
                                        :key="item.value"
                                        :label="item.label"
                                        :value="item.value"
                                    />
                                </el-select>
                                <el-select v-model="quickForm.follow_result" size="small" class="w-[115px]">
                                    <el-option
                                        v-for="item in followOptions.follow_result_options"
                                        :key="item.value"
                                        :label="item.label"
                                        :value="item.value"
                                    />
                                </el-select>
                                <el-select v-model="quickForm.intention_after" size="small" class="w-[115px]">
                                    <el-option
                                        v-for="item in followOptions.intention_options"
                                        :key="item.value"
                                        :label="item.label"
                                        :value="item.value"
                                    />
                                </el-select>
                                <el-date-picker
                                    v-model="quickForm.next_follow_time"
                                    type="datetime"
                                    size="small"
                                    value-format="YYYY-MM-DD HH:mm:ss"
                                    placeholder="下次跟进时间"
                                    class="flex-1 min-w-[175px]"
                                />
                                <el-button size="small" plain @click="setQuickNextDays(3)">3天后</el-button>
                                <el-button size="small" plain @click="setQuickNextDays(7)">7天后</el-button>
                            </div>
                            <div class="customer-drawer__quick-input">
                                <el-input
                                    v-model="quickForm.follow_content"
                                    type="textarea"
                                    :rows="2"
                                    maxlength="2000"
                                    placeholder="快速记录沟通要点、意向变化或下一步动作…"
                                />
                                <div class="customer-drawer__quick-foot">
                                    <el-checkbox v-model="quickImportant">标为重要</el-checkbox>
                                    <el-button
                                        type="primary"
                                        size="small"
                                        :loading="quickSubmitting"
                                        @click="handleQuickFollowSubmit"
                                    >
                                        提交跟进
                                    </el-button>
                                </div>
                            </div>
                        </div>
                        <el-alert
                            v-else
                            type="info"
                            :closable="false"
                            title="该客户尚未分配顾问，请先点击右上角「转移顾问」指定顾问后再记录跟进。"
                            class="mb-3"
                        />

                        <el-timeline v-if="detail.follow_records?.length" class="mt-3">
                            <el-timeline-item
                                v-for="rec in detail.follow_records"
                                :key="rec.id"
                                :timestamp="rec.create_time_text"
                                :type="rec.is_important ? 'danger' : 'primary'"
                                placement="top"
                            >
                                <div class="customer-drawer__rec-card">
                                    <div class="customer-drawer__rec-head">
                                        <div class="customer-drawer__rec-tags">
                                            <el-tag size="small" effect="plain">{{ rec.follow_type_desc }}</el-tag>
                                            <el-tag size="small" :type="getResultType(rec.follow_result)">
                                                {{ rec.follow_result_desc }}
                                            </el-tag>
                                            <el-tag v-if="rec.is_important" size="small" type="danger">重要</el-tag>
                                            <span class="text-xs text-tx-secondary">
                                                意向：{{ rec.intention_before || '-' }} → {{ rec.intention_after || '-' }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-tx-secondary">
                                            {{ rec.advisor_name || '-' }} · {{ rec.admin_name || '系统' }}
                                        </span>
                                    </div>
                                    <p class="customer-drawer__rec-content">{{ rec.follow_content }}</p>
                                    <div v-if="rec.next_follow_time_text || rec.next_follow_content" class="customer-drawer__rec-next">
                                        下次计划：{{ rec.next_follow_time_text || '-' }}
                                        <template v-if="rec.next_follow_content"> · {{ rec.next_follow_content }}</template>
                                    </div>
                                    <div v-if="rec.attachments?.length" class="customer-drawer__rec-imgs">
                                        <el-image
                                            v-for="(img, idx) in rec.attachments"
                                            :key="idx"
                                            :src="img"
                                            :preview-src-list="rec.attachments"
                                            :initial-index="idx"
                                            fit="cover"
                                            class="customer-drawer__rec-img"
                                            preview-teleported
                                        />
                                    </div>
                                </div>
                            </el-timeline-item>
                        </el-timeline>
                        <el-empty v-else description="暂无跟进记录" :image-size="72" />
                    </el-tab-pane>

                    <el-tab-pane label="婚礼档案" name="profile">
                        <el-descriptions :column="2" border size="small">
                            <el-descriptions-item label="所在城市">
                                {{ [detail.city, detail.district].filter(Boolean).join(' / ') || '-' }}
                            </el-descriptions-item>
                            <el-descriptions-item label="计划婚期">{{ detail.wedding_date || '-' }}</el-descriptions-item>
                            <el-descriptions-item label="婚礼场地">{{ detail.wedding_venue || '-' }}</el-descriptions-item>
                            <el-descriptions-item label="婚礼预算">
                                {{ detail.budget_range || (Number(detail.wedding_budget || 0) > 0 ? `¥${detail.wedding_budget}` : '-') }}
                            </el-descriptions-item>
                            <el-descriptions-item label="来源渠道">{{ detail.source_channel_desc || '-' }}</el-descriptions-item>
                            <el-descriptions-item label="来源详情">{{ detail.source_detail_text || detail.source_detail || '-' }}</el-descriptions-item>
                            <el-descriptions-item label="服务需求" :span="2">
                                <el-tag
                                    v-for="item in detail.service_needs || []"
                                    :key="item"
                                    size="small"
                                    class="mr-1 mb-1"
                                >
                                    {{ item }}
                                </el-tag>
                                <span v-if="!detail.service_needs?.length">-</span>
                            </el-descriptions-item>
                            <el-descriptions-item label="客户标签" :span="2">
                                <el-tag
                                    v-for="item in detail.tags || []"
                                    :key="item"
                                    size="small"
                                    type="info"
                                    effect="plain"
                                    class="mr-1 mb-1"
                                >
                                    {{ item }}
                                </el-tag>
                                <span v-if="!detail.tags?.length">-</span>
                            </el-descriptions-item>
                            <el-descriptions-item label="首次联系">{{ detail.first_contact_time_text || '-' }}</el-descriptions-item>
                            <el-descriptions-item label="分配时间">{{ detail.assign_time_text || '-' }}</el-descriptions-item>
                            <el-descriptions-item v-if="detail.customer_status === 4" label="流失原因" :span="2">
                                {{ detail.loss_reason || '-' }}
                            </el-descriptions-item>
                            <el-descriptions-item label="客户备注" :span="2">
                                {{ detail.remark || '-' }}
                            </el-descriptions-item>
                        </el-descriptions>
                    </el-tab-pane>

                    <el-tab-pane :label="`关联订单 (${detail.orders?.length || 0})`" name="orders">
                        <el-table v-if="detail.orders?.length" :data="detail.orders" size="small" border>
                            <el-table-column label="订单号" prop="order_sn" min-width="150" />
                            <el-table-column label="服务日期" prop="service_date" width="110" />
                            <el-table-column label="总额 / 实付" min-width="130">
                                <template #default="{ row }">
                                    ¥{{ Number(row.pay_amount || 0).toFixed(2) }} /
                                    <span class="text-success">¥{{ Number(row.paid_amount || 0).toFixed(2) }}</span>
                                </template>
                            </el-table-column>
                            <el-table-column label="订单状态" prop="order_status_desc" width="95" />
                            <el-table-column label="支付状态" prop="pay_status_desc" width="95" />
                            <el-table-column label="下单时间" prop="create_time_text" min-width="150" />
                        </el-table>
                        <el-empty v-else description="暂无关联订单" :image-size="72" />
                    </el-tab-pane>

                    <el-tab-pane label="流转与预警" name="logs">
                        <div class="customer-drawer__sub-title">流失预警记录</div>
                        <el-table v-if="detail.loss_warnings?.length" :data="detail.loss_warnings" size="small" border class="mb-4">
                            <el-table-column label="类型 / 等级" min-width="140">
                                <template #default="{ row }">
                                    {{ row.warning_type_desc }}（{{ row.warning_level_desc }}）
                                </template>
                            </el-table-column>
                            <el-table-column label="原因" prop="warning_reason" min-width="160" />
                            <el-table-column label="状态" prop="warning_status_desc" width="85" />
                            <el-table-column label="操作" width="120">
                                <template #default="{ row }">
                                    <template v-if="Number(row.warning_status) === 0">
                                        <el-button
                                            v-perms="['crm.lossWarning/handle']"
                                            type="primary"
                                            link
                                            size="small"
                                            @click="handleResolveWarning(row.id, 'handle')"
                                        >
                                            处理
                                        </el-button>
                                        <el-button
                                            v-perms="['crm.lossWarning/ignore']"
                                            type="info"
                                            link
                                            size="small"
                                            @click="handleResolveWarning(row.id, 'ignore')"
                                        >
                                            忽略
                                        </el-button>
                                    </template>
                                    <span v-else class="text-xs text-tx-secondary">{{ row.handle_remark || '已结束' }}</span>
                                </template>
                            </el-table-column>
                        </el-table>
                        <el-empty v-else description="暂无流失预警" :image-size="56" class="mb-4" />

                        <div class="customer-drawer__sub-title">顾问分配与流转日志</div>
                        <el-table v-if="detail.assign_logs?.length" :data="detail.assign_logs" size="small" border>
                            <el-table-column label="流转方向" min-width="160">
                                <template #default="{ row }">
                                    {{ row.from_advisor_name }} → {{ row.to_advisor_name }}
                                </template>
                            </el-table-column>
                            <el-table-column label="类型" prop="assign_type_desc" width="95" />
                            <el-table-column label="原因" prop="assign_reason" min-width="150" />
                            <el-table-column label="操作人" prop="admin_name" width="95" />
                            <el-table-column label="时间" prop="create_time_text" min-width="150" />
                        </el-table>
                        <el-empty v-else description="暂无分配日志" :image-size="56" />
                    </el-tab-pane>
                </el-tabs>
            </template>
        </div>
    </el-drawer>
</template>

<script lang="ts" setup>
import { ElMessage } from 'element-plus'

import { customerDetail } from '@/api/crm/customer'
import { followRecordAdd, followRecordOptions } from '@/api/crm/followRecord'
import { lossWarningHandle, lossWarningIgnore } from '@/api/crm/lossWarning'

const props = defineProps<{
    modelValue: boolean
    customerId: number
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'edit', customer: any): void
    (e: 'transfer', customer: any): void
    (e: 'updated'): void
}>()

const loading = ref(false)
const activeTab = ref('timeline')
const detail = ref<any>(null)
const quickSubmitting = ref(false)
const quickImportant = ref(false)

const followOptions = reactive({
    follow_type_options: [] as Array<{ value: number | string; label: string }>,
    follow_result_options: [] as Array<{ value: number | string; label: string }>,
    intention_options: [] as Array<{ value: number | string; label: string }>
})

const quickForm = reactive({
    follow_type: 1,
    follow_result: 1,
    intention_after: 'D',
    next_follow_time: '',
    follow_content: ''
})

const canTransfer = computed(() =>
    [1, 2].includes(Number(detail.value?.customer_status || 0))
)

const getStatusType = (status: number): 'success' | 'warning' | 'primary' | 'danger' | 'info' => {
    const map: Record<number, 'success' | 'warning' | 'primary' | 'danger' | 'info'> = {
        1: 'success',
        2: 'warning',
        3: 'primary',
        4: 'danger',
        5: 'info'
    }
    return map[Number(status)] || 'info'
}

const getIntentionType = (level: string): 'danger' | 'warning' | 'primary' | 'info' => {
    const map: Record<string, 'danger' | 'warning' | 'primary' | 'info'> = {
        A: 'danger',
        B: 'warning',
        C: 'primary',
        D: 'info'
    }
    return map[String(level)] || 'info'
}

const getResultType = (result: number): 'success' | 'warning' | 'primary' | 'danger' | 'info' => {
    const map: Record<number, 'success' | 'warning' | 'primary' | 'danger' | 'info'> = {
        1: 'primary',
        2: 'success',
        3: 'warning',
        4: 'success',
        5: 'danger'
    }
    return map[Number(result)] || 'info'
}

const copyText = async (val: string, label: string) => {
    try {
        await navigator.clipboard.writeText(val)
        ElMessage.success(`${label}已复制`)
    } catch {
        ElMessage.info(val)
    }
}

const setQuickNextDays = (days: number) => {
    const d = new Date()
    d.setDate(d.getDate() + days)
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    quickForm.next_follow_time = `${y}-${m}-${day} 10:00:00`
}

const loadDetail = async () => {
    if (!props.customerId) return
    loading.value = true
    try {
        const [data, optData] = await Promise.all([
            customerDetail({ id: props.customerId }),
            followOptions.follow_type_options.length ? Promise.resolve(null) : followRecordOptions()
        ])
        if (optData) {
            followOptions.follow_type_options = optData.follow_type_options || []
            followOptions.follow_result_options = optData.follow_result_options || []
            followOptions.intention_options = optData.intention_options || []
        }
        detail.value = data || null
        if (data?.intention_level) {
            quickForm.intention_after = String(data.intention_level)
        }
    } finally {
        loading.value = false
    }
}

watch(
    () => [props.modelValue, props.customerId] as const,
    ([open, id]) => {
        if (open && id > 0) {
            activeTab.value = 'timeline'
            quickForm.follow_content = ''
            quickForm.next_follow_time = ''
            quickImportant.value = false
            void loadDetail()
        }
    }
)

const handleQuickFollowSubmit = async () => {
    const content = quickForm.follow_content.trim()
    if (!content) {
        ElMessage.warning('请填写跟进内容')
        return
    }
    quickSubmitting.value = true
    try {
        await followRecordAdd({
            customer_id: Number(detail.value.id),
            follow_type: Number(quickForm.follow_type || 1),
            follow_result: Number(quickForm.follow_result || 1),
            intention_after: quickForm.intention_after,
            next_follow_time: quickForm.next_follow_time || '',
            follow_content: content,
            is_important: quickImportant.value ? 1 : 0
        })
        ElMessage.success('跟进已记录')
        quickForm.follow_content = ''
        quickForm.next_follow_time = ''
        quickImportant.value = false
        await loadDetail()
        emit('updated')
    } finally {
        quickSubmitting.value = false
    }
}

const handleResolveWarning = async (warningId: number, action: 'handle' | 'ignore') => {
    if (action === 'handle') {
        await lossWarningHandle({ id: warningId, remark: '客户档案抽屉内手动处理' })
        ElMessage.success('预警已处理')
    } else {
        await lossWarningIgnore({ id: warningId, remark: '客户档案抽屉内忽略' })
        ElMessage.success('预警已忽略')
    }
    await loadDetail()
    emit('updated')
}

defineExpose({
    refresh: loadDetail
})
</script>

<style lang="scss" scoped>
.customer-drawer {
    min-height: 240px;

    &__hero {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 18px;
        border-radius: 10px;
        background: var(--el-fill-color-light);
        border: 1px solid var(--el-border-color-lighter);
    }

    &__hero-main {
        flex: 1;
        min-width: 0;
    }

    &__title-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }

    &__name {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__contacts,
    &__metrics {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 18px;
        margin-top: 8px;
        font-size: 12px;
        color: var(--el-text-color-regular);
    }

    &__hero-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: none;
    }

    &__tabs {
        margin-top: 14px;
    }

    &__quick-box {
        padding: 12px;
        border-radius: 8px;
        background: var(--el-fill-color-lighter);
        border: 1px solid var(--el-border-color-lighter);
    }

    &__quick-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    &__quick-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 8px;
    }

    &__rec-card {
        padding: 10px 12px;
        border-radius: 8px;
        background: var(--el-bg-color-page);
        border: 1px solid var(--el-border-color-lighter);
    }

    &__rec-head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 8px;
        align-items: center;
    }

    &__rec-tags {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
    }

    &__rec-content {
        margin: 8px 0 0;
        font-size: 13px;
        line-height: 1.6;
        color: var(--el-text-color-primary);
        white-space: pre-wrap;
    }

    &__rec-next {
        margin-top: 6px;
        font-size: 12px;
        color: var(--el-color-primary);
    }

    &__rec-imgs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }

    &__rec-img {
        width: 56px;
        height: 56px;
        border-radius: 6px;
    }

    &__sub-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--el-text-color-primary);
        margin-bottom: 8px;
    }
}
</style>
