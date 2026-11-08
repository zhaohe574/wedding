<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell :bottom-bar="true" title="回访问卷" bg="page" :show-back="true">
        <view class="page-wrap cb-page">
            <view v-if="loading" class="page-pad">
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

            <template v-else>
                <view class="head">
                    <text class="head__title">{{ questionnaire.title || '服务回访问卷' }}</text>
                    <text v-if="questionnaire.description" class="head__desc">{{
                        questionnaire.description
                    }}</text>
                </view>

                <!-- P2 §3-2 新增：长表单进度（02 §6.1 口径）。原来没有任何进度显示，
                     10 题滚 3 屏以上，用户不知道还剩多少。
                     补：requiredTotal === 0 时整行不渲染 —— 后台配置的问卷可能一道必填题都没有，
                     此时分母为 0，会恒显示「已完成 0 / 0」。进度条的口径是「离能提交还差多少」，
                     没有提交门槛时它没有意义，藏掉比显示 0/0 诚实。
                     （不要改用 questions.length 当分母：那会让「已完成」和「还差 N 题」用两套分母。） -->
                <view v-if="requiredTotal > 0" class="page-pad progress">
                    <text class="progress__text"
                        >已完成 {{ requiredDone }} / {{ requiredTotal }}</text
                    >
                </view>

                <view class="page-pad questions">
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
                                <view
                                    hover-class="is-pressed"
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

                            <view v-else-if="q.type === 'rating'" class="q__rating">
                                <view
                                    hover-class="is-pressed"
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
                                        :color="
                                            n <= Number(answers[keyOf(q)] || 0)
                                                ? 'var(--c-warning)'
                                                : 'var(--c-ink-3)'
                                        "
                                    />
                                </view>
                            </view>
                        </view>
                    </base-card>
                </view>
            </template>
        </view>
        <!-- 裁决 2：吸底收尾。原来 page-shell 已预留 --bottom-bar-h(128rpx)，但栏体还是自绘
             .submit{position:fixed}，实际高 24+按钮+24+安全区 > 128rpx → 最后一道题底部被吃掉。
             预留与栏体必须成对：统一挂到 page-shell 的 #bottom 槽 + bottom-bar 组件。
             按钮放默认槽（.bbar__left 是 flex:1）→ 整宽；放 #actions 会被 flex-shrink:0 压成贴右小按钮。 -->
        <template #bottom>
            <bottom-bar>
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

/** 必填题总数 / 已完成数 / 还差几题 */
const requiredTotal = computed(() => questions.value.filter((q) => Number(q.required) === 1).length)
const requiredDone = computed(
    () => questions.value.filter((q) => Number(q.required) === 1 && isAnswered(q)).length
)
const missingCount = computed(() => requiredTotal.value - requiredDone.value)

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

.head {
    background: var(--c-ink);
    padding: var(--sp-5) var(--sp-4);

    &__title {
        display: block;
        font-size: var(--fs-xl);
        font-weight: 500;
        line-height: var(--lh-tight);
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
    /* Q1：原 padding-bottom: calc(164rpx + var(--safe-bottom)) 已删除，
       预留改由 page-shell 的 :bottom-bar="true" 统一负责 */
    padding-bottom: var(--sp-4);
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

/* 进度条（P2 §3-2 新增） */
.progress {
    padding-top: var(--sp-2);
}

.progress__text {
    display: block;
    font-size: var(--fs-md);
    color: var(--c-ink-3);
    line-height: var(--lh-body);
}

.q__title {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-1);
    margin-bottom: var(--sp-3);
}

.q__index {
    flex-shrink: 0;
    /* P2 §3-2：题号走 --fs-xs 衬线（原来是 --fs-md 无衬线，和题干同权重，抢注意力） */
    font-family: var(--font-serif);
    font-size: var(--fs-xs);
    color: var(--c-brand-text);
    font-weight: 500;
}

.q__text {
    flex: 1;
    min-width: 0;
    font-size: var(--fs-md);
    color: var(--c-ink);
    font-weight: 500;
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
    /* 批 1.5 E：未选中边框原 --c-line 在白底 1.13:1（等于没有边框） */
    border: 1rpx solid var(--c-control-border);
    box-sizing: border-box;

    &.is-active {
        /* 批 1.5 A（口径修正）：选项卡片只换描边，不做墨黑填充 —— 整块填墨黑会把选项文字压住。
           --c-brand 对白底 2.08:1 → --c-brand-text 5.34:1 */
        border-color: var(--c-brand-text);
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
</style>
