<template>
    <view class="page-wrap publish">
        <base-card>
            <view class="target">
                <text class="target__title">{{ form.packageName || '婚庆服务' }}</text>
                <text v-if="form.staffName" class="target__meta">服务人员 {{ form.staffName }}</text>
            </view>
        </base-card>

        <!-- 综合评分 -->
        <base-card>
            <view class="score-main">
                <view class="score-main__head">
                    <text class="score-main__label">综合评分</text>
                    <text class="score-main__text">{{ scoreText }}</text>
                </view>
                <view class="stars">
                    <view hover-class="is-pressed" v-for="n in 5" :key="n" class="stars__item" @tap="score = n">
                        <u-icon
                            :name="n <= score ? 'star-fill' : 'star'"
                            :size="48"
                            :color="n <= score ? 'var(--c-warning)' : 'var(--c-line-strong)'"
                        />
                    </view>
                </view>
            </view>
        </base-card>

        <!-- 分项评分 -->
        <base-card>
            <view v-for="d in DIMENSIONS" :key="d.key" class="dim">
                <text class="dim__label">{{ d.label }}</text>
                <view class="stars stars--sm">
                    <view hover-class="is-pressed" v-for="n in 5" :key="n" class="stars__item" @tap="dims[d.key] = n">
                        <u-icon
                            :name="n <= dims[d.key] ? 'star-fill' : 'star'"
                            :size="40"
                            :color="n <= dims[d.key] ? 'var(--c-warning)' : 'var(--c-line-strong)'"
                        />
                    </view>
                </view>
            </view>
        </base-card>

        <!-- 标签 -->
        <base-card v-if="tags.length">
            <text class="block-title">印象标签</text>
            <view class="tags">
                <view hover-class="is-pressed"
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

        <!-- 内容 -->
        <base-card>
            <text class="block-title">评价内容</text>
            <textarea
                v-model="content"
                class="textarea"
                placeholder="说说这次服务的感受，最多 500 字"
                placeholder-class="textarea__ph"
                maxlength="500"
                :show-confirm-bar="false"
            />
            <text class="counter">{{ content.length }}/500</text>
        </base-card>

        <!-- 图片 -->
        <base-card>
            <text class="block-title">上传图片（最多 9 张）</text>
            <view class="images">
                <view v-for="(img, i) in images" :key="i" class="image">
                    <image class="image__img" :src="img" mode="aspectFill" />
                    <view hover-class="is-pressed" class="image__del" @tap="removeImage(i)">
                        <u-icon name="close" :size="24" color="var(--c-ink-inverse)" />
                    </view>
                </view>
                <view hover-class="is-pressed" v-if="images.length < 9" class="image image--add" @tap="chooseImages">
                    <u-icon name="plus" :size="40" color="var(--c-ink-3)" />
                </view>
            </view>
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

        <view class="submit">
            <base-button text="提交评价" variant="ink" size="lg" :loading="submitting" @click="handleSubmit" />
        </view>
    </view>
    <app-toast />
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
import { uploadImage } from '@/api/app'

const DIMENSIONS = [
    { key: 'score_service', label: '服务态度' },
    { key: 'score_professional', label: '专业水平' },
    { key: 'score_punctual', label: '时间守约' },
    { key: 'score_effect', label: '整体效果' }
] as const

type DimKey = (typeof DIMENSIONS)[number]['key']

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

const form = reactive({ staffName: '', packageName: '' })

const scoreText = computed(() => {
    const map: Record<number, string> = { 1: '很差', 2: '较差', 3: '一般', 4: '满意', 5: '非常满意' }
    return map[score.value] || ''
})

function toggleTag(id: number) {
    const i = selectedTags.value.indexOf(id)
    if (i >= 0) selectedTags.value.splice(i, 1)
    else selectedTags.value.push(id)
}

function onAnonChange(e: any) {
    anonymous.value = !!e?.detail?.value
}

function chooseImages() {
    const remain = 9 - images.value.length
    uni.chooseImage({
        count: remain,
        sizeType: ['compressed'],
        success: async (res) => {
            const paths = (res.tempFilePaths || []).slice(0, remain)
            for (const p of paths) {
                try {
                    const up = await uploadImage(p)
                    const uri = (up as any)?.uri || (up as any)?.url || p
                    images.value.push(uri)
                } catch {
                    // 单张失败跳过
                }
            }
        }
    })
}

function removeImage(i: number) {
    images.value.splice(i, 1)
}

async function handleSubmit() {
    if (submitting.value) return
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
    padding: var(--sp-3) var(--sp-4) calc(164rpx + var(--safe-bottom));
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

.score-main {
    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__label {
        font-size: var(--fs-md);
        color: var(--c-ink);
        font-weight: 500;
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-warning);
    }

    > .stars {
        margin-top: var(--sp-2);
    }
}

.stars {
    display: flex;
    align-items: center;
    width: 100%;
    max-width: 440rpx;

    &__item {
        flex: 1;
        min-width: 0;
        max-width: 88rpx;
        height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    &--sm {
        margin-left: auto;
    }
}

.dim {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    min-height: 96rpx;

    & + & {
        border-top: 1rpx solid var(--c-line);
    }

    &__label {
        width: 112rpx;
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
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
    border: 1rpx solid var(--c-line);
    box-sizing: border-box;

    &.is-active {
        background: var(--c-brand-bg);
        border-color: var(--c-brand);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &.is-active &__text {
        color: var(--c-brand-text);
    }
}

.textarea {
    width: 100%;
    min-height: 220rpx;
    padding: var(--sp-3);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    background: var(--c-surface-2);
    box-sizing: border-box;
    font-size: var(--fs-md);
    color: var(--c-ink);
    line-height: 1.7;

    &__ph {
        color: var(--c-ink-3);
    }
}

.counter {
    display: block;
    margin-top: var(--sp-1);
    text-align: right;
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

.images {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-3) var(--sp-2);
}

.image {
    position: relative;
    width: 160rpx;
    height: 160rpx;
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);

    &--add {
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1rpx dashed var(--c-line-strong);
        background: var(--c-surface-2);
        box-sizing: border-box;
        overflow: hidden;
    }

    &__img {
        display: block;
        width: 100%;
        height: 100%;
        border-radius: var(--r-sm);
        overflow: hidden;
    }

    &__del {
        position: absolute;
        top: -24rpx;
        right: -24rpx;
        z-index: 2;
        width: 88rpx;
        height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: center;

        &::before {
            content: '';
            position: absolute;
            width: 40rpx;
            height: 40rpx;
            border-radius: var(--r-pill);
            background: var(--c-mask);
        }
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
