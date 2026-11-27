<template>
    <view
        class="card"
        :class="{
            'card--flush': flush,
            'card--compact': compact,
            'card--selected': selected,
            'press-spring': clickable,
            'is-pressing': pressing,
            'is-rebound': rebound
        }"
        :aria-role="clickable ? 'button' : undefined"
        :aria-label="ariaLabel || undefined"
        @touchstart="onTouchStart"
        @touchend="onTouchEnd"
        @touchcancel="onTouchCancel"
        @tap="onTap"
    >
        <slot />
        <!-- 选中态形状编码：不只靠颜色（视觉规范 §7.4 / AC-A-11）
             勾选一笔描出（v10 §3.6）：H5 走 SVG dashoffset，小程序走 CSS 双段短臂 → 长臂。
             节点只在 selected 时挂载；挂载后先停在「未描出」，下一拍再切终点，
             transition 才有起点（v-if 直接带终态会闪出，没有描出过程）。 -->
        <view v-if="selected" class="card__check" :class="{ 'is-drawn': drawn }" aria-hidden="true">
            <!-- #ifdef H5 -->
            <svg viewBox="0 0 24 24" class="card__check-svg">
                <path d="M5 12.5 L10 17.5 L19 7" pathLength="1" />
            </svg>
            <!-- #endif -->
            <!-- #ifndef H5 -->
            <view class="card__check-arms">
                <view class="card__check-arm card__check-arm--short" />
                <view class="card__check-arm card__check-arm--long" />
            </view>
            <!-- #endif -->
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 统一卡片容器：圆角、描边、阴影与内边距。
 *
 * v10 §2.1：浅色靠暖影分层（--c-card-border 为 transparent），暗色由同一变量的 --c-line
 * 承担描边 —— 组件只消费变量，不写 .scheme-dark 后代选择器（scoped 样式穿不透祖父节点）。
 *
 * v10 §3.1：clickable 时接管按压两段式弹簧（压实 ≥75ms → 松手 440ms 过冲回弹），
 * 不再用 hover-class 的透明度变暗（与卡片选中/禁用态叠加会脏）。
 */
import { onUnmounted, ref, watch } from 'vue'

const props = withDefaults(
    defineProps<{
        /** 去掉内边距（用于整图卡片） */
        flush?: boolean
        clickable?: boolean
        /** 紧凑卡片：24rpx 内边距、32rpx 圆角（--r-md） */
        compact?: boolean
        /** 选中态只换描边颜色，不增线宽，不引起尺寸跳动 */
        selected?: boolean
        /** clickable 时的读屏标签（含条目主信息） */
        ariaLabel?: string
    }>(),
    { flush: false, clickable: false, compact: false, selected: false, ariaLabel: '' }
)

const emit = defineEmits<{ (e: 'tap'): void }>()

/** 快速轻点的最短压实时长：不足 75ms 人眼与触感都来不及感知下压 */
const PRESS_MIN_MS = 75
/** 与 globals.scss 的 spring-release 动画时长一致，播完即摘类，避免动画属性长期挂在节点上 */
const RELEASE_MS = 440

const pressing = ref(false)
const rebound = ref(false)
let pressStartAt = 0
let pressTimer: ReturnType<typeof setTimeout> | null = null
let reboundTimer: ReturnType<typeof setTimeout> | null = null

function clearTimers() {
    if (pressTimer) {
        clearTimeout(pressTimer)
        pressTimer = null
    }
    if (reboundTimer) {
        clearTimeout(reboundTimer)
        reboundTimer = null
    }
}

function onTouchStart() {
    if (!props.clickable) return
    clearTimers()
    rebound.value = false
    pressStartAt = Date.now()
    pressing.value = true
}

function onTouchEnd() {
    // 只看 pressing：按压中途 clickable 被改掉时也要把状态收干净，否则会停在 96%
    if (!pressing.value) return
    const rest = Math.max(0, PRESS_MIN_MS - (Date.now() - pressStartAt))
    if (pressTimer) clearTimeout(pressTimer)
    // 不足 75ms 的轻点延时补齐再回弹，避免「点了但看不出按过」
    pressTimer = setTimeout(() => {
        pressTimer = null
        pressing.value = false
        rebound.value = true
        reboundTimer = setTimeout(() => {
            rebound.value = false
            reboundTimer = null
        }, RELEASE_MS)
    }, rest)
}

