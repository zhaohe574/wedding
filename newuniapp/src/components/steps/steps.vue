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
                <tn-icon v-if="i < current" name="check" :size="24" color="var(--c-ink-inverse)" />
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
    /* 左右内缩用 --page-gutter（42rpx），与 .page-pad 同口径，不要页面自己再补 */
    padding: 0 var(--page-gutter);
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
        /* 对勾图标靠继承拿色：tn-icon 的颜色解析只认 tn-* 类名与 #hex/rgb/rgba，
           var() 会被静默丢弃 → 图标继承默认黑，黑勾压在墨黑圆点上等于看不见。
           写在这里让勾继承反白，而不是继续用 color="var(--c-ink-inverse)"。 */
        color: var(--c-ink-inverse);
    }
    .is-done &__line {
        background: var(--c-ink);
    }
    .is-current &__dot {
        /* 01 §7.15（v2 校正）：原 8rpx 的「浅金底外环」写法作废 —— 它对本页底仅 1.08:1、
           对 --c-surface 1.15:1，落地等于没画。改 6rpx --c-ink-3 单层外环：
           对 --c-bg 5.35:1 / 对 --c-surface 5.67:1 / 对圆点 --c-ink 3.13:1，三项均 ≥3:1。
           6rpx 而非 8rpx：waitlist 圆点与文字间距仅 8rpx、order-detail 圆点上方净空 10rpx，
           8rpx 环会贴字贴边。不用 --c-control-border 是因为 §5.3 把它定义为「可点/可输入」的
           控件边界，步骤条不可点，语义错配。 */
        background: var(--c-ink);
        border-color: var(--c-ink);
        box-shadow: 0 0 0 6rpx var(--c-ink-3);
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
