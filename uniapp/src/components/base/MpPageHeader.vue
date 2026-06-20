<template>
    <view v-if="showFixedSpacer" class="mp-page-header-spacer" :style="spacerStyle"></view>
    <view class="mp-page-header" :class="headerClass">
        <view class="mp-page-header__status" :style="{ height: `${navBarMetrics.statusBarHeight}px` }"></view>
        <view class="mp-page-header__body" :class="bodyClass" :style="{ height: `${navBarMetrics.contentHeight}px` }">
            <view class="mp-page-header__side" :style="leftSideStyle">
                <view v-if="$slots.left" class="mp-page-header__side-content">
                    <slot name="left" />
                </view>
            </view>
            <view class="mp-page-header__title" :class="titleClass">
                <image v-if="showTitleImage" class="mp-page-header__title-image" :src="titleImage" mode="heightFix"></image>
                <text v-else class="mp-page-header__title-text" :class="titleTextClass" :style="{ color: titleTextColor }">
                    {{ resolvedTitle }}
                </text>
            </view>
            <view class="mp-page-header__right" :style="rightAreaStyle">
                <view class="mp-page-header__right-content">
                    <slot name="right" />
                </view>
                <view class="mp-page-header__capsule-safe" :style="capsuleSafeStyle"></view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, useSlots } from 'vue'
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'

interface Props {
    title?: string
    titleImage?: string
    sticky?: boolean
    fixed?: boolean
    reserveSpace?: boolean
    surface?: 'overlay' | 'glass' | 'dark' | 'light'
    titleAlign?: 'center' | 'left'
    titleSize?: 'default' | 'large'
}

const props = withDefaults(defineProps<Props>(), {
    title: '',
    titleImage: '',
    sticky: true,
    fixed: false,
    reserveSpace: true,
    surface: 'dark',
    titleAlign: 'center',
    titleSize: 'default'
})

const navBarMetrics = useNavBarMetrics()
const slots = useSlots()

const showTitleImage = computed(() => typeof props.titleImage === 'string' && props.titleImage.trim().length > 0)
const resolvedTitle = computed(() => (typeof props.title === 'string' && props.title.trim() ? props.title : ''))
const titleTextColor = computed(() =>
    props.surface === 'dark' || props.surface === 'overlay'
        ? 'var(--wm-nav-text, #FFFDF8)'
        : 'var(--wm-text-primary, #191713)'
)
const headerClass = computed(() => [
    `mp-page-header--${props.surface}`,
    {
        'mp-page-header--fixed': props.fixed,
        'mp-page-header--sticky': !props.fixed && props.sticky
    }
])
const bodyClass = computed(() => ({
    'mp-page-header__body--left': props.titleAlign === 'left'
}))
const titleClass = computed(() => `mp-page-header__title--${props.titleAlign}`)
const titleTextClass = computed(() => `mp-page-header__title-text--${props.titleSize}`)
const capsuleSafeStyle = computed(() => ({ width: `${navBarMetrics.safeInset}px` }))
const rightAreaStyle = computed(() => ({ minWidth: `${navBarMetrics.safeInset}px` }))
const leftSideStyle = computed(() => ({
    width: `${props.titleAlign === 'left' && !slots.left ? 0 : navBarMetrics.safeInset}px`
}))
const showFixedSpacer = computed(() => props.fixed && props.reserveSpace)
const spacerStyle = computed(() => ({
    height: `${navBarMetrics.navBarHeight}px`
}))
</script>

<script lang="ts">
export default {
    name: 'MpPageHeader',
    options: {
        virtualHost: true
    }
}
</script>

<style scoped lang="scss">
.mp-page-header-spacer {
    width: 100%;
    flex-shrink: 0;
}

.mp-page-header {
    position: relative;
    width: 100%;
    z-index: 20;

    &--fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 80;
    }

    &--sticky {
        position: sticky;
        top: 0;
    }

    &--overlay,
    &--dark {
        color: var(--wm-nav-text, #FFFDF8);
        background: var(--wm-nav-bg, #000000);
        border-bottom: 1rpx solid var(--wm-nav-border, var(--wm-color-champagne, #D9BE82));
        box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
    }

    &--glass,
    &--light {
        background: rgba(255, 253, 248, 0.94);
        border-bottom: 1rpx solid rgba(227, 215, 201, 0.86);
        box-shadow: 0 10rpx 28rpx rgba(74, 43, 24, 0.08);
    }

    &__body {
        display: flex;
        align-items: center;
        width: 100%;
        padding-left: var(--wm-space-page-x, 32rpx);
        padding-right: 0;
        box-sizing: border-box;
    }

    &__side {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        height: 100%;
    }

    &__right {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        height: 100%;
    }

    &__right-content {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        min-width: 0;
    }

    &__capsule-safe {
        flex-shrink: 0;
        height: 100%;
    }

    &__title {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        padding: 0 12rpx;
        overflow: hidden;
    }

    &__title--center {
        justify-content: center;
    }

    &__title--left {
        justify-content: flex-start;
        padding-left: 0;
    }

    &__title-image {
        max-width: 320rpx;
        height: 54rpx;
    }

    &__title-text {
        max-width: 100%;
        font-size: 32rpx;
        font-weight: 900;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__title-text--large {
        font-size: 42rpx;
        font-weight: 900;
        letter-spacing: 0;
    }
}
</style>
