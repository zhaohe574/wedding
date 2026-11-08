<template>
    <view class="skeleton" aria-hidden="true">
        <view v-for="i in rows" :key="i" class="skeleton__row" :class="`skeleton__row--${variant}`">
            <view v-if="variant === 'card'" class="skeleton__thumb" />
            <view class="skeleton__lines">
                <view class="skeleton__line skeleton__line--title" />
                <view class="skeleton__line" />
                <view v-if="variant === 'card'" class="skeleton__line skeleton__line--short" />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/** 骨架屏：首屏不等接口，先渲染骨架再替换内容（无交互、无事件，与最终内容同尺寸） */
withDefaults(defineProps<{ rows?: number; variant?: 'text' | 'card' }>(), {
    rows: 3,
    variant: 'card'
})
</script>

<style lang="scss" scoped>
@keyframes shimmer {
    0% {
        opacity: 0.55;
    }
    50% {
        opacity: 1;
    }
    100% {
        opacity: 0.55;
    }
}

.skeleton {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);

    &__row {
        display: flex;
        gap: var(--sp-3);
        background: var(--c-surface); /* 无描边、无阴影、无渐变 */
        border-radius: var(--r-md); /* --r-lg → --r-md，与卡片一致 */
        padding: var(--sp-4);
        animation: shimmer 1.2s ease-in-out infinite; /* 1.4s → 1.2s（循环动效白名单 A18） */
    }

    &__thumb {
        width: 120rpx;
        height: 120rpx;
        border-radius: var(--r-md);
        background: var(--c-neutral-bg);
        flex-shrink: 0;
    }

    &__lines {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-2);
        justify-content: center;
    }

    &__line {
        height: 24rpx;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);

        &--title {
            width: 55%;
            height: 32rpx;
        }

        &--short {
            width: 35%;
        }
    }
}

/* 循环动效白名单 + 减少动态效果：静态占位仍完整保留，不靠动画消失表达状态 */
@media (prefers-reduced-motion: reduce) {
    .skeleton__row {
        animation: none;
        background: var(--c-neutral-bg);
    }
}
</style>
