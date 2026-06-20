<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="回访问卷详情"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view v-if="detail" class="aftersale-detail-page">
            <view class="aftersale-detail-page__wrapper wm-page-content">
                <BaseCard variant="hero" scene="consumer" class="callback-status-card">
                    <view class="callback-status-card__top">
                        <view class="callback-status-card__copy">
                            <text class="callback-status-card__label">问卷状态</text>
                            <text class="callback-status-card__title">
                                {{ callbackStatus.label }}
                            </text>
                        </view>

                        <StatusBadge tone="primary" size="sm">
                            {{ callbackTypeText }}
                        </StatusBadge>
                    </view>

                    <view class="callback-status-card__metrics">
                        <view
                            v-for="item in bannerMetrics"
                            :key="item.label"
                            class="callback-status-card__metric"
                        >
                            <text class="callback-status-card__metric-label">{{ item.label }}</text>
                            <text class="callback-status-card__metric-value">{{ item.value }}</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="aftersale-detail-card">
                    <view class="aftersale-detail-card__head">
                        <text class="aftersale-detail-card__title">回访信息</text>
                        <StatusBadge :tone="callbackStatus.tone" size="sm" dot>
                            {{ callbackStatus.label }}
                        </StatusBadge>
                    </view>

                    <view class="callback-info-grid">
                        <view
                            v-for="item in infoItems"
                            :key="item.label"
                            class="callback-info-item"
                        >
                            <text class="callback-info-item__label">{{ item.label }}</text>
                            <text class="callback-info-item__value">{{ item.value }}</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="aftersale-detail-card">
                    <view class="aftersale-detail-card__head">
                        <view class="aftersale-detail-card__title-group">
                            <text class="aftersale-detail-card__title">
                                {{ questionnaireTitle }}
                            </text>
                            <text v-if="questions.length" class="aftersale-detail-card__caption">
                                {{ questions.length }} 个问题
                            </text>
                        </view>

                        <StatusBadge
                            :tone="isPending ? 'warning' : 'success'"
                            size="sm"
                        >
                            {{ isPending ? '待提交' : '已提交' }}
                        </StatusBadge>
                    </view>

                    <template v-if="isPending">
                        <view class="aftersale-questionnaire__field">
                            <view class="aftersale-questionnaire__field-head">
                                <text class="aftersale-questionnaire__label">整体评分</text>
                                <text class="aftersale-questionnaire__score">
                                    {{ scoreOverall }} 分
                                </text>
                            </view>
                            <view class="aftersale-questionnaire__rate">
                                <u-rate v-model="scoreOverall" :min-count="1" />
                            </view>
                        </view>

                        <view
                            v-for="(question, index) in questions"
                            :key="`${question.key}-${index}`"
                            class="aftersale-questionnaire__field"
                        >
                            <view class="aftersale-questionnaire__field-head">
                                <text class="aftersale-questionnaire__label">
                                    {{ question.title }}
                                </text>
                                <text
                                    v-if="question.type === 'rating'"
                                    class="aftersale-questionnaire__score"
                                >
                                    {{ getRatingValue(question.key) }} 分
                                </text>
                            </view>

                            <view
                                v-if="question.type === 'rating'"
                                class="aftersale-questionnaire__rate"
                            >
                                <u-rate
                                    :model-value="getRatingValue(question.key)"
                                    :min-count="1"
                                    @update:model-value="
                                        setRatingValue(question.key, Number($event || 5))
                                    "
                                    @change="setRatingValue(question.key, Number($event || 5))"
                                />
                            </view>

                            <textarea
                                v-else-if="question.type === 'textarea'"
                                :value="String(answerMap[question.key] || '')"
                                class="aftersale-questionnaire__textarea"
                                :placeholder="question.placeholder || '请输入反馈'"
                                @input="handleTextInput(question.key, $event)"
                            />

                            <view v-else class="aftersale-questionnaire__options">
                                <view
                                    v-for="option in question.options"
                                    :key="option"
                                    class="aftersale-questionnaire__option"
                                    :class="{
                                        'is-active':
                                            question.type === 'multiple'
                                                ? isSelected(question.key, option)
                                                : answerMap[question.key] === option
                                    }"
                                    @click="
                                        question.type === 'multiple'
                                            ? toggleOption(question.key, option)
                                            : (answerMap[question.key] = option)
                                    "
                                >
                                    {{ option }}
                                </view>
                            </view>
                        </view>

                        <view class="aftersale-questionnaire__field">
                            <text class="aftersale-questionnaire__label">补充反馈</text>
                            <textarea
                                v-model="feedback"
                                class="aftersale-questionnaire__textarea"
                                placeholder="可补充本次体验"
                            />
                        </view>
                    </template>

                    <template v-else>
                        <view class="aftersale-questionnaire__result-grid">
                            <view class="aftersale-questionnaire__result-card">
                                <text class="aftersale-questionnaire__result-label">整体评分</text>
                                <text class="aftersale-questionnaire__result-value">
                                    {{ resultScoreText }}
                                </text>
                            </view>
                            <view class="aftersale-questionnaire__result-card">
                                <text class="aftersale-questionnaire__result-label">提交时间</text>
                                <text class="aftersale-questionnaire__result-value">
                                    {{ actualTimeText }}
                                </text>
                            </view>
                        </view>
                        <view class="aftersale-questionnaire__feedback">
                            <text class="aftersale-questionnaire__feedback-label">补充反馈</text>
                            <text class="aftersale-questionnaire__feedback-text">
                                {{ detail.content || '未填写补充反馈。' }}
                            </text>
                        </view>
                    </template>
                </BaseCard>
            </view>
        </view>

        <ActionArea v-if="detail && isPending" sticky safeBottom>
            <view class="aftersale-detail-page__actions">
                <BaseButton variant="primary" size="lg" block @click="handleSubmit">
                    提交问卷
                </BaseButton>
            </view>
        </ActionArea>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { getQuestionnaire, submitQuestionnaire } from '@/packages/common/api/aftersale'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import ActionArea from '@/components/base/ActionArea.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'
