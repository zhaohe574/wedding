<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="form" hasSafeBottom>
        <BaseNavbar
            title="新人问卷"
            title-align="center"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view v-if="loading" class="questionnaire-detail questionnaire-detail--state wm-page-content">
            <BaseCard variant="panel" scene="consumer" class="questionnaire-detail__state-card">
                <LoadingState text="正在加载问卷..." />
            </BaseCard>
        </view>

        <view v-else-if="pageError" class="questionnaire-detail questionnaire-detail--state wm-page-content">
            <BaseCard variant="panel" scene="consumer" class="questionnaire-detail__state-card">
                <EmptyState
                    :title="pageError.title"
                    :description="pageError.description"
                    :actionText="pageError.actionText"
                    @action="handleStateAction"
                />
            </BaseCard>
        </view>

        <view v-else-if="submittedSuccess" class="questionnaire-detail questionnaire-detail--state wm-page-content">
            <BaseCard variant="hero" scene="consumer" class="questionnaire-detail__thanks">
                <view class="questionnaire-detail__thanks-icon">
                    <text>✓</text>
                </view>
                <StatusBadge tone="success" size="sm" strong>已提交</StatusBadge>
                <text class="questionnaire-detail__thanks-title">问卷已提交</text>
                <text class="questionnaire-detail__thanks-desc">
                    服务人员会基于这些资料完善婚礼方案。
                </text>
                <view class="questionnaire-detail__thanks-actions">
                    <BaseButton block variant="light" size="md" @click="goOrderDetail">查看订单</BaseButton>
                    <BaseButton block variant="secondary" size="md" @click="reloadDetail">填写结果</BaseButton>
                </view>
            </BaseCard>
        </view>

        <view v-else-if="detail" class="questionnaire-detail wm-page-content">
            <view class="questionnaire-detail__hero-wrap">
                <BaseCard variant="hero" scene="consumer" class="questionnaire-detail__hero">
                    <view class="questionnaire-detail__status-row">
                        <StatusBadge :tone="statusBadgeTone" size="sm" strong>
                            {{ detail.status_desc || statusText }}
                        </StatusBadge>
                        <StatusBadge tone="warning" size="sm">
                            {{ detail.send_status_desc || '已推送' }}
                        </StatusBadge>
                    </view>

                    <view class="questionnaire-detail__hero-copy">
                        <text class="questionnaire-detail__title">{{
                            detail.title_snapshot || '新人问卷'
                        }}</text>
                        <text v-if="detail.description_snapshot" class="questionnaire-detail__desc">
                            {{ detail.description_snapshot }}
                        </text>
                    </view>

                    <view class="questionnaire-detail__info-panel">
                        <view class="questionnaire-detail__info-row">
                            <text class="questionnaire-detail__info-label">订单号</text>
                            <text class="questionnaire-detail__info-value questionnaire-detail__info-value--strong">{{
                                detail.order?.order_sn || detail.order_id || '-'
                            }}</text>
                        </view>
                        <view class="questionnaire-detail__info-grid">
                            <view class="questionnaire-detail__info-cell">
                                <text class="questionnaire-detail__info-label">服务人员</text>
                                <text class="questionnaire-detail__info-value">{{
                                    detail.staff?.name || '待补充'
                                }}</text>
                            </view>
                            <view class="questionnaire-detail__info-cell">
                                <text class="questionnaire-detail__info-label">版本</text>
                                <text class="questionnaire-detail__info-value">v{{ detail.version_no || '-' }}</text>
                            </view>
                            <view class="questionnaire-detail__info-cell questionnaire-detail__info-cell--wide">
                                <text class="questionnaire-detail__info-label">推送</text>
                                <text class="questionnaire-detail__info-value">{{
                                    detail.last_send_time || detail.send_time || '已发送'
                                }}</text>
                            </view>
                        </view>
                    </view>

                    <view class="questionnaire-detail__privacy">
                        <text>仅用于当前订单策划与服务沟通，请勿填写身份证号、银行卡号等非必要敏感信息。</text>
                    </view>
                </BaseCard>
            </view>

            <BaseCard v-if="questions.length" variant="panel" scene="consumer" class="questionnaire-detail__card">
                <view class="questionnaire-detail__section-head">
                    <view class="questionnaire-detail__section-copy">
                        <text class="questionnaire-detail__section-title">问卷内容</text>
                        <text class="questionnaire-detail__section-meta">{{
                            canEdit ? '请补全必填题目后提交' : '当前为查看状态'
                        }}</text>
                    </view>
                    <StatusBadge tone="neutral" size="sm">{{ questions.length }} 题</StatusBadge>
                </view>

                <view
                    v-for="question in questions"
                    :key="getQuestionKey(question)"
                    :id="getQuestionDomId(question)"
                    class="question-field"
                    :class="{ 'has-error': touched && getQuestionError(question) }"
                >
                    <view class="question-field__label-row">
                        <text class="question-field__label">{{ question.title }}</text>
                        <StatusBadge
                            v-if="question.required"
                            class="question-field__required"
                            tone="warning"
                            size="xs"
                        >
                            必填
                        </StatusBadge>
                    </view>

                    <template v-if="canEdit">
                        <textarea
                            v-if="['text', 'textarea'].includes(String(question.type || ''))"
                            :value="getTextAnswer(question)"
                            class="question-field__textarea"
                            :maxlength="question.type === 'text' ? 120 : 800"
                            :placeholder="question.placeholder || '请输入'"
                            @input="handleTextAnswerInput(question, $event)"
                        />

                        <view v-else-if="question.type === 'rating'" class="question-field__rating">
                            <view
                                v-for="score in 5"
                                :key="score"
                                class="question-field__star"
                                :class="{ 'is-active': Number(answerMap[getQuestionKey(question)] || 0) >= score }"
                                role="button"
                                :aria-label="`${score} 分`"
                                @click="answerMap[getQuestionKey(question)] = score"
                            >
                                ★
                            </view>
                        </view>

                        <view v-else class="question-field__options">
                            <view
                                v-for="option in question.options || []"
                                :key="option"
                                class="question-field__option"
                                :class="{ 'is-active': isOptionSelected(question, option) }"
                                role="button"
                                :aria-label="`${question.title}：${option}`"
                                @click="toggleOption(question, option)"
                            >
                                {{ option }}
                            </view>
                        </view>
                    </template>

                    <template v-else>
                        <text class="question-field__answer">{{ getSubmittedAnswer(question) }}</text>
                    </template>

                    <text v-if="touched && getQuestionError(question)" class="question-field__error">
                        {{ getQuestionError(question) }}
                    </text>
                </view>
            </BaseCard>

            <BaseCard v-else variant="panel" scene="consumer" class="questionnaire-detail__card">
                <EmptyState
                    title="暂无问卷题目"
                    actionText="重新加载"
                    @action="reloadDetail"
                />
            </BaseCard>
        </view>

        <view
            v-if="detail && canEdit && questions.length"
            class="questionnaire-detail__fixed-actions"
            style="background: #fffdf8;"
        >
            <view class="questionnaire-detail__action-bg"></view>
            <view class="questionnaire-detail__action-shell">
                <view class="questionnaire-detail__action-tip">
                    <text>资料会同步给服务人员用于当前订单。</text>
                </view>
                <view class="questionnaire-detail__actions">
                    <BaseButton block variant="light" size="md" height="88rpx" @click="goOrderDetail">
                        查看订单
                    </BaseButton>
                    <BaseButton
                        block
                        variant="dark"
                        size="md"
                        height="88rpx"
                        :loading="submitting"
                        @click="handleSubmit"
                    >
                        提交问卷
                    </BaseButton>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'
