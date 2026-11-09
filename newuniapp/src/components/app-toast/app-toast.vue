<template>
    <!-- 无遮罩、不阻断交互：容器只覆盖 Toast 本体，未铺满全屏 -->
    <view
        v-if="render"
        class="toast"
        :class="[phase]"
        :aria-live="state.type === 'error' ? 'assertive' : 'polite'"
        aria-role="status"
    >
        <!-- v1 u-icon → v2 tn-icon：面性状态图标映射见 ICONS -->
        <tn-icon
            v-if="iconName"
            :name="iconName"
            :size="32"
            class="toast__icon"
            aria-hidden="true"
        />
        <text class="toast__text">{{ state.text }}</text>
    </view>
</template>

<script setup lang="ts">
/**
 * 全局 Toast 渲染层（配合 composables/useToast.ts 单例）。
 * 契约：不含操作按钮、无遮罩、不阻断交互、不做页面状态、不发起请求；
 * 只做 opacity + 8rpx 位移，动效时长与缓动全走 token。
 */
import { computed, ref, watch } from 'vue'
import { useToast } from '@/composables/useToast'

const { state } = useToast()

/** 面性状态图标（视觉规范 §6.2 规则 3 / §7.12：状态与结果类一律面性 / 填充）
 *  v1 u-icon → v2 tn-icon 名称映射：checkmark-circle-fill→success-circle-fill、
 *  close-circle-fill→close-fill（字体无 close-circle-fill，按"面性优先"取实心叉）、
 *  info-circle-fill→tips-fill */
const ICONS: Record<string, string> = {
    success: 'success-circle-fill',
    error: 'close-fill',
    warning: 'tips-fill',
    info: 'tips-fill',
    loading: ''
}
const iconName = computed(() => ICONS[state.value.type] || 'tips-fill')

// v-if 直接移除会丢失退出动画：用本地 render + phase 做 leave 过渡
const render = ref(state.value.visible)
const phase = ref<'toast--enter' | 'toast--leave'>('toast--enter')
let leaveTimer: ReturnType<typeof setTimeout> | null = null

watch(
    () => state.value.visible,
    (visible) => {
        if (leaveTimer) {
            clearTimeout(leaveTimer)
            leaveTimer = null
        }
        if (visible) {
            render.value = true
            phase.value = 'toast--enter'
        } else if (render.value) {
            phase.value = 'toast--leave'
            leaveTimer = setTimeout(() => {
                render.value = false
            }, 150) // --dur-fast
        }
    }
)
</script>

<style lang="scss" scoped>
.toast {
    position: fixed;
    left: 50%;
    bottom: 160rpx; /* 避开 tabBar / 吸底栏 */
    transform: translateX(-50%);
    max-width: 560rpx;
    min-height: 72rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--sp-1);
    padding: var(--sp-2) var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-toast-bg);
    box-shadow: var(--shadow-float);
    z-index: 1000;
    /* 无描边；不设遮罩、不铺满全屏，pointer 事件不拦截下层 */

    &__icon {
        flex-shrink: 0;
        /* 深底上的图标必须反白；tn-icon 不认 var()，靠父级继承 */
        color: var(--c-on-dark);
    }

    &__text {
        color: var(--c-on-dark);
        font-size: var(--fs-md);
        font-weight: var(--fw-regular);
        line-height: var(--lh-body);
        text-align: center;
    }

    &__sub {
        color: var(--c-on-dark-2);
    }

    &--enter {
        animation: toast-in var(--dur-base) var(--ease-out);
    }

    &--leave {
        animation: toast-out var(--dur-fast) var(--ease-in);
    }
}

@keyframes toast-in {
    from {
        opacity: 0;
        transform: translate(-50%, 8rpx);
    }
    to {
        opacity: 1;
        transform: translate(-50%, 0);
    }
}
@keyframes toast-out {
    from {
        opacity: 1;
        transform: translate(-50%, 0);
    }
    to {
        opacity: 0;
        transform: translate(-50%, 8rpx);
    }
}

@media (prefers-reduced-motion: reduce) {
    .toast--enter,
    .toast--leave {
        animation: none;
    }
}
</style>
