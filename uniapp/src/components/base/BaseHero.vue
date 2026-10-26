<template>
    <view class="base-hero" :style="rootStyle">
        <image
            v-if="image"
            class="base-hero__image"
            :src="image"
            mode="aspectFill"
        />
        <view v-if="image" class="base-hero__scrim" :style="{ background: scrimColor }"></view>

        <view class="base-hero__orb base-hero__orb--gold" :style="orbGoldStyle"></view>
        <view class="base-hero__orb base-hero__orb--light" :style="orbLightStyle"></view>
        <view class="base-hero__orb base-hero__orb--warm" :style="orbWarmStyle"></view>

        <view class="base-hero__nav">
            <slot name="nav" />
        </view>

        <view class="base-hero__content">
            <slot />
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    /** 背景图地址；为空时使用深色底 + 光晕 */
    image?: string
    /** hero 高度，单位 rpx 或 css 长度 */
    height?: string | number
    /** 图片遮罩透明度，0-1 */
    overlay?: number
    /** 是否渲染装饰光晕 */
    glow?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    image: '',
    height: 400,
    overlay: 0.42,
    glow: true
})

const toRpx = (value: string | number) =>
    typeof value === 'number' || /^\d+(\.\d+)?$/.test(String(value)) ? `${value}rpx` : String(value)

const rootStyle = computed(() => ({ height: toRpx(props.height) }))
const scrimColor = computed(() => `rgba(24, 22, 20, ${props.overlay})`)

const orbGoldStyle = computed(() => ({
    display: props.glow ? 'block' : 'none',
    width: '300rpx',
    height: '300rpx',
    right: '-80rpx',
    top: '48rpx',
    background: 'rgba(217, 190, 130, 0.11)'
}))

const orbLightStyle = computed(() => ({
    display: props.glow ? 'block' : 'none',
    width: '172rpx',
    height: '172rpx',
    right: '28rpx',
    top: '236rpx',
    background: 'rgba(255, 253, 248, 0.05)'
}))

const orbWarmStyle = computed(() => ({
    display: props.glow ? 'block' : 'none',
    width: '192rpx',
    height: '192rpx',
    left: '-52rpx',
    bottom: '-40rpx',
    background: 'rgba(198, 161, 91, 0.08)'
}))
</script>

<style lang="scss" scoped>
.base-hero {
    position: relative;
    width: 100%;
    overflow: hidden;
    background: var(--wm-color-primary, #181614);

    &__image {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
    }

    &__scrim {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
    }

    &__orb {
        position: absolute;
        border-radius: 50%;
    }

    &__nav {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        z-index: 2;
    }

    &__content {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 2;
        padding: 0 40rpx 52rpx;
        box-sizing: border-box;
    }
}
</style>
