<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="投诉详情" bg="page" :show-back="true">
        <view class="page-wrap detail">
            <view v-if="loading" class="detail-pad">
                <base-skeleton :rows="4" />
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

            <template v-else>
                <view class="detail-pad body">
                    <!-- 内嵌墨金圆角头卡 -->
                    <view class="hero-card">
                        <view class="hero-card__top">
                            <text class="hero-card__eyebrow">COMPLAINT RECORD</text>
                            <status-tag :label="statusLabel" :tone="statusTone" size="md" />
                        </view>
                        <text class="hero-card__title">{{ data.title || '投诉' }}</text>
                        <view class="hero-card__tags">
                            <text class="hero-card__pill">{{ typeText }}</text>
                            <text class="hero-card__pill">{{ levelText }}</text>
                        </view>
                    </view>

                    <!-- 平台处理结果独立成高亮反馈卡 -->
                    <view v-if="data.handle_result" class="result-card">
                        <view class="result-card__head">
                            <view class="result-card__title-wrap">
                                <tn-icon name="tips" :size="24" class="result-card__ic" />
                                <text class="result-card__title">平台处理结果</text>
                            </view>
                            <text v-if="data.handle_time" class="result-card__time">{{
                                data.handle_time
                            }}</text>
                        </view>
                        <text class="result-card__body">{{ data.handle_result }}</text>
                    </view>

                    <base-card v-if="data.content">
                        <view class="sec-title">
                            <view class="sec-title__bar" />
                            <text class="sec-title__text">投诉内容</text>
                        </view>
                        <text class="block-text">{{ data.content }}</text>
                    </base-card>

                    <base-card v-if="data.expect_result">
                        <view class="sec-title">
                            <view class="sec-title__bar" />
                            <text class="sec-title__text">期望处理结果</text>
                        </view>
                        <text class="block-text">{{ data.expect_result }}</text>
                    </base-card>

                    <base-card v-if="images.length">
                        <view class="sec-title">
                            <view class="sec-title__bar" />
                            <text class="sec-title__text">凭证附件</text>
                        </view>
                        <view class="images">
                            <image
                                v-for="(img, i) in images"
                                :key="i"
                                class="image__img"
                                :src="imageUrl(img)"
                                mode="aspectFill"
                                @tap="preview(i)"
                            />
                        </view>
                    </base-card>

                    <base-card>
                        <view class="sec-title">
                            <view class="sec-title__bar" />
                            <text class="sec-title__text">记录详情</text>
                        </view>
                        <view class="kv">
                            <text class="kv__k">提交时间</text>
                            <text class="kv__v">{{ data.create_time || '—' }}</text>
                        </view>
                        <view v-if="data.handle_time" class="kv">
                            <text class="kv__k">处理时间</text>
                            <text class="kv__v">{{ data.handle_time }}</text>
                        </view>
                        <view v-if="ratedScore > 0" class="kv">
                            <text class="kv__k">满意度评分</text>
                            <text class="kv__v kv__v--brand">{{
                                SCORE_LABELS[ratedScore] || `${ratedScore} 分`
                            }}</text>
                        </view>
                    </base-card>

                    <base-card v-if="canRate">
                        <view class="sec-title">
                            <view class="sec-title__bar" />
                            <text class="sec-title__text">处理满意度</text>
                        </view>
                        <view class="stars">
                            <view
                                hover-class="is-pressed"
                                :hover-start-time="20"
                                :hover-stay-time="70"
                                :hover-stop-propagation="true"
                                v-for="n in 5"
                                :key="n"
                                class="stars__item"
                                @tap="score = n"
                            >
                                <!-- v1 u-icon → v2 tn-icon：评分星形保留（star-fill / star） -->
                                <!-- 批 1.5 C：未选星原 --c-line-strong 仅 1.60:1（看不见有几颗星可选）→ --c-ink-3 -->
                                <tn-icon
                                    :name="n <= score ? 'star-fill' : 'star'"
                                    :size="48"
                                    class="ic-star"
                                    :class="{ 'is-on': n <= score }"
                                />
                            </view>
                        </view>
                        <text class="stars-feedback">{{ SCORE_LABELS[score] }}</text>
                        <textarea
                            v-model="feedback"
                            class="textarea"
                            placeholder="补充说明（选填）"
                            placeholder-class="textarea__ph"
                            maxlength="300"
                        />
                        <view class="rate-submit">
                            <base-button
                                text="提交评价"
                                variant="ink"
                                size="md"
                                :loading="rating"
                                @click="handleRate"
                            />
                        </view>
                    </base-card>
                </view>
            </template>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/** 投诉详情：处理完成后可评分（rateComplaint） */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import {
    COMPLAINT_STATUS_NAME_MAP,
    COMPLAINT_STATUS_TONE,
    COMPLAINT_TYPE_NAME_MAP,
    COMPLAINT_LEVEL_NAME_MAP,
    ComplaintStatus,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getComplaintDetail, rateComplaint } from '@/api/aftersale'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()

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
const SCORE_LABELS: Record<number, string> = {
    1: '1 分 · 很不满意',
    2: '2 分 · 不太满意',
    3: '3 分 · 一般',
    4: '4 分 · 比较满意',
    5: '5 分 · 非常满意'
}

const rating = ref(false)
const score = ref(5)
const feedback = ref('')

const status = computed(() => Number(data.value.status))
const ratedScore = computed(() => Number(data.value.satisfaction || 0))