import {
    getCoupleQuestionnaireDetail,
    submitCoupleQuestionnaire
} from '@/api/coupleQuestionnaire'
import type {
    CoupleQuestionnaireTask,
    QuestionnaireAnswerValue,
    QuestionnaireQuestion
} from '@/types/coupleQuestionnaire'

const $theme = useThemeStore()
const taskId = ref(0)
const detail = ref<CoupleQuestionnaireTask | null>(null)
const answerMap = reactive<Record<string, QuestionnaireAnswerValue | ''>>({})
const loading = ref(false)
const submitting = ref(false)
const touched = ref(false)
const submittedSuccess = ref(false)
const submittedOrderId = ref(0)
const pageError = ref<{ title: string; description: string; actionText: string; action: 'retry' | 'back' } | null>(null)
const submittableStatuses = [0, 3]
type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'primary'

const questions = computed<QuestionnaireQuestion[]>(() => detail.value?.questions || [])
const canEdit = computed(() => {
    const canSubmit = detail.value?.can_submit
    if (canSubmit !== undefined && canSubmit !== null) {
        return Number(canSubmit) === 1
    }
    return submittableStatuses.includes(Number(detail.value?.status || 0))
})
const statusText = computed(() => {
    const status = Number(detail.value?.status || 0)
    if (status === 1) return '已填写'
    if (status === 2) return '已取消'
    if (status === 3) return '已查看'
    if (status === 4) return '已过期'
    return '待填写'
})
const statusBadgeTone = computed<BadgeTone>(() => {
    const status = Number(detail.value?.status || 0)
    if (status === 1) return 'success'
    if (status === 2 || status === 4) return 'neutral'
    return 'warning'
})

