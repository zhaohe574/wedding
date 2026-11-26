<template>
    <el-drawer
        v-model="visible"
        title="问卷任务详情"
        size="min(760px, 94vw)"
        destroy-on-close
        class="task-detail-drawer"
    >
        <div v-loading="loading" class="task-detail">
            <el-alert
                v-if="loadError"
                type="error"
                :title="loadError"
                :closable="false"
                show-icon
                class="task-detail__alert"
            >
                <template #default>
                    <el-button type="primary" link @click="loadDetail()">重试</el-button>
                </template>
            </el-alert>

            <template v-if="detail">
                <el-alert
                    v-if="detail.send_error"
                    type="error"
                    :closable="false"
                    show-icon
                    class="task-detail__alert"
                    :title="`最近推送失败：${detail.send_error}`"
                >
                    <template #default>
                        <el-button
                            v-if="canSendTask"
                            type="primary"
                            link
                            :loading="sending"
                            @click="sendTask"
                        >
                            重试发送
                        </el-button>
                    </template>
                </el-alert>

                <section class="task-overview">
                    <div class="task-overview__sn">
                        <span class="task-overview__label">任务编号</span>
                        <span class="task-overview__value">{{ detail.task_sn || '-' }}</span>
                    </div>
                    <div class="task-overview__tags">
                        <el-tag
                            :type="taskStatusMeta.tagType"
                            effect="plain"
                            size="small"
                        >
                            {{ detail.status_desc || taskStatusMeta.label }}
                        </el-tag>
                        <el-tag
                            :type="sendStatusMeta.tagType"
                            effect="plain"
                            size="small"
                        >
                            {{ detail.send_status_desc || sendStatusMeta.label }}
                        </el-tag>
                        <el-tag type="info" effect="plain" size="small">
                            {{ Number(detail.version_no || 0) > 0 ? `v${detail.version_no}` : '无版本快照' }}
                        </el-tag>
                        <el-tag type="info" effect="plain" size="small">
                            {{ questionTotal }} 题
                        </el-tag>
                    </div>
                </section>

                <el-descriptions :column="2" border class="task-detail__block">
                    <el-descriptions-item label="订单编号">
                        {{ detail.order?.order_sn || detail.order_id || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item v-if="!hideStaffInfo" label="服务人员">
                        {{ detail.staff?.name || detail.staff_name || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="客户姓名">
                        {{ detail.user?.nickname || detail.order?.contact_name || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="客户手机号">
                        {{ detail.user?.mobile || detail.order?.contact_mobile || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="推送方式">
                        {{ detail.push_mode_desc || '-' }}<template
                            v-if="
                                detail.push_schedule_desc &&
                                detail.push_schedule_desc !== detail.push_mode_desc
                            "
                        >
                            · {{ detail.push_schedule_desc }}</template>
                    </el-descriptions-item>
                    <el-descriptions-item label="推送尝试次数">
                        {{ Number(detail.send_count || 0) }} 次
                    </el-descriptions-item>
                    <el-descriptions-item label="用户小程序入口" :span="2">
                        <div class="entry-path">
                            <span class="entry-path__text">{{ entryPath }}</span>
                            <el-button type="primary" link @click="copyQuestionnairePath(detail)">
                                一键复制
                            </el-button>
                        </div>
                    </el-descriptions-item>
                </el-descriptions>

                <section class="lifecycle">
                    <div class="section-title">生命周期</div>
                    <div class="lifecycle__grid">
                        <div v-for="node in lifecycleNodes" :key="node.label" class="lifecycle__card">
                            <span class="lifecycle__label">{{ node.label }}</span>
                            <span class="lifecycle__value">{{ node.value || '-' }}</span>
                        </div>
                    </div>
                </section>

                <section class="answer-section">
                    <div class="section-title">问卷题目与答案</div>
                    <el-empty
                        v-if="!answerGroups.length"
                        description="当前任务暂无可展示题目，请检查服务人员是否已发布问卷版本。"
                        :image-size="80"
                    />
                    <div
                        v-for="group in answerGroups"
                        :key="group.category"
                        class="answer-group"
                    >
                        <div class="answer-group__title">{{ group.category }}</div>
                        <article
                            v-for="item in group.items"
                            :key="item.question.id"
                            class="answer-card"
                        >
                            <header class="answer-card__head">
                                <span class="answer-card__no">
                                    Q{{ formatQuestionIndex(questionIndexMap.get(String(item.question.id))) }}
                                </span>
                                <el-tag size="small" effect="plain">
                                    {{ getQuestionTypeLabel(item.question.type) }}
                                </el-tag>
                                <el-tag
                                    v-if="Number(item.question.required) === 1"
                                    size="small"
                                    type="danger"
                                    effect="plain"
                                >
                                    必填
                                </el-tag>
                            </header>
                            <div class="answer-card__title">{{ item.question.title }}</div>
                            <div class="answer-card__value">
                                <span v-if="item.answer.empty" class="answer-card__empty">
                                    未填写
                                </span>
                                <template v-else-if="item.question.type === 'rating'">
                                    <span class="answer-card__rating">
                                        {{ item.answer.rating }} / 5 分
                                    </span>
                                    <el-rate
                                        disabled
                                        :model-value="item.answer.rating"
                                        class="answer-card__rate"
                                    />
                                </template>
                                <div v-else-if="item.answer.tags.length" class="answer-card__tags">
                                    <el-tag
                                        v-for="tag in item.answer.tags"
                                        :key="tag"
                                        size="small"
                                        effect="plain"
                                    >
                                        {{ tag }}
                                    </el-tag>
                                </div>
                                <span v-else>{{ item.answer.text }}</span>
                            </div>
                        </article>
                    </div>
                </section>
            </template>

            <el-empty
                v-else-if="!loading && !loadError"
                description="未查询到问卷任务详情，可能已删除或无权限查看。"
            />
        </div>

        <template #footer>
            <div class="drawer-footer">
                <div class="drawer-footer__actions">
                    <el-button v-if="detail" @click="copyQuestionnairePath(detail)">
                        复制小程序路径
                    </el-button>
                    <el-button @click="visible = false">关闭</el-button>
                    <el-button
                        v-if="detail && isTaskResendable(detail) && canSendTask"
                        type="primary"
                        :loading="sending"
                        @click="sendTask"
                    >
                        {{ getSendActionLabel(detail) }}
                    </el-button>
                </div>
            </div>
        </template>
    </el-drawer>
</template>

<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus'

import type { QuestionnaireTaskDetail } from '@/api/questionnaire'
import { coupleQuestionnaireSend, coupleQuestionnaireTaskDetail } from '@/api/questionnaire'
import { hasPermission } from '@/utils/perm'

import {
    buildUserQuestionnairePath,
    copyQuestionnairePath,
    extractErrorMessage,
    formatAnswerValue,
    getQuestionTypeLabel,
    getSendActionLabel,
    getSendStatusMeta,
    getTaskStatusMeta,
    groupQuestionsByCategory,
    isTaskResendable
} from '../shared'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        taskId: number
        fetchDetail?: (params: { id: number }) => Promise<any>
        sendTaskApi?: (params: { id: number }) => Promise<any>
        skipPerms?: boolean
        hideStaffInfo?: boolean
    }>(),
    {
        skipPerms: false,
        hideStaffInfo: false
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'sent'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const canSendTask = computed(
    () => props.skipPerms || hasPermission(['growth.coupleQuestionnaire/send'])
)

const loading = ref(false)
const sending = ref(false)
const loadError = ref('')
const detail = ref<QuestionnaireTaskDetail | null>(null)
let loadToken = 0

const taskStatusMeta = computed(() => getTaskStatusMeta(detail.value?.status))
const sendStatusMeta = computed(() => getSendStatusMeta(detail.value?.send_status))

const questionTotal = computed(() =>
    Array.isArray(detail.value?.questions) ? detail.value.questions.length : 0
)

const entryPath = computed(() => buildUserQuestionnairePath(detail.value?.id))

const answerList = computed<any[]>(() => {
    const answers = detail.value?.answer?.answers
    return Array.isArray(answers) ? answers : []
})

const questionIndexMap = computed(() => {
    const map = new Map<string, number>()
    const questions = Array.isArray(detail.value?.questions) ? detail.value.questions : []
    questions.forEach((question, index) => {
        map.set(String(question.id), index)
    })
    return map
})

const answerGroups = computed(() =>
    groupQuestionsByCategory(detail.value?.questions || []).map((group) => ({
        category: group.category,
        items: group.items.map((question) => ({
            question,
            answer: formatAnswerValue(question, answerList.value)
        }))
    }))
)

const lifecycleNodes = computed(() => {
    const task = detail.value
    return [
        { label: '创建时间', value: task?.create_time },
        { label: '计划推送', value: task?.planned_send_time },
        { label: '首次推送', value: task?.send_time },
        { label: '最近推送', value: task?.last_send_time },
        { label: '首次查看', value: task?.viewed_time },
        { label: '提交时间', value: task?.submitted_time || task?.submit_time },
        { label: '过期时间', value: task?.expire_time }
    ]
})

const formatQuestionIndex = (index?: number) =>
    String(Number(index ?? 0) + 1).padStart(2, '0')

const loadDetail = async () => {
    if (props.taskId <= 0) {
        return
    }
    const token = ++loadToken
    loading.value = true
    loadError.value = ''
    try {
        const res: any = await (props.fetchDetail || coupleQuestionnaireTaskDetail)({
            id: props.taskId
        })
        if (token !== loadToken) {
            return
        }
        const payload: any =
            res && typeof res === 'object' && !Array.isArray(res) && 'task_sn' in res
                ? res
                : (res?.data ?? res)
        detail.value = payload || null
    } catch (error) {
        if (token !== loadToken) {
            return
        }
        detail.value = null
        loadError.value = extractErrorMessage(error, '问卷任务详情加载失败，请稍后重试')
    } finally {
        if (token === loadToken) {
            loading.value = false
        }
    }
}

watch(
    [() => props.modelValue, () => props.taskId],
    ([isOpen]) => {
        if (isOpen && props.taskId > 0) {
            void loadDetail()
        }
    }
)

const sendTask = async () => {
    const task = detail.value
    if (!task?.id || sending.value) {
        return
    }
    const label = getSendActionLabel(task)
    const isAheadOfSchedule =
        Number(task.send_status || 0) === 0 && Boolean(task.planned_send_time)
    const confirmText = isAheadOfSchedule
        ? `该任务原计划于 ${task.planned_send_time} 自动发送，确定现在提前发送吗？本次会通过站内消息提醒客户填写「${task.title_snapshot || '新人问卷'}」。`
        : `确定${label}吗？本次会通过站内消息提醒客户填写「${task.title_snapshot || '新人问卷'}」。`
    try {
        await ElMessageBox.confirm(confirmText, '发送新人问卷', { type: 'info' })
    } catch {
        return
    }
    sending.value = true
    try {
        await (props.sendTaskApi || coupleQuestionnaireSend)({ id: Number(task.id) })
        ElMessage.success('问卷已发送')
        await loadDetail()
        emit('sent')
    } catch {
        // 业务错误已由 request 层统一提示，这里不重复弹窗
    } finally {
        sending.value = false
    }
}
</script>

<style scoped lang="scss">
.task-detail-drawer {
    :deep(.el-drawer__body) {
        padding: 16px 20px;
        background: var(--admin-surface-soft);
    }
}

.task-detail {
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-height: 200px;

    &__alert {
        border-radius: var(--admin-radius-sm);
    }

    &__block {
        border-radius: var(--admin-radius-sm);
        overflow: hidden;
    }
}

.task-overview {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding: 14px 16px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-md);
    background: var(--admin-surface-card);

    &__sn {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    &__label {
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__value {
        font-size: 15px;
        font-weight: 600;
        color: var(--admin-color-title);
    }

    &__tags {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
}

.section-title {
    margin-bottom: 10px;
    font-size: 14px;
    font-weight: 600;
    color: var(--admin-color-title);
}

.lifecycle {
    padding: 14px 16px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-md);
    background: var(--admin-surface-card);

    &__grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    &__card {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 10px 12px;
        border-radius: var(--admin-radius-sm);
        background: var(--admin-surface-soft);
    }

    &__label {
        font-size: 12px;
        color: var(--admin-color-muted);
    }

    &__value {
        font-size: 13px;
        font-weight: 500;
        color: var(--admin-color-title);
        word-break: break-all;
    }
}

.answer-section {
    padding: 14px 16px 4px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-md);
    background: var(--admin-surface-card);
}

.answer-group {
    margin-bottom: 16px;

    &__title {
        position: relative;
        margin-bottom: 10px;
        padding-left: 10px;
        font-size: 13px;
        font-weight: 600;
        color: var(--admin-color-title);

        &::before {
            content: '';
            position: absolute;
            left: 0;
            top: 3px;
            width: 3px;
            height: 13px;
            border-radius: 2px;
            background: var(--el-color-primary);
        }
    }
}

.answer-card {
    padding: 12px;
    margin-bottom: 10px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-soft);

    &__head {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }

    &__no {
        font-size: 12px;
        font-weight: 600;
        color: var(--el-color-primary);
        letter-spacing: 0.4px;
    }

    &__title {
        font-size: 14px;
        font-weight: 500;
        line-height: 1.6;
        color: var(--admin-color-title);
    }

    &__value {
        margin-top: 8px;
        font-size: 13px;
        line-height: 1.7;
        color: #374151;
    }

    &__empty {
        color: var(--admin-color-muted);
        font-style: italic;
    }

    &__rating {
        font-weight: 600;
        color: var(--el-color-warning);
    }

    &__rate {
        margin-left: 8px;
        vertical-align: middle;
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
}

.entry-path {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;

    &__text {
        font-family: monospace;
        font-size: 12px;
        color: var(--admin-color-muted);
        word-break: break-all;
    }
}

.drawer-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;

    &__actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }
}

@media (max-width: 640px) {
    .lifecycle__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .drawer-footer__actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>