const typeText = computed(
    () => (COMPLAINT_TYPE_NAME_MAP as Record<number, string>)[Number(data.value.type)] || '投诉'
)
const levelText = computed(
    () => (COMPLAINT_LEVEL_NAME_MAP as Record<number, string>)[Number(data.value.level)] || '-'
)

const statusLabel = computed(() => {
    const mapped = resolveStatusLabel(COMPLAINT_STATUS_NAME_MAP as any, status.value)
    return mapped.known ? mapped.label : data.value.status_desc || mapped.label
})

const statusTone = computed(() => resolveTone(COMPLAINT_STATUS_TONE as any, status.value))

/** 已处理且未评价才显示评分 */
const canRate = computed(
    () => status.value === ComplaintStatus.HANDLED && Number(data.value.is_rated ?? 0) !== 1
)

const images = computed<string[]>(() => {
    const raw = data.value.images
    if (Array.isArray(raw)) return raw
    if (typeof raw === 'string' && raw) {
        try {
            const parsed = JSON.parse(raw)
            return Array.isArray(parsed) ? parsed : []
        } catch {
            return []
        }
    }
    return []
})

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

function preview(index: number) {
    uni.previewImage({ urls: images.value.map((u) => imageUrl(u)), current: index })
}

async function load() {
    loading.value = true
    error.value = false
    try {
        const res = await getComplaintDetail({ id: id.value })
        if (!res) throw new Error('empty')
        data.value = res
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

async function handleRate() {
    if (rating.value) return
    rating.value = true
    try {
        await rateComplaint({
            id: id.value,
            score: score.value,
            feedback: feedback.value.trim() || undefined
        })
        toast.success('感谢反馈')
        load()
    } catch {
        // 请求层已提示
    } finally {
        rating.value = false
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

/** P1 成对条目：pages.json 的 enablePullDownRefresh 已配；finally 复位，失败也不让圈一直转 */
onPullDownRefresh(async () => {
    try {
        await load()
    } finally {
        uni.stopPullDownRefresh()
    }
})
</script>

<style lang="scss" scoped>
.detail {
    background: var(--c-bg);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
}

.detail-pad {
    padding: 0 var(--page-gutter);
}

.body {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
}

.hero-card {
    padding: var(--sp-4);
    border-radius: var(--r-md);
    background: linear-gradient(135deg, var(--c-dark-1), var(--c-dark-2));
    color: var(--c-on-dark);

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-2);
    }

    &__eyebrow {
        font-family: var(--font-serif);
        font-size: var(--fs-20);
        letter-spacing: 0.08em;
        color: var(--c-brand-soft);
    }

    &__title {
        display: block;
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
        overflow-wrap: anywhere;
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-2);
        margin-top: var(--sp-3);
        padding-top: var(--sp-2);
        border-top: 1rpx solid var(--c-dark-3);
    }

    &__pill {
        padding: 4rpx var(--sp-2);
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-brand-soft);
        font-size: var(--fs-20);
        line-height: var(--lh-none);
        color: var(--c-brand-soft);
    }
}

.result-card {
    padding: var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-2);
    }

    &__title-wrap {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__ic {
        color: var(--c-brand-text);
        flex: none;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }

    &__time {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        flex: none;
    }

    &__body {
        display: block;
        font-size: var(--fs-md);
        line-height: var(--lh-loose);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }
}

.sec-title {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    margin-bottom: var(--sp-3);

    &__bar {
        width: 4rpx;
        height: 24rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand-text);
        flex: none;
    }

    &__text {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.block-text {
    display: block;
    font-size: var(--fs-md);
    color: var(--c-ink-2);
    line-height: var(--lh-loose);
    overflow-wrap: anywhere;
}

.images {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.image__img {
    width: 160rpx;
    height: 160rpx;
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);
}

.kv {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 56rpx;
    padding: var(--sp-2) 0;
    box-sizing: border-box;

    & + & {
        border-top: 1rpx solid var(--c-line);
    }

    &__k {
        /* P1 §3-7：键固定宽 —— 原来是自适应，长值会把标签挤到换行、两行标签高度不一 */
        width: 140rpx;
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
        text-align: right;
        /* 恢复折行，不做 ellipsis：这是详情页的键值行，值本身就是内容（「处理结果」被截断
           且无处可看 = 信息丢失）。行高不一致是正常排版，"每行一样高"是偏好，"值能读全"是功能。 */
        overflow-wrap: anywhere;

        &--brand {
            color: var(--c-brand-text);
            font-weight: var(--fw-medium);
        }
    }
}

.stars {
    display: flex;
    width: 100%;
    max-width: 440rpx;
    margin-bottom: var(--sp-1);

    &__item {
        flex: 1;
        min-width: 0;
        max-width: 88rpx;
        height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

.stars-feedback {
    display: block;
    margin-bottom: var(--sp-3);
    font-size: var(--fs-xs);
    font-weight: var(--fw-medium);
    color: var(--c-brand-text);
}

.textarea {
    width: 100%;
    min-height: 180rpx;
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    padding: var(--sp-3);
    font-size: var(--fs-md);
    color: var(--c-ink);
    line-height: var(--lh-body);
    box-sizing: border-box;

    &__ph {
        color: var(--c-ink-3);
    }
}

.rate-submit {
    margin-top: var(--sp-3);
}

/* 动态图标颜色：状态由父级/自身的 is-on 表达，颜色留在 CSS（tn-icon 的 color prop 不认 var()） */
.ic-star {
    color: var(--c-ink-3);
}
.ic-star.is-on {
    color: var(--c-warning);
}
</style>
