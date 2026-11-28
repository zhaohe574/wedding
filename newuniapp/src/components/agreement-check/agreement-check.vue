<template>
    <view class="agree">
        <view class="agree__row" @tap="toggle">
            <view
                class="agree__box"
                :class="{ 'is-on': modelValue }"
                :aria-role="'checkbox'"
                :aria-checked="modelValue"
                :aria-label="'同意协议'"
            >
                <!-- v10 §3.6 一笔描出：H5 用 SVG dashoffset，小程序用 CSS 双段短臂 → 长臂 -->
                <!-- #ifdef H5 -->
                <svg viewBox="0 0 24 24" class="agree__check">
                    <path d="M5 12.5 L10 17.5 L19 7" pathLength="1" />
                </svg>
                <!-- #endif -->
                <!-- #ifndef H5 -->
                <view class="agree__check-arms" aria-hidden="true">
                    <view class="agree__arm agree__arm--short" />
                    <view class="agree__arm agree__arm--long" />
                </view>
                <!-- #endif -->
            </view>
            <view class="agree__text">
                <slot />
            </view>
        </view>
        <!-- D4 合规修正：未勾选不置灰按钮，点提交后在这里出红字提示 -->
        <text v-if="error" class="agree__error" aria-live="polite">{{ error }}</text>
    </view>
</template>

<script setup lang="ts">
/**
 * 协议勾选行（登录 21 / 预约确认 08 / 候补 19 三处共用）。
 *
 * 合规修正（D4，有意偏离设计稿，已登记）：
 *  1. **默认未勾选**：设计稿默认勾选，属于「默认同意」，不合规；
 *  2. **未勾选时提交按钮不置灰**：置灰会让用户不知道哪里错了；点击后在勾选行下方出红字，
 *     让用户知道「要勾这里」；
 *  3. 不提供「一键全选/记住选择」。
 *
 * 契约：内部自绘勾选框（不包 tn-checkbox：它带 padding/label 组合，在「整行可点」场景下会吃掉热区）。
 * 文案由页面通过默认插槽给（协议名要能单独点击时，页面在插槽里放 <text @tap>）。
 *
 * v10 §3.6：方框底色 180ms 过渡，对勾一笔描出 240ms（不再瞬间跳出一个 tn-icon）。
 * v10 §2.5：整行热区抬到 88rpx，文案里的协议链接仍可各自点击（row 的 tap 会被子节点 stop）。
 */
const props = withDefaults(defineProps<{ modelValue: boolean; error?: string }>(), { error: '' })

const emit = defineEmits<{ (e: 'update:modelValue', v: boolean): void }>()

function toggle() {
    emit('update:modelValue', !props.modelValue)
}
</script>

<style lang="scss" scoped>
.agree {
    &__row {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        min-height: 88rpx; /* §2.5 整行最小热区 */
        padding: var(--sp-1) 0;
        box-sizing: border-box;
    }

    /* 30rpx 方框 + 88rpx 热区（热区靠 row 的 @tap，整行可点） */
    &__box {
        width: 30rpx;
        height: 30rpx;
        flex: none;
        box-sizing: border-box;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 180ms var(--ease-out), border-color 180ms var(--ease-out);

        &.is-on {
            border-color: var(--c-active-border);
            background: var(--c-active-bg);
        }
    }

    /* H5：真实 SVG 一笔描出（pathLength=1 → dashoffset 1→0 即整段画完） */
    &__check {
        width: 76%;
        height: 76%;

        path {
            fill: none;
            stroke: var(--c-active-fg);
            stroke-width: 2.6;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 1;
            stroke-dashoffset: 1;
            transition: stroke-dashoffset 240ms var(--ease-out);
        }
    }

    &__box.is-on &__check path {
        stroke-dashoffset: 0;
    }

    /* 小程序：rotate(-45deg) 把「竖臂 + 横臂」的 L 转成对勾，
       短臂 90ms 先长出、长臂 150ms 延迟 90ms 补上，总长 240ms，视觉等同一笔描出。 */
    &__check-arms {
        position: relative;
        width: 22rpx;
        height: 22rpx;
        transform: rotate(-45deg);
    }

    &__arm {
        position: absolute;
        left: 0;
        bottom: 0;
        background: var(--c-active-fg);
        border-radius: var(--r-pill);

        &--short {
            width: 3rpx;
            height: 0;
            transition: height 90ms var(--ease-out);
        }

        &--long {
            width: 0;
            height: 3rpx;
            transition: width 150ms var(--ease-out) 90ms;
        }
    }

    &__box.is-on .agree__arm--short {
        height: 45%;
    }

    &__box.is-on .agree__arm--long {
        width: 100%;
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
    }

    &__error {
        display: block;
        margin-top: var(--sp-1);
        padding-left: 44rpx; /* 与文案左缘对齐（30rpx 方框 + 16rpx 间距） */
        font-size: var(--fs-xs);
        color: var(--c-danger);
        line-height: var(--lh-body);
    }
}

/* v10 §3.13：减弱动效下直接呈现终态（勾仍在，只是不描出） */
@media (prefers-reduced-motion: reduce) {
    .agree__box,
    .agree__check path,
    .agree__arm--short,
    .agree__arm--long {
        transition: none;
    }
}
</style>
