<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell :bottom-bar="showBottomBar" title="回访问卷" bg="page" :show-back="true">
        <view class="page-wrap cb-page">
            <view v-if="loading" class="cb-pad">
                <base-skeleton :rows="5" variant="text" />
            </view>

            <base-empty
                v-else-if="error"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="load"
            />

            <base-empty
                v-else-if="!questions.length"
                status="empty"
                empty-text="该回访没有配置问卷"
                fixed
            />

            <view v-else class="cb-pad cb-body">
                <!-- 内嵌墨金圆角问卷头卡 -->
                <view class="hero-card">
                    <text class="hero-card__eyebrow">SERVICE FEEDBACK</text>
                    <text class="hero-card__title">{{
                        questionnaire.title || '服务回访问卷'
                    }}</text>
                    <text v-if="questionnaire.description" class="hero-card__desc">{{
                        questionnaire.description
                    }}</text>
                </view>

                <!-- 可视化必填进度卡 -->
                <view v-if="requiredTotal > 0" class="progress-card">
                    <view class="progress-card__top">
                        <text class="progress-card__label">必填进度</text>
                        <text class="progress-card__count"
                            >{{ requiredDone }} / {{ requiredTotal }}</text
                        >
                    </view>
                    <view class="progress-card__track">
                        <view
                            class="progress-card__fill"
                            :style="{ width: `${progressPercent}%` }"
                        />
                    </view>
                </view>

                <view class="questions">
                    <!-- P2 §3-2：一题一卡 → 整页一卡。10 题 = 10 张白卡，滚动 3 屏以上且无进度感；
                         题目之间是同一份问卷的连续项，不是独立信息块。 -->
                    <base-card>
                        <view
                            v-for="(q, qi) in questions"
                            :key="q.id || qi"
                            :id="'q-' + qi"
                            class="q"
                        >
                            <view class="q__title">
                                <text class="q__index">{{ String(qi + 1).padStart(2, '0') }}</text>
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
                                <view
                                    hover-class="is-pressed"
                                    :hover-start-time="20"
                                    :hover-stay-time="70"
                                    :hover-stop-propagation="true"
                                    v-for="(opt, oi) in q.options || []"
                                    :key="oi"
                                    class="opt"
                                    :class="{ 'is-active': answers[keyOf(q)] === optValue(opt) }"
                                    @tap="answers[keyOf(q)] = optValue(opt)"
                                >
                                    <view
                                        class="opt__radio"
                                        :class="{ 'is-on': answers[keyOf(q)] === optValue(opt) }"
                                    />
                                    <text class="opt__text">{{ optLabel(opt) }}</text>
                                </view>
                            </view>

                            <view v-else-if="q.type === 'multiple'" class="q__options">
                                <view
                                    hover-class="is-pressed"
                                    :hover-start-time="20"
                                    :hover-stay-time="70"
                                    :hover-stop-propagation="true"
                                    v-for="(opt, oi) in q.options || []"
                                    :key="oi"
                                    class="opt"
                                    :class="{ 'is-active': isMulti(q, optValue(opt)) }"
                                    @tap="toggleMulti(q, optValue(opt))"
                                >
                                    <view
                                        class="opt__radio opt__radio--square"
                                        :class="{ 'is-on': isMulti(q, optValue(opt)) }"
                                    />
                                    <text class="opt__text">{{ optLabel(opt) }}</text>
                                </view>
                            </view>

                            <template v-else-if="q.type === 'rating'">
                                <view class="q__rating">
                                    <view
                                        hover-class="is-pressed"
                                        :hover-start-time="20"
                                        :hover-stay-time="70"
                                        :hover-stop-propagation="true"
                                        v-for="n in 5"
                                        :key="n"
                                        class="q__rating-item"
                                        @tap="answers[keyOf(q)] = n"
                                    >
                                        <!-- v1 u-icon → v2 tn-icon：评分星形保留（star-fill / star） -->
                                        <tn-icon
                                            :name="
                                                n <= Number(answers[keyOf(q)] || 0)
                                                    ? 'star-fill'
                                                    : 'star'
                                            "
                                            :size="48"
                                            class="ic-star"
                                            :class="{
                                                'is-on': n <= Number(answers[keyOf(q)] || 0)
                                            }"
                                        />
                                    </view>
                                </view>
                                <text
                                    v-if="Number(answers[keyOf(q)] || 0) > 0"
                                    class="q__rating-label"
                                    >{{ SCORE_LABELS[Number(answers[keyOf(q)])] }}</text
                                >
                            </template>
                        </view>
                    </base-card>
                </view>
            </view>
        </view>
        <template #bottom>
            <bottom-bar v-if="showBottomBar">
                <base-button
                    :text="
                        submitting
                            ? '提交中…'
                            : missingCount > 0
                            ? `还差 ${missingCount} 题`
                            : '提交问卷'
                    "
                    :variant="missingCount > 0 ? 'ghost' : 'ink'"
                    size="lg"
                    :loading="submitting"
                    @click="onSubmitTap"
                />
            </bottom-bar>
        </template>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/**
 * 回访问卷填写。
 * 提交结构：{ id, questionnaire_id, answers:[{key,value}] } —— 后端从 answers.questionnaire_id 取配置版本，
 * 缺失时回落到当前启用版本，可能与用户看到的题目不一致，所以必须带。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { getCallbackQuestionnaire, submitCallbackQuestionnaire } from '@/api/aftersale'

const id = ref(0)
const data = ref<any>({})
const loading = ref(true)
const error = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作（状态机骨架保持不变） */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    // contact / 其余：保持现有客服入口，不新增跳转
}
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
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

