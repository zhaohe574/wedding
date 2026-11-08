<template>
    <view class="agree">
        <view class="agree__row" @tap="toggle">
            <view
                class="agree__box"
                :class="{ 'is-on': modelValue }"
                :aria-role="'checkbox'"
                :aria-checked="modelValue"
                :aria-label="'同意协议'"
                hover-class="is-pressed"
            >
                <tn-icon v-if="modelValue" name="check" aria-hidden="true" />
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
        align-items: flex-start;
        gap: var(--sp-2);
    }

    /* 24rpx 方框 + 88rpx 热区（热区靠 row 的 @tap，整行可点） */
    &__box {
        width: 30rpx;
        height: 30rpx;
        margin-top: 6rpx; /* 与首行文字视觉居中 */
        flex: none;
        box-sizing: border-box;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--fs-xs);
        line-height: var(--lh-none);
        color: transparent;

        &.is-on {
            border-color: var(--c-ink);
            background: var(--c-ink);
            color: var(--c-brand-soft); /* 墨底香槟勾（D4） */
        }
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
        padding-left: 44rpx; /* 与文案左缘对齐（28rpx 方框 + 16rpx 间距） */
        font-size: var(--fs-xs);
        color: var(--c-danger);
        line-height: var(--lh-body);
    }
}
</style>