const getQuestionKey = (question: QuestionnaireQuestion) => String(question.id || question.bank_id || 0)

const getAnswerArray = (question: QuestionnaireQuestion): Array<string | number> => {
    const value = answerMap[getQuestionKey(question)]
    return Array.isArray(value) ? value : []
}

const getTextAnswer = (question: QuestionnaireQuestion) => {
    const value = answerMap[getQuestionKey(question)]
    return Array.isArray(value) ? value.join('、') : String(value || '')
}

const setTextAnswer = (question: QuestionnaireQuestion, value: string) => {
    answerMap[getQuestionKey(question)] = value
}

const handleTextAnswerInput = (question: QuestionnaireQuestion, event: Event) => {
    const value = (event as Event & { detail?: { value?: string } }).detail?.value || ''
    setTextAnswer(question, value)
}

const getSubmittedAnswerValue = (question: QuestionnaireQuestion) => {
    const answers = detail.value?.answer?.answers || []
    const answer = answers.find((item) => String(item.key) === getQuestionKey(question))
    return answer?.value
}

const hydrateAnswers = () => {
    Object.keys(answerMap).forEach((key) => delete answerMap[key])
    questions.value.forEach((question) => {
        const questionKey = getQuestionKey(question)
        const submittedValue = getSubmittedAnswerValue(question)
        if (submittedValue !== undefined && submittedValue !== null) {
            answerMap[questionKey] = Array.isArray(submittedValue)
                ? [...submittedValue]
                : submittedValue
        } else if (question.type === 'multiple') {
            answerMap[questionKey] = []
        } else if (question.type === 'rating') {
            answerMap[questionKey] = 0
        } else {
            answerMap[questionKey] = ''
        }
    })
}

const normalizeErrorMessage = (error: unknown) =>
    error instanceof Error ? error.message : String(error || '')

const loadDetail = async () => {
    if (!taskId.value) {
        pageError.value = {
            title: '问卷链接无效',
            description: '未找到问卷任务编号，请从订单详情或站内消息重新进入。',
            actionText: '返回上一页',
            action: 'back'
        }
        return
    }
    loading.value = true
    pageError.value = null
    submittedSuccess.value = false
    submittedOrderId.value = 0
    touched.value = false
    try {
        const res = await getCoupleQuestionnaireDetail(taskId.value)
        const nextDetail = res?.data || res
        if (!nextDetail || !Number(nextDetail.id || 0)) {
            pageError.value = {
                title: '问卷不可访问',
                description: '可能不是当前账号的问卷，或问卷任务已被取消/删除。',
                actionText: '重新加载',
                action: 'retry'
            }
            detail.value = null
            return
        }
        detail.value = nextDetail
        hydrateAnswers()
    } catch (error: unknown) {
        const message = normalizeErrorMessage(error)
        pageError.value = {
            title: message.includes('登录') || message.includes('token') ? '请先登录' : '问卷加载失败',
            description: message || '请检查网络后重试，或从订单详情/站内消息重新进入问卷。',
            actionText: '重试',
            action: 'retry'
        }
        detail.value = null
    } finally {
        loading.value = false
    }
}

const reloadDetail = () => {
    void loadDetail()
}

const isOptionSelected = (question: QuestionnaireQuestion, option: string) => {
    const value = answerMap[getQuestionKey(question)]
    return question.type === 'multiple'
        ? Array.isArray(value) && value.map(String).includes(option)
        : value === option
}

const toggleOption = (question: QuestionnaireQuestion, option: string) => {
    if (question.type !== 'multiple') {
        answerMap[getQuestionKey(question)] = option
        return
    }
    const questionKey = getQuestionKey(question)
    const current = getAnswerArray(question)
    answerMap[questionKey] = current.map(String).includes(option)
        ? current.filter((item) => String(item) !== option)
        : [...current, option]
}

