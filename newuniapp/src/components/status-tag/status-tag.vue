<template>
    <text class="tag" :class="[`tone-${tone}`, `tag--${size}`]">{{ label }}</text>
</template>

<script setup lang="ts">
/**
 * 状态标签。只接受 Tone（语义），不接受颜色 —— 颜色由 tokens.css 的 .tone-* 决定。
 * 未知状态时由调用方用 resolveStatusLabel 拿到「未知状态(n)」再传进来，组件不做兜底。
 */
import type { Tone } from '@shared/contracts'

withDefaults(defineProps<{ label: string; tone?: Tone; size?: 'sm' | 'md' }>(), {
    tone: 'neutral',
    size: 'sm'
})
</script>

<style lang="scss" scoped>
.tag {
    display: inline-block;
    border-radius: var(--r-xs);
    line-height: 1;
    white-space: nowrap;

    &--sm {
        font-size: var(--fs-xs);
        padding: 6rpx 12rpx;
    }

    &--md {
        font-size: var(--fs-xs);
        padding: 8rpx 16rpx;
    }
}
</style>