/** 一题是否已作答（必填判断与进度条共用同一套规则，避免两处口径漂移） */
function isAnswered(q: any) {
    const v = answers[keyOf(q)]
    if (v === undefined || v === '') return false
    if (Array.isArray(v) && v.length === 0) return false
    return true
}

const SCORE_LABELS: Record<number, string> = {
    1: '1 分 · 很不满意',
    2: '2 分 · 不太满意',
    3: '3 分 · 一般',
    4: '4 分 · 比较满意',
    5: '5 分 · 非常满意'
}

/** 必填题总数 / 已完成数 / 还差几题 */
const requiredTotal = computed(() => questions.value.filter((q) => Number(q.required) === 1).length)
const requiredDone = computed(
    () => questions.value.filter((q) => Number(q.required) === 1 && isAnswered(q)).length
)
const missingCount = computed(() => requiredTotal.value - requiredDone.value)
const progressPercent = computed(() => {
    if (!requiredTotal.value) return 0
    return Math.min(100, Math.round((requiredDone.value / requiredTotal.value) * 100))
})
const showBottomBar = computed(() => !loading.value && !error.value && questions.value.length > 0)

/** 提交按钮点击：还差题时滚到第一道未完成的题，而不是弹 toast 让用户自己找 */
function onSubmitTap() {
    if (submitting.value) return
    if (missingCount.value > 0) {
        const idx = questions.value.findIndex((q) => Number(q.required) === 1 && !isAnswered(q))
        if (idx >= 0) {
            // uni 的 SelectorQuery 类型定义不完整（boundingClientRect 被要求传回调），
            // 这里用 any 承接；运行时用法与官方文档一致。
            const query: any = uni.createSelectorQuery()
            query.select(`#q-${idx}`).boundingClientRect()
            query.selectViewport().scrollOffset()
            query.exec((res: any[]) => {
                const rect = res?.[0]
                const viewport = res?.[1]
                if (!rect) return
                // 顶部多留 60px 上下文，避免滚动后题干被导航栏压住
                const top = rect.top + (viewport?.scrollTop || 0) - 60
                uni.pageScrollTo({ scrollTop: Math.max(0, top), duration: 200 })
            })
        }
        return
    }
    handleSubmit()
}

