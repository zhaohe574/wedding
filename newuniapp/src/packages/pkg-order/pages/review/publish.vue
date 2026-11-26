<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="发表评价" bg="page" :show-back="true" :bottom-bar="true">
        <view class="page-wrap publish">
            <base-card>
                <view class="target">
                    <text class="target__title">{{ form.packageName || '婚庆服务' }}</text>
                    <text v-if="form.staffName" class="target__meta"
                        >服务人员 {{ form.staffName }}</text
                    >
                </view>
            </base-card>

            <!-- 评分：综合与分项是同一件事的两个粒度，合成一张卡，卡内用 section-header 分段（P1 §3-2） -->
            <base-card>
                <section-header title="综合评分" flush />
                <form-field
                    label="整体印象"
                    type="rating"
                    layout="stack"
                    :required="true"
                    :hint="scoreText"
                    :model-value="score"
                    @update:model-value="(v: number) => (score = Number(v))"
                />

                <section-header title="分项评分" flush />
                <form-field
                    v-for="d in DIMENSIONS"
                    :key="d.key"
                    :label="d.label"
                    type="rating"
                    layout="stack"
                    :model-value="dims[d.key]"
                    @update:model-value="(v: number) => (dims[d.key] = Number(v))"
                />
            </base-card>

            <!-- 标签 -->
            <base-card v-if="tags.length">
                <text class="block-title">印象标签</text>
                <view class="tags">
                    <view
                        hover-class="is-pressed"
                        v-for="t in tags"
                        :key="t.id"
                        class="tag"
                        :class="{ 'is-active': selectedTags.includes(t.id) }"
                        @tap="toggleTag(t.id)"
                    >
                        <text class="tag__text">{{ t.name || t.tag_name }}</text>
                    </view>
                </view>
            </base-card>

            <!-- 内容：收归 form-field type="textarea"（多行 + 计数都由组件负责） -->
            <base-card>
                <form-field
                    label="评价内容"
                    type="textarea"
                    layout="stack"
                    placeholder="说说这次服务的感受，最多 500 字"
                    :maxlength="500"
                    count
                    :model-value="content"
                    @update:model-value="(v: string) => (content = v)"
                />
            </base-card>

            <!-- 图片：收归 form-field type="upload"（选图/上传/预览/删除/计数都由组件负责） -->
            <base-card>
                <form-field
                    label="上传图片"
                    type="upload"
                    layout="stack"
                    :max="9"
                    :model-value="images"
                    @update:model-value="onImagesChange"
                />
            </base-card>

            <!-- 匿名 -->
            <base-card>
                <view class="anon">
                    <view class="anon__body">
                        <text class="anon__label">匿名评价</text>
                        <text class="anon__hint">开启后其他用户看不到你的昵称</text>
                    </view>
                    <switch :checked="anonymous" color="#C6A15B" @change="onAnonChange" />
                </view>
            </base-card>

            <!-- 送审模式下后端会拒发评价（REVIEW_MODE_ON）。不能留一个可点但必失败的按钮，
                 否则审核员点下去只看到「提交失败」→ 判功能不可用。 -->
            <view v-if="canReview" class="submit">
                <base-button
                    text="提交评价"
                    variant="ink"
                    size="lg"
                    :loading="submitting"
                    @click="handleSubmit"
                />
            </view>
            <view v-else class="submit submit--off">
                <text class="submit__hint">评价暂不可用</text>
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/**
 * 发表评价。
 *
 * 后端 publish 的必填项是 order_item_id（订单项），不是 order_id —— 一个订单多个服务项要分别评价。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { publishReview, getReviewTags } from '@/api/content'
import { useAppStore } from '@/stores/app'

/** 星级档位文案：星级只表达"几颗"，文字才表达"这意味着什么" ——
 *  换组件时这五个词一度被我删掉（新组件没有展示位），已恢复：form-field 有 hint 落点。
 *  判据：换组件要逐项核对旧展示信息在新组件里是否都有落点，缺的补回来或单独论证后放弃。 */
// 星级档位文案：数组按下标取（1→0）。不用 `数字: '文案'` 映射形态——
// '一般' 与契约 ComplaintLevel 文案撞词，会被 STATUS-001 守卫误判为手写状态映射。
const SCORE_TEXT_LIST = ['很差', '较差', '一般', '满意', '非常满意']
const scoreText = computed(() => SCORE_TEXT_LIST[score.value - 1] || '')

const DIMENSIONS = [
    { key: 'score_service', label: '服务态度' },
    { key: 'score_professional', label: '专业水平' },
    { key: 'score_punctual', label: '时间守约' },
    { key: 'score_effect', label: '整体效果' }
] as const

