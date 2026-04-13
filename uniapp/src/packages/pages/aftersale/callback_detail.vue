<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="回访问卷详情" />

        <view v-if="detail" class="aftersale-detail-page">
            <view class="aftersale-detail-page__wrapper">
                <AfterSaleStatusBanner
                    label="问卷状态"
                    :title="callbackStatus.label"
                    :summary="callbackStatus.summary"
                    :badges="[{ text: detail.type_desc || '服务回访', tone: 'info' }]"
                    :metrics="bannerMetrics"
                />

                <BaseCard variant="surface" scene="consumer" class="aftersale-detail-card">
                    <text class="aftersale-detail-card__title">回访信息</text>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">回访编号</text>
                        <text class="aftersale-detail-card__value">{{ detail.callback_sn }}</text>
                    </view>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">关联订单</text>
                        <text class="aftersale-detail-card__value">{{
                            detail.order?.order_sn || '未关联'
                        }}</text>
                    </view>
                    <view class="aftersale-detail-card__kv">
                        <text class="aftersale-detail-card__label">服务人员</text>
                        <text class="aftersale-detail-card__value">{{
                            detail.staff?.name || '待补充'
                        }}</text>
                    </view>
                </BaseCard>

                <BaseCard variant="surface" scene="consumer" class="aftersale-detail-card">
                    <text class="aftersale-detail-card__title">
                        {{ detail.questionnaire?.title || '服务回访问卷' }}
                    </text>
                    <text
                        v-if="detail.questionnaire?.description"
                        class="aftersale-detail-card__paragraph"
                    >
                        {{ detail.questionnaire.description }}
                    </text>

                    <template v-if="detail.status === 0">
                        <view class="aftersale-questionnaire__field">
                            <text class="aftersale-questionnaire__label">整体评分</text>
                            <u-rate v-model="scoreOverall" :min-count="1" />
                        </view>

                        <view
                            v-for="(question, index) in questions"
                            :key="`${question.key}-${index}`"
                            class="aftersale-questionnaire__field"
                        >
                            <text class="aftersale-questionnaire__label">{{ question.title }}</text>

                            <textarea
                                v-if="question.type === 'textarea'"
                                v-model="answerMap[question.key]"
                                class="aftersale-questionnaire__textarea"
                                :placeholder="question.placeholder || '请输入反馈'"
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
                                    {{ detail.score_overall || detail.score || 0 }}
                                </text>
                            </view>
                            <view class="aftersale-questionnaire__result-card">
                                <text class="aftersale-questionnaire__result-label">反馈状态</text>
                                <text class="aftersale-questionnaire__result-value">已提交</text>
                            </view>
                        </view>
                        <text class="aftersale-detail-card__paragraph">
                            {{ detail.content || '未填写补充反馈。' }}
                        </text>
                    </template>
                </BaseCard>
            </view>
        </view>

        <ActionArea v-if="detail && detail.status === 0" sticky safeBottom>
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
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'
import AfterSaleStatusBanner from './components/AfterSaleStatusBanner.vue'
import { useAftersaleQuestionnaire } from './composables/useAftersaleQuestionnaire'
import { getCallbackStatusMeta } from './shared'

const $theme = useThemeStore()
const callbackId = ref(0)
const detail = ref<any>(null)

const callbackStatus = computed(() => getCallbackStatusMeta(Number(detail.value?.status || 0)))
const bannerMetrics = computed(() => [
    {
        label: '计划时间',
        value: String(detail.value?.plan_time || detail.value?.create_time || '-')
    },
    { label: '当前状态', value: callbackStatus.value.label }
])

const { questions, answerMap, scoreOverall, feedback, isSelected, toggleOption, buildAnswers } =
    useAftersaleQuestionnaire(detail)

const getDetail = async () => {
    try {
        const res = await getQuestionnaire(callbackId.value)
        detail.value = res?.data || res
    } catch (error) {
        uni.showToast({ title: '获取问卷失败', icon: 'none' })
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
        uni.showToast({ title: '提交成功', icon: 'none' })
        await getDetail()
    } catch (error: any) {
        uni.showToast({ title: error?.message || '提交失败', icon: 'none' })
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
}

.aftersale-detail-card {
    @include aftersale-detail-card;
}

.aftersale-detail-card__title {
    @include aftersale-detail-section-title;
    margin-bottom: 0;
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

.aftersale-questionnaire__field {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.aftersale-questionnaire__label {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
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
    min-height: 180rpx;
    padding: 22rpx 24rpx;
    @include aftersale-input-surface;
    box-sizing: border-box;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-questionnaire__result-grid {
    @include aftersale-result-grid;
}

.aftersale-questionnaire__result-card {
    @include aftersale-result-card;
}

.aftersale-questionnaire__result-label {
    display: block;
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.aftersale-questionnaire__result-value {
    display: block;
    margin-top: 8rpx;
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.aftersale-detail-page__actions {
    @include aftersale-action-row;
}
</style>
