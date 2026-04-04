<template>
    <view v-if="showSpacer" class="base-navbar-spacer" :style="spacerStyle"></view>
    <view
        class="base-navbar-wrapper"
        :class="{
            'base-navbar-wrapper--fixed': fixed,
            'base-navbar-wrapper--transparent': transparent
        }"
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
                        <text class="base-navbar__back-text" :style="backTextStyle">‹ 返回</text>
                    </view>
                </view>

                <text class="base-navbar__title" :style="titleStyle">{{ title }}</text>

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
}

const props = withDefaults(defineProps<Props>(), {
    title: '',
    back: undefined,
    backIcon: true,
    fixed: true,
    reserveSpace: true,
    transparent: false,
    bgColor: '',
    textColor: ''
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
    props.transparent ? 'transparent' : props.bgColor || themeStore.navBgColor || '#FCFBF9'
)
const resolvedTextColor = computed(() => props.textColor || themeStore.navColor)
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
const backTextStyle = computed(() => ({
    color: props.textColor || themeStore.primaryColor
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
    background: var(--wm-nav-bg, #fcfbf9);
    box-sizing: border-box;
}

.base-navbar__status {
    width: 100%;
}

.base-navbar__bar {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0 var(--wm-space-6, 45rpx);
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
    font-size: 40rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-primary, #1e2432);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.base-navbar__back {
    min-height: 82rpx;
    display: inline-flex;
    align-items: center;
    padding: 0 var(--wm-space-3, 22rpx) 0 0;
}

.base-navbar__back-text,
.base-navbar__placeholder {
    font-size: 30rpx;
    font-weight: 600;
    line-height: 1;
}

.base-navbar__back-text {
    color: var(--wm-color-primary, #e85a4f);
}

.base-navbar__placeholder {
    color: transparent;
}
</style>
