<template>
    <view class="skeleton">
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
/** 骨架屏：首屏不等接口，先渲染骨架再替换内容 */
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
        background: var(--c-surface);
        border-radius: var(--r-lg);
        padding: var(--sp-4);
        animation: shimmer 1.4s ease-in-out infinite;
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
</style>
