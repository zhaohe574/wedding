<template>
    <view class="page-wrap q-page">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="5" variant="text" />
        </view>

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="load" />

        <template v-else>
            <view class="head">
                <text class="head__title">{{ detail.title_snapshot || '新人问卷' }}</text>
                <text v-if="detail.description_snapshot" class="head__desc">{{ detail.description_snapshot }}</text>
                <view class="head__meta">
                    <status-tag :label="statusLabel" :tone="statusTone" size="sm" />
                    <text v-if="!editable" class="head__hint">已提交，不可修改</text>
                </view>
            </view>

            <view class="page-pad questions" :class="{ 'questions--editable': editable }">
                <base-card v-for="(q, qi) in questions" :key="q.id || qi">
                    <view class="q">
                        <view class="q__title">
                            <text class="q__index">{{ qi + 1 }}.</text>
                            <text class="q__text">{{ q.title }}</text>
                            <text v-if="Number(q.required) === 1" class="q__required">*</text>
                        </view>

                        <!-- 单行文本 -->
                        <input
                            v-if="q.type === 'text'"
                            v-model="answers[q.key || String(q.id)]"
                            class="q__input"
                            :placeholder="q.placeholder || '请输入'"
                            placeholder-class="q__ph"
                            :disabled="!editable"
                        />

                        <!-- 多行文本 -->
                        <textarea
                            v-else-if="q.type === 'textarea'"
                            v-model="answers[q.key || String(q.id)]"
                            class="q__textarea"
                            :placeholder="q.placeholder || '请输入'"
                            placeholder-class="q__ph"
                            :disabled="!editable"
                        />

                        <!-- 单选 -->
                        <view v-else-if="q.type === 'single'" class="q__options">
                            <view hover-class="is-pressed"
                                v-for="(opt, oi) in q.options || []"
                                :key="oi"
                                class="opt"
                                :class="{ 'is-active': answers[q.key || String(q.id)] === opt }"
                                @tap="editable && (answers[q.key || String(q.id)] = opt)"
                            >
                                <view class="opt__radio" :class="{ 'is-on': answers[q.key || String(q.id)] === opt }" />
                                <text class="opt__text">{{ opt }}</text>
                            </view>
                        </view>

                        <!-- 多选 -->
                        <view v-else-if="q.type === 'multiple'" class="q__options">
                            <view hover-class="is-pressed"
                                v-for="(opt, oi) in q.options || []"
                                :key="oi"
                                class="opt"
                                :class="{ 'is-active': isMultiChecked(q, opt) }"
                                @tap="editable && toggleMulti(q, opt)"
                            >
                                <view class="opt__radio opt__radio--square" :class="{ 'is-on': isMultiChecked(q, opt) }" />
                                <text class="opt__text">{{ opt }}</text>
                            </view>
                        </view>

                        <!-- 评分 -->
                        <view v-else-if="q.type === 'rating'" class="q__rating">
                            <view hover-class="is-pressed"
                                v-for="n in 5"
                                :key="n"
                                class="q__rating-item"
                                @tap="editable && (answers[q.key || String(q.id)] = n)"
                            >
                                <u-icon
                                    :name="n <= Number(answers[q.key || String(q.id)] || 0) ? 'star-fill' : 'star'"
                                    :size="48"
                                    :color="n <= Number(answers[q.key || String(q.id)] || 0) ? 'var(--c-warning)' : 'var(--c-line-strong)'"
                                />
                            </view>
                        </view>
                    </view>
                </base-card>

                <view v-if="editable" class="submit">
                    <base-button text="提交问卷" variant="brand" size="lg" :loading="submitting" @click="handleSubmit" />
                </view>
                <view class="space" />
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
/**
 * 新人问卷。
 *
 * 可填条件：can_submit === 1 或 status ∈ [0,3]（待填写/已查看）。
 * 只判断 status===0 会让用户点开一次（变已查看）后就再也填不了 —— 契约测试 CQ-FE-002 明确禁止。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import {
    QUESTIONNAIRE_TASK_STATUS_NAME_MAP,
    QUESTIONNAIRE_TASK_STATUS_TONE,
    QuestionnaireTaskStatus,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getQuestionnaireDetail, submitQuestionnaire } from '@/api/questionnaire'

const id = ref(0)
const detail = ref<any>({})
const questions = ref<any[]>([])
const answers = reactive<Record<string, any>>({})
const loading = ref(true)
const error = ref(false)
const submitting = ref(false)

const status = computed(() => Number(detail.value.status))

const editable = computed(() => {
    const canSubmit = Number(detail.value.can_submit)
    if (canSubmit === 1) return true
    return status.value === QuestionnaireTaskStatus.PENDING || status.value === QuestionnaireTaskStatus.VIEWED
})

const statusLabel = computed(() => {
    const mapped = resolveStatusLabel(QUESTIONNAIRE_TASK_STATUS_NAME_MAP as any, status.value)
    return mapped.known ? mapped.label : detail.value.status_desc || mapped.label
})

const statusTone = computed(() => resolveTone(QUESTIONNAIRE_TASK_STATUS_TONE as any, status.value))

function keyOf(q: any) {
    return q.key || String(q.id)
}

function isMultiChecked(q: any, opt: string) {
    const v = answers[keyOf(q)]
    return Array.isArray(v) && v.includes(opt)
}

function toggleMulti(q: any, opt: string) {
    const k = keyOf(q)
    const cur = Array.isArray(answers[k]) ? [...answers[k]] : []
    const i = cur.indexOf(opt)
    if (i >= 0) cur.splice(i, 1)
    else cur.push(opt)
    answers[k] = cur
}

async function load() {
    loading.value = true
    error.value = false
    try {
        const res = await getQuestionnaireDetail({ id: id.value })
        if (!res) throw new Error('empty')
        detail.value = res
        questions.value = Array.isArray(res.questions) ? res.questions : []

        // 回填已填答案
        const snapshot = (res as any).answer
        if (snapshot && Array.isArray(snapshot.answers)) {
            for (const a of snapshot.answers) {
                answers[a.key] = a.value
            }
        }
        for (const q of questions.value) {
            const k = keyOf(q)
            if (answers[k] === undefined) answers[k] = q.type === 'multiple' ? [] : ''
        }
    } catch {
        error.value = true
    } finally {
        loading.value = false
    }
}

async function handleSubmit() {
    if (submitting.value || !editable.value) return

    // 必填校验
    for (const q of questions.value) {
        if (Number(q.required) !== 1) continue
        const v = answers[keyOf(q)]
        const empty = v === undefined || v === '' || (Array.isArray(v) && v.length === 0)
        if (empty) {
            uni.showToast({ title: `请完成第 ${questions.value.indexOf(q) + 1} 题`, icon: 'none' })
            return
        }
    }

    submitting.value = true
    try {
        const payload = questions.value.map((q) => ({ key: keyOf(q), value: answers[keyOf(q)] }))
        await submitQuestionnaire({ id: id.value, answers: payload })
        uni.showToast({ title: '提交成功', icon: 'success' })
        setTimeout(() => uni.navigateBack(), 600)
    } catch {
        // 请求层已提示
    } finally {
        submitting.value = false
    }
}

onLoad((options: any) => {
    id.value = Number(options?.id || 0)
    if (!id.value) {
        error.value = true
        loading.value = false
        return
    }
    load()
})
</script>

<style lang="scss" scoped>
.q-page {
    background: var(--c-bg);
}

.head {
    background: var(--c-ink);
    padding: var(--sp-5) var(--sp-4);

    &__title {
        display: block;
        font-size: var(--fs-xl);
        font-weight: 500;
        line-height: 1.4;
        color: var(--c-on-dark);
        overflow-wrap: anywhere;
    }

    &__desc {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        line-height: 1.7;
        overflow-wrap: anywhere;
    }

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--sp-2);
        margin-top: var(--sp-3);
    }

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }
}

.questions {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    margin-top: -20rpx;
    position: relative;
    z-index: 2;
    padding-top: var(--sp-3);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));

    &--editable {
        padding-bottom: calc(164rpx + var(--safe-bottom));
    }
}

.q {
    &__title {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-1);
        margin-bottom: var(--sp-3);
    }

    &__index {
        flex-shrink: 0;
        font-size: var(--fs-md);
        color: var(--c-brand-text);
        font-weight: 500;
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
        font-weight: 500;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    &__required {
        flex-shrink: 0;
        color: var(--c-danger);
        font-size: var(--fs-md);
    }

    &__input {
        width: 100%;
        height: 88rpx;
        padding: 0 var(--sp-3);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-line);
        border-radius: var(--r-md);
        box-sizing: border-box;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__textarea {
        width: 100%;
        min-height: 200rpx;
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-line);
        border-radius: var(--r-md);
        padding: var(--sp-3);
        box-sizing: border-box;
        font-size: var(--fs-md);
        color: var(--c-ink);
        line-height: 1.7;
    }

    &__ph {
        color: var(--c-ink-3);
    }

    &__options {
        display: flex;
        flex-direction: column;
        gap: var(--sp-2);
    }

    &__rating {
        display: flex;
        width: 100%;
        max-width: 440rpx;

        &-item {
            flex: 1;
            min-width: 0;
            max-width: 88rpx;
            height: 88rpx;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
}

.opt {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    min-height: 88rpx;
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);
    box-sizing: border-box;

    &.is-active {
        border-color: var(--c-brand);
        background: var(--c-brand-bg);
    }

    &__radio {
        width: 32rpx;
        height: 32rpx;
        border-radius: 50%;
        border: 1rpx solid var(--c-line-strong);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-sizing: border-box;

        &--square {
            border-radius: var(--r-xs);
        }

        &.is-on {
            background: var(--c-brand);
            border-color: var(--c-brand);

            &::after {
                content: '';
                width: 12rpx;
                height: 12rpx;
                border-radius: 50%;
                background: var(--c-on-brand);
            }
        }
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: 1.6;
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }
}

.submit {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-3) + var(--safe-bottom));
    border-top: 1rpx solid var(--c-line);
    background: var(--c-surface);
    box-sizing: border-box;
}

.space {
    height: 0;
}
</style>