import { showError, showSuccess } from '@/utils/feedback'
import { useAftersaleQuestionnaire } from './composables/useAftersaleQuestionnaire'
import { getCallbackStatusMeta, getValueText } from './shared'

const $theme = useThemeStore()
const callbackId = ref(0)
const detail = ref<any>(null)

const formatTimeText = (value: unknown, fallback = '待补充') => {
    if (value === undefined || value === null || value === '') {
        return fallback
    }

    if (typeof value === 'number' || /^\d+$/.test(String(value))) {
        const timestamp = Number(value)
        if (timestamp <= 0) {
            return fallback
        }

        const date = new Date(timestamp * 1000)
        const month = `${date.getMonth() + 1}`.padStart(2, '0')
        const day = `${date.getDate()}`.padStart(2, '0')
        const hour = `${date.getHours()}`.padStart(2, '0')
        const minute = `${date.getMinutes()}`.padStart(2, '0')
        return `${month}.${day} ${hour}:${minute}`
    }

    return getValueText(value, fallback)
}

const callbackStatus = computed(() => getCallbackStatusMeta(Number(detail.value?.status || 0)))
const isPending = computed(() => Number(detail.value?.status || 0) === 0)
const callbackTypeText = computed(() => getValueText(detail.value?.type_desc, '服务回访'))
const questionnaireTitle = computed(() =>
    getValueText(detail.value?.questionnaire?.title, '服务回访问卷')
)
const actualTimeText = computed(() =>
    formatTimeText(detail.value?.actual_time || detail.value?.update_time, '已提交')
)
const resultScoreText = computed(() => {
    const score = Number(detail.value?.score_overall || detail.value?.score || 0)
    return score > 0 ? `${score} 分` : '未评分'
})
const bannerMetrics = computed(() => [
    {
        label: '计划时间',
        value: formatTimeText(detail.value?.plan_time || detail.value?.create_time)
    },
    { label: '服务人员', value: getValueText(detail.value?.staff?.name, '待补充') }
])
const infoItems = computed(() => [
    {
        label: '回访编号',
        value: getValueText(detail.value?.callback_sn, '编号待补充')
    },
    {
        label: '关联订单',
        value: getValueText(detail.value?.order?.order_sn, '未关联')
    },
    {
        label: '服务人员',
        value: getValueText(detail.value?.staff?.name, '待补充')
    },
    {
        label: '计划时间',
        value: formatTimeText(detail.value?.plan_time || detail.value?.create_time)
    }
])

