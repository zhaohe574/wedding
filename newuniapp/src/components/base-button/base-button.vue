<template>
    <view
        class="btn"
        :class="[
            `btn--${variant}`,
            `btn--${size}`,
            { 'is-disabled': disabled || loading, 'btn--block': block }
        ]"
        :hover-class="disabled || loading ? 'none' : 'btn--hover'"
        :hover-start-time="20"
        :hover-stay-time="70"
        :hover-stop-propagation="true"
        :aria-busy="loading ? 'true' : undefined"
        :aria-disabled="disabled ? 'true' : undefined"
        @tap="handleTap"
    >
        <u-loading v-if="loading" :size="32" :color="loadingColor" />
        <text v-if="!loading || loadingText" class="btn__text">{{
            loading ? loadingText : text
        }}</text>
    </view>
</template>

<script setup lang="ts">
/**
 * 主按钮。颜色一律走 token，不在页面里写色值。
 * 默认带防重复点击（500ms），这是老端最常出的事故点。
 */
import { ref } from 'vue'

const props = withDefaults(
    defineProps<{
        text?: string
        /**
         * ink  = 黑底主操作（默认，对白底 18:1）
         * gold = 金底强调，仅限深色容器内或单屏唯一强调动作，必须配深墨字
         * ghost / danger / text 为次动作（03 §4.4 / §5.9 主次决策表）
         * T-37：历史别名 brand / primary 已随全站归零删除（顺序铁律最后一步）
         */
        variant?: 'ink' | 'gold' | 'ghost' | 'danger' | 'text'
        size?: 'lg' | 'md' | 'sm'
        disabled?: boolean
        loading?: boolean
        /** loading 时跟在 spinner 后的文案；不传则只显示 spinner（历史行为不变） */
        loadingText?: string
        block?: boolean
        /** 防重复点击间隔（毫秒），0 表示不限制 */
        lockMs?: number
    }>(),
    {
        text: '',
        variant: 'ink',
        size: 'md',
        disabled: false,
        loading: false,
        loadingText: '',
        block: true,
        lockMs: 500
    }
)

const emit = defineEmits<{ (e: 'click'): void }>()

const locked = ref(false)

/**
 * loading 时按钮必然进入 `.is-disabled`（中性浅底），所以 spinner 用 ink-3；
 * 原先按 variant 映射会在 ink 按钮上给出白 spinner，浅底上看不见。
 */
const loadingColor = 'var(--c-ink-3)'

function handleTap() {
    if (props.disabled || props.loading || locked.value) return
    emit('click')
    if (props.lockMs > 0) {
        locked.value = true
        setTimeout(() => (locked.value = false), props.lockMs)
    }
}
</script>

<style lang="scss" scoped>
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-1);
    border-radius: var(--r-pill);
    transition: opacity var(--dur-fast) var(--ease-out),
        background-color var(--dur-fast) var(--ease-out);
    font-weight: var(--fw-medium);

    &--hover {
        opacity: 0.82;
    }

    &--block {
        display: flex;
        width: 100%;
    }

    &__text {
        line-height: var(--lh-none);
    }

    /* variant：ink = 主操作（浅色墨黑实心，暗色香槟金实心）；gold = 金底强调（T-37 已删 brand/primary 别名选择器） */
    &--ink {
        background: var(--c-action-bg);
        color: var(--c-action-fg);
    }
    &--gold {
        background: var(--c-brand);
        color: var(--c-on-brand);
        border: 1rpx solid var(--c-brand-text);
    }
    &--ghost {
        background: transparent;
        color: var(--c-ink);
        border: 1rpx solid var(--c-control-border);
    }
    &--danger {
        background: var(--c-danger-bg);
        color: var(--c-danger);
        border: 1rpx solid var(--c-danger);
    }
    &--text {
        background: transparent;
        color: var(--c-ink-2);
        padding: 0;
    }

    /* size：md 提到 88rpx（44px）满足触控热区下限 */
    &--lg {
        height: 104rpx;
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        padding: 0 var(--sp-5);
    }
    &--md {
        height: 88rpx;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        padding: 0 var(--sp-4);
    }
    &--sm {
        position: relative;
        height: 60rpx;
        min-width: 88rpx;
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        padding: 0 var(--sp-3);

        /* 保持 60rpx 视觉高度，纵向扩展至 88rpx 独立热区 */
        &::before {
            content: '';
            position: absolute;
            top: -14rpx;
            bottom: -14rpx;
            left: 0;
            right: 0;
        }
    }

    /* 禁用态：显式色，去 opacity（视觉规范 §7.3 / §9.2 A10） */
    &.is-disabled {
        background: var(--c-neutral-bg);
        color: var(--c-ink-3);
        border: 1rpx solid var(--c-line);
        opacity: 1;
    }

    /* ghost / text 的按下态用浅底更明确（§8.2） */
    &--ghost.is-pressed,
    &--text.is-pressed {
        background: var(--c-surface-2);
        opacity: 1;
    }
}
</style>
