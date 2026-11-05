<template>
    <view class="page-wrap cb-page">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="5" variant="text" />
        </view>

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="load" />

        <base-empty
            v-else-if="!questions.length"
            status="empty"
            empty-text="该回访没有配置问卷"
            fixed
        />

        <template v-else>
            <view class="head">
                <text class="head__title">{{ questionnaire.title || '服务回访问卷' }}</text>
                <text v-if="questionnaire.description" class="head__desc">{{ questionnaire.description }}</text>
            </view>

            <view class="page-pad questions">
                <base-card v-for="(q, qi) in questions" :key="q.id || qi">
                    <view class="q__title">
                        <text class="q__index">{{ qi + 1 }}.</text>
                        <text class="q__text">{{ q.title }}</text>
                        <text v-if="Number(q.required) === 1" class="q__required">*</text>
                    </view>

                    <input
                        v-if="q.type === 'text'"
                        v-model="answers[keyOf(q)]"
                        class="q__input"
                        :placeholder="q.placeholder || '请输入'"
                        placeholder-class="q__ph"
                    />

                    <textarea
                        v-else-if="q.type === 'textarea'"
                        v-model="answers[keyOf(q)]"
                        class="q__textarea"
                        :placeholder="q.placeholder || '请输入'"
                        placeholder-class="q__ph"
                    />

                    <view v-else-if="q.type === 'single'" class="q__options">
                        <view hover-class="is-pressed"
                            v-for="(opt, oi) in q.options || []"
                            :key="oi"
                            class="opt"
                            :class="{ 'is-active': answers[keyOf(q)] === optValue(opt) }"
                            @tap="answers[keyOf(q)] = optValue(opt)"
                        >
                            <view class="opt__radio" :class="{ 'is-on': answers[keyOf(q)] === optValue(opt) }" />
                            <text class="opt__text">{{ optLabel(opt) }}</text>
                        </view>
                    </view>

                    <view v-else-if="q.type === 'multiple'" class="q__options">
                        <view hover-class="is-pressed"
                            v-for="(opt, oi) in q.options || []"
                            :key="oi"
                            class="opt"
                            :class="{ 'is-active': isMulti(q, optValue(opt)) }"
                            @tap="toggleMulti(q, optValue(opt))"
                        >
                            <view class="opt__radio opt__radio--square" :class="{ 'is-on': isMulti(q, optValue(opt)) }" />
                            <text class="opt__text">{{ optLabel(opt) }}</text>
                        </view>
                    </view>

                    <view v-else-if="q.type === 'rating'" class="q__rating">
                        <view hover-class="is-pressed"
                            v-for="n in 5"
                            :key="n"
                            class="q__rating-item"
                            @tap="answers[keyOf(q)] = n"
                        >
                            <u-icon
                                :name="n <= Number(answers[keyOf(q)] || 0) ? 'star-fill' : 'star'"
                                :size="48"
                                :color="n <= Number(answers[keyOf(q)] || 0) ? 'var(--c-warning)' : 'var(--c-line-strong)'"
                            />
                        </view>
                    </view>
                </base-card>

                <view class="submit">
                    <base-button text="提交" variant="brand" size="lg" :loading="submitting" @click="handleSubmit" />
                </view>
                <view class="space" />
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
/**
 * 回访问卷填写。
 * 提交结构：{ id, questionnaire_id, answers:[{key,value}] } —— 后端从 answers.questionnaire_id 取配置版本，
 * 缺失时回落到当前启用版本，可能与用户看到的题目不一致，所以必须带。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getCallbackQuestionnaire, submitCallbackQuestionnaire } from '@/api/aftersale'

const id = ref(0)
const data = ref<any>({})
const loading = ref(true)
const error = ref(false)
const submitting = ref(false)
const answers = reactive<Record<string, any>>({})

const questionnaire = computed<any>(() => data.value?.questionnaire || {})
const questions = computed<any[]>(() => {
    const list = questionnaire.value?.questions
    return Array.isArray(list) ? list : []
})

function keyOf(q: any) {
    return String(q.key || q.id)
}

/** 选项可能是字符串，也可能是 {label,value} */
function optValue(opt: any) {
    return typeof opt === 'object' && opt !== null ? opt.value ?? opt.label : opt
}

function optLabel(opt: any) {
    return typeof opt === 'object' && opt !== null ? opt.label ?? String(opt.value) : opt
}

function isMulti(q: any, val: any) {
    const v = answers[keyOf(q)]
    return Array.isArray(v) && v.indexOf(val) >= 0
}

function toggleMulti(q: any, val: any) {
    const k = keyOf(q)
    const cur = Array.isArray(answers[k]) ? [...answers[k]] : []
    const i = cur.indexOf(val)
    if (i >= 0) cur.splice(i, 1)
    else cur.push(val)
    answers[k] = cur
}

async function load() {
    loading.value = true
    error.value = false
    try {
        const res = await getCallbackQuestionnaire({ id: id.value })
        if (!res) throw new Error('empty')
        data.value = res
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
    if (submitting.value) return

    for (let i = 0; i < questions.value.length; i += 1) {
        const q = questions.value[i]
        if (Number(q.required) !== 1) continue
        const v = answers[keyOf(q)]
        const empty = v === undefined || v === '' || (Array.isArray(v) && v.length === 0)
        if (empty) {
            uni.showToast({ title: `请完成第 ${i + 1} 题`, icon: 'none' })
            return
        }
    }

    submitting.value = true
    try {
        const payload = questions.value.map((q) => ({ key: keyOf(q), value: answers[keyOf(q)] }))
        await submitCallbackQuestionnaire({
            id: id.value,
            questionnaire_id: Number(questionnaire.value.id || 0) || undefined,
            answers: payload
        })
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
.cb-page {
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
}

.questions {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    margin-top: -20rpx;
    position: relative;
    z-index: 2;
    padding-top: var(--sp-3);
    padding-bottom: calc(164rpx + var(--safe-bottom));
}

.q__title {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-1);
    margin-bottom: var(--sp-3);
}

.q__index {
    flex-shrink: 0;
    font-size: var(--fs-md);
    color: var(--c-brand-text);
    font-weight: 500;
}

.q__text {
    flex: 1;
    min-width: 0;
    font-size: var(--fs-md);
    color: var(--c-ink);
    font-weight: 500;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.q__required {
    flex-shrink: 0;
    color: var(--c-danger);
    font-size: var(--fs-md);
}

.q__input {
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

.q__textarea {
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

.q__ph {
    color: var(--c-ink-3);
}

.q__options {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.q__rating {
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