const {
    questions,
    answerMap,
    scoreOverall,
    feedback,
    isSelected,
    toggleOption,
    getRatingValue,
    setRatingValue,
    buildAnswers
} = useAftersaleQuestionnaire(detail)

const handleTextInput = (key: string, event: any) => {
    answerMap[key] = String(event?.detail?.value || '')
}

const getDetail = async () => {
    try {
        const res = await getQuestionnaire(callbackId.value)
        detail.value = res?.data || res
    } catch (error) {
        showError('获取问卷失败')
    }
}

const handleSubmit = async () => {
    try {
        await submitQuestionnaire({
            id: callbackId.value,
            score: scoreOverall.value,
            score_service: scoreOverall.value,
            score_professional: scoreOverall.value,
            score_punctual: scoreOverall.value,
            score_overall: scoreOverall.value,
            feedback: feedback.value.trim(),
            questionnaire_id: detail.value?.questionnaire?.id || 0,
            answers: buildAnswers()
        })
        showSuccess('提交成功')
        await getDetail()
    } catch (error: any) {
        showError(error, '提交失败')
    }
}

onLoad((options: any) => {
    callbackId.value = Number(options?.id || 0)
    if (callbackId.value) {
        void getDetail()
    }
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.aftersale-detail-page {
    @include aftersale-page-base;
    min-height: 100vh;
}

.aftersale-detail-page__wrapper {
    @include aftersale-page-wrapper;
    gap: 18rpx;
    padding-top: 16rpx;
    padding-bottom: calc(var(--wm-space-section-gap-lg, 30rpx) + 132rpx);
}

.callback-status-card {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

.callback-status-card__top,
.callback-status-card__metrics {
    position: relative;
    z-index: 1;
}

.callback-status-card__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20rpx;
}

.callback-status-card__copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.callback-status-card__label {
    font-size: 22rpx;
    line-height: 1.2;
    font-weight: 800;
    color: var(--wm-color-champagne, #d9be82);
}

.callback-status-card__title {
    display: block;
    font-size: 42rpx;
    line-height: 1.18;
    font-weight: 900;
    color: var(--wm-text-inverse, #fffdf8);
}

.callback-status-card__metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14rpx;
}

.callback-status-card__metric {
    min-width: 0;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.24);
    background: rgba(255, 253, 248, 0.08);
    box-sizing: border-box;
}

.callback-status-card__metric-label {
    display: block;
    font-size: 21rpx;
    line-height: 1.35;
    color: rgba(255, 253, 248, 0.62);
}

.callback-status-card__metric-value {
    display: block;
    min-width: 0;
    margin-top: 8rpx;
    font-size: 26rpx;
    line-height: 1.35;
    font-weight: 900;
    color: var(--wm-text-inverse, #fffdf8);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.aftersale-detail-card {
    @include aftersale-detail-card;
    gap: 20rpx;
}

.aftersale-detail-card__head {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18rpx;
}

.aftersale-detail-card__title-group {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.aftersale-detail-card__title {
    @include aftersale-detail-section-title;
    margin-bottom: 0;
}

.aftersale-detail-card__caption {
    display: block;
    font-size: 22rpx;
    line-height: 1.35;
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-detail-card__paragraph {
    @include aftersale-detail-card-paragraph;
}

.aftersale-detail-card__kv {
    @include aftersale-kv-row;
}

.aftersale-detail-card__label {
    @include aftersale-kv-label;
}

.aftersale-detail-card__value {
    @include aftersale-kv-value;
}

.callback-info-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
}

.callback-info-item {
    min-width: 0;
    padding: 18rpx 20rpx;
    border-radius: 24rpx;
    background: rgba(248, 242, 228, 0.58);
    border: 1rpx solid rgba(216, 201, 173, 0.62);
    box-sizing: border-box;
}

.callback-info-item__label {
    display: block;
    font-size: 21rpx;
    line-height: 1.35;
    color: var(--wm-text-tertiary, #9a9388);
}

.callback-info-item__value {
    display: block;
    min-width: 0;
    margin-top: 8rpx;
    font-size: 25rpx;
    line-height: 1.42;
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.aftersale-questionnaire__field {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 20rpx;
    border-radius: 26rpx;
    background: rgba(248, 242, 228, 0.52);
    border: 1rpx solid rgba(216, 201, 173, 0.58);
    box-sizing: border-box;
}

.aftersale-questionnaire__field + .aftersale-questionnaire__field {
    margin-top: 18rpx;
}

.aftersale-questionnaire__field-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.aftersale-questionnaire__label {
    min-width: 0;
    flex: 1;
    font-size: 25rpx;
    line-height: 1.42;
    font-weight: 800;
    color: var(--wm-text-primary, #191713);
}

.aftersale-questionnaire__score {
    flex-shrink: 0;
    font-size: 22rpx;
    line-height: 1.3;
    font-weight: 900;
    color: var(--wm-color-gold, #b8954a);
}

.aftersale-questionnaire__rate {
    min-height: 54rpx;
    display: flex;
    align-items: center;
}

.aftersale-questionnaire__options {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
}

.aftersale-questionnaire__option {
    @include aftersale-choice-chip;

    &.is-active {
        @include aftersale-choice-chip-active;
    }
}

.aftersale-questionnaire__textarea {
    display: block;
    width: 100%;
    max-width: 100%;
    min-height: 180rpx;
    padding: 22rpx 24rpx;
    @include aftersale-input-surface;
    box-sizing: border-box;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #111111);
}

.aftersale-questionnaire__field .aftersale-questionnaire__textarea {
    align-self: stretch;
    margin-top: 2rpx;
}

.aftersale-questionnaire__result-grid {
    @include aftersale-result-grid;
    gap: 14rpx;
}

.aftersale-questionnaire__result-card {
    @include aftersale-result-card;
    min-width: 0;
}

.aftersale-questionnaire__result-label {
    display: block;
    font-size: 22rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.aftersale-questionnaire__result-value {
    display: block;
    margin-top: 8rpx;
    min-width: 0;
    font-size: 28rpx;
    line-height: 1.28;
    font-weight: 900;
    color: var(--wm-text-primary, #111111);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.aftersale-questionnaire__feedback {
    margin-top: 18rpx;
    padding: 22rpx 24rpx;
    border-radius: 24rpx;
    background: rgba(255, 253, 248, 0.82);
    border: 1rpx solid rgba(216, 201, 173, 0.62);
}

.aftersale-questionnaire__feedback-label {
    display: block;
    font-size: 22rpx;
    line-height: 1.35;
    color: var(--wm-text-tertiary, #9a9388);
}

.aftersale-questionnaire__feedback-text {
    display: block;
    margin-top: 10rpx;
    font-size: 26rpx;
    line-height: 1.65;
    color: var(--wm-text-primary, #191713);
}

.aftersale-detail-page__actions {
    @include aftersale-action-row;
}

@media screen and (max-width: 360px) {
    .callback-status-card__top,
    .aftersale-detail-card__head {
        align-items: stretch;
        flex-direction: column;
    }

    .callback-status-card__metrics,
    .callback-info-grid,
    .aftersale-questionnaire__result-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .aftersale-questionnaire__field-head {
        align-items: flex-start;
        flex-direction: column;
        gap: 8rpx;
    }
}
</style>