/** 手指变成滑动（滚动列表）：直接复位，不播回弹，避免卡片在滚动中晃一下 */
function onTouchCancel() {
    if (!props.clickable) return
    clearTimers()
    pressing.value = false
    rebound.value = false
}

onUnmounted(clearTimers)

/* ---- 选中勾一笔描出（§3.6） ---- */
/** 挂载后等一帧再切终点：留出起点样式，240ms 描出才有过程 */
const DRAW_DELAY_MS = 30
const drawn = ref(false)
let drawTimer: ReturnType<typeof setTimeout> | null = null

watch(
    () => props.selected,
    (on) => {
        if (drawTimer) {
            clearTimeout(drawTimer)
            drawTimer = null
        }
        if (!on) {
            drawn.value = false
            return
        }
        drawn.value = false
        drawTimer = setTimeout(() => {
            drawn.value = true
            drawTimer = null
        }, DRAW_DELAY_MS)
    },
    { immediate: true }
)

onUnmounted(() => {
    if (drawTimer) clearTimeout(drawTimer)
})

function onTap() {
    emit('tap')
}
</script>

<style lang="scss" scoped>
.card {
    position: relative;
    background: var(--c-surface);
    /* 浅色 transparent / 暗色 --c-line，明暗切换靠变量继承，跨组件作用域安全 */
    border: 1rpx solid var(--c-card-border);
    border-radius: var(
        --r-md
    ); /* --r-lg 28rpx → --r-md 32rpx（A7；注释原写 20rpx 有误，v2 校正 2026-11-08） */
    padding: var(--sp-4);
    box-shadow: var(--shadow-card); /* 暖褐三层柔影，浅色承担分层 */

    &--compact {
        padding: var(--sp-3);
        border-radius: var(--r-md);
    }

    &--selected {
        border-color: var(--c-brand-text); /* --c-brand → --c-brand-text（A8，5.34:1 ≥3:1） */
        /* 只改颜色，不增 border-width，不引起尺寸跳动 */
    }

    &--flush {
        padding: 0;
        overflow: hidden;
    }

    /* 右上角选中徽标：金色圆点 + 反白对勾。
       底色与对勾同拍进入（180ms / 240ms），选中瞬间不再是「凭空冒出一个图标」。 */
    &__check {
        position: absolute;
        top: var(--sp-2);
        right: var(--sp-2);
        width: 36rpx;
        height: 36rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: transparent;
        color: var(--c-on-dark);
        pointer-events: none;
        transition: background-color 180ms var(--ease-out);

        &.is-drawn {
            background: var(--c-brand-text);
        }
    }

    /* H5：真实 SVG 一笔描出（pathLength=1 → dashoffset 1→0 即整段画完） */
    &__check-svg {
        width: 60%;
        height: 60%;

        path {
            fill: none;
            stroke: var(--c-on-dark);
            stroke-width: 2.4;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 1;
            stroke-dashoffset: 1;
            transition: stroke-dashoffset 240ms var(--ease-out);
        }
    }

    &__check.is-drawn &__check-svg path {
        stroke-dashoffset: 0;
    }

    /* 小程序：rotate(-45deg) 把「竖臂 + 横臂」的 L 转成对勾，
       先短臂 90ms 纵向长出来，再长臂 150ms 横向补上（延迟 90ms），视觉等同一笔描出。 */
    &__check-arms {
        position: relative;
        width: 20rpx;
        height: 20rpx;
        transform: rotate(-45deg);
    }

    &__check-arm {
        position: absolute;
        left: 0;
        bottom: 0;
        background: var(--c-on-dark);
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

    &__check.is-drawn .card__check-arm--short {
        height: 45%;
    }

    &__check.is-drawn .card__check-arm--long {
        width: 100%;
    }
}

/* v10 §3.13：减弱动效下直接呈现终态（勾仍在，只是不描出） */
@media (prefers-reduced-motion: reduce) {
    .card__check,
    .card__check-arm--short,
    .card__check-arm--long,
    .card__check-svg path {
        transition: none;
    }
}
</style>

<style lang="scss">
/* 小程序自定义组件宿主默认 display:inline，导致父级 margin-top 失效与 flex 宽度异常 */
:host {
    display: block;
    width: 100%;
}
</style>
