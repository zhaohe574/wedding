<template>
    <view
        class="eswitch"
        :class="{
            'is-on': modelValue,
            'is-disabled': disabled,
            'is-stretching--on': stretchTo === 'on',
            'is-stretching--off': stretchTo === 'off'
        }"
        aria-role="switch"
        :aria-checked="modelValue ? 'true' : 'false'"
        :aria-label="ariaLabel || undefined"
        :aria-disabled="disabled ? 'true' : undefined"
        @tap="toggle"
    >
        <view class="eswitch__track">
            <view class="eswitch__thumb" />
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 弹性开关（01 总方案 §3.12）。
 *
 * 轨道消费 --c-action-bg / --c-action-fg（浅色墨黑、暗色香槟金），
 * 一举替代三处原生 <switch color="#C6A15B"> 的硬编码。
 * 切换瞬间滑块沿运动方向拉成胶囊再弹回，同时触发 light 触觉。
 */
import { onUnmounted, ref } from 'vue'
import { haptic } from '@/utils/haptics'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        disabled?: boolean
        ariaLabel?: string
    }>(),
    { disabled: false, ariaLabel: '' }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'change', value: boolean): void
}>()

/** 与滑块回缩动画同时长（320ms spring-soft） */
const STRETCH_MS = 320

const stretchTo = ref<'' | 'on' | 'off'>('')
let stretchTimer: ReturnType<typeof setTimeout> | null = null

function toggle() {
    if (props.disabled) return

    const next = !props.modelValue
    emit('update:modelValue', next)
    emit('change', next)
    haptic('light', 'elastic-switch')

    stretchTo.value = next ? 'on' : 'off'
    if (stretchTimer) clearTimeout(stretchTimer)
    stretchTimer = setTimeout(() => {
        stretchTo.value = ''
        stretchTimer = null
    }, STRETCH_MS)
}

onUnmounted(() => {
    if (stretchTimer) clearTimeout(stretchTimer)
})
</script>

<style lang="scss" scoped>
$track-w: 92rpx;
$track-h: 52rpx;
$thumb: 44rpx;
$pad: 4rpx;
$travel: $track-w - $thumb - $pad * 2;

/* 外层热区 ≥ 88rpx（44px），视觉轨道保持 92×52 */
.eswitch {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 88rpx;
    min-height: 88rpx;
    padding: 0 var(--sp-1);
    box-sizing: border-box;
}

.eswitch__track {
    position: relative;
    width: $track-w;
    height: $track-h;
    border-radius: var(--r-pill);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-control-border);
    box-sizing: border-box;
    transition:
        background-color 180ms var(--ease-out),
        border-color 180ms var(--ease-out);
}

.eswitch__thumb {
    position: absolute;
    top: $pad;
    left: $pad;
    width: $thumb;
    height: $thumb;
    border-radius: var(--r-pill);
    background: var(--c-ink-3);
    transform: translate3d(0, 0, 0);
    transition:
        transform 320ms var(--ease-spring-soft),
        background-color 180ms var(--ease-out);
}

.eswitch.is-on .eswitch__track {
    background: var(--c-action-bg);
    border-color: var(--c-action-bg);
}

.eswitch.is-on .eswitch__thumb {
    background: var(--c-action-fg);
    transform: translate3d($travel, 0, 0);
}

/* 切换瞬间的胶囊拉伸：方向由 is-stretching--on/off 决定，避免关闭时弹到对侧 */
.eswitch.is-stretching--on .eswitch__thumb {
    transform: translate3d($travel, 0, 0) scaleX(1.34);
}

.eswitch.is-stretching--off .eswitch__thumb {
    transform: translate3d(0, 0, 0) scaleX(1.34);
}

.eswitch.is-disabled {
    opacity: 0.45;
}

@media (prefers-reduced-motion: reduce) {
    .eswitch__track,
    .eswitch__thumb {
        transition: none;
    }
    .eswitch.is-stretching--on .eswitch__thumb {
        transform: translate3d($travel, 0, 0);
    }
    .eswitch.is-stretching--off .eswitch__thumb {
        transform: translate3d(0, 0, 0);
    }
}
</style>
