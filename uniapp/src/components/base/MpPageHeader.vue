<template>
    <view class="mp-page-header" :class="headerClass">
        <view
            class="mp-page-header__status"
            :style="{ height: `${navBarMetrics.statusBarHeight}px` }"
        ></view>
        <view
            class="mp-page-header__body"
            :class="bodyClass"
            :style="{ height: `${navBarMetrics.contentHeight}px` }"
        >
            <view class="mp-page-header__side" :style="leftSideStyle">
                <view v-if="$slots.left" class="mp-page-header__side-content">
                    <slot name="left" />
                </view>
            </view>
            <view class="mp-page-header__title" :class="titleClass">
                <image
                    v-if="showTitleImage"
                    class="mp-page-header__title-image"
                    :src="titleImage"
                    mode="heightFix"
                ></image>
                <text
                    v-else
                    class="mp-page-header__title-text"
                    :class="titleTextClass"
                    :style="{ color: titleTextColor }"
                >
                    {{ resolvedTitle }}
                </text>
            </view>
            <view
                class="mp-page-header__side mp-page-header__side--placeholder"
                :style="sideSlotStyle"
            ></view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, useSlots } from 'vue'
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'
import { useThemeStore } from '@/stores/theme'

interface Props {
    title?: string
    titleImage?: string
    sticky?: boolean
    surface?: 'overlay' | 'glass'
    titleAlign?: 'center' | 'left'
    titleSize?: 'default' | 'large'
}

const props = withDefaults(defineProps<Props>(), {
    title: '',
    titleImage: '',
    sticky: true,
    surface: 'overlay',
    titleAlign: 'center',
    titleSize: 'default'
})

const navBarMetrics = useNavBarMetrics()
const themeStore = useThemeStore()
const slots = useSlots()

const showTitleImage = computed(() => {
    return typeof props.titleImage === 'string' && props.titleImage.trim().length > 0
})

const resolvedTitle = computed(() => {
    return typeof props.title === 'string' && props.title.trim() ? props.title : ''
})
const titleTextColor = computed(() => themeStore.navColor || '#1E2432')
const headerClass = computed(() => [
    `mp-page-header--${props.surface}`,
    {
        'mp-page-header--sticky': props.sticky
    }
])
const bodyClass = computed(() => ({
    'mp-page-header__body--left': props.titleAlign === 'left'
}))
const titleClass = computed(() => `mp-page-header__title--${props.titleAlign}`)
const titleTextClass = computed(() => `mp-page-header__title-text--${props.titleSize}`)

const sideSlotStyle = computed(() => ({
    width: `${navBarMetrics.safeInset}px`
}))
const leftSideStyle = computed(() => ({
    width: `${props.titleAlign === 'left' && !slots.left ? 0 : navBarMetrics.safeInset}px`
}))
</script>

<style scoped lang="scss">
.mp-page-header {
    position: relative;
    width: 100%;
    z-index: 20;

    &--sticky {
        position: sticky;
        top: 0;
    }

    &--overlay,
    &--glass {
        background: linear-gradient(
            180deg,
            rgba(255, 247, 244, 0.96) 0%,
            rgba(255, 247, 244, 0.78) 76%,
            rgba(255, 247, 244, 0) 100%
        );
        backdrop-filter: blur(18rpx);
        -webkit-backdrop-filter: blur(18rpx);
    }

    &--glass {
        border-bottom: 1rpx solid rgba(239, 230, 225, 0.56);
        box-shadow: 0 14rpx 32rpx rgba(214, 185, 167, 0.08);
    }

    &__body {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 0 var(--wm-space-page-x, 37rpx);
    }

    &__body--left {
        padding-left: var(--wm-space-page-x, 37rpx);
        padding-right: var(--wm-space-page-x, 37rpx);
    }

    &__side {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        height: 100%;
    }

    &__side-content {
        display: inline-flex;
        align-items: center;
    }

    &__side--placeholder {
        justify-content: flex-end;
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
        font-size: 36rpx;
        font-weight: 700;
        line-height: 1.2;
        color: #1e2432;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__title-text--large {
        font-size: 42rpx;
        font-weight: 800;
        letter-spacing: 1rpx;
    }
}
</style>
