<template>
    <view
        class="nav"
        :class="{ 'nav--transparent': transparent, 'nav--fixed': fixed, 'nav--scroll-solid': isScrollSolid }"
        :style="navStyle"
    >
        <view :style="{ height: statusBarHeight + 'px' }" />
        <view class="nav__bar">
            <view hover-class="is-pressed" class="nav__left nav__back-hit" aria-role="button" aria-label="返回" @tap="onBack">
                <u-icon v-if="showBack" name="arrow-left" :size="32" :color="iconColor" />
            </view>
            <text class="nav__title" :style="{ color: titleColor }">{{ title }}</text>
            <view class="nav__right" :class="{ 'nav__right--capsule-avoid': capsuleAvoid }">
                <slot name="right" />
            </view>
        </view>
    </view>
    <!-- 占位，避免内容被 fixed 导航遮挡（fixed && !transparent 时保持） -->
    <view v-if="fixed && !transparent" :style="{ height: totalHeight + 'px' }" />
</template>

<script setup lang="ts">
/**
 * 自定义导航栏（N6 状态契约并入本组件，非新组件）。
 * 小程序 navigationStyle=custom 时没有系统导航，必须自己处理状态栏高度，
 * 否则内容会顶到刘海下面。
 *
 * 契约边界：
 *  - scrollTop 由页面 @scroll 提供，组件不监听全局滚动、不感知 pageStatus、不重写返回链路。
 *  - capsuleAvoid 启用时右侧 slot 避让微信右上角胶囊（--nav-capsule-reserve）。
 *  - H5 navigationStyle:custom 叠加冲突（O16）不在组件内擅自修，交 T-39 真机书面结论。
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
        /** transparent / solid / scroll-solid（滚动切实底由页面传 scrollTop 驱动） */
        variant?: 'transparent' | 'solid' | 'scroll-solid'
        /** 右侧动作避让微信胶囊；更稳妥的默认做法是不下发右侧 slot、动作下沉内容区 */
        capsuleAvoid?: boolean
        /** 页面 @scroll 的滚动距离（px），组件不自行监听 */
        scrollTop?: number
    }>(),
    {
        title: '',
        transparent: false,
        fixed: true,
        showBack: true,
        titleColor: 'var(--c-ink)',
        iconColor: 'var(--c-ink)',
        variant: 'transparent',
        capsuleAvoid: false,
        scrollTop: 0
    }
)

const { statusBarHeight } = getDeviceInfo()
const BAR_HEIGHT = 44
const totalHeight = computed(() => statusBarHeight + BAR_HEIGHT)

/** scroll-solid 变体：滚动超过 8px 切切实底，240ms 过渡 */
const isScrollSolid = computed(() => props.variant === 'scroll-solid' && (props.scrollTop ?? 0) > 8)

const navStyle = computed(() => {
    const solid = props.variant === 'solid' || isScrollSolid.value
    // scroll-solid 未达阈值前保持透明；solid 始终实底；其余沿用 transparent prop
    const isTransparent = (props.transparent || props.variant === 'scroll-solid') && !solid
    return {
        backgroundColor: isTransparent ? 'transparent' : 'var(--c-surface)',
        borderBottom: isTransparent ? 'none' : '1rpx solid var(--c-line)'
    }
})

function onBack() {
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}
</script>

<style lang="scss" scoped>
.nav {
    width: 100%;
    z-index: 99;   /* 必须 > .chip-bar 的 50（体验规范 §2.5） */

    &--fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
    }

    /* 滚动切实底（240ms / ease-out） */
    &--scroll-solid {
        transition: background-color var(--dur-base) var(--ease-out),
                    border-color var(--dur-base) var(--ease-out);
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

        /* 胶囊避让契约：仅当页面存在右上角胶囊且确实下发右侧 slot 时启用 */
        &--capsule-avoid {
            margin-right: var(--nav-capsule-reserve);
        }
    }

    /* 纯图标返回 88rpx 热区 */
    &__back-hit {
        min-width: 88rpx;
        min-height: 88rpx;
        justify-content: flex-start;
    }

    &__title {
        flex: 1;
        text-align: center;
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);   /* 500 → 600，H3（视觉规范 §3.1） */
        line-height: var(--lh-tight);
    }
}
</style>