async function handleSubmit() {
    if (submitting.value) return

    for (let i = 0; i < questions.value.length; i += 1) {
        const q = questions.value[i]
        if (Number(q.required) !== 1) continue
        const v = answers[keyOf(q)]
        const empty = v === undefined || v === '' || (Array.isArray(v) && v.length === 0)
        if (empty) {
            toast.info(`请完成第 ${i + 1} 题`)
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
        toast.success('提交成功')
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

.cb-pad {
    padding-left: var(--page-gutter);
    padding-right: var(--page-gutter);
}

.cb-body {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
    padding-bottom: var(--sp-4);
}

.hero-card {
    padding: var(--sp-4);
    border-radius: var(--r-md);
    background: linear-gradient(135deg, var(--c-dark-1), var(--c-dark-2));
    color: var(--c-on-dark);

    &__eyebrow {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-20);
        letter-spacing: 0.08em;
        color: var(--c-brand-soft);
        margin-bottom: var(--sp-1);
    }

    &__title {
        display: block;
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
        overflow-wrap: anywhere;
    }

    &__desc {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
        line-height: var(--lh-loose);
        overflow-wrap: anywhere;
    }
}

.progress-card {
    padding: var(--sp-3) var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--sp-2);
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__count {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-brand-text);
    }

    &__track {
        width: 100%;
        height: 8rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        overflow: hidden;
    }

    &__fill {
        height: 100%;
        border-radius: var(--r-pill);
        background: var(--c-brand-text);
    }
}

.questions {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

/* 整页一卡后，题与题之间用分割线分层（P2 §3-2） */
.q {
    padding: var(--sp-3) 0;

    & + & {
        border-top: 1rpx solid var(--c-line);
    }

    &:first-child {
        padding-top: 0;
    }

    &:last-child {
        padding-bottom: 0;
    }
}

.q__title {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    margin-bottom: var(--sp-3);
}

.q__index {
    flex-shrink: 0;
    padding: 4rpx var(--sp-1);
    border-radius: var(--r-xs);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);
    font-family: var(--font-serif);
    font-size: var(--fs-xs);
    line-height: var(--lh-none);
    color: var(--c-brand-text);
    font-weight: var(--fw-medium);
    margin-top: 4rpx;
}

.q__text {
    flex: 1;
    min-width: 0;
    font-size: var(--fs-md);
    color: var(--c-ink);
    font-weight: var(--fw-medium);
    line-height: var(--lh-body);
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
    line-height: var(--lh-loose);
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

    &-label {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-brand-text);
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
    /* 批 1.5 E：未选中边框原 --c-line 在白底 1.13:1（等于没有边框） */
    border: 1rpx solid var(--c-control-border);
    box-sizing: border-box;

    &.is-active {
        background: var(--c-brand-bg);
        border-color: var(--c-brand-text);

        .opt__text {
            color: var(--c-ink);
            font-weight: var(--fw-medium);
        }
    }

    &__radio {
        width: 32rpx;
        height: 32rpx;
        border-radius: var(--r-pill);
        /* 批 1.5 B：未选边框原 --c-line-strong 在白底 1.40:1，改控件边界色 */
        border: 1rpx solid var(--c-control-border);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-sizing: border-box;

        &--square {
            border-radius: var(--r-xs);
        }

        &.is-on {
            /* 批 1.5 A（口径修正）：墨黑填充只用在 16rpx 圆点上，不用在整块卡片 */
            background: var(--c-ink);
            border-color: var(--c-ink);

            &::after {
                content: '';
                width: 12rpx;
                height: 12rpx;
                border-radius: var(--r-pill);
                /* 圆点填墨黑后内点必须转白：--c-on-brand 是深墨 #181614，落墨黑底会直接消失 */
                background: var(--c-on-dark);
            }
        }
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }
}

/* 动态图标颜色：状态由父级/自身的 is-on 表达，颜色留在 CSS（tn-icon 的 color prop 不认 var()） */
.ic-star {
    color: var(--c-ink-3);
}
.ic-star.is-on {
    color: var(--c-warning);
}
</style>
