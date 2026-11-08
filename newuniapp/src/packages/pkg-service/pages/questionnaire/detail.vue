<template>
    <!--
        11 新人问卷（设计稿 2999–3094） / 12 问卷已提交（3094–3165，同页状态）
        设计意图：让填问卷像聊天，不像交作业——每道题一句说明，进度与草稿让用户敢退出。

        三条实现口径：
        1. 可填条件 = can_submit===1 或 status ∈ [0,3]。只看 status===0 会让用户点开一次后再也交不了
           （后端把「点开」记为已查看）——这是契约测试明令禁止的写法。
        2. 评分题按后端 5 星渲染（F-32：后端无 1–10 刻度），设计稿的两端标注保留
        3. 草稿存本地（F-34：后端无草稿接口）；提交幂等键在成功前复用同一个
    -->
    <page-shell :title="detail.title_snapshot || '新人问卷'" bg="page" :show-back="true">
        <view v-if="loading" class="pad">
            <base-skeleton :rows="5" />
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

        <!-- 12 问卷已提交：同页状态，不新建页面 -->
        <view v-else-if="submitted" class="done">
            <view class="done__seal">
                <brand-seal :size="123" round label="S" variant="light" />
            </view>
            <text class="done__title">问卷已提交</text>
            <text class="done__sub">
                {{ submittedTime ? `${submittedTime} 已提交` : '已提交' }} · 策划师会在 3
                个工作日内给出台本初稿
            </text>

            <view class="done__card">
                <view class="done__row">
                    <text class="done__k">问卷版本</text>
                    <text class="done__v">第 {{ detail.version_no || 1 }} 版</text>
                </view>
                <view class="done__row">
                    <text class="done__k">已答题目</text>
                    <text class="done__v">{{ questions.length }} 题</text>
                </view>
                <view v-if="detail.staff_name" class="done__row done__row--last">
                    <text class="done__k">策划师</text>
                    <text class="done__v">{{ detail.staff_name }}</text>
                </view>
            </view>

            <view class="done__acts">
                <base-button
                    text="查看我的答案"
                    variant="ghost"
                    size="lg"
                    @click="reviewMode = true"
                />
                <base-button text="返回" variant="ink" size="lg" @click="goBack" />
            </view>
        </view>

        <template v-else>
            <!-- 进度条 + 草稿状态 -->
            <view class="qbar">
                <view class="qbar__top">
                    <text class="qbar__text">
                        已完成 <text class="qbar__num">{{ answeredCount }}</text> /
                        {{ questions.length }} 题
                    </text>
                    <view class="qbar__draft">
                        <view class="qbar__dot" :class="{ 'is-off': !draftSaved }" />
                        <text class="qbar__draft-text">{{
                            draftSaved ? '草稿已保存' : '未保存'
                        }}</text>
                    </view>
                </view>
                <view class="qbar__track">
                    <view class="qbar__fill" :style="{ width: `${progress}%` }" />
                </view>
            </view>

            <view v-if="!editable" class="locked">
                <tn-icon name="tips" :size="24" color="var(--c-ink-3)" />
                <text class="locked__text">这份问卷已提交，以下内容不可修改</text>
            </view>

            <!-- 题目 -->
            <view v-for="(q, qi) in questions" :key="keyOf(q)" class="qblock">
                <view class="qblock__h">
                    <text class="qblock__idx">{{ String(qi + 1).padStart(2, '0') }}</text>
                    <text class="qblock__title">{{ q.title }}</text>
                    <text v-if="Number(q.required) === 1" class="qblock__req">必填</text>
                    <text v-else-if="q.type === 'multiple'" class="qblock__hint">可多选</text>
                </view>
                <text v-if="q.hint" class="qblock__note">{{ q.hint }}</text>

                <!-- 单选 -->
                <view v-if="q.type === 'single'" class="chips">
                    <view
                        v-for="opt in q.options || []"
                        :key="opt"
                        class="chip"
                        :class="{ 'is-on': answers[keyOf(q)] === opt, 'is-lock': !editable }"
                        hover-class="is-pressed"
                        @tap="pickSingle(q, opt)"
                    >
                        <text class="chip__text">{{ opt }}</text>
                    </view>
                </view>

                <!-- 多选 -->
                <view v-else-if="q.type === 'multiple'" class="chips">
                    <view
                        v-for="opt in q.options || []"
                        :key="opt"
                        class="chip"
                        :class="{ 'is-on': isPicked(q, opt), 'is-lock': !editable }"
                        hover-class="is-pressed"
                        @tap="toggleMulti(q, opt)"
                    >
                        <text class="chip__text">{{ opt }}</text>
                    </view>
                </view>

                <!-- 评分：后端固定 5 星（F-32），两端含义保留 -->
                <view v-else-if="q.type === 'rating'" class="score">
                    <view class="score__row">
                        <view
                            v-for="n in ratingMax(q)"
                            :key="n"
                            class="score__item"
                            :class="{ 'is-on': Number(answers[keyOf(q)] || 0) >= n }"
                            hover-class="is-pressed"
                            @tap="pickRating(q, n)"
                        >
                            <!-- 第 7 处评分星：未选星原 --c-line-strong 仅 1.60:1（看不出有几颗星）→ --c-ink-3，
                                 与 publish×2 / callback-detail / complaint-detail / ticket-detail / staff-detail 统一 -->
                            <tn-icon
                                :name="Number(answers[keyOf(q)] || 0) >= n ? 'star-fill' : 'star'"
                                :size="40"
                                :color="
                                    Number(answers[keyOf(q)] || 0) >= n
                                        ? 'var(--c-warning)'
                                        : 'var(--c-ink-3)'
                                "
                            />
                        </view>
                    </view>
                    <view v-if="ratingLabels(q)" class="score__labels">
                        <text class="score__label">{{ ratingLabels(q)!.min }}</text>
                        <text class="score__label">{{ ratingLabels(q)!.max }}</text>
                    </view>
                </view>

                <!-- 长文本 / 短文本 -->
                <view v-else class="ta-wrap">
                    <textarea
                        :value="String(answers[keyOf(q)] || '')"
                        class="ta"
                        :disabled="!editable"
                        :maxlength="q.type === 'textarea' ? 500 : 60"
                        :placeholder="
                            q.placeholder || (q.type === 'textarea' ? '写几句就好' : '请输入')
                        "
                        placeholder-class="ta__ph"
                        :auto-height="q.type === 'textarea'"
                        @input="onTextInput(q, $event)"
                    />
                    <text v-if="q.type === 'textarea'" class="ta__count">
                        {{ String(answers[keyOf(q)] || '').length }} / 500
                    </text>
                </view>
            </view>

            <view class="qfoot">
                <text class="qfoot__text">问卷内容只用于台本编排，不会对外展示</text>
            </view>
        </template>
    </page-shell>

    <!-- 吸底提交栏：未完成时把原因写在按钮上，不装死 -->
    <view v-if="!loading && !error && !submitted && editable" class="bar">
        <view class="bar__in">
            <view class="bar__left">
                <text class="bar__label">进度</text>
                <text class="bar__value">{{ answeredCount }} / {{ questions.length }}</text>
            </view>
            <view
                class="bar__btn"
                :class="{ 'is-off': missingRequired.length > 0 }"
                hover-class="is-pressed"
                @tap="onSubmitTap"
            >
                <text class="bar__btn-text">
                    {{
                        submitting
                            ? '提交中…'
                            : missingRequired.length
                            ? `还差 ${missingRequired.length} 题`
                            : '提交问卷'
                    }}
                </text>
            </view>
        </view>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 新人问卷。
 *
 * 幂等：提交失败后重试必须复用同一个 idempotencyKey（请求层默认一次调用一个 key，
 * 「失败→再点一次」是两次调用，会拿到两个 key）。key 存在页面变量 + 本地，
 * 提交成功后才清。
 */
