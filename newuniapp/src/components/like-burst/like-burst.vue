<template>
    <view v-if="playing" class="burst" aria-hidden="true">
        <view
            v-for="n in SPARKS"
            :key="n"
            class="spark"
            :class="[`spark--${n}`, n % 2 === 0 ? 'spark--brand' : 'spark--danger']"
        />
    </view>
</template>

<script setup lang="ts">
/**
 * 点赞烟花微粒（01 总方案 §3.5）。
 *
 * active 由 false → true 时播放一次 560ms；600ms 冷却防连点抖动；
 * active 变回 false（取消点赞）不播放。
 *
 * 12 个微粒的角度/半径在 SCSS 里预计算成 12 组终点位移，
 * 不使用 CSS cos()/sin()（小程序 WCSS 基础库支持不一致）。
 */
import { onUnmounted, ref, watch } from 'vue'

const props = defineProps<{ active: boolean }>()

const SPARKS = 12
const BURST_MS = 560
const COOLDOWN_MS = 600

const playing = ref(false)
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
        if (!active) return
        const now = Date.now()
        // 冷却窗口内重复点亮：忽略，避免动画重入抖动
        if (now - lastPlayedAt < COOLDOWN_MS) return
        lastPlayedAt = now
        clearStopTimer()
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
/* 12 组终点位移：每 30° 一颗，半径交替 28rpx / 44rpx。
   角度从 0° 起顺时针，y 轴向下为正（translate3d 坐标系）。 */
$spark-frames: (
    (1, 28rpx, 0rpx),
    (2, 28rpx, 16rpx),
    (3, 18rpx, 31rpx),
    (4, 0rpx, 44rpx),
    (5, -22rpx, 38rpx),
    (6, -28rpx, 16rpx),
    (7, -44rpx, 0rpx),
    (8, -28rpx, -16rpx),
    (9, -22rpx, -38rpx),
    (10, 0rpx, -44rpx),
    (11, 18rpx, -31rpx),
    (12, 28rpx, -16rpx)
);

.burst {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 0;
    height: 0;
    pointer-events: none;
    z-index: 2;
}

.spark {
    position: absolute;
    left: 0;
    top: 0;
    width: 8rpx;
    height: 8rpx;
    margin-left: -4rpx;
    margin-top: -4rpx;
    border-radius: var(--r-pill);
    opacity: 0;
    pointer-events: none;
    /* 动画名由 .spark--N 指定，基础类只固定时长/曲线/填充模式 */
    animation-duration: 560ms;
    animation-timing-function: var(--ease-out);
    animation-iteration-count: 1;
    animation-fill-mode: forwards;
}

.spark--brand {
    background: var(--c-brand);
}

.spark--danger {
    background: var(--c-danger);
}

@each $index, $tx, $ty in $spark-frames {
    .spark--#{$index} {
        animation-name: burst-#{$index};
    }

    @keyframes burst-#{$index} {
        0% {
            opacity: 0;
            transform: translate3d(0, 0, 0) scale(0.4);
        }
        18% {
            opacity: 1;
        }
        100% {
            opacity: 0;
            transform: translate3d($tx, $ty, 0) scale(0.2);
        }
    }
}

@media (prefers-reduced-motion: reduce) {
    .spark {
        display: none;
    }
}
</style>
