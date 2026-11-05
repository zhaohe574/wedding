<template>
    <view class="nav" :class="{ 'nav--transparent': transparent, 'nav--fixed': fixed }" :style="navStyle">
        <view :style="{ height: statusBarHeight + 'px' }" />
        <view class="nav__bar">
            <view hover-class="is-pressed" class="nav__left" @tap="onBack">
                <u-icon v-if="showBack" name="arrow-left" :size="32" :color="iconColor" />
            </view>
            <text class="nav__title" :style="{ color: titleColor }">{{ title }}</text>
            <view class="nav__right">
                <slot name="right" />
            </view>
        </view>
    </view>
    <!-- 占位，避免内容被 fixed 导航遮挡 -->
    <view v-if="fixed && !transparent" :style="{ height: totalHeight + 'px' }" />
</template>

<script setup lang="ts">
/**
 * 自定义导航栏。
 * 小程序 navigationStyle=custom 时没有系统导航，必须自己处理状态栏高度，
 * 否则内容会顶到刘海下面。
 */
import { computed } from 'vue'
import { getDeviceInfo } from '@/platform/device'

const props = withDefaults(
    defineProps<{
        title?: string
        transparent?: boolean
        fixed?: boolean
        showBack?: boolean
        titleColor?: string
        iconColor?: string
    }>(),
    {
        title: '',
        transparent: false,
        fixed: true,
        showBack: true,
        titleColor: 'var(--c-ink)',
        iconColor: 'var(--c-ink)'
    }
)

const { statusBarHeight } = getDeviceInfo()
const BAR_HEIGHT = 44
const totalHeight = computed(() => statusBarHeight + BAR_HEIGHT)

const navStyle = computed(() => ({
    backgroundColor: props.transparent ? 'transparent' : 'var(--c-surface)',
    borderBottom: props.transparent ? 'none' : '1rpx solid var(--c-line)'
}))

function onBack() {
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}
</script>

<style lang="scss" scoped>
.nav {
    width: 100%;
    z-index: 99;

    &--fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
    }

    &__bar {
        height: 44px;
        display: flex;
        align-items: center;
        padding: 0 var(--sp-3);
    }

    &__left,
    &__right {
        width: 80rpx;
        display: flex;
        align-items: center;
    }

    &__right {
        justify-content: flex-end;
    }

    &__title {
        flex: 1;
        text-align: center;
        font-size: var(--fs-lg);
        font-weight: 500;
    }
}
</style>