import { computed, reactive, ref, watch } from 'vue'
import { onLoad, onUnload } from '@dcloudio/uni-app'
import { getQuestionnaireDetail, submitQuestionnaire } from '@/api/questionnaire'
import { toast } from '@/composables/useToast'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { report } from '@/platform/report'

const id = ref(0)
const detail = ref<any>({})
const questions = computed<any[]>(() =>
    Array.isArray(detail.value.questions) ? detail.value.questions : []
)
const loading = ref(true)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: true }))

const answers = reactive<Record<string, any>>({})
const submitting = ref(false)
const submitted = ref(false)
const reviewMode = ref(false)
const draftSaved = ref(false)
const submittedTime = ref('')

let draftTimer: ReturnType<typeof setTimeout> | null = null
let idemKey = ''

const DRAFT_KEY = () => `q_draft_${id.value}`

/**
 * 可填条件：can_submit===1 或 status ∈ [0,3]。
 * 后端把「点开」记为已查看（status=1），只认 status===0 会让用户永远交不了。
 */
const editable = computed(() => {
    if (submitted.value || reviewMode.value) return false
    const d = detail.value || {}
    if (Number(d.can_submit) === 1) return true
    const st = Number(d.status)
    return [0, 1, 3].includes(st)
})

function keyOf(q: any) {
    return q.key || String(q.id)
}

