<template>
    <view class="steps" :aria-label="`流程进度，第 ${current + 1} 步，共 ${steps.length} 步：${steps[current] || ''}`">
        <view
            v-for="(s, i) in steps"
            :key="i"
            class="steps__item"
            :class="{ 'is-done': i < current, 'is-current': i === current, 'is-failed': i === failedIndex }"
            :aria-current="i === current ? 'step' : undefined"
        >
            <view class="steps__dot" aria-hidden="true">
                <u-icon v-if="i < current" name="checkmark" :size="24" color="var(--c-ink-inverse)" />
                <text v-else class="steps__no">{{ i + 1 }}</text>
            </view>
            <text class="steps__label">{{ s }}</text>
            <view v-if="i < steps.length - 1" class="steps__line" aria-hidden="true" />
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 步骤条（只读展示，收编 booking.vue 内联 .steps）。
 * 契约：不控制步骤切换（由页面 nextStep/prevStep + 底栏按钮）、无 @change、不可点、
 * 不写 STEPS 文案、不改业务步骤数（O1：booking 恒定 3 步）。
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
        border: 1rpx solid var(--c-line-strong);   /* 未达步骤连接线/圆点描边（装饰位白名单） */
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
