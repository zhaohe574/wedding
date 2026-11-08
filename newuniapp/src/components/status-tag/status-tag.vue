<template>
    <!-- 根节点用 view：小程序 text 组件内不允许嵌 view，圆点需独立 block 才能显示 10rpx 尺寸 -->
    <view
        class="tag"
        :class="[`tone-${tone}`, `tag--${size}`, { 'tag--long': isLong }]"
        :aria-label="label"
    >
        <!-- 形状编码：10rpx 实心状态点，色继承 .tone-* 的 color；装饰不进读屏顺序 -->
        <view v-if="dot" class="tag__dot" aria-hidden="true" />
        <text class="tag__label">{{ label }}</text>
    </view>
</template>

<script setup lang="ts">
/**
 * 状态标签。只接受 Tone（语义），不接受颜色 —— 颜色由 tokens.css 的 .tone-* 决定。
 * 未知状态时由调用方用 resolveStatusLabel 拿到「未知状态(n)」再传进来，组件不做兜底。
 */
import { computed } from 'vue'
import type { Tone } from '@shared/contracts'

const props = withDefaults(
    defineProps<{
        label: string
        tone?: Tone
        size?: 'sm' | 'md'
        /**
         * 是否显示左侧状态点。默认 true（状态类信息三重编码的一半）。
         * 纯等级标签（如「首席」「资深」）传 false：它不是状态，没有「当前是否成立」的语义。
         */
        dot?: boolean
    }>(),
    {
        tone: 'neutral',
        size: 'sm',
        dot: true
    }
)

/** >8 个中文字符改用 Caption 档并允许换行，不缩字号硬塞（按字符数而非 UTF-16 长度） */
const isLong = computed(() => [...props.label].length > 8)
</script>

<style lang="scss" scoped>
.tag {
    display: inline-flex;
    align-items: center;
    gap: var(--sp-1);
    border-radius: var(--r-xs);
    line-height: var(--lh-none);
    white-space: nowrap;
    font-weight: var(--fw-semibold); /* 600，形状 + 中文词 + 语义色三重编码 */
    /* 1rpx 同色调描边来自 tokens.css 的 .tone-{tone} { border: 1rpx solid var(--c-{tone}-border) } */

    &__dot {
        width: 10rpx;
        height: 10rpx;
        border-radius: var(--r-pill);
        background: currentColor;
        flex-shrink: 0;
    }

    &--sm {
        font-size: var(--fs-xs);
        padding: 6rpx 12rpx;
    }

    &--md {
        font-size: var(--fs-xs);
        padding: 8rpx 16rpx;
    }

    &--long {
        font-size: var(--fs-md);
        white-space: normal;
        line-height: var(--lh-body);
    }
}
</style>
