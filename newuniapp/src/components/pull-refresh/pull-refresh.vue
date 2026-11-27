<template>
    <view
        class="pr"
        :class="['pr--' + tone, 'pr--' + state, { 'pr--dragging': dragging }]"
        :style="wrapStyle"
        aria-hidden="true"
    >
        <view class="pr__inner">
            <view class="pr__emblem">
                <view v-if="state === 'refreshing'" class="pr__spinner" />
                <tn-icon v-else-if="state === 'done'" name="check" :size="24" />
                <view v-else class="pr__arrow" :class="{ 'is-up': state === 'ready' }">
                    <tn-icon name="down-arrow" :size="24" />
                </view>
            </view>
            <text v-if="statusText" class="pr__text">{{ statusText }}</text>
        </view>
        <view class="pr__bar" :style="barStyle" />
    </view>
</template>

<script setup lang="ts">
/**
 * 下拉刷新指示器（page-shell 内置自定义下拉刷新的视觉层，四态）：
 * idle 隐藏 / pulling 下拉刷新 / ready 松手刷新 / refreshing 正在刷新 / done 已更新。
 * 颜色、字号、圆角一律消费 tokens.css 语义变量；tone="light" 用于透明导航（深色 hero）下的深底浅字。
 */
import { computed, watch } from 'vue'
import { haptic } from '@/utils/haptics'

type PullState = 'idle' | 'pulling' | 'ready' | 'refreshing' | 'done'

const props = withDefaults(
    defineProps<{
        state: PullState
        height: number
        progress: number
        dragging: boolean
        tone?: 'dark' | 'light'
        topInset?: number
    }>(),
    {
        tone: 'dark',
        topInset: 0
    }
)

const statusText = computed(() => {
    switch (props.state) {
        case 'pulling':
            return '下拉刷新'
        case 'ready':
            return '松手刷新'
        case 'refreshing':
            return '正在刷新…'
        case 'done':
            return '已更新'
        default:
            return ''
    }
})

/** 刷新中 / 完成态进度条铺满；其余跟随手势进度 */
const barRatio = computed(() =>
    props.state === 'refreshing' || props.state === 'done'
        ? 1
        : Math.max(0, Math.min(1, props.progress))
)

/**
 * 触觉只挂在「可松手 / 已更新」两个跨过阈值的瞬间。
 * watch 本身只在状态变化时触发，停留在同一状态不会重复震。
 */
watch(
    () => props.state,
    (next) => {
        if (next === 'ready' || next === 'done') haptic('light', 'pull-refresh')
    }
)

/**
 * 外层高度 = 拉出高度 + 透明导航下的状态栏补位。
 * 关键：height <= 0 时 paddingTop 必须为 0 —— box-sizing: border-box 下
 * 残留的 paddingTop 会把 0 高度撑出来，收起后顶部多出一条空隙。
 */
const wrapStyle = computed(() => {
    const active = props.height > 0
    const padTop = active && props.topInset > 0 ? props.topInset : 0
    return {
        height: `${active ? props.height + padTop : 0}px`,
        paddingTop: `${padTop}px`
    }
})

const barStyle = computed(() => ({
    width: `${Math.round(barRatio.value * 48)}rpx`,
    opacity: props.height > 4 ? 1 : 0
}))
</script>

<style lang="scss" scoped>
.pr {
    width: 100%;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: center;
    transition: height var(--dur-base) var(--ease-out);

    &--dragging {
        transition: none;
    }

    &--dark {
        background: var(--c-bg);
    }

    &--light {
        background: var(--c-dark-2);
    }
}

.pr__inner {
    height: 96rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-2);
}

.pr__emblem {
    width: 44rpx;
    height: 44rpx;
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-line-strong);
    background: var(--c-surface);
    color: var(--c-ink-2);
    display: flex;
    align-items: center;
    justify-content: center;
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out),
        color var(--dur-fast) var(--ease-out),
        transform var(--dur-fast) var(--ease-out);
}

.pr--light .pr__emblem {
    background: var(--c-dark-1);
    border-color: var(--c-line-strong);
    color: var(--c-on-dark-2);
}

.pr--ready .pr__emblem {
    border-color: var(--c-brand);
    background: var(--c-brand-bg);
    color: var(--c-brand-text);
}

.pr--light.pr--ready .pr__emblem {
    background: var(--c-dark-3);
    border-color: var(--c-brand-soft);
    color: var(--c-brand-soft);
}

.pr--done .pr__emblem {
    border-color: var(--c-success-border);
    background: var(--c-success-bg);
    color: var(--c-success);
    animation: pr-pop var(--dur-base) var(--ease-out);
}

.pr--light.pr--done .pr__emblem {
    background: var(--c-dark-3);
    border-color: var(--c-brand-soft);
    color: var(--c-brand-soft);
}

.pr__arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform var(--dur-fast) var(--ease-out);

    &.is-up {
        transform: rotate(180deg);
    }
}

.pr__spinner {
    width: 24rpx;
    height: 24rpx;
    border-radius: var(--r-pill);
    border: 3rpx solid var(--c-line-strong);
    border-top-color: var(--c-brand);
    animation: pr-spin 720ms linear infinite;
}

.pr__text {
    font-size: var(--fs-xs);
    font-weight: var(--fw-medium);
    letter-spacing: 0.04em;
    line-height: var(--lh-none);
    color: var(--c-ink-3);
}

.pr--ready .pr__text {
    color: var(--c-brand-text);
    font-weight: var(--fw-semibold);
}

.pr--done .pr__text {
    color: var(--c-success);
}

.pr--light .pr__text {
    color: var(--c-on-dark-2);
}

.pr--light.pr--ready .pr__text,
.pr--light.pr--done .pr__text {
    color: var(--c-brand-soft);
}

.pr__bar {
    height: 2rpx;
    margin-bottom: var(--sp-1);
    border-radius: var(--r-pill);
    background: var(--c-brand);
    transition: opacity var(--dur-fast) var(--ease-out);
}

@keyframes pr-spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes pr-pop {
    0% {
        transform: scale(0.75);
    }
    60% {
        transform: scale(1.08);
    }
    100% {
        transform: scale(1);
    }
}

/* v10 §3.13：减弱动效下不转圈、不弹跳，状态仍由文案与颜色表达 */
@media (prefers-reduced-motion: reduce) {
    .pr__spinner {
        animation: none;
    }

    .pr--done .pr__emblem {
        animation: none;
    }

    .pr__arrow {
        transition: none;
    }
}
</style>
