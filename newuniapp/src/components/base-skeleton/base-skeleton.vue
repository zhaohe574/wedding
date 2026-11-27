<template>
    <view class="skeleton" aria-hidden="true">
        <view
            v-for="i in rows"
            :key="i"
            class="skeleton__row"
            :class="`skeleton__row--${variant}`"
            :style="rowStyle"
        >
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
/**
 * 骨架屏：首屏不等接口，先渲染骨架再替换内容（无交互、无事件，与最终内容同尺寸）。
 *
 * v10 §3.4：
 *  - 等高：页面用 height 显式传设计稿高度（如卡片 1:1 占位），与真实内容同高，切换不跳；
 *  - 双态暖调 Shimmer：浅色暖金 / 暗色暖白（--sk-sheen 已是两套值），
 *    用 background-position 扫光而不是整块 opacity 呼吸灯。
 */
import { computed } from 'vue'

const props = withDefaults(
    defineProps<{
        rows?: number
        variant?: 'text' | 'card'
        /** 显式高度（如 '220rpx'）：覆盖行内自适应，保证与真实卡片 1:1 等高 */
        height?: string
        /** 骨架块圆角，默认与卡片一致 */
        radius?: string
    }>(),
    {
        rows: 3,
        variant: 'card',
        height: '',
        radius: 'var(--r-md)'
    }
)

const rowStyle = computed<Record<string, string>>(() => {
    const style: Record<string, string> = { borderRadius: props.radius }
    if (props.height) style.height = props.height
    return style
})
</script>

<style lang="scss" scoped>
/* 流光位移（GPU 友好）：只动 background-position，不动 opacity / 布局 */
@keyframes sheen {
    0% {
        background-position: 180% 0;
    }
    100% {
        background-position: -60% 0;
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
        /* 浅色透明 / 暗色 --c-line：与真实卡片同一套描边变量，跨组件继承 */
        border: 1rpx solid var(--c-card-border);
        border-radius: var(--r-md);
        box-shadow: var(--shadow-card);
        padding: var(--sp-4);
        box-sizing: border-box;
        overflow: hidden;
    }

    /* 骨架块：暖调流光扫过中性底，两态共用同一套 keyframes */
    &__thumb,
    &__line {
        background: linear-gradient(
                100deg,
                transparent 20%,
                var(--sk-sheen) 40%,
                transparent 60%
            )
            var(--c-neutral-bg);
        background-size: 220% 100%;
        animation: sheen 1.4s linear infinite;
    }

    &__thumb {
        width: 120rpx;
        height: 120rpx;
        border-radius: var(--r-md);
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

        &--title {
            width: 55%;
            height: 32rpx;
        }

        &--short {
            width: 35%;
        }
    }
}

/* v10 §3.13：减弱动效 → 静态占位（骨架本身仍在，不靠动画表达状态） */
@media (prefers-reduced-motion: reduce) {
    .skeleton__thumb,
    .skeleton__line {
        animation: none;
        background: var(--c-neutral-bg);
    }
}
</style>
