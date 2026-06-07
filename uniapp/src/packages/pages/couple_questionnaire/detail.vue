<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="新人问卷" />

        <view v-if="loading" class="questionnaire-detail questionnaire-detail--state wm-page-content">
            <LoadingState text="正在加载问卷..." />
        </view>

        <view v-else-if="pageError" class="questionnaire-detail questionnaire-detail--state wm-page-content">
            <EmptyState
                :title="pageError.title"
                :description="pageError.description"
                :actionText="pageError.actionText"
                @action="handleStateAction"
            />
        </view>

        <view v-else-if="submittedSuccess" class="questionnaire-detail questionnaire-detail--state wm-page-content">
            <BaseCard variant="surface" scene="consumer" class="questionnaire-detail__thanks">
                <text class="questionnaire-detail__thanks-icon">✓</text>
                <text class="questionnaire-detail__title">问卷已提交</text>
                <text class="questionnaire-detail__desc">
                    感谢补充资料，服务人员会基于你的填写继续完善婚礼仪式方案。
                </text>
                <BaseButton block variant="primary" size="lg" @click="goOrderDetail">查看关联订单</BaseButton>
                <BaseButton block variant="secondary" size="lg" @click="reloadDetail">查看填写结果</BaseButton>
            </BaseCard>
        </view>

        <view v-else-if="detail" class="questionnaire-detail wm-page-content">
            <BaseCard variant="surface" scene="consumer" class="questionnaire-detail__card">
                <text class="questionnaire-detail__title">{{ detail.title_snapshot || '新人问卷' }}</text>
                <text class="questionnaire-detail__desc">
                    {{ detail.description_snapshot || '请补充婚礼仪式策划资料。' }}
                </text>
                <view class="questionnaire-detail__meta">
                    <text>订单：{{ detail.order?.order_sn || detail.order_id || '-' }}</text>
                    <text>服务人员：{{ detail.staff?.name || '待补充' }}</text>
                    <text>问卷版本：v{{ detail.version_no || '-' }}</text>
                    <text>推送时间：{{ detail.last_send_time || detail.send_time || '服务人员已发送' }}</text>
                </view>
                <view class="questionnaire-detail__status-row">
                    <text class="questionnaire-detail__status" :class="`is-${statusTone}`">
                        {{ detail.status_desc || statusText }}
                    </text>
                    <text class="questionnaire-detail__status is-send">
                        {{ detail.send_status_desc || '已推送' }}
                    </text>
                </view>
                <view class="questionnaire-detail__privacy">
                    <text>隐私提示：本问卷仅用于当前订单婚礼策划与服务沟通，服务人员和平台管理员可查看。请勿填写身份证号、银行卡号等非必要敏感信息。</text>
                </view>
            </BaseCard>

            <BaseCard v-if="questions.length" variant="surface" scene="consumer" class="questionnaire-detail__card">
                <view
                    v-for="question in questions"
                    :key="question.id"
                    :id="getQuestionDomId(question)"
                    class="question-field"
                    :class="{ 'has-error': touched && getQuestionError(question) }"
                >
                    <view class="question-field__label-row">
                        <text class="question-field__label">{{ question.title }}</text>
                        <text v-if="question.required" class="question-field__required">必填</text>
                    </view>

                    <template v-if="canEdit">
                        <textarea
                            v-if="['text', 'textarea'].includes(question.type)"
                            v-model="answerMap[question.id]"
                            class="question-field__textarea"
                            :maxlength="question.type === 'text' ? 120 : 800"
                            :placeholder="question.placeholder || '请输入'"
                        />

                        <view v-else-if="question.type === 'rating'" class="question-field__rating">
                            <view
                                v-for="score in 5"
                                :key="score"
                                class="question-field__star"
                                :class="{ 'is-active': Number(answerMap[question.id] || 0) >= score }"
                                role="button"
                                :aria-label="`${score} 分`"
                                @click="answerMap[question.id] = score"
                            >
                                ★
                            </view>
                        </view>

                        <view v-else class="question-field__options">
                            <view
                                v-for="option in question.options"
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

            <BaseCard v-else variant="surface" scene="consumer" class="questionnaire-detail__card">
                <EmptyState
                    title="暂无问卷题目"
                    description="服务人员可能尚未发布问卷模板，请联系服务人员确认后再填写。"
                    actionText="重新加载"
                    @action="reloadDetail"
                />
            </BaseCard>
        </view>

        <ActionArea v-if="detail && canEdit && questions.length" sticky safeBottom>
            <view class="questionnaire-detail__action-shell">
                <view class="questionnaire-detail__action-tip">
                    <text>提交即表示同意服务人员基于当前订单使用这些资料完善婚礼服务方案。</text>
                </view>
                <view class="questionnaire-detail__actions">
                    <BaseButton block variant="secondary" size="lg" @click="goOrderDetail">
                        查看订单
                    </BaseButton>
                    <BaseButton block variant="primary" size="lg" :loading="submitting" @click="handleSubmit">
                        提交问卷
                    </BaseButton>
                </view>
            </view>
        </ActionArea>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import {
    getCoupleQuestionnaireDetail,
    submitCoupleQuestionnaire
} from '@/api/coupleQuestionnaire'