function ratingMax(q: any) {
    const n = Number(q?.rating?.max || 0)
    return n > 0 ? n : 5
}

function ratingLabels(q: any) {
    const r = q?.rating
    if (!r) return null
    const min = r.min_label || '平静'
    const max = r.max_label || '热烈'
    return { min, max }
}

const answeredCount = computed(() => {
    let n = 0
    questions.value.forEach((q) => {
        const v = answers[keyOf(q)]
        if (
            Array.isArray(v)
                ? v.length > 0
                : v !== undefined && v !== null && String(v).trim() !== ''
        )
            n += 1
    })
    return n
})

const progress = computed(() => {
    const total = questions.value.length || 1
    return Math.min(100, Math.round((answeredCount.value / total) * 100))
})

/** 未完成的必填题（用于「还差 N 题」） */
const missingRequired = computed(() => {
    return questions.value.filter((q) => {
        if (Number(q.required) !== 1) return false
        const v = answers[keyOf(q)]
        return Array.isArray(v)
            ? v.length === 0
            : v === undefined || v === null || String(v).trim() === ''
    })
})

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    if (kind === 'reselect' || kind === 'back') uni.navigateBack()
}

function pickSingle(q: any, opt: string) {
    if (!editable.value) return
    answers[keyOf(q)] = answers[keyOf(q)] === opt ? '' : opt
    onInput()
}

function isPicked(q: any, opt: string) {
    const v = answers[keyOf(q)]
    return Array.isArray(v) && v.includes(opt)
}

function toggleMulti(q: any, opt: string) {
    if (!editable.value) return
    const k = keyOf(q)
    const cur = Array.isArray(answers[k]) ? [...answers[k]] : []
    const i = cur.indexOf(opt)
    if (i > -1) cur.splice(i, 1)
    else {
        const max = Number(q.max_select || 0)
        if (max > 0 && cur.length >= max) {
            toast.info(`最多选 ${max} 项`)
            return
        }
        cur.push(opt)
    }
    answers[k] = cur
    onInput()
}

function pickRating(q: any, n: number) {
    if (!editable.value) return
    answers[keyOf(q)] = answers[keyOf(q)] === n ? 0 : n
    onInput()
}

/** 输入即存草稿（600ms 防抖），让用户随时敢退出 */
function onTextInput(q: any, e: any) {
    if (!editable.value) return
    answers[keyOf(q)] = e?.detail?.value ?? ''
    onInput()
}

function onInput() {
    if (draftTimer) clearTimeout(draftTimer)
    draftSaved.value = false
    draftTimer = setTimeout(() => {
        saveDraft()
    }, 600)
}

function saveDraft() {
    try {
        const payload: Record<string, any> = {}
        questions.value.forEach((q) => {
            const v = answers[keyOf(q)]
            if (
                v !== undefined &&
                v !== null &&
                String(v).trim() !== '' &&
                !(Array.isArray(v) && !v.length)
            ) {
                payload[keyOf(q)] = v
            }
        })
        uni.setStorageSync(DRAFT_KEY(), { answers: payload, saved_at: Date.now() })
        draftSaved.value = true
    } catch {
        draftSaved.value = false
    }
}

function restoreDraft() {
    try {
        const saved: any = uni.getStorageSync(DRAFT_KEY())
        if (saved && saved.answers && typeof saved.answers === 'object') {
            Object.keys(saved.answers).forEach((k) => {
                answers[k] = saved.answers[k]
            })
            draftSaved.value = true
        }
    } catch {
        // 忽略损坏草稿
    }
}