const normalizeAnswerValue = (question: QuestionnaireQuestion) => {
    const value = answerMap[getQuestionKey(question)]
    if (Array.isArray(value)) {
        return value.filter((item: string | number) => String(item || '').trim())
    }
    return typeof value === 'string' ? value.trim() : value
}

const isEmptyAnswer = (question: QuestionnaireQuestion) => {
    const value = normalizeAnswerValue(question)
    if (Array.isArray(value)) return value.length === 0
    return value === undefined || value === null || value === '' || (question.type === 'rating' && Number(value || 0) <= 0)
}

const getQuestionError = (question: QuestionnaireQuestion) => {
    if (Number(question.required || 0) !== 1) return ''
    return isEmptyAnswer(question) ? '这道题为必填项，请补充后再提交' : ''
}

const getQuestionDomId = (question: QuestionnaireQuestion) =>
    `question-field-${getQuestionKey(question)}`

const scrollToQuestion = (question: QuestionnaireQuestion) => {
    const selector = `#${getQuestionDomId(question)}`
    setTimeout(() => {
        uni.pageScrollTo({
            selector,
            duration: 240,
            fail: () => {}
        })
    }, 50)
}

const validateAnswers = () => {
    const invalid = questions.value.find((question) => getQuestionError(question))
    if (invalid) {
        scrollToQuestion(invalid)
        showError('请先补全必填题目')
        return false
    }
    return true
}

const buildAnswers = () =>
    questions.value.map((question) => ({
        key: getQuestionKey(question),
        value: normalizeAnswerValue(question)
    }))

const handleSubmit = async () => {
    touched.value = true
    if (!canEdit.value) {
        showError(Number(detail.value?.status || 0) === 1 ? '问卷已提交' : '当前问卷不可填写')
        return
    }
    if (!validateAnswers()) return
    const confirmed = await confirmModal({
        title: '提交新人问卷',
        content: '提交后将同步给服务人员用于当前订单服务沟通，是否确认提交？',
        confirmText: '确认提交'
    })
    if (!confirmed) {
        return
    }
    submitting.value = true
    try {
        const currentOrderId = Number(detail.value?.order_id || detail.value?.order?.id || 0)
        await submitCoupleQuestionnaire({
            id: taskId.value,
            answers: buildAnswers()
        })
        submittedOrderId.value = currentOrderId
        submittedSuccess.value = true
        detail.value = null
        showSuccess('提交成功')
    } catch (error: unknown) {
        const message = normalizeErrorMessage(error) || '提交失败，请稍后重试'
        showError(message)
        if (message.includes('已提交') || message.includes('不可填写')) {
            await loadDetail()
        }
    } finally {
        submitting.value = false
    }
}

const getSubmittedAnswer = (question: QuestionnaireQuestion) => {
    const value = getSubmittedAnswerValue(question)
    if (Array.isArray(value)) {
        return value.join('、') || '未填写'
    }
    return value || '未填写'
}

const goOrderDetail = () => {
    const orderId = Number(detail.value?.order_id || detail.value?.order?.id || submittedOrderId.value || 0)
    if (!orderId) {
        uni.navigateBack()
        return
    }
    uni.navigateTo({
        url: `/pages/order_detail/order_detail?id=${orderId}`
    })
}

const handleStateAction = () => {
    if (pageError.value?.action === 'back') {
        uni.navigateBack()
        return
    }
    reloadDetail()
}

onLoad((options?: { id?: string | number; task_id?: string | number }) => {
    taskId.value = Number(options?.id || options?.task_id || 0)
    void loadDetail()
})
</script>

<style scoped lang="scss">
.questionnaire-detail {
    min-height: 100vh;
    padding-top: 20rpx;
    padding-bottom: var(--wm-safe-bottom-action, calc(env(safe-area-inset-bottom) + 220rpx));
    box-sizing: border-box;
}

.questionnaire-detail--state {
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}

.questionnaire-detail__state-card {
    width: 100%;
}

.questionnaire-detail__thanks {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    align-items: center;
    padding: 44rpx 34rpx;
    text-align: center;
    box-sizing: border-box;
}

.questionnaire-detail__thanks-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 104rpx;
    height: 104rpx;
    border-radius: 999rpx;
    background: rgba(255, 253, 248, 0.12);
    border: 1rpx solid rgba(217, 190, 130, 0.52);
    color: var(--wm-color-champagne, #d9be82);
    font-size: 58rpx;
    font-weight: 900;
}

