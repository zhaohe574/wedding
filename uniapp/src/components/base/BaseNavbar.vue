<template>
    <view v-if="showSpacer" class="base-navbar-spacer" :style="spacerStyle"></view>
    <view
        class="base-navbar-wrapper"
        :class="wrapperClass"
    >
        <view class="base-navbar" :style="navbarStyle">
            <view
                class="base-navbar__status"
                :style="{ height: `${navBarMetrics.statusBarHeight}px` }"
            ></view>
            <view class="base-navbar__bar" :style="{ height: `${navBarMetrics.contentHeight}px` }">
                <view class="base-navbar__side" :style="sideStyle">
                    <slot v-if="hasLeftSlot" name="left" />
                    <view v-else-if="resolvedBack" class="base-navbar__back" @click="handleBack">
                        <text class="base-navbar__back-icon" :style="backTextStyle">‹</text>
                        <text class="base-navbar__back-text" :style="backTextStyle">返回</text>
                    </view>
                </view>

                <text class="base-navbar__title" :class="titleClass" :style="titleStyle">{{
                    title
                }}</text>

                <view class="base-navbar__side base-navbar__side--placeholder" :style="sideStyle">
                    <slot v-if="hasRightSlot" name="right" />
                    <text v-else-if="resolvedBack" class="base-navbar__placeholder">‹ 返回</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance, useSlots } from 'vue'
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'
import { useThemeStore } from '@/stores/theme'

interface Props {
    title?: string
    back?: boolean
    backIcon?: boolean
    fixed?: boolean
    reserveSpace?: boolean
    transparent?: boolean
    bgColor?: string
    textColor?: string
    variant?: 'solid' | 'glass' | 'transparent'
    titleAlign?: 'center' | 'left'
}

const props = withDefaults(defineProps<Props>(), {
    title: '',
    back: undefined,
    backIcon: true,
    fixed: true,
    reserveSpace: true,
    transparent: false,
    bgColor: '',
    textColor: '',
    variant: 'solid',
    titleAlign: 'center'
})

const emit = defineEmits<{
    (event: 'back'): void
}>()

const instance = getCurrentInstance()
const slots = useSlots()
const themeStore = useThemeStore()
const navBarMetrics = useNavBarMetrics()

const resolvedBack = computed(() => {
    return props.back === undefined ? props.backIcon : props.back
})

const hasLeftSlot = computed(() => Boolean(slots.left))
const hasRightSlot = computed(() => Boolean(slots.right))
const resolvedBgColor = computed(() =>
    props.transparent ? 'transparent' : props.bgColor || themeStore.navBgColor || '#000000'
)
const resolvedTextColor = computed(() => props.textColor || themeStore.navColor || '#FFFFFF')
const resolvedVariant = computed(() => (props.transparent ? 'transparent' : props.variant))
const wrapperClass = computed(() => ({
    'base-navbar-wrapper--fixed': props.fixed,
    'base-navbar-wrapper--transparent': props.transparent,
    [`base-navbar-wrapper--${resolvedVariant.value}`]: true
}))
const showSpacer = computed(() => props.fixed && props.reserveSpace)
const spacerStyle = computed(() => ({
    height: `${navBarMetrics.navBarHeight}px`
}))
const sideStyle = computed(() => ({
    width: `${navBarMetrics.safeInset}px`
}))
const titleStyle = computed(() => ({
    color: resolvedTextColor.value
}))
const titleClass = computed(() => ({
    'base-navbar__title--left': props.titleAlign === 'left'
}))
const backTextStyle = computed(() => ({
    color: resolvedTextColor.value
}))
const hasBackListener = computed(() => Boolean(instance?.vnode.props?.onBack))
const navbarStyle = computed(() => ({
    background: resolvedBgColor.value
}))

const handleBack = () => {
    emit('back')

    if (hasBackListener.value) {
        return
    }

    if (getCurrentPages().length > 1) {
        uni.navigateBack()
        return
    }

    uni.switchTab({
        url: '/pages/index/index'
    })
}
</script>

<script lang="ts">
export default {
    name: 'BaseNavbar',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-navbar-spacer {
    width: 100%;
    flex-shrink: 0;
}

.base-navbar-wrapper {
    position: relative;
    z-index: 60;
    width: 100%;

    &--fixed {
        .base-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 60;
        }
    }

    &--transparent {
        .base-navbar {
            background: transparent;
        }
    }
}

.base-navbar {
    width: 100%;
    background: var(--wm-nav-bg, #000000);
    box-sizing: border-box;
    border-bottom: 1rpx solid rgba(255, 255, 255, 0.08);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}

.base-navbar-wrapper--solid .base-navbar {
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}

.base-navbar-wrapper--transparent .base-navbar {
    border-bottom-color: transparent;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}

.base-navbar__status {
    width: 100%;
}

.base-navbar__bar {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0 var(--wm-space-page-x, 37rpx);
    box-sizing: border-box;
}

.base-navbar__side {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    height: 100%;

    &--placeholder {
        justify-content: flex-end;
    }
}

.base-navbar__title {
    flex: 1;
    min-width: 0;
    padding: 0 var(--wm-space-3, 22rpx);
    text-align: center;
    font-size: 36rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-nav-text, #ffffff);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.base-navbar__title--left {
    text-align: left;
}

.base-navbar__back {
    min-height: 88rpx;
    min-width: 88rpx;
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    padding: 0 var(--wm-space-3, 22rpx) 0 0;
}

.base-navbar__back-icon {
    font-size: 42rpx;
    font-weight: 500;
    line-height: 1;
}

.base-navbar__back-text,
.base-navbar__placeholder {
    font-size: 26rpx;
    font-weight: 600;
    line-height: 1;
}

.base-navbar__back-text {
    color: var(--wm-nav-text, #ffffff);
}

.base-navbar__placeholder {
    color: transparent;
}

/* #ifdef MP-WEIXIN */
.base-navbar {
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
/* #endif */
</style>
