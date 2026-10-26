<template>
    <view class="base-ring" :style="rootStyle">
        <view class="base-ring__track" :style="trackStyle"></view>

        <view class="base-ring__half base-ring__half--right">
            <view class="base-ring__fill" :style="rightStyle"></view>
        </view>
        <view class="base-ring__half base-ring__half--left">
            <view class="base-ring__fill" :style="leftStyle"></view>
        </view>

        <view class="base-ring__inner">
            <slot>
                <text class="base-ring__value" :style="{ color, fontSize: valueSize }">{{ displayValue }}</text>
            </slot>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    /** 进度 0-100 */
    value?: number
    /** 直径，单位 rpx */
    size?: number | string
    /** 环宽，单位 rpx */
    stroke?: number | string
    color?: string
    trackColor?: string
    /** 中间文字，缺省显示百分比 */
    text?: string
    valueSize?: string
}

const props = withDefaults(defineProps<Props>(), {
    value: 0,
    size: 120,
    stroke: 6,
    color: 'var(--wm-color-champagne, #D9BE82)',
    trackColor: 'rgba(255, 253, 248, 0.14)',
    text: '',
    valueSize: '28rpx'
})

const toRpx = (value: number | string) =>
    typeof value === 'number' || /^\d+(\.\d+)?$/.test(String(value)) ? `${value}rpx` : String(value)

const percent = computed(() => Math.min(100, Math.max(0, Number(props.value) || 0)))

const rootStyle = computed(() => ({
    width: toRpx(props.size),
    height: toRpx(props.size)
}))

const trackStyle = computed(() => ({
    borderWidth: toRpx(props.stroke),
    borderColor: props.trackColor
}))

const fillBase = computed(() => ({
    borderWidth: toRpx(props.stroke),
    borderColor: props.color
}))

const rightStyle = computed(() => ({
    ...fillBase.value,
    transform: `rotate(${Math.min(percent.value, 50) * 3.6 - 180}deg)`
}))

const leftStyle = computed(() => ({
    ...fillBase.value,
    transform: `rotate(${Math.max(percent.value - 50, 0) * 3.6 - 180}deg)`,
    display: percent.value > 50 ? 'block' : 'none'
}))

const displayValue = computed(() => props.text || `${Math.round(percent.value)}%`)
</script>

<style lang="scss" scoped>
.base-ring {
    position: relative;
    flex-shrink: 0;

    &__track {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        box-sizing: border-box;
        border-style: solid;
        border-radius: 50%;
    }

    &__half {
        position: absolute;
        top: 0;
        width: 50%;
        height: 100%;
        overflow: hidden;
    }

    &__half--right {
        right: 0;
    }

    &__half--left {
        left: 0;
    }

    &__fill {
        position: absolute;
        top: 0;
        width: 200%;
        height: 100%;
        box-sizing: border-box;
        border-style: solid;
        border-radius: 50%;
        transition: transform 0.4s ease;
    }

    &__half--right &__fill {
        left: 0;
    }

    &__half--left &__fill {
        right: 0;
    }

    &__inner {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__value {
        font-family: var(--wm-font-family-display, Georgia, serif);
        font-weight: 500;
    }
}
</style>