.questionnaire-detail__thanks-icon text {
    line-height: 1;
}

.questionnaire-detail__thanks-title,
.questionnaire-detail__thanks-desc {
    display: block;
}

.questionnaire-detail__thanks-title {
    font-size: 40rpx;
    line-height: 1.22;
    font-weight: 900;
    color: #ffffff;
}

.questionnaire-detail__thanks-desc {
    max-width: 560rpx;
    font-size: 24rpx;
    line-height: 1.5;
    color: rgba(255, 253, 248, 0.72);
}

.questionnaire-detail__thanks-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14rpx;
    width: 100%;
    margin-top: 6rpx;
}

.questionnaire-detail__card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    overflow: hidden;
}

.questionnaire-detail__card {
    margin-bottom: 18rpx;
}

.questionnaire-detail__hero-wrap {
    margin-bottom: 38rpx;
}

.questionnaire-detail__hero {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 28rpx;
    padding: 34rpx 30rpx 30rpx;
    overflow: hidden;
}

.questionnaire-detail__status-row,
.questionnaire-detail__hero-copy,
.questionnaire-detail__info-panel,
.questionnaire-detail__privacy,
.questionnaire-detail__section-head,
.question-field {
    position: relative;
    z-index: 1;
}

.questionnaire-detail__status-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-bottom: 0;
}

.questionnaire-detail__hero-copy {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.questionnaire-detail__title {
    display: block;
    color: #ffffff;
    font-size: 39rpx;
    line-height: 1.28;
    font-weight: 900;
}

.questionnaire-detail__desc {
    display: block;
    font-size: 23rpx;
    line-height: 1.68;
    color: rgba(255, 253, 248, 0.72);
}

.questionnaire-detail__info-panel {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    padding: 22rpx 20rpx;
    border-radius: 24rpx;
    background: rgba(255, 253, 248, 0.1);
    border: 1rpx solid rgba(255, 253, 248, 0.16);
    box-sizing: border-box;
}

.questionnaire-detail__info-row {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    min-width: 0;
    padding: 0 0 18rpx;
    border-bottom: 1rpx solid rgba(255, 253, 248, 0.14);
    box-sizing: border-box;
}

.questionnaire-detail__info-row:first-child {
    padding-top: 0;
}

.questionnaire-detail__info-row:last-child {
    padding-bottom: 0;
    border-bottom: 0;
}

.questionnaire-detail__info-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 18rpx 22rpx;
}