const $theme = useThemeStore()
const taskId = ref(0)
const detail = ref<any>(null)
const answerMap = reactive<Record<string, any>>({})
const loading = ref(false)
const submitting = ref(false)
const touched = ref(false)
const submittedSuccess = ref(false)
const submittedOrderId = ref(0)
const pageError = ref<{ title: string; description: string; actionText: string; action: 'retry' | 'back' } | null>(null)
const submittableStatuses = [0, 3]

const questions = computed<any[]>(() => detail.value?.questions || [])
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
const statusTone = computed(() => {
    const status = Number(detail.value?.status || 0)
    if (status === 1) return 'done'
    if (status === 2 || status === 4) return 'closed'
    return 'pending'
})

const getSubmittedAnswerValue = (question: any) => {
    const answers = detail.value?.answer?.answers || []
    const answer = answers.find((item: any) => String(item.key) === String(question.id))
    return answer?.value
}

const hydrateAnswers = () => {
    Object.keys(answerMap).forEach((key) => delete answerMap[key])
    questions.value.forEach((question) => {
        const submittedValue = getSubmittedAnswerValue(question)
        if (submittedValue !== undefined && submittedValue !== null) {
            answerMap[question.id] = Array.isArray(submittedValue) ? [...submittedValue] : submittedValue
        } else if (question.type === 'multiple') {
            answerMap[question.id] = []
        } else if (question.type === 'rating') {
            answerMap[question.id] = 0
        } else {
            answerMap[question.id] = ''
        }
    })
}

const normalizeErrorMessage = (error: any) => String(error?.message || error || '')

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
    } catch (error: any) {
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

const isOptionSelected = (question: any, option: string) => {
    const value = answerMap[question.id]
    return question.type === 'multiple'
        ? Array.isArray(value) && value.includes(option)
        : value === option
}

const toggleOption = (question: any, option: string) => {
    if (question.type !== 'multiple') {
        answerMap[question.id] = option
        return
    }
    const current = Array.isArray(answerMap[question.id]) ? [...answerMap[question.id]] : []
    answerMap[question.id] = current.includes(option)
        ? current.filter((item) => item !== option)
        : [...current, option]
}

const normalizeAnswerValue = (question: any) => {
    const value = answerMap[question.id]
    if (Array.isArray(value)) {
        return value.filter((item) => String(item || '').trim())
    }
    return typeof value === 'string' ? value.trim() : value
}

const isEmptyAnswer = (question: any) => {
    const value = normalizeAnswerValue(question)
    if (Array.isArray(value)) return value.length === 0
    return value === undefined || value === null || value === '' || (question.type === 'rating' && Number(value || 0) <= 0)
}

const getQuestionError = (question: any) => {
    if (Number(question.required || 0) !== 1) return ''
    return isEmptyAnswer(question) ? '这道题为必填项，请补充后再提交' : ''
}

const getQuestionDomId = (question: any) => `question-field-${question.id || question.bank_id || 0}`

const scrollToQuestion = (question: any) => {
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
        uni.showToast({ title: '请先补全必填题目', icon: 'none' })
        return false
    }
    return true
}

const buildAnswers = () =>
    questions.value.map((question) => ({
        key: question.id,
        value: normalizeAnswerValue(question)
    }))

const handleSubmit = async () => {
    touched.value = true
    if (!canEdit.value) {
        uni.showToast({ title: Number(detail.value?.status || 0) === 1 ? '问卷已提交' : '当前问卷不可填写', icon: 'none' })
        return
    }
    if (!validateAnswers()) return
    try {
        await new Promise((resolve, reject) => {
            uni.showModal({
                title: '提交新人问卷',
                content: '提交后将同步给服务人员用于当前订单服务沟通，是否确认提交？',
                confirmText: '确认提交',
                success: (res) => (res.confirm ? resolve(true) : reject(new Error('cancel'))),
                fail: reject
            })
        })
    } catch (error) {
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
        uni.showToast({ title: '提交成功', icon: 'none' })
    } catch (error: any) {
        const message = normalizeErrorMessage(error) || '提交失败，请稍后重试'
        uni.showToast({ title: message, icon: 'none' })
        if (message.includes('已提交') || message.includes('不可填写')) {
            await loadDetail()
        }
    } finally {
        submitting.value = false
    }
}

