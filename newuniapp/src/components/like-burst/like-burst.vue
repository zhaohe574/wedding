<template>
    <view v-if="playing" :key="burstKey" class="burst" aria-hidden="true">
        <view class="burst__ring" />
        <view
            v-for="n in SPARKS"
            :key="n"
            class="spark"
            :class="[
                `spark--${n}`,
                n % 3 === 0 ? 'spark--danger' : n % 2 === 0 ? 'spark--brand' : 'spark--soft'
            ]"
        />
    </view>
</template>

<script setup lang="ts">
/**
 * 点赞烟花微粒（01 总方案 §3.5）。
 *
 * active 由 false → true 时播放一次 580ms；240ms 冷却防连点抖动；
 * active 变回 false（取消点赞）立即收尾，取消后马上再点不会被冷却吞掉。
 *
 * 12 个微粒的角度/半径在 SCSS 里预计算成 12 组终点位移，
 * 不使用 CSS cos()/sin()（小程序 WCSS 基础库支持不一致）。
 */
import { onUnmounted, ref, watch } from 'vue'

const props = defineProps<{ active: boolean }>()

const SPARKS = 12
const BURST_MS = 580
const COOLDOWN_MS = 240

const playing = ref(false)
/** 参与根节点 :key，每次触发强制重建节点，动画从头播而不是复用旧节点 */
const burstKey = ref(0)
let lastPlayedAt = 0
let stopTimer: ReturnType<typeof setTimeout> | null = null

function clearStopTimer() {
    if (!stopTimer) return
    clearTimeout(stopTimer)
    stopTimer = null
}

watch(
    () => props.active,
    (active) => {
        // 取消点赞：立刻收尾并清冷却，否则紧接着再点会被 COOLDOWN_MS 吞掉
        if (!active) {
            clearStopTimer()
            playing.value = false
            lastPlayedAt = 0
            return
        }
        const now = Date.now()
        // 冷却窗口内重复点亮：忽略，避免动画重入抖动
        if (now - lastPlayedAt < COOLDOWN_MS) return
        lastPlayedAt = now
        clearStopTimer()
        burstKey.value += 1
        playing.value = true
        stopTimer = setTimeout(() => {
            playing.value = false
            stopTimer = null
        }, BURST_MS)
    }
)

onUnmounted(clearStopTimer)
</script>

<style lang="scss" scoped>

/* 12 组终点位移：每 30° 一颗，半径 42rpx–62rpx，飞出爱心外缘。
   角度从 0° 起顺时针，y 轴向下为正（translate3d 坐标系）。 */
$spark-frames: (
    (1, 44rpx, 0rpx),
    (2, 54rpx, 31rpx),
    (3, 21rpx, 36rpx),
    (4, 0rpx, 62rpx),
    (5, -24rpx, 42rpx),
    (6, -54rpx, 31rpx),
    (7, -62rpx, 0rpx),
    (8, -38rpx, -22rpx),
    (9, -31rpx, -54rpx),
    (10, 0rpx, -44rpx),
    (11, 31rpx, -54rpx),
    (12, 38rpx, -22rpx)
);

.burst {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 0;
    height: 0;
    pointer-events: none;
    overflow: visible;
    z-index: 3;
}

/* 扩散光环：一圈 danger 描边从爱心内部放大淡出，补上微粒之外的「冲击感」 */
.burst__ring {
    position: absolute;
    left: 0;
    top: 0;
    width: 44rpx;
    height: 44rpx;
    margin-left: -22rpx;
    margin-top: -22rpx;
    border-radius: var(--r-pill);
    border: 3rpx solid var(--c-danger);
    box-sizing: border-box;
    opacity: 0;
    pointer-events: none;
    animation: burst-ring 420ms cubic-bezier(0.2, 0, 0, 1) forwards;
}

@keyframes burst-ring {
    0% {
        opacity: 0.85;
        transform: scale(0.35);
    }
    100% {
        opacity: 0;
        transform: scale(1.65);
    }
}

.spark {
    position: absolute;
    left: 0;
    top: 0;
    width: 10rpx;
    height: 10rpx;
    margin-left: -5rpx;
    margin-top: -5rpx;
    border-radius: var(--r-pill);
    opacity: 0;
    pointer-events: none;
}

/* 三色微粒全部走 token：brand 香槟 / danger 朱红 / brand-soft 浅金 */
.spark--brand {
    background: var(--c-brand);
}

.spark--danger {
    background: var(--c-danger);
    width: 8rpx;
    height: 8rpx;
    margin-left: -4rpx;
    margin-top: -4rpx;
}

.spark--soft {
    background: var(--c-brand-soft);
}

@each $index, $tx, $ty in $spark-frames {
    /* 动画简写统一在 @each 里给定：名称随序号，时长/曲线/填充模式一次说完 */
    .spark--#{$index} {
        animation: burst-#{$index} 560ms cubic-bezier(0.2, 0, 0, 1) forwards;
    }

    @keyframes burst-#{$index} {
        0% {
            opacity: 0;
            transform: translate3d(0, 0, 0) scale(0.35);
        }
        22% {
            opacity: 1;
            transform: translate3d($tx * 0.45, $ty * 0.45, 0) scale(1.05);
        }
        100% {
            opacity: 0;
            transform: translate3d($tx, $ty, 0) scale(0.25);
        }
    }
}

@media (prefers-reduced-motion: reduce) {
    .spark,
    .burst__ring {
        display: none;
    }
}
</style>

<style lang="scss">
/* 小程序自定义组件 :host 不能带 scoped data-v 前缀，否则无法命中宿主节点 */
:host {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 0;
    height: 0;
    pointer-events: none;
    overflow: visible;
    z-index: 3;
}
</style>
