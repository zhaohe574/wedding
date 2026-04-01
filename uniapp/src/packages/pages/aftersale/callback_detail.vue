<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="回访问卷详情" />

        <view v-if="detail" class="callback-detail">
            <view class="callback-detail__status-card">
                <view>
                    <text class="callback-detail__status-text">
                        {{ detail.status_desc || getStatusText(detail.status) }}
                    </text>
                    <text class="callback-detail__status-time">
                        {{ detail.plan_time || detail.create_time }}
                    </text>
                </view>
                <view class="callback-detail__status-tag" :class="getStatusClass(detail.status)">
                    {{ detail.status_desc || getStatusText(detail.status) }}
                </view>
            </view>

            <view class="callback-detail__section">
                <text class="callback-detail__section-title">回访信息</text>
                <view class="callback-detail__kv">
                    <text class="callback-detail__kv-label">回访编号</text>
                    <text class="callback-detail__kv-value">{{ detail.callback_sn }}</text>
                </view>
                <view v-if="detail.order?.order_sn" class="callback-detail__kv">
                    <text class="callback-detail__kv-label">关联订单</text>
                    <text class="callback-detail__kv-value">{{ detail.order.order_sn }}</text>
                </view>
                <view v-if="detail.staff?.name" class="callback-detail__kv">
                    <text class="callback-detail__kv-label">服务人员</text>
                    <text class="callback-detail__kv-value">{{ detail.staff.name }}</text>
                </view>
                <view class="callback-detail__kv">
                    <text class="callback-detail__kv-label">回访类型</text>
                    <text class="callback-detail__kv-value">{{ detail.type_desc || '服务回访' }}</text>
                </view>
            </view>

            <view class="callback-detail__section">
                <text class="callback-detail__section-title">
                    {{ questionnaireTitle }}
                </text>
                <text v-if="questionnaireDescription" class="callback-detail__section-desc">
                    {{ questionnaireDescription }}
                </text>

                <template v-if="detail.status === 0">
                    <view class="callback-detail__field">
                        <text class="callback-detail__field-label">整体评分</text>
                        <u-rate v-model="form.score_overall" :min-count="1" />
                    </view>

                    <view
                        v-for="(question, index) in normalizedQuestions"
                        :key="`${question.key}-${index}`"
                        class="callback-detail__field"
                    >
                        <text class="callback-detail__field-label">{{ question.title }}</text>

                        <textarea
                            v-if="question.type === 'textarea'"
                            v-model="answerMap[question.key]"
                            class="callback-detail__textarea"
                            :placeholder="question.placeholder || '请输入您的反馈'"
                        />

                        <view v-else-if="question.type === 'multiple'" class="callback-detail__options">
                            <view
                                v-for="option in question.options"
                                :key="option"
                                class="callback-detail__option"
                                :class="{ 'is-active': isSelected(question.key, option) }"
                                @click="toggleOption(question.key, option)"
                            >
                                {{ option }}
                            </view>
                        </view>

                        <view v-else class="callback-detail__options">
                            <view
                                v-for="option in question.options"
                                :key="option"
                                class="callback-detail__option"
                                :class="{ 'is-active': answerMap[question.key] === option }"
                                @click="answerMap[question.key] = option"
                            >
                                {{ option }}
                            </view>
                        </view>
                    </view>

                    <view class="callback-detail__field">
                        <text class="callback-detail__field-label">补充反馈</text>
                        <textarea
                            v-model="form.feedback"
                            class="callback-detail__textarea"
                            placeholder="欢迎补充本次服务体验与建议"
                        />
                    </view>
                </template>

                <template v-else>
                    <view class="callback-detail__result-grid">
                        <view class="callback-detail__result-card">
                            <text class="callback-detail__result-label">整体评分</text>
                            <text class="callback-detail__result-value">
                                {{ detail.score_overall || detail.score || 0 }}
                            </text>
                        </view>
                        <view class="callback-detail__result-card">
                            <text class="callback-detail__result-label">服务评分</text>
                            <text class="callback-detail__result-value">
                                {{ detail.score_service || detail.score || 0 }}
                            </text>
                        </view>
                    </view>
                    <text class="callback-detail__result-text">
                        {{ detail.content || '用户未填写补充反馈。' }}
                    </text>
                </template>
            </view>
        </view>

        <view v-if="detail && detail.status === 0" class="callback-detail__action-bar">
            <BaseButton block size="lg" @click="handleSubmit">提交问卷</BaseButton>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { getQuestionnaire, submitQuestionnaire } from '@/api/aftersale'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'

type QuestionType = 'single' | 'multiple' | 'textarea'

interface NormalizedQuestion {
    key: string
    title: string
    type: QuestionType
    options: string[]
    placeholder?: string
}

const $theme = useThemeStore()
const callbackId = ref(0)
const detail = ref<any>(null)
const answerMap = reactive<Record<string, string | string[]>>({})
const form = reactive({
    score_overall: 5,
    feedback: ''
})

