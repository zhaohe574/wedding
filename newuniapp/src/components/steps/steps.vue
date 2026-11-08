<template>
    <view
        class="steps"
        :aria-label="`流程进度，第 ${current + 1} 步，共 ${steps.length} 步：${
            steps[current] || ''
        }`"
    >
        <view
            v-for="(s, i) in steps"
            :key="i"
            class="steps__item"
            :class="{
                'is-done': i < current,
                'is-current': i === current,
                'is-failed': i === failedIndex
            }"
            :aria-current="i === current ? 'step' : undefined"
        >
            <view class="steps__dot" aria-hidden="true">
                <!-- v1 u-icon → v2 tn-icon：checkmark → check -->
                <tn-icon
                    v-if="i < current"
                    name="check"
                    :size="24"
                    color="var(--c-ink-inverse)"
                />
                <text v-else class="steps__no">{{ i + 1 }}</text>
            </view>
            <text class="steps__label">{{ s }}</text>
            <view v-if="i < steps.length - 1" class="steps__line" aria-hidden="true" />
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 步骤条（只读展示，收编 booking.vue 内联 .steps2）。
 * 契约：不控制步骤切换（由页面 nextStep/prevStep + 底栏按钮）、无 @change、不可点、
 * 不写 STEPS 文案、不改业务步骤数 —— 调用方传几段就渲染几段。
 * ⚠️ 2026-11-08 更正：原文写「O1：booking 恒定 3 步」已过期，booking 现为 2 步
 *   （选套餐 / 附加项）；本组件自建成起零引用，未进包、未经产物验证。
 */
withDefaults(
    defineProps<{
        steps: string[]
        /** 0-based，由页面 step 驱动 */
        current: number
        /** 失败步索引，标 --c-danger */
        failedIndex?: number
    }>(),
    { failedIndex: -1 }
)
</script>

<style lang="scss" scoped>
.steps {
    display: flex;
    height: 96rpx;
    padding: 0 var(--sp-4);
    align-items: center;

    &__item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-1);
        position: relative;
    }

    &__dot {
        width: 32rpx;
        height: 32rpx;
        border-radius: var(--r-pill);
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--c-surface);
        /* 前置3：未达态描边 --c-line-strong → --c-control-border。
           01 §7.15 原写 --c-line-strong（对底 1.59:1，「还没到这一步」看起来像「这里没有这一步」），
           与 01:887「不得用 --c-line-strong 承载信息」自相矛盾；booking 的 .st__dot 基础态
           本来就用 --c-control-border（3.77:1），本条是让组件和规范跟上 booking 的现状。 */
        border: 1rpx solid var(--c-control-border);
    }

    &__no {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-none);
    }

    &__label {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink-3);
        line-height: var(--lh-none);
    }

    &__line {
        position: absolute;
        top: 16rpx;
        left: 50%;
        width: 100%;
        height: 2rpx;
        background: var(--c-line);
        z-index: -1;
    }

    .is-done &__dot {
        background: var(--c-ink);
        border-color: var(--c-ink);
    }
    .is-done &__line {
        background: var(--c-ink);
    }
    .is-current &__dot {
        background: var(--c-ink);
        border-color: var(--c-ink);
        box-shadow: 0 0 0 8rpx var(--c-brand-bg);
    }
    .is-current &__label {
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
    .is-failed &__dot {
        background: var(--c-danger);
        border-color: var(--c-danger);
    }
}
</style>
