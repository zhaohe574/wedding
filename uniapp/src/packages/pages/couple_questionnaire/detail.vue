<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="新人问卷" />

        <view v-if="detail" class="questionnaire-detail wm-page-content">
            <BaseCard variant="surface" scene="consumer" class="questionnaire-detail__card">
                <text class="questionnaire-detail__title">{{ detail.title_snapshot || '新人问卷' }}</text>
                <text class="questionnaire-detail__desc">
                    {{ detail.description_snapshot || '请补充婚礼仪式策划资料。' }}
                </text>
                <view class="questionnaire-detail__meta">
                    <text>订单：{{ detail.order?.order_sn || detail.order_id || '-' }}</text>
                    <text>服务人员：{{ detail.staff?.name || '待补充' }}</text>
                    <text>状态：{{ detail.status_desc || '待填写' }}</text>
                </view>
            </BaseCard>

            <BaseCard variant="surface" scene="consumer" class="questionnaire-detail__card">
                <view
                    v-for="question in questions"
                    :key="question.id"
                    class="question-field"
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
                            :placeholder="question.placeholder || '请输入'"
                        />

                        <view v-else-if="question.type === 'rating'" class="question-field__rating">
                            <view
                                v-for="score in 5"
                                :key="score"
                                class="question-field__star"
                                :class="{ 'is-active': Number(answerMap[question.id] || 0) >= score }"
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
                                @click="toggleOption(question, option)"
                            >
                                {{ option }}
                            </view>
                        </view>
                    </template>

                    <template v-else>
                        <text class="question-field__answer">{{ getSubmittedAnswer(question) }}</text>
                    </template>
                </view>
            </BaseCard>
        </view>

        <ActionArea v-if="detail && canEdit" sticky safeBottom>
            <view class="questionnaire-detail__actions">
                <BaseButton block variant="primary" size="lg" :loading="submitting" @click="handleSubmit">
                    提交问卷
                </BaseButton>
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
const submitting = ref(false)

const questions = computed<any[]>(() => detail.value?.questions || [])
const canEdit = computed(() => Number(detail.value?.status || 0) === 0)

const hydrateAnswers = () => {
    Object.keys(answerMap).forEach((key) => delete answerMap[key])
    questions.value.forEach((question) => {
        if (question.type === 'multiple') {
            answerMap[question.id] = []
        } else if (question.type === 'rating') {
            answerMap[question.id] = 5
        } else {
            answerMap[question.id] = ''
        }
    })
}

const loadDetail = async () => {
    try {
        const res = await getCoupleQuestionnaireDetail(taskId.value)
        detail.value = res?.data || res
        hydrateAnswers()
    } catch (error: any) {
        uni.showToast({ title: error?.message || '获取问卷失败', icon: 'none' })
    }
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

const buildAnswers = () =>
    questions.value.map((question) => ({
        key: question.id,
        value: answerMap[question.id]
    }))

const handleSubmit = async () => {
    submitting.value = true
    try {
        await submitCoupleQuestionnaire({
            id: taskId.value,
            answers: buildAnswers()
        })
        uni.showToast({ title: '提交成功', icon: 'none' })
        await loadDetail()
    } catch (error: any) {
        uni.showToast({ title: error?.message || '提交失败', icon: 'none' })
    } finally {
        submitting.value = false
    }
}

const getSubmittedAnswer = (question: any) => {
    const answers = detail.value?.answer?.answers || []
    const answer = answers.find((item: any) => String(item.key) === String(question.id))
    const value = answer?.value
    if (Array.isArray(value)) {
        return value.join('、') || '未填写'
    }
    return value || '未填写'
}

onLoad((options: any) => {
    taskId.value = Number(options?.id || 0)
    if (taskId.value > 0) {
        void loadDetail()
    }
})
</script>

<style scoped lang="scss">
.questionnaire-detail {
    min-height: 100vh;
    padding-top: 16rpx;
    padding-bottom: var(--wm-safe-bottom-action, calc(env(safe-area-inset-bottom) + 150rpx));
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

.question-field {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding: 18rpx 0;
    border-bottom: 1rpx solid rgba(17, 17, 17, 0.08);

    &:last-child {
        border-bottom: 0;
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

.questionnaire-detail__actions {
    display: grid;
    grid-template-columns: 0.8fr 1.2fr;
    gap: 16rpx;
}
</style>
