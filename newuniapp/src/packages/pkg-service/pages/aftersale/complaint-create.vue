<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="提交投诉" bg="page" :show-back="true" :bottom-bar="true">
        <view class="page-wrap create">
            <base-card>
                <view class="step-head">
                    <text class="step-head__no">01</text>
                    <text class="step-head__title">投诉分类</text>
                </view>
                <text class="block-title">投诉类型</text>
                <view class="chips">
                    <view
                        hover-class="is-pressed"
                        :hover-start-time="20"
                        :hover-stay-time="70"
                        :hover-stop-propagation="true"
                        v-for="t in TYPES"
                        :key="t.value"
                        class="chips__item hit-88"
                        :class="{ 'is-active': form.type === t.value }"
                        @tap="pickType(t.value)"
                    >
                        <text class="chips__text">{{ t.label }}</text>
                    </view>
                </view>

                <text class="block-title block-title--gap">紧急程度</text>
                <view class="chips">
                    <view
                        hover-class="is-pressed"
                        :hover-start-time="20"
                        :hover-stay-time="70"
                        :hover-stop-propagation="true"
                        v-for="l in LEVELS"
                        :key="l.value"
                        class="chips__item hit-88"
                        :class="[
                            `chips__item--${levelTone(l.value)}`,
                            { 'is-active': form.level === l.value }
                        ]"
                        @tap="pickLevel(l.value)"
                    >
                        <text class="chips__text">{{ l.label }}</text>
                    </view>
                </view>
            </base-card>

            <base-card>
                <view class="step-head">
                    <text class="step-head__no">02</text>
                    <text class="step-head__title">问题描述</text>
                </view>
                <!-- P1 §3-6：必填标识从文案里的「（必填）」改为 form-field 的红星 -->
                <form-field
                    label="标题"
                    :required="true"
                    :model-value="form.title"
                    :maxlength="50"
                    placeholder="一句话说明问题"
                    @update:model-value="(v: string) => (form.title = v)"
                />

                <view class="block-gap">
                    <form-field
                        label="详细描述"
                        type="textarea"
                        layout="stack"
                        placeholder="请描述发生的时间、地点、经过，便于核实"
                        :maxlength="1000"
                        count
                        :model-value="form.content"
                        @update:model-value="(v: string) => (form.content = v)"
                    />
                </view>
            </base-card>

            <base-card>
                <view class="step-head">
                    <text class="step-head__no">03</text>
                    <text class="step-head__title">期望与凭证</text>
                </view>
                <form-field
                    label="期望处理结果"
                    type="textarea"
                    layout="stack"
                    placeholder="如：希望补拍 / 希望退款 / 希望道歉（选填）"
                    :maxlength="300"
                    :model-value="form.expect_result"
                    @update:model-value="(v: string) => (form.expect_result = v)"
                />

                <view class="block-gap">
                    <!-- 上传收归 form-field type="upload"（真实上限 6 张，与原 `.image--add` 的 6 一致） -->
                    <form-field
                        label="上传凭证（选填，最多 6 张）"
                        type="upload"
                        layout="stack"
                        :max="6"
                        :model-value="form.images"
                        @update:model-value="(v: string[]) => (form.images = v)"
                    />
                </view>
            </base-card>

            <view class="tips">
                <!-- v1 u-icon → v2 tn-icon：info-circle → tips -->
                <tn-icon name="tips" :size="24" class="ic-ink-3" />
                <view class="tips__body">
                    <text class="tips__text">24 小时内响应处理，恶意投诉将影响账号信用</text>
                </view>
            </view>
        </view>
        <template #bottom>
            <bottom-bar>
                <base-button
                    text="提交投诉"
                    variant="ink"
                    size="lg"
                    :loading="submitting"
                    @click="handleSubmit"
                />
            </bottom-bar>
        </template>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/** 提交投诉：type(1-5) + level(1-3) + title(必填) + content + expect_result + images */
import { reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import {
    COMPLAINT_TYPE_NAME_MAP,
    COMPLAINT_LEVEL_NAME_MAP,
    ComplaintType,
    ComplaintLevel
} from '@shared/contracts'
import { submitComplaint } from '@/api/aftersale'
import { haptic } from '@/utils/haptics'

const TYPES = Object.entries(COMPLAINT_TYPE_NAME_MAP).map(([k, label]) => ({
    value: Number(k),
    label
}))

const LEVELS = Object.entries(COMPLAINT_LEVEL_NAME_MAP).map(([k, label]) => ({
    value: Number(k),
    label
}))

const submitting = ref(false)
const orderId = ref(0)

const form = reactive({
    type: ComplaintType.SERVICE as number,
    level: ComplaintLevel.NORMAL as number,
    title: '',
    content: '',
    expect_result: '',
    images: [] as string[]
})

function levelTone(lv: number) {
    if (lv === ComplaintLevel.URGENT) return 'urgent'
    if (lv === ComplaintLevel.SERIOUS) return 'serious'
    return 'normal'
}

function pickType(v: number) {
    form.type = v
}

function pickLevel(v: number) {
    form.level = v
}

async function handleSubmit() {
    if (submitting.value) return
    if (!form.title.trim()) {
        toast.info('请填写标题')
        return
    }
    submitting.value = true
    haptic('medium', 'cc-submit')
    try {
        await submitComplaint({
            order_id: orderId.value || undefined,
            type: form.type,
            level: form.level,
            title: form.title.trim(),
            content: form.content.trim() || undefined,
            expect_result: form.expect_result.trim() || undefined,
            images: form.images.length ? form.images : undefined
        })
        toast.success('已提交')
        setTimeout(() => uni.navigateBack(), 700)
    } catch {
        // 请求层已提示
    } finally {
        submitting.value = false
    }
}

onLoad((options: any) => {
    orderId.value = Number(options?.order_id || 0)
})
</script>

<style lang="scss" scoped>
.create {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--page-gutter) var(--sp-4);
    background: var(--c-bg);
}

.step-head {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    margin-bottom: var(--sp-3);
    padding-bottom: var(--sp-2);
    border-bottom: 1rpx solid var(--c-line);

    &__no {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

/* 相邻 form-field 之间的纵向间隔，与 .block-title--gap 同值（--sp-4），保持同一节奏 */
.block-gap {
    margin-top: var(--sp-4);
}

.block-title {
    display: block;
    margin-bottom: var(--sp-2);
    font-size: var(--fs-xs);
    font-weight: var(--fw-medium);
    color: var(--c-ink-2);

    &--gap {
        margin-top: var(--sp-4);
    }
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);

    &__item {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 64rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-control-border);
        box-sizing: border-box;
        transition:
            background-color var(--dur-base) var(--ease-out),
            border-color var(--dur-base) var(--ease-out);

        &.is-active {
            background: var(--c-active-bg);
            border-color: var(--c-active-border);
        }

        &--serious.is-active {
            background: var(--c-warning);
            border-color: var(--c-warning);
        }

        &--urgent.is-active {
            background: var(--c-danger);
            border-color: var(--c-danger);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__item.is-active &__text {
        color: var(--c-active-fg);
        font-weight: var(--fw-medium);
    }
}

.tips {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);

    /* 拆句后两行容器 */
    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__text {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
        overflow-wrap: anywhere;
    }
}

/* tn-icon 颜色工具类（本地 scoped 版）：tn-icon 的 color prop 不认 var()，
   颜色必须靠 CSS 落在图标根节点上再由内部 <text> 继承。 */
.ic-ink-3 {
    color: var(--c-ink-3);
}

/* v10 §3.13：减弱动效下芯片直接换色，不做过渡 */
@media (prefers-reduced-motion: reduce) {
    .chips__item {
        transition: none;
    }
}
</style>
