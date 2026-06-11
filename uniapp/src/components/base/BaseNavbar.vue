<template>
    <view v-if="showSpacer" class="base-navbar-spacer" :style="spacerStyle"></view>
    <view class="base-navbar-wrapper" :class="wrapperClass">
        <view class="base-navbar" :style="navbarStyle">
            <view class="base-navbar__status" :style="{ height: `${navBarMetrics.statusBarHeight}px` }"></view>
            <view class="base-navbar__bar" :style="{ height: `${navBarMetrics.contentHeight}px` }">
                <view class="base-navbar__side base-navbar__side--left" :style="sideStyle">
                    <slot v-if="hasLeftSlot" name="left" />
                    <view v-else-if="resolvedBack" class="base-navbar__back" @click="handleBack">
                        <BaseIcon name="left" size="34" :color="resolvedTextColor" />
                    </view>
                </view>

                <text class="base-navbar__title" :class="titleClass" :style="titleStyle">
                    {{ title }}
                </text>

                <view class="base-navbar__right" :style="rightAreaStyle">
                    <view class="base-navbar__right-content">
                        <slot v-if="hasRightSlot" name="right" />
                        <template v-else-if="safeActions.length">
                            <view
                                v-for="action in safeActions"
                                :key="action.name"
                                class="base-navbar__action"
                                @click="emit('action', action.name)"
                            >
                                <BaseIcon v-if="action.icon" :name="action.icon" size="30" :color="resolvedTextColor" />
                            </view>
                        </template>
                    </view>
                    <view class="base-navbar__capsule-safe" :style="capsuleSafeStyle"></view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance, useSlots } from 'vue'
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'
import { useThemeStore } from '@/stores/theme'
import BaseIcon from './BaseIcon.vue'

interface NavAction {
    name: string
    icon: string
}

interface Props {
    title?: string
    back?: boolean
    backIcon?: boolean
    fixed?: boolean
    reserveSpace?: boolean
    transparent?: boolean
    bgColor?: string
    textColor?: string
    variant?: 'solid' | 'glass' | 'transparent' | 'light'
    titleAlign?: 'center' | 'left'
    actions?: NavAction[]
    /** @deprecated 顶部状态栏仅保留系统安全占位，不再渲染模拟内容。 */
    showStatusContent?: boolean
    /** @deprecated 顶部状态栏仅保留系统安全占位，不再渲染模拟内容。 */
    statusTime?: string
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
    variant: 'light',
    titleAlign: 'center',
    actions: () => [],
    showStatusContent: false,
    statusTime: '9:41'
})

const emit = defineEmits<{
    (event: 'back'): void
    (event: 'action', name: string): void
}>()

const instance = getCurrentInstance()
const slots = useSlots()
const themeStore = useThemeStore()
const navBarMetrics = useNavBarMetrics()

const resolvedBack = computed(() => (props.back === undefined ? props.backIcon : props.back))
const hasLeftSlot = computed(() => Boolean(slots.left))
const hasRightSlot = computed(() => Boolean(slots.right))
const resolvedVariant = computed(() => (props.transparent ? 'transparent' : props.variant))
const isDark = computed(() => resolvedVariant.value === 'solid')
const resolvedBgColor = computed(() => {
    if (props.transparent) return 'transparent'
    if (props.bgColor) return props.bgColor
    if (resolvedVariant.value === 'solid') return themeStore.navBgColor || '#1A1A1A'
    if (resolvedVariant.value === 'glass') return 'rgba(255, 253, 248, 0.92)'
    return 'var(--wm-color-bg-card, #FFFDF8)'
})
const resolvedTextColor = computed(() => {
    if (props.textColor) return props.textColor
    return isDark.value ? 'var(--wm-text-inverse, #FFFDF8)' : 'var(--wm-text-primary, #1A1A1A)'
})
const safeActions = computed(() =>
    props.actions
        .map((action) => ({
            ...action,
            icon: typeof action.icon === 'string' ? action.icon.trim() : ''
        }))
        .filter((action) => action.name)
)
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
const capsuleSafeStyle = computed(() => ({
    width: `${navBarMetrics.safeInset}px`
}))
const rightAreaStyle = computed(() => ({
    minWidth: `${navBarMetrics.safeInset}px`
}))
const titleStyle = computed(() => ({
    color: resolvedTextColor.value
}))
const titleClass = computed(() => ({
    'base-navbar__title--left': props.titleAlign === 'left'
}))
const navbarStyle = computed(() => ({
    background: resolvedBgColor.value,
    color: resolvedTextColor.value
}))
const hasBackListener = computed(() => Boolean(instance?.vnode.props?.onBack))

const handleBack = () => {
    emit('back')
    if (hasBackListener.value) return
    if (getCurrentPages().length > 1) {
        uni.navigateBack()
        return
    }
    uni.switchTab({ url: '/pages/index/index' })
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

    &--fixed .base-navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 60;
    }
}

.base-navbar {
    width: 100%;
    box-sizing: border-box;
    border-bottom: 1rpx solid rgba(227, 215, 201, 0.84);
    box-shadow: 0 10rpx 28rpx rgba(74, 43, 24, 0.08);
}

.base-navbar-wrapper--solid .base-navbar {
    border-bottom-color: var(--wm-color-champagne, #E9C7A7);
    box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
}

.base-navbar-wrapper--transparent .base-navbar {
    border-bottom-color: transparent;
    box-shadow: none;
}

.base-navbar__status {
    width: 100%;
}

.base-navbar__bar {
    display: flex;
    align-items: center;
    width: 100%;
    padding-left: var(--wm-space-page-x, 32rpx);
    padding-right: 0;
    box-sizing: border-box;
}

.base-navbar__side {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    height: 100%;
    gap: 12rpx;
}

.base-navbar__side--left {
    justify-content: flex-start;
}

.base-navbar__right {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    height: 100%;
}

.base-navbar__right-content {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12rpx;
    min-width: 0;
}

.base-navbar__capsule-safe {
    flex-shrink: 0;
    height: 100%;
}

.base-navbar__back,
.base-navbar__action {
    width: 64rpx;
    height: 64rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
}

.base-navbar__back:active,
.base-navbar__action:active {
    background: rgba(212, 145, 110, 0.12);
}

.base-navbar__title {
    flex: 1;
    min-width: 0;
    padding: 0 var(--wm-space-3, 24rpx);
    text-align: center;
    font-size: 32rpx;
    font-weight: 900;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.base-navbar__title--left {
    text-align: left;
}
</style>