async function load() {
    loading.value = true
    rawError.value = null
    try {
        const res: any = await getQuestionnaireDetail({ id: id.value })
        detail.value = res || {}
        submittedTime.value = detail.value.submit_time || ''

        // 已提交：进入 12 屏状态，答案直接展示（不可编辑）
        const st = Number(detail.value.status)
        const snap = detail.value.answer
        if (snap && Array.isArray(snap.answers) && snap.answers.length) {
            snap.answers.forEach((a: any) => {
                answers[a.key] = a.value
            })
            submitted.value = st === 2 || !editable.value
        }

        // 初始化答案容器
        questions.value.forEach((q) => {
            const k = keyOf(q)
            if (answers[k] === undefined) answers[k] = q.type === 'multiple' ? [] : ''
        })

        // 未提交时恢复本地草稿
        if (!submitted.value) restoreDraft()
    } catch (e) {
        rawError.value = e
        report('api_error', { endpoint: 'couple_questionnaire.detail', message: '问卷加载失败' })
    } finally {
        loading.value = false
    }
}

function onSubmitTap() {
    if (submitting.value) return
    if (missingRequired.value.length) {
        const first = questions.value.findIndex((q) => missingRequired.value.includes(q))
        toast.info(`请先完成第 ${first + 1} 题`)
        return
    }
    doSubmit()
}

async function doSubmit() {
    if (submitting.value) return
    submitting.value = true
    if (!idemKey) idemKey = `q-${id.value}-${Date.now()}`
    try {
        const payload = questions.value.map((q) => ({ key: keyOf(q), value: answers[keyOf(q)] }))
        await submitQuestionnaire({ id: id.value, answers: payload as any }, idemKey)
        // 成功才清草稿与幂等键
        try {
            uni.removeStorageSync(DRAFT_KEY())
        } catch {
            // 忽略
        }
        idemKey = ''
        submitted.value = true
        submittedTime.value = new Date().toISOString().slice(0, 16).replace('T', ' ')
        toast.success('提交成功')
    } catch (e: any) {
        // 失败保留 key，下次重试复用，避免重复提交
        toast.error(e?.message || '提交失败，请重试')
        report('api_error', { endpoint: 'couple_questionnaire.submit', message: '问卷提交失败' })
    } finally {
        submitting.value = false
    }
}

function goBack() {
    uni.navigateBack()
}

onLoad((options: any) => {
    id.value = Number(options?.id || 0)
    if (!id.value) {
        rawError.value = { code: 'PARAM', message: '缺少问卷 id' }
        loading.value = false
        return
    }
    load()
})

onUnload(() => {
    if (draftTimer) clearTimeout(draftTimer)
    // 离开前落一次草稿，避免最后 600ms 的输入丢失
    if (!submitted.value && editable.value) saveDraft()
})

watch(answeredCount, () => {
    // 答题变化后重新计时保存（onInput 已覆盖，这里兜住评分/多选路径）
})
</script>

<style lang="scss" scoped>
.pad {
    padding: var(--sp-4);
}

.qbar {
    padding: 26rpx 42rpx 0;

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__text {
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }

    &__num {
        font-family: var(--font-serif);
        font-size: 28rpx;
        color: var(--c-brand-text);
    }

    &__draft {
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    &__dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: var(--r-pill);
        background: var(--c-success);

        &.is-off {
            background: var(--c-ink-3);
        }
    }

    &__draft-text {
        font-size: var(--fs-19);
        color: var(--c-ink-3);
    }

    &__track {
        margin-top: 14rpx;
        height: 6rpx;
        border-radius: var(--r-pill);
        background: var(--c-line);
        overflow: hidden;
    }

    &__fill {
        height: 100%;
        border-radius: var(--r-pill);
        background: var(--c-ink);
        transition: width 240ms var(--ease-out);
    }
}

.locked {
    display: flex;
    align-items: center;
    gap: 10rpx;
    margin: 22rpx 42rpx 0;
    padding: 16rpx 20rpx;
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__text {
        font-size: 20rpx;
        color: var(--c-ink-3);
    }
}