const getSubmittedAnswer = (question: any) => {
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

onLoad((options: any) => {
    taskId.value = Number(options?.id || options?.task_id || 0)
    void loadDetail()
})
</script>

<style scoped lang="scss">
.questionnaire-detail {
    min-height: 100vh;
    padding-top: 16rpx;
    padding-bottom: var(--wm-safe-bottom-action, calc(env(safe-area-inset-bottom) + 150rpx));
}

.questionnaire-detail--state {
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}

.questionnaire-detail__thanks {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 20rpx;
    align-items: center;
    text-align: center;
}

.questionnaire-detail__thanks-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 96rpx;
    height: 96rpx;
    border-radius: 999rpx;
    background: rgba(79, 111, 90, 0.12);
    color: #4f6f5a;
    font-size: 56rpx;
    font-weight: 900;
}

.questionnaire-detail__card {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
    margin-bottom: 16rpx;
}

.questionnaire-detail__title {
    display: block;
    color: var(--wm-text-primary, #111111);
    font-size: 34rpx;
    font-weight: 800;
}

.questionnaire-detail__desc,
.questionnaire-detail__meta {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    color: var(--wm-text-secondary, #5f5a50);
    font-size: 24rpx;
    line-height: 1.7;
}

.questionnaire-detail__status-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.questionnaire-detail__status {
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    background: rgba(17, 17, 17, 0.08);
    color: var(--wm-text-primary, #111111);
    font-size: 22rpx;
    font-weight: 700;

    &.is-pending {
        background: rgba(159, 122, 46, 0.12);
        color: #8f6b21;
    }

    &.is-done {
        background: rgba(79, 111, 90, 0.12);
        color: #3f684c;
    }

    &.is-closed {
        background: rgba(89, 106, 122, 0.12);
        color: #596a7a;
    }

    &.is-send {
        background: rgba(11, 11, 11, 0.08);
        color: var(--wm-color-primary, #111111);
    }
}

.questionnaire-detail__privacy {
    padding: 18rpx 20rpx;
    border-radius: 16rpx;
    background: rgba(159, 122, 46, 0.08);
    color: #7f6224;
    font-size: 23rpx;
    line-height: 1.6;
}

.question-field {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 18rpx 0;
    border-bottom: 1rpx solid rgba(17, 17, 17, 0.08);

    &:last-child {
        border-bottom: 0;
    }

    &.has-error {
        margin: 10rpx -12rpx;
        padding: 20rpx 12rpx;
        border-radius: 14rpx;
        border-bottom-color: transparent;
        background: rgba(138, 75, 69, 0.06);
    }
}

.question-field__label-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.question-field__label {
    flex: 1;
    min-width: 0;
    color: var(--wm-text-primary, #111111);
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.5;
}

.question-field__required {
    color: #b2533e;
    font-size: 22rpx;
    font-weight: 700;
}

.question-field__textarea {
    min-height: 180rpx;
    padding: 22rpx 24rpx;
    border: 1rpx solid rgba(17, 17, 17, 0.1);
    border-radius: 12rpx;
    box-sizing: border-box;
    background: rgba(255, 255, 255, 0.72);
    color: var(--wm-text-primary, #111111);
    font-size: 26rpx;
    line-height: 1.7;
}

.question-field__options {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
}

.question-field__option {
    padding: 14rpx 22rpx;
    border: 1rpx solid rgba(17, 17, 17, 0.12);
    border-radius: 999rpx;
    color: var(--wm-text-secondary, #5f5a50);
    font-size: 24rpx;
    font-weight: 600;

    &.is-active {
        background: var(--wm-color-primary, #111111);
        color: #ffffff;
    }
}

.question-field__rating {
    display: flex;
    gap: 12rpx;
}

.question-field__star {
    color: rgba(17, 17, 17, 0.18);
    font-size: 46rpx;

    &.is-active {
        color: #d9a441;
    }
}

.question-field__answer {
    color: var(--wm-text-primary, #111111);
    font-size: 26rpx;
    line-height: 1.7;
}

.question-field__error {
    color: #8a4b45;
    font-size: 22rpx;
    line-height: 1.5;
}

.questionnaire-detail__action-shell {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.questionnaire-detail__action-tip {
    padding: 0 4rpx;
    color: var(--wm-text-secondary, #5f5a50);
    font-size: 22rpx;
    line-height: 1.5;
    text-align: center;
}

.questionnaire-detail__actions {
    display: grid;
    grid-template-columns: 0.8fr 1.2fr;
    gap: 16rpx;
    min-width: 0;
}
</style>