.questionnaire-detail__info-cell {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.questionnaire-detail__info-cell--wide {
    grid-column: 1 / -1;
    padding-top: 18rpx;
    border-top: 1rpx solid rgba(255, 253, 248, 0.14);
}

.questionnaire-detail__info-label,
.questionnaire-detail__info-value {
    display: block;
    width: 100%;
    white-space: normal;
    word-break: break-all;
    box-sizing: border-box;
}

.questionnaire-detail__info-label {
    font-size: 20rpx;
    line-height: 1.45;
    font-weight: 900;
    color: rgba(255, 253, 248, 0.56);
}

.questionnaire-detail__info-value {
    min-width: 0;
    font-size: 25rpx;
    line-height: 1.45;
    font-weight: 900;
    color: rgba(255, 253, 248, 0.95);
}

.questionnaire-detail__info-value--strong {
    color: #ffffff;
}

.questionnaire-detail__privacy {
    margin-top: 2rpx;
    padding: 16rpx 18rpx;
    border-radius: 20rpx;
    background: rgba(241, 229, 200, 0.14);
    border: 1rpx solid rgba(217, 190, 130, 0.24);
}

.questionnaire-detail__privacy text {
    display: block;
    font-size: 21rpx;
    line-height: 1.42;
    font-weight: 700;
    color: rgba(255, 253, 248, 0.74);
}

.questionnaire-detail__section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.questionnaire-detail__section-copy {
    flex: 1;
    min-width: 0;
}

.questionnaire-detail__section-title,
.questionnaire-detail__section-meta {
    display: block;
}

.questionnaire-detail__section-title {
    font-size: 31rpx;
    line-height: 1.25;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.questionnaire-detail__section-meta {
    margin-top: 8rpx;
    font-size: 22rpx;
    line-height: 1.4;
    font-weight: 700;
    color: var(--wm-text-secondary, #665e52);
}

.question-field {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 24rpx 0;
    border-bottom: 1rpx solid rgba(216, 201, 173, 0.58);

    &:last-child {
        border-bottom: 0;
    }

    &.has-error {
        margin: 8rpx -12rpx;
        padding: 24rpx 12rpx;
        border-radius: 24rpx;
        border-bottom-color: transparent;
        background: rgba(138, 75, 69, 0.08);
    }
}

.question-field__label-row {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
}

.question-field__label {
    flex: 1;
    min-width: 0;
    color: var(--wm-text-primary, #191713);
    font-size: 28rpx;
    font-weight: 900;
    line-height: 1.45;
}

.question-field__required {
    flex-shrink: 0;
}

.question-field__textarea {
    width: 100%;
    min-height: 188rpx;
    padding: 24rpx;
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    border-radius: 28rpx;
    box-sizing: border-box;
    background: #ffffff;
    color: var(--wm-text-primary, #191713);
    font-size: 26rpx;
    line-height: 1.65;
}

.question-field__options {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx 12rpx;
}

.question-field__option {
    min-height: 70rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 24rpx;
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    border-radius: 999rpx;
    background: #ffffff;
    color: var(--wm-text-secondary, #665e52);
    font-size: 24rpx;
    font-weight: 800;
    box-sizing: border-box;

    &.is-active {
        background: var(--wm-color-primary, #191713);
        border-color: var(--wm-color-champagne, #d9be82);
        color: var(--wm-text-inverse, #fffdf8);
        box-shadow: 0 12rpx 28rpx rgba(74, 43, 24, 0.14);
    }
}

.question-field__rating {
    display: flex;
    gap: 10rpx;
}

.question-field__star {
    width: 62rpx;
    height: 62rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #d8c9ad);
    color: rgba(25, 23, 19, 0.22);
    font-size: 40rpx;
    line-height: 1;
    box-sizing: border-box;

    &.is-active {
        border-color: var(--wm-color-champagne, #d9be82);
        color: var(--wm-color-gold, #b8954a);
        background: var(--wm-color-gold-soft, #f1e5c8);
    }
}

.question-field__answer {
    padding: 20rpx 22rpx;
    border-radius: 24rpx;
    background: #ffffff;
    color: var(--wm-text-primary, #191713);
    font-size: 26rpx;
    line-height: 1.6;
}

.question-field__error {
    color: #8a4b45;
    font-size: 22rpx;
    line-height: 1.5;
}

.questionnaire-detail__fixed-actions {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: var(--wm-z-action, 90);
    min-height: calc(176rpx + env(safe-area-inset-bottom));
    padding: 18rpx 24rpx calc(30rpx + env(safe-area-inset-bottom));
    background: #fffdf8;
    border-top: 1rpx solid rgba(216, 201, 173, 0.78);
    box-shadow: 0 -10rpx 26rpx rgba(74, 43, 24, 0.08);
    box-sizing: border-box;
}

.questionnaire-detail__action-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
    background: #fffdf8;
    pointer-events: none;
}

.questionnaire-detail__action-shell {
    position: relative;
    z-index: 1;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 0;
    background: transparent;
    border: 0;
    box-shadow: none;
    box-sizing: border-box;
}

.questionnaire-detail__action-tip {
    padding: 0 4rpx 2rpx;
    color: var(--wm-text-secondary, #665e52);
    font-size: 22rpx;
    line-height: 1.5;
    text-align: center;
    font-weight: 700;
}

.questionnaire-detail__actions {
    display: grid;
    grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
    gap: 14rpx;
    min-width: 0;
}

:deep(.base-navbar-wrapper--solid .base-navbar) {
    border-bottom-color: rgba(217, 190, 130, 0.42);
    box-shadow: 0 12rpx 30rpx rgba(11, 11, 11, 0.18);
}

@media (max-width: 360px) {
    .questionnaire-detail {
        padding-top: 16rpx;
    }

    .questionnaire-detail__hero {
        padding: 26rpx 24rpx;
    }

    .questionnaire-detail__card {
        padding: 24rpx;
    }

    .questionnaire-detail__title {
        font-size: 36rpx;
    }

    .questionnaire-detail__hero-wrap {
        margin-bottom: 34rpx;
    }

    .questionnaire-detail__info-panel {
        padding: 16rpx;
    }

    .question-field__label {
        font-size: 26rpx;
    }

    .question-field__option {
        min-height: 66rpx;
        padding: 0 20rpx;
    }
}
</style>