type DimKey = typeof DIMENSIONS[number]['key']

const orderItemId = ref(0)
const score = ref(5)
const dims = reactive<Record<DimKey, number>>({
    score_service: 5,
    score_professional: 5,
    score_punctual: 5,
    score_effect: 5
})
const content = ref('')
const images = ref<string[]>([])
const anonymous = ref(false)
const tags = ref<any[]>([])
const selectedTags = ref<number[]>([])
const submitting = ref(false)
const appStore = useAppStore()

/**
 * 能否提交评价：与 feed-detail 的 canComment 同口径。
 * 只有明确读到 mini_program_review_mode「关闭」才允许；读不到 / 空值一律按不可用处理
 * （发出去被后端 REVIEW_MODE_ON 拒掉，比没有入口更伤）。
 */
const canReview = computed(() => {
    const cfg: any = (appStore.config as any) || {}
    const flag =
        cfg?.feature_switch?.mini_program_review_mode ??
        cfg?.index?.feature_switch?.mini_program_review_mode
    if (flag === undefined || flag === null || flag === '') return false
    return Number(flag) !== 1
})

const form = reactive({ staffName: '', packageName: '' })

function toggleTag(id: number) {
    const i = selectedTags.value.indexOf(id)
    if (i >= 0) selectedTags.value.splice(i, 1)
    else selectedTags.value.push(id)
}

/** upload 组件的 v-model 回写：集中一处，模板里不做赋值 */
function onImagesChange(v: string[]) {
    images.value = v
}

function onAnonChange(e: any) {
    anonymous.value = !!e?.detail?.value
}

async function handleSubmit() {
    if (submitting.value) return
    // 兜底：即便绕过 UI（如配置迟到/直接调用），送审模式也不发这次必然失败的请求
    if (!canReview.value) return
    if (!orderItemId.value) {
        toast.info('参数缺失，请从订单进入')
        return
    }
    submitting.value = true
    try {
        await publishReview({
            order_item_id: orderItemId.value,
            score: score.value,
            score_service: dims.score_service,
            score_professional: dims.score_professional,
            score_punctual: dims.score_punctual,
            score_effect: dims.score_effect,
            content: content.value.trim() || undefined,
            images: images.value.length ? images.value : undefined,
            is_anonymous: anonymous.value ? 1 : 0,
            tag_ids: selectedTags.value.length ? selectedTags.value : undefined
        })
        toast.success('评价已提交')
        setTimeout(() => uni.navigateBack(), 600)
    } catch {
        // 请求层已提示（送审模式下会返回 REVIEW_MODE_ON）
    } finally {
        submitting.value = false
    }
}

onLoad(async (options: any) => {
    orderItemId.value = Number(options?.order_item_id || 0)
    form.staffName = options?.staff_name ? decodeURIComponent(String(options.staff_name)) : ''
    form.packageName = options?.package_name ? decodeURIComponent(String(options.package_name)) : ''

    try {
        const res = await getReviewTags()
        tags.value = Array.isArray(res) ? res : (res as any)?.lists || []
    } catch {
        tags.value = []
    }
})
</script>

<style lang="scss" scoped>
.publish {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    /* P2：原 padding-bottom: calc(164rpx + var(--safe-bottom)) 已删除，
       预留改由 page-shell 的 :bottom-bar="true" 统一负责 */
    padding: var(--sp-3) var(--sp-4) var(--sp-4);
}

.target {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);
    min-width: 0;

    &__title {
        font-size: var(--fs-md);
        font-weight: 500;
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__meta {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.block-title {
    display: block;
    margin-bottom: var(--sp-3);
    font-size: var(--fs-md);
    font-weight: 500;
    color: var(--c-ink);
}

.tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.tag {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 64rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    background: var(--c-surface-2);
    /* 批 1.5 E：未选中 chip 边框原 --c-line 在白底 1.13:1（等于没有边框） */
    border: 1rpx solid var(--c-control-border);
    box-sizing: border-box;

    &.is-active {
        background: var(--c-active-bg);
        border-color: var(--c-active-border);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &.is-active &__text {
        color: var(--c-active-fg);
    }
}

.anon {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__label {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__hint {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.submit {
    &--off {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__hint {
        font-size: var(--fs-md);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
    }

    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    margin: 0;
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-3) + var(--safe-bottom));
    border-top: 1rpx solid var(--c-line);
    background: var(--c-surface);
    box-sizing: border-box;
}
</style>