.qblock {
    margin: 26rpx 42rpx 0;
    padding: 26rpx;
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);

    &__h {
        display: flex;
        align-items: baseline;
        gap: 10rpx;
    }

    &__idx {
        font-family: var(--font-serif);
        font-size: var(--fs-19);
        color: var(--c-brand-text);
        letter-spacing: 0.08em;
    }

    &__title {
        flex: 1;
        font-size: 28rpx;
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-body);
    }

    &__req {
        font-size: var(--fs-18);
        color: var(--c-danger);
        background: var(--c-danger-bg);
        padding: 4rpx 10rpx;
        border-radius: var(--r-xs);
        flex: none;
    }

    &__hint {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
        flex: none;
    }

    &__note {
        display: block;
        margin-top: 10rpx;
        font-size: 20rpx;
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 14rpx;
    margin-top: 18rpx;
}

.chip {
    padding: 14rpx 24rpx;
    border-radius: var(--r-pill);
    /* 可点控件边界（模板 @tap="pickSingle/toggleMulti" + hover-class + is-on）：
       原 --c-line-strong 对白底 1.60:1 不可见，按 chip-group 既有注释改 --c-control-border 3.77:1。
       注：此处在视觉侧清单之外，按主理人"可点块残留应归零"这条验收标准一并处理。 */
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);

    &__text {
        font-size: var(--fs-23);
        color: var(--c-ink-2);
    }

    &.is-on {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .chip__text {
            color: var(--c-on-dark);
        }
    }

    &.is-lock {
        opacity: 0.72;
    }
}

.score {
    margin-top: 18rpx;

    &__row {
        display: flex;
        gap: 10rpx;
    }

    &__item {
        flex: none;
        padding: 4rpx;
    }

    &__labels {
        display: flex;
        justify-content: space-between;
        margin-top: 10rpx;
    }

    &__label {
        font-size: var(--fs-19);
        color: var(--c-ink-3);
    }
}

.ta-wrap {
    margin-top: 18rpx;
}

.ta {
    width: 100%;
    min-height: 120rpx;
    padding: 20rpx;
    border-radius: var(--r-sm);
    background: var(--c-surface-2);
    font-size: var(--fs-25);
    line-height: 1.7;
    color: var(--c-ink);

    &__ph {
        color: var(--c-ink-3);
    }

    &__count {
        display: block;
        margin-top: 8rpx;
        text-align: right;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.qfoot {
    padding: 30rpx 42rpx 180rpx;
    text-align: center;

    &__text {
        font-size: var(--fs-19);
        color: var(--c-ink-3);
    }
}

.bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 90;
    padding: 16rpx 42rpx calc(16rpx + var(--safe-bottom));
    background: var(--c-surface);
    border-top: 1rpx solid var(--c-line);

    &__in {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20rpx;
    }

    &__left {
        display: flex;
        flex-direction: column;
    }

    &__label {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__value {
        font-family: var(--font-serif);
        font-size: 28rpx;
        color: var(--c-ink);
    }

    &__btn {
        height: 84rpx;
        padding: 0 44rpx;
        border-radius: var(--r-pill);
        background: var(--c-ink);
        display: flex;
        align-items: center;
        justify-content: center;

        &-text {
            font-size: var(--fs-26);
            font-weight: var(--fw-medium);
            color: var(--c-on-dark);
        }

        &.is-off {
            background: var(--c-surface-2);

            .bar__btn-text {
                color: var(--c-ink-3);
            }
        }
    }
}

.done {
    padding: 60rpx 42rpx calc(40rpx + var(--safe-bottom));
    display: flex;
    flex-direction: column;
    align-items: center;

    &__seal {
        margin-bottom: 26rpx;
    }

    &__title {
        font-size: var(--fs-34);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__sub {
        margin-top: 14rpx;
        text-align: center;
        font-size: var(--fs-22);
        line-height: 1.7;
        color: var(--c-ink-3);
    }

    &__card {
        width: 100%;
        margin-top: 34rpx;
        padding: 8rpx 26rpx;
        border-radius: var(--r-md);
        background: var(--c-surface);
        border: 1rpx solid var(--c-line);
    }

    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 22rpx 0;
        border-bottom: 1rpx solid var(--c-line);

        &--last {
            border-bottom: 0;
        }
    }

    &__k {
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }

    &__v {
        font-size: var(--fs-23);
        color: var(--c-ink);
    }

    &__acts {
        width: 100%;
        margin-top: 34rpx;
        display: flex;
        flex-direction: column;
        gap: 16rpx;
    }
}
</style>