const normalizedQuestions = computed<NormalizedQuestion[]>(() => {
    const questions = detail.value?.questionnaire?.questions
    if (!Array.isArray(questions)) {
        return []
    }

    return questions.map((question: any, index: number) => {
        const rawType = String(question?.type || question?.question_type || '').toLowerCase()
        const options = Array.isArray(question?.options)
            ? question.options.map((item: any) =>
                  typeof item === 'string' ? item : item?.label || item?.text || String(item?.value || '')
              )
            : []

        let type: QuestionType = 'single'
        if (rawType.includes('multi') || rawType.includes('checkbox')) {
            type = 'multiple'
        } else if (rawType.includes('text') || rawType.includes('textarea') || options.length === 0) {
            type = 'textarea'
        }

        return {
            key: String(question?.id || question?.key || index),
            title: String(question?.title || question?.question || `问题 ${index + 1}`),
            type,
            options,
            placeholder: question?.placeholder || ''
        }
    })
})

const questionnaireTitle = computed(() => {
    return detail.value?.questionnaire?.title || '服务回访问卷'
})

const questionnaireDescription = computed(() => {
    return detail.value?.questionnaire?.description || ''
})

const hydrateForm = () => {
    form.score_overall = Number(detail.value?.score_overall || detail.value?.score || 5)
    form.feedback = String(detail.value?.content || '')

    normalizedQuestions.value.forEach((question) => {
        if (question.type === 'multiple') {
            answerMap[question.key] = []
        } else {
            answerMap[question.key] = ''
        }
    })
}

const getDetail = async () => {
    try {
        const res = await getQuestionnaire(callbackId.value)
        detail.value = res?.data || res
        hydrateForm()
    } catch (error) {
        uni.showToast({ title: '获取问卷失败', icon: 'none' })
    }
}

const getStatusClass = (status: number) => {
    return status === 0 ? 'status-pending' : 'status-completed'
}

const getStatusText = (status: number) => {
    return status === 0 ? '待填写' : '已完成'
}

const isSelected = (key: string, option: string) => {
    const value = answerMap[key]
    return Array.isArray(value) ? value.includes(option) : false
}

const toggleOption = (key: string, option: string) => {
    const current = Array.isArray(answerMap[key]) ? [...(answerMap[key] as string[])] : []
    const exists = current.includes(option)
    answerMap[key] = exists ? current.filter((item) => item !== option) : [...current, option]
}

const buildAnswers = () => {
    return normalizedQuestions.value.map((question) => ({
        key: question.key,
        title: question.title,
        value: answerMap[question.key]
    }))
}

const handleSubmit = async () => {
    try {
        await submitQuestionnaire({
            id: callbackId.value,
            score: form.score_overall,
            score_service: form.score_overall,
            score_professional: form.score_overall,
            score_punctual: form.score_overall,
            score_overall: form.score_overall,
            feedback: form.feedback.trim(),
            questionnaire_id: detail.value?.questionnaire?.id || 0,
            answers: buildAnswers()
        })
        uni.showToast({ title: '提交成功', icon: 'none' })
        getDetail()
    } catch (error: any) {
        uni.showToast({ title: error?.message || '提交失败', icon: 'none' })
    }
}

onLoad((options: any) => {
    callbackId.value = Number(options?.id || 0)
    if (callbackId.value) {
        getDetail()
    }
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.callback-detail {
    @include aftersale-page-base;
    padding: 0 20rpx 160rpx;
}

.callback-detail__status-card,
.callback-detail__section {
    @include aftersale-section-card;
    margin-bottom: 16rpx;
}

.callback-detail__status-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.callback-detail__status-text {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.callback-detail__status-time {
    display: block;
    margin-top: 8rpx;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.callback-detail__status-tag {
    padding: 10rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 700;
}

.callback-detail__section-title {
    display: block;
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.callback-detail__section-desc {
    display: block;
    margin-top: 12rpx;
    font-size: 24rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.callback-detail__kv {
    @include aftersale-kv-row;
    margin-top: 16rpx;
}

.callback-detail__kv-label {
    @include aftersale-kv-label;
}

.callback-detail__kv-value {
    @include aftersale-kv-value;
}

.callback-detail__field {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    margin-top: 18rpx;
}

.callback-detail__field-label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.callback-detail__textarea {
    min-height: 180rpx;
    padding: 20rpx;
    border-radius: 18rpx;
    border: 1rpx solid $aftersale-border;
    background: rgba(255, 255, 255, 0.82);
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #1e2432);
    box-sizing: border-box;
}

.callback-detail__options {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.callback-detail__option {
    padding: 12rpx 18rpx;
    border-radius: 999rpx;
    border: 1rpx solid $aftersale-border;
    background: rgba(255, 255, 255, 0.8);
    font-size: 24rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.callback-detail__option.is-active {
    color: #ffffff;
    background: var(--wm-color-primary, #e85a4f);
    border-color: var(--wm-color-primary, #e85a4f);
}

.callback-detail__result-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
    margin-top: 18rpx;
}

.callback-detail__result-card {
    padding: 18rpx;
    border-radius: 18rpx;
    background: rgba(255, 247, 244, 0.78);
}

.callback-detail__result-label {
    display: block;
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.callback-detail__result-value {
    display: block;
    margin-top: 10rpx;
    font-size: 40rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.callback-detail__result-text {
    display: block;
    margin-top: 18rpx;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.callback-detail__action-bar {
    position: fixed;
    left: 20rpx;
    right: 20rpx;
    bottom: calc(20rpx + env(safe-area-inset-bottom));
}

.status-pending {
    color: #c98524;
    background: rgba(201, 133, 36, 0.12);
}

.status-completed {
    color: #2f7d58;
    background: rgba(47, 125, 88, 0.12);
}
</style>
